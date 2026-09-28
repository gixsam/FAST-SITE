<?php
// =========================================================================
// shop.php — Clean Branded Partner Storefront & OpenGraph Social Cards
// URL Route: https://fastsite.best-travel.ltd/shop/dark_wolf
// =========================================================================
session_start();
require_once __DIR__ . '/config.php';

$rawSlug = trim($_GET['slug'] ?? ($_GET['shop'] ?? ''));
$slug = strtolower($rawSlug);

// 1. Reserved Keyword Blacklist
$blacklist = ['admin', 'api', 'cart', 'checkout', 'login', 'register', 'settings', 'user', 'partner', 'downloads', 'config', 'assets', 'system'];
if (empty($slug) || in_array($slug, $blacklist)) {
    header('Location: /'); exit;
}

// Ensure self-healing schema
try {
    if (isset($pdo)) {
        @$pdo->exec("ALTER TABLE `partners` ADD COLUMN `shop_slug` VARCHAR(100) DEFAULT NULL");
    }
} catch (Exception $e) {}

$slugClean = strtolower(trim($slug));
$slugRaw   = strtolower(trim(str_replace(['_', '-'], ' ', $slugClean)));
$slugId    = is_numeric($slugClean) ? (int)$slugClean : 0;

$shop = null;

// 2. Fetch Shop Details from DB (Multi-Strategy Defensive Search)
try {
    // Strategy A: Match by business_name variations
    $stmt = $pdo->prepare("
        SELECT * FROM partners 
        WHERE LOWER(TRIM(business_name)) = :s_raw
           OR LOWER(TRIM(REPLACE(business_name, ' ', '_'))) = :s
           OR LOWER(TRIM(REPLACE(business_name, '-', '_'))) = :s
           OR (id > 0 AND id = :s_id)
        LIMIT 1
    ");
    $stmt->execute([
        ':s_raw' => $slugRaw,
        ':s'     => $slugClean,
        ':s_id'  => $slugId
    ]);
    $shop = $stmt->fetch(PDO::FETCH_ASSOC);

    // Strategy B: Fallback search with LIKE on business_name
    if (!$shop) {
        $stmtLike = $pdo->prepare("SELECT * FROM partners WHERE LOWER(business_name) LIKE :s_like LIMIT 1");
        $stmtLike->execute([':s_like' => '%' . $slugRaw . '%']);
        $shop = $stmtLike->fetch(PDO::FETCH_ASSOC);
    }

    // Strategy C: Check shop_slug if populated
    if (!$shop) {
        try {
            $stmtSlug = $pdo->prepare("SELECT * FROM partners WHERE LOWER(TRIM(shop_slug)) = :s LIMIT 1");
            $stmtSlug->execute([':s' => $slugClean]);
            $shop = $stmtSlug->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {}
    }
} catch (Exception $e) {}

// 3. Fetch Shop Products & Services with HD Thumbnails
$products = [];

if ($shop && !empty($shop['id'])) {
    try {
        $pStmt = $pdo->prepare("
            SELECT p.*,
            (SELECT image_url FROM partner_product_images WHERE product_id = p.id AND is_thumbnail = 1 LIMIT 1) AS thumb,
            (SELECT image_url FROM partner_product_images WHERE product_id = p.id ORDER BY id ASC LIMIT 1) AS fallback_img
            FROM partner_products p
            WHERE p.partner_id = :pid AND (p.is_published = 1 OR p.is_published IS NULL)
            ORDER BY p.id DESC
        ");
        $pStmt->execute([':pid' => $shop['id']]);
        $products = $pStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
}

// Strategy D: If products is still empty, search partner_products by partner business_name JOIN
if (empty($products)) {
    try {
        $pStmt2 = $pdo->prepare("
            SELECT p.*,
            pt.id AS partner_table_id, pt.business_name AS pt_business_name, pt.description AS pt_description,
            pt.profile_pic AS pt_profile_pic, pt.cover_pic AS pt_cover_pic, pt.is_official AS pt_is_official,
            (SELECT image_url FROM partner_product_images WHERE product_id = p.id AND is_thumbnail = 1 LIMIT 1) AS thumb,
            (SELECT image_url FROM partner_product_images WHERE product_id = p.id ORDER BY id ASC LIMIT 1) AS fallback_img
            FROM partner_products p
            JOIN partners pt ON p.partner_id = pt.id
            WHERE (LOWER(TRIM(pt.business_name)) = :bname 
               OR LOWER(TRIM(REPLACE(pt.business_name, ' ', '_'))) = :bslug 
               OR LOWER(TRIM(REPLACE(pt.business_name, '-', '_'))) = :bslug
               OR LOWER(pt.business_name) LIKE :blike)
              AND (p.is_published = 1 OR p.is_published IS NULL)
            ORDER BY p.id DESC
        ");
        $pStmt2->execute([
            ':bname' => $slugRaw,
            ':bslug' => $slugClean,
            ':blike' => '%' . $slugRaw . '%'
        ]);
        $products = $pStmt2->fetchAll(PDO::FETCH_ASSOC);

        // If we found products and need to hydrate shop details
        if (!empty($products) && (!$shop || empty($shop['id']))) {
            $firstP = $products[0];
            $shop = [
                'id'            => $firstP['partner_table_id'] ?? $firstP['partner_id'],
                'business_name' => $firstP['pt_business_name'] ?? ucwords($slugRaw),
                'description'   => $firstP['pt_description'] ?? 'Verified Partner Shop on Fast Site Marketplace.',
                'profile_pic'   => $firstP['pt_profile_pic'] ?? null,
                'cover_pic'     => $firstP['pt_cover_pic'] ?? null,
                'is_official'   => $firstP['pt_is_official'] ?? 0,
                'whatsapp'      => '8801337320544'
            ];
        }
    } catch (Exception $e) {}
}

// Fallback demo shop if DB record missing
if (!$shop) {
    $shop = [
        'id' => 0,
        'business_name' => ucwords(str_replace(['_', '-'], ' ', $slug)),
        'description' => 'Verified Official Partner Shop on Fast Site Marketplace.',
        'profile_pic' => null,
        'cover_pic' => null,
        'logo_url' => 'assets/images/logo.png',
        'banner_url' => 'assets/images/banner_placeholder.jpg',
        'whatsapp' => '8801337320544',
        'created_at' => date('Y-m-d')
    ];
}

// 4. Auto-Assign Affiliate / Referral Attribution
if (!empty($shop['id'])) {
    $_SESSION['ref_partner_id'] = $shop['id'];
    $_SESSION['ref_shop_slug']  = $slug;
}

// 5. Resolve Shop Branding Images
$shopLogo = resolveShopMedia($shop, 'avatar');
$shopBanner = resolveShopMedia($shop, 'cover');

// OpenGraph Meta Values
$og_title = htmlspecialchars($shop['business_name']) . " — Official Store | Fast Site";
$og_desc = htmlspecialchars($shop['description'] ?? 'Browse verified products and services with 100% Escrow buyer protection.');
$og_image = preg_match('#^https?://#i', $shopLogo) ? $shopLogo : "https://fastsite.best-travel.ltd" . $shopLogo;
$og_url = "https://fastsite.best-travel.ltd/shop/" . urlencode($shop['shop_slug'] ?? $slug);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  
  <!-- OpenGraph Social Sharing Meta Tags -->
  <title><?= $og_title ?></title>
  <meta name="description" content="<?= $og_desc ?>"/>
  <meta property="og:type" content="website"/>
  <meta property="og:url" content="<?= $og_url ?>"/>
  <meta property="og:title" content="<?= $og_title ?>"/>
  <meta property="og:description" content="<?= $og_desc ?>"/>
  <meta property="og:image" content="<?= $og_image ?>"/>
  <meta property="twitter:card" content="summary_large_image"/>
  <meta property="twitter:title" content="<?= $og_title ?>"/>
  <meta property="twitter:description" content="<?= $og_desc ?>"/>
  <meta property="twitter:image" content="<?= $og_image ?>"/>

  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Oswald:wght@600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="/assets/css/admin.css">
  
  <style>
    :root {
      --bg: #07090e;
      --card-bg: #111420;
      --gold: #fcb900;
      --emerald: #10b981;
      --border: rgba(255, 255, 255, 0.08);
    }
    * { box-sizing: border-box; }
    body { background: var(--bg); color: #fff; font-family: 'Inter', sans-serif; margin:0; padding:0; min-height: 100vh; }
    
    .shop-banner {
      height: 240px;
      background: linear-gradient(180deg, rgba(7,9,14,0.3) 0%, rgba(7,9,14,0.95) 100%), url('<?= htmlspecialchars($shopBanner) ?>') center/cover no-repeat;
      position: relative;
      border-bottom: 1px solid rgba(252,185,0,0.25);
    }
    .shop-header-wrap {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 1.5rem;
      position: relative;
      top: -70px;
      display: flex;
      align-items: flex-end;
      gap: 1.5rem;
      flex-wrap: wrap;
    }
    .shop-avatar {
      width: 120px;
      height: 120px;
      border-radius: 24px;
      background: #111420;
      border: 3px solid var(--gold);
      box-shadow: 0 10px 30px rgba(0,0,0,0.7);
      object-fit: cover;
      flex-shrink: 0;
    }
    .shop-info {
      flex: 1;
      min-width: 250px;
    }
    .shop-info h1 {
      font-family: 'Oswald', sans-serif;
      font-size: 2.2rem;
      color: #fff;
      margin: 0;
      text-transform: uppercase;
      letter-spacing: 0.04em;
      display: flex;
      align-items: center;
      gap: 0.6rem;
    }
    .verified-badge {
      display: inline-flex;
      align-items: center;
      gap: 0.3rem;
      background: rgba(16,185,129,0.15);
      border: 1px solid #10b981;
      color: #34d399;
      font-size: 0.75rem;
      font-weight: 800;
      padding: 0.2rem 0.6rem;
      border-radius: 20px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .shop-info p {
      color: #94a3b8;
      margin: 6px 0 0 0;
      font-size: 0.92rem;
      line-height: 1.4;
    }
    
    .section-title-wrap {
      max-width: 1200px;
      margin: -20px auto 1.5rem auto;
      padding: 0 1.5rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      border-bottom: 1px solid var(--border);
      padding-bottom: 0.8rem;
    }
    .section-title {
      color: var(--gold);
      font-family: 'Oswald', sans-serif;
      text-transform: uppercase;
      font-size: 1.3rem;
      margin: 0;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    .item-count {
      color: #94a3b8;
      font-size: 0.85rem;
      font-weight: 600;
    }

    .product-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
      gap: 1.5rem;
      max-width: 1200px;
      margin: 0 auto 4rem auto;
      padding: 0 1.5rem;
    }
    .product-card {
      background: var(--card-bg);
      border: 1px solid var(--border);
      border-radius: 16px;
      overflow: hidden;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      display: flex;
      flex-direction: column;
    }
    .product-card:hover {
      border-color: rgba(252, 185, 0, 0.5);
      transform: translateY(-5px);
      box-shadow: 0 12px 35px rgba(0,0,0,0.6);
    }
    .product-img-wrap {
      position: relative;
      width: 100%;
      height: 190px;
      background: #000;
      overflow: hidden;
    }
    .product-img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.4s;
    }
    .product-card:hover .product-img {
      transform: scale(1.05);
    }
    .badge-pill {
      position: absolute;
      top: 10px;
      left: 10px;
      background: rgba(0,0,0,0.7);
      backdrop-filter: blur(8px);
      border: 1px solid rgba(255,255,255,0.15);
      color: #fbbf24;
      font-size: 0.72rem;
      font-weight: 800;
      padding: 0.25rem 0.65rem;
      border-radius: 20px;
      text-transform: uppercase;
    }
    .product-body {
      padding: 1.2rem;
      display: flex;
      flex-direction: column;
      flex: 1;
    }
    .product-title {
      font-weight: 700;
      font-size: 1.05rem;
      color: #f1f5f9;
      margin-bottom: 0.5rem;
      line-height: 1.35;
    }
    .product-price-row {
      margin-top: auto;
      display: flex;
      align-items: baseline;
      justify-content: space-between;
      padding-top: 0.8rem;
    }
    .product-price {
      color: var(--gold);
      font-weight: 900;
      font-size: 1.2rem;
    }
    .btn-buy {
      background: linear-gradient(135deg, #fcb900 0%, #f59e0b 100%);
      color: #090a10;
      border: none;
      width: 100%;
      padding: 0.75rem;
      border-radius: 10px;
      font-weight: 800;
      font-size: 0.9rem;
      cursor: pointer;
      margin-top: 1rem;
      transition: opacity 0.2s;
    }
    .btn-buy:hover {
      opacity: 0.92;
    }
    .empty-state {
      grid-column: 1/-1;
      text-align: center;
      padding: 4rem 1.5rem;
      background: var(--card-bg);
      border-radius: 16px;
      border: 1px dashed var(--border);
    }
  </style>
</head>
<body>
<?php if (!empty($shop['announcement_text'])): ?>
  <div style="background: <?= htmlspecialchars($shop['announcement_bg'] ?? '#fcb900') ?>; padding: 10px 15px; text-align: center; font-weight: 800; color: #fff; font-size: 0.95rem; text-transform: uppercase; letter-spacing: 0.5px; text-shadow: 0 1px 3px rgba(0,0,0,0.4); z-index: 100; position: relative;">
    📢 <?= htmlspecialchars($shop['announcement_text']) ?>
  </div>
<?php endif; ?>

<div class="shop-banner"></div>

<div class="shop-header-wrap">
  <img src="<?= htmlspecialchars($shopLogo) ?>" class="shop-avatar" alt="Shop Logo" onerror="this.onerror=null; this.src='/assets/images/logo.png';"/>
  <div class="shop-info">
    <h1>
      <?= htmlspecialchars($shop['business_name']) ?>
      <span class="verified-badge">✓ Verified Partner</span>
    </h1>
    <p><?= htmlspecialchars($shop['description'] ?? 'Verified Official Partner Shop on Fast Site Marketplace.') ?> • 🛡️ Escrow Protected</p>
  </div>
</div>

<div class="section-title-wrap">
  <h2 class="section-title">🛍️ Store Catalog</h2>
  <span class="item-count"><?= count($products) ?> Listing<?= count($products) === 1 ? '' : 's' ?></span>
</div>

<div class="product-grid">
  <?php if(empty($products)): ?>
    <div class="empty-state">
      <div style="font-size: 2.5rem; margin-bottom: 0.8rem;">📦</div>
      <h3 style="color:#e2e8f0; font-size:1.2rem; margin-bottom:0.4rem;">No items currently listed in this shop</h3>
      <p style="color:#94a3b8; font-size:0.9rem;">Check back soon or browse other shops on the marketplace.</p>
      <br>
      <a href="/" style="display:inline-block; background:rgba(255,255,255,0.08); color:#fff; padding:0.6rem 1.4rem; border-radius:8px; text-decoration:none; font-weight:700;">🏠 Browse Marketplace</a>
    </div>
  <?php else: ?>
    <?php foreach($products as $p): 
        $isAff = ($p['listing_type'] ?? '') === 'affiliate' || !empty($p['affiliate_url']) || !empty($p['affiliate_link']);
        $destUrl = !empty($p['affiliate_url']) ? $p['affiliate_url'] : ($p['affiliate_link'] ?? '');
        $prodArt = resolveProductArtwork($p['thumb'] ?? '', $p['fallback_img'] ?? '', $shop['business_name'] ?? '', $p['category'] ?? '', $p['title'] ?? '', $p['listing_type'] ?? 'product');
    ?>
    <div class="product-card">
      <div class="product-img-wrap">
        <img src="<?= htmlspecialchars($prodArt) ?>" class="product-img" alt="<?= htmlspecialchars($p['title']) ?>" loading="lazy" onerror="this.onerror=null; this.src='/assets/images/services/default_service.svg';"/>
        <?php if (!empty($p['audio_file'])): ?>
          <span class="badge-pill" style="background: rgba(0, 230, 118, 0.25); color: #00e676; border: 1px solid #00e676;">🎵 Voice / Audio</span>
        <?php else: ?>
          <span class="badge-pill"><?= htmlspecialchars($p['category'] ?? 'Product') ?></span>
        <?php endif; ?>
      </div>
      <div class="product-body">
        <div class="product-title"><?= htmlspecialchars($p['title']) ?></div>
        <div class="product-price-row">
          <div class="product-price">
            <?= $p['price'] > 0 ? number_format($p['price'], 2) . ' BDT' : '<span style="color:#10b981;">🎁 FREE / PROMO</span>' ?>
          </div>
        </div>
        <?php if ($isAff && !empty($destUrl)): ?>
          <a href="<?= htmlspecialchars($destUrl) ?>" target="_blank" rel="noopener noreferrer" style="text-decoration:none;">
            <button class="btn-buy" style="background:linear-gradient(135deg, #10b981, #00b0ff); color:#fff;">🔗 Visit Partner Website ↗</button>
          </a>
        <?php else: ?>
          <a href="/product_detail.php?id=<?= $p['id'] ?>" style="text-decoration:none;">
            <button class="btn-buy">🛒 View &amp; Order (Escrow Safe)</button>
          </a>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<?php 
if (file_exists(__DIR__ . '/includes/whatsapp_button.php')) {
    include_once __DIR__ . '/includes/whatsapp_button.php';
}
?>
<script src="/assets/js/pull_to_refresh.js"></script>
</body>
</html>
