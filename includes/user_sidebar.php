<?php
// includes/user_sidebar.php - Unified Navigation for User & Partner Portals
?>
<!-- Premium Global Loader -->
<style>
#fast-site-global-loader {
    position: fixed;
    top: 0; left: 0; width: 100vw; height: 100vh;
    background: #080911;
    z-index: 999999;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    transition: opacity 0.6s cubic-bezier(0.8, 0, 0.2, 1), visibility 0.6s;
}
#fast-site-global-loader.hide {
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
}
.loader-brand {
    position: relative;
    width: 80px;
    height: 80px;
    margin-bottom: 20px;
}
.loader-spinner {
    position: absolute;
    top: 0; left: 0; width: 100%; height: 100%;
    border: 3px solid rgba(252, 185, 0, 0.1);
    border-top-color: #fcb900;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}
.loader-logo-inner {
    position: absolute;
    top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    font-size: 2rem;
    font-weight: 900;
    font-family: 'Oswald', sans-serif;
    color: #fff;
    text-shadow: 0 0 20px rgba(252, 185, 0, 0.5);
    letter-spacing: -1px;
}
.loader-text {
    font-family: 'Inter', sans-serif;
    font-weight: 800;
    font-size: 1.2rem;
    color: #fff;
    letter-spacing: 2px;
    text-transform: uppercase;
    background: linear-gradient(90deg, #fff, #fcb900, #fff);
    background-size: 200% auto;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    animation: shine 2s linear infinite;
}
@keyframes spin { 100% { transform: rotate(360deg); } }
@keyframes shine { to { background-position: 200% center; } }
</style>
<div id="fast-site-global-loader">
    <div class="loader-brand">
        <div class="loader-spinner"></div>
        <div class="loader-logo-inner">FS</div>
    </div>
    <div class="loader-text">Loading...</div>
</div>
<script>
window.addEventListener('load', function() {
    const loader = document.getElementById('fast-site-global-loader');
    if (loader) {
        setTimeout(() => {
            loader.classList.add('hide');
        }, 300);
    }
});
</script>
<?php
// includes/user_sidebar.php
// A globally included top-nav and sidebar for the User Panel.

$currentPage = basename($_SERVER['PHP_SELF']);

// ─── 1. SELF-CONTAINED USER SHOP STATE HELPER (Phase 87 M1) ───
if (!function_exists('getUserShopState')) {
    function getUserShopState($pdo, $userId, $userPhone = ''): array {
        $result = [
            'state'     => 'none', // 'approved' | 'pending' | 'none'
            'shop'      => null,
            'request'   => null,
            'shop_name' => 'My Shop',
            'shop_id'   => 0
        ];

        $userId = (int)$userId;
        if ($userId <= 0 || !$pdo) {
            return $result;
        }

        if (empty($userPhone)) {
            try {
                $st_u = $pdo->prepare("SELECT phone FROM users WHERE id = ? LIMIT 1");
                $st_u->execute([$userId]);
                $userPhone = (string)($st_u->fetchColumn() ?: '');
            } catch (Exception $e) {}
        }

        try {
            // 1. Check partners table
            $st_p = $pdo->prepare("SELECT * FROM partners WHERE (user_id = :uid OR (phone = :p AND :p != '')) AND (status IS NULL OR status != 'suspended') ORDER BY id DESC LIMIT 1");
            $st_p->execute([':uid' => $userId, ':p' => $userPhone]);
            $partner = $st_p->fetch(PDO::FETCH_ASSOC);

            if ($partner) {
                $result['shop'] = $partner;
                $result['shop_name'] = !empty($partner['business_name']) ? $partner['business_name'] : 'My Shop';
                $result['shop_id'] = (int)($partner['id'] ?? 0);

                $status = strtolower(trim($partner['status'] ?? ''));
                if ($status === 'approved' || $status === 'active' || empty($status)) {
                    $result['state'] = 'approved';
                    return $result;
                } elseif ($status === 'pending') {
                    $result['state'] = 'pending';
                    return $result;
                }
            }

            // 2. Check partner_requests table
            $st_r = $pdo->prepare("SELECT * FROM partner_requests WHERE user_id = :uid ORDER BY id DESC LIMIT 1");
            $st_r->execute([':uid' => $userId]);
            $req = $st_r->fetch(PDO::FETCH_ASSOC);

            if ($req) {
                $result['request'] = $req;
                $reqStatus = strtolower(trim($req['status'] ?? ''));
                if ($reqStatus === 'pending') {
                    $result['state'] = 'pending';
                    return $result;
                } elseif ($reqStatus === 'approved') {
                    $result['state'] = 'approved';
                    return $result;
                }
            }
        } catch (Exception $e) {}

        return $result;
    }
}

$user_app_orders_count = 0;
$partner_shop_orders_count = 0;
$user_unread_notif_count = 0;
$shopStateData = [
    'state' => 'none',
    'shop' => null,
    'request' => null,
    'shop_name' => 'My Shop',
    'shop_id' => 0
];
$shopState = 'none';

if (isset($pdo) && isset($_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];
    $uPhone = $user['phone'] ?? '';

    $shopStateData = getUserShopState($pdo, $uid, $uPhone);
    $shopState = $shopStateData['state'];

    if (!isset($partnerInfo) || empty($partnerInfo)) {
        $partnerInfo = $shopStateData['shop'];
    }
    if (!isset($isPartner)) {
        $isPartner = ($shopState === 'approved');
    }

    try {
        $st_ao = $pdo->prepare("SELECT COUNT(*) FROM applications a JOIN users u ON a.user_phone = u.phone WHERE u.id = ? AND a.status IN ('pending', 'processing', 'review', 'in_progress')");
        $st_ao->execute([$uid]);
        $user_app_orders_count = (int)$st_ao->fetchColumn();
    } catch(Exception $e) {
        try {
            $st_ao2 = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ? AND status IN ('pending', 'processing', 'review', 'in_progress')");
            $st_ao2->execute([$uid]);
            $user_app_orders_count = (int)$st_ao2->fetchColumn();
        } catch(Exception $e2) {}
    }

    try {
        $st_un = $pdo->prepare("SELECT COUNT(*) FROM user_notifications WHERE user_id = ? AND is_read = 0");
        $st_un->execute([$uid]);
        $user_unread_notif_count = (int)$st_un->fetchColumn();
    } catch(Exception $e) {}

    try {
        if (!empty($partnerInfo['id'])) {
            $st_po = $pdo->prepare("SELECT COUNT(*) FROM partner_orders WHERE partner_id = ? AND status != 'completed'");
            $st_po->execute([$partnerInfo['id']]);
            $partner_shop_orders_count = (int)$st_po->fetchColumn();
        }
    } catch(Exception $e) {}
}
?>
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');

:root {
  --brand: #2196F3;
  --brand-glow: rgba(33, 150, 243, 0.15);
  --dark: #08080c;
  --dark-card: rgba(20, 20, 31, 0.85);
  --border: rgba(33, 150, 243, 0.25);
  --text: #f8fafc;
  --muted: #94a3b8;
  --red: #ff5252;
  --green: #00e676;
  --gold: #fcb900;
  --surface: rgba(13, 13, 20, 0.95);
  --sidebar-width: 280px;
}

body {
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
  background: #08080c !important;
  background-image: 
    radial-gradient(at 0% 0%, rgba(33, 150, 243, 0.12) 0px, transparent 50%),
    radial-gradient(at 100% 0%, rgba(252, 185, 0, 0.1) 0px, transparent 50%),
    radial-gradient(at 50% 100%, rgba(0, 230, 118, 0.08) 0px, transparent 50%) !important;
  background-attachment: fixed !important;
  color: #f8fafc !important;
}

/* User Sidebar & Top Nav */
.top-nav {
  position: fixed;
  top: 0; left: 0; right: 0;
  height: calc(60px + env(safe-area-inset-top, 0px));
  background: rgba(10, 13, 26, 0.95) !important;
  backdrop-filter: blur(20px);
  border-bottom: 1px solid var(--border);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: env(safe-area-inset-top, 0px) 1rem 0;
  box-sizing: border-box;
  z-index: 1000;
}
.hamburger {
  background: none !important; border: none !important; padding: 0 !important;
  width: 44px !important; height: 44px !important; min-width: 44px !important; min-height: 44px !important; cursor: pointer; color: var(--text) !important;
  fill: currentColor; box-shadow: none !important;
}
.sidebar {
  position: fixed;
  top: 0; left: -300px;
  width: 280px; height: 100vh;
  background: rgba(13, 13, 20, 0.98) !important;
  backdrop-filter: blur(20px);
  border-right: 1px solid var(--border);
  z-index: 1001;
  transition: left 0.3s ease;
  display: flex; flex-direction: column;
  overflow-y: auto;
  color: var(--text) !important;
  transition: left 0.35s cubic-bezier(0.4, 0, 0.2, 1) !important;
  box-shadow: 5px 0 25px rgba(0,0,0,0.5);
}
.sidebar.active { left: 0 !important; }
.sidebar-overlay {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(0,0,0,0.6);
  backdrop-filter: blur(4px);
  z-index: 1000;
  opacity: 0; pointer-events: none;
  transition: opacity 0.3s ease;
}
.sidebar-overlay.active { opacity: 1; pointer-events: all; }
.sidebar-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 1rem; border-bottom: 1px solid rgba(255,255,255,0.08);
}
.close-btn {
  background: none !important; border: none !important; font-size: 1.5rem !important; 
  color: var(--muted) !important; padding: 0 !important; width: 44px !important; height: 44px !important; min-width: 44px !important; min-height: 44px !important; cursor: pointer; box-shadow: none !important; display: inline-flex !important; align-items: center !important; justify-content: center !important;
}
.sidebar-item {
  display: block; padding: 0.85rem 1.5rem; color: #94a3b8 !important; text-decoration: none;
  font-weight: 600; font-size: 0.9rem; transition: 0.2s; border-bottom: 1px solid rgba(255,255,255,0.03);
  background: none !important; border: none !important; text-align: left; width: 100%; cursor: pointer;
  box-shadow: none !important; border-radius: 0 !important;
}
.sidebar-item:hover, .sidebar-item.active {
  background: rgba(33, 150, 243, 0.15) !important; color: #2196F3 !important;
}
.nav-logo {
  position: relative !important;
  left: auto !important;
  transform: none !important;
  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;
  gap: 8px !important;
  text-decoration: none !important;
  font-size: 1.3rem;
  color: #2196F3 !important;
  font-weight: 800;
  letter-spacing: 0.02em;
}
.nav-logo img {
  height: 40px;
  max-height: 44px;
  width: auto;
  object-fit: contain;
  filter: drop-shadow(0 3px 10px rgba(33, 150, 243, 0.6));
}

.top-nav {
  position: fixed;
  top: 0; left: 0; right: 0;
  height: calc(60px + env(safe-area-inset-top, 0px));
  background: rgba(10, 13, 26, 0.95) !important;
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  border-bottom: 1px solid var(--border);
  display: flex !important;
  align-items: center !important;
  justify-content: space-between !important;
  padding: env(safe-area-inset-top, 0px) 1.2rem 0 !important;
  box-sizing: border-box !important;
  z-index: 1000;
}

@media (max-width: 650px) {
  .nav-logo-text-user {
    font-size: 0.95rem !important;
    letter-spacing: 0.5px !important;
    display: inline-block !important;
  }
  .nav-logo img {
    height: 34px !important;
    max-height: 36px !important;
  }
  .top-nav {
    padding: 0 0.8rem !important;
  }
}
@media (min-width: 1025px) {
  .hamburger { display: flex !important; }
  .sidebar { left: -300px; }
  .sidebar.active { left: 0 !important; }
  .sidebar-overlay { display: none; }
  .top-nav { left: 0; transition: left 0.35s ease; }
  body.sidebar-open .top-nav { left: 280px; }
  body.dashboard-mode { padding-left: 0; transition: padding-left 0.35s ease; }
  body.dashboard-mode.sidebar-open { padding-left: 280px; }
}
</style>
<div class="top-nav">
  <?php
    $u_logo_img = !empty($settings['logo_url']) ? '/' . ltrim($settings['logo_url'], '/') : '/assets/images/logo.png';
  ?>
  <!-- Left Slot: Hamburger + 1-Tap Mode Switcher Pill -->
  <div style="flex:1; display:flex; align-items:center; justify-content:flex-start; gap:8px;">
    <button class="hamburger" onclick="toggleSidebar()" title="Toggle Dashboard Hub" style="width:44px; height:44px; min-width:44px; min-height:44px; display:inline-flex; align-items:center; justify-content:center; border-radius:8px; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); cursor:pointer;">
      <svg width="22" height="22" viewBox="0 0 24 24" style="width:22px; height:22px; fill:currentColor;"><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/></svg>
    </button>
    <?php if ($shopState === 'approved'): ?>
      <a href="/partner/dashboard.php" class="top-mode-pill mode-approved" title="Switch to Shop Mode">
        <span class="pill-icon">🏪</span>
        <span class="pill-text-desktop">Switch to Shop Mode</span>
        <span class="pill-text-mobile">Shop</span>
        <span class="pill-arrow">⇄</span>
      </a>
    <?php elseif ($shopState === 'pending'): ?>
      <button type="button" onclick="openShopReviewModal()" class="top-mode-pill mode-pending" title="Shop Application Under Review">
        <span class="pill-icon">⏳</span>
        <span class="pill-text-desktop">Shop Under Review</span>
        <span class="pill-text-mobile">Review</span>
      </button>
    <?php else: ?>
      <button type="button" onclick="openQuickShopDrawer()" class="top-mode-pill mode-shopless" title="Open Your Free Shop">
        <span class="pill-icon">➕</span>
        <span class="pill-text-desktop">Open Free Shop</span>
        <span class="pill-text-mobile">Free Shop</span>
      </button>
    <?php endif; ?>
  </div>

  <!-- Center Slot: Centered Logo & Fast Site Wordmark -->
  <a href="/index.php" class="nav-logo" style="flex-shrink:0;">
    <img src="<?= htmlspecialchars($u_logo_img) ?>" alt="Fast Site Logo" style="height:36px; max-height:40px; width:auto; filter:drop-shadow(0 2px 8px rgba(33,150,243,0.5));" onerror="this.onerror=null; this.src='/assets/images/logo.png';">
    <span class="nav-logo-text-user" style="font-family:'Oswald',sans-serif; font-size:1.15rem; font-weight:900; letter-spacing:1px; background:linear-gradient(135deg, #ffffff 0%, #2196F3 50%, #fcb900 100%); -webkit-background-clip:text; -webkit-text-fill-color:transparent; text-transform:uppercase;">
      <?= htmlspecialchars((!empty($siteName) && $siteName !== 'Fast Sitee') ? $siteName : 'FAST SITE') ?>
    </span>
  </a>
  
  <!-- Right Slot: Notification Bell + Messages with 44px Touch Targets -->
  <div style="flex:1; display:flex; align-items:center; justify-content:flex-end; gap:6px;">
      <button onclick="toggleNotificationDrawer()" style="position:relative; width:44px; height:44px; min-width:44px; min-height:44px; display:inline-flex; align-items:center; justify-content:center; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); border-radius:8px; color:var(--text); cursor:pointer; padding:0;" title="Notifications">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor">
          <path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.63-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2zm-2 1H8v-6c0-2.48 1.51-4.5 4-4.5s4 2.02 4 4.5v6z"/>
        </svg>
        <?php if ($user_unread_notif_count > 0): ?>
          <span style="position:absolute; top:6px; right:6px; background:var(--red); width:8px; height:8px; border-radius:50%; box-shadow:0 0 6px var(--red);"></span>
        <?php endif; ?>
      </button>
      <a href="/user/messages.php" style="width:44px; height:44px; min-width:44px; min-height:44px; display:inline-flex; align-items:center; justify-content:center; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); border-radius:8px; color:var(--text); text-decoration:none;" title="Messages">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
      </a>
  </div>
</div>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeAllDrawers()"></div>
<div class="sidebar" id="sidebarMenu">
  <div class="sidebar-header" style="margin-bottom: 0;">
    <div style="display:flex; align-items:center; gap:10px;">
      <img src="<?= htmlspecialchars($u_logo_img) ?>" alt="Logo" style="height:34px; width:auto; filter:drop-shadow(0 2px 6px rgba(33,150,243,0.4));" onerror="this.onerror=null; this.src='/assets/images/logo.png';">
      <h3 style="color:#fff; margin:0; font-size:1.2rem; font-family:'Oswald',sans-serif; letter-spacing:0.5px; background:linear-gradient(90deg, #2196F3, #fcb900); -webkit-background-clip:text; -webkit-text-fill-color:transparent;">Dashboard Hub</h3>
    </div>
    <button class="close-btn" onclick="toggleSidebar()">&times;</button>
  </div>
  
  <!-- 1. PROFILE SECTION (Clicking directs cleanly to /user/profile.php) -->
  <div style="display:flex; align-items:center; gap:12px; padding: 1rem; border-bottom: 1px solid rgba(255,255,255,0.06); background:rgba(255,255,255,0.02);">
      <a href="/user/profile.php" style="width:44px; height:44px; border-radius:50%; border: 2px solid var(--gold); overflow:hidden; flex-shrink:0; background:#0e0e16; display:flex; align-items:center; justify-content:center; text-decoration:none;" title="Edit Profile">
          <?php 
            $user_pic = !empty($user['profile_pic']) ? '/' . ltrim($user['profile_pic'], '/') : '/assets/images/default_avatar.png';
          ?>
          <img src="<?= htmlspecialchars($user_pic) ?>" alt="" style="width:100%; height:100%; object-fit:cover;" onerror="this.onerror=null; this.src='/assets/images/default_avatar.png';">
      </a>
      <div style="text-align:left; flex:1; min-width:0;">
          <h4 style="color:#fff; margin-bottom:2px; font-size:0.95rem; font-weight:700; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= htmlspecialchars($user['name'] ?? 'User') ?></h4>
          <a href="/user/profile.php" class="edit-profile-link" style="color:var(--gold); font-size:0.75rem; text-decoration:none; font-weight:600; display:inline-flex; align-items:center; gap:4px; margin-top:2px;">
              Edit Profile ✏️
          </a>
      </div>
  </div>
  
  <div style="padding: 0.5rem 0;">
      <!-- 2. STORE & ORDERS -->
      <div class="sidebar-header" style="padding: 0.6rem 1rem 0.3rem; border-bottom: none;">
        <span style="font-size:0.7rem; font-weight:800; color:var(--muted); text-transform:uppercase; letter-spacing:0.05em;">Store &amp; Orders</span>
      </div>
      <a href="/index.php" class="sidebar-item">🏪 Storefront Home</a>

      <?php if ($currentPage !== 'dashboard.php'): ?>
          <a href="/user/dashboard.php" class="sidebar-item">💎 User Dashboard</a>
      <?php endif; ?>

      <?php if ($currentPage === 'dashboard.php'): ?>
          <button class="sidebar-item" onclick="switchTab(event, 'tab-orders'); toggleSidebar();">📦 My Orders <?= ($user_app_orders_count > 0) ? '(' . $user_app_orders_count . ')' : '' ?></button>
      <?php else: ?>
          <a href="/user/dashboard.php?tab=orders" class="sidebar-item">📦 My Orders <?= ($user_app_orders_count > 0) ? '(' . $user_app_orders_count . ')' : '' ?></a>
      <?php endif; ?>

      <?php if ($shopState === 'approved'): ?>
          <a href="/partner/dashboard.php" class="sidebar-item" style="color:var(--gold); font-weight:700;">⚡ <?= htmlspecialchars($partnerInfo['business_name'] ?? $shopStateData['shop_name']) ?></a>
          <a href="/partner/dashboard.php?tab=upload" class="sidebar-item" style="color:#00e676; font-weight: 800; background: rgba(0,230,118,0.05);">➕ Add New Product</a>
      <?php elseif ($shopState === 'pending'): ?>
          <button type="button" class="sidebar-item" onclick="openShopReviewModal(); toggleSidebar();" style="color:#f59e0b; background:rgba(245,158,11,0.08); border:none; width:100%; text-align:left; cursor:pointer; font-weight:700; display:flex; align-items:center; gap:8px;">⏳ Store (Under Review)</button>
      <?php else: ?>
          <button type="button" class="sidebar-item" onclick="openQuickShopDrawer(); toggleSidebar();" style="color:var(--gold); background:none; border:none; width:100%; text-align:left; cursor:pointer; font-weight:700; display:flex; align-items:center; gap:8px;">➕ Open a Free Shop</button>
      <?php endif; ?>
      
      <?php if ($partner_shop_orders_count > 0): ?>
          <a href="/user/partner_orders.php" class="sidebar-item <?= ($currentPage === 'partner_orders.php') ? 'active' : '' ?>">🛍️ Store Orders (<?= $partner_shop_orders_count ?>)</a>
      <?php endif; ?>

      <!-- 3. REWARDS & INVITES -->
      <div class="sidebar-header" style="margin-top: 0.8rem; padding: 0.6rem 1rem 0.3rem; border-bottom: none;">
        <span style="font-size:0.7rem; font-weight:800; color:var(--muted); text-transform:uppercase; letter-spacing:0.05em;">Rewards &amp; Invites</span>
      </div>

      <a href="/user/tasks.php" class="sidebar-item <?= ($currentPage === 'tasks.php') ? 'active' : '' ?>" style="color:var(--gold); font-weight:700;">🎯 Daily Tasks &amp; Bonus Points</a>
      <a href="/user/missions.php" class="sidebar-item <?= ($currentPage === 'missions.php') ? 'active' : '' ?>">🏆 Loyalty Rewards &amp; Missions</a>
      <?php if ($currentPage === 'dashboard.php'): ?>
          <button class="sidebar-item" onclick="switchTab(event, 'tab-affiliate'); toggleSidebar();">🤝 Rewards &amp; Invites</button>
      <?php else: ?>
          <a href="/user/dashboard.php?tab=affiliate" class="sidebar-item">🤝 Rewards &amp; Invites</a>
      <?php endif; ?>

      <!-- 4. WALLET & CASH OUT -->
      <div class="sidebar-header" style="margin-top: 0.8rem; padding: 0.6rem 1rem 0.3rem; border-bottom: none;">
        <span style="font-size:0.7rem; font-weight:800; color:var(--muted); text-transform:uppercase; letter-spacing:0.05em;">Wallet &amp; Cash Out</span>
      </div>
      <a href="/user/wallet.php" class="sidebar-item <?= ($currentPage === 'wallet.php') ? 'active' : '' ?>">🪙 Available Balance &amp; Wallet</a>
      <a href="/user/wallet.php?action=withdraw" class="sidebar-item">💸 Cash Out / Withdraw Money</a>

      <!-- 5. SUPPORT & SETTINGS -->
      <div class="sidebar-header" style="margin-top: 0.8rem; padding: 0.6rem 1rem 0.3rem; border-bottom: none;">
        <span style="font-size:0.7rem; font-weight:800; color:var(--muted); text-transform:uppercase; letter-spacing:0.05em;">Support &amp; Settings</span>
      </div>
      <a href="/user/messages.php" class="sidebar-item <?= ($currentPage === 'messages.php') ? 'active' : '' ?>">✉️ Messages &amp; Chat</a>
      
      <?php if ($currentPage === 'dashboard.php'): ?>
          <button class="sidebar-item" onclick="switchTab(event, 'tab-support'); toggleSidebar();">💬 Support Center</button>
          <button class="sidebar-item" onclick="switchTab(event, 'tab-settings'); toggleSidebar();">⚙️ Account Settings</button>
      <?php else: ?>
          <a href="/user/dashboard.php?tab=support" class="sidebar-item">💬 Support Center</a>
          <a href="/user/dashboard.php?tab=settings" class="sidebar-item">⚙️ Account Settings</a>
      <?php endif; ?>
      <a href="/fastsite_storefront.apk" class="sidebar-item" style="background:rgba(16,185,129,0.1); border-left:3px solid #10b981; color:#fff !important; font-weight:700; display:flex; align-items:center; gap:10px; margin:8px 0;" download>
          <img src="/assets/images/fast_site_world_app_icon.jpg" style="width:22px; height:22px; border-radius:5px; object-fit:cover; border:1px solid rgba(252,185,0,0.4);" alt="APK" onerror="this.onerror=null; this.src='/assets/images/logo.png';"/>
          <span>FAST SITE WORLD (Android App)</span>
      </a>
  </div>
  
  <div style="margin-top: auto; padding: 1.5rem;">
    <a href="/user/logout.php" style="display: block; text-align: center; background: rgba(255,82,82,0.1); color: #ff5252; text-decoration: none; padding: 0.8rem; border-radius: 8px; font-weight: 700; border: 1px solid rgba(255,82,82,0.2);">Logout</a>
  </div>
</div>

<!-- Notification Drawer -->
<div id="notification-drawer" style="position:fixed; top:0; right:-350px; width:320px; height:100vh; background:rgba(13,13,20,0.98); backdrop-filter:blur(20px); border-left:1px solid rgba(255,255,255,0.08); z-index:2000; transition:right 0.3s ease; display:flex; flex-direction:column; box-shadow:-5px 0 25px rgba(0,0,0,0.5);">
    <div style="padding:1.2rem; border-bottom:1px solid rgba(255,255,255,0.08); display:flex; justify-content:space-between; align-items:center;">
        <h3 style="margin:0; font-size:1.1rem; color:#fff; display:flex; align-items:center; gap:8px;">🔔 Notifications</h3>
        <button onclick="toggleNotificationDrawer()" style="background:none; border:none; color:var(--muted); font-size:1.5rem; cursor:pointer; width:44px; height:44px; min-width:44px; min-height:44px; display:inline-flex; align-items:center; justify-content:center; padding:0;" title="Close Notifications">&times;</button>
    </div>
    <div id="notification-content" style="flex:1; overflow-y:auto; padding:1rem;">
        <!-- Loaded via AJAX -->
        <div style="text-align:center; padding:2rem; color:var(--muted);">Loading...</div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════ -->
<!-- GOOGLE STITCH NOCTURNE AURUM: SHOP REVIEW MODAL (#shopReviewModal) -->
<!-- ══════════════════════════════════════════════════════════ -->
<div class="bottom-sheet-backdrop" id="shopReviewBackdrop" onclick="closeShopReviewModal(event)"></div>
<div class="bottom-sheet" id="shopReviewModal" onclick="event.stopPropagation()">
  <div class="bottom-sheet-handle"></div>
  <div style="text-align:center; padding: 0.2rem 0 0.8rem;">
    <div style="font-size:2.8rem; line-height:1; margin-bottom:0.4rem;" class="pulse-gold">⏳</div>
    <h3 style="margin:0 0 0.4rem 0; font-size:1.3rem; color:#fff; font-family:'Inter',sans-serif; font-weight:800;">Shop Application Under Review</h3>
    <div style="display:inline-block; background:rgba(245,158,11,0.15); border:1px solid rgba(245,158,11,0.4); color:#f59e0b; font-size:0.75rem; font-weight:800; padding:4px 14px; border-radius:50px; text-transform:uppercase; letter-spacing:0.04em;">
      Status: Pending Admin Approval
    </div>
  </div>

  <p style="color:var(--muted); font-size:0.85rem; text-align:center; line-height:1.5; margin:0 0 1.2rem 0;">
    Your store application for <strong style="color:#fff;"><?= htmlspecialchars($shopStateData['shop_name']) ?></strong> has been received and is currently being verified by our compliance team.
  </p>

  <!-- Details Card -->
  <div style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08); border-radius:14px; padding:1rem 1.2rem; margin-bottom:1.2rem; font-size:0.85rem;">
    <div style="display:flex; justify-content:space-between; margin-bottom:0.6rem;">
      <span style="color:var(--muted);">Store Name:</span>
      <strong style="color:#fff;">⚡ <?= htmlspecialchars($shopStateData['shop_name']) ?></strong>
    </div>
    <div style="display:flex; justify-content:space-between; margin-bottom:0.6rem;">
      <span style="color:var(--muted);">Tracking ID:</span>
      <strong style="font-family:monospace; color:var(--gold); font-size:0.9rem;"><?= htmlspecialchars($shopStateData['shop']['registration_number'] ?? ('FS-APP-' . str_pad((string)($_SESSION['user_id'] ?? 0), 5, '0', STR_PAD_LEFT))) ?></strong>
    </div>
    <div style="display:flex; justify-content:space-between;">
      <span style="color:var(--muted);">Estimated Review SLA:</span>
      <strong style="color:#10b981; font-weight:800;">24 – 48 Hours</strong>
    </div>
  </div>

  <!-- 3-Step Progress Stepper -->
  <div style="display:flex; flex-direction:column; gap:12px; margin-bottom:1.4rem; padding:0 0.2rem;">
    <div class="stepper-item">
      <div class="stepper-num step-done">✓</div>
      <div>
        <div style="color:#fff; font-size:0.85rem; font-weight:700;">1. Application Submitted</div>
        <div style="color:var(--muted); font-size:0.75rem;">Store profile &amp; seller credentials recorded</div>
      </div>
    </div>
    <div class="stepper-item">
      <div class="stepper-num step-active">⏳</div>
      <div>
        <div style="color:#f59e0b; font-size:0.85rem; font-weight:700;">2. Security &amp; Compliance Review (Current)</div>
        <div style="color:var(--muted); font-size:0.75rem;">Admin compliance team is verifying account safety</div>
      </div>
    </div>
    <div class="stepper-item" style="opacity:0.5;">
      <div class="stepper-num step-pending">3</div>
      <div>
        <div style="color:#fff; font-size:0.85rem; font-weight:700;">3. Storefront Live &amp; Selling Active</div>
        <div style="color:var(--muted); font-size:0.75rem;">Instant product uploads &amp; SafePay guaranteed payouts</div>
      </div>
    </div>
  </div>

  <!-- WhatsApp Escalation & Actions -->
  <?php
    $waText = urlencode("Hello Fast Site Support, I submitted a shop application for '" . $shopStateData['shop_name'] . "' (ID: " . ($shopStateData['shop']['registration_number'] ?? ('FS-APP-' . ($_SESSION['user_id'] ?? 0))) . "). Please help check review status.");
  ?>
  <a href="https://wa.me/8801963601472?text=<?= $waText ?>" target="_blank" class="btn" style="display:flex; align-items:center; justify-content:center; gap:8px; width:100%; min-height:44px; background:#25D366; color:#fff; font-weight:800; padding:0.85rem; border-radius:12px; text-decoration:none; font-size:0.9rem; margin-bottom:0.7rem; box-shadow:0 4px 15px rgba(37,211,102,0.3); box-sizing:border-box;">
    <span>💬</span> WhatsApp Priority Review Support
  </a>
  <button type="button" onclick="closeShopReviewModal()" style="width:100%; min-height:44px; background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.12); color:#fff; padding:0.75rem; border-radius:12px; font-size:0.85rem; font-weight:700; cursor:pointer; box-sizing:border-box;">
    Close
  </button>
</div>

<!-- ══════════════════════════════════════════════════════════ -->
<!-- GOOGLE STITCH NOCTURNE AURUM: QUICK SHOP SETUP DRAWER (#quickShopDrawer) -->
<!-- ══════════════════════════════════════════════════════════ -->
<div class="bottom-sheet-backdrop" id="quickShopBackdrop" onclick="closeQuickShopDrawer(event)"></div>
<div class="bottom-sheet" id="quickShopDrawer" onclick="event.stopPropagation()">
  <div class="bottom-sheet-handle"></div>
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.8rem;">
    <h3 style="margin:0; font-size:1.25rem; color:#fff; display:flex; align-items:center; gap:8px; font-weight:800;">
      <span>🏪</span> Open Your Free Shop
    </h3>
    <button type="button" onclick="closeQuickShopDrawer()" style="background:none; border:none; color:var(--muted); font-size:1.6rem; cursor:pointer; width:44px; height:44px; min-width:44px; min-height:44px; display:inline-flex; align-items:center; justify-content:center; padding:0;" title="Close">&times;</button>
  </div>
  <p style="color:var(--muted); font-size:0.85rem; margin-top:0; margin-bottom:1.2rem; line-height:1.4;">
    Setup your digital storefront in 30 seconds. Start selling directly on Fast Site with SafePay Buyer Guarantee and zero upfront fee.
  </p>

  <form action="/user/create_shop.php" method="POST">
    <input type="hidden" name="owner_name" value="<?= htmlspecialchars($user['name'] ?? 'Shop Owner') ?>">
    <input type="hidden" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>">
    <input type="hidden" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
    <input type="hidden" name="accept_terms" value="1">
    <input type="hidden" name="payout_method" value="bkash">

    <div style="margin-bottom:1rem;">
      <label style="display:block; font-size:0.75rem; font-weight:800; color:var(--muted); text-transform:uppercase; letter-spacing:0.04em; margin-bottom:6px;">Shop / Business Name *</label>
      <input type="text" name="business_name" required placeholder="e.g. Dhaka Digital Mart" style="width:100%; min-height:44px; background:rgba(10,13,26,0.9); border:1px solid rgba(255,255,255,0.18); color:#fff; padding:0.8rem 1rem; border-radius:12px; font-size:0.92rem; outline:none; box-sizing:border-box;">
    </div>

    <div style="margin-bottom:1rem;">
      <label style="display:block; font-size:0.75rem; font-weight:800; color:var(--muted); text-transform:uppercase; letter-spacing:0.04em; margin-bottom:6px;">Store Category *</label>
      <select name="description" style="width:100%; min-height:44px; background:rgba(10,13,26,0.9); border:1px solid rgba(255,255,255,0.18); color:#fff; padding:0.8rem 1rem; border-radius:12px; font-size:0.92rem; outline:none; cursor:pointer; box-sizing:border-box;">
        <option value="Retail & E-commerce Products">🛍️ Retail &amp; E-commerce</option>
        <option value="Digital Services & Software">💻 Digital Services &amp; Software</option>
        <option value="Fashion & Apparel">👗 Fashion &amp; Apparel</option>
        <option value="Electronics & Gadgets">📱 Electronics &amp; Gadgets</option>
        <option value="Official Citizen Services">🏛️ Official Services</option>
      </select>
    </div>

    <div style="margin-bottom:1.2rem;">
      <label style="display:block; font-size:0.75rem; font-weight:800; color:var(--muted); text-transform:uppercase; letter-spacing:0.04em; margin-bottom:6px;">bKash / Nagad Payout Number</label>
      <input type="text" name="payout_account" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="01XXXXXXXXX" style="width:100%; min-height:44px; background:rgba(10,13,26,0.9); border:1px solid rgba(255,255,255,0.18); color:#fff; padding:0.8rem 1rem; border-radius:12px; font-size:0.92rem; outline:none; box-sizing:border-box;">
    </div>

    <div style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08); padding:0.85rem; border-radius:12px; font-size:0.78rem; color:var(--muted); margin-bottom:1.2rem; line-height:1.5;">
      👤 Owner: <strong style="color:#fff;"><?= htmlspecialchars($user['name'] ?? 'User') ?></strong> &bull; 📞 Phone: <strong style="color:#fff;"><?= htmlspecialchars($user['phone'] ?? 'N/A') ?></strong> &bull; 🛡️ SafePay: <strong style="color:var(--gold);">100% Protected (সুরক্ষিত)</strong>
    </div>

    <button type="submit" class="btn" style="width:100%; min-height:48px; background:linear-gradient(135deg, #f59e0b, #d97706); color:#000; font-weight:900; font-size:1rem; padding:0.9rem; border-radius:50px; border:none; cursor:pointer; box-shadow:0 6px 20px rgba(245,158,11,0.35); box-sizing:border-box; transition:transform 0.15s ease;">
      🚀 Launch My Free Shop
    </button>

    <div style="text-align:center; margin-top:1rem;">
      <a href="/user/create_shop.php" style="color:var(--muted); font-size:0.78rem; text-decoration:underline; min-height:44px; display:inline-flex; align-items:center;">
        Advanced setup (Upload NID &amp; Trade License) ➔
      </a>
    </div>
  </form>
</div>

<script>
  function closeAllDrawers() {
    const sidebar = document.getElementById('sidebarMenu');
    const drawer = document.getElementById('notification-drawer');
    const overlay = document.getElementById('sidebarOverlay');
    const reviewModal = document.getElementById('shopReviewModal');
    const reviewBackdrop = document.getElementById('shopReviewBackdrop');
    const quickDrawer = document.getElementById('quickShopDrawer');
    const quickBackdrop = document.getElementById('quickShopBackdrop');

    if (sidebar) sidebar.classList.remove('active');
    if (drawer) drawer.style.right = '-350px';
    if (overlay) overlay.classList.remove('active');
    if (reviewModal) reviewModal.classList.remove('active');
    if (reviewBackdrop) reviewBackdrop.classList.remove('active');
    if (quickDrawer) quickDrawer.classList.remove('active');
    if (quickBackdrop) quickBackdrop.classList.remove('active');
    document.body.classList.remove('sidebar-open');
  }

  function toggleSidebar() {
    const sidebar = document.getElementById('sidebarMenu');
    const notifDrawer = document.getElementById('notification-drawer');
    const overlay = document.getElementById('sidebarOverlay');
    if (notifDrawer && notifDrawer.style.right === '0px') {
        notifDrawer.style.right = '-350px';
    }
    const willBeActive = !sidebar.classList.contains('active');
    if (willBeActive) {
        sidebar.classList.add('active');
        overlay.classList.add('active');
        document.body.classList.add('sidebar-open');
    } else {
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
        document.body.classList.remove('sidebar-open');
    }
  }

  function toggleNotificationDrawer() {
    const drawer = document.getElementById('notification-drawer');
    const sidebar = document.getElementById('sidebarMenu');
    const overlay = document.getElementById('sidebarOverlay');
    
    if (sidebar && sidebar.classList.contains('active')) {
        sidebar.classList.remove('active');
        document.body.classList.remove('sidebar-open');
    }

    if (drawer.style.right === '0px') {
        drawer.style.right = '-350px';
        overlay.classList.remove('active');
    } else {
        drawer.style.right = '0px';
        overlay.classList.add('active');
        // Fetch notifications
        fetch('/api/get_notifications.php')
            .then(res => res.text())
            .then(html => {
                const notifBox = document.getElementById('notification-content');
                if (notifBox) notifBox.innerHTML = html;
            }).catch(() => {});
    }
  }

  function openShopReviewModal() {
    closeAllDrawers();
    const modal = document.getElementById('shopReviewModal');
    const backdrop = document.getElementById('shopReviewBackdrop');
    if (modal) modal.classList.add('active');
    if (backdrop) backdrop.classList.add('active');
  }

  function closeShopReviewModal(e) {
    if (e && e.target && e.target.closest && e.target.closest('#shopReviewModal') && !e.target.closest('button')) {
      return;
    }
    const modal = document.getElementById('shopReviewModal');
    const backdrop = document.getElementById('shopReviewBackdrop');
    if (modal) modal.classList.remove('active');
    if (backdrop) backdrop.classList.remove('active');
  }

  function openQuickShopDrawer() {
    closeAllDrawers();
    const drawer = document.getElementById('quickShopDrawer');
    const backdrop = document.getElementById('quickShopBackdrop');
    if (drawer) drawer.classList.add('active');
    if (backdrop) backdrop.classList.add('active');
  }

  function closeQuickShopDrawer(e) {
    if (e && e.target && e.target.closest && e.target.closest('#quickShopDrawer') && !e.target.closest('button[onclick*="closeQuickShopDrawer"]')) {
      return;
    }
    const drawer = document.getElementById('quickShopDrawer');
    const backdrop = document.getElementById('quickShopBackdrop');
    if (drawer) drawer.classList.remove('active');
    if (backdrop) backdrop.classList.remove('active');
  }

  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      closeAllDrawers();
    }
  });
</script>
<script src="/assets/js/pull_to_refresh.js"></script>
