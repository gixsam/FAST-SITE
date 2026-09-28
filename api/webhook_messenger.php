<?php
// ==========================================
// FAST SITE - HEADLESS MESSENGER WEBHOOK
// ==========================================
// Routes customer messages from Fast Site to external admin panels (Ayra Mart, Enzor).

header('Content-Type: application/json');

require_once __DIR__ . '/../config.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Only POST allowed.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$api_key = $input['api_key'] ?? '';
$shop_name = $input['shop_name'] ?? '';
$customer_msg = $input['message'] ?? '';
$customer_id = $input['customer_id'] ?? '';

// Simple Hardcoded Master API Key for early implementation
$master_api_key = "FS_MASTER_" . hash('sha256', 'super_secret_fast_site_key');

if ($api_key !== $master_api_key) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized Master API Key.']);
    exit;
}

if (empty($shop_name) || empty($customer_msg)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing payload data.']);
    exit;
}

try {
    // 1. Log the message into Fast Site's central chats table
    // Find shop ID
    $shopStmt = $pdo->prepare("SELECT id FROM partners WHERE name LIKE ? LIMIT 1");
    $shopStmt->execute(['%' . $shop_name . '%']);
    $shop = $shopStmt->fetch(PDO::FETCH_ASSOC);

    if ($shop) {
        $insert = $pdo->prepare("INSERT INTO chats (user_id, partner_id, message, sender_type, created_at) VALUES (?, ?, ?, 'user', NOW())");
        $insert->execute([$customer_id, $shop['id'], $customer_msg]);
        
        // 2. Here, you would CURL/HTTP POST to the external website's webhook endpoint
        // e.g. curl_post("https://atayramart.com/api/receive_message.php", $payload)
        
        echo json_encode([
            'status' => 'success', 
            'message' => 'Message successfully routed to ' . htmlspecialchars($shop_name)
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Target shop not found in Master Database.']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Webhook processing failed: ' . $e->getMessage()]);
}
