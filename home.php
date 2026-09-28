<?php
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isAdminLoggedIn = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$isUserLoggedIn = isset($_SESSION['user_id']);

$search = trim($_GET['search'] ?? '');
$selectedCategory = trim($_GET['category'] ?? 'All');
$viewType = trim($_GET['type'] ?? 'all');
$selectedDistrict = trim($_GET['district'] ?? '');

$settings = $pdo->query("SELECT setting_key, setting_value FROM homepage_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
$isGeoEnabled = ($settings['feature_geotagging'] ?? 'off') === 'on';
$mobileGridCols = $settings['mobile_grid_cols'] ?? '2';
$mobileGridCss  = ($mobileGridCols === '1') ? '1fr' : 'repeat(2, 1fr)';

// Fetch distinct categories
$categories = [];
try {
    $catQuery = $pdo->query("SELECT DISTINCT category FROM partner_products WHERE is_published = 1 AND category IS NOT NULL AND category != ''");
    if ($catQuery) {
        $categories = $catQuery->fetchAll(PDO::FETCH_COLUMN);
    }
} catch (Exception $e) {}

// Build Query
$where = ["p.is_published = 1", "(COALESCE(pt.status, 'approved') != 'suspended' OR p.partner_id = 0)"];
$params = [];

if ($search !== '') {
    // When searching by keyword: search across all products (including affiliate partner offers)
    $where[] = "(p.title LIKE :search1 OR p.description LIKE :search2 OR p.category LIKE :search3)";
    $params[':search1'] = '%' . $search . '%';
    $params[':search2'] = '%' . $search . '%';
    $params[':search3'] = '%' . $search . '%';
} elseif ($viewType === 'offers' || $viewType === 'affiliate') {
    // When user explicitly clicks "Partner Offers / Deals" filter
    $where[] = "(p.affiliate_url IS NOT NULL AND p.affiliate_url != '')";
} else {
    // Default homepage browsing: strictly genuine direct Escrow products and services
    $where[] = "(p.affiliate_url IS NULL OR p.affiliate_url = '')";
}

if ($selectedCategory !== 'All') {
    $where[] = "p.category = :category";
    $params[':category'] = $selectedCategory;
}

if ($viewType === 'services') {
    $where[] = "p.listing_type = 'service'";
} elseif ($viewType === 'products') {
    $where[] = "p.listing_type = 'product'";
}

if ($isGeoEnabled && $selectedDistrict !== '') {
    $where[] = "(pt.district = :district OR p.partner_id = 0)";
    $params[':district'] = $selectedDistrict;
}

$wsql = implode(' AND ', $where);

$sql = "SELECT p.*, 
        COALESCE(pt.business_name, 'Fast Site Official') AS shop_name, 
        COALESCE(pt.status, 'active') AS shop_status, 
        COALESCE(pt.rating, 5.0) AS shop_rating, 
        COALESCE(pt.is_official, 1) AS is_official, 
        COALESCE(pt.total_orders, 500) AS total_orders, 
        COALESCE(pt.tags, '') AS partner_tags, 
        COALESCE(pt.seller_level, 3) AS seller_level,
        pt.profile_pic AS shop_profile_pic,
        pt.description AS shop_description,
        (SELECT image_url FROM partner_product_images WHERE product_id = p.id AND is_thumbnail = 1 LIMIT 1) AS thumb,
        (SELECT image_url FROM partner_product_images WHERE product_id = p.id ORDER BY id ASC LIMIT 1) AS fallback_img
        FROM partner_products p
        LEFT JOIN partners pt ON p.partner_id = pt.id
        WHERE $wsql
        ORDER BY p.created_at DESC";

$products = [];
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
    
    $all_tags_raw = $pdo->query("SELECT * FROM shop_tags")->fetchAll();
    $all_tags = [];
    foreach($all_tags_raw as $t) {
        $all_tags[$t['id']] = $t;
    }
} catch (Exception $e) {
    error_log("FastSite DB Error: " . $e->getMessage()); die("<div style='text-align:center;padding:4rem;color:#ff5252;font-family:sans-serif;background:#0a0a0f;'><h2>Something went wrong</h2><p>Please try again or contact support.</p><a href='/' style='color:#fcb900;'>← Back to Marketplace</a></div>");
}

// ===============================================================
// FETCH TRENDING PRODUCTS (PHASE 59) — STRICTLY REAL ESCROW PRODUCTS
// ===============================================================
$trending_products = [];
try {
    $t_sql = "SELECT p.*, 
        COUNT(o.id) as order_count,
        COALESCE(pt.business_name, 'Fast Site Official') AS shop_name, 
        COALESCE(pt.status, 'active') AS shop_status, 
        COALESCE(pt.is_official, 1) AS is_official, 
        pt.profile_pic AS shop_profile_pic,
        (SELECT image_url FROM partner_product_images WHERE product_id = p.id AND is_thumbnail = 1 LIMIT 1) AS thumb,
        (SELECT image_url FROM partner_product_images WHERE product_id = p.id ORDER BY id ASC LIMIT 1) AS fallback_img
        FROM partner_products p
        LEFT JOIN partner_orders o ON p.id = o.product_id
        LEFT JOIN partners pt ON p.partner_id = pt.id
        WHERE p.is_published = 1 
          AND (COALESCE(pt.status, 'approved') != 'suspended' OR p.partner_id = 0)
          AND (p.affiliate_url IS NULL OR p.affiliate_url = '')
        GROUP BY p.id
        ORDER BY p.is_trending DESC, order_count DESC, p.created_at DESC
        LIMIT 10";
    $trending_products = $pdo->query($t_sql)->fetchAll();
    
    // Normalize thumbnails for trending
    foreach ($trending_products as $k => $tp) {
        $trending_products[$k]['display_thumb'] = resolveProductArtwork(
            $tp['thumb'] ?? '',
            $tp['fallback_img'] ?? '',
            $tp['shop_name'] ?? '',
            $tp['category'] ?? '',
            $tp['title'] ?? '',
            $tp['listing_type'] ?? 'product'
        );
    }
} catch (Exception $e) {}

// ===============================================================
// REAL DB SPLIT: Group by Shops for Market View
// ===============================================================
$shops = [];

// 1. Pre-populate all active/approved partner shops from database
try {
    $pShopsStmt = $pdo->query("SELECT * FROM partners ORDER BY id ASC");
    $dbShops = $pShopsStmt ? $pShopsStmt->fetchAll(PDO::FETCH_ASSOC) : [];
    foreach ($dbShops as $ds) {
        $status = strtolower($ds['status'] ?? 'approved');
        if ($status === 'suspended' || $status === 'banned') continue;
        if (!empty($ds['is_hidden']) && intval($ds['is_hidden']) === 1) continue;

        $sid = (int)$ds['id'];
        $shops[$sid] = [
            'shop_id'      => $sid,
            'shop_name'    => $ds['business_name'] ?? 'Partner Shop',
            'shop_status'  => $status,
            'shop_rating'  => floatval($ds['rating'] ?? 5.0),
            'is_official'  => intval($ds['is_official'] ?? 0),
            'total_orders' => intval($ds['total_orders'] ?? 0),
            'partner_tags' => $ds['tags'] ?? ($ds['partner_tags'] ?? ''),
            'seller_level' => intval($ds['seller_level'] ?? 1),
            'profile_pic'  => $ds['profile_pic'] ?? null,
            'cover_pic'    => $ds['cover_pic'] ?? null,
            'description'  => $ds['description'] ?? 'Verified Partner Store on Fast Site Marketplace',
            'website_url'  => $ds['website_url'] ?? '',
            'products'     => [],
            'services'     => []
        ];
    }
} catch (Exception $e) {}

// Ensure Official Fast Site shop is indexed
if (!isset($shops[1]) && !isset($shops[0])) {
    $shops[1] = [
        'shop_id'      => 1,
        'shop_name'    => 'Fast Site Official',
        'shop_status'  => 'approved',
        'shop_rating'  => 5.0,
        'is_official'  => 1,
        'total_orders' => 500,
        'partner_tags' => 'Official,Escrow,Verified',
        'seller_level' => 3,
        'profile_pic'  => null,
        'cover_pic'    => null,
        'description'  => 'Official Fast Site Services and Products',
        'website_url'  => '',
        'products'     => [],
        'services'     => []
    ];
}

// 2. Attach products to their respective shops
foreach ($products as $k => $p) {
    $shop_id = (int)($p['partner_id'] ?: 1);
    
    if (!isset($shops[$shop_id])) {
        $shops[$shop_id] = [
            'shop_id'      => $shop_id,
            'shop_name'    => $p['shop_name'],
            'shop_status'  => $p['shop_status'],
            'shop_rating'  => $p['shop_rating'],
            'is_official'  => $p['is_official'],
            'total_orders' => $p['total_orders'],
            'partner_tags' => $p['partner_tags'],
            'seller_level' => $p['seller_level'],
            'profile_pic'  => $p['shop_profile_pic'] ?? null,
            'cover_pic'    => null,
            'description'  => $p['shop_description'] ?? 'Premium Digital Assets & Services',
            'website_url'  => '',
            'products'     => [],
            'services'     => []
        ];
    }
    
    $p['display_thumb'] = resolveProductArtwork(
        $p['thumb'] ?? '',
        $p['fallback_img'] ?? '',
        $p['shop_name'] ?? '',
        $p['category'] ?? '',
        $p['title'] ?? '',
        $p['listing_type'] ?? 'product'
    );
    $products[$k]['display_thumb'] = $p['display_thumb']; // SAVE BACK TO ARRAY

    if (($p['listing_type'] ?? 'product') === 'service') {
        $shops[$shop_id]['services'][] = $p;
    } else {
        $shops[$shop_id]['products'][] = $p;
    }
}


