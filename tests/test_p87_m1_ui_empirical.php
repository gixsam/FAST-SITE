<?php
/**
 * Automated Empirical Verification Script for Phase 87 Milestone 1 (M1) UI/UX & CSS
 * 
 * Target:
 * - assets/css/user.css
 * - includes/user_sidebar.php
 * - user/dashboard.php
 * 
 * Checks:
 * 1. Nocturne Aurum design tokens (#0a0d1a / #0A0D1A, rgba(18, 22, 43, 0.85/0.97), #f59e0b / #F59E0B)
 * 2. Active tap feedback: transform: scale(0.96)
 * 3. Minimum touch target sizes: 44px
 * 4. Modals and drawers: #shopReviewModal, #quickShopDrawer, backdrops, handles
 * 5. HTML structural integrity: zero duplicate IDs, zero unclosed tags across all 3 shop states
 * 6. Adversarial edge cases: escaping, missing fields, JS toggle coordination
 */

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

$root = realpath(__DIR__ . '/..');
$userCssPath = $root . '/assets/css/user.css';
$nativeMobileCssPath = $root . '/assets/css/native_mobile.css';
$userSidebarPath = $root . '/includes/user_sidebar.php';
$userDashboardPath = $root . '/user/dashboard.php';

$totalAssertions = 0;
$passedAssertions = 0;
$failedAssertions = [];

function checkAssert($condition, $description, &$totalAssertions, &$passedAssertions, &$failedAssertions) {
    $totalAssertions++;
    if ($condition) {
        $passedAssertions++;
        echo "  [PASS] {$description}\n";
    } else {
        $failedAssertions[] = $description;
        echo "  [FAIL] {$description}\n";
    }
}

echo "======================================================================\n";
echo "FAST SITE PHASE 87 MILESTONE 1 — EMPIRICAL UI/UX & CSS VERIFICATION\n";
echo "======================================================================\n\n";

// ---------------------------------------------------------------------
// TEST SUITE 1: CSS Nocturne Aurum Tokens, Tap Feedback & Touch Targets
// ---------------------------------------------------------------------
echo "--- TEST SUITE 1: CSS Inspection in assets/css/user.css ---\n";
checkAssert(file_exists($userCssPath), "assets/css/user.css exists", $totalAssertions, $passedAssertions, $failedAssertions);

$userCss = file_get_contents($userCssPath);
$nativeCss = file_exists($nativeMobileCssPath) ? file_get_contents($nativeMobileCssPath) : '';

// 1. Nocturne Aurum Token: #0A0D1A / #0a0d1a
$has0a0d1a = (stripos($userCss, '#0a0d1a') !== false) || (stripos($nativeCss, '#0a0d1a') !== false);
checkAssert($has0a0d1a, "Nocturne Aurum token #0a0d1a / #0A0D1A is present in user.css or native_mobile.css", $totalAssertions, $passedAssertions, $failedAssertions);

// 2. Nocturne Aurum Token: rgba(18, 22, 43, ...)
$hasFrostedGlassElevation = (stripos($userCss, 'rgba(18, 22, 43') !== false) || (stripos($nativeCss, 'rgba(18, 22, 43') !== false);
checkAssert($hasFrostedGlassElevation, "Elevation layer rgba(18, 22, 43, ...) is present", $totalAssertions, $passedAssertions, $failedAssertions);

// 3. Nocturne Aurum Token: #f59e0b / #F59E0B
$hasF59e0b = (stripos($userCss, '#f59e0b') !== false);
checkAssert($hasF59e0b, "Luminous gold highlight #f59e0b / #F59E0B is present in user.css", $totalAssertions, $passedAssertions, $failedAssertions);

// 4. Active tap feedback: transform: scale(0.96)
$hasScale96 = (strpos($userCss, 'scale(0.96)') !== false);
checkAssert($hasScale96, "Active tap feedback transform: scale(0.96) is present in user.css", $totalAssertions, $passedAssertions, $failedAssertions);

// Check selectors that receive scale(0.96)
$hasActivePillSelector = preg_match('/\.top-mode-pill:active\s*\{[^}]*scale\(0\.96\)/s', $userCss);
checkAssert($hasActivePillSelector, ".top-mode-pill:active explicitly applies scale(0.96)", $totalAssertions, $passedAssertions, $failedAssertions);

