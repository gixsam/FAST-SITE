<?php
// =========================================================================
// admin/products.php — Comprehensive Product & Listing Command Center
// =========================================================================
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php'); exit;
}
require_once __DIR__ . '/../config.php';

$msg = $err = '';

// Self-healing schema check
try {
    if (isset($pdo)) {
        @$pdo->exec("ALTER TABLE partner_products ADD COLUMN is_trending INTEGER DEFAULT 0");
        @$pdo->exec("ALTER TABLE partner_products ADD COLUMN listing_type TEXT DEFAULT 'product'");
        @$pdo->exec("ALTER TABLE partner_products ADD COLUMN affiliate_url TEXT DEFAULT ''");
    }
} catch (Exception $e) {}

// Helper function to send instant notification to shop owner
function notify_shop_owner($pdo, $partner_id, $title, $message, $type = 'shop_alert') {
    if (!$partner_id) return;
    try {
        // Find owner user_id
        $pStmt = $pdo->prepare("SELECT user_id, business_name FROM partners WHERE id = ? LIMIT 1");
        $pStmt->execute([$partner_id]);
        $p = $pStmt->fetch(PDO::FETCH_ASSOC);
        if ($p && !empty($p['user_id'])) {
            $nStmt = $pdo->prepare("INSERT INTO user_notifications (user_id, title, message, type, is_read, created_at) VALUES (?, ?, ?, ?, 0, NOW())");
            try {
                $nStmt->execute([(int)$p['user_id'], $title, $message, $type]);
            } catch (Exception $e) {
                // SQLite fallback for datetime
                $nStmtSqlite = $pdo->prepare("INSERT INTO user_notifications (user_id, title, message, type, is_read) VALUES (?, ?, ?, ?, 0)");
                $nStmtSqlite->execute([(int)$p['user_id'], $title, $message, $type]);
            }
        }
    } catch (Exception $e) {
        error_log("Failed to notify shop owner: " . $e->getMessage());
    }
}

// ── Handle Actions ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $prod_id = intval($_POST['product_id'] ?? 0);

    if ($prod_id > 0) {
        // Fetch product details first
        $pCheck = $pdo->prepare("SELECT p.*, pt.business_name FROM partner_products p LEFT JOIN partners pt ON p.partner_id = pt.id WHERE p.id = ?");
        $pCheck->execute([$prod_id]);
        $product = $pCheck->fetch(PDO::FETCH_ASSOC);

        if ($product) {
            $partner_id = (int)$product['partner_id'];
            $prod_title = htmlspecialchars($product['title']);
            $shop_name  = htmlspecialchars($product['business_name'] ?? 'Fast Site Official');

            try {
                // 1. Toggle Trending (ADD / CANCEL from Trending)
                if ($action === 'toggle_trending') {
                    $new_val = empty($product['is_trending']) ? 1 : 0;
                    $pdo->prepare("UPDATE partner_products SET is_trending = ? WHERE id = ?")->execute([$new_val, $prod_id]);
                    
                    if ($new_val === 1) {
                        notify_shop_owner($pdo, $partner_id, 'Promoted to Trending! 🔥', "Congratulations! Your listing \"{$prod_title}\" was selected by Admin to appear in the Homepage Trending Section!");
                        $msg = "Listing \"{$prod_title}\" added to Trending Section!";
                    } else {
                        $msg = "Listing \"{$prod_title}\" removed from Trending Section.";
                    }
                }

                // 2. Put On Hold / Activate (Suspend / Unhold)
                elseif ($action === 'toggle_hold') {
                    $new_status = ((int)($product['is_published'] ?? 1) === 1) ? 0 : 1;
                    $pdo->prepare("UPDATE partner_products SET is_published = ? WHERE id = ?")->execute([$new_status, $prod_id]);

                    if ($new_status === 0) {
                        notify_shop_owner($pdo, $partner_id, '⚠️ Listing Placed on Hold', "Your listing \"{$prod_title}\" has been temporarily placed on hold by Admin. It is currently hidden from the public marketplace.");
                        $msg = "Listing \"{$prod_title}\" has been placed ON HOLD.";
                    } else {
                        notify_shop_owner($pdo, $partner_id, '🟢 Listing Activated', "Good news! Your listing \"{$prod_title}\" has been reactivated and is now live on the marketplace.");
                        $msg = "Listing \"{$prod_title}\" has been ACTIVATED and is now live.";
                    }
                }

                // 3. Delete Product
                elseif ($action === 'delete_product') {
                    // Delete associated images
                    try {
                        $pdo->prepare("DELETE FROM partner_product_images WHERE product_id = ?")->execute([$prod_id]);
                    } catch (Exception $e) {}
                    
                    // Delete product
                    $pdo->prepare("DELETE FROM partner_products WHERE id = ?")->execute([$prod_id]);

                    notify_shop_owner($pdo, $partner_id, '❌ Listing Removed', "Your listing \"{$prod_title}\" has been removed from the platform by Fast Site Administration.");
                    $msg = "Listing \"{$prod_title}\" deleted successfully, and the shop owner was notified.";
                }
            } catch (Exception $e) {
                $err = 'Error updating listing: ' . $e->getMessage();
            }
        } else {
            $err = 'Product listing not found.';
        }
    }
}

