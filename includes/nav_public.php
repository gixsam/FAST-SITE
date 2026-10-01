<?php
// Securely get user data
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
        // Slight delay for premium feel
        setTimeout(() => {
            loader.classList.add('hide');
        }, 300);
    }
});
</script>
<?php
$coins = '0.00';
$is_user_logged_in = false;
$user_name = '';
$user_ref_code = '';

if (isset($_SESSION['user_id'])) {
    $is_user_logged_in = true;
    try {
        global $pdo;
        $st = $pdo->prepare("SELECT id, name, phone, email, profile_pic, coins_balance, ref_code, role FROM users WHERE id = ?");
        $st->execute([$_SESSION['user_id']]);
        $u = $st->fetch();
        if ($u) {
            $coins = number_format($u['coins_balance'], 2);
            $user_name = $u['name'];
            $user_ref_code = $u['ref_code'];
            $user_role = $u['role'] ?? 'user';
            
            $p_stmt = $pdo->prepare("SELECT id, business_name, status FROM partners WHERE (user_id = :uid OR phone = :phone) AND (status IS NULL OR status != 'suspended') ORDER BY id DESC LIMIT 1");
            $p_stmt->execute([':uid' => $u['id'], ':phone' => $u['phone']]);
            $shop = $p_stmt->fetch();
            $has_shop = $shop ? true : false;
            $shop_status = $shop['status'] ?? 'approved';
            $shop_name = $shop['business_name'] ?? 'My Shop';
        }
    } catch (Exception $e) {}
}