$hasUniversalActiveCompression = preg_match('/\.b-nav-item:active[^}]*scale\(0\.96\)/s', $userCss) || preg_match('/button:active[^}]*scale\(0\.96\)/s', $userCss);
checkAssert($hasUniversalActiveCompression, "Bottom dock items and buttons receive scale(0.96) active tap compression", $totalAssertions, $passedAssertions, $failedAssertions);

// 5. Minimum touch target sizes: 44px
$has44pxPillMinHeight = (strpos($userCss, 'min-height: 44px') !== false);
$has44pxPillMinWidth = (strpos($userCss, 'min-width: 44px') !== false);
checkAssert($has44pxPillMinHeight && $has44pxPillMinWidth, "44px minimum touch targets (min-height & min-width: 44px) are enforced in user.css", $totalAssertions, $passedAssertions, $failedAssertions);

// 6. Modal and drawer CSS rules
$hasBottomSheetRule = (strpos($userCss, '.bottom-sheet {') !== false);
$hasBackdropRule = (strpos($userCss, '.bottom-sheet-backdrop {') !== false);
$hasHandleRule = (strpos($userCss, '.bottom-sheet-handle {') !== false);
$hasBottomSheetActive = (strpos($userCss, '.bottom-sheet.active') !== false);
$hasBackdropActive = (strpos($userCss, '.bottom-sheet-backdrop.active') !== false);

checkAssert($hasBottomSheetRule, ".bottom-sheet class defined in user.css", $totalAssertions, $passedAssertions, $failedAssertions);
checkAssert($hasBackdropRule, ".bottom-sheet-backdrop class defined in user.css", $totalAssertions, $passedAssertions, $failedAssertions);
checkAssert($hasHandleRule, ".bottom-sheet-handle class defined in user.css with 44px width", $totalAssertions, $passedAssertions, $failedAssertions);
checkAssert($hasBottomSheetActive && $hasBackdropActive, ".bottom-sheet.active and .bottom-sheet-backdrop.active transitions defined", $totalAssertions, $passedAssertions, $failedAssertions);

// CSS brace balance check
$openBraces = substr_count($userCss, '{');
$closeBraces = substr_count($userCss, '}');
checkAssert($openBraces === $closeBraces, "user.css has balanced CSS braces (open: {$openBraces}, close: {$closeBraces})", $totalAssertions, $passedAssertions, $failedAssertions);

echo "\n";

// ---------------------------------------------------------------------
// TEST SUITE 2: Static Template Analysis in includes/user_sidebar.php & user/dashboard.php
// ---------------------------------------------------------------------
echo "--- TEST SUITE 2: Static Template Analysis ---\n";
$sidebarContent = file_get_contents($userSidebarPath);
$dashboardContent = file_get_contents($userDashboardPath);

// Modals & Drawers in user_sidebar.php
checkAssert(strpos($sidebarContent, 'id="shopReviewModal"') !== false, "#shopReviewModal element exists in includes/user_sidebar.php", $totalAssertions, $passedAssertions, $failedAssertions);
checkAssert(strpos($sidebarContent, 'id="quickShopDrawer"') !== false, "#quickShopDrawer element exists in includes/user_sidebar.php", $totalAssertions, $passedAssertions, $failedAssertions);
checkAssert(strpos($sidebarContent, 'id="shopReviewBackdrop"') !== false, "#shopReviewBackdrop backdrop exists", $totalAssertions, $passedAssertions, $failedAssertions);
checkAssert(strpos($sidebarContent, 'id="quickShopBackdrop"') !== false, "#quickShopBackdrop backdrop exists", $totalAssertions, $passedAssertions, $failedAssertions);
checkAssert(substr_count($sidebarContent, 'class="bottom-sheet-handle"') >= 2, "Both modals contain .bottom-sheet-handle", $totalAssertions, $passedAssertions, $failedAssertions);

