<?php
// api/subscribe_pro.php
require_once __DIR__ . '/../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$cost = 500.0;

try {
    $pdo->beginTransaction();

    // 1. Verify partner exists
    $stmtP = $pdo->prepare("SELECT id, is_pro, pro_expires_at FROM partners WHERE user_id = ?");
    $stmtP->execute([$user_id]);
    $partner = $stmtP->fetch();

    if (!$partner) {
        throw new Exception("You must have a registered shop to upgrade.");
    }

    // 2. Check coin balance
    $stmtW = $pdo->prepare("SELECT balance FROM coin_wallets WHERE user_id = ? FOR UPDATE");
    $stmtW->execute([$user_id]);
    $wallet = $stmtW->fetch();

    if (!$wallet || $wallet['balance'] < $cost) {
        throw new Exception("Insufficient coin balance. You need $cost coins to upgrade.");
    }

    // 3. Deduct coins
    $new_balance = $wallet['balance'] - $cost;
    $stmtUw = $pdo->prepare("UPDATE coin_wallets SET balance = ? WHERE user_id = ?");
    $stmtUw->execute([$new_balance, $user_id]);

    // 4. Log transaction
    $stmtTx = $pdo->prepare("INSERT INTO coin_transactions (user_id, type, amount, reference, status) VALUES (?, ?, ?, ?, ?)");
    $stmtTx->execute([$user_id, 'pro_subscription', -$cost, 'PRO Upgrade 30 Days', 'completed']);

    // 5. Update partner PRO status
    $new_expiry = date('Y-m-d H:i:s', strtotime('+30 days'));
    if ($partner['is_pro'] && strtotime($partner['pro_expires_at']) > time()) {
        // Extend existing subscription
        $new_expiry = date('Y-m-d H:i:s', strtotime('+30 days', strtotime($partner['pro_expires_at'])));
    }

    $stmtUp = $pdo->prepare("UPDATE partners SET is_pro = 1, pro_expires_at = ? WHERE id = ?");
    $stmtUp->execute([$new_expiry, $partner['id']]);

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Successfully upgraded to PRO!', 'new_balance' => $new_balance]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