$coin_name    = getPartnerSetting('coin_name', 'Fast Points');
$exchange_rate = floatval(getPartnerSetting('exchange_rate', '1'));

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover"/>
  <meta name="apple-mobile-web-app-capable" content="yes"/>
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent"/>
  <meta name="theme-color" content="#0A0D1A"/>
  <title>Unified Marketplace — Fast Site</title>
  <link rel="stylesheet" href="/assets/css/native_mobile.css"/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Oswald:wght@600;700&display=swap" rel="stylesheet"/>
  <style>
    :root {
      --gold: #fcb900;
      --brand: #fcb900;
      --gold-glow: rgba(252, 185, 0, 0.2);
      --brand-glow: rgba(252, 185, 0, 0.2);
      --blue: #3b82f6;
      --blue-glow: rgba(59, 130, 246, 0.2);
      --dark: #0a0a0f;
      --dark-card: rgba(18, 18, 26, 0.65);
      --surface: rgba(13, 13, 20, 0.95);
      --border: rgba(255, 255, 255, 0.06);
      --text: #f8f8f8;
      --muted: #9ca3af;
      --green: #10b981;
      --red: #ef4444;
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    
    body {
    overflow-x: hidden;
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
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
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

    .btn-nav.active {
      color: var(--gold);
      background: rgba(252, 185, 0, 0.12);
      border-color: var(--border);
    }

    .btn-wallet {
      background: rgba(33, 150, 243, 0.1);
      border: 1px solid rgba(33, 150, 243, 0.25);
      color: var(--blue);
    }
    
    .btn-wallet:hover {
      background: var(--blue);
      color: #000;
      box-shadow: 0 4px 12px var(--blue-glow);
    }

    /* ================= MAIN CONTENT & STITCH COMMAND HUB ================= */
    .hero {
      text-align: center;
      padding: 3rem 1.2rem 2rem;
      background: radial-gradient(circle at top, rgba(252, 185, 0, 0.12) 0%, rgba(16, 19, 32, 0.4) 50%, transparent 100%);
      border-bottom: 1px solid rgba(255, 255, 255, 0.06);
      position: relative;
      overflow: hidden;
    }
    
    .hero::before {
      content: '';
      position: absolute;
      top: -50%;
      left: -50%;
      width: 200%;
      height: 200%;
      background: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23fcb900\' fill-opacity=\'0.03\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');
      z-index: 0;
      pointer-events: none;
    }

    .hero-tagline {
      display: inline-block;
      background: rgba(252, 185, 0, 0.12);
      color: var(--gold);
      padding: 0.35rem 0.9rem;
      border-radius: 50px;
      font-size: 0.76rem;
      font-weight: 800;
      letter-spacing: 1.5px;
      text-transform: uppercase;
      margin-bottom: 0.8rem;
      border: 1px solid rgba(252, 185, 0, 0.25);
      position: relative;
      z-index: 1;
    }

    .hero-title, .hero h1 {
      font-family: 'Oswald', 'Sora', sans-serif;
      font-size: 2.3rem;
      font-weight: 900;
      line-height: 1.18;
      background: linear-gradient(135deg, #ffffff 60%, #fcb900 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      margin-bottom: 0.5rem;
      position: relative;
      z-index: 1;
      text-shadow: 0 10px 30px rgba(252, 185, 0, 0.15);
    }

    .hero-subtitle, .hero p {
      font-size: 0.95rem;
      color: var(--muted);
      max-width: 580px;
      margin: 0 auto 1.3rem;
      position: relative;
      z-index: 1;
      line-height: 1.45;
    }

    /* Search Command Hub */
    .search-command-hub, .search-filter-container {
      max-width: 720px;
      margin: 0 auto 1.1rem;
      position: relative;
      width: 100%;
      z-index: 2;
    }
    .search-command-form, .search-form {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      position: relative;
      width: 100%;
    }
    .search-input-box {
      flex: 1;
      min-width: 0;
      position: relative;
      display: flex;
      align-items: center;
      background: rgba(18, 22, 43, 0.85);
      backdrop-filter: blur(14px);
      -webkit-backdrop-filter: blur(14px);
      border: 1px solid rgba(255, 255, 255, 0.12);
      border-radius: 50px;
      padding: 0 0.8rem 0 2.6rem;
      height: 50px;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.45);
      transition: all 0.25s ease;
    }
    .search-input-box:focus-within {
      border-color: #fcb900;
      box-shadow: 0 0 20px rgba(252, 185, 0, 0.3), inset 0 0 8px rgba(252, 185, 0, 0.1);
    }
    .search-lens-icon, .search-icon {
      position: absolute;
      left: 1rem;
      color: #94a3b8;
      font-size: 1.05rem;
      pointer-events: none;
    }
    .search-command-input, .search-form input {
      width: 100%;
      background: transparent;
      border: none;
      color: #fff;
      font-size: 0.92rem;
      font-family: 'Inter', sans-serif;
      outline: none;
    }
    .search-clear-trigger {
      color: #94a3b8;
      font-size: 1.3rem;
      text-decoration: none;
      padding: 0 0.4rem;
      line-height: 1;
      cursor: pointer;
      transition: color 0.2s;
    }
    .search-clear-trigger:hover {
      color: #ff5252;
    }

    /* Integrated Filter Trigger Button */
    .btn-filter-trigger {
      height: 50px;
      padding: 0 1.2rem;
      border-radius: 50px;
      background: rgba(18, 22, 43, 0.9);
      border: 1px solid rgba(255, 255, 255, 0.14);
      color: #fff;
      font-weight: 700;
      font-size: 0.86rem;
      display: flex;
      align-items: center;
      gap: 0.45rem;
      cursor: pointer;
      white-space: nowrap;
      flex-shrink: 0;
      position: relative;
      box-shadow: 0 6px 20px rgba(0, 0, 0, 0.35);
      transition: all 0.2s ease;
    }
    .btn-filter-trigger:hover {
      background: rgba(26, 32, 59, 0.95);
      border-color: #fcb900;
      color: #fcb900;
    }
    .btn-filter-trigger.active {
      background: linear-gradient(135deg, rgba(252, 185, 0, 0.2), rgba(255, 145, 0, 0.1));
      border-color: #fcb900;
      color: #fcb900;
    }
    .filter-badge-dot {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: #00e676;
      box-shadow: 0 0 8px #00e676;
    }

    /* Quick Types Ribbon */
    .quick-types-ribbon {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
      overflow-x: auto;
      padding: 0.2rem 0.5rem;
      max-width: 780px;
      margin: 0 auto;
      -webkit-overflow-scrolling: touch;
      scrollbar-width: none;
      position: relative;
      z-index: 1;
    }
    .quick-types-ribbon::-webkit-scrollbar {
      display: none;
    }
    .type-pill, .segment-btn {
      background: rgba(255, 255, 255, 0.04);
      border: 1px solid rgba(255, 255, 255, 0.08);
      color: #94a3b8;
      padding: 0.42rem 0.85rem;
      border-radius: 50px;
      font-size: 0.78rem;
      font-weight: 700;
      text-decoration: none;
      white-space: nowrap;
      transition: all 0.2s ease;
      display: inline-flex;
      align-items: center;
      gap: 4px;
      flex-shrink: 0;
      cursor: pointer;
    }
    .type-pill:hover, .segment-btn:hover {
      color: #fff;
      background: rgba(255, 255, 255, 0.08);
      border-color: rgba(255, 255, 255, 0.16);
    }
    .type-pill.active, .segment-btn.active {
      background: linear-gradient(135deg, #fcb900, #ff8a00);
      color: #080911;
      border-color: transparent;
      font-weight: 800;
      box-shadow: 0 4px 15px rgba(252, 185, 0, 0.3);
    }
    .category-trigger-pill {
      background: rgba(33, 150, 243, 0.1);
      border-color: rgba(33, 150, 243, 0.3);
      color: #3b82f6;
    }
    .category-trigger-pill:hover {
      background: rgba(33, 150, 243, 0.2);
      border-color: #3b82f6;
      color: #fff;
    }
    .category-trigger-pill.active {
      background: rgba(33, 150, 243, 0.22);
      border-color: #3b82f6;
      color: #60a5fa;
      box-shadow: 0 0 12px rgba(59, 130, 246, 0.3);
    }

    /* Universal Category Drawer & Scrim */
    .drawer-scrim, .modal-overlay {
      position: fixed;
      inset: 0;
      background: rgba(4, 6, 12, 0.75);
      backdrop-filter: blur(8px);
      -webkit-backdrop-filter: blur(8px);
      z-index: 2000;
      opacity: 0;
      pointer-events: none;
      transition: opacity 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .drawer-scrim.active, .modal-overlay.active {
      opacity: 1;
      pointer-events: auto;
    }

    .category-drawer {
      position: fixed;
      bottom: 0;
      left: 0;
      right: 0;
      max-height: 85vh;
      background: rgba(12, 15, 26, 0.98);
      backdrop-filter: blur(24px);
      -webkit-backdrop-filter: blur(24px);
      border-top: 1px solid rgba(252, 185, 0, 0.3);
      border-radius: 24px 24px 0 0;
      z-index: 2001;
      display: flex;
      flex-direction: column;
      box-shadow: 0 -15px 50px rgba(0, 0, 0, 0.8), 0 0 25px rgba(252, 185, 0, 0.1);
      transform: translateY(100%);
      transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1);
      box-sizing: border-box;
      overflow: hidden;
    }
    .category-drawer.active {
      transform: translateY(0);
    }

    @media (min-width: 768px) {
      .category-drawer {
        top: 50%;
        left: 50%;
        bottom: auto;
        right: auto;
        width: 90%;
        max-width: 580px;
        max-height: 80vh;
        border-radius: 20px;
        border: 1px solid rgba(252, 185, 0, 0.25);
        transform: translate(-50%, -40%) scale(0.96);
        opacity: 0;
        pointer-events: none;
        transition: transform 0.25s ease, opacity 0.25s ease;
      }
      .category-drawer.active {
        transform: translate(-50%, -50%) scale(1);
        opacity: 1;
        pointer-events: auto;
      }
      .drawer-handle-bar {
        display: none !important;
      }
    }

    .drawer-handle-bar {
      width: 100%;
      padding: 10px 0 4px;
      display: flex;
      justify-content: center;
      cursor: grab;
    }
    .drawer-drag-pill {
      width: 44px;
      height: 5px;
      border-radius: 50px;
      background: rgba(255, 255, 255, 0.2);
    }

    .category-drawer-header {
      padding: 0.8rem 1.4rem 1rem;
      border-bottom: 1px solid rgba(255, 255, 255, 0.06);
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .drawer-title {
      font-family: 'Oswald', 'Sora', sans-serif;
      font-size: 1.2rem;
      font-weight: 800;
      color: #fff;
      margin: 0;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .drawer-subtitle {
      font-size: 0.78rem;
      color: var(--muted);
      margin: 2px 0 0;
    }
    .drawer-close-btn, .close-modal {
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.1);
      color: #fff;
      width: 32px;
      height: 32px;
      border-radius: 50%;
      font-size: 1.2rem;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: all 0.2s;
    }
    .drawer-close-btn:hover, .close-modal:hover {
      background: rgba(255, 82, 82, 0.2);
      color: #ff5252;
    }

    .category-drawer-body {
      padding: 1.2rem 1.4rem;
      overflow-y: auto;
      flex: 1;
      -webkit-overflow-scrolling: touch;
    }
    .drawer-section {
      margin-bottom: 1.4rem;
    }
    .section-label-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 0.7rem;
    }
    .section-label {
      font-size: 0.76rem;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      color: #fcb900;
    }
    .section-count {
      font-size: 0.72rem;
      color: var(--muted);
    }

    .type-card-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 0.5rem;
    }
    .type-card-label {
      cursor: pointer;
      margin: 0;
    }
    .type-card-label.full-width {
      grid-column: 1 / -1;
    }
    .type-card-label input {
      display: none;
    }
    .type-card, .filter-radio-card {
      background: rgba(255, 255, 255, 0.03);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 12px;
      padding: 0.7rem 0.8rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
      color: #cbd5e1;
      font-size: 0.82rem;
      font-weight: 700;
      transition: all 0.2s ease;
      user-select: none;
    }
    .type-card.active, .filter-radio-card.active, input[type="radio"]:checked + .filter-radio-card {
      background: rgba(252, 185, 0, 0.15);
      border-color: rgba(252, 185, 0, 0.5);
      color: #fcb900;
      box-shadow: 0 0 12px rgba(252, 185, 0, 0.2);
    }

    .categories-visual-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 0.6rem;
      max-height: 240px;
      overflow-y: auto;
      padding: 0.2rem;
    }
    .cat-card-label {
      cursor: pointer;
      margin: 0;
    }
    .cat-card-label input {
      display: none;
    }
    .cat-visual-card {
      background: rgba(18, 22, 43, 0.8);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 12px;
      padding: 0.75rem 0.8rem;
      display: flex;
      align-items: center;
      gap: 0.6rem;
      position: relative;
      transition: all 0.2s ease;
    }
    .cat-icon-wrap {
      width: 32px;
      height: 32px;
      border-radius: 8px;
      background: rgba(255, 255, 255, 0.04);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.1rem;
      flex-shrink: 0;
    }
    .cat-info {
      flex: 1;
      min-width: 0;
    }
    .cat-name {
      display: block;
      font-size: 0.8rem;
      font-weight: 700;
      color: #fff;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .cat-sub {
      display: block;
      font-size: 0.68rem;
      color: var(--muted);
    }
    .cat-check {
      display: none;
      color: #00e676;
      font-weight: 900;
      font-size: 0.9rem;
    }
    .cat-visual-card.active {
      background: rgba(0, 230, 118, 0.08);
      border-color: rgba(0, 230, 118, 0.5);
    }
    .cat-visual-card.active .cat-name {
      color: #00e676;
    }
    .cat-visual-card.active .cat-check {
      display: inline-block;
    }

    .drawer-select {
      width: 100%;
      background: rgba(18, 22, 43, 0.8);
      color: #fff;
      border: 1px solid rgba(255, 255, 255, 0.12);
      border-radius: 10px;
      padding: 0.75rem 1rem;
      font-size: 0.88rem;
      outline: none;
    }

    .category-drawer-footer {
      padding: 0.9rem 1.4rem;
      border-top: 1px solid rgba(255, 255, 255, 0.06);
      background: rgba(10, 13, 22, 0.95);
      display: flex;
      gap: 0.8rem;
    }
    .btn-drawer-reset {
      flex: 1;
      padding: 0.8rem 1rem;
      border-radius: 12px;
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.1);
      color: #94a3b8;
      text-align: center;
      font-weight: 700;
      font-size: 0.85rem;
      text-decoration: none;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.2s;
    }
    .btn-drawer-reset:hover {
      color: #fff;
      background: rgba(255, 255, 255, 0.1);
    }
    .btn-drawer-apply {
      flex: 2;
      padding: 0.8rem 1.2rem;
      border-radius: 12px;
      background: linear-gradient(135deg, #fcb900, #ff8a00);
      border: none;
      color: #080911;
      font-weight: 900;
      font-size: 0.9rem;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.4rem;
      box-shadow: 0 4px 15px rgba(252, 185, 0, 0.35);
      transition: all 0.2s;
    }
    .btn-drawer-apply:active {
      transform: scale(0.98);
    }

    /* Responsive Mobile Queries */
    @media (max-width: 600px) {
      .hero {
        padding: 1.8rem 0.8rem 1.4rem;
      }
      .hero-title, .hero h1 {
        font-size: 1.55rem;
        margin-bottom: 0.35rem;
      }
      .hero-subtitle, .hero p {
        font-size: 0.82rem;
        margin-bottom: 1rem;
      }
      .search-input-box {
        height: 46px;
        padding-left: 2.3rem;
        font-size: 0.86rem;
      }
      .btn-filter-trigger {
        height: 46px;
        padding: 0 0.85rem;
        font-size: 0.78rem;
        gap: 0.3rem;
      }
      .quick-types-ribbon {
        justify-content: flex-start;
        padding: 0.3rem 0.2rem;
      }
      .categories-visual-grid {
        grid-template-columns: 1fr;
      }
    }
    
    .trust-badges {
      display: flex;
      justify-content: center;
      gap: 1rem;
      margin-bottom: 2rem;
      flex-wrap: wrap;
    }
    .trust-badge {
      display: flex;
      align-items: center;
      gap: 0.4rem;
      font-size: 0.72rem;
      font-weight: 700;
      color: var(--muted);
      background: rgba(255,255,255,0.03);
      padding: 0.4rem 0.8rem;
      border-radius: 8px;
      border: 1px solid rgba(255,255,255,0.05);
    }

    /* ================= PRODUCT/SHOP GRID ================= */
    .grid-section {
      max-width: 1200px;
      margin: 0 auto;
      padding: 1rem 1.5rem 4rem;
    }

    /* ================= PRODUCT/SHOP GRID ================= */
    .grid-section {
      max-width: 1200px;
      margin: 0 auto;
      padding: 1rem 1.5rem 4rem;
    }

    .shop-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 1.5rem;
    }
    @media (max-width: 992px) {
      .shop-grid {
        grid-template-columns: repeat(2, 1fr);
      }
    }
    @media (max-width: 600px) {
      .shop-grid {
        grid-template-columns: <?= $mobileGridCss ?>;
        gap: 0.8rem;
      }
    }

    .product-grid-4 {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 1.2rem;
      width: 100%;
      max-width: 1260px;
      margin: 0 auto;
      padding: 0 1rem;
    }
    @media (max-width: 1024px) { .product-grid-4 { grid-template-columns: repeat(3, 1fr); } }
    @media (max-width: 720px)  { .product-grid-4 { grid-template-columns: repeat(2, 1fr); gap: 0.8rem; padding: 0 0.5rem; } }
    @media (max-width: 500px)  { .product-grid-4 { grid-template-columns: <?= $mobileGridCss ?>; gap: 0.6rem; padding: 0 0.4rem; } }

    .shop-card {
      background: rgba(20, 20, 31, 0.85);
      border: 1px solid rgba(255, 255, 255, 0.05);
      border-radius: 16px;
      padding: 1.5rem;
      transition: all 0.3s ease;
      display: flex;
      flex-direction: column;
      gap: 1rem;
      position: relative;
      overflow: hidden;
      box-shadow: 0 4px 20px rgba(0,0,0,0.3);
    }
    .shop-card::before {
      content: '';
      position: absolute; top: 0; left: 0; right: 0; height: 2px;
      background: linear-gradient(90deg, transparent, var(--gold), transparent);
      opacity: 0; transition: opacity 0.3s ease;
    }
    .shop-card:hover::before { opacity: 1; }
    .shop-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 12px 40px rgba(0,0,0,0.5);
      border-color: rgba(252, 185, 0, 0.4);
    }
    
    .shop-header {
      display: flex;
      gap: 1rem;
      align-items: center;
      border-bottom: 1px solid rgba(255,255,255,0.05);
      padding-bottom: 1rem;
    }
    .shop-logo {
      width: 48px;
      height: 48px;
      border-radius: 50%;
      border: 1px solid rgba(252,185,0,0.3);
      object-fit: cover;
    }
    .shop-title-area {
      flex: 1;
    }
    .shop-name {
      font-size: 1.1rem;
      font-weight: 800;
      color: #fff;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    .shop-tagline {
      font-size: 0.75rem;
      color: var(--muted);
      margin-top: 0.2rem;
    }
    
    /* ============= SHOP CARD INNER PRODUCT LIST ============= */
    .portfolio-grid {
      display: flex;
      flex-direction: column;
      gap: 0.6rem;
    }
    .portfolio-item {
      display: flex;
      gap: 0.75rem;
      align-items: center;
      background: rgba(0,0,0,0.25);
      border: 1px solid rgba(255,255,255,0.04);
      padding: 0.5rem 0.6rem;
      border-radius: 10px;
      text-decoration: none;
      transition: all 0.2s;
    }
    .portfolio-item:hover {
      background: rgba(252,185,0,0.05);
      border-color: rgba(252,185,0,0.2);
    }
    .portfolio-img {
      width: 44px;
      height: 44px;
      border-radius: 8px;
      object-fit: cover;
      flex-shrink: 0;
      border: 1px solid rgba(255,255,255,0.06);
    }
    .portfolio-details {
      flex: 1;
      overflow: hidden;
    }
    .portfolio-title {
      color: #fff;
      font-size: 0.82rem;
      font-weight: 600;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .portfolio-price {
      color: var(--gold);
      font-size: 0.72rem;
      font-weight: 800;
      margin-top: 0.15rem;
    }

    /* ============= PREMIUM 4-COLUMN PRODUCT GRID ============= */
    .product-grid-4 {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 1.2rem;
      width: 100%;
      max-width: 1260px;
      margin: 0 auto;
      padding: 0 1rem;
    }
    @media (max-width: 1024px) { .product-grid-4 { grid-template-columns: repeat(3, 1fr); gap: 1rem; } }
    @media (max-width: 768px)  { 
      .product-grid-4 { 
        grid-template-columns: repeat(2, 1fr) !important; 
        gap: 0.65rem !important; 
        padding: 0 0.4rem !important; 
      } 
    }

    .pcard {
      background: linear-gradient(145deg, rgba(20,20,32,0.95), rgba(14,14,22,0.98));
      border: 1px solid rgba(255,255,255,0.06);
      border-radius: 18px;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      text-decoration: none;
      transition: all 0.35s cubic-bezier(0.25, 0.8, 0.25, 1);
      position: relative;
      box-shadow: 0 4px 20px rgba(0,0,0,0.35);
    }
    .pcard:hover {
      transform: translateY(-6px) scale(1.01);
      box-shadow: 0 16px 40px rgba(0,0,0,0.55), 0 0 0 1px rgba(252,185,0,0.25);
      border-color: rgba(252,185,0,0.3);
    }
    .pcard-img-wrap {
      width: 100%;
      aspect-ratio: 4/3;
      overflow: hidden;
      position: relative;
      background: #0d0d15;
    }
    .pcard-img-wrap img {
      width: 100%; height: 100%;
      object-fit: cover;
      transition: transform 0.55s ease;
      color: transparent;
    }
    .pcard-share-btn {
      position: absolute;
      top: 0.65rem; right: 0.65rem;
      background: rgba(0,0,0,0.7);
      backdrop-filter: blur(8px);
      border: 1px solid rgba(255,255,255,0.18);
      color: #fff;
      font-size: 0.75rem;
      width: 30px; height: 30px;
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      cursor: pointer;
      z-index: 10;
      transition: all 0.25s ease;
      padding: 0;
      line-height: 1;
    }
    .pcard-share-btn:hover {
      background: var(--gold);
      border-color: var(--gold);
      color: #000;
      transform: scale(1.15);
      box-shadow: 0 0 12px rgba(252,185,0,0.6);
    }
    .pcard:hover .pcard-img-wrap img { transform: scale(1.08); }
    .pcard-badge {
      position: absolute;
      top: 0.7rem; left: 0.7rem;
      background: rgba(0,0,0,0.65);
      backdrop-filter: blur(8px);
      border: 1px solid rgba(255,255,255,0.12);
      color: #fff;
      font-size: 0.6rem;
      font-weight: 700;
      padding: 0.25rem 0.55rem;
      border-radius: 50px;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }
    .pcard-badge.service { border-color: rgba(59,130,246,0.4); color: var(--blue); }
    .pcard-body {
      padding: 0.9rem 1rem;
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
      flex: 1;
    }
    .pcard-shop {
      font-size: 0.65rem;
      font-weight: 700;
      color: var(--muted);
      text-transform: uppercase;
      letter-spacing: 0.08em;
      display: flex;
      align-items: center;
      gap: 0.3rem;
    }
    .pcard-shop .dot { width: 5px; height: 5px; border-radius: 50%; background: var(--green); display: inline-block; }
    .pcard-title {
      color: #f0f0f0;
      font-size: 0.88rem;
      font-weight: 700;
      line-height: 1.35;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }
    .pcard-footer {
      padding: 0.65rem 1rem;
      border-top: 1px solid rgba(255,255,255,0.04);
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .pcard-price {
      font-size: 0.9rem;
      font-weight: 900;
      color: var(--gold);
      letter-spacing: -0.02em;
    }
    .pcard-action {
      font-size: 0.65rem;
      font-weight: 700;
      color: var(--muted);
      background: rgba(255,255,255,0.04);
      border: 1px solid rgba(255,255,255,0.07);
      padding: 0.3rem 0.6rem;
      border-radius: 50px;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      transition: all 0.2s;
    }
    .pcard:hover .pcard-action {
      background: rgba(252,185,0,0.12);
      border-color: rgba(252,185,0,0.35);
      color: var(--gold);
    }

    .product-card {
      background: var(--dark-card);
      border: 1px solid rgba(255, 255, 255, 0.05);
      border-radius: 16px;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      position: relative;
    }

    .product-card::before {
      content: '';
      position: absolute;
      inset: 0;
      border-radius: 16px;
      padding: 1px;
      background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.02));
      -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
      -webkit-mask-composite: xor;
      mask-composite: exclude;
      pointer-events: none;
      transition: background 0.3s;
    }

    .product-card:hover {
      transform: translateY(-6px);
      box-shadow: 0 12px 30px rgba(0, 0, 0, 0.5);
    }

    .product-card:hover::before {
      background: linear-gradient(135deg, rgba(0, 230, 118, 0.3), rgba(33, 150, 243, 0.1));
    }

    /* Product Thumbnail */
    .card-img-wrap {
      width: 100%;
      height: 180px;
      overflow: hidden;
      position: relative;
      background: #101018;
    }

    .card-img-wrap img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.5s;
    }

    .product-card:hover .card-img-wrap img {
      transform: scale(1.06);
    }

    .badge-category {
      position: absolute;
      top: 0.8rem;
      left: 0.8rem;
      background: rgba(0, 0, 0, 0.7);
      backdrop-filter: blur(8px);
      border: 1px solid rgba(255, 255, 255, 0.15);
      color: #fff;
      font-size: 0.65rem;
      font-weight: 700;
      padding: 0.3rem 0.6rem;
      border-radius: 50px;
      text-transform: uppercase;
    }

    .badge-rating {
      position: absolute;
      top: 0.8rem;
      right: 0.8rem;
      background: rgba(252, 185, 0, 0.15);
      backdrop-filter: blur(8px);
      border: 1px solid rgba(252, 185, 0, 0.3);
      color: var(--gold);
      font-size: 0.68rem;
      font-weight: 700;
      padding: 0.3rem 0.6rem;
      border-radius: 50px;
      display: inline-flex;
      align-items: center;
      gap: 3px;
    }

    /* Card Details */
    .card-body {
      padding: 1.2rem;
      display: flex;
      flex-direction: column;
      flex: 1;
    }

    .shop-name {
      font-size: 0.7rem;
      font-weight: 700;
      text-transform: uppercase;
      color: var(--muted);
      letter-spacing: 0.05em;
      margin-bottom: 0.3rem;
      display: inline-flex;
      align-items: center;
      gap: 4px;
    }

    .verified-icon {
      color: var(--green);
      font-size: 0.75rem;
    }

    .product-title {
      font-size: 0.95rem;
      font-weight: 700;
      color: #fff;
      margin-bottom: 0.8rem;
      line-height: 1.4;
      text-overflow: ellipsis;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
      height: 2.7rem;
    }

    .card-footer {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-top: auto;
      border-top: 1px solid rgba(255, 255, 255, 0.04);
      padding-top: 0.8rem;
    }

    .price-block {
      display: flex;
      flex-direction: column;
    }

    .price-coins {
      font-size: 1.1rem;
      font-weight: 800;
      color: var(--green);
      display: inline-flex;
      align-items: center;
      gap: 4px;
    }

    .price-bdt {
      font-size: 0.68rem;
      color: var(--muted);
    }

    .card-actions {
      display: flex;
      gap: 0.4rem;
      align-items: center;
    }

    .btn-share {
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.1);
      color: #fff;
      border-radius: 8px;
      padding: 0.5rem 0.6rem;
      font-size: 0.8rem;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 0.3rem;
      transition: all 0.2s;
      text-decoration: none;
    }
    .btn-share:hover {
      background: rgba(0, 230, 118, 0.15);
      border-color: rgba(0, 230, 118, 0.3);
      color: var(--green);
    }

    .btn-buy {
      background: linear-gradient(135deg, var(--green), #00b0ff);
      color: #000;
      font-weight: 800;
      text-decoration: none;
      padding: 0.5rem 1rem;
      border-radius: 50px;
      font-size: 0.78rem;
      transition: all 0.2s;
      box-shadow: 0 4px 10px rgba(0, 230, 118, 0.15);
    }

    .btn-buy:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 15px rgba(0, 230, 118, 0.3);
      filter: brightness(1.1);
    }

    /* Empty state */
    .empty-state {
      text-align: center;
      padding: 4rem 2rem;
      background: var(--dark-card);
      border: 1px dashed rgba(255, 255, 255, 0.08);
      border-radius: 16px;
      max-width: 600px;
      margin: 2rem auto;
    }

    .empty-state h3 {
      font-size: 1.2rem;
      color: var(--gold);
      margin-bottom: 0.5rem;
    }

    .empty-state p {
      font-size: 0.85rem;
      color: var(--muted);
    }

    /* ================= FOOTER ================= */
    .footer {
      text-align: center;
      padding: 2rem;
      border-top: 1px solid rgba(255, 255, 255, 0.05);
      margin-top: 2rem;
      background: #06060a;
    }

    .footer p {
      font-size: 0.8rem;
      color: var(--muted);
    }

    /* Mobile adjustments */
    @media (max-width: 600px) {
      .top-header { padding: 0.8rem 1rem; }
      .brand { font-size: 1.2rem; }
      .hero h1 { font-size: 1.8rem; }
      .product-grid { grid-template-columns: repeat(auto-fill, minmax(145px, 1fr)); gap: 0.8rem; }
      .grid-section { padding: 0.5rem 0.8rem 3rem; }
      .card-img-wrap { height: 110px; }
      .product-title { font-size: 0.8rem; height: 2.2rem; margin-bottom: 0.4rem; -webkit-line-clamp: 2; }
      .card-body { padding: 0.8rem; }
      .price-coins { font-size: 0.9rem; }
      .btn-buy { padding: 0.4rem 0.7rem; font-size: 0.7rem; }
      .badge-category, .badge-rating { font-size: 0.58rem; padding: 0.2rem 0.4rem; }
    }

    /* Dynamic Upload Button */
    .dynamic-upload-btn {
      display: inline-flex;
      align-items: center;
      gap: 1rem;
      background: linear-gradient(135deg, var(--brand), #ff9100);
      color: #000;
      padding: 0.8rem 1.8rem;
      border-radius: 50px;
      text-decoration: none;
      box-shadow: 0 8px 25px rgba(252, 185, 0, 0.4);
      transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
      border: 2px solid rgba(255,255,255,0.2);
    }
    .dynamic-upload-btn:hover {
      transform: translateY(-5px) scale(1.05);
      box-shadow: 0 12px 35px rgba(252, 185, 0, 0.6);
    }
    .dynamic-upload-btn .btn-icon {
      font-size: 2rem;
      filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
    }
    .dynamic-upload-btn .btn-text {
      text-align: left;
      display: flex;
      flex-direction: column;
    }
    .dynamic-upload-btn .btn-text strong {
      font-size: 1.2rem;
      font-weight: 900;
      letter-spacing: 0.5px;
      line-height: 1.1;
    }
    .dynamic-upload-btn .btn-text span {
      font-size: 0.75rem;
      font-weight: 600;
      opacity: 0.8;
    }
    @media (max-width: 600px) {
      .dynamic-upload-btn { padding: 0.6rem 1.2rem; }
      .dynamic-upload-btn .btn-text strong { font-size: 1rem; }
      .dynamic-upload-btn .btn-icon { font-size: 1.6rem; }
    }
  </style>
</head>
<body>
<?php include __DIR__ . '/includes/nav_public.php'; ?>

<?php if ($isAdminLoggedIn): ?>
  <!-- Admin View Bar -->
  <div style="background: rgba(252, 185, 0, 0.15); border-bottom: 1px solid rgba(252, 185, 0, 0.3); padding: 0.5rem 1rem; display: flex; justify-content: space-between; align-items: center; font-size: 0.82rem; z-index: 1001; position: relative;">
    <span style="color: #fcb900; display: inline-flex; align-items: center; gap: 6px;">
      <span style="display: inline-block; width: 8px; height: 8px; background: #00e676; border-radius: 50%; box-shadow: 0 0 8px #00e676; animation: pulse 1.5s infinite;"></span>
      Logged in as Administrator (Staff)
    </span>
    <a href="/admin/dashboard.php" style="background: #fcb900; color: #000; font-weight: 700; text-decoration: none; padding: 3px 10px; border-radius: 4px; transition: transform 0.2s;">⚙️ Admin Panel</a>
  </div>
  <style>
    @keyframes pulse {
      0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(0, 230, 118, 0.7); }
      70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(0, 230, 118, 0); }
      100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(0, 230, 118, 0); }
    }
  </style>
