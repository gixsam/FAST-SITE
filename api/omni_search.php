<?php
// =========================================================================
// api/omni_search.php    v4: Omni-Search Unified API (Phase 21)
// =========================================================================
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); 
require_once __DIR__ . '/../config.php';

// This API receives a search query and simultaneously queries:
// 1. Fast Site's local Global Marketplace
// 2. Simulated external APIs (e.g. Ayra Mart dropship catalog)

$query = $_GET['q'] ?? '';
if (empty($query)) {
    die(json_encode(['status' => 'error', 'message' => 'No search query provided.']));
}

$results = [];

// 1. Search Local Fast Site Marketplace
try {
    $stmt = $pdo->prepare("SELECT id, title, price, category FROM partner_products WHERE title LIKE ? OR category LIKE ? LIMIT 5");
    $searchTerm = "%{$query}%";
    $stmt->execute([$searchTerm, $searchTerm]);
    $local_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($local_items as $item) {
        $results[] = [
            'source' => 'Fast Site Marketplace',
            'title' => $item['title'],
            'price' => $item['price'],
            'url' => "/product_detail.php?id=" . $item['id']
        ];
    }
} catch (Exception $e) {
    // Log error, continue to external search
}

// 2. Simulate External API Search (Ayra Mart Dropship)
// In production, this would be a cURL request to https://ayramart.com/api/search?q=$query
if (stripos('helmet', $query) !== false || stripos('bike', $query) !== false) {
    $results[] = [
        'source' => 'Enzor Motor',
        'title' => 'Premium Full Face Helmet (External)',
        'price' => '4500 BDT',
        'url' => "/shop_wrapper.php?shop=ENZOR_MOTOR"
    ];
}

if (stripos('travel', $query) !== false || stripos('bag', $query) !== false) {
    $results[] = [
        'source' => 'Best Travel',
        'title' => 'Waterproof Trekking Bag 50L (External)',
        'price' => '2500 BDT',
        'url' => "/shop_wrapper.php?shop=BEST_TRAVEL"
    ];
}

echo json_encode([
    'status' => 'success',
    'query' => htmlspecialchars($query),
    'total_found' => count($results),
    'results' => $results
]);
?>
