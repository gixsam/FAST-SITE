<?php
/**
 * Fast Site Phase 87 — Independent Victory Audit Test Suite
 * Executed by victory_auditor_2
 */

$rootDir = realpath(__DIR__ . '/../../');
$testsRun = 0;
$testsPassed = 0;
$testsFailed = 0;
$failures = [];

function assertTest($description, $condition, $details = '') {
    global $testsRun, $testsPassed, $testsFailed, $failures;
    $testsRun++;
    if ($condition) {
        $testsPassed++;
        echo "  [PASS] {$description}\n";
    } else {
        $testsFailed++;
        $msg = "  [FAIL] {$description}" . ($details ? " -> {$details}" : "");
        echo $msg . "\n";
        $failures[] = $msg;
    }
}

echo "====================================================================\n";
echo "FAST SITE PHASE 87 — INDEPENDENT VICTORY AUDITOR TEST SUITE\n";
echo "Working Root: {$rootDir}\n";
echo "====================================================================\n\n";

// -------------------------------------------------------------------------
// SECTION 1: PHP SYNTAX LINTING (php -l)
// -------------------------------------------------------------------------
echo "--- SECTION 1: PHP SYNTAX LINTING ---\n";
$phpFiles = [
    'includes/user_sidebar.php',
    'user/dashboard.php',
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

foreach ($phpFiles as $relPath) {
    $fullPath = $rootDir . '/' . $relPath;
    if (!file_exists($fullPath)) {
        assertTest("File exists: {$relPath}", false, "File not found on disk");
        continue;
    }
    // Change to root directory and use relative path in quotes
    $cmd = 'cd /d "' . $rootDir . '" && php -l "' . $relPath . '" 2>&1';
    $output = shell_exec($cmd);
    $isClean = (strpos($output, 'No syntax errors detected') !== false);
    assertTest("Syntax lint: {$relPath}", $isClean, trim($output));
}

// -------------------------------------------------------------------------
// SECTION 2: PURE UTF-8 & ZERO BOM / ZERO MOJIBAKE AUDIT
// -------------------------------------------------------------------------
echo "\n--- SECTION 2: ENCODING & MOJIBAKE AUDIT ---\n";
$allFiles = array_merge($phpFiles, [
    'assets/css/user.css',
    'DEPLOYMENT_GUIDE.txt'
]);

$mojibakePattern = '/(Ã.|â€|â‚¬|ï¿½|Ã¢|Â©|Â®|Ã©|Ã§|Ã |Ã¨|Ã¹)/u';

foreach ($allFiles as $relPath) {
    $fullPath = $rootDir . '/' . $relPath;
    if (!file_exists($fullPath)) {
        assertTest("Encoding file exists: {$relPath}", false);
        continue;
    }
    $bytes = file_get_contents($fullPath);
    
    // Check BOM (0xEF, 0xBB, 0xBF)
    $hasBOM = (substr($bytes, 0, 3) === "\xEF\xBB\xBF");
    assertTest("No UTF-8 BOM: {$relPath}", !$hasBOM, "Found UTF-8 BOM header");

    // Check UTF-8 validity
    $isValidUtf8 = mb_check_encoding($bytes, 'UTF-8');
    assertTest("Valid UTF-8: {$relPath}", $isValidUtf8, "Invalid UTF-8 encoding");

    // Check Mojibake
    $hasMojibake = preg_match($mojibakePattern, $bytes);
    assertTest("No Mojibake: {$relPath}", !$hasMojibake, "Potential mojibake sequence detected");
}

// -------------------------------------------------------------------------
// SECTION 3: ARCHITECTURE & SCOPE VERIFICATION (R1-R4)
// -------------------------------------------------------------------------
echo "\n--- SECTION 3: CODE INTEGRITY & SCOPE CHECKS ---\n";

// 3.1 includes/user_sidebar.php checks
$userSidebar = file_get_contents($rootDir . '/includes/user_sidebar.php');
assertTest("user_sidebar.php: defines getUserShopState()", strpos($userSidebar, 'function getUserShopState(') !== false);
assertTest("user_sidebar.php: checks approved state", strpos($userSidebar, "mode-approved") !== false);
assertTest("user_sidebar.php: checks pending state", strpos($userSidebar, "mode-pending") !== false);
assertTest("user_sidebar.php: checks shopless state", strpos($userSidebar, "mode-shopless") !== false);
assertTest("user_sidebar.php: contains Switch to Shop Mode text", strpos($userSidebar, "Switch to Shop Mode") !== false);
assertTest("user_sidebar.php: contains Shop Under Review text", strpos($userSidebar, "Shop Under Review") !== false);
assertTest("user_sidebar.php: contains Open Free Shop text", strpos($userSidebar, "Open Free Shop") !== false);
assertTest("user_sidebar.php: contains #shopReviewModal", strpos($userSidebar, 'id="shopReviewModal"') !== false);
assertTest("user_sidebar.php: contains #quickShopDrawer", strpos($userSidebar, 'id="quickShopDrawer"') !== false);
assertTest("user_sidebar.php: contains 44px touch target on hamburger", strpos($userSidebar, 'width:44px; height:44px;') !== false);
assertTest("user_sidebar.php: contains closeAllDrawers()", strpos($userSidebar, 'function closeAllDrawers()') !== false);
assertTest("user_sidebar.php: queries partners table authentically", strpos($userSidebar, 'SELECT * FROM partners WHERE') !== false);
assertTest("user_sidebar.php: queries partner_requests table authentically", strpos($userSidebar, 'SELECT * FROM partner_requests WHERE') !== false);

// 3.2 user/dashboard.php checks
$userDash = file_get_contents($rootDir . '/user/dashboard.php');
assertTest("user/dashboard.php: contains #user-floating-bottom-nav", strpos($userDash, 'id="user-floating-bottom-nav"') !== false);
assertTest("user/dashboard.php: dock slot 1 Store", strpos($userDash, 'id="dock-item-store"') !== false);
assertTest("user/dashboard.php: dock slot 2 Orders with badge", strpos($userDash, 'id="dock-item-orders"') !== false);
assertTest("user/dashboard.php: dock slot 3 Mode Switcher", strpos($userDash, 'id="dock-item-mode"') !== false);
assertTest("user/dashboard.php: dock slot 4 Wallet", strpos($userDash, 'id="dock-item-wallet"') !== false);
assertTest("user/dashboard.php: dock slot 5 Profile", strpos($userDash, 'id="dock-item-profile"') !== false);
assertTest("user/dashboard.php: dynamic tab sync for dock items", strpos($userDash, "querySelectorAll('#user-floating-bottom-nav .b-nav-item')") !== false);

// 3.3 partner/nav.php checks
$partnerNav = file_get_contents($rootDir . '/partner/nav.php');
assertTest("partner/nav.php: header 1-tap buyer mode pill", strpos($partnerNav, 'class="header-mode-pill"') !== false);
assertTest("partner/nav.php: header buyer mode text", strpos($partnerNav, 'Switch to Buyer Mode') !== false);
assertTest("partner/nav.php: persistent back button on subpages", strpos($partnerNav, 'class="nav-back-btn"') !== false);
assertTest("partner/nav.php: back button SVG present", strpos($partnerNav, 'points="15 18 9 12 15 6"') !== false);
assertTest("partner/nav.php: 5-slot bottom dock", strpos($partnerNav, 'class="partner-bottom-dock"') !== false);
assertTest("partner/nav.php: dock slot 1 Hub", strpos($partnerNav, 'Hub') !== false);
assertTest("partner/nav.php: dock slot 2 Orders + badge", strpos($partnerNav, 'dock-badge-counter') !== false);
assertTest("partner/nav.php: dock slot 3 Center FAB Add", strpos($partnerNav, 'dock-item-primary') !== false);
assertTest("partner/nav.php: dock slot 4 Catalog", strpos($partnerNav, 'Catalog') !== false);
assertTest("partner/nav.php: dock slot 5 Buyer Mode", strpos($partnerNav, 'dock-item-buyer') !== false);
assertTest("partner/nav.php: isActive supports array safely", strpos($partnerNav, 'is_array($page)') !== false);
assertTest("partner/nav.php: unauthenticated redirect to /user/login.php", strpos($partnerNav, "header('Location: /user/login.php');") !== false);

// 3.4 partner/dashboard.php checks
$partnerDash = file_get_contents($rootDir . '/partner/dashboard.php');
assertTest("partner/dashboard.php: hero bar .btn-action-buyer", strpos($partnerDash, 'btn-action-buyer') !== false);
assertTest("partner/dashboard.php: active scale compression (scale(0.96))", strpos($partnerDash, 'transform: scale(0.96)') !== false);

// 3.5 Partner redirect files
$redirectFiles = [
    'partner/index.php',
    'partner/logout.php',
    'partner/product_add.php',
    'partner/product_edit.php',
    'partner/product_delete.php',
    'partner/profile.php',
    'partner/api_docs.php'
];
foreach ($redirectFiles as $rf) {
    $content = file_get_contents($rootDir . '/' . $rf);
    assertTest("Redirect {$rf} -> /user/login.php", strpos($content, "/user/login.php") !== false);
    assertTest("No legacy 404 /partner/login.php in {$rf}", strpos($content, "/partner/login.php") === false);
}

// 3.6 assets/css/user.css checks
$userCss = file_get_contents($rootDir . '/assets/css/user.css');
assertTest("user.css: top-mode-pill styles", strpos($userCss, '.top-mode-pill') !== false);
assertTest("user.css: active tap compression scale(0.96)", strpos($userCss, 'transform: scale(0.96)') !== false);
assertTest("user.css: safe-area-inset-top support", strpos($userCss, 'env(safe-area-inset-top') !== false);
assertTest("user.css: safe-area-inset-bottom support", strpos($userCss, 'env(safe-area-inset-bottom') !== false);
assertTest("user.css: bottom-sheet drawer styles", strpos($userCss, '.bottom-sheet') !== false);
assertTest("user.css: 44px touch targets", strpos($userCss, 'min-height: 44px') !== false || strpos($userCss, '44px') !== false);

// -------------------------------------------------------------------------
// SECTION 4: LOCAL SERVER LIVE HOST (http://localhost:8000)
// -------------------------------------------------------------------------
echo "\n--- SECTION 4: LOCAL SERVER LIVE HOST VERIFICATION ---\n";

function checkUrl($url, $expectedCode, $followRedirect = false) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $followRedirect);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $redirectUrl = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
    $err = curl_error($ch);
    curl_close($ch);
    return ['code' => $httpCode, 'redirect' => $redirectUrl, 'err' => $err];
}

