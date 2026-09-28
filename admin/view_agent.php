<?php
// admin/view_agent.php  –  Admin previews any agent's dashboard (read-only, no agent login needed)
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php'); exit;
}
require_once __DIR__ . '/../config.php';

$agentId = (int)($_GET['id'] ?? 0);
if (!$agentId) { header('Location: agents.php'); exit; }

$a = $pdo->prepare("SELECT * FROM agents WHERE id=:id LIMIT 1");
$a->execute([':id'=>$agentId]);
$agent = $a->fetch();
if (!$agent) { header('Location: agents.php'); exit; }

$available   = round($agent['total_earned'] - $agent['total_withdrawn'], 2);
$stats       = $pdo->prepare("SELECT COUNT(*) AS total, SUM(commission_amount) AS earned FROM agent_commissions WHERE agent_id=:id");
$stats->execute([':id'=>$agentId]);
$st          = $stats->fetch();
$commissions = $pdo->prepare("SELECT * FROM agent_commissions WHERE agent_id=:id ORDER BY created_at DESC LIMIT 15");
$commissions->execute([':id'=>$agentId]);
$comms       = $commissions->fetchAll();
$payouts     = $pdo->prepare("SELECT * FROM agent_payouts WHERE agent_id=:id ORDER BY requested_at DESC LIMIT 5");
$payouts->execute([':id'=>$agentId]);
$pays        = $payouts->fetchAll();

