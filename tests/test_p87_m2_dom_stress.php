<?php
/**
 * =========================================================================
 * Fast Site Phase 87 Milestone 2: Empirical DOM & Component Stress Test
 * =========================================================================
 * Adversarial test harness verifying:
 * 1. Rendering of partner/nav.php across Cases A, B, C, D (0, 5, 99, 100, 150)
 * 2. Bottom dock exact 5-slot structure, classes, hrefs, icons, and labels
 * 3. Back button isolation (absent on dashboard.php, present on subpages)
 * 4. CSS rules: @media (max-width: 600px) text collapse, 44px+ touch targets, safe-area insets
 * 5. Stress test edge cases: unauthenticated redirects, missing partner records, active slot highlighting
 */

$rootDir = dirname(__DIR__);
$navFile = $rootDir . '/partner/nav.php';
$dashFile = $rootDir . '/partner/dashboard.php';

$totalChecks = 0;
$passedChecks = 0;
$failedChecks = [];

function check($condition, $description, $details = '') {
    global $totalChecks, $passedChecks, $failedChecks;
    $totalChecks++;
    if ($condition) {
        $passedChecks++;
        echo "  [PASS] " . $description . PHP_EOL;
    } else {
        $failedChecks[] = $description . ($details ? " -> Details: " . $details : "");
        echo "  [FAIL] " . $description . ($details ? " -> Details: " . $details : "") . PHP_EOL;
    }
}

/**
 * Helper to render partner/nav.php in an isolated PHP subprocess.
 */
function renderNavIsolated($pageSelf, $pendingOrdersCount = null, $userId = 1, $partnerId = 1, $isImpersonating = false) {
    global $rootDir;
    
    $code = '<?php' . PHP_EOL;
    $code .= 'session_start();' . PHP_EOL;
    if ($userId !== null) {
        $code .= '$_SESSION["user_id"] = ' . var_export($userId, true) . ';' . PHP_EOL;
    } else {
        $code .= 'unset($_SESSION["user_id"]);' . PHP_EOL;
    }
    if ($partnerId !== null) {
        $code .= '$_SESSION["partner_id"] = ' . var_export($partnerId, true) . ';' . PHP_EOL;
    }
    if ($isImpersonating) {
        $code .= '$_SESSION["is_impersonating"] = true;' . PHP_EOL;
    }
    $code .= '$_SERVER["PHP_SELF"] = ' . var_export($pageSelf, true) . ';' . PHP_EOL;
    $code .= '$_SERVER["REQUEST_METHOD"] = "GET";' . PHP_EOL;
    if ($pendingOrdersCount !== null) {
        $code .= '$partner_pending_orders_count = ' . var_export($pendingOrdersCount, true) . ';' . PHP_EOL;
    }
    $code .= 'ob_start();' . PHP_EOL;
    $code .= 'require ' . var_export($rootDir . '/partner/nav.php', true) . ';' . PHP_EOL;
    $code .= '$html = ob_get_clean();' . PHP_EOL;
    $code .= 'echo "###HTML_START###" . $html . "###HTML_END###";' . PHP_EOL;

    $tempFile = tempnam(sys_get_temp_dir(), 'nav_test_');
    file_put_contents($tempFile, $code);

    $cmd = 'php ' . escapeshellarg($tempFile);
    $output = shell_exec($cmd);
    @unlink($tempFile);

    if (preg_match('/###HTML_START###(.*)###HTML_END###/s', $output, $m)) {
        return ['html' => $m[1], 'raw' => $output];
    }
    return ['html' => '', 'raw' => $output];
}

/**
 * Helper to parse HTML fragment into DOMXPath.
 */
function getXPath($html) {
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    return new DOMXPath($dom);
}

echo "=================================================================" . PHP_EOL;
echo "1. TASK 1: CONDITIONAL RENDERING OF partner/nav.php" . PHP_EOL;
echo "=================================================================" . PHP_EOL;

