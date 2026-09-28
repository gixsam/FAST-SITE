<?php
// =========================================================================
// admin/dashboard.php — Premium Animated Admin Workspace
// =========================================================================
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php'); exit;
}
require_once __DIR__ . '/../config.php';

// Check default dashboard view redirect
$settings = $GLOBALS['settings'] ?? [];
$default_dash_key = $settings['default_dashboard_view'] ?? 'staff_dash';
if ($default_dash_key !== 'staff_dash' && !isset($_GET['no_redirect'])) {
    $dash_links = [
        'user_site'      => '../index.php',
        'user_dash'      => '../user/dashboard.php',
        'api_dash'       => 'api_partners.php',
        'agent_dash'     => '../user/dashboard.php',
        'affiliate_dash' => 'partner_shops.php',
        'shopper_dash'   => '../user/partner_orders.php'
    ];
    if (isset($dash_links[$default_dash_key])) {
        header('Location: ' . $dash_links[$default_dash_key]);
        exit;
    }
}

// Handle inline status + notes update 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_id'])) {
    $upId     = (int)$_POST['update_id'];
    $upStatus = $_POST['status'] ?? 'pending';
    $upNotes  = trim($_POST['admin_notes'] ?? '');
    $allowed  = ['pending','processing','approved','cancelled'];
    if (!in_array($upStatus, $allowed)) $upStatus = 'pending';
    
    $pdo->prepare("UPDATE `applications` SET `status`=:s,`admin_notes`=:n WHERE `id`=:id")
        ->execute([':s'=>$upStatus,':n'=>$upNotes,':id'=>$upId]);
    header('Location: dashboard.php?updated=1');
    exit;
}

// Handle cancel 
if (isset($_GET['cancel'])) {
    $cid = (int)$_GET['cancel'];
    $pdo->prepare("UPDATE `applications` SET `status`='cancelled' WHERE `id`=:id")
        ->execute([':id'=>$cid]);
    header('Location: dashboard.php?cancelled=1');
    exit;
}

// Filters & Search 
$filter = trim($_GET['filter'] ?? '');
$search = trim($_GET['search'] ?? '');
$where  = []; $params = [];

if ($filter && in_array($filter, ['pending','processing','approved','cancelled'])) {
    $where[]           = '`a`.`status`=:filter';
    $params[':filter'] = $filter;
}
if ($search) {
    $where[]      = '(`a`.`user_name` LIKE :s OR `a`.`user_phone` LIKE :s OR `a`.`nid_number` LIKE :s OR `a`.`passport_number` LIKE :s)';
    $params[':s'] = '%'.$search.'%';
}
$wsql = $where ? 'WHERE '.implode(' AND ',$where) : '';

$apps = $pdo->prepare("
    SELECT a.*, s.name AS service_name
    FROM `applications` a
    JOIN `services` s ON a.service_id=s.id
    $wsql
    ORDER BY a.created_at DESC
");
$apps->execute($params);
$applications = $apps->fetchAll();

// Stat counts 
$counts = $pdo->query(
    "SELECT status, COUNT(*) AS cnt FROM applications GROUP BY status"
)->fetchAll(PDO::FETCH_KEY_PAIR);

// Top Metric Cards KPI Queries
try {
    $total_shops_count = (int)$pdo->query("SELECT COUNT(*) FROM partners WHERE status = 'approved'")->fetchColumn();
    $pending_shops_count = (int)$pdo->query("SELECT COUNT(*) FROM partners WHERE status = 'pending'")->fetchColumn();
    $tx_requests_count = (int)$pdo->query("SELECT (SELECT COUNT(*) FROM deposit_requests WHERE status = 'pending') + (SELECT COUNT(*) FROM user_withdrawals WHERE status = 'pending')")->fetchColumn();
    $active_tasks_count = (int)$pdo->query("SELECT COUNT(*) FROM agent_tasks WHERE status = 'active'")->fetchColumn();
    $escrow_disputes_count = (int)$pdo->query("SELECT COUNT(*) FROM partner_disputes WHERE admin_decision = 'pending'")->fetchColumn();
} catch (Exception $e) {
    $total_shops_count = $pending_shops_count = $tx_requests_count = $active_tasks_count = $escrow_disputes_count = 0;
}

// Chart Data (Last 30 Days - Revenue & Active Users)
$chart_labels = [];
$chart_data_revenue = [];
$chart_data_users = [];
for ($i = 29; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    if ($i % 3 === 0 || $i === 0) {
        $chart_labels[] = date('M d', strtotime($date));
    } else {
        $chart_labels[] = ''; // Sparse labels for better fit
    }
    
    // Revenue (Sum of deposits)
    $stmtRev = $pdo->prepare("SELECT SUM(amount) FROM coin_transactions WHERE DATE(created_at) = :d AND type = 'deposit'");
    $stmtRev->execute([':d' => $date]);
    $chart_data_revenue[] = floatval($stmtRev->fetchColumn());

    // Active Users (Distinct users making orders)
    $stmtUsr = $pdo->prepare("SELECT COUNT(DISTINCT customer_id) FROM partner_orders WHERE DATE(created_at) = :d");
    $stmtUsr->execute([':d' => $date]);
    $chart_data_users[] = intval($stmtUsr->fetchColumn());
}
$chart_labels_json = json_encode($chart_labels);
$chart_revenue_json = json_encode($chart_data_revenue);
$chart_users_json = json_encode($chart_data_users);

// Settings & Branding
$settings = $pdo->query("SELECT setting_key, setting_value FROM homepage_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
$global_notice = $settings['global_notice'] ?? '';
$chatCount = (int)$pdo->query("SELECT COUNT(*) FROM chat_sessions WHERE status='with_agent'")->fetchColumn();

// Status badge formatter
function statusBadge($st) {
    $map = [
        'pending'    => ['#ff9800','Pending'],
        'processing' => ['#2196f3','Processing'],
        'approved'   => ['#00e676','Approved'],
        'cancelled'  => ['#ff5252','Cancelled'],
    ];
    [$c,$l] = $map[$st] ?? ['#888','Unknown'];
    return "<span class=\"status-capsule\" style=\"background:rgba(" . hex2rgb($c) . ", 0.14); color:{$c}; border:1px solid rgba(" . hex2rgb($c) . ", 0.35);\">{$l}</span>";
}

function hex2rgb($hex) {
   $hex = str_replace("#", "", $hex);
   if(strlen($hex) == 3) {
      $r = hexdec(substr($hex,0,1).substr($hex,0,1));
      $g = hexdec(substr($hex,1,1).substr($hex,1,1));
      $b = hexdec(substr($hex,2,1).substr($hex,2,1));
   } else {
      $r = hexdec(substr($hex,0,2));
      $g = hexdec(substr($hex,2,2));
      $b = hexdec(substr($hex,4,2));
   }
   return "$r, $g, $b";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Dashboard — Fast Site</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Oswald:wght@600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="/assets/css/admin.css?v=<?= time() ?>">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');
    :root {
      --gold: #fcb900;
      --brand: #6366f1;
      --dark: #080911;
      --dark-card: rgba(16, 18, 28, 0.85);
      --border: rgba(255, 255, 255, 0.09);
      --text: #f8fafc;
      --muted: #94a3b8;
    }
    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
      background: #080911 !important;
      background-image: 
        radial-gradient(at 0% 0%, rgba(99, 102, 241, 0.08) 0px, transparent 50%),
        radial-gradient(at 100% 0%, rgba(252, 185, 0, 0.08) 0px, transparent 50%),
        radial-gradient(at 50% 100%, rgba(16, 185, 129, 0.05) 0px, transparent 50%) !important;
      color: #f8fafc !important;
    }
    /* Inline Guarantee Styles for Layout Integrity */
    .stats {
      display: grid !important;
      grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)) !important;
      gap: 14px !important;
      margin-bottom: 1.8rem !important;
    }
    .stat-card {
      background: rgba(16, 18, 28, 0.85) !important;
      border-radius: 16px !important;
      padding: 1.25rem 1rem !important;
      text-align: center !important;
      backdrop-filter: blur(12px) !important;
      transition: transform 0.3s ease, box-shadow 0.3s ease !important;
      position: relative;
      overflow: hidden;
    }
    .stat-card .card-watermark {
        position: absolute;
        right: 12px;
        bottom: 5px;
        font-size: 3.8rem;
        opacity: 0.12;
        pointer-events: none;
        user-select: none;
        z-index: 0;
    }
    .stat-card > * {
        position: relative;
        z-index: 1;
    }
    .stat-card:hover {
      transform: translateY(-4px) !important;
      box-shadow: 0 12px 28px rgba(0, 0, 0, 0.4) !important;
    }
    .admin-hero-title {
      font-family: 'Oswald', sans-serif !important;
      font-size: clamp(1.2rem, 3.5vw, 1.8rem) !important;
      color: #ffffff !important;
      text-transform: uppercase !important;
    }
  </style>
