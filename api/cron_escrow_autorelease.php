<?php
// =========================================================================
// api/cron_escrow_autorelease.php — Layer 2 Escrow Fallback Auto-Release Cron
// Phase 66: 48-Hour Auto-Release Engine
// =========================================================================
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/escrow_engine.php';

function runEscrowAutoRelease($pdo) {
    $res1 = escrow_run_auto_release_cron($pdo);
    
    $releasedCount = 0;
    try {
        // Phase 66 (Part 2) 48-Hour Auto-Release Engine
        $stmt = $pdo->prepare("
            SELECT o.*, u.id AS partner_user_id 
            FROM partner_orders o
            LEFT JOIN partners p ON o.partner_id = p.id
            LEFT JOIN users u ON p.email = u.email
            WHERE o.status = 'waiting_confirmation'
              AND o.delivered_at IS NOT NULL
              AND o.auto_release_deadline <= CURRENT_TIMESTAMP
              AND NOT EXISTS (
                  SELECT 1 FROM partner_disputes d 
                  WHERE d.order_id = o.id AND d.admin_decision = 'pending'
              )
        ");
        $stmt->execute();
        $eligibleOrders = $stmt->fetchAll();

        foreach ($eligibleOrders as $order) {
            $pdo->beginTransaction();
            
            // 1. Mark as completed
            $pdo->prepare("UPDATE partner_orders SET status = 'completed', customer_confirmed_at = CURRENT_TIMESTAMP WHERE id = ?")
                ->execute([$order['id']]);
                
            // 2. Transfer escrow coins to partners.total_earned and total_orders
            $pdo->prepare("UPDATE partners SET total_earned = total_earned + ?, total_orders = total_orders + 1 WHERE id = ?")
                ->execute([$order['total_coins'], $order['partner_id']]);
                
            // 3. Log transaction & sync to unified wallet
            if ($order['partner_user_id']) {
                $pdo->prepare("UPDATE users SET coins_balance = coins_balance + ? WHERE id = ?")
                    ->execute([$order['total_coins'], $order['partner_user_id']]);
                    
                $pdo->prepare("INSERT INTO coin_transactions (user_id, type, amount, reference, status) VALUES (?, 'deposit', ?, ?, 'completed')")
                    ->execute([$order['partner_user_id'], $order['total_coins'], 'SafePay 48h Auto-Completion for Order #' . $order['id']]);
            }
            
            if (function_exists('updatePartnerSellerLevel')) {
                updatePartnerSellerLevel($order['partner_id'], $pdo);
            }
            
            $pdo->commit();
            $releasedCount++;
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
    }
    
    return [
        'status' => 'success',
        'escrow_vault_released' => $res1['released_count'] ?? 0,
        'orders_auto_released' => $releasedCount,
        'fallback_hours' => 48
    ];
}

// If accessed directly via URL, execute and print JSON
if (basename($_SERVER['PHP_SELF']) === 'cron_escrow_autorelease.php') {
    header('Content-Type: application/json');
    $result = runEscrowAutoRelease($pdo);
    echo json_encode($result, JSON_PRETTY_PRINT);
}