// -------------------------------------------------------------
// Case A: $current_page = 'dashboard.php' -> Back button NOT rendered
// -------------------------------------------------------------
echo PHP_EOL . "--- Case A: dashboard.php (Back button must NOT be rendered) ---" . PHP_EOL;
$resA = renderNavIsolated('/partner/dashboard.php', 0);
$xpA = getXPath($resA['html']);

$backBtnsA = $xpA->query('//a[contains(@class, "nav-back-btn")]');
check($backBtnsA->length === 0, "Case A: .nav-back-btn is NOT rendered on dashboard.php", "Found count: " . $backBtnsA->length);

$brandLinkA = $xpA->query('//a[contains(@class, "top-brand")]');
check($brandLinkA->length === 1 && $brandLinkA->item(0)->getAttribute('href') === 'dashboard.php',
    "Case A: Brand link points to dashboard.php");

// -------------------------------------------------------------
// Case B: $current_page = 'orders.php' -> Back button IS rendered and links to dashboard.php
// -------------------------------------------------------------
echo PHP_EOL . "--- Case B: orders.php (Back button IS rendered & links to dashboard.php) ---" . PHP_EOL;
$resB = renderNavIsolated('/partner/orders.php', 0);
$xpB = getXPath($resB['html']);

$backBtnsB = $xpB->query('//a[contains(@class, "nav-back-btn")]');
check($backBtnsB->length === 1, "Case B: Exactly 1 .nav-back-btn rendered on orders.php", "Found count: " . $backBtnsB->length);
if ($backBtnsB->length === 1) {
    $backBtnNode = $backBtnsB->item(0);
    check($backBtnNode->getAttribute('href') === 'dashboard.php',
        "Case B: Back button href is 'dashboard.php'", "href: " . $backBtnNode->getAttribute('href'));
    check($backBtnNode->getAttribute('title') === 'Back to Shop Overview',
        "Case B: Back button title is 'Back to Shop Overview'");
    check(strpos($resB['html'], '<svg width="20" height="20"') !== false,
        "Case B: Back button contains SVG chevron");
}

// -------------------------------------------------------------
// Case C: $current_page = 'products.php' -> Back button IS rendered
// -------------------------------------------------------------
echo PHP_EOL . "--- Case C: products.php (Back button IS rendered) ---" . PHP_EOL;
$resC = renderNavIsolated('/partner/products.php', 0);
$xpC = getXPath($resC['html']);

$backBtnsC = $xpC->query('//a[contains(@class, "nav-back-btn")]');
check($backBtnsC->length === 1, "Case C: Exactly 1 .nav-back-btn rendered on products.php", "Found count: " . $backBtnsC->length);
if ($backBtnsC->length === 1) {
    check($backBtnsC->item(0)->getAttribute('href') === 'dashboard.php',
        "Case C: Back button href is 'dashboard.php'");
}

// Additional Subpages check (Case C extension)
$subpages = ['product_add.php', 'product_edit.php', 'coupons.php', 'earnings.php', 'disputes.php', 'profile.php'];
foreach ($subpages as $subpage) {
    $resSub = renderNavIsolated('/partner/' . $subpage, 0);
    $xpSub = getXPath($resSub['html']);
    $btns = $xpSub->query('//a[contains(@class, "nav-back-btn")]');
    check($btns->length === 1 && $btns->item(0)->getAttribute('href') === 'dashboard.php',
        "Case C Extension: Back button rendered on $subpage linking to dashboard.php");
}

// -------------------------------------------------------------
// Case D: $partner_pending_orders_count = 0 vs > 0 vs > 99
// -------------------------------------------------------------
echo PHP_EOL . "--- Case D: Badge behavior for pending orders counter ---" . PHP_EOL;