<?php endif; ?>

<?php
// PROMOTION BANNER LOGIC (Similar to old ex-homepage)
$show_banner = $settings['banner_type'] ?? 'none';
if ($show_banner !== 'none'):
?>
<div class="promo-banner" style="background: var(--surface); padding: 1rem; border-bottom: 1px solid rgba(255,255,255,0.05); text-align: center;">
    <?php if ($show_banner === 'image' && !empty($settings['banner_image'])): ?>
        <a href="<?= htmlspecialchars($settings['banner_redirect_url'] ?? '#') ?>">
            <img src="/uploads/<?= htmlspecialchars($settings['banner_image']) ?>" alt="Promo" style="max-width: 100%; border-radius: 12px; height: auto;">
        </a>
    <?php elseif ($show_banner === 'text' && !empty($settings['banner_text'])): ?>
        <a href="<?= htmlspecialchars($settings['banner_redirect_url'] ?? '#') ?>" style="color: var(--brand); text-decoration: none; font-weight: bold; font-size: 1.1rem;">
            <?= htmlspecialchars($settings['banner_text']) ?>
        </a>
    <?php elseif ($show_banner === 'custom' && !empty($settings['banner_custom_html'])): ?>
        <?= $settings['banner_custom_html'] ?>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="hero">
  <?php if ($is_user_logged_in): ?>
      <span class="hero-tagline">✨ Welcome back, <?= htmlspecialchars($user_name) ?>!</span>
  <?php else: ?>
      <span class="hero-tagline">✨ Verified Digital Escrow Marketplace</span>
  <?php endif; ?>

  <h1 class="hero-title">BUY &amp; SELL PREMIUM DIGITAL ASSETS SECURELY</h1>
  <p class="hero-subtitle">Join the Fast Site ecosystem. Purchase high-quality digital products safely using coin escrow, or become a seller and earn massive commissions!</p>

  <!-- Prominent Search Command Bar -->
  <div class="search-command-hub">
    <form class="search-command-form" method="GET" action="index.php" id="omniSearchForm">
      <div class="search-input-box">
        <span class="search-lens-icon">🔍</span>
        <input type="text" name="search" id="omniSearchInput" autocomplete="off"
               placeholder="Search products, services, shops..." 
               value="<?= htmlspecialchars($search) ?>" 
               class="search-command-input"/>
        <?php if (!empty($search)): ?>
          <a href="index.php" class="search-clear-trigger" title="Clear search">&times;</a>
        <?php endif; ?>
      </div>

      <!-- Integrated Filters Trigger Button -->
      <button type="button" class="btn-filter-trigger <?= ($selectedCategory !== 'All' || $viewType !== 'all') ? 'active' : '' ?>" 
              onclick="toggleCategoryDrawer(true)" title="Filter by Category or Type">
        <span class="filter-icon">⚙️</span>
        <span class="filter-label">Filters</span>
        <?php if ($selectedCategory !== 'All' || $viewType !== 'all'): ?>
          <span class="filter-badge-dot"></span>
        <?php endif; ?>
      </button>

      <!-- Omni Search Live Dropdown -->
      <div id="omniDropdown" class="omni-dropdown" style="display:none;"></div>
      
      <?php if ($selectedCategory !== 'All'): ?>
        <input type="hidden" name="category" id="hiddenCategoryInput" value="<?= htmlspecialchars($selectedCategory) ?>"/>
      <?php endif; ?>
      <?php if ($viewType !== 'all'): ?>
        <input type="hidden" name="type" id="hiddenTypeInput" value="<?= htmlspecialchars($viewType) ?>"/>
      <?php endif; ?>
      <?php if ($isGeoEnabled && !empty($selectedDistrict)): ?>
        <input type="hidden" name="district" id="hiddenDistrictInput" value="<?= htmlspecialchars($selectedDistrict) ?>"/>
      <?php endif; ?>
    </form>
  </div>

  <!-- Horizontal Quick-Type Segment Ribbon -->
  <div class="quick-types-ribbon no-scrollbar">
    <a href="index.php?type=all<?= $search ? '&search='.urlencode($search) : '' ?><?= $selectedCategory !== 'All' ? '&category='.urlencode($selectedCategory) : '' ?>" 
       class="type-pill <?= ($viewType === 'all' || empty($viewType)) ? 'active' : '' ?>">
       📦 All
    </a>
    <a href="index.php?type=products<?= $search ? '&search='.urlencode($search) : '' ?><?= $selectedCategory !== 'All' ? '&category='.urlencode($selectedCategory) : '' ?>" 
       class="type-pill <?= $viewType === 'products' ? 'active' : '' ?>">
       🛍️ Products
    </a>
    <a href="index.php?type=services<?= $search ? '&search='.urlencode($search) : '' ?><?= $selectedCategory !== 'All' ? '&category='.urlencode($selectedCategory) : '' ?>" 
       class="type-pill <?= $viewType === 'services' ? 'active' : '' ?>">
       🤝 Services
    </a>
    <a href="index.php?type=shops<?= $search ? '&search='.urlencode($search) : '' ?><?= $selectedCategory !== 'All' ? '&category='.urlencode($selectedCategory) : '' ?>" 
       class="type-pill <?= $viewType === 'shops' ? 'active' : '' ?>">
       🏪 Shops
    </a>
    <a href="index.php?type=offers<?= $search ? '&search='.urlencode($search) : '' ?><?= $selectedCategory !== 'All' ? '&category='.urlencode($selectedCategory) : '' ?>" 
       class="type-pill <?= ($viewType === 'offers' || $viewType === 'affiliate') ? 'active' : '' ?>">
       ⚡ Deals
    </a>
    <button type="button" class="type-pill category-trigger-pill <?= $selectedCategory !== 'All' ? 'active' : '' ?>" onclick="toggleCategoryDrawer(true)">
       📁 <?= $selectedCategory !== 'All' ? htmlspecialchars(mb_strtoupper($selectedCategory, 'UTF-8')) : 'All Categories' ?> ➔
    </button>
  </div>