</head>
<body>

<?php include 'nav.php'; ?>

<?php if($global_notice): ?>
<div class="notice-marquee">
  <span style="background:#fff;color:#ff004c;padding:2px 8px;border-radius:50px;font-size:.65rem;margin-right:10px;z-index:2;position:relative;font-weight:800;">NOTICE</span>
  <marquee scrollamount="5" style="flex:1;"><?= htmlspecialchars($global_notice) ?></marquee>
</div>
<?php endif; ?>

<div class="dashboard-container">

<!-- Header Admin Hero -->
<div class="admin-hero">
  <h2 class="admin-hero-title">⚡ FAST SITE SYSTEM PANEL</h2>
  <p class="admin-hero-subtitle">System Administrator & Staff Workspace</p>
  
  <div style="margin-top: 0.8rem; display:flex; gap:0.6rem; flex-wrap:wrap;">
    <button type="button" onclick="openQuickUploadModal()" class="btn-sm" style="display:inline-flex; align-items:center; gap:0.4rem; padding: 0.55rem 1.2rem; background: linear-gradient(135deg, var(--gold) 0%, #f59e0b 100%); color:#000; font-weight:800; border:none; border-radius:8px; cursor:pointer; box-shadow:0 4px 15px rgba(252,185,0,0.35);">
      ✨ Quick Product / Service Upload
    </button>
    <a href="impersonate_official.php?redirect=product_add.php" class="btn-sm" style="display:inline-flex; align-items:center; gap:0.4rem; padding: 0.55rem 1.1rem; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12); color:#fff; font-weight:700; text-decoration:none; border-radius:8px;">
      🎨 Full Product Studio ↗
    </a>
    <button onclick="document.querySelector('.fs-chat-launcher')?.click(); return false;" style="display:inline-flex; align-items:center; gap:0.4rem; padding: 0.55rem 1.1rem; background: rgba(99, 102, 241, 0.15); border: 1px solid #6366f1; color: #818cf8; border-radius: 10px; font-size: 0.8rem; font-weight: 700; cursor: pointer;">
      🤖 Smart AI Guide
    </button>
  </div>
  
  <div class="tabs-nav">
    <button class="tab-btn active" id="btn-tab-registry" onclick="switchTab(event, 'tab-registry')">📋 Applications Registry</button>
    <button class="tab-btn" id="btn-tab-sync" onclick="switchTab(event, 'tab-sync')">🔄 Omni-Network Sync Hub</button>
    <button class="tab-btn" id="btn-tab-metrics" onclick="switchTab(event, 'tab-metrics')">📊 Performance Metrics</button>
    <button class="tab-btn" id="btn-tab-tools" onclick="switchTab(event, 'tab-tools')">🛠️ Quick Workspace Tools</button>
  </div>
</div>

