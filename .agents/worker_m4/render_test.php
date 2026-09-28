<?php
// Mock session for user
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'partner';
$_SESSION['phone'] = '01700000000';
$_SESSION['user_name'] = 'Test Partner';

echo "--- Testing home.php render ---\n";
ob_start();
try {
    include __DIR__ . '/../../home.php';
    $out = ob_get_clean();
    echo "home.php rendered successfully (" . strlen($out) . " bytes)\n";
} catch (Throwable $e) {
    ob_end_clean();
    echo "home.php error: " . $e->getMessage() . "\n";
}

echo "--- Testing user/dashboard.php render ---\n";
ob_start();
try {
    include __DIR__ . '/../../user/dashboard.php';
    $out = ob_get_clean();
    echo "user/dashboard.php rendered successfully (" . strlen($out) . " bytes)\n";
} catch (Throwable $e) {
    ob_end_clean();
    echo "user/dashboard.php error: " . $e->getMessage() . "\n";
}

echo "--- Testing partner/dashboard.php render ---\n";
ob_start();
try {
    include __DIR__ . '/../../partner/dashboard.php';
    $out = ob_get_clean();
    echo "partner/dashboard.php rendered successfully (" . strlen($out) . " bytes)\n";
} catch (Throwable $e) {
    ob_end_clean();
    echo "partner/dashboard.php error: " . $e->getMessage() . "\n";
}

echo "--- Testing partner/orders.php render ---\n";
ob_start();
try {
    include __DIR__ . '/../../partner/orders.php';
    $out = ob_get_clean();
    echo "partner/orders.php rendered successfully (" . strlen($out) . " bytes)\n";
} catch (Throwable $e) {
    ob_end_clean();
    echo "partner/orders.php error: " . $e->getMessage() . "\n";
}

echo "--- Testing partner/products.php render ---\n";
ob_start();
try {
    include __DIR__ . '/../../partner/products.php';
    $out = ob_get_clean();
    echo "partner/products.php rendered successfully (" . strlen($out) . " bytes)\n";
} catch (Throwable $e) {
    ob_end_clean();
    echo "partner/products.php error: " . $e->getMessage() . "\n";
}
