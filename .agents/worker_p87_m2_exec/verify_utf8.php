<?php
$files = [
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

$allGood = true;
foreach ($files as $f) {
    $path = __DIR__ . '/../../' . $f;
    if (!file_exists($path)) {
        echo "MISSING: $f\n";
        $allGood = false;
        continue;
    }
    $b = file_get_contents($path);
    $hasBom = (substr($b, 0, 3) === "\xEF\xBB\xBF");
    $validUtf8 = mb_check_encoding($b, 'UTF-8');
    echo "$f -> BOM: " . ($hasBom ? 'YES [ERROR]' : 'NO [OK]') . " | Valid UTF-8: " . ($validUtf8 ? 'YES [OK]' : 'NO [ERROR]') . "\n";
    if ($hasBom || !$validUtf8) {
        $allGood = false;
    }
}
if ($allGood) {
    echo "\nALL 9 FILES PASSED UTF-8 AND BOM VALIDATION CLEANLY.\n";
    exit(0);
} else {
    echo "\nERRORS DETECTED.\n";
    exit(1);
}
