<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "==================================================\n";
echo "  FORENSIC INTEGRITY AUDIT SUITE - PHASE 87 M2\n";
echo "==================================================\n\n";

require_once __DIR__ . '/../../config.php';

$failures = [];
$passes = [];

function recordResult($testName, $isSuccess, $detail = '') {
    global $failures, $passes;
    if ($isSuccess) {
        $passes[] = $testName;
        echo "[PASS] $testName" . ($detail ? " - $detail" : "") . "\n";
    } else {
        $failures[] = ["name" => $testName, "detail" => $detail];
        echo "[FAIL] $testName - $detail\n";
    }
}

function renderIsolated($file, $page, $sessionUserId = 1, $sessionPartnerId = 1, $pendingOrdersCount = null) {
    $script = '<?php ' .
        'session_start(); ' .
        '$_SESSION["user_id"] = ' . (int)$sessionUserId . '; ' .
        '$_SESSION["partner_id"] = ' . (int)$sessionPartnerId . '; ' .
        '$_SERVER["PHP_SELF"] = "/partner/' . addslashes($page) . '"; ' .
        '$_SERVER["REQUEST_METHOD"] = "GET"; ';
    if ($pendingOrdersCount !== null) {
        $script .= '$partner_pending_orders_count = ' . (int)$pendingOrdersCount . '; ';
    }
    $script .= 'ob_start(); include __DIR__ . "/../../partner/' . addslashes($file) . '"; echo ob_get_clean();';
    
    $descriptors = [
        0 => ["pipe", "r"],
        1 => ["pipe", "w"],
        2 => ["pipe", "w"]
    ];
    $process = proc_open('php', $descriptors, $pipes, __DIR__);
    if (is_resource($process)) {
        fwrite($pipes[0], $script);
        fclose($pipes[0]);
        $out = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);
        return $out;
    }
    return '';
}

// ----------------------------------------------------
// 1. Database & Order Counter Query Forensic Check
// ----------------------------------------------------
echo "--- 1. Order Counter Query Integrity ---\n";
$partnerId = 1;
$userId = 1;
try {
    $stmt = $pdo->query("SELECT id, user_id, business_name FROM partners LIMIT 1");
    $partnerRow = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($partnerRow) {
        $partnerId = (int)$partnerRow['id'];
        $userId = (int)$partnerRow['user_id'];
        recordResult("Partner DB Query", true, "Found partner ID: " . $partnerId);
    } else {
        recordResult("Partner DB Query", true, "No partners in DB, using fallback ID 1");
    }

    // Execute the exact query from partner/nav.php
    $q = "SELECT COUNT(*) FROM partner_orders WHERE partner_id = :pid AND status IN ('pending', 'accepted', 'in_progress', 'waiting_confirmation')";
    $countStmt = $pdo->prepare($q);
    $countStmt->execute([':pid' => $partnerId]);
    $liveCount = (int)$countStmt->fetchColumn();
    recordResult("Live Order Counter Execution", true, "Executed query successfully; live pending count = " . $liveCount);
    
    // Dynamic badge rendering when count > 0
    $navOrdersHtml = renderIsolated('nav.php', 'orders.php', $userId, $partnerId, 7);
    $hasDockBadge = strpos($navOrdersHtml, '<span class="dock-badge-counter">7</span>') !== false;
    $hasDrawerBadge = strpos($navOrdersHtml, '<span class="drawer-badge-counter">7</span>') !== false;
    recordResult("Badge Dynamic Rendering (>0)", $hasDockBadge && $hasDrawerBadge, "Dock badge: " . ($hasDockBadge ? "YES" : "NO") . ", Drawer badge: " . ($hasDrawerBadge ? "YES" : "NO"));
    
    // Dynamic badge suppression when count = 0
    $navZeroHtml = renderIsolated('nav.php', 'orders.php', $userId, $partnerId, 0);
    $hasNoDockBadge = strpos($navZeroHtml, '<span class="dock-badge-counter"') === false;
    $hasNoDrawerBadge = strpos($navZeroHtml, '<span class="drawer-badge-counter"') === false;
    recordResult("Badge Suppression (count=0)", $hasNoDockBadge && $hasNoDrawerBadge, "Dock badge hidden: " . ($hasNoDockBadge ? "YES" : "NO") . ", Drawer badge hidden: " . ($hasNoDrawerBadge ? "YES" : "NO"));

} catch (Exception $e) {
    recordResult("Database Query Test", false, "Exception: " . $e->getMessage());
}