</div>

<!-- Universal Category & Filter Drawer Overlay -->
<div class="drawer-scrim" id="categoryDrawerScrim" onclick="toggleCategoryDrawer(false)"></div>

<!-- Slide-Up Category Drawer Container -->
<div class="category-drawer drawer-menu modal-box" id="categoryDrawer" role="dialog" aria-modal="true">
    <!-- Top Grab Handle (Mobile UX) -->
    <div class="drawer-handle-bar">
        <div class="drawer-drag-pill"></div>
    </div>

    <!-- Drawer Header -->
    <div class="category-drawer-header">
        <div class="header-titles">
            <h2 class="drawer-title">
                <span>📁</span> Categories &amp; Filters
            </h2>
            <p class="drawer-subtitle">Browse products, services, and official partner shops</p>
        </div>
        <button type="button" class="drawer-close-btn" onclick="toggleCategoryDrawer(false)" aria-label="Close Drawer">&times;</button>
    </div>

    <!-- Scrollable Content Body -->
    <div class="category-drawer-body no-scrollbar">
        <form method="GET" action="index.php" id="drawerFilterForm">
            <?php if (!empty($search)): ?>
                <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
            <?php endif; ?>

            <!-- Section 1: Listing Types -->
            <div class="drawer-section">
                <div class="section-label-row">
                    <span class="section-label">1. Select Listing Type</span>
                </div>
                <div class="type-card-grid">
                    <label class="type-card-label">
                        <input type="radio" name="type" value="all" <?= ($viewType === 'all' || empty($viewType)) ? 'checked' : '' ?> onchange="onDrawerTypeChange(this)">
                        <div class="type-card <?= ($viewType === 'all' || empty($viewType)) ? 'active' : '' ?>">
                            <span class="type-icon">📦</span>
                            <span class="type-text">All Items</span>
                        </div>
                    </label>
                    <label class="type-card-label">
                        <input type="radio" name="type" value="products" <?= $viewType === 'products' ? 'checked' : '' ?> onchange="onDrawerTypeChange(this)">
                        <div class="type-card <?= $viewType === 'products' ? 'active' : '' ?>">
                            <span class="type-icon">🛍️</span>
                            <span class="type-text">Products</span>
                        </div>
                    </label>
                    <label class="type-card-label">
                        <input type="radio" name="type" value="services" <?= $viewType === 'services' ? 'checked' : '' ?> onchange="onDrawerTypeChange(this)">
                        <div class="type-card <?= $viewType === 'services' ? 'active' : '' ?>">
                            <span class="type-icon">🤝</span>
                            <span class="type-text">Services</span>
                        </div>
                    </label>
                    <label class="type-card-label">
                        <input type="radio" name="type" value="shops" <?= $viewType === 'shops' ? 'checked' : '' ?> onchange="onDrawerTypeChange(this)">
                        <div class="type-card <?= $viewType === 'shops' ? 'active' : '' ?>">
                            <span class="type-icon">🏪</span>
                            <span class="type-text">Partner Shops</span>
                        </div>
                    </label>
                    <label class="type-card-label full-width">
                        <input type="radio" name="type" value="offers" <?= ($viewType === 'offers' || $viewType === 'affiliate') ? 'checked' : '' ?> onchange="onDrawerTypeChange(this)">
                        <div class="type-card <?= ($viewType === 'offers' || $viewType === 'affiliate') ? 'active' : '' ?>">
                            <span class="type-icon">⚡</span>
                            <span class="type-text">Partner Deals &amp; Offers</span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Section 2: Visual Categories Grid -->
            <div class="drawer-section">
                <div class="section-label-row">
                    <span class="section-label">2. Browse Categories</span>
                    <span class="section-count"><?= count($categories) + 1 ?> available</span>
                </div>

                <div class="categories-visual-grid no-scrollbar">
                    <!-- All Categories Option -->
                    <label class="cat-card-label">
                        <input type="radio" name="category" value="All" <?= $selectedCategory === 'All' ? 'checked' : '' ?> onchange="onDrawerCatChange(this)">
                        <div class="cat-visual-card <?= $selectedCategory === 'All' ? 'active' : '' ?>">
                            <div class="cat-icon-wrap">🌐</div>
                            <div class="cat-info">
                                <span class="cat-name">All Categories</span>
                                <span class="cat-sub">Complete catalog</span>
                            </div>
                            <span class="cat-check">✓</span>
                        </div>
                    </label>

                    <?php foreach ($categories as $cat): 
                        // Map category icons intelligently
                        $icon = '📁';
                        $catLower = strtolower($cat);
                        if (strpos($catLower, 'nid') !== false) $icon = '🪪';
                        elseif (strpos($catLower, 'license') !== false || strpos($catLower, 'driving') !== false || strpos($catLower, 'transport') !== false) $icon = '🚗';
                        elseif (strpos($catLower, 'passport') !== false || strpos($catLower, 'travel') !== false || strpos($catLower, 'visa') !== false) $icon = '✈️';
                        elseif (strpos($catLower, 'tech') !== false || strpos($catLower, 'saas') !== false || strpos($catLower, 'software') !== false || strpos($catLower, 'web') !== false) $icon = '💻';
                        elseif (strpos($catLower, 'fashion') !== false || strpos($catLower, 'cloth') !== false || strpos($catLower, 'apparel') !== false) $icon = '👗';
                        elseif (strpos($catLower, 'motor') !== false || strpos($catLower, 'part') !== false || strpos($catLower, 'auto') !== false) $icon = '⚙️';
                        elseif (strpos($catLower, 'gaming') !== false || strpos($catLower, 'game') !== false) $icon = '🎮';
                        elseif (strpos($catLower, 'certificate') !== false || strpos($catLower, 'birth') !== false || strpos($catLower, 'legal') !== false) $icon = '📜';
                        elseif (strpos($catLower, 'digital') !== false || strpos($catLower, 'download') !== false) $icon = '💾';
                        elseif (strpos($catLower, 'mobile') !== false || strpos($catLower, 'recharge') !== false || strpos($catLower, 'topup') !== false) $icon = '📱';
                    ?>
                    <label class="cat-card-label">
                        <input type="radio" name="category" value="<?= htmlspecialchars($cat) ?>" <?= $selectedCategory === $cat ? 'checked' : '' ?> onchange="onDrawerCatChange(this)">
                        <div class="cat-visual-card <?= $selectedCategory === $cat ? 'active' : '' ?>">
                            <div class="cat-icon-wrap"><?= $icon ?></div>
                            <div class="cat-info">
                                <span class="cat-name"><?= htmlspecialchars(mb_strtoupper($cat, 'UTF-8')) ?></span>
                                <span class="cat-sub">Verified Listings</span>
                            </div>
                            <span class="cat-check">✓</span>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php if ($isGeoEnabled): ?>
            <!-- Section 3: Region / District Filter (Conditional) -->
            <div class="drawer-section">
                <div class="section-label-row">
                    <span class="section-label">3. Region / District</span>
                </div>
                <select name="district" class="drawer-select">
                    <option value="">🌍 All Regions</option>
                    <option value="Dhaka" <?= $selectedDistrict == 'Dhaka' ? 'selected' : '' ?>>Dhaka</option>
                    <option value="Chittagong" <?= $selectedDistrict == 'Chittagong' ? 'selected' : '' ?>>Chittagong</option>
                    <option value="Sylhet" <?= $selectedDistrict == 'Sylhet' ? 'selected' : '' ?>>Sylhet</option>
                    <option value="Rajshahi" <?= $selectedDistrict == 'Rajshahi' ? 'selected' : '' ?>>Rajshahi</option>
                    <option value="Khulna" <?= $selectedDistrict == 'Khulna' ? 'selected' : '' ?>>Khulna</option>
                    <option value="Barisal" <?= $selectedDistrict == 'Barisal' ? 'selected' : '' ?>>Barisal</option>
                    <option value="Rangpur" <?= $selectedDistrict == 'Rangpur' ? 'selected' : '' ?>>Rangpur</option>
                    <option value="Mymensingh" <?= $selectedDistrict == 'Mymensingh' ? 'selected' : '' ?>>Mymensingh</option>
                </select>
            </div>
            <?php endif; ?>
        </form>
    </div>

    <!-- Sticky Bottom Action Dock -->
    <div class="category-drawer-footer">
        <a href="index.php" class="btn-drawer-reset">Reset All</a>
        <button type="submit" form="drawerFilterForm" class="btn-drawer-apply">
            <span>Apply Filters</span>
            <span class="apply-icon">➔</span>
        </button>
    </div>