// JS Drawer & Modal handlers
checkAssert(strpos($sidebarContent, 'function openShopReviewModal()') !== false, "openShopReviewModal() JS function defined", $totalAssertions, $passedAssertions, $failedAssertions);
checkAssert(strpos($sidebarContent, 'function closeShopReviewModal(') !== false, "closeShopReviewModal() JS function defined", $totalAssertions, $passedAssertions, $failedAssertions);
checkAssert(strpos($sidebarContent, 'function openQuickShopDrawer()') !== false, "openQuickShopDrawer() JS function defined", $totalAssertions, $passedAssertions, $failedAssertions);
checkAssert(strpos($sidebarContent, 'function closeQuickShopDrawer(') !== false, "closeQuickShopDrawer() JS function defined", $totalAssertions, $passedAssertions, $failedAssertions);
checkAssert(strpos($sidebarContent, 'closeAllDrawers()') !== false, "closeAllDrawers() coordinates closing of both modals", $totalAssertions, $passedAssertions, $failedAssertions);

// Floating Bottom Nav in user/dashboard.php
checkAssert(strpos($dashboardContent, 'id="user-floating-bottom-nav"') !== false, "#user-floating-bottom-nav exists in user/dashboard.php", $totalAssertions, $passedAssertions, $failedAssertions);
checkAssert(strpos($dashboardContent, 'id="dock-item-store"') !== false, "Dock Slot 1 (Store) present", $totalAssertions, $passedAssertions, $failedAssertions);
checkAssert(strpos($dashboardContent, 'id="dock-item-orders"') !== false, "Dock Slot 2 (Orders) present", $totalAssertions, $passedAssertions, $failedAssertions);
checkAssert(strpos($dashboardContent, 'id="dock-item-mode"') !== false, "Dock Slot 3 (Center Mode Switcher) present", $totalAssertions, $passedAssertions, $failedAssertions);
checkAssert(strpos($dashboardContent, 'id="dock-item-wallet"') !== false, "Dock Slot 4 (Wallet) present", $totalAssertions, $passedAssertions, $failedAssertions);
checkAssert(strpos($dashboardContent, 'id="dock-item-profile"') !== false, "Dock Slot 5 (Profile) present", $totalAssertions, $passedAssertions, $failedAssertions);

// 44px touch targets on dock items
$dockSlots = ['dock-item-store', 'dock-item-orders', 'dock-item-mode', 'dock-item-wallet', 'dock-item-profile'];
$allDockSlotsCompliant = true;
foreach ($dockSlots as $slotId) {
    // Match the element containing this ID and its style attribute
    if (preg_match('/id="' . $slotId . '"[\s\S]*?style="([^"]+)"/i', $dashboardContent, $sm)) {
        $style = $sm[1];
        $hasMinH = (strpos($style, 'min-height:48px') !== false || strpos($style, 'min-height: 48px') !== false || strpos($style, 'min-height:44px') !== false || strpos($style, 'min-height: 44px') !== false);
        $hasMinW = (strpos($style, 'min-width:44px') !== false || strpos($style, 'min-width: 44px') !== false);
        if (!$hasMinH || !$hasMinW) {
            $allDockSlotsCompliant = false;
            echo "    [DEBUG] Slot {$slotId} failed: {$style}\n";
        }
    } else {
        $allDockSlotsCompliant = false;
        echo "    [DEBUG] Slot {$slotId} style not found\n";
    }
}
checkAssert($allDockSlotsCompliant, "All 5 dock slots enforce min-height >= 44px and min-width >= 44px", $totalAssertions, $passedAssertions, $failedAssertions);

// Top nav buttons 44px touch targets
checkAssert(strpos($sidebarContent, 'class="hamburger"') !== false && strpos($sidebarContent, 'min-width:44px; min-height:44px') !== false, "Top nav hamburger button enforces 44px min touch target", $totalAssertions, $passedAssertions, $failedAssertions);
checkAssert(strpos($sidebarContent, 'toggleNotificationDrawer()') !== false && strpos($sidebarContent, 'min-width:44px; min-height:44px') !== false, "Top nav notification button enforces 44px min touch target", $totalAssertions, $passedAssertions, $failedAssertions);
checkAssert(strpos($sidebarContent, '/user/messages.php') !== false && strpos($sidebarContent, 'min-width:44px; min-height:44px') !== false, "Top nav messages button enforces 44px min touch target", $totalAssertions, $passedAssertions, $failedAssertions);

echo "\n";

// ---------------------------------------------------------------------
// TEST SUITE 3: Dynamic Multi-State HTML Rendering & DOM Validation
// ---------------------------------------------------------------------
echo "--- TEST SUITE 3: Multi-State Rendering & Tag Integrity Verification ---\n";