<div class="wrap">
  <!-- Status Feedback -->
  <?php if (isset($_GET['updated'])):   ?><div class="alert ok" style="background:rgba(16,185,129,0.15); border:1px solid #10b981; color:#10b981; padding:0.8rem 1.2rem; border-radius:12px; margin-bottom:1rem; font-weight:700;">✅ Application status updated successfully.</div><?php endif; ?>
  <?php if (isset($_GET['cancelled'])): ?><div class="alert warn" style="background:rgba(239,68,68,0.15); border:1px solid #ef4444; color:#ef4444; padding:0.8rem 1.2rem; border-radius:12px; margin-bottom:1rem; font-weight:700;">⚠️ Application was successfully cancelled.</div><?php endif; ?>

  <!-- ========================================================================= -->
  <!-- TAB 1: APPLICATIONS REGISTRY -->
  <!-- ========================================================================= -->
  <div id="tab-registry" class="tab-content active">
    <!-- Animated Interactive Stats Filter Grid -->
    <div class="stats" id="filter-stats-bar" style="margin-bottom:1.5rem;">
      <a href="partner_shops.php" class="stat-card" style="border: 1px solid rgba(252, 185, 0, 0.3); color: var(--gold); background:rgba(16, 18, 28, 0.85); text-decoration:none; cursor:pointer; width:100%; text-align:center; padding:1.2rem; display:block;">
        <div class="stat-num" style="font-size:1.8rem; font-weight:900; margin-bottom:0.4rem;">🏪 <?= $total_shops_count ?></div>
        <div class="stat-label" style="font-size:0.75rem; font-weight:800; letter-spacing:0.05em; text-transform:uppercase;">TOTAL SHOPS</div>
      <div class='card-watermark'>🏪</div>
      </a>
      <a href="partner_shops.php#tab-requests" class="stat-card" style="border: 1px solid rgba(245, 158, 11, 0.35); color: #f59e0b; background:rgba(16, 18, 28, 0.85); text-decoration:none; cursor:pointer; width:100%; text-align:center; padding:1.2rem; display:block;">
        <div class="stat-num" style="font-size:1.8rem; font-weight:900; margin-bottom:0.4rem;">⏳ <?= $pending_shops_count ?></div>
        <div class="stat-label" style="font-size:0.75rem; font-weight:800; letter-spacing:0.05em; text-transform:uppercase;">SHOPS PENDING REQUEST</div>
      <div class='card-watermark'>⏳</div>
      </a>
      <a href="wallet.php" class="stat-card" style="border: 1px solid rgba(59, 130, 246, 0.35); color: #3b82f6; background:rgba(16, 18, 28, 0.85); text-decoration:none; cursor:pointer; width:100%; text-align:center; padding:1.2rem; display:block;">
        <div class="stat-num" style="font-size:1.8rem; font-weight:900; margin-bottom:0.4rem;">💳 <?= $tx_requests_count ?></div>
        <div class="stat-label" style="font-size:0.75rem; font-weight:800; letter-spacing:0.05em; text-transform:uppercase;">SHOP DEPOSIT/WITHDRAWAL REQUEST</div>
      <div class='card-watermark'>💳</div>
      </a>
      <a href="tasks.php" class="stat-card" style="border: 1px solid rgba(16, 185, 129, 0.35); color: #10b981; background:rgba(16, 18, 28, 0.85); text-decoration:none; cursor:pointer; width:100%; text-align:center; padding:1.2rem; display:block;">
        <div class="stat-num" style="font-size:1.8rem; font-weight:900; margin-bottom:0.4rem;">🎯 <?= $active_tasks_count ?></div>
        <div class="stat-label" style="font-size:0.75rem; font-weight:800; letter-spacing:0.05em; text-transform:uppercase;">ALL TASKS</div>
      <div class='card-watermark'>🎯</div>
      </a>
      <a href="partner_shops.php#tab-disputes" class="stat-card" style="border: 1px solid rgba(239, 68, 68, 0.35); color: #ef4444; background:rgba(16, 18, 28, 0.85); text-decoration:none; cursor:pointer; width:100%; text-align:center; padding:1.2rem; display:block;">
        <div class="stat-num" style="font-size:1.8rem; font-weight:900; margin-bottom:0.4rem;">⚖️ <?= $escrow_disputes_count ?></div>
        <div class="stat-label" style="font-size:0.75rem; font-weight:800; letter-spacing:0.05em; text-transform:uppercase;">ESCROW DISPUTES</div>
      <div class='card-watermark'>⚖️</div>
      </a>
    </div>

    <!-- Search & Filter Controls -->
    <div id="registry-table" style="scroll-margin-top:80px;">
      <form method="GET" action="dashboard.php" style="margin-bottom:1rem;">
        <div class="controls" style="display:flex; align-items:center; gap:0.6rem; flex-wrap:wrap;">
          <input type="text" name="search" placeholder="🔍 Search customer, phone, NID, PP..." value="<?= htmlspecialchars($search) ?>" style="flex:1; min-width:220px;"/>
          <select name="filter" id="filter-select" onchange="applyQuickFilter(this.value)">
            <option value="">All Statuses</option>
            <?php foreach(['pending','processing','approved','cancelled'] as $st): ?>
              <option value="<?=$st?>" <?= $filter===$st?'selected':''?>><?= ucfirst($st)?></option>
            <?php endforeach; ?>
          </select>
          <button type="submit" class="btn-sm">Search</button>
          
          <span id="active-filter-badge" style="display:<?= $filter ? 'inline-flex' : 'none' ?>; align-items:center; background:rgba(252,185,0,0.15); border:1px solid var(--gold); color:var(--gold); padding:0.35rem 0.8rem; border-radius:20px; font-size:0.78rem; font-weight:700;">
            Showing: <strong><?= strtoupper($filter) ?></strong>
            <button type="button" onclick="applyQuickFilter('')" style="background:none; border:none; color:var(--gold); font-weight:800; cursor:pointer; margin-left:6px; font-size:0.9rem;">✕</button>
          </span>

          <?php if($filter||$search): ?>
            <a href="dashboard.php" style="color:var(--muted); font-size:0.8rem; text-decoration:none; margin-left:0.3rem;">Reset All</a>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <!-- ================= DESKTOP VIEW (TABLE) ================= -->
    <div class="desktop-table-wrap">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Service Name</th>
            <th>Total Fee</th>
            <th>Customer Info</th>
            <th>Submitted Docs</th>
            <th>Current Status</th>
            <th>Admin Notes</th>
            <th>Created</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php if (empty($applications)): ?>
          <tr id="empty-row"><td colspan="9" style="text-align:center; padding:3rem; color:var(--muted);">No applications found.</td></tr>
        <?php else: ?>
        <?php foreach($applications as $app): ?>
        <tr data-status="<?= htmlspecialchars($app['status']) ?>">
          <td><span class="id-badge">FS-<?= str_pad($app['id'],6,'0',STR_PAD_LEFT) ?></span></td>
          <td style="font-weight:700; color:#ffffff;"><?= htmlspecialchars($app['service_name']) ?></td>
          <td><span class="fee-badge">৳<?= number_format((float)$app['fee'],2) ?></span></td>
          <td>
            <div style="font-weight:700; color:#fff;"><?= htmlspecialchars($app['user_name']) ?></div>
            <span style="font-size:0.75rem; color:var(--muted);">📞 <?= htmlspecialchars($app['user_phone']) ?></span>
            <?php if($app['user_email']): ?>
              <br/><span style="font-size:0.75rem; color:var(--muted);">✉️ <?= htmlspecialchars($app['user_email']) ?></span>
            <?php endif; ?>
          </td>
          <td>
            <div style="font-size:0.75rem; display:flex; flex-direction:column; gap:0.1rem; color: #cbd5e1;">
              <?php if($app['nid_number'])      echo '<span><strong>NID:</strong> '.htmlspecialchars($app['nid_number']).'</span>'; ?>
              <?php if($app['passport_number']) echo '<span><strong>PP:</strong> '.htmlspecialchars($app['passport_number']).'</span>'; ?>
              <?php if($app['driving_license']) echo '<span><strong>DL:</strong> '.htmlspecialchars($app['driving_license']).'</span>'; ?>
            </div>
          </td>
          <td><?= statusBadge($app['status']) ?></td>
          <td><span style="font-size:0.76rem; color:var(--muted); font-style:italic;"><?= htmlspecialchars($app['admin_notes'] ?? '—') ?></span></td>
          <td style="font-size:0.72rem; color:var(--muted); white-space:nowrap;"><?= date('d M Y, h:i A', strtotime($app['created_at'])) ?></td>
          <td>
            <form method="POST" action="dashboard.php" style="display:flex; flex-direction:column; gap:0.3rem;">
              <input type="hidden" name="update_id" value="<?= $app['id'] ?>"/>
              <select name="status" style="background:#0a0a0f; color:#fff; border:1px solid var(--border); padding:0.3rem; border-radius:6px; font-size:0.75rem;">
                <?php foreach(['pending','processing','approved','cancelled'] as $st): ?>
                  <option value="<?=$st?>" <?= $app['status']===$st?'selected':''?>><?= ucfirst($st)?></option>
                <?php endforeach; ?>
              </select>
              <input type="text" name="admin_notes" placeholder="Notes..." value="<?= htmlspecialchars($app['admin_notes'] ?? '') ?>" style="background:#0a0a0f; color:#fff; border:1px solid var(--border); padding:0.3rem; border-radius:6px; font-size:0.75rem;"/>
              <button type="submit" class="btn-sm" style="padding:0.3rem; font-size:0.75rem;">Save</button>
            </form>
            <div style="display:flex; gap:0.5rem; margin-top:0.4rem;">
              <a href="edit.php?id=<?= $app['id'] ?>" style="color:var(--brand); text-decoration:none; font-size:0.75rem; font-weight:700;">Edit</a>
              <?php if($app['status'] !== 'cancelled'): ?>
                <a href="dashboard.php?cancel=<?= $app['id'] ?>" style="color:var(--red); text-decoration:none; font-size:0.75rem; font-weight:700;" onclick="return confirm('Cancel this application?')">Cancel</a>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- ================= MOBILE VIEW (CARDS) ================= -->
    <div class="mobile-cards-wrap">
      <?php if (empty($applications)): ?>
        <div style="text-align:center; padding:3rem 0; color:var(--muted);">No applications found.</div>
      <?php else: ?>
        <?php foreach($applications as $app): ?>
          <div class="mobile-card">
            <div class="mobile-card-row">
              <span class="id-badge">FS-<?= str_pad($app['id'],6,'0',STR_PAD_LEFT) ?></span>
              <span class="fee-badge">৳<?= number_format((float)$app['fee'],2) ?></span>
            </div>
            
            <div class="mobile-card-row" style="border-bottom:none; padding-bottom:0;">
              <div class="mobile-card-details">
                <span style="font-size:0.7rem; color:var(--gold); text-transform:uppercase; font-weight:800; letter-spacing:0.04em;"><?= htmlspecialchars($app['service_name']) ?></span>
                <strong style="color:#ffffff; font-size:0.95rem; display:block; margin-top:2px;"><?= htmlspecialchars($app['user_name']) ?></strong>
                <span style="color:var(--muted); font-size:0.78rem;">📞 <?= htmlspecialchars($app['user_phone']) ?></span>
                <?php if($app['user_email']): ?>
                  <br/><span style="color:var(--muted); font-size:0.78rem;">✉️ <?= htmlspecialchars($app['user_email']) ?></span>
                <?php endif; ?>
              </div>
              <div>
                <?= statusBadge($app['status']) ?>
              </div>
            </div>
            
            <?php if($app['nid_number'] || $app['passport_number'] || $app['driving_license']): ?>
              <div style="background:rgba(255,255,255,0.03); border-radius:8px; padding:0.6rem; font-size:0.78rem; color:#cbd5e1;">
                <?php if($app['nid_number'])      echo '<div><strong>NID:</strong> '.htmlspecialchars($app['nid_number']).'</div>'; ?>
                <?php if($app['passport_number']) echo '<div><strong>PP:</strong> '.htmlspecialchars($app['passport_number']).'</div>'; ?>
                <?php if($app['driving_license']) echo '<div><strong>DL:</strong> '.htmlspecialchars($app['driving_license']).'</div>'; ?>
              </div>
            <?php endif; ?>

            <?php if(!empty($app['admin_notes'])): ?>
              <div style="background:rgba(255,255,255,0.02); border-radius:8px; padding:0.5rem 0.8rem; font-size:0.75rem; color:#94a3b8; font-style:italic;">
                 📝 <?= htmlspecialchars($app['admin_notes']) ?>
              </div>
            <?php endif; ?>
            
            <form method="POST" action="dashboard.php" style="display:flex; flex-direction:column; gap:0.6rem; margin-top:0.4rem;">
              <input type="hidden" name="update_id" value="<?= $app['id'] ?>"/>
              <div style="display:flex; flex-direction:column; gap:0.3rem;">
                <label style="font-size:0.75rem; color:var(--muted); font-weight:700;">Update Status</label>
                <select name="status" style="background:#080911; color:#fff; border:1px solid var(--border); padding:0.5rem; border-radius:8px; font-size:0.85rem;">
                  <?php foreach(['pending','processing','approved','cancelled'] as $st): ?>
                    <option value="<?=$st?>" <?= $app['status']===$st?'selected':''?>><?= ucfirst($st)?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div style="display:flex; flex-direction:column; gap:0.3rem;">
                <label style="font-size:0.75rem; color:var(--muted); font-weight:700;">Admin Notes</label>
                <input type="text" name="admin_notes" placeholder="Update notes..." value="<?= htmlspecialchars($app['admin_notes'] ?? '') ?>" style="background:#080911; color:#fff; border:1px solid var(--border); padding:0.5rem; border-radius:8px; font-size:0.85rem;"/>
              </div>
              <button type="submit" class="btn-sm" style="width:100%; text-align:center; padding:0.6rem;">💾 Save Order Changes</button>
            </form>
            
            <div style="display:flex; gap:0.5rem; justify-content:flex-end; margin-top:0.4rem;">
              <a href="edit.php?id=<?= $app['id'] ?>" style="background:rgba(255,255,255,0.05); color:#fff; border:1px solid rgba(255,255,255,0.1); text-decoration:none; display:inline-flex; align-items:center; padding:0.4rem 0.8rem; border-radius:8px; font-size:0.78rem; font-weight:700;">✏️ Edit Details</a>
              <?php if($app['status'] !== 'cancelled'): ?>
                <a href="dashboard.php?cancel=<?= $app['id'] ?>" style="background:rgba(239,68,68,0.15); color:var(--red); border:1px solid rgba(239,68,68,0.3); text-decoration:none; display:inline-flex; align-items:center; padding:0.4rem 0.8rem; border-radius:8px; font-size:0.78rem; font-weight:700;" onclick="return confirm('Cancel this application?')">❌ Cancel</a>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- 🏪 SHOPS & ESCROW QUICK HUB -->
    <div class="overview-card" style="margin-top: 2rem; border: 1px solid rgba(252, 185, 0, 0.3); background: rgba(16, 18, 28, 0.95); border-radius: 16px; padding: 1.5rem;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 1.2rem;">
            <div>
                <h3 style="color:var(--gold); font-size:1.1rem; font-family:'Oswald',sans-serif; margin:0; text-transform:uppercase;">🏪 SHOPS & ESCROW QUICK HUB</h3>
                <span style="font-size:0.78rem; color:var(--muted);">Manage registered storefronts, pending approvals, and partner payouts</span>
            </div>
            <a href="partner_shops.php" class="btn-sm" style="text-decoration:none; padding:0.4rem 0.9rem; font-size:0.78rem;">Full Shops Hub →</a>
        </div>

        <!-- Quick Shops Table -->
        <div class="desktop-table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Shop / Business</th>
                        <th>Owner</th>
                        <th>Orders & Rating</th>
                        <th>Status</th>
                        <th>Quick Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $dash_shops = $pdo->query("SELECT * FROM partners ORDER BY created_at DESC LIMIT 5")->fetchAll();
                    foreach($dash_shops as $ds):
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($ds['business_name']) ?></strong></td>
                        <td><?= htmlspecialchars($ds['owner_name']) ?> (<?= htmlspecialchars($ds['phone']) ?>)</td>
                        <td>⭐ <?= $ds['rating'] ?? '5.0' ?> (<?=$ds['total_orders'] ?? 0 ?> Orders)</td>
                        <td><span class="status-capsule"><?= ucfirst($ds['status']) ?></span></td>
                        <td>
                            <a href="shop_edit.php?id=<?= $ds['id'] ?>" class="btn-sm" style="text-decoration:none; padding:0.25rem 0.5rem; font-size:0.72rem;">👁️ Edit Shop</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
  </div>

  <!-- ========================================================================= -->
  <!-- TAB 2: SYSTEM METRICS -->
  <!-- ========================================================================= -->
  <div id="tab-metrics" class="tab-content">
    <!-- Analytics Chart -->
    <div style="background: var(--dark-card); border: 1px solid var(--border); border-radius: 16px; padding: 1.5rem; margin-bottom: 2rem; backdrop-filter: blur(12px);">
      <h3 style="color: var(--gold); font-size: 0.95rem; margin-bottom: 1rem; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 800;">📈 30-Day Platform Growth & Revenue</h3>
      <canvas id="trendChart" height="80"></canvas>
    </div>

    <div class="overview-grid">
      <div class="overview-card">
        <h3>📊 Order Processing Stats</h3>
        <?php
        $totalApps = array_sum($counts);
        $approvedApps = $counts['approved'] ?? 0;
        $cancelledApps = $counts['cancelled'] ?? 0;
        $processingRate = $totalApps > 0 ? round(($approvedApps / $totalApps) * 100, 1) : 0;
        $cancellationRate = $totalApps > 0 ? round(($cancelledApps / $totalApps) * 100, 1) : 0;
        ?>
        <div style="display:flex; flex-direction:column; gap:0.9rem; font-size:0.88rem;">
          <div style="display:flex; justify-content:space-between; border-bottom:1px solid var(--border); padding-bottom:0.5rem;"><span>Approved Conversion Rate:</span><strong style="color:var(--green);"><?=$processingRate?>%</strong></div>
          <div style="display:flex; justify-content:space-between; border-bottom:1px solid var(--border); padding-bottom:0.5rem;"><span>Cancellation Rate:</span><strong style="color:var(--red);"><?=$cancellationRate?>%</strong></div>
          <div style="display:flex; justify-content:space-between;"><span>Active Working Queue:</span><strong style="color:var(--gold);"><?=($counts['pending']??0) + ($counts['processing']??0)?> Applications</strong></div>
        </div>
      </div>
      
      <div class="overview-card">
        <h3>🤝 Affiliate Agent System</h3>
        <?php
        $totalAgents = (int)$pdo->query("SELECT COUNT(*) FROM agents")->fetchColumn();
        $activeAgents = (int)$pdo->query("SELECT COUNT(*) FROM agents WHERE status='active'")->fetchColumn();
        $totalPayoutRequested = (float)$pdo->query("SELECT SUM(amount) FROM agent_payouts WHERE status='pending'")->fetchColumn();
        ?>
        <div style="display:flex; flex-direction:column; gap:0.9rem; font-size:0.88rem;">
          <div style="display:flex; justify-content:space-between; border-bottom:1px solid var(--border); padding-bottom:0.5rem;"><span>Total Registered Agents:</span><strong><?=$totalAgents?></strong></div>
          <div style="display:flex; justify-content:space-between; border-bottom:1px solid var(--border); padding-bottom:0.5rem;"><span>Active Validated Agents:</span><strong style="color:var(--green);"><?=$activeAgents?></strong></div>
          <a href="payouts.php" style="display:flex; justify-content:space-between; text-decoration:none; color:inherit; cursor:pointer;"><span style="text-decoration:underline;">Pending Payout Requests:</span><strong style="color:var(--gold);">৳<?=number_format($totalPayoutRequested, 2)?></strong></a>
        </div>
      </div>
    </div>
  </div>

  <!-- ========================================================================= -->
  <!-- TAB 3: QUICK TOOLS WORKSPACE -->
  <!-- ========================================================================= -->
  <div id="tab-tools" class="tab-content">
    <div class="overview-grid">
      <div class="overview-card">
        <h3>🌐 Content & Services</h3>
        <a href="manage_directory.php" class="premium-block-link"><span>🏛️ Manage Government Services</span><span>→</span></a>
        <a href="partner_shops.php" class="premium-block-link"><span>🏪 Manage External Partners</span><span>→</span></a>
        <a href="manage_directory.php" class="premium-block-link"><span>🛡️ Trust Directory & Blacklist</span><span>→</span></a>
      </div>
      
      <div class="overview-card">
        <h3>👥 Users & Affiliates</h3>
        <a href="agents.php" class="premium-block-link"><span>👨‍💼 View & Suspend Agents</span><span>→</span></a>
        <a href="payouts.php" class="premium-block-link"><span>💰 Process Agent Withdrawals</span><span>→</span></a>
        <a href="chats.php" class="premium-block-link"><span>💬 Client Support Chats (<?=$chatCount?>)</span><span>→</span></a>
        <a href="settings.php" class="premium-block-link"><span>⚙️ Global System Settings</span><span>→</span></a>
      </div>

      <div class="overview-card" style="border: 1px solid rgba(252, 185, 0, 0.4); box-shadow: 0 0 20px rgba(252, 185, 0, 0.15);">
        <h3 style="color: var(--gold);">🔄 Omni-Network Sync Hub</h3>
        <p style="font-size:0.8rem; color:var(--muted); margin-bottom: 1.2rem;">Synchronize services and products across the 7 ecosystem shops.</p>
        <a href="sync_hub.php" class="btn-sm" style="text-align: center; display: block; text-decoration: none; padding:0.8rem;">Launch Sync Hub</a>
      </div>
    </div>
  </div>

  <!-- ========================================================================= -->
  <!-- TAB: OMNI-NETWORK SYNC HUB -->
  <!-- ========================================================================= -->
  <div id="tab-sync" class="tab-content">
    <div class="overview-card" style="border: 1px solid rgba(252, 185, 0, 0.4); box-shadow: 0 0 25px rgba(252, 185, 0, 0.15);">
      <h3 style="color: var(--gold); font-size: 1.1rem; margin-bottom: 0.5rem;">🔄 Omni-Network Sync Hub</h3>
      <p style="font-size: 0.85rem; color: var(--muted); margin-bottom: 1.5rem;">Cross-post services, sync products, and push catalog updates across all 7 ecosystem partner shops with 1 click.</p>
      
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
        <div style="background: rgba(8,9,17,0.8); border: 1px solid var(--border); padding: 1.2rem; border-radius: 12px;">
          <strong style="color: #fff; font-size: 0.95rem; display: block; margin-bottom: 0.4rem;">🛍️ Sync Ayra Mart Products</strong>
          <span style="font-size: 0.78rem; color: var(--muted);">Fetch latest fashion and retail listings into Fast Site marketplace grid.</span>
          <a href="sync_hub.php?action=sync_ayramart" class="btn-sm" style="display: block; text-align: center; text-decoration: none; margin-top: 0.8rem; padding: 0.5rem;">Sync Ayra Mart Now</a>
        </div>
        <div style="background: rgba(8,9,17,0.8); border: 1px solid var(--border); padding: 1.2rem; border-radius: 12px;">
          <strong style="color: #fff; font-size: 0.95rem; display: block; margin-bottom: 0.4rem;">🏍️ Sync Enzor Motor Parts</strong>
          <span style="font-size: 0.78rem; color: var(--muted);">Fetch latest auto parts and bike accessories into Fast Site.</span>
          <a href="sync_hub.php?action=sync_enzor" class="btn-sm" style="display: block; text-align: center; text-decoration: none; margin-top: 0.8rem; padding: 0.5rem;">Sync Enzor Motor Now</a>
        </div>
        <div style="background: rgba(8,9,17,0.8); border: 1px solid var(--border); padding: 1.2rem; border-radius: 12px;">
          <strong style="color: #fff; font-size: 0.95rem; display: block; margin-bottom: 0.4rem;">🏛️ Seed All 7 Ecosystem Shops</strong>
          <span style="font-size: 0.78rem; color: var(--muted);">Verify and auto-create all partner shop accounts & referral codes.</span>
          <a href="seed_ecosystem_shops.php" class="btn-sm" style="display: block; text-align: center; text-decoration: none; margin-top: 0.8rem; padding: 0.5rem;">Seed Ecosystem Shops</a>
        </div>
        <div style="background: rgba(16,18,28,0.95); border: 1px solid var(--gold); padding: 1.2rem; border-radius: 12px; display:flex; align-items:center; gap:12px;">
          <img src="/assets/images/fast_site_hq_admin_icon.jpg" style="width:55px; height:55px; border-radius:12px; object-fit:cover; border:2px solid var(--gold);" alt="FAST SITE HQ"/>
          <div style="flex:1;">
            <strong style="color: var(--gold); font-size: 0.95rem; display: block; font-family:'Oswald',sans-serif;">🛡️ FAST SITE HQ (ADMIN APK APP)</strong>
            <span style="font-size: 0.78rem; color: var(--muted);">Dedicated Admin Mobile Control App.</span>
            <a href="../<?= htmlspecialchars($settings['admin_apk_download_url'] ?? 'fastsite_hq.apk') ?>" download class="btn-sm" style="display: inline-block; text-align: center; text-decoration: none; margin-top: 0.5rem; padding: 0.4rem 1rem; background:linear-gradient(135deg, #fcb900 0%, #f59e0b 100%); color:#000; font-weight:800;">⬇️ Download FAST SITE HQ APK</a>
          </div>
        </div>
      </div>
    </div>
  </div>

