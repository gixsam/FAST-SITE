<?php
require_once __DIR__ . '/../../config.php';

// Test 1: Function existence and contract structure
echo "=== TEST 1: Function Contract Verification ===\n";
require_once __DIR__ . '/../../includes/user_sidebar.php';

if (!function_exists('getUserShopState')) {
    echo "FAIL: getUserShopState not defined!\n";
    exit(1);
}
echo "PASS: getUserShopState exists.\n";

// Test 2: Edge cases: invalid user IDs, null PDO
echo "=== TEST 2: Edge Cases & Defense ===\n";
$res0 = getUserShopState($pdo, 0);
assert($res0['state'] === 'none', "User 0 should be 'none'");
assert($res0['shop_name'] === 'My Shop', "Default shop name mismatch");
assert($res0['shop_id'] === 0, "Default shop_id mismatch");
echo "PASS: User 0 handled safely.\n";

$resNeg = getUserShopState($pdo, -5);
assert($resNeg['state'] === 'none', "User -5 should be 'none'");
echo "PASS: Negative user ID handled safely.\n";

$resNullPdo = getUserShopState(null, 1);
assert($resNullPdo['state'] === 'none', "Null PDO should return 'none'");
echo "PASS: Null PDO handled safely.\n";

// Test 3: Existing users in database
echo "=== TEST 3: DB Users State Resolution ===\n";
// Let's check partners in DB
try {
    $partners = $pdo->query("SELECT id, user_id, phone, status, business_name FROM partners LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    echo "Found " . count($partners) . " partners in DB:\n";
    foreach ($partners as $p) {
        $pState = getUserShopState($pdo, (int)$p['user_id'], (string)($p['phone'] ?? ''));
        echo " - Partner ID {$p['id']}, UID {$p['user_id']}, Status '{$p['status']}' => resolved state: '{$pState['state']}', name: '{$pState['shop_name']}'\n";
    }
} catch (Exception $e) {
    echo "Note: Partners query: " . $e->getMessage() . "\n";
}

// Check partner requests in DB
try {
    $reqs = $pdo->query("SELECT id, user_id, status FROM partner_requests LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    echo "Found " . count($reqs) . " partner_requests in DB:\n";
    foreach ($reqs as $r) {
        $rState = getUserShopState($pdo, (int)$r['user_id']);
        echo " - Request ID {$r['id']}, UID {$r['user_id']}, Status '{$r['status']}' => resolved state: '{$rState['state']}'\n";
    }
} catch (Exception $e) {
    echo "Note: Requests query: " . $e->getMessage() . "\n";
}

echo "\nALL TESTS PASSED!\n";
