<?php
// =========================================================================
// cart.php — Smart Multi-Store Unified Shopping Cart & Split Escrow Engine
// Phase 104: Allows multi-vendor products in 1 cart with grouped shop views
// Google Stitch Nocturne Aurum Design Standards
// =========================================================================
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/image_helper.php';

// Initialize Cart Session
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$exchange_rate = floatval(getPartnerSetting('exchange_rate', '1'));
$delivery_inside = floatval(getPartnerSetting('delivery_inside_dhaka', '60'));
$delivery_outside = floatval(getPartnerSetting('delivery_outside_dhaka', '120'));

// -------------------------------------------------------------------------
// ACTION DISPATCHER (Add, Update, Remove, Clear)
// -------------------------------------------------------------------------
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
           || (isset($_POST['ajax']) && $_POST['ajax'] == '1')
           || (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false);

if (!empty($action)) {
    if ($action === 'add') {
        $product_id = intval($_POST['product_id'] ?? $_GET['product_id'] ?? 0);
        $qty = max(1, intval($_POST['quantity'] ?? $_GET['quantity'] ?? $_POST['qty'] ?? $_GET['qty'] ?? 1));

        if ($product_id > 0) {
            try {
                $stmt = $pdo->prepare("SELECT p.*, pt.shop_name, pt.business_name, pt.is_official, pt.whatsapp as partner_whatsapp 
                                       FROM partner_products p 
                                       LEFT JOIN partners pt ON p.partner_id = pt.id 
                                       WHERE p.id = ? LIMIT 1");
                $stmt->execute([$product_id]);
                $prod = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($prod) {
                    $pid = intval($prod['id']);
                    $p_partner_id = intval($prod['partner_id'] ?: 1);
                    $p_shop_name = !empty($prod['shop_name']) ? $prod['shop_name'] : (!empty($prod['business_name']) ? $prod['business_name'] : 'Fast Site Official');
                    $p_price_bdt = floatval($prod['price'] ?? 0) * $exchange_rate;
                    $p_coins = ceil(floatval($prod['price'] ?? 0));
                    $p_shipping = $prod['shipping_type'] ?? 'physical';
                    $p_thumb = resolveProductArtwork($prod['image'] ?? '', $prod['id'] ?? 0);

                    if (isset($_SESSION['cart'][$pid])) {
                        $_SESSION['cart'][$pid]['quantity'] += $qty;
                    } else {
                        $_SESSION['cart'][$pid] = [
                            'product_id'    => $pid,
                            'partner_id'    => $p_partner_id,
                            'shop_name'     => $p_shop_name,
                            'title'         => $prod['title'] ?? 'Product #' . $pid,
                            'price_bdt'     => $p_price_bdt,
                            'coins'         => $p_coins,
                            'quantity'      => $qty,
                            'image'         => $p_thumb,
                            'shipping_type' => $p_shipping
                        ];
                    }

                    $total_items = array_sum(array_column($_SESSION['cart'], 'quantity'));

                    if ($is_ajax) {
                        header('Content-Type: application/json; charset=utf-8');
                        echo json_encode([
                            'success'    => true,
                            'cart_count' => $total_items,
                            'message'    => 'পণ্যটি কার্টে যোগ করা হয়েছে!',
                            'product'    => [
                                'id'       => $pid,
                                'title'    => $prod['title'],
                                'quantity' => $_SESSION['cart'][$pid]['quantity']
                            ]
                        ]);
                        exit;
                    }

                    header('Location: /cart.php?added=1');
                    exit;
                }
            } catch (Exception $ex) {
                if ($is_ajax) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['success' => false, 'error' => $ex->getMessage()]);
                    exit;
                }
            }
        }
    }

    if ($action === 'update') {
        $product_id = intval($_POST['product_id'] ?? $_GET['product_id'] ?? 0);
        $qty = intval($_POST['quantity'] ?? $_GET['quantity'] ?? 0);

        if ($product_id > 0) {
            if ($qty <= 0) {
                unset($_SESSION['cart'][$product_id]);
            } else {
                $_SESSION['cart'][$product_id]['quantity'] = min(99, $qty);
            }

            $total_items = array_sum(array_column($_SESSION['cart'], 'quantity'));

            if ($is_ajax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success'    => true,
                    'cart_count' => $total_items,
                    'message'    => 'কার্ট আপডেট করা হয়েছে!'
                ]);
                exit;
            }
        }
        header('Location: /cart.php');
        exit;
    }

    if ($action === 'remove') {
        $product_id = intval($_GET['product_id'] ?? $_POST['product_id'] ?? 0);
        if ($product_id > 0 && isset($_SESSION['cart'][$product_id])) {
            unset($_SESSION['cart'][$product_id]);
        }
        if ($is_ajax) {
            $total_items = array_sum(array_column($_SESSION['cart'], 'quantity'));
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => true, 'cart_count' => $total_items]);
            exit;
        }
        header('Location: /cart.php');
        exit;
    }

    if ($action === 'clear') {
        $_SESSION['cart'] = [];
        header('Location: /cart.php');
        exit;
    }
}

