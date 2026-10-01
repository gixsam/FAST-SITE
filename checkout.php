<?php
// =========================================================================
// checkout.php — Frictionless 3-Field Guest Checkout & SafePay Architecture
// Accepts Cash on Delivery (COD), bKash (+8801337320544), or Nagad
// Zero Password Friction • Instant 1-Tap Guest Order
// =========================================================================
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/escrow_engine.php';

$product_id = intval($_GET['product_id'] ?? $_POST['product_id'] ?? 0);
$partner_id = intval($_GET['partner_id'] ?? $_POST['partner_id'] ?? 0);

$exchange_rate = floatval(getPartnerSetting('exchange_rate', '1'));
$delivery_inside = floatval(getPartnerSetting('delivery_inside_dhaka', '60'));
$delivery_outside = floatval(getPartnerSetting('delivery_outside_dhaka', '120'));

// Fetch product details
$product = null;
if ($product_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT p.*, pt.shop_name, pt.whatsapp as partner_whatsapp 
                               FROM partner_products p 
                               LEFT JOIN partners pt ON p.partner_id = pt.id 
                               WHERE p.id = ? LIMIT 1");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
}

// Fallback demo product
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

            // 2. Generate unique order ID
            $new_order_id = 'FS-' . strtoupper(substr(md5(uniqid()), 0, 8));
            $formatted_shipping = "নাম: {$name}\nমোবাইল: {$clean_phone}\nঠিকানা: {$address}\nএলাকা: " . ($delivery_zone === 'outside_dhaka' ? 'ঢাকা সিটির বাইরে' : ($delivery_zone === 'soft' ? 'ডিজিটাল ডেলিভারি' : 'ঢাকা সিটির ভিতরে'));

            // 3. Create partner_orders record with all shipping & payment fields
            $pay_status = ($payment_method === 'COD') ? 'pending_cod' : (!empty($trx_id) ? 'paid_unverified' : 'pending_payment');

            $insO = $pdo->prepare("INSERT INTO partner_orders 
                (partner_id, customer_id, product_id, total_coins, payment_method, payment_status, sender_number, transaction_id, gateway_ref, delivery_location, delivery_charge, shipping_address, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', CURRENT_TIMESTAMP)");
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
                $formatted_shipping
            ]);

            if ($used_coupon_id) {
                $pdo->prepare("UPDATE shop_coupons SET used_count = used_count + 1 WHERE id = ?")->execute([$used_coupon_id]);
            }

            // 4. Initialize 3-Layer SafePay Hold
            escrow_hold_funds($pdo, $new_order_id, $user_id, $seller_id, $final_total_bdt);

            // Phase 69: Post-Purchase Mystery Reward
            $mystery_reward = rand(5, 25);
            try {
                $pdo->prepare("UPDATE users SET coins_balance = coins_balance + ? WHERE id = ?")->execute([$mystery_reward, $user_id]);
                $pdo->prepare("INSERT INTO coin_transactions (user_id, type, amount, reference, status, created_at) VALUES (?, 'order_cashback', ?, ?, 'completed', CURRENT_TIMESTAMP)")
                    ->execute([$user_id, $mystery_reward, "Order #{$new_order_id} Mystery Cashback"]);
            } catch (Exception $ex) {}

            $order_placed = true;
            $placed_total = $final_total_bdt;
            $placed_method = $payment_method;
            $msg = "Order #{$new_order_id} successfully placed under 100% SafePay Buyer Guarantee (নিরাপদ গ্যারান্টি)!";
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
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Oswald:wght@600;700&display=swap" rel="stylesheet"/>
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
      font-family: 'Inter', sans-serif;
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

    /* Product Summary Card */
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
    <a href="product_detail.php?id=<?= $product['id'] ?>" class="back-link">← পণ্যের পেজে ফিরে যান</a>
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
      <p style="color:#fff; font-size:1.15rem; font-weight:800; margin-bottom:0.4rem;">Order ID: #<?= htmlspecialchars($new_order_id) ?></p>
      <p style="color:#cbd5e1; font-size:0.88rem; max-width:480px; margin:0 auto 1.2rem auto; line-height:1.45;">
        আপনার অর্ডারটি SafePay সুরক্ষিত তহবিলে (Protected Vault) অন্তর্ভুক্ত করা হয়েছে। ডেলিভারির পর পণ্য হাতে পেয়ে সন্তুষ্ট হলে তবেই পেমেন্ট সম্পন্ন হবে।
      </p>

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

    <!-- Product Summary Card -->
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

    <!-- SafePay Guarantee Badge -->
    <div class="safepay-banner">
      <span style="font-size:1.3rem;">🛡️</span>
      <div>
        <strong>SafePay ১০০% ক্রেতা গ্যারান্টি:</strong> সঠিক পণ্য হাতে পেয়ে ডেলিভারি ম্যানের উপস্থিতিতে চেক করার পরই সেলার পেমেন্ট পাবে। কোনো সমস্যা হলে ১০০% রিফান্ড সহায়তা।
      </div>
    </div>

    <!-- 3-FIELD FRICTIONLESS ORDER FORM -->
    <form method="POST" id="checkout-form">
      <input type="hidden" name="product_id" value="<?= $product['id'] ?>"/>
      <input type="hidden" name="partner_id" value="<?= $partner_id ?>"/>

      <!-- Field 1 & 2: Name & Phone -->
      <div class="form-grid">
        <div class="field-group">
          <label class="field-label">আপনার পূর্ণ নাম (Full Name) *</label>
          <input type="text" name="customer_name" class="field-input" required value="<?= htmlspecialchars($prefill_name) ?>" placeholder="যেমন: রহিম চৌধুরী"/>
        </div>
        <div class="field-group">
          <label class="field-label">মোবাইল নম্বর (11-Digit Mobile Number) *</label>
          <input type="tel" name="customer_phone" class="field-input" required value="<?= htmlspecialchars($prefill_phone) ?>" pattern="^(?:\+?88)?01[3-9]\d{8}$" placeholder="01XXXXXXXXX"/>
        </div>
      </div>

      <!-- Field 3: Delivery Address -->
      <div class="field-group">
        <label class="field-label">ডেলিভারি ঠিকানা ও জেলা (Full Address) *</label>
        <textarea name="delivery_address" class="field-textarea" required rows="2" placeholder="বাসা/হোল্ডিং নম্বর, রোড, এলাকা, থানা এবং জেলা..."><?= htmlspecialchars($prefill_address) ?></textarea>
      </div>

      <!-- Delivery Zone & Payment Method -->
      <div class="form-grid">
        <div class="field-group">
          <label class="field-label">ডেলিভারি এলাকা (Delivery Area) *</label>
          <select name="delivery_zone" id="delivery_zone" class="field-select" onchange="recalculateTotal()">
            <option value="inside_dhaka" data-cost="<?= $delivery_inside ?>">ঢাকা সিটির ভিতরে (৳<?= number_format($delivery_inside, 0) ?>)</option>
            <option value="outside_dhaka" data-cost="<?= $delivery_outside ?>">ঢাকা সিটির বাইরে (৳<?= number_format($delivery_outside, 0) ?>)</option>
            <option value="soft" data-cost="0">ডিজিটাল ডেলিভারি (৳0)</option>
          </select>
        </div>
        <div class="field-group">
          <label class="field-label">পেমেন্ট পদ্ধতি (Payment Method) *</label>
          <select name="payment_method" id="payment_method" class="field-select" onchange="updatePaymentGuide()">
            <option value="COD">💵 ক্যাশ অন ডেলিভারি (COD)</option>
            <option value="bKash">📱 bKash (বিকাশ সেন্ড মানি)</option>
            <option value="Nagad">📱 Nagad (নগদ সেন্ড মানি)</option>
          </select>
        </div>
      </div>

      <!-- Payment Guide Box -->
      <div id="payment-guide" class="payment-guide-box">
        💵 <strong>ক্যাশ অন ডেলিভারি:</strong> অগ্রিম কোনো টাকা দিতে হবে না। পার্সেল হাতে পেয়ে ডেলিভারি ম্যানের কাছে মূল্য পরিশোধ করবেন।
      </div>

      <div id="trx-input-group" class="field-group" style="display:none; margin-top:0.75rem;">
        <label class="field-label">Transaction ID / TrxID (যদি অগ্রিম পরিশোধ করে থাকেন)</label>
        <input type="text" name="transaction_id" class="field-input" placeholder="যেমন: 9J87AK2X0"/>
      </div>

      <!-- Promo Code Field -->
      <div class="field-group" style="margin-top:0.6rem;">
        <label class="field-label">কুপন বা ডিসকাউন্ট কোড (ঐচ্ছিক)</label>
        <div class="promo-row">
          <input type="text" name="coupon_code" id="coupon_code" class="field-input" placeholder="PROMO CODE">
          <button type="button" class="btn-promo-apply" id="btn-apply-promo">Apply</button>
        </div>
        <div id="promo-msg" style="margin-top:4px; font-size:0.78rem; font-weight:700;"></div>
      </div>

      <!-- Price Breakdown Summary -->
      <div class="summary-card">
        <div class="summary-row">
          <span>পণ্যের মূল্য:</span>
          <span id="sum-product-price">৳<?= number_format($product['price_bdt'], 0) ?></span>
        </div>
        <div class="summary-row">
          <span>ডেলিভারি চার্জ:</span>
          <span id="sum-delivery-charge">৳<?= number_format($delivery_inside, 0) ?></span>
        </div>
        <div class="summary-row" id="sum-discount-row" style="display:none; color:#10b981;">
          <span>কুপন ডিসকাউন্ট:</span>
          <span id="sum-discount-amount">-৳0</span>
        </div>
        <div class="summary-row total-row">
          <span>সর্বমোট প্রদেয়:</span>
          <strong id="sum-grand-total">৳<?= number_format($product['price_bdt'] + $delivery_inside, 0) ?></strong>
        </div>
      </div>

      <!-- Submit Order Button -->
      <button type="submit" name="place_order" class="btn-submit-order">
        <span>🛡️</span>
        <span>SafePay গ্যারান্টিতে অর্ডার কনফার্ম করুন</span>
      </button>
    </form>

  <?php endif; ?>