<!-- ========================================================================= -->
<!-- ULTRA-PREMIUM QUICK ADD PRODUCT / SERVICE MODAL -->
<!-- ========================================================================= -->
<div id="quick-add-product-modal" style="display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.88); backdrop-filter: blur(16px); z-index: 99999; align-items: center; justify-content: center; padding: 1rem;">
  <div style="background: radial-gradient(circle at 50% 0%, #1c2237 0%, #0d101d 100%); border: 1px solid rgba(252, 185, 0, 0.35); border-radius: 24px; padding: 2.2rem; max-width: 580px; width: 100%; box-shadow: 0 25px 60px rgba(0,0,0,0.8), 0 0 35px rgba(252,185,0,0.15); position: relative; max-height: 90vh; overflow-y: auto;">
    
    <button type="button" onclick="closeQuickUploadModal()" style="position: absolute; top: 1.2rem; right: 1.2rem; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); color: #fff; border-radius: 50%; width: 34px; height: 34px; cursor: pointer; font-size: 1rem; display: flex; align-items: center; justify-content: center; transition: 0.2s;">✕</button>
    
    <div style="display:flex; align-items:center; gap:8px; margin-bottom:0.3rem;">
      <span style="font-size:1.4rem;">✨</span>
      <h3 style="color: #fff; font-size: 1.4rem; margin: 0; font-family: 'Oswald', sans-serif; text-transform:uppercase; letter-spacing:0.04em;">QUICK PRODUCT &amp; SERVICE UPLOAD</h3>
    </div>
    <p style="font-size: 0.8rem; color: var(--muted); margin: 0 0 1.4rem 0;">Publish digital assets, custom services, or free promo deals to the Official Fast Site Catalog in seconds.</p>

    <!-- Success Message Container -->
    <div id="quick-upload-success" style="display:none; background:rgba(0,230,118,0.12); border:1px solid rgba(0,230,118,0.3); border-radius:14px; padding:1.2rem; text-align:center; margin-bottom:1.2rem;">
      <div style="font-size:2rem; margin-bottom:0.4rem;">🎉</div>
      <h4 style="color:#00e676; font-size:1.1rem; margin:0 0 0.4rem 0; font-family:'Oswald',sans-serif;">LISTING PUBLISHED SUCCESSFULLY!</h4>
      <p style="color:#cbd5e1; font-size:0.82rem; margin:0 0 1rem 0;" id="quick-success-msg">Your item is now live in the Official Catalog.</p>
      <div style="display:flex; justify-content:center; gap:0.6rem; flex-wrap:wrap;">
        <a href="#" id="quick-view-product-link" target="_blank" style="background:linear-gradient(135deg, var(--gold), #f59e0b); color:#000; font-weight:800; font-size:0.8rem; padding:0.5rem 1rem; border-radius:8px; text-decoration:none;">👁️ View Listing ↗</a>
        <button type="button" onclick="resetQuickUploadForm()" style="background:rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.15); color:#fff; font-weight:700; font-size:0.8rem; padding:0.5rem 1rem; border-radius:8px; cursor:pointer;">➕ Upload Another</button>
      </div>
    </div>

    <form id="admin-quick-product-form" onsubmit="handleAdminQuickUpload(event)">
      
      <!-- Type Switcher -->
      <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:0.5rem; background:rgba(0,0,0,0.3); padding:4px; border-radius:10px; margin-bottom:1.2rem;">
        <button type="button" class="quick-type-btn active" id="qtype-prod" onclick="setQuickType('product')" style="background:rgba(252,185,0,0.15); color:var(--gold); border:1px solid rgba(252,185,0,0.35); padding:0.5rem; border-radius:8px; font-weight:700; font-size:0.75rem; cursor:pointer;">📦 Digital Item</button>
        <button type="button" class="quick-type-btn" id="qtype-serv" onclick="setQuickType('service')" style="background:transparent; color:#94a3b8; border:1px solid transparent; padding:0.5rem; border-radius:8px; font-weight:700; font-size:0.75rem; cursor:pointer;">🤝 Service</button>
        <button type="button" class="quick-type-btn" id="qtype-aff" onclick="setQuickType('affiliate')" style="background:transparent; color:#94a3b8; border:1px solid transparent; padding:0.5rem; border-radius:8px; font-weight:700; font-size:0.75rem; cursor:pointer;">🔗 Promo Deal</button>
      </div>
      <input type="hidden" name="listing_type" id="quick-listing-type" value="product"/>

      <div style="display: flex; flex-direction: column; gap: 1rem;">
        
        <div>
          <label style="font-size: 0.75rem; color: #94a3b8; font-weight: 700; text-transform:uppercase; display: block; margin-bottom: 0.35rem;">Listing Title *</label>
          <input type="text" name="title" id="quick-input-title" required placeholder="e.g. NID Correction Fast Track / Netflix 1-Mo / Source Code" style="width: 100%; background: #080911; border: 1px solid rgba(255,255,255,0.1); color: #fff; padding: 0.75rem 0.9rem; border-radius: 10px; font-size: 0.88rem; box-sizing:border-box;"/>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.8rem;">
          <div>
            <label style="font-size: 0.75rem; color: #94a3b8; font-weight: 700; text-transform:uppercase; display: block; margin-bottom: 0.35rem;">
              Price (৳ / FP) <span style="color:#00e676; text-transform:none; font-weight:normal;">(0 = FREE)</span>
            </label>
            <input type="number" step="0.1" min="0" name="price" placeholder="0.00 (Free / Promo)" style="width: 100%; background: #080911; border: 1px solid rgba(255,255,255,0.1); color: #fff; padding: 0.75rem 0.9rem; border-radius: 10px; font-size: 0.88rem; box-sizing:border-box;"/>
          </div>
          <div>
            <label style="font-size: 0.75rem; color: #94a3b8; font-weight: 700; text-transform:uppercase; display: block; margin-bottom: 0.35rem;">Category</label>
            <select name="category" style="width: 100%; background: #080911; border: 1px solid rgba(255,255,255,0.1); color: #fff; padding: 0.75rem 0.9rem; border-radius: 10px; font-size: 0.88rem; box-sizing:border-box;">
              <option value="General">General</option>
              <option value="Subscriptions">Subscriptions</option>
              <option value="Gaming & Top-up">Gaming &amp; Top-up</option>
              <option value="Software & Keys">Software &amp; Keys</option>
              <option value="Document Correction">Document Correction</option>
              <option value="NID Services">NID Services</option>
              <option value="Passport & Visa">Passport &amp; Visa</option>
            </select>
          </div>
        </div>

        <div>
          <label style="font-size: 0.75rem; color: #94a3b8; font-weight: 700; text-transform:uppercase; display: block; margin-bottom: 0.35rem;">Description &amp; Instructions</label>
          <textarea name="description" rows="2" placeholder="Explain what the buyer receives upon order..." style="width: 100%; background: #080911; border: 1px solid rgba(255,255,255,0.1); color: #fff; padding: 0.65rem 0.9rem; border-radius: 10px; font-size: 0.85rem; box-sizing:border-box;"></textarea>
        </div>

        <div id="quick-aff-row" style="display:none;">
          <label style="font-size: 0.75rem; color: #94a3b8; font-weight: 700; text-transform:uppercase; display: block; margin-bottom: 0.35rem;">Affiliate / Destination Link</label>
          <input type="url" name="affiliate_url" placeholder="https://..." style="width: 100%; background: #080911; border: 1px solid rgba(255,255,255,0.1); color: #fff; padding: 0.75rem 0.9rem; border-radius: 10px; font-size: 0.88rem; box-sizing:border-box;"/>
        </div>

        <!-- Cover Photo Upload with 1:1 Live Preview -->
        <div>
          <label style="font-size: 0.75rem; color: #94a3b8; font-weight: 700; text-transform:uppercase; display: block; margin-bottom: 0.35rem;">Cover Photo (Optional)</label>
          <input type="file" name="cover_image" id="quick-photo-input" accept="image/*" onchange="previewQuickPhoto(event)" style="width: 100%; background: #080911; border: 1px solid rgba(255,255,255,0.1); color: #fff; padding: 0.55rem; border-radius: 10px; font-size: 0.8rem; box-sizing:border-box;"/>
        </div>

        <div id="quick-crop-preview-box" style="display: none; background: rgba(8,9,17,0.9); border: 1px solid rgba(252,185,0,0.3); border-radius: 12px; padding: 0.8rem; text-align: center;">
          <div style="width: 90px; height: 90px; margin: 0 auto; border-radius: 10px; overflow: hidden; border: 2px solid var(--gold);">
            <img id="quick-crop-img" src="" alt="Preview" style="width: 100%; height: 100%; object-fit: cover;"/>
          </div>
          <div style="font-size: 0.7rem; color: var(--gold); margin-top: 0.3rem;">1:1 Square Auto-Preview</div>
        </div>

        <!-- 1-to-100% Upload Progress Bar Container -->
        <div id="quick-upload-progress-box" style="display: none; margin-top: 0.3rem;">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.3rem;">
            <span id="quick-upload-status" style="font-size: 0.8rem; font-weight: 800; color: var(--gold);">⚡ Initializing payload...</span>
            <span id="quick-upload-pct" style="font-size: 0.85rem; font-weight: 800; color: #00e676;">1%</span>
          </div>
          <div style="height: 10px; background: rgba(255,255,255,0.08); border-radius: 50px; overflow: hidden; border: 1px solid rgba(255,255,255,0.1);">
            <div id="quick-upload-progress-bar" style="width: 1%; height: 100%; background: linear-gradient(90deg, #fcb900, #00e676); transition: width 0.15s linear;"></div>
          </div>
        </div>

        <button type="submit" id="btn-quick-submit" class="btn-sm" style="padding: 0.9rem; text-align: center; font-size: 0.95rem; font-weight: 900; background: linear-gradient(135deg, var(--gold) 0%, #f59e0b 100%); color:#000; border:none; border-radius:50px; cursor:pointer; box-shadow:0 8px 25px rgba(252,185,0,0.35); text-transform:uppercase; letter-spacing:0.04em;">
          🚀 Publish Listing Instantly
        </button>

      </div>
    </form>
  </div>
