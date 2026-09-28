<?php
// =========================================================================
// admin/coin_deposits.php — User Fast Points & Coin Deposit Approvals
// =========================================================================
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/../config.php';

$msg = '';
$err = '';

// Handle Approval/Rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $req_id = intval($_POST['request_id'] ?? 0);
    $action = $_POST['action'];
    $notes = trim($_POST['admin_notes'] ?? '');
    
    if ($req_id > 0) {
        $stmt = $pdo->prepare("SELECT * FROM deposit_requests WHERE id = ? AND status = 'pending'");
        $stmt->execute([$req_id]);
        $req = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($req) {
            if ($action === 'approve') {
                $pdo->beginTransaction();
                try {
                    // Update request status
                    $pdo->prepare("UPDATE deposit_requests SET status = 'approved', admin_notes = ? WHERE id = ?")->execute([$notes, $req_id]);
                    // Add coins to user
                    $pdo->prepare("UPDATE users SET coins_balance = coins_balance + ? WHERE id = ?")->execute([$req['amount'], $req['user_id']]);
                    
                    // Add transaction log
                    $txNote = "Deposit Approval: TrxID " . ($req['transaction_id'] ?? 'Manual');
                    try {
                        $pdo->prepare("INSERT INTO coin_transactions (user_id, type, amount, reference, status, created_at) VALUES (?, 'deposit', ?, ?, 'completed', CURRENT_TIMESTAMP)")
                            ->execute([$req['user_id'], $req['amount'], $txNote]);
                    } catch (Exception $eTx) {
                        try {
                            $pdo->prepare("INSERT INTO coin_transactions (user_id, type, amount, description) VALUES (?, 'deposit', ?, ?)")
                                ->execute([$req['user_id'], $req['amount'], $txNote]);
                        } catch (Exception $eTx2) {}
                    }

                    $pdo->commit();
                    $msg = "✅ Deposit request #$req_id approved! " . number_format($req['amount'], 2) . " Coins credited to user.";
                } catch (Exception $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    $err = "Error approving request: " . $e->getMessage();
                }
            } elseif ($action === 'reject') {
                $pdo->prepare("UPDATE deposit_requests SET status = 'rejected', admin_notes = ? WHERE id = ?")->execute([$notes, $req_id]);
                $msg = "Deposit request #$req_id rejected.";
            }
        }
    }
}