</div>

<script>
const basePrice = <?= floatval($product['price_bdt']) ?>;
let discountVal = 0;

function recalculateTotal() {
  const zoneSelect = document.getElementById('delivery_zone');
  if (!zoneSelect) return;
  const activeOpt = zoneSelect.options[zoneSelect.selectedIndex];
  const deliveryCharge = parseFloat(activeOpt.getAttribute('data-cost') || 0);

  const grandTotal = Math.max(0, basePrice - discountVal) + deliveryCharge;

  document.getElementById('sum-delivery-charge').textContent = '৳' + deliveryCharge.toFixed(0);
  document.getElementById('sum-grand-total').textContent = '৳' + grandTotal.toFixed(0);
}

function updatePaymentGuide() {
  const method = document.getElementById('payment_method').value;
  const guide = document.getElementById('payment-guide');
  const trxGroup = document.getElementById('trx-input-group');

  if (method === 'COD') {
    guide.innerHTML = '💵 <strong>ক্যাশ অন ডেলিভারি:</strong> অগ্রিম কোনো টাকা দিতে হবে না। পার্সেল হাতে পেয়ে ডেলিভারি ম্যানের কাছে মূল্য পরিশোধ করবেন।';
    trxGroup.style.display = 'none';
  } else if (method === 'bKash') {
    guide.innerHTML = '📱 <strong>bKash পেমেন্ট:</strong> আমাদের অফিসিয়াল মার্চেন্ট নম্বরে <strong>+8801337320544</strong> সেন্ড মানি করুন। পেমেন্ট শেষে ট্রানজেকশন আইডি নিচে দিতে পারেন।';
    trxGroup.style.display = 'flex';
  } else if (method === 'Nagad') {
    guide.innerHTML = '📱 <strong>Nagad পেমেন্ট:</strong> আমাদের অফিসিয়াল নগদ নম্বরে <strong>+8801337320544</strong> সেন্ড মানি করুন। পেমেন্ট শেষে ট্রানজেকশন আইডি নিচে দিতে পারেন।';
    trxGroup.style.display = 'flex';
  }
}

