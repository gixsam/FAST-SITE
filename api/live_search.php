<?php
// =========================================================================
// api/live_search.php — Real-Time Instant Live Search API (Algolia Style)
// =========================================================================
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

$search = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? 'All');
$type = trim($_GET['type'] ?? 'all');
$district = trim($_GET['district'] ?? '');

$exchange_rate = floatval(getPartnerSetting('exchange_rate', '1'));

$where = ["p.is_published = 1", "(pt.status = 'approved' OR p.partner_id = 0)"];
$params = [];

if ($search !== '') {
    $where[] = "(p.title LIKE :search OR p.description LIKE :search OR pt.business_name LIKE :search)";
    $params[':search'] = '%' . $search . '%';
} elseif ($type === 'offers' || $type === 'affiliate') {
    $where[] = "(p.affiliate_url IS NOT NULL AND p.affiliate_url != '')";
} else {
    $where[] = "(p.affiliate_url IS NULL OR p.affiliate_url = '')";
}

if ($category !== '' && $category !== 'All') {
    $where[] = "p.category = :cat";
    $params[':cat'] = $category;
}

if ($district !== '') {
    $where[] = "(pt.district = :district OR p.partner_id = 0)";
    $params[':district'] = $district;
}

$wsql = implode(' AND ', $where);

$sql = "SELECT p.id, p.title, p.price, p.category, p.partner_id, p.listing_type, p.created_at, p.affiliate_url,
        COALESCE(pt.business_name, 'Fast Site Official') AS shop_name,
        (SELECT image_url FROM partner_product_images WHERE product_id = p.id AND is_thumbnail = 1 LIMIT 1) AS thumb,
        (SELECT image_url FROM partner_product_images WHERE product_id = p.id ORDER BY id ASC LIMIT 1) AS fallback_img
        FROM partner_products p
        LEFT JOIN partners pt ON p.partner_id = pt.id
        WHERE $wsql
        ORDER BY p.created_at DESC LIMIT 60";

$results = [];
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    foreach ($rows as $r) {
        $isService = ($r['listing_type'] ?? 'product') === 'service';
        if ($type === 'services' && !$isService) continue;
        if ($type === 'products' && $isService) continue;

        $priceBdt = floatval(preg_replace('/[^0-9.]/', '', $r['price']));
        $priceCoins = ceil($priceBdt / $exchange_rate);
        
        $thumbSrc = resolveProductArtwork(
            $r['thumb'] ?? '',
            $r['fallback_img'] ?? '',
            $r['shop_name'] ?? '',
            $r['category'] ?? '',
            $r['title'] ?? '',
            $r['listing_type'] ?? 'product'
        );

        $isAffiliate = ($r['listing_type'] ?? '') === 'affiliate' || !empty($r['affiliate_url']);
        $affUrl = $r['affiliate_url'] ?? '';
        $targetUrl = ($isAffiliate && !empty($affUrl)) ? $affUrl : 'product_detail.php?id=' . $r['id'];

        $results[] = [
            'id' => $r['id'],
            'title' => $r['title'],
            'price_coins' => number_format($priceCoins),
            'price_bdt' => number_format($priceBdt, 0),
            'shop_name' => $r['shop_name'],
            'category' => $isAffiliate ? '⚡ Partner Offer' : ($isService ? 'Service' : ($r['category'] ?? 'Product')),
            'is_service' => $isService,
            'is_affiliate' => $isAffiliate,
            'is_external' => $isAffiliate && !empty($affUrl),
            'thumb' => $thumbSrc,
            'detail_url' => $targetUrl
        ];
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}

echo json_encode(['status' => 'success', 'count' => count($results), 'data' => $results]);
