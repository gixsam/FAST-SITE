<?php
/**
 * Automated DOM & CSS Verification Script for Phase 87 Milestone 1
 * Verifies DOM IDs, classes, touch targets, and accessibility requirements
 * in includes/user_sidebar.php, user/dashboard.php, and assets/css/user.css
 */

$sidebarFile = __DIR__ . '/../includes/user_sidebar.php';
$dashboardFile = __DIR__ . '/../user/dashboard.php';
$cssFile = __DIR__ . '/../assets/css/user.css';

$sidebarContent = file_get_contents($sidebarFile);
$dashboardContent = file_get_contents($dashboardFile);
$cssContent = file_get_contents($cssFile);

$totalChecks = 0;
$passedChecks = 0;
$failedChecks = [];

function check($condition, $description, $details = '') {
    global $totalChecks, $passedChecks, $failedChecks;
    $totalChecks++;
    if ($condition) {
        $passedChecks++;
        echo "  [PASS] $description" . PHP_EOL;
    } else {
        $failedChecks[] = "$description: $details";
        echo "  [FAIL] $description: $details" . PHP_EOL;
    }
}

echo "=======================================================" . PHP_EOL;
echo "1. VERIFYING TOP HEADER & USER SIDEBAR (user_sidebar.php)" . PHP_EOL;
echo "=======================================================" . PHP_EOL;

// 1. Top mode pills
check(strpos($sidebarContent, 'class="top-mode-pill mode-approved"') !== false, 
    "user_sidebar.php contains '.top-mode-pill mode-approved'");
check(strpos($sidebarContent, 'class="top-mode-pill mode-pending"') !== false, 
    "user_sidebar.php contains '.top-mode-pill mode-pending'");
check(strpos($sidebarContent, 'class="top-mode-pill mode-shopless"') !== false, 
    "user_sidebar.php contains '.top-mode-pill mode-shopless'");

// 2. Pill links and event handlers
check(strpos($sidebarContent, 'href="/partner/dashboard.php"') !== false, 
    "Approved mode links to /partner/dashboard.php");
check(strpos($sidebarContent, 'onclick="openShopReviewModal()"') !== false, 
    "Pending mode invokes openShopReviewModal()");
check(strpos($sidebarContent, 'onclick="openQuickShopDrawer()"') !== false, 
    "Shopless mode invokes openQuickShopDrawer()");

// 3. Responsive text containers
check(strpos($sidebarContent, 'class="pill-text-desktop"') !== false, 
    "Contains .pill-text-desktop class");
check(strpos($sidebarContent, 'class="pill-text-mobile"') !== false, 
    "Contains .pill-text-mobile class");

// 4. Header buttons touch targets (44px)
check(preg_match('/class="hamburger"[^>]*width:44px;\s*height:44px/i', $sidebarContent) === 1,
    "Hamburger button specifies 44px x 44px touch target inline/class");
check(preg_match('/onclick="toggleNotificationDrawer\(\)"[^>]*width:44px;\s*height:44px/i', $sidebarContent) === 1,
    "Notification Bell specifies 44px x 44px touch target");
check(preg_match('/href="\/user\/messages\.php"[^>]*width:44px;\s*height:44px/i', $sidebarContent) === 1,
    "Messages header button specifies 44px x 44px touch target");

// 5. Onboarding and Review Modals DOM IDs
check(strpos($sidebarContent, 'id="shopReviewModal"') !== false, 
    "Contains modal ID #shopReviewModal");
check(strpos($sidebarContent, 'id="shopReviewBackdrop"') !== false, 
    "Contains backdrop ID #shopReviewBackdrop");
check(strpos($sidebarContent, 'id="quickShopDrawer"') !== false, 
    "Contains drawer ID #quickShopDrawer");
check(strpos($sidebarContent, 'id="quickShopBackdrop"') !== false, 
    "Contains backdrop ID #quickShopBackdrop");

// 6. Review Modal elements
check(strpos($sidebarContent, 'pulse-gold') !== false, 
    "Review modal contains pulsating gold status icon");
check(strpos($sidebarContent, 'Estimated Review SLA:') !== false, 
    "Review modal includes 24-48h SLA display");
check(strpos($sidebarContent, 'stepper-item') !== false, 
    "Review modal contains .stepper-item progress steps");
check(strpos($sidebarContent, 'https://wa.me/8801963601472') !== false, 
    "Review modal contains WhatsApp priority review support link");

// 7. Quick Shop Drawer Form & Fields
check(preg_match('/<form[^>]+action="\/user\/create_shop\.php"[^>]+method="POST"/i', $sidebarContent) === 1,
    "Quick shop drawer submits to /user/create_shop.php via POST");
check(strpos($sidebarContent, 'name="business_name"') !== false, 
    "Form contains input 'business_name'");
check(strpos($sidebarContent, 'name="description"') !== false, 
    "Form contains category select 'description'");
check(strpos($sidebarContent, 'name="payout_account"') !== false, 
    "Form contains input 'payout_account'");
check(strpos($sidebarContent, 'name="accept_terms"') !== false, 
    "Form contains hidden input 'accept_terms'");

// 8. JS Controller functions in user_sidebar.php
check(strpos($sidebarContent, 'function openShopReviewModal(') !== false, 
    "JS defines openShopReviewModal()");
check(strpos($sidebarContent, 'function closeShopReviewModal(') !== false, 
    "JS defines closeShopReviewModal()");
check(strpos($sidebarContent, 'function openQuickShopDrawer(') !== false, 
    "JS defines openQuickShopDrawer()");
check(strpos($sidebarContent, 'function closeQuickShopDrawer(') !== false, 
    "JS defines closeQuickShopDrawer()");
