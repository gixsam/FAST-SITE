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

foreach ($files as $f) {
    $sz = filesize($f);
    $sha = hash_file('sha256', $f);
    echo sprintf("%-30s | %6d bytes | SHA256: %s\n", $f, $sz, $sha);
}
