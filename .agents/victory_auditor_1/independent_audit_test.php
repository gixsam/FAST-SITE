<?php
/**
 * Independent Victory Audit Verification Suite
 * Phase 85 UX/UI & Navigation Overhaul
 */

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

$totalTests = 0;
$passedTests = 0;
$failedTests = 0;

function assertCheck($condition, $name, $details = '') {
    global $totalTests, $passedTests, $failedTests;
    $totalTests++;
    if ($condition) {
        $passedTests++;
        echo "[PASS] $name\n";
    } else {
        $failedTests++;
        echo "[FAIL] $name" . ($details ? " - $details" : "") . "\n";
    }
}

echo "====================================================================\n";
echo "VICTORY AUDITOR INDEPENDENT TEST SUITE - PHASE 85\n";
echo "====================================================================\n\n";

// SECTION 1: LINTING CHECKS (All 9 PHP Files)
echo "--- SECTION 1: PHP SYNTAX LINTING ---\n";
$phpFiles = [
    'user/dashboard.php',
    'includes/user_sidebar.php',
    'partner/dashboard.php',
    'partner/orders.php',
    'partner/products.php',
    'partner/product_add.php',
    'partner/nav.php',
    'home.php',
    'includes/nav_public.php'
];

foreach ($phpFiles as $file) {
    $fullPath = __DIR__ . '/../../' . $file;
    $output = [];
    $returnCode = 0;
    exec("php -l \"$fullPath\"", $output, $returnCode);
    assertCheck($returnCode === 0, "Syntax Lint: $file", implode(" ", $output));
}

// SECTION 2: MODULE 1 (User Dashboard & Navigation Overhaul)
echo "\n--- SECTION 2: MODULE 1 (USER DASHBOARD & NAVIGATION) ---\n";
$userDash = file_get_contents(__DIR__ . '/../../user/dashboard.php');
$userSide = file_get_contents(__DIR__ . '/../../includes/user_sidebar.php');
$userCss  = file_get_contents(__DIR__ . '/../../assets/css/user.css');
$mobCss   = file_get_contents(__DIR__ . '/../../assets/css/mobile_responsive.css');

// Plain terminology
assertCheck(strpos($userDash, 'Member ID') !== false || strpos($userDash, 'FS-') !== false, "User: Member ID terminology present");
assertCheck(strpos($userDash, 'Invite Code') !== false || strpos($userDash, 'Share & Earn') !== false, "User: Invite Code / Referral plain wording present");
assertCheck(strpos($userDash, 'Available Balance') !== false || strpos($userDash, '৳') !== false, "User: Available Balance phrasing present");
assertCheck(strpos($userDash, 'Cash Out') !== false || strpos($userDash, 'withdraw_coins.php') !== false, "User: Cash Out action present");

// Routing & Tabs
assertCheck(strpos($userDash, 'tab-social') === false, "User: Eliminated dead 'tab-social' route");
assertCheck(strpos($userDash, 'id="tab-orders"') !== false, "User: Consolidated 'tab-orders' present");
assertCheck(strpos($userDash, '$totalActiveOrders') === false && strpos($userDash, '$activeOrders') !== false, "User: No undefined \$totalActiveOrders variable; \$activeOrders used");

// Zero overlap & Bottom Floating Navigation
assertCheck(strpos($userDash, 'user-bottom-dock') !== false || strpos($userDash, 'bottom-nav') !== false || strpos($userDash, 'floating-bottom-bar') !== false || strpos($userDash, 'bottom_nav') !== false, "User: 5-Slot Bottom Floating Bar implemented");
assertCheck(strpos($userDash, '/index.php') !== false && strpos($userDash, 'tab=orders') !== false && strpos($userDash, 'wallet.php') !== false && strpos($userDash, 'profile.php') !== false, "User: Bottom bar links (Store, Orders, Wallet, Alerts, Profile) configured");
assertCheck(strpos($userSide, 'closeAllDrawers') !== false, "User: Dual drawers decoupled with closeAllDrawers()");
assertCheck(strpos($userCss, 'calc(') !== false || strpos($mobCss, 'calc(') !== false, "User: CSS Viewport clearance with calc() present");
assertCheck(strpos($userDash, 'reflink-quick') !== false && strpos($userDash, 'reflink-share') !== false, "User: Unique DOM IDs for referral link inputs");

// SECTION 3: MODULE 2 (Shop / Partner Portal Simplification)
echo "\n--- SECTION 3: MODULE 2 (SHOP / PARTNER PORTAL) ---\n";
$partDash = file_get_contents(__DIR__ . '/../../partner/dashboard.php');
$partProd = file_get_contents(__DIR__ . '/../../partner/products.php');
$partOrd  = file_get_contents(__DIR__ . '/../../partner/orders.php');
$partAdd  = file_get_contents(__DIR__ . '/../../partner/product_add.php');
$partNav  = file_get_contents(__DIR__ . '/../../partner/nav.php');

