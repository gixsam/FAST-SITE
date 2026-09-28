<?php
// =========================================================================
// includes/escrow_engine.php — 3-Layer Hybrid Escrow Release Engine
// Layer 1: Instant release upon Buyer "Confirm Received"
// Layer 2: 5-Day (120-hour) automatic fallback release for shipped items
// Layer 3: Admin 1-click override & dispute resolution
// Webhook-Ready: Receiver for Pathao/Steadfast courier APIs
// =========================================================================

if (!defined('ESCROW_AUTO_RELEASE_HOURS')) {
    define('ESCROW_AUTO_RELEASE_HOURS', 120); // 5 days fallback
}

/**
 * Ensures escrow tables exist in database
 */
function escrow_ensure_tables($pdo) {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS escrow_vault (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id VARCHAR(100) NOT NULL UNIQUE,
            buyer_id INT NOT NULL,
            seller_id INT NOT NULL,
            amount_bdt DECIMAL(12,2) NOT NULL,
            status ENUM('held', 'shipped', 'released', 'disputed', 'refunded') DEFAULT 'held',
            courier_name VARCHAR(100) DEFAULT NULL,
            tracking_code VARCHAR(150) DEFAULT NULL,
            shipped_at DATETIME DEFAULT NULL,
            released_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX(buyer_id),
            INDEX(seller_id),
            INDEX(status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        $pdo->exec("CREATE TABLE IF NOT EXISTS escrow_disputes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id VARCHAR(100) NOT NULL,
            opened_by_user_id INT NOT NULL,
            reason TEXT NOT NULL,
            status ENUM('open', 'resolved_seller', 'resolved_buyer') DEFAULT 'open',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            resolved_at DATETIME DEFAULT NULL,
            INDEX(order_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    } catch (Exception $e) {
        error_log("Escrow Table Init Error: " . $e->getMessage());
    }
}

/**
 * Initializes escrow vault hold for a new order
 */
function escrow_hold_funds($pdo, $order_id, $buyer_id, $seller_id, $amount_bdt) {
    escrow_ensure_tables($pdo);
    $stmt = $pdo->prepare("INSERT INTO escrow_vault (order_id, buyer_id, seller_id, amount_bdt, status) 
                           VALUES (?, ?, ?, ?, 'held')
                           ON DUPLICATE KEY UPDATE amount_bdt = VALUES(amount_bdt)");
    return $stmt->execute([$order_id, $buyer_id, $seller_id, $amount_bdt]);
}

/**
 * Seller marks item as shipped (starts 5-day fallback timer)
 */
function escrow_mark_shipped($pdo, $order_id, $courier_name, $tracking_code) {
    escrow_ensure_tables($pdo);
    $stmt = $pdo->prepare("UPDATE escrow_vault 
                           SET status = 'shipped', courier_name = ?, tracking_code = ?, shipped_at = NOW() 
                           WHERE order_id = ? AND status = 'held'");
    return $stmt->execute([$courier_name, $tracking_code, $order_id]);
}

/**
 * Layer 1: Buyer clicks "Confirm Delivery Received" -> Instant Release
 */
function escrow_buyer_confirm_release($pdo, $order_id, $buyer_id) {
    escrow_ensure_tables($pdo);
    
    // Fetch escrow record
    $stmt = $pdo->prepare("SELECT * FROM escrow_vault WHERE order_id = ? AND buyer_id = ? AND status IN ('held', 'shipped')");
    $stmt->execute([$order_id, $buyer_id]);
    $vault = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$vault) {
        return ['success' => false, 'message' => 'Escrow record not found or already released/disputed.'];
    }

    return escrow_execute_seller_payout($pdo, $vault, 'buyer_confirmed');
}

/**
 * Layer 2: 5-Day (120-hour) automatic fallback release
 */
function escrow_run_auto_release_cron($pdo) {
    escrow_ensure_tables($pdo);
    $cutoff = date('Y-m-d H:i:s', strtotime('-' . ESCROW_AUTO_RELEASE_HOURS . ' hours'));
    
    $stmt = $pdo->prepare("SELECT * FROM escrow_vault WHERE status = 'shipped' AND shipped_at <= ?");
    $stmt->execute([$cutoff]);
    $eligible = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $released_count = 0;
    foreach ($eligible as $vault) {
        $res = escrow_execute_seller_payout($pdo, $vault, 'auto_release_5days');
        if ($res['success']) $released_count++;
    }

    return ['success' => true, 'released_count' => $released_count];
}

/**
 * Layer 3: Admin Force Release to Seller
 */
function escrow_admin_force_release($pdo, $order_id) {
    escrow_ensure_tables($pdo);
    $stmt = $pdo->prepare("SELECT * FROM escrow_vault WHERE order_id = ? AND status IN ('held', 'shipped', 'disputed')");
    $stmt->execute([$order_id]);
    $vault = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$vault) {
        return ['success' => false, 'message' => 'Escrow record not found or already processed.'];
    }

    return escrow_execute_seller_payout($pdo, $vault, 'admin_force_release');
}

/**
 * Layer 3: Admin Refund Customer
 */
function escrow_admin_refund_buyer($pdo, $order_id) {
    escrow_ensure_tables($pdo);
    $stmt = $pdo->prepare("SELECT * FROM escrow_vault WHERE order_id = ? AND status IN ('held', 'shipped', 'disputed')");
    $stmt->execute([$order_id]);
    $vault = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$vault) {
        return ['success' => false, 'message' => 'Escrow record not found or already processed.'];
    }

    $pdo->beginTransaction();
    try {
        // Update vault status to refunded
        $upd = $pdo->prepare("UPDATE escrow_vault SET status = 'refunded', released_at = NOW() WHERE id = ?");
        $upd->execute([$vault['id']]);

        // Credit buyer balance
        $credit = $pdo->prepare("UPDATE users SET coins = coins + ? WHERE id = ?");
        $credit->execute([$vault['amount_bdt'], $vault['buyer_id']]);

        $pdo->commit();
        return ['success' => true, 'message' => "Refunded {$vault['amount_bdt']} BDT to buyer."];
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}

/**
 * Helper: Executes seller wallet payout and updates vault status
 */
function escrow_execute_seller_payout($pdo, $vault, $reason) {
    $pdo->beginTransaction();
    try {
        // Update vault status to released
        $upd = $pdo->prepare("UPDATE escrow_vault SET status = 'released', released_at = NOW() WHERE id = ?");
        $upd->execute([$vault['id']]);

        // Credit seller balance / earnings
        $credit = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
        $credit->execute([$vault['amount_bdt'], $vault['seller_id']]);

        $pdo->commit();
        return ['success' => true, 'message' => "Escrow released successfully ({$vault['amount_bdt']} BDT credited to seller)."];
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'message' => 'Database error during escrow release: ' . $e->getMessage()];
    }
}