</div>

<script>
function toggleCategoryDrawer(show) {
    const drawer = document.getElementById('categoryDrawer');
    const scrim = document.getElementById('categoryDrawerScrim');
    if (!drawer || !scrim) return;

    if (show) {
        scrim.classList.add('active');
        drawer.classList.add('active');
        document.body.style.overflow = 'hidden';
    } else {
        scrim.classList.remove('active');
        drawer.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// Backward compatibility alias for any legacy triggers
window.toggleFilterModal = toggleCategoryDrawer;

function onDrawerTypeChange(radio) {
    const allCards = document.querySelectorAll('.type-card');
    allCards.forEach(c => c.classList.remove('active'));
    const parent = radio.closest('.type-card-label');
    if (parent) {
        const card = parent.querySelector('.type-card');
        if (card) card.classList.add('active');
    }
}

function onDrawerCatChange(radio) {
    const allCards = document.querySelectorAll('.cat-visual-card');
    allCards.forEach(c => c.classList.remove('active'));
    const parent = radio.closest('.cat-card-label');
    if (parent) {
        const card = parent.querySelector('.cat-visual-card');
        if (card) card.classList.add('active');
    }
}

// Backward compatibility helper
function updateRadioUI(input) {
    onDrawerTypeChange(input);
}

// ESC Key closes drawer
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        toggleCategoryDrawer(false);
    }
});
</script>

