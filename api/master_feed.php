<?php
// ==========================================
// FAST SITE - HEADLESS MASTER FEED API
// ==========================================
// This API allows external websites (Ayra Mart, Enzor, etc.) 
// to fetch their products seamlessly from Fast Site's Master Database.

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Allow external sites to fetch

require_once __DIR__ . '/../config.php';

$api_key = $_GET['api_key'] ?? '';
$shop_name = $_GET['shop_name'] ?? '';

if (empty($api_key) || empty($shop_name)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required parameters.']);
    exit;
}

// Simple Hardcoded Master API Key for early implementation
// (In the future, this can pull from homepage_settings)
$master_api_key = "FS_MASTER_" . hash('sha256', 'super_secret_fast_site_key');

if ($api_key !== $master_api_key) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized Master API Key.']);
    exit;
}

try {
    // 1. Find the Partner/Shop ID by Name
    $shopStmt = $pdo->prepare("SELECT id, name, profile_pic FROM partners WHERE name LIKE ? LIMIT 1");
    $shopStmt->execute(['%' . $shop_name . '%']);
    $shop = $shopStmt->fetch(PDO::FETCH_ASSOC);

    if (!$shop) {
        echo json_encode(['status' => 'error', 'message' => 'Shop not found.']);
        exit;
    }

    // 2. Fetch the Products
    $prodStmt = $pdo->prepare("SELECT id, title, description, price_coins, original_price_coins, category, delivery_type, photo, photo2, photo3 
                               FROM partner_products WHERE partner_id = ? AND is_active = 1");
    $prodStmt->execute([$shop['id']]);
    $products = $prodStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'shop' => $shop,
        'product_count' => count($products),
        'products' => $products,
        'documentation' => 'To display these products on your external site, parse this JSON array and render the images by pointing to https://fastsite.best-travel.ltd/uploads/{photo}'
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()]);
}
