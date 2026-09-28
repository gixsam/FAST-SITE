<?php
// =========================================================================
// admin/partner_withdrawals.php  –  Manage Partner Cashouts
// =========================================================================
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php'); exit;
}
require_once __DIR__ . '/../config.php';

$msg = $err = '';
$coin_name = getPartnerSetting('coin_name', 'Fast Points');
$exchange_rate = floatval(getPartnerSetting('exchange_rate', '1'));

// Handle Process / Reject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $request_id = intval($_POST['request_id'] ?? 0);
    $action     = $_POST['action'];
    $admin_note = trim($_POST['admin_notes'] ?? '');

    if ($request_id > 0) {
        // Fetch request details
        $stmt_req = $pdo->prepare("SELECT * FROM partner_withdrawal_requests WHERE id = :id LIMIT 1");
        $stmt_req->execute([':id' => $request_id]);
        $request = $stmt_req->fetch();

        if ($request && $request['status'] === 'pending') {
            if ($action === 'process') {
                $pdo->prepare("UPDATE partner_withdrawal_requests 
                    SET status = 'processed', admin_notes = :note, processed_at = :now 
                    WHERE id = :id")
                    ->execute([
                        ':note' => $admin_note ?: null,
                        ':now'  => date('Y-m-d H:i:s'),
                        ':id'   => $request_id
                    ]);
                $msg = 'Withdrawal processed and marked as paid!';
            } elseif ($action === 'reject') {
                $pdo->prepare("UPDATE partner_withdrawal_requests 
                    SET status = 'rejected', admin_notes = :note 
                    WHERE id = :id")
                    ->execute([
                        ':note' => $admin_note ?: null,
                        ':id'   => $request_id
                    ]);
                $msg = 'Withdrawal request rejected.';
            }
        } else {
            $err = 'Withdrawal request not found or already processed.';
        }
    }
}

// Fetch requests
$filter = $_GET['status'] ?? 'pending';
$where = "1=1";
if (in_array($filter, ['pending', 'processed', 'rejected'])) {
    $where = "wr.status = '$filter'";
}

$requests = $pdo->query("SELECT wr.*, p.business_name, p.owner_name, p.phone AS partner_phone, p.payout_method, p.payout_account 
    FROM partner_withdrawal_requests wr 
    JOIN partners p ON wr.partner_id = p.id 
    WHERE $where 
    ORDER BY wr.created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Partner Withdrawals — Fast Site Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>
<?php include 'nav.php'; ?>

<div class="wrap">
  <h1>💸 Partner Withdrawal Requests</h1>
  <p style="font-size:0.85rem; color:#8888aa; margin-bottom:1.5rem;">Review and process withdrawal requests from partner shop vendors.</p>

  <?php if($msg): ?><div class="alert-ok">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if($err): ?><div class="alert-err">❌ <?= htmlspecialchars($err) ?></div><?php endif; ?>

  <div class="tabs-bar">
    <a href="partner_withdrawals.php?status=pending" class="tab-link <?= $filter === 'pending' ? 'active' : '' ?>">Pending Payouts</a>
    <a href="partner_withdrawals.php?status=processed" class="tab-link <?= $filter === 'processed' ? 'active' : '' ?>">Processed / Paid</a>
    <a href="partner_withdrawals.php?status=rejected" class="tab-link <?= $filter === 'rejected' ? 'active' : '' ?>">Rejected</a>
  </div>

  <div class="panel">
    <div class="table-container">
      <?php if(empty($requests)): ?>
        <p style="color:#8888aa; font-size:0.85rem; text-align:center; padding:2rem 0;">No withdrawal requests found in this category.</p>
      <?php else: ?>
        <div class="desktop-table-wrap">
          <table>
            <thead>
              <tr>
                <th>Request ID</th>
                <th>Partner Shop</th>
                <th>Payout Address</th>
                <th>Amount (<?= htmlspecialchars($coin_name) ?>)</th>
                <th>BDT Value</th>
                <th>Processed Date</th>
                <th>Admin Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($requests as $req): ?>
                <tr>
                  <td>#<?= htmlspecialchars($req['id']) ?></td>
                  <td>
                    <div style="font-weight:700; color:#fff;"><?= htmlspecialchars($req['business_name']) ?></div>
                    <div style="font-size:0.75rem; color:#8888aa;">By <?= htmlspecialchars($req['owner_name']) ?> (<?= htmlspecialchars($req['partner_phone']) ?>)</div>
                  </td>
                  <td>
                    <div style="font-size:0.82rem; font-weight:600; color:#fcb900;"><?= strtoupper(htmlspecialchars($req['account_info'] ?: $req['payout_method'] . ': ' . $req['payout_account'])) ?></div>
                  </td>
                  <td style="font-weight:700;"><?= number_format($req['amount'], 1) ?></td>
                  <td style="color:#00e676; font-weight:700;"><?= number_format($req['amount'] * $exchange_rate, 2) ?> BDT</td>
                  <td>
                    <span class="status-badge status-<?= htmlspecialchars($req['status']) ?>">
                      <?= htmlspecialchars($req['status']) ?>
                    </span>
                    <?php if($req['processed_at']): ?>
                      <div style="font-size:0.72rem; color:#8888aa; margin-top:0.25rem;"><?= date('d M Y, h:i A', strtotime($req['processed_at'])) ?></div>
                    <?php endif; ?>
                    <?php if($req['admin_notes']): ?>
                      <div style="font-size:0.75rem; color:#8888aa; margin-top:0.3rem;">Notes: <?= htmlspecialchars($req['admin_notes']) ?></div>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if($req['status'] === 'pending'): ?>
                      <form method="POST" style="display:flex; flex-direction:column; gap:0.3rem; max-width: 180px;">
                        <input type="hidden" name="request_id" value="<?= $req['id'] ?>"/>
                        <textarea class="pi-notes" name="admin_notes" placeholder="Payout notes/transaction ID..."></textarea>
                        <div style="display:flex; gap:0.4rem;">
                          <button type="submit" name="action" value="process" class="btn-action" style="flex:1;">Confirm Paid</button>
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
                <div style="font-weight:700; color:#00e676; font-size:1.1rem;"><?= number_format($req['amount'] * $exchange_rate, 2) ?> BDT</div>
                <div style="font-size:0.75rem; color:var(--muted);"><?= number_format($req['amount'], 1) ?> <?= htmlspecialchars($coin_name) ?></div>
              </div>
              <span class="status-badge status-<?= htmlspecialchars($req['status']) ?>"><?= htmlspecialchars($req['status']) ?></span>
            </div>
            
            <div style="margin-bottom:10px; font-size:0.9rem;">
              <div><strong style="color:var(--muted);">Req ID:</strong> #<?= htmlspecialchars($req['id']) ?></div>
              <div><strong style="color:var(--muted);">Shop:</strong> <?= htmlspecialchars($req['business_name']) ?></div>
              <div><strong style="color:var(--muted);">Owner:</strong> <?= htmlspecialchars($req['owner_name']) ?> (<?= htmlspecialchars($req['partner_phone']) ?>)</div>
            </div>

            <div style="margin-bottom:10px; font-size:0.85rem; background:rgba(0,0,0,0.3); border:1px solid var(--border); padding:8px; border-radius:8px;">
              <strong style="color:var(--muted); display:block; margin-bottom:4px;">Payout To:</strong>
              <div style="color:#fcb900; font-weight:600;"><?= strtoupper(htmlspecialchars($req['account_info'] ?: $req['payout_method'] . ': ' . $req['payout_account'])) ?></div>
            </div>

            <?php if($req['status'] === 'pending'): ?>
              <form method="POST" style="display:flex; flex-direction:column; gap:8px;">
                <input type="hidden" name="request_id" value="<?= $req['id'] ?>"/>
                <textarea class="pi-notes" name="admin_notes" placeholder="Payout notes/transaction ID..." style="width:100%;"></textarea>
                <div style="display:flex; gap:10px;">
                  <button type="submit" name="action" value="process" class="btn-action" style="flex:1;">Confirm Paid</button>
                  <button type="submit" name="action" value="reject" class="btn-action btn-reject" style="flex:1;">Reject</button>
                </div>
              </form>
            <?php else: ?>
              <?php if($req['processed_at']): ?>
                <div style="font-size:0.85rem; color:var(--muted); margin-bottom:4px;">Processed: <?= date('d M Y, h:i A', strtotime($req['processed_at'])) ?></div>
              <?php endif; ?>
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
