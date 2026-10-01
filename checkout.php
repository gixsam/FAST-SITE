<?php
// =========================================================================
// checkout.php — Frictionless 3-Field Guest Checkout & SafePay Architecture
// Supports: Single-Product Quick Order & Multi-Store Unified Cart Split Escrow
// Accepts Cash on Delivery (COD), bKash (+8801337320544), or Nagad
// Zero Password Friction • Instant 1-Tap Guest Order • Split Escrow Holding
// =========================================================================
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/image_helper.php';
require_once __DIR__ . '/includes/escrow_engine.php';

$product_id = intval($_GET['product_id'] ?? $_POST['product_id'] ?? 0);
$partner_id = intval($_GET['partner_id'] ?? $_POST['partner_id'] ?? 0);

$exchange_rate = floatval(getPartnerSetting('exchange_rate', '1'));
$delivery_inside = floatval(getPartnerSetting('delivery_inside_dhaka', '60'));
$delivery_outside = floatval(getPartnerSetting('delivery_outside_dhaka', '120'));

// Detect Cart Checkout vs Single Product Checkout
$is_cart_mode = (isset($_GET['cart']) || isset($_POST['is_cart'])) || ($product_id <= 0 && !empty($_SESSION['cart']));

if ($is_cart_mode && empty($_SESSION['cart']) && !isset($_POST['place_order'])) {
    header('Location: /cart.php');
    exit;
}

// -------------------------------------------------------------------------
// FETCH DATA ACCORDING TO MODE
// -------------------------------------------------------------------------
$product = null;
$grouped_shops = [];
$total_cart_count = 0;
$cart_subtotal_bdt = 0.0;
$cart_subtotal_coins = 0;
$physical_shops_count = 0;