// D1: count = 0 -> No badge rendered in dock or drawer
$resD0 = renderNavIsolated('/partner/dashboard.php', 0);
$xpD0 = getXPath($resD0['html']);
$dockBadge0 = $xpD0->query('//nav[contains(@class, "partner-bottom-dock")]//span[contains(@class, "dock-badge-counter")]');
$drawerBadge0 = $xpD0->query('//div[contains(@class, "side-drawer")]//span[contains(@class, "drawer-badge-counter")]');
check($dockBadge0->length === 0, "Case D (count=0): No dock badge rendered", "Found: " . $dockBadge0->length);
check($drawerBadge0->length === 0, "Case D (count=0): No drawer badge rendered", "Found: " . $drawerBadge0->length);

// D2: count = 5 -> Badge rendered with "5"
$resD5 = renderNavIsolated('/partner/dashboard.php', 5);
$xpD5 = getXPath($resD5['html']);
$dockBadge5 = $xpD5->query('//nav[contains(@class, "partner-bottom-dock")]//span[contains(@class, "dock-badge-counter")]');
$drawerBadge5 = $xpD5->query('//div[contains(@class, "side-drawer")]//span[contains(@class, "drawer-badge-counter")]');
check($dockBadge5->length === 1 && trim($dockBadge5->item(0)->textContent) === '5',
    "Case D (count=5): Dock badge rendered with text '5'", "Found: " . ($dockBadge5->length ? trim($dockBadge5->item(0)->textContent) : 'none'));
check($drawerBadge5->length === 1 && trim($drawerBadge5->item(0)->textContent) === '5',
    "Case D (count=5): Drawer badge rendered with text '5'", "Found: " . ($drawerBadge5->length ? trim($drawerBadge5->item(0)->textContent) : 'none'));

// D3: count = 99 (Boundary check) -> Badge rendered with "99"
$resD99 = renderNavIsolated('/partner/dashboard.php', 99);
$xpD99 = getXPath($resD99['html']);
$dockBadge99 = $xpD99->query('//nav[contains(@class, "partner-bottom-dock")]//span[contains(@class, "dock-badge-counter")]');
$drawerBadge99 = $xpD99->query('//div[contains(@class, "side-drawer")]//span[contains(@class, "drawer-badge-counter")]');
check($dockBadge99->length === 1 && trim($dockBadge99->item(0)->textContent) === '99',
    "Case D (count=99): Dock badge rendered with text '99'", "Found: " . ($dockBadge99->length ? trim($dockBadge99->item(0)->textContent) : 'none'));
check($drawerBadge99->length === 1 && trim($drawerBadge99->item(0)->textContent) === '99',
    "Case D (count=99): Drawer badge rendered with text '99'", "Found: " . ($drawerBadge99->length ? trim($drawerBadge99->item(0)->textContent) : 'none'));

// D4: count = 100 (Boundary check) -> Badge rendered with "99+"
$resD100 = renderNavIsolated('/partner/dashboard.php', 100);
$xpD100 = getXPath($resD100['html']);
$dockBadge100 = $xpD100->query('//nav[contains(@class, "partner-bottom-dock")]//span[contains(@class, "dock-badge-counter")]');
$drawerBadge100 = $xpD100->query('//div[contains(@class, "side-drawer")]//span[contains(@class, "drawer-badge-counter")]');
check($dockBadge100->length === 1 && trim($dockBadge100->item(0)->textContent) === '99+',
    "Case D (count=100): Dock badge rendered with text '99+'", "Found: " . ($dockBadge100->length ? trim($dockBadge100->item(0)->textContent) : 'none'));
check($drawerBadge100->length === 1 && trim($drawerBadge100->item(0)->textContent) === '99+',
    "Case D (count=100): Drawer badge rendered with text '99+'", "Found: " . ($drawerBadge100->length ? trim($drawerBadge100->item(0)->textContent) : 'none'));

// D5: count = 250 -> Badge rendered with "99+"
$resD250 = renderNavIsolated('/partner/dashboard.php', 250);
$xpD250 = getXPath($resD250['html']);
$dockBadge250 = $xpD250->query('//nav[contains(@class, "partner-bottom-dock")]//span[contains(@class, "dock-badge-counter")]');
$drawerBadge250 = $xpD250->query('//div[contains(@class, "side-drawer")]//span[contains(@class, "drawer-badge-counter")]');
check($dockBadge250->length === 1 && trim($dockBadge250->item(0)->textContent) === '99+',
    "Case D (count=250): Dock badge rendered with text '99+'", "Found: " . ($dockBadge250->length ? trim($dockBadge250->item(0)->textContent) : 'none'));
