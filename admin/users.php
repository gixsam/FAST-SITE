<?php
// =========================================================================
// admin/users.php — Unified User Panel & Customer Management Hub + KYC Center
// =========================================================================
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php'); exit;
}
require_once __DIR__ . '/../config.php';

$msg = $err = '';

// ── 1. Handle Delete User ──
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $msg = 'User deleted successfully.';
}


// ── Handle Edit User Profile ──
if (isset($_POST['action']) && $_POST['action'] === 'edit_user_profile' && isset($_POST['user_id'])) {
    $uid = (int)$_POST['user_id'];
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    try {
        $pdo->prepare("UPDATE users SET name = :n, phone = :p, email = :e, address = :a WHERE id = :id")->execute([':n' => $name, ':p' => $phone, ':e' => $email, ':a' => $address, ':id' => $uid]);
        $msg = "User profile #$uid updated successfully.";
    } catch (Exception $e) {
        $err = "Error updating user: " . $e->getMessage();
    }
}

// ── Handle Edit Ref Code ──
if (isset($_POST['action']) && $_POST['action'] === 'update_ref_code' && isset($_POST['user_id'])) {
    $uid = (int)$_POST['user_id'];
    $code = strtoupper(preg_replace('/[^A-Z0-9]/i', '', trim($_POST['ref_code'] ?? '')));
    if (strlen($code) < 3 || strlen($code) > 15) {
        $err = "Referral code must be 3-15 alphanumeric characters.";
    } else {
        $dup = $pdo->prepare("SELECT id FROM users WHERE ref_code = :c AND id != :id");
        $dup->execute([':c' => $code, ':id' => $uid]);
        if ($dup->fetch()) {
            $err = "Referral code '$code' is already in use by another user.";
        } else {
            $pdo->prepare("UPDATE users SET ref_code = :c WHERE id = :id")->execute([':c' => $code, ':id' => $uid]);
            $msg = "Referral code updated successfully.";
        }
    }
}

// ── Handle Delete User/Shop ──
if (isset($_POST['action']) && $_POST['action'] === 'delete_user_shop' && isset($_POST['user_id'])) {
    $uid = (int)$_POST['user_id'];
    $delType = $_POST['delete_type'] ?? 'shop';
    if ($delType === 'shop') {
        $pdo->prepare("DELETE FROM partners WHERE user_id = :uid")->execute([':uid' => $uid]);
        $msg = 'Shop deleted successfully. User account preserved.';
    } else if ($delType === 'both') {
        $pdo->prepare("DELETE FROM partners WHERE user_id = :uid")->execute([':uid' => $uid]);
        $pdo->prepare("DELETE FROM users WHERE id = :uid")->execute([':uid' => $uid]);
        $msg = 'User and Shop deleted entirely.';
    }
}

// ── Handle Adjust Coins ──
if (isset($_POST['action']) && $_POST['action'] === 'adjust_coins' && isset($_POST['user_id'])) {
    $uid = (int)$_POST['user_id'];
    $amount = (float)$_POST['coins_amount'];
    $type = $_POST['adj_type'] === 'debit' ? 'debit' : 'credit';
    $reason = trim($_POST['reason'] ?? 'Admin Adjustment');
    if ($amount > 0) {
        if ($type === 'credit') {
            $pdo->prepare("UPDATE users SET coins_balance = coins_balance + :amt WHERE id = :uid")->execute([':amt' => $amount, ':uid' => $uid]);
            $msg = "Credited " . number_format($amount, 2) . " coins to User #{$uid}.";
        } else {
            $pdo->prepare("UPDATE users SET coins_balance = GREATEST(0, coins_balance - :amt) WHERE id = :uid")->execute([':amt' => $amount, ':uid' => $uid]);
            $msg = "Debited " . number_format($amount, 2) . " coins from User #{$uid}.";
        }
        try {
            $txType = $type === 'credit' ? 'deposit' : 'debit';
            $pdo->prepare("INSERT INTO coin_transactions (user_id, type, amount, reference, status, description) VALUES (:uid, :type, :amt, :reason, 'completed', :reason)")
                ->execute([':uid' => $uid, ':type' => $txType, ':amt' => $amount, ':reason' => $reason]);
        } catch (Exception $eTx) {}
    }
}

