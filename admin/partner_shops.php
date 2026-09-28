<?php
// =========================================================================
// admin/partner_shops.php — Unified Partner Hub & Escrow Disputes
// =========================================================================
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php'); exit;
}
require_once __DIR__ . '/../config.php';

$msg = $err = '';

// Handle Status Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $partner_id = intval($_POST['partner_id'] ?? 0);
    $action = $_POST['action'];

    if ($partner_id > 0) {
        try {
            // Defensive column additions
            try { $pdo->exec("ALTER TABLE partners ADD COLUMN status VARCHAR(50) DEFAULT 'pending'"); } catch (Exception $e) {}
            try { $pdo->exec("ALTER TABLE partners ADD COLUMN is_hidden TINYINT(1) DEFAULT 0"); } catch (Exception $e) {}
            try { $pdo->exec("ALTER TABLE partners ADD COLUMN is_official TINYINT(1) DEFAULT 0"); } catch (Exception $e) {}
            try { $pdo->exec("ALTER TABLE partners ADD COLUMN registration_number VARCHAR(100) DEFAULT NULL"); } catch (Exception $e) {}
            try { $pdo->exec("ALTER TABLE partners ADD COLUMN user_id INT DEFAULT 0"); } catch (Exception $e) {}

            if ($action === 'approve' || $action === 'activate') {
                $pdo->prepare("UPDATE partners SET status = 'approved', is_hidden = 0 WHERE id = :id")->execute([':id' => $partner_id]);
                
                $stmt = $pdo->prepare("SELECT user_id, registration_number, business_name, phone FROM partners WHERE id = ?");
                $stmt->execute([$partner_id]);
                $p = $stmt->fetch();
                if ($p) {
                    if (empty($p['user_id']) || (int)$p['user_id'] === 0) {
                        if (!empty($p['phone'])) {
                            $uStmt = $pdo->prepare("SELECT id FROM users WHERE phone = ? LIMIT 1");
                            $uStmt->execute([$p['phone']]);
                            $foundUid = $uStmt->fetchColumn();
                            if ($foundUid) {
                                $pdo->prepare("UPDATE partners SET user_id = ? WHERE id = ?")->execute([$foundUid, $partner_id]);
                                $p['user_id'] = $foundUid;
                            }
                        }
                    }
                    if (empty($p['registration_number'])) {
                        $regNum = 'FS-SHOP-' . str_pad($p['user_id'] ?: $partner_id, 5, '0', STR_PAD_LEFT);
                        $pdo->prepare("UPDATE partners SET registration_number = ? WHERE id = ?")->execute([$regNum, $partner_id]);
                    }
                    if (!empty($p['user_id'])) {
                        try {
                            $pdo->prepare("UPDATE users SET role = 'partner', is_active = 1 WHERE id = ?")->execute([$p['user_id']]);
                            $pdo->prepare("INSERT INTO user_notifications (user_id, title, message, type) VALUES (?, ?, ?, 'partner')")
                                ->execute([$p['user_id'], 'Shop Approved! 🎉', 'Congratulations! Your shop "' . $p['business_name'] . '" has been approved and is now live on Fast Site Marketplace!', 'system']);
                        } catch (Exception $eNotif) {}
                    }
                }
                $msg = 'Partner shop approved and activated successfully!';
            } elseif ($action === 'suspend') {
                $pdo->prepare("UPDATE partners SET status = 'suspended' WHERE id = :id")->execute([':id' => $partner_id]);
                $msg = 'Partner shop suspended!';
            } elseif ($action === 'make_official') {
                $pdo->prepare("UPDATE partners SET is_official = 1 WHERE id = :id")->execute([':id' => $partner_id]);
                $msg = 'Shop marked as OFFICIAL!';
            } elseif ($action === 'remove_official') {
                $pdo->prepare("UPDATE partners SET is_official = 0 WHERE id = :id")->execute([':id' => $partner_id]);
                $msg = 'OFFICIAL tag removed!';
            } elseif ($action === 'hide') {
                $pdo->prepare("UPDATE partners SET is_hidden = 1 WHERE id = :id")->execute([':id' => $partner_id]);
                $msg = 'Shop hidden from marketplace.';
            } elseif ($action === 'unhide') {
                $pdo->prepare("UPDATE partners SET is_hidden = 0 WHERE id = :id")->execute([':id' => $partner_id]);
                $msg = 'Shop visible on marketplace.';
            } elseif ($action === 'approve_request') {
                $req_id = $partner_id;
                $stmt = $pdo->prepare("SELECT * FROM partner_requests WHERE id = ?");
                $stmt->execute([$req_id]);
                $r = $stmt->fetch();
                $pdo->prepare("UPDATE partner_requests SET status = 'approved' WHERE id = ?")->execute([$req_id]);
                if ($r && !empty($r['user_id'])) {
                    $uId = (int)$r['user_id'];
                    $bName = !empty($r['business_name']) ? $r['business_name'] : 'Fast Site Partner Shop';
                    $pdo->prepare("UPDATE users SET role = 'partner', is_active = 1 WHERE id = ?")->execute([$uId]);
                    
                    // Check if partner row exists or create if missing
                    $chkP = $pdo->prepare("SELECT id FROM partners WHERE user_id = ? OR phone = (SELECT phone FROM users WHERE id = ?)");
                    $chkP->execute([$uId, $uId]);
                    $existingPartnerId = $chkP->fetchColumn();

                    if ($existingPartnerId) {
                        $pdo->prepare("UPDATE partners SET status = 'approved', is_hidden = 0, user_id = ? WHERE id = ?")->execute([$uId, $existingPartnerId]);
                    } else {
                        $shopSlug = strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '', str_replace(' ', '_', $bName)));
                        $regNum = 'FS-SHOP-' . str_pad($uId, 5, '0', STR_PAD_LEFT);
                        $uPhoneStmt = $pdo->prepare("SELECT phone, name FROM users WHERE id = ?");
                        $uPhoneStmt->execute([$uId]);
                        $uData = $uPhoneStmt->fetch();
                        $uPhone = $uData['phone'] ?? '';
                        $uOwner = $uData['name'] ?? '';

                        $pdo->prepare("INSERT INTO partners (user_id, business_name, owner_name, shop_slug, phone, registration_number, status, is_hidden) VALUES (?, ?, ?, ?, ?, ?, 'approved', 0)")
                            ->execute([$uId, $bName, $uOwner, $shopSlug, $uPhone, $regNum]);
                    }

                    try {
                        $pdo->prepare("INSERT INTO user_notifications (user_id, title, message, type) VALUES (?, ?, ?, 'partner')")
                            ->execute([$uId, 'Shop Request Approved! 🎉', 'Your partner shop application "' . $bName . '" has been approved and is now live on Fast Site Marketplace!', 'system']);
                    } catch (Exception $eNotif) {}
                }
                $msg = 'Partner application approved and shop activated successfully!';
            } elseif ($action === 'reject_request') {
                $req_id = $partner_id;
                $pdo->prepare("UPDATE partner_requests SET status = 'rejected' WHERE id = ?")->execute([$req_id]);
                $msg = 'Partner application rejected.';
            }
        } catch (Exception $e) {
            $err = 'Error updating shop: ' . $e->getMessage();
        }
    }
}

