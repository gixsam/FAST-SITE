<?php
$files = [
    __DIR__ . '/../../includes/user_sidebar.php',
    __DIR__ . '/../../user/dashboard.php',
    __DIR__ . '/../../assets/css/user.css'
];

foreach ($files as $f) {
    $real = realpath($f);
    $content = file_get_contents($real);
    $hasBom = (substr($content, 0, 3) === "\xEF\xBB\xBF");
    $validUtf8 = mb_check_encoding($content, 'UTF-8');
    echo basename($real) . " => UTF-8: " . ($validUtf8 ? "YES" : "NO") . ", BOM: " . ($hasBom ? "YES" : "NO") . ", Bytes: " . strlen($content) . PHP_EOL;
}
