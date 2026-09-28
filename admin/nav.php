<?php
// =========================================================================
// admin/nav.php — Unified Enterprise Navigation Hub (Phase 37 Ultra-Premium)
// =========================================================================

if (!isset($pdo)) {
    require_once __DIR__ . '/../config.php';
}

$current_page = basename($_SERVER['PHP_SELF']);

// Load staff permissions if staff user
$admin_role = $_SESSION['admin_role'] ?? 'admin';
$admin_user = $_SESSION['admin_user'] ?? ($_SESSION['admin_username'] ?? '');

$staff_perms = [];
if ($admin_role === 'staff') {
    try {
        $stmt_p = $pdo->prepare("SELECT permissions FROM staff_users WHERE username = :u LIMIT 1");
        $stmt_p->execute([':u' => $admin_user]);
        $p_json = $stmt_p->fetchColumn();
        if ($p_json) {
            $decoded = json_decode($p_json, true);
            if (is_array($decoded)) {
                $staff_perms = $decoded;
            } else {
                $staff_perms = array_filter(array_map('trim', explode(',', $p_json)));
            }
        }
    } catch (Exception $e) {}
}

if (!function_exists('has_admin_permission')) {
    function has_admin_permission($section) {
        global $admin_role, $staff_perms;
        if ($admin_role === 'admin') return true;
        if (empty($staff_perms)) return false;
        return in_array($section, $staff_perms);
    }
}