// -------------------------------------------------------------------------
// CART DATA PREPARATION & MULTI-STORE GROUPING
// -------------------------------------------------------------------------
$selected_zone = $_POST['delivery_zone'] ?? $_GET['delivery_zone'] ?? $_SESSION['delivery_zone'] ?? 'inside_dhaka';
$_SESSION['delivery_zone'] = $selected_zone;

$cart_items = $_SESSION['cart'];
$total_cart_count = 0;
$cart_subtotal_bdt = 0.0;
$cart_subtotal_coins = 0;
$grouped_shops = [];

foreach ($cart_items as $item) {
    $qty = intval($item['quantity']);
    $price = floatval($item['price_bdt']);
    $coins = intval($item['coins']);
    $partner_id = intval($item['partner_id']);

    $total_cart_count += $qty;
    $cart_subtotal_bdt += ($price * $qty);
    $cart_subtotal_coins += ($coins * $qty);

    if (!isset($grouped_shops[$partner_id])) {
        $grouped_shops[$partner_id] = [
            'partner_id'     => $partner_id,
            'shop_name'      => $item['shop_name'],
            'items'          => [],
            'subtotal_bdt'   => 0.0,
            'subtotal_coins' => 0,
            'has_physical'   => false
        ];
    }

    $grouped_shops[$partner_id]['items'][] = $item;
    $grouped_shops[$partner_id]['subtotal_bdt'] += ($price * $qty);
    $grouped_shops[$partner_id]['subtotal_coins'] += ($coins * $qty);

    if (($item['shipping_type'] ?? 'physical') !== 'digital') {
        $grouped_shops[$partner_id]['has_physical'] = true;
    }
}

// Calculate delivery charges across distinct shops requiring delivery
$physical_shops_count = 0;
foreach ($grouped_shops as $s) {
    if ($s['has_physical']) {
        $physical_shops_count++;
    }
}

if ($selected_zone === 'outside_dhaka') {
    $per_shop_delivery = $delivery_outside;
} elseif ($selected_zone === 'soft') {
    $per_shop_delivery = 0.0;
} else {
    $per_shop_delivery = $delivery_inside;
}

$total_delivery_charge = ($selected_zone === 'soft') ? 0.0 : ($physical_shops_count * $per_shop_delivery);
$grand_total_bdt = $cart_subtotal_bdt + $total_delivery_charge;

