<?php
// =========================================================================
// admin/analytics.php — Flagship Data & Platform Analytics Hub
// =========================================================================
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/../config.php';

// Timeframe Filter
$allowed_ranges = [7, 30, 90, 365];
$days_back = intval($_GET['range'] ?? 30);
if (!in_array($days_back, $allowed_ranges)) {
    $days_back = 30;
}

// 1. High-Level Lifetime & Rolling KPI Metrics
$total_orders = 0;
$total_gmv = 0;
$total_revenue = 0;
$total_shops = 0;
$total_customers = 0;
$open_disputes = 0;

try {
    $stmt = $pdo->query("SELECT COUNT(id) as cnt, COALESCE(SUM(total_coins), 0) as gmv FROM partner_orders");
    if ($row = $stmt->fetch()) {
        $total_orders = (int)$row['cnt'];
        $total_gmv = (float)$row['gmv'];
    }
} catch (Exception $e) {}

try {
    $stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM coin_transactions WHERE type IN ('deposit', 'credit')");
    $total_revenue = (float)$stmt->fetchColumn();
} catch (Exception $e) {}

try {
    $stmt = $pdo->query("SELECT COUNT(id) FROM partners WHERE status = 'approved'");
    $total_shops = (int)$stmt->fetchColumn();
} catch (Exception $e) {}

try {
    $stmt = $pdo->query("SELECT COUNT(id) FROM users");
    $total_customers = (int)$stmt->fetchColumn();
} catch (Exception $e) {}

try {
    $stmt = $pdo->query("SELECT COUNT(id) FROM partner_disputes WHERE status = 'open'");
    $open_disputes = (int)$stmt->fetchColumn();
} catch (Exception $e) {}

// 2. Chart Data Generation: Revenue & Volume Over Time
$chart_labels = [];
$chart_data_revenue = [];
$chart_data_gmv = [];
$chart_data_users = [];
$chart_data_shops = [];

$step = 1;
if ($days_back == 30) $step = 3;
if ($days_back == 90) $step = 7;
if ($days_back == 365) $step = 30;

for ($i = $days_back - 1; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    if ($i % $step === 0 || $i === 0) {
        $chart_labels[] = date('M d', strtotime($date));
    } else {
        $chart_labels[] = ''; // Sparse labels for clean rendering
    }
    
    // Revenue Inflow
    $stmtRev = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM coin_transactions WHERE DATE(created_at) = :d AND type IN ('deposit', 'credit')");
    $stmtRev->execute([':d' => $date]);
    $chart_data_revenue[] = round(floatval($stmtRev->fetchColumn()), 2);

    // Marketplace GMV
    $stmtGMV = $pdo->prepare("SELECT COALESCE(SUM(total_coins), 0) FROM partner_orders WHERE DATE(created_at) = :d");
    $stmtGMV->execute([':d' => $date]);
    $chart_data_gmv[] = round(floatval($stmtGMV->fetchColumn()), 2);

    // Active Buyers
    $stmtUsr = $pdo->prepare("SELECT COUNT(DISTINCT customer_id) FROM partner_orders WHERE DATE(created_at) = :d");
    $stmtUsr->execute([':d' => $date]);
    $chart_data_users[] = intval($stmtUsr->fetchColumn());

    // New Registered Shops
    $stmtShops = $pdo->prepare("SELECT COUNT(id) FROM partners WHERE DATE(created_at) = :d");
    $stmtShops->execute([':d' => $date]);
    $chart_data_shops[] = intval($stmtShops->fetchColumn());
}

$chart_labels_json = json_encode($chart_labels);
$chart_revenue_json = json_encode($chart_data_revenue);
$chart_gmv_json = json_encode($chart_data_gmv);
$chart_users_json = json_encode($chart_data_users);
$chart_shops_json = json_encode($chart_data_shops);

