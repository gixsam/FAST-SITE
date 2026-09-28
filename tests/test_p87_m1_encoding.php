<?php
$files = [
    'includes/user_sidebar.php',
    'user/dashboard.php',
    'assets/css/user.css',
    'PROJECT.md',
    'PROJECT_STATE.md'
];

$errors = 0;
foreach ($files as $file) {
    $path = __DIR__ . '/../' . $file;
    if (!file_exists($path)) {
        echo "Missing file: $file" . PHP_EOL;
        $errors++;
        continue;
    }
    $raw = file_get_contents($path);
    $bom = substr($raw, 0, 3);
    if ($bom === "\xEF\xBB\xBF") {
        echo "[FAIL] $file has UTF-8 BOM!" . PHP_EOL;
        $errors++;
    } else {
        echo "[PASS] $file: Pure UTF-8 without BOM" . PHP_EOL;
    }

    if (!mb_check_encoding($raw, 'UTF-8')) {
        echo "[FAIL] $file has invalid UTF-8 byte sequences!" . PHP_EOL;
        $errors++;
    }
}

exit($errors === 0 ? 0 : 1);
