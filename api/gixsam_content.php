<?php
// =========================================================================
// api/gixsam_content.php
// Public CORS API serving GixSam Landing Page settings from Fast Site DB
// =========================================================================
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config.php';

$defaults = [
    'hero_name'      => 'Sadman Hossain Sayam',
    'hero_headline'  => 'Managing Director – Best Force Ltd & Best Travel',
    'hero_subtitle'  => 'Dynamic Entrepreneur, Global Explorer & Visionary Leader',
    'hero_photo_url' => 'https://gixsam.best-travel.ltd/assets/images/portrait.jpg',
    'hero_location'  => 'Dhaka, Bangladesh',
    'about_heading'  => 'Pioneering Excellence Across Security & Global Travel',
    'about_bio_1'    => 'Sadman Hossain Sayam is an ambitious Bangladeshi entrepreneur leading Best Force Ltd (Security & Logistics) and Best Travel (Global Tourism). With a vision to revolutionize digital marketplaces and security, he oversees an interconnected ecosystem of 7 enterprises.',
    'about_bio_2'    => 'Driven by innovation and trust, his mission is to build seamless B2B & B2C platforms that empower local Bangladeshis with world-class services.',
    'about_quote'    => 'Leadership is not about being in charge. It is about taking care of those in your charge.',
    'exp_years'      => '10+',
    'ventures_count' => '7+',
    'phone'          => '+880 1627-127534',
    'email'          => 'khangroup01@gmail.com',
    'whatsapp'       => '8801627127534',
    'facebook_url'   => 'https://www.facebook.com/share/18QWLZABMs/',
    'instagram_url'  => 'https://www.instagram.com/sadman_sayam/',
    'twitter_url'    => 'https://x.com/sadmansaya12282',
    'youtube_url'    => 'https://youtube.com/@princesayam6'
];

try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM gixsam_settings");
    $db_data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $result = array_merge($defaults, $db_data ?: []);
    echo json_encode(['status' => 'success', 'data' => $result]);
} catch (Exception $e) {
    echo json_encode(['status' => 'success', 'data' => $defaults]);
}
