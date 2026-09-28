<?php
$files = [
    'includes/user_sidebar.php',
    'user/dashboard.php',
    'assets/css/user.css',
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

$all_clean = true;
echo "========================================================================================\n";
echo sprintf("%-30s | %-8s | %-6s | %-8s | %-10s | %-10s\n", "File", "Size(B)", "Hex(3)", "BOM", "UTF-8", "Mojibake");
echo "========================================================================================\n";

foreach ($files as $f) {
    if (!file_exists($f)) {
        echo "MISSING: $f\n";
        $all_clean = false;
        continue;
    }
    $raw = file_get_contents($f);
    $bytes = substr($raw, 0, 3);
    $hex = bin2hex($bytes);
    
    // Check BOM (EF BB BF)
    $has_bom = ($bytes === "\xEF\xBB\xBF");
    
    // Check UTF-8 validity
    $is_utf8 = mb_check_encoding($raw, 'UTF-8');
    
    // Check suspected mojibake
    $mojibake = preg_match('/(?:Ã©|Ã |Ã¨|Ã§|Ã¹|Ã¢|Ãª|Ã®|Ã´|Ã»|â‚¬|â„¢|â€œ|â€\x9d|â€˜|â€™|â€”|â€“|ï¿½)/u', $raw);
    
    printf("%-30s | %8d | %6s | %-8s | %-10s | %-10s\n",
        $f,
        strlen($raw),
        $hex,
        $has_bom ? 'YES (FAIL)' : 'NO (OK)',
        $is_utf8 ? 'VALID (OK)' : 'FAIL',
        $mojibake ? 'DETECTED' : 'NONE (OK)'
    );
    
    if ($has_bom || !$is_utf8 || $mojibake) {
        $all_clean = false;
    }
}
echo "========================================================================================\n";
if ($all_clean) {
    echo "RESULT: ALL 12 FILES ARE 100% PURE UTF-8 WITH ZERO BOM AND ZERO MOJIBAKE.\n";
} else {
    echo "RESULT: ERRORS DETECTED IN AUDIT.\n";
}
