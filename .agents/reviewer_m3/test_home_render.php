<?php
// Test runner for home.php rendering
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['PHP_SELF'] = '/index.php';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/index.php';

// Test Case 1: Default load
echo "Testing Default Load...\n";
$_GET = [];
ob_start();
try {
    include __DIR__ . '/../../home.php';
    $out = ob_get_clean();
    echo "Default Load Success: Length " . strlen($out) . " bytes\n";
    if (strpos($out, 'category-drawer') === false) {
        echo "FAIL: category-drawer not in output\n";
    } else {
        echo "PASS: category-drawer present in output\n";
    }
    if (strpos($out, 'search-command-hub') === false) {
        echo "FAIL: search-command-hub not in output\n";
    } else {
        echo "PASS: search-command-hub present in output\n";
    }
} catch (Throwable $e) {
    ob_end_clean();
    echo "ERROR in Test 1: " . $e->getMessage() . "\n";
}

// Test Case 2: Filtered load (search + category + type)
echo "\nTesting Filtered Load...\n";
$_GET = [
    'search' => 'test',
    'category' => 'Technology',
    'type' => 'services'
];
ob_start();
try {
    include __DIR__ . '/../../home.php';
    $out2 = ob_get_clean();
    echo "Filtered Load Success: Length " . strlen($out2) . " bytes\n";
    if (strpos($out2, 'value="Technology"') !== false) {
        echo "PASS: Category parameter reflected in inputs\n";
    }
} catch (Throwable $e) {
    ob_end_clean();
    echo "ERROR in Test 2: " . $e->getMessage() . "\n";
}