// ── Query & Filter Listings ──────────────────────────────────────────────
$tab = $_GET['tab'] ?? 'all';
$search = trim($_GET['search'] ?? '');
$filter_partner = intval($_GET['partner_id'] ?? 0);

$where = ["1=1"];
$params = [];

if ($search !== '') {
    $where[] = "(p.title LIKE :s1 OR p.description LIKE :s2 OR p.category LIKE :s3 OR pt.business_name LIKE :s4)";
    $params[':s1'] = "%{$search}%";
    $params[':s2'] = "%{$search}%";
    $params[':s3'] = "%{$search}%";
    $params[':s4'] = "%{$search}%";
}

if ($filter_partner > 0) {
    $where[] = "p.partner_id = :pid";
    $params[':pid'] = $filter_partner;
}

if ($tab === 'trending') {
    $where[] = "p.is_trending = 1";
} elseif ($tab === 'products') {
    $where[] = "(p.listing_type = 'product' OR p.listing_type IS NULL) AND (p.affiliate_url IS NULL OR p.affiliate_url = '')";
} elseif ($tab === 'services') {
    $where[] = "p.listing_type = 'service'";
} elseif ($tab === 'affiliates') {
    $where[] = "(p.affiliate_url IS NOT NULL AND p.affiliate_url != '')";
} elseif ($tab === 'onhold') {
    $where[] = "p.is_published = 0";
}

$wsql = implode(' AND ', $where);

