<?php
// =========================================================================
// api/app_bootstrap.php    v4: Super-App Native Backend (Phase 19)
// =========================================================================
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); 

// This API is called by the Fast Site Native Mobile App (Flutter/React Native) 
// upon startup. It provides the mobile app with the master list of connected sites,
// the wrapper endpoints, and the UI themes to render natively on the phone.

$super_app_config = [
    'app_version' => '1.0.0',
    'require_update' => false,
    'theme_dark' => '#0a0a0f',
    'theme_brand' => '#fcb900',
    'connected_shops' => [
        [
            'id' => 'AYRA_MART',
            'name' => 'Ayra Mart',
            'icon_url' => 'https://cdn.fastsite.com/assets/ayramart_icon.png',
            'wrapper_endpoint' => 'https://fastsite.com/shop_wrapper.php?shop=AYRA_MART',
            'native_theme_color' => '#10b981'
        ],
        [
            'id' => 'BEST_TRAVEL',
            'name' => 'Best Travel',
            'icon_url' => 'https://cdn.fastsite.com/assets/besttravel_icon.png',
            'wrapper_endpoint' => 'https://fastsite.com/shop_wrapper.php?shop=BEST_TRAVEL',
            'native_theme_color' => '#3b82f6'
        ],
        [
            'id' => 'ENZOR_MOTOR',
            'name' => 'Enzor Motor',
            'icon_url' => 'https://cdn.fastsite.com/assets/enzor_icon.png',
            'wrapper_endpoint' => 'https://fastsite.com/shop_wrapper.php?shop=ENZOR_MOTOR',
            'native_theme_color' => '#ef4444'
        ]
    ]
];

echo json_encode([
    'status' => 'success',
    'config' => $super_app_config
]);
?>
