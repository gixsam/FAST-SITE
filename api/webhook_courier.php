<?php
// =========================================================================
// api/webhook_courier.php — Webhook Receiver for Pathao, Steadfast, RedX
// Automatically triggers Escrow 72-Hour Timer when status = "Delivered"
// =========================================================================
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/escrow_engine.php';

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true) ?? $_POST;

$courier = trim($data['courier'] ?? $data['provider'] ?? 'generic');
$trackingCode = trim($data['tracking_code'] ?? $data['consignment_id'] ?? $data['tracking_id'] ?? '');
$status = strtolower(trim($data['status'] ?? $data['delivery_status'] ?? ''));

if (empty($trackingCode)) {
    echo json_encode(['success' => false, 'message' => 'Missing tracking code.']);
    exit;
}

try {
    escrow_ensure_tables($pdo);

    if (in_array($status, ['delivered', 'completed', 'successful'])) {
        // Update vault status & set shipped_at to start 72h countdown
        $stmt = $pdo->prepare("UPDATE escrow_vault 
                               SET status = 'shipped', shipped_at = NOW(), courier_name = ? 
                               WHERE tracking_code = ? OR order_id = ?");
        $stmt->execute([$courier, $trackingCode, $trackingCode]);

        echo json_encode([
            'success' => true, 
            'message' => "Courier webhook processed. Status '{$status}' updated. Escrow timer active."
        ]);
    } else if (in_array($status, ['returned', 'cancelled', 'failed'])) {
        $stmt = $pdo->prepare("UPDATE escrow_vault SET status = 'disputed' WHERE tracking_code = ? OR order_id = ?");
        $stmt->execute([$trackingCode, $trackingCode]);

        echo json_encode([
            'success' => true, 
            'message' => "Courier webhook processed. Delivery failed/returned."
        ]);
    } else {
        echo json_encode([
            'success' => true, 
            'message' => "Courier status '{$status}' logged."
        ]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Webhook error: ' . $e->getMessage()]);
}