</div>

<div style="text-align:center; padding: 2rem 1rem; border-top: 1px solid var(--border); margin-top: 3rem; color: var(--muted); font-size: 0.85rem;">
    <a href="../policy.php" style="color: var(--gold); text-decoration: none; margin: 0 10px;">Terms & Policies</a> | 
    <a href="guide.php" style="color: var(--gold); text-decoration: none; margin: 0 10px;">Guide & Help</a>
    <div style="margin-top: 10px;">&copy; <?= date('Y') ?> <?= htmlspecialchars($site_name ?? 'Fast Site') ?>. All rights reserved.</div>
</div>
</div>
</div>

<script>
function switchTab(evt, tabId) {
  const contents = document.querySelectorAll('.tab-content');
  contents.forEach(c => c.classList.remove('active'));
  
  const buttons = document.querySelectorAll('.tab-btn');
  buttons.forEach(b => b.classList.remove('active'));
  
  const target = document.getElementById(tabId);
  if (target) {
    target.classList.add('active');
  }
  if (evt && evt.currentTarget) {
    evt.currentTarget.classList.add('active');
  } else if (tabId) {
    const btn = document.getElementById('btn-' + tabId);
    if (btn) btn.classList.add('active');
  }
}

function handleHashTab() {
  const hash = window.location.hash.replace('#', '');
  if (hash) {
    switchTab(null, hash);
  }
}
window.addEventListener('DOMContentLoaded', handleHashTab);
window.addEventListener('hashchange', handleHashTab);