// Fetch Partner Shops
$partners = $pdo->query("SELECT * FROM partners ORDER BY created_at DESC")->fetchAll();

// Fetch Disputes
$disputes = [];
try {
    $disputes = $pdo->query("
        SELECT d.*, po.total_coins AS total_amount, po.partner_id, p.business_name AS shop_name, u.name AS customer_name
        FROM partner_disputes d
        JOIN partner_orders po ON d.order_id = po.id
        JOIN partners p ON po.partner_id = p.id
        JOIN users u ON po.customer_id = u.id
        ORDER BY d.created_at DESC
    ")->fetchAll();
} catch (Exception $e) {}

// Fetch Partner Payouts
$partner_payouts = [];
try {
    $partner_payouts = $pdo->query("
        SELECT w.*, p.business_name, p.owner_name, p.phone
        FROM partner_withdrawals w
        JOIN partners p ON w.partner_id = p.id
        ORDER BY w.created_at DESC
    ")->fetchAll();
} catch (Exception $e) {}

// Fetch Partner Requests (Pending / Unapproved)
$partner_requests = [];
try {
    $partner_requests = $pdo->query("SELECT * FROM partner_requests WHERE status = 'pending' ORDER BY created_at DESC")->fetchAll();
    $pending_shops = $pdo->query("
        SELECT p.*, u.name as user_name, u.phone as user_phone, u.email as user_email
        FROM partners p
        LEFT JOIN users u ON p.user_id = u.id
        WHERE p.status = 'pending'
        ORDER BY p.created_at DESC
    ")->fetchAll();
} catch (Exception $e) {}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Unified Partner Hub — Fast Site Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Oswald:wght@600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="/assets/css/admin.css?v=<?= time() ?>">
</head>
<body>
<?php include 'nav.php'; ?>

<div class="dashboard-container">

  <!-- Header Admin Hero -->
  <div class="admin-hero">
    <h2 class="admin-hero-title">🏪 SHOPS & ESCROW HUB</h2>
    <p class="admin-hero-subtitle">Manage registered storefronts, shop requests, partner payouts & 1-click escrow disputes</p>
    
    <div class="tabs-nav">
      <button class="tab-btn active" id="btn-shops" onclick="switchTab(event, 'tab-shops')">🛒 Shop Registrations (<?= count($partners) ?>)</button>
      <button class="tab-btn" id="btn-requests" onclick="switchTab(event, 'tab-requests')">📝 Shop Requests (<?= count($partner_requests ?? []) ?>)</button>
      <button class="tab-btn" id="btn-payouts" onclick="switchTab(event, 'tab-payouts')">💰 Payout Requests (<?= count($partner_payouts) ?>)</button>
      <button class="tab-btn" id="btn-disputes" onclick="switchTab(event, 'tab-disputes')">⚖️ Escrow Disputes (<?= count($disputes) ?>)</button>
    </div>
  </div>

  <?php if($msg): ?><div style="background:rgba(16,185,129,0.15); border:1px solid #10b981; color:#10b981; padding:0.8rem 1.2rem; border-radius:12px; margin-bottom:1rem; font-weight:700;">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>

  <!-- TAB 1: PARTNER SHOPS -->
  <div id="tab-shops" class="tab-content active">
    <div class="desktop-table-wrap">
      <table>
        <thead>
          <tr>
            <th>Logo / Business</th>
            <th>Owner Details</th>
            <th>Payout Profile</th>
            <th>Orders & Rating</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($partners as $p): ?>
          <tr>
            <td>
              <div style="display:flex; align-items:center; gap:0.6rem;">
                <img src="<?= htmlspecialchars(resolveShopMedia($p, 'avatar')) ?>" alt="Logo" style="width:40px; height:40px; border-radius:8px; object-fit:cover; border:1px solid var(--border);" onerror="this.onerror=null; this.src='/assets/images/logo.png';"/>
                <div>
                  <strong style="color:#fff; font-size:0.95rem; display:block;"><?= htmlspecialchars($p['business_name']) ?></strong>
                  <?php if ($p['is_official']): ?>
                    <span style="background:rgba(252,185,0,0.15); color:var(--gold); border:1px solid rgba(252,185,0,0.3); font-size:0.65rem; font-weight:800; padding:1px 5px; border-radius:4px;">✓ OFFICIAL</span>
                  <?php endif; ?>
                </div>
              </div>
            </td>
            <td>
              <strong style="color:#fff;"><?= htmlspecialchars($p['owner_name']) ?></strong><br/>
              <span style="font-size:0.78rem; color:var(--muted);">📞 <?= htmlspecialchars($p['phone']) ?></span>
            </td>
            <td>
              <span style="color:#10b981; font-weight:700;"><?= strtoupper(htmlspecialchars($p['payout_method'] ?? 'bKash')) ?></span><br/>
              <span style="font-size:0.75rem; color:var(--muted);"><?= htmlspecialchars($p['payout_account'] ?? 'N/A') ?></span>
            </td>
            <td>
              <span style="color:var(--gold); font-weight:800;">⭐ <?= $p['rating'] ?? '5.0' ?></span>
              <span style="font-size:0.75rem; color:var(--muted); display:block;"><?= $p['total_orders'] ?? 0 ?> Orders</span>
            </td>
            <td>
              <span class="status-capsule" style="background:<?= $p['status']==='approved'?'rgba(16,185,129,0.15)':'rgba(239,68,68,0.15)' ?>; color:<?= $p['status']==='approved'?'#10b981':'#ef4444' ?>;">
                <?= ucfirst($p['status']) ?>
              </span>
            </td>
            <td>
              <div style="display:flex; flex-wrap:wrap; gap:0.3rem;">
                <a href="shop_edit.php?id=<?= $p['id'] ?>" class="btn-sm" style="background:rgba(252,185,0,0.15); color:var(--gold); border:1px solid rgba(252,185,0,0.3); text-decoration:none; padding:0.3rem 0.6rem; font-size:0.75rem; border-radius:6px;">👁️ See Shop & Edit</a>
                <?php if ($p['status'] === 'approved'): ?>
                  <form method="POST" style="display:inline;">
                    <input type="hidden" name="partner_id" value="<?= $p['id'] ?>"/>
                    <input type="hidden" name="action" value="suspend"/>
                    <button type="submit" class="btn-sm" style="background:rgba(239,68,68,0.2); color:#ef4444; border:1px solid rgba(239,68,68,0.3); padding:0.3rem 0.6rem; font-size:0.75rem; border-radius:6px; cursor:pointer;" onclick="return confirm('Suspend shop <?= htmlspecialchars(addslashes($p['business_name'])) ?>?');">Suspend</button>
                  </form>
                <?php else: ?>
                  <form method="POST" style="display:inline;">
                    <input type="hidden" name="partner_id" value="<?= $p['id'] ?>"/>
                    <input type="hidden" name="action" value="approve"/>
                    <button type="submit" class="btn-sm" style="background:linear-gradient(135deg, #10b981, #059669); color:#000; font-weight:800; border:none; padding:0.3rem 0.7rem; border-radius:6px; font-size:0.75rem; cursor:pointer;">✓ Approve Shop</button>
                  </form>
                <?php endif; ?>

                <?php if (empty($p['is_official'])): ?>
                  <form method="POST" style="display:inline;">
                    <input type="hidden" name="partner_id" value="<?= $p['id'] ?>"/>
                    <input type="hidden" name="action" value="make_official"/>
                    <button type="submit" class="btn-sm" style="background:rgba(99,102,241,0.2); color:#818cf8; border:1px solid rgba(99,102,241,0.3); padding:0.3rem 0.6rem; font-size:0.75rem; border-radius:6px; cursor:pointer;">Make Official</button>
                  </form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- TAB 2: SHOP REQUESTS -->
  <div id="tab-requests" class="tab-content">
    <div class="overview-card">
      <h3 style="color:var(--gold); margin-bottom:1rem; font-size:1.15rem; font-weight:800;">📝 Pending Shop Applications & Registrations</h3>
      <?php if(empty($partner_requests) && empty($pending_shops)): ?>
        <p style="color:var(--muted); font-size:0.85rem;">No pending shop applications awaiting review.</p>
      <?php else: ?>
        <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(300px, 1fr)); gap:1rem;">
          
          <!-- Old Partner Requests Table format converted to Cards -->
          <?php foreach($partner_requests as $req): ?>
          <div style="background:var(--surface); border:1px solid rgba(255,255,255,0.08); border-radius:12px; padding:1.2rem; display:flex; flex-direction:column; gap:0.8rem;">
            <div style="display:flex; justify-content:space-between; align-items:flex-start;">
              <div>
                <strong style="color:#fff; font-size:1.1rem; display:block;"><?= htmlspecialchars($req['business_name'] ?? 'New Shop') ?></strong>
                <span style="font-size:0.85rem; color:var(--muted);"><?= htmlspecialchars($req['owner_name'] ?? '') ?></span>
              </div>
              <span style="background:rgba(252,185,0,0.15); color:var(--gold); padding:3px 8px; border-radius:6px; font-size:0.7rem; font-weight:800;">REQUEST</span>
            </div>
            <div>
              <div style="color:#10b981; font-weight:700; font-size:0.9rem;">📞 <?= htmlspecialchars($req['phone'] ?? '') ?></div>
              <div style="font-size:0.8rem; color:var(--muted);"><?= htmlspecialchars($req['email'] ?? 'No Email') ?></div>
            </div>
            <div style="font-size:0.75rem; color:var(--muted);">Applied: <?= date('d M Y', strtotime($req['created_at'])) ?></div>
            <form method="POST" style="display:flex; gap:0.5rem; margin-top:auto;">
              <input type="hidden" name="partner_id" value="<?= $req['id'] ?>"/>
              <button type="submit" name="action" value="approve_request" class="btn-sm" onclick="return confirm('Approve this shop request?')" style="flex:1; background:linear-gradient(135deg, #00e676, #00bfa5); color:#000; font-weight:800; border:none; padding:0.6rem; border-radius:8px; cursor:pointer;">✅ Approve</button>
              <button type="submit" name="action" value="reject_request" class="btn-sm" onclick="return confirm('Reject this shop request?')" style="flex:1; background:rgba(255,82,82,0.15); color:#ff5252; border:1px solid rgba(255,82,82,0.3); font-weight:700; padding:0.6rem; border-radius:8px; cursor:pointer;">❌ Reject</button>
            </form>
          </div>
          <?php endforeach; ?>

          <!-- Native Pending Partners Format -->
          <?php foreach($pending_shops as $ps): ?>
          <div style="background:var(--surface); border:1px solid rgba(255,255,255,0.08); border-radius:12px; padding:1.2rem; display:flex; flex-direction:column; gap:0.8rem;">
            <div style="display:flex; align-items:center; gap:0.8rem;">
              <img src="<?= htmlspecialchars(resolveShopMedia($ps, 'avatar')) ?>" style="width:48px; height:48px; border-radius:10px; object-fit:cover; border:1px solid var(--border);" onerror="this.onerror=null; this.src='/assets/images/logo.png';"/>
              <div>
                <strong style="color:#fff; font-size:1.1rem; display:block;"><?= htmlspecialchars($ps['business_name']) ?></strong>
                <span style="font-size:0.85rem; color:var(--muted);"><?= htmlspecialchars($ps['owner_name'] ?: $ps['user_name']) ?></span>
              </div>
            </div>
            <div>
              <div style="color:#10b981; font-weight:700; font-size:0.9rem;">📞 <?= htmlspecialchars($ps['phone'] ?: $ps['user_phone']) ?></div>
              <div style="font-size:0.8rem; color:var(--muted);"><?= htmlspecialchars($ps['user_email'] ?? 'No Email') ?></div>
            </div>
            <div style="font-size:0.75rem; color:var(--muted);">Registered: <?= date('d M Y', strtotime($ps['created_at'])) ?></div>
            <form method="POST" style="display:flex; gap:0.5rem; margin-top:auto;">
              <input type="hidden" name="partner_id" value="<?= $ps['id'] ?>"/>
              <button type="submit" name="action" value="approve" class="btn-sm" onclick="return confirm('Approve this shop?')" style="flex:1; background:linear-gradient(135deg, #00e676, #00bfa5); color:#000; font-weight:800; border:none; padding:0.6rem; border-radius:8px; cursor:pointer;">✅ Approve Shop</button>
              <button type="submit" name="action" value="suspend" class="btn-sm" onclick="return confirm('Reject this shop?')" style="flex:1; background:rgba(255,82,82,0.15); color:#ff5252; border:1px solid rgba(255,82,82,0.3); font-weight:700; padding:0.6rem; border-radius:8px; cursor:pointer;">❌ Reject Shop</button>
            </form>
          </div>
          <?php endforeach; ?>

        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- TAB 3: PARTNER PAYOUTS -->
  <div id="tab-payouts" class="tab-content">
    <div class="overview-card">
      <h3>💰 Partner Payout Requests</h3>
      <?php if(empty($partner_payouts)): ?>
        <p style="color:var(--muted); font-size:0.85rem;">No active partner payout requests.</p>
      <?php else: ?>
        <div class="desktop-table-wrap">
          <table>
            <thead>
              <tr>
                <th>Partner Shop</th>
                <th>Amount</th>
                <th>Method</th>
                <th>Account</th>
                <th>Date</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($partner_payouts as $pw): ?>
              <tr>
                <td style="font-weight:700; color:#fff;"><?= htmlspecialchars($pw['business_name']) ?></td>
                <td style="color:var(--gold); font-weight:800;">৳<?= number_format($pw['amount'], 2) ?></td>
                <td><?= strtoupper(htmlspecialchars($pw['method'] ?? 'bKash')) ?></td>
                <td><?= htmlspecialchars($pw['account_number'] ?? 'N/A') ?></td>
                <td style="font-size:0.75rem; color:var(--muted);"><?= date('d M Y', strtotime($pw['created_at'])) ?></td>
                <td><span class="status-capsule"><?= ucfirst($pw['status']) ?></span></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- TAB 3: ESCROW DISPUTES -->
  <div id="tab-disputes" class="tab-content">
    <div class="overview-card" style="border:1px solid rgba(239,68,68,0.3); box-shadow:0 0 20px rgba(239,68,68,0.1);">
      <h3 style="color:var(--red);">⚖️ Escrow Disputes Adjudication</h3>
      <?php if(empty($disputes)): ?>
        <p style="color:var(--muted); font-size:0.85rem;">No active disputes in the queue. All marketplace orders are operating smoothly!</p>
      <?php else: ?>
        <div class="desktop-table-wrap">
          <table>
            <thead>
              <tr>
                <th>Order ID</th>
                <th>Shop Name</th>
                <th>Customer</th>
                <th>Dispute Reason</th>
                <th>Amount</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($disputes as $d): ?>
              <tr>
                <td><span class="id-badge">ORDER #<?= $d['order_id'] ?></span></td>
                <td style="font-weight:700; color:#fff;"><?= htmlspecialchars($d['shop_name']) ?></td>
                <td><?= htmlspecialchars($d['customer_name']) ?></td>
                <td style="font-size:0.8rem; color:var(--muted);"><?= htmlspecialchars($d['reason'] ?? 'Item not received') ?></td>
                <td style="color:var(--gold); font-weight:800;">৳<?= number_format($d['total_amount'], 2) ?></td>
                <td>
                  <a href="partner_disputes.php?id=<?= $d['id'] ?>" class="btn-sm" style="text-decoration:none; padding:0.4rem 0.8rem; font-size:0.78rem;">Adjudicate →</a>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
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
  if (target) target.classList.add('active');
  if (evt && evt.currentTarget) evt.currentTarget.classList.add('active');
  window.location.hash = tabId;
}

window.addEventListener('DOMContentLoaded', () => {
  if (window.location.hash) {
    const hash = window.location.hash.substring(1);
    const btn = document.getElementById('btn-' + hash.replace('tab-', ''));
    if (btn) btn.click();
  }
});
</script>
</body>
</html>
