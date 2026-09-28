<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/user_sidebar.php';

echo "=== ADVERSARIAL STRESS TESTING FOR MILESTONE 1 ===" . PHP_EOL . PHP_EOL;

// 1. Schema check
echo "[1] Checking columns of partner_requests and partners:" . PHP_EOL;
try {
    $cols1 = $pdo->query("PRAGMA table_info(partner_requests)")->fetchAll(PDO::FETCH_ASSOC);
    $c1 = array_column($cols1, 'name');
    echo "  partner_requests columns: " . implode(', ', $c1) . PHP_EOL;
} catch (Exception $e) {
    echo "  partner_requests PRAGMA error: " . $e->getMessage() . PHP_EOL;
}

try {
    $cols2 = $pdo->query("PRAGMA table_info(partners)")->fetchAll(PDO::FETCH_ASSOC);
    $c2 = array_column($cols2, 'name');
    echo "  partners columns: " . implode(', ', $c2) . PHP_EOL;
} catch (Exception $e) {
    echo "  partners PRAGMA error: " . $e->getMessage() . PHP_EOL;
}

// 2. Test getUserShopState edge cases
echo PHP_EOL . "[2] Stress testing getUserShopState inputs:" . PHP_EOL;

// Edge case A: Negative user ID
$resNeg = getUserShopState($pdo, -1, '');
echo "  Negative UID (-1): state=" . $resNeg['state'] . ", shop_name=" . $resNeg['shop_name'] . PHP_EOL;
assert($resNeg['state'] === 'none');

// Edge case B: Non-existent user ID
$res999 = getUserShopState($pdo, 999999, '01999999999');
echo "  Non-existent UID: state=" . $res999['state'] . ", shop_name=" . $res999['shop_name'] . PHP_EOL;
assert($res999['state'] === 'none');

// Edge case C: User with null phone
$resNullPhone = getUserShopState($pdo, 0, null);
echo "  Null phone: state=" . $resNullPhone['state'] . PHP_EOL;
assert($resNullPhone['state'] === 'none');

// Edge case D: Empty PDO or null PDO
$resNullPdo = getUserShopState(null, 1, '01700000000');
echo "  Null PDO: state=" . $resNullPdo['state'] . PHP_EOL;
assert($resNullPdo['state'] === 'none');

// 3. Test simulating pending state in partner_requests
echo PHP_EOL . "[3] Testing partner_requests state resolution:" . PHP_EOL;
// Let's see if any pending partner_requests exist in the DB
$pendingReq = $pdo->query("SELECT * FROM partner_requests WHERE status = 'pending' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if ($pendingReq) {
    $resPR = getUserShopState($pdo, (int)$pendingReq['user_id'], $pendingReq['phone'] ?? '');
    echo "  Existing pending request user {$pendingReq['user_id']}: state=" . $resPR['state'] . ", shop_name=" . $resPR['shop_name'] . PHP_EOL;
} else {
    echo "  No existing pending partner_requests in DB." . PHP_EOL;
}

// 4. Test rendering of user_sidebar.php and user/dashboard.php with mock session
echo PHP_EOL . "[4] Testing file rendering with output buffering..." . PHP_EOL;
$_SESSION['user_id'] = 1;
$user = [
    'id' => 1,
    'name' => 'Test User',
    'phone' => '01337320545',
    'email' => 'test@fastsite.com',
    'profile_pic' => ''
];
$siteName = 'FAST SITE';
$settings = ['logo_url' => '/assets/images/logo.png'];

ob_start();
include __DIR__ . '/../../includes/user_sidebar.php';
$sidebarHtml = ob_get_clean();
echo "  user_sidebar.php rendered successfully, length: " . strlen($sidebarHtml) . " bytes." . PHP_EOL;

// Check key HTML elements in user_sidebar.php
assert(strpos($sidebarHtml, 'top-mode-pill') !== false, "Must contain top-mode-pill");
assert(strpos($sidebarHtml, 'id="shopReviewModal"') !== false, "Must contain shopReviewModal");
assert(strpos($sidebarHtml, 'id="quickShopDrawer"') !== false, "Must contain quickShopDrawer");
assert(strpos($sidebarHtml, 'openShopReviewModal') !== false, "Must contain openShopReviewModal");
assert(strpos($sidebarHtml, 'openQuickShopDrawer') !== false, "Must contain openQuickShopDrawer");
echo "  ✓ All expected UI tokens found in user_sidebar.php" . PHP_EOL;

echo PHP_EOL . "=== ALL ADVERSARIAL STRESS TESTS COMPLETED ===" . PHP_EOL;