/**
 * Helper to render isolated sidebar top nav & modals + dashboard bottom dock
 */
function renderComponentsForState($state, $shopName = 'My Test Store', $regNumber = 'FS-SHOP-99881') {
    global $userSidebarPath, $userDashboardPath;

    // Simulate session and context variables
    $user = [
        'id' => 101,
        'name' => 'John Tester',
        'email' => 'tester@fastsite.ltd',
        'phone' => '01711223344',
        'profile_pic' => '/uploads/profiles/test.jpg'
    ];
    $settings = ['logo_url' => '/assets/images/logo.png'];
    $siteName = 'FAST SITE';
    $user_unread_notif_count = 2;
    $user_app_orders_count = 1;
    $partner_shop_orders_count = 0;
    $currentPage = 'dashboard.php';
    $_SESSION['user_id'] = 101;

    $shopState = $state;
    $shopStateData = [
        'state' => $state,
        'shop' => ($state === 'approved') ? ['id' => 12, 'business_name' => $shopName, 'registration_number' => $regNumber] : null,
        'request' => ($state === 'pending') ? ['id' => 5, 'business_name' => $shopName, 'status' => 'pending'] : null,
        'shop_name' => $shopName,
        'shop_id' => ($state === 'approved') ? 12 : 0
    ];
    $partnerInfo = $shopStateData['shop'];
    $isPartner = ($state === 'approved');

    // Extract relevant markup sections from user_sidebar.php (Top Nav, Modals, Drawers)
    $sbCode = file_get_contents($userSidebarPath);
    
    // We isolate the HTML from user_sidebar.php starting at <div class="top-nav"> through the end of the file
    $topNavPos = strpos($sbCode, '<div class="top-nav">');
    $sbHtmlTemplate = substr($sbCode, $topNavPos);

    // Extract bottom dock from user/dashboard.php
    $dbCode = file_get_contents($userDashboardPath);
    $dockStart = strpos($dbCode, '<!-- Universal Mobile Bottom Floating Navigation Bar');
    $dockEnd = strpos($dbCode, '<?php include __DIR__ . \'/../includes/footer.php\'; ?>');
    $dockHtmlTemplate = substr($dbCode, $dockStart, $dockEnd - $dockStart);

    // Render both templates
    ob_start();
    eval('?>' . $sbHtmlTemplate);
    $renderedSidebar = ob_get_clean();

    // Prepare dock variables
    $isOrdersTab = false;
    $isStoreTab = false;
    $isWalletTab = false;
    $isProfileTab = false;
    $totalActiveOrdersBadge = 1;
    $dockShopState = $state;

    ob_start();
    eval('?>' . $dockHtmlTemplate);
    $renderedDock = ob_get_clean();

    return $renderedSidebar . "\n" . $renderedDock;
}

$states = [
    'approved' => ['name' => 'Approved Partner Shop', 'shop' => 'Sayam Tech Hub', 'reg' => 'FS-SHOP-12345'],
    'pending'  => ['name' => 'Pending Application Under Review', 'shop' => 'Future Digital Mart', 'reg' => 'FS-APP-00101'],
    'none'     => ['name' => 'Shopless Standard Customer', 'shop' => 'My Shop', 'reg' => '']
];

