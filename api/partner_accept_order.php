<?php
// =========================================================================
// api/partner_accept_order.php  –  Vendor Accept Order API
// =========================================================================
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['partner_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

require_once __DIR__ . '/../config.php';

$partner_id = $_SESSION['partner_id'];
$order_id = intval($_POST['order_id'] ?? 0);

if ($order_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID.']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE partner_orders 
        SET status = 'accepted' 
        WHERE id = :id AND partner_id = :partner_id AND status = 'pending'");
    $stmt->execute([':id' => $order_id, ':partner_id' => $partner_id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Order accepted successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Order not found, unauthorized, or not pending.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
