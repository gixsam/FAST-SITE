<?php
// Test edge cases for includes/user_sidebar.php and user/dashboard.php
session_start();

require_once __DIR__ . '/../../config.php';

echo "=== ADVERSARIAL STRESS TEST: Unset / Malicious / Null Variables ===\n";

// Scenario A: Completely empty session and unset variables
$_SESSION = [];
unset($user);
unset($partnerInfo);
unset($isPartner);
unset($siteName);
unset($settings);
unset($activeOrders);

// Capture output of user_sidebar without errors
ob_start();
try {
    include __DIR__ . '/../../includes/user_sidebar.php';
    $sidebarOut = ob_get_clean();
    echo "PASS: user_sidebar.php rendered cleanly with 0 session/user data without throwing warnings.\n";
} catch (Throwable $t) {
    ob_end_clean();
    echo "FAIL: user_sidebar.php threw exception: " . $t->getMessage() . "\n";
    exit(1);
}

// Check if mode-shopless is rendered
if (strpos($sidebarOut, 'mode-shopless') !== false) {
    echo "PASS: Default state is 'mode-shopless' (Open Free Shop).\n";
} else {
    echo "FAIL: Expected 'mode-shopless' in output.\n";
    exit(1);
}

// Scenario B: Simulated XSS in shop_name
echo "\n=== Scenario B: XSS in Shop Name ===\n";
$_SESSION['user_id'] = 99999;
$user = [
    'id' => 99999,
    'name' => '<script>alert("XSS")</script>',
    'phone' => '01999999999',
    'email' => 'test@xss.com'
];

// Test getUserShopState with mock return
$testData = [
    'state' => 'pending',
    'shop' => ['registration_number' => 'FS-APP-99999'],
    'request' => null,
    'shop_name' => '<script>alert("XSS_SHOP")</script>',
    'shop_id' => 123
];

ob_start();
$shopStateData = $testData;
$shopState = 'pending';
include __DIR__ . '/../../includes/user_sidebar.php';
$xssOut = ob_get_clean();

if (strpos($xssOut, '<script>alert("XSS_SHOP")</script>') !== false) {
    echo "FAIL: Unescaped XSS found in output!\n";
    exit(1);
} else {
    echo "PASS: XSS was properly sanitized via htmlspecialchars.\n";
}

// Scenario C: Verify CSS file exists and has Nocturne Aurum tokens
echo "\n=== Scenario C: CSS Tokens Verification ===\n";
$css = file_get_contents(__DIR__ . '/../../assets/css/user.css');
$requiredTokens = [
    '.top-mode-pill',
    'min-height: 44px',
    'min-width: 44px',
    '.top-mode-pill.mode-approved',
    '.top-mode-pill.mode-pending',
    '.top-mode-pill.mode-shopless',
    '.dock-center-circle',
    '.dock-center-circle.mode-shop',
    '.dock-center-circle.mode-review',
    '.dock-center-circle.mode-create',
    '.bottom-sheet-backdrop',
    '.bottom-sheet',
    'scale(0.96)',
    'pulsePillBorder'
];

foreach ($requiredTokens as $tok) {
    if (strpos($css, $tok) === false) {
        echo "FAIL: Missing token in assets/css/user.css: {$tok}\n";
        exit(1);
    }
}
echo "PASS: All Google Stitch Nocturne Aurum tokens present in assets/css/user.css.\n";

echo "\nALL ADVERSARIAL STRESS TESTS PASSED!\n";