check($drawerBadge250->length === 1 && trim($drawerBadge250->item(0)->textContent) === '99+',
    "Case D (count=250): Drawer badge rendered with text '99+'", "Found: " . ($drawerBadge250->length ? trim($drawerBadge250->item(0)->textContent) : 'none'));


echo PHP_EOL . "=================================================================" . PHP_EOL;
echo "2. TASK 2: VERIFY BOTTOM DOCK STRUCTURE & SLOTS" . PHP_EOL;
echo "=================================================================" . PHP_EOL;

$resDock = renderNavIsolated('/partner/dashboard.php', 3);
$xpDock = getXPath($resDock['html']);

$dockNodes = $xpDock->query('//nav[contains(@class, "partner-bottom-dock")]');
check($dockNodes->length === 1, "Bottom dock element exists with class 'partner-bottom-dock'");

$dockItems = $xpDock->query('//nav[contains(@class, "partner-bottom-dock")]/a');
check($dockItems->length === 5, "Bottom dock has EXACTLY 5 slot elements", "Found count: " . $dockItems->length);

if ($dockItems->length === 5) {
    // Slot 1: Hub
    $slot1 = $dockItems->item(0);
    $s1Href = $slot1->getAttribute('href');
    $s1Text = trim($slot1->textContent);
    check($s1Href === 'dashboard.php', "Slot 1 links to 'dashboard.php'", "href: $s1Href");
    check(strpos($s1Text, 'Hub') !== false, "Slot 1 label contains 'Hub'", "text: $s1Text");
    check(strpos($s1Text, '📊') !== false, "Slot 1 icon is '📊'", "text: $s1Text");

    // Slot 2: Customer Orders
    $slot2 = $dockItems->item(1);
    $s2Href = $slot2->getAttribute('href');
    $s2Text = trim($slot2->textContent);
    check($s2Href === 'orders.php', "Slot 2 links to 'orders.php'", "href: $s2Href");
    check(strpos($s2Text, 'Orders') !== false, "Slot 2 label contains 'Orders'", "text: $s2Text");
    check(strpos($s2Text, '📦') !== false, "Slot 2 icon is '📦'", "text: $s2Text");

    // Slot 3: Center FAB - Add Product
    $slot3 = $dockItems->item(2);
    $s3Href = $slot3->getAttribute('href');
    $s3Class = $slot3->getAttribute('class');
    $s3Text = trim($slot3->textContent);
    check($s3Href === 'product_add.php', "Slot 3 links to 'product_add.php'", "href: $s3Href");
    check(strpos($s3Class, 'dock-item-primary') !== false, "Slot 3 (center slot) has class 'dock-item-primary'", "class: $s3Class");
    check(strpos($s3Text, '➕') !== false, "Slot 3 icon is '➕'", "text: $s3Text");

    // Slot 4: Catalog
    $slot4 = $dockItems->item(3);
    $s4Href = $slot4->getAttribute('href');
    $s4Text = trim($slot4->textContent);
    check($s4Href === 'products.php', "Slot 4 links to 'products.php'", "href: $s4Href");
    check(strpos($s4Text, 'Catalog') !== false, "Slot 4 label contains 'Catalog'", "text: $s4Text");
    check(strpos($s4Text, '🛍️') !== false, "Slot 4 icon is '🛍️'", "text: $s4Text");

    // Slot 5: 1-Tap Buyer Mode
    $slot5 = $dockItems->item(4);
    $s5Href = $slot5->getAttribute('href');
    $s5Class = $slot5->getAttribute('class');
    $s5Text = trim($slot5->textContent);
    check($s5Href === '/user/dashboard.php', "Slot 5 links to '/user/dashboard.php'", "href: $s5Href");
    check(strpos($s5Class, 'dock-item-buyer') !== false, "Slot 5 has class 'dock-item-buyer'", "class: $s5Class");
    check(strpos($s5Text, 'Buyer Mode') !== false, "Slot 5 label contains 'Buyer Mode'", "text: $s5Text");
    check(strpos($s5Text, '👤') !== false, "Slot 5 icon is '👤'", "text: $s5Text");
}

