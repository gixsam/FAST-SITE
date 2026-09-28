<?php
// =========================================================================
// admin/agents.php — Affiliate Agents & Multi-Tier Partners Hub
// =========================================================================
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/../config.php';

$msg = $err = '';

// Handle approve / suspend / reactivate / delete
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id  = (int)$_GET['id'];
    $act = $_GET['action'];
    if ($act === 'approve') {
        $pdo->prepare("UPDATE agents SET status='active' WHERE id=:id")->execute([':id'=>$id]);
        $msg = 'Agent approved successfully!';
    } elseif ($act === 'suspend') {
        $pdo->prepare("UPDATE agents SET status='suspended' WHERE id=:id")->execute([':id'=>$id]);
        $msg = 'Agent suspended.';
    } elseif ($act === 'delete') {
        $pdo->prepare("DELETE FROM agents WHERE id=:id")->execute([':id'=>$id]);
        $msg = 'Agent deleted from system.';
    }
}

// Handle commission mark-as-paid
if (isset($_GET['pay_commission'])) {
    $cid = (int)$_GET['pay_commission'];
    try {
        $pdo->prepare("UPDATE agent_commissions SET status='paid' WHERE id=:id")->execute([':id'=>$cid]);
        $msg = 'Commission marked as settled.';
    } catch (Exception $e) {}
}