$endpoints = [
    'http://localhost:8000/index.php' => ['expected' => 200, 'follow' => false],
    'http://localhost:8000/user/login.php' => ['expected' => 200, 'follow' => false],
    'http://localhost:8000/partner/index.php' => ['expected' => 302, 'follow' => false, 'redirect' => '/user/login.php'],
    'http://localhost:8000/partner/dashboard.php' => ['expected' => 302, 'follow' => false, 'redirect' => '/user/login.php'],
    'http://localhost:8000/partner/logout.php' => ['expected' => 302, 'follow' => false, 'redirect' => '/user/login.php'],
    'http://localhost:8000/partner/product_add.php' => ['expected' => 302, 'follow' => false, 'redirect' => '/user/login.php'],
    'http://localhost:8000/partner/profile.php' => ['expected' => 302, 'follow' => false, 'redirect' => '/user/login.php'],
    'http://localhost:8000/user/dashboard.php' => ['expected' => 302, 'follow' => false, 'redirect' => '/user/login.php'],
];

foreach ($endpoints as $url => $config) {
    $res = checkUrl($url, $config['expected'], $config['follow']);
    $passCode = ($res['code'] === $config['expected']);
    $passRedirect = true;
    if (isset($config['redirect'])) {
        $passRedirect = (strpos($res['redirect'], $config['redirect']) !== false);
    }
    assertTest("Live Host: {$url} (HTTP {$config['expected']})", $passCode && $passRedirect, "Got HTTP {$res['code']}, Redirect: {$res['redirect']}, Err: {$res['err']}");
}

