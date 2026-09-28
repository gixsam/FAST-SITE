<?php
session_start();
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// 1. Live Platform Financial Metrics
$circulating_coins = (float)$pdo->query("SELECT COALESCE(SUM(coins_balance), 0) FROM users")->fetchColumn();
$held_escrow = (float)$pdo->query("SELECT COALESCE(SUM(total_coins), 0) FROM partner_orders WHERE status IN ('pending', 'accepted', 'in_progress', 'waiting_confirmation')")->fetchColumn();
$withdrawn_coins = (float)$pdo->query("SELECT COALESCE(SUM(amount_coins), 0) FROM user_withdrawals WHERE status = 'paid'")->fetchColumn();
$platform_revenue = (float)$pdo->query("SELECT COALESCE(SUM(total_coins * 0.05), 0) FROM partner_orders WHERE status = 'completed'")->fetchColumn();

// 2. Active Escrow Holds Grouped by Partner Shop
$stmt = $pdo->query("
    SELECT p.business_name, p.id as shop_id, 
           SUM(o.total_coins) as locked_amount, 
           COUNT(o.id) as pending_orders, 
           MIN(o.created_at) as oldest_order 
    FROM partner_orders o 
    JOIN partners p ON o.partner_id = p.id 
    WHERE o.status IN ('pending', 'accepted', 'in_progress', 'waiting_confirmation') 
    GROUP BY p.id
    ORDER BY locked_amount DESC
");
$shop_holds = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Escrow Heatmap - Admin</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Oswald:wght@500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/admin.css">
  <style>
    .heatmap-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
    .heat-card { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; padding: 1.5rem; text-align: center; }
    .heat-card h4 { margin: 0 0 0.5rem 0; color: #94a3b8; font-size: 0.9rem; text-transform: uppercase; }
    .heat-card .val { font-size: 2rem; font-family: 'Oswald', sans-serif; font-weight: 700; color: #fff; }
    .val-gold { color: #fcb900 !important; }
    .val-green { color: #00e676 !important; }
    .val-blue { color: #00b0ff !important; }
    .val-red { color: #ff5252 !important; }
  </style>
</head>
<body>
<div class="admin-layout">
  <?php include 'nav.php'; ?>
  <div class="admin-content">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 2rem;">
      <h1 style="margin:0; font-family:'Oswald',sans-serif; color:var(--gold);">🔥 ESCROW FINANCIAL HEATMAP</h1>
      <span style="background:rgba(252,185,0,0.1); color:var(--gold); padding:0.5rem 1rem; border-radius:8px; font-weight:700;">Live Economy Status</span>
    </div>

    <div class="heatmap-grid">
      <div class="heat-card">
        <h4>Total Circulation</h4>
        <div class="val val-blue">🪙 <?= number_format($circulating_coins, 2) ?></div>
      </div>
      <div class="heat-card" style="border-color: rgba(255,82,82,0.4); background: rgba(255,82,82,0.05);">
        <h4>Locked in Escrow Vault</h4>
        <div class="val val-red">🔒 <?= number_format($held_escrow, 2) ?></div>
      </div>
      <div class="heat-card">
        <h4>Total Settled Payouts</h4>
        <div class="val val-green">💳 <?= number_format($withdrawn_coins, 2) ?></div>
      </div>
      <div class="heat-card" style="border-color: rgba(252,185,0,0.4); background: rgba(252,185,0,0.05);">
        <h4>Net Platform Revenue (5%)</h4>
        <div class="val val-gold">💰 <?= number_format($platform_revenue, 2) ?></div>
      </div>
    </div>

    <div class="admin-card">
      <h3 style="margin-top:0;">Shop Escrow Exposure (Active Holds)</h3>
      <?php if (empty($shop_holds)): ?>
        <p style="color:#64748b;">No active escrow holds at the moment.</p>
      <?php else: ?>
        <table class="admin-table">
          <thead>
            <tr>
              <th>Shop Name</th>
              <th>Locked Amount (Coins)</th>
              <th>Pending Orders</th>
              <th>Oldest Unreleased Order</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($shop_holds as $hold): 
              $is_stale = (time() - strtotime($hold['oldest_order'])) > (86400 * 3);
            ?>
              <tr <?= $is_stale ? 'style="background:rgba(255,82,82,0.1);"' : '' ?>>
                <td style="font-weight:700; color:#fff;">🏪 <?= htmlspecialchars($hold['business_name']) ?></td>
                <td style="color:#ff5252; font-weight:800;">🪙 <?= number_format($hold['locked_amount'], 2) ?></td>
                <td style="color:#fcb900; font-weight:700;"><?= $hold['pending_orders'] ?> Orders</td>
                <td>
                  <?= date('M d, Y h:i A', strtotime($hold['oldest_order'])) ?>
                  <?php if($is_stale): ?> <span style="background:#ff5252; color:#fff; padding:2px 6px; border-radius:4px; font-size:0.7rem; margin-left:8px;">⚠️ >72 Hours</span> <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>
</div>
</body>
</html>