// ----------------------------------------------------
// 2. Top Mode Switcher Pill & Back Button Integrity
// ----------------------------------------------------
echo "\n--- 2. Navigation Pill & Back Button Integrity ---\n";

$dashNavHtml = renderIsolated('nav.php', 'dashboard.php', $userId, $partnerId);

$hasModePillLink = strpos($dashNavHtml, 'href="/user/dashboard.php" class="header-mode-pill"') !== false;
$hasModePillDesktopText = strpos($dashNavHtml, 'class="mode-pill-text-desktop">Switch to Buyer Mode</span>') !== false;
$hasModePillMobileText = strpos($dashNavHtml, 'class="mode-pill-text-mobile">Buyer</span>') !== false;
recordResult("Header Mode Pill Link", $hasModePillLink, "Links directly to /user/dashboard.php");
recordResult("Header Mode Pill Responsive Text", $hasModePillDesktopText && $hasModePillMobileText, "Desktop: Switch to Buyer Mode, Mobile: Buyer");

// Back button on dashboard MUST be omitted
$hasNoBackOnDash = strpos($dashNavHtml, '<a href="dashboard.php" class="nav-back-btn"') === false;
recordResult("Back Button Omitted on Dashboard", $hasNoBackOnDash, "nav-back-btn correctly omitted on dashboard.php");

// Subpages MUST have back button linking to dashboard.php
$subpages = ['orders.php', 'products.php'];
foreach ($subpages as $sub) {
    $subHtml = renderIsolated('nav.php', $sub, $userId, $partnerId);
    $hasBack = strpos($subHtml, '<a href="dashboard.php" class="nav-back-btn"') !== false;
    recordResult("Back Button on " . $sub, $hasBack, "nav-back-btn linking to dashboard.php present");
}

// ----------------------------------------------------
// 3. Mobile Bottom Dock 5-Slot Architecture
// ----------------------------------------------------
echo "\n--- 3. Mobile Bottom Dock 5-Slot Architecture ---\n";

$hasSlot1 = strpos($dashNavHtml, 'href="dashboard.php" class="dock-item') !== false && strpos($dashNavHtml, '<span>Hub</span>') !== false;
recordResult("Dock Slot 1 (Hub)", $hasSlot1, "Hub link to dashboard.php");

$hasSlot2 = strpos($dashNavHtml, 'href="orders.php" class="dock-item') !== false && strpos($dashNavHtml, '<span>Orders</span>') !== false;
recordResult("Dock Slot 2 (Orders)", $hasSlot2, "Orders link to orders.php");

$hasSlot3 = strpos($dashNavHtml, 'href="product_add.php" class="dock-item-primary') !== false;
recordResult("Dock Slot 3 (Center FAB Add)", $hasSlot3, "Center FAB link to product_add.php");

$hasSlot4 = strpos($dashNavHtml, 'href="products.php" class="dock-item') !== false && strpos($dashNavHtml, '<span>Catalog</span>') !== false;
recordResult("Dock Slot 4 (Catalog)", $hasSlot4, "Catalog link to products.php");

$hasSlot5 = strpos($dashNavHtml, 'href="/user/dashboard.php" class="dock-item dock-item-buyer"') !== false && strpos($dashNavHtml, '<span>Buyer Mode</span>') !== false;
recordResult("Dock Slot 5 (Buyer Mode)", $hasSlot5, "Buyer Mode link to /user/dashboard.php");

$dockContentStart = strpos($dashNavHtml, '<nav class="partner-bottom-dock"');
$dockContentEnd = strpos($dashNavHtml, '</nav>', $dockContentStart);
$dockOnlyHtml = substr($dashNavHtml, $dockContentStart, $dockContentEnd - $dockContentStart);
$hasNoObsoleteMenu = strpos($dockOnlyHtml, 'menu-toggle') === false && strpos($dockOnlyHtml, '<span>Menu</span>') === false;
recordResult("Obsolete Menu Removed from Dock", $hasNoObsoleteMenu, "Menu button no longer exists in bottom dock");

$hasSafeAreaInset = strpos($dashNavHtml, 'env(safe-area-inset-bottom') !== false;
recordResult("Hardware Safe-Area Insets", $hasSafeAreaInset, "env(safe-area-inset-bottom) present in dock styling");

