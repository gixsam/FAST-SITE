<?php
// Mock rendering test with SQLite in-memory DB
$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Create required tables
$pdo->exec("
    CREATE TABLE homepage_settings (
        setting_key TEXT PRIMARY KEY,
        setting_value TEXT
    );
    INSERT INTO homepage_settings (setting_key, setting_value) VALUES 
    ('site_name', 'FAST SITE'),
    ('feature_geotagging', 'on'),
    ('mobile_grid_cols', '2'),
    ('banner_type', 'none');

    CREATE TABLE partner_products (
        id INTEGER PRIMARY KEY,
        partner_id INTEGER,
        title TEXT,
        description TEXT,
        price TEXT,
        category TEXT,
        listing_type TEXT,
        is_published INTEGER,
        affiliate_url TEXT,
        affiliate_link TEXT,
        audio_file TEXT,
        created_at TEXT
    );

    CREATE TABLE partners (
        id INTEGER PRIMARY KEY,
        user_id INTEGER,
        phone TEXT,
        business_name TEXT,
        status TEXT,
        rating REAL,
        is_official INTEGER,
        total_orders INTEGER,
        tags TEXT,
        seller_level INTEGER,
        profile_pic TEXT,
        description TEXT,
        district TEXT
    );

    CREATE TABLE partner_product_images (
        id INTEGER PRIMARY KEY,
        product_id INTEGER,
        image_url TEXT,
        is_thumbnail INTEGER
    );

    CREATE TABLE shop_tags (
        id INTEGER PRIMARY KEY,
        tag_name TEXT
    );

    CREATE TABLE users (
        id INTEGER PRIMARY KEY,
        name TEXT,
        phone TEXT,
        coins_balance INTEGER,
        profile_pic TEXT
    );

    INSERT INTO partners (id, user_id, phone, business_name, status, is_official) 
    VALUES (1, 10, '01700000000', 'Ayra Mart', 'approved', 1);

    INSERT INTO partner_products (id, partner_id, title, description, price, category, listing_type, is_published, created_at)
    VALUES (1, 1, 'Tech SaaS Subscription', 'Full tech access', '1000', 'Technology', 'service', 1, '2026-09-01 12:00:00');
");

// Define helper functions if not present
if (!function_exists('getPartnerSetting')) {
    function getPartnerSetting($key, $default = '') {
        return $default;
    }
}
if (!function_exists('resolveProductArtwork')) {
    function resolveProductArtwork($thumb, $fallback, $shop, $cat, $title, $type) {
        return '/assets/images/services/default_service.svg';
    }
}

$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['PHP_SELF'] = '/index.php';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/index.php';
$_SESSION = [
    'user_id' => 10,
    'user_name' => 'Reviewer Admin'
];

echo "Mock Environment Initialized.\n";

// Test rendering of includes/nav_public.php
echo "Testing includes/nav_public.php rendering...\n";
$coins = 500;
$user_name = 'Reviewer';
$is_user_logged_in = true;
ob_start();
include __DIR__ . '/../../includes/nav_public.php';
$nav_html = ob_get_clean();

$checks_nav = [
    'public-nav' => strpos($nav_html, 'public-nav') !== false,
    'nav-zone-left' => strpos($nav_html, 'nav-zone-left') !== false,
    'nav-zone-center' => strpos($nav_html, 'nav-zone-center') !== false,
    'nav-zone-right' => strpos($nav_html, 'nav-zone-right') !== false,
    'coin-badge-pill' => strpos($nav_html, 'coin-badge-pill') !== false,
    'nav-brand-centered' => strpos($nav_html, 'nav-brand-centered') !== false,
    'shop-nav-btn' => strpos($nav_html, 'shop-nav-btn') !== false,
    'safe-area-inset-top' => strpos($nav_html, 'safe-area-inset-top') !== false,
    'hamburger-btn' => strpos($nav_html, 'hamburger-btn') !== false
];

foreach ($checks_nav as $k => $v) {
    echo "NAV CHECK [$k]: " . ($v ? "PASS" : "FAIL") . "\n";
}

// Test rendering of home.php markup components
echo "\nTesting home.php markup rendering...\n";
// Extract HTML portion of home.php (from lines 338 to 1900)
$home_content = file_get_contents(__DIR__ . '/../../home.php');

$checks_home = [
    'search-command-hub' => strpos($home_content, 'search-command-hub') !== false,
    'omniSearchInput' => strpos($home_content, 'id="omniSearchInput"') !== false,
    'btn-filter-trigger' => strpos($home_content, 'btn-filter-trigger') !== false,
    'filter-badge-dot' => strpos($home_content, 'filter-badge-dot') !== false,
    'quick-types-ribbon' => strpos($home_content, 'quick-types-ribbon') !== false,
    'ribbon-all' => strpos($home_content, '📦 All') !== false,
    'ribbon-products' => strpos($home_content, '🛍️ Products') !== false,
    'ribbon-services' => strpos($home_content, '🤝 Services') !== false,
    'ribbon-shops' => strpos($home_content, '🏪 Shops') !== false,
    'ribbon-deals' => strpos($home_content, '⚡ Deals') !== false,
    'ribbon-categories-trigger' => strpos($home_content, 'category-trigger-pill') !== false,
    'categoryDrawer' => strpos($home_content, 'id="categoryDrawer"') !== false,
    'categoryDrawerScrim' => strpos($home_content, 'id="categoryDrawerScrim"') !== false,
    'drawer-handle-bar' => strpos($home_content, 'drawer-handle-bar') !== false,
    'type-card-grid' => strpos($home_content, 'type-card-grid') !== false,
    'categories-visual-grid' => strpos($home_content, 'categories-visual-grid') !== false,
    'category-drawer-footer' => strpos($home_content, 'category-drawer-footer') !== false,
    'btn-drawer-reset' => strpos($home_content, 'btn-drawer-reset') !== false,
    'btn-drawer-apply' => strpos($home_content, 'btn-drawer-apply') !== false,
    'ptr-classes-exemption' => (strpos($home_content, 'category-drawer drawer-menu modal-box') !== false),
    'toggleCategoryDrawer-js' => strpos($home_content, 'function toggleCategoryDrawer') !== false,
    'onDrawerTypeChange-js' => strpos($home_content, 'function onDrawerTypeChange') !== false,
    'onDrawerCatChange-js' => strpos($home_content, 'function onDrawerCatChange') !== false
];

foreach ($checks_home as $k => $v) {
    echo "HOME CHECK [$k]: " . ($v ? "PASS" : "FAIL") . "\n";
}