// Fetch all agents safely (no invalid user_id join)
$agents = [];
try {
    $agents = $pdo->query("SELECT * FROM agents ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $agents = [];
}

// Fetch summaries per agent safely
$summaries = [];
foreach ($agents as $ag) {
    $agId = $ag['id'];
    $summaries[$agId] = ['orders' => 0, 'earned' => 0];
    try {
        $s = $pdo->prepare("SELECT COUNT(*) AS orders, COALESCE(SUM(commission_amount), 0) AS earned FROM agent_commissions WHERE agent_id = :id");
        $s->execute([':id' => $agId]);
        if ($row = $s->fetch()) {
            $summaries[$agId] = $row;
        }
    } catch (Exception $e) {}
}

function agentStatusBadge($st) {
    $map = [
        'pending'   => ['rgba(245,158,11,0.2)', '#f59e0b', 'Pending Approval'],
        'active'    => ['rgba(16,185,129,0.2)', '#10b981', 'Active Agent'],
        'suspended' => ['rgba(239,68,68,0.2)', '#ef4444', 'Suspended']
    ];
    [$bg, $col, $lbl] = $map[$st] ?? ['rgba(255,255,255,0.08)', '#94a3b8', ucfirst($st)];
    return "<span style=\"background:{$bg}; color:{$col}; border:1px solid {$col}; padding:3px 10px; border-radius:20px; font-size:0.72rem; font-weight:800; text-transform:uppercase; letter-spacing:0.5px;\">{$lbl}</span>";
}

$total_agents   = count($agents);
$active_agents  = count(array_filter($agents, fn($a) => ($a['status'] ?? '') === 'active'));
$pending_agents = count(array_filter($agents, fn($a) => ($a['status'] ?? '') === 'pending'));
$total_paid     = 0;
try {
    $total_paid = (float)($pdo->query("SELECT COALESCE(SUM(total_withdrawn), 0) FROM agents")->fetchColumn() ?: 0);
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0"/>
  <title>Affiliate Agents Hub — Fast Site Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Oswald:wght@600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="/assets/css/admin.css?v=<?= time() ?>"/>
  <style>
    .kpi-grid-4 {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 1.2rem;
      margin-bottom: 2rem;
    }
    .kpi-card {
      background: rgba(16, 18, 28, 0.85);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 18px;
      padding: 1.4rem;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
      backdrop-filter: blur(14px);
    }
    .kpi-label {
      font-size: 0.75rem;
      font-weight: 800;
      color: var(--muted);
      text-transform: uppercase;
      letter-spacing: 0.8px;
      margin-bottom: 0.4rem;
    }
    .kpi-val {
      font-size: 2rem;
      font-weight: 800;
      font-family: 'Oswald', sans-serif;
      line-height: 1.1;
      color: #fff;
    }

    .table-container {
      background: rgba(16, 18, 28, 0.85);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 18px;
      padding: 1.5rem;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
      backdrop-filter: blur(14px);
      overflow-x: auto;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      text-align: left;
      font-size: 0.88rem;
    }
    th {
      border-bottom: 1px solid rgba(255, 255, 255, 0.08);
      color: var(--muted);
      font-size: 0.72rem;
      text-transform: uppercase;
      letter-spacing: 0.8px;
      padding: 0.8rem 0.6rem;
    }
    td {
      border-bottom: 1px solid rgba(255, 255, 255, 0.04);
      padding: 0.9rem 0.6rem;
      color: #fff;
      vertical-align: middle;
    }
    tr:hover td {
      background: rgba(255, 255, 255, 0.02);
    }
    .ref-code-pill {
      font-family: monospace;
      background: rgba(252, 185, 0, 0.12);
      border: 1px solid rgba(252, 185, 0, 0.3);
      color: var(--gold);
      padding: 3px 8px;
      border-radius: 6px;
      font-size: 0.75rem;
      font-weight: 700;
    }
    .action-btn {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      padding: 0.4rem 0.75rem;
      border-radius: 8px;
      font-size: 0.75rem;
      font-weight: 700;
      text-decoration: none;
      transition: all 0.2s;
    }
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
        <h2 class="admin-hero-title">🔗 AFFILIATE AGENTS HUB</h2>
        <p class="admin-hero-subtitle">Manage registered referral agents, commission earnings, and partner performance</p>
      </div>
      <div style="display:flex; gap:0.6rem; flex-wrap:wrap;">
        <a href="payouts.php#tab-agent-payouts" class="btn-sm" style="display:inline-flex; align-items:center; gap:0.4rem; padding: 0.55rem 1.2rem; background: rgba(16, 185, 129, 0.15); border:1px solid #10b981; color:#10b981; font-weight:700; text-decoration:none; border-radius:10px;">
          💳 Agent Payouts ➔
        </a>
        <a href="partner_shops.php" class="btn-sm" style="display:inline-flex; align-items:center; gap:0.4rem; padding: 0.55rem 1.2rem; background: rgba(255, 255, 255, 0.08); border:1px solid rgba(255, 255, 255, 0.15); color:#fff; font-weight:700; text-decoration:none; border-radius:10px;">
          🏪 Partner Shops ➔
        </a>
      </div>
    </div>
  </div>

  <!-- Status Alerts -->
  <?php if ($msg): ?>
    <div style="background:rgba(16,185,129,0.15); border:1px solid #10b981; color:#10b981; padding:0.9rem 1.3rem; border-radius:12px; margin-bottom:1.5rem; font-weight:700; display:flex; align-items:center; gap:8px;">
      ✅ <?= htmlspecialchars($msg) ?>
    </div>
  <?php endif; ?>

  <!-- KPI Overview Cards -->
  <div class="kpi-grid-4">
    <div class="kpi-card">
      <div class="kpi-label">👥 Total Agents</div>
      <div class="kpi-val" style="color:var(--gold);"><?= number_format($total_agents) ?></div>
    </div>
    <div class="kpi-card">
      <div class="kpi-label">⚡ Active Agents</div>
      <div class="kpi-val" style="color:#10b981;"><?= number_format($active_agents) ?></div>
    </div>
    <div class="kpi-card">
      <div class="kpi-label">⏳ Pending Approval</div>
      <div class="kpi-val" style="color:#f59e0b;"><?= number_format($pending_agents) ?></div>
    </div>
    <div class="kpi-card">
      <div class="kpi-label">💰 Total Paid Out</div>
      <div class="kpi-val" style="color:#38bdf8;">৳<?= number_format($total_paid, 2) ?></div>
    </div>
  </div>

  <!-- Main Agents Ledger Table -->
  <div class="table-container">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.2rem;">
      <h3 style="font-family:'Oswald',sans-serif; color:#fff; font-size:1.15rem; margin:0; text-transform:uppercase;">
        📋 Registered Agents Ledger (<?= count($agents) ?>)
      </h3>
    </div>

    <?php if (empty($agents)): ?>
      <div style="text-align:center; padding:3rem; color:#94a3b8;">
        <div style="font-size:3rem; margin-bottom:0.5rem;">👥</div>
        No affiliate agents registered in the platform yet.
      </div>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Agent Name</th>
            <th>Phone / Contact</th>
            <th>Ref Code</th>
            <th style="text-align:center;">Orders</th>
            <th style="text-align:right;">Earned</th>
            <th style="text-align:right;">Withdrawn</th>
            <th style="text-align:center;">Status</th>
            <th>Registered</th>
            <th style="text-align:right;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($agents as $ag): ?>
            <?php 
              $sm = $summaries[$ag['id']] ?? ['orders' => 0, 'earned' => 0];
              $status = $ag['status'] ?? 'pending';
            ?>
            <tr>
              <td style="color:#94a3b8; font-family:monospace;">#<?= $ag['id'] ?></td>
              <td>
                <div style="font-weight:700; color:#fff;"><?= htmlspecialchars($ag['name']) ?></div>
                <div style="font-size:0.75rem; color:#94a3b8;"><?= htmlspecialchars($ag['email'] ?: 'No email') ?></div>
              </td>
              <td style="font-family:monospace; color:#cbd5e1;"><?= htmlspecialchars($ag['phone']) ?></td>
              <td>
                <span class="ref-code-pill"><?= htmlspecialchars($ag['ref_code']) ?></span>
              </td>
              <td style="text-align:center; font-weight:700; color:#cbd5e1;"><?= (int)$sm['orders'] ?></td>
              <td style="text-align:right; font-weight:800; font-family:'Oswald',sans-serif; color:var(--gold); font-size:0.95rem;">
                ৳<?= number_format((float)$sm['earned'], 2) ?>
              </td>
              <td style="text-align:right; font-weight:800; font-family:'Oswald',sans-serif; color:#10b981; font-size:0.95rem;">
                ৳<?= number_format((float)$ag['total_withdrawn'], 2) ?>
              </td>
              <td style="text-align:center;">
                <?= agentStatusBadge($status) ?>
              </td>
              <td style="font-size:0.75rem; color:#94a3b8;">
                <?= date('d M Y', strtotime($ag['created_at'] ?? 'now')) ?>
              </td>
              <td style="text-align:right; white-space:nowrap;">
                <div style="display:inline-flex; gap:0.4rem; align-items:center;">
                  <?php if ($status === 'pending'): ?>
                    <a href="agents.php?action=approve&id=<?= $ag['id'] ?>" class="action-btn" style="background:rgba(16,185,129,0.15); border:1px solid #10b981; color:#10b981;">
                      ✓ Approve
                    </a>
                  <?php elseif ($status === 'active'): ?>
                    <a href="agents.php?action=suspend&id=<?= $ag['id'] ?>" onclick="return confirm('Suspend this agent?');" class="action-btn" style="background:rgba(245,158,11,0.15); border:1px solid #f59e0b; color:#f59e0b;">
                      ⏸ Suspend
                    </a>
                  <?php elseif ($status === 'suspended'): ?>
                    <a href="agents.php?action=approve&id=<?= $ag['id'] ?>" class="action-btn" style="background:rgba(16,185,129,0.15); border:1px solid #10b981; color:#10b981;">
                      ▶ Reactivate
                    </a>
                  <?php endif; ?>
                  
                  <a href="view_agent.php?id=<?= $ag['id'] ?>" class="action-btn" style="background:rgba(56,189,248,0.12); border:1px solid #38bdf8; color:#38bdf8;" target="_blank">
                    👁 View
                  </a>

                  <a href="agents.php?action=delete&id=<?= $ag['id'] ?>" onclick="return confirm('Permanently delete agent <?= htmlspecialchars($ag['name']) ?>?');" class="action-btn" style="background:rgba(239,68,68,0.12); border:1px solid rgba(239,68,68,0.3); color:#ef4444;">
                    🗑
                  </a>
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
