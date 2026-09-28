<?php
// =========================================================================
// api/news_feed.php  –  Bangla News RSS parser returning JSON
// =========================================================================
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config.php';

$feedUrl = $GLOBALS['settings']['rss_feed_url'] ?? 'https://www.prothomalo.com/feed';
if (empty($feedUrl)) {
    $feedUrl = 'https://www.prothomalo.com/feed';
}

$response = [];

// Try to fetch RSS feed with a strict timeout
$context = stream_context_create([
    'http' => [
        'timeout' => 5, // 5 seconds timeout
        'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
    ]
]);

$rssContent = @file_get_contents($feedUrl, false, $context);

if ($rssContent) {
    // Disable external entity loading for safety
    $backup_entity_loader = false;
    if (function_exists('libxml_disable_entity_loader')) {
        $backup_entity_loader = libxml_disable_entity_loader(true);
    }
    
    $xml = @simplexml_load_string($rssContent, 'SimpleXMLElement', LIBXML_NOCDATA);
    
    if (function_exists('libxml_disable_entity_loader')) {
        libxml_disable_entity_loader($backup_entity_loader);
    }

    if ($xml && isset($xml->channel->item)) {
        $count = 0;
        foreach ($xml->channel->item as $item) {
            $response[] = [
                'title' => (string)$item->title,
                'link' => (string)$item->link,
                'pubDate' => (string)$item->pubDate
            ];
            $count++;
            if ($count >= 15) { // Limit to top 15 items
                break;
            }
        }
    }
}

// Fallback items if RSS fetch fails
if (empty($response)) {
    $response = [
        [
            'title' => 'বাংলাদেশ জাতীয় পরিচয়পত্র সংশোধন ও পাসপোর্ট সেবার দ্রুত সমাধান এখন ফাস্ট সাইটে।',
            'link' => '#',
            'pubDate' => date('r')
        ],
        [
            'title' => 'নতুন ড্রাইভিং লাইসেন্স এবং অনলাইন জন্ম নিবন্ধন সেবাসমূহ চালু হয়েছে।',
            'link' => '#',
            'pubDate' => date('r')
        ],
        [
            'title' => 'ফাস্ট সাইট হেডকোয়ার্টার (Fast Site HQ) ও পার্টনার প্যানেল নতুন রূপ নিয়েছে।',
            'link' => '#',
            'pubDate' => date('r')
        ]
    ];
}

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
