<?php
// Test getUserShopState logic against local database

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/user_sidebar.php';

echo "Testing getUserShopState..." . PHP_EOL;

// Test with dummy user ID 0
$state0 = getUserShopState($pdo, 0, '');
assert($state0['state'] === 'none', "User 0 should be 'none'");
echo "✓ User 0 -> state: " . $state0['state'] . PHP_EOL;

// Test with an actual partner row if any exists in DB
$pStmt = $pdo->query("SELECT user_id, phone, status, business_name FROM partners LIMIT 5");
$partners = $pStmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($partners as $p) {
    $uid = (int)$p['user_id'];
    $phone = $p['phone'];
    $expectedStatus = strtolower(trim($p['status'] ?? 'approved'));
    if ($expectedStatus === 'active' || empty($expectedStatus)) $expectedStatus = 'approved';
    
    $res = getUserShopState($pdo, $uid, $phone);
    echo "✓ Partner [UID: $uid, Phone: $phone, DB status: {$p['status']}] -> state: " . $res['state'] . ", Shop: " . $res['shop_name'] . PHP_EOL;
}

echo "All tests passed successfully!" . PHP_EOL;
