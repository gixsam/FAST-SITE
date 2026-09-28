<?php
$endpoints = [
    'http://localhost:8000/index.php',
    'http://localhost:8000/partner/dashboard.php',
    'http://localhost:8000/partner/index.php',
    'http://localhost:8000/user/dashboard.php',
    'http://localhost:8000/user/login.php',
    'https://zoo-dubai-hopefully-note.trycloudflare.com/index.php'
];

echo "========================================================================================\n";
echo sprintf("%-60s | %-6s | %-30s\n", "Endpoint", "Status", "Redirect / Info");
echo "========================================================================================\n";

foreach ($endpoints as $url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $redirectUrl = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
    $err = curl_error($ch);
    curl_close($ch);
    
    if ($err) {
        printf("%-60s | %-6s | %-30s\n", $url, "ERR", $err);
    } else {
        printf("%-60s | %-6d | %-30s\n", $url, $httpCode, $redirectUrl ?: "OK (No redirect)");
    }
}
echo "========================================================================================\n";