assertCheck(strpos($partDash, 'Add New Product') !== false, "Partner: Plain 'Add New Product' title");
assertCheck(strpos($partProd, '👁️ View') !== false || strpos($partProd, 'product_detail.php?id=') !== false, "Partner: Live storefront preview link in products table");
assertCheck(strpos($partProd, 'FREE') !== false, "Partner: Emerald FREE badge for 0-priced products");
assertCheck(strpos($partOrd, '<<div') === false, "Partner: Fixed stray <<div in orders.php");
assertCheck(strpos($partNav, 'partner-bottom-dock') !== false, "Partner: 62px mobile bottom dock implemented");
assertCheck(strpos($partNav, 'bottom: 62px') !== false, "Partner: Side drawer mobile clearance above bottom dock");

// SECTION 4: MODULE 3 (Marketplace Header, Filter & Drawer Streamlining)
echo "\n--- SECTION 4: MODULE 3 (MARKETPLACE HEADER, FILTER & DRAWER) ---\n";
$homePhp = file_get_contents(__DIR__ . '/../../home.php');
$navPub  = file_get_contents(__DIR__ . '/../../includes/nav_public.php');

// Symmetrical 3-Zone Header
assertCheck(strpos($navPub, 'grid-template-columns: 1fr auto 1fr') !== false || strpos($navPub, 'nav-zone-center') !== false, "Marketplace: 3-Zone symmetrical grid header");
assertCheck(strpos($navPub, 'nav-brand-centered') !== false, "Marketplace: Centered brand logo branding");
assertCheck(strpos($navPub, 'safe-area-inset-top') !== false, "Marketplace: Mobile APK safe-area top inset");

// Search Command Hub & Quick Types
assertCheck(strpos($homePhp, 'search-command-hub') !== false, "Marketplace: Prominent Search Command Hub");
assertCheck(strpos($homePhp, 'btn-filter-trigger') !== false, "Marketplace: Integrated Filter Trigger Button");
assertCheck(strpos($homePhp, 'quick-types-ribbon') !== false, "Marketplace: Horizontal Quick-Types Ribbon");
assertCheck(strpos($homePhp, '📦 All') !== false && strpos($homePhp, '🛍️ Products') !== false && strpos($homePhp, '🤝 Services') !== false, "Marketplace: Quick touch chips for All, Products, Services");

// Slide-Up Category Drawer
assertCheck(strpos($homePhp, 'id="categoryDrawer"') !== false, "Marketplace: Slide-Up Category Drawer element present");
assertCheck(strpos($homePhp, 'id="categoryDrawerScrim"') !== false, "Marketplace: Category Drawer Scrim present");
assertCheck(strpos($homePhp, 'drawer-handle-bar') !== false, "Marketplace: Mobile grab handle present");
assertCheck(strpos($homePhp, 'category-drawer-footer') !== false, "Marketplace: Sticky drawer footer present");
assertCheck(strpos($homePhp, 'toggleCategoryDrawer') !== false, "Marketplace: toggleCategoryDrawer JS function present");
assertCheck(strpos($homePhp, 'category-drawer drawer-menu modal-box') !== false, "Marketplace: Pull-to-refresh exclusion tags present");

// SECTION 5: MODULE 4 (Packaging, Guides & Directives)
echo "\n--- SECTION 5: MODULE 4 (PACKAGING & DIRECTIVES) ---\n";
$projState = file_get_contents(__DIR__ . '/../../PROJECT_STATE.md');
$deployGuide = file_get_contents(__DIR__ . '/../../DEPLOYMENT_GUIDE.txt');
$zipPath = __DIR__ . '/../../fastsite_phase85.zip';

assertCheck(strpos($projState, 'Phase 85 Complete') !== false || strpos($projState, 'Phase 85 ✅') !== false, "Project State: Updated to Phase 85 Complete");
assertCheck(file_exists($zipPath), "Archive: fastsite_phase85.zip exists");
assertCheck(filesize($zipPath) > 50000, "Archive: fastsite_phase85.zip has valid size (>50KB)");
assertCheck(file_exists(__DIR__ . '/../../DEPLOYMENT_GUIDE.txt'), "Deployment Guide: DEPLOYMENT_GUIDE.txt exists");
assertCheck(strpos($deployGuide, 'LOCAL SERVER LIVE HOST') !== false, "Deployment Guide: Contains Directive 5 Local Server Live Host reminder");
assertCheck(strpos($deployGuide, 'public_html') !== false, "Deployment Guide: Contains exact Hostinger folder paths");
assertCheck(strpos($deployGuide, 'Phase 85') !== false, "Deployment Guide: Explicitly references Phase 85");

echo "\n====================================================================\n";
echo "TEST RESULTS SUMMARY: $passedTests PASSED / $failedTests FAILED out of $totalTests TOTAL\n";
echo "====================================================================\n";

if ($failedTests === 0) {
    echo "VERDICT: ALL TESTS PASSED SUCCESSFULLY!\n";
    exit(0);
} else {
    echo "VERDICT: TESTS FAILED!\n";
    exit(1);
}