// 3. Category Sales Distribution (Doughnut Chart)
$dist_labels = [];
$dist_data = [];
$dist_colors = ['#fcb900', '#10b981', '#6366f1', '#ec4899', '#38bdf8', '#8b5cf6'];

try {
    $dist_sql = "SELECT COALESCE(p.category, 'General') as cat, COUNT(o.id) as order_count 
                 FROM partner_orders o 
                 JOIN partner_products p ON o.product_id = p.id 
                 GROUP BY cat 
                 ORDER BY order_count DESC LIMIT 6";
    $dist_stmt = $pdo->query($dist_sql);
    while ($row = $dist_stmt->fetch()) {
        $dist_labels[] = htmlspecialchars($row['cat'] ?: 'General');
        $dist_data[] = (int)$row['order_count'];
    }
} catch (Exception $e) {}

if (empty($dist_labels)) {
    $dist_labels = ['No Orders Yet'];
    $dist_data = [1];
    $dist_colors = ['#334155'];
}

$dist_labels_json = json_encode($dist_labels);
$dist_data_json = json_encode($dist_data);
$dist_colors_json = json_encode(array_slice($dist_colors, 0, count($dist_labels)));

// 4. Top Performing Shops
$top_shops = [];
try {
    $top_shops = $pdo->query("
        SELECT p.id, p.business_name, p.owner_name, p.is_official, p.category, p.status,
               COUNT(o.id) as total_orders,
               COALESCE(SUM(o.total_coins), 0) as total_volume
        FROM partners p
        LEFT JOIN partner_orders o ON p.id = o.partner_id
        GROUP BY p.id, p.business_name, p.owner_name, p.is_official, p.category, p.status
        ORDER BY total_volume DESC, total_orders DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $top_shops = [];
}

// 5. Top Selling Products
$top_products = [];
try {
    $top_products = $pdo->query("
        SELECT pr.id, pr.title, pr.category, pr.price_coins,
               p.business_name as shop_name,
               COUNT(o.id) as units_sold,
               COALESCE(SUM(o.total_coins), 0) as product_volume
        FROM partner_products pr
        LEFT JOIN partner_orders o ON pr.id = o.product_id
        LEFT JOIN partners p ON pr.partner_id = p.id
        GROUP BY pr.id, pr.title, pr.category, pr.price_coins, p.business_name
        ORDER BY units_sold DESC, product_volume DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $top_products = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0"/>
  <title>Data & Analytics Hub — Fast Site Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Oswald:wght@600;700&display=swap" rel="stylesheet"/>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link rel="stylesheet" href="/assets/css/admin.css?v=<?= time() ?>"/>
  <style>
    /* Analytics Specific Styling */
    .kpi-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      gap: 1.2rem;
      margin-bottom: 2rem;
    }
    .kpi-card {
      background: rgba(16, 18, 28, 0.85);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 18px;
      padding: 1.5rem;
      position: relative;
      overflow: hidden;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
      backdrop-filter: blur(14px);
      transition: transform 0.2s, box-shadow 0.2s;
    }
    .kpi-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 14px 35px rgba(0, 0, 0, 0.5);
      border-color: rgba(252, 185, 0, 0.3);
    }
    .kpi-label {
      font-size: 0.75rem;
      font-weight: 800;
      color: var(--muted);
      text-transform: uppercase;
      letter-spacing: 0.8px;
      margin-bottom: 0.4rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .kpi-value {
      font-size: 2.1rem;
      font-weight: 800;
      font-family: 'Oswald', sans-serif;
      line-height: 1.1;
      color: #fff;
    }
    .kpi-sub {
      font-size: 0.75rem;
      color: #94a3b8;
      margin-top: 0.5rem;
      display: flex;
      align-items: center;
      gap: 0.35rem;
    }
    .kpi-icon-bg {
      position: absolute;
      right: -10px;
      bottom: -15px;
      font-size: 5rem;
      opacity: 0.05;
      pointer-events: none;
    }
    
    .filter-pills {
      display: flex;
      gap: 0.5rem;
      flex-wrap: wrap;
      align-items: center;
      margin-top: 1rem;
    }
    .filter-pill {
      padding: 0.45rem 1rem;
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 20px;
      color: #94a3b8;
      font-size: 0.8rem;
      font-weight: 700;
      text-decoration: none;
      transition: all 0.2s;
    }
    .filter-pill:hover {
      background: rgba(255, 255, 255, 0.12);
      color: #fff;
    }
    .filter-pill.active {
      background: linear-gradient(135deg, var(--gold) 0%, #f59e0b 100%);
      color: #000;
      border-color: var(--gold);
      box-shadow: 0 4px 12px rgba(252, 185, 0, 0.3);
    }

    .chart-box {
      background: rgba(16, 18, 28, 0.85);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 18px;
      padding: 1.6rem;
      margin-bottom: 2rem;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
      backdrop-filter: blur(14px);
    }
    .chart-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 1.5rem;
      flex-wrap: wrap;
      gap: 0.8rem;
    }
    .chart-title {
      font-family: 'Oswald', sans-serif;
      font-size: 1.15rem;
      letter-spacing: 0.5px;
      color: #fff;
      display: flex;
      align-items: center;
      gap: 0.5rem;
      text-transform: uppercase;
    }

    .analytics-grid-2 {
      display: grid;
      grid-template-columns: 2fr 1fr;
      gap: 1.5rem;
      margin-bottom: 2rem;
    }
    @media (max-width: 980px) {
      .analytics-grid-2 {
        grid-template-columns: 1fr;
      }
    }

    .table-card {
      background: rgba(16, 18, 28, 0.85);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 18px;
      padding: 1.5rem;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
      backdrop-filter: blur(14px);
      overflow-x: auto;
    }
    .rank-badge {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 26px;
      height: 26px;
      border-radius: 50%;
      font-weight: 800;
      font-size: 0.75rem;
    }
    .rank-1 { background: rgba(252, 185, 0, 0.2); color: #fcb900; border: 1px solid #fcb900; }
    .rank-2 { background: rgba(148, 163, 184, 0.2); color: #cbd5e1; border: 1px solid #cbd5e1; }
    .rank-3 { background: rgba(180, 83, 9, 0.2); color: #f59e0b; border: 1px solid #f59e0b; }
    .rank-other { background: rgba(255, 255, 255, 0.05); color: #94a3b8; }
  </style>
</head>
<body>

<!-- Unified Master Top Navigation -->
<?php include __DIR__ . '/nav.php'; ?>

<div class="dashboard-container">

  <!-- Header Admin Hero -->
  <div class="admin-hero">
    <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:1rem;">
      <div>
        <h2 class="admin-hero-title">📊 DATA & ANALYTICS HUB</h2>
        <p class="admin-hero-subtitle">Comprehensive Ecosystem Metrics, Revenue Growth, and Buyer Volume</p>
      </div>
      <div style="display:flex; gap:0.6rem; flex-wrap:wrap;">
        <a href="wallet.php" class="btn-sm" style="display:inline-flex; align-items:center; gap:0.4rem; padding: 0.55rem 1.2rem; background: rgba(252, 185, 0, 0.15); border:1px solid #fcb900; color:#fcb900; font-weight:700; text-decoration:none; border-radius:10px;">
          💼 Master Wallet ➔
        </a>
        <a href="partner_shops.php" class="btn-sm" style="display:inline-flex; align-items:center; gap:0.4rem; padding: 0.55rem 1.2rem; background: rgba(99, 102, 241, 0.15); border:1px solid #6366f1; color:#fff; font-weight:700; text-decoration:none; border-radius:10px;">
          🏪 Shops & Escrow ➔
        </a>
      </div>
    </div>

    <!-- Timeframe Filter Buttons -->
    <div class="filter-pills">
      <span style="font-size:0.75rem; font-weight:800; color:var(--muted); text-transform:uppercase; margin-right:0.3rem;">Range:</span>
      <a href="?range=7" class="filter-pill <?= $days_back == 7 ? 'active' : '' ?>">Last 7 Days</a>
      <a href="?range=30" class="filter-pill <?= $days_back == 30 ? 'active' : '' ?>">Last 30 Days</a>
      <a href="?range=90" class="filter-pill <?= $days_back == 90 ? 'active' : '' ?>">Last 90 Days</a>
      <a href="?range=365" class="filter-pill <?= $days_back == 365 ? 'active' : '' ?>">Full Year (365d)</a>
    </div>
  </div>

  <!-- Key Performance Stat Cards (4 Cards) -->
  <div class="kpi-grid">
    
    <!-- 1. Total Coin Revenue & Deposits -->
    <div class="kpi-card">
      <div class="kpi-label">
        <span>💰 Inflow & Deposits</span>
        <span style="color:#10b981; font-weight:700;">Live Economy</span>
      </div>
      <div class="kpi-value" style="color:#10b981;">
        <?= number_format($total_revenue, 2) ?> <span style="font-size:1.1rem; color:#94a3b8;">Coins</span>
      </div>
      <div class="kpi-sub">
        <span>⚡ Inflow across platform deposits</span>
      </div>
      <div class="kpi-icon-bg">🪙</div>
    </div>

    <!-- 2. Marketplace Orders & GMV -->
    <div class="kpi-card">
      <div class="kpi-label">
        <span>🛒 Total Marketplace GMV</span>
        <span style="color:#fcb900; font-weight:700;"><?= number_format($total_orders) ?> Orders</span>
      </div>
      <div class="kpi-value" style="color:#fcb900;">
        <?= number_format($total_gmv, 2) ?> <span style="font-size:1.1rem; color:#94a3b8;">Coins</span>
      </div>
      <div class="kpi-sub">
        <span>📦 Lifetime marketplace sales volume</span>
      </div>
      <div class="kpi-icon-bg">🛍️</div>
    </div>

    <!-- 3. Ecosystem Stores -->
    <div class="kpi-card">
      <div class="kpi-label">
        <span>🏪 Active Storefronts</span>
        <span style="color:#6366f1; font-weight:700;">Approved</span>
      </div>
      <div class="kpi-value" style="color:#6366f1;">
        <?= number_format($total_shops) ?> <span style="font-size:1.1rem; color:#94a3b8;">Shops</span>
      </div>
      <div class="kpi-sub">
        <span>🌐 Verified sellers & ecosystem brands</span>
      </div>
      <div class="kpi-icon-bg">🏢</div>
    </div>

    <!-- 4. Registered Users -->
    <div class="kpi-card">
      <div class="kpi-label">
        <span>👥 Registered Buyers</span>
        <span style="color:#38bdf8; font-weight:700;">Userbase</span>
      </div>
      <div class="kpi-value" style="color:#38bdf8;">
        <?= number_format($total_customers) ?> <span style="font-size:1.1rem; color:#94a3b8;">Users</span>
      </div>
      <div class="kpi-sub">
        <span>🪪 Active customer accounts</span>
      </div>
      <div class="kpi-icon-bg">👤</div>
    </div>

  </div>

  <!-- Primary Line Chart: Revenue & GMV Growth -->
  <div class="chart-box">
    <div class="chart-header">
      <div class="chart-title">
        <span>📈</span> <?= $days_back ?>-Day Revenue & Marketplace GMV Trend
      </div>
      <div style="font-size:0.8rem; color:#94a3b8; display:flex; align-items:center; gap:1rem;">
        <span style="display:flex; align-items:center; gap:5px;"><span style="width:10px; height:10px; background:#10b981; border-radius:50%; display:inline-block;"></span> Revenue (Inflow)</span>
        <span style="display:flex; align-items:center; gap:5px;"><span style="width:10px; height:10px; background:#fcb900; border-radius:50%; display:inline-block;"></span> Marketplace GMV</span>
      </div>
    </div>
    <div style="position:relative; height:320px; width:100%;">
      <canvas id="revenueGmvChart"></canvas>
    </div>
  </div>

  <!-- Secondary Grid: Ecosystem Activity & Category Distribution -->
  <div class="analytics-grid-2">
    
    <!-- Ecosystem Activity (Bar Chart) -->
    <div class="chart-box" style="margin-bottom:0;">
      <div class="chart-header">
        <div class="chart-title">
          <span>📊</span> User Acquisition & Ecosystem Activity
        </div>
      </div>
      <div style="position:relative; height:280px; width:100%;">
        <canvas id="activityChart"></canvas>
      </div>
    </div>

    <!-- Category Sales Distribution (Doughnut Chart) -->
    <div class="chart-box" style="margin-bottom:0;">
      <div class="chart-header">
        <div class="chart-title">
          <span>🥧</span> Sales by Category
        </div>
      </div>
      <div style="position:relative; height:280px; width:100%;">
        <canvas id="distChart"></canvas>
      </div>
    </div>

  </div>

  <!-- Top Performing Shops & Top Products Grid -->
  <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(360px, 1fr)); gap:1.5rem; margin-top:2rem; margin-bottom:2.5rem;">
    
    <!-- Top Performing Shops -->
    <div class="table-card">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.2rem;">
        <h3 style="font-family:'Oswald',sans-serif; font-size:1.1rem; text-transform:uppercase; color:#fcb900; display:flex; align-items:center; gap:0.5rem; margin:0;">
          🏆 Top Performing Shops
        </h3>
        <a href="partner_shops.php" style="font-size:0.75rem; color:#94a3b8; text-decoration:none; font-weight:700;">View All ➔</a>
      </div>

      <?php if (!empty($top_shops)): ?>
        <table style="width:100%; border-collapse:collapse; text-align:left; font-size:0.85rem;">
          <thead>
            <tr style="border-bottom:1px solid rgba(255,255,255,0.08); color:var(--muted); font-size:0.72rem; text-transform:uppercase;">
              <th style="padding:0.6rem 0.4rem;">Rank</th>
              <th style="padding:0.6rem 0.4rem;">Shop Name</th>
              <th style="padding:0.6rem 0.4rem; text-align:center;">Orders</th>
              <th style="padding:0.6rem 0.4rem; text-align:right;">Total Volume</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($top_shops as $idx => $shop): ?>
              <?php 
                $rank = $idx + 1;
                $rank_class = $rank === 1 ? 'rank-1' : ($rank === 2 ? 'rank-2' : ($rank === 3 ? 'rank-3' : 'rank-other'));
              ?>
              <tr style="border-bottom:1px solid rgba(255,255,255,0.04);">
                <td style="padding:0.75rem 0.4rem;">
                  <span class="rank-badge <?= $rank_class ?>"><?= $rank ?></span>
                </td>
                <td style="padding:0.75rem 0.4rem;">
                  <div style="font-weight:700; color:#fff; display:flex; align-items:center; gap:5px;">
                    <?= htmlspecialchars($shop['business_name']) ?>
                    <?php if (!empty($shop['is_official'])): ?>
                      <span style="font-size:0.65rem; background:rgba(252,185,0,0.2); color:#fcb900; padding:2px 6px; border-radius:4px; font-weight:800;">OFFICIAL</span>
                    <?php endif; ?>
                  </div>
                  <div style="font-size:0.7rem; color:#94a3b8;"><?= htmlspecialchars($shop['owner_name'] ?: 'Partner') ?></div>
                </td>
                <td style="padding:0.75rem 0.4rem; text-align:center; font-weight:700; color:#cbd5e1;">
                  <?= number_format($shop['total_orders']) ?>
                </td>
                <td style="padding:0.75rem 0.4rem; text-align:right; font-weight:800; font-family:'Oswald',sans-serif; color:#10b981; font-size:0.95rem;">
                  <?= number_format($shop['total_volume'], 2) ?> <span style="font-size:0.75rem; color:#94a3b8;">Coins</span>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <div style="text-align:center; padding:2rem; color:#94a3b8; font-size:0.85rem;">No shop orders recorded yet.</div>
      <?php endif; ?>
    </div>

    <!-- Top Selling Listings -->
    <div class="table-card">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.2rem;">
        <h3 style="font-family:'Oswald',sans-serif; font-size:1.1rem; text-transform:uppercase; color:#6366f1; display:flex; align-items:center; gap:0.5rem; margin:0;">
          🛍️ Top Selling Products
        </h3>
        <a href="dashboard.php" style="font-size:0.75rem; color:#94a3b8; text-decoration:none; font-weight:700;">All Products ➔</a>
      </div>

      <?php if (!empty($top_products)): ?>
        <table style="width:100%; border-collapse:collapse; text-align:left; font-size:0.85rem;">
          <thead>
            <tr style="border-bottom:1px solid rgba(255,255,255,0.08); color:var(--muted); font-size:0.72rem; text-transform:uppercase;">
              <th style="padding:0.6rem 0.4rem;">Rank</th>
              <th style="padding:0.6rem 0.4rem;">Product Listing</th>
              <th style="padding:0.6rem 0.4rem; text-align:center;">Units</th>
              <th style="padding:0.6rem 0.4rem; text-align:right;">Sales Volume</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($top_products as $idx => $prod): ?>
              <?php 
                $rank = $idx + 1;
                $rank_class = $rank === 1 ? 'rank-1' : ($rank === 2 ? 'rank-2' : ($rank === 3 ? 'rank-3' : 'rank-other'));
              ?>
              <tr style="border-bottom:1px solid rgba(255,255,255,0.04);">
                <td style="padding:0.75rem 0.4rem;">
                  <span class="rank-badge <?= $rank_class ?>"><?= $rank ?></span>
                </td>
                <td style="padding:0.75rem 0.4rem;">
                  <div style="font-weight:700; color:#fff; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:200px;">
                    <?= htmlspecialchars($prod['title']) ?>
                  </div>
                  <div style="font-size:0.7rem; color:#94a3b8;"><?= htmlspecialchars($prod['shop_name'] ?: 'Marketplace') ?></div>
                </td>
                <td style="padding:0.75rem 0.4rem; text-align:center; font-weight:700; color:#cbd5e1;">
                  <?= number_format($prod['units_sold']) ?>
                </td>
                <td style="padding:0.75rem 0.4rem; text-align:right; font-weight:800; font-family:'Oswald',sans-serif; color:#fcb900; font-size:0.95rem;">
                  <?= number_format($prod['product_volume'], 2) ?> <span style="font-size:0.75rem; color:#94a3b8;">Coins</span>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <div style="text-align:center; padding:2rem; color:#94a3b8; font-size:0.85rem;">No product sales recorded yet.</div>
      <?php endif; ?>
    </div>

  </div>

</div>

<!-- Chart.js Configuration -->
<script>
  Chart.defaults.color = '#94a3b8';
  Chart.defaults.font.family = "'Inter', sans-serif";
  Chart.defaults.borderColor = 'rgba(255,255,255,0.06)';

  // 1. Dual Line & Area Chart (Revenue & GMV)
  const gmvCtx = document.getElementById('revenueGmvChart').getContext('2d');
  const revGradient = gmvCtx.createLinearGradient(0, 0, 0, 300);
  revGradient.addColorStop(0, 'rgba(16, 185, 129, 0.35)');
  revGradient.addColorStop(1, 'rgba(16, 185, 129, 0.0)');

  const gmvGradient = gmvCtx.createLinearGradient(0, 0, 0, 300);
  gmvGradient.addColorStop(0, 'rgba(252, 185, 0, 0.25)');
  gmvGradient.addColorStop(1, 'rgba(252, 185, 0, 0.0)');

  new Chart(gmvCtx, {
    type: 'line',
    data: {
      labels: <?= $chart_labels_json ?>,
      datasets: [
        {
          label: 'Platform Revenue (Inflow Coins)',
          data: <?= $chart_revenue_json ?>,
          borderColor: '#10b981',
          backgroundColor: revGradient,
          borderWidth: 3,
          tension: 0.35,
          fill: true,
          pointBackgroundColor: '#10b981',
          pointBorderColor: '#fff',
          pointRadius: 3,
          pointHoverRadius: 6
        },
        {
          label: 'Marketplace GMV (Order Coins)',
          data: <?= $chart_gmv_json ?>,
          borderColor: '#fcb900',
          backgroundColor: gmvGradient,
          borderWidth: 2.5,
          borderDash: [4, 4],
          tension: 0.35,
          fill: true,
          pointBackgroundColor: '#fcb900',
          pointBorderColor: '#fff',
          pointRadius: 3,
          pointHoverRadius: 6
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: {
        mode: 'index',
        intersect: false,
      },
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: 'rgba(15, 17, 26, 0.95)',
          titleColor: '#fcb900',
          bodyColor: '#fff',
          borderColor: 'rgba(255,255,255,0.1)',
          borderWidth: 1,
          padding: 12,
          boxPadding: 6,
          usePointStyle: true,
          callbacks: {
            label: function(context) {
              return context.dataset.label + ': ' + Number(context.raw).toLocaleString() + ' Coins';
            }
          }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          grid: { color: 'rgba(255,255,255,0.05)' },
          ticks: {
            callback: function(value) {
              return value.toLocaleString() + ' C';
            }
          }
        },
        x: {
          grid: { display: false }
        }
      }
    }
  });

  // 2. Ecosystem Activity Chart (Bar)
  const actCtx = document.getElementById('activityChart').getContext('2d');
  new Chart(actCtx, {
    type: 'bar',
    data: {
      labels: <?= $chart_labels_json ?>,
      datasets: [
        {
          label: 'Active Buyers',
          data: <?= $chart_users_json ?>,
          backgroundColor: '#6366f1',
          borderRadius: 4
        },
        {
          label: 'New Partner Stores',
          data: <?= $chart_shops_json ?>,
          backgroundColor: '#fcb900',
          borderRadius: 4
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { position: 'top', labels: { boxWidth: 12, boxHeight: 12 } },
        tooltip: {
          backgroundColor: 'rgba(15, 17, 26, 0.95)',
          borderColor: 'rgba(255,255,255,0.1)',
          borderWidth: 1,
          padding: 10
        }
      },
      scales: {
        y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.05)' } },
        x: { grid: { display: false } }
      }
    }
  });

  // 3. Category Distribution Chart (Doughnut)
  const distCtx = document.getElementById('distChart').getContext('2d');
  new Chart(distCtx, {
    type: 'doughnut',
    data: {
      labels: <?= $dist_labels_json ?>,
      datasets: [{
        data: <?= $dist_data_json ?>,
        backgroundColor: <?= $dist_colors_json ?>,
        borderWidth: 0,
        hoverOffset: 6
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'bottom',
          labels: { boxWidth: 12, boxHeight: 12, padding: 12 }
        },
        tooltip: {
          backgroundColor: 'rgba(15, 17, 26, 0.95)',
          borderColor: 'rgba(255,255,255,0.1)',
          borderWidth: 1,
          padding: 10,
          callbacks: {
            label: function(context) {
              return ' ' + context.label + ': ' + context.raw + ' Orders';
            }
          }
        }
      },
      cutout: '72%'
    }
  });
</script>
</body>
</html>