// Fetch all requests
$requests = [];
try {
    $stmt = $pdo->query("
        SELECT d.*, u.name as user_name, u.phone as user_phone, u.coins_balance
        FROM deposit_requests d 
        LEFT JOIN users u ON d.user_id = u.id 
        ORDER BY d.created_at DESC
    ");
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $requests = [];
}

$pending_count = count(array_filter($requests, fn($r) => ($r['status'] ?? '') === 'pending'));
$approved_count = count(array_filter($requests, fn($r) => ($r['status'] ?? '') === 'approved'));
$total_deposit_vol = array_sum(array_map(fn($r) => ($r['status'] === 'approved' ? (float)$r['amount'] : 0), $requests));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0"/>
  <title>Coin Deposit Requests — Fast Site Admin</title>
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
    .badge-approved {
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
        <h2 class="admin-hero-title">💰 COIN DEPOSIT & POINTS INVOICES</h2>
        <p class="admin-hero-subtitle">Review incoming bKash/Nagad customer payments and approve fast coin credits</p>
      </div>
      <div style="display:flex; gap:0.6rem; flex-wrap:wrap;">
        <a href="wallet.php" class="btn-sm" style="display:inline-flex; align-items:center; gap:0.4rem; padding: 0.55rem 1.2rem; background: rgba(252, 185, 0, 0.15); border:1px solid #fcb900; color:#fcb900; font-weight:700; text-decoration:none; border-radius:10px;">
          💼 Master Wallet ➔
        </a>
        <a href="user_withdrawals.php" class="btn-sm" style="display:inline-flex; align-items:center; gap:0.4rem; padding: 0.55rem 1.2rem; background: rgba(239, 68, 68, 0.15); border:1px solid #ef4444; color:#ef4444; font-weight:700; text-decoration:none; border-radius:10px;">
          💸 User Cashouts ➔
        </a>
      </div>
    </div>
  </div>

  <!-- Alerts -->
  <?php if ($msg): ?>
    <div style="background:rgba(16,185,129,0.15); border:1px solid #10b981; color:#10b981; padding:0.9rem 1.3rem; border-radius:12px; margin-bottom:1.5rem; font-weight:700; display:flex; align-items:center; gap:8px;">
      <?= htmlspecialchars($msg) ?>
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
      <div class="kpi-label">⏳ Pending Invoices</div>
      <div class="kpi-val" style="color:#f59e0b;"><?= number_format($pending_count) ?></div>
    </div>
    <div class="kpi-card">
      <div class="kpi-label">✅ Approved Deposits</div>
      <div class="kpi-val" style="color:#10b981;"><?= number_format($approved_count) ?></div>
    </div>
    <div class="kpi-card">
      <div class="kpi-label">🪙 Total Approved Volume</div>
      <div class="kpi-val" style="color:var(--gold);"><?= number_format($total_deposit_vol, 2) ?> Coins</div>
    </div>
  </div>

  <!-- Deposit Requests Ledger Table -->
  <div class="table-container">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.2rem;">
      <h3 style="font-family:'Oswald',sans-serif; color:#fff; font-size:1.15rem; margin:0; text-transform:uppercase;">
        📋 Deposit Invoices Ledger (<?= count($requests) ?>)
      </h3>
    </div>

    <?php if (empty($requests)): ?>
      <div style="text-align:center; padding:3rem; color:#94a3b8;">
        <div style="font-size:3rem; margin-bottom:0.5rem;">📫</div>
        No deposit requests submitted yet.
      </div>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Date & Time</th>
            <th>Customer</th>
            <th>Sender & TRXID</th>
            <th style="text-align:right;">Deposit Amount</th>
            <th style="text-align:center;">Status</th>
            <th style="text-align:right;">Actions / Verification</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($requests as $r): ?>
            <?php 
              $st = strtolower($r['status'] ?? 'pending');
              $badgeClass = $st === 'approved' ? 'badge-approved' : ($st === 'rejected' ? 'badge-rejected' : 'badge-pending');
            ?>
            <tr>
              <td style="font-size:0.75rem; color:#94a3b8;">
                <?= date('d M Y, h:i A', strtotime($r['created_at'])) ?>
              </td>
              <td>
                <div style="font-weight:700; color:#fff;"><?= htmlspecialchars($r['user_name'] ?: 'Customer #' . $r['user_id']) ?></div>
                <div style="font-size:0.75rem; color:#94a3b8; font-family:monospace;"><?= htmlspecialchars($r['user_phone'] ?: 'No Phone') ?></div>
              </td>
              <td>
                <div style="color:var(--gold); font-weight:700; font-family:monospace;">
                  TRX: <?= htmlspecialchars($r['transaction_id'] ?: 'N/A') ?>
                </div>
                <div style="font-size:0.75rem; color:#38bdf8;">
                  📱 Sender: <?= htmlspecialchars($r['sender_number'] ?: 'N/A') ?>
                </div>
              </td>
              <td style="text-align:right; font-family:'Oswald',sans-serif; font-size:1.15rem; font-weight:800; color:#10b981;">
                +<?= number_format($r['amount'], 2) ?> <span style="font-size:0.75rem; color:#94a3b8;">BDT / Coins</span>
              </td>
              <td style="text-align:center;">
                <span class="<?= $badgeClass ?>"><?= strtoupper($st) ?></span>
              </td>
              <td style="text-align:right;">
                <?php if ($st === 'pending'): ?>
                  <form method="POST" style="display:inline-flex; flex-direction:column; gap:6px; align-items:flex-end;">
                    <input type="hidden" name="request_id" value="<?= $r['id'] ?>"/>
                    <input type="text" name="admin_notes" placeholder="Note (Optional)" style="background:rgba(0,0,0,0.5); border:1px solid rgba(255,255,255,0.12); padding:0.35rem 0.6rem; border-radius:6px; color:#fff; font-size:0.75rem; width:140px; outline:none;"/>
                    <div style="display:flex; gap:5px;">
                      <button type="submit" name="action" value="approve" onclick="return confirm('Approve deposit and credit <?= number_format($r['amount'], 2) ?> coins to user?');" style="background:rgba(16,185,129,0.2); border:1px solid #10b981; color:#10b981; padding:0.35rem 0.75rem; border-radius:6px; font-weight:700; font-size:0.75rem; cursor:pointer;">
                        ✓ Approve
                      </button>
                      <button type="submit" name="action" value="reject" onclick="return confirm('Reject deposit request?');" style="background:rgba(239,68,68,0.2); border:1px solid #ef4444; color:#ef4444; padding:0.35rem 0.75rem; border-radius:6px; font-weight:700; font-size:0.75rem; cursor:pointer;">
                        ✕ Reject
                      </button>
                    </div>
                  </form>
                <?php else: ?>
                  <div style="font-size:0.75rem; color:#94a3b8;">
                    <?= htmlspecialchars($r['admin_notes'] ?: 'Processed') ?>
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