// Quick Upload Modal Controls
function openQuickUploadModal() {
  const modal = document.getElementById('quick-add-product-modal');
  modal.style.display = 'flex';
}

function closeQuickUploadModal() {
  const modal = document.getElementById('quick-add-product-modal');
  modal.style.display = 'none';
}

function setQuickType(type) {
  document.getElementById('quick-listing-type').value = type;
  document.querySelectorAll('.quick-type-btn').forEach(b => {
    b.style.background = 'transparent';
    b.style.color = '#94a3b8';
    b.style.borderColor = 'transparent';
  });
  const activeBtn = document.getElementById('qtype-' + (type === 'product' ? 'prod' : (type === 'service' ? 'serv' : 'aff')));
  if (activeBtn) {
    activeBtn.style.background = 'rgba(252,185,0,0.15)';
    activeBtn.style.color = 'var(--gold)';
    activeBtn.style.borderColor = 'rgba(252,185,0,0.35)';
  }
  document.getElementById('quick-aff-row').style.display = (type === 'affiliate') ? 'block' : 'none';
}

function previewQuickPhoto(event) {
  const file = event.target.files[0];
  if (file) {
    const reader = new FileReader();
    reader.onload = function(e) {
      document.getElementById('quick-crop-img').src = e.target.result;
      document.getElementById('quick-crop-preview-box').style.display = 'block';
    };
    reader.readAsDataURL(file);
  }
}

