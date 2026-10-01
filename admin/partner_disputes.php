<?php
// =========================================================================
// admin/partner_disputes.php  –  Admin Disputes Adjudication Board
// =========================================================================
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php'); exit;
}
require_once __DIR__ . '/../config.php';

$msg = $err = '';
$coin_name = getPartnerSetting('coin_name', 'Fast Points');

// Handle Adjudication Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $dispute_id = intval($_POST['dispute_id'] ?? 0);
    $action = $_POST['action'];

    if ($dispute_id > 0) {
        try {
            $pdo->beginTransaction();

            // Fetch dispute detail
            $stmt_disp = $pdo->prepare("SELECT d.*, o.customer_id, o.partner_id, o.total_coins 
                FROM partner_disputes d 
                JOIN partner_orders o ON d.order_id = o.id 
                WHERE d.id = :id LIMIT 1");
            $stmt_disp->execute([':id' => $dispute_id]);
            $dispute = $stmt_disp->fetch();

            if ($dispute && $dispute['admin_decision'] === 'pending') {
                if ($action === 'refund') {
                    // 1. Update dispute status
                    $pdo->prepare("UPDATE partner_disputes 
                        SET admin_decision = 'refund_customer', resolved_at = :now 
                        WHERE id = :id")
                        ->execute([
                            ':now' => date('Y-m-d H:i:s'),
                            ':id'  => $dispute_id
                        ]);

                    // 2. Update order status to cancelled
                    $pdo->prepare("UPDATE partner_orders SET status = 'cancelled', cancelled_at = :now WHERE id = :oid")
                        ->execute([
                            ':now' => date('Y-m-d H:i:s'),
                            ':oid' => $dispute['order_id']
                        ]);

                    // 3. Refund coins to customer's wallet balance
                    $pdo->prepare("UPDATE users SET coins_balance = coins_balance + :coins WHERE id = :uid")
                        ->execute([
                            ':coins' => $dispute['total_coins'],
                            ':uid'   => $dispute['customer_id']
                        ]);

                    // 4. Log transaction
                    $pdo->prepare("INSERT INTO coin_transactions 
                        (user_id, type, amount, reference, status) 
                        VALUES (:uid, 'refund', :coins, :ref, 'completed')")
                        ->execute([
                            ':uid'   => $dispute['customer_id'],
                            ':coins' => $dispute['total_coins'],
                            ':ref'   => "Dispute Refund for Order #" . $dispute['order_id']
                        ]);

                    $msg = 'Claim resolved! Funds refunded to customer wallet.';
                } elseif ($action === 'release') {
                    // 1. Update dispute status
                    $pdo->prepare("UPDATE partner_disputes 
                        SET admin_decision = 'release_to_partner', resolved_at = :now 
                        WHERE id = :id")
                        ->execute([
                            ':now' => date('Y-m-d H:i:s'),
                            ':id'  => $dispute_id
                        ]);

                    // 2. Update order status to completed
                    $pdo->prepare("UPDATE partner_orders SET status = 'completed', customer_confirmed_at = :now WHERE id = :oid")
                        ->execute([
                            ':now' => date('Y-m-d H:i:s'),
                            ':oid' => $dispute['order_id']
                        ]);

                    // 3. Release coins to partner total_earned
                    $pdo->prepare("UPDATE partners 
                        SET total_earned = total_earned + :coins, total_orders = total_orders + 1 
                        WHERE id = :pid")
                        ->execute([
                            ':coins' => $dispute['total_coins'],
                            ':pid'   => $dispute['partner_id']
                        ]);

                    // 4. Log transaction
                    $pdo->prepare("INSERT INTO coin_transactions 
                        (user_id, type, amount, reference, status) 
                        VALUES (:uid, 'release', :coins, :ref, 'completed')")
                        ->execute([
                            ':uid'   => $dispute['customer_id'],
                            ':coins' => $dispute['total_coins'],
                            ':ref'   => "Claim Released for Order #" . $dispute['order_id']
                        ]);

                    $msg = 'Claim resolved! Funds released to partner shop total earnings.';
                }

                $pdo->commit();
            } else {
                $pdo->rollBack();
                $err = 'Claim not found or already resolved.';
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $err = 'Database Error: ' . $e->getMessage();
        }
    }
}

// Fetch disputes
$filter = $_GET['status'] ?? 'pending';
$where = "1=1";
if (in_array($filter, ['pending', 'refund_customer', 'release_to_partner'])) {
    $where = "d.admin_decision = '$filter'";
}

$disputes = $pdo->query("SELECT d.*, o.total_coins, o.created_at AS order_date, p.title AS product_title, u.name AS customer_name, pt.business_name AS partner_name 
    FROM partner_disputes d 
    JOIN partner_orders o ON d.order_id = o.id 
    JOIN partner_products p ON o.product_id = p.id 
    JOIN users u ON o.customer_id = u.id 
    JOIN partners pt ON o.partner_id = pt.id 
    WHERE $where 
    ORDER BY d.created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Refund &amp; Claim Center — Fast Site Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>
<?php include 'nav.php'; ?>

<div class="wrap">
  <h1>⚖️ Refund &amp; Claim Center (রিটার্ন ও রিফান্ড সেন্টার)</h1>
  <p style="font-size:0.85rem; color:#8888aa; margin-bottom:1.5rem;">Review and resolve customer refund requests and return claims. 1-Click action to refund buyer or release payment to seller.</p>

  <?php if($msg): ?><div class="alert-ok">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if($err): ?><div class="alert-err">❌ <?= htmlspecialchars($err) ?></div><?php endif; ?>

  <div class="tabs-bar">
    <a href="partner_disputes.php?status=pending" class="tab-link <?= $filter === 'pending' ? 'active' : '' ?>">Pending Claims</a>
    <a href="partner_disputes.php?status=refund_customer" class="tab-link <?= $filter === 'refund_customer' ? 'active' : '' ?>">Refunded to Buyer</a>
    <a href="partner_disputes.php?status=release_to_partner" class="tab-link <?= $filter === 'release_to_partner' ? 'active' : '' ?>">Released to Seller</a>
  </div>

  <div class="panel">
    <div class="table-container">
      <?php if(empty($disputes)): ?>
        <p style="color:#8888aa; font-size:0.85rem; text-align:center; padding:2rem 0;">No active claims found in this category.</p>
      <?php else: ?>
        <div class="desktop-table-wrap">
          <table>
            <thead>
              <tr>
                <th>ID</th>
                <th>Order Details</th>
                <th>Contesting Parties</th>
                <th>Claim Details</th>
                <th>Evidence Provided</th>
                <th>Status</th>
                <th>Resolution Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($disputes as $d): ?>
                <tr>
                  <td>#<?= htmlspecialchars($d['id']) ?></td>
                  <td>
                    <div style="font-weight:700;">Order #<?= htmlspecialchars($d['order_id']) ?></div>
                    <div style="font-size:0.8rem; color:#fcb900; font-weight:700;"><?= number_format($d['total_coins'], 1) ?> <?= htmlspecialchars($coin_name) ?></div>
                    <div style="font-size:0.72rem; color:#8888aa;"><?= date('d M Y, h:i A', strtotime($d['order_date'])) ?></div>
                  </td>
                  <td>
                    <div style="font-size:0.82rem;">Cust: <strong><?= htmlspecialchars($d['customer_name']) ?></strong></div>
                    <div style="font-size:0.82rem; margin-top:0.25rem;">Shop: <strong style="color:#fcb900;"><?= htmlspecialchars($d['partner_name']) ?></strong></div>
                  </td>
                  <td>
                    <div style="font-size:0.8rem; font-weight:600; color:#ff6b6b; margin-bottom:0.25rem;">Reason:</div>
                    <div style="font-size:0.78rem; background:rgba(0,0,0,0.2); border:1px solid rgba(255,255,255,0.05); padding:0.4rem; border-radius:6px; max-width:250px;"><?= htmlspecialchars($d['reason'] ?: 'None.') ?></div>
                  </td>
                  <td>
                    <div style="font-size:0.75rem; display:flex; flex-direction:column; gap:0.25rem;">
                      <div>Cust: <?php if($d['evidence_customer']): ?><a href="/uploads/evidence/<?= htmlspecialchars($d['evidence_customer']) ?>" target="_blank" style="color:#fcb900; text-decoration:none; font-weight:700;">View File ↗</a><?php else: ?>No attachment<?php endif; ?></div>
                      <div>Shop: <?php if($d['evidence_partner']): ?><a href="/uploads/evidence/<?= htmlspecialchars($d['evidence_partner']) ?>" target="_blank" style="color:#fcb900; text-decoration:none; font-weight:700;">View File ↗</a><?php else: ?>No attachment<?php endif; ?></div>
                    </div>
                  </td>
                  <td>
                    <span class="status-badge status-<?= htmlspecialchars($d['admin_decision']) ?>">
                      <?= str_replace('_', ' ', htmlspecialchars($d['admin_decision'])) ?>
                    </span>
                    <?php if($d['resolved_at']): ?>
                      <div style="font-size:0.72rem; color:#8888aa; margin-top:0.25rem;"><?= date('d M Y', strtotime($d['resolved_at'])) ?></div>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if($d['admin_decision'] === 'pending'): ?>
                      <div style="display:flex; flex-direction:column; gap:0.4rem; min-width: 140px;">
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Confirm release of payment to partner seller?');">
                          <input type="hidden" name="dispute_id" value="<?= $d['id'] ?>"/>
                          <input type="hidden" name="action" value="release"/>
                          <button type="submit" class="btn-action" style="width:100%; background:linear-gradient(135deg, #10b981, #059669); color:#000; font-weight:800;">🟢 Pay Seller</button>
                        </form>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Confirm refund of payment to customer wallet?');">
                          <input type="hidden" name="dispute_id" value="<?= $d['id'] ?>"/>
                          <input type="hidden" name="action" value="refund"/>
                          <button type="submit" class="btn-action btn-refund" style="width:100%; background:linear-gradient(135deg, #ef4444, #dc2626); color:#fff; font-weight:800;">🔴 Refund Buyer</button>
                        </form>
                      </div>
                    <?php else: ?>
                      <span style="color:#8888aa;">Resolved</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <!-- Mobile Stacked Cards -->
        <div class="mobile-cards-wrap">
          <?php foreach($disputes as $d): ?>
          <div class="box" style="margin-bottom:1rem; background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.05); padding:1rem; border-radius:12px;">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px;">
              <div>
                <div style="font-weight:700; font-size:1.1rem; color:#fcb900;">Order #<?= htmlspecialchars($d['order_id']) ?></div>
                <div style="font-size:0.75rem; color:var(--muted);"><?= date('d M Y, h:i A', strtotime($d['order_date'])) ?></div>
              </div>
              <span class="status-badge status-<?= htmlspecialchars($d['admin_decision']) ?>"><?= str_replace('_', ' ', htmlspecialchars($d['admin_decision'])) ?></span>
            </div>
            
            <div style="margin-bottom:10px; font-size:0.9rem;">
              <div><strong style="color:var(--muted);">Claim ID:</strong> #<?= htmlspecialchars($d['id']) ?></div>
              <div><strong style="color:var(--muted);">Amount:</strong> <?= number_format($d['total_coins'], 1) ?> <?= htmlspecialchars($coin_name) ?></div>
              <div><strong style="color:var(--muted);">Cust:</strong> <?= htmlspecialchars($d['customer_name']) ?></div>
              <div><strong style="color:var(--muted);">Shop:</strong> <?= htmlspecialchars($d['partner_name']) ?></div>
            </div>

            <div style="margin-bottom:10px; font-size:0.85rem; background:rgba(0,0,0,0.3); border:1px solid var(--border); padding:8px; border-radius:8px;">
              <strong style="color:#ff6b6b; display:block; margin-bottom:4px;">Reason:</strong>
              <?= htmlspecialchars($d['reason'] ?: 'None.') ?>
            </div>

            <div style="margin-bottom:10px; font-size:0.85rem;">
              <strong style="color:var(--muted); display:block; margin-bottom:4px;">Evidence:</strong>
              <div style="display:flex; gap:15px;">
                <div>Cust: <?php if($d['evidence_customer']): ?><a href="/uploads/evidence/<?= htmlspecialchars($d['evidence_customer']) ?>" target="_blank" style="color:#fcb900; text-decoration:none;">View ↗</a><?php else: ?>None<?php endif; ?></div>
                <div>Shop: <?php if($d['evidence_partner']): ?><a href="/uploads/evidence/<?= htmlspecialchars($d['evidence_partner']) ?>" target="_blank" style="color:#fcb900; text-decoration:none;">View ↗</a><?php else: ?>None<?php endif; ?></div>
              </div>
            </div>

            <?php if($d['admin_decision'] === 'pending'): ?>
              <div style="display:flex; gap:10px; margin-top:15px;">
                <form method="POST" style="flex:1;" onsubmit="return confirm('Release payment to partner seller?');">
                  <input type="hidden" name="dispute_id" value="<?= $d['id'] ?>"/>
                  <input type="hidden" name="action" value="release"/>
                  <button type="submit" class="btn-action" style="width:100%; padding:10px; background:linear-gradient(135deg, #10b981, #059669); color:#000; font-weight:800;">🟢 Pay Seller</button>
                </form>
                <form method="POST" style="flex:1;" onsubmit="return confirm('Refund payment to customer?');">
                  <input type="hidden" name="dispute_id" value="<?= $d['id'] ?>"/>
                  <input type="hidden" name="action" value="refund"/>
                  <button type="submit" class="btn-action btn-refund" style="width:100%; padding:10px; background:linear-gradient(135deg, #ef4444, #dc2626); color:#fff; font-weight:800;">🔴 Refund Buyer</button>
                </form>
              </div>
            <?php else: ?>
              <div style="font-size:0.85rem; color:var(--muted);">
                Resolved on: <?= $d['resolved_at'] ? date('d M Y, h:i A', strtotime($d['resolved_at'])) : 'N/A' ?>
              </div>
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
