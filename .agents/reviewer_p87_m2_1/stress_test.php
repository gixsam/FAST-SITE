<?php
/**
 * Independent Adversarial Stress Test Suite for Phase 87 Milestone 2
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config.php';

// Find a real partner in DB to test realistic query execution
$stmt = $pdo->query("SELECT user_id, id FROM partners LIMIT 1");
$row = $stmt->fetch(PDO::FETCH_ASSOC);

$_SESSION['user_id'] = $row ? (int)$row['user_id'] : 1;
$_SESSION['partner_id'] = $row ? (int)$row['id'] : 1;
$_SESSION['user_role'] = 'user';

$results = [];

function recordTest($name, $passed, $details = '') {
    global $results;
    $results[] = [
        'name' => $name,
        'passed' => $passed,
        'details' => $details
    ];
    echo ($passed ? "[PASS] " : "[FAIL] ") . $name . ($details ? " ($details)" : "") . "\n";
}

// 1. Stress Test nav.php under dashboard.php simulation
$_SERVER['PHP_SELF'] = '/partner/dashboard.php';
$_SERVER['REQUEST_URI'] = '/partner/dashboard.php';

ob_start();
include __DIR__ . '/../../partner/nav.php';
$dashNavOutput = ob_get_clean();

// Check back button absence on dashboard
$hasBackBtnOnDash = (strpos($dashNavOutput, 'class="nav-back-btn"') !== false);
recordTest('Dashboard: Persistent back button absent on dashboard.php', !$hasBackBtnOnDash);

// Check 1-Tap Mode Switcher Pill in Top Nav
$hasTopModePill = (strpos($dashNavOutput, 'class="header-mode-pill"') !== false);
recordTest('Dashboard: Top header 1-tap mode pill exists', $hasTopModePill);

$hasUserDashLinkInPill = (strpos($dashNavOutput, 'href="/user/dashboard.php" class="header-mode-pill"') !== false);
recordTest('Dashboard: Top mode pill targets /user/dashboard.php', $hasUserDashLinkInPill);

$hasDesktopText = (strpos($dashNavOutput, 'class="mode-pill-text-desktop"') !== false && strpos($dashNavOutput, 'Switch to Buyer Mode') !== false);
recordTest('Dashboard: Desktop text "Switch to Buyer Mode" present', $hasDesktopText);

$hasMobileText = (strpos($dashNavOutput, 'class="mode-pill-text-mobile"') !== false && strpos($dashNavOutput, 'Buyer') !== false);
recordTest('Dashboard: Mobile collapse text "Buyer" present', $hasMobileText);

// Check 5-slot bottom dock
$hasBottomDock = (strpos($dashNavOutput, 'class="partner-bottom-dock"') !== false);
recordTest('Dashboard: 5-slot bottom dock markup present', $hasBottomDock);

$hasBuyerDockItem = (strpos($dashNavOutput, 'dock-item-buyer') !== false && strpos($dashNavOutput, 'Buyer Mode') !== false);
recordTest('Dashboard: Bottom dock slot 5 is 1-Tap Buyer Mode', $hasBuyerDockItem);

$hasOldMenuBtn = (preg_match('/dock-item.*Menu/i', $dashNavOutput));
recordTest('Dashboard: Obsolete Menu button completely removed from dock', !$hasOldMenuBtn);

$hasSafeBottomCSS = (strpos($dashNavOutput, 'env(safe-area-inset-bottom') !== false);
recordTest('Dashboard: Safe-area-inset-bottom styling included', $hasSafeBottomCSS);

$hasMinTouchTargetCSS = (strpos($dashNavOutput, 'min-height: 44px') !== false);
recordTest('Dashboard: Min 44px touch target CSS included', $hasMinTouchTargetCSS);


// 2. Stress Test nav.php under subpages (orders.php) via sub-process
$cmdOrders = 'php -r "' .
    'session_start();' .
    '$_SESSION[\'user_id\'] = ' . $_SESSION['user_id'] . ';' .
    '$_SESSION[\'partner_id\'] = ' . $_SESSION['partner_id'] . ';' .
    '$_SERVER[\'PHP_SELF\'] = \'/partner/orders.php\';' .
    '$_SERVER[\'REQUEST_URI\'] = \'/partner/orders.php\';' .
    'ob_start();' .
    'include \'partner/nav.php\';' .
    '$out = ob_get_clean();' .
    '$hasBack = (strpos($out, \'class=\"nav-back-btn\"\') !== false);' .
    '$hasBackLink = (strpos($out, \'href=\"dashboard.php\" class=\"nav-back-btn\"\') !== false);' .
    '$hasSvg = (strpos($out, \'polyline points=\"15 18 9 12 15 6\"\') !== false);' .
    'echo ($hasBack ? \'1\' : \'0\') . \',\';' .
    'echo ($hasBackLink ? \'1\' : \'0\') . \',\';' .
    'echo ($hasSvg ? \'1\' : \'0\');' .
'"';

$ordersOutput = shell_exec($cmdOrders);
$ordersParts = explode(',', trim($ordersOutput));

recordTest('Orders: Persistent back button present on orders.php', ($ordersParts[0] ?? '') === '1');
recordTest('Orders: Back button links to dashboard.php', ($ordersParts[1] ?? '') === '1');
recordTest('Orders: Back button includes crisp SVG chevron', ($ordersParts[2] ?? '') === '1');


// 3. Stress Test nav.php under product_add.php (FAB active state) via sub-process
$cmdProdAdd = 'php -r "' .
    'session_start();' .
    '$_SESSION[\'user_id\'] = ' . $_SESSION['user_id'] . ';' .
    '$_SESSION[\'partner_id\'] = ' . $_SESSION['partner_id'] . ';' .
    '$_SERVER[\'PHP_SELF\'] = \'/partner/product_add.php\';' .
    '$_SERVER[\'REQUEST_URI\'] = \'/partner/product_add.php\';' .
    'ob_start();' .
    'include \'partner/nav.php\';' .
    '$out = ob_get_clean();' .
    '$hasBack = (strpos($out, \'class=\"nav-back-btn\"\') !== false);' .
    '$fabActive = (strpos($out, \'dock-item-primary active\') !== false);' .
    'echo ($hasBack ? \'1\' : \'0\') . \',\';' .
    'echo ($fabActive ? \'1\' : \'0\');' .
'"';

$prodAddOutput = shell_exec($cmdProdAdd);
$prodAddParts = explode(',', trim($prodAddOutput));

recordTest('Product Add: Persistent back button present on product_add.php', ($prodAddParts[0] ?? '') === '1');
recordTest('Product Add: Center FAB receives active class on product_add.php', ($prodAddParts[1] ?? '') === '1');


// 4. Stress Test dashboard.php quick action bar
$dashContent = file_get_contents(__DIR__ . '/../../partner/dashboard.php');
$hasHeroBuyerBtn = (strpos($dashContent, 'class="btn-action-hero btn-action-buyer"') !== false);
recordTest('Dashboard Hero: btn-action-buyer exists in quick-action-bar', $hasHeroBuyerBtn);

$heroBuyerTarget = (strpos($dashContent, 'href="/user/dashboard.php" class="btn-action-hero btn-action-buyer"') !== false);
recordTest('Dashboard Hero: btn-action-buyer links to /user/dashboard.php', $heroBuyerTarget);

$heroBuyerCSS = (strpos($dashContent, '.btn-action-buyer') !== false);
recordTest('Dashboard Hero: .btn-action-buyer styling defined', $heroBuyerCSS);


// 5. Check all tests
$failed = 0;
foreach ($results as $r) {
    if (!$r['passed']) $failed++;
}

echo "\n============================================\n";
echo "TOTAL TESTS: " . count($results) . " | FAILED: " . $failed . "\n";
echo "============================================\n";

if ($failed === 0) {
    echo "ALL INDEPENDENT ADVERSARIAL STRESS TESTS PASSED!\n";
    exit(0);
} else {
    echo "SOME TESTS FAILED!\n";
    exit(1);
}