function resetQuickUploadForm() {
  document.getElementById('admin-quick-product-form').reset();
  document.getElementById('admin-quick-product-form').style.display = 'block';
  document.getElementById('quick-upload-success').style.display = 'none';
  document.getElementById('quick-crop-preview-box').style.display = 'none';
  document.getElementById('quick-upload-progress-box').style.display = 'none';
}

// 1-to-100% Progress AJAX Upload Handler
async function handleAdminQuickUpload(event) {
  event.preventDefault();

  const form = document.getElementById('admin-quick-product-form');
  const progressBox = document.getElementById('quick-upload-progress-box');
  const progressBar = document.getElementById('quick-upload-progress-bar');
  const pctText = document.getElementById('quick-upload-pct');
  const statusText = document.getElementById('quick-upload-status');
  const submitBtn = document.getElementById('btn-quick-submit');

  progressBox.style.display = 'block';
  submitBtn.disabled = true;
  submitBtn.style.opacity = '0.5';

  let currentPct = 1;
  const timer = setInterval(() => {
    if (currentPct < 85) {
      currentPct += 3;
      progressBar.style.width = currentPct + '%';
      pctText.textContent = currentPct + '%';
      if (currentPct > 30 && currentPct < 65) statusText.textContent = '🖼️ Encoding cover photo...';
      if (currentPct >= 65) statusText.textContent = '🔒 Publishing to Official Store Catalog...';
    }
  }, 40);

  const formData = new FormData(form);

  try {
    const res = await fetch('ajax_quick_product_add.php', {
      method: 'POST',
      body: formData
    });
    const data = await res.json();
    clearInterval(timer);

    if (data.success) {
      progressBar.style.width = '100%';
      pctText.textContent = '100%';
      statusText.textContent = '✅ Listing Published 100%!';

      setTimeout(() => {
        form.style.display = 'none';
        document.getElementById('quick-upload-success').style.display = 'block';
        document.getElementById('quick-success-msg').textContent = data.message || 'Product published successfully!';
        document.getElementById('quick-view-product-link').href = data.product_url || '#';
        submitBtn.disabled = false;
        submitBtn.style.opacity = '1';
      }, 500);
    } else {
      statusText.textContent = '⚠️ ' + (data.error || 'Upload failed');
      submitBtn.disabled = false;
      submitBtn.style.opacity = '1';
    }
  } catch (err) {
    clearInterval(timer);
    statusText.textContent = '⚠️ Network error. Please try again.';
    submitBtn.disabled = false;
    submitBtn.style.opacity = '1';
  }
}

