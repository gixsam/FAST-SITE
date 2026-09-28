<?php
// =========================================================================
// api/customer_confirm.php  –  Customer Confirm Delivery & Release Escrow API
// Layer 1 of Escrow Release Engine
// =========================================================================
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/escrow_engine.php';

$user_id = $_SESSION['user_id'];
$order_id = trim($_POST['order_id'] ?? '');

if (empty($order_id)) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID.']);
    exit;
}

try {
    // 1. Trigger escrow engine buyer confirm
    $res = escrow_buyer_confirm_release($pdo, $order_id, $user_id);

    if ($res['success']) {
        // Also update legacy partner_orders table if exists
        $upd = $pdo->prepare("UPDATE partner_orders SET status = 'completed', customer_confirmed_at = NOW() WHERE id = ? OR tracking_code = ?");
        $upd->execute([$order_id, $order_id]);

        echo json_encode(['success' => true, 'message' => 'Delivery confirmed! Escrow funds released to seller wallet.']);
    } else {
        echo json_encode(['success' => false, 'message' => $res['message']]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
