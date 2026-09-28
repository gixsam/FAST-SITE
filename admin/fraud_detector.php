<?php
session_start();
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Handle Quick Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $user_id = (int)($_POST['user_id'] ?? 0);
    
    if ($user_id && $action === 'suspend') {
        try {
            $pdo->prepare("UPDATE users SET role = 'suspended' WHERE id = ?")->execute([$user_id]);
        } catch(Exception $e) {}
    }
    header('Location: fraud_detector.php');
    exit;
}

// 1. Duplicate Payout Accounts Alert
$stmt = $pdo->query("
    SELECT payout_account, COUNT(DISTINCT user_id) as user_count, GROUP_CONCAT(DISTINCT user_id) as user_ids 
    FROM user_withdrawals 
    GROUP BY payout_account 
    HAVING user_count > 1
");
$duplicate_payouts = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

// 2. Self-Referral Loop Flag
// Finds users whose referral code was used by accounts with matching phone prefixes (first 7 digits like 0171234)
$stmt = $pdo->query("
    SELECT u1.id as referrer_id, u1.name as referrer_name, u1.phone as referrer_phone, 
           COUNT(u2.id) as fake_refs, GROUP_CONCAT(u2.id) as fake_user_ids
    FROM users u1 
    JOIN users u2 ON u1.ref_code = u2.referred_by 
    WHERE SUBSTR(u1.phone, 1, 7) = SUBSTR(u2.phone, 1, 7) 
    GROUP BY u1.id 
    HAVING fake_refs > 0
");
$self_referrals = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

// 3. High-Dispute Shop Alert (>20% dispute rate)
$stmt = $pdo->query("
    SELECT p.id, p.business_name, 
           COUNT(o.id) as total_orders, 
           SUM(CASE WHEN o.status IN ('disputed', 'cancelled', 'refunded') THEN 1 ELSE 0 END) as disputed_orders 
    FROM partner_orders o 
    JOIN partners p ON o.partner_id = p.id 
    GROUP BY p.id 
    HAVING total_orders > 4 AND (disputed_orders / total_orders) > 0.20
");
$high_dispute_shops = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Fraud & Security Monitor - Admin</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Oswald:wght@500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<div class="admin-layout">
  <?php include 'nav.php'; ?>
  <div class="admin-content">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 2rem;">
      <h1 style="margin:0; font-family:'Oswald',sans-serif; color:#ff5252;">🛡️ FRAUD & SECURITY MONITOR</h1>
      <span style="background:rgba(255,82,82,0.1); color:#ff5252; padding:0.5rem 1rem; border-radius:8px; font-weight:700;">Live Threat Intelligence</span>
    </div>

    <!-- Duplicate Payouts -->
    <div class="admin-card" style="border-left: 4px solid #ff5252;">
      <h3 style="margin-top:0; color:#ff5252;">💳 Duplicate Payout Accounts</h3>
      <p style="color:#94a3b8; font-size:0.9rem; margin-bottom:1rem;">Flags bKash/Nagad numbers used across multiple different user accounts (possible multi-accounting).</p>
      <?php if (empty($duplicate_payouts)): ?>
        <p style="color:#00e676; font-weight:700;">✅ No duplicate payout accounts detected.</p>
      <?php else: ?>
        <table class="admin-table">
          <thead><tr><th>Payout Account</th><th>Linked Users Count</th><th>User IDs</th><th>Risk Level</th><th>Actions</th></tr></thead>
          <tbody>
            <?php foreach ($duplicate_payouts as $item): ?>
              <tr>
                <td style="font-weight:800; color:#fff;"><?= htmlspecialchars($item['payout_account']) ?></td>
                <td style="color:#ff5252; font-weight:700;"><?= $item['user_count'] ?> Accounts</td>
                <td style="color:#fcb900; font-family:monospace;"><?= htmlspecialchars($item['user_ids']) ?></td>
                <td><span style="background:rgba(255,82,82,0.2); color:#ff5252; padding:4px 8px; border-radius:6px; font-size:0.75rem; font-weight:700;">🚨 HIGH RISK</span></td>
                <td>
                  <form method="POST" style="display:inline-block;">
                    <input type="hidden" name="action" value="suspend">
                    <input type="hidden" name="user_id" value="<?= explode(',', $item['user_ids'])[0] ?>">
                    <button class="btn" style="background:#ff5252; padding:5px 10px; font-size:0.75rem;">⛔ Suspend (Primary)</button>
                  </form>
                  <button class="btn" style="background:rgba(255,255,255,0.1); padding:5px 10px; font-size:0.75rem;">Dismiss</button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

    <!-- Self-Referral Loops -->
    <div class="admin-card" style="border-left: 4px solid #fcb900; margin-top:2rem;">
      <h3 style="margin-top:0; color:#fcb900;">🔄 Self-Referral Loop Flag</h3>
      <p style="color:#94a3b8; font-size:0.9rem; margin-bottom:1rem;">Flags users referring accounts with identical phone prefixes (likely self-referral farming).</p>
      <?php if (empty($self_referrals)): ?>
        <p style="color:#00e676; font-weight:700;">✅ No self-referral loops detected.</p>
      <?php else: ?>
        <table class="admin-table">
          <thead><tr><th>Referrer</th><th>Phone</th><th>Fake Referrals</th><th>Flagged User IDs</th><th>Risk Level</th><th>Actions</th></tr></thead>
          <tbody>
            <?php foreach ($self_referrals as $item): ?>
              <tr>
                <td style="font-weight:700; color:#fff;"><?= htmlspecialchars($item['referrer_name']) ?> (ID: <?= $item['referrer_id'] ?>)</td>
                <td style="color:#94a3b8;"><?= htmlspecialchars($item['referrer_phone']) ?></td>
                <td style="color:#ff5252; font-weight:700;"><?= $item['fake_refs'] ?> Farming Accounts</td>
                <td style="color:#fcb900; font-family:monospace;"><?= htmlspecialchars($item['fake_user_ids']) ?></td>
                <td><span style="background:rgba(252,185,0,0.2); color:#fcb900; padding:4px 8px; border-radius:6px; font-size:0.75rem; font-weight:700;">⚠️ CAUTION</span></td>
                <td>
                  <form method="POST" style="display:inline-block;">
                    <input type="hidden" name="action" value="suspend">
                    <input type="hidden" name="user_id" value="<?= $item['referrer_id'] ?>">
                    <button class="btn" style="background:#ff5252; padding:5px 10px; font-size:0.75rem;">⛔ Suspend Account</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

    <!-- High-Dispute Shops -->
    <div class="admin-card" style="border-left: 4px solid #00b0ff; margin-top:2rem;">
      <h3 style="margin-top:0; color:#00b0ff;">🏪 High-Dispute Shop Alert</h3>
      <p style="color:#94a3b8; font-size:0.9rem; margin-bottom:1rem;">Flags partner shops where the dispute/cancellation rate exceeds 20% of total orders.</p>
      <?php if (empty($high_dispute_shops)): ?>
        <p style="color:#00e676; font-weight:700;">✅ All shops maintain healthy order fulfillment rates.</p>
      <?php else: ?>
        <table class="admin-table">
          <thead><tr><th>Shop Name</th><th>Total Orders</th><th>Disputed/Cancelled</th><th>Dispute Rate</th><th>Risk Level</th><th>Actions</th></tr></thead>
          <tbody>
            <?php foreach ($high_dispute_shops as $item): 
              $rate = round(($item['disputed_orders'] / $item['total_orders']) * 100, 1);
            ?>
              <tr>
                <td style="font-weight:700; color:#fff;">🏪 <?= htmlspecialchars($item['business_name']) ?> (ID: <?= $item['id'] ?>)</td>
                <td style="color:#94a3b8; font-weight:700;"><?= $item['total_orders'] ?></td>
                <td style="color:#ff5252; font-weight:700;"><?= $item['disputed_orders'] ?></td>
                <td style="color:#fcb900; font-weight:800;"><?= $rate ?>%</td>
                <td><span style="background:rgba(255,82,82,0.2); color:#ff5252; padding:4px 8px; border-radius:6px; font-size:0.75rem; font-weight:700;">🚨 HIGH RISK</span></td>
                <td>
                  <button class="btn" style="background:rgba(255,255,255,0.1); padding:5px 10px; font-size:0.75rem;">Flag Shop</button>
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