// ── Interactive Quick Filter Handler for KPI Cards ──
function applyQuickFilter(status) {
  // 1. Update active card highlight
  document.querySelectorAll('#filter-stats-bar .stat-card').forEach(b => {
    const bFilter = b.getAttribute('data-filter') || '';
    if (bFilter === status) {
      b.classList.add('active-filter');
    } else {
      b.classList.remove('active-filter');
    }
  });

  // 2. Update select dropdown value
  const sel = document.getElementById('filter-select');
  if (sel) sel.value = status;

  // 3. Filter desktop table rows
  const rows = document.querySelectorAll('.desktop-table-wrap tbody tr');
  let matchCount = 0;
  rows.forEach(r => {
    const st = r.getAttribute('data-status');
    if (!st) return;
    if (!status || st === status) {
      r.style.display = '';
      matchCount++;
    } else {
      r.style.display = 'none';
    }
  });

  // 4. Filter mobile cards
  const mobileCards = document.querySelectorAll('.mobile-cards-wrap .mobile-card');
  mobileCards.forEach(c => {
    const st = c.getAttribute('data-status');
    if (!st) return;
    if (!status || st === status) {
      c.style.display = '';
    } else {
      c.style.display = 'none';
    }
  });

  // 5. Update active filter badge notice
  const filterNotice = document.getElementById('active-filter-badge');
  if (filterNotice) {
    if (status) {
      filterNotice.style.display = 'inline-flex';
      filterNotice.innerHTML = `Showing: <strong>${status.toUpperCase()}</strong> (${matchCount}) <button type="button" onclick="applyQuickFilter('')" style="background:none; border:none; color:var(--gold); font-weight:800; cursor:pointer; margin-left:6px; font-size:0.9rem;">✕</button>`;
    } else {
      filterNotice.style.display = 'none';
    }
  }

  // 6. Update browser URL without reloading
  const url = new URL(window.location);
  if (status) url.searchParams.set('filter', status);
  else url.searchParams.delete('filter');
  window.history.replaceState({}, '', url);

  // 7. Smooth scroll down to table
  const regTable = document.getElementById('registry-table');
  if (regTable) {
    regTable.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }
}
</script>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
window.addEventListener('DOMContentLoaded', () => {
  const chartCanvas = document.getElementById('trendChart');
  if (chartCanvas) {
    const ctx = chartCanvas.getContext('2d');
    new Chart(ctx, {
      type: 'line',
      data: {
        labels: <?= $chart_labels_json ?>,
        datasets: [
          {
            label: 'Revenue (Coins)',
            data: <?= $chart_revenue_json ?>,
            borderColor: '#10b981',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            borderWidth: 3,
            tension: 0.4,
            fill: true,
            yAxisID: 'y'
          },
          {
            label: 'Active Users',
            data: <?= $chart_users_json ?>,
            borderColor: '#6366f1',
            backgroundColor: 'rgba(99, 102, 241, 0.1)',
            borderWidth: 3,
            tension: 0.4,
            fill: true,
            yAxisID: 'y1'
          }
        ]
      },
      options: {
        responsive: true,
        plugins: { 
          legend: { labels: { color: '#94a3b8' } } 
        },
        scales: {
          y: { 
            beginAtZero: true, 
            type: 'linear', 
            display: true, 
            position: 'left',
            grid: { color: 'rgba(255, 255, 255, 0.05)' }, 
            ticks: { color: '#10b981' } 
          },
          y1: { 
            beginAtZero: true, 
            type: 'linear', 
            display: true, 
            position: 'right',
            grid: { drawOnChartArea: false }, 
            ticks: { color: '#6366f1' } 
          },
          x: { 
            grid: { color: 'rgba(255, 255, 255, 0.05)' }, 
            ticks: { color: '#94a3b8', maxRotation: 0, autoSkip: false } 
          }
        }
      }
    });
  }
});
</script>

<!-- FAST SITE HQ Admin Mobile APK Bottom Navigation -->
<div class="bottom-nav mobile-only-nav" style="position: fixed; bottom: 15px; left: 50%; transform: translateX(-50%); width: 92%; max-width: 440px; height: 62px; background: rgba(16, 18, 28, 0.92); backdrop-filter: blur(25px); border: 1px solid rgba(252, 185, 0, 0.3); border-radius: 35px; box-shadow: 0 10px 30px rgba(0,0,0,0.6); z-index: 9990; display: flex; align-items: center; justify-content: space-around; padding: 0 0.5rem;">
  <a href="dashboard.php" style="display:flex; flex-direction:column; align-items:center; color:var(--gold); text-decoration:none; font-size:0.7rem; font-weight:700;">
    <span style="font-size:1.3rem;">📊</span> Dash
  </a>
  <a href="partner_shops.php" style="display:flex; flex-direction:column; align-items:center; color:var(--muted); text-decoration:none; font-size:0.7rem; font-weight:700;">
    <span style="font-size:1.3rem;">🏪</span> Shops
  </a>
  <a href="escrow.php" style="display:flex; flex-direction:column; align-items:center; color:var(--muted); text-decoration:none; font-size:0.7rem; font-weight:700;">
    <span style="font-size:1.3rem;">🛡️</span> Escrow
  </a>
  <a href="accounts.php" style="display:flex; flex-direction:column; align-items:center; color:var(--muted); text-decoration:none; font-size:0.7rem; font-weight:700;">
    <span style="font-size:1.3rem;">💰</span> Payouts
  </a>
  <a href="settings.php" style="display:flex; flex-direction:column; align-items:center; color:var(--muted); text-decoration:none; font-size:0.7rem; font-weight:700;">
    <span style="font-size:1.3rem;">⚙️</span> Settings
  </a>
</div>

<style>
@media (min-width: 1025px) {
  .mobile-only-nav { display: none !important; }
}
</style>

</body>
</html>
