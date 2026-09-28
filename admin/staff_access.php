<?php
// =========================================================================
// admin/staff_access.php — Staff & Role Access Control Vault (Phase 36)
// =========================================================================
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/../config.php';

// Access strictly restricted to root admin role
if (($_SESSION['admin_role'] ?? 'staff') !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

$msg = $err = '';

// Available permission modules
$sections_list = [
    'dashboard' => [
        'title' => '📊 Dashboard & Analytics',
        'desc'  => 'Access orders tracker, real-time application processing, KPI stats & revenue charts.',
        'color' => '#10b981'
    ],
    'shops'     => [
        'title' => '🏪 Shops & Escrow Hub',
        'desc'  => 'Approve/suspend merchant stores, review shop requests & adjudicate escrow disputes.',
        'color' => '#fcb900'
    ],
    'users'     => [
        'title' => '👥 Customer Hub & KYC',
        'desc'  => 'Search all users, review KYC vault verifications, send broadcast alerts & check loyalty.',
        'color' => '#38bdf8'
    ],
    'wallet'    => [
        'title' => '💼 Master Wallet & Payouts',
        'desc'  => 'Global liquidity metrics, gift coins to users, and process bKash/Nagad withdrawals.',
        'color' => '#6366f1'
    ],
    'partners'  => [
        'title' => '🌐 Ecosystem Brands & API',
        'desc'  => 'Manage Affiliate Agents, API Developer Partners, and Omni-Network brand links.',
        'color' => '#ec4899'
    ],
    'chats'     => [
        'title' => '💬 Live Chat & Team Tasks',
        'desc'  => 'Live customer support chat widget, internal team messaging, and staff task assignments.',
        'color' => '#f59e0b'
    ],
    'settings'  => [
        'title' => '⚙️ Advanced System Settings',
        'desc'  => 'System branding, payment gateway toggles, SEO metadata, and marketplace configuration.',
        'color' => '#8b5cf6'
    ]
];

// Handle Permission Changes Save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // 1. Save Permissions
    if ($action === 'save_permissions') {
        $staffId = (int)$_POST['staff_id'];
        if ($staffId > 1) { // Root admin is immutable
            $selected = [];
            foreach (array_keys($sections_list) as $key) {
                if (isset($_POST['perm_' . $staffId . '_' . $key])) {
                    $selected[] = $key;
                }
            }
            $permsJson = json_encode($selected);
            $stmt = $pdo->prepare("UPDATE staff_users SET permissions = :perms WHERE id = :id");
            $stmt->execute([':perms' => $permsJson, ':id' => $staffId]);
            $msg = 'Staff permissions updated successfully!';
        }
    }

    // 2. Create New Staff Member
    if ($action === 'create_staff') {
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $role     = 'staff';

        if (empty($username) || empty($password)) {
            $err = 'Username and Password are required.';
        } else {
            // Check username uniqueness
            $chk = $pdo->prepare("SELECT id FROM staff_users WHERE username = :u LIMIT 1");
            $chk->execute([':u' => $username]);
            if ($chk->fetch()) {
                $err = "Username '$username' already exists.";
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $selected = [];
                foreach (array_keys($sections_list) as $key) {
                    if (isset($_POST['new_perm_' . $key])) {
                        $selected[] = $key;
                    }
                }
                $permsJson = json_encode($selected);
                $stmt = $pdo->prepare("INSERT INTO staff_users (username, email, password_hash, role, permissions, created_at) VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)");
                $stmt->execute([$username, $email ?: null, $hash, $role, $permsJson]);
                $msg = "New staff member '$username' created successfully!";
            }
        }
    }

    // 3. Reset Staff Password
    if ($action === 'reset_password') {
        $staffId = (int)$_POST['staff_id'];
        $newPass = trim($_POST['new_password'] ?? '');
        if ($staffId > 0 && !empty($newPass)) {
            $hash = password_hash($newPass, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE staff_users SET password_hash = :p WHERE id = :id");
            $stmt->execute([':p' => $hash, ':id' => $staffId]);
            $msg = 'Password reset successfully for staff member #' . $staffId . '!';
        } else {
            $err = 'Please enter a valid new password.';
        }
    }

    // 4. Delete Staff Member
    if ($action === 'delete_staff') {
        $staffId = (int)$_POST['staff_id'];
        if ($staffId > 1) { // Never delete root admin
            $pdo->prepare("DELETE FROM staff_users WHERE id = :id")->execute([':id' => $staffId]);
            $msg = 'Staff member removed from system.';
        } else {
            $err = 'Root Admin cannot be deleted!';
        }
    }
}

// Fetch all staff users
$staffList = [];
try {
    $staffList = $pdo->query("SELECT * FROM staff_users ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0"/>
  <title>Staff & Role Access Control — Fast Site Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Oswald:wght@600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="/assets/css/admin.css?v=<?= time() ?>"/>
  <style>
    .staff-card {
      background: rgba(16, 18, 28, 0.85);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 20px;
      padding: 1.8rem;
      margin-bottom: 2rem;
      box-shadow: 0 12px 35px rgba(0, 0, 0, 0.4);
      backdrop-filter: blur(16px);
      position: relative;
      overflow: hidden;
      transition: border-color 0.2s;
    }
    .staff-card.root-card {
      border: 1px solid rgba(252, 185, 0, 0.35);
      background: linear-gradient(135deg, rgba(252, 185, 0, 0.04) 0%, rgba(16, 18, 28, 0.9) 100%);
    }
    .staff-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 1rem;
      padding-bottom: 1.2rem;
      margin-bottom: 1.5rem;
      border-bottom: 1px solid rgba(255, 255, 255, 0.06);
    }
    .staff-info {
      display: flex;
      align-items: center;
      gap: 1rem;
    }
    .staff-avatar {
      width: 52px;
      height: 52px;
      border-radius: 14px;
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.1);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.6rem;
      font-weight: 800;
      color: #fff;
    }
    .staff-name {
      font-family: 'Oswald', sans-serif;
      font-size: 1.3rem;
      color: #fff;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    .role-badge {
      font-size: 0.68rem;
      font-weight: 800;
      padding: 3px 8px;
      border-radius: 6px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      font-family: 'Inter', sans-serif;
    }
    .role-root {
      background: rgba(252, 185, 0, 0.2);
      color: #fcb900;
      border: 1px solid #fcb900;
    }
    .role-staff {
      background: rgba(99, 102, 241, 0.2);
      color: #818cf8;
      border: 1px solid #6366f1;
    }

    .preset-bar {
      display: flex;
      gap: 0.5rem;
      align-items: center;
      flex-wrap: wrap;
      margin-bottom: 1.2rem;
      background: rgba(0, 0, 0, 0.25);
      padding: 0.6rem 1rem;
      border-radius: 12px;
      border: 1px solid rgba(255, 255, 255, 0.04);
    }
    .preset-btn {
      background: rgba(255, 255, 255, 0.06);
      border: 1px solid rgba(255, 255, 255, 0.1);
      color: #cbd5e1;
      padding: 0.35rem 0.8rem;
      border-radius: 8px;
      font-size: 0.75rem;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.2s;
    }
    .preset-btn:hover {
      background: rgba(252, 185, 0, 0.15);
      color: #fcb900;
      border-color: #fcb900;
    }

    .perms-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 1rem;
      margin-bottom: 1.5rem;
    }
    .perm-toggle-card {
      background: rgba(0, 0, 0, 0.3);
      border: 1px solid rgba(255, 255, 255, 0.05);
      border-radius: 14px;
      padding: 1.1rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 1rem;
      transition: all 0.2s;
      cursor: pointer;
    }
    .perm-toggle-card:hover {
      background: rgba(255, 255, 255, 0.03);
      border-color: rgba(255, 255, 255, 0.12);
    }
    .perm-toggle-card.active {
      background: rgba(99, 102, 241, 0.08);
      border-color: rgba(99, 102, 241, 0.4);
    }
    .perm-text {
      flex: 1;
    }
    .perm-title {
      font-size: 0.9rem;
      font-weight: 700;
      color: #fff;
      margin-bottom: 0.25rem;
    }
    .perm-desc {
      font-size: 0.75rem;
      color: #94a3b8;
      line-height: 1.35;
    }

    /* iOS Switch */
    .switch {
      position: relative;
      display: inline-block;
      width: 44px;
      height: 24px;
      flex-shrink: 0;
    }
    .switch input {
      opacity: 0;
      width: 0;
      height: 0;
    }
    .slider {
      position: absolute;
      cursor: pointer;
      inset: 0;
      background-color: rgba(255, 255, 255, 0.15);
      transition: .3s;
      border-radius: 24px;
    }
    .slider:before {
      position: absolute;
      content: "";
      height: 18px;
      width: 18px;
      left: 3px;
      bottom: 3px;
      background-color: white;
      transition: .3s;
      border-radius: 50%;
    }
    input:checked + .slider {
      background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    }
    input:checked + .slider:before {
      transform: translateX(20px);
    }
    input:disabled + .slider {
      background: #fcb900 !important;
      opacity: 0.8;
      cursor: not-allowed;
    }

    .staff-footer {
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 1rem;
      padding-top: 1.2rem;
      border-top: 1px solid rgba(255, 255, 255, 0.06);
    }

    /* Collapsible Drawer for Add Staff */
    .add-staff-panel {
      display: none;
      background: rgba(20, 24, 38, 0.95);
      border: 1px solid rgba(99, 102, 241, 0.3);
      border-radius: 18px;
      padding: 1.8rem;
      margin-bottom: 2rem;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
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
        <h2 class="admin-hero-title">🔑 STAFF & ROLE ACCESS CONTROL</h2>
        <p class="admin-hero-subtitle">Manage admin team members, role privileges, and fine-grained module access</p>
      </div>
      <div style="display:flex; gap:0.6rem; flex-wrap:wrap;">
        <button onclick="toggleAddStaffDrawer()" class="btn-sm" style="display:inline-flex; align-items:center; gap:0.4rem; padding: 0.6rem 1.3rem; background: linear-gradient(135deg, var(--gold) 0%, #f59e0b 100%); color:#000; font-weight:800; border:none; border-radius:10px; cursor:pointer;">
          ➕ Add New Staff Member
        </button>
        <a href="give_task_staff.php" class="btn-sm" style="display:inline-flex; align-items:center; gap:0.4rem; padding: 0.6rem 1.2rem; background: rgba(255, 255, 255, 0.08); border:1px solid rgba(255, 255, 255, 0.15); color:#fff; font-weight:700; text-decoration:none; border-radius:10px;">
          📋 Assign Staff Tasks ➔
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

  <?php if ($err): ?>
    <div style="background:rgba(239,68,68,0.15); border:1px solid #ef4444; color:#ef4444; padding:0.9rem 1.3rem; border-radius:12px; margin-bottom:1.5rem; font-weight:700; display:flex; align-items:center; gap:8px;">
      ⚠️ <?= htmlspecialchars($err) ?>
    </div>
  <?php endif; ?>

  <!-- Add New Staff Drawer -->
  <div id="add-staff-drawer" class="add-staff-panel">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
      <h3 style="font-family:'Oswald',sans-serif; color:#fff; font-size:1.3rem; margin:0; display:flex; align-items:center; gap:0.5rem;">
        ➕ Create New Staff Account
      </h3>
      <button onclick="toggleAddStaffDrawer()" style="background:transparent; border:none; color:#94a3b8; font-size:1.2rem; cursor:pointer;">✕</button>
    </div>

    <form method="POST">
      <input type="hidden" name="action" value="create_staff"/>
      <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:1.2rem; margin-bottom:1.5rem;">
        <div>
          <label style="display:block; font-size:0.75rem; font-weight:800; color:var(--muted); text-transform:uppercase; margin-bottom:0.4rem;">Username *</label>
          <input type="text" name="username" required placeholder="e.g. JohnStaff" style="width:100%; background:rgba(0,0,0,0.5); border:1px solid rgba(255,255,255,0.12); padding:0.75rem 1rem; border-radius:10px; color:#fff; font-size:0.9rem; outline:none;"/>
        </div>
        <div>
          <label style="display:block; font-size:0.75rem; font-weight:800; color:var(--muted); text-transform:uppercase; margin-bottom:0.4rem;">Email Address</label>
          <input type="email" name="email" placeholder="staff@fastsite.com" style="width:100%; background:rgba(0,0,0,0.5); border:1px solid rgba(255,255,255,0.12); padding:0.75rem 1rem; border-radius:10px; color:#fff; font-size:0.9rem; outline:none;"/>
        </div>
        <div>
          <label style="display:block; font-size:0.75rem; font-weight:800; color:var(--muted); text-transform:uppercase; margin-bottom:0.4rem;">Initial Password *</label>
          <input type="password" name="password" required placeholder="••••••••" style="width:100%; background:rgba(0,0,0,0.5); border:1px solid rgba(255,255,255,0.12); padding:0.75rem 1rem; border-radius:10px; color:#fff; font-size:0.9rem; outline:none;"/>
        </div>
      </div>

      <div style="font-size:0.8rem; font-weight:800; color:var(--gold); text-transform:uppercase; letter-spacing:0.5px; margin-bottom:0.8rem;">Initial Module Permissions:</div>
      <div class="perms-grid">
        <?php foreach ($sections_list as $key => $info): ?>
          <label class="perm-toggle-card">
            <div class="perm-text">
              <div class="perm-title"><?= $info['title'] ?></div>
              <div class="perm-desc"><?= $info['desc'] ?></div>
            </div>
            <div class="switch">
              <input type="checkbox" name="new_perm_<?= $key ?>" value="1" checked/>
              <span class="slider"></span>
            </div>
          </label>
        <?php endforeach; ?>
      </div>

      <div style="text-align:right;">
        <button type="submit" class="btn" style="background:linear-gradient(135deg, #10b981 0%, #059669 100%); color:#fff; font-weight:800; padding:0.8rem 2rem; border-radius:10px; border:none; cursor:pointer; font-size:0.95rem;">
          🚀 Create Staff Account
        </button>
      </div>
    </form>
  </div>

  <!-- Staff Members List -->
  <?php foreach ($staffList as $st): ?>
    <?php 
      $isRoot = ((int)$st['id'] === 1) || ($st['role'] === 'admin');
      
      // Parse permissions
      $assigned = [];
      if ($isRoot) {
        $assigned = array_keys($sections_list);
      } else {
        $rawPerms = $st['permissions'] ?? '';
        $decoded = json_decode($rawPerms, true);
        if (is_array($decoded)) {
          $assigned = $decoded;
        } elseif (!empty($rawPerms)) {
          $assigned = array_filter(array_map('trim', explode(',', $rawPerms)));
        }
      }
    ?>

    <div class="staff-card <?= $isRoot ? 'root-card' : '' ?>" id="staff-card-<?= $st['id'] ?>">
      
      <!-- Staff Header -->
      <div class="staff-header">
        <div class="staff-info">
          <div class="staff-avatar" style="<?= $isRoot ? 'border-color:#fcb900; background:rgba(252,185,0,0.1); color:#fcb900;' : '' ?>">
            <?= strtoupper(substr($st['username'], 0, 1)) ?>
          </div>
          <div>
            <div class="staff-name">
              <?= htmlspecialchars($st['username']) ?>
              <?php if ($isRoot): ?>
                <span class="role-badge role-root">👑 ROOT ADMIN</span>
              <?php else: ?>
                <span class="role-badge role-staff">STAFF MEMBER</span>
              <?php endif; ?>
            </div>
            <div style="font-size:0.8rem; color:#94a3b8; margin-top:2px;">
              <?= htmlspecialchars($st['email'] ?: 'No email registered') ?> • Added <?= date('M d, Y', strtotime($st['created_at'] ?? 'now')) ?>
            </div>
          </div>
        </div>

        <div>
          <?php if ($isRoot): ?>
            <div style="font-size:0.8rem; color:#fcb900; font-weight:700; display:flex; align-items:center; gap:5px;">
              ✨ Full System Privileges
            </div>
          <?php else: ?>
            <button type="button" onclick="openResetPassModal(<?= $st['id'] ?>, '<?= htmlspecialchars($st['username']) ?>')" style="background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.12); color:#cbd5e1; padding:0.45rem 0.9rem; border-radius:8px; font-size:0.75rem; font-weight:700; cursor:pointer; margin-right:0.4rem;">
              🔑 Reset Password
            </button>
            <form method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete staff member <?= htmlspecialchars($st['username']) ?>?');">
              <input type="hidden" name="action" value="delete_staff"/>
              <input type="hidden" name="staff_id" value="<?= $st['id'] ?>"/>
              <button type="submit" style="background:rgba(239,68,68,0.12); border:1px solid rgba(239,68,68,0.3); color:#ef4444; padding:0.45rem 0.9rem; border-radius:8px; font-size:0.75rem; font-weight:700; cursor:pointer;">
                🗑️ Delete
              </button>
            </form>
          <?php endif; ?>
        </div>
      </div>

      <!-- Quick Preset Selector for Staff -->
      <?php if (!$isRoot): ?>
        <div class="preset-bar">
          <span style="font-size:0.75rem; font-weight:800; color:var(--gold); text-transform:uppercase; margin-right:0.4rem;">Quick Presets:</span>
          <button type="button" class="preset-btn" onclick="applyPreset(<?= $st['id'] ?>, 'all')">⚡ All Modules</button>
          <button type="button" class="preset-btn" onclick="applyPreset(<?= $st['id'] ?>, 'support')">🎧 Customer Support</button>
          <button type="button" class="preset-btn" onclick="applyPreset(<?= $st['id'] ?>, 'finance')">💳 Finance & Payouts</button>
          <button type="button" class="preset-btn" onclick="applyPreset(<?= $st['id'] ?>, 'shops')">🛒 Shop Moderator</button>
          <button type="button" class="preset-btn" onclick="applyPreset(<?= $st['id'] ?>, 'none')">❌ Clear All</button>
        </div>
      <?php endif; ?>

      <!-- Permission Form -->
      <form method="POST">
        <input type="hidden" name="action" value="save_permissions"/>
        <input type="hidden" name="staff_id" value="<?= $st['id'] ?>"/>

        <div class="perms-grid">
          <?php foreach ($sections_list as $key => $info): ?>
            <?php 
              $isChecked = in_array($key, $assigned) ? 'checked' : '';
              $isDisabled = $isRoot ? 'disabled' : '';
            ?>
            <label class="perm-toggle-card <?= $isChecked ? 'active' : '' ?>">
              <div class="perm-text">
                <div class="perm-title"><?= $info['title'] ?></div>
                <div class="perm-desc"><?= $info['desc'] ?></div>
              </div>
              <div class="switch">
                <input type="checkbox" id="perm_<?= $st['id'] ?>_<?= $key ?>" name="perm_<?= $st['id'] ?>_<?= $key ?>" value="1" <?= $isChecked ?> <?= $isDisabled ?>/>
                <span class="slider"></span>
              </div>
            </label>
          <?php endforeach; ?>
        </div>

        <div class="staff-footer">
          <?php if ($isRoot): ?>
            <div style="font-size:0.8rem; color:#94a3b8;">
              👑 Primary owner has permanent, unrestricted access to all modules and root system configurations.
            </div>
          <?php else: ?>
            <div style="font-size:0.8rem; color:#94a3b8;">
              Changes take effect immediately upon saving.
            </div>
            <button type="submit" class="btn" style="background:linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); color:#fff; font-weight:800; padding:0.7rem 1.6rem; border-radius:10px; border:none; cursor:pointer; font-size:0.88rem; box-shadow:0 4px 15px rgba(99,102,241,0.3);">
              💾 Save Permissions for <?= htmlspecialchars($st['username']) ?>
            </button>
          <?php endif; ?>
        </div>
      </form>

    </div>
  <?php endforeach; ?>

</div>

<!-- Reset Password Modal -->
<div id="reset-pass-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.75); z-index:9999; align-items:center; justify-content:center; backdrop-filter:blur(6px);">
  <div style="background:#121422; border:1px solid rgba(255,255,255,0.12); border-radius:18px; padding:2rem; width:90%; max-width:400px; box-shadow:0 20px 50px rgba(0,0,0,0.8);">
    <h3 style="color:#fff; font-family:'Oswald',sans-serif; margin:0 0 0.5rem 0;" id="modal-staff-title">🔑 Reset Password</h3>
    <p style="color:#94a3b8; font-size:0.85rem; margin-bottom:1.5rem;">Enter a new secure password for this staff member.</p>
    
    <form method="POST">
      <input type="hidden" name="action" value="reset_password"/>
      <input type="hidden" name="staff_id" id="modal-staff-id" value=""/>
      
      <div style="margin-bottom:1.5rem;">
        <label style="display:block; font-size:0.75rem; font-weight:800; color:var(--muted); text-transform:uppercase; margin-bottom:0.4rem;">New Password</label>
        <input type="password" name="new_password" required placeholder="••••••••" style="width:100%; background:rgba(0,0,0,0.5); border:1px solid rgba(255,255,255,0.12); padding:0.75rem 1rem; border-radius:10px; color:#fff; font-size:0.9rem; outline:none;"/>
      </div>
      
      <div style="display:flex; justify-content:flex-end; gap:0.6rem;">
        <button type="button" onclick="closeResetPassModal()" style="background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.1); color:#cbd5e1; padding:0.6rem 1.2rem; border-radius:8px; font-weight:700; cursor:pointer;">Cancel</button>
        <button type="submit" style="background:linear-gradient(135deg, var(--gold) 0%, #f59e0b 100%); border:none; color:#000; padding:0.6rem 1.4rem; border-radius:8px; font-weight:800; cursor:pointer;">Update Password</button>
      </div>
    </form>
  </div>
</div>

<script>
function toggleAddStaffDrawer() {
  const drawer = document.getElementById('add-staff-drawer');
  drawer.style.display = (drawer.style.display === 'block') ? 'none' : 'block';
  if (drawer.style.display === 'block') {
    drawer.scrollIntoView({ behavior: 'smooth' });
  }
}

function openResetPassModal(staffId, username) {
  document.getElementById('modal-staff-id').value = staffId;
  document.getElementById('modal-staff-title').innerText = '🔑 Reset Password for ' + username;
  document.getElementById('reset-pass-modal').style.display = 'flex';
}

function closeResetPassModal() {
  document.getElementById('reset-pass-modal').style.display = 'none';
}

function applyPreset(staffId, preset) {
  const allKeys = ['dashboard', 'shops', 'users', 'wallet', 'partners', 'chats', 'settings'];
  let targetKeys = [];
  
  if (preset === 'all') {
    targetKeys = allKeys;
  } else if (preset === 'support') {
    targetKeys = ['dashboard', 'users', 'chats'];
  } else if (preset === 'finance') {
    targetKeys = ['dashboard', 'wallet', 'shops'];
  } else if (preset === 'shops') {
    targetKeys = ['dashboard', 'shops', 'partners'];
  } else if (preset === 'none') {
    targetKeys = [];
  }

  allKeys.forEach(k => {
    const el = document.getElementById('perm_' + staffId + '_' + k);
    if (el) {
      el.checked = targetKeys.includes(k);
      const card = el.closest('.perm-toggle-card');
      if (card) {
        if (el.checked) card.classList.add('active');
        else card.classList.remove('active');
      }
    }
  });
}

// Attach change listeners to update card active borders
document.querySelectorAll('.perm-toggle-card input[type="checkbox"]').forEach(cb => {
  cb.addEventListener('change', function() {
    const card = this.closest('.perm-toggle-card');
    if (card) {
      if (this.checked) card.classList.add('active');
      else card.classList.remove('active');
    }
  });
});
</script>

</body>
</html>