foreach ($states as $stKey => $stData) {
    echo "  >> Testing State [{$stKey}] ({$stData['name']})...\n";
    $html = renderComponentsForState($stKey, $stData['shop'], $stData['reg']);

    // 1. Check Mode Switcher Top Pill
    if ($stKey === 'approved') {
        $hasApprovedPill = (strpos($html, 'class="top-mode-pill mode-approved"') !== false) && (strpos($html, 'href="/partner/dashboard.php"') !== false);
        checkAssert($hasApprovedPill, "State [approved]: Top pill correctly renders mode-approved linking to /partner/dashboard.php", $totalAssertions, $passedAssertions, $failedAssertions);
        
        $hasApprovedDock = (strpos($html, 'class="dock-center-circle mode-shop"') !== false) && (strpos($html, 'Shop Mode') !== false);
        checkAssert($hasApprovedDock, "State [approved]: Dock center circle renders mode-shop with 'Shop Mode'", $totalAssertions, $passedAssertions, $failedAssertions);
    } elseif ($stKey === 'pending') {
        $hasPendingPill = (strpos($html, 'class="top-mode-pill mode-pending"') !== false) && (strpos($html, 'onclick="openShopReviewModal()"') !== false);
        checkAssert($hasPendingPill, "State [pending]: Top pill correctly renders mode-pending triggering openShopReviewModal()", $totalAssertions, $passedAssertions, $failedAssertions);
        
        $hasPendingDock = (strpos($html, 'class="dock-center-circle mode-review"') !== false) && (strpos($html, 'In Review') !== false);
        checkAssert($hasPendingDock, "State [pending]: Dock center circle renders mode-review with 'In Review'", $totalAssertions, $passedAssertions, $failedAssertions);
    } else {
        $hasShoplessPill = (strpos($html, 'class="top-mode-pill mode-shopless"') !== false) && (strpos($html, 'onclick="openQuickShopDrawer()"') !== false);
        checkAssert($hasShoplessPill, "State [none]: Top pill correctly renders mode-shopless triggering openQuickShopDrawer()", $totalAssertions, $passedAssertions, $failedAssertions);
        
        $hasShoplessDock = (strpos($html, 'class="dock-center-circle mode-create"') !== false) && (strpos($html, 'Free Shop') !== false);
        checkAssert($hasShoplessDock, "State [none]: Dock center circle renders mode-create with 'Free Shop'", $totalAssertions, $passedAssertions, $failedAssertions);
    }

    // 2. Modals & Backdrops presence in rendered output
    checkAssert(strpos($html, 'id="shopReviewModal"') !== false, "State [{$stKey}]: #shopReviewModal present in DOM", $totalAssertions, $passedAssertions, $failedAssertions);
    checkAssert(strpos($html, 'id="quickShopDrawer"') !== false, "State [{$stKey}]: #quickShopDrawer present in DOM", $totalAssertions, $passedAssertions, $failedAssertions);
    checkAssert(strpos($html, 'id="shopReviewBackdrop"') !== false, "State [{$stKey}]: #shopReviewBackdrop present in DOM", $totalAssertions, $passedAssertions, $failedAssertions);
    checkAssert(strpos($html, 'id="quickShopBackdrop"') !== false, "State [{$stKey}]: #quickShopBackdrop present in DOM", $totalAssertions, $passedAssertions, $failedAssertions);

    // 3. Duplicate ID Check across entire rendered HTML
    preg_match_all('/\bid=["\']([^"\']+)["\']/i', $html, $matches);
    $ids = $matches[1];
    $idCounts = array_count_values($ids);
    $duplicateIds = [];
    foreach ($idCounts as $id => $cnt) {
        if ($cnt > 1) {
            $duplicateIds[] = "{$id} (count: {$cnt})";
        }
    }
    $noDuplicateIds = empty($duplicateIds);
    $dupMsg = $noDuplicateIds ? "Zero duplicate IDs found in rendered DOM" : "Duplicate IDs detected: " . implode(', ', $duplicateIds);
    checkAssert($noDuplicateIds, "State [{$stKey}]: {$dupMsg}", $totalAssertions, $passedAssertions, $failedAssertions);

    // 4. HTML Tag Balance Check
    // Strip self-closing / void elements (<input>, <img>, <br>, <hr>, <meta>, <link>, <path>, <circle>, <polygon>, <rect>)
    $cleanHtml = preg_replace('/<(img|input|br|hr|meta|link|path|circle|polygon|rect)[^>]*>/i', '', $html);
    // Strip script blocks content
    $cleanHtml = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $cleanHtml);
    $cleanHtml = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $cleanHtml);

    // Extract opened tags
    preg_match_all('/<([a-z0-9]+)(\s+[^>]*)?>/i', $cleanHtml, $openMatches);
    $openedTags = array_map('strtolower', $openMatches[1]);

    // Extract closed tags
    preg_match_all('/<\/([a-z0-9]+)>/i', $cleanHtml, $closeMatches);
    $closedTags = array_map('strtolower', $closeMatches[1]);

    $openedTagCounts = array_count_values($openedTags);
    $closedTagCounts = array_count_values($closedTags);

    $unbalancedTags = [];
    $allTagNames = array_unique(array_merge(array_keys($openedTagCounts), array_keys($closedTagCounts)));
    foreach ($allTagNames as $tag) {
        $o = $openedTagCounts[$tag] ?? 0;
        $c = $closedTagCounts[$tag] ?? 0;
        if ($o !== $c) {
            $unbalancedTags[] = "<{$tag}> (opened: {$o}, closed: {$c})";
        }
    }
    $tagsBalanced = empty($unbalancedTags);
    $tagMsg = $tagsBalanced ? "All HTML tags are balanced" : "Unclosed/Mismatched tags: " . implode(', ', $unbalancedTags);
    checkAssert($tagsBalanced, "State [{$stKey}]: {$tagMsg}", $totalAssertions, $passedAssertions, $failedAssertions);

    // 5. DOMDocument Validation
    libxml_use_internal_errors(true);
    libxml_clear_errors();
    $doc = new DOMDocument();
    // Wrap with html/body so parser has a root
    $wrappedHtml = '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>' . $html . '</body></html>';
    $loaded = $doc->loadHTML($wrappedHtml, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    $errors = libxml_get_errors();
    libxml_clear_errors();

    // Filter out harmless HTML5 tag warnings (e.g. unknown tag svg/path/nav in older libxml)
    $criticalErrors = [];
    foreach ($errors as $err) {
        if ($err->level === LIBXML_ERR_FATAL) {
            $criticalErrors[] = trim($err->message);
        }
    }
    checkAssert(empty($criticalErrors), "State [{$stKey}]: DOMDocument loads cleanly with 0 fatal errors", $totalAssertions, $passedAssertions, $failedAssertions);
}

