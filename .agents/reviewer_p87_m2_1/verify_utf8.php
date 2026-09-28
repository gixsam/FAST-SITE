<?php
$files = [
    __DIR__ . '/../../partner/nav.php',
    __DIR__ . '/../../partner/dashboard.php',
    __DIR__ . '/../../partner/index.php',
    __DIR__ . '/../../partner/logout.php',
    __DIR__ . '/../../partner/product_add.php',
    __DIR__ . '/../../partner/product_edit.php',
    __DIR__ . '/../../partner/product_delete.php',
    __DIR__ . '/../../partner/profile.php',
    __DIR__ . '/../../partner/api_docs.php',
];

$allPassed = true;
foreach ($files as $filePath) {
    $realPath = realpath($filePath);
    if (!$realPath || !file_exists($realPath)) {
        echo "[ERROR] File not found: $filePath\n";
        $allPassed = false;
        continue;
    }
    $content = file_get_contents($realPath);
    $hasBom = (substr($content, 0, 3) === "\xEF\xBB\xBF");
    $validUtf8 = mb_check_encoding($content, 'UTF-8');
    
    // Check for Unicode replacement character  (U+FFFD)
    $hasReplacementChar = (strpos($content, "\xEF\xBF\xBD") !== false);
    
    $rel = str_replace(realpath(__DIR__ . '/../../') . DIRECTORY_SEPARATOR, '', $realPath);
    echo sprintf(
        "%-30s | BOM: %-3s | Valid UTF-8: %-3s | Replacement Char (U+FFFD): %-3s\n",
        $rel,
        $hasBom ? 'YES' : 'NO',
        $validUtf8 ? 'YES' : 'NO',
        $hasReplacementChar ? 'YES' : 'NO'
    );
    if ($hasBom || !$validUtf8 || $hasReplacementChar) {
        $allPassed = false;
    }
}

if ($allPassed) {
    echo "\n>>> ALL FILES ENCODING CHECKS PASSED (Zero BOM, Valid UTF-8, Zero Replacement Chars) <<<\n";
    exit(0);
} else {
    echo "\n>>> ENCODING CHECK FAILED <<<\n";
    exit(1);
}
