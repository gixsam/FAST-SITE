<?php
$files = [
    'partner/product_add.php',
    'partner/product_edit.php',
    'partner/profile.php',
];

foreach ($files as $f) {
    $lines = file($f);
    echo "=== $f ===\n";
    foreach ($lines as $num => $line) {
        if (preg_match('/[ÃÂ][\x80-\xBF]/', $line, $m)) {
            echo "Line " . ($num + 1) . ": " . trim($line) . "\n";
            echo "Match: " . bin2hex($m[0]) . "\n";
            if ($num > 30) { echo "... (truncated)\n"; break; }
        }
    }
}