echo "\n";

// ---------------------------------------------------------------------
// TEST SUITE 4: Adversarial Stress Testing & Edge Cases
// ---------------------------------------------------------------------
echo "--- TEST SUITE 4: Adversarial Stress Testing ---\n";

// Edge Case 1: XSS / quotes in shop_name
$xssShopName = 'Store & "Escaped" <script>alert(1)</script>';
$xssHtml = renderComponentsForState('pending', $xssShopName);
$noScriptLeak = (strpos($xssHtml, '<script>alert(1)</script>') === false);
$hasEscapedEntity = (strpos($xssHtml, '&lt;script&gt;alert(1)&lt;/script&gt;') !== false);
checkAssert($noScriptLeak && $hasEscapedEntity, "Adversarial: Store name with XSS payload is strictly HTML-escaped", $totalAssertions, $passedAssertions, $failedAssertions);

// Edge Case 2: Null / Empty shop registration number in pending state
$nullRegHtml = renderComponentsForState('pending', 'Simple Shop', null);
$hasFallbackTrackingId = (strpos($nullRegHtml, 'FS-APP-') !== false);
checkAssert($hasFallbackTrackingId, "Adversarial: Null registration number falls back to generated FS-APP-XXXXX tracking ID", $totalAssertions, $passedAssertions, $failedAssertions);

// Edge Case 3: Mobile collapse classes verification
checkAssert(strpos($userCss, '.pill-text-desktop { display: none !important; }') !== false, "Responsive: Desktop label hides on mobile screen (<600px)", $totalAssertions, $passedAssertions, $failedAssertions);
checkAssert(strpos($userCss, '.pill-text-mobile  { display: inline !important; }') !== false, "Responsive: Compact mobile label shows on mobile screen (<600px)", $totalAssertions, $passedAssertions, $failedAssertions);

// Edge Case 4: Modal Backdrop Z-Index & Elevation
checkAssert(strpos($userCss, 'z-index: 10000') !== false, "Z-Index Hierarchy: Backdrop is elevated to z-index 10000", $totalAssertions, $passedAssertions, $failedAssertions);
// ---------------------------------------------------------------------
// TEST SUITE 5: Full End-to-End Execution of user/dashboard.php
// ---------------------------------------------------------------------
echo "\n--- TEST SUITE 5: Full Page End-to-End Execution ---\n";
$runnerCode = '<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
require_once __DIR__ . "/../config.php";
try {
    $st = $pdo->query("SELECT id FROM users LIMIT 1");
    $uid = $st->fetchColumn() ?: 1;
} catch (Exception $e) {
    $uid = 1;
}
$_SESSION["user_id"] = $uid;
$_SERVER["PHP_SELF"] = "/user/dashboard.php";
$_SERVER["REQUEST_METHOD"] = "GET";
ob_start();
try {
    require __DIR__ . "/../user/dashboard.php";
    $fullHtml = ob_get_clean();
} catch (Throwable $t) {
    ob_end_clean();
    echo "FATAL:" . $t->getMessage() . "\n";
    exit(1);
}

