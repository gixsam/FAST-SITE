<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');

$code = strtoupper(trim($_GET['code'] ?? ''));
$shop_id = intval($_GET['shop_id'] ?? 0);
$price = floatval($_GET['price'] ?? 0);

if (empty($code) || !$shop_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid request.']);
    exit;
}

// Look for the coupon belonging to this shop
$stmt = $pdo->prepare("SELECT * FROM shop_coupons WHERE coupon_code = :code AND partner_id = :pid AND is_active = 1 LIMIT 1");
$stmt->execute([':code' => $code, ':pid' => $shop_id]);
$coupon = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$coupon) {
    echo json_encode(['success' => false, 'error' => 'Invalid promo code for this shop.']);
    exit;
}

// Check expiration
if ($coupon['expires_at'] && strtotime($coupon['expires_at']) < time()) {
    echo json_encode(['success' => false, 'error' => 'This promo code has expired.']);
    exit;
}

// Check usage limits
if ($coupon['usage_limit'] > 0 && $coupon['used_count'] >= $coupon['usage_limit']) {
    echo json_encode(['success' => false, 'error' => 'This promo code has reached its usage limit.']);
    exit;
}

// Check min order
if ($price > 0 && $price < $coupon['min_order_bdt']) {
    echo json_encode(['success' => false, 'error' => 'Minimum order amount is ৳' . number_format($coupon['min_order_bdt'], 2)]);
    exit;
}

// It's valid!
echo json_encode([
    'success' => true,
    'discount_value' => (float)$coupon['discount_value'],
    'discount_type' => $coupon['discount_type'],
    'coupon_id' => $coupon['id']
]);