// ----------------------------------------------------
// 4. Hero Action Bar in partner/dashboard.php
// ----------------------------------------------------
echo "\n--- 4. Hero Action Bar in partner/dashboard.php ---\n";
$dashFullHtml = renderIsolated('dashboard.php', 'dashboard.php', $userId, $partnerId);
$hasHeroBuyerBtn = strpos($dashFullHtml, 'href="/user/dashboard.php" class="btn-action-hero btn-action-buyer"') !== false;
$hasHeroBuyerText = strpos($dashFullHtml, 'Switch to Buyer Mode') !== false;
recordResult("Hero Action Bar Buyer Switcher", $hasHeroBuyerBtn && $hasHeroBuyerText, "btn-action-buyer button present in quick-action-bar");

// ----------------------------------------------------
// 5. Touch Target & Nocturne Aurum Token Audit
// ----------------------------------------------------
echo "\n--- 5. Touch Target & Nocturne Aurum Token Audit ---\n";
$navCss = file_get_contents(__DIR__ . '/../../partner/nav.php');

$has44pxBack = (strpos($navCss, '.nav-back-btn {') !== false && strpos($navCss, 'min-width: 44px;') !== false && strpos($navCss, 'min-height: 44px;') !== false);
$has44pxPill = (strpos($navCss, '.header-mode-pill {') !== false && strpos($navCss, 'min-width: 44px;') !== false && strpos($navCss, 'min-height: 44px;') !== false);
$has44pxDock = (strpos($navCss, '.dock-item {') !== false && strpos($navCss, 'min-height: 44px;') !== false);
$has44pxHamburger = (strpos($navCss, '.hamburger-btn {') !== false && strpos($navCss, 'min-width: 44px;') !== false && strpos($navCss, 'min-height: 44px;') !== false);
$hasActiveScale = (strpos($navCss, '.dock-item:active {') !== false && strpos($navCss, 'transform: scale(0.96);') !== false);

recordResult("44px Touch Target - Back Button", $has44pxBack, ".nav-back-btn has min-width/height 44px");
recordResult("44px Touch Target - Header Mode Pill", $has44pxPill, ".header-mode-pill has min-width/height 44px");
recordResult("44px Touch Target - Dock Items", $has44pxDock, ".dock-item has min-height 44px");
recordResult("44px Touch Target - Hamburger Button", $has44pxHamburger, ".hamburger-btn has min-width/height 44px");
recordResult("Nocturne Aurum Active Tap Feedback", $hasActiveScale, ".dock-item:active has scale(0.96)");

// ----------------------------------------------------
// 6. Forensic Search for Cheating, Facades, or Bypasses
// ----------------------------------------------------
echo "\n--- 6. Prohibited Pattern & Facade Detection ---\n";
$scopeFiles = [
    'partner/nav.php',
    'partner/dashboard.php',
    'partner/index.php',
    'partner/logout.php',
    'partner/product_add.php',
    'partner/product_edit.php',
    'partner/product_delete.php',
    'partner/profile.php',
    'partner/api_docs.php'
];

$cheatingFound = false;
foreach ($scopeFiles as $sf) {
    $content = file_get_contents(__DIR__ . '/../../' . $sf);
    
    // Check for hardcoded test returns or auth bypasses in source files
    if (preg_match('/(\\$_SESSION\\[[\'"]user_id[\'"]\\]\\s*=\\s*\\d+)/', $content, $m)) {
        recordResult("Auth Bypass Detection (" . $sf . ")", false, "Hardcoded user_id found: " . $m[1]);
        $cheatingFound = true;
    } else {
        recordResult("Auth Bypass Detection (" . $sf . ")", true, "No hardcoded session user_id auth bypass");
    }
    
    // Check for fake return constants
    if (preg_match('/function\\s+\\w+\\([^)]*\\)\\s*\\{\\s*return\\s+(true|false|1|0|"pass"|\'pass\');\\s*\\}/i', $content, $m)) {
        recordResult("Facade Detection (" . $sf . ")", false, "Facade dummy function found: " . $m[0]);
        $cheatingFound = true;
    } else {
        recordResult("Facade Detection (" . $sf . ")", true, "No dummy facade functions");
    }
}

// ----------------------------------------------------
// Final Summary
// ----------------------------------------------------
echo "\n==================================================\n";
echo "TOTAL PASSES: " . count($passes) . "\n";
echo "TOTAL FAILURES: " . count($failures) . "\n";
if (count($failures) === 0) {
    echo "VERDICT: CLEAN\n";
    exit(0);
} else {
    echo "VERDICT: INTEGRITY VIOLATION\n";
    print_r($failures);
    exit(1);
}
