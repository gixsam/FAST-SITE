<?php
/**
 * Fast Site Phase 87 Milestone 2 Retest:
 * Dedicated Empirical Inspector for products.php Active Class & Navigation Dock Highlighting
 */

$rootDir = dirname(__DIR__);

function renderNav($pageSelf) {
    global $rootDir;
    $code = '<?php' . PHP_EOL;
    $code .= 'session_start();' . PHP_EOL;
    $code .= '$_SESSION["user_id"] = 1;' . PHP_EOL;
    $code .= '$_SESSION["partner_id"] = 1;' . PHP_EOL;
    $code .= '$_SERVER["PHP_SELF"] = ' . var_export($pageSelf, true) . ';' . PHP_EOL;
    $code .= '$_SERVER["REQUEST_METHOD"] = "GET";' . PHP_EOL;
    $code .= '$partner_pending_orders_count = 0;' . PHP_EOL;
    $code .= 'ob_start();' . PHP_EOL;
    $code .= 'require ' . var_export($rootDir . '/partner/nav.php', true) . ';' . PHP_EOL;
    $code .= '$html = ob_get_clean();' . PHP_EOL;
    $code .= 'echo "###HTML_START###" . $html . "###HTML_END###";' . PHP_EOL;

    $tempFile = tempnam(sys_get_temp_dir(), 'retest_nav_');
    file_put_contents($tempFile, $code);
    $output = shell_exec('php ' . escapeshellarg($tempFile));
    @unlink($tempFile);

    if (preg_match('/###HTML_START###(.*)###HTML_END###/s', $output, $m)) {
        return $m[1];
    }
    return '';
}

function getXPathFromHtml($html) {
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    return new DOMXPath($dom);
}

echo "=================================================================" . PHP_EOL;
echo "EMPIRICAL VERIFICATION: partner/products.php EXACT ACTIVE CLASS" . PHP_EOL;
echo "=================================================================" . PHP_EOL;

$pagesToTest = [
    '/partner/products.php' => 'products.php',
    '/partner/product_edit.php' => 'products.php',
    '/partner/product_add.php' => 'product_add.php',
    '/partner/dashboard.php' => 'dashboard.php',
    '/partner/orders.php' => 'orders.php',
];

$allPassed = true;

foreach ($pagesToTest as $pageUri => $expectedActiveHref) {
    echo PHP_EOL . "Testing visiting: $pageUri" . PHP_EOL;
    $html = renderNav($pageUri);
    if (empty($html)) {
        echo "  [FAIL] Failed to render HTML for $pageUri" . PHP_EOL;
        $allPassed = false;
        continue;
    }

    $xp = getXPathFromHtml($html);

    // Check for any boolean ' 1' or ' 1"' in entire rendered HTML
    if (preg_match('/class="[^"]*\b1\b[^"]*"/', $html, $m1)) {
        echo "  [FAIL] Detected stringified boolean '1' in class attribute: " . $m1[0] . PHP_EOL;
        $allPassed = false;
    } else {
        echo "  [PASS] Zero stringified boolean '1' classes found in entire HTML" . PHP_EOL;
    }

    // Inspect dock items
    $dockItems = $xp->query('//nav[contains(@class, "partner-bottom-dock")]//a');
    foreach ($dockItems as $item) {
        $href = $item->getAttribute('href');
        $class = $item->getAttribute('class');
        $label = trim(preg_replace('/\s+/', ' ', $item->textContent));

        if ($href === $expectedActiveHref) {
            echo "  Target Slot ($label, href='$href'): class='$class'" . PHP_EOL;
            if (strpos($class, 'active') !== false) {
                echo "    [PASS] Active class correctly present on target slot!" . PHP_EOL;
            } else {
                echo "    [FAIL] Active class MISSING on target slot: '$class'" . PHP_EOL;
                $allPassed = false;
            }
            if (strpos($class, ' 1') !== false || $class === 'dock-item 1') {
                echo "    [FAIL] Boolean '1' bug detected on target slot: '$class'" . PHP_EOL;
                $allPassed = false;
            }
        }
    }

    // Specific check for /partner/products.php dock item class
    if ($pageUri === '/partner/products.php') {
        $catalogLinks = $xp->query('//nav[contains(@class, "partner-bottom-dock")]//a[@href="products.php"]');
        if ($catalogLinks->length === 1) {
            $catClass = $catalogLinks->item(0)->getAttribute('class');
            echo "  [SPECIFIC CHECK] Catalog Dock Item Class: '$catClass'" . PHP_EOL;
            if ($catClass === 'dock-item active') {
                echo "  [PASS] Catalog dock item is EXACTLY 'dock-item active'!" . PHP_EOL;
            } else {
                echo "  [FAIL] Catalog dock item is '$catClass' (expected EXACTLY 'dock-item active')" . PHP_EOL;
                $allPassed = false;
            }
        } else {
            echo "  [FAIL] Catalog dock link not found in bottom dock" . PHP_EOL;
            $allPassed = false;
        }

        // Also check drawer link
        $drawerLinks = $xp->query('//div[contains(@class, "drawer-menu")]//a[@href="products.php"]');
        if ($drawerLinks->length === 1) {
            $drawClass = $drawerLinks->item(0)->getAttribute('class');
            echo "  [SPECIFIC CHECK] Products Drawer Item Class: '$drawClass'" . PHP_EOL;
            if ($drawClass === 'drawer-link active') {
                echo "  [PASS] Products drawer item is EXACTLY 'drawer-link active'!" . PHP_EOL;
            } else {
                echo "  [FAIL] Products drawer item is '$drawClass' (expected EXACTLY 'drawer-link active')" . PHP_EOL;
                $allPassed = false;
            }
        }
    }
}

echo PHP_EOL . "=================================================================" . PHP_EOL;
if ($allPassed) {
    echo "FINAL VERDICT: ALL DOCK AND DRAWER CLASS TESTS PASSED (APPROVE)" . PHP_EOL;
    exit(0);
} else {
    echo "FINAL VERDICT: FAILURES DETECTED (REQUEST_CHANGES)" . PHP_EOL;
    exit(1);
}