$site_name = 'FAST SITE';
?>
<!DOCTYPE html>
<html lang="bn">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover"/>
  <title>আপনার শপিং কার্ট | <?= htmlspecialchars($site_name) ?> SafePay</title>
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Oswald:wght@600;700;800&family=Hind+Siliguri:wght@400;600;700&display=swap" rel="stylesheet">
  
  <link rel="stylesheet" href="/assets/css/native_mobile.css"/>
  
  <style>
    :root {
      --bg: #0A0D1A;
      --card-bg: rgba(18, 22, 43, 0.85);
      --card-border: rgba(255, 255, 255, 0.08);
      --gold: #F59E0B;
      --gold-glow: rgba(245, 158, 11, 0.25);
      --green: #10B981;
      --green-glow: rgba(16, 185, 129, 0.25);
      --red: #EF4444;
      --text: #F3F4F6;
      --muted: #9CA3AF;
    }

    body {
      background: var(--bg);
      color: var(--text);
      font-family: 'Inter', 'Hind Siliguri', sans-serif;
      margin: 0;
      padding: 0;
      min-height: 100vh;
      -webkit-font-smoothing: antialiased;
    }

    .cart-container {
      max-width: 1100px;
      margin: 0 auto;
      padding: 1.5rem 1rem 7rem;
      box-sizing: border-box;
    }

    /* Page Header */
    .cart-header {
      margin-bottom: 1.5rem;
    }
    .cart-title {
      font-size: 1.6rem;
      font-weight: 800;
      color: #fff;
      display: flex;
      align-items: center;
      gap: 0.5rem;
      margin: 0 0 0.4rem;
    }
    .cart-subtitle {
      font-size: 0.88rem;
      color: var(--muted);
      margin: 0;
    }

    /* Trust Strip */
    .trust-strip {
      display: flex;
      flex-wrap: wrap;
      gap: 0.8rem;
      background: rgba(245, 158, 11, 0.06);
      border: 1px solid rgba(245, 158, 11, 0.2);
      border-radius: 12px;
      padding: 0.75rem 1rem;
      margin-bottom: 1.5rem;
      font-size: 0.82rem;
      color: #FBBF24;
      align-items: center;
      justify-content: space-around;
    }
    .trust-item {
      display: flex;
      align-items: center;
      gap: 6px;
      font-weight: 600;
    }

    /* 2-Column Grid Layout */
    .cart-layout {
      display: grid;
      grid-template-columns: 1fr 360px;
      gap: 1.5rem;
      align-items: start;
    }

    @media (max-width: 900px) {
      .cart-layout {
        grid-template-columns: 1fr;
      }
    }

    /* Shop Container Group */
    .shop-group-card {
      background: var(--card-bg);
      border: 1px solid var(--card-border);
      border-radius: 16px;
      padding: 1.2rem;
      margin-bottom: 1.2rem;
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      box-shadow: 0 8px 30px rgba(0, 0, 0, 0.35);
    }

    .shop-header-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding-bottom: 0.8rem;
      border-bottom: 1px solid rgba(255, 255, 255, 0.06);
      margin-bottom: 1rem;
    }
    .shop-name-tag {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 1.05rem;
      font-weight: 800;
      color: #fff;
    }
    .shop-badge-verified {
      background: rgba(16, 185, 129, 0.15);
      border: 1px solid rgba(16, 185, 129, 0.3);
      color: #34D399;
      font-size: 0.7rem;
      font-weight: 700;
      padding: 2px 8px;
      border-radius: 50px;
      display: inline-flex;
      align-items: center;
      gap: 3px;
    }

    /* Cart Product Row */
    .cart-item-row {
      display: grid;
      grid-template-columns: 72px 1fr auto;
      gap: 1rem;
      align-items: center;
      padding: 0.9rem 0;
      border-bottom: 1px dashed rgba(255, 255, 255, 0.05);
    }
    .cart-item-row:last-child {
      border-bottom: none;
      padding-bottom: 0.2rem;
    }

    .cart-item-thumb {
      width: 72px;
      height: 72px;
      border-radius: 10px;
      object-fit: cover;
      background: #111422;
      border: 1px solid rgba(255, 255, 255, 0.08);
      flex-shrink: 0;
    }

    .cart-item-info {
      min-width: 0;
    }
    .cart-item-title {
      font-size: 0.95rem;
      font-weight: 700;
      color: #fff;
      margin: 0 0 4px;
      line-height: 1.35;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
      text-decoration: none;
    }
    .cart-item-title:hover {
      color: var(--gold);
    }
    .cart-item-price-unit {
      font-size: 0.82rem;
      color: var(--gold);
      font-weight: 700;
      display: flex;
      align-items: center;
      gap: 6px;
    }
    .cart-item-price-unit span {
      color: var(--muted);
      font-size: 0.75rem;
      font-weight: 500;
    }

    /* Quantity Stepper */
    .cart-item-actions {
      display: flex;
      flex-direction: column;
      align-items: flex-end;
      gap: 0.5rem;
    }

    .qty-stepper {
      display: inline-flex;
      align-items: center;
      background: rgba(0, 0, 0, 0.4);
      border: 1px solid rgba(255, 255, 255, 0.12);
      border-radius: 8px;
      overflow: hidden;
    }
    .qty-btn {
      background: transparent;
      border: none;
      color: #fff;
      font-size: 1rem;
      font-weight: 800;
      width: 32px;
      height: 32px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: background 0.2s;
    }
    .qty-btn:hover {
      background: rgba(255, 255, 255, 0.1);
      color: var(--gold);
    }
    .qty-display {
      width: 36px;
      text-align: center;
      font-size: 0.85rem;
      font-weight: 800;
      color: #fff;
      border: none;
      background: transparent;
    }

    .cart-item-subtotal {
      font-size: 0.95rem;
      font-weight: 800;
      color: #fff;
      text-align: right;
    }

    .btn-remove-item {
      background: transparent;
      border: none;
      color: #ef4444;
      font-size: 0.75rem;
      cursor: pointer;
      padding: 2px 6px;
      border-radius: 4px;
      display: inline-flex;
      align-items: center;
      gap: 4px;
      transition: background 0.2s;
    }
    .btn-remove-item:hover {
      background: rgba(239, 68, 68, 0.15);
    }

    /* Shop Subtotal Bar */
    .shop-subtotal-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: rgba(255, 255, 255, 0.02);
      border: 1px solid rgba(255, 255, 255, 0.04);
      border-radius: 10px;
      padding: 0.65rem 0.9rem;
      margin-top: 0.9rem;
      font-size: 0.85rem;
    }
    .shop-subtotal-bar strong {
      color: var(--gold);
      font-size: 0.95rem;
    }

    /* Order Summary Card */
    .cart-summary-card {
      background: var(--card-bg);
      border: 1px solid rgba(245, 158, 11, 0.3);
      border-radius: 18px;
      padding: 1.4rem;
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5), 0 0 20px var(--gold-glow);
      position: sticky;
      top: 80px;
    }
    .summary-title {
      font-size: 1.15rem;
      font-weight: 800;
      color: #fff;
      margin: 0 0 1.2rem;
      padding-bottom: 0.8rem;
      border-bottom: 1px solid rgba(255, 255, 255, 0.08);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .summary-line {
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 0.88rem;
      color: var(--muted);
      margin-bottom: 0.8rem;
    }
    .summary-line strong {
      color: #fff;
      font-weight: 700;
    }

    /* Delivery Zone Selector */
    .zone-selector-box {
      background: rgba(0, 0, 0, 0.25);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 12px;
      padding: 0.85rem;
      margin: 1rem 0;
    }
    .zone-selector-label {
      font-size: 0.78rem;
      font-weight: 700;
      color: var(--gold);
      margin-bottom: 0.6rem;
      display: block;
      text-transform: uppercase;
      letter-spacing: 0.04em;
    }
    .zone-option {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 0.5rem;
      border-radius: 8px;
      margin-bottom: 4px;
      cursor: pointer;
      font-size: 0.82rem;
      transition: background 0.2s;
    }
    .zone-option:hover {
      background: rgba(255, 255, 255, 0.04);
    }
    .zone-option input[type="radio"] {
      accent-color: var(--gold);
    }

    /* Grand Total */
    .grand-total-box {
      background: rgba(245, 158, 11, 0.08);
      border: 1px solid rgba(245, 158, 11, 0.25);
      border-radius: 12px;
      padding: 1rem;
      margin: 1.2rem 0;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .grand-total-label {
      font-size: 0.95rem;
      font-weight: 800;
      color: #fff;
    }
    .grand-total-val-bdt {
      font-size: 1.35rem;
      font-weight: 900;
      color: var(--gold);
      text-align: right;
    }
    .grand-total-val-coins {
      font-size: 0.78rem;
      color: var(--muted);
      text-align: right;
    }

    /* Checkout Primary CTA */
    .btn-checkout-primary {
      width: 100%;
      background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);
      color: #0A0D1A;
      font-size: 1.05rem;
      font-weight: 800;
      padding: 0.95rem;
      border: none;
      border-radius: 50px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      text-decoration: none;
      box-shadow: 0 4px 20px var(--gold-glow);
      transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
      box-sizing: border-box;
    }
    .btn-checkout-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 25px rgba(245, 158, 11, 0.45);
      filter: brightness(1.05);
    }
    .btn-checkout-primary:active {
      transform: scale(0.97);
    }

    .btn-clear-cart {
      background: transparent;
      border: 1px solid rgba(255, 255, 255, 0.1);
      color: var(--muted);
      width: 100%;
      padding: 0.6rem;
      border-radius: 50px;
      font-size: 0.8rem;
      font-weight: 600;
      margin-top: 0.6rem;
      cursor: pointer;
      transition: all 0.2s;
    }
    .btn-clear-cart:hover {
      border-color: rgba(239, 68, 68, 0.4);
      color: #ef4444;
    }

    /* Empty Cart State */
    .empty-cart-card {
      background: var(--card-bg);
      border: 1px solid var(--card-border);
      border-radius: 20px;
      padding: 3.5rem 1.5rem;
      text-align: center;
      max-width: 500px;
      margin: 3rem auto;
      box-shadow: 0 8px 30px rgba(0, 0, 0, 0.4);
    }
    .empty-cart-icon {
      font-size: 4rem;
      margin-bottom: 1rem;
      display: inline-block;
      filter: drop-shadow(0 0 15px rgba(245, 158, 11, 0.3));
    }
    .empty-cart-title {
      font-size: 1.4rem;
      font-weight: 800;
      color: #fff;
      margin: 0 0 0.5rem;
    }
    .empty-cart-desc {
      font-size: 0.9rem;
      color: var(--muted);
      margin: 0 0 1.8rem;
      line-height: 1.5;
    }

    /* Mobile Sticky Checkout Dock (< 768px) */
    .mobile-cart-dock {
      display: none;
      position: fixed;
      bottom: 0;
      left: 0;
      right: 0;
      background: rgba(10, 13, 26, 0.96);
      border-top: 1px solid rgba(245, 158, 11, 0.3);
      padding: 0.75rem 1rem calc(0.75rem + env(safe-area-inset-bottom, 0px));
      z-index: 999;
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.6);
    }

    @media (max-width: 768px) {
      .mobile-cart-dock {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
      }
      .cart-container {
        padding-bottom: 8rem;
      }
      .cart-item-row {
        grid-template-columns: 60px 1fr;
        gap: 0.75rem;
      }
      .cart-item-thumb {
        width: 60px;
        height: 60px;
      }
      .cart-item-actions {
        grid-column: span 2;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        margin-top: 0.3rem;
      }
    }
  </style>
