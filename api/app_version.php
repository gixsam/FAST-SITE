<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Load configurations
require_once __DIR__ . '/../config.php';
$settings = [];
if (isset($pdo)) {
    try {
        $settings = $pdo->query("SELECT setting_key, setting_value FROM homepage_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (Exception $e) {}
}

// Client configuration
$client_version_code = isset($settings['app_client_version_code']) ? intval($settings['app_client_version_code']) : 9;
$client_version_name = isset($settings['app_client_version_name']) ? $settings['app_client_version_name'] : '1.8';
$client_apk_url      = isset($settings['app_client_apk_url']) ? $settings['app_client_apk_url'] : 'https://fastsite.best-travel.ltd/fastsite_storefront.apk';

// Admin configuration
$admin_version_code  = isset($settings['app_admin_version_code']) ? intval($settings['app_admin_version_code']) : 2;
$admin_version_name  = isset($settings['app_admin_version_name']) ? $settings['app_admin_version_name'] : '1.1';
$admin_apk_url       = isset($settings['app_admin_apk_url']) ? $settings['app_admin_apk_url'] : 'https://fastsite.best-travel.ltd/fastsite_hq.apk';

echo json_encode([
    'client_app' => [
        'version_code' => $client_version_code,
        'version_name' => $client_version_name,
        'apk_url'      => $client_apk_url
    ],
    'admin_app' => [
        'version_code' => $admin_version_code,
        'version_name' => $admin_version_name,
        'apk_url'      => $admin_apk_url
    ]
]);
