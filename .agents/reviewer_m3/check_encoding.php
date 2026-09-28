<?php
$files = [
    __DIR__ . '/../../home.php',
    __DIR__ . '/../../includes/nav_public.php'
];

foreach ($files as $f) {
    $real = realpath($f);
    $content = file_get_contents($real);
    $valid = mb_check_encoding($content, 'UTF-8');
    echo basename($real) . " valid UTF-8: " . ($valid ? "YES" : "NO") . "\n";
    if (strpos($content, "\xEF\xBF\xBD") !== false) {
        echo "WARNING: Replacement character U+FFFD detected in " . basename($real) . "\n";
    } else {
        echo "No U+FFFD in " . basename($real) . "\n";
    }

    // Check for broken sequences like double UTF-8 encoded sequences (e.g. Ã©, â‚¬, etc.)
    if (preg_match('/(?:Ã[\x80-\xBF]|â[\x80-\xBF]{2})/u', $content, $m)) {
        echo "POSSIBLE MOJIBAKE MATCH in " . basename($real) . ": " . bin2hex($m[0]) . "\n";
    } else {
        echo "No obvious double-encoded mojibake in " . basename($real) . "\n";
    }
}