document.addEventListener('DOMContentLoaded', function() {
  const btnApply = document.getElementById('btn-apply-promo');
  const inputCode = document.getElementById('coupon_code');
  const msgDiv = document.getElementById('promo-msg');
  const discRow = document.getElementById('sum-discount-row');
  const discEl = document.getElementById('sum-discount-amount');

  if (btnApply) {
    btnApply.addEventListener('click', function() {
      const code = inputCode.value.trim();
      const shopId = <?= $partner_id > 0 ? $partner_id : 1 ?>;
      if (!code) return;

      msgDiv.style.color = '#fff';
      msgDiv.innerText = 'যাচাই করা হচ্ছে...';

      fetch(`api/validate_coupon.php?code=${encodeURIComponent(code)}&shop_id=${shopId}&price=${basePrice}`)
        .then(r => r.json())
        .then(data => {
          if (data.success) {
            msgDiv.style.color = '#10b981';
            if (data.discount_type === 'percentage') {
              discountVal = basePrice * (parseFloat(data.discount_value) / 100);
              msgDiv.innerText = `✅ ${data.discount_value}% ছাড় সফলভাবে যুক্ত হয়েছে!`;
            } else {
              discountVal = parseFloat(data.discount_value);
              msgDiv.innerText = `✅ ৳${data.discount_value} ফ্ল্যাট ছাড় যুক্ত হয়েছে!`;
            }
            if (discountVal > basePrice) discountVal = basePrice;
            discRow.style.display = 'flex';
            discEl.textContent = '-৳' + discountVal.toFixed(0);
            recalculateTotal();
          } else {
            msgDiv.style.color = '#ef4444';
            msgDiv.innerText = '❌ ' + (data.error || 'ভুল কুপন কোড');
            discountVal = 0;
            discRow.style.display = 'none';
            recalculateTotal();
          }
        })
        .catch(() => {
          msgDiv.style.color = '#ef4444';
          msgDiv.innerText = '❌ নেটওয়ার্ক সমস্যা হয়েছে।';
        });
    });
  }
});
</script>

</body>
</html>