<section class="grid-section">

<?php if (empty($search) && $selectedCategory === 'All' && !empty($trending_products)): ?>
<!-- TRENDING SECTION (PHASE 59) -->
<div class="trending-container" style="margin-bottom: 2rem;">
  <div style="display:flex; align-items:center; justify-content:space-between; gap:0.5rem; margin-bottom: 1rem; flex-wrap:nowrap;">
    <h2 style="font-size:clamp(1.05rem, 3.8vw, 1.25rem); font-weight:800; color:#fff; white-space:nowrap; margin:0; line-height:1.2;">🔥 Trending Right Now</h2>
    <span style="font-size:0.72rem; background:rgba(252,185,0,0.15); color:var(--gold); padding:0.2rem 0.6rem; border-radius:50px; border:1px solid rgba(252,185,0,0.3); white-space:nowrap; flex-shrink:0;">Top Picks</span>
  </div>
  
  <div class="trending-scroller" style="display: flex; gap: 1rem; overflow-x: auto; padding-bottom: 1rem; scroll-snap-type: x mandatory; scrollbar-width: thin; scrollbar-color: var(--brand) var(--dark-card);">
    <?php foreach ($trending_products as $item): 
        $priceBdt  = floatval(preg_replace('/[^0-9.]/', '', $item['price']));
        $priceCoins = ceil($priceBdt / $exchange_rate);
        $isAff = ($item['listing_type'] ?? '') === 'affiliate' || !empty($item['affiliate_url']) || !empty($item['affiliate_link']);
        $destUrl = !empty($item['affiliate_url']) ? $item['affiliate_url'] : ($item['affiliate_link'] ?? '');
        $itemHref = ($isAff && !empty($destUrl)) ? htmlspecialchars($destUrl) : "product_detail.php?id={$item['id']}";
        $itemTarget = ($isAff && !empty($destUrl)) ? 'target="_blank" rel="noopener noreferrer"' : '';
    ?>
    <a href="<?= $itemHref ?>" <?= $itemTarget ?> class="trending-card" style="flex: 0 0 260px; scroll-snap-align: start; background: var(--dark-card); border: 1px solid rgba(255,255,255,0.05); border-radius: 12px; overflow: hidden; text-decoration: none; transition: transform 0.3s, box-shadow 0.3s;">
      <div style="height: 140px; position: relative; background: #111;">
        <?php if (!empty($item['audio_file'])): ?>
          <div style="position: absolute; top: 8px; right: 8px; background: rgba(0,0,0,0.7); border: 1px solid #00e676; color: #00e676; border-radius: 20px; font-size: 0.68rem; font-weight: 800; padding: 2px 8px; backdrop-filter: blur(4px);">🎵 Audio Demo</div>
        <?php endif; ?>
        <div style="position: absolute; bottom: 0; left: 0; right: 0; background: linear-gradient(to top, rgba(0,0,0,0.9), transparent); padding: 1.5rem 0.8rem 0.5rem;">
          <div style="color: #fff; font-weight: 700; font-size: 0.85rem; display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden;"><?= htmlspecialchars($item['title']) ?></div>
        </div>
      </div>
      <div style="padding: 0.8rem; display: flex; justify-content: space-between; align-items: center;">
        <div style="font-size: 0.65rem; color: var(--muted); text-transform: uppercase; font-weight: 700;"><span style="color:var(--green)">●</span> <?= htmlspecialchars($item['shop_name']) ?></div>
        <div style="font-size: 0.9rem; font-weight: 900; color: var(--gold);">🪙 <?= number_format($priceCoins) ?></div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
  <style>
    .trending-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(252,185,0,0.15); border-color: rgba(252,185,0,0.3); }
    .trending-scroller::-webkit-scrollbar { height: 6px; }
    .trending-scroller::-webkit-scrollbar-track { background: var(--dark-card); border-radius: 10px; }
    .trending-scroller::-webkit-scrollbar-thumb { background: var(--brand); border-radius: 10px; }
  </style>
</div>
<?php endif; ?>


<?php if (empty($products)): ?>
  <div class="empty-state">
    <div style="font-size:3rem; margin-bottom:1rem;">🔍</div>
    <h3>NO ITEMS FOUND</h3>
    <p>Try searching with another keyword or browse different categories.</p>
    <a href="index.php" style="display:inline-block; margin-top:1.5rem; background:linear-gradient(135deg,#fcb900,#ff9100); color:#000; font-weight:700; padding:0.7rem 1.8rem; border-radius:50px; text-decoration:none; font-size:0.85rem;">Clear Filters</a>
  </div>