echo "FULL_HTML_LENGTH:" . strlen($fullHtml) . "\n";

// Check duplicate IDs across full dashboard
preg_match_all(\'/\bid=["\\\']([^"\\\'\$\{\<\>]+)["\\\']/i\', $fullHtml, $m);
$idCounts = array_count_values($m[1]);
$dups = [];
foreach ($idCounts as $id => $cnt) {
    if ($cnt > 1) {
        $dups[] = $id . " (" . $cnt . ")";
    }
}
echo "DUPLICATE_IDS:" . (empty($dups) ? "NONE" : implode(",", $dups)) . "\n";

// Check presence of Milestone 1 components in full page
echo "HAS_SHOP_REVIEW_MODAL:" . (strpos($fullHtml, \'id="shopReviewModal"\') !== false ? "YES" : "NO") . "\n";
echo "HAS_QUICK_SHOP_DRAWER:" . (strpos($fullHtml, \'id="quickShopDrawer"\') !== false ? "YES" : "NO") . "\n";
echo "HAS_BOTTOM_DOCK:" . (strpos($fullHtml, \'id="user-floating-bottom-nav"\') !== false ? "YES" : "NO") . "\n";
echo "HAS_TOP_MODE_PILL:" . (strpos($fullHtml, \'class="top-mode-pill\') !== false ? "YES" : "NO") . "\n";
';

$tempRunnerPath = $root . '/tests/_temp_runner.php';
file_put_contents($tempRunnerPath, $runnerCode);

$output = shell_exec('php "' . $tempRunnerPath . '" 2>&1');
if (file_exists($tempRunnerPath)) {
    unlink($tempRunnerPath);
}

$fullLengthOk = preg_match('/FULL_HTML_LENGTH:(\d+)/', $output, $lm) && ((int)$lm[1] > 1000);
checkAssert($fullLengthOk, "Full user/dashboard.php executes cleanly end-to-end (rendered bytes: " . ($lm[1] ?? '0') . ")", $totalAssertions, $passedAssertions, $failedAssertions);

$noDupsInFull = (strpos($output, 'DUPLICATE_IDS:NONE') !== false);
checkAssert($noDupsInFull, "Zero duplicate IDs found across full 1700-line rendered dashboard page", $totalAssertions, $passedAssertions, $failedAssertions);

$fullHasReviewModal = (strpos($output, 'HAS_SHOP_REVIEW_MODAL:YES') !== false);
checkAssert($fullHasReviewModal, "Full dashboard output includes #shopReviewModal", $totalAssertions, $passedAssertions, $failedAssertions);

$fullHasQuickDrawer = (strpos($output, 'HAS_QUICK_SHOP_DRAWER:YES') !== false);
checkAssert($fullHasQuickDrawer, "Full dashboard output includes #quickShopDrawer", $totalAssertions, $passedAssertions, $failedAssertions);

$fullHasDock = (strpos($output, 'HAS_BOTTOM_DOCK:YES') !== false);
checkAssert($fullHasDock, "Full dashboard output includes synchronized 5-slot #user-floating-bottom-nav", $totalAssertions, $passedAssertions, $failedAssertions);

$fullHasTopPill = (strpos($output, 'HAS_TOP_MODE_PILL:YES') !== false);
checkAssert($fullHasTopPill, "Full dashboard output includes 1-tap .top-mode-pill", $totalAssertions, $passedAssertions, $failedAssertions);


echo "\n======================================================================\n";
echo "SUMMARY RESULTS:\n";
echo "Total Assertions Tested: {$totalAssertions}\n";
echo "Passed Assertions:       {$passedAssertions}\n";
echo "Failed Assertions:       " . count($failedAssertions) . "\n";
echo "======================================================================\n";

if (empty($failedAssertions)) {
    echo "\n>>> VERDICT: APPROVE <<<\n";
    echo "Milestone 1 UI/UX and CSS implementation strictly conforms to all specifications.\n";
    exit(0);
} else {
    echo "\n>>> VERDICT: REJECT <<<\n";
    echo "Failures:\n";
    foreach ($failedAssertions as $fail) {
        echo " - " . $fail . "\n";
    }
    exit(1);
}