$settings = $pdo->query("SELECT setting_key, setting_value FROM homepage_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
$siteName = $settings['site_name'] ?? 'Fast Site';
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Viewing Agent: <?= htmlspecialchars($agent['name']) ?> — Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Oswald:wght@600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>

<!-- ADMIN INDICATOR BAR -->
<div class="admin-bar">
  <span>👁 <strong>ADMIN VIEW</strong> — Viewing: <?= htmlspecialchars($agent['name']) ?> (<?= htmlspecialchars($agent['ref_code']) ?>) — Read Only</span>
  <a href="agents.php">← Back to Agents</a>
</div>

<!-- Fake agent nav (read-only) -->
<?php include 'nav.php'; ?>

<!-- Agent info summary -->
<div class="agent-info-card">
  <p><strong>Name:</strong> <?= htmlspecialchars($agent['name']) ?> &nbsp;|&nbsp;
     <strong>Phone:</strong> <?= htmlspecialchars($agent['phone']) ?> &nbsp;|&nbsp;
     <strong>Ref Code:</strong> <span style="color:var(--gold);font-weight:700;"><?= htmlspecialchars($agent['ref_code']) ?></span> &nbsp;|&nbsp;
     <strong>Status:</strong> <span class="status-chip <?= $agent['status'] ?>"><?= ucfirst($agent['status']) ?></span>
  </p>
  <p><strong>Registered:</strong> <?= date('d M Y', strtotime($agent['created_at'])) ?></p>
</div>

<!-- HERO EARNINGS -->
<div class="hero-earnings">
  <p class="earnings-label">💰 মোট আয়</p>
  <div class="earnings-amount">৳<?= number_format($agent['total_earned'], 2) ?></div>
  <p class="earnings-sub">উপলব্ধ: <strong style="color:var(--green);">৳<?= number_format($available, 2) ?></strong></p>
</div>

<!-- STATS -->
<div class="stats-row">
  <div class="stat-card"><div class="stat-num" style="color:var(--gold);">৳<?= number_format($agent['total_earned'],2) ?></div><div class="stat-label">মোট উপার্জন</div></div>
  <div class="stat-card"><div class="stat-num" style="color:var(--green);">৳<?= number_format($available,2) ?></div><div class="stat-label">উপলব্ধ</div></div>
  <div class="stat-card"><div class="stat-num" style="color:#42a5f5;"><?= (int)($st['total']??0) ?></div><div class="stat-label">মোট অর্ডার</div></div>
  <div class="stat-card"><div class="stat-num" style="color:#ce93d8;">৳<?= number_format($agent['total_withdrawn'],2) ?></div><div class="stat-label">উত্তোলিত</div></div>
</div>

<div class="wrap">
  <!-- Commissions -->
  <div class="section-hd">📋 কমিশন ইতিহাস</div>
  <div class="desktop-table-wrap">
    <?php if(empty($comms)): ?>
      <div class="empty" style="text-align:center;padding:1.5rem;color:var(--muted);">No commissions yet.</div>
    <?php else: ?>
      <div class="desktop-table-wrap">
        <table>
          <thead><tr><th>Ref</th><th>Service</th><th>Order Fee</th><th>Commission</th><th>Status</th><th>Date</th></tr></thead>
          <tbody>
          <?php foreach($comms as $c): ?>
            <tr>
              <td style="color:var(--muted);font-size:.72rem;"><?= htmlspecialchars($c['order_ref']) ?></td>
              <td><?= htmlspecialchars($c['service_name']) ?></td>
              <td>৳<?= number_format($c['order_fee'],2) ?></td>
              <td style="color:var(--gold);font-weight:700;">৳<?= number_format($c['commission_amount'],2) ?></td>
              <td><span class="badge <?= $c['status'] ?>"><?= ucfirst($c['status']) ?></span></td>
              <td style="font-size:.72rem;color:var(--muted);"><?= date('d M Y',strtotime($c['created_at'])) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Mobile Stacked Cards -->
      <div class="mobile-cards-wrap">
        <?php foreach($comms as $c): ?>
        <div class="box" style="margin-bottom:1rem; background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.05); padding:1rem; border-radius:12px;">
          <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px;">
            <div>
              <div style="font-weight:700; color:var(--gold); font-size:1.1rem;">৳<?= number_format($c['commission_amount'],2) ?></div>
              <div style="font-size:0.75rem; color:var(--muted);"><?= date('d M Y',strtotime($c['created_at'])) ?></div>
            </div>
            <span class="badge <?= $c['status'] ?>"><?= ucfirst($c['status']) ?></span>
          </div>
          
          <div style="margin-bottom:10px; font-size:0.9rem;">
            <div><strong style="color:var(--muted);">Service:</strong> <?= htmlspecialchars($c['service_name']) ?></div>
            <div><strong style="color:var(--muted);">Order Fee:</strong> ৳<?= number_format($c['order_fee'],2) ?></div>
            <div><strong style="color:var(--muted);">Ref:</strong> <span style="font-family:monospace;"><?= htmlspecialchars($c['order_ref']) ?></span></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- Payouts -->
  <div class="section-hd">💳 উত্তোলন ইতিহাস</div>
  <div class="desktop-table-wrap">
    <?php if(empty($pays)): ?>
      <div class="empty" style="text-align:center;padding:1.5rem;color:var(--muted);">No payouts yet.</div>
    <?php else: ?>
      <div class="desktop-table-wrap">
        <table>
          <thead><tr><th>Amount</th><th>bKash</th><th>Status</th><th>Requested</th><th>Paid</th></tr></thead>
          <tbody>
          <?php foreach($pays as $p): ?>
            <tr>
              <td style="color:var(--gold);font-weight:700;">৳<?= number_format($p['amount'],2) ?></td>
              <td style="color:#8888aa;"><?= htmlspecialchars($p['bkash_number']) ?></td>
              <td><span class="badge <?= $p['status'] ?>"><?= ucfirst($p['status']) ?></span></td>
              <td style="font-size:.72rem;color:var(--muted);"><?= date('d M Y',strtotime($p['requested_at'])) ?></td>
              <td style="font-size:.72rem;color:var(--muted);"><?= $p['paid_at']?date('d M Y',strtotime($p['paid_at'])):'—' ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Mobile Stacked Cards -->
      <div class="mobile-cards-wrap">
        <?php foreach($pays as $p): ?>
        <div class="box" style="margin-bottom:1rem; background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.05); padding:1rem; border-radius:12px;">
          <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px;">
            <div>
              <div style="font-weight:700; color:var(--gold); font-size:1.1rem;">৳<?= number_format($p['amount'],2) ?></div>
              <div style="font-size:0.75rem; color:var(--muted);"><?= date('d M Y',strtotime($p['requested_at'])) ?></div>
            </div>
            <span class="badge <?= $p['status'] ?>"><?= ucfirst($p['status']) ?></span>
          </div>
          
          <div style="margin-bottom:10px; font-size:0.9rem;">
            <div><strong style="color:var(--muted);">bKash:</strong> <span style="color:#00e676; font-weight:600;"><?= htmlspecialchars($p['bkash_number']) ?></span></div>
            <?php if($p['paid_at']): ?>
              <div><strong style="color:var(--muted);">Paid:</strong> <?= date('d M Y',strtotime($p['paid_at'])) ?></div>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

</body>
</html>