// Fetch All Listings with Thumbnails & Shop Details
$products_stmt = $pdo->prepare("
    SELECT p.*,
    COALESCE(pt.business_name, 'Fast Site Official') AS shop_name,
    COALESCE(pt.owner_name, 'System') AS owner_name,
    COALESCE(pt.phone, '') AS shop_phone,
    COALESCE(pt.status, 'approved') AS shop_status,
    COALESCE(pt.is_official, 0) AS is_official,
    (SELECT image_url FROM partner_product_images WHERE product_id = p.id AND is_thumbnail = 1 LIMIT 1) AS thumb,
    (SELECT image_url FROM partner_product_images WHERE product_id = p.id ORDER BY id ASC LIMIT 1) AS fallback_img
    FROM partner_products p
    LEFT JOIN partners pt ON p.partner_id = pt.id
    WHERE {$wsql}
    ORDER BY p.is_trending DESC, p.id DESC
");
$products_stmt->execute($params);
$products = $products_stmt->fetchAll(PDO::FETCH_ASSOC);

// Counts for KPI Summary
$total_count = (int)$pdo->query("SELECT COUNT(*) FROM partner_products")->fetchColumn();
$trending_count = (int)$pdo->query("SELECT COUNT(*) FROM partner_products WHERE is_trending = 1")->fetchColumn();
$services_count = (int)$pdo->query("SELECT COUNT(*) FROM partner_products WHERE listing_type = 'service'")->fetchColumn();
$affiliate_count = (int)$pdo->query("SELECT COUNT(*) FROM partner_products WHERE affiliate_url IS NOT NULL AND affiliate_url != ''")->fetchColumn();
$onhold_count = (int)$pdo->query("SELECT COUNT(*) FROM partner_products WHERE is_published = 0")->fetchColumn();

// Fetch All Partners for filter dropdown
$partners_list = $pdo->query("SELECT id, business_name FROM partners ORDER BY business_name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Product &amp; Listing Management &mdash; Fast Site Admin</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="/assets/css/admin.css">
  <link rel="stylesheet" href="/assets/css/admin-nav.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Oswald:wght@500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --gold: #fcb900;
      --gold-glow: rgba(252, 185, 0, 0.25);
      --dark-card: #0e111d;
      --border-line: rgba(255, 255, 255, 0.08);
      --green: #00e676;
      --red: #ff5252;
      --blue: #00b0ff;
    }
    body {
      background: #080911;
      color: #e2e8f0;
      font-family: 'Inter', sans-serif;
      margin: 0;
      padding-top: 70px;
    }
    .container {
      max-width: 1400px;
      margin: 0 auto;
      padding: 1.5rem 1rem;
    }
    .page-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 1rem;
      margin-bottom: 1.5rem;
    }
    .page-title {
      font-family: 'Oswald', sans-serif;
      font-size: 1.8rem;
      font-weight: 700;
      color: #fff;
      margin: 0;
      display: flex;
      align-items: center;
      gap: 0.6rem;
    }
    .kpi-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 1rem;
      margin-bottom: 1.5rem;
    }
    .kpi-card {
      background: var(--dark-card);
      border: 1px solid var(--border-line);
      border-radius: 12px;
      padding: 1rem 1.2rem;
      display: flex;
      flex-direction: column;
      gap: 0.4rem;
      transition: transform 0.2s, border-color 0.2s;
    }
    .kpi-card:hover {
      transform: translateY(-2px);
      border-color: rgba(252, 185, 0, 0.35);
    }
    .kpi-label {
      font-size: 0.75rem;
      color: #94a3b8;
      text-transform: uppercase;
      font-weight: 700;
      letter-spacing: 0.5px;
    }
    .kpi-value {
      font-size: 1.6rem;
      font-weight: 900;
      color: #fff;
      font-family: 'Oswald', sans-serif;
    }
    .filter-bar {
      background: var(--dark-card);
      border: 1px solid var(--border-line);
      border-radius: 12px;
      padding: 1rem;
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
      margin-bottom: 1.5rem;
    }
    .tabs-nav {
      display: flex;
      flex-wrap: wrap;
      gap: 0.5rem;
    }
    .tab-pill {
      padding: 0.5rem 1rem;
      border-radius: 20px;
      font-size: 0.82rem;
      font-weight: 700;
      text-decoration: none;
      color: #94a3b8;
      background: rgba(255, 255, 255, 0.04);
      border: 1px solid transparent;
      transition: 0.2s;
    }
    .tab-pill:hover, .tab-pill.active {
      color: #000;
      background: var(--gold);
      border-color: var(--gold);
    }
    .search-form {
      display: flex;
      gap: 0.5rem;
      flex: 1;
      max-width: 450px;
    }
    .form-input {
      background: rgba(0, 0, 0, 0.4);
      border: 1px solid var(--border-line);
      color: #fff;
      padding: 0.55rem 1rem;
      border-radius: 8px;
      font-size: 0.85rem;
      width: 100%;
      outline: none;
    }
    .form-input:focus {
      border-color: var(--gold);
    }
    .btn-submit {
      background: var(--gold);
      color: #000;
      border: none;
      font-weight: 800;
      padding: 0.55rem 1.2rem;
      border-radius: 8px;
      cursor: pointer;
      font-size: 0.85rem;
    }
    .product-table-wrap {
      background: var(--dark-card);
      border: 1px solid var(--border-line);
      border-radius: 14px;
      overflow: hidden;
      box-shadow: 0 10px 30px rgba(0,0,0,0.4);
    }
    table.data-table {
      width: 100%;
      border-collapse: collapse;
      text-align: left;
      font-size: 0.85rem;
    }
    table.data-table th {
      background: rgba(255, 255, 255, 0.03);
      padding: 1rem 1.2rem;
      font-weight: 800;
      color: #94a3b8;
      text-transform: uppercase;
      font-size: 0.72rem;
      letter-spacing: 0.5px;
      border-bottom: 1px solid var(--border-line);
    }
    table.data-table td {
      padding: 1rem 1.2rem;
      border-bottom: 1px solid rgba(255, 255, 255, 0.04);
      vertical-align: middle;
    }
    table.data-table tr:hover {
      background: rgba(255, 255, 255, 0.02);
    }
    .prod-item-row {
      display: flex;
      align-items: center;
      gap: 1rem;
    }
    .prod-thumb {
      width: 52px;
      height: 52px;
      border-radius: 8px;
      object-fit: cover;
      background: #000;
      border: 1px solid rgba(255,255,255,0.1);
      flex-shrink: 0;
    }
    .prod-meta-title {
      font-weight: 700;
      color: #fff;
      font-size: 0.92rem;
      margin-bottom: 0.2rem;
    }
    .prod-meta-sub {
      font-size: 0.75rem;
      color: #94a3b8;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    .badge {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      padding: 3px 8px;
      border-radius: 4px;
      font-size: 0.7rem;
      font-weight: 800;
      text-transform: uppercase;
    }
    .badge-trending {
      background: rgba(252, 185, 0, 0.2);
      color: var(--gold);
      border: 1px solid rgba(252, 185, 0, 0.4);
    }
    .badge-live {
      background: rgba(0, 230, 118, 0.15);
      color: var(--green);
      border: 1px solid rgba(0, 230, 118, 0.3);
    }
    .badge-hold {
      background: rgba(255, 82, 82, 0.15);
      color: var(--red);
      border: 1px solid rgba(255, 82, 82, 0.3);
    }
    .badge-affiliate {
      background: rgba(0, 176, 255, 0.15);
      color: var(--blue);
      border: 1px solid rgba(0, 176, 255, 0.3);
    }
    .badge-service {
      background: rgba(168, 85, 247, 0.15);
      color: #c084fc;
      border: 1px solid rgba(168, 85, 247, 0.3);
    }
    .actions-cluster {
      display: flex;
      align-items: center;
      gap: 0.4rem;
      flex-wrap: wrap;
    }
    .action-btn {
      padding: 0.4rem 0.75rem;
      border-radius: 6px;
      font-size: 0.76rem;
      font-weight: 700;
      border: none;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 4px;
      text-decoration: none;
      transition: 0.2s;
    }
    .btn-trend-add {
      background: rgba(252, 185, 0, 0.15);
      color: var(--gold);
      border: 1px solid rgba(252, 185, 0, 0.3);
    }
    .btn-trend-add:hover {
      background: var(--gold);
      color: #000;
    }
    .btn-trend-del {
      background: rgba(255, 255, 255, 0.06);
      color: #94a3b8;
      border: 1px solid rgba(255, 255, 255, 0.15);
    }
    .btn-trend-del:hover {
      background: rgba(255, 82, 82, 0.2);
      color: var(--red);
      border-color: var(--red);
    }
    .btn-hold {
      background: rgba(255, 145, 0, 0.15);
      color: #ff9100;
      border: 1px solid rgba(255, 145, 0, 0.3);
    }
    .btn-hold:hover {
      background: #ff9100;
      color: #000;
    }
    .btn-activate {
      background: rgba(0, 230, 118, 0.15);
      color: var(--green);
      border: 1px solid rgba(0, 230, 118, 0.3);
    }
    .btn-activate:hover {
      background: var(--green);
      color: #000;
    }
    .btn-delete {
      background: rgba(255, 82, 82, 0.15);
      color: var(--red);
      border: 1px solid rgba(255, 82, 82, 0.3);
    }
    .btn-delete:hover {
      background: var(--red);
      color: #fff;
    }
    .alert-msg {
      background: rgba(0, 230, 118, 0.15);
      border: 1px solid var(--green);
      color: #e2e8f0;
      padding: 1rem;
      border-radius: 8px;
      margin-bottom: 1.5rem;
      font-weight: 600;
    }
    .alert-err {
      background: rgba(255, 82, 82, 0.15);
      border: 1px solid var(--red);
      color: #e2e8f0;
      padding: 1rem;
      border-radius: 8px;
      margin-bottom: 1.5rem;
      font-weight: 600;
    }
    @media (max-width: 900px) {
      .data-table thead { display: none; }
      .data-table, .data-table tbody, .data-table tr, .data-table td {
        display: block;
        width: 100%;
        box-sizing: border-box;
      }
      .data-table tr {
        margin-bottom: 1rem;
        background: var(--dark-card);
        border: 1px solid var(--border-line);
        border-radius: 12px;
        padding: 0.8rem;
      }
      .data-table td {
        border-bottom: none;
        padding: 0.5rem 0;
      }
    }
  </style>
