<?php
// =========================================================================
// api/webhook_stock.php — Real-Time Inventory & Stock Synchronization
// Accepts webhooks from Ayra Mart / Enzor Motors to update stock sitewide
// =========================================================================
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true) ?? $_POST;

$api_key = trim($data['api_key'] ?? '');
$shop_name = trim($data['shop_name'] ?? '');
$product_sku = trim($data['product_sku'] ?? $data['product_id'] ?? '');
$stock_qty = intval($data['stock_quantity'] ?? 0);

if (empty($product_sku)) {
    echo json_encode(['success' => false, 'message' => 'Missing product SKU or ID.']);
    exit;
}

try {
    $status = ($stock_qty <= 0) ? 'out_of_stock' : 'active';
    
    // Update product stock status
    $stmt = $pdo->prepare("UPDATE partner_products SET status = ?, updated_at = NOW() WHERE id = ? OR sku = ?");
    $stmt->execute([$status, $product_sku, $product_sku]);
    $affected = $stmt->rowCount();

    echo json_encode([
        'success' => true,
        'message' => "Inventory updated for SKU '{$product_sku}'. Quantity: {$stock_qty}, Status: {$status}.",
        'records_updated' => $affected
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Stock update failed: ' . $e->getMessage()]);
}