check(strpos($sidebarContent, 'function closeAllDrawers(') !== false, 
    "JS defines closeAllDrawers()");
check(strpos($sidebarContent, "e.key === 'Escape'") !== false, 
    "JS handles Escape key to dismiss all drawers");

echo PHP_EOL . "=======================================================" . PHP_EOL;
echo "2. VERIFYING USER DASHBOARD FLOATING BOTTOM DOCK (dashboard.php)" . PHP_EOL;
echo "=======================================================" . PHP_EOL;

// 1. Bottom nav container
check(strpos($dashboardContent, 'id="user-floating-bottom-nav"') !== false, 
    "Contains ID #user-floating-bottom-nav");
check(strpos($dashboardContent, 'mobile-only-bottom-nav') !== false, 
    "Contains class .mobile-only-bottom-nav");

// 2. 5 Synchronized Dock Slots
check(strpos($dashboardContent, 'id="dock-item-store"') !== false, 
    "Slot 1: contains ID #dock-item-store");
check(strpos($dashboardContent, 'id="dock-item-orders"') !== false, 
    "Slot 2: contains ID #dock-item-orders");
check(strpos($dashboardContent, 'id="dock-item-mode"') !== false, 
    "Slot 3 (Center): contains ID #dock-item-mode");
check(strpos($dashboardContent, 'id="dock-item-wallet"') !== false, 
    "Slot 4: contains ID #dock-item-wallet");
check(strpos($dashboardContent, 'id="dock-item-profile"') !== false, 
    "Slot 5: contains ID #dock-item-profile");

// 3. Center Elevate Circle Modes
check(strpos($dashboardContent, 'dock-center-circle mode-shop') !== false, 
    "Center circle has mode-shop variant");
check(strpos($dashboardContent, 'dock-center-circle mode-review') !== false, 
    "Center circle has mode-review variant");
check(strpos($dashboardContent, 'dock-center-circle mode-create') !== false, 
    "Center circle has mode-create variant");

// 4. Touch target bounds in dock
check(preg_match_all('/min-height:\s*48px;\s*min-width:\s*44px/i', $dashboardContent) >= 4, 
    "All dock slots enforce minimum 48px height x 44px width touch targets");

// 5. Active orders badge integration
check(strpos($dashboardContent, '$totalActiveOrdersBadge') !== false, 
    "Orders dock slot integrates dynamic \$totalActiveOrdersBadge counter");

echo PHP_EOL . "=======================================================" . PHP_EOL;
echo "3. VERIFYING GOOGLE STITCH CSS TOKENS (assets/css/user.css)" . PHP_EOL;
echo "=======================================================" . PHP_EOL;

// 1. Top Mode Pill CSS rules
check(strpos($cssContent, '.top-mode-pill') !== false, 
    "CSS defines .top-mode-pill");
check(preg_match('/\.top-mode-pill\s*\{[^}]*min-height:\s*44px/i', $cssContent) === 1, 
    ".top-mode-pill enforces min-height: 44px");
check(preg_match('/\.top-mode-pill\s*\{[^}]*min-width:\s*44px/i', $cssContent) === 1, 
    ".top-mode-pill enforces min-width: 44px");

// 2. Mode pill variants
check(strpos($cssContent, '.top-mode-pill.mode-approved') !== false, 
    "CSS defines .top-mode-pill.mode-approved");
check(strpos($cssContent, '.top-mode-pill.mode-pending') !== false, 
    "CSS defines .top-mode-pill.mode-pending");
check(strpos($cssContent, '.top-mode-pill.mode-shopless') !== false, 
    "CSS defines .top-mode-pill.mode-shopless");

// 3. Bottom dock center action circle
check(strpos($cssContent, '.dock-center-circle') !== false, 
    "CSS defines .dock-center-circle");
check(strpos($cssContent, '.dock-center-circle.mode-shop') !== false, 
    "CSS defines .dock-center-circle.mode-shop");
check(strpos($cssContent, '.dock-center-circle.mode-review') !== false, 
    "CSS defines .dock-center-circle.mode-review");
check(strpos($cssContent, '.dock-center-circle.mode-create') !== false, 
    "CSS defines .dock-center-circle.mode-create");

// 4. Bottom sheet modal & backdrop
check(strpos($cssContent, '.bottom-sheet') !== false, 
    "CSS defines .bottom-sheet");
check(strpos($cssContent, '.bottom-sheet-backdrop') !== false, 
    "CSS defines .bottom-sheet-backdrop");
check(strpos($cssContent, '.bottom-sheet-handle') !== false, 
    "CSS defines .bottom-sheet-handle");

// 5. Animations and micro-interactions
check(strpos($cssContent, '@keyframes pulsePillBorder') !== false, 
    "CSS defines keyframe @keyframes pulsePillBorder");
check(strpos($cssContent, '@keyframes pulseGoldGlow') !== false, 
    "CSS defines keyframe @keyframes pulseGoldGlow");
check(strpos($cssContent, 'transform: scale(0.96)') !== false, 
    "CSS defines active compression tap feedback (scale(0.96))");

echo PHP_EOL . "=======================================================" . PHP_EOL;
echo "DOM & CSS VERIFICATION SUMMARY:" . PHP_EOL;
echo "Total Checks: $totalChecks" . PHP_EOL;
echo "Passed Checks: $passedChecks" . PHP_EOL;
echo "Failed Checks: " . count($failedChecks) . PHP_EOL;
if (!empty($failedChecks)) {
    echo "Failures:" . PHP_EOL;
    foreach ($failedChecks as $fail) {
        echo " - $fail" . PHP_EOL;
    }
}
echo "=======================================================" . PHP_EOL;

if (count($failedChecks) === 0) {
    exit(0);
} else {
    exit(1);
}