// Load branding and header settings
$nav_settings = [];
try {
    $nav_settings = $pdo->query("SELECT setting_key, setting_value FROM homepage_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (Exception $e) {}

$admin_header_name = $nav_settings['admin_header_name'] ?? 'FAST SITE';
$admin_header_tag  = $nav_settings['admin_header_tag'] ?? 'OVERPOWERED FUNCTIONAL SYSTEM';

// Fetch Official Admin Shop Business Name
$official_shop_name = 'ADMIN SHOP';
try {
    $stmt_shop = $pdo->query("SELECT business_name, registration_number FROM partners WHERE is_official = 1 OR registration_number = 'FS-OFFICIAL-1' LIMIT 1");
    $shop_row = $stmt_shop->fetch();
    if ($shop_row && !empty($shop_row['business_name'])) {
        $official_shop_name = $shop_row['business_name'];
    }
} catch (Exception $e) {}

// Dynamic SVG Icons
$default_svg_icons = [
    'dashboard'     => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>',
    'admin'         => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>',
    'users'         => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>',
    'shops'         => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>',
    'products'      => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>',
    'partner'       => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><circle cx="19" cy="8" r="3"></circle><line x1="16" y1="11" x2="20" y2="11"></line></svg>',
    'wallet'        => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2" ry="2"></rect><line x1="2" y1="10" x2="22" y2="10"></line></svg>',
    'setting'       => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>'
];

// Sub-Items Definition
$admin_sub_items = [
    ['title' => '🎯 Omni-Task Management', 'link' => 'tasks.php'],
    ['title' => '👥 Staff & Role Access', 'link' => 'staff_access.php'],
    ['title' => '📋 Tasks to Staff', 'link' => 'give_task_staff.php'],
    ['title' => '💬 Internal Team Live Chat', 'link' => 'chats.php#tab-internal'],
    ['title' => '🔒 Personal Admin Profile', 'link' => 'settings.php#sec-personal-profile']
];

$users_sub_items = [
    ['title' => '👥 All Customers & Users', 'link' => 'users.php#tab-customers'],
    ['title' => '🎯 User Tasks & Rewards', 'link' => 'tasks.php'],
    ['title' => '🪪 KYC & Verification Vault', 'link' => 'users.php#tab-kyc'],
    ['title' => '🔔 Broadcast Notifications', 'link' => 'users.php#tab-broadcast'],
    ['title' => '🎁 Customer Loyalty & Streaks', 'link' => 'users.php#tab-loyalty']
];

$shops_sub_items = [
    ['title' => '🛒 Shop Registrations', 'link' => 'partner_shops.php#tab-shops'],
    ['title' => '📝 Shop Requests', 'link' => 'partner_shops.php#tab-requests'],
    ['title' => '💸 Payout Requests', 'link' => 'partner_shops.php#tab-payouts'],
    ['title' => '⚖️ Escrow Disputes Adjudication', 'link' => 'partner_shops.php#tab-disputes']
];

$products_sub_items = [
    ['title' => '🛍️ All Products & Listings', 'link' => 'products.php#tab-all'],
    ['title' => '🔥 Trending Selections', 'link' => 'products.php#tab-trending'],
    ['title' => '🤝 Services & Applications', 'link' => 'products.php#tab-services'],
    ['title' => '⚡ Partner Affiliate Offers', 'link' => 'products.php#tab-affiliates'],
    ['title' => '⏸️ Suspended / On Hold', 'link' => 'products.php#tab-onhold']
];

$partner_sub_items = [
    ['title' => '🔗 Affiliate Agents Hub', 'link' => 'agents.php'],
    ['title' => '⚡ API Developer Partners', 'link' => 'api_partners.php'],
    ['title' => '🌐 Omni-Network Control Hub', 'link' => 'empire_control.php'],
    ['title' => '✈️ Best Travel', 'link' => 'https://best-travel.ltd', 'target' => '_blank'],
    ['title' => '💼 GixSam (Portfolio Editor)', 'link' => 'gixsam_editor.php'],
    ['title' => '📰 Affi Bangla (Aggregator)', 'link' => 'https://affibangla.best-travel.ltd', 'target' => '_blank'],
    ['title' => '👗 Ayra Mart (Fashion)', 'link' => 'https://atayramart.com', 'target' => '_blank'],
    ['title' => '📰 Manza (News & Content)', 'link' => 'https://manza.best-travel.ltd', 'target' => '_blank'],
    ['title' => '🚗 Enzor Motor (Auto Parts)', 'link' => 'https://enzor.best-travel.ltd', 'target' => '_blank']
];

$wallet_sub_items = [
    ['title' => '💼 Master Wallet & Liquidity', 'link' => 'wallet.php'],
    ['title' => "🤝 Agent's Payouts", 'link' => 'payouts.php#tab-agent-payouts'],
    ['title' => '🏪 Partner Withdrawals', 'link' => 'payouts.php#tab-partner-payouts'],
    ['title' => '⚡ Coin Deposits & Wallet Logs', 'link' => 'payouts.php#tab-deposits']
];

// Phase 72: High-Risk Detection Badge
$high_risk_detected = false;
try {
    $dupCount = (int)$pdo->query("SELECT COUNT(*) FROM (SELECT payout_account FROM user_withdrawals GROUP BY payout_account HAVING COUNT(DISTINCT user_id) > 1) as t")->fetchColumn();
    $dispCount = (int)$pdo->query("SELECT COUNT(*) FROM (SELECT p.id FROM partner_orders o JOIN partners p ON o.partner_id = p.id GROUP BY p.id HAVING COUNT(o.id) > 4 AND (SUM(CASE WHEN o.status IN ('disputed', 'cancelled', 'refunded') THEN 1 ELSE 0 END) / COUNT(o.id)) > 0.20) as t2")->fetchColumn();
    if ($dupCount > 0 || $dispCount > 0) $high_risk_detected = true;
} catch (Exception $e) {}

$setting_sub_items = [
    ['title' => '⚙️ System Advanced Settings', 'link' => 'settings.php'],
    ['title' => '📊 Escrow Financial Heatmap', 'link' => 'escrow_heatmap.php'],
    ['title' => '🛡️ Fraud & Security Monitor' . ($high_risk_detected ? ' 🔴' : ''), 'link' => 'fraud_detector.php'],
    ['title' => '🔑 Master API & Keys', 'link' => 'settings.php#sec-api'],
    ['title' => '🎨 Branding & Media Banners', 'link' => 'settings.php#sec-logo-banner-media'],
    ['title' => '💳 Marketplace & Gateways', 'link' => 'settings.php#sec-marketplace'],
    ['title' => '📝 GixSam Landing Editor', 'link' => 'gixsam_editor.php'],
    ['title' => '📜 Terms & Conditions', 'link' => 'settings.php#sec-tnc']
];

// Clean Navigation Hubs (With Products Section)
$nav_items_def = [
    'dashboard' => [
        'title'        => 'DASHBOARD',
        'link'         => 'dashboard.php',
        'active_pages' => ['dashboard.php']
    ],
    'admin' => [
        'title'        => 'ADMIN',
        'link'         => 'staff_access.php',
        'dropdown'     => $admin_sub_items,
        'active_pages' => ['staff_access.php', 'give_task_staff.php', 'chats.php', 'chat_view.php', 'internal_chats.php']
    ],
    'users' => [
        'title'        => 'USERS',
        'link'         => 'users.php',
        'dropdown'     => $users_sub_items,
        'active_pages' => ['users.php']
    ],
    'shops' => [
        'title'        => 'SHOPS',
        'link'         => 'partner_shops.php',
        'dropdown'     => $shops_sub_items,
        'active_pages' => ['partner_shops.php', 'partner_requests.php', 'partner_withdrawals.php', 'partner_disputes.php']
    ],
    'products' => [
        'title'        => 'PRODUCTS',
        'link'         => 'products.php',
        'dropdown'     => $products_sub_items,
        'active_pages' => ['products.php']
    ],
    'partner' => [
        'title'        => 'PARTNER',
        'link'         => 'agents.php',
        'dropdown'     => $partner_sub_items,
        'active_pages' => ['agents.php', 'api_partners.php', 'empire_control.php', 'network_products.php']
    ],
    'wallet' => [
        'title'        => 'WALLET',
        'link'         => 'wallet.php',
        'dropdown'     => $wallet_sub_items,
        'active_pages' => ['wallet.php', 'payouts.php', 'accounts.php']
    ],
    'setting' => [
        'title'        => 'SETTING',
        'link'         => 'settings.php',
        'dropdown'     => $setting_sub_items,
        'active_pages' => ['settings.php', 'gixsam_editor.php', 'escrow_heatmap.php', 'fraud_detector.php']
    ]
];

// Comprehensive Notification Alerts Fetcher for Admin & Mobile APK
$admin_notif_count = 0;
$admin_notifications = [];
try {
    // 1. Pending Partner Shops & Applications
    $pCount = 0;
    try {
        $pCount += (int)$pdo->query("SELECT COUNT(*) FROM partners WHERE status = 'pending'")->fetchColumn();
    } catch (Exception $e) {}
    try {
        $pCount += (int)$pdo->query("SELECT COUNT(*) FROM partner_requests WHERE status = 'pending'")->fetchColumn();
    } catch (Exception $e) {}
    if ($pCount > 0) {
        $admin_notif_count += $pCount;
        $admin_notifications[] = ["text" => "$pCount pending shop registration/request(s)", "link" => "partner_shops.php#tab-requests"];
    }

    // 2. Pending KYC / Identity Verification Requests
    try {
        $kycCount = (int)$pdo->query("
            SELECT COUNT(*) FROM users 
            WHERE kyc_status = 'pending' 
              AND (
                (nid IS NOT NULL AND nid != '') 
                OR (passport IS NOT NULL AND passport != '') 
                OR (driving_license IS NOT NULL AND driving_license != '') 
                OR (etin IS NOT NULL AND etin != '')
                OR (nid_front_photo IS NOT NULL AND nid_front_photo != '')
              )
        ")->fetchColumn();
        if ($kycCount > 0) {
            $admin_notif_count += $kycCount;
            $admin_notifications[] = ["text" => "$kycCount pending user KYC verification(s)", "link" => "users.php#tab-kyc"];
        }
    } catch (Exception $e) {}

    // 3. Pending Customer Service Application Orders
    try {
        $appCount = (int)$pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'pending'")->fetchColumn();
        if ($appCount > 0) {
            $admin_notif_count += $appCount;
            $admin_notifications[] = ["text" => "$appCount pending service application order(s)", "link" => "dashboard.php#tab-registry"];
        }
    } catch (Exception $e) {}

    // 4. Pending Escrow Disputes
    try {
        $dispCount = (int)$pdo->query("SELECT COUNT(*) FROM partner_disputes WHERE status = 'pending' OR status = 'open'")->fetchColumn();
        if ($dispCount > 0) {
            $admin_notif_count += $dispCount;
            $admin_notifications[] = ["text" => "$dispCount active escrow dispute(s)", "link" => "partner_shops.php#tab-disputes"];
        }
    } catch (Exception $e) {}

    // 5. Pending User & Partner Withdrawals
    try {
        $wCount = 0;
        try { $wCount += (int)$pdo->query("SELECT COUNT(*) FROM user_withdrawals WHERE status = 'pending'")->fetchColumn(); } catch (Exception $e) {}
        try { $wCount += (int)$pdo->query("SELECT COUNT(*) FROM partner_withdrawal_requests WHERE status = 'pending'")->fetchColumn(); } catch (Exception $e) {}
        if ($wCount > 0) {
            $admin_notif_count += $wCount;
            $admin_notifications[] = ["text" => "$wCount pending withdrawal payout(s)", "link" => "payouts.php#tab-deposits"];
        }
    } catch (Exception $e) {}

    // 6. Pending Coin Deposits
    try {
        $dCount = (int)$pdo->query("SELECT COUNT(*) FROM deposit_requests WHERE status = 'pending'")->fetchColumn();
        if ($dCount > 0) {
            $admin_notif_count += $dCount;
            $admin_notifications[] = ["text" => "$dCount pending coin deposit request(s)", "link" => "payouts.php#tab-deposits"];
        }
    } catch (Exception $e) {}

    // 7. Pending User Task Proof Submissions
    try {
        $tCount = (int)$pdo->query("SELECT COUNT(*) FROM task_submissions WHERE status = 'pending'")->fetchColumn();
        if ($tCount > 0) {
            $admin_notif_count += $tCount;
            $admin_notifications[] = ["text" => "$tCount pending user task proof(s)", "link" => "tasks.php"];
        }
    } catch (Exception $e) {}
} catch (Exception $e) {}
?>
<link rel="stylesheet" href="/assets/css/admin-nav.css?v=<?= time() ?>" />

<nav class="enterprise-nav">
  <div class="nav-inner-container">
  <?php 
    $admin_logo_img = !empty($nav_settings['logo_url']) ? '/' . ltrim($nav_settings['logo_url'], '/') : '/assets/images/logo.png';
  ?>
  <div class="nav-brand-container">
    <a href="dashboard.php" class="nav-brand-link">
      <div class="nav-logo-wrap">
        <img src="<?= htmlspecialchars($admin_logo_img) ?>" class="nav-logo-img" alt="FAST SITE Logo" onerror="this.onerror=null; this.src='/assets/images/logo.png';"/>
      </div>
      <div class="nav-brand-text-col">
        <span class="logo-main-text"><?= htmlspecialchars(mb_strtoupper($admin_header_name, 'UTF-8')) ?></span>
        <span class="nav-tag"><?= htmlspecialchars(mb_strtoupper($admin_header_tag, 'UTF-8')) ?></span>
      </div>
    </a>
  </div>
  
  <!-- Center Main Navigation Hubs -->
  <div class="nav-links">
    <?php foreach ($nav_items_def as $key => $item): ?>
      <?php 
        $isActive = in_array($current_page, $item['active_pages']);
        $activeClass = $isActive ? 'active' : '';
        $logo_html = $default_svg_icons[$key] ?? '';
      ?>
      <?php if (isset($item['dropdown'])): ?>
        <div class="nav-dropdown">
          <a href="<?= htmlspecialchars($item['link']) ?>" class="nav-item-link <?= $activeClass ?>" title="<?= htmlspecialchars($item['title']) ?>">
            <span class="nav-icon"><?= $logo_html ?></span>
            <span class="nav-text"><?= htmlspecialchars($item['title']) ?></span>
            <span class="dropdown-caret">▾</span>
          </a>
          <div class="nav-dropdown-content">
            <?php foreach ($item['dropdown'] as $subItem): 
                $target = isset($subItem['target']) ? 'target="' . htmlspecialchars($subItem['target']) . '"' : '';
            ?>
              <a href="<?= htmlspecialchars($subItem['link']) ?>" <?= $target ?> class="nav-sub-item">
                <?= htmlspecialchars($subItem['title']) ?>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      <?php else: ?>
        <a href="<?= htmlspecialchars($item['link']) ?>" class="nav-item-link <?= $activeClass ?>" title="<?= htmlspecialchars($item['title']) ?>">
          <span class="nav-icon"><?= $logo_html ?></span>
          <span class="nav-text"><?= htmlspecialchars($item['title']) ?></span>
        </a>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>

  <!-- Right Side Utilities -->
  <div class="nav-utility-bar">
    
    <!-- 1. Admin Shop Direct SSO Button -->
    <a href="impersonate_official.php?redirect=dashboard.php" class="nav-btn-shop" title="Access <?= htmlspecialchars($official_shop_name) ?> Hub">
      <span>🏪 <?= htmlspecialchars($official_shop_name) ?></span>
    </a>

    <!-- 2. Notification Icon -->
    <div class="admin-notif-dropdown">
      <button onclick="document.getElementById('admin-notif-menu').classList.toggle('show-notif')" class="nav-icon-btn" title="Notification Alerts">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
        <?php if ($admin_notif_count > 0): ?>
          <span class="notif-pill-badge"><?= $admin_notif_count ?></span>
        <?php endif; ?>
      </button>
      <div id="admin-notif-menu" class="notif-menu">
        <div class="menu-head">
          <span>🔔 Action Alerts</span>
          <?php if($admin_notif_count > 0): ?><span class="badge-count"><?= $admin_notif_count ?> Pending</span><?php endif; ?>
        </div>
        <div class="menu-body">
          <?php if (empty($admin_notifications)): ?>
              <p style="color:var(--muted); font-size:0.8rem; text-align:center; padding:1rem 0; margin:0;">All systems clear. No pending items.</p>
          <?php else: ?>
              <?php foreach ($admin_notifications as $notif): ?>
                  <a href="<?= htmlspecialchars($notif['link']) ?>" class="notif-item">
                    <span class="bullet">⚡</span> <?= htmlspecialchars($notif['text']) ?>
                  </a>
              <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
    
    <!-- 3. Fast Site Omni App Directory -->
    <div class="app-hub-dropdown">
      <button onclick="document.getElementById('app-hub-menu').classList.toggle('show-hub')" class="nav-icon-btn" title="Fast Site Omni Ecosystem Apps">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
      </button>
      <div id="app-hub-menu" class="hub-menu">
        <div class="menu-head">
          <span>🌐 Omni App Directory</span>
        </div>
        <div class="hub-grid">
          <a href="https://fastsite.best-travel.ltd" target="_blank" class="hub-app-card">
            <span class="app-icon">⚡</span>
            <span class="app-name">Fast Site (Hub)</span>
          </a>
          <a href="https://best-travel.ltd" target="_blank" class="hub-app-card">
            <span class="app-icon">✈️</span>
            <span class="app-name">Best Travel</span>
          </a>
          <a href="https://atayramart.com" target="_blank" class="hub-app-card">
            <span class="app-icon">👗</span>
            <span class="app-name">Ayra Mart</span>
          </a>
          <a href="https://enzor.best-travel.ltd" target="_blank" class="hub-app-card">
            <span class="app-icon">🚗</span>
            <span class="app-name">Enzor Motor</span>
          </a>
          <a href="https://affibangla.best-travel.ltd" target="_blank" class="hub-app-card">
            <span class="app-icon">📰</span>
            <span class="app-name">Affi Bangla</span>
          </a>
          <a href="https://manza.best-travel.ltd" target="_blank" class="hub-app-card">
            <span class="app-icon">📰</span>
            <span class="app-name">Manza News</span>
          </a>
          <a href="https://gixsam.best-travel.ltd" target="_blank" class="hub-app-card">
            <span class="app-icon">💼</span>
            <span class="app-name">GixSam Site</span>
          </a>
          <a href="gixsam_editor.php" class="hub-app-card" style="border-color:rgba(252,185,0,0.3);">
            <span class="app-icon">📝</span>
            <span class="app-name">GixSam Editor</span>
          </a>
        </div>
      </div>
    </div>
    
    <!-- 4. Logout Button -->
    <a href="logout.php" class="nav-btn-logout" title="Sign Out">
      <span>Logout</span>
    </a>

    <!-- Mobile Drawer Trigger -->
    <button class="hamburger-btn" onclick="toggleDrawer(true)">☰</button>

  </div>
  </div>
</nav>

<script>
// Click outside listener for dropdowns
document.addEventListener('click', function(e) {
  const hubDropdown = document.querySelector('.app-hub-dropdown');
  const hubMenu = document.getElementById('app-hub-menu');
  if (hubDropdown && hubMenu && !hubDropdown.contains(e.target)) {
    hubMenu.classList.remove('show-hub');
  }

  const notifDropdown = document.querySelector('.admin-notif-dropdown');
  const notifMenu = document.getElementById('admin-notif-menu');
  if (notifDropdown && notifMenu && !notifDropdown.contains(e.target)) {
    notifMenu.classList.remove('show-notif');
  }
});

function toggleDrawer(open) {
  const overlay = document.getElementById('mobile-drawer-overlay');
  const drawer = document.getElementById('mobile-drawer');
  if (open) {
    overlay.style.display = 'block';
    drawer.style.transform = 'translateX(0)';
  } else {
    overlay.style.display = 'none';
    drawer.style.transform = 'translateX(-100%)';
  }
}
</script>

<!-- Mobile Navigation Drawer -->
<div id="mobile-drawer-overlay" onclick="toggleDrawer(false)" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.75); backdrop-filter:blur(8px); z-index:99998;"></div>
<div id="mobile-drawer" style="position:fixed; top:0; left:0; width:310px; height:100vh; background:radial-gradient(circle at top, #14182b 0%, #080911 100%); z-index:99999; transform:translateX(-100%); transition:transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); display:flex; flex-direction:column; padding:1.5rem; box-shadow:5px 0 35px rgba(0,0,0,0.9); border-right:1px solid rgba(252,185,0,0.25);">
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; border-bottom:1px solid rgba(255,255,255,0.08); padding-bottom:0.8rem;">
    <span style="font-family:'Oswald',sans-serif; font-size:1.35rem; font-weight:800; color:var(--gold);">⚡ <?= htmlspecialchars(mb_strtoupper($admin_header_name, 'UTF-8')) ?></span>
    <button onclick="toggleDrawer(false)" style="background:none; border:none; color:#fff; font-size:1.6rem; cursor:pointer;">&times;</button>
  </div>
  
  <div style="display:flex; flex-direction:column; gap:0.4rem; overflow-y:auto; flex:1;">
    <?php foreach ($nav_items_def as $key => $item): ?>
      <?php if (isset($item['dropdown'])): ?>
        <div style="margin-bottom:0.4rem;">
          <div style="font-size:0.75rem; font-weight:800; color:var(--gold); text-transform:uppercase; margin:0.6rem 0 0.3rem 0.5rem; letter-spacing:0.5px;">
            <?= htmlspecialchars($item['title']) ?>
          </div>
          <?php foreach ($item['dropdown'] as $subItem): 
              $target = isset($subItem['target']) ? 'target="' . htmlspecialchars($subItem['target']) . '"' : '';
          ?>
            <a href="<?= htmlspecialchars($subItem['link']) ?>" <?= $target ?> style="color:#cbd5e1; text-decoration:none; padding:0.55rem 0.8rem; border-radius:8px; display:block; font-size:0.85rem; font-weight:600; transition:0.2s;">
              <?= htmlspecialchars($subItem['title']) ?>
            </a>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <a href="<?= htmlspecialchars($item['link']) ?>" style="color:#fff; text-decoration:none; padding:0.65rem 0.8rem; border-radius:8px; display:block; font-size:0.9rem; font-weight:700; background:rgba(255,255,255,0.04); margin-bottom:0.3rem;">
          <?= htmlspecialchars($item['title']) ?>
        </a>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>

  <div style="border-top:1px solid rgba(255,255,255,0.08); padding-top:1rem; margin-top:1rem; display:flex; flex-direction:column; gap:0.6rem;">
    <a href="impersonate_official.php?redirect=dashboard.php" style="background:rgba(255,255,255,0.08); border:1px solid var(--gold); color:var(--gold); font-weight:800; font-size:0.85rem; padding:0.65rem; border-radius:8px; text-decoration:none; text-align:center;">
      🏪 <?= htmlspecialchars($official_shop_name) ?>
    </a>
    <a href="logout.php" style="background:rgba(255,82,82,0.15); color:#ff5252; font-weight:700; font-size:0.85rem; padding:0.65rem; border-radius:8px; text-decoration:none; text-align:center;">
      Logout
    </a>
  </div>
</div>
<script src="/assets/js/pull_to_refresh.js"></script>