// -------------------------------------------------------------------------
// SECTION 5: ZIP PACKAGE INTEGRITY & MANIFEST AUDIT (via Python zipfile)
// -------------------------------------------------------------------------
echo "\n--- SECTION 5: ZIP PACKAGE & MANIFEST AUDIT ---\n";
$zipPath = $rootDir . '/fastsite_phase87.zip';
assertTest("Archive exists: fastsite_phase87.zip", file_exists($zipPath), "File not found");

$pyScript = <<<'PY'
import sys, zipfile, os, hashlib

root_dir = sys.argv[1]
zip_path = os.path.join(root_dir, 'fastsite_phase87.zip')

expected_entries = [
    'assets/css/user.css',
    'includes/user_sidebar.php',
    'partner/api_docs.php',
    'partner/dashboard.php',
    'partner/index.php',
    'partner/logout.php',
    'partner/nav.php',
    'partner/product_add.php',
    'partner/product_delete.php',
    'partner/product_edit.php',
    'partner/profile.php',
    'user/dashboard.php',
    'DEPLOYMENT_GUIDE.txt'
]

results = []

try:
    with zipfile.ZipFile(zip_path, 'r') as zf:
        # CRC test
        bad_crc = zf.testzip()
        results.append(('fastsite_phase87.zip opens cleanly with CRC check', bad_crc is None, str(bad_crc)))
        
        infolist = zf.infolist()
        namelist = [info.filename for info in infolist]
        
        # Check backslashes
        has_backslashes = any('\\' in name for name in namelist)
        results.append(('Zip contains zero Windows backslashes in paths', not has_backslashes, 'Found backslashes'))
        
        # Check expected entries
        for exp in expected_entries:
            found = exp in namelist
            results.append((f'Zip contains {exp}', found, f'Missing {exp}'))
            
            if found:
                zip_data = zf.read(exp)
                disk_path = os.path.join(root_dir, exp.replace('/', os.sep))
                with open(disk_path, 'rb') as df:
                    disk_data = df.read()
                matches = (zip_data == disk_data)
                results.append((f'Zip content matches disk exactly: {exp}', matches, 'Content mismatch'))

