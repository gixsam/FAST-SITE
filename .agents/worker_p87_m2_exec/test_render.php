<?php
// Simulate logged in partner session
session_start();
require_once __DIR__ . '/../../config.php';

// Find a partner in DB or simulate one
$stmt = $pdo->query("SELECT user_id, id FROM partners LIMIT 1");
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row) {
    $_SESSION['user_id'] = (int)$row['user_id'];
    $_SESSION['partner_id'] = (int)$row['id'];
} else {
    $_SESSION['user_id'] = 1;
    $_SESSION['partner_id'] = 1;
}

$_SERVER['PHP_SELF'] = '/partner/dashboard.php';
$_SERVER['REQUEST_METHOD'] = 'GET';

echo "=== TESTING partner/dashboard.php ===\n";
ob_start();
include __DIR__ . '/../../partner/dashboard.php';
$dashOutput = ob_get_clean();

$checksDash = [
    'header-mode-pill' => strpos($dashOutput, 'header-mode-pill') !== false,
    'Switch to Buyer Mode (pill)' => strpos($dashOutput, 'Switch to Buyer Mode') !== false,
    'mode-pill-text-desktop' => strpos($dashOutput, 'mode-pill-text-desktop') !== false,
    'mode-pill-text-mobile' => strpos($dashOutput, 'mode-pill-text-mobile') !== false,
    'btn-action-buyer' => strpos($dashOutput, 'btn-action-buyer') !== false,
    'partner-bottom-dock' => strpos($dashOutput, 'partner-bottom-dock') !== false,
    'dock-item-buyer' => strpos($dashOutput, 'dock-item-buyer') !== false,
    'dock-item-primary' => strpos($dashOutput, 'dock-item-primary') !== false,
    'No back button on dashboard' => strpos($dashOutput, '<a href="dashboard.php" class="nav-back-btn"') === false,
];

foreach ($checksDash as $name => $pass) {
    echo "Dashboard: $name -> " . ($pass ? "PASS" : "FAIL") . "\n";
}

echo "\n=== TESTING partner/orders.php via CLI sub-process ===\n";
$cmd = 'php -r "' .
    'session_start();' .
    '$_SESSION[\'user_id\'] = ' . $_SESSION['user_id'] . ';' .
    '$_SESSION[\'partner_id\'] = ' . $_SESSION['partner_id'] . ';' .
    '$_SERVER[\'PHP_SELF\'] = \'/partner/orders.php\';' .
    '$_SERVER[\'REQUEST_METHOD\'] = \'GET\';' .
    'ob_start();' .
    'include \'partner/orders.php\';' .
    '$out = ob_get_clean();' .
    '$hasBack = strpos($out, \'nav-back-btn\') !== false;' .
    '$hasPill = strpos($out, \'header-mode-pill\') !== false;' .
    '$hasDock = strpos($out, \'partner-bottom-dock\') !== false;' .
    '$hasBuyerDock = strpos($out, \'dock-item-buyer\') !== false;' .
    'echo \'Orders: nav-back-btn on subpage -> \' . ($hasBack ? \'PASS\' : \'FAIL\') . PHP_EOL;' .
    'echo \'Orders: header-mode-pill -> \' . ($hasPill ? \'PASS\' : \'FAIL\') . PHP_EOL;' .
    'echo \'Orders: partner-bottom-dock -> \' . ($hasDock ? \'PASS\' : \'FAIL\') . PHP_EOL;' .
    'echo \'Orders: dock-item-buyer -> \' . ($hasBuyerDock ? \'PASS\' : \'FAIL\') . PHP_EOL;' .
'"';
passthru($cmd);