if (!$is_cart_mode) {
    // Single Product Mode
    if ($product_id > 0) {
        try {
            $stmt = $pdo->prepare("SELECT p.*, pt.shop_name, pt.business_name, pt.whatsapp as partner_whatsapp 
                                   FROM partner_products p 
                                   LEFT JOIN partners pt ON p.partner_id = pt.id 
                                   WHERE p.id = ? LIMIT 1");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {}
    }

    if (!$product) {
        $product = [
            'id' => 101,
            'title' => 'Ecosystem Verified Product',
            'price' => 1500.00,
            'image' => '/assets/images/placeholder.jpg',
            'shop_name' => 'Fast Site Verified Shop'
        ];
    }

    $product_price_bdt = floatval($product['price'] ?? 0) * $exchange_rate;
    $product['price_bdt'] = $product_price_bdt;

    if ($partner_id <= 0 && !empty($product['partner_id'])) {
        $partner_id = intval($product['partner_id']);
    }
} else {
    // Multi-Store Cart Mode
    $cart_items = $_SESSION['cart'] ?? [];
    foreach ($cart_items as $item) {
        $qty = intval($item['quantity']);
        $price = floatval($item['price_bdt']);
        $coins = intval($item['coins']);
        $p_id = intval($item['partner_id']);

        $total_cart_count += $qty;
        $cart_subtotal_bdt += ($price * $qty);
        $cart_subtotal_coins += ($coins * $qty);

        if (!isset($grouped_shops[$p_id])) {
            $grouped_shops[$p_id] = [
                'partner_id'     => $p_id,
                'shop_name'      => $item['shop_name'],
                'items'          => [],
                'subtotal_bdt'   => 0.0,
                'subtotal_coins' => 0,
                'has_physical'   => false
            ];
        }

        $grouped_shops[$p_id]['items'][] = $item;
        $grouped_shops[$p_id]['subtotal_bdt'] += ($price * $qty);
        $grouped_shops[$p_id]['subtotal_coins'] += ($coins * $qty);

        if (($item['shipping_type'] ?? 'physical') !== 'digital') {
            $grouped_shops[$p_id]['has_physical'] = true;
        }
    }

    foreach ($grouped_shops as $s) {
        if ($s['has_physical']) {
            $physical_shops_count++;
        }
    }
}

// Pre-fill user data if logged in
$current_user = null;
if (isset($_SESSION['user_id'])) {
    try {
        $u_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $u_stmt->execute([$_SESSION['user_id']]);
        $current_user = $u_stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
}

$prefill_name = $current_user['name'] ?? '';
$prefill_phone = $current_user['phone'] ?? '';
$prefill_address = $current_user['address'] ?? '';

$msg = ''; $err = ''; $order_placed = false; $new_order_id = '';
$placed_total = 0; $placed_method = '';
$placed_sub_orders = [];
$mystery_reward = 0;

// -------------------------------------------------------------------------
// ORDER SUBMISSION HANDLER
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $name = trim($_POST['customer_name'] ?? '');
    $phone = trim($_POST['customer_phone'] ?? '');
    $address = trim($_POST['delivery_address'] ?? '');
    $delivery_zone = trim($_POST['delivery_zone'] ?? 'inside_dhaka');
    $payment_method = trim($_POST['payment_method'] ?? 'COD');
    $trx_id = trim($_POST['transaction_id'] ?? '');
    $coupon_code = strtoupper(trim($_POST['coupon_code'] ?? ''));

    // Normalize and validate phone
    $clean_phone = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($clean_phone) === 13 && strpos($clean_phone, '880') === 0) {
        $clean_phone = substr($clean_phone, 2);
    }
    
    if (empty($name) || empty($clean_phone) || empty($address)) {
        $err = 'অনুগ্রহ করে আপনার নাম, মোবাইল নম্বর এবং সম্পূর্ণ ডেলিভারি ঠিকানা পূরণ করুন।';
    } elseif (strlen($clean_phone) !== 11 || strpos($clean_phone, '01') !== 0) {
        $err = 'অনুগ্রহ করে সঠিক ১১ ডিজিটের মোবাইল নম্বর দিন (যেমন: 017XXXXXXXX)।';
    } else {
        try {
            // 1. Auto-create or fetch user account by phone
            $user_id = 0;
            $uStmt = $pdo->prepare("SELECT id, name FROM users WHERE phone = ? OR email = ? LIMIT 1");
            $uStmt->execute([$clean_phone, $clean_phone . '@customer.fastsite']);
            $existing_user = $uStmt->fetch(PDO::FETCH_ASSOC);

            if ($existing_user) {
                $user_id = intval($existing_user['id']);
            } else {
                $ref_code = 'CUST' . rand(10000, 99999);
                $insU = $pdo->prepare("INSERT INTO users (name, phone, email, password_hash, ref_code, role) VALUES (?, ?, ?, ?, ?, 'customer')");
                $insU->execute([$name, $clean_phone, $clean_phone . '@customer.fastsite', password_hash($clean_phone, PASSWORD_DEFAULT), $ref_code]);
                $user_id = intval($pdo->lastInsertId());
            }

            // Auto-login session for guest so they can track their order seamlessly
            if (!isset($_SESSION['user_id'])) {
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_name'] = $name;
                $_SESSION['user_phone'] = $clean_phone;
                $_SESSION['role'] = 'customer';
            }

            $pay_status = ($payment_method === 'COD') ? 'pending_cod' : (!empty($trx_id) ? 'paid_unverified' : 'pending_payment');
            $delivery_area_label = ($delivery_zone === 'outside_dhaka' ? 'ঢাকা সিটির বাইরে' : ($delivery_zone === 'soft' ? 'ডিজিটাল ডেলিভারি' : 'ঢাকা সিটির ভিতরে'));
            $formatted_shipping = "নাম: {$name}\nমোবাইল: {$clean_phone}\nঠিকানা: {$address}\nএলাকা: {$delivery_area_label}";

            if (!$is_cart_mode) {
                // SINGLE PRODUCT ORDER
                $base_price = (float)$product['price_bdt'];
                $seller_id = $partner_id > 0 ? $partner_id : 1;
                
                // Validate & Apply Coupon
                $discount_amount = 0;
                $used_coupon_id = null;
                if (!empty($coupon_code)) {
                    $cStmt = $pdo->prepare("SELECT * FROM shop_coupons WHERE coupon_code = ? AND partner_id = ? AND is_active = 1 LIMIT 1");
                    $cStmt->execute([$coupon_code, $seller_id]);
                    $c = $cStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($c) {
                        $is_expired = ($c['expires_at'] && strtotime($c['expires_at']) < time());
                        $is_maxed = ($c['usage_limit'] > 0 && $c['used_count'] >= $c['usage_limit']);
                        $meets_min = ($base_price >= $c['min_order_bdt']);
                        
                        if (!$is_expired && !$is_maxed && $meets_min) {
                            if ($c['discount_type'] === 'percentage') {
                                $discount_amount = $base_price * ($c['discount_value'] / 100);
                            } else {
                                $discount_amount = (float)$c['discount_value'];
                            }
                            if ($discount_amount > $base_price) $discount_amount = $base_price;
                            $used_coupon_id = $c['id'];
                        } else {
                            throw new Exception("Coupon '$coupon_code' is expired, maxed out, or minimum order not met.");
                        }
                    } else {
                        throw new Exception("Coupon '$coupon_code' is not valid for this shop.");
                    }
                }

                $discounted_price = max(0, $base_price - $discount_amount);
                $delivery_charge = ($delivery_zone === 'outside_dhaka') ? $delivery_outside : (($delivery_zone === 'soft') ? 0 : $delivery_inside);
                $final_total_bdt = $discounted_price + $delivery_charge;

                $new_order_id = 'FS-' . strtoupper(substr(md5(uniqid()), 0, 8));

                $insO = $pdo->prepare("INSERT INTO partner_orders 
                    (partner_id, customer_id, product_id, total_coins, payment_method, payment_status, sender_number, transaction_id, gateway_ref, delivery_location, delivery_charge, shipping_address, status, customer_name, customer_phone, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, CURRENT_TIMESTAMP)");
                $insO->execute([
                    $seller_id,
                    $user_id,
                    $product['id'],
                    $final_total_bdt,
                    $payment_method,
                    $pay_status,
                    $clean_phone,
                    $trx_id,
                    $new_order_id,
                    $delivery_zone,
                    $delivery_charge,
                    $formatted_shipping,
                    $name,
                    $clean_phone
                ]);

                if ($used_coupon_id) {
                    $pdo->prepare("UPDATE shop_coupons SET used_count = used_count + 1 WHERE id = ?")->execute([$used_coupon_id]);
                }

                // Initialize 3-Layer SafePay Hold
                escrow_hold_funds($pdo, $new_order_id, $user_id, $seller_id, $final_total_bdt);

                $placed_total = $final_total_bdt;

            } else {
                // MULTI-STORE CART SPLIT ORDER
                $order_group_id = 'FS-GRP-' . strtoupper(substr(md5(uniqid()), 0, 8));
                $new_order_id = $order_group_id;
                $placed_total = 0;

                $per_shop_deliv = ($delivery_zone === 'outside_dhaka') ? $delivery_outside : (($delivery_zone === 'soft') ? 0 : $delivery_inside);

                foreach ($grouped_shops as $p_id => $shop_data) {
                    $shop_subtotal = floatval($shop_data['subtotal_bdt']);
                    $shop_delivery = ($delivery_zone === 'soft' || !$shop_data['has_physical']) ? 0.0 : $per_shop_deliv;
                    $shop_final_total = $shop_subtotal + $shop_delivery;

                    $sub_order_id = 'FS-' . strtoupper(substr(md5(uniqid(strval($p_id))), 0, 8));
                    $primary_prod_id = intval($shop_data['items'][0]['product_id'] ?? 0);
                    $order_items_json = json_encode($shop_data['items'], JSON_UNESCAPED_UNICODE);

                    $insO = $pdo->prepare("INSERT INTO partner_orders 
                        (partner_id, customer_id, product_id, total_coins, payment_method, payment_status, sender_number, transaction_id, gateway_ref, delivery_location, delivery_charge, shipping_address, status, order_group_id, customer_name, customer_phone, order_items, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?, CURRENT_TIMESTAMP)");
                    $insO->execute([
                        $p_id,
                        $user_id,
                        $primary_prod_id,
                        $shop_final_total,
                        $payment_method,
                        $pay_status,
                        $clean_phone,
                        $trx_id,
                        $sub_order_id,
                        $delivery_zone,
                        $shop_delivery,
                        $formatted_shipping,
                        $order_group_id,
                        $name,
                        $clean_phone,
                        $order_items_json
                    ]);

                    // Initialize SafePay hold per vendor order
                    escrow_hold_funds($pdo, $sub_order_id, $user_id, $p_id, $shop_final_total);

                    $placed_sub_orders[] = [
                        'order_id'    => $sub_order_id,
                        'shop_name'   => $shop_data['shop_name'],
                        'amount'      => $shop_final_total,
                        'items_count' => count($shop_data['items'])
                    ];

                    $placed_total += $shop_final_total;
                }

                // Clear session cart
                $_SESSION['cart'] = [];
            }

            // Post-Purchase Mystery Cashback (Phase 69)
            $mystery_reward = rand(5, 25);
            try {
                $pdo->prepare("UPDATE users SET coins_balance = coins_balance + ? WHERE id = ?")->execute([$mystery_reward, $user_id]);
                $pdo->prepare("INSERT INTO coin_transactions (user_id, type, amount, reference, status, created_at) VALUES (?, 'order_cashback', ?, ?, 'completed', CURRENT_TIMESTAMP)")
                    ->execute([$user_id, $mystery_reward, "Order #{$new_order_id} Mystery Cashback"]);
            } catch (Exception $ex) {}

            $order_placed = true;
            $placed_method = $payment_method;
            $msg = "Order successfully placed under 100% SafePay Buyer Guarantee (নিরাপদ গ্যারান্টি)!";

        } catch (Exception $e) {
            $err = 'অর্ডার সম্পন্ন হতে সমস্যা হয়েছে: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover"/>
  <title>SafePay Checkout — Fast Site</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Oswald:wght@600;700&family=Hind+Siliguri:wght@400;600;700&display=swap" rel="stylesheet"/>
  <style>
    :root {
      --gold: #fcb900;
      --gold-hover: #e0a500;
      --dark: #0A0D1A;
      --dark-card: rgba(18, 22, 43, 0.85);
      --border: rgba(255, 255, 255, 0.08);
      --border-gold: rgba(252, 185, 0, 0.35);
      --text: #f8fafc;
      --muted: #94a3b8;
      --green: #10b981;
      --red: #ef4444;
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      background: var(--dark);
      color: var(--text);
      font-family: 'Inter', 'Hind Siliguri', sans-serif;
      min-height: 100vh;
      line-height: 1.5;
      padding: 1.5rem 1rem calc(2rem + env(safe-area-inset-bottom, 0px)) 1rem;
    }

    .checkout-wrap {
      max-width: 680px;
      margin: 0 auto;
      background: var(--dark-card);
      backdrop-filter: blur(18px);
      -webkit-backdrop-filter: blur(18px);
      border: 1px solid var(--border-gold);
      border-radius: 20px;
      padding: 1.8rem 1.4rem;
      box-shadow: 0 16px 45px rgba(0, 0, 0, 0.65);
    }

    .top-brand-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.2rem;
      padding-bottom: 0.8rem;
      border-bottom: 1px solid var(--border);
    }

    .brand-title {
      font-family: 'Oswald', sans-serif;
      color: var(--gold);
      font-size: 1.35rem;
      text-transform: uppercase;
      letter-spacing: 0.04em;
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .back-link {
      color: var(--muted);
      font-size: 0.8rem;
      font-weight: 600;
      text-decoration: none;
      transition: color 0.2s;
    }
    .back-link:hover { color: #fff; }

    /* Single Product Summary Card */
    .item-box {
      display: flex;
      gap: 0.9rem;
      align-items: center;
      background: rgba(255, 255, 255, 0.03);
      border: 1px solid var(--border);
      border-radius: 14px;
      padding: 0.85rem;
      margin-bottom: 1.2rem;
    }

    .item-img {
      width: 72px;
      height: 72px;
      object-fit: cover;
      border-radius: 10px;
      border: 1px solid rgba(255, 255, 255, 0.1);
      background: #111;
      flex-shrink: 0;
    }

    .item-info {
      flex: 1;
      min-width: 0;
    }

    .item-title {
      font-weight: 700;
      font-size: 0.95rem;
      color: #fff;
      line-height: 1.3;
      margin-bottom: 3px;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .item-shop {
      font-size: 0.72rem;
      color: var(--muted);
      margin-bottom: 4px;
    }

    .item-price {
      font-size: 1.15rem;
      font-weight: 900;
      color: var(--gold);
      letter-spacing: -0.02em;
    }

    /* Multi-Store Cart Summary Box */
    .cart-summary-box {
      background: rgba(255, 255, 255, 0.03);
      border: 1px solid var(--border);
      border-radius: 14px;
      padding: 0.85rem 1rem;
      margin-bottom: 1.2rem;
    }
    .cart-summary-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 0.6rem;
      border-bottom: 1px solid rgba(255, 255, 255, 0.06);
      padding-bottom: 0.4rem;
    }

    /* SafePay Trust Banner */
    .safepay-banner {
      display: flex;
      align-items: center;
      gap: 8px;
      background: rgba(16, 185, 129, 0.08);
      border: 1px solid rgba(16, 185, 129, 0.25);
      border-radius: 10px;
      padding: 0.65rem 0.85rem;
      margin-bottom: 1.3rem;
      font-size: 0.78rem;
      color: #a7f3d0;
      line-height: 1.35;
    }

    /* Form Fields */
    .form-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 0.85rem;
      margin-bottom: 0.85rem;
    }

    @media (max-width: 600px) {
      .form-grid { grid-template-columns: 1fr; }
      .checkout-wrap { padding: 1.4rem 1rem; }
    }

    .field-group {
      display: flex;
      flex-direction: column;
      gap: 0.35rem;
      margin-bottom: 0.85rem;
    }

    .field-label {
      font-size: 0.75rem;
      color: #cbd5e1;
      font-weight: 700;
      letter-spacing: 0.02em;
    }

    .field-input, .field-select, .field-textarea {
      width: 100%;
      background: rgba(0, 0, 0, 0.35);
      border: 1px solid rgba(255, 255, 255, 0.12);
      border-radius: 10px;
      padding: 0.65rem 0.85rem;
      color: #fff;
      font-size: 0.88rem;
      font-family: inherit;
      outline: none;
      transition: border-color 0.2s, box-shadow 0.2s;
      box-sizing: border-box;
    }

    .field-input:focus, .field-select:focus, .field-textarea:focus {
      border-color: var(--gold);
      box-shadow: 0 0 0 3px rgba(252, 185, 0, 0.18);
    }

    .field-textarea {
      resize: vertical;
      min-height: 70px;
    }

    /* Payment Guide Box */
    .payment-guide-box {
      background: rgba(252, 185, 0, 0.06);
      border: 1px dashed rgba(252, 185, 0, 0.35);
      border-radius: 10px;
      padding: 0.85rem;
      margin-top: 0.5rem;
      font-size: 0.8rem;
      color: #e2e8f0;
      line-height: 1.45;
    }

    /* Promo Row */
    .promo-row {
      display: flex;
      gap: 0.5rem;
      margin-top: 0.4rem;
    }
    .promo-row input {
      flex: 1;
      text-transform: uppercase;
    }
    .btn-promo-apply {
      background: rgba(255, 255, 255, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.2);
      color: #fff;
      font-weight: 700;
      border-radius: 10px;
      padding: 0 1.2rem;
      cursor: pointer;
      transition: all 0.2s;
      white-space: nowrap;
    }
    .btn-promo-apply:hover {
      background: rgba(252, 185, 0, 0.2);
      border-color: var(--gold);
      color: var(--gold);
    }

    /* Price Summary Box */
    .summary-card {
      background: rgba(0, 0, 0, 0.25);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 0.85rem 1rem;
      margin-top: 1.2rem;
      margin-bottom: 1.4rem;
    }

    .summary-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 0.82rem;
      color: var(--muted);
      margin-bottom: 0.45rem;
    }

    .summary-row.total-row {
      font-size: 1.05rem;
      color: #fff;
      font-weight: 800;
      border-top: 1px dashed rgba(255, 255, 255, 0.1);
      padding-top: 0.6rem;
      margin-top: 0.5rem;
      margin-bottom: 0;
    }

    .summary-row.total-row strong {
      color: var(--gold);
      font-size: 1.25rem;
    }

    /* Submit Button */
    .btn-submit-order {
      width: 100%;
      background: linear-gradient(135deg, var(--gold), #ff9100);
      color: #000;
      font-weight: 900;
      font-size: 1rem;
      border: none;
      border-radius: 50px;
      padding: 0.9rem;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      box-shadow: 0 6px 20px rgba(252, 185, 0, 0.35);
      transition: transform 0.15s, box-shadow 0.15s;
      min-height: 48px;
    }
    .btn-submit-order:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 26px rgba(252, 185, 0, 0.5);
    }
    .btn-submit-order:active {
      transform: scale(0.97);
    }
  </style>
</head>
<body>

<div class="checkout-wrap">
  <div class="top-brand-bar">
    <a href="/" class="brand-title">
      <span>⚡</span> FAST SITE CHECKOUT
    </a>
    <?php if ($is_cart_mode): ?>
      <a href="/cart.php" class="back-link">← কার্টে ফিরে যান</a>
    <?php else: ?>
      <a href="product_detail.php?id=<?= $product['id'] ?>" class="back-link">← পণ্যের পেজে ফিরে যান</a>
    <?php endif; ?>
  </div>

  <?php if($msg): ?>
    <div style="background:rgba(16,185,129,0.15); border:1px solid #10b981; color:#10b981; padding:0.85rem 1rem; border-radius:12px; margin-bottom:1.2rem; font-weight:700; font-size:0.88rem;">
      ✅ <?= htmlspecialchars($msg) ?>
    </div>
  <?php endif; ?>

  <?php if($err): ?>
    <div style="background:rgba(239,68,68,0.15); border:1px solid #ef4444; color:#ef4444; padding:0.85rem 1rem; border-radius:12px; margin-bottom:1.2rem; font-weight:700; font-size:0.88rem;">
      ❌ <?= htmlspecialchars($err) ?>
    </div>
  <?php endif; ?>

  <?php if($order_placed): ?>
    <!-- SUCCESS CONFIRMATION VIEW -->
    <div style="text-align:center; padding:1.5rem; background:rgba(16,185,129,0.06); border:1px solid #10b981; border-radius:16px;">
      <div style="font-size:3rem; margin-bottom:0.5rem;">🎉</div>
      <h2 style="color:#10b981; font-family:'Oswald',sans-serif; font-size:1.5rem; margin-bottom:0.4rem;">ORDER CONFIRMED!</h2>
      <p style="color:#fff; font-size:1.15rem; font-weight:800; margin-bottom:0.4rem;">
        <?= !empty($placed_sub_orders) ? "Order Group: #" . htmlspecialchars($new_order_id) : "Order ID: #" . htmlspecialchars($new_order_id) ?>
      </p>
      <p style="color:#cbd5e1; font-size:0.88rem; max-width:480px; margin:0 auto 1.2rem auto; line-height:1.45;">
        আপনার অর্ডারটি SafePay সুরক্ষিত তহবিলে (Protected Vault) অন্তর্ভুক্ত করা হয়েছে। ডেলিভারির পর পণ্য হাতে পেয়ে সন্তুষ্ট হলে তবেই পেমেন্ট সম্পন্ন হবে।
      </p>

      <?php if (!empty($placed_sub_orders)): ?>
        <!-- Multi-Store Order Breakdown -->
        <div style="text-align:left; background:rgba(0,0,0,0.3); border:1px solid rgba(255,255,255,0.08); border-radius:12px; padding:0.85rem 1rem; margin:1rem 0;">
          <div style="font-size:0.8rem; font-weight:700; color:var(--gold); margin-bottom:0.6rem; text-transform:uppercase;">
            📦 শপ ভিত্তিক সাব-অর্ডারসমূহ (Individual Shop Orders):
          </div>
          <?php foreach ($placed_sub_orders as $sub): ?>
            <div style="display:flex; justify-content:space-between; align-items:center; padding:0.45rem 0; border-bottom:1px dashed rgba(255,255,255,0.06);">
              <div>
                <strong style="color:#fff; font-size:0.88rem;">🏪 <?= htmlspecialchars($sub['shop_name']) ?></strong>
                <div style="font-size:0.75rem; color:var(--muted);">Order #<?= htmlspecialchars($sub['order_id']) ?> • <?= $sub['items_count'] ?> টি পণ্য</div>
              </div>
              <div style="color:var(--gold); font-weight:800; font-size:0.95rem;">
                ৳<?= number_format($sub['amount'], 2) ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if ($placed_method === 'COD'): ?>
        <div style="background:rgba(16,185,129,0.1); border:1px solid rgba(16,185,129,0.3); padding:0.9rem; border-radius:12px; margin-top:1rem; text-align:left; font-size:0.85rem;">
          <strong style="color:#10b981;">💵 ক্যাশ অন ডেলিভারি (COD):</strong>
          <p style="margin:4px 0 0 0; color:#e2e8f0;">ডেলিভারি ম্যানের কাছে পার্সেল পাওয়ার পর সর্বমোট <strong>৳<?= number_format($placed_total, 2) ?></strong> পরিশোধ করবেন।</p>
        </div>
      <?php else: ?>
        <div style="background:rgba(252,185,0,0.1); border:1px solid var(--gold); padding:0.9rem; border-radius:12px; margin-top:1rem; text-align:left; font-size:0.85rem;">
          <strong style="color:var(--gold);"><?= htmlspecialchars($placed_method) ?> পেমেন্ট নির্দেশিকা:</strong>
          <p style="margin:4px 0 0 0; color:#fff;">অনুগ্রহ করে সর্বমোট <strong>৳<?= number_format($placed_total, 2) ?></strong> টাকা আমাদের অফিসিয়াল নম্বরে পাঠান: <br>
          <span style="color:var(--gold); font-weight:900; font-size:1.15rem;">+8801337320544</span></p>
        </div>
      <?php endif; ?>

      <!-- Mystery Cashback Card -->
      <?php if ($mystery_reward > 0): ?>
      <div id="mysteryCard" style="margin-top:1.5rem; background: linear-gradient(135deg, #181b2a, #0f121d); border: 2px dashed #fcb900; border-radius: 14px; padding: 1.5rem; cursor: pointer; transition: all 0.3s; box-shadow: 0 0 20px rgba(252,185,0,0.2);">
        <div id="mysteryCover">
          <div style="font-size: 2.5rem; margin-bottom: 0.3rem;">🎁</div>
          <h3 style="color: #fcb900; font-size:1.05rem; margin: 0;">Tap to Reveal Your Mystery Cashback!</h3>
          <p style="color: #94a3b8; font-size: 0.78rem; margin: 0.3rem 0 0 0;">A special shopping reward for your order.</p>
        </div>
        <div id="mysteryReveal" style="display: none;">
          <div style="font-size: 2.5rem; margin-bottom: 0.3rem;">🎉</div>
          <h2 style="color: #00e676; font-size:1.3rem; margin: 0;">+<?= $mystery_reward ?> Fast Cash / Points</h2>
          <p style="color: #94a3b8; font-size: 0.78rem; margin: 0.3rem 0 0 0;">Coins have been instantly credited to your wallet!</p>
        </div>
      </div>
      <?php endif; ?>

      <div style="display:flex; gap:0.6rem; justify-content:center; margin-top:1.5rem; flex-wrap:wrap;">
        <a href="/user/partner_orders.php" style="text-decoration:none;">
          <button style="background:rgba(252,185,0,0.15); border:1px solid var(--gold); color:var(--gold); font-weight:700; border-radius:50px; padding:0.65rem 1.4rem; cursor:pointer; font-size:0.85rem;">📦 ট্র্যাক অর্ডার (My Orders)</button>
        </a>
        <a href="/" style="text-decoration:none;">
          <button style="background:rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.2); color:#fff; font-weight:700; border-radius:50px; padding:0.65rem 1.4rem; cursor:pointer; font-size:0.85rem;">🏠 হোমপেজে ফিরে যান</button>
        </a>
      </div>
    </div>

    <script>
      const mysteryCard = document.getElementById('mysteryCard');
      if (mysteryCard) {
        mysteryCard.addEventListener('click', function() {
          document.getElementById('mysteryCover').style.display = 'none';
          document.getElementById('mysteryReveal').style.display = 'block';
          this.style.borderStyle = 'solid';
          this.style.borderColor = '#00e676';
          this.style.boxShadow = '0 0 25px rgba(0,230,118,0.3)';
          this.style.cursor = 'default';
        });
      }
    </script>

  <?php else: ?>

    <?php if ($is_cart_mode): ?>
      <!-- MULTI-STORE CART DISPLAY -->
      <div class="cart-summary-box">
        <div class="cart-summary-header">
          <span style="font-weight:800; font-size:0.95rem; color:#fff;">🛒 কার্টের পণ্যসমূহ (<?= $total_cart_count ?> টি পণ্য)</span>
          <a href="/cart.php" style="color:var(--gold); font-size:0.78rem; text-decoration:none; font-weight:700;">✏️ কার্ট পরিবর্তন করুন</a>
        </div>
        <?php foreach ($grouped_shops as $p_id => $s): ?>
          <div style="margin-bottom:0.75rem;">
            <div style="font-size:0.82rem; font-weight:700; color:var(--gold); margin-bottom:4px;">
              🏪 <?= htmlspecialchars($s['shop_name']) ?>
            </div>
            <?php foreach ($s['items'] as $item): ?>
              <div style="display:flex; justify-content:space-between; align-items:center; font-size:0.82rem; padding:3px 0; color:#e2e8f0;">
                <span style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:65%;">
                  • <?= htmlspecialchars($item['title']) ?> <span style="color:var(--muted);">(x<?= $item['quantity'] ?>)</span>
                </span>
                <strong style="color:#fff;">৳<?= number_format($item['price_bdt'] * $item['quantity'], 0) ?></strong>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <!-- SINGLE PRODUCT DISPLAY -->
      <?php 
        $chkArt = resolveProductArtwork($product['image'] ?? $product['thumbnail'] ?? $product['image_url'] ?? '', '', $product['shop_name'] ?? '', $product['category'] ?? '', $product['title'] ?? '');
      ?>
      <div class="item-box">
        <img src="<?= htmlspecialchars($chkArt) ?>" class="item-img" alt="Product" onerror="this.onerror=null; this.src='/assets/images/services/default_service.svg';"/>
        <div class="item-info">
          <div class="item-title"><?= htmlspecialchars($product['title']) ?></div>
          <div class="item-shop">🏪 <?= htmlspecialchars($product['shop_name'] ?? 'Fast Site Shop') ?></div>
          <div class="item-price">৳<?= number_format($product['price_bdt'], 0) ?></div>
        </div>
      </div>
    <?php endif; ?>

    <!-- SafePay Guarantee Badge -->
    <div class="safepay-banner">
      <span style="font-size:1.3rem;">🛡️</span>
      <div>
        <strong>SafePay ১০০% ক্রেতা গ্যারান্টি:</strong> সঠিক পণ্য হাতে পেয়ে ডেলিভারি ম্যানের উপস্থিতিতে চেক করার পরই সেলার পেমেন্ট পাবে। কোনো সমস্যা হলে ১০০% রিফান্ড সহায়তা।
      </div>
    </div>

    <!-- 3-FIELD FRICTIONLESS ORDER FORM -->
    <form method="POST" id="checkout-form">
      <?php if ($is_cart_mode): ?>
        <input type="hidden" name="is_cart" value="1"/>
      <?php else: ?>
        <input type="hidden" name="product_id" value="<?= $product['id'] ?>"/>
        <input type="hidden" name="partner_id" value="<?= $partner_id ?>"/>
      <?php endif; ?>

      <!-- Field 1 & 2: Name & Phone -->
      <div class="form-grid">
        <div class="field-group">
          <label class="field-label" for="customer_name">১. আপনার পূর্ণ নাম (Full Name) *</label>
          <input type="text" id="customer_name" name="customer_name" class="field-input" placeholder="আপনার নাম লিখুন" value="<?= htmlspecialchars($_POST['customer_name'] ?? $prefill_name) ?>" required/>
        </div>

        <div class="field-group">
          <label class="field-label" for="customer_phone">২. মোবাইল নম্বর (Phone Number) *</label>
          <input type="tel" id="customer_phone" name="customer_phone" class="field-input" placeholder="017XXXXXXXX" value="<?= htmlspecialchars($_POST['customer_phone'] ?? $prefill_phone) ?>" pattern="^(?:\+?88)?01[3-9]\d{8}$" title="সঠিক ১১ ডিজিটের মোবাইল নম্বর দিন" required/>
        </div>
      </div>

      <!-- Field 3: Delivery Address -->
      <div class="field-group">
        <label class="field-label" for="delivery_address">৩. পূর্ণাঙ্গ ডেলিভারি ঠিকানা ও জেলা (Delivery Address) *</label>
        <textarea id="delivery_address" name="delivery_address" class="field-textarea" placeholder="বাসা/রোড নম্বর, এলাকা, থানা ও জেলা উল্লেখ করুন..." required><?= htmlspecialchars($_POST['delivery_address'] ?? $prefill_address) ?></textarea>
      </div>

      <!-- Delivery Zone -->
      <div class="field-group">
        <label class="field-label" for="delivery_zone">ডেলিভারি এলাকা নির্বাচন করুন *</label>
        <select id="delivery_zone" name="delivery_zone" class="field-select" onchange="recalculateTotal()">
          <option value="inside_dhaka" <?= (($_POST['delivery_zone'] ?? 'inside_dhaka') === 'inside_dhaka') ? 'selected' : '' ?>>
            ঢাকা সিটির ভিতরে (২৪-৪৮ ঘণ্টা) — ৳<?= number_format($delivery_inside, 0) ?> <?= ($is_cart_mode && $physical_shops_count > 1) ? "(প্রতি শপ)" : "" ?>
          </option>
          <option value="outside_dhaka" <?= (($_POST['delivery_zone'] ?? '') === 'outside_dhaka') ? 'selected' : '' ?>>
            ঢাকা সিটির বাইরে (২-৩ দিন) — ৳<?= number_format($delivery_outside, 0) ?> <?= ($is_cart_mode && $physical_shops_count > 1) ? "(প্রতি শপ)" : "" ?>
          </option>
          <option value="soft" <?= (($_POST['delivery_zone'] ?? '') === 'soft') ? 'selected' : '' ?>>
            ডিজিটাল ডেলিভারি (অনলাইন ইনস্ট্যান্ট) — ৳০
          </option>
        </select>
      </div>

      <!-- Payment Method Selection -->
      <div class="field-group">
        <label class="field-label">পেমেন্ট মেথড নির্বাচন করুন *</label>
        <div style="display:flex; gap:0.6rem; margin-top:0.3rem;">
          <label style="flex:1; background:rgba(0,0,0,0.3); border:1px solid rgba(255,255,255,0.12); padding:0.75rem 0.6rem; border-radius:10px; cursor:pointer; text-align:center; font-size:0.82rem; font-weight:700; transition:all 0.2s;" id="label-cod">
            <input type="radio" name="payment_method" value="COD" checked onchange="handlePaymentChange('COD')"/>
            <div style="margin-top:3px;">💵 ক্যাশ অন ডেলিভারি</div>
          </label>
          <label style="flex:1; background:rgba(0,0,0,0.3); border:1px solid rgba(255,255,255,0.12); padding:0.75rem 0.6rem; border-radius:10px; cursor:pointer; text-align:center; font-size:0.82rem; font-weight:700; transition:all 0.2s;" id="label-bkash">
            <input type="radio" name="payment_method" value="bKash" onchange="handlePaymentChange('bKash')"/>
            <div style="margin-top:3px;">🌸 bKash Send Money</div>
          </label>
          <label style="flex:1; background:rgba(0,0,0,0.3); border:1px solid rgba(255,255,255,0.12); padding:0.75rem 0.6rem; border-radius:10px; cursor:pointer; text-align:center; font-size:0.82rem; font-weight:700; transition:all 0.2s;" id="label-nagad">
            <input type="radio" name="payment_method" value="Nagad" onchange="handlePaymentChange('Nagad')"/>
            <div style="margin-top:3px;">🟠 Nagad Send Money</div>
          </label>
        </div>
      </div>

      <!-- Dynamic MFS Instructions -->
      <div id="mfs-guide-box" class="payment-guide-box" style="display:none;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
          <strong id="mfs-title" style="color:var(--gold);">bKash Payment Guide</strong>
          <span style="font-size:0.7rem; background:rgba(252,185,0,0.2); color:var(--gold); padding:2px 6px; border-radius:4px;">Personal / Send Money</span>
        </div>
        <p style="margin:4px 0;">আমাদের অফিশিয়াল বিকাশ/নগদ নম্বর:</p>
        <div style="display:flex; align-items:center; justify-content:space-between; background:rgba(0,0,0,0.4); border:1px solid rgba(252,185,0,0.3); padding:0.5rem 0.8rem; border-radius:8px; margin:4px 0;">
          <span style="font-size:1.15rem; font-weight:900; color:var(--gold); letter-spacing:0.04em;">01337320544</span>
          <button type="button" onclick="navigator.clipboard.writeText('01337320544'); alert('নম্বর কপি করা হয়েছে!');" style="background:var(--gold); border:none; color:#000; font-weight:800; font-size:0.75rem; padding:4px 8px; border-radius:5px; cursor:pointer;">কপি করুন</button>
        </div>
        <p style="font-size:0.74rem; color:var(--muted); margin-top:4px;">* টাকা পাঠানোর পর নিচে প্রেরক নম্বর ও TrxID প্রদান করুন (অথবা ক্যাশ অন ডেলিভারি নির্বাচন করুন)।</p>

        <div style="margin-top:0.6rem;">
          <label class="field-label" for="transaction_id">Transaction ID (TrxID)</label>
          <input type="text" id="transaction_id" name="transaction_id" class="field-input" placeholder="e.g. 9J47ABX78"/>
        </div>
      </div>

      <!-- Coupon Code (Single Product Mode) -->
      <?php if (!$is_cart_mode): ?>
      <div class="field-group" style="margin-top:0.8rem;">
        <label class="field-label" for="coupon_code">প্রোমোকোড / কুপন থাকলে দিন (Optional)</label>
        <div class="promo-row">
          <input type="text" id="coupon_code" name="coupon_code" class="field-input" placeholder="PROMO CODE" value="<?= htmlspecialchars($_POST['coupon_code'] ?? '') ?>"/>
          <button type="button" class="btn-promo-apply" onclick="applyCoupon()">প্রয়োগ করুন</button>
        </div>
      </div>
      <?php endif; ?>

      <!-- Price Breakdown Summary -->
      <div class="summary-card">
        <div class="summary-row">
          <span>পণ্যের মূল্য (Subtotal):</span>
          <span id="summary-subtotal">৳<?= number_format($is_cart_mode ? $cart_subtotal_bdt : $product['price_bdt'], 0) ?></span>
        </div>
        <div class="summary-row">
          <span>ডেলিভারি চার্জ:</span>
          <span id="summary-delivery">৳<?= number_format($delivery_inside * max(1, $physical_shops_count), 0) ?></span>
        </div>
        <div class="summary-row total-row">
          <span>সর্বমোট প্রদেয়:</span>
          <strong id="summary-grand-total">৳<?= number_format(($is_cart_mode ? $cart_subtotal_bdt : $product['price_bdt']) + ($delivery_inside * max(1, $physical_shops_count)), 0) ?></strong>
        </div>
      </div>

      <!-- Submit Order Button -->
      <button type="submit" name="place_order" class="btn-submit-order" id="btn-submit">
        <span>🛡️</span>
        <span>অর্ডার কনফার্ম করুন (SafePay Guarantee)</span>
      </button>

      <p style="text-align:center; font-size:0.72rem; color:var(--muted); margin-top:0.8rem;">
        অর্ডার কনফার্ম করার মাধ্যমে আপনি Fast Site এর <a href="/policy.php" style="color:var(--gold); text-decoration:none;">শর্তাবলী ও রিফান্ড নীতিমালা</a> মেনে নিচ্ছেন।
      </p>

    </form>

  <?php endif; ?>

</div>

<script>
const baseSubtotal = <?= $is_cart_mode ? $cart_subtotal_bdt : $product['price_bdt'] ?>;
const insideRate = <?= $delivery_inside ?>;
const outsideRate = <?= $delivery_outside ?>;
const physicalShops = <?= max(1, $physical_shops_count) ?>;

function recalculateTotal() {
  const zone = document.getElementById('delivery_zone').value;
  let deliv = 0;
  if (zone === 'inside_dhaka') {
    deliv = insideRate * physicalShops;
  } else if (zone === 'outside_dhaka') {
    deliv = outsideRate * physicalShops;
  } else {
    deliv = 0;
  }

  const grand = baseSubtotal + deliv;
  document.getElementById('summary-delivery').innerText = '৳' + deliv.toLocaleString();
  document.getElementById('summary-grand-total').innerText = '৳' + grand.toLocaleString();
}

function handlePaymentChange(method) {
  const guide = document.getElementById('mfs-guide-box');
  const title = document.getElementById('mfs-title');
  if (method === 'COD') {
    guide.style.display = 'none';
  } else {
    guide.style.display = 'block';
    title.innerText = (method === 'bKash') ? 'bKash Send Money নির্দেশিকা' : 'Nagad Send Money নির্দেশিকা';
  }
}

function applyCoupon() {
  const code = document.getElementById('coupon_code').value.trim();
  if (!code) {
    alert('অনুগ্রহ করে কুপন কোড লিখুন।');
    return;
  }
  document.getElementById('checkout-form').submit();
}
</script>

</body>
</html>