<?php else: ?>

  <?php if ($viewType === 'shops'): ?>
    <?php if (empty($shops)): ?>
      <div class="empty-state">
        <h3>NO SHOPS FOUND</h3>
        <p>Try searching with another keyword or browse different categories.</p>
      </div>
    <?php else: ?>
      <div class="shop-grid">
        <?php foreach ($shops as $shop): 
          $isOfficial = $shop['is_official'] == 1;
          $profilePic = !empty($shop['profile_pic']) ? '/uploads/partners/' . basename(htmlspecialchars($shop['profile_pic'])) : '/assets/images/logo.png';
          
          $portfolio = array_merge($shop['products'], $shop['services']);
          $portfolio = array_slice($portfolio, 0, 3);
          $seoSlug = strtolower(str_replace(' ', '_', $shop['shop_name']));
        ?>
        <div class="shop-card">
          <div class="shop-header">
            <a href="/shop/<?= urlencode($seoSlug) ?>" style="text-decoration:none; display:flex; align-items:center; gap:0.8rem; width:100%;">
              <img src="<?= $profilePic ?>" alt="Shop Logo" class="shop-logo" onerror="this.onerror=null; this.src='/assets/images/logo.png';">
              <div class="shop-title-area">
                <div class="shop-name">
                  <?= htmlspecialchars($shop['shop_name']) ?>
                  <?php if ($isOfficial): ?>
                    <span style="color: #fcb900; background: rgba(252,185,0,0.15); padding: 2px 6px; border-radius: 4px; font-size: 0.65rem; border: 1px solid rgba(252,185,0,0.3);">✓ OFFICIAL</span>
                  <?php endif; ?>
                </div>
                <div class="shop-tagline">
                  <?= htmlspecialchars(substr($shop['description'], 0, 60)) ?><?= strlen($shop['description']) > 60 ? '...' : '' ?>
                </div>
              </div>
            </a>
          </div>
          
          <div class="portfolio-grid">
            <?php if (empty($portfolio)): ?>
              <div style="grid-column: 1/-1; padding: 1.2rem; background: rgba(255,255,255,0.02); border-radius: 10px; border: 1px dashed rgba(255,255,255,0.08); text-align: center;">
                <div style="font-size: 0.85rem; color: #94a3b8; margin-bottom: 0.6rem;">🌐 Ecosystem Verified Storefront</div>
                <a href="/shop/<?= urlencode($seoSlug) ?>" style="display: inline-block; background: rgba(252,185,0,0.12); color: #fcb900; border: 1px solid rgba(252,185,0,0.3); padding: 0.45rem 1.1rem; border-radius: 20px; font-size: 0.78rem; font-weight: 700; text-decoration: none;">
                  Explore Storefront &amp; Services →
                </a>
              </div>
            <?php else: ?>
              <?php foreach ($portfolio as $item): 
                $priceBdt = floatval(preg_replace('/[^0-9.]/', '', $item['price']));
                $priceCoins = ceil($priceBdt / $exchange_rate);
                $isAff = ($item['listing_type'] ?? '') === 'affiliate' || !empty($item['affiliate_url']) || !empty($item['affiliate_link']);
                $destUrl = !empty($item['affiliate_url']) ? $item['affiliate_url'] : ($item['affiliate_link'] ?? '');
                $itemHref = ($isAff && !empty($destUrl)) ? htmlspecialchars($destUrl) : "product_detail.php?id={$item['id']}";
                $itemTarget = ($isAff && !empty($destUrl)) ? 'target="_blank" rel="noopener noreferrer"' : '';
              ?>
              <a href="<?= $itemHref ?>" <?= $itemTarget ?> class="portfolio-item">
                <img src="<?= htmlspecialchars($item['display_thumb']) ?>" alt="Thumbnail" class="portfolio-img" onerror="this.onerror=null; this.src='/assets/images/services/default_service.svg';">
                <div class="portfolio-details">
                  <div class="portfolio-title"><?= htmlspecialchars($item['title']) ?></div>
                  <div class="portfolio-price">🪙 <?= number_format($priceCoins) ?></div>
                </div>
              </a>
              <?php endforeach; ?>
              
              <?php if (count($portfolio) >= 3): ?>
              <a href="/shop/<?= urlencode($seoSlug) ?>" style="text-align: center; color: var(--muted); font-size: 0.8rem; text-decoration: none; display: block; padding-top: 0.5rem; transition: color 0.2s;">
                View all from this shop →
              </a>
              <?php endif; ?>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  <?php else: ?>
    <!-- FULL PREMIUM 4-COLUMN PRODUCT GRID -->
    <?php
      // Extract Trending IDs (Phase 78)
      $trending_ids = !empty($trending_products) ? array_column($trending_products, 'id') : [];
      // Count visible items
      $visibleCount = 0;
      foreach ($products as $item) {
          if (!empty($trending_ids) && in_array($item['id'], $trending_ids)) {
              continue; // Skip items already shown in trending
          }
          $isSrv = ($item['listing_type'] ?? 'product') === 'service';
          $isAff = ($item['listing_type'] ?? '') === 'affiliate' || !empty($item['affiliate_url']) || !empty($item['affiliate_link']);
          if ($viewType === 'services' && !$isSrv) continue;
          if ($viewType === 'products' && ($isSrv || $isAff)) continue;
          if (($viewType === 'offers' || $viewType === 'affiliate') && !$isAff) continue;
          $visibleCount++;
      }
    ?>
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.2rem; padding:0 0.2rem;">
      <div style="font-size:0.8rem; color:var(--muted); font-weight:600;">
        Showing <strong style="color:#fff;"><?= number_format($visibleCount) ?></strong> listings
        <?= $search ? ' for "<em style="color:var(--gold);">' . htmlspecialchars($search) . '</em>"' : '' ?>
      </div>
      <div style="font-size:0.72rem; color:var(--muted);">Sorted by newest</div>
    </div>
    <div class="product-grid-4">
      <?php foreach ($products as $item):
          if (!empty($trending_ids) && in_array($item['id'], $trending_ids)) {
              continue; // Skip items already shown in trending
          }
          $isService = ($item['listing_type'] ?? 'product') === 'service';
          $isAff = ($item['listing_type'] ?? '') === 'affiliate' || !empty($item['affiliate_url']) || !empty($item['affiliate_link']);
          if ($viewType === 'services' && !$isService) continue;
          if ($viewType === 'products' && ($isService || $isAff)) continue;
          if (($viewType === 'offers' || $viewType === 'affiliate') && !$isAff) continue;
          $priceBdt  = floatval(preg_replace('/[^0-9.]/', '', $item['price']));
          $priceCoins = ceil($priceBdt / $exchange_rate);
          $badgeLabel = $isService ? 'Service' : ($item['category'] ?? 'Product');
          $shopName   = htmlspecialchars($item['shop_name'] ?? 'Fast Site Official');
          $thumbSrc   = htmlspecialchars($item['display_thumb'] ?? '/assets/img/placeholder.png');
          
          $isAff = ($item['listing_type'] ?? '') === 'affiliate' || !empty($item['affiliate_url']) || !empty($item['affiliate_link']);
          $destUrl = !empty($item['affiliate_url']) ? $item['affiliate_url'] : ($item['affiliate_link'] ?? '');
          $cardHref = ($isAff && !empty($destUrl)) ? htmlspecialchars($destUrl) : "product_detail.php?id={$item['id']}";
          $cardTarget = ($isAff && !empty($destUrl)) ? 'target="_blank" rel="noopener noreferrer"' : '';
      ?>
      <a href="<?= $cardHref ?>" <?= $cardTarget ?> class="pcard">
        <div class="pcard-img-wrap">
          <img src="<?= $thumbSrc ?>" alt="<?= htmlspecialchars($item['title']) ?>" loading="lazy"
               onerror="this.src='/assets/images/services/default_service.svg'">
          <?php if (!empty($item['audio_file'])): ?>
            <span class="pcard-badge" style="background: rgba(0, 230, 118, 0.25); color: #00e676; border: 1px solid rgba(0, 230, 118, 0.4); font-weight:800; display:inline-flex; align-items:center; gap:3px;">🎵 Audio Track</span>
          <?php elseif ($isAff): ?>
            <span class="pcard-badge" style="background: linear-gradient(135deg, #10b981, #00b0ff); color:#fff; font-weight:800;">⚡ Partner Offer</span>
          <?php else: ?>
            <span class="pcard-badge <?= $isService ? 'service' : '' ?>"><?= htmlspecialchars($badgeLabel) ?></span>
          <?php endif; ?>
          <button type="button" class="pcard-share-btn" onclick="quickShareProduct(event, '<?= htmlspecialchars(addslashes($item['title'])) ?>', '<?= $item['id'] ?>')" title="Share Product">📤</button>
        </div>
        <div class="pcard-body">
          <div class="pcard-shop"><span class="dot"></span><?= $shopName ?></div>
          <div class="pcard-title"><?= htmlspecialchars($item['title']) ?></div>
        </div>
        <div class="pcard-footer">
          <span class="pcard-price"><?= $priceCoins > 0 ? '🪙 ' . number_format($priceCoins) : '<span style="color:#10b981; font-weight:800;">FREE / PROMO</span>' ?></span>
          <?php if ($isAff && !empty($destUrl)): ?>
            <span class="pcard-action" style="color: #00e676; font-weight: 800; display:inline-flex; align-items:center; gap:3px;">Visit Partner ↗</span>
          <?php else: ?>
            <span class="pcard-action">View →</span>
          <?php endif; ?>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

<?php endif; ?>

</section>

<?php include __DIR__ . '/includes/footer.php'; ?>

<!-- Marketplace Welcome Popup — Premium Edition -->
<div class="modal-overlay" id="welcomeModal" style="z-index:9999;">
  <div class="modal-box" style="max-width:400px; padding:0; overflow:hidden; border:1px solid rgba(252,185,0,0.25); box-shadow: 0 0 60px rgba(252,185,0,0.12), 0 20px 60px rgba(0,0,0,0.6);">
    <!-- Gold gradient top bar -->
    <div style="background:linear-gradient(135deg, #fcb900 0%, #f7971e 50%, #ff5722 100%); height:4px; width:100%;"></div>

    <!-- Header -->
    <div style="padding:1.6rem 1.6rem 0; display:flex; align-items:flex-start; justify-content:space-between;">
      <div>
        <div style="display:inline-flex; align-items:center; gap:8px; background:rgba(252,185,0,0.1); border:1px solid rgba(252,185,0,0.25); border-radius:20px; padding:4px 12px; margin-bottom:10px;">
          <span style="font-size:0.65rem; font-weight:800; letter-spacing:2px; text-transform:uppercase; color:var(--gold);">Fast Site Marketplace</span>
        </div>
        <h2 style="font-family:'Oswald',sans-serif; font-size:1.65rem; font-weight:700; color:#fff; line-height:1.2; margin:0;">
          <?php
            $greetingHour = (int)date('G');
            if ($greetingHour < 12) $timeGreeting = "Good Morning";
            elseif ($greetingHour < 17) $timeGreeting = "Good Afternoon";
            else $timeGreeting = "Good Evening";

            if ($isUserLoggedIn && !empty($user_data['name'])) {
              $firstName = explode(' ', trim($user_data['name']))[0];
              echo htmlspecialchars($timeGreeting) . ',<br><span style="color:var(--gold);">' . htmlspecialchars($firstName) . '!</span>';
            } else {
              echo 'Welcome to<br><span style="color:var(--gold);">Fast Site!</span>';
            }
          ?>
        </h2>
      </div>
      <button id="closeModalBtn" style="background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.12); color:rgba(255,255,255,0.5); width:32px; height:32px; border-radius:50%; font-size:1rem; cursor:pointer; flex-shrink:0; display:flex; align-items:center; justify-content:center; transition:all 0.2s;" onmouseover="this.style.color='#fff';this.style.borderColor='rgba(255,255,255,0.4)'" onmouseout="this.style.color='rgba(255,255,255,0.5)';this.style.borderColor='rgba(255,255,255,0.12)'">&times;</button>
    </div>

    <!-- Body -->
    <div style="padding:1rem 1.6rem 1.6rem;">
      <p style="color:rgba(255,255,255,0.55); font-size:0.85rem; line-height:1.6; margin:0 0 1.4rem;">
        Bangladesh's premier digital marketplace — discover freelance services, digital assets, partner shops, and exclusive offers all in one place.
      </p>

      <!-- Feature highlights -->
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.55rem; margin-bottom:1.4rem;">
        <div style="background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.07); border-radius:10px; padding:0.75rem 0.85rem; display:flex; align-items:center; gap:0.6rem;">
          <span style="font-size:1.2rem;">🤝</span>
          <div>
            <div style="font-size:0.72rem; font-weight:700; color:#fff; line-height:1.2;">Freelance</div>
            <div style="font-size:0.65rem; color:rgba(255,255,255,0.4);">Services</div>
          </div>
        </div>
        <div style="background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.07); border-radius:10px; padding:0.75rem 0.85rem; display:flex; align-items:center; gap:0.6rem;">
          <span style="font-size:1.2rem;">📦</span>
          <div>
            <div style="font-size:0.72rem; font-weight:700; color:#fff; line-height:1.2;">Digital Assets</div>
            <div style="font-size:0.65rem; color:rgba(255,255,255,0.4);">& Templates</div>
          </div>
        </div>
        <div style="background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.07); border-radius:10px; padding:0.75rem 0.85rem; display:flex; align-items:center; gap:0.6rem;">
          <span style="font-size:1.2rem;">🏪</span>
          <div>
            <div style="font-size:0.72rem; font-weight:700; color:#fff; line-height:1.2;">Partner Shops</div>
            <div style="font-size:0.65rem; color:rgba(255,255,255,0.4);">& Marketplace</div>
          </div>
        </div>
        <div style="background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.07); border-radius:10px; padding:0.75rem 0.85rem; display:flex; align-items:center; gap:0.6rem;">
          <span style="font-size:1.2rem;">🪙</span>
          <div>
            <div style="font-size:0.72rem; font-weight:700; color:#fff; line-height:1.2;">Fast Points</div>
            <div style="font-size:0.65rem; color:rgba(255,255,255,0.4);">Rewards</div>
          </div>
        </div>
      </div>

      <!-- CTA button -->
      <button onclick="dismissWelcomeModal()" style="width:100%; padding:0.85rem; background:linear-gradient(135deg, #fcb900, #f7971e); color:#000; font-weight:800; font-size:0.9rem; border:none; border-radius:10px; cursor:pointer; letter-spacing:0.5px; transition:opacity 0.2s;" onmouseover="this.style.opacity='0.88'" onmouseout="this.style.opacity='1'">
        🚀 Explore the Marketplace
      </button>

      <!-- Auto-dismiss note -->
      <p style="text-align:center; font-size:0.68rem; color:rgba(255,255,255,0.2); margin:0.75rem 0 0;">
        Auto-closes in <span id="welcomeCountdown">8</span>s · Won't appear again
      </p>
    </div>
  </div>