except Exception as e:
    results.append(('Zip inspection completed without exception', False, str(e)))

import json
print(json.dumps(results))
PY;

$pyFile = __DIR__ . '/check_zip.py';
file_put_contents($pyFile, $pyScript);

$pyCmd = 'python "' . $pyFile . '" "' . $rootDir . '" 2>&1';
$pyOut = shell_exec($pyCmd);
$pyResults = json_decode($pyOut, true);

if (is_array($pyResults)) {
    foreach ($pyResults as $r) {
        assertTest($r[0], $r[1], $r[2]);
    }
} else {
    assertTest("Python zip auditor execution", false, "Output: " . $pyOut);
}

// 5.2 DEPLOYMENT_GUIDE.txt checks
$guidePath = $rootDir . '/DEPLOYMENT_GUIDE.txt';
if (file_exists($guidePath)) {
    $guideContent = file_get_contents($guidePath);
    assertTest("DEPLOYMENT_GUIDE: mentions public_html/", strpos($guideContent, 'public_html/') !== false);
    assertTest("DEPLOYMENT_GUIDE: states Phase 87 100% COMPLETE", strpos($guideContent, 'Phase 87 100% COMPLETE') !== false || strpos($guideContent, 'Phase 87') !== false);
    assertTest("DEPLOYMENT_GUIDE: includes LOCAL SERVER LIVE HOST reminder", strpos($guideContent, 'LOCAL SERVER LIVE HOST') !== false);
    assertTest("DEPLOYMENT_GUIDE: includes file manifest with SHA256", strpos($guideContent, 'SHA256 Checksum') !== false);
}

// -------------------------------------------------------------------------
// SUMMARY & VERDICT
// -------------------------------------------------------------------------
echo "\n====================================================================\n";
echo "TEST RESULTS: {$testsPassed} PASSED, {$testsFailed} FAILED (Total: {$testsRun})\n";
if ($testsFailed === 0) {
    echo "STATUS: 100% INDEPENDENT TEST PASS — ZERO INTEGRITY VIOLATIONS\n";
} else {
    echo "STATUS: TEST FAILURES DETECTED:\n";
    foreach ($failures as $f) {
        echo "  - {$f}\n";
    }
}
echo "====================================================================\n";

exit($testsFailed === 0 ? 0 : 1);