// Verify obsolete Menu button is completely removed
$oldMenu = $xpDock->query('//nav[contains(@class, "partner-bottom-dock")]//a[contains(text(), "Menu") or contains(@href, "#menu")]');
check($oldMenu->length === 0, "Obsolete 'Menu' button is completely eradicated from bottom dock", "Found: " . $oldMenu->length);


echo PHP_EOL . "=================================================================" . PHP_EOL;
echo "3. TASK 3: CSS RULES & RESPONSIVENESS IN partner/nav.php" . PHP_EOL;
echo "=================================================================" . PHP_EOL;

$navSource = file_get_contents($navFile);

// Extract CSS inside <style>
preg_match('/<style>(.*?)<\/style>/is', $navSource, $styleMatch);
$cssBlock = $styleMatch[1] ?? $navSource;

// 1. @media (max-width: 600px) text collapse
echo "--- Checking @media (max-width: 600px) text collapse ---" . PHP_EOL;
check(preg_match('/@media\s*\(\s*max-width:\s*600px\s*\)/i', $cssBlock) === 1,
    "CSS contains '@media (max-width: 600px)' media query");

check(preg_match('/\.mode-pill-text-desktop\s*\{[^}]*display:\s*none\s*!important/i', $cssBlock) === 1,
    "CSS @media (max-width: 600px) hides .mode-pill-text-desktop with display:none !important");

check(preg_match('/\.mode-pill-text-mobile\s*\{[^}]*display:\s*inline\s*!important/i', $cssBlock) === 1,
    "CSS @media (max-width: 600px) shows .mode-pill-text-mobile with display:inline !important");

check(preg_match('/\.partner-badge\s*\{[^}]*display:\s*none\s*!important/i', $cssBlock) === 1,
    "CSS @media (max-width: 600px) hides .partner-badge with display:none !important to prevent header overflow");

// 2. Touch target dimensions (>= 44px min-height & min-width)
echo PHP_EOL . "--- Checking Touch Target Dimensions (>= 44px) ---" . PHP_EOL;

// .nav-back-btn
check(preg_match('/\.nav-back-btn\s*\{[^}]*min-width:\s*44px;[^}]*min-height:\s*44px;/is', $cssBlock) === 1,
    ".nav-back-btn specifies min-width: 44px and min-height: 44px");

// .hamburger-btn
check(preg_match('/\.hamburger-btn\s*\{[^}]*min-width:\s*44px;[^}]*min-height:\s*44px;/is', $cssBlock) === 1,
    ".hamburger-btn specifies min-width: 44px and min-height: 44px");

// .header-mode-pill
check(preg_match('/\.header-mode-pill\s*\{[^}]*min-width:\s*44px;[^}]*min-height:\s*44px;/is', $cssBlock) === 1,
    ".header-mode-pill specifies min-width: 44px and min-height: 44px");

// .app-hub-dropdown button
check(preg_match('/\.app-hub-dropdown\s+button\s*\{[^}]*min-width:\s*44px;[^}]*min-height:\s*44px;/is', $cssBlock) === 1,
    ".app-hub-dropdown button specifies min-width: 44px and min-height: 44px");

// .dock-item
check(preg_match('/\.dock-item\s*\{[^}]*min-height:\s*44px;/is', $cssBlock) === 1,
    ".dock-item specifies min-height: 44px");

// .dock-item-primary (FAB center slot)
check(preg_match('/\.dock-item-primary\s*\{[^}]*min-width:\s*48px;[^}]*min-height:\s*48px;/is', $cssBlock) === 1,
    ".dock-item-primary specifies min-width: 48px and min-height: 48px (>= 44px touch target)");