if (!isset($settings)) {
    try { $settings = $pdo->query("SELECT setting_key, setting_value FROM homepage_settings")->fetchAll(PDO::FETCH_KEY_PAIR); } catch(Exception $e) { $settings = []; }
}
$site_name = $settings['site_name'] ?? 'FAST SITE';
?>
<!-- Unified Premium Navigation Bar -->
<link rel="stylesheet" href="/assets/css/native_mobile.css"/>
<script src="/assets/js/app_environment.js" defer></script>
<style>
    :root {
        --nav-glass-bg: rgba(8, 8, 12, 0.75);
        --nav-solid-bg: rgba(10, 13, 26, 0.96);
        --nav-text: #ffffff;
        --nav-accent: #00e676;
        --nav-gold: #fcb900;
        --nav-gold-glow: rgba(252, 185, 0, 0.3);
        --nav-border: rgba(255, 255, 255, 0.08);
    }
    
    .public-nav {
        background: var(--nav-glass-bg);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border-bottom: 1px solid var(--nav-border);
        padding: max(0.65rem, env(safe-area-inset-top, 0px)) 1.2rem 0.65rem;
        display: grid;
        grid-template-columns: 1fr auto 1fr;
        align-items: center;
        position: fixed;
        width: 100%;
        top: 0;
        left: 0;
        z-index: 1000;
        transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        box-sizing: border-box;
    }
    
    .public-nav.scrolled {
        background: var(--nav-solid-bg);
        border-bottom-color: rgba(252, 185, 0, 0.2);
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.6);
        padding-top: max(0.5rem, env(safe-area-inset-top, 0px));
        padding-bottom: 0.5rem;
    }
    
    /* Zone 1: Left (Coin Wallet Pill) */
    .nav-zone-left { 
        display: flex; 
        align-items: center; 
        justify-content: flex-start;
        min-width: 0;
    }
    
    .coin-badge-pill, .coin-badge {
        background: rgba(18, 22, 43, 0.85);
        border: 1px solid rgba(252, 185, 0, 0.3);
        padding: 0.32rem 0.65rem;
        border-radius: 50px;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        font-weight: 800;
        color: var(--nav-gold);
        font-size: 0.82rem;
        text-decoration: none;
        transition: all 0.2s ease;
        white-space: nowrap;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.35);
    }
    .coin-badge-pill:hover, .coin-badge:hover {
        background: rgba(252, 185, 0, 0.15);
        border-color: var(--nav-gold);
        transform: translateY(-1px);
        box-shadow: 0 0 15px var(--nav-gold-glow);
    }
    .coin-topup-btn {
        background: rgba(252, 185, 0, 0.25);
        border-radius: 50%;
        width: 16px;
        height: 16px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        font-weight: 900;
        color: #fff;
        margin-left: 1px;
        line-height: 1;
    }
    
    /* Zone 2: Center Brand Identity (Strictly Centered) */
    .nav-zone-center {
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        min-width: 0;
    }
    .nav-brand-centered, .nav-brand-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.45rem;
        text-decoration: none;
    }
    .nav-logo-icon {
        height: 38px;
        width: auto;
        display: block;
        filter: drop-shadow(0 2px 8px rgba(252, 185, 0, 0.35));
    }
    .nav-brand-text {
        font-family: 'Oswald', 'Sora', sans-serif;
        font-weight: 800;
        font-size: 1.15rem;
        letter-spacing: 0.04em;
        background: linear-gradient(135deg, #ffffff 50%, #fcb900 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        text-transform: uppercase;
        white-space: nowrap;
    }
    
    /* Zone 3: Right Actions (Shop Pill & Hamburger Button) */
    .nav-zone-right { 
        display: flex; 
        align-items: center; 
        justify-content: flex-end;
        gap: 0.5rem;
        min-width: 0;
    }

    .cart-nav-pill {
        position: relative;
        background: rgba(18, 22, 43, 0.85);
        border: 1px solid rgba(252, 185, 0, 0.35);
        color: #fff;
        width: 38px;
        height: 38px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.35);
        flex-shrink: 0;
    }
    .cart-nav-pill:hover {
        background: rgba(252, 185, 0, 0.15);
        border-color: var(--nav-gold);
        transform: translateY(-1px);
        box-shadow: 0 0 15px var(--nav-gold-glow);
    }
    .cart-nav-pill:active {
        transform: scale(0.94);
    }
    .cart-nav-count {
        position: absolute;
        top: -4px;
        right: -4px;
        background: linear-gradient(135deg, #EF4444, #DC2626);
        color: #fff;
        font-size: 0.68rem;
        font-weight: 900;
        min-width: 17px;
        height: 17px;
        line-height: 17px;
        border-radius: 50px;
        text-align: center;
        padding: 0 4px;
        border: 1.5px solid #080911;
        box-shadow: 0 2px 6px rgba(239, 68, 68, 0.6);
        box-sizing: border-box;
    }

    .shop-nav-btn {
        background: linear-gradient(135deg, var(--nav-gold), #ff9100);
        color: #080911 !important;
        font-size: 0.74rem;
        font-weight: 800;
        padding: 0.35rem 0.65rem;
        border-radius: 50px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        box-shadow: 0 2px 10px rgba(252, 185, 0, 0.3);
        max-width: 125px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        transition: transform 0.2s;
    }
    .shop-nav-btn:hover {
        transform: scale(1.02);
    }
    .shop-nav-name {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        max-width: 90px;
    }
    
    .hamburger-btn {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.12);
        border-radius: 10px;
        width: 38px;
        height: 38px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #ffffff;
        cursor: pointer;
        transition: all 0.2s ease;
        flex-shrink: 0;
    }
    .hamburger-btn svg {
        width: 20px;
        height: 20px;
        fill: currentColor;
    }
    .hamburger-btn:hover {
        background: rgba(255, 255, 255, 0.1);
        border-color: rgba(252, 185, 0, 0.4);
        color: var(--nav-gold);
    }
    
    /* --- Side Drawer Menu (Dark Premium) --- */
    .drawer-overlay {
        position: fixed; inset: 0;
        background: rgba(0,0,0,0.7);
        backdrop-filter: blur(5px);
        z-index: 2000; display: none; opacity: 0; transition: opacity 0.3s;
    }
    .drawer-menu {
        position: fixed; top: 0; right: -320px; width: 320px; height: 100vh;
        background: #0d0d14; border-left: 1px solid rgba(255,255,255,0.05);
        z-index: 2001; box-shadow: -15px 0 50px rgba(0,0,0,0.5);
        transition: right 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex; flex-direction: column; overflow-y: auto;
    }
    .drawer-menu.active { right: 0; }
    .drawer-overlay.active { display: block; opacity: 1; }
    
    .drawer-header {
        padding: 1.5rem;
        border-bottom: 1px solid rgba(255,255,255,0.05);
        display: flex; align-items: center; justify-content: space-between;
    }
    .drawer-header h3 {
        margin: 0; color: #fff; font-size: 1.1rem; font-weight: 800;
        letter-spacing: 0.05em; text-transform: uppercase;
    }
    .close-drawer {
        background: rgba(255,255,255,0.05); border: none; border-radius: 50%;
        width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 1.2rem; cursor: pointer; transition: 0.2s;
    }
    .close-drawer:hover { background: rgba(255, 82, 82, 0.2); color: #ff5252; }
    
    .drawer-user-section {
        padding: 1.1rem 1.25rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        background: linear-gradient(180deg, rgba(252, 185, 0, 0.05) 0%, rgba(13, 13, 20, 0.95) 100%);
    }
    
    /* Highlighted VIP Guest Card */
    .drawer-auth-card {
        background: linear-gradient(135deg, rgba(252, 185, 0, 0.12) 0%, rgba(26, 29, 45, 0.85) 100%);
        border: 1.5px solid rgba(252, 185, 0, 0.4);
        border-radius: 14px;
        padding: 0.95rem;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4), 0 0 16px rgba(252, 185, 0, 0.18);
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        box-sizing: border-box;
    }
    .drawer-auth-header {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .drawer-auth-avatar {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        background: linear-gradient(135deg, rgba(252, 185, 0, 0.25), rgba(245, 158, 11, 0.1));
        border: 1px solid rgba(252, 185, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
        flex-shrink: 0;
        box-shadow: 0 0 10px rgba(252, 185, 0, 0.2);
    }
    .drawer-auth-meta {
        flex: 1;
        min-width: 0;
    }
    .drawer-auth-meta h4 {
        margin: 0;
        font-size: 0.92rem;
        font-weight: 800;
        color: #ffffff;
        font-family: 'Inter', sans-serif;
    }
    .drawer-auth-meta p {
        margin: 2px 0 0;
        font-size: 0.72rem;
        font-weight: 700;
        color: var(--nav-gold);
    }
    .drawer-auth-tabs {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
    }
    .btn-auth-tab {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 0.65rem 0.5rem;
        border-radius: 10px;
        font-family: 'Inter', sans-serif;
        font-size: 0.82rem;
        font-weight: 800;
        text-decoration: none !important;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        box-sizing: border-box;
        text-align: center;
    }
    .btn-auth-tab.login {
        background: linear-gradient(135deg, #fcb900 0%, #f7971e 100%);
        color: #080911 !important;
        border: none;
        box-shadow: 0 4px 14px rgba(252, 185, 0, 0.4);
    }
    .btn-auth-tab.login:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(252, 185, 0, 0.55);
        color: #000 !important;
    }
    .btn-auth-tab.login:active {
        transform: scale(0.96);
    }
    .btn-auth-tab.register {
        background: rgba(255, 255, 255, 0.06);
        color: #fcb900 !important;
        border: 1px solid rgba(252, 185, 0, 0.45);
    }
    .btn-auth-tab.register:hover {
        background: rgba(252, 185, 0, 0.15);
        border-color: #fcb900;
        transform: translateY(-2px);
    }
    .btn-auth-tab.register:active {
        transform: scale(0.96);
    }
    
    .user-profile-badge {
        display: flex; align-items: center; gap: 1rem;
    }
    .user-avatar {
        width: 45px; height: 45px; border-radius: 50%; background: rgba(255,255,255,0.1);
        display: flex; align-items: center; justify-content: center; color: #fff; font-size: 1.2rem;
    }
    .user-profile-badge h4 { margin: 0; color: #fff; font-size: 1rem; }
    .user-profile-badge p { margin: 0; color: var(--nav-gold); font-size: 0.8rem; font-weight: 600; }
    
    .drawer-nav { padding: 1rem 0; flex: 1; }
    .drawer-link {
        display: flex; align-items: center; gap: 1rem; padding: 1rem 1.5rem;
        color: #a0a0b0; text-decoration: none; font-weight: 500; font-size: 0.95rem;
        transition: all 0.2s; border-left: 3px solid transparent;
    }
    .drawer-link svg { width: 18px; height: 18px; fill: currentColor; opacity: 0.8; }
    .drawer-link:hover {
        background: rgba(255,255,255,0.03); color: #fff; border-left-color: var(--nav-accent);
    }
    .drawer-link:hover svg { opacity: 1; color: var(--nav-accent); }
    
    .drawer-footer {
        padding: 1.5rem; border-top: 1px solid rgba(255,255,255,0.05); text-align: center;
    }
    .btn-logout {
        display: inline-block; padding: 0.6rem 1.5rem; background: rgba(255,82,82,0.1);
        color: #ff5252; text-decoration: none; border-radius: 50px; font-size: 0.85rem; font-weight: 600; transition: 0.2s;
    }
    .btn-logout:hover { background: #ff5252; color: #fff; }
    
    /* Responsive Media Queries */
    @media (max-width: 600px) {
        .public-nav {
            padding: max(0.5rem, env(safe-area-inset-top, 0px)) 0.75rem 0.5rem;
        }
        .nav-logo-icon {
            height: 32px;
        }
        .nav-brand-text {
            font-size: 0.95rem;
            letter-spacing: 0.02em;
        }
        .shop-nav-btn {
            display: none !important; /* Keep mobile top navbar spacious and logo perfectly centered */
        }
        .coin-badge-pill, .coin-badge {
            padding: 0.28rem 0.45rem;
            font-size: 0.74rem;
        }
        .coin-topup-btn {
            display: none;
        }
    }
    @media (max-width: 375px) {
        .nav-brand-text {
            display: none !important; /* Zero collision guaranteed on ultra-narrow phones */
        }
        .nav-logo-icon {
            height: 34px;
        }
        .shop-nav-btn {
            display: none !important;
        }
    }

    /* Push main content down to account for fixed navbar */
    body { padding-top: calc(65px + env(safe-area-inset-top, 0px)); }
</style>

<nav class="public-nav" id="mainNav">
    <!-- Zone 1: Left Actions (Coin Wallet Pill) -->
    <div class="nav-zone-left">
        <a href="/user/wallet.php" class="coin-badge-pill coin-badge" title="My Wallet Balance">
            <span class="coin-icon">🪙</span>
            <span class="coin-amount"><?= $coins ?></span>
            <span class="coin-topup-btn">+</span>
        </a>
    </div>

    <!-- Zone 2: Center Brand Identity (Strictly Centered) -->
    <div class="nav-zone-center">
        <a href="/index.php" class="nav-brand-centered nav-brand-link" title="<?= htmlspecialchars($site_name) ?> Marketplace">
            <img src="/assets/images/logo.png" class="nav-logo-icon" alt="FAST SITE"/>
            <span class="nav-brand-text"><?= htmlspecialchars($site_name) ?></span>
        </a>
    </div>

    <!-- Zone 3: Right Actions (Shop Pill & Hamburger Drawer) -->
    <div class="nav-zone-right">
        <?php if(basename($_SERVER['PHP_SELF']) === 'index.php' || basename($_SERVER['PHP_SELF']) === 'home.php'): ?>
            <?php if ($is_user_logged_in && isset($has_shop) && $has_shop && $shop_status === 'approved'): ?>
                <a href="/partner/dashboard.php" class="shop-nav-btn" title="Partner Shop: <?= htmlspecialchars($shop_name) ?>">
                   <span class="shop-icon" style="font-size:0.85rem;">🏪</span>
                   <span class="shop-nav-name"><?= htmlspecialchars($shop_name) ?></span>
                </a>
            <?php elseif ($is_user_logged_in && isset($has_shop) && $has_shop && $shop_status === 'pending'): ?>
                <a href="#" onclick="alert('Your shop is pending admin approval.');" class="shop-nav-btn pending" style="background:rgba(255,255,255,0.08); color:#ccc !important; border:1px solid rgba(255,255,255,0.18); box-shadow:none;">
                   <span class="shop-icon" style="font-size:0.85rem;">⏳</span> <span class="shop-nav-name">Pending</span>
                </a>
            <?php endif; ?>
        <?php endif; ?>
        <!-- Cart Nav Pill -->
        <?php 
          $nav_cart_count = 0;
          if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
              $nav_cart_count = array_sum(array_column($_SESSION['cart'], 'quantity'));
          }
        ?>
        <a href="/cart.php" class="cart-nav-pill" id="navCartPill" title="Shopping Cart">
            <span style="font-size:1.1rem; line-height:1;">🛒</span>
            <span class="cart-nav-count" id="navCartCount" style="<?= $nav_cart_count > 0 ? '' : 'display:none;' ?>"><?= $nav_cart_count ?></span>
        </a>
        <button class="hamburger-btn" onclick="toggleDrawer()" aria-label="Open Platform Menu">
            <svg viewBox="0 0 24 24"><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/></svg>
        </button>
    </div>
</nav>

<!-- Side Drawer -->
<div class="drawer-overlay" id="drawerOverlay" onclick="toggleDrawer()"></div>
<div class="drawer-menu" id="drawerMenu">
    <div class="drawer-header">
        <h3>Menu Hub</h3>
        <button class="close-drawer" onclick="toggleDrawer()">&times;</button>
    </div>
    
    <div class="drawer-user-section">
        <?php if ($is_user_logged_in): ?>
            <a href="/user/dashboard.php?tab=social" class="user-profile-badge" style="text-decoration:none; display:flex; align-items:center; gap:12px;">
                <div class="user-avatar" style="width:48px; height:48px; border-radius:50%; border:2px solid var(--gold); overflow:hidden; flex-shrink:0; background:#12121c; display:flex; align-items:center; justify-content:center; box-shadow:0 0 12px rgba(252,185,0,0.35);">
                    <?php 
                      $u_pic = !empty($u['profile_pic']) ? '/' . ltrim($u['profile_pic'], '/') : '/assets/images/default_avatar.png';
                    ?>
                    <img src="<?= htmlspecialchars($u_pic) ?>" alt="Profile" style="width:100%; height:100%; object-fit:cover;" onerror="this.onerror=null; this.src='/assets/images/default_avatar.png';">
                </div>
                <div style="text-align:left; flex:1; min-width:0;">
                    <h4 style="color:#fff; margin-bottom:2px; font-size:0.95rem; font-weight:800; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= htmlspecialchars($user_name) ?></h4>
                    <p style="color:var(--gold); font-size:0.75rem; font-weight:700; margin:0;">Edit Profile ➔</p>
                </div>
            </a>
        <?php else: ?>
            <div class="drawer-auth-card">
                <div class="drawer-auth-header">
                    <div class="drawer-auth-avatar">👤</div>
                    <div class="drawer-auth-meta">
                        <h4>Welcome to FAST SITE</h4>
                        <p><span>🪙</span> Free 50 Fast Cash (৳50) on Register</p>
                    </div>
                </div>
                <div class="drawer-auth-tabs">
                    <a href="/user/login.php" class="btn-auth-tab login" style="display:flex; align-items:center; justify-content:center; gap:6px; background:linear-gradient(135deg, #fcb900 0%, #f7971e 100%); color:#080911 !important; font-weight:800; font-size:0.82rem; padding:0.65rem 0.5rem; border-radius:10px; box-shadow:0 4px 14px rgba(252,185,0,0.4); text-decoration:none !important; text-transform:uppercase; letter-spacing:0.03em;">
                        <span>🔐</span>
                        <span>Login</span>
                    </a>
                    <a href="/user/register.php" class="btn-auth-tab register" style="display:flex; align-items:center; justify-content:center; gap:6px; background:rgba(255,255,255,0.06); color:#fcb900 !important; border:1px solid rgba(252,185,0,0.45); font-weight:800; font-size:0.82rem; padding:0.65rem 0.5rem; border-radius:10px; text-decoration:none !important; text-transform:uppercase; letter-spacing:0.03em;">
                        <span>✨</span>
                        <span>Register</span>
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="drawer-nav">
        <!-- MARKETPLACE TOOLS -->
        <div style="font-size:0.7rem; font-weight:800; color:var(--muted); text-transform:uppercase; letter-spacing:0.05em; padding: 1rem 1.5rem 0.5rem;">Marketplace Tools</div>
        <a href="/cart.php" class="drawer-link" style="color:var(--gold); font-weight:700;">
            <span style="font-size:1.2rem; margin-right:8px;">🛒</span> Shopping Cart (কার্ট)
        </a>
        
        <?php if ($is_user_logged_in): ?>
            <?php if ($has_shop && in_array($shop_status, ['approved', 'active', null])): ?>
                <a href="/partner/dashboard.php" class="drawer-link" style="color:var(--gold); font-weight:700;">
                    <span style="font-size:1.2rem; margin-right:8px;">🏪</span> Shop: <?= htmlspecialchars($shop_name) ?>
                </a>
            <?php elseif ($has_shop && $shop_status === 'pending'): ?>
                <a href="/partner/dashboard.php" class="drawer-link" style="color:#f59e0b;">
                    <span style="font-size:1.2rem; margin-right:8px;">⏳</span> Shop: <?= htmlspecialchars($shop_name) ?> (Pending)
                </a>
            <?php else: ?>
                <a href="/user/create_shop.php" class="drawer-link" style="color:var(--gold);">
                    <span style="font-size:1.2rem; margin-right:8px;">➕</span> Open Shop
                </a>
            <?php endif; ?>
            
            <a href="/user/partner_orders.php" class="drawer-link">
                <span style="font-size:1.2rem; margin-right:8px;">🛍️</span> Store Orders
            </a>
            
            <div style="font-size:0.7rem; font-weight:800; color:var(--muted); text-transform:uppercase; letter-spacing:0.05em; padding: 1rem 1.5rem 0.5rem;">Financial Tools</div>
            <a href="/user/wallet.php" class="drawer-link">
                <span style="font-size:1.2rem; margin-right:8px;">🪙</span> My Wallet
            </a>
            <a href="/user/deposit.php" class="drawer-link">
                <span style="font-size:1.2rem; margin-right:8px;">💳</span> Add Funds / Deposit
            </a>
            
            <div style="font-size:0.7rem; font-weight:800; color:var(--muted); text-transform:uppercase; letter-spacing:0.05em; padding: 1rem 1.5rem 0.5rem;">Others</div>
            <a href="/user/messages.php" class="drawer-link">
                <span style="font-size:1.2rem; margin-right:8px;">✉️</span> Messages
            </a>
            <a href="/user/notifications.php" class="drawer-link">
                <span style="font-size:1.2rem; margin-right:8px;">🔔</span> Notifications
            </a>
            
            <a href="/user/dashboard.php?tab=support" class="drawer-link">
                <span style="font-size:1.2rem; margin-right:8px;">💬</span> Help & Support
            </a>
        <?php endif; ?>

        <div style="font-size:0.7rem; font-weight:800; color:var(--muted); text-transform:uppercase; letter-spacing:0.05em; padding: 1rem 1.5rem 0.5rem;">Quick Links</div>

        <?php if ($is_user_logged_in): ?>
        <a href="/user/deposit.php" class="drawer-link">
            <span style="font-size:1.2rem; margin-right:8px;">🪙</span> Fast Cash / Points
        </a>
        <a href="/user/partner_orders.php" class="drawer-link">
            <span style="font-size:1.2rem; margin-right:8px;">📦</span> My Purchases &amp; Orders
        </a>
        <?php endif; ?>
        
        <a href="/track.php" class="drawer-link">
            <span style="font-size:1.2rem; margin-right:8px;">📦</span> Track Public Order
        </a>
        
        <?php if (!isset($user_role) || $user_role !== 'partner'): ?>
            <a href="/user/become_partner.php" class="drawer-link">
                <span style="font-size:1.2rem; margin-right:8px;">🤝</span> Become Partner
            </a>
        <?php endif; ?>
        
        <a href="/about.php" class="drawer-link">
            <span style="font-size:1.2rem; margin-right:8px;">ℹ️</span> About FAST SITE
        </a>

        <!-- FAST SITE NATIVE APP -->
        <?php
          if (isset($pdo) && !isset($settings)) {
              $settings = $pdo->query("SELECT setting_key, setting_value FROM homepage_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
          }
          $nav_apk_name = !empty($settings['apk_app_name']) ? $settings['apk_app_name'] : 'FAST SITE WORLD';
          $nav_apk_photo = !empty($settings['apk_app_photo']) ? '/' . ltrim($settings['apk_app_photo'], '/') : '/assets/images/fast_site_world_app_icon.jpg';
        ?>
        <a href="/fastsite_storefront.apk" class="drawer-link apk-download-card hide-in-apk" style="background: rgba(16, 185, 129, 0.1); border-left: 3px solid #10b981; margin: 10px 15px; border-radius: 6px; display:flex; align-items:center; gap:10px; padding:0.75rem 1rem;" download>
            <img src="<?= htmlspecialchars($nav_apk_photo) ?>" style="width:24px; height:24px; border-radius:6px; object-fit:cover; display:inline-block; border:1px solid rgba(252,185,0,0.4);" alt="APK" onerror="this.onerror=null; this.src='/assets/images/logo.png';"/>
            <span style="font-weight:800; color:#fff; font-size:0.88rem;"><?= htmlspecialchars($nav_apk_name) ?> (APK)</span>
        </a>

        <!-- SHARE BUTTON TAB -->
        <div style="font-size:0.7rem; font-weight:800; color:var(--muted); text-transform:uppercase; letter-spacing:0.05em; padding: 1rem 1.5rem 0.5rem;">Share App</div>
        <div style="padding: 0.5rem 1.5rem; display:flex; gap:10px; flex-wrap:wrap;">
            <?php 
              $shareUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
            ?>
            <a href="https://wa.me/?text=<?= urlencode('Check out FAST SITE Marketplace: ' . $shareUrl) ?>" target="_blank" style="background:#25D366; color:#fff; padding:6px 12px; border-radius:6px; font-size:0.75rem; text-decoration:none; font-weight:700;">WhatsApp</a>
            <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($shareUrl) ?>" target="_blank" style="background:#1877F2; color:#fff; padding:6px 12px; border-radius:6px; font-size:0.75rem; text-decoration:none; font-weight:700;">Facebook</a>
        </div>
    </div>
    
    <?php if ($is_user_logged_in): ?>
        <div class="drawer-footer">
            <a href="/user/logout.php" class="btn-logout">Logout</a>
        </div>
    <?php endif; ?>
</div>

<script>
    function toggleDrawer() {
        const menu = document.getElementById('drawerMenu');
        const overlay = document.getElementById('drawerOverlay');
        const willOpen = !menu.classList.contains('active');
        menu.classList.toggle('active');
        overlay.classList.toggle('active');
        if (willOpen && typeof window.pushModalBackState === 'function') {
            window.pushModalBackState();
        }
    }
    
    // Dynamic Navbar Scroll Effect
    window.addEventListener('scroll', () => {
        const nav = document.getElementById('mainNav');
        if (window.scrollY > 20) {
            nav.classList.add('scrolled');
        } else {
            nav.classList.remove('scrolled');
        }
    });
</script>