</head>
<body>

<?php include __DIR__ . '/includes/nav_public.php'; ?>

<main class="cart-container">
  
  <!-- Header -->
  <div class="cart-header">
    <h1 class="cart-title">
      <span>🛒</span> আপনার শপিং কার্ট
      <?php if ($total_cart_count > 0): ?>
        <span style="font-size: 0.9rem; background: rgba(245, 158, 11, 0.18); border: 1px solid var(--gold); color: var(--gold); padding: 2px 10px; border-radius: 50px; font-weight: 800;">
          <?= $total_cart_count ?> টি পণ্য
        </span>
      <?php endif; ?>
    </h1>
    <p class="cart-subtitle">SafePay সুরক্ষিত তহবিল গ্যারান্টি সহ সহজে একাধিক শপ থেকে একবারে অর্ডার করুন</p>
  </div>

  <!-- Trust Strip -->
  <div class="trust-strip">
    <div class="trust-item">
      <span>🛡️</span> SafePay ১০০% সুরক্ষিত তহবিল
    </div>
    <div class="trust-item">
      <span>🚚</span> ক্যাশ অন ডেলিভারি (COD)
    </div>
    <div class="trust-item">
      <span>🔄</span> সহজ রিটার্ন ও রিফান্ড ক্লেইম
    </div>
  </div>

  <?php if (empty($cart_items)): ?>
    <!-- Empty Cart Card -->
    <div class="empty-cart-card">
      <div class="empty-cart-icon">🛒</div>
      <h2 class="empty-cart-title">আপনার কার্ট বর্তমানে খালি</h2>
      <p class="empty-cart-desc">
        মার্কেটপ্লেসের আকর্ষণীয় ও ভেরিফাইড সব পণ্য দেখতে এখনই শপিং শুরু করুন।
      </p>
      <a href="/index.php" class="btn-checkout-primary" style="display:inline-flex; width:auto; padding: 0.85rem 2rem;">
        🛍️ মার্কেটপ্লেস ব্রাউজ করুন (Start Shopping)
      </a>
    </div>
  <?php else: ?>

    <div class="cart-layout">
      
      <!-- Left Column: Products Grouped by Shop -->
      <div class="cart-items-column">
        <?php foreach ($grouped_shops as $p_id => $shop_data): ?>
          <div class="shop-group-card">
            
            <div class="shop-header-row">
              <div class="shop-name-tag">
                <span style="font-size:1.15rem;">🏪</span>
                <span><?= htmlspecialchars($shop_data['shop_name']) ?></span>
                <span class="shop-badge-verified">✓ ভেরিফাইড</span>
              </div>
              <span style="font-size: 0.8rem; color: var(--muted); font-weight: 600;">
                <?= count($shop_data['items']) ?> টি আইটেম
              </span>
            </div>

            <!-- Items in this shop -->
            <?php foreach ($shop_data['items'] as $item): ?>
              <div class="cart-item-row" id="cart-row-<?= $item['product_id'] ?>">
                <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['title']) ?>" class="cart-item-thumb" onerror="this.onerror=null; this.src='/assets/images/placeholder.jpg';"/>
                
                <div class="cart-item-info">
                  <a href="/product_detail.php?id=<?= $item['product_id'] ?>" class="cart-item-title">
                    <?= htmlspecialchars($item['title']) ?>
                  </a>
                  <div class="cart-item-price-unit">
                    ৳<?= number_format($item['price_bdt'], 0) ?>
                    <span>(🪙 <?= number_format($item['coins']) ?> pts)</span>
                  </div>
                </div>

                <div class="cart-item-actions">
                  <div class="qty-stepper">
                    <button type="button" class="qty-btn" onclick="updateItemQty(<?= $item['product_id'] ?>, <?= $item['quantity'] - 1 ?>)">−</button>
                    <input type="text" class="qty-display" value="<?= $item['quantity'] ?>" readonly/>
                    <button type="button" class="qty-btn" onclick="updateItemQty(<?= $item['product_id'] ?>, <?= $item['quantity'] + 1 ?>)">+</button>
                  </div>

                  <div class="cart-item-subtotal">
                    ৳<?= number_format($item['price_bdt'] * $item['quantity'], 0) ?>
                  </div>

                  <a href="/cart.php?action=remove&product_id=<?= $item['product_id'] ?>" class="btn-remove-item" onclick="return confirm('পণ্যটি কার্ট থেকে মুছে ফেলতে চান?');">
                    🗑️ সরান
                  </a>
                </div>
              </div>
            <?php endforeach; ?>

            <!-- Shop Subtotal Bar -->
            <div class="shop-subtotal-bar">
              <span style="color:var(--muted);">এই শপের সাব-টোটাল:</span>
              <strong>৳<?= number_format($shop_data['subtotal_bdt'], 0) ?></strong>
            </div>

          </div>
        <?php endforeach; ?>
      </div>

      <!-- Right Column: Order Summary Card -->
      <aside>
        <div class="cart-summary-card">
          <h3 class="summary-title">
            <span>অর্ডারের সারাংশ</span>
            <span style="font-size:0.85rem; font-weight:600; color:var(--gold);">SafePay</span>
          </h3>

          <div class="summary-line">
            <span>মোট পণ্যের সংখ্যা:</span>
            <strong><?= $total_cart_count ?> টি</strong>
          </div>

          <div class="summary-line">
            <span>পণ্যের মূল্য (Subtotal):</span>
            <strong>৳<?= number_format($cart_subtotal_bdt, 0) ?></strong>
          </div>

          <!-- Interactive Delivery Zone Selector -->
          <div class="zone-selector-box">
            <span class="zone-selector-label">📍 ডেলিভারি এলাকা নির্বাচন করুন:</span>
            
            <label class="zone-option" onclick="changeDeliveryZone('inside_dhaka')">
              <input type="radio" name="zone_radio" value="inside_dhaka" <?= $selected_zone === 'inside_dhaka' ? 'checked' : '' ?> onchange="changeDeliveryZone('inside_dhaka')"/>
              <div style="flex:1;">
                <strong>ঢাকা সিটির ভিতরে</strong>
                <div style="font-size:0.72rem; color:var(--muted);">২৪-৪৮ ঘণ্টা • ৳<?= number_format($delivery_inside, 0) ?> <?= $physical_shops_count > 1 ? "(প্রতি শপ)" : "" ?></div>
              </div>
            </label>

            <label class="zone-option" onclick="changeDeliveryZone('outside_dhaka')">
              <input type="radio" name="zone_radio" value="outside_dhaka" <?= $selected_zone === 'outside_dhaka' ? 'checked' : '' ?> onchange="changeDeliveryZone('outside_dhaka')"/>
              <div style="flex:1;">
                <strong>ঢাকা সিটির বাইরে</strong>
                <div style="font-size:0.72rem; color:var(--muted);">২-৩ দিন • ৳<?= number_format($delivery_outside, 0) ?> <?= $physical_shops_count > 1 ? "(প্রতি শপ)" : "" ?></div>
              </div>
            </label>

            <label class="zone-option" onclick="changeDeliveryZone('soft')">
              <input type="radio" name="zone_radio" value="soft" <?= $selected_zone === 'soft' ? 'checked' : '' ?> onchange="changeDeliveryZone('soft')"/>
              <div style="flex:1;">
                <strong>ডিজিটাল ডেলিভারি</strong>
                <div style="font-size:0.72rem; color:var(--muted);">অনলাইন ইনস্ট্যান্ট • ৳০ চার্জ</div>
              </div>
            </label>
          </div>

          <div class="summary-line">
            <span>ডেলিভারি চার্জ:</span>
            <strong>৳<?= number_format($total_delivery_charge, 0) ?></strong>
          </div>

          <!-- Grand Total -->
          <div class="grand-total-box">
            <div>
              <div class="grand-total-label">সর্বমোট প্রদেয়:</div>
              <div style="font-size:0.72rem; color:var(--muted);">ভ্যাট অন্তর্ভুক্ত</div>
            </div>
            <div>
              <div class="grand-total-val-bdt">৳<?= number_format($grand_total_bdt, 0) ?></div>
              <div class="grand-total-val-coins">🪙 <?= number_format($cart_subtotal_coins) ?> Fast Points</div>
            </div>
          </div>

          <!-- Primary CTA Button -->
          <a href="/checkout.php?cart=1" class="btn-checkout-primary">
            <span>🛒 অর্ডার সম্পন্ন করুন</span>
            <span>➔</span>
          </a>

          <!-- Clear Cart Button -->
          <a href="/cart.php?action=clear" class="btn-clear-cart" onclick="return confirm('আপনি কি নিশ্চিত যে সম্পূর্ণ কার্ট খালি করতে চান?');" style="display:block; text-align:center; text-decoration:none; box-sizing:border-box;">
            ✕ কার্ট খালি করুন
          </a>

          <!-- SafePay Reassurance -->
          <div style="margin-top: 1.2rem; background: rgba(16, 185, 129, 0.08); border: 1px solid rgba(16, 185, 129, 0.2); border-radius: 10px; padding: 0.75rem; font-size: 0.76rem; color: #6EE7B7; line-height: 1.4;">
            🛡️ <strong>SafePay গ্যারান্টি:</strong> পণ্য হাতে পেয়ে চেক করার পর কনফার্ম করলেই কেবল সেলার পেমেন্ট পাবে। আপনার টাকা ১০০% সুরক্ষিত।
          </div>

        </div>
      </aside>

    </div>

    <!-- Mobile Sticky Checkout Dock -->
    <div class="mobile-cart-dock">
      <div>
        <div style="font-size:0.72rem; color:var(--muted);">সর্বমোট (ডেলিভারি সহ)</div>
        <div style="font-size:1.25rem; font-weight:900; color:var(--gold);">৳<?= number_format($grand_total_bdt, 0) ?></div>
      </div>
      <a href="/checkout.php?cart=1" class="btn-checkout-primary" style="width:auto; padding: 0.75rem 1.6rem; font-size:0.95rem;">
        🛒 চেকআউট ➔
      </a>
    </div>

  <?php endif; ?>

</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
function updateItemQty(productId, newQty) {
  if (newQty < 1) {
    if (!confirm('পণ্যটি কার্ট থেকে মুছে ফেলতে চান?')) return;
  }
  window.location.href = '/cart.php?action=update&product_id=' + productId + '&quantity=' + newQty;
}

function changeDeliveryZone(zone) {
  const form = document.createElement('form');
  form.method = 'POST';
  form.action = '/cart.php';
  
  const input = document.createElement('input');
  input.type = 'hidden';
  input.name = 'delivery_zone';
  input.value = zone;
  
  form.appendChild(input);
  document.body.appendChild(form);
  form.submit();
}
</script>

</body>
</html>
