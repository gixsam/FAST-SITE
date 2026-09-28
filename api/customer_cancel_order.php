<?php
header('Content-Type: application/json');

session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Please login.']);
    exit;
}

require_once __DIR__ . '/../config.php';
$userId = $_SESSION['user_id'];

$data = json_decode(file_get_contents('php://input'), true);
$orderId = intval($data['order_id'] ?? 0);

if ($orderId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid order identifier.']);
    exit;
}

try {
    // 1. Fetch order details
    $stmt = $pdo->prepare("SELECT * FROM partner_orders WHERE id = :oid LIMIT 1");
    $stmt->execute([':oid' => $orderId]);
    $order = $stmt->fetch();

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order record not found.']);
        exit;
    }

    if (intval($order['customer_id']) !== $userId) {
        echo json_encode(['success' => false, 'message' => 'You do not own this order.']);
        exit;
    }

    if ($order['status'] !== 'pending') {
        echo json_encode(['success' => false, 'message' => 'This order cannot be cancelled as it has already been accepted or processed.']);
        exit;
    }

    // 2. Check cooling-off period
    $cooling_hours = intval(getPartnerSetting('cooling_off_hours', '24'));
    $order_time = strtotime($order['created_at']);
    $time_diff_hours = (time() - $order_time) / 3600;

    if ($time_diff_hours > $cooling_hours) {
        echo json_encode(['success' => false, 'message' => "Cancellation deadline has passed. Orders can only be cancelled within $cooling_hours hours of placement."]);
        exit;
    }

    // 3. Process Cancellation and Refund
    $pdo->beginTransaction();

    // 3.1 Update order status
    $pdo->prepare("UPDATE partner_orders SET status = 'cancelled', cancelled_at = :now WHERE id = :oid")
        ->execute([
            ':now' => date('Y-m-d H:i:s'),
            ':oid' => $orderId
        ]);

    // 3.2 Refund points to customer wallet
    $pdo->prepare("UPDATE coin_wallets SET balance = balance + :coins WHERE user_id = :uid")
        ->execute([
            ':coins' => $order['total_coins'],
            ':uid'   => $userId
        ]);

    // 3.3 Log transaction
    $pdo->prepare("INSERT INTO coin_transactions 
        (user_id, type, amount, reference, status) 
        VALUES (:uid, 'refund', :coins, :ref, 'completed')")
        ->execute([
            ':uid'   => $userId,
            ':coins' => $order['total_coins'],
            ':ref'   => "Refund for Cancelled Order #$orderId"
        ]);

    $pdo->commit();

    echo json_encode([
        'success' => true, 
        'message' => 'Order cancelled successfully and points refunded to your wallet balance.'
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        'success' => false, 
        'message' => 'Internal server error occurred: ' . $e->getMessage()
    ]);
}
?>
