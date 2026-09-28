<?php
// =========================================================================
// admin/partner_deposits.php  –  Verify and Process Point Purchases
// =========================================================================
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php'); exit;
}
require_once __DIR__ . '/../config.php';

$msg = $err = '';
$coin_name = getPartnerSetting('coin_name', 'Fast Points');
$exchange_rate = floatval(getPartnerSetting('exchange_rate', '1'));

// Handle Approve / Reject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $request_id = intval($_POST['request_id'] ?? 0);
    $action     = $_POST['action'];
    $admin_note = trim($_POST['admin_notes'] ?? '');

    if ($request_id > 0) {
        // Fetch request details
        $stmt_req = $pdo->prepare("SELECT * FROM deposit_requests WHERE id = :id LIMIT 1");
        $stmt_req->execute([':id' => $request_id]);
        $request = $stmt_req->fetch();

        if ($request && $request['status'] === 'pending') {
            if ($action === 'approve') {
                try {
                    $pdo->beginTransaction();

                    // 1. Update request status
                    $pdo->prepare("UPDATE deposit_requests SET status = 'approved', admin_notes = :note WHERE id = :id")
                        ->execute([':note' => $admin_note ?: null, ':id' => $request_id]);

                    // Calculate coins to credit based on BDT amount and exchange rate
                    $coins = $request['amount'] * $exchange_rate;

                    // 2. Ensure wallet exists and credit balance
                    $stmt_wallet = $pdo->prepare("SELECT balance FROM coin_wallets WHERE user_id = :uid LIMIT 1");
                    $stmt_wallet->execute([':uid' => $request['user_id']]);
                    $exists = $stmt_wallet->fetchColumn() !== false;

                    if ($exists) {
                        $pdo->prepare("UPDATE coin_wallets SET balance = balance + :coins WHERE user_id = :uid")
                            ->execute([':coins' => $coins, ':uid' => $request['user_id']]);
                    } else {
                        $pdo->prepare("INSERT INTO coin_wallets (user_id, balance) VALUES (:uid, :coins)")
                            ->execute([':coins' => $coins, ':uid' => $request['user_id']]);
                    }

                    // 3. Insert transaction log
                    $pdo->prepare("INSERT INTO coin_transactions 
                        (user_id, type, amount, reference, status) 
                        VALUES (:uid, 'deposit', :coins, :ref, 'completed')")
                        ->execute([
                            ':uid'   => $request['user_id'],
                            ':coins' => $coins,
                            ':ref'   => "Deposit Request #$request_id (TrxID: " . $request['transaction_id'] . ")"
                        ]);

                    $pdo->commit();
                    $msg = 'Deposit approved and points credited to user wallet!';
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $err = 'Database Error: ' . $e->getMessage();
                }
            } elseif ($action === 'reject') {
                $pdo->prepare("UPDATE deposit_requests SET status = 'rejected', admin_notes = :note WHERE id = :id")
                    ->execute([':note' => $admin_note ?: null, ':id' => $request_id]);
                $msg = 'Deposit request rejected.';
            }
        } else {
            $err = 'Deposit request not found or already processed.';
        }
    }
}

// Fetch requests
$filter = $_GET['status'] ?? 'pending';
$where = "1=1";
if (in_array($filter, ['pending', 'approved', 'rejected'])) {
    $where = "dr.status = '$filter'";
}