// 3. Safe-area insets syntax (env(safe-area-inset-bottom, 0px))
echo PHP_EOL . "--- Checking Safe-Area Insets Syntax ---" . PHP_EOL;

check(preg_match('/\.partner-bottom-dock\s*\{[^}]*height:\s*calc\(64px\s*\+\s*env\(safe-area-inset-bottom,\s*0px\)\);/is', $cssBlock) === 1,
    ".partner-bottom-dock height includes calc(64px + env(safe-area-inset-bottom, 0px))");

check(preg_match('/\.partner-bottom-dock\s*\{[^}]*padding:\s*6px\s*8px\s*calc\(env\(safe-area-inset-bottom,\s*0px\)\s*\+\s*6px\)\s*!important;/is', $cssBlock) === 1,
    ".partner-bottom-dock padding includes calc(env(safe-area-inset-bottom, 0px) + 6px) !important");

check(preg_match('/\.side-drawer\s*\{[^}]*bottom:\s*calc\(64px\s*\+\s*env\(safe-area-inset-bottom,\s*0px\)\)\s*!important;/is', $cssBlock) === 1,
    ".side-drawer bottom offset respects calc(64px + env(safe-area-inset-bottom, 0px)) on mobile");

check(preg_match('/body\s*\{[^}]*padding-bottom:\s*calc\(76px\s*\+\s*env\(safe-area-inset-bottom,\s*0px\)\)\s*!important;/is', $cssBlock) === 1,
    "body padding-bottom respects calc(76px + env(safe-area-inset-bottom, 0px)) on mobile");


echo PHP_EOL . "=================================================================" . PHP_EOL;
echo "4. ADVERSARIAL STRESS TESTING & EDGE CASES" . PHP_EOL;
echo "=================================================================" . PHP_EOL;

// Stress Test 1: Unauthenticated visitor -> Redirect to /user/login.php
echo "--- Stress Test 1: Unauthenticated visitor ---" . PHP_EOL;
check(preg_match('/if\s*\(\s*!isset\(\$_SESSION\[[\'"]user_id[\'"]\]\)\s*\)\s*\{\s*header\([\'"]Location:\s*\/user\/login\.php[\'"]\);\s*exit;\s*\}/is', $navSource) === 1,
    "partner/nav.php enforces strict unauthenticated redirect: Location: /user/login.php with immediate exit");

// Live HTTP check against Local Server Live Host
$curlOut = shell_exec('curl.exe -I -s http://localhost:8000/partner/dashboard.php');
if ($curlOut) {
    check(strpos($curlOut, 'Location: /user/login.php') !== false,
        "Live Server Live Host (localhost:8000) confirms HTTP 302 with Location: /user/login.php");
}

// Stress Test 2: User with non-existent partner record -> default values fallback without PHP errors
echo PHP_EOL . "--- Stress Test 2: User with non-existent partner record ---" . PHP_EOL;
$resMissing = renderNavIsolated('/partner/dashboard.php', null, 888888, 888888);
check(strpos($resMissing['html'], 'New Partner') !== false || strpos($resMissing['html'], 'partner-avatar') !== false,
    "Missing partner record gracefully falls back without crashing");
check(strpos($resMissing['raw'], 'Fatal error') === false && strpos($resMissing['raw'], 'Parse error') === false,
    "Missing partner record executes with ZERO fatal or parse errors");

// Stress Test 3: Impersonation state
echo PHP_EOL . "--- Stress Test 3: Admin impersonation session ---" . PHP_EOL;
$resImp = renderNavIsolated('/partner/dashboard.php', 0, 1, 1, true);
check(strpos($resImp['html'], 'return_to_admin.php') !== false,
    "Admin impersonation renders 'Return to Admin Panel' link");

