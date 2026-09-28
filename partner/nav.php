<?php
// =========================================================================
// partner/nav.php  –  Partner Portal Navigation Component
// =========================================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header('Location: /user/login.php');
    exit;
}
require_once __DIR__ . '/../config.php';

// Fetch current user phone first
$ustmt = $pdo->prepare("SELECT phone FROM users WHERE id = :uid LIMIT 1");
$ustmt->execute([':uid' => $_SESSION['user_id']]);
$uPhone = $ustmt->fetchColumn();

$partner = null;
$session_partner_id = $_SESSION['partner_id'] ?? 0;
try {
    if ($session_partner_id > 0) {
        $stmt = $pdo->prepare("SELECT * FROM partners WHERE id = :pid LIMIT 1");
        $stmt->execute([':pid' => $session_partner_id]);
        $partner = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    if (!$partner && !empty($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("SELECT * FROM partners WHERE user_id = :uid ORDER BY id DESC LIMIT 1");
        $stmt->execute([':uid' => $_SESSION['user_id']]);
        $partner = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    if (!$partner && !empty($uPhone)) {
        $stmt = $pdo->prepare("SELECT * FROM partners WHERE phone = :phone ORDER BY id DESC LIMIT 1");
        $stmt->execute([':phone' => $uPhone]);
        $partner = $stmt->fetch(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {}

if (!$partner) {
    $partner = [
        'id' => 0, 
        'status' => 'pending', 
        'profile_pic' => '', 
        'business_name' => 'New Partner', 
        'owner_name' => 'Partner', 
        'user_id' => $_SESSION['user_id'] ?? 0, 
        'description' => '', 
        'phone' => '', 
        'email' => ''
    ];
}

if ($partner) {
    $_SESSION['partner_id'] = $partner['id'];
} else {
    $_SESSION['partner_id'] = 0;
}

// Phase 87 M2: Real-time pending orders counter for dock and drawer badges
if (!isset($partner_pending_orders_count)) {
    $partner_pending_orders_count = 0;
    if (!empty($partner['id'])) {
        try {
            $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM partner_orders WHERE partner_id = :pid AND status IN ('pending', 'accepted', 'in_progress', 'waiting_confirmation')");
            $stmtCount->execute([':pid' => (int)$partner['id']]);
            $partner_pending_orders_count = (int)$stmtCount->fetchColumn();
        } catch (Exception $e) {
            $partner_pending_orders_count = 0;
        }
    }
}

// Active page helper
$current_page = basename($_SERVER['PHP_SELF']);
if (!function_exists('isActive')) {
    function isActive($page, $current_page) {
        if (is_array($page)) {
            return in_array($current_page, $page, true) ? 'active' : '';
        }
        return $page === $current_page ? 'active' : '';
    }
}
?>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Oswald:wght@600;700&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="/assets/css/native_mobile.css?v=<?= time() ?>">
<link rel="stylesheet" href="/assets/css/mobile_responsive.css?v=<?= time() ?>">
<script src="/assets/js/global_loader.js"></script>
<style>
  :root {
    --brand: #fcb900;
    --brand-glow: rgba(252, 185, 0, 0.15);
    --dark: #08080c;
    --dark-card: rgba(20, 20, 31, 0.75);
    --border: rgba(252, 185, 0, 0.25);
    --text: #e8e8f0;
    --muted: #7777aa;
    --red: #ff5252;
    --green: #00e676;
    --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  }

  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    overflow-x: hidden;
    font-family: 'Inter', sans-serif;
    background: var(--dark);
    color: var(--text);
    min-height: 100vh;
    padding-top: calc(70px + env(safe-area-inset-top, 0px));
    padding-left: 280px; /* Space for desktop drawer */
    transition: var(--transition);
  }

  @media (max-width: 1024px) {
    body {
      padding-left: 0;
    }
  }

  /* Top Navbar (Google Stitch Nocturne Aurum) */
  .top-nav {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    height: calc(70px + env(safe-area-inset-top, 0px));
    background: rgba(10, 13, 26, 0.92);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-bottom: 1px solid rgba(245, 158, 11, 0.18);
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: env(safe-area-inset-top, 0px) 1.25rem 0;
    box-sizing: border-box;
  }

  .nav-left {
    display: flex;
    align-items: center;
    gap: 0.75rem;
  }

  /* Persistent Back Navigation Button */
  .nav-back-btn {
    width: 44px;
    height: 44px;
    min-width: 44px;
    min-height: 44px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.06);
    border: 1px solid rgba(255, 255, 255, 0.12);
    color: #fff;
    text-decoration: none;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    flex-shrink: 0;
    -webkit-tap-highlight-color: transparent;
  }
  .nav-back-btn:hover {
    background: rgba(255, 255, 255, 0.12);
    border-color: rgba(255, 255, 255, 0.25);
    transform: translateX(-2px);
  }
  .nav-back-btn:active {
    transform: scale(0.96);
  }

  .hamburger-btn {
    display: none;
    background: rgba(255, 255, 255, 0.06);
    border: 1px solid rgba(255, 255, 255, 0.12);
    color: var(--text);
    font-size: 1.3rem;
    cursor: pointer;
    width: 44px;
    height: 44px;
    min-width: 44px;
    min-height: 44px;
    border-radius: 10px;
    align-items: center;
    justify-content: center;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    -webkit-tap-highlight-color: transparent;
    flex-shrink: 0;
  }
  .hamburger-btn:hover {
    background: rgba(255, 255, 255, 0.12);
    border-color: rgba(255, 255, 255, 0.25);
  }
  .hamburger-btn:active {
    transform: scale(0.96);
  }

  @media (max-width: 1024px) {
    .hamburger-btn {
      display: inline-flex;
    }
  }

  .top-brand {
    font-family: 'Oswald', sans-serif;
    font-size: 1.5rem;
    font-weight: 700;
    background: linear-gradient(90deg, var(--brand), #ff9100);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    text-decoration: none;
    transition: var(--transition);
  }

  .nav-right {
    display: flex;
    align-items: center;
    gap: 0.75rem;
  }

  /* 1-Tap Buyer Mode Switcher Pill in Top Bar */
  .header-mode-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    background: rgba(33, 150, 243, 0.12);
    border: 1px solid rgba(56, 189, 248, 0.35);
    color: #38bdf8;
    font-size: 0.78rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    padding: 8px 14px;
    border-radius: 50px;
    text-decoration: none;
    min-width: 44px;
    min-height: 44px;
    box-sizing: border-box;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 2px 10px rgba(33, 150, 243, 0.15);
    -webkit-tap-highlight-color: transparent;
    cursor: pointer;
    flex-shrink: 0;
  }
  .header-mode-pill:hover {
    background: rgba(33, 150, 243, 0.22);
    border-color: rgba(56, 189, 248, 0.6);
    box-shadow: 0 4px 15px rgba(33, 150, 243, 0.3);
    color: #7dd3fc;
  }
  .header-mode-pill:active {
    transform: scale(0.96);
  }

  .mode-pill-text-mobile {
    display: none;
  }
  .mode-pill-text-desktop {
    display: inline;
  }

  /* App Hub Dropdown Button Touch Target */
  .app-hub-dropdown button {
    width: 44px;
    height: 44px;
    min-width: 44px;
    min-height: 44px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.06);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 10px;
    color: var(--text);
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    -webkit-tap-highlight-color: transparent;
    padding: 0;
    flex-shrink: 0;
  }
  .app-hub-dropdown button:hover {
    background: rgba(255, 255, 255, 0.12);
    border-color: rgba(255, 255, 255, 0.25);
  }
  .app-hub-dropdown button:active {
    transform: scale(0.96);
  }

  .partner-badge {
    background: var(--brand-glow);
    border: 1px solid var(--brand);
    color: var(--brand);
    font-size: 0.75rem;
    font-weight: 700;
    padding: 0.25rem 0.6rem;
    border-radius: 50px;
    text-transform: uppercase;
  }

  .partner-avatar {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid var(--brand);
  }

  /* Side Drawer */
  .side-drawer {
    position: fixed;
    top: calc(70px + env(safe-area-inset-top, 0px));
    left: 0;
    bottom: 0;
    width: 280px;
    background: var(--dark-card);
    border-right: 1px solid rgba(252, 185, 0, 0.15);
    backdrop-filter: blur(12px);
    z-index: 999;
    padding: 2rem 1rem;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    transition: var(--transition);
    overflow-y: auto;
    overscroll-behavior: contain;
  }

  @media (max-width: 1024px) {
    .side-drawer {
      left: -280px;
    }
    .side-drawer.open {
      left: 0;
      box-shadow: 0 0 30px rgba(0, 0, 0, 0.5);
    }
  }

  .drawer-profile {
    text-align: center;
    margin-bottom: 2rem;
  }

  .drawer-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid var(--brand);
    margin-bottom: 0.8rem;
    box-shadow: 0 5px 15px rgba(252, 185, 0, 0.2);
  }

  .drawer-shopname {
    font-size: 1.05rem;
    font-weight: 700;
    color: #fff;
    margin-bottom: 0.2rem;
  }

  .drawer-owner {
    font-size: 0.78rem;
    color: var(--muted);
  }

  /* Drawer Navigation Links */
  .drawer-links {
    display: flex;
    flex-direction: column;
    gap: 0.6rem;
  }

  .drawer-link {
    display: flex;
    align-items: center;
    gap: 0.9rem;
    color: var(--text);
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 500;
    padding: 0.8rem 1rem;
    border-radius: 12px;
    border: 1px solid transparent;
    transition: var(--transition);
  }

  .drawer-link:hover {
    background: rgba(252, 185, 0, 0.05);
    border-color: rgba(252, 185, 0, 0.15);
    color: var(--brand);
    transform: translateX(4px);
  }

  .drawer-link.active {
    background: linear-gradient(135deg, var(--brand), #ff9100);
    color: #000;
    font-weight: 700;
    box-shadow: 0 5px 15px rgba(252, 185, 0, 0.25);
  }

  .drawer-link.active:hover {
    transform: none;
    color: #000;
  }

  /* Drawer Overlay on Mobile */
  .drawer-overlay {
    display: none;
    position: fixed;
    top: 70px;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    backdrop-filter: blur(4px);
    z-index: 998;
  }

  @media (max-width: 1024px) {
    .drawer-overlay.open {
      display: block;
    }
  }

  /* ==========================================
     MOBILE APK FIXES — Prevent Overflow Breaks
     ========================================== */

  /* Force nav to never exceed screen width */
  .top-nav {
    max-width: 100vw !important;
    overflow: hidden !important;
  }

  /* Partner badge & mode pill responsive behavior on small screens */
  @media (max-width: 600px) {
    .partner-badge {
      display: none !important; /* Badge causes vertical overflow on small mobile */
    }
    .top-brand {
      font-size: 1.05rem !important;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      max-width: 140px;
    }
    .nav-left {
      gap: 0.45rem !important;
    }
    .nav-right {
      gap: 0.45rem !important;
    }
    .top-nav {
      padding: 0 0.65rem !important;
    }
    .mode-pill-text-desktop {
      display: none !important;
    }
    .mode-pill-text-mobile {
      display: inline !important;
      font-size: 0.75rem !important;
    }
    .header-mode-pill {
      padding: 6px 10px !important;
      font-size: 0.72rem !important;
      min-height: 44px !important;
      gap: 4px !important;
    }
    /* Hub dropdown — stay within screen on mobile */
    .hub-menu {
      right: 0 !important;
      left: auto !important;
      max-width: calc(100vw - 1rem) !important;
      width: 260px !important;
    }
  }

  @media (max-width: 380px) {
    .top-brand {
      max-width: 95px !important;
      font-size: 0.95rem !important;
    }
    .top-nav {
      padding: 0 0.4rem !important;
    }
  }

  /* Prevent any child of nav from causing horizontal scroll */
  * {
    box-sizing: border-box;
  }

  /* Content wrapper mobile enforcement */
  .content-wrapper {
    padding: 2rem;
    max-width: 1200px;
    margin: 0 auto;
    width: 100%;
    overflow-x: hidden;
  }

  @media (max-width: 768px) {
    .content-wrapper {
      padding: 1rem 0.85rem !important;
      width: 100% !important;
      max-width: 100vw !important;
      overflow-x: hidden !important;
    }
    /* Prevent stat cards from overflowing */
    .stat-cards-row,
    [style*="display:flex"],
    [style*="display: flex"] {
      flex-wrap: wrap !important;
      max-width: 100% !important;
    }
  }

  /* =======================================================================
     FastSite Partner Portal - Mobile Bottom Dock (Google Stitch Nocturne Aurum)
     ======================================================================= */
  .partner-bottom-dock {
    display: none;
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    height: calc(64px + env(safe-area-inset-bottom, 0px));
    background: rgba(10, 13, 26, 0.94) !important;
    backdrop-filter: blur(24px) !important;
    -webkit-backdrop-filter: blur(24px) !important;
    border-top: 1px solid rgba(245, 158, 11, 0.2) !important;
    z-index: 1000;
    box-shadow: 0 -8px 30px rgba(0, 0, 0, 0.6) !important;
    padding: 6px 8px calc(env(safe-area-inset-bottom, 0px) + 6px) !important;
    box-sizing: border-box !important;
  }

  @media (max-width: 900px) {
    .side-drawer {
      bottom: calc(64px + env(safe-area-inset-bottom, 0px)) !important;
    }
    .partner-bottom-dock {
      display: flex;
      justify-content: space-around;
      align-items: center;
    }
    body {
      padding-bottom: calc(76px + env(safe-area-inset-bottom, 0px)) !important;
    }
  }

  .dock-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    flex: 1;
    height: 100%;
    min-height: 44px;
    color: #8b94ee;
    text-decoration: none;
    font-size: 0.68rem;
    font-weight: 600;
    gap: 3px;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    padding: 4px 0;
    background: transparent;
    border: none;
    cursor: pointer;
    -webkit-tap-highlight-color: transparent;
  }

  .dock-item:active {
    transform: scale(0.96);
  }

  .dock-item span.icon {
    font-size: 1.15rem;
    line-height: 1;
  }

  .dock-item.active {
    color: #fcb900;
  }

  .dock-item.active span.icon {
    transform: scale(1.1);
  }

  .dock-item-primary {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    position: relative;
    top: -10px;
    background: linear-gradient(135deg, #fcb900, #ff9100);
    color: #0b0e14 !important;
    width: 48px;
    height: 48px;
    min-width: 48px;
    min-height: 48px;
    border-radius: 50%;
    box-shadow: 0 4px 14px rgba(252, 185, 0, 0.45);
    text-decoration: none;
    font-size: 1.35rem;
    font-weight: 800;
    border: 3px solid #0a0d1a;
    transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    -webkit-tap-highlight-color: transparent;
  }

  .dock-item-primary:active {
    transform: scale(0.92);
  }

  .dock-item-buyer {
    color: #38bdf8 !important;
  }
  .dock-item-buyer:hover,
  .dock-item-buyer:active {
    color: #7dd3fc !important;
  }

  .dock-badge-counter {
    position: absolute;
    top: -4px;
    right: -8px;
    background: #ff5252;
    color: #fff;
    font-size: 0.62rem;
    font-weight: 800;
    min-width: 17px;
    height: 17px;
    line-height: 17px;
    border-radius: 9999px;
    text-align: center;
    padding: 0 4px;
    box-shadow: 0 0 8px rgba(255, 82, 82, 0.6);
  }

  .drawer-badge-counter {
    background: #ff5252;
    color: #fff;
    font-size: 0.7rem;
    font-weight: 800;
    min-width: 20px;
    height: 20px;
    line-height: 20px;
    border-radius: 9999px;
    text-align: center;
    padding: 0 6px;
    box-shadow: 0 0 8px rgba(255, 82, 82, 0.5);
  }
</style>

<!-- Top Navbar (Google Stitch Nocturne Aurum Standards) -->
<nav class="top-nav">
  <div class="nav-left">
    <?php if ($current_page !== 'dashboard.php'): ?>
      <a href="dashboard.php" class="nav-back-btn" title="Back to Shop Overview" aria-label="Back to Shop Overview">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
      </a>
    <?php endif; ?>
    <button class="hamburger-btn" id="menu-toggle" aria-label="Open Navigation Menu">☰</button>
    <a href="dashboard.php" class="top-brand">⚡ FAST SITE SHOP</a>
  </div>
  <div class="nav-right">
    <!-- 1-Tap Buyer Mode Switcher Pill in Top Bar -->
    <a href="/user/dashboard.php" class="header-mode-pill" title="Switch to Buyer Mode">
      <span class="mode-pill-icon">👤</span>
      <span class="mode-pill-text-desktop">Switch to Buyer Mode</span>
      <span class="mode-pill-text-mobile">Buyer</span>
    </a>

    <!-- THE BRIDGE: Cross-Platform Hub -->
    <div style="position:relative;" class="app-hub-dropdown">
      <button type="button" onclick="document.getElementById('app-hub-menu').classList.toggle('show-hub')" title="App Hub" aria-label="App Hub">
        <svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor"><path d="M4 4h4v4H4V4zm6 0h4v4h-4V4zm6 0h4v4h-4V4zM4 10h4v4H4v-4zm6 0h4v4h-4v-4zm6 0h4v4h-4v-4zM4 16h4v4H4v-4zm6 0h4v4h-4v-4zm6 0h4v4h-4v-4z"/></svg>
      </button>
      <div id="app-hub-menu" class="hub-menu" style="display:none; position:absolute; right: -10px; top:45px; background:var(--dark-card); border:1px solid var(--border); border-radius:12px; padding:1rem; width:280px; max-width: 90vw; box-shadow:0 10px 30px rgba(0,0,0,0.9); z-index:9999; backdrop-filter:blur(10px);">
        <h4 style="color:var(--brand); font-size:0.85rem; text-transform:uppercase; margin-bottom:1rem; text-align:center; border-bottom:1px solid rgba(255,255,255,0.1); padding-bottom:0.5rem;">Cross-Platform Hub</h4>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.8rem;">
          <a href="/index.php" style="display:flex; flex-direction:column; align-items:center; text-decoration:none; padding:0.8rem; border-radius:8px; background:rgba(255,255,255,0.03); transition:background 0.2s;">
            <span style="font-size:1.5rem; margin-bottom:0.3rem;">🏪</span>
            <span style="color:var(--text); font-size:0.75rem; font-weight:600;">Storefront</span>
          </a>
          <a href="/user/dashboard.php" style="display:flex; flex-direction:column; align-items:center; text-decoration:none; padding:0.8rem; border-radius:8px; background:rgba(255,255,255,0.03); transition:background 0.2s;">
            <span style="font-size:1.5rem; margin-bottom:0.3rem;">👤</span>
            <span style="color:var(--text); font-size:0.75rem; font-weight:600;">User Panel</span>
          </a>
          <a href="/partner/dashboard.php" style="display:flex; flex-direction:column; align-items:center; text-decoration:none; padding:0.8rem; border-radius:8px; background:rgba(255,255,255,0.03); transition:background 0.2s;">
            <span style="font-size:1.5rem; margin-bottom:0.3rem;">💼</span>
            <span style="color:var(--text); font-size:0.75rem; font-weight:600;">Partner Hub</span>
          </a>
          <a href="/admin/index.php" style="display:flex; flex-direction:column; align-items:center; text-decoration:none; padding:0.8rem; border-radius:8px; background:rgba(255,255,255,0.03); transition:background 0.2s;">
            <span style="font-size:1.5rem; margin-bottom:0.3rem;">⚙️</span>
            <span style="color:var(--text); font-size:0.75rem; font-weight:600;">Admin</span>
          </a>
        </div>
      </div>
    </div>
    
    <span class="partner-badge"><?= htmlspecialchars($partner['status']) ?></span>
    <img src="<?= $partner['profile_pic'] ? '/uploads/partners/' . htmlspecialchars($partner['profile_pic']) : '/assets/img/fast site logo only.jpeg' ?>" alt="Logo" class="partner-avatar"/>
  </div>
</nav>

<script>
// The Bridge Hub click outside close
document.addEventListener('click', function(e) {
  const hubBtn = document.querySelector('.app-hub-dropdown button');
  const hubMenu = document.getElementById('app-hub-menu');
  if (hubBtn && hubMenu && !hubBtn.contains(e.target) && !hubMenu.contains(e.target)) {
    hubMenu.classList.remove('show-hub');
  }
});
</script>
<style>
.show-hub { display: block !important; }
.hub-menu a:hover { background: rgba(255,255,255,0.08) !important; transform: translateY(-2px); }
</style>

<!-- Side Drawer -->
<div class="side-drawer" id="side-drawer">
  <div>
    <div class="drawer-profile">
      <img src="<?= $partner['profile_pic'] ? '/uploads/partners/' . htmlspecialchars($partner['profile_pic']) : '/assets/img/fast site logo only.jpeg' ?>" alt="Logo" class="drawer-avatar"/>
      <div class="drawer-shopname"><?= htmlspecialchars($partner['business_name']) ?></div>
      <div class="drawer-owner">By <?= htmlspecialchars($partner['owner_name']) ?></div>
    </div>
    
    <div class="drawer-links">
      <!-- 1. Single Clean User Dashboard Switcher Link (Google Stitch Nocturne Aurum) -->
      <a href="/user/dashboard.php" class="drawer-link" style="background:rgba(33,150,243,0.12); border:1px solid rgba(56,189,248,0.35); color:#38bdf8; font-weight:800; margin-bottom:0.6rem; border-radius:12px; box-shadow:0 2px 10px rgba(33,150,243,0.12);">
        <span>👤</span> Switch to Buyer Mode
      </a>

      <a href="dashboard.php" class="drawer-link <?= isActive('dashboard.php', $current_page) ?>">
        <span>📊</span> Shop Overview
      </a>
      <a href="products.php" class="drawer-link <?= isActive(['products.php', 'product_add.php', 'product_edit.php'], $current_page) ?>">
        <span>🛍️</span> My Products
      </a>
      <a href="orders.php" class="drawer-link <?= isActive('orders.php', $current_page) ?>" style="display:flex; justify-content:space-between; align-items:center;">
        <span style="display:flex; align-items:center; gap:0.9rem;"><span>📦</span> Customer Orders</span>
        <?php if (!empty($partner_pending_orders_count) && $partner_pending_orders_count > 0): ?>
          <span class="drawer-badge-counter"><?= $partner_pending_orders_count > 99 ? '99+' : $partner_pending_orders_count ?></span>
        <?php endif; ?>
      </a>
      <a href="earnings.php" class="drawer-link <?= isActive(['earnings.php', 'withdraw.php'], $current_page) ?>">
        <span>💰</span> Shop Earnings
      </a>
      <a href="coupons.php" class="drawer-link <?= isActive('coupons.php', $current_page) ?>">
        <span>🎟️</span> Discounts &amp; Coupons
      </a>
      <a href="disputes.php" class="drawer-link <?= isActive('disputes.php', $current_page) ?>">
        <span>🛡️</span> Order Help &amp; Disputes
      </a>

      <a href="profile.php" class="drawer-link <?= isActive('profile.php', $current_page) ?>">
        <span>⚙️</span> Shop Settings
      </a>
      
      <div style="font-size:0.7rem; font-weight:800; color:var(--muted); text-transform:uppercase; letter-spacing:0.05em; padding: 1rem 1.5rem 0.5rem;">User Hub Tools</div>
      <a href="/user/wallet.php" class="drawer-link">
        <span>🪙</span> My Wallet
      </a>
      <a href="/user/deposit.php" class="drawer-link">
        <span>💳</span> Buy Points / Deposit
      </a>
      <a href="/user/messages.php" class="drawer-link">
        <span>✉️</span> Messages
      </a>
      <a href="/user/notifications.php" class="drawer-link">
        <span>🔔</span> Notifications
      </a>
    </div>
  </div>

  <div style="margin-top: auto;">
    <?php if (isset($_SESSION['is_impersonating']) && $_SESSION['is_impersonating']): ?>
      <a href="return_to_admin.php" class="drawer-link" style="border-color: rgba(252,185,0,0.3); color: var(--gold); background: rgba(252,185,0,0.1); margin-bottom: 0.5rem; justify-content: center; font-weight: 800; font-size: 0.9rem;">🚀 Return to Admin Panel</a>
    <?php endif; ?>
    <a href="logout.php" class="drawer-link" onclick="return confirm('Are you sure you want to log out?');" style="border-color: rgba(255,82,82,0.1); color: var(--red);">
      <span>🚪</span> Logout
    </a>
  </div>
</div>

<!-- Drawer Overlay -->
<div class="drawer-overlay" id="drawer-overlay"></div>

<!-- Mobile Bottom Navigation Dock (5-Slot Google Stitch Nocturne Aurum Standards) -->
<nav class="partner-bottom-dock" aria-label="Partner Mobile Dock">
  <!-- Slot 1: Hub -->
  <a href="dashboard.php" class="dock-item <?= isActive('dashboard.php', $current_page) ?>">
    <span class="icon">📊</span>
    <span>Hub</span>
  </a>

  <!-- Slot 2: Customer Orders with Live Badge -->
  <a href="orders.php" class="dock-item <?= isActive('orders.php', $current_page) ?>" style="position:relative;">
    <div style="position:relative; display:inline-flex;">
      <span class="icon">📦</span>
      <?php if (!empty($partner_pending_orders_count) && $partner_pending_orders_count > 0): ?>
        <span class="dock-badge-counter"><?= $partner_pending_orders_count > 99 ? '99+' : $partner_pending_orders_count ?></span>
      <?php endif; ?>
    </div>
    <span>Orders</span>
  </a>

  <!-- Slot 3: Center FAB - Add Product -->
  <a href="product_add.php" class="dock-item-primary <?= isActive('product_add.php', $current_page) ?>" title="Add Product" aria-label="Add Product">
    <span>➕</span>
  </a>

  <!-- Slot 4: My Products Catalog -->
  <a href="products.php" class="dock-item <?= isActive(['products.php', 'product_edit.php'], $current_page) ?>">
    <span class="icon">🛍️</span>
    <span>Catalog</span>
  </a>

  <!-- Slot 5: 1-Tap Buyer Mode Switcher -->
  <a href="/user/dashboard.php" class="dock-item dock-item-buyer" title="Switch to Buyer Mode">
    <span class="icon">👤</span>
    <span>Buyer Mode</span>
  </a>
</nav>

<script>
  const toggleBtn = document.getElementById('menu-toggle');
  const drawer = document.getElementById('side-drawer');
  const overlay = document.getElementById('drawer-overlay');

  function openNavDrawer() {
    if (drawer) drawer.classList.toggle('open');
    if (overlay) overlay.classList.toggle('open');
  }

  if (toggleBtn) {
    toggleBtn.addEventListener('click', openNavDrawer);
  }

  if (overlay) {
    overlay.addEventListener('click', () => {
      if (drawer) drawer.classList.remove('open');
      if (overlay) overlay.classList.remove('open');
    });
  }
</script>

<?php if (file_exists(__DIR__ . '/../includes/whatsapp_button.php')) { include_once __DIR__ . '/../includes/whatsapp_button.php'; } ?>
<script src="/assets/js/pull_to_refresh.js"></script>