$requests = $pdo->query("SELECT dr.*, u.name AS user_name, u.phone AS user_phone 
    FROM deposit_requests dr 
    JOIN users u ON dr.user_id = u.id 
    WHERE $where 
    ORDER BY dr.created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Customer Deposits — Fast Site Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>
<?php include 'nav.php'; ?>

<div class="wrap">
  <h1>🪙 Customer Points Purchase Requests</h1>
  <p style="font-size:0.85rem; color:#8888aa; margin-bottom:1.5rem;">Verify and approve coin deposits made by users via bKash/Nagad.</p>

  <?php if($msg): ?><div class="alert-ok">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if($err): ?><div class="alert-err">❌ <?= htmlspecialchars($err) ?></div><?php endif; ?>

  <div class="tabs-bar">
    <a href="partner_deposits.php?status=pending" class="tab-link <?= $filter === 'pending' ? 'active' : '' ?>">Pending Requests</a>
    <a href="partner_deposits.php?status=approved" class="tab-link <?= $filter === 'approved' ? 'active' : '' ?>">Approved</a>
    <a href="partner_deposits.php?status=rejected" class="tab-link <?= $filter === 'rejected' ? 'active' : '' ?>">Rejected</a>
  </div>

  <div class="panel">
    <div class="table-container">
      <?php if(empty($requests)): ?>
        <p style="color:#8888aa; font-size:0.85rem; text-align:center; padding:2rem 0;">No purchase requests found in this category.</p>
      <?php else: ?>
        <div class="desktop-table-wrap">
          <table>
            <thead>
              <tr>
                <th>Request ID</th>
                <th>Customer</th>
                <th>Payment Details</th>
                <th>Amount Sent</th>
                <th>Attachment</th>
                <th>Status</th>
                <th>Admin Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($requests as $req): ?>
                <tr>
                  <td>#<?= htmlspecialchars($req['id']) ?></td>
                  <td>
                    <div style="font-weight:700; color:#fff;"><?= htmlspecialchars($req['user_name']) ?></div>
                    <div style="font-size:0.75rem; color:#8888aa;"><?= htmlspecialchars($req['user_phone']) ?></div>
                  </td>
                  <td>
                    <div style="font-size:0.8rem;">Sender No: <strong><?= htmlspecialchars($req['sender_number']) ?></strong></div>
                    <div style="font-size:0.8rem;">TrxID: <strong style="color:#fcb900;"><?= htmlspecialchars($req['transaction_id']) ?></strong></div>
                  </td>
                  <td>
                    <div style="font-weight:700; color:#00e676;"><?= number_format($req['amount'], 2) ?> BDT</div>
                    <div style="font-size:0.75rem; color:#8888aa;">≈ <?= number_format($req['amount'] * $exchange_rate, 1) ?> <?= htmlspecialchars($coin_name) ?></div>
                  </td>
                  <td>
                    <?php if($req['screenshot_url']): ?>
                      <a href="/uploads/deposits/<?= htmlspecialchars($req['screenshot_url']) ?>" target="_blank" style="color:#fcb900; text-decoration:none; font-weight:700;">View Screenshot ↗</a>
                    <?php else: ?>
                      <span style="color:#8888aa;">No Screenshot</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <span class="status-badge status-<?= htmlspecialchars($req['status']) ?>">
                      <?= htmlspecialchars($req['status']) ?>
                    </span>
                    <?php if($req['admin_notes']): ?>
                      <div style="font-size:0.75rem; color:#8888aa; margin-top:0.3rem;">Notes: <?= htmlspecialchars($req['admin_notes']) ?></div>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if($req['status'] === 'pending'): ?>
                      <form method="POST" style="display:flex; flex-direction:column; gap:0.3rem; max-width: 180px;">
                        <input type="hidden" name="request_id" value="<?= $req['id'] ?>"/>
                        <textarea class="pi-notes" name="admin_notes" placeholder="Optional admin notes..."></textarea>
                        <div style="display:flex; gap:0.4rem;">
                          <button type="submit" name="action" value="approve" class="btn-action" style="flex:1;">Approve</button>
                          <button type="submit" name="action" value="reject" class="btn-action btn-reject" style="flex:1;">Reject</button>
                        </div>
                      </form>
                    <?php else: ?>
                      <span style="color:#8888aa;">Processed</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <!-- Mobile Stacked Cards -->
        <div class="mobile-cards-wrap">
          <?php foreach($requests as $req): ?>
          <div class="box" style="margin-bottom:1rem; background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.05); padding:1rem; border-radius:12px;">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px;">
              <div>
                <div style="font-weight:700; color:#00e676; font-size:1.1rem;"><?= number_format($req['amount'], 2) ?> BDT</div>
                <div style="font-size:0.75rem; color:var(--muted);">≈ <?= number_format($req['amount'] * $exchange_rate, 1) ?> <?= htmlspecialchars($coin_name) ?></div>
              </div>
              <span class="status-badge status-<?= htmlspecialchars($req['status']) ?>"><?= htmlspecialchars($req['status']) ?></span>
            </div>
            
            <div style="margin-bottom:10px; font-size:0.9rem;">
              <div><strong style="color:var(--muted);">Req ID:</strong> #<?= htmlspecialchars($req['id']) ?></div>
              <div><strong style="color:var(--muted);">Customer:</strong> <?= htmlspecialchars($req['user_name']) ?> (<?= htmlspecialchars($req['user_phone']) ?>)</div>
              <div><strong style="color:var(--muted);">Sender No:</strong> <?= htmlspecialchars($req['sender_number']) ?></div>
              <div><strong style="color:var(--muted);">TrxID:</strong> <span style="color:#fcb900;"><?= htmlspecialchars($req['transaction_id']) ?></span></div>
              <?php if($req['screenshot_url']): ?>
                <div style="margin-top:5px;"><a href="/uploads/deposits/<?= htmlspecialchars($req['screenshot_url']) ?>" target="_blank" style="color:#fcb900; text-decoration:none; font-weight:700; font-size:0.85rem;">View Screenshot ↗</a></div>
              <?php endif; ?>
            </div>

            <?php if($req['status'] === 'pending'): ?>
              <form method="POST" style="display:flex; flex-direction:column; gap:8px;">
                <input type="hidden" name="request_id" value="<?= $req['id'] ?>"/>
                <textarea class="pi-notes" name="admin_notes" placeholder="Optional admin notes..." style="width:100%;"></textarea>
                <div style="display:flex; gap:10px;">
                  <button type="submit" name="action" value="approve" class="btn-action" style="flex:1;">Approve</button>
                  <button type="submit" name="action" value="reject" class="btn-action btn-reject" style="flex:1;">Reject</button>
                </div>
              </form>
            <?php else: ?>
              <?php if($req['admin_notes']): ?>
                <div style="font-size:0.85rem; color:var(--muted);">Notes: <?= htmlspecialchars($req['admin_notes']) ?></div>
              <?php else: ?>
                <div style="font-size:0.85rem; color:var(--muted);">Processed</div>
              <?php endif; ?>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

</body>
</html>