</div>
<!-- Share Modal -->
<div class="modal-overlay" id="shareModal">
  <div class="modal-box" style="text-align:center;">
    <div class="modal-header">
      <h2>Share & Earn!</h2>
      <button class="close-modal" onclick="closeShareModal()">&times;</button>
    </div>
    <div class="modal-body">
      <div style="font-size: 3rem; margin-bottom:1rem;">💰</div>
      <p style="color:#fff; font-size:1.1rem; margin-bottom:0.5rem; font-weight:bold;">Want to earn passive income?</p>
      <p style="color:var(--muted); font-size:0.9rem; margin-bottom:1.5rem; line-height:1.5;">Sign up or log in to generate your unique tracking link. When someone buys a service through your link, you earn a direct commission to your wallet!</p>
      <div style="display:flex; flex-direction:column; gap:0.8rem;">
        <a href="user/login.php" class="btn" style="background:var(--gold); color:#000; padding:0.8rem; font-size:1rem; text-align:center;">Log In</a>
        <a href="user/register.php" class="btn" style="background:rgba(255,255,255,0.05); color:#fff; border:1px solid rgba(255,255,255,0.1); padding:0.8rem; font-size:1rem; text-align:center;">Register Account</a>
      </div>
    </div>
  </div>
</div>

<script>
// Marketplace Welcome Modal — Premium Auto-Dismiss Logic
document.addEventListener('DOMContentLoaded', () => {
  const modal = document.getElementById('welcomeModal');
  if (!modal) return;

  const hasSeen = localStorage.getItem('fastsite_welcome_seen');
  const urlParams = new URLSearchParams(window.location.search);

  // Don't show if already dismissed before or URL has explicit params
  if (hasSeen || urlParams.has('type') || urlParams.has('q') || urlParams.has('cat')) {
    modal.style.display = 'none';
    return;
  }

  // Show after a brief delay
  setTimeout(() => modal.classList.add('active'), 600);

  // Auto-dismiss countdown
  let seconds = 8;
  const countdownEl = document.getElementById('welcomeCountdown');
  const countdownInterval = setInterval(() => {
    seconds--;
    if (countdownEl) countdownEl.textContent = seconds;
    if (seconds <= 0) {
      clearInterval(countdownInterval);
      dismissWelcomeModal();
    }
  }, 1000);

  // Close button
  document.getElementById('closeModalBtn').addEventListener('click', () => {
    clearInterval(countdownInterval);
    dismissWelcomeModal();
  });

  // Close on overlay backdrop click
  modal.addEventListener('click', (e) => {
    if (e.target === modal) {
      clearInterval(countdownInterval);
      dismissWelcomeModal();
    }
  });
});

function dismissWelcomeModal() {
  const modal = document.getElementById('welcomeModal');
  if (modal) modal.classList.remove('active');
  localStorage.setItem('fastsite_welcome_seen', 'true');
}

const isLoggedIn = <?= $isUserLoggedIn ? 'true' : 'false' ?>;
const myRefCode = "<?= $isUserLoggedIn && !empty($user_ref_code) ? htmlspecialchars($user_ref_code) : '' ?>";

function shareProduct(productId) {
  if (!isLoggedIn || !myRefCode) {
    document.getElementById('shareModal').classList.add('active');
    return;
  }
  
  const link = window.location.origin + '/ref.php?ref=' + encodeURIComponent(myRefCode) + '&redirect=/product_detail.php?id=' + productId;
  
  if (navigator.clipboard) {
    navigator.clipboard.writeText(link).then(() => {
      alert("✅ Your unique affiliate link is copied to clipboard!\nShare it to earn commission.");
    }).catch(err => {
      alert("Here is your link: " + link);
    });
  } else {
    alert("Here is your link: " + link);
  }
}

function closeShareModal() {
  document.getElementById('shareModal').classList.remove('active');
}

// Mobile real-time search filter for instant feedback (mobile_fast!)
const searchInput = document.querySelector('.search-form input');
if (searchInput) {
  searchInput.addEventListener('input', (e) => {
    const term = e.target.value.toLowerCase().trim();
    const cards = document.querySelectorAll('.product-card');
    
    cards.forEach(card => {
      const title = card.getAttribute('data-title') || '';
      const cat = card.getAttribute('data-category') || '';
      if (title.includes(term) || cat.includes(term)) {
        card.style.display = 'block';
      } else {
        card.style.display = 'none';
      }
    });
  });
}

// Omni-Search Engine Logic (Phase 21)
const omniInput = document.getElementById('omniSearchInput');
const omniDropdown = document.getElementById('omniDropdown');
let omniTimeout = null;

// Algolia-Style Live Instant Grid Search
let liveSearchTimeout = null;
const gridContainer = document.querySelector('.product-grid-4');
const countDisplay = document.querySelector('.grid-section strong');

if (omniInput && gridContainer) {
    omniInput.addEventListener('input', (e) => {
        const query = e.target.value.trim();
        const selectedCat = "<?= htmlspecialchars($selectedCategory) ?>";
        const selectedType = "<?= htmlspecialchars($viewType) ?>";
        const selectedDistrict = "<?= htmlspecialchars($selectedDistrict) ?>";
        
        clearTimeout(liveSearchTimeout);
        liveSearchTimeout = setTimeout(() => {
            fetch(`api/live_search.php?search=${encodeURIComponent(query)}&category=${encodeURIComponent(selectedCat)}&type=${encodeURIComponent(selectedType)}&district=${encodeURIComponent(selectedDistrict)}`)
                .then(res => res.json())
                .then(res => {
                    if (res.status === 'success') {
                        if (countDisplay) countDisplay.textContent = res.count;
                        
                        if (res.data.length === 0) {
                            gridContainer.innerHTML = `
                                <div class="empty-state" style="grid-column: 1 / -1; width:100%;">
                                    <div style="font-size:3rem; margin-bottom:1rem;">🔍</div>
                                    <h3>NO MATCHING ITEMS</h3>
                                    <p>No products found for "<strong>${query}</strong>". Try another search keyword.</p>
                                </div>`;
                            return;
                        }
                        
                        let html = '';
                        res.data.forEach(item => {
                            const targetAttr = item.is_external ? 'target="_blank" rel="noopener noreferrer"' : '';
                            const actionLabel = item.is_external ? '<span style="color:#00e676; font-weight:800;">Visit Partner ↗</span>' : 'View →';
                            const badgeStyle = item.is_affiliate ? 'style="background:linear-gradient(135deg,#10b981,#00b0ff); color:#fff; font-weight:800;"' : '';
                            html += `
                            <a href="${item.detail_url}" ${targetAttr} class="pcard" style="animation: fadeIn 0.3s ease-in-out;">
                                <div class="pcard-img-wrap">
                                  <img src="${item.thumb}" alt="${item.title}" loading="lazy" onerror="this.src='/assets/images/services/default_service.svg'">
                                  <span class="pcard-badge ${item.is_service ? 'service' : ''}" ${badgeStyle}>${item.category}</span>
                                  <button type="button" class="pcard-share-btn" onclick="quickShareProduct(event, '${item.title.replace(/'/g, "\\'")}', '${item.id}')" title="Share Product">📤</button>
                                </div>
                                <div class="pcard-body">
                                  <div class="pcard-shop"><span class="dot"></span>${item.shop_name}</div>
                                  <div class="pcard-title">${item.title}</div>
                                </div>
                                <div class="pcard-footer">
                                  <span class="pcard-price">🪙 ${item.price_coins}</span>
                                  <span class="pcard-action">${actionLabel}</span>
                                </div>
                            </a>`;
                        });
                        gridContainer.innerHTML = html;
                    }
                }).catch(() => {});
        }, 300);
    });
}

if (omniInput) {
    omniInput.addEventListener('input', (e) => {
        clearTimeout(omniTimeout);
        const query = e.target.value.trim();
        
        if (query.length < 2) {
            omniDropdown.style.display = 'none';
            return;
        }

        omniDropdown.style.display = 'block';
        omniDropdown.innerHTML = '<div class="omni-item" style="text-align:center; color:#888;">Searching network...</div>';

        omniTimeout = setTimeout(() => {
            fetch(`/api/omni_search.php?q=${encodeURIComponent(query)}`)
                .then(res => res.json())
                .then(data => {
                    omniDropdown.innerHTML = '';
                    if (data.results && data.results.length > 0) {
                        data.results.forEach(item => {
                            const isExternal = item.source !== 'Fast Site Marketplace';
                            const badgeColor = isExternal ? '#ef4444' : '#10b981';
                            
                            omniDropdown.innerHTML += `
                                <a href="${item.url}" class="omni-item">
                                    <div style="font-weight:700; color:#fff;">${item.title}</div>
                                    <div style="font-size:0.8rem; color:#aaa; display:flex; justify-content:space-between; margin-top:4px;">
                                        <span><span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:${badgeColor}; margin-right:4px;"></span>${item.source}</span>
                                        <span style="color:#fcb900; font-weight:700;">${item.price}</span>
                                    </div>
                                </a>
                            `;
                        });
                    } else {
                        omniDropdown.innerHTML = '<div class="omni-item" style="text-align:center; color:#888;">No results found across network.</div>';
                    }
                })
                .catch(err => {
                    omniDropdown.innerHTML = '<div class="omni-item" style="text-align:center; color:#ef4444;">Error connecting to Omni-Network.</div>';
                });
        }, 500);
    });

    document.addEventListener('click', (e) => {
        if (!e.target.closest('#omniSearchForm')) {
            omniDropdown.style.display = 'none';
        }
    });
}
</script>
<style>
/* Omni-Search Dropdown Styles */
.omni-dropdown {
    position: absolute; top: 100%; left: 0; right: 0; z-index: 1000;
    background: var(--dark-card, #1c1c24); border: 1px solid rgba(255,255,255,0.1);
    border-radius: 12px; margin-top: 8px; box-shadow: 0 10px 30px rgba(0,0,0,0.5);
    overflow: hidden; max-height: 400px; overflow-y: auto;
}
.omni-item {
    display: block; padding: 12px 15px; border-bottom: 1px solid rgba(255,255,255,0.05);
    text-decoration: none; transition: background 0.2s; text-align: left;
}
.omni-item:hover { background: rgba(252,185,0,0.1); }
.omni-item:last-child { border-bottom: none; }
</style>

<script>
function quickShareProduct(e, title, id) {
    e.preventDefault();
    e.stopPropagation();
    
    const baseUrl = window.location.origin;
    const shareUrl = `${baseUrl}/product_detail.php?id=${id}`;
    
    if (navigator.share) {
        navigator.share({
            title: title,
            text: `Check out ${title} on Fast Site!`,
            url: shareUrl
        }).catch(() => {});
    } else {
        navigator.clipboard.writeText(shareUrl);
        showShareToast('📋 Product link copied to clipboard!');
    }
}

function showShareToast(msg) {
    let toast = document.getElementById('shareToast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'shareToast';
        toast.style.cssText = 'position:fixed; bottom:30px; left:50%; transform:translateX(-50%); background:#fcb900; color:#000; font-weight:800; padding:12px 24px; border-radius:50px; box-shadow:0 10px 30px rgba(0,0,0,0.5); z-index:99999; font-size:0.85rem; transition:all 0.3s ease; opacity:0; pointer-events:none;';
        document.body.appendChild(toast);
    }
    toast.textContent = msg;
    toast.style.opacity = '1';
    toast.style.transform = 'translateX(-50%) translateY(-10px)';
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(-50%) translateY(0)';
    }, 2500);
}
</script>
<script src="/assets/js/pull_to_refresh.js"></script>
</body>
</html>