// ── 3. Handle KYC Verification Action ──
if (isset($_POST['action']) && $_POST['action'] === 'kyc_action' && isset($_POST['user_id'])) {
    $uid = (int)$_POST['user_id'];
    $status = in_array($_POST['kyc_status'], ['approved', 'rejected', 'pending']) ? $_POST['kyc_status'] : 'approved';
    $nid_num = trim($_POST['nid_number'] ?? '');
    
    try {
        // Defensive self-healing schema additions
        try { $pdo->exec("ALTER TABLE users ADD COLUMN kyc_status VARCHAR(50) DEFAULT 'pending'"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE users ADD COLUMN nid_number VARCHAR(100) DEFAULT NULL"); } catch (Exception $e) {}
        
        $sqlUpdate = "UPDATE users SET kyc_status = :st";
        $params = [':st' => $status, ':id' => $uid];
        if (!empty($nid_num)) {
            $sqlUpdate .= ", nid_number = :nid";
            $params[':nid'] = $nid_num;
        }
        $sqlUpdate .= " WHERE id = :id";
        
        $pdo->prepare($sqlUpdate)->execute($params);
        
        // Notify User
        try {
            $notif_msg = ($status === 'approved') 
                ? 'Your KYC Identity Verification has been approved! Your account is now fully verified.' 
                : (($status === 'rejected') 
                    ? 'Your KYC verification was rejected. Please review your documents and submit clear copies.'
                    : 'Your KYC verification is currently under review.');
            $pdo->prepare("INSERT INTO user_notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)")
                ->execute([$uid, 'KYC Verification Update', $notif_msg, 'kyc']);
        } catch (Exception $eNotif) {}

        $msg = "KYC status for User #{$uid} updated to " . strtoupper($status) . " successfully!";
    } catch (Exception $e) {
        $err = "Failed to update KYC status: " . $e->getMessage();
    }
}

// ── 4. Handle Toggle User Status (Activate / Suspend) ──
if (isset($_POST['action']) && $_POST['action'] === 'toggle_user_status' && isset($_POST['user_id'])) {
    $uid = (int)$_POST['user_id'];
    $new_status = intval($_POST['is_active'] ?? 1);
    try {
        try { $pdo->exec("ALTER TABLE users ADD COLUMN is_active TINYINT(1) DEFAULT 1"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE users ADD COLUMN status VARCHAR(50) DEFAULT 'active'"); } catch (Exception $e) {}
        $status_str = $new_status ? 'active' : 'suspended';
        $pdo->prepare("UPDATE users SET is_active = :st, status = :st_str WHERE id = :uid")->execute([':st' => $new_status, ':st_str' => $status_str, ':uid' => $uid]);
        $msg = "User #{$uid} account status updated to " . ($new_status ? 'ACTIVE (APPROVED)' : 'SUSPENDED') . "!";
    } catch (Exception $e) {
        $err = "Failed to update user status: " . $e->getMessage();
    }
}

// ── 5. Handle Broadcast Notification ──
if (isset($_POST['action']) && $_POST['action'] === 'send_broadcast') {
    $title = trim($_POST['title'] ?? '');
    $body = trim($_POST['message'] ?? '');
    $target_uid = intval($_POST['target_user_id'] ?? 0); // 0 means all users

    if ($title && $body) {
        try {
            if ($target_uid > 0) {
                $pdo->prepare("INSERT INTO user_notifications (user_id, title, message, type) VALUES (?, ?, ?, 'system')")
                    ->execute([$target_uid, $title, $body]);
                $msg = "Notification sent to User #{$target_uid}!";
            } else {
                $all_uids = $pdo->query("SELECT id FROM users")->fetchAll(PDO::FETCH_COLUMN);
                $stmt_n = $pdo->prepare("INSERT INTO user_notifications (user_id, title, message, type) VALUES (?, ?, ?, 'system')");
                foreach ($all_uids as $uid) {
                    $stmt_n->execute([$uid, $title, $body]);
                }
                $msg = "Broadcast notification successfully sent to all " . count($all_uids) . " users!";
            }
        } catch (Exception $e) {
            $err = "Error sending notification: " . $e->getMessage();
        }
    }
}

// Search Filter
$search = trim($_GET['search'] ?? '');
$users = [];
try {
    if ($search !== '') {
        $stmt = $pdo->prepare("
            SELECT u.*, p.id as shop_id, p.business_name as shop_name, p.shop_slug, p.profile_pic as shop_logo, p.status as shop_status, p.created_at as shop_created_at 
            FROM users u 
            LEFT JOIN partners p ON u.id = p.user_id
            WHERE u.name LIKE :q 
               OR u.phone LIKE :q 
               OR u.email LIKE :q 
               OR u.ref_code LIKE :q 
               OR p.business_name LIKE :q
            ORDER BY u.created_at DESC
        ");
        $stmt->execute([':q' => "%$search%"]);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $users = $pdo->query("SELECT u.*, p.id as shop_id, p.business_name as shop_name, p.shop_slug, p.profile_pic as shop_logo, p.status as shop_status, p.created_at as shop_created_at FROM users u LEFT JOIN partners p ON u.id = p.user_id ORDER BY u.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    try {
        $users = $pdo->query("SELECT u.id, u.name, u.phone, u.email, u.ref_code, u.coins_balance, u.created_at, p.id as shop_id, p.business_name as shop_name, p.shop_slug, p.profile_pic as shop_logo, p.status as shop_status, p.created_at as shop_created_at FROM users u LEFT JOIN partners p ON u.id = p.user_id ORDER BY u.id DESC")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e2) {
        $users = [];
    }
}

// Fetch KYC users (separate pending queue from verified archive)
$pending_kyc_users = [];
$verified_kyc_users = [];
try {
    $all_kyc = $pdo->query("
        SELECT * FROM users 
        WHERE (nid IS NOT NULL AND nid != '') 
           OR (passport IS NOT NULL AND passport != '') 
           OR (driving_license IS NOT NULL AND driving_license != '') 
           OR (etin IS NOT NULL AND etin != '')
           OR (nid_front_photo IS NOT NULL AND nid_front_photo != '')
        ORDER BY id DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($all_kyc as $ku) {
        $st = strtolower($ku['kyc_status'] ?? 'pending');
        if ($st === 'approved' || $st === 'verified') {
            $verified_kyc_users[] = $ku;
        } else {
            $pending_kyc_users[] = $ku;
        }
    }
} catch (Exception $e) {
    $pending_kyc_users = [];
    $verified_kyc_users = [];
}

$kyc_users = $pending_kyc_users; // Active queue only contains pending

// Fetch Loyalty Leaderboard safely
$loyalty_users = [];
try {
    $loyalty_users = $pdo->query("SELECT * FROM users ORDER BY coins_balance DESC LIMIT 30")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $loyalty_users = [];
}

$totalUsers = count($users);
$pending_kyc_count = count($pending_kyc_users);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=0"/>
  <title>Unified User & KYC Hub — Fast Site Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Oswald:wght@600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="/assets/css/admin.css?v=<?= time() ?>">
  <style>
    .user-card-table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0 8px;
    }
    .user-card-table th {
      padding: 0.8rem 1rem;
      font-size: 0.75rem;
      color: var(--muted);
      text-transform: uppercase;
      font-weight: 800;
      border: none;
    }
    .user-card-table td {
      padding: 1rem;
      background: rgba(20, 20, 31, 0.8);
      border-top: 1px solid rgba(255, 255, 255, 0.05);
      border-bottom: 1px solid rgba(255, 255, 255, 0.05);
      font-size: 0.9rem;
      vertical-align: middle;
    }
    .user-card-table tr td:first-child {
      border-left: 1px solid rgba(255, 255, 255, 0.05);
      border-radius: 12px 0 0 12px;
    }
    .user-card-table tr td:last-child {
      border-right: 1px solid rgba(255, 255, 255, 0.05);
      border-radius: 0 12px 12px 0;
    }
    .kyc-preview-img {
      width: 130px;
      height: 85px;
      object-fit: cover;
      border-radius: 8px;
      border: 1px solid rgba(255,255,255,0.15);
      cursor: pointer;
      transition: transform 0.2s;
    }
    .kyc-preview-img:hover {
      transform: scale(1.05);
      border-color: var(--gold);
    }
    .doc-chip {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      padding: 3px 8px;
      border-radius: 6px;
      font-size: 0.72rem;
      font-weight: 700;
      background: rgba(255,255,255,0.06);
      border: 1px solid rgba(255,255,255,0.12);
      color: #cbd5e1;
      text-decoration: none;
    }
    .doc-chip:hover {
      border-color: var(--gold);
      color: #fff;
    }
  </style>
</head>
<body>
<?php include __DIR__ . '/nav.php'; ?>

<div class="dashboard-container">

  <!-- Header Admin Hero -->
  <div class="admin-hero">
    <h2 class="admin-hero-title">👤 UNIFIED USER PANEL HUB</h2>
    <p class="admin-hero-subtitle">Customer Accounts, KYC Identity Verification, Loyalty Streaks & Broadcast Center</p>
    
    <div class="tabs-nav">
      <button class="tab-btn active" id="btn-customers" onclick="switchTab(event, 'tab-customers')">
        👥 All Customers (<?= $totalUsers ?>)
      </button>
      <button class="tab-btn" id="btn-kyc" onclick="switchTab(event, 'tab-kyc')">
        🪪 KYC Vault (<?= count($kyc_users) ?>) <?= $pending_kyc_count > 0 ? "<span style='background:#ef4444; color:#fff; padding:2px 6px; border-radius:10px; font-size:0.7rem;'>$pending_kyc_count</span>" : "" ?>
      </button>
      <button class="tab-btn" id="btn-broadcast" onclick="switchTab(event, 'tab-broadcast')">
        🔔 Broadcast Notifications
      </button>
      <button class="tab-btn" id="btn-loyalty" onclick="switchTab(event, 'tab-loyalty')">
        🎁 Loyalty & Streaks
      </button>
    </div>
  </div>

  <div class="wrap" style="padding:0; max-width:100%;">

    <?php if($msg): ?>
      <div style="background:rgba(0,230,118,0.12); border:1px solid rgba(0,230,118,0.3); color:#00e676; padding:1rem 1.4rem; border-radius:14px; margin-bottom:1.5rem; font-weight:700;">
        ✅ <?= htmlspecialchars($msg) ?>
      </div>
    <?php endif; ?>

    <?php if($err): ?>
      <div style="background:rgba(255,82,82,0.12); border:1px solid rgba(255,82,82,0.3); color:#ff5252; padding:1rem 1.4rem; border-radius:14px; margin-bottom:1.5rem; font-weight:700;">
        ⚠️ <?= htmlspecialchars($err) ?>
      </div>
    <?php endif; ?>

    <!-- ── TAB 1: ALL CUSTOMERS & USERS ── -->
    <div id="tab-customers" class="tab-content active">
      <!-- Search Bar -->
      <form method="GET" action="users.php" style="margin-bottom:1.5rem;">
        <div style="display:flex; gap:0.8rem; flex-wrap:wrap;">
          <input type="text" name="search" placeholder="🔍 Search customer by name, phone, email, NID, ref code..." value="<?= htmlspecialchars($search) ?>" style="flex:1; min-width:250px; background:rgba(0,0,0,0.4); border:1px solid rgba(255,255,255,0.12); padding:0.8rem 1.2rem; border-radius:10px; color:#fff; outline:none; font-size:0.9rem;"/>
          <button type="submit" class="btn-sm" style="padding:0.8rem 1.5rem; background:linear-gradient(135deg, var(--gold), #f59e0b); color:#000; font-weight:800; border:none; border-radius:10px; cursor:pointer;">
            Search
          </button>
          <?php if($search): ?>
            <a href="users.php" style="color:var(--muted); font-size:0.85rem; text-decoration:none; display:flex; align-items:center; padding:0 0.5rem;">Clear</a>
          <?php endif; ?>
        </div>
      </form>

      <div style="overflow-x:auto;">
        <table class="user-card-table">
          <thead>
            <tr>
              <th>User</th>
              <th>Shop</th>
              <th>Wallet</th>
              <th>KYC</th>
              <th>Ref Code</th>
              <th style="text-align:right;">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($users)): ?>
              <tr><td colspan="7" style="text-align:center; padding:2rem; color:var(--muted);">No customers found matching search.</td></tr>
            <?php else: ?>
              <?php foreach($users as $u): 
                  $kyc_badge = '#fcb900';
                  $kyc_label = $u['kyc_status'] ?? 'pending';
                  if ($kyc_label === 'approved') $kyc_badge = '#10b981';
                  elseif ($kyc_label === 'rejected') $kyc_badge = '#ef4444';
                  
                  // Encode user data for instant KYC modal
                  $uJson = htmlspecialchars(json_encode([
                    'id' => $u['id'],
                    'name' => $u['name'] ?? '',
                    'phone' => $u['phone'] ?? '',
                    'email' => $u['email'] ?? '',
                    'kyc_status' => $u['kyc_status'] ?? 'pending',
                    'nid_number' => $u['nid_number'] ?? '',
                    'nid' => $u['nid'] ?? '',
                    'etin' => $u['etin'] ?? '',
                    'passport' => $u['passport'] ?? '',
                    'driving_license' => $u['driving_license'] ?? '',
                    'nid_front' => $u['nid_front_photo'] ?? '',
                    'nid_back' => $u['nid_back_photo'] ?? '',
                    'trade_license' => $u['trade_license'] ?? '',
                    'address' => $u['address'] ?? '',
                    'district' => $u['district'] ?? ''
                  ]), ENT_QUOTES, 'UTF-8');
              ?>
              <tr>
                <td>
                  <div style="display:flex; align-items:center; gap:10px;">
                    <img src="<?= htmlspecialchars(resolveShopMedia($u, 'avatar')) ?>" style="width:40px; height:40px; border-radius:50%; object-fit:cover; border:1px solid rgba(255,255,255,0.1);"/>
                    <div>
                      <strong style="color:#fff; font-size:0.95rem;"><?= htmlspecialchars($u['name']) ?></strong>
                      <div style="font-size:0.75rem; color:var(--muted); margin-top:2px;">
                        📞 <?= htmlspecialchars($u['phone']) ?> <br/>
                        <span style="color:#94a3b8; font-size:0.7rem;"><?= date('M d, Y', strtotime($u['created_at'])) ?></span>
                      </div>
                    </div>
                  </div>
                </td>
                <td>
                  <?php if(!empty($u['shop_id'])): ?>
                    <div style="display:flex; align-items:center; gap:8px;">
                      <img src="<?= htmlspecialchars(resolveShopMedia(['profile_pic'=>$u['shop_logo']], 'avatar')) ?>" style="width:32px; height:32px; border-radius:8px; object-fit:cover; border:1px solid rgba(252,185,0,0.3);"/>
                      <div>
                        <strong style="color:var(--gold); font-size:0.85rem;"><?= htmlspecialchars($u['shop_name']) ?></strong>
                        <div style="font-size:0.7rem; color:var(--muted); margin-top:1px;">
                          <a href="/shop/<?= htmlspecialchars($u['shop_slug']) ?>" target="_blank" style="color:#38bdf8; text-decoration:none;">/shop/<?= htmlspecialchars($u['shop_slug']) ?></a>
                        </div>
                      </div>
                    </div>
                  <?php else: ?>
                    <span style="background:rgba(255,255,255,0.05); color:#94a3b8; padding:3px 8px; border-radius:6px; font-size:0.75rem;">No Shop Yet</span>
                  <?php endif; ?>
                </td>
                <td>
                  <span style="font-family:'Oswald',sans-serif; font-size:1.15rem; color:var(--gold); font-weight:700;">
                    🪙 <?= number_format($u['coins_balance'] ?? 0, 2) ?>
                  </span>
                </td>
                <td>
                  <button onclick="openKycModal(<?= $uJson ?>)" title="Click to view & verify KYC" style="background:rgba(255,255,255,0.04); border:1px solid <?= $kyc_badge ?>; color:<?= $kyc_badge ?>; padding:3px 10px; border-radius:12px; font-size:0.72rem; font-weight:800; text-transform:uppercase; cursor:pointer; display:inline-flex; align-items:center; gap:4px;">
                    🪪 <?= htmlspecialchars($kyc_label) ?>
                  </button>
                </td>
                <td>
                  <div style="display:flex; align-items:center; gap:6px;">
                    <span style="font-family:monospace; color:var(--brand); font-weight:700; font-size:0.85rem; background:rgba(0,0,0,0.3); padding:4px 8px; border-radius:6px;">
                      <?= htmlspecialchars($u['ref_code'] ?? 'FS-U-' . $u['id']) ?>
                    </span>
                    <button onclick="openRefCodeModal(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['ref_code'] ?? 'FS-U-' . $u['id'])) ?>')" style="background:none; border:none; color:var(--muted); cursor:pointer; font-size:0.9rem;" title="Edit Ref Code">✏️</button>
                  </div>
                </td>
                <td style="text-align:right; white-space:nowrap;">
                  <div style="display:inline-flex; gap:6px; align-items:center;">
                    <button onclick="openEditChoiceModal(<?= $u['id'] ?>, <?= $u['shop_id'] ?? 'null' ?>, '<?= htmlspecialchars(addslashes($u['name'])) ?>', '<?= htmlspecialchars(addslashes($u['phone'])) ?>', '<?= htmlspecialchars(addslashes($u['email'])) ?>', '<?= htmlspecialchars(addslashes($u['address'] ?? '')) ?>')" style="background:rgba(255,255,255,0.1); color:#fff; border:1px solid rgba(255,255,255,0.2); padding:5px 10px; border-radius:6px; font-size:0.75rem; font-weight:700; cursor:pointer;">
                      ✏️ Edit
                    </button>
                    <button onclick="openCoinModal(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['name'])) ?>')" style="background:rgba(252,185,0,0.15); color:var(--gold); border:1px solid var(--gold); padding:5px 10px; border-radius:6px; font-size:0.75rem; font-weight:800; cursor:pointer;">
                      🪙 Wallet
                    </button>
                    <button onclick="openDeleteChoiceModal(<?= $u['id'] ?>, <?= $u['shop_id'] ?? 'null' ?>)" style="background:rgba(239,68,68,0.12); color:#ef4444; border:1px solid rgba(239,68,68,0.3); padding:5px 10px; border-radius:6px; font-weight:700; cursor:pointer; font-size:0.75rem;">
                      ✕ Del
                    </button>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ── TAB 2: KYC VERIFICATION VAULT ── -->
    <div id="tab-kyc" class="tab-content">
      <div style="background:rgba(20,20,31,0.85); border:1px solid rgba(255,255,255,0.08); border-radius:18px; padding:1.8rem;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
          <div>
            <h3 style="color:#fff; font-size:1.2rem; font-weight:800; margin:0 0 4px 0; display:flex; align-items:center; gap:8px;">
              🪪 Identity Verification (KYC) Submissions Queue
            </h3>
            <p style="color:var(--muted); font-size:0.85rem; margin:0;">
              Review uploaded National IDs, Passports, and Certificates to grant verified user badges.
            </p>
          </div>
          <span style="background:rgba(252,185,0,0.15); color:var(--gold); border:1px solid rgba(252,185,0,0.3); padding:4px 12px; border-radius:20px; font-size:0.8rem; font-weight:800;">
            <?= count($kyc_users) ?> KYC Records
          </span>
        </div>

        <?php if (empty($kyc_users)): ?>
          <div style="text-align:center; padding:3.5rem; color:var(--muted);">
            <div style="font-size:3rem; margin-bottom:0.5rem; opacity:0.6;">✅</div>
            <h4>KYC Vault Clear</h4>
            <p style="font-size:0.85rem;">No customer documents currently awaiting review.</p>
          </div>
        <?php else: ?>
          <div style="display:flex; flex-direction:column; gap:1.2rem;">
            <?php foreach($kyc_users as $ku): 
                $st = strtolower($ku['kyc_status'] ?? 'pending');
                $badgeBg = $st === 'approved' ? '#10b981' : ($st === 'rejected' ? '#ef4444' : '#f59e0b');
            ?>
              <div style="background:rgba(0,0,0,0.3); border:1px solid rgba(255,255,255,0.06); border-radius:14px; padding:1.5rem; display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:1.5rem;">
                <div style="flex:1; min-width:280px;">
                  <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px; flex-wrap:wrap;">
                    <strong style="color:#fff; font-size:1.15rem;"><?= htmlspecialchars($ku['name']) ?></strong>
                    <span style="background:rgba(255,255,255,0.05); color:var(--gold); padding:2px 8px; border-radius:4px; font-size:0.75rem; font-weight:800;">USER #<?= $ku['id'] ?></span>
                    <span style="border:1px solid <?= $badgeBg ?>; color:<?= $badgeBg ?>; background:rgba(255,255,255,0.03); padding:2px 8px; border-radius:10px; font-size:0.7rem; font-weight:800; text-transform:uppercase;">
                      <?= htmlspecialchars($st) ?>
                    </span>
                  </div>

                  <div style="font-size:0.82rem; color:var(--muted); margin-bottom:1rem; display:flex; gap:12px; flex-wrap:wrap;">
                    <span>📱 Phone: <strong style="color:#fff;"><?= htmlspecialchars($ku['phone']) ?></strong></span>
                    <span>✉️ Email: <strong style="color:#cbd5e1;"><?= htmlspecialchars($ku['email'] ?: 'N/A') ?></strong></span>
                    <span>🆔 NID/Doc: <strong style="color:var(--brand);"><?= htmlspecialchars($ku['nid_number'] ?: 'Submitted') ?></strong></span>
                    <?php if (!empty($ku['district'])): ?>
                      <span>📍 Region: <strong style="color:#cbd5e1;"><?= htmlspecialchars($ku['district']) ?></strong></span>
                    <?php endif; ?>
                  </div>

                  <!-- KYC Photo / Document Gallery -->
                  <div style="display:flex; gap:12px; flex-wrap:wrap; align-items:center;">
                    <?php if(!empty($ku['nid'])): ?>
                      <div>
                        <div style="font-size:0.7rem; color:var(--muted); margin-bottom:3px;">National ID</div>
                        <a href="/<?= htmlspecialchars(ltrim($ku['nid'], '/')) ?>" target="_blank">
                          <img src="/<?= htmlspecialchars(ltrim($ku['nid'], '/')) ?>" class="kyc-preview-img" alt="NID Document" onerror="this.src='/assets/images/default_avatar.png';"/>
                        </a>
                      </div>
                    <?php endif; ?>

                    <?php if(!empty($ku['passport'])): ?>
                      <div>
                        <div style="font-size:0.7rem; color:var(--muted); margin-bottom:3px;">Passport</div>
                        <a href="/<?= htmlspecialchars(ltrim($ku['passport'], '/')) ?>" target="_blank">
                          <img src="/<?= htmlspecialchars(ltrim($ku['passport'], '/')) ?>" class="kyc-preview-img" alt="Passport Document" onerror="this.src='/assets/images/default_avatar.png';"/>
                        </a>
                      </div>
                    <?php endif; ?>

                    <?php if(!empty($ku['etin'])): ?>
                      <div>
                        <div style="font-size:0.7rem; color:var(--muted); margin-bottom:3px;">e-TIN</div>
                        <a href="/<?= htmlspecialchars(ltrim($ku['etin'], '/')) ?>" target="_blank">
                          <img src="/<?= htmlspecialchars(ltrim($ku['etin'], '/')) ?>" class="kyc-preview-img" alt="e-TIN" onerror="this.src='/assets/images/default_avatar.png';"/>
                        </a>
                      </div>
                    <?php endif; ?>

                    <?php if(!empty($ku['driving_license'])): ?>
                      <div>
                        <div style="font-size:0.7rem; color:var(--muted); margin-bottom:3px;">Driving License</div>
                        <a href="/<?= htmlspecialchars(ltrim($ku['driving_license'], '/')) ?>" target="_blank">
                          <img src="/<?= htmlspecialchars(ltrim($ku['driving_license'], '/')) ?>" class="kyc-preview-img" alt="Driving License" onerror="this.src='/assets/images/default_avatar.png';"/>
                        </a>
                      </div>
                    <?php endif; ?>

                    <?php if(empty($ku['nid']) && empty($ku['passport']) && empty($ku['etin']) && empty($ku['driving_license']) && empty($ku['nid_front_photo'])): ?>
                      <span style="font-size:0.8rem; color:var(--muted); font-style:italic;">No visual document attachments uploaded yet.</span>
                    <?php endif; ?>
                  </div>
                </div>

                <!-- KYC Approval Triggers -->
                <div style="display:flex; flex-direction:column; gap:0.6rem; min-width:170px;">
                  <form method="POST">
                    <input type="hidden" name="action" value="kyc_action"/>
                    <input type="hidden" name="user_id" value="<?= $ku['id'] ?>"/>
                    <input type="hidden" name="kyc_status" value="approved"/>
                    <button type="submit" style="width:100%; background:linear-gradient(135deg, #10b981, #059669); color:#000; font-weight:900; border:none; padding:0.65rem 1.2rem; border-radius:8px; cursor:pointer; font-size:0.85rem;">
                      ✓ Approve KYC (Verified)
                    </button>
                  </form>

                  <form method="POST">
                    <input type="hidden" name="action" value="kyc_action"/>
                    <input type="hidden" name="user_id" value="<?= $ku['id'] ?>"/>
                    <input type="hidden" name="kyc_status" value="rejected"/>
                    <button type="submit" style="width:100%; background:rgba(239,68,68,0.15); color:#ef4444; border:1px solid #ef4444; font-weight:700; padding:0.55rem 1.2rem; border-radius:8px; cursor:pointer; font-size:0.85rem;">
                      ✕ Reject Verification
                    </button>
                  </form>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
        <?php if (!empty($verified_kyc_users)): ?>
          <!-- Collapsible Verified Accounts Archive -->
          <div style="margin-top:2rem; border-top:1px solid rgba(255,255,255,0.08); padding-top:1.5rem;">
            <details style="background:rgba(0,0,0,0.25); border:1px solid rgba(16,185,129,0.2); border-radius:12px; padding:1rem;">
              <summary style="cursor:pointer; font-size:0.95rem; font-weight:800; color:#10b981; display:flex; align-items:center; justify-content:space-between; user-select:none;">
                <span>✅ Verified Accounts Archive (<?= count($verified_kyc_users) ?>)</span>
                <span style="font-size:0.75rem; color:var(--muted);">Click to View / Hide</span>
              </summary>
              <div style="display:flex; flex-direction:column; gap:1rem; margin-top:1.2rem;">
                <?php foreach($verified_kyc_users as $vu): ?>
                  <div style="background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.05); border-radius:10px; padding:1rem; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
                    <div>
                      <strong style="color:#fff;"><?= htmlspecialchars($vu['name']) ?></strong>
                      <span style="background:rgba(16,185,129,0.15); color:#10b981; padding:2px 6px; border-radius:4px; font-size:0.7rem; font-weight:800; margin-left:6px;">VERIFIED USER #<?= $vu['id'] ?></span>
                      <div style="font-size:0.78rem; color:var(--muted); margin-top:4px;">
                        📱 <?= htmlspecialchars($vu['phone']) ?> | ✉️ <?= htmlspecialchars($vu['email'] ?: 'N/A') ?>
                      </div>
                    </div>
                    <form method="POST">
                      <input type="hidden" name="action" value="kyc_action"/>
                      <input type="hidden" name="user_id" value="<?= $vu['id'] ?>"/>
                      <input type="hidden" name="kyc_status" value="pending"/>
                      <button type="submit" style="background:rgba(255,255,255,0.05); color:#cbd5e1; border:1px solid rgba(255,255,255,0.1); padding:0.4rem 0.8rem; border-radius:6px; font-size:0.75rem; cursor:pointer;" onclick="return confirm('Reset KYC status to pending for this user?');">
                        🔄 Reset Status
                      </button>
                    </form>
                  </div>
                <?php endforeach; ?>
              </div>
            </details>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- ── TAB 3: BROADCAST NOTIFICATIONS ── -->
    <div id="tab-broadcast" class="tab-content">
      <div style="background:rgba(20,20,31,0.85); border:1px solid rgba(255,255,255,0.08); border-radius:18px; padding:2rem; max-width:750px;">
        <h3 style="color:var(--gold); font-size:1.2rem; font-weight:800; margin-bottom:0.4rem; display:flex; align-items:center; gap:8px;">
          🔔 Send Broadcast Push Notification
        </h3>
        <p style="color:var(--muted); font-size:0.85rem; margin-bottom:1.5rem;">
          Dispatch system messages directly into user dashboards and mobile APK notifications drawer.
        </p>

        <form method="POST">
          <input type="hidden" name="action" value="send_broadcast"/>

          <div style="margin-bottom:1.2rem;">
            <label style="display:block; font-size:0.75rem; font-weight:700; color:var(--muted); text-transform:uppercase; margin-bottom:0.4rem;">Target Audience</label>
            <select name="target_user_id" style="width:100%; background:rgba(0,0,0,0.4); border:1px solid rgba(255,255,255,0.12); color:#fff; padding:0.8rem; border-radius:10px; outline:none;">
              <option value="0">📢 All Registered Users (Global Broadcast)</option>
              <?php foreach(array_slice($users, 0, 50) as $usr): ?>
                <option value="<?= $usr['id'] ?>">👤 User #<?= $usr['id'] ?> — <?= htmlspecialchars($usr['name']) ?> (<?= htmlspecialchars($usr['phone']) ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>

          <div style="margin-bottom:1.2rem;">
            <label style="display:block; font-size:0.75rem; font-weight:700; color:var(--muted); text-transform:uppercase; margin-bottom:0.4rem;">Notification Title *</label>
            <input type="text" name="title" required placeholder="e.g. 🎁 Special Loyalty Bonus Available!" style="width:100%; background:rgba(0,0,0,0.4); border:1px solid rgba(255,255,255,0.12); color:#fff; padding:0.8rem; border-radius:10px; outline:none;"/>
          </div>

          <div style="margin-bottom:1.5rem;">
            <label style="display:block; font-size:0.75rem; font-weight:700; color:var(--muted); text-transform:uppercase; margin-bottom:0.4rem;">Message Body *</label>
            <textarea name="message" rows="4" required placeholder="Enter message text details..." style="width:100%; background:rgba(0,0,0,0.4); border:1px solid rgba(255,255,255,0.12); color:#fff; padding:0.8rem; border-radius:10px; outline:none; font-family:'Inter',sans-serif;"></textarea>
          </div>

          <button type="submit" style="background:linear-gradient(135deg, var(--brand), #007bb5); color:#fff; font-weight:800; border:none; padding:0.9rem 2rem; border-radius:10px; cursor:pointer; font-size:0.95rem; box-shadow:0 4px 15px rgba(33,150,243,0.3);">
            🚀 Dispatch Notification
          </button>
        </form>
      </div>
    </div>

    <!-- ── TAB 4: CUSTOMER LOYALTY & STREAKS ── -->
    <div id="tab-loyalty" class="tab-content">
      <div style="background:rgba(20,20,31,0.85); border:1px solid rgba(255,255,255,0.08); border-radius:18px; padding:1.8rem;">
        <h3 style="color:var(--gold); font-size:1.2rem; font-weight:800; margin-bottom:1.5rem; display:flex; align-items:center; gap:8px;">
          🏆 Customer Fast Points & Check-In Streak Leaderboard
        </h3>

        <div style="overflow-x:auto;">
          <table class="user-card-table">
            <thead>
              <tr>
                <th>Rank</th>
                <th>Customer</th>
                <th>Daily Streak</th>
                <th>Fast Points Balance</th>
                <th>Estimated BDT Value</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($loyalty_users as $idx => $lu): ?>
              <tr>
                <td style="font-family:'Oswald',sans-serif; font-size:1.1rem; color:<?= $idx < 3 ? 'var(--gold)' : 'var(--muted)' ?>;">
                  #<?= $idx + 1 ?> <?= $idx === 0 ? '👑' : ($idx === 1 ? '🥈' : ($idx === 2 ? '🥉' : '')) ?>
                </td>
                <td>
                  <strong style="color:#fff;"><?= htmlspecialchars($lu['name']) ?></strong>
                  <div style="font-size:0.75rem; color:var(--muted);">ID: #<?= $lu['id'] ?> • <?= htmlspecialchars($lu['phone']) ?></div>
                </td>
                <td>
                  <span style="background:rgba(255,145,0,0.15); color:#ff9100; border:1px solid rgba(255,145,0,0.3); padding:3px 10px; border-radius:20px; font-weight:800; font-size:0.75rem;">
                    🔥 <?= (int)($lu['day_streak'] ?? 0) ?> Days
                  </span>
                </td>
                <td>
                  <span style="font-family:'Oswald',sans-serif; font-size:1.2rem; color:var(--gold); font-weight:700;">
                    🪙 <?= number_format($lu['coins_balance'] ?? 0, 2) ?>
                  </span>
                </td>
                <td style="color:#00e676; font-weight:700; font-size:0.95rem;">
                  ৳<?= number_format($lu['coins_balance'] ?? 0, 2) ?> BDT
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
</div>


<!-- ========================================== -->
<!-- 1. KYC & Trade License Verification Modal -->
<!-- ========================================== -->
<div id="kycModal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.85); z-index:99999; align-items:center; justify-content:center; padding:1rem; backdrop-filter:blur(10px);">
  <div style="background:#12131e; border-radius:18px; width:100%; max-width:650px; border:1px solid rgba(255,255,255,0.08); box-shadow:0 20px 50px rgba(0,0,0,0.8); overflow:hidden; display:flex; flex-direction:column; max-height:90vh;">
    
    <div style="padding:1.5rem 1.8rem; border-bottom:1px solid rgba(255,255,255,0.08); display:flex; justify-content:space-between; align-items:center; background:rgba(255,255,255,0.02);">
      <h3 style="color:#fff; margin:0; font-size:1.2rem; font-weight:800; display:flex; align-items:center; gap:8px;">🪪 Document & KYC Vault</h3>
      <button onclick="closeKycModal()" style="background:transparent; border:none; color:#94a3b8; font-size:1.5rem; cursor:pointer;">✕</button>
    </div>

    <div style="padding:1.5rem 1.8rem; overflow-y:auto; flex:1;">
      <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1.5rem; flex-wrap:wrap; gap:10px;">
        <div>
          <strong id="modalKycUserName" style="color:#fff; font-size:1.1rem;"></strong>
          <div style="font-size:0.8rem; color:#94a3b8; margin-top:2px;">
            Phone: <span id="modalKycUserPhone" style="color:#fff;"></span> | Email: <span id="modalKycUserEmail" style="color:#cbd5e1;"></span>
          </div>
        </div>
        <span id="modalKycStatusBadge" style="padding:3px 10px; border-radius:10px; font-size:0.75rem; font-weight:800; text-transform:uppercase;"></span>
      </div>

      <div style="font-size:0.8rem; color:#94a3b8; margin-bottom:1rem;">
        Address: <span id="modalKycUserAddress" style="color:#cbd5e1;"></span>
      </div>

      <div style="font-size:0.75rem; font-weight:800; color:var(--muted); text-transform:uppercase; margin-bottom:0.6rem;">Attached Identity Documents:</div>
      <div id="modalKycDocsList" style="display:flex; gap:10px; flex-wrap:wrap;"></div>
    </div>

    <div style="padding:1.5rem 1.8rem; background:rgba(0,0,0,0.3); border-top:1px solid rgba(255,255,255,0.05);">
      <form method="POST">
        <input type="hidden" name="action" value="kyc_action"/>
        <input type="hidden" name="user_id" id="modalKycUserId" value=""/>

        <div style="margin-bottom:1.2rem;">
          <label style="color:var(--muted); font-size:0.75rem; font-weight:700; text-transform:uppercase; display:block; margin-bottom:0.4rem;">National ID / Passport Number</label>
          <input type="text" name="nid_number" id="modalKycNidInput" placeholder="e.g. 1995829104820" style="width:100%; background:rgba(0,0,0,0.4); border:1px solid rgba(255,255,255,0.15); color:#fff; font-size:0.9rem; padding:0.75rem 1rem; border-radius:10px; outline:none;"/>
        </div>

        <div style="display:flex; gap:10px; justify-content:flex-end; flex-wrap:wrap;">
          <button type="submit" name="kyc_status" value="rejected" style="background:rgba(239,68,68,0.2); border:1px solid #ef4444; color:#ef4444; font-weight:800; padding:0.75rem 1.2rem; border-radius:10px; cursor:pointer;">
            ✕ Reject KYC
          </button>
          <button type="submit" name="kyc_status" value="pending" style="background:rgba(245,158,11,0.2); border:1px solid #f59e0b; color:#f59e0b; font-weight:800; padding:0.75rem 1.2rem; border-radius:10px; cursor:pointer;">
            ⏸ Suspend (Pending)
          </button>
          <button type="submit" name="kyc_status" value="approved" style="background:linear-gradient(135deg, #10b981, #059669); color:#000; font-weight:900; border:none; padding:0.75rem 1.6rem; border-radius:10px; cursor:pointer;">
            ✓ Approve (Mark Verified)
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ========================================== -->
<!-- 2. Wallet Adjustment Modal (+ / -) -->
<!-- ========================================== -->
<div id="coinModal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.85); z-index:99999; align-items:center; justify-content:center; backdrop-filter:blur(10px);">
  <div style="background:#12131e; padding:2rem; border-radius:18px; width:90%; max-width:420px; border:1px solid rgba(252,185,0,0.35); box-shadow:0 20px 50px rgba(0,0,0,0.8);">
    <h3 style="color:var(--gold); margin-bottom:0.4rem; font-size:1.2rem; font-weight:800;">Gift / Adjust Coins</h3>
    <p style="color:var(--muted); font-size:0.85rem; margin-bottom:1.2rem;">Adjust balance for <strong id="coinUserName" style="color:#fff;"></strong></p>
    
    <form method="POST">
      <input type="hidden" name="action" value="adjust_coins"/>
      <input type="hidden" name="user_id" id="coinUserId" value=""/>
      
      <div style="margin-bottom:1rem;">
        <label style="color:var(--muted); font-size:0.75rem; font-weight:700; text-transform:uppercase; display:block; margin-bottom:0.3rem;">Adjustment Type</label>
        <select name="adj_type" required style="width:100%; background:rgba(0,0,0,0.4); border:1px solid rgba(255,255,255,0.15); color:#fff; font-size:0.9rem; padding:0.8rem; border-radius:10px; outline:none;">
          <option value="credit">Credit / Add Coins (+)</option>
          <option value="debit">Debit / Deduct Coins (-)</option>
        </select>
      </div>

      <div style="margin-bottom:1rem;">
        <label style="color:var(--muted); font-size:0.75rem; font-weight:700; text-transform:uppercase; display:block; margin-bottom:0.3rem;">Amount *</label>
        <input type="number" step="any" min="1" name="coins_amount" placeholder="e.g. 100" required style="width:100%; background:rgba(0,0,0,0.4); border:1px solid rgba(255,255,255,0.15); color:var(--gold); font-size:1.1rem; font-weight:800; padding:0.8rem; border-radius:10px; outline:none;"/>
      </div>

      <div style="margin-bottom:1.5rem;">
        <label style="color:var(--muted); font-size:0.75rem; font-weight:700; text-transform:uppercase; display:block; margin-bottom:0.3rem;">Reason / Note (Visible in History)</label>
        <input type="text" name="reason" placeholder="e.g. Compensation, Bonus, Penalty..." required style="width:100%; background:rgba(0,0,0,0.4); border:1px solid rgba(255,255,255,0.15); color:#fff; font-size:0.9rem; padding:0.8rem; border-radius:10px; outline:none;"/>
      </div>

      <div style="display:flex; gap:10px; justify-content:flex-end;">
        <button type="button" onclick="closeCoinModal()" style="background:transparent; color:var(--muted); border:none; padding:0.8rem 1rem; cursor:pointer; font-weight:700;">Cancel</button>
        <button type="submit" style="background:linear-gradient(135deg, #f59e0b, #d97706); color:#000; font-weight:800; border:none; padding:0.8rem 1.5rem; border-radius:10px; cursor:pointer;">Confirm Adjustment</button>
      </div>
    </form>
  </div>
</div>

<!-- ========================================== -->
<!-- 3. Edit Choice Modal (User or Shop) -->
<!-- ========================================== -->
<div id="editChoiceModal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.85); z-index:99999; align-items:center; justify-content:center; backdrop-filter:blur(10px);">
  <div style="background:#12131e; padding:2rem; border-radius:18px; width:90%; max-width:420px; border:1px solid rgba(255,255,255,0.1); box-shadow:0 20px 50px rgba(0,0,0,0.8);">
    <h3 style="color:#fff; margin-bottom:1rem; font-size:1.2rem; font-weight:800;">Edit Hub</h3>
    <p style="color:var(--muted); font-size:0.85rem; margin-bottom:1.5rem;">What would you like to edit for <strong id="editUserName" style="color:#fff;"></strong>?</p>
    
    <div style="display:flex; flex-direction:column; gap:10px;">
      <button onclick="openInlineUserEdit()" style="background:rgba(56,189,248,0.15); border:1px solid #38bdf8; color:#38bdf8; padding:1rem; border-radius:10px; font-weight:700; cursor:pointer; font-size:0.95rem; text-align:left; display:flex; align-items:center; gap:8px;">
        👤 Edit User Profile (Inline)
      </button>
      <a id="editShopLink" href="#" style="background:rgba(252,185,0,0.15); border:1px solid var(--gold); color:var(--gold); padding:1rem; border-radius:10px; font-weight:700; cursor:pointer; font-size:0.95rem; text-decoration:none; text-align:left; display:flex; align-items:center; gap:8px;">
        🏪 Edit Shop Profile (Full Page)
      </a>
    </div>
    
    <button onclick="closeEditChoiceModal()" style="width:100%; margin-top:1rem; background:transparent; color:var(--muted); border:1px solid rgba(255,255,255,0.1); padding:0.8rem; border-radius:10px; cursor:pointer; font-weight:700;">Cancel</button>
  </div>
</div>

<!-- Inline User Edit Form Modal -->
<div id="inlineUserEditModal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.9); z-index:100000; align-items:center; justify-content:center; backdrop-filter:blur(5px);">
  <div style="background:#1e1e2d; padding:2rem; border-radius:18px; width:90%; max-width:450px; border:1px solid rgba(255,255,255,0.1);">
    <h3 style="color:#fff; margin-bottom:1.5rem;">Edit User Profile</h3>
    <form method="POST">
      <input type="hidden" name="action" value="edit_user_profile"/>
      <input type="hidden" name="user_id" id="eu_id" value=""/>
      
      <div style="margin-bottom:1rem;">
        <label style="color:var(--muted); font-size:0.8rem; display:block; margin-bottom:5px;">Name</label>
        <input type="text" name="name" id="eu_name" required style="width:100%; padding:0.8rem; border-radius:8px; background:rgba(0,0,0,0.4); border:1px solid rgba(255,255,255,0.1); color:#fff;"/>
      </div>
      <div style="margin-bottom:1rem;">
        <label style="color:var(--muted); font-size:0.8rem; display:block; margin-bottom:5px;">Phone</label>
        <input type="text" name="phone" id="eu_phone" required style="width:100%; padding:0.8rem; border-radius:8px; background:rgba(0,0,0,0.4); border:1px solid rgba(255,255,255,0.1); color:#fff;"/>
      </div>
      <div style="margin-bottom:1rem;">
        <label style="color:var(--muted); font-size:0.8rem; display:block; margin-bottom:5px;">Email</label>
        <input type="email" name="email" id="eu_email" style="width:100%; padding:0.8rem; border-radius:8px; background:rgba(0,0,0,0.4); border:1px solid rgba(255,255,255,0.1); color:#fff;"/>
      </div>
      <div style="margin-bottom:1.5rem;">
        <label style="color:var(--muted); font-size:0.8rem; display:block; margin-bottom:5px;">Address</label>
        <input type="text" name="address" id="eu_address" style="width:100%; padding:0.8rem; border-radius:8px; background:rgba(0,0,0,0.4); border:1px solid rgba(255,255,255,0.1); color:#fff;"/>
      </div>

      <div style="display:flex; justify-content:flex-end; gap:10px;">
        <button type="button" onclick="closeInlineUserEdit()" style="background:transparent; color:var(--muted); border:none; padding:0.8rem; cursor:pointer;">Cancel</button>
        <button type="submit" style="background:#38bdf8; color:#000; border:none; padding:0.8rem 1.5rem; border-radius:8px; font-weight:bold; cursor:pointer;">Save Profile</button>
      </div>
    </form>
  </div>
</div>

<!-- ========================================== -->
<!-- 4. Delete Choice Modal -->
<!-- ========================================== -->
<div id="deleteChoiceModal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.85); z-index:99999; align-items:center; justify-content:center; backdrop-filter:blur(10px);">
  <div style="background:#12131e; padding:2rem; border-radius:18px; width:90%; max-width:420px; border:1px solid rgba(239,68,68,0.3); box-shadow:0 20px 50px rgba(0,0,0,0.8);">
    <h3 style="color:#ef4444; margin-bottom:1rem; font-size:1.2rem; font-weight:800;">Destructive Action</h3>
    <p style="color:var(--muted); font-size:0.85rem; margin-bottom:1.5rem;">Choose how to delete User <strong id="delUserName" style="color:#fff;"></strong>.</p>
    
    <form method="POST">
      <input type="hidden" name="action" value="delete_user_shop"/>
      <input type="hidden" name="user_id" id="delUserId" value=""/>
      
      <div style="display:flex; flex-direction:column; gap:10px; margin-bottom:1.5rem;">
        <label style="background:rgba(255,255,255,0.05); padding:1rem; border-radius:10px; cursor:pointer; display:flex; align-items:center; gap:10px; border:1px solid rgba(255,255,255,0.1);">
          <input type="radio" name="delete_type" value="shop" checked style="accent-color:#ef4444;" id="delOptShop"/>
          <div>
            <div style="color:#fff; font-weight:bold; font-size:0.9rem;">Delete Shop Only</div>
            <div style="color:var(--muted); font-size:0.75rem; margin-top:2px;">User account remains intact.</div>
          </div>
        </label>
        <label style="background:rgba(239,68,68,0.1); padding:1rem; border-radius:10px; cursor:pointer; display:flex; align-items:center; gap:10px; border:1px solid rgba(239,68,68,0.3);">
          <input type="radio" name="delete_type" value="both" style="accent-color:#ef4444;"/>
          <div>
            <div style="color:#ef4444; font-weight:bold; font-size:0.9rem;">Delete User & Shop</div>
            <div style="color:var(--muted); font-size:0.75rem; margin-top:2px;">Permanent wipe of user and store.</div>
          </div>
        </label>
      </div>

      <div style="display:flex; justify-content:flex-end; gap:10px;">
        <button type="button" onclick="closeDeleteChoiceModal()" style="background:transparent; color:var(--muted); border:none; padding:0.8rem; cursor:pointer;">Cancel</button>
        <button type="submit" onclick="return confirm('Are you absolutely sure you want to proceed with deletion?');" style="background:#ef4444; color:#fff; border:none; padding:0.8rem 1.5rem; border-radius:8px; font-weight:bold; cursor:pointer;">Confirm Deletion</button>
      </div>
    </form>
  </div>
</div>

<!-- ========================================== -->
<!-- 5. Edit Ref Code Modal -->
<!-- ========================================== -->
<div id="refCodeModal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.85); z-index:99999; align-items:center; justify-content:center; backdrop-filter:blur(10px);">
  <div style="background:#12131e; padding:2rem; border-radius:18px; width:90%; max-width:400px; border:1px solid rgba(255,255,255,0.1);">
    <h3 style="color:#fff; margin-bottom:1.5rem;">Edit Referral Code</h3>
    <form method="POST">
      <input type="hidden" name="action" value="update_ref_code"/>
      <input type="hidden" name="user_id" id="refUserId" value=""/>
      
      <div style="margin-bottom:1.5rem;">
        <label style="color:var(--muted); font-size:0.8rem; display:block; margin-bottom:5px;">Unique Alphanumeric Code (3-15 chars)</label>
        <input type="text" name="ref_code" id="refCodeInput" minlength="3" maxlength="15" pattern="[a-zA-Z0-9]+" required style="width:100%; padding:0.8rem; border-radius:8px; background:rgba(0,0,0,0.4); border:1px solid rgba(255,255,255,0.1); color:var(--brand); font-weight:bold; text-transform:uppercase;"/>
      </div>

      <div style="display:flex; justify-content:flex-end; gap:10px;">
        <button type="button" onclick="closeRefCodeModal()" style="background:transparent; color:var(--muted); border:none; padding:0.8rem; cursor:pointer;">Cancel</button>
        <button type="submit" style="background:var(--brand); color:#fff; border:none; padding:0.8rem 1.5rem; border-radius:8px; font-weight:bold; cursor:pointer;">Save Code</button>
      </div>
    </form>
  </div>
</div>

<script>
function switchTab(e, tabId) {
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
  
  e.currentTarget.classList.add('active');
  const target = document.getElementById(tabId);
  if (target) target.classList.add('active');
}

function handleHashTab() {
  const hash = window.location.hash.replace('#', '');
  if (hash) {
    const btn = document.getElementById('btn-' + hash.replace('tab-', ''));
    if (btn) btn.click();
  }
}
window.addEventListener('DOMContentLoaded', handleHashTab);
window.addEventListener('hashchange', handleHashTab);

// 1. KYC MODAL
function openKycModal(user) {
  document.getElementById('modalKycUserId').value = user.id;
  document.getElementById('modalKycUserName').innerText = user.name + ' (#' + user.id + ')';
  document.getElementById('modalKycUserPhone').innerText = user.phone || 'N/A';
  document.getElementById('modalKycUserEmail').innerText = user.email || 'N/A';
  document.getElementById('modalKycUserAddress').innerText = (user.address || '') + (user.district ? ', ' + user.district : 'N/A');
  document.getElementById('modalKycNidInput').value = user.nid_number || '';
  
  const badge = document.getElementById('modalKycStatusBadge');
  const st = (user.kyc_status || 'pending').toLowerCase();
  badge.innerText = st;
  if (st === 'approved') {
    badge.style.background = 'rgba(16,185,129,0.2)'; badge.style.color = '#10b981'; badge.style.border = '1px solid #10b981';
  } else if (st === 'rejected') {
    badge.style.background = 'rgba(239,68,68,0.2)'; badge.style.color = '#ef4444'; badge.style.border = '1px solid #ef4444';
  } else {
    badge.style.background = 'rgba(245,158,11,0.2)'; badge.style.color = '#f59e0b'; badge.style.border = '1px solid #f59e0b';
  }

  const list = document.getElementById('modalKycDocsList');
  list.innerHTML = '';
  const docs = [
    { label: 'National ID', path: user.nid },
    { label: 'Passport', path: user.passport },
    { label: 'e-TIN', path: user.etin },
    { label: 'Driving License', path: user.driving_license },
    { label: 'NID Front', path: user.nid_front },
    { label: 'NID Back', path: user.nid_back },
    { label: 'Trade License', path: user.trade_license }
  ];

  let hasDoc = false;
  docs.forEach(d => {
    if (d.path) {
      hasDoc = true;
      const cleanPath = '/' + d.path.replace(/^\/+/, '');
      const item = document.createElement('div');
      item.innerHTML = `
        <div style="font-size:0.7rem; color:#94a3b8; margin-bottom:2px;">${d.label}</div>
        <a href="${cleanPath}" target="_blank">
          <img src="${cleanPath}" class="kyc-preview-img" alt="${d.label}" onerror="this.src='/assets/images/default_avatar.png';"/>
        </a>
      `;
      list.appendChild(item);
    }
  });

  if (!hasDoc) {
    list.innerHTML = '<span style="color:#94a3b8; font-size:0.8rem; font-style:italic;">No uploaded document attachments found.</span>';
  }
  document.getElementById('kycModal').style.display = 'flex';
}
function closeKycModal() { document.getElementById('kycModal').style.display = 'none'; }

// 2. COIN MODAL
function openCoinModal(id, name) {
  document.getElementById('coinUserId').value = id;
  document.getElementById('coinUserName').innerText = name;
  document.getElementById('coinModal').style.display = 'flex';
}
function closeCoinModal() { document.getElementById('coinModal').style.display = 'none'; }

// 3. EDIT CHOICE MODAL
let currentEditUser = {};
function openEditChoiceModal(id, shopId, name, phone, email, address) {
  currentEditUser = { id, name, phone, email, address };
  document.getElementById('editUserName').innerText = name;
  const link = document.getElementById('editShopLink');
  if (shopId) {
    link.href = 'shop_edit.php?id=' + shopId;
    link.style.display = 'flex';
  } else {
    link.style.display = 'none';
  }
  document.getElementById('editChoiceModal').style.display = 'flex';
}
function closeEditChoiceModal() { document.getElementById('editChoiceModal').style.display = 'none'; }

function openInlineUserEdit() {
  closeEditChoiceModal();
  document.getElementById('eu_id').value = currentEditUser.id;
  document.getElementById('eu_name').value = currentEditUser.name;
  document.getElementById('eu_phone').value = currentEditUser.phone;
  document.getElementById('eu_email').value = currentEditUser.email;
  document.getElementById('eu_address').value = currentEditUser.address;
  document.getElementById('inlineUserEditModal').style.display = 'flex';
}
function closeInlineUserEdit() { document.getElementById('inlineUserEditModal').style.display = 'none'; }

// 4. DELETE CHOICE MODAL
function openDeleteChoiceModal(id, shopId) {
  document.getElementById('delUserId').value = id;
  document.getElementById('delUserName').innerText = "#" + id;
  const optShop = document.getElementById('delOptShop');
  if (!shopId) {
    optShop.disabled = true;
    optShop.checked = false;
    document.querySelector('input[name="delete_type"][value="both"]').checked = true;
  } else {
    optShop.disabled = false;
    optShop.checked = true;
  }
  document.getElementById('deleteChoiceModal').style.display = 'flex';
}
function closeDeleteChoiceModal() { document.getElementById('deleteChoiceModal').style.display = 'none'; }

// 5. REF CODE MODAL
function openRefCodeModal(id, code) {
  document.getElementById('refUserId').value = id;
  document.getElementById('refCodeInput').value = code;
  document.getElementById('refCodeModal').style.display = 'flex';
}
function closeRefCodeModal() { document.getElementById('refCodeModal').style.display = 'none'; }

</script>

</body>
</html>