</head>
<body>

<?php include __DIR__ . '/nav.php'; ?>

<div class="container">

  <div class="page-header">
    <div>
      <h1 class="page-title">🛍️ Product &amp; Listing Management</h1>
      <div style="font-size:0.85rem; color:#94a3b8; margin-top:0.3rem;">
        Direct control over all products, official services, trending picks, and partner affiliate deals.
      </div>
    </div>
    <div>
      <a href="dashboard.php" class="action-btn btn-trend-del" style="padding:0.6rem 1.2rem; font-size:0.85rem;">← Admin Dashboard</a>
    </div>
  </div>

  <?php if (!empty($msg)): ?>
    <div class="alert-msg">✅ <?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>
  <?php if (!empty($err)): ?>
    <div class="alert-err">⚠️ <?= htmlspecialchars($err) ?></div>
  <?php endif; ?>

  <!-- KPI Summary Bar -->
  <div class="kpi-grid">
    <div class="kpi-card">
      <span class="kpi-label">📦 Total Listings</span>
      <span class="kpi-value"><?= number_format($total_count) ?></span>
    </div>
    <div class="kpi-card" style="border-color:rgba(252,185,0,0.3);">
      <span class="kpi-label" style="color:var(--gold);">🔥 Trending Picks</span>
      <span class="kpi-value" style="color:var(--gold);"><?= number_format($trending_count) ?></span>
    </div>
    <div class="kpi-card">
      <span class="kpi-label">🤝 Services</span>
      <span class="kpi-value"><?= number_format($services_count) ?></span>
    </div>
    <div class="kpi-card" style="border-color:rgba(0,176,255,0.3);">
      <span class="kpi-label" style="color:var(--blue);">⚡ Affiliate Deals</span>
      <span class="kpi-value" style="color:var(--blue);"><?= number_format($affiliate_count) ?></span>
    </div>
    <div class="kpi-card" style="border-color:rgba(255,82,82,0.3);">
      <span class="kpi-label" style="color:var(--red);">⏸️ On Hold / Hidden</span>
      <span class="kpi-value" style="color:var(--red);"><?= number_format($onhold_count) ?></span>
    </div>
  </div>

  <!-- Filter & Search Controls -->
  <div class="filter-bar">
    <div class="tabs-nav">
      <a href="products.php?tab=all<?= $search ? '&search='.urlencode($search) : '' ?>" class="tab-pill <?= $tab === 'all' ? 'active' : '' ?>">All Listings</a>
      <a href="products.php?tab=trending<?= $search ? '&search='.urlencode($search) : '' ?>" class="tab-pill <?= $tab === 'trending' ? 'active' : '' ?>">🔥 Trending (<?= $trending_count ?>)</a>
      <a href="products.php?tab=products<?= $search ? '&search='.urlencode($search) : '' ?>" class="tab-pill <?= $tab === 'products' ? 'active' : '' ?>">📦 Products</a>
      <a href="products.php?tab=services<?= $search ? '&search='.urlencode($search) : '' ?>" class="tab-pill <?= $tab === 'services' ? 'active' : '' ?>">🤝 Services (<?= $services_count ?>)</a>
      <a href="products.php?tab=affiliates<?= $search ? '&search='.urlencode($search) : '' ?>" class="tab-pill <?= $tab === 'affiliates' ? 'active' : '' ?>">⚡ Partner Deals (<?= $affiliate_count ?>)</a>
      <a href="products.php?tab=onhold<?= $search ? '&search='.urlencode($search) : '' ?>" class="tab-pill <?= $tab === 'onhold' ? 'active' : '' ?>">⏸️ On Hold (<?= $onhold_count ?>)</a>
    </div>

    <form method="GET" action="products.php" class="search-form">
      <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
      <input type="text" name="search" class="form-input" placeholder="Search by title, shop, or category..." value="<?= htmlspecialchars($search) ?>">
      <button type="submit" class="btn-submit">Search</button>
      <?php if ($search || $tab !== 'all'): ?>
        <a href="products.php" class="action-btn btn-trend-del" style="padding:0.55rem 0.8rem;">Clear</a>
      <?php endif; ?>
    </form>
  </div>

  <!-- Products List Table -->
  <div class="product-table-wrap">
    <?php if (empty($products)): ?>
      <div style="text-align:center; padding:3.5rem 1rem; color:#94a3b8;">
        <div style="font-size:2.5rem; margin-bottom:0.8rem;">🔍</div>
        <h3 style="color:#fff; margin-bottom:0.4rem;">No Listings Found</h3>
        <p style="font-size:0.85rem; margin-bottom:1rem;">Try clearing the search query or switching tabs.</p>
        <a href="products.php" class="action-btn btn-trend-add">Reset Filters</a>
      </div>
    <?php else: ?>
      <table class="data-table">
        <thead>
          <tr>
            <th>Product / Listing</th>
            <th>Shop / Owner</th>
            <th>Type &amp; Category</th>
            <th>Price</th>
            <th>Status &amp; Trend</th>
            <th style="text-align:right;">Admin Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($products as $p): 
              $isService = ($p['listing_type'] ?? 'product') === 'service';
              $isAff = !empty($p['affiliate_url']) || ($p['listing_type'] ?? '') === 'affiliate';
              $isLive = ((int)($p['is_published'] ?? 1) === 1);
              $isTrending = ((int)($p['is_trending'] ?? 0) === 1);
              
              $thumb = resolveProductArtwork($p['thumb'] ?? '', $p['fallback_img'] ?? '', $p['shop_name'], $p['category'] ?? '', $p['title'], $p['listing_type'] ?? 'product');
              $priceBdt = floatval(preg_replace('/[^0-9.]/', '', $p['price']));
          ?>
          <tr>
            <td>
              <div class="prod-item-row">
                <img src="<?= htmlspecialchars($thumb) ?>" alt="Thumb" class="prod-thumb" onerror="this.src='/assets/images/services/default_service.svg'">
                <div>
                  <div class="prod-meta-title"><?= htmlspecialchars($p['title']) ?></div>
                  <div class="prod-meta-sub">
                    <span>ID #<?= $p['id'] ?></span>
                    <span>&bull;</span>
                    <span>Created: <?= date('d M Y', strtotime($p['created_at'] ?? 'now')) ?></span>
                    <?php if (!empty($p['affiliate_url'])): ?>
                      <span>&bull;</span>
                      <a href="<?= htmlspecialchars($p['affiliate_url']) ?>" target="_blank" style="color:var(--blue); text-decoration:none; font-weight:700;">Partner Link ↗</a>
                    <?php endif; ?>
                    <?php if (!empty($p['audio_file'])): ?>
                      <span>&bull;</span>
                      <a href="/uploads/audio/<?= htmlspecialchars($p['audio_file']) ?>" target="_blank" style="color:var(--teal); text-decoration:none; font-weight:700; background:rgba(0,230,118,0.12); padding:1px 6px; border-radius:4px; border:1px solid rgba(0,230,118,0.25);">🎵 Audio Demo ↗</a>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </td>
            <td>
              <div style="font-weight:700; color:#fff; font-size:0.88rem;"><?= htmlspecialchars($p['shop_name']) ?></div>
              <div style="font-size:0.75rem; color:#94a3b8;"><?= htmlspecialchars($p['owner_name']) ?> <?= $p['shop_phone'] ? '(' . htmlspecialchars($p['shop_phone']) . ')' : '' ?></div>
            </td>
            <td>
              <?php if ($isAff): ?>
                <span class="badge badge-affiliate">⚡ Partner Deal</span>
              <?php elseif ($isService): ?>
                <span class="badge badge-service">🤝 Service</span>
              <?php else: ?>
                <span class="badge" style="background:rgba(255,255,255,0.06); color:#e2e8f0;">📦 Direct Product</span>
              <?php endif; ?>
              <div style="font-size:0.75rem; color:#94a3b8; margin-top:0.3rem;"><?= htmlspecialchars($p['category'] ?? 'General') ?></div>
            </td>
            <td>
              <div style="font-weight:800; color:var(--gold); font-size:0.95rem;">
                <?= $priceBdt > 0 ? number_format($priceBdt) . ' BDT' : 'FREE / PROMO' ?>
              </div>
            </td>
            <td>
              <div style="display:flex; flex-direction:column; gap:0.3rem; align-items:flex-start;">
                <?php if ($isLive): ?>
                  <span class="badge badge-live">🟢 LIVE</span>
                <?php else: ?>
                  <span class="badge badge-hold">⏸️ ON HOLD</span>
                <?php endif; ?>

                <?php if ($isTrending): ?>
                  <span class="badge badge-trending">🔥 TRENDING</span>
                <?php endif; ?>
              </div>
            </td>
            <td style="text-align:right;">
              <div class="actions-cluster" style="justify-content:flex-end;">
                <!-- 1. Trending Add/Cancel Toggle -->
                <form method="POST" action="products.php" style="display:inline;" onsubmit="return confirm('<?= $isTrending ? 'Remove this product from the Trending Section?' : 'Promote this product to the Trending Section?' ?>');">
                  <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                  <input type="hidden" name="action" value="toggle_trending">
                  <?php if ($isTrending): ?>
                    <button type="submit" class="action-btn btn-trend-del" title="Remove from Trending Section">❌ Cancel Trend</button>
                  <?php else: ?>
                    <button type="submit" class="action-btn btn-trend-add" title="Promote to Trending Section">⭐ Add Trending</button>
                  <?php endif; ?>
                </form>

                <!-- 2. Hold / Activate Toggle -->
                <form method="POST" action="products.php" style="display:inline;" onsubmit="return confirm('<?= $isLive ? 'Place this listing on HOLD? Shop owner will be notified.' : 'Activate and restore this listing to the marketplace?' ?>');">
                  <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                  <input type="hidden" name="action" value="toggle_hold">
                  <?php if ($isLive): ?>
                    <button type="submit" class="action-btn btn-hold" title="Put listing on hold (hides from marketplace)">⏸️ Hold</button>
                  <?php else: ?>
                    <button type="submit" class="action-btn btn-activate" title="Activate listing (makes live)">▶️ Unhold</button>
                  <?php endif; ?>
                </form>

                <!-- 3. Delete Action -->
                <form method="POST" action="products.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to permanently DELETE this listing? Shop owner will receive a removal notification.');">
                  <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                  <input type="hidden" name="action" value="delete_product">
                  <button type="submit" class="action-btn btn-delete" title="Permanently delete listing">🗑️ Delete</button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

</div>

</body>
</html>