// Stress Test 4: Active slot highlighting on each respective page
echo PHP_EOL . "--- Stress Test 4: Active Slot Highlighting ---" . PHP_EOL;
$pagesToTest = [
    '/partner/dashboard.php' => 'Hub',
    '/partner/orders.php' => 'Orders',
    '/partner/product_add.php' => '➕',
];
foreach ($pagesToTest as $pageUri => $expectedActiveText) {
    $r = renderNavIsolated($pageUri, 0);
    $xp = getXPath($r['html']);
    $activeDockItem = $xp->query('//nav[contains(@class, "partner-bottom-dock")]//a[contains(@class, "active")]');
    check($activeDockItem->length === 1 && strpos($activeDockItem->item(0)->textContent, $expectedActiveText) !== false,
        "Page '$pageUri' highlights correct active dock slot ('$expectedActiveText')");
}

// CRITICAL EMPIRICAL VULNERABILITY CHECK:
// Multi-page active highlight bug in partner/nav.php lines 738, 748, 815
echo PHP_EOL . "--- Stress Test 4b: Multi-Page Active Class Evaluation on products.php ---" . PHP_EOL;
$rProd = renderNavIsolated('/partner/products.php', 0);
$xpProd = getXPath($rProd['html']);
$activeProdDock = $xpProd->query('//nav[contains(@class, "partner-bottom-dock")]//a[contains(@class, "active")]');

$renderedClassDock = '';
$dockLinks = $xpProd->query('//nav[contains(@class, "partner-bottom-dock")]/a');
foreach ($dockLinks as $dl) {
    if ($dl->getAttribute('href') === 'products.php') {
        $renderedClassDock = $dl->getAttribute('class');
        break;
    }
}

// Check if class is 'active' vs '1'
check($activeProdDock->length === 1 && strpos($renderedClassDock, 'active') !== false,
    "Page '/partner/products.php' properly applies class 'active' to Catalog dock item",
    "ACTUAL RENDERED CLASS IS: '$renderedClassDock' (Boolean '1' stringification bug!)");

// Stress Test 5: partner/dashboard.php quick action buyer button
echo PHP_EOL . "--- Stress Test 5: partner/dashboard.php quick action buyer button ---" . PHP_EOL;
$dashSource = file_get_contents($dashFile);
check(strpos($dashSource, 'class="btn-action-hero btn-action-buyer"') !== false,
    "partner/dashboard.php contains '.btn-action-hero btn-action-buyer'");
check(preg_match('/<a[^>]*href="\/user\/dashboard\.php"[^>]*class="btn-action-hero btn-action-buyer"[^>]*>/i', $dashSource) === 1,
    "partner/dashboard.php buyer button links directly to '/user/dashboard.php'");
check(preg_match('/\.btn-action-buyer\s*\{[^}]*background:\s*rgba\(33,\s*150,\s*243/i', $dashSource) === 1,
    "partner/dashboard.php defines sky-blue styling for .btn-action-buyer");
check(preg_match('/\.btn-action-buyer:active\s*\{[^}]*transform:\s*scale\(0\.96\)/i', $dashSource) === 1,
    "partner/dashboard.php defines transform: scale(0.96) active tap compression for .btn-action-buyer");


echo PHP_EOL . "=================================================================" . PHP_EOL;
echo "TEST SUMMARY" . PHP_EOL;
echo "=================================================================" . PHP_EOL;
echo "Total Checks: $totalChecks" . PHP_EOL;
echo "Passed Checks: $passedChecks" . PHP_EOL;
echo "Failed Checks: " . count($failedChecks) . PHP_EOL;

if (count($failedChecks) > 0) {
    echo PHP_EOL . "FAILED CHECKS DETAILS:" . PHP_EOL;
    foreach ($failedChecks as $fail) {
        echo "  - $fail" . PHP_EOL;
    }
    echo PHP_EOL . "FINAL VERDICT: REQUEST_CHANGES" . PHP_EOL;
    exit(1);
} else {
    echo PHP_EOL . "FINAL VERDICT: ALL $totalChecks CHECKS PASSED (APPROVE)" . PHP_EOL;
    exit(0);
}
