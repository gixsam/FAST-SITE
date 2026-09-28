<?php
// =========================================================================
// admin/user_withdrawals.php — Customer Coin Withdrawals & Cashout Gateway
// =========================================================================
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/../config.php';

$msg = $err = '';

// Handle Accept / Reject
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['withdrawal_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    
    if ($id > 0 && in_array($action, ['paid', 'rejected'])) {
        if ($action === 'rejected') {
            // Refund the coins
            try {
                $pdo->beginTransaction();
                $wStmt = $pdo->prepare("SELECT user_id, amount_coins, status FROM user_withdrawals WHERE id = ?");
                $wStmt->execute([$id]);
                $w = $wStmt->fetch(PDO::FETCH_ASSOC);
                if ($w && $w['status'] === 'pending') {
                    $pdo->prepare("UPDATE users SET coins_balance = coins_balance + ? WHERE id = ?")->execute([$w['amount_coins'], $w['user_id']]);
                    $pdo->prepare("UPDATE user_withdrawals SET status = 'rejected', processed_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$id]);
                    
                    // Transaction log for refund
                    try {
                        $pdo->prepare("INSERT INTO coin_transactions (user_id, type, amount, reference, status, created_at) VALUES (?, 'refund', ?, ?, 'completed', CURRENT_TIMESTAMP)")
                            ->execute([$w['user_id'], $w['amount_coins'], "Withdrawal #$id Rejected & Refunded"]);
                    } catch (Exception $eTx) {}
                }
                $pdo->commit();
                $msg = "Withdrawal #$id rejected and " . number_format($w['amount_coins'] ?? 0, 2) . " coins refunded to user.";
            } catch(Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $err = "Error rejecting withdrawal: " . $e->getMessage();
            }
        } else {
            // Mark as Paid
            try {
                $pdo->prepare("UPDATE user_withdrawals SET status = 'paid', processed_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$id]);
                $msg = "Withdrawal #$id marked as paid successfully!";
            } catch (Exception $e) {
                $err = "Error marking paid: " . $e->getMessage();
            }
        }
    }
}

// Fetch all withdrawals
$withdrawals = [];
try {
    $withdrawals = $pdo->query("
        SELECT w.*, u.name as user_name, u.phone as user_phone, u.coins_balance
        FROM user_withdrawals w 
        LEFT JOIN users u ON w.user_id = u.id 
        ORDER BY CASE WHEN w.status = 'pending' THEN 0 ELSE 1 END, w.created_at DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $withdrawals = [];
}

$pending_count = count(array_filter($withdrawals, fn($w) => ($w['status'] ?? '') === 'pending'));
$paid_count = count(array_filter($withdrawals, fn($w) => ($w['status'] ?? '') === 'paid'));
$total_paid_vol = array_sum(array_map(fn($w) => ($w['status'] === 'paid' ? (float)$w['amount_coins'] : 0), $withdrawals));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0"/>
  <title>Customer Withdrawals — Fast Site Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Oswald:wght@600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="/assets/css/admin.css?v=<?= time() ?>"/>
  <style>
    .kpi-grid-3 {
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
    .badge-pending {
      background: rgba(245, 158, 11, 0.2);
      border: 1px solid #f59e0b;
      color: #f59e0b;
      padding: 3px 8px;
      border-radius: 6px;
      font-size: 0.72rem;
      font-weight: 800;
      text-transform: uppercase;
    }
    .badge-paid {
      background: rgba(16, 185, 129, 0.2);
      border: 1px solid #10b981;
      color: #10b981;
      padding: 3px 8px;
      border-radius: 6px;
      font-size: 0.72rem;
      font-weight: 800;
      text-transform: uppercase;
    }
    .badge-rejected {
      background: rgba(239, 68, 68, 0.2);
      border: 1px solid #ef4444;
      color: #ef4444;
      padding: 3px 8px;
      border-radius: 6px;
      font-size: 0.72rem;
      font-weight: 800;
      text-transform: uppercase;
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
        <h2 class="admin-hero-title">💸 CUSTOMER COIN CASHOUTS</h2>
        <p class="admin-hero-subtitle">Process user reward withdrawals to bKash, Nagad, or Bank accounts</p>
      </div>
      <div style="display:flex; gap:0.6rem; flex-wrap:wrap;">
        <a href="wallet.php" class="btn-sm" style="display:inline-flex; align-items:center; gap:0.4rem; padding: 0.55rem 1.2rem; background: rgba(252, 185, 0, 0.15); border:1px solid #fcb900; color:#fcb900; font-weight:700; text-decoration:none; border-radius:10px;">
          💼 Master Wallet ➔
        </a>
        <a href="coin_deposits.php" class="btn-sm" style="display:inline-flex; align-items:center; gap:0.4rem; padding: 0.55rem 1.2rem; background: rgba(16, 185, 129, 0.15); border:1px solid #10b981; color:#10b981; font-weight:700; text-decoration:none; border-radius:10px;">
          💰 User Deposits ➔
        </a>
      </div>
    </div>
  </div>

  <!-- Alerts -->
  <?php if ($msg): ?>
    <div style="background:rgba(16,185,129,0.15); border:1px solid #10b981; color:#10b981; padding:0.9rem 1.3rem; border-radius:12px; margin-bottom:1.5rem; font-weight:700; display:flex; align-items:center; gap:8px;">
      ✅ <?= htmlspecialchars($msg) ?>
    </div>
  <?php endif; ?>

  <?php if ($err): ?>
    <div style="background:rgba(239,68,68,0.15); border:1px solid #ef4444; color:#ef4444; padding:0.9rem 1.3rem; border-radius:12px; margin-bottom:1.5rem; font-weight:700; display:flex; align-items:center; gap:8px;">
      ⚠️ <?= htmlspecialchars($err) ?>
    </div>
  <?php endif; ?>

  <!-- KPI Overview Cards -->
  <div class="kpi-grid-3">
    <div class="kpi-card">
      <div class="kpi-label">⏳ Pending Requests</div>
      <div class="kpi-val" style="color:#f59e0b;"><?= number_format($pending_count) ?></div>
    </div>
    <div class="kpi-card">
      <div class="kpi-label">✅ Settled Payouts</div>
      <div class="kpi-val" style="color:#10b981;"><?= number_format($paid_count) ?></div>
    </div>
    <div class="kpi-card">
      <div class="kpi-label">💸 Total Cash Disbursed</div>
      <div class="kpi-val" style="color:#38bdf8;">৳<?= number_format($total_paid_vol, 2) ?></div>
    </div>
  </div>

  <!-- Main Withdrawals Table -->
  <div class="table-container">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.2rem;">
      <h3 style="font-family:'Oswald',sans-serif; color:#fff; font-size:1.15rem; margin:0; text-transform:uppercase;">
        📋 Cashout Requests Ledger (<?= count($withdrawals) ?>)
      </h3>
    </div>

    <?php if (empty($withdrawals)): ?>
      <div style="text-align:center; padding:3rem; color:#94a3b8;">
        <div style="font-size:3rem; margin-bottom:0.5rem;">📫</div>
        No customer withdrawal requests submitted yet.
      </div>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Date & Time</th>
            <th>Customer</th>
            <th>Payout Method</th>
            <th>Account / Number</th>
            <th style="text-align:right;">Amount (Coins)</th>
            <th style="text-align:center;">Status</th>
            <th style="text-align:right;">Actions / Settlement</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($withdrawals as $w): ?>
            <?php 
              $st = strtolower($w['status'] ?? 'pending');
              $badgeClass = $st === 'paid' ? 'badge-paid' : ($st === 'rejected' ? 'badge-rejected' : 'badge-pending');
            ?>
            <tr>
              <td style="font-size:0.75rem; color:#94a3b8;">
                <?= date('d M Y, h:i A', strtotime($w['created_at'])) ?>
              </td>
              <td>
                <div style="font-weight:700; color:#fff;"><?= htmlspecialchars($w['user_name'] ?: 'Customer #' . $w['user_id']) ?></div>
                <div style="font-size:0.75rem; color:#94a3b8; font-family:monospace;"><?= htmlspecialchars($w['user_phone'] ?: 'No Phone') ?></div>
              </td>
              <td>
                <span style="font-weight:700; color:#38bdf8; text-transform:uppercase;">
                  📱 <?= htmlspecialchars($w['payout_method'] ?: 'bKash') ?>
                </span>
              </td>
              <td>
                <span style="font-family:monospace; color:var(--gold); font-weight:700; background:rgba(252,185,0,0.1); padding:2px 8px; border-radius:6px;">
                  <?= htmlspecialchars($w['payout_account'] ?: 'N/A') ?>
                </span>
              </td>
              <td style="text-align:right; font-family:'Oswald',sans-serif; font-size:1.15rem; font-weight:800; color:#fcb900;">
                -<?= number_format($w['amount_coins'], 2) ?> <span style="font-size:0.75rem; color:#94a3b8;">Coins (৳)</span>
              </td>
              <td style="text-align:center;">
                <span class="<?= $badgeClass ?>"><?= strtoupper($st) ?></span>
              </td>
              <td style="text-align:right;">
                <?php if ($st === 'pending'): ?>
                  <form method="POST" style="display:inline-flex; gap:6px;">
                    <input type="hidden" name="withdrawal_id" value="<?= $w['id'] ?>"/>
                    <button type="submit" name="action" value="paid" onclick="return confirm('Confirm cash payment of ৳<?= number_format($w['amount_coins'], 2) ?> to <?= htmlspecialchars($w['payout_account']) ?>?');" style="background:rgba(16,185,129,0.2); border:1px solid #10b981; color:#10b981; padding:0.4rem 0.8rem; border-radius:6px; font-weight:700; font-size:0.75rem; cursor:pointer;">
                      ✓ Mark Paid
                    </button>
                    <button type="submit" name="action" value="rejected" onclick="return confirm('Reject request and refund coins to user?');" style="background:rgba(239,68,68,0.2); border:1px solid #ef4444; color:#ef4444; padding:0.4rem 0.8rem; border-radius:6px; font-weight:700; font-size:0.75rem; cursor:pointer;">
                      ✕ Reject
                    </button>
                  </form>
                <?php else: ?>
                  <div style="font-size:0.75rem; color:#94a3b8;">
                    Processed <?= !empty($w['processed_at']) ? date('d M Y', strtotime($w['processed_at'])) : 'Done' ?>
                  </div>
                <?php endif; ?>
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
