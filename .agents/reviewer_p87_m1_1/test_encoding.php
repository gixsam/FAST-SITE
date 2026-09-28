<?php
$files = [
    __DIR__ . '/../../includes/user_sidebar.php',
    __DIR__ . '/../../user/dashboard.php',
    __DIR__ . '/../../assets/css/user.css'
];

echo "=== UTF-8 & BOM AUDIT ===\n";
foreach ($files as $f) {
    $content = file_get_contents($f);
    $bom = substr($content, 0, 3);
    if ($bom === "\xEF\xBB\xBF") {
        echo "FAIL: UTF-8 BOM detected in " . basename($f) . "!\n";
        exit(1);
    }
    // Check if valid UTF-8
    if (!mb_check_encoding($content, 'UTF-8')) {
        echo "FAIL: Invalid UTF-8 encoding in " . basename($f) . "!\n";
        exit(1);
    }
    // Check for common mojibake patterns (e.g. â, Ã, etc.)
    if (preg_match('/(Ã©|Ã¨|â‚¬|â„¢|Â |Ã¡|Ã³)/', $content)) {
        echo "WARNING: Potential mojibake sequence found in " . basename($f) . "\n";
    }
    echo "PASS: " . basename($f) . " is valid UTF-8 without BOM.\n";
}
echo "Encoding verification complete.\n";
