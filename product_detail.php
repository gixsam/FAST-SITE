<?php
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: index.php');
    exit;
}

// Fetch product + partner info
$stmt = $pdo->prepare("SELECT p.*, 
                       COALESCE(pt.business_name, 'Fast Site Official') AS shop_name, 
                       COALESCE(pt.status, 'approved') AS shop_status, 
                       COALESCE(pt.rating, 5.0) AS shop_rating, 
                       COALESCE(pt.is_official, 1) AS is_official, 
                       COALESCE(pt.total_orders, 500) AS total_orders, 
                       COALESCE(pt.id, 0) AS partner_id, 
                       COALESCE(pt.tags, '') AS partner_tags, 
                       COALESCE(pt.seller_level, 3) AS seller_level
                       FROM partner_products p 
                       LEFT JOIN partners pt ON p.partner_id = pt.id
                       WHERE p.id = :id AND p.is_published = 1 AND (pt.status = 'approved' OR p.partner_id = 0) LIMIT 1");
$stmt->execute([':id' => $id]);
$p = $stmt->fetch();

$all_tags_raw = $pdo->query("SELECT * FROM shop_tags")->fetchAll();
$all_tags = [];
foreach($all_tags_raw as $t) {
    $all_tags[$t['id']] = $t;
}

if (!$p) {
    die("<div style='background:#0d0d14; color:#ff5252; text-align:center; padding:3rem; font-family:sans-serif;'><h2>Product not found or has been unpublished.</h2><br><a href='marketplace_board.php' style='color:#fcb900;'>Back to Marketplace</a></div>");
}

// Instant affiliate redirection if requested
$isAffiliateProduct = ($p['listing_type'] === 'affiliate' || !empty($p['affiliate_url']) || !empty($p['affiliate_link']));
$affiliateTargetUrl = !empty($p['affiliate_url']) ? $p['affiliate_url'] : ($p['affiliate_link'] ?? '');

if (isset($_GET['redirect']) && $_GET['redirect'] == '1' && $isAffiliateProduct && !empty($affiliateTargetUrl)) {
    header("Location: " . $affiliateTargetUrl);
    exit;
}
$imagesStmt = $pdo->prepare("SELECT image_url, is_thumbnail FROM partner_product_images WHERE product_id = :id ORDER BY is_thumbnail DESC, id ASC");
$imagesStmt->execute([':id' => $id]);
$images = $imagesStmt->fetchAll();

if (empty($images)) {
    $artwork = resolveProductArtwork('', '', $p['shop_name'] ?? '', $p['category'] ?? '', $p['title'] ?? '', $p['listing_type'] ?? 'product');
    $images = [['image_url' => $artwork, 'is_thumbnail' => 1]];
} else {
    foreach ($images as &$img) {
        $img['image_url'] = resolveProductArtwork($img['image_url'], '', $p['shop_name'] ?? '', $p['category'] ?? '', $p['title'] ?? '', $p['listing_type'] ?? 'product');
    }
    unset($img);
}

// Fetch reviews for this partner
$reviews = [];
try {
    $revStmt = $pdo->prepare("SELECT r.*, u.name AS user_name 
                              FROM partner_ratings r 
                              JOIN users u ON r.customer_id = u.id 
                              WHERE r.partner_id = :pid 
                              ORDER BY r.created_at DESC LIMIT 10");
    $revStmt->execute([':pid' => $p['partner_id']]);
    $reviews = $revStmt->fetchAll();
} catch (Exception $e) {}

$isAdminLoggedIn = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$isUserLoggedIn = isset($_SESSION['user_id']);

$user_coins = 0.0;
$user_profile = null;
if ($isUserLoggedIn) {
    $userId = $_SESSION['user_id'];
    try {
        $u_stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
        $u_stmt->execute([':id' => $userId]);
        $user_profile = $u_stmt->fetch();

        $walletQuery = $pdo->prepare("SELECT balance FROM coin_wallets WHERE user_id = :uid LIMIT 1");
        $walletQuery->execute([':uid' => $userId]);
        $res = $walletQuery->fetchColumn();
        if ($res !== false) {
            $user_coins = floatval($res);
        }
    } catch (Exception $e) {}
}

$coin_name = getPartnerSetting('coin_name', 'Fast Points');
$exchange_rate = floatval(getPartnerSetting('exchange_rate', '1'));

$product_price = floatval($p['price']);
$product_price_bdt = $product_price * $exchange_rate;
$hasEnoughBalance = $user_coins >= $product_price;

// Gateway controls
$card_enabled = getPartnerSetting('payment_card_enabled', '1') === '1';
$bkash_enabled = getPartnerSetting('payment_bkash_enabled', '1') === '1';
$nagad_enabled = getPartnerSetting('payment_nagad_enabled', '1') === '1';
$cod_enabled = getPartnerSetting('payment_cod_enabled', '1') === '1';
$delivery_inside = floatval(getPartnerSetting('delivery_inside_dhaka', '60'));
$delivery_outside = floatval(getPartnerSetting('delivery_outside_dhaka', '120'));

// WhatsApp 1-Click Order Link & Message Resolution
$official_wa = getPartnerSetting('marketplace_whatsapp', '') ?: getPartnerSetting('whatsapp_number', '8801337320544');
$shop_wa = !empty($p['whatsapp']) ? $p['whatsapp'] : $official_wa;
$clean_wa = preg_replace('/[^0-9]/', '', $shop_wa);
if (strlen($clean_wa) === 11 && strpos($clean_wa, '01') === 0) {
    $clean_wa = '88' . $clean_wa;
}
if (empty($clean_wa)) {
    $clean_wa = '8801337320544';
}

$current_product_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/product_detail.php?id=' . $p['id'];

$wa_msg = "আসসালামু আলাইকুম Fast Site, আমি এই পণ্যটি অর্ডার করতে চাই:\n\n"
        . "📌 পণ্য: " . ($p['title'] ?? 'Product') . "\n"
        . "💰 মূল্য: ৳" . number_format($product_price_bdt, 0) . " (" . number_format(ceil($product_price)) . " Points)\n"
        . "🔗 লিংক: " . $current_product_url . "\n\n"
        . "আমার ডেলিভারি তথ্য:\n"
        . "নাম: " . (!empty($user_profile['name']) ? $user_profile['name'] : '') . "\n"
        . "মোবাইল নম্বর: " . (!empty($user_profile['phone']) ? $user_profile['phone'] : '') . "\n"
        . "ঠিকানা ও জেলা: ";

$wa_order_link = "https://wa.me/" . $clean_wa . "?text=" . rawurlencode($wa_msg);
$primary_thumb = !empty($images[0]['image_url']) ? $images[0]['image_url'] : '/assets/img/placeholder.png';
?>
<!DOCTYPE html>
<html lang="en">
<?php
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$ogImage = !empty($images) ? $images[0]['image_url'] : 'assets/img/placeholder.png';
if (strpos($ogImage, 'http://') === 0 || strpos($ogImage, 'https://') === 0 || strpos($ogImage, '//') === 0) {
    $ogImageUrl = $ogImage;
} else {
    $ogImageUrl = $protocol . '://' . $host . '/' . ltrim($ogImage, '/');
}
$ogUrl = $protocol . '://' . $host . $_SERVER['REQUEST_URI'];

$ogPriceText = '৳ ' . number_format($product_price_bdt, 0) . ' (' . number_format(ceil($product_price)) . ' Fast Points)';
$ogFullTitle = 'Buy ' . htmlspecialchars($p['title']) . ' — ' . $ogPriceText . ' | Fast Site';
$ogDesc = 'Price: ' . $ogPriceText . ' | Shop: ' . htmlspecialchars($p['shop_name']) . '. 100% SafePay Buyer Guarantee (নিরাপদ গ্যারান্টি) on Fast Site. ' . strip_tags($p['description']);
if (mb_strlen($ogDesc) > 200) $ogDesc = mb_substr($ogDesc, 0, 197) . '...';
?>
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= $ogFullTitle ?></title>
  <meta property="og:site_name" content="Fast Site Marketplace" />
  <meta property="og:title" content="<?= $ogFullTitle ?>" />
  <meta property="og:description" content="<?= htmlspecialchars($ogDesc) ?>" />
  <meta property="og:image" content="<?= htmlspecialchars($ogImageUrl) ?>" />
  <meta property="og:image:width" content="1200" />
  <meta property="og:image:height" content="630" />
  <meta property="og:url" content="<?= htmlspecialchars($ogUrl) ?>" />
  <meta property="og:type" content="product" />
  <meta name="twitter:card" content="summary_large_image" />
  <meta name="twitter:title" content="<?= $ogFullTitle ?>" />
  <meta name="twitter:description" content="<?= htmlspecialchars($ogDesc) ?>" />
  <meta name="twitter:image" content="<?= htmlspecialchars($ogImageUrl) ?>" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Oswald:wght@600;700&display=swap" rel="stylesheet"/>
  <style>
    :root {
      --gold: #fcb900;
      --gold-glow: rgba(252, 185, 0, 0.2);
      --blue: #3b82f6;
      --blue-glow: rgba(59, 130, 246, 0.2);
      --dark: #0a0a0f;
      --dark-card: rgba(18, 18, 26, 0.65);
      --border: rgba(255, 255, 255, 0.06);
      --text: #f8f8f8;
      --muted: #9ca3af;
      --green: #10b981;
      --red: #ef4444;
      --brand: #fcb900;
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    
    body {
      font-family: 'Inter', sans-serif;
      background: var(--dark);
      color: var(--text);
      min-height: 100vh;
      line-height: 1.6;
    }

    /* ================= HEADER BAR ================= */
    .top-header {
      background: rgba(13, 13, 20, 0.9);
      backdrop-filter: blur(18px);
      border-bottom: 1px solid var(--border);
      padding: 0.8rem 1.5rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      position: sticky;
      top: 0;
      z-index: 1000;
    }

    .brand {
      font-family: 'Oswald', sans-serif;
      font-size: 1.4rem;
      font-weight: 700;
      color: var(--gold);
      text-decoration: none;
      letter-spacing: 1px;
    }

    .nav-buttons {
      display: flex;
      gap: 0.8rem;
      align-items: center;
    }

    .btn-nav {
      color: var(--muted);
      text-decoration: none;
      font-size: 0.82rem;
      font-weight: 600;
      padding: 0.45rem 0.9rem;
      border-radius: 8px;
      transition: all 0.2s;
      border: 1px solid transparent;
    }

    .btn-nav:hover {
      color: var(--gold);
      background: rgba(252, 185, 0, 0.08);
      border-color: rgba(252, 185, 0, 0.15);
    }

    .btn-wallet {
      background: rgba(33, 150, 243, 0.1);
      border: 1px solid rgba(33, 150, 243, 0.25);
      color: var(--blue);
    }
    
    .btn-wallet:hover {
      background: var(--blue);
      color: #000;
    }

    /* ================= DETAIL CONTAINER ================= */
    .wrap {
      max-width: 1100px;
      margin: 0 auto;
      padding: 2rem 1.5rem 4rem;
    }

    .back-link {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      color: var(--muted);
      text-decoration: none;
      font-size: 0.85rem;
      font-weight: 600;
      margin-bottom: 1.5rem;
      transition: color 0.2s;
    }

    .back-link:hover {
      color: var(--gold);
    }

    .product-layout {
      display: grid;
      grid-template-columns: 1.2fr 1fr;
      gap: 2.5rem;
      margin-bottom: 3rem;
    }

    @media (max-width: 900px) {
      .product-layout {
        grid-template-columns: 1fr;
        gap: 1.8rem;
      }
    }

    /* Left Side: Images */
    .media-section {
      display: flex;
      flex-direction: column;
      gap: 1rem;
    }

    .main-image-panel {
      width: 100%;
      height: 380px;
      background: #101018;
      border: 1px solid rgba(255, 255, 255, 0.06);
      border-radius: 16px;
      overflow: hidden;
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
    }

    .main-image-panel img {
      width: 100%;
      height: 100%;
      object-fit: contain;
    }

    /* ─── Audio Player Card (Spotify / Studio Glassmorphism) ─── */
    .audio-player-card {
      background: linear-gradient(135deg, rgba(20, 24, 40, 0.95) 0%, rgba(10, 12, 22, 0.98) 100%);
      border: 1px solid rgba(252, 185, 0, 0.35);
      border-radius: 18px;
      padding: 1.25rem;
      display: flex;
      gap: 1.2rem;
      align-items: center;
      margin-bottom: 1.2rem;
      position: relative;
      overflow: hidden;
      box-shadow: 0 8px 30px rgba(0, 0, 0, 0.45), 0 0 20px rgba(252, 185, 0, 0.1);
    }
    .audio-player-card::before {
      content: '';
      position: absolute;
      inset: 0;
      background: radial-gradient(circle at 20% 50%, rgba(252, 185, 0, 0.08) 0%, rgba(0, 230, 118, 0.04) 50%, transparent 80%);
      pointer-events: none;
    }
    .audio-player-artwork {
      position: relative;
      flex-shrink: 0;
      width: 96px;
      height: 96px;
    }
    .audio-player-artwork img {
      width: 96px;
      height: 96px;
      border-radius: 14px;
      object-fit: cover;
      border: 2px solid rgba(252, 185, 0, 0.4);
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5);
    }
    .audio-vinyl-spin {
      position: absolute;
      bottom: -6px;
      right: -6px;
      width: 36px;
      height: 36px;
      background: #08080c;
      border: 2px solid var(--gold);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.1rem;
      box-shadow: 0 2px 8px rgba(0,0,0,0.8);
      transition: transform 0.3s;
    }
    .audio-vinyl-spin.spinning {
      animation: vinylSpin 2.5s linear infinite;
    }
    @keyframes vinylSpin { 100% { transform: rotate(360deg); } }

    /* Animated Sound Waves */
    .audio-wave-bars {
      display: inline-flex;
      align-items: flex-end;
      gap: 2px;
      height: 14px;
      vertical-align: middle;
      margin-left: 6px;
    }
    .audio-wave-bars span {
      width: 3px;
      height: 4px;
      background: var(--gold);
      border-radius: 2px;
      transition: height 0.2s;
    }
    .audio-vinyl-spin.spinning + .audio-title .audio-wave-bars span:nth-child(1),
    .is-playing .audio-wave-bars span:nth-child(1) { animation: waveBar 0.8s ease-in-out infinite 0.1s; }
    .is-playing .audio-wave-bars span:nth-child(2) { animation: waveBar 0.8s ease-in-out infinite 0.3s; }
    .is-playing .audio-wave-bars span:nth-child(3) { animation: waveBar 0.8s ease-in-out infinite 0.5s; }
    .is-playing .audio-wave-bars span:nth-child(4) { animation: waveBar 0.8s ease-in-out infinite 0.2s; }
    @keyframes waveBar {
      0%, 100% { height: 4px; }
      50% { height: 14px; background: #00e676; }
    }

    .audio-player-controls {
      flex: 1;
      min-width: 0;
    }
    .audio-title {
      font-weight: 800;
      font-size: 1.05rem;
      color: #fff;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      margin-bottom: 2px;
      display: flex;
      align-items: center;
    }
    .audio-artist {
      font-size: 0.75rem;
      color: var(--gold);
      font-weight: 700;
      margin-bottom: 0.6rem;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      display: flex;
      align-items: center;
      gap: 4px;
    }
    .audio-progress-wrap {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      margin-bottom: 0.7rem;
    }
    .audio-time {
      font-size: 0.7rem;
      color: #94a3b8;
      font-family: monospace;
      white-space: nowrap;
    }
    .audio-progress-bar {
      flex: 1;
      height: 6px;
      background: rgba(255,255,255,0.12);
      border-radius: 10px;
      cursor: pointer;
      position: relative;
      overflow: hidden;
      touch-action: none;
    }
    .audio-progress-fill {
      height: 100%;
      width: 0%;
      background: linear-gradient(90deg, #fcb900, #00e676);
      border-radius: 10px;
      transition: width 0.15s linear;
    }
    .audio-btns {
      display: flex;
      align-items: center;
      gap: 0.6rem;
      flex-wrap: wrap;
    }
    .audio-play-btn {
      width: 42px; height: 42px;
      border-radius: 50%;
      background: linear-gradient(135deg, #fcb900 0%, #ff9100 100%);
      border: none;
      color: #000;
      font-size: 1.15rem;
      cursor: pointer;
      display: flex; align-items: center; justify-content: center;
      font-weight: 900;
      box-shadow: 0 4px 15px rgba(252, 185, 0, 0.4);
      transition: all 0.2s;
      flex-shrink: 0;
    }
    .audio-play-btn:hover { transform: scale(1.08); box-shadow: 0 6px 20px rgba(252, 185, 0, 0.6); }
    .audio-ctrl-btn {
      background: rgba(255,255,255,0.06);
      border: 1px solid rgba(255,255,255,0.12);
      color: #fff;
      border-radius: 8px;
      padding: 0.4rem 0.65rem;
      font-size: 0.8rem;
      cursor: pointer;
      transition: all 0.2s;
    }
    .audio-ctrl-btn:hover { background: rgba(252, 185, 0, 0.15); border-color: var(--gold); color: var(--gold); }
    .audio-volume {
      width: 70px;
      accent-color: var(--gold);
      cursor: pointer;
      margin-left: auto;
    }
    .audio-preview-notice {
      font-size: 0.7rem;
      color: #00e676;
      margin-top: 0.5rem;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 4px;
    }
    @media (max-width: 540px) {
      .audio-player-card { flex-direction: column; align-items: stretch; text-align: center; }
      .audio-player-artwork { margin: 0 auto; width: 100px; height: 100px; }
      .audio-player-artwork img { width: 100px; height: 100px; }
      .audio-title { justify-content: center; }
      .audio-artist { justify-content: center; }
      .audio-btns { justify-content: center; }
      .audio-volume { margin-left: 0; width: 60px; }
    }

    .thumbnails-row {
      display: flex;
      gap: 0.6rem;
      overflow-x: auto;
      padding-bottom: 0.4rem;
    }

    .thumb-btn {
      width: 70px;
      height: 70px;
      border: 2px solid transparent;
      border-radius: 10px;
      overflow: hidden;
      cursor: pointer;
      background: #101018;
      transition: all 0.2s;
      flex-shrink: 0;
      padding: 0;
    }

    .thumb-btn img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .thumb-btn:hover, .thumb-btn.active {
      border-color: var(--gold);
      box-shadow: 0 0 10px var(--gold-glow);
    }

    /* Right Side: Product Description & Order Panel */
    .info-section {
      display: flex;
      flex-direction: column;
      gap: 1.5rem;
    }

    .shop-meta-header {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      font-size: 0.72rem;
      font-weight: 700;
      color: var(--muted);
      text-transform: uppercase;
      letter-spacing: 0.08em;
    }

    .shop-meta-header a {
      color: var(--gold);
      text-decoration: none;
    }

    .shop-meta-header a:hover {
      text-decoration: underline;
    }

    .product-title {
      font-size: 1.8rem;
      font-weight: 800;
      color: #fff;
      line-height: 1.3;
    }

    .price-tag-card {
      background: rgba(255, 255, 255, 0.02);
      border: 1px solid rgba(255, 255, 255, 0.05);
      border-radius: 14px;
      padding: 1.2rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .price-large-coin {
      font-size: 1.6rem;
      font-weight: 900;
      color: var(--green);
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }

    .price-large-bdt {
      font-size: 0.85rem;
      color: var(--muted);
    }

    .desc-box {
      font-size: 0.92rem;
      color: var(--text);
      line-height: 1.7;
    }

    /* Order / Wallet Status Panel */
    .action-panel {
      background: var(--dark-card);
      border: 1px solid rgba(255, 255, 255, 0.06);
      border-radius: 16px;
      padding: 1.5rem;
      box-shadow: 0 8px 25px rgba(0,0,0,0.4);
    }

    .wallet-status-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 0.8rem;
      color: var(--muted);
      margin-bottom: 1.2rem;
      border-bottom: 1px dashed rgba(255, 255, 255, 0.06);
      padding-bottom: 0.8rem;
    }

    .wallet-status-row strong {
      color: #fff;
    }

    .btn-add-cart-detail {
      flex: 1;
      min-width: 140px;
      background: rgba(18, 22, 43, 0.9);
      border: 1.5px solid rgba(245, 158, 11, 0.4);
      color: #fff;
      font-weight: 800;
      border-radius: 50px;
      padding: 0.85rem;
      font-size: 0.95rem;
      cursor: pointer;
      min-height: 48px;
      transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
    }
    .btn-add-cart-detail:hover {
      background: rgba(245, 158, 11, 0.15);
      border-color: var(--gold);
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(245, 158, 11, 0.3);
    }
    .btn-add-cart-detail:active {
      transform: scale(0.96);
    }

    .dock-btn-cart {
      background: rgba(18, 22, 43, 0.9);
      border: 1px solid rgba(245, 158, 11, 0.4);
      color: #fff;
      border-radius: 50px;
      padding: 0 10px;
      height: 42px;
      font-weight: 700;
      font-size: 0.82rem;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 4px;
      cursor: pointer;
      transition: all 0.2s;
      flex-shrink: 0;
    }
    .dock-btn-cart:active {
      transform: scale(0.95);
    }

    .btn-buy-now {
      background: linear-gradient(135deg, var(--green), #00b0ff);
      color: #000;
      font-weight: 800;
      border: none;
      width: 100%;
      border-radius: 50px;
      padding: 0.85rem;
      font-size: 0.95rem;
      cursor: pointer;
      min-height: 48px;
      transition: all 0.2s;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      box-shadow: 0 4px 15px rgba(0, 230, 118, 0.15);
    }

    .btn-buy-now:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(0, 230, 118, 0.3);
      filter: brightness(1.1);
    }

    .btn-secondary-login {
      display: flex;
      align-items: center;
      justify-content: center;
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.1);
      color: #fff;
      text-decoration: none;
      font-weight: 700;
      width: 100%;
      border-radius: 50px;
      padding: 0.85rem;
      font-size: 0.9rem;
      text-align: center;
      min-height: 48px;
      transition: all 0.2s;
    }

    .btn-secondary-login:hover {
      background: rgba(255, 255, 255, 0.1);
    }

    .deposit-alert-box {
      border: 1px solid rgba(255, 82, 82, 0.25);
      background: rgba(255, 82, 82, 0.06);
      color: #ff6b6b;
      padding: 1rem;
      border-radius: 12px;
      font-size: 0.83rem;
      margin-bottom: 1.2rem;
      line-height: 1.5;
    }

    .deposit-alert-box a {
      color: #fff;
      font-weight: 700;
      text-decoration: underline;
    }

    /* Escrow explanation badge */
    .escrow-shield-badge {
      display: flex;
      align-items: flex-start;
      gap: 10px;
      background: rgba(33, 150, 243, 0.06);
      border: 1px solid rgba(33, 150, 243, 0.2);
      padding: 1rem;
      border-radius: 12px;
      margin-top: 1rem;
    }

    .escrow-shield-badge span {
      font-size: 1.5rem;
    }

    .escrow-shield-badge p {
      font-size: 0.78rem;
      color: var(--muted);
      line-height: 1.4;
    }

    /* WhatsApp 1-Click Order Button */
    .btn-whatsapp-order {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      background: linear-gradient(135deg, #25D366, #128C7E);
      color: #fff;
      font-weight: 800;
      text-decoration: none;
      width: 100%;
      border-radius: 50px;
      padding: 0.85rem 1rem;
      font-size: 0.95rem;
      margin-top: 0.75rem;
      box-shadow: 0 4px 18px rgba(37, 211, 102, 0.3);
      transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
      min-height: 48px;
      box-sizing: border-box;
    }
    .btn-whatsapp-order:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 24px rgba(37, 211, 102, 0.45);
      filter: brightness(1.08);
      color: #fff;
    }
    .btn-whatsapp-order:active {
      transform: scale(0.97);
    }

    /* 4 Transparent Delivery & Trust Badges */
    .delivery-trust-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 0.6rem;
      margin-top: 1.2rem;
    }
    @media (max-width: 480px) {
      .delivery-trust-grid {
        grid-template-columns: 1fr;
      }
    }
    .trust-badge-item {
      display: flex;
      align-items: center;
      gap: 8px;
      background: rgba(255, 255, 255, 0.03);
      border: 1px solid rgba(255, 255, 255, 0.07);
      border-radius: 10px;
      padding: 0.65rem 0.8rem;
      transition: border-color 0.2s;
    }
    .trust-badge-item:hover {
      border-color: rgba(252, 185, 0, 0.25);
      background: rgba(252, 185, 0, 0.02);
    }
    .trust-badge-icon {
      font-size: 1.25rem;
      flex-shrink: 0;
    }
    .trust-badge-text {
      font-size: 0.76rem;
      color: #cbd5e1;
      line-height: 1.35;
    }
    .trust-badge-text strong {
      color: #fff;
      font-weight: 700;
    }

    /* Mobile Sticky Action Dock (< 768px) */
    .mobile-sticky-action-dock {
      display: none;
    }

    @media (max-width: 768px) {
      body {
        padding-bottom: calc(75px + env(safe-area-inset-bottom, 0px)) !important;
      }
      
      .mobile-sticky-action-dock {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        z-index: 1050;
        background: rgba(10, 13, 26, 0.94);
        backdrop-filter: blur(18px);
        -webkit-backdrop-filter: blur(18px);
        border-top: 1px solid rgba(252, 185, 0, 0.25);
        box-shadow: 0 -8px 30px rgba(0, 0, 0, 0.75);
        padding: 0.6rem 0.9rem calc(env(safe-area-inset-bottom, 0px) + 0.55rem) 0.9rem;
        box-sizing: border-box;
      }

      .dock-product-preview {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        min-width: 0;
        flex: 1;
      }

      .dock-thumb {
        width: 44px;
        height: 44px;
        border-radius: 8px;
        object-fit: cover;
        border: 1px solid rgba(255, 255, 255, 0.15);
        background: #12121a;
        flex-shrink: 0;
      }

      .dock-price-col {
        display: flex;
        flex-direction: column;
        justify-content: center;
        min-width: 0;
        overflow: hidden;
      }

      .dock-price-bdt {
        font-size: 1.05rem;
        font-weight: 900;
        color: #fff;
        white-space: nowrap;
        line-height: 1.2;
        letter-spacing: -0.02em;
      }

      .dock-price-coin {
        font-size: 0.72rem;
        color: var(--gold);
        font-weight: 700;
        white-space: nowrap;
      }

      .dock-buttons-group {
        display: flex;
        align-items: center;
        gap: 0.45rem;
        flex-shrink: 0;
      }

      .dock-btn-whatsapp {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
        background: linear-gradient(135deg, #25D366, #128C7E);
        color: #fff !important;
        text-decoration: none;
        font-weight: 700;
        font-size: 0.8rem;
        padding: 0.55rem 0.75rem;
        border-radius: 50px;
        min-height: 42px;
        box-shadow: 0 4px 14px rgba(37, 211, 102, 0.35);
        transition: transform 0.15s ease;
        white-space: nowrap;
      }
      .dock-btn-whatsapp:active {
        transform: scale(0.95);
      }

      .dock-btn-buy {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
        background: linear-gradient(135deg, var(--gold), #ff9100);
        color: #000 !important;
        border: none;
        text-decoration: none;
        font-weight: 800;
        font-size: 0.85rem;
        padding: 0.55rem 0.95rem;
        border-radius: 50px;
        min-height: 42px;
        cursor: pointer;
        box-shadow: 0 4px 16px rgba(252, 185, 0, 0.35);
        transition: transform 0.15s ease;
        white-space: nowrap;
      }
      .dock-btn-buy:active {
        transform: scale(0.95);
      }
    }

    @media (max-width: 375px) {
      .dock-btn-text {
        display: none;
      }
      .dock-btn-whatsapp, .dock-btn-buy {
        padding: 0.55rem 0.75rem;
      }
    }

    /* ================= REVIEWS SECTION ================= */
    .reviews-section {
      margin-top: 4rem;
      border-top: 1px solid rgba(255, 255, 255, 0.06);
      padding-top: 2.5rem;
    }

    .reviews-section h2 {
      font-size: 1.3rem;
      font-weight: 700;
      color: var(--gold);
      margin-bottom: 1.5rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .review-card {
      background: rgba(255, 255, 255, 0.01);
      border: 1px solid rgba(255, 255, 255, 0.04);
      border-radius: 12px;
      padding: 1.2rem;
      margin-bottom: 1rem;
    }

    .review-meta {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 0.6rem;
    }

    .review-user {
      font-weight: 700;
      font-size: 0.85rem;
      color: #fff;
    }

    .review-date {
      font-size: 0.72rem;
      color: var(--muted);
    }

    .review-stars {
      color: var(--gold);
      font-size: 0.8rem;
      margin-bottom: 0.6rem;
    }

    .review-text {
      font-size: 0.88rem;
      color: var(--text);
      line-height: 1.5;
    }

    .empty-reviews {
      text-align: center;
      color: var(--muted);
      font-size: 0.85rem;
      padding: 2.5rem 0;
    }

    /* Mobile details responsive */
    @media (max-width: 600px) {
      .product-title { font-size: 1.4rem; }
      .main-image-panel { height: 260px; }
      .price-tag-card { padding: 0.8rem; }
      .price-large-coin { font-size: 1.3rem; }
      .wrap { padding: 1rem 1rem 3rem; }
    }
  </style>

  <!-- GOOGLE RICH SNIPPET STRUCTURED DATA (JSON-LD) -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org/",
    "@type": "Product",
    "name": <?= json_encode($p['title']) ?>,
    "image": [<?= json_encode($ogImageUrl) ?>],
    "description": <?= json_encode($ogDesc) ?>,
    "sku": <?= json_encode("FAST-" . $p['id']) ?>,
    "brand": {
      "@type": "Brand",
      "name": <?= json_encode($p['shop_name']) ?>
    },
    "aggregateRating": {
      "@type": "AggregateRating",
      "ratingValue": <?= json_encode(number_format(floatval($p['shop_rating'] ?? 5.0), 1)) ?>,
      "reviewCount": <?= json_encode(intval($p['total_orders'] ?? 10)) ?>
    },
    "offers": {
      "@type": "Offer",
      "url": <?= json_encode($ogUrl) ?>,
      "priceCurrency": "BDT",
      "price": <?= json_encode(number_format($product_price_bdt, 2, '.', '')) ?>,
      "priceValidUntil": "2030-12-31",
      "itemCondition": "https://schema.org/NewCondition",
      "availability": "https://schema.org/InStock",
      "seller": {
        "@type": "Organization",
        "name": <?= json_encode($p['shop_name']) ?>
      }
    }
  }
  </script>
</head>
<body>

<?php if ($isAdminLoggedIn): ?>
  <!-- Admin View Bar -->
  <div style="background: rgba(252, 185, 0, 0.15); border-bottom: 1px solid rgba(252, 185, 0, 0.3); padding: 0.5rem 1rem; display: flex; justify-content: space-between; align-items: center; font-size: 0.82rem; z-index: 1001; position: relative;">
    <span style="color: #fcb900; display: inline-flex; align-items: center; gap: 6px;">
      <span style="display: inline-block; width: 8px; height: 8px; background: #00e676; border-radius: 50%; box-shadow: 0 0 8px #00e676; animation: pulse 1.5s infinite;"></span>
      Logged in as Administrator (Staff)
    </span>
    <a href="/admin/dashboard.php" style="background: #fcb900; color: #000; font-weight: 700; text-decoration: none; padding: 3px 10px; border-radius: 4px; transition: transform 0.2s;"> Admin Panel</a>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/includes/nav_public.php'; ?>

<div class="wrap">
  <a href="index.php" class="back-link"> Back to Marketplace</a>
  
  <div class="product-layout">
    
    <!-- Left column: images + audio -->
    <div class="media-section">
      <?php 
        $defaultImg = !empty($images) ? $images[0]['image_url'] : 'uploads/products/placeholder.png';
        
        // Robust audio path detection across potential columns
        $audioRaw = $p['audio_file'] ?? $p['audio_track'] ?? $p['preview_audio'] ?? '';
        $audioPath = '';
        if (!empty($audioRaw)) {
            $raw = trim($audioRaw);
            if (strpos($raw, 'http://') === 0 || strpos($raw, 'https://') === 0) {
                $audioPath = $raw;
            } elseif (strpos($raw, 'uploads/') === 0 || strpos($raw, '/uploads/') === 0) {
                $audioPath = '/' . ltrim($raw, '/');
            } else {
                $audioPath = '/uploads/audio/' . ltrim($raw, '/');
            }
        }
        $hasAudio = !empty($audioPath);
      ?>

      <?php if ($hasAudio): ?>
      <!-- PREMIUM AUDIO PLAYER CARD -->
      <div class="audio-player-card" id="audio-player-card">
        <div class="audio-player-artwork">
          <img src="<?= htmlspecialchars($defaultImg) ?>" alt="Cover Art" onerror="this.src='/assets/images/services/default_service.svg'" />
          <div class="audio-vinyl-spin" id="vinyl-disc" title="Now Playing">🎵</div>
        </div>
        <div class="audio-player-controls">
          <!-- Cross-platform HTML5 audio -->
          <audio id="product-audio" preload="metadata" playsinline webkit-playsinline>
            <source src="<?= htmlspecialchars($audioPath) ?>" />
            Your device does not support direct audio playback.
          </audio>
          
          <div class="audio-title">
            <span><?= htmlspecialchars($p['title']) ?></span>
            <div class="audio-wave-bars">
              <span></span><span></span><span></span><span></span>
            </div>
          </div>
          
          <div class="audio-artist">
            <span>🎤 <?= htmlspecialchars($p['shop_name']) ?></span>
            <span style="font-size:0.65rem; color:#00e676; background:rgba(0,230,118,0.12); padding:1px 6px; border-radius:4px; margin-left:auto;">LIVE DEMO</span>
          </div>

          <div class="audio-progress-wrap">
            <span class="audio-time" id="audio-current">0:00</span>
            <div class="audio-progress-bar" id="audio-progress-bar" onclick="seekAudio(event)" title="Click / Tap to Seek">
              <div class="audio-progress-fill" id="audio-progress-fill"></div>
            </div>
            <span class="audio-time" id="audio-duration">0:00</span>
          </div>

          <div class="audio-btns">
            <button type="button" class="audio-ctrl-btn" onclick="skipAudio(-10)" title="Rewind 10 seconds">⏪ 10s</button>
            <button type="button" class="audio-play-btn" id="audio-play-btn" onclick="togglePlay()" title="Play / Pause Audio Track">▶</button>
            <button type="button" class="audio-ctrl-btn" onclick="skipAudio(10)" title="Forward 10 seconds">10s ⏩</button>
            <button type="button" class="audio-ctrl-btn" id="audio-loop-btn" onclick="toggleLoop()" title="Loop Track">🔁</button>
            <input type="range" class="audio-volume" id="audio-volume" min="0" max="1" step="0.05" value="1" oninput="setVolume(this.value)" title="Volume Slider"/>
          </div>

          <div class="audio-preview-notice">
            <span>🎧 Interactive Audio Preview &mdash; Tap play to listen before purchasing</span>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <div class="main-image-panel" <?= $hasAudio ? 'style="margin-top:1rem;"' : '' ?>>
        <img id="main-product-img" src="<?= htmlspecialchars($defaultImg) ?>" alt="<?= htmlspecialchars($p['title']) ?>" onerror="this.src='/assets/images/services/default_service.svg';"/>
      </div>
      
      <?php if (count($images) > 1): ?>
        <div class="thumbnails-row">
          <?php foreach ($images as $idx => $img): ?>
            <button class="thumb-btn <?= $idx === 0 ? 'active' : '' ?>" onclick="switchMainImage('<?= htmlspecialchars($img['image_url']) ?>', this)">
              <img src="<?= htmlspecialchars($img['image_url']) ?>"/>
            </button>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
    
    <!-- Right column: product details -->
    <div class="info-section">
      <div class="shop-meta-header" style="display:flex; justify-content:space-between; align-items:center;">
        <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
          <span>Sold by:</span>
          <a href="#"><?= htmlspecialchars($p['shop_name']) ?></a>
          <?php 
            $has_tags = false;
            if (!empty($p['partner_tags'])) {
              $ptags = explode(',', $p['partner_tags']); 
              foreach($ptags as $tid) {
                if (isset($all_tags[$tid])) {
                  $has_tags = true;
                  $tagObj = $all_tags[$tid];
                  echo '<span title="'.htmlspecialchars($tagObj['name']).'" style="color: '.htmlspecialchars($tagObj['color']).'; background: '.htmlspecialchars($tagObj['bg_color']).'; padding: 2px 6px; border-radius: 4px; font-size: 0.65rem; border: 1px solid '.htmlspecialchars($tagObj['color']).';">';
                  echo htmlspecialchars($tagObj['icon'] . ' ' . $tagObj['name']);
                  echo '</span>';
                }
              }
            }
          ?>
        </div>
        <div style="display:flex; align-items:center; gap:0.5rem; margin-top:0.4rem; flex-wrap:wrap;">
          <?php if ($p['is_official']): ?>
            <span style="color:var(--gold); font-size:0.6rem; background:rgba(252, 185, 0, 0.1); padding:1px 5px; border-radius:4px; border:1px solid rgba(252, 185, 0, 0.2);">Official Partner</span>
          <?php elseif (!$has_tags && $p['shop_status'] === 'approved'): ?>
            <span style="color:var(--green); font-size:0.6rem; background:rgba(0, 230, 118, 0.08); padding:1px 5px; border-radius:4px; border:1px solid rgba(0, 230, 118, 0.18);">Verified Shop</span>
          <?php endif; ?>
          <?php if (($p['seller_level'] ?? 1) == 2): ?>
            <span style="color:#00bcd4; font-size:0.6rem; background:rgba(0, 188, 212, 0.1); padding:1px 5px; border-radius:4px; border:1px solid rgba(0, 188, 212, 0.2); font-weight:800;">Level 2 Seller</span>
          <?php elseif (($p['seller_level'] ?? 1) == 3): ?>
            <span style="color:#e91e63; font-size:0.6rem; background:rgba(233, 30, 99, 0.1); padding:1px 5px; border-radius:4px; border:1px solid rgba(233, 30, 99, 0.2); font-weight:800;">Top Rated Seller</span>
          <?php endif; ?>
          <span style="color: var(--muted); font-size: 0.65rem; margin-left: auto;"> <?= intval($p['total_orders']) ?>+ Orders</span>
        </div>
        <a href="/user/messages.php?partner_id=<?= $p['partner_id'] ?>" style="background:var(--brand); color:#fff; font-size:0.75rem; padding:0.4rem 0.8rem; border-radius:4px; text-decoration:none; font-weight:600;"> Message Shop</a>
      </div>
      
      <h1 class="product-title"><?= htmlspecialchars($p['title']) ?></h1>
      
      <div class="price-tag-card">
        <?php if ($product_price <= 0): ?>
          <span class="price-large-coin" style="color:var(--teal,#00e676);">🎁 FREE / PROMOTIONAL</span>
          <span class="price-large-bdt" style="color:#a7f3d0;">0.00 BDT</span>
        <?php else: ?>
          <span class="price-large-coin">🪙 <?= number_format($product_price, 0) ?> <?= htmlspecialchars($coin_name) ?></span>
          <span class="price-large-bdt">৳ <?= number_format($product_price_bdt, 2) ?> BDT</span>
        <?php endif; ?>
      </div>

      <?php
        $share_link = 'https://' . $_SERVER['HTTP_HOST'] . '/product_detail.php?id=' . $p['id'];
        if ($isUserLoggedIn && !empty($user_profile['ref_code'])) {
            $share_link = 'https://' . $_SERVER['HTTP_HOST'] . '/ref.php?ref=' . urlencode($user_profile['ref_code']) . '&redirect=/product_detail.php?id=' . $p['id'];
        }
      ?>
      <!-- MULTI-PLATFORM SHARE & EARN BOX -->
      <?php 
        $shareText = urlencode("Check out " . $p['title'] . " for 🪙 " . number_format(ceil($product_price)) . " Coins on Fast Site!");
        $encodedShareLink = urlencode($share_link);
      ?>
      <div style="margin-bottom: 1.5rem; padding: 1.2rem; background: rgba(252, 185, 0, 0.05); border: 1px solid rgba(252, 185, 0, 0.25); border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.2);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 0.6rem;">
          <h4 style="color: var(--gold); font-size: 0.9rem; font-weight: 800; display: flex; align-items: center; gap: 6px; margin:0;">
            <span>📤</span> Share Product &amp; Earn Rewards
          </h4>
          <span style="font-size:0.68rem; background:rgba(252,185,0,0.15); color:var(--gold); padding:2px 8px; border-radius:50px; font-weight:700;">Affiliate Active</span>
        </div>
        <p style="font-size: 0.78rem; color: var(--muted); margin-bottom: 1rem; line-height:1.4;">
          Share this product with friends or on social media. Anyone opening this link will see the full product picture, price, and shop details.
        </p>

        <!-- Social Share Buttons Grid -->
        <div style="display:flex; gap: 0.5rem; flex-wrap:wrap; margin-bottom: 0.8rem;">
          <a href="https://wa.me/?text=<?= $shareText ?>%20<?= $encodedShareLink ?>" target="_blank" style="flex:1; min-width:100px; background:#25D366; color:#fff; font-weight:700; text-decoration:none; padding:0.6rem 0.8rem; border-radius:8px; font-size:0.78rem; display:flex; align-items:center; justify-content:center; gap:6px; transition:transform 0.2s;">
            <span>💬</span> WhatsApp
          </a>
          <a href="https://www.facebook.com/sharer/sharer.php?u=<?= $encodedShareLink ?>" target="_blank" style="flex:1; min-width:100px; background:#1877F2; color:#fff; font-weight:700; text-decoration:none; padding:0.6rem 0.8rem; border-radius:8px; font-size:0.78rem; display:flex; align-items:center; justify-content:center; gap:6px; transition:transform 0.2s;">
            <span>📘</span> Facebook
          </a>
          <a href="https://t.me/share/url?url=<?= $encodedShareLink ?>&text=<?= $shareText ?>" target="_blank" style="flex:1; min-width:100px; background:#0088cc; color:#fff; font-weight:700; text-decoration:none; padding:0.6rem 0.8rem; border-radius:8px; font-size:0.78rem; display:flex; align-items:center; justify-content:center; gap:6px; transition:transform 0.2s;">
            <span>✈️</span> Telegram
          </a>
          <button onclick="triggerNativeShare('<?= htmlspecialchars(addslashes($p['title'])) ?>', '<?= htmlspecialchars($share_link) ?>')" style="background:rgba(255,255,255,0.08); color:#fff; font-weight:700; border:1px solid rgba(255,255,255,0.15); padding:0.6rem 0.8rem; border-radius:8px; cursor:pointer; font-size:0.78rem; display:flex; align-items:center; justify-content:center; gap:6px;">
            <span>📲</span> More...
          </button>
        </div>

        <!-- Copy Link Row -->
        <div style="display:flex; gap: 0.5rem;">
          <input type="text" readonly value="<?= htmlspecialchars($share_link) ?>" id="affLink" style="flex:1; background: #08080c; border: 1px solid rgba(255,255,255,0.1); color: var(--gold); padding: 0.55rem 0.8rem; border-radius: 8px; font-size: 0.75rem; font-family:monospace;" />
          <button onclick="copyAffLink()" id="copyBtnTxt" style="background: linear-gradient(135deg, var(--gold), #ff9100); color: #000; font-weight: 800; border: none; padding: 0.55rem 1.2rem; border-radius: 8px; cursor: pointer; font-size: 0.78rem; transition: transform 0.2s; white-space:nowrap;">📋 Copy Link</button>
        </div>
      </div>
      
      <script>
        function triggerNativeShare(title, url) {
          if (navigator.share) {
            navigator.share({
              title: title,
              text: 'Check out ' + title + ' on Fast Site!',
              url: url
            }).catch(() => {});
          } else {
            copyAffLink();
          }
        }
        function copyAffLink() {
          const input = document.getElementById('affLink');
          input.select();
          navigator.clipboard.writeText(input.value);
          const btn = document.getElementById('copyBtnTxt');
          btn.innerHTML = '✅ Copied!';
          btn.style.background = '#10b981';
          btn.style.color = '#fff';
          setTimeout(() => {
            btn.innerHTML = '📋 Copy Link';
            btn.style.background = 'linear-gradient(135deg, var(--gold), #ff9100)';
            btn.style.color = '#000';
          }, 2500);
        }
      </script>
      
      <?php 
        $is_saved = false;
        if (isset($_SESSION['user_id'])) {
            try {
                $stmt_fav = $pdo->prepare("SELECT id FROM user_wishlist WHERE user_id = ? AND product_id = ?");
                $stmt_fav->execute([$_SESSION['user_id'], $p['id']]);
                $is_saved = (bool)$stmt_fav->fetchColumn();
            } catch (Exception $e) {}
        }
      ?>
      <div style="display:flex; gap: 0.5rem; margin-bottom: 1.5rem;">
          <button onclick="toggleWishlist(<?= $p['id'] ?>)" id="wishlist-btn" class="btn-secondary-login" style="flex:1; background: <?= $is_saved ? 'rgba(255, 20, 147, 0.2)' : 'rgba(255, 20, 147, 0.05)' ?>; color: #ff1493; border-color: rgba(255, 20, 147, 0.25); padding: 0.7rem; cursor:pointer; transition: all 0.3s;">
              <span id="wishlist-icon" style="font-size: 1.1rem;"><?= $is_saved ? '❤️' : '🤍' ?></span> <span id="wishlist-text"><?= $is_saved ? 'Saved' : 'Save for Later' ?></span>
          </button>
          <button onclick="shareNativeProduct()" class="btn-secondary-login" style="flex:1; background: rgba(33, 150, 243, 0.1); color: var(--blue); border-color: rgba(33, 150, 243, 0.25); padding: 0.7rem; cursor:pointer;">📤 Share</button>
      </div>
      <script>
      function toggleWishlist(pid) {
        fetch('/api/toggle_wishlist.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({product_id: pid})
        })
        .then(res => res.json())
        .then(data => {
          if (!data.success) {
            alert(data.error);
            return;
          }
          const btn = document.getElementById('wishlist-btn');
          const icon = document.getElementById('wishlist-icon');
          const txt = document.getElementById('wishlist-text');
          if (data.action === 'added') {
            btn.style.background = 'rgba(255, 20, 147, 0.2)';
            icon.innerText = '❤️';
            txt.innerText = 'Saved';
          } else {
            btn.style.background = 'rgba(255, 20, 147, 0.05)';
            icon.innerText = '🤍';
            txt.innerText = 'Save for Later';
          }
        })
        .catch(err => console.error(err));
      }

      function shareNativeProduct() {
        if (navigator.share) {
          navigator.share({
            title: '<?= htmlspecialchars(addslashes($p['title'])) ?>',
            text: 'Check out <?= htmlspecialchars(addslashes($p['title'])) ?> on Fast Site!',
            url: window.location.href
          }).catch(console.error);
        } else {
          if (navigator.clipboard) {
            navigator.clipboard.writeText(window.location.href);
            alert('Product link copied to clipboard!');
          } else {
            alert('Sharing is not supported on this browser.');
          }
        }
      }
      </script>

      <div class="desc-box">
        <h4 style="color:#fff; margin-bottom:0.4rem; font-size:0.9rem;">Product Description</h4>
        <p><?= nl2br(htmlspecialchars($p['description'])) ?></p>
      </div>

      <?php 
        $has_req_sub = !empty($p['require_submission']) || !empty($p['required_docs']) || !empty($p['submission_prompt']);
        $req_prompt = !empty($p['submission_prompt']) ? $p['submission_prompt'] : ($p['required_docs'] ?? '');
        $is_mandatory = !empty($p['submission_required']);
      ?>
      <?php if ($has_req_sub && !empty($req_prompt)): ?>
        <div style="background: rgba(252, 185, 0, 0.06); border: 1px solid rgba(252, 185, 0, 0.25); border-radius: 12px; padding: 1.2rem; margin-bottom: 1.5rem;">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.6rem;">
            <h4 style="color: var(--gold); font-size: 0.88rem; font-weight: 800; display: flex; align-items: center; gap: 6px; margin:0;">
              <span>📑</span> Required Information / Documents from Buyer
            </h4>
            <span style="font-size:0.68rem; background:<?= $is_mandatory ? 'rgba(255,82,82,0.2)' : 'rgba(255,255,255,0.1)' ?>; color:<?= $is_mandatory ? '#ff5252' : '#aaa' ?>; padding:2px 8px; border-radius:50px; font-weight:800; border:1px solid <?= $is_mandatory ? 'rgba(255,82,82,0.3)' : 'rgba(255,255,255,0.2)' ?>;">
              <?= $is_mandatory ? 'MUST SUBMIT' : 'OPTIONAL' ?>
            </span>
          </div>
          <p style="font-size: 0.84rem; color: #e2e8f0; line-height:1.5; margin:0; background:rgba(0,0,0,0.3); padding:0.8rem; border-radius:8px; border:1px solid rgba(255,255,255,0.06);">
            <?= nl2br(htmlspecialchars($req_prompt)) ?>
          </p>
        </div>
      <?php endif; ?>
      
      <div class="action-panel">
        <?php if ($isAffiliateProduct && !empty($affiliateTargetUrl)): ?>
          <a href="<?= htmlspecialchars($affiliateTargetUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn-buy-now" style="background: linear-gradient(135deg, #10b981, #00b0ff); padding: 1.1rem; text-align:center; text-decoration:none; display:block; font-size:1.1rem; font-weight:800; border-radius:12px; box-shadow:0 8px 25px rgba(0, 176, 255, 0.35);">
            🔗 Visit Partner Website (Claim Offer) ↗
          </a>
          <?php if(!empty($p['affiliate_action'])): ?>
              <div style="font-size:0.85rem; color:var(--gold); text-align:center; margin-top:0.9rem; font-weight:700; background:rgba(252,185,0,0.08); padding:0.6rem; border-radius:8px; border:1px dashed rgba(252,185,0,0.3);">
                🎁 <?= htmlspecialchars($p['affiliate_action']) ?>
              </div>
          <?php endif; ?>
        <?php else: ?>
          <div style="display:flex; gap:10px; margin-bottom:0.75rem; flex-wrap:wrap;">
            <?php if ($isUserLoggedIn): ?>
              <button onclick="openCheckoutModal()" class="btn-buy-now" style="flex:1; min-width:140px; background: linear-gradient(135deg, var(--gold), #ff9100);">
                ⚡ সরাসরি অর্ডার করুন
              </button>
            <?php else: ?>
              <a href="checkout.php?product_id=<?= $p['id'] ?>&partner_id=<?= $p['partner_id'] ?>" class="btn-buy-now" style="flex:1; min-width:140px; background: linear-gradient(135deg, var(--gold), #ff9100); text-decoration:none;">
                ⚡ সরাসরি অর্ডার করুন
              </a>
            <?php endif; ?>
            <button type="button" onclick="addToCart(<?= $p['id'] ?>)" class="btn-add-cart-detail" id="btnAddToCart">
              <span>🛒</span>
              <span>কার্টে যোগ করুন</span>
            </button>
          </div>
        <?php endif; ?>
        
        <!-- 1-Click WhatsApp Quick Order Button -->
        <a href="<?= htmlspecialchars($wa_order_link) ?>" target="_blank" rel="noopener noreferrer" class="btn-whatsapp-order">
          <span style="font-size: 1.25rem;">💬</span>
          <span>WhatsApp এ সরাসরি অর্ডার করুন</span>
        </a>

        <!-- 4 Transparent Delivery & SafePay Trust Badges -->
        <div class="delivery-trust-grid">
          <div class="trust-badge-item">
            <span class="trust-badge-icon">🚚</span>
            <div class="trust-badge-text">
              <strong>ঢাকা সিটির ভিতরে:</strong> ২৪-৪৮ ঘণ্টা (৳<?= number_format($delivery_inside, 0) ?>)
            </div>
          </div>
          <div class="trust-badge-item">
            <span class="trust-badge-icon">🚛</span>
            <div class="trust-badge-text">
              <strong>ঢাকা সিটির বাইরে:</strong> ২-৩ দিন (৳<?= number_format($delivery_outside, 0) ?>)
            </div>
          </div>
          <div class="trust-badge-item">
            <span class="trust-badge-icon">💵</span>
            <div class="trust-badge-text">
              <strong>ক্যাশ অন ডেলিভারি:</strong> পণ্য হাতে পেয়ে মূল্য পরিশোধ
            </div>
          </div>
          <div class="trust-badge-item">
            <span class="trust-badge-icon">🛡️</span>
            <div class="trust-badge-text">
              <strong>SafePay গ্যারান্টি:</strong> ১০০% সুরক্ষিত তহবিল ও রিফান্ড
            </div>
          </div>
        </div>
      </div>
      
    </div>
    
  </div>
  
  <!-- Reviews Listing -->
  <div class="reviews-section">
    <h2>
      <span> Customer Reviews (Shop Feedback)</span>
      <span style="font-size:0.85rem; color:var(--muted); font-weight:normal;"> <?= number_format($p['shop_rating'] ?: 5.0, 1) ?> out of 5</span>
    </h2>
    
    <?php if (empty($reviews)): ?>
      <div class="empty-reviews">
        <p>No reviews have been written for this shop yet.</p>
      </div>
    <?php else: ?>
      <?php foreach ($reviews as $rev): ?>
        <div class="review-card">
          <div class="review-meta">
            <span class="review-user"> <?= htmlspecialchars($rev['user_name']) ?></span>
            <span class="review-date"><?= date('d M Y, h:i A', strtotime($rev['created_at'])) ?></span>
          </div>
          <div class="review-stars">
            <?php 
              $rVal = intval($rev['rating']);
              for ($i = 1; $i <= 5; $i++) {
                  echo $i <= $rVal ? '★' : '☆';
              }
            ?>
          </div>
          <p class="review-text"><?= nl2br(htmlspecialchars($rev['review'])) ?></p>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
  
</div>

<footer class="footer">
  <p>© <?= date('Y') ?> Fast Site • Secure Direct Checkout Marketplace</p>
</footer>

<!-- Checkout Modal -->
<div id="checkout-modal-backdrop" onclick="closeCheckoutModal()" style="position:fixed; inset:0; background:rgba(0,0,0,0.75); backdrop-filter:blur(6px); z-index:9999; display:none; opacity:0; transition:opacity 0.3s;"></div>
<div id="checkout-modal" style="position:fixed; top:50%; left:50%; transform:translate(-50%, -50%) scale(0.9); z-index:10000; width:540px; max-width:95vw; max-height:90vh; overflow-y:auto; background:#101018; border:1px solid var(--border); border-radius:20px; box-shadow:0 20px 60px rgba(0,0,0,0.8); display:none; opacity:0; transition:transform 0.3s, opacity 0.3s; padding:1.8rem 1.5rem; box-sizing:border-box;">
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.2rem; border-bottom:1px solid rgba(255,255,255,0.06); padding-bottom:0.6rem;">
    <h3 style="color:var(--gold); font-family:'Oswald', sans-serif; font-size:1.3rem; margin:0; text-transform: uppercase;">🛒 MARKETPLACE CHECKOUT</h3>
    <button onclick="closeCheckoutModal()" style="background:none; border:none; color:var(--muted); font-size:1.2rem; cursor:pointer;">✕</button>
  </div>
  
  <form id="checkout-form" action="/user/place_order.php" method="POST" enctype="multipart/form-data" onsubmit="handleCheckoutSubmit(event)">
    <input type="hidden" name="product_id" value="<?= $p['id'] ?>"/>
    <input type="hidden" name="partner_id" value="<?= $p['partner_id'] ?>"/>
    <input type="hidden" name="price" value="<?= $product_price ?>"/>
    <input type="hidden" id="form-delivery-charge" name="delivery_charge" value="<?= $delivery_inside ?>"/>
    
    <!-- Step 1: Shipping Details -->
    <div style="margin-bottom: 1.2rem;">
      <h4 style="font-size: 0.8rem; color: #fff; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.8rem; border-bottom: 1px solid rgba(255,255,255,0.04); padding-bottom: 0.3rem;">📦 SHIPPING & CONTACT</h4>
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.8rem; margin-bottom: 0.8rem;">
        <div class="order-field" style="display: flex; flex-direction: column; gap: 0.3rem;">
          <label style="font-size: 0.65rem; color: var(--muted); font-weight: 700; text-transform: uppercase;">RECIPIENT NAME *</label>
          <input type="text" name="recipient_name" value="<?= htmlspecialchars($user_profile['name'] ?? '') ?>" required style="background: rgba(0,0,0,0.25); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; padding: 0.55rem; color: #fff; font-size: 0.85rem;" />
        </div>
        <div class="order-field" style="display: flex; flex-direction: column; gap: 0.3rem;">
          <label style="font-size: 0.65rem; color: var(--muted); font-weight: 700; text-transform: uppercase;">CONTACT PHONE *</label>
          <input type="tel" name="recipient_phone" value="<?= htmlspecialchars($user_profile['phone'] ?? '') ?>" required style="background: rgba(0,0,0,0.25); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; padding: 0.55rem; color: #fff; font-size: 0.85rem;" />
        </div>
      </div>
      
      <div class="order-field" style="display: flex; flex-direction: column; gap: 0.3rem; margin-bottom: 0.8rem;">
        <label style="font-size: 0.65rem; color: var(--muted); font-weight: 700; text-transform: uppercase;">DELIVERY ADDRESS / NOTES *</label>
        <textarea name="shipping_address" required rows="2" placeholder="Full address (House, Road, Area, City) or digital delivery email/phone..." style="background: rgba(0,0,0,0.25); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; padding: 0.55rem; color: #fff; font-size: 0.85rem; font-family: inherit; resize: none;"></textarea>
      </div>

      <div class="order-field" style="display: flex; flex-direction: column; gap: 0.3rem;">
        <label style="font-size: 0.65rem; color: var(--muted); font-weight: 700; text-transform: uppercase;">SHIPPING REGION *</label>
        <select name="delivery_location" id="delivery-location" onchange="updateTotal()" required style="background: #161622; border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; padding: 0.55rem; color: #fff; font-size: 0.85rem;">
          <option value="inside_dhaka" data-charge="<?= $delivery_inside ?>">Inside Dhaka (<?= number_format($delivery_inside, 0) ?>)</option>
          <option value="outside_dhaka" data-charge="<?= $delivery_outside ?>">Outside Dhaka (<?= number_format($delivery_outside, 0) ?>)</option>
          <option value="soft" data-charge="0">Soft / Digital Product (0)</option>
        </select>
      </div>
    </div>

    <!-- Step 2: Customer Document & Information Submission (Summation Box) -->
    <?php if ($has_req_sub): ?>
      <div style="background: rgba(252, 185, 0, 0.05); border: 1px solid rgba(252, 185, 0, 0.3); border-radius: 12px; padding: 1rem; margin-bottom: 1.2rem;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.5rem;">
          <h4 style="color: var(--gold); font-size: 0.8rem; font-weight: 800; text-transform: uppercase; margin:0; display:flex; align-items:center; gap:6px;">
            <span>📑</span> Required Information / Documents
          </h4>
          <span style="font-size:0.65rem; background:<?= $is_mandatory ? 'rgba(255,82,82,0.2)' : 'rgba(255,255,255,0.1)' ?>; color:<?= $is_mandatory ? '#ff5252' : '#aaa' ?>; padding:2px 8px; border-radius:50px; font-weight:800; border:1px solid <?= $is_mandatory ? 'rgba(255,82,82,0.3)' : 'rgba(255,255,255,0.2)' ?>;">
            <?= $is_mandatory ? 'MUST SUBMIT' : 'OPTIONAL' ?>
          </span>
        </div>
        
        <?php if (!empty($req_prompt)): ?>
          <p style="color: #cbd5e1; font-size: 0.8rem; margin:0 0 0.8rem 0; line-height:1.4; background:rgba(0,0,0,0.3); padding:0.6rem; border-radius:8px;">
            <?= nl2br(htmlspecialchars($req_prompt)) ?>
          </p>
        <?php endif; ?>

        <div class="order-field" style="display: flex; flex-direction: column; gap: 0.3rem; margin-bottom: 0.8rem;">
          <label style="font-size: 0.65rem; color: var(--gold); font-weight: 700; text-transform: uppercase;">
            YOUR INFORMATION / DETAILS <?= $is_mandatory ? '*' : '' ?>
          </label>
          <textarea name="customer_submission" rows="3" placeholder="Enter requested NID number, details, links, or instructions..." <?= $is_mandatory ? 'required' : '' ?> style="background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.15); border-radius: 8px; padding: 0.6rem; color: #fff; font-size: 0.85rem; font-family: inherit; resize: vertical;"></textarea>
        </div>

        <div class="order-field" style="display: flex; flex-direction: column; gap: 0.3rem;">
          <label style="font-size: 0.65rem; color: var(--gold); font-weight: 700; text-transform: uppercase;">
            ATTACH DOCUMENT / PHOTOS (PDF, JPG, PNG)
          </label>
          <input type="file" name="submission_files[]" multiple accept="image/*,application/pdf,.zip" style="background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.15); border-radius: 8px; padding: 0.5rem; color: #fff; font-size: 0.8rem; width:100%; box-sizing:border-box;" />
        </div>
      </div>
    <?php endif; ?>

    <!-- Step 3: Payment Method (Coins Only) -->
    <div style="margin-bottom: 1.2rem;">
      <h4 style="font-size: 0.8rem; color: #fff; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.8rem; border-bottom: 1px solid rgba(255,255,255,0.04); padding-bottom: 0.3rem;">🪙 PAYMENT METHOD</h4>
      <div style="display: grid; grid-template-columns: 1fr; gap: 0.6rem;">
        
        <label class="pay-method-card" style="display: flex; align-items: center; gap: 10px; background: rgba(252, 185, 0, 0.1); border: 1px solid var(--gold); border-radius: 10px; padding: 0.8rem; cursor: pointer;">
          <input type="radio" name="payment_method" value="coins" checked style="accent-color: var(--gold);" />
          <div>
            <div style="font-size: 0.9rem; font-weight: 700; color: #fff;"> Pay with <?= htmlspecialchars($settings['coin_name'] ?? 'Fast Coins') ?></div>
            <div style="font-size: 0.75rem; color: var(--gold);">Instant automated checkout from wallet balance</div>
          </div>
        </label>
        
        <label class="pay-method-card" style="display: flex; align-items: center; gap: 10px; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; padding: 0.6rem; opacity: 0.5; cursor: not-allowed;">
          <input type="radio" disabled />
          <span style="font-size: 0.8rem; font-weight: 600; color: #fff;"> bKash / Nagad / Card (Under Development - Please use Coins)</span>
        </label>

      </div>
    </div>

    <!-- Step 4: Summary & Submit -->
    <div style="background: rgba(255, 255, 255, 0.01); border: 1px solid rgba(255,255,255,0.04); border-radius: 12px; padding: 1rem; margin-bottom: 1.2rem; display: flex; flex-direction: column; gap: 0.4rem;">
      <div style="display: flex; justify-content: space-between; font-size: 0.83rem; color: var(--muted);">
        <span>Product Price:</span>
        <span style="color: #fff; font-weight: 600;"><?= number_format($product_price_bdt, 2) ?></span>
      </div>
      <div style="display: flex; justify-content: space-between; font-size: 0.83rem; color: var(--muted);">
        <span>Delivery Charge:</span>
        <span id="summary-delivery-charge" style="color: #fff; font-weight: 600;">0.00</span>
      </div>
      <div style="display: flex; justify-content: space-between; font-size: 0.95rem; color: #fff; font-weight: 800; border-top: 1px dashed rgba(255,255,255,0.08); padding-top: 0.5rem; margin-top: 0.2rem;">
        <span style="color: var(--gold);">Grand Total:</span>
        <span id="summary-grand-total" style="color: var(--green);">0.00</span>
      </div>
    </div>

    <div id="checkout-error" style="display:none; background:rgba(244,67,54,.12); border:1px solid rgba(244,67,54,.3); border-radius:8px; padding:.5rem .9rem; font-size:.85rem; color:#ff6b6b; margin-bottom:.8rem;"></div>

    <button type="submit" id="checkout-submit-btn" class="btn-buy-now" style="background: linear-gradient(135deg, var(--gold), #ff9100);"> Pay with <?= htmlspecialchars($settings['coin_name'] ?? 'Coins') ?></button>
  </form>
</div>

<!-- MOCK 3D SECURE OTP MODAL -->
<div id="otp-modal-backdrop" style="position:fixed; inset:0; background:rgba(0,0,0,0.85); backdrop-filter:blur(8px); z-index:20000; display:none; opacity:0; transition:opacity 0.3s;"></div>
<div id="otp-modal" style="position:fixed; top:50%; left:50%; transform:translate(-50%, -50%) scale(0.9); z-index:20001; width:360px; max-width:90vw; background:#fff; color:#333; border-radius:16px; box-shadow:0 25px 50px rgba(0,0,0,0.5); display:none; opacity:0; transition:transform 0.3s, opacity 0.3s; padding:1.8rem; box-sizing:border-box; font-family: sans-serif;">
  <div style="text-align:center; margin-bottom:1.2rem;">
    <div style="font-size:1.8rem; color:#1a73e8; font-weight:bold; letter-spacing:0.5px; margin-bottom: 4px;"> SafePay 3D Secure</div>
    <div style="font-size:0.75rem; color:#666; font-weight:600; text-transform:uppercase;">Verified by Visa / Mastercard</div>
  </div>
  
  <div style="background:#f1f3f4; border-radius:8px; padding:0.8rem; font-size:0.8rem; color:#444; margin-bottom:1.2rem; line-height:1.45;">
    <div style="display:flex; justify-content:space-between; margin-bottom:4px;"><span>Merchant:</span> <strong>FAST SITE STORE</strong></div>
    <div style="display:flex; justify-content:space-between; margin-bottom:4px;"><span>Amount:</span> <strong id="otp-amount" style="color:#1e8e3e;">0.00</strong></div>
    <div style="display:flex; justify-content:space-between;"><span>Card Ending:</span> <strong id="otp-card-ending">**** 0000</strong></div>
  </div>

  <p style="font-size:0.8rem; color:#666; margin-bottom:1.2rem; line-height:1.4; text-align:center;">
    A mock OTP code has been generated. Please enter <strong style="color:#000;">1234</strong> to complete this simulated payment.
  </p>

  <div style="display:flex; flex-direction:column; gap:0.6rem; margin-bottom:1.2rem;">
    <input type="text" id="otp-input" maxlength="6" style="background:#fff; border:2px solid #dadce0; border-radius:8px; padding:0.65rem; color:#000; font-size:1.2rem; text-align:center; letter-spacing:4px; font-weight:bold; outline:none; transition:border-color 0.2s;" placeholder="����" />
    <div id="otp-error-msg" style="display:none; color:#d93025; font-size:0.75rem; text-align:center; font-weight:600;"></div>
  </div>

  <button onclick="verifyOtp()" style="background:#1a73e8; color:#fff; border:none; border-radius:8px; padding:0.7rem; font-size:0.9rem; font-weight:bold; width:100%; cursor:pointer; transition:background 0.2s;">Verify & Complete Payment</button>
  <button onclick="closeOtpModal()" style="background:none; border:none; color:#5f6368; font-size:0.78rem; text-decoration:underline; width:100%; margin-top:0.8rem; cursor:pointer;">Cancel Transaction</button>
</div>

<!-- LOADING SPINNER OVERLAY -->
<div id="loading-overlay" style="position:fixed; inset:0; background:rgba(8,8,12,0.9); backdrop-filter:blur(8px); z-index:30000; display:none; align-items:center; justify-content:center; flex-direction:column; gap:1.2rem;">
  <div style="width: 50px; height: 50px; border: 4px solid rgba(252,185,0,0.1); border-top: 4px solid var(--gold); border-radius: 50%; animation: spin 1s linear infinite;"></div>
  <div id="loading-text" style="color: #fff; font-size: 0.95rem; font-weight: 600; font-family: 'Inter', sans-serif;">Verifying payment details...</div>
</div>

<style>
@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
.pay-method-card:hover {
  border-color: rgba(252,185,0,0.4) !important;
  background: rgba(252,185,0,0.04) !important;
}
.pay-method-card input:checked + span {
  color: var(--gold) !important;
}
</style>

<script>
function switchMainImage(url, thumbBtn) {
  document.getElementById('main-product-img').src = url;
  document.querySelectorAll('.thumb-btn').forEach(btn => btn.classList.remove('active'));
  thumbBtn.classList.add('active');
}

const productPrice = <?= $product_price_bdt ?>;

function openCheckoutModal() {
  document.getElementById('checkout-modal-backdrop').style.display = 'block';
  document.getElementById('checkout-modal').style.display = 'block';
  setTimeout(() => {
    document.getElementById('checkout-modal-backdrop').style.opacity = '1';
    document.getElementById('checkout-modal').style.opacity = '1';
    document.getElementById('checkout-modal').style.transform = 'translate(-50%, -50%) scale(1)';
  }, 10);
  updateTotal();
  const activeRadio = document.querySelector('input[name="payment_method"]:checked');
  if (activeRadio) {
    selectPaymentMethod(activeRadio.value);
  }
}

function closeCheckoutModal() {
  document.getElementById('checkout-modal-backdrop').style.opacity = '0';
  document.getElementById('checkout-modal').style.opacity = '0';
  document.getElementById('checkout-modal').style.transform = 'translate(-50%, -50%) scale(0.9)';
  setTimeout(() => {
    document.getElementById('checkout-modal-backdrop').style.display = 'none';
    document.getElementById('checkout-modal').style.display = 'none';
  }, 300);
}

function updateTotal() {
  const locSelect = document.getElementById('delivery-location');
  const activeOpt = locSelect.options[locSelect.selectedIndex];
  const charge = parseFloat(activeOpt.getAttribute('data-charge') || 0);
  
  document.getElementById('form-delivery-charge').value = charge;
  document.getElementById('summary-delivery-charge').textContent = '' + charge.toFixed(2);
  
  const grandTotal = productPrice + charge;
  document.getElementById('summary-grand-total').textContent = '' + grandTotal.toFixed(2);
}

function selectPaymentMethod(method) {
  const cardFields = document.getElementById('card-fields');
  const mfsFields = document.getElementById('mfs-fields');
  const codFields = document.getElementById('cod-fields');
  const noticeBox = document.getElementById('showcase-notice');
  const noticeText = document.getElementById('showcase-notice-text');

  cardFields.style.display = 'none';
  mfsFields.style.display = 'none';
  codFields.style.display = 'none';
  noticeBox.style.display = 'none';

  if (method === 'card') {
    cardFields.style.display = 'flex';
    noticeBox.style.display = 'flex';
    noticeText.innerHTML = " <strong>'PAY BY CARD'</strong> FEATURES IS UNDER DEVLOPMENT BUT IT WILL BE ANIMATED PROFESSIONALLY FOR SHOW CASING.";
  } else if (method === 'bkash') {
    mfsFields.style.display = 'flex';
    noticeBox.style.display = 'flex';
    noticeText.innerHTML = " <strong>'BKASH AND NAGAD'</strong> FEATURES ARE UNDER DEVLOPMENT BUT IT WILL BE ANIMATED PROFESSIONALLY FOR SHOW CASING.";
  } else if (method === 'nagad') {
    mfsFields.style.display = 'flex';
    noticeBox.style.display = 'flex';
    noticeText.innerHTML = " <strong>'BKASH AND NAGAD'</strong> FEATURES ARE UNDER DEVLOPMENT BUT IT WILL BE ANIMATED PROFESSIONALLY FOR SHOW CASING.";
  } else if (method === 'cod') {
    codFields.style.display = 'block';
  }
}

function handleCheckoutSubmit(e) {
  e.preventDefault();
  
  const activeRadio = document.querySelector('input[name="payment_method"]:checked');
  if (!activeRadio) {
    alert("Please select a payment method.");
    return;
  }
  const method = activeRadio.value;
  const errBox = document.getElementById('checkout-error');
  errBox.style.display = 'none';

  const address = document.querySelector('textarea[name="shipping_address"]').value.trim();
  if (!address) {
    errBox.textContent = ' Shipping address is required.';
    errBox.style.display = 'block';
    return;
  }

  if (method === 'card') {
    const cName = document.getElementById('card-name').value.trim();
    const cNum = document.getElementById('card-number').value.trim();
    const cExp = document.getElementById('card-expiry').value.trim();
    const cCvv = document.getElementById('card-cvv').value.trim();
    if (!cName || !cNum || !cExp || !cCvv) {
      errBox.textContent = ' All card details are required.';
      errBox.style.display = 'block';
      return;
    }
    openOtpModal();
  } else if (method === 'bkash' || method === 'nagad') {
    const sender = document.getElementById('mfs-sender').value.trim();
    const trx = document.getElementById('mfs-trxid').value.trim();
    if (!sender || !trx) {
      errBox.textContent = ' Sender mobile number and Transaction ID are required.';
      errBox.style.display = 'block';
      return;
    }

    showLoading("Verifying mobile payment details...");
    setTimeout(() => {
      hideLoading();
      document.getElementById('checkout-form').submit();
    }, 1800);
  } else if (method === 'cod') {
    document.getElementById('checkout-form').submit();
  }
}

function openOtpModal() {
  const locSelect = document.getElementById('delivery-location');
  const charge = parseFloat(locSelect.options[locSelect.selectedIndex].getAttribute('data-charge') || 0);
  const total = productPrice + charge;
  
  const cardNum = document.getElementById('card-number').value.replace(/\s+/g, '');
  const ending = cardNum.length >= 4 ? cardNum.substring(cardNum.length - 4) : '0000';

  document.getElementById('otp-amount').textContent = '' + total.toFixed(2);
  document.getElementById('otp-card-ending').textContent = '**** ' + ending;
  document.getElementById('otp-input').value = '';
  document.getElementById('otp-error-msg').style.display = 'none';

  document.getElementById('otp-modal-backdrop').style.display = 'block';
  document.getElementById('otp-modal').style.display = 'block';
  setTimeout(() => {
    document.getElementById('otp-modal-backdrop').style.opacity = '1';
    document.getElementById('otp-modal').style.opacity = '1';
    document.getElementById('otp-modal').style.transform = 'translate(-50%, -50%) scale(1)';
  }, 10);
}

function closeOtpModal() {
  document.getElementById('otp-modal-backdrop').style.opacity = '0';
  document.getElementById('otp-modal').style.opacity = '0';
  document.getElementById('otp-modal').style.transform = 'translate(-50%, -50%) scale(0.9)';
  setTimeout(() => {
    document.getElementById('otp-modal-backdrop').style.display = 'none';
    document.getElementById('otp-modal').style.display = 'none';
  }, 300);
}

function verifyOtp() {
  const otp = document.getElementById('otp-input').value.trim();
  const errorMsg = document.getElementById('otp-error-msg');
  errorMsg.style.display = 'none';

  if (otp === '1234') {
    closeOtpModal();
    showLoading("OTP Verified. Authorizing transaction...");
    setTimeout(() => {
      hideLoading();
      const inputRef = document.createElement('input');
      inputRef.type = 'hidden';
      inputRef.name = 'gateway_ref';
      inputRef.value = 'CARD_OTP_VERIFIED';
      document.getElementById('checkout-form').appendChild(inputRef);
      
      const inputTrx = document.createElement('input');
      inputTrx.type = 'hidden';
      inputTrx.name = 'transaction_id';
      inputTrx.value = 'MOCK_CARD_TX_' + Date.now();
      document.getElementById('checkout-form').appendChild(inputTrx);

      document.getElementById('checkout-form').submit();
    }, 1500);
  } else {
    errorMsg.textContent = ' Invalid OTP. Please try again (Hint: 1234)';
    errorMsg.style.display = 'block';
  }
}

function showLoading(text) {
  document.getElementById('loading-text').textContent = text;
  document.getElementById('loading-overlay').style.display = 'flex';
}

function hideLoading() {
  document.getElementById('loading-overlay').style.display = 'none';
}

document.addEventListener('DOMContentLoaded', () => {
  const cardInput = document.getElementById('card-number');
  if (cardInput) {
    cardInput.addEventListener('input', (e) => {
      let v = e.target.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
      let matches = v.match(/\d{4,16}/g);
      let match = matches && matches[0] || '';
      let parts = [];
      for (let i=0, len=match.length; i<len; i+=4) {
        parts.push(match.substring(i, i+4));
      }
      if (parts.length > 0) {
        e.target.value = parts.join(' ');
      } else {
        e.target.value = v;
      }
    });
  }

  const expiryInput = document.getElementById('card-expiry');
  if (expiryInput) {
    expiryInput.addEventListener('input', (e) => {
      let v = e.target.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
      if (v.length >= 2) {
        e.target.value = v.substring(0, 2) + '/' + v.substring(2, 4);
      } else {
        e.target.value = v;
      }
    });
  }
});
</script>

<script>
// ─── High-Fidelity Audio Player Engine (Desktop & Mobile APK) ────────────────
(function() {
  const audio = document.getElementById('product-audio');
  if (!audio) return;

  const card       = document.getElementById('audio-player-card');
  const playBtn    = document.getElementById('audio-play-btn');
  const fillEl     = document.getElementById('audio-progress-fill');
  const currentEl  = document.getElementById('audio-current');
  const durationEl = document.getElementById('audio-duration');
  const vinyl      = document.getElementById('vinyl-disc');
  const loopBtn    = document.getElementById('audio-loop-btn');

  function fmt(s) {
    if (isNaN(s) || !isFinite(s)) return '0:00';
    s = Math.floor(s || 0);
    const m = Math.floor(s / 60);
    const sec = s % 60;
    return m + ':' + (sec < 10 ? '0' : '') + sec;
  }

  function updateDuration() {
    if (audio.duration && isFinite(audio.duration)) {
      durationEl.textContent = fmt(audio.duration);
    }
  }

  audio.addEventListener('loadedmetadata', updateDuration);
  audio.addEventListener('durationchange', updateDuration);
  audio.addEventListener('canplay', updateDuration);

  audio.addEventListener('timeupdate', () => {
    if (!audio.duration) return;
    const pct = (audio.currentTime / audio.duration) * 100;
    fillEl.style.width = pct + '%';
    currentEl.textContent = fmt(audio.currentTime);
  });

  audio.addEventListener('ended', () => {
    if (!audio.loop) {
      playBtn.textContent = '▶';
      vinyl && vinyl.classList.remove('spinning');
      card && card.classList.remove('is-playing');
    }
  });

  audio.addEventListener('play', () => {
    playBtn.textContent = '⏸';
    vinyl && vinyl.classList.add('spinning');
    card && card.classList.add('is-playing');
  });

  audio.addEventListener('pause', () => {
    playBtn.textContent = '▶';
    vinyl && vinyl.classList.remove('spinning');
    card && card.classList.remove('is-playing');
  });

  window.togglePlay = function() {
    if (audio.paused) {
      const playPromise = audio.play();
      if (playPromise !== undefined) {
        playPromise.catch(err => {
          console.warn("Audio playback issue:", err);
          // If auto-play policy blocks, fallback
        });
      }
    } else {
      audio.pause();
    }
  };

  window.skipAudio = function(sec) {
    if (!audio.duration) return;
    audio.currentTime = Math.max(0, Math.min(audio.duration, audio.currentTime + sec));
  };

  window.toggleLoop = function() {
    audio.loop = !audio.loop;
    if (loopBtn) {
      loopBtn.style.background = audio.loop ? 'rgba(252, 185, 0, 0.3)' : 'rgba(255, 255, 255, 0.06)';
      loopBtn.style.borderColor = audio.loop ? 'var(--gold)' : 'rgba(255, 255, 255, 0.12)';
      loopBtn.style.color = audio.loop ? 'var(--gold)' : '#fff';
    }
  };

  window.seekAudio = function(e) {
    const bar = document.getElementById('audio-progress-bar');
    if (!bar || !audio.duration) return;
    const rect = bar.getBoundingClientRect();
    const clientX = e.touches ? e.touches[0].clientX : e.clientX;
    const pct = Math.max(0, Math.min(1, (clientX - rect.left) / rect.width));
    audio.currentTime = pct * audio.duration;
  };

  // Touch drag support on mobile APK
  const progressBar = document.getElementById('audio-progress-bar');
  if (progressBar) {
    let isDragging = false;
    progressBar.addEventListener('touchstart', (e) => { isDragging = true; window.seekAudio(e); }, { passive: true });
    progressBar.addEventListener('touchmove', (e) => { if (isDragging) window.seekAudio(e); }, { passive: true });
    progressBar.addEventListener('touchend', () => { isDragging = false; });
  }

  window.setVolume = function(val) {
    audio.volume = parseFloat(val);
  };
})();
</script>
<!-- MOBILE STICKY ACTION DOCK (< 768px) -->
<div class="mobile-sticky-action-dock">
  <div class="dock-product-preview">
    <img src="<?= htmlspecialchars($primary_thumb) ?>" alt="<?= htmlspecialchars($p['title']) ?>" class="dock-thumb" />
    <div class="dock-price-col">
      <div class="dock-price-bdt">৳<?= number_format($product_price_bdt, 0) ?></div>
      <div class="dock-price-coin">🪙 <?= number_format(ceil($product_price)) ?> pts</div>
    </div>
  </div>
  <div class="dock-buttons-group">
    <a href="<?= htmlspecialchars($wa_order_link) ?>" target="_blank" rel="noopener noreferrer" class="dock-btn-whatsapp" title="WhatsApp এ অর্ডার করুন">
      <span>💬</span>
      <span class="dock-btn-text">WhatsApp</span>
    </a>
    <button type="button" onclick="addToCart(<?= $p['id'] ?>)" class="dock-btn-cart" title="কার্টে যোগ করুন">
      <span>🛒</span>
      <span class="dock-btn-text">কার্ট</span>
    </button>
    <?php if ($isAffiliateProduct && !empty($affiliateTargetUrl)): ?>
      <a href="<?= htmlspecialchars($affiliateTargetUrl) ?>" target="_blank" rel="noopener noreferrer" class="dock-btn-buy" style="background: linear-gradient(135deg, #10b981, #00b0ff);">
        <span>🔗</span>
        <span class="dock-btn-text">Claim Offer</span>
      </a>
    <?php elseif ($isUserLoggedIn): ?>
      <button onclick="openCheckoutModal()" class="dock-btn-buy">
        <span>🛒</span>
        <span class="dock-btn-text">অর্ডার করুন</span>
      </button>
    <?php else: ?>
      <a href="checkout.php?product_id=<?= $p['id'] ?>&partner_id=<?= $p['partner_id'] ?>" class="dock-btn-buy">
        <span>🛒</span>
        <span class="dock-btn-text">অর্ডার করুন</span>
      </a>
    <?php endif; ?>
  </div>
</div>

<script>
function addToCart(pid, qty) {
  if (!qty) qty = 1;
  const btn = document.getElementById('btnAddToCart');
  let originalHtml = '';
  if (btn) {
    originalHtml = btn.innerHTML;
    btn.innerHTML = '<span>⏳</span><span>যোগ হচ্ছে...</span>';
    btn.disabled = true;
  }
  
  const formData = new FormData();
  formData.append('action', 'add');
  formData.append('product_id', pid);
  formData.append('quantity', qty);
  formData.append('ajax', '1');

  fetch('/cart.php', {
    method: 'POST',
    body: formData,
    headers: {
      'X-Requested-With': 'XMLHttpRequest',
      'Accept': 'application/json'
    }
  })
  .then(res => res.json())
  .then(data => {
    if (data && data.success) {
      const cartCountBadge = document.getElementById('navCartCount');
      if (cartCountBadge) {
        cartCountBadge.innerText = data.cart_count;
        cartCountBadge.style.display = data.cart_count > 0 ? 'inline-block' : 'none';
      }
      alert('✅ পণ্যটি সফলভাবে কার্টে যোগ করা হয়েছে!');
      if (btn) {
        btn.innerHTML = '<span>✓</span><span>যোগ হয়েছে!</span>';
        btn.style.borderColor = '#10B981';
        btn.style.color = '#10B981';
        setTimeout(() => {
          btn.innerHTML = originalHtml;
          btn.disabled = false;
          btn.style.borderColor = '';
          btn.style.color = '';
        }, 2200);
      }
    } else {
      alert('ত্রুটি: ' + ((data && data.error) ? data.error : 'কার্টে যোগ করা যায়নি'));
      if (btn) {
        btn.innerHTML = originalHtml;
        btn.disabled = false;
      }
    }
  })
  .catch(err => {
    console.error(err);
    window.location.href = '/cart.php?action=add&product_id=' + pid + '&quantity=' + qty;
  });
}
</script>
</body>
</html>
