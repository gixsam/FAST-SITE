<?php
// =========================================================================
// user/dashboard.php  –  v3: Premium Dashboard Overhaul
// =========================================================================
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: /user/login.php'); exit; }
require_once __DIR__ . '/../config.php';

$userId = (int)$_SESSION['user_id'];
$u = $pdo->prepare("SELECT * FROM users WHERE id=:id LIMIT 1");
$u->execute([':id'=>$userId]);
$user = $u->fetch();
if (!$user) { session_destroy(); header('Location: /user/login.php'); exit; }

$msg = $err = '';

// Handle Profile & Social Update

// ── Handle Custom Ref Code Update ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_ref_code'])) {
    $code = strtoupper(preg_replace('/[^A-Z0-9]/i', '', trim($_POST['custom_ref_code'] ?? '')));
    if (strlen($code) < 3 || strlen($code) > 15) {
        $error = "Referral code must be 3-15 alphanumeric characters.";
    } else {
        $dup = $pdo->prepare("SELECT id FROM users WHERE ref_code = :c AND id != :id");
        $dup->execute([':c' => $code, ':id' => $userId]);
        if ($dup->fetch()) {
            $error = "Referral code '$code' is already taken. Please choose another.";
        } else {
            $pdo->prepare("UPDATE users SET ref_code = :c WHERE id = :id")->execute([':c' => $code, ':id' => $userId]);
            $success = "Referral code successfully updated!";
            $user['ref_code'] = $code;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $name      = trim($_POST['name'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $whatsapp  = trim($_POST['whatsapp'] ?? '');
    $facebook  = trim($_POST['facebook'] ?? '');
    $instagram = trim($_POST['instagram'] ?? '');
    $twitter   = trim($_POST['twitter'] ?? '');
    $youtube   = trim($_POST['youtube'] ?? '');
    $newPass   = $_POST['new_password'] ?? '';
    
    if (!$name || !$phone) {
        $err = 'Name and Phone Number are required.';
    } else {
        // Check duplicate phone
        $chk = $pdo->prepare("SELECT id FROM users WHERE phone=:p AND id!=:id");
        $chk->execute([':p'=>$phone, ':id'=>$userId]);
        if ($chk->fetch()) {
            $err = 'This phone number is already registered.';
        } else {
            $sql = "UPDATE users SET name=:n, phone=:p, whatsapp=:wa, facebook=:fb, instagram=:ig, twitter=:tw, youtube=:yt";
            $params = [
                ':n'   => $name,
                ':p'   => $phone,
                ':wa'  => $whatsapp ?: null,
                ':fb'  => $facebook ?: null,
                ':ig'  => $instagram ?: null,
                ':tw'  => $twitter ?: null,
                ':yt'  => $youtube ?: null,
                ':id'  => $userId
            ];
            
            if ($newPass !== '') {
                if (strlen($newPass) < 6) {
                    $err = 'Password must be at least 6 characters.';
                } else {
                    $sql .= ", password_hash=:h";
                    $params[':h'] = password_hash($newPass, PASSWORD_BCRYPT);
                }
            }
            
            if (!$err) {
                $sql .= " WHERE id=:id";
                $pdo->prepare($sql)->execute($params);
                $msg = 'Profile and social accounts updated successfully!';
                
                // Refresh local user data
                $u = $pdo->prepare("SELECT * FROM users WHERE id=:id LIMIT 1");
                $u->execute([':id'=>$userId]);
                $user = $u->fetch();
            }
        }
    }
}

// Check pending cash
$pc = $pdo->prepare("SELECT SUM(amount) AS total_pending FROM user_pending_cash WHERE user_id=:id AND status='pending'");
$pc->execute([':id'=>$userId]);
$pendingRow = $pc->fetch();
$totalPending = round((float)($pendingRow['total_pending'] ?? 0), 2);

// Fetch user's orders
$ordersStmt = $pdo->prepare("
    SELECT a.*, s.name AS service_name
    FROM applications a
    JOIN services s ON a.service_id = s.id
    WHERE a.user_phone = :phone
    ORDER BY a.created_at DESC
");
$ordersStmt->execute([':phone' => $user['phone']]);
$userOrders = $ordersStmt->fetchAll();

$ordersCount = count($userOrders);
$activeOrders = 0;
$completedOrders = 0;
foreach ($userOrders as $o) {
    if (in_array($o['status'], ['pending', 'processing', 'in_progress', 'review'])) $activeOrders++;
    if ($o['status'] === 'approved' || $o['status'] === 'completed') $completedOrders++;
}

// Fetch user's store purchases (partner_orders)
$userPartnerOrders = [];
try {
    $po_stmt = $pdo->prepare("
        SELECT o.*, p.title AS product_title, p.price AS product_price, pt.business_name AS partner_name
        FROM partner_orders o
        JOIN partner_products p ON o.product_id = p.id
        JOIN partners pt ON o.partner_id = pt.id
        WHERE o.customer_id = :uid
        ORDER BY o.created_at DESC
        LIMIT 20
    ");
    $po_stmt->execute([':uid' => $userId]);
    $userPartnerOrders = $po_stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($userPartnerOrders as $po) {
        $ordersCount++;
        if (in_array($po['status'], ['pending', 'accepted', 'in_progress', 'waiting_confirmation', 'disputed'])) $activeOrders++;
        if ($po['status'] === 'completed' || $po['status'] === 'delivered') $completedOrders++;
    }
} catch (Exception $e) {}

// Partner Shop Data
$isPartner = false;
$partnerInfo = null;
$partner_products = [];
$p_stmt = $pdo->prepare("SELECT * FROM partners WHERE (user_id = :uid OR phone = :p) AND (status IS NULL OR status != 'suspended') ORDER BY id DESC LIMIT 1");
$p_stmt->execute([':uid' => $userId, ':p' => $user['phone']]);

$unread_stmt = $pdo->prepare("SELECT COUNT(id) FROM user_notifications WHERE user_id=? AND is_read=0");
$unread_stmt->execute([$userId]);
$unreadCount = $unread_stmt->fetchColumn();

if ($partnerRow = $p_stmt->fetch()) {
    $isPartner = true;
    $partnerInfo = $partnerRow;
    
    // Fetch Partner Stats
    $stmt_prod = $pdo->prepare("SELECT COUNT(*) FROM partner_products WHERE partner_id = :id");
    $stmt_prod->execute([':id' => $partnerInfo['id']]);
    $partner_product_count = $stmt_prod->fetchColumn();

    $stmt_orders = $pdo->prepare("SELECT COUNT(*) FROM partner_orders WHERE partner_id = :id AND status IN ('pending', 'accepted', 'in_progress', 'waiting_confirmation', 'disputed')");
    $stmt_orders->execute([':id' => $partnerInfo['id']]);
    $partner_active_orders = $stmt_orders->fetchColumn();

    $partner_total_earned = floatval($partnerInfo['total_earned']);
    
    // Fetch products list with HD image resolution
    $stmt_products = $pdo->prepare("
        SELECT p.*,
               pt.business_name AS shop_name,
               (SELECT image_url FROM partner_product_images WHERE product_id = p.id AND is_thumbnail = 1 LIMIT 1) AS thumb,
               (SELECT image_url FROM partner_product_images WHERE product_id = p.id ORDER BY id ASC LIMIT 1) AS fallback_img
        FROM partner_products p
        LEFT JOIN partners pt ON pt.id = p.partner_id
        WHERE p.partner_id = :id 
        ORDER BY p.created_at DESC
    ");
    $stmt_products->execute([':id' => $partnerInfo['id']]);
    $partner_products = $stmt_products->fetchAll(PDO::FETCH_ASSOC);
    foreach ($partner_products as $k => $p) {
        $partner_products[$k]['display_thumb'] = resolveProductArtwork(
            $p['thumb'] ?? '',
            $p['fallback_img'] ?? '',
            $p['shop_name'] ?? '',
            $p['category'] ?? '',
            $p['title'] ?? '',
            $p['listing_type'] ?? 'product'
        );
    }
}

$latest_marketplace_products = [];
if (!$isPartner) {
    try {
        $stmt_all_products = $pdo->query("
            SELECT p.*,
                   COALESCE(pt.business_name, 'Fast Site Official') AS shop_name,
                   (SELECT image_url FROM partner_product_images WHERE product_id = p.id AND is_thumbnail = 1 LIMIT 1) AS thumb,
                   (SELECT image_url FROM partner_product_images WHERE product_id = p.id ORDER BY id ASC LIMIT 1) AS fallback_img
            FROM partner_products p
            LEFT JOIN partners pt ON pt.id = p.partner_id
            WHERE p.is_published = 1 
            ORDER BY p.created_at DESC 
            LIMIT 4
        ");
        $latest_marketplace_products = $stmt_all_products->fetchAll(PDO::FETCH_ASSOC);
        foreach ($latest_marketplace_products as $k => $p) {
            $latest_marketplace_products[$k]['display_thumb'] = resolveProductArtwork(
                $p['thumb'] ?? '',
                $p['fallback_img'] ?? '',
                $p['shop_name'] ?? '',
                $p['category'] ?? '',
                $p['title'] ?? '',
                $p['listing_type'] ?? 'product'
            );
        }
    } catch(Exception $e) {}
}

// Agent / Affiliate Data
$isAgent = false;
$agentInfo = null;
$total_referrals = 0;

try {
    $a_stmt = $pdo->prepare("SELECT * FROM agents WHERE phone=:p AND status='active' LIMIT 1");
    $a_stmt->execute([':p' => $user['phone']]);
    if ($agentRow = $a_stmt->fetch()) {
        $isAgent = true;
        $agentInfo = $agentRow;
        
        // Get total referrals
        if (!empty($user['ref_code'])) {
            $ref_stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE ref_by = :ref");
            $ref_stmt->execute([':ref' => $user['ref_code']]);
            $total_referrals = (int)$ref_stmt->fetchColumn();
        }
    }
} catch (Exception $e) {}
// Calculate profile completion percentage
$completion_percentage = 0;
// Fixed 20% for profile pic
if (!empty($user['profile_pic'])) $completion_percentage += 20;
// Fixed 20% for NID photo
if (!empty($user['nid'])) $completion_percentage += 20;

$other_fields = ['name', 'phone', 'email', 'dob', 'gender', 'nid_number', 'etin', 'passport', 'driving_license', 'whatsapp'];
$each_field_score = 60 / count($other_fields); // 6% each
foreach ($other_fields as $field) {
    if (!empty($user[$field])) {
        $completion_percentage += $each_field_score;
    }
}
$completion_percentage = round($completion_percentage);

// Referral URL
if (empty($user['ref_code'])) {
    $newRefCode = 'FS' . strtoupper(substr(md5($userId . '_fastsite_' . time()), 0, 6));
    try {
        $pdo->prepare("UPDATE users SET ref_code = ? WHERE id = ?")->execute([$newRefCode, $userId]);
    } catch(Exception $e) {}
    $user['ref_code'] = $newRefCode;
}
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
$baseUrl = $protocol . '://' . $host . '/ref.php?ref=' . urlencode($user['ref_code']);

$settings = $pdo->query("SELECT setting_key, setting_value FROM homepage_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
$siteName = $settings['site_name'] ?? 'Fast Site';
$notice = $settings['global_notice'] ?? '';

// Helper for timeline status representation
function renderTimeline($status) {
    $statusKey = strtolower(trim($status ?? 'pending'));
    if ($statusKey === 'cancelled' || $statusKey === 'rejected') {
        return '
        <div class="timeline-container cancelled" style="display:flex; align-items:center; gap:8px; background:rgba(255,82,82,0.1); border:1px solid rgba(255,82,82,0.25); padding:0.6rem 0.8rem; border-radius:8px; margin-top:0.6rem;">
            <span style="color:#ff5252; font-size:1.1rem;">✕</span>
            <div class="timeline-content">
                <span style="font-weight:700; color:#ff5252; font-size:0.82rem;">Order Cancelled</span>
                <span style="font-size:0.72rem; color:var(--muted); display:block;">Contact support for details.</span>
            </div>
        </div>';
    }

    $currentIdx = 0;
    if (in_array($statusKey, ['processing', 'in_progress', 'review', 'accepted', 'shipped'])) $currentIdx = 1;
    if (in_array($statusKey, ['approved', 'completed', 'delivered'])) $currentIdx = 2;

    $steps = ['Pending', 'Processing', 'Completed'];

    $html = '<div class="timeline-flow">';
    $idx = 0;
    foreach ($steps as $key => $label) {
        $activeClass = $idx <= $currentIdx ? 'active' : '';
        $currentClass = $idx === $currentIdx ? 'current' : '';
        $html .= "
        <div class='timeline-node {$activeClass} {$currentClass}'>
            <div class='node-circle'></div>
            <span class='node-label'>{$label}</span>
        </div>";
        $idx++;
    }
    $html .= '</div>';
    return $html;
}

// Calculate user referral metrics and Loyalty Tier
$refCount = 0;
if (!empty($user['ref_code'])) {
    try {
        $st_rc = $pdo->prepare("SELECT COUNT(*) FROM users WHERE ref_by = ?");
        $st_rc->execute([$user['ref_code']]);
        $refCount = (int)$st_rc->fetchColumn();
    } catch(Exception $e) {}
}

$loyalty_tier = 'Bronze';
$loyalty_badge = '🥉 Bronze Member';
$loyalty_bg = 'linear-gradient(135deg, rgba(205,127,50,0.18) 0%, rgba(13,13,20,0.95) 100%)';
$loyalty_border = 'rgba(205,127,50,0.35)';

if ($refCount >= 100) {
    $loyalty_tier = 'Diamond';
    $loyalty_badge = '💎 Diamond Legend';
    $loyalty_bg = 'linear-gradient(135deg, rgba(179,136,255,0.22) 0%, rgba(13,13,20,0.95) 100%)';
    $loyalty_border = 'rgba(179,136,255,0.45)';
} elseif ($refCount >= 50) {
    $loyalty_tier = 'Platinum';
    $loyalty_badge = '👑 Platinum Elite';
    $loyalty_bg = 'linear-gradient(135deg, rgba(0,229,255,0.2) 0%, rgba(13,13,20,0.95) 100%)';
    $loyalty_border = 'rgba(0,229,255,0.4)';
} elseif ($refCount >= 25) {
    $loyalty_tier = 'Gold';
    $loyalty_badge = '🥇 Gold Master';
    $loyalty_bg = 'linear-gradient(135deg, rgba(252,185,0,0.2) 0%, rgba(13,13,20,0.95) 100%)';
    $loyalty_border = 'rgba(252,185,0,0.45)';
} elseif ($refCount >= 5) {
    $loyalty_tier = 'Silver';
    $loyalty_badge = '🥈 Silver VIP';
    $loyalty_bg = 'linear-gradient(135deg, rgba(192,192,192,0.18) 0%, rgba(13,13,20,0.95) 100%)';
    $loyalty_border = 'rgba(192,192,192,0.4)';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover"/>
  <meta name="apple-mobile-web-app-capable" content="yes"/>
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent"/>
  <meta name="theme-color" content="#0A0D1A"/>
  <title>My Dashboard — <?= htmlspecialchars($siteName) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Oswald:wght@600;700&display=swap" rel="stylesheet"/>
  
  <link rel="stylesheet" href="/assets/css/native_mobile.css?v=<?= time() ?>">
  <link rel="stylesheet" href="/assets/css/user.css?v=<?= time() ?>">
  <script src="/assets/js/app_environment.js" defer></script>
  <style>
  @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');
  :root {
    --brand: #2196F3;
    --brand-glow: rgba(33, 150, 243, 0.2);
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
  
  /* ─── PREVENT WALL-TO-WALL EDGE STRETCHING ON DESKTOP ─── */
  .dashboard-container {
    max-width: 1100px !important;
    margin: 0 auto !important;
    padding-left: 1.5rem !important;
    padding-right: 1.5rem !important;
    padding-bottom: 4rem !important;
    width: 100% !important;
    box-sizing: border-box !important;
  }
  
  .hero {
    max-width: 1100px !important;
    margin: 0 auto 1.5rem auto !important;
    background: rgba(16, 18, 28, 0.85) !important;
    border: 1px solid rgba(33, 150, 243, 0.25) !important;
    border-radius: 20px !important;
    padding: 1.5rem 1.8rem !important;
    box-shadow: 0 10px 40px rgba(0,0,0,0.5) !important;
    backdrop-filter: blur(12px) !important;
    width: 100% !important;
    box-sizing: border-box !important;
  }
  
  .wrap {
    max-width: 1100px !important;
    margin: 0 auto !important;
    width: 100% !important;
    box-sizing: border-box !important;
  }
  
  .notice-marquee {
    max-width: 1100px !important;
    margin: 0 auto !important;
    border-radius: 12px !important;
  }

  /* Custom Functional Switches */
  .fs-switch {
    position: relative;
    display: inline-block;
    width: 48px;
    height: 26px;
    flex-shrink: 0;
  }
  .fs-switch input {
    opacity: 0;
    width: 0;
    height: 0;
  }
  .fs-slider {
    position: absolute;
    cursor: pointer;
    top: 0; left: 0; right: 0; bottom: 0;
    background-color: #2a2a3e;
    transition: .3s cubic-bezier(0.4, 0, 0.2, 1);
    border-radius: 30px;
    border: 1px solid rgba(255,255,255,0.15);
  }
  .fs-slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: #fff;
    transition: .3s cubic-bezier(0.4, 0, 0.2, 1);
    border-radius: 50%;
    box-shadow: 0 2px 5px rgba(0,0,0,0.4);
  }
  .fs-switch input:checked + .fs-slider {
    background-color: #2196F3;
    border-color: #2196F3;
  }
  .fs-switch input:checked + .fs-slider:before {
    transform: translateX(22px);
    background-color: #fff;
  }
  </style>
</head>
<body class="dashboard-mode">
<?php include __DIR__ . '/../includes/user_sidebar.php'; ?>

<script>
  function updateShopAnalytics(filter) {
    const statUploads = document.getElementById('stat-uploads');
    const statOrders = document.getElementById('stat-orders');
    const statEarned = document.getElementById('stat-earned');
    
    if(!statUploads) return;

    // Loading animation
    statUploads.innerHTML = '...';
    statOrders.innerHTML = '...';
    statEarned.innerHTML = '...';

    fetch('/api/shop_analytics.php?filter=' + filter)
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          statUploads.innerHTML = data.uploads;
          statOrders.innerHTML = data.active_orders;
          statEarned.innerHTML = '৳' + Number(data.total_earned).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }
      })
      .catch(err => console.error(err));
  }
</script>

<?php if($notice): ?>
<div class="notice-marquee">
  <span style="background:#fff;color:#ff004c;padding:2px 8px;border-radius:50px;font-size:.65rem;margin-right:10px;z-index:2;position:relative;">NOTICE</span>
  <marquee scrollamount="5" style="flex:1;"><?= htmlspecialchars($notice) ?></marquee>
</div>
<?php endif; ?>

<div class="dashboard-container" style="padding-top: <?= $notice ? '105px' : '75px' ?>; padding-bottom: 110px !important;">
<!-- ── HERO HEADER WITH DYNAMIC LOYALTY BADGE & GRADIENT BACKGROUND ── -->
<div class="hero" style="border-radius: 0 0 30px 30px; box-shadow: 0 10px 40px rgba(0,0,0,0.5); border-bottom: 1px solid <?= $loyalty_border ?>; background: <?= $loyalty_bg ?> !important;">
  <!-- Row 1: Avatar + Name + Loyalty Badge + Member ID + Invite Code -->
  <div style="display:flex; align-items:center; gap:0.9rem; margin-bottom:1rem;">
      <a href="/user/profile.php" style="text-decoration:none; flex-shrink:0; position:relative;" title="Edit Profile">
          <div class="profile-circle" style="position:relative; width:76px; height:76px; border-radius:50%; border:3px solid var(--gold); overflow:hidden; background:rgba(255,255,255,0.05); display:flex; align-items:center; justify-content:center; box-shadow: 0 0 18px rgba(252, 185, 0, 0.45); flex-shrink:0;">
              <?php 
                $u_pic = !empty($user['profile_pic']) ? '/' . ltrim($user['profile_pic'], '/') : '/assets/images/default_avatar.png'; 
              ?>
              <img src="<?= htmlspecialchars($u_pic) ?>" alt="" style="width:100%; height:100%; object-fit:cover;" onerror="this.onerror=null; this.src='/assets/images/default_avatar.png';">
              <div style="position:absolute; bottom:0; left:0; right:0; background:rgba(0,0,0,0.88); color:var(--gold); font-size:0.6rem; font-weight:800; text-align:center; padding:2px 0;">Profile <?= $completion_percentage ?>%</div>
          </div>
      </a>
      <div style="flex:1; min-width:0;">
          <span class="welcome-lbl" style="color:var(--brand); font-weight:700; font-size:0.75rem; letter-spacing:0.5px; text-transform:uppercase;">Welcome Back</span>
          
          <div style="display:flex; align-items:center; gap:6px; flex-wrap:nowrap; margin:2px 0 4px 0; overflow:hidden;">
              <h2 class="user-name" style="font-size:clamp(0.9rem, 3.4vw, 1.08rem); font-weight:900; margin:0; line-height:1.2; color:#fff; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:175px;"><?= htmlspecialchars($user['name']) ?></h2>
              <span style="font-size:0.65rem; font-weight:800; padding:2px 7px; border-radius:20px; background:rgba(255,255,255,0.08); border:1px solid <?= $loyalty_border ?>; color:#fff; white-space:nowrap; flex-shrink:0;">
                  <?= $loyalty_badge ?>
              </span>
          </div>
          
          <div style="display:flex; flex-wrap:wrap; align-items:center; gap:0.35rem; margin-top:2px;">
            <div style="font-size:0.65rem; color:#fff; font-family:monospace; background:rgba(255,255,255,0.1); border:1px solid rgba(255,255,255,0.2); padding:2px 6px; border-radius:6px; display:inline-block; white-space:nowrap;" title="Member Identification Number">
              Member ID: #<?= htmlspecialchars($user['registration_number'] ?: $user['id']) ?>
            </div>
            <!-- Invite code + share button -->
            <div style="display:flex; align-items:center; gap:0.3rem; flex-shrink:0;">
              <?php
                $raw_ref = $user['ref_code'] ?? '';
                $disp_ref = (strlen($raw_ref) > 13) ? substr($raw_ref, 0, 11) . '..' : $raw_ref;
              ?>
              <span style="font-size:0.65rem; color:var(--gold); font-weight:700; background:rgba(252,185,0,0.15); border:1px solid rgba(252,185,0,0.3); padding:2px 6px; border-radius:6px; white-space:nowrap;">
                Invite Code: <?= htmlspecialchars($disp_ref) ?>
              </span>
              <button onclick="copyLink('reflink-quick', this)" style="background:rgba(255,255,255,0.1); border:1px solid rgba(255,255,255,0.2); cursor:pointer; color:#fff; display:flex; align-items:center; padding:3px 5px; border-radius:4px; transition:0.2s;" title="Copy Invite Link">
                <svg viewBox="0 0 24 24" width="12" height="12" fill="currentColor"><path d="M18 16.08c-.76 0-1.44.3-1.96.77L8.91 12.7c.05-.23.09-.46.09-.7s-.04-.47-.09-.7l7.05-4.11c.54.5 1.25.81 2.04.81 1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3c0 .24.04.47.09.7L8.04 9.81C7.5 9.31 6.79 9 6 9c-1.66 0-3 1.34-3 3s1.34 3 3 3c.79 0 1.5-.31 2.04-.81l7.12 4.16c-.05.21-.08.43-.08.65 0 1.61 1.31 2.92 2.92 2.92 1.61 0 2.92-1.31 2.92-2.92z"/></svg>
              </button>
            </div>
          </div>
      </div>
  </div>

  <!-- Row 2: Daily Check-in + Available Balance side by side, full width -->
  <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.65rem; margin-bottom:1rem;">
    <!-- Daily Check-in Box -->
    <a href="/user/missions.php" style="text-decoration:none; background:linear-gradient(135deg, rgba(255,82,82,0.14) 0%, rgba(255,82,82,0.04) 100%); border:1px solid rgba(255,82,82,0.35); padding:0.75rem 0.5rem; border-radius:14px; display:flex; align-items:center; justify-content:center; gap:8px; box-shadow:inset 0 0 20px rgba(255,82,82,0.08); min-width:0; overflow:hidden;" title="Check-in daily to claim bonus points">
      <span style="font-size:1.45rem; filter:drop-shadow(0 0 8px rgba(255,82,82,0.6)); flex-shrink:0;">🔥</span>
      <div style="min-width:0; text-align:left;">
        <div style="font-size:0.62rem; color:#ff8a80; text-transform:uppercase; font-weight:800; letter-spacing:0.04em; white-space:nowrap; margin-bottom:2px;">Daily Check-in</div>
        <div style="font-size:1.15rem; font-weight:900; color:#fff; line-height:1; white-space:nowrap;">Day <?= (int)($user['current_streak'] ?? 0) ?></div>
      </div>
    </a>
    <!-- Available Balance Box (Links to /user/wallet.php) -->
    <a href="/user/wallet.php" style="text-decoration:none; background:linear-gradient(135deg, rgba(252,185,0,0.14) 0%, rgba(252,185,0,0.04) 100%); border:1px solid rgba(252,185,0,0.35); padding:0.75rem 0.5rem; border-radius:14px; display:flex; align-items:center; justify-content:center; gap:8px; box-shadow:inset 0 0 20px rgba(252,185,0,0.08); cursor:pointer; transition:all 0.2s; min-width:0; overflow:hidden;" title="View wallet and manage money">
      <span style="font-size:1.45rem; filter:drop-shadow(0 0 8px rgba(252,185,0,0.6)); flex-shrink:0;">🪙</span>
      <div style="min-width:0; text-align:left;">
        <div style="font-size:0.62rem; color:var(--gold); text-transform:uppercase; font-weight:800; letter-spacing:0.04em; display:flex; align-items:center; gap:2px; white-space:nowrap; margin-bottom:2px;">Available Balance ৳ <span>↗</span></div>
        <div style="font-size:clamp(0.82rem, 3.0vw, 1.05rem); font-weight:900; color:#fff; line-height:1; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">৳ <?= number_format($user['coins_balance'] ?? 0, 2) ?></div>
      </div>
    </a>
  </div>

  <!-- Row 3: Orders Summary (Clickable, switches cleanly to tab-orders) -->
  <div onclick="switchUserTab('orders')" style="cursor:pointer; background:rgba(0,0,0,0.4); border:1px solid rgba(255,255,255,0.08); border-radius:16px; padding:0.85rem 0.5rem; display:grid; grid-template-columns:repeat(3, 1fr); gap:0.4rem; text-align:center;" title="Click to view all orders & tracking">
    <div style="position:relative; min-width:0;">
        <div style="font-size:1.45rem; font-weight:900; color:#fff; line-height:1.1;"><?= $ordersCount ?></div>
        <div style="font-size:clamp(0.58rem, 2vw, 0.68rem); color:var(--muted); text-transform:uppercase; font-weight:800; margin-top:3px; letter-spacing:0.3px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">Total Orders</div>
    </div>
    <div style="position:relative; min-width:0; border-left:1px solid rgba(255,255,255,0.08); border-right:1px solid rgba(255,255,255,0.08);">
        <div style="font-size:1.45rem; font-weight:900; color:var(--brand); line-height:1.1;"><?= $activeOrders ?></div>
        <div style="font-size:clamp(0.58rem, 2vw, 0.68rem); color:var(--brand); text-transform:uppercase; font-weight:800; margin-top:3px; letter-spacing:0.3px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">In Progress</div>
    </div>
    <div style="position:relative; min-width:0;">
        <div style="font-size:1.45rem; font-weight:900; color:var(--green); line-height:1.1;"><?= $completedOrders ?></div>
        <div style="font-size:clamp(0.58rem, 2vw, 0.68rem); color:var(--green); text-transform:uppercase; font-weight:800; margin-top:3px; letter-spacing:0.3px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">Completed</div>
    </div>
  </div>
</div>

<div class="wrap">
<?php if(isset($_SESSION['streak_reward_msg'])): ?>
<div style="background: rgba(0, 230, 118, 0.1); border: 1px solid rgba(0, 230, 118, 0.3); color: #00e676; padding: 0.8rem; border-radius: 12px; margin-bottom: 1.5rem; text-align: center; font-weight: 700; display:flex; align-items:center; justify-content:center; gap:8px;">
    <span>🎁</span> <?= htmlspecialchars($_SESSION['streak_reward_msg']) ?>
</div>
<?php unset($_SESSION['streak_reward_msg']); endif; ?>

<?php if (isset($user['missed_commissions']) && $user['missed_commissions'] > 0 && !$isAgent): ?>
  <div style="background: rgba(252, 185, 0, 0.08); border: 1px solid rgba(252, 185, 0, 0.3); border-radius: 14px; padding: 1.3rem; margin-bottom: 1.8rem; position: relative; overflow: hidden;">
    <div style="position: absolute; top: -20px; right: -20px; font-size: 5rem; opacity: 0.08;">💸</div>
    <h3 style="color: var(--gold); margin-bottom: 0.5rem; display: flex; align-items: center; gap: 8px; font-size: 1.1rem;">
      <span style="font-size: 1.4rem;">💡</span> You have referral earnings ready! ৳<?= number_format($user['missed_commissions'], 2) ?>
    </h3>
    <p style="color: #e8e8f0; font-size: 0.85rem; line-height: 1.5; margin-bottom: 1rem;">
      Friends and clients ordered through your shared links! Join our <strong>Affiliate Partner</strong> program for free to claim and withdraw your cash earnings directly to bKash or Nagad.
    </p>
    <a href="/user/become_agent.php" style="display: inline-block; background: linear-gradient(135deg, var(--gold), #ff9100); color: #000; font-weight: 800; padding: 0.6rem 1.3rem; border-radius: 8px; text-decoration: none; font-size: 0.85rem; box-shadow: 0 4px 15px rgba(252, 185, 0, 0.3); transition: transform 0.2s;">
      Become an Affiliate Partner ➔
    </a>
  </div>
<?php endif; ?>
  <?php if($msg): ?><div class="alert alert-ok">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if($err): ?><div class="alert alert-err">❌ <?= htmlspecialchars($err) ?></div><?php endif; ?>

  <!-- ══════════════════════════════════════ -->
  <!-- 1. 🎁 REWARDS & INVITES (QUICK HUB) -->
  <!-- ══════════════════════════════════════ -->
  <div class="premium-card" style="margin-bottom: 1.8rem; position: relative; overflow: hidden; border: 1px solid rgba(252, 185, 0, 0.35); background: linear-gradient(135deg, rgba(252, 185, 0, 0.08) 0%, rgba(20, 20, 31, 0.95) 100%); box-shadow: 0 10px 30px rgba(252, 185, 0, 0.15); border-radius: 20px; padding: 1.4rem;">
    <div style="position: absolute; top: -40px; right: -40px; width: 140px; height: 140px; background: radial-gradient(circle, rgba(252, 185, 0, 0.25) 0%, transparent 70%); border-radius: 50%;"></div>
    
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.6rem; margin-bottom:1rem; position:relative; z-index:2;">
        <div>
            <h2 style="margin:0; font-size:1.25rem; color:#fff; display:flex; align-items:center; gap:8px;">
                <span>🎁</span> <span style="background:linear-gradient(90deg, #fff, #fcb900); -webkit-background-clip:text; -webkit-text-fill-color:transparent; font-weight:900;">Rewards &amp; Invites</span>
            </h2>
            <p style="color:var(--muted); font-size:0.82rem; margin:4px 0 0 0;">Complete daily tasks, invite friends, and earn bonus rewards &amp; instant cash!</p>
        </div>
        <a href="/user/missions.php" class="btn" style="background:linear-gradient(135deg, var(--gold), #ff9100); color:#000; font-size:0.78rem; padding:0.45rem 1.1rem; border-radius:50px; text-decoration:none; font-weight:800; box-shadow:0 4px 15px rgba(252,185,0,0.3); white-space:nowrap;">
            🏆 Loyalty Rewards ➔
        </a>
    </div>

    <!-- Quick Referral Share Link -->
    <div style="background:rgba(0,0,0,0.45); border:1px solid rgba(252,185,0,0.25); border-radius:14px; padding:0.9rem; margin-bottom:1rem; display:flex; flex-direction:column; gap:0.5rem;">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.4rem;">
            <span style="font-size:0.82rem; font-weight:800; color:#fff; display:flex; align-items:center; gap:6px;">
                <span>🔗</span> Your Invite Link
            </span>
            <span style="background:rgba(0,230,118,0.15); color:#00e676; border:1px solid rgba(0,230,118,0.3); padding:2px 8px; border-radius:12px; font-size:0.7rem; font-weight:800;">
                ⚡ 20% Instant Bonus
            </span>
        </div>
        <div style="display:flex; gap:0.5rem; align-items:center; flex-wrap:wrap;">
            <input id="reflink-quick" readonly value="<?= htmlspecialchars($baseUrl) ?>" style="flex:1; min-width:200px; background:#08080c; border:1px solid rgba(252,185,0,0.3); color:#fff; padding:0.6rem 0.85rem; border-radius:8px; font-family:monospace; font-size:0.8rem; outline:none;" />
            <button id="copyRefBtn-quick" onclick="copyLink('reflink-quick', this)" style="background:linear-gradient(135deg, var(--gold), #ff9100); color:#000; font-weight:800; border:none; padding:0.6rem 1.1rem; border-radius:8px; cursor:pointer; font-size:0.8rem; display:inline-flex; align-items:center; gap:0.4rem; white-space:nowrap;">
                📋 Copy Link
            </button>
        </div>
    </div>

    <!-- 3 Quick Action Mission Buttons -->
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap:0.6rem;">
        <a href="/user/tasks.php" style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08); padding:0.75rem; border-radius:12px; text-decoration:none; display:flex; align-items:center; gap:8px; transition:0.2s;">
            <span style="font-size:1.5rem;">🎯</span>
            <div>
                <div style="color:#fff; font-size:0.8rem; font-weight:800;">Daily Tasks</div>
                <div style="color:var(--gold); font-size:0.68rem; font-weight:700;">Earn Bonus Points</div>
            </div>
        </a>
        <a href="/user/missions.php" style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08); padding:0.75rem; border-radius:12px; text-decoration:none; display:flex; align-items:center; gap:8px; transition:0.2s;">
            <span style="font-size:1.5rem;">🏆</span>
            <div>
                <div style="color:#fff; font-size:0.8rem; font-weight:800;">Loyalty Rewards</div>
                <div style="color:#00e676; font-size:0.68rem; font-weight:700;"><?= $loyalty_badge ?></div>
            </div>
        </a>
        <a href="/user/wallet.php?action=withdraw" style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08); padding:0.75rem; border-radius:12px; text-decoration:none; display:flex; align-items:center; gap:8px; transition:0.2s;">
            <span style="font-size:1.5rem;">💸</span>
            <div>
                <div style="color:#fff; font-size:0.8rem; font-weight:800;">Cash Out</div>
                <div style="color:#00b0ff; font-size:0.68rem; font-weight:700;">Withdraw Money</div>
            </div>
        </a>
    </div>
  </div>

  <!-- ══════════════════════════════════════ -->
  <!-- 2. PARTNER SHOP SECTION -->
  <!-- ══════════════════════════════════════ -->
  <div class="premium-card" id="marketplace-shopper-view" style="margin-bottom: 2rem; position: relative; overflow: hidden; border-color: rgba(33, 150, 243, 0.4); box-shadow: 0 10px 30px rgba(33, 150, 243, 0.15);">
    <!-- Decorative background element -->
    <div style="position: absolute; top: -50px; right: -50px; width: 150px; height: 150px; background: radial-gradient(circle, rgba(33, 150, 243, 0.2) 0%, transparent 70%); border-radius: 50%;"></div>
    
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; position:relative; z-index:2; flex-wrap:wrap; gap:0.6rem;">
        <h2 style="margin:0; font-size:1.3rem; color:#fff; display:flex; align-items:center; gap:8px;">
            <span style="color:var(--gold);">⚡</span> <span><?= htmlspecialchars($partnerInfo['business_name'] ?? 'FAST SITE') ?></span>
        </h2>
        <?php if ($isPartner): ?>
            <a href="/partner/dashboard.php?tab=orders" class="btn" style="background:rgba(255,255,255,0.1); border:1px solid rgba(255,255,255,0.2); color:#fff; font-size:0.75rem; padding:0.4rem 0.8rem; border-radius:8px; text-decoration:none; font-weight:700; transition:background 0.2s;">View Orders</a>
        <?php endif; ?>
    </div>

    <!-- ── 1.1 PRODUCTS / STOREFRONT SECTION ── -->
    <?php if ($isPartner && ($partnerInfo['status'] ?? 'approved') !== 'suspended'): ?>
        <!-- Track Shop Performance -->
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
            <h4 style="color:var(--muted); font-size:0.85rem; text-transform:uppercase; letter-spacing:1px; margin:0;">Analytics Overview</h4>
            <select id="shop-analytics-filter" style="background:rgba(0,0,0,0.5); border:1px solid rgba(33, 150, 243, 0.4); color:#fff; padding:0.3rem 0.6rem; border-radius:6px; font-size:0.75rem; outline:none; cursor:pointer;" onchange="updateShopAnalytics(this.value)">
                <option value="all">All Time</option>
                <option value="today">Today</option>
                <option value="week">This Week</option>
                <option value="month">This Month</option>
            </select>
        </div>
        
        <div style="background:rgba(0,0,0,0.3); border:1px solid rgba(255,255,255,0.05); border-radius:12px; padding:1.2rem; margin-bottom:1.5rem; display:grid; grid-template-columns:repeat(3, 1fr); gap:1rem; text-align:center;">
            <div>
                <div id="stat-uploads" style="font-size:1.6rem; font-weight:900; color:#fff;"><?= (int)$partner_product_count ?></div>
                <div style="font-size:0.7rem; color:var(--muted); text-transform:uppercase; font-weight:700; margin-top:4px;">Products Uploaded</div>
            </div>
            <div style="border-left:1px solid rgba(255,255,255,0.05); border-right:1px solid rgba(255,255,255,0.05);">
                <div id="stat-orders" style="font-size:1.6rem; font-weight:900; color:var(--brand);"><?= (int)$partner_active_orders ?></div>
                <div style="font-size:0.7rem; color:var(--brand); text-transform:uppercase; font-weight:700; margin-top:4px;">Active Orders</div>
            </div>
            <div>
                <div id="stat-earned" style="font-size:1.6rem; font-weight:900; color:#00e676;">৳<?= number_format($partner_total_earned, 2) ?></div>
                <div style="font-size:0.7rem; color:#00e676; text-transform:uppercase; font-weight:700; margin-top:4px;">Total Earned</div>
            </div>
        </div>

        <!-- UPLOAD PRODUCT CTA -->
        <div style="text-align:center; margin:2rem 0;">
            <a href="/partner/dashboard.php?tab=upload" class="btn" style="display:inline-flex; align-items:center; justify-content:center; gap:8px; background:linear-gradient(135deg, var(--brand), #007bb5); color:#fff; font-size:1.1rem; font-weight:800; padding:1rem 2.5rem; border-radius:50px; text-decoration:none; box-shadow:0 8px 25px rgba(33, 150, 243, 0.4); transition:transform 0.2s;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                UPLOAD PRODUCT
            </a>
        </div>

        <!-- Latest Products Showcase -->
        <h4 style="color:var(--muted); font-size:0.85rem; text-transform:uppercase; letter-spacing:1px; margin-bottom:1rem;">Manage your products</h4>
        <?php if(empty($partner_products)): ?>
            <div style="text-align:center; padding:1.5rem; background:rgba(255,255,255,0.02); border-radius:12px; border:1px dashed rgba(255,255,255,0.1);">
                <p style="color:var(--muted); margin:0;">You haven't uploaded any products yet.</p>
            </div>
        <?php else: ?>
            <div style="display:grid; grid-template-columns: repeat(2, 1fr); gap:1rem; margin-bottom:1.5rem;">
                <?php 
                $latest_prods = array_slice($partner_products, 0, 4);
                foreach($latest_prods as $prod): 
                ?>
                <div style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08); border-radius:12px; overflow:hidden; display:flex; flex-direction:column;">
                    <div style="height:100px; background:rgba(0,0,0,0.5); position:relative;">
                        <img src="<?= htmlspecialchars($prod['display_thumb']) ?>" style="width:100%; height:100%; object-fit:cover;" onerror="this.onerror=null; this.src='/assets/images/services/default_service.svg';">
                        <?php if($prod['is_published']): ?>
                            <span style="position:absolute; top:6px; right:6px; background:#00e676; color:#000; font-size:0.6rem; font-weight:800; padding:2px 6px; border-radius:4px;">LIVE</span>
                        <?php endif; ?>
                    </div>
                    <div style="padding: 0.75rem;">
                        <div style="font-size:0.8rem; font-weight:700; margin-bottom:0.3rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; color:#fff;"><?= htmlspecialchars($prod['title']) ?></div>
                        <div style="color:var(--gold); font-weight:800; font-size:0.85rem;">৳ <?= number_format($prod['price']) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Bottom Links -->
        <div style="display:flex; justify-content:center; gap:1.5rem; margin-top:2rem; border-top:1px solid rgba(255,255,255,0.1); padding-top:1.5rem;">
            <a href="/partner/dashboard.php" style="color:var(--brand); text-decoration:none; font-weight:700; font-size:0.9rem; display:flex; align-items:center; gap:4px;">
                Manage Shop ➡️
            </a>
            <a href="/partner/dashboard.php?tab=orders" style="color:var(--gold); text-decoration:none; font-weight:700; font-size:0.9rem; display:flex; align-items:center; gap:4px;">
                📦 Recent Shop Orders
            </a>
        </div>
    <?php else: ?>
        <!-- Latest Marketplace Products for Non-Partners -->
        <h4 style="color:var(--muted); font-size:0.85rem; text-transform:uppercase; letter-spacing:1px; margin-bottom:1rem;">Latest Marketplace Products</h4>
        <?php if(empty($latest_marketplace_products)): ?>
            <div style="text-align:center; padding: 2rem; background:rgba(255,255,255,0.02); border-radius:12px; border:1px dashed rgba(255,255,255,0.1);">
                <div style="font-size:3rem; margin-bottom:1rem;">🏪</div>
                <p style="color:#fff; font-weight:600; font-size:1.1rem; margin-bottom:0.5rem;">Start Selling on Fast Site</p>
                <p style="color:var(--muted); font-size:0.9rem; margin-bottom:1.5rem; max-width:300px; margin-left:auto; margin-right:auto;">Create your storefront today and start earning money by selling products and services.</p>
                <a href="/user/create_shop.php" class="btn" style="background:var(--brand); color:#fff; padding:0.8rem 1.5rem; text-decoration:none; border-radius:8px; font-weight:700;">Create Shop</a>
            </div>
        <?php else: ?>
            <div style="display:grid; grid-template-columns: repeat(2, 1fr); gap:1rem; margin-bottom:1.5rem;">
                <?php foreach($latest_marketplace_products as $prod): 
                    $isAff = ($prod['listing_type'] ?? '') === 'affiliate' || !empty($prod['affiliate_url']) || !empty($prod['affiliate_link']);
                    $affUrl = !empty($prod['affiliate_url']) ? $prod['affiliate_url'] : ($prod['affiliate_link'] ?? '');
                    $cardHref = ($isAff && !empty($affUrl)) ? htmlspecialchars($affUrl) : "/product_detail.php?id={$prod['id']}";
                    $cardTarget = ($isAff && !empty($affUrl)) ? 'target="_blank" rel="noopener noreferrer"' : '';
                ?>
                <div style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08); border-radius:12px; overflow:hidden; display:flex; flex-direction:column; position:relative;">
                    <a href="<?= $cardHref ?>" <?= $cardTarget ?> style="position:absolute; inset:0; z-index:5;"></a>
                    <div style="height:100px; background:rgba(0,0,0,0.5); position:relative;">
                        <img src="<?= htmlspecialchars($prod['display_thumb']) ?>" style="width:100%; height:100%; object-fit:cover;" onerror="this.onerror=null; this.src='/assets/images/services/default_service.svg';">
                        <?php if($isAff): ?>
                            <span style="position:absolute; top:6px; right:6px; background:linear-gradient(135deg, #10b981, #00b0ff); color:#fff; font-size:0.58rem; font-weight:800; padding:2px 6px; border-radius:4px;">AFFILIATE ↗</span>
                        <?php else: ?>
                            <span style="position:absolute; top:6px; right:6px; background:var(--brand); color:#fff; font-size:0.6rem; font-weight:800; padding:2px 6px; border-radius:4px;">NEW</span>
                        <?php endif; ?>
                    </div>
                    <div style="padding: 0.75rem;">
                        <div style="font-size:0.8rem; font-weight:700; margin-bottom:0.3rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; color:#fff;"><?= htmlspecialchars($prod['title']) ?></div>
                        <div style="color:var(--gold); font-weight:800; font-size:0.85rem;">৳ <?= number_format($prod['price']) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div style="text-align:center; padding: 1.5rem; background:rgba(33, 150, 243, 0.05); border-radius:12px; border:1px dashed rgba(33, 150, 243, 0.2);">
                <p style="color:#fff; font-weight:600; font-size:1rem; margin-bottom:0.5rem;">Want to sell your own items?</p>
                <p style="color:var(--muted); font-size:0.85rem; margin-bottom:1rem;">Open a free shop and start earning.</p>
                <a href="/user/create_shop.php" class="btn" style="background:linear-gradient(135deg, var(--brand), #007bb5); color:#fff; padding:0.6rem 1.2rem; text-decoration:none; border-radius:8px; font-weight:700; box-shadow:0 4px 15px rgba(33,150,243,0.3);">Create My Shop</a>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- ── 1.2 MY WISHLIST (SUB-SECTION INSIDE SHOP) ── -->
    <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid rgba(255,255,255,0.08);">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.2rem;">
          <h3 style="margin:0; font-size:1.15rem; color:#fff; display:flex; align-items:center; gap:8px;">
              ❤️ My Wishlist
          </h3>
      </div>
      
      <?php 
      $wishlist_items = [];
      try {
          $stmt_wish = $pdo->prepare("
              SELECT w.id as wish_id, p.*, s.business_name,
                     (SELECT image_url FROM partner_product_images WHERE product_id = p.id AND is_thumbnail = 1 LIMIT 1) AS thumb,
                     (SELECT image_url FROM partner_product_images WHERE product_id = p.id ORDER BY id ASC LIMIT 1) AS fallback_img
              FROM user_wishlist w 
              JOIN partner_products p ON w.product_id = p.id 
              JOIN partners s ON p.partner_id = s.id 
              WHERE w.user_id = ? 
              ORDER BY w.created_at DESC
          ");
          $stmt_wish->execute([$user['id']]);
          $wishlist_items = $stmt_wish->fetchAll(PDO::FETCH_ASSOC);
          foreach ($wishlist_items as $k => $p) {
              $wishlist_items[$k]['display_thumb'] = resolveProductArtwork(
                  $p['thumb'] ?? '',
                  $p['fallback_img'] ?? '',
                  $p['business_name'] ?? '',
                  $p['category'] ?? '',
                  $p['title'] ?? '',
                  $p['listing_type'] ?? 'product'
              );
          }
      } catch(Exception $e) {}
      ?>
      
      <?php if(empty($wishlist_items)): ?>
          <div class="empty-card" style="background:rgba(255,255,255,0.02); border:1px dashed rgba(255,255,255,0.1); border-radius:12px; padding:1.5rem; text-align:center;">
              <div style="font-size:2rem; margin-bottom:0.4rem; opacity:0.6;">🤍</div>
              <p style="font-size:0.9rem; color:#fff; font-weight:600; margin-bottom:0.2rem;">Your wishlist is empty.</p>
              <p style="font-size:0.8rem; color:var(--muted); margin-top:0;">Save products you like so you can easily find them later!</p>
          </div>
      <?php else: ?>
          <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap:1rem;">
              <?php foreach($wishlist_items as $prod): ?>
              <div style="background:var(--dark-card); border:1px solid rgba(255, 20, 147, 0.2); border-radius:12px; overflow:hidden; position:relative; display:flex; flex-direction:column;">
                  <button onclick="toggleWishlistDb(<?= $prod['id'] ?>, this)" style="position:absolute; top:8px; right:8px; background:rgba(0,0,0,0.6); border:none; border-radius:50%; width:30px; height:30px; display:flex; align-items:center; justify-content:center; cursor:pointer; z-index:10; font-size:0.9rem;" title="Remove">❌</button>
                  <a href="/product_detail.php?id=<?= $prod['id'] ?>" style="text-decoration:none; color:inherit; display:block; height:100%;">
                      <div style="height:120px; background:rgba(0,0,0,0.5);">
                          <img src="<?= htmlspecialchars($prod['display_thumb']) ?>" style="width:100%; height:100%; object-fit:cover;" onerror="this.onerror=null; this.src='/assets/images/services/default_service.svg';">
                      </div>
                      <div style="padding: 1rem; flex:1; display:flex; flex-direction:column; justify-content:space-between;">
                          <div>
                              <div style="font-size:0.7rem; color:var(--muted); text-transform:uppercase; margin-bottom:2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= htmlspecialchars($prod['business_name']) ?></div>
                              <div style="font-size:0.85rem; font-weight:700; margin-bottom:0.4rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; color:#fff;"><?= htmlspecialchars($prod['title']) ?></div>
                          </div>
                          <div style="color:var(--green); font-weight:800; font-size:0.9rem;">৳ <?= number_format($prod['price']) ?></div>
                      </div>
                  </a>
              </div>
              <?php endforeach; ?>
          </div>
          <script>
          function toggleWishlistDb(pid, btnElem) {
              fetch('/api/toggle_wishlist.php', {
                  method: 'POST',
                  headers: {'Content-Type': 'application/json'},
                  body: JSON.stringify({product_id: pid})
              })
              .then(res => res.json())
              .then(data => {
                  if(data.success && data.action === 'removed') {
                      btnElem.closest('div').style.display = 'none';
                  }
              });
          }
          </script>
      <?php endif; ?>
    </div>

    <!-- ── 1.3 MY SERVICE ORDERS (SUB-SECTION, ONLY VISIBLE WHEN ORDERS EXIST) ── -->
    <?php if (!empty($userOrders)): ?>
    <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid rgba(255,255,255,0.08);">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.2rem;">
          <h3 style="margin:0; font-size:1.15rem; color:#fff; display:flex; align-items:center; gap:8px;">
              📊 My Service Orders
          </h3>
          <div style="display:flex; align-items:center; gap:8px;">
              <span style="font-size:0.75rem; color:var(--brand); background:rgba(33,150,243,0.1); padding:3px 8px; border-radius:6px; font-weight:700;">
                  <?= count($userOrders) ?> Orders
              </span>
              <button onclick="switchUserTab('orders')" style="background:transparent; border:none; color:var(--brand); font-size:0.78rem; font-weight:700; cursor:pointer; padding:0;">View All ➔</button>
          </div>
      </div>
      
      <div style="display:flex; flex-direction:column; gap:1rem;">
      <?php foreach($userOrders as $order): ?>
        <div class="order-card" style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.05); border-radius:12px; padding:1.2rem;">
          <div class="order-header" style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid rgba(255,255,255,0.05); padding-bottom:0.8rem; margin-bottom:1rem;">
            <span class="order-name" style="font-weight:700; color:#fff; font-size:1rem;"><?= htmlspecialchars($order['service_name']) ?></span>
            <span class="order-ref" style="font-family:monospace; background:rgba(255,255,255,0.1); padding:2px 6px; border-radius:4px; font-size:0.75rem; color:var(--brand);">FS-<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></span>
          </div>
          
          <?php if($order['details']): ?>
            <div class="order-details-box" style="background:rgba(0,0,0,0.3); padding:0.8rem; border-radius:8px; font-size:0.85rem; color:var(--muted); margin-bottom:1rem;">
              <strong style="color:#fff;">Details:</strong> <?= htmlspecialchars($order['details']) ?>
            </div>
          <?php endif; ?>

          <!-- Progress Timeline Tracker -->
          <?= renderTimeline($order['status']) ?>
          
          <div style="font-size:0.75rem; color:var(--muted); text-align:right; margin-top:1rem;">
            📅 Placed: <?= date('d M Y, h:i A', strtotime($order['created_at'])) ?>
          </div>
        </div>
      <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <!-- ══════════════════════════════════════ -->
  <!-- TAB: MY ORDERS & TRACKING -->
  <!-- ══════════════════════════════════════ -->
  <div class="tab-content" id="tab-orders" style="display:none; margin-top: 2rem;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.2rem; flex-wrap: wrap; gap: 0.5rem;">
          <div>
              <h2 style="color:var(--brand); margin: 0; display:flex; align-items:center; gap:8px;">
                  📦 My Orders &amp; Tracking
              </h2>
              <p style="color:var(--muted); font-size: 0.85rem; margin: 4px 0 0 0;">Track the status of your official service applications and marketplace store orders.</p>
          </div>
          <a href="/index.php" class="btn" style="background:linear-gradient(135deg, var(--gold), #ff9100); color:#000; font-weight:800; font-size:0.8rem; padding:0.5rem 1.2rem; border-radius:50px; text-decoration:none;">
              🏪 Browse Store
          </a>
      </div>

      <!-- Service Orders List -->
      <?php if (!empty($userOrders)): ?>
      <div class="premium-card" style="margin-bottom: 1.5rem; border-radius: 16px; border: 1px solid rgba(33,150,243,0.3);">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; border-bottom:1px solid rgba(255,255,255,0.06); padding-bottom:0.75rem;">
              <h3 style="margin:0; font-size:1.1rem; color:#fff; display:flex; align-items:center; gap:8px;">
                  🏛️ Official Service Orders
              </h3>
              <span style="font-size:0.75rem; color:var(--brand); background:rgba(33,150,243,0.15); border:1px solid rgba(33,150,243,0.3); padding:2px 8px; border-radius:12px; font-weight:700;">
                  <?= count($userOrders) ?> Orders
              </span>
          </div>

          <div style="display:flex; flex-direction:column; gap:1rem;">
          <?php foreach($userOrders as $order): ?>
            <div class="order-card" style="background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.06); border-radius:12px; padding:1.2rem;">
              <div class="order-header" style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid rgba(255,255,255,0.05); padding-bottom:0.8rem; margin-bottom:1rem;">
                <span class="order-name" style="font-weight:700; color:#fff; font-size:1rem;"><?= htmlspecialchars($order['service_name']) ?></span>
                <span class="order-ref" style="font-family:monospace; background:rgba(255,255,255,0.1); padding:2px 6px; border-radius:4px; font-size:0.75rem; color:var(--brand);">FS-<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></span>
              </div>
              
              <?php if($order['details']): ?>
                <div class="order-details-box" style="background:rgba(0,0,0,0.3); padding:0.8rem; border-radius:8px; font-size:0.85rem; color:var(--muted); margin-bottom:1rem;">
                  <strong style="color:#fff;">Details:</strong> <?= htmlspecialchars($order['details']) ?>
                </div>
              <?php endif; ?>

              <!-- Progress Timeline Tracker (Pending -> Processing -> Completed) -->
              <?= renderTimeline($order['status']) ?>
              
              <div style="font-size:0.75rem; color:var(--muted); text-align:right; margin-top:1rem;">
                📅 Placed: <?= date('d M Y, h:i A', strtotime($order['created_at'])) ?>
              </div>
            </div>
          <?php endforeach; ?>
          </div>
      </div>
      <?php endif; ?>

      <!-- Store Purchases List -->
      <?php if (!empty($userPartnerOrders)): ?>
      <div class="premium-card" style="margin-bottom: 1.5rem; border-radius: 16px; border: 1px solid rgba(0,230,118,0.3);">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; border-bottom:1px solid rgba(255,255,255,0.06); padding-bottom:0.75rem;">
              <h3 style="margin:0; font-size:1.1rem; color:#fff; display:flex; align-items:center; gap:8px;">
                  🛍️ Marketplace Store Purchases
              </h3>
              <a href="/user/partner_orders.php" style="font-size:0.78rem; color:#00e676; text-decoration:none; font-weight:700;">
                  View All Orders ➔
              </a>
          </div>

          <div style="display:flex; flex-direction:column; gap:1rem;">
          <?php foreach($userPartnerOrders as $po): ?>
            <div class="order-card" style="background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.06); border-radius:12px; padding:1.2rem;">
              <div class="order-header" style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid rgba(255,255,255,0.05); padding-bottom:0.8rem; margin-bottom:0.8rem;">
                <div>
                  <span style="font-weight:700; color:#fff; font-size:1rem; display:block;"><?= htmlspecialchars($po['product_title']) ?></span>
                  <span style="font-size:0.75rem; color:var(--gold);">Store: <?= htmlspecialchars($po['partner_name']) ?></span>
                </div>
                <div style="text-align:right;">
                  <span style="font-family:monospace; background:rgba(255,255,255,0.1); padding:2px 6px; border-radius:4px; font-size:0.75rem; color:var(--brand); display:block; margin-bottom:2px;">#ORD-<?= (int)$po['id'] ?></span>
                  <span style="font-weight:800; color:#00e676; font-size:0.9rem;">৳ <?= number_format($po['price'] ?? $po['product_price'] ?? 0, 2) ?></span>
                </div>
              </div>

              <?= renderTimeline($po['status']) ?>

              <div style="display:flex; justify-content:space-between; align-items:center; font-size:0.75rem; color:var(--muted); margin-top:1rem;">
                <span>Status: <strong style="color:#fff; text-transform:capitalize;"><?= htmlspecialchars($po['status']) ?></strong></span>
                <span>📅 <?= date('d M Y, h:i A', strtotime($po['created_at'])) ?></span>
              </div>
            </div>
          <?php endforeach; ?>
          </div>
      </div>
      <?php endif; ?>

      <?php if (empty($userOrders) && empty($userPartnerOrders)): ?>
      <div style="text-align:center; padding:3.5rem 1.5rem; background:rgba(255,255,255,0.02); border:1px dashed rgba(255,255,255,0.1); border-radius:20px;">
          <div style="font-size:3.5rem; margin-bottom:0.8rem;">📦</div>
          <h3 style="color:#fff; margin-bottom:0.4rem; font-size:1.3rem;">No Orders Placed Yet</h3>
          <p style="color:var(--muted); font-size:0.88rem; max-width:420px; margin:0 auto 1.6rem auto; line-height:1.5;">You have not placed any service applications or product orders yet. Explore our verified official services or discover deals across community shops!</p>
          <a href="/index.php" class="btn" style="background:linear-gradient(135deg, var(--gold), #ff9100); color:#000; font-weight:800; padding:0.75rem 2rem; border-radius:30px; text-decoration:none; display:inline-block; box-shadow:0 4px 15px rgba(252,185,0,0.3);">
              🏪 Explore Storefront
          </a>
      </div>
      <?php endif; ?>
  </div>

  <!-- ══════════════════════════════════════ -->
  <!-- 2. REWARDS & INVITES (PARENT CONTAINER) -->
  <!-- ══════════════════════════════════════ -->
  <div class="tab-content" id="tab-affiliate" style="margin-top: 2rem;">
      <div style="display: flex; justify-content: space-between; align-items: center;">
        <h2 style="color:var(--brand); margin-bottom: 0;">🤝 Rewards &amp; Invites</h2>
      </div>
      <p style="color:var(--muted); font-size: 0.9rem; margin-top:0.5rem; margin-bottom: 1.5rem;">Earn bonus rewards and cash commissions by completing tasks and inviting friends.</p>

      <!-- ── 2.1 CUSTOMER LOYALTY REWARDS ── -->
      <div class="premium-card" style="background: linear-gradient(135deg, rgba(252,185,0,0.1), rgba(33,150,243,0.05)); border: 1px solid rgba(252,185,0,0.2); text-align: center; margin-bottom: 1.5rem; border-radius: 16px;">
          <h3 style="color: var(--gold); font-size: 1.3rem; margin-bottom: 0.5rem;">🏆 Customer Loyalty Rewards</h3>
          <p style="color: var(--text); font-size: 0.9rem; margin-bottom: 1.2rem;">Complete fun missions like leaving reviews and placing orders to earn Fast Site Bonus Points &amp; Coins!</p>
          <a href="missions.php" class="btn" style="background: linear-gradient(135deg, var(--gold), #ff9100); color: #000; padding: 0.75rem 2rem; border-radius: 50px; text-decoration: none; font-weight: 800; font-size: 0.95rem; box-shadow: 0 4px 15px rgba(252,185,0,0.3); display: inline-block;">View My Missions ➡️</a>
      </div>

      <!-- ── 2.2 SHARE & EARN REAL CASH! ── -->
      <div class="premium-card" style="border: 1px solid rgba(252, 185, 0, 0.25); background: linear-gradient(135deg, rgba(252, 185, 0, 0.06) 0%, rgba(20, 20, 31, 0.6) 100%); padding: 1.5rem; border-radius: 16px; margin-bottom: 1.5rem;">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:0.5rem; margin-bottom:0.8rem;">
          <div>
            <h3 style="color:#fff; font-size:1.15rem; font-weight:800; display:flex; align-items:center; gap:0.5rem; margin:0 0 0.3rem 0;">
              🎁 Share &amp; Earn Real Cash!
            </h3>
            <p style="color:var(--muted); font-size:0.85rem; margin:0;">
              Invite friends to order services or products. You earn <strong style="color:var(--gold);">Instant Cash Commissions</strong> on every completed order!
            </p>
          </div>
          <span style="background:rgba(252,185,0,0.15); color:var(--gold); border:1px solid rgba(252,185,0,0.3); padding:4px 10px; border-radius:20px; font-size:0.75rem; font-weight:700;">
            ⚡ 20% Instant Bonus
          </span>
        </div>
        
        <div style="display:flex; gap:0.6rem; align-items:center; margin:1.2rem 0; flex-wrap:wrap;">
          <input id="reflink-share" readonly value="<?= htmlspecialchars($baseUrl) ?>" style="flex:1; min-width:240px; background:#0c0c14; border:1px solid rgba(252,185,0,0.3); color:#fff; padding:0.75rem 1rem; border-radius:10px; font-family:monospace; font-size:0.85rem; outline:none;" />
          <button id="copyRefBtn-share" onclick="copyLink('reflink-share', this)" style="background:linear-gradient(135deg, var(--gold), #ff9100); color:#000; font-weight:800; border:none; padding:0.75rem 1.4rem; border-radius:10px; cursor:pointer; font-size:0.85rem; display:inline-flex; align-items:center; gap:0.4rem; transition:transform 0.2s; white-space:nowrap;">
            📋 Copy Link
          </button>
        </div>

        <!-- Quick Social Sharing -->
        <div style="font-size:0.72rem; font-weight:700; color:var(--muted); text-transform:uppercase; letter-spacing:0.05em; margin-bottom:0.6rem;">Share link directly to:</div>
        <div style="display:flex; gap:0.6rem; flex-wrap:wrap;">
          <a href="https://wa.me/?text=<?= urlencode('Get high quality online services and instant delivery on Fast Site! Join here: ' . $baseUrl) ?>" target="_blank" style="background:rgba(37, 211, 102, 0.12); color:#25D366; border:1px solid rgba(37, 211, 102, 0.3); padding:0.5rem 1rem; border-radius:8px; text-decoration:none; font-size:0.8rem; font-weight:700; display:inline-flex; align-items:center; gap:0.4rem; transition:all 0.2s;">
            💬 WhatsApp
          </a>
          <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($baseUrl) ?>" target="_blank" style="background:rgba(24, 119, 242, 0.12); color:#1877F2; border:1px solid rgba(24, 119, 242, 0.3); padding:0.5rem 1rem; border-radius:8px; text-decoration:none; font-size:0.8rem; font-weight:700; display:inline-flex; align-items:center; gap:0.4rem; transition:all 0.2s;">
            📘 Facebook
          </a>
          <a href="https://twitter.com/intent/tweet?url=<?= urlencode($baseUrl) ?>&text=<?= urlencode('Get fast digital services and tools on Fast Site!') ?>" target="_blank" style="background:rgba(29, 161, 242, 0.12); color:#1DA1F2; border:1px solid rgba(29, 161, 242, 0.3); padding:0.5rem 1rem; border-radius:8px; text-decoration:none; font-size:0.8rem; font-weight:700; display:inline-flex; align-items:center; gap:0.4rem; transition:all 0.2s;">
            🐦 Twitter/X
          </a>
          <a href="https://t.me/share/url?url=<?= urlencode($baseUrl) ?>&text=<?= urlencode('Join Fast Site Marketplace!') ?>" target="_blank" style="background:rgba(0, 136, 204, 0.12); color:#0088cc; border:1px solid rgba(0, 136, 204, 0.3); padding:0.5rem 1rem; border-radius:8px; text-decoration:none; font-size:0.8rem; font-weight:700; display:inline-flex; align-items:center; gap:0.4rem; transition:all 0.2s;">
            ✈️ Telegram
          </a>
        </div>
      </div>

      <!-- ── 2.3 AFFILIATE MILESTONE BADGES ── -->
      <div class="premium-card" style="border-left: 4px solid var(--gold); background: linear-gradient(135deg, rgba(252,185,0,0.05) 0%, rgba(0,0,0,0) 100%); padding: 1.5rem; border-radius: 16px; margin-bottom: 1.5rem;">
        <h3 style="display:flex; align-items:center; gap:0.5rem; color:#fff; margin-bottom:0.3rem;">🏆 Affiliate Milestone Badges</h3>
        <p style="font-size:0.8rem; margin-bottom:1.2rem; color:var(--muted);">Unlock exclusive badges and permanent commission boosts by completing missions!</p>
        
        <?php
        // Fetch user mission progress
        $um_map = [];
        try {
            $um_stmt = $pdo->prepare("SELECT * FROM user_missions WHERE user_id = ?");
            $um_stmt->execute([$userId]);
            while ($um = $um_stmt->fetch(PDO::FETCH_ASSOC)) {
                $um_map[$um['mission_key']] = $um;
            }
        } catch (Exception $e) {}

        // Calculate actual progress on the fly for display
        $ref_count = 0;
        try {
            if (!empty($user['ref_code'])) {
                $r_stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE ref_by = ?");
                $r_stmt->execute([$user['ref_code']]);
                $ref_count = (int)$r_stmt->fetchColumn();
            }
        } catch (Exception $e) {}

        $pur_count = 0;
        try {
            $p_stmt = $pdo->prepare("SELECT COUNT(*) FROM partner_orders WHERE customer_id = ? AND status = 'completed'");
            $p_stmt->execute([$userId]);
            $pur_count = (int)$p_stmt->fetchColumn();
        } catch (Exception $e) {}

        $missions = [
            'first_purchase' => ['target' => 1, 'reward' => 50, 'name' => 'Shopper Badge', 'desc' => 'Make your first purchase on Fast Site.', 'progress' => $pur_count, 'icon' => '🛍️', 'color' => '#3b82f6'],
            'refer_5' => ['target' => 5, 'reward' => 100, 'name' => 'Bronze Affiliate', 'desc' => 'Refer 5 friends to the platform.', 'progress' => $ref_count, 'icon' => '🥉', 'color' => '#cd7f32'],
            'refer_25' => ['target' => 25, 'reward' => 500, 'name' => 'Silver Affiliate', 'desc' => 'Refer 25 friends to the platform.', 'progress' => $ref_count, 'icon' => '🥈', 'color' => '#c0c0c0'],
        ];
        ?>

        <div style="display:flex; flex-direction:column; gap:1rem;">
          <?php foreach ($missions as $key => $m): 
              $is_completed = isset($um_map[$key]) && $um_map[$key]['is_completed'];
              $progress_pct = min(100, ($m['progress'] / $m['target']) * 100);
              $can_claim = !$is_completed && ($m['progress'] >= $m['target']);
          ?>
          <div style="background:rgba(255,255,255,0.02); border:1px solid <?= $is_completed ? $m['color'] : 'rgba(255,255,255,0.06)' ?>; padding:1rem; border-radius:12px; display:flex; align-items:center; justify-content:space-between; gap:1rem; position:relative; overflow:hidden;">
            <?php if($is_completed): ?>
              <div style="position:absolute; right:-20px; top:-20px; font-size:5rem; opacity:0.1; filter:blur(2px);"><?= $m['icon'] ?></div>
            <?php endif; ?>
            <div style="font-size:2.5rem; filter:drop-shadow(0 0 10px <?= $m['color'] ?>);"><?= $m['icon'] ?></div>
            <div style="flex-grow:1; text-align:left; z-index:2;">
              <div style="font-size:1rem; font-weight:800; color:<?= $is_completed ? $m['color'] : '#fff' ?>;"><?= htmlspecialchars($m['name']) ?></div>
              <div style="font-size:0.75rem; color:var(--muted); margin-bottom:8px;"><?= htmlspecialchars($m['desc']) ?></div>
              
              <!-- Progress Bar -->
              <div style="width:100%; max-width:200px; height:6px; background:rgba(255,255,255,0.1); border-radius:3px; overflow:hidden;">
                  <div style="width:<?= $progress_pct ?>%; height:100%; background:<?= $m['color'] ?>; box-shadow:0 0 8px <?= $m['color'] ?>;"></div>
              </div>
              <div style="font-size:0.65rem; color:var(--muted); margin-top:4px;"><?= $m['progress'] ?> / <?= $m['target'] ?> Completed</div>
            </div>
            <div style="text-align:right; z-index:2; min-width: 100px;">
              <span style="display:inline-block; font-size:0.8rem; background:rgba(252,185,0,0.15); color:var(--gold); padding:0.2rem 0.6rem; border-radius:6px; margin-bottom:0.4rem; font-weight:800;">+<?= $m['reward'] ?> Coins</span>
              <br/>
              <?php if($is_completed): ?>
                  <button disabled style="padding:0.4rem 0.8rem; font-size:0.75rem; background:rgba(255,255,255,0.1); color:#888; border-radius:6px; border:none; font-weight:700; width:100%;">Claimed ✅</button>
              <?php elseif($can_claim): ?>
                  <button onclick="claimMission('<?= $key ?>')" style="padding:0.4rem 0.8rem; font-size:0.75rem; background:linear-gradient(135deg, #00e676, #00bfa5); color:#000; border-radius:6px; border:none; cursor:pointer; font-weight:800; width:100%; box-shadow:0 0 10px rgba(0,230,118,0.4); animation: pulse 2s infinite;">Claim Reward!</button>
              <?php else: ?>
                  <button disabled style="padding:0.4rem 0.8rem; font-size:0.75rem; background:rgba(0,0,0,0.5); color:#666; border-radius:6px; border:1px solid rgba(255,255,255,0.1); font-weight:700; width:100%;">Locked 🔒</button>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <script>
      function claimMission(missionKey) {
          let fd = new FormData();
          fd.append('mission_key', missionKey);
          fetch('/api/claim_mission_reward.php', { method: 'POST', body: fd })
          .then(r => r.json())
          .then(res => {
              alert(res.message);
              if(res.success) location.reload();
          })
          .catch(err => alert('Error claiming reward.'));
      }
      </script>
      
      <style>
      @keyframes pulse {
          0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(0, 230, 118, 0.7); }
          70% { transform: scale(1.05); box-shadow: 0 0 0 10px rgba(0, 230, 118, 0); }
          100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(0, 230, 118, 0); }
      }
      </style>

      <!-- ── 2.4 WANT HIGHER COMMISSIONS? ── -->
      <?php if (!$isAgent): ?>
      <div class="premium-card" style="border:1px solid rgba(252, 185, 0, 0.4); background:linear-gradient(180deg, rgba(252, 185, 0, 0.05) 0%, rgba(0,0,0,0) 100%); position:relative; overflow:hidden; border-radius: 16px;">
        <div style="position:absolute; top:-20px; right:-20px; font-size:6rem; opacity:0.1; filter:blur(4px);">👑</div>
        <h3 style="color:var(--gold); font-size:1.3rem; margin-bottom:0.8rem; position:relative; z-index:2; display:flex; align-items:center; gap:8px;">
          <span style="font-size:1.6rem;">👑</span> Want higher commissions?
        </h3>
        <p style="color:#e2e8f0; font-size:0.95rem; line-height:1.5; margin-bottom:1.5rem; position:relative; z-index:2;">
          Become an <strong>Affiliate Partner</strong> for free! Partners get up to <strong>20% flat commission</strong>, target milestones, and direct cash withdrawals to bKash or Nagad.
        </p>
        <a href="/user/become_agent.php" class="btn" style="background:linear-gradient(135deg, var(--gold), #ff9800); color:#000; font-weight:800; padding:0.8rem 1.5rem; font-size:1rem; border-radius:8px; border:none; text-decoration:none; display:inline-block; box-shadow:0 6px 20px rgba(252,185,0,0.3); transition:transform 0.2s; position:relative; z-index:2;">
          Become an Affiliate Partner 💸
        </a>
      </div>
      <?php else: ?>
      <div class="premium-card" style="border-color: rgba(59,130,246,0.3); border-radius: 16px;">
        <h3 style="color:var(--brand);">🔗 Affiliate Partner Program</h3>
        <p style="color:var(--muted); font-size:0.85rem; margin-bottom:1rem;">Manage your referrals, track commissions, and process cash payouts.</p>
        <div style="margin-top:1.5rem; text-align:center;">
            <button onclick="switchTab(event, 'tab-agent')" class="btn" style="background:transparent; border:1px solid var(--brand); color:var(--brand); padding:0.6rem 1.2rem; border-radius:8px; font-weight:700; cursor:pointer;">Open Partner Dashboard 👑</button>
        </div>
      </div>
      <?php endif; ?>
  </div> <!-- End of tab-affiliate -->

  <?php if ($isAgent): ?>
  <!-- AGENT / AFFILIATE PARTNER TAB -->
  <div class="tab-content" id="tab-agent">
    <div style="display: flex; justify-content: space-between; align-items: center;">
      <h2 style="color:var(--brand); margin-bottom: 0;">Affiliate Partner Program 👑</h2>
    </div>
    <p style="color:var(--muted); font-size: 0.9rem; margin-top:0.5rem;">Manage your referrals and cash earnings.</p>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin-top: 2rem;">
        <div style="background: rgba(255,255,255,0.03); padding: 1.5rem; border-radius: 12px; text-align: center; border: 1px solid rgba(255,255,255,0.05);">
            <div style="font-size: 2rem; font-weight: 800; color: var(--brand); margin-bottom: 0.5rem;"><?= (int)$total_referrals ?></div>
            <div style="font-size: 0.85rem; color: var(--muted); text-transform: uppercase;">Total Referrals</div>
        </div>
        <div style="background: rgba(255,255,255,0.03); padding: 1.5rem; border-radius: 12px; text-align: center; border: 1px solid rgba(255,255,255,0.05);">
            <div style="font-size: 2rem; font-weight: 800; color:#00e676; margin-bottom: 0.5rem;">৳<?= number_format($agentInfo['total_earnings'], 2) ?></div>
            <div style="font-size: 0.85rem; color: var(--muted); text-transform: uppercase;">Total Earned</div>
        </div>
        <div style="background: rgba(255,255,255,0.03); padding: 1.5rem; border-radius: 12px; text-align: center; border: 1px solid rgba(255,255,255,0.05); position:relative;">
            <div style="font-size: 1.4rem; font-weight: 800; color: var(--brand); padding: 0.3rem 0; margin-bottom: 0.5rem;"><?= htmlspecialchars($user['ref_code']) ?></div>
            <div style="font-size: 0.85rem; color: var(--muted); text-transform: uppercase; margin-bottom:0.5rem;">Your Code</div>
            <button onclick="document.getElementById('editRefCodeForm').style.display='block'" style="background:rgba(255,255,255,0.05); color:#fff; border:1px solid rgba(255,255,255,0.1); padding:4px 8px; border-radius:6px; cursor:pointer; font-size:0.75rem;">✏️ Customize Code</button>
            
            <form id="editRefCodeForm" method="POST" style="display:none; margin-top:1rem; padding-top:1rem; border-top:1px solid rgba(255,255,255,0.05);">
                <input type="text" name="custom_ref_code" value="<?= htmlspecialchars($user['ref_code']) ?>" minlength="3" maxlength="15" pattern="[a-zA-Z0-9]+" required style="width:100%; max-width:200px; padding:0.5rem; border-radius:6px; background:rgba(0,0,0,0.5); border:1px solid var(--brand); color:#fff; text-align:center; text-transform:uppercase; margin-bottom:0.5rem;">
                <br/>
                <button type="submit" name="update_ref_code" class="btn" style="background:var(--brand); color:#fff; border:none; padding:0.4rem 1rem; border-radius:6px; font-weight:bold; cursor:pointer; font-size:0.8rem;">Save</button>
                <button type="button" onclick="document.getElementById('editRefCodeForm').style.display='none'" style="background:transparent; color:var(--muted); border:none; cursor:pointer; font-size:0.8rem;">Cancel</button>
            </form>
        </div>
    </div>

    <div style="margin-top: 3rem; background: rgba(255,255,255,0.02); padding: 1.5rem; border-radius: 12px; text-align: center; border: 1px solid rgba(255,255,255,0.05);">
        <h3 style="margin-bottom: 1rem; color:#fff;">Share your invite link</h3>
        <input type="text" id="reflink-agent" value="<?= htmlspecialchars($baseUrl) ?>" readonly style="width: 100%; max-width: 400px; padding: 0.8rem; background: #000; border: 1px solid var(--muted); color: #fff; border-radius: 8px; text-align: center; font-family: monospace; margin-bottom:1rem;">
        <br/>
        <button onclick="copyLink('reflink-agent', this)" class="btn" style="background:linear-gradient(135deg, var(--gold), #ffb300); color:#000; border:none; padding: 0.6rem 1.2rem; border-radius: 6px; font-weight: 700; cursor:pointer;">Copy Link</button>
    </div>
  </div>
  <?php endif; ?>

  <!-- SETTINGS TAB -->
  <div class="tab-content" id="tab-settings" style="display:none; margin-top:2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom:1.5rem;">
      <h2 style="color:var(--brand); margin-bottom: 0;">⚙️ Account Settings</h2>
    </div>
    
    <div style="background: rgba(255,255,255,0.03); padding: 1.5rem; border-radius: 16px; border: 1px solid rgba(255,255,255,0.06); margin-bottom: 1.5rem;">
        <h3 style="color:#fff; margin-bottom:1rem; font-size:1.1rem; display:flex; align-items:center; gap:8px;">
            <span>👤</span> Profile &amp; Verification
        </h3>
        <div style="display:flex; justify-content:space-between; align-items:center; padding: 0.5rem 0; gap:1rem;">
            <div style="flex:1; min-width:0; padding-right:0.5rem;">
                <strong style="color:#fff; font-size:0.95rem; display:block;">Personal Profile &amp; Identity</strong>
                <p style="color:var(--muted); font-size:0.82rem; margin:4px 0 0 0; line-height:1.4;">Update your photo, phone number, address, and national identity document.</p>
            </div>
            <a href="/user/profile.php" style="background:linear-gradient(135deg, var(--gold), #ffb300); color:#000; padding:0.5rem 1.2rem; min-width:90px; text-align:center; font-weight:800; font-size:0.85rem; border-radius:8px; text-decoration:none; white-space:nowrap; flex-shrink:0; display:inline-block; border:none; box-shadow:0 4px 12px rgba(252,185,0,0.3);">Edit Profile</a>
        </div>
    </div>

    <div style="background: rgba(255,255,255,0.03); padding: 1.5rem; border-radius: 16px; border: 1px solid rgba(255,255,255,0.06); margin-bottom: 1.5rem;">
        <h3 style="color:#fff; margin-bottom:1rem; font-size:1.1rem; display:flex; align-items:center; gap:8px;">
            <span>🎨</span> Dashboard Display
        </h3>
        
        <div style="display:flex; justify-content:space-between; align-items:center; padding: 0.5rem 0; gap:1rem;">
            <div style="flex:1; min-width:0; padding-right:0.5rem;">
                <strong style="color:#fff; font-size:0.95rem; display:block;">Show Store Products on Dashboard</strong>
                <p style="color:var(--muted); font-size:0.82rem; margin:4px 0 0 0; line-height:1.4;">Turn on to show the products showcase directly inside your dashboard.</p>
            </div>
            <label class="fs-switch" style="flex-shrink:0;" title="Toggle Storefront View">
              <input type="checkbox" id="storefront-toggle" onchange="toggleStorefrontView(this.checked); localStorage.setItem('fastsite_storefront_view', this.checked ? '1' : '0');">
              <span class="fs-slider"></span>
            </label>
        </div>
    </div>

    <div style="background: rgba(255,255,255,0.03); padding: 1.5rem; border-radius: 16px; border: 1px solid rgba(255,255,255,0.06);">
        <h3 style="color:#fff; margin-bottom:1rem; font-size:1.1rem; display:flex; align-items:center; gap:8px;">
            <span>🔒</span> Security &amp; Access
        </h3>
        
        <div style="display:flex; justify-content:space-between; align-items:center; padding: 1rem 0; border-bottom:1px solid rgba(255,255,255,0.06); gap:1rem;">
            <div style="flex:1; min-width:0; padding-right:0.5rem;">
                <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                    <strong style="color:#fff; font-size:0.95rem;">Biometric Login</strong>
                    <span style="font-size:0.68rem; color:var(--gold); background:rgba(252,185,0,0.12); border:1px solid rgba(252,185,0,0.25); padding:1px 6px; border-radius:4px; font-weight:700; white-space:nowrap;">Device Dependent</span>
                </div>
                <p style="color:var(--muted); font-size:0.82rem; margin:4px 0 0 0; line-height:1.4;">Use Fingerprint or Face ID for faster login (Supported phone hardware only).</p>
            </div>
            <label class="fs-switch" style="flex-shrink:0;" title="Toggle Biometric Login">
              <input type="checkbox" id="biometric-toggle" onchange="handleBiometricToggle(this)">
              <span class="fs-slider"></span>
            </label>
        </div>
        
        <div style="display:flex; justify-content:space-between; align-items:center; padding: 1rem 0; gap:1rem;">
            <div style="flex:1; min-width:0; padding-right:0.5rem;">
                <strong style="color:#fff; font-size:0.95rem; display:block;">Change Password</strong>
                <p style="color:var(--muted); font-size:0.82rem; margin:4px 0 0 0; line-height:1.4;">Update your account security password</p>
            </div>
            <a href="forgot_password.php" style="background:var(--brand); color:#fff; padding:0.5rem 1.2rem; min-width:80px; text-align:center; font-weight:800; font-size:0.85rem; border-radius:8px; text-decoration:none; white-space:nowrap; flex-shrink:0; display:inline-block; border:none; box-shadow:0 4px 12px rgba(33,150,243,0.3);">Update</a>
        </div>
    </div>
  </div>

  <!-- DOWNLOAD APP TAB -->
  <div class="tab-content" id="tab-download-app" style="display:none; margin-top:2rem;">
    <div style="text-align: center; padding: 2.5rem 1rem; background:rgba(16,18,28,0.7); border:1px solid rgba(255,255,255,0.06); border-radius:20px; max-width:550px; margin:0 auto;">
        <div style="width:80px; height:80px; margin:0 auto 1.2rem auto; border-radius:18px; overflow:hidden; border:2px solid var(--gold); box-shadow:0 0 25px rgba(252,185,0,0.35);">
            <img src="/assets/images/fast_site_world_app_icon.jpg" style="width:100%; height:100%; object-fit:cover;" alt="FAST SITE WORLD" onerror="this.onerror=null; this.src='/assets/images/logo.png';"/>
        </div>
        <h2 style="color:#fff; font-size:1.6rem; margin-bottom: 0.4rem; font-family:'Oswald',sans-serif; letter-spacing:0.5px;">FAST SITE WORLD</h2>
        <span style="font-size:0.78rem; color:var(--gold); font-weight:800; display:inline-block; margin-bottom:1rem; background:rgba(252,185,0,0.12); padding:3px 12px; border-radius:20px; border:1px solid rgba(252,185,0,0.3);">v2.0.4-world Official Release</span>
        <p style="color:var(--muted); max-width:400px; margin:0 auto 1.8rem auto; font-size:0.88rem; line-height:1.5;">Experience a faster, smoother way to manage your orders, shops, and wallet rewards on Android.</p>
        
        <a href="/fastsite_storefront.apk" download class="btn" style="display:inline-flex; align-items:center; justify-content:center; gap:10px; width:auto; padding:0.9rem 2.4rem; font-size:1rem; border-radius:30px; margin-bottom:1.8rem; text-decoration:none; font-weight:800; background:linear-gradient(135deg, #fcb900 0%, #ff9100 100%); color:#000; box-shadow:0 4px 20px rgba(252,185,0,0.4);">
            <svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg>
            Download APK (v2.0.4-world)
        </a>
        
        <div style="margin-top:1.2rem; border-top:1px solid rgba(255,255,255,0.06); padding-top:1.2rem;">
            <p style="color:var(--muted); font-size:0.8rem; margin-bottom:0.8rem; font-weight:700;">Share the App with Friends</p>
            <div style="display:flex; justify-content:center; gap:0.8rem; flex-wrap:wrap;">
                <a href="https://wa.me/?text=<?= urlencode('Download FAST SITE WORLD Official App: https://fastsite.best-travel.ltd/download.php') ?>" target="_blank" style="background:#25D366; color:#000; padding:8px 18px; border-radius:8px; text-decoration:none; font-weight:bold; font-size:0.82rem;">WhatsApp</a>
                <a href="https://www.facebook.com/sharer/sharer.php?u=https://fastsite.best-travel.ltd/download.php" target="_blank" style="background:#1877F2; color:#fff; padding:8px 18px; border-radius:8px; text-decoration:none; font-weight:bold; font-size:0.82rem;">Facebook</a>
            </div>
        </div>
    </div>
  </div>

  <!-- 4. SUPPORT CENTER TAB -->
  <!-- ══════════════════════════════════════ -->
  <div class="tab-content" id="tab-support" style="display:none; margin-top:2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom:1.5rem;">
      <h2 style="color:var(--brand); margin-bottom: 0;">💬 Support Center</h2>
    </div>
    <p style="color:var(--muted); font-size: 0.9rem; margin-top:0.5rem; margin-bottom:1.5rem;">Need assistance? Our dedicated support team is available to help you with orders, wallet deposits, and partner inquiries.</p>

    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap:1.2rem; margin-bottom:2rem;">
      <!-- WhatsApp Direct Support -->
      <div class="premium-card" style="background:linear-gradient(135deg, rgba(37, 211, 102, 0.1), rgba(20, 20, 31, 0.8)); border:1px solid rgba(37, 211, 102, 0.3); border-radius:16px; padding:1.5rem; text-align:center;">
        <div style="font-size:3rem; margin-bottom:0.5rem;">💬</div>
        <h3 style="color:#25D366; margin-bottom:0.5rem;">Official WhatsApp Support</h3>
        <p style="color:var(--muted); font-size:0.85rem; margin-bottom:1.2rem;">Chat directly with our official support agents for instant responses.</p>
        <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $whatsappSupport ?? '01963601472') ?>?text=<?= urlencode('Hello Fast Site Support, I need help with my account (User ID: ' . $userId . ')') ?>" target="_blank" class="btn" style="background:#25D366; color:#000; font-weight:800; border-radius:30px; text-decoration:none; padding:0.7rem 1.5rem; display:inline-block; box-shadow:0 4px 15px rgba(37,211,102,0.4);">
          Open WhatsApp Chat ➔
        </a>
      </div>

      <!-- Official Email Support -->
      <div class="premium-card" style="background:linear-gradient(135deg, rgba(252, 185, 0, 0.1), rgba(20, 20, 31, 0.8)); border:1px solid rgba(252, 185, 0, 0.3); border-radius:16px; padding:1.5rem; text-align:center;">
        <div style="font-size:3rem; margin-bottom:0.5rem;">📧</div>
        <h3 style="color:var(--gold); margin-bottom:0.5rem;">Official Support Email</h3>
        <p style="color:var(--muted); font-size:0.85rem; margin-bottom:1.2rem;">For official requests, dispute escalation, or verification:</p>
        <a href="mailto:info.fastsite@gmail.com" class="btn" style="background:linear-gradient(135deg, var(--gold), #ff9100); color:#000; font-weight:800; border-radius:30px; text-decoration:none; padding:0.7rem 1.5rem; display:inline-block; box-shadow:0 4px 15px rgba(252,185,0,0.3);">
          info.fastsite@gmail.com
        </a>
      </div>

      <!-- Messages / Seller Chat -->
      <div class="premium-card" style="background:linear-gradient(135deg, rgba(33, 150, 243, 0.1), rgba(20, 20, 31, 0.8)); border:1px solid rgba(33, 150, 243, 0.3); border-radius:16px; padding:1.5rem; text-align:center;">
        <div style="font-size:3rem; margin-bottom:0.5rem;">✉️</div>
        <h3 style="color:var(--brand); margin-bottom:0.5rem;">In-App Messages</h3>
        <p style="color:var(--muted); font-size:0.85rem; margin-bottom:1.2rem;">Message sellers or view replies regarding your active product orders.</p>
        <a href="messages.php" class="btn" style="background:linear-gradient(135deg, var(--brand), #007bb5); color:#fff; font-weight:800; border-radius:30px; text-decoration:none; padding:0.7rem 1.5rem; display:inline-block;">
          Go to Messages ➔
        </a>
      </div>
    </div>

    <!-- FAQ Accordion -->
    <div class="premium-card" style="border-radius:16px; padding:1.5rem;">
      <h3 style="color:#fff; margin-bottom:1.2rem;">❓ Frequently Asked Questions</h3>
      <details style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08); border-radius:10px; padding:1rem; margin-bottom:0.8rem; cursor:pointer;">
        <summary style="font-weight:700; color:#fff;">How do I deposit funds or buy Coins?</summary>
        <p style="color:var(--muted); font-size:0.85rem; margin-top:0.8rem; line-height:1.5;">Navigate to the <strong>Deposit / Buy Points</strong> page, enter the desired amount, choose bKash or Nagad, and submit the transaction ID.</p>
      </details>
      <details style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08); border-radius:10px; padding:1rem; margin-bottom:0.8rem; cursor:pointer;">
        <summary style="font-weight:700; color:#fff;">How do I open a free seller shop?</summary>
        <p style="color:var(--muted); font-size:0.85rem; margin-top:0.8rem; line-height:1.5;">Click <strong>Open a Free Shop</strong> in the sidebar menu, fill in your shop name and details, and start listing products once approved.</p>
      </details>
      <details style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08); border-radius:10px; padding:1rem; cursor:pointer;">
        <summary style="font-weight:700; color:#fff;">How do referral commissions work?</summary>
        <p style="color:var(--muted); font-size:0.85rem; margin-top:0.8rem; line-height:1.5;">Share your referral link from the <strong>Missions & Real Cash Hub</strong>. When users order services or purchase items, you instantly earn cashback & coins!</p>
      </details>
    </div>
  </div>

<!-- ══════════════════════════════════════ -->
<!-- 5. PREMIUM FOOTER -->
<!-- ══════════════════════════════════════ -->
<div style="text-align:center; padding: 2.5rem 1rem; border-top: 1px solid rgba(255,255,255,0.05); margin-top: 3rem; background:rgba(0,0,0,0.2); border-radius:0 0 20px 20px;">
    <div style="display:flex; justify-content:center; gap:1rem; margin-bottom:1.5rem; flex-wrap:wrap;">
        <a href="../policy.php" style="background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); color:var(--text); text-decoration:none; padding:0.6rem 1.2rem; border-radius:30px; font-size:0.85rem; font-weight:600; display:flex; align-items:center; gap:6px; transition:all 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.1)'" onmouseout="this.style.background='rgba(255,255,255,0.05)'">
            📜 Terms & Policies
        </a>
        <a href="dashboard.php?tab=support" style="background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); color:var(--text); text-decoration:none; padding:0.6rem 1.2rem; border-radius:30px; font-size:0.85rem; font-weight:600; display:flex; align-items:center; gap:6px; transition:all 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.1)'" onmouseout="this.style.background='rgba(255,255,255,0.05)'">
            ❓ Guide & Help
        </a>
    </div>
    <div style="color:var(--muted); font-size:0.8rem; font-weight:500; letter-spacing:0.5px;">
        &copy; <?= date('Y') ?> <?= htmlspecialchars($siteName ?? 'Fast Site') ?>. All rights reserved.
    </div>
</div>

<!-- Cash Loop pop-up -->
<div class="modal-overlay" id="cashModal">
  <div class="modal-card">
    <div class="m-icon">🎉</div>
    <div class="m-title">Congratulations!</div>
    <p style="font-size:0.8rem; color:var(--muted);">A client placed an order using your link. You have earned a referral commission of:</p>
    <div class="m-cash">৳<?= number_format($totalPending, 2) ?></div>
    <div class="m-text">Upgrade your account to a <strong>Fast Site Agent</strong> for FREE to instantly unlock and withdraw your cash earnings!</div>
    <a href="become_agent.php?upgrade=1" class="m-btn">Claim & Cash Out 💸</a>
    <br/>
    <button class="m-close" onclick="closeModal()">Maybe Later</button>
  </div>
</div>

<div class="toast" id="copy-toast">Link Copied to Clipboard! ✓</div>

</div> <!-- /dashboard-container -->

<script>
  function switchTab(evt, tabId) {
    const target = document.getElementById(tabId);
    if (!target) return;

    // Hide all contents
    const contents = document.querySelectorAll('.tab-content');
    contents.forEach(c => {
        c.classList.remove('active');
        c.style.display = 'none';
    });
    
    // Deactivate all buttons
    const buttons = document.querySelectorAll('.tab-btn');
    buttons.forEach(b => b.classList.remove('active'));
    
    // Show active tab
    target.style.display = 'block';
    target.classList.add('active');
    
    if (evt && evt.currentTarget && evt.currentTarget.classList) {
      evt.currentTarget.classList.add('active');
    }
    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  function switchUserTab(tabName) {
    if (tabName === 'social') tabName = 'settings';
    const target = document.getElementById('tab-' + tabName);
    if (!target) return;
    switchTab(null, 'tab-' + tabName);
    const url = new URL(window.location);
    url.searchParams.set('tab', tabName);
    window.history.pushState({}, '', url);

    // Synchronize bottom dock active highlighting
    document.querySelectorAll('#user-floating-bottom-nav .b-nav-item').forEach(el => el.classList.remove('active'));
    if (tabName === 'orders') {
      const dockOrders = document.getElementById('dock-item-orders');
      if (dockOrders) dockOrders.classList.add('active');
    } else if (tabName === 'settings') {
      const dockProfile = document.getElementById('dock-item-profile');
      if (dockProfile) dockProfile.classList.add('active');
    }
  }

  function copyLink(elementId, btnElement) {
    let el = null;
    if (typeof elementId === 'string' && elementId.length > 0) {
      el = document.getElementById(elementId);
    }
    if (!el) {
      el = document.getElementById('reflink-quick') || document.getElementById('reflink-share') || document.getElementById('reflink-agent') || document.getElementById('reflink');
    }
    if (!el) return;
    
    el.select(); 
    el.setSelectionRange(0, 99999);
    const btn = btnElement || document.getElementById('copyRefBtn');
    
    const finishCopy = () => {
      if (btn) {
        const orig = btn.innerHTML;
        btn.innerHTML = '✓ Copied!';
        btn.style.background = '#00e676';
        btn.style.color = '#000';
        setTimeout(() => {
          btn.innerHTML = orig;
          btn.style.background = '';
          btn.style.color = '';
        }, 2000);
      }
      showToast();
    };

    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(el.value).then(finishCopy).catch(() => {
        document.execCommand('copy');
        finishCopy();
      });
    } else {
      document.execCommand('copy');
      finishCopy();
    }
  }

  function showToast() {
    const toast = document.getElementById('copy-toast');
    if (toast) {
      toast.classList.add('active');
      setTimeout(() => {
        toast.classList.remove('active');
      }, 2000);
    }
  }

  // Handle URL tab parameter on page load and modal
  window.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    let tabParam = urlParams.get('tab');
    if (tabParam === 'social') {
      tabParam = 'settings';
    }
    if (tabParam) {
      const tabEl = document.getElementById('tab-' + tabParam);
      if (tabEl) {
         document.querySelectorAll('.tab-content').forEach(c => {
           c.classList.remove('active');
           c.style.display = 'none';
         });
         tabEl.classList.add('active');
         tabEl.style.display = 'block';
         const activeBtn = document.querySelector(`.tab-btn[onclick*="tab-${tabParam}"]`);
         if (activeBtn) {
           document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
           activeBtn.classList.add('active');
         }
         if (tabParam === 'orders') {
           const dockOrders = document.getElementById('dock-item-orders');
           if (dockOrders) {
             document.querySelectorAll('#user-floating-bottom-nav .b-nav-item').forEach(el => el.classList.remove('active'));
             dockOrders.classList.add('active');
           }
         } else if (tabParam === 'settings') {
           const dockProfile = document.getElementById('dock-item-profile');
           if (dockProfile) {
             document.querySelectorAll('#user-floating-bottom-nav .b-nav-item').forEach(el => el.classList.remove('active'));
             dockProfile.classList.add('active');
           }
         }
      }
    }

    // Restore settings toggles from localStorage
    const sfToggle = document.getElementById('storefront-toggle');
    if (sfToggle) {
      const sfSaved = localStorage.getItem('fastsite_storefront_view');
      const isEnabled = (sfSaved === null) ? true : (sfSaved === '1');
      sfToggle.checked = isEnabled;
      toggleStorefrontView(isEnabled);
    }

    const bioToggle = document.getElementById('biometric-toggle');
    if (bioToggle) {
      bioToggle.checked = localStorage.getItem('fastsite_biometric_enabled') === '1';
    }

  <?php if($totalPending > 0): ?>
    setTimeout(() => {
      const cashM = document.getElementById('cashModal');
      if (cashM) cashM.classList.add('active');
    }, 800);
  <?php endif; ?>
  });

  function closeModal() {
    const cashM = document.getElementById('cashModal');
    if (cashM) cashM.classList.remove('active');
  }

  function toggleStorefrontView(isActive) {
    const shopperView = document.getElementById('marketplace-shopper-view');
    if (shopperView) {
      shopperView.style.display = isActive ? 'block' : 'none';
    }
  }

  function handleBiometricToggle(checkbox) {
    if (checkbox.checked) {
      if (window.FastSiteNative && typeof window.FastSiteNative.requestBiometricAuth === 'function') {
        window.onBioEnrollSuccess = function() {
          localStorage.setItem('fastsite_biometric_enabled', '1');
          localStorage.setItem('fastsite_bio_user_id', '<?= (int)$user['id'] ?>');
          localStorage.setItem('fastsite_bio_phone', '<?= htmlspecialchars($user['phone'] ?? '') ?>');
          alert('✅ Biometric Sensor Verified! Fingerprint / Face ID login is now fully activated on this device.');
        };
        window.onBioEnrollFail = function(err) {
          checkbox.checked = false;
          localStorage.setItem('fastsite_biometric_enabled', '0');
          alert('❌ Biometric verification failed: ' + (err || 'Sensor not recognized'));
        };
        window.FastSiteNative.requestBiometricAuth('onBioEnrollSuccess', 'onBioEnrollFail');
      } else {
        localStorage.setItem('fastsite_biometric_enabled', '1');
        localStorage.setItem('fastsite_bio_user_id', '<?= (int)$user['id'] ?>');
        localStorage.setItem('fastsite_bio_phone', '<?= htmlspecialchars($user['phone'] ?? '') ?>');
        alert('✅ Biometric login preference enabled on this browser/device!');
      }
    } else {
      localStorage.setItem('fastsite_biometric_enabled', '0');
      alert('Biometric Login disabled.');
    }
  }
</script>
<?php
$currentTab = $_GET['tab'] ?? '';
$isOrdersTab = ($currentTab === 'orders');
$isStoreTab = ($currentPage === 'index.php');
$isWalletTab = ($currentPage === 'wallet.php');
$isProfileTab = ($currentPage === 'profile.php' || $currentTab === 'settings');
$totalActiveOrdersBadge = ($activeOrders ?? 0) + ($user_app_orders_count ?? 0);
$dockShopState = $shopState ?? ($shopStateData['state'] ?? 'none');
?>
<!-- Universal Mobile Bottom Floating Navigation Bar (5 Synchronized Slots - Phase 87 M1) -->
<div class="bottom-nav mobile-only-bottom-nav" id="user-floating-bottom-nav">
  <div class="bottom-nav-inner" style="display:grid; grid-template-columns:repeat(5, 1fr); align-items:center; width:100%; text-align:center; padding: 4px 6px;">
    
    <!-- 1. STORE (Slot 1) -->
    <a href="/index.php" id="dock-item-store" class="b-nav-item <?= $isStoreTab ? 'active' : '' ?>" title="Browse Store" style="display:flex; flex-direction:column; align-items:center; justify-content:center; min-height:48px; min-width:44px; text-decoration:none;">
      <svg width="22" height="22" viewBox="0 0 24 24" style="width:22px; height:22px; fill:currentColor; flex-shrink:0;">
        <path d="M20 4H4v2h16V4zm1 10v-2l-1-5H4l-1 5v2h1v6h10v-6h4v6h2v-6h1zm-9 4H6v-4h6v4z"/>
      </svg>
      <span class="dock-label" style="font-size:0.65rem; font-weight:700; margin-top:3px;">Store</span>
    </a>

    <!-- 2. ORDERS (Slot 2) -->
    <a href="/user/dashboard.php?tab=orders" id="dock-item-orders" onclick="switchUserTab('orders'); return false;" class="b-nav-item <?= $isOrdersTab ? 'active' : '' ?>" title="My Orders" style="display:flex; flex-direction:column; align-items:center; justify-content:center; min-height:48px; min-width:44px; text-decoration:none;">
      <div style="position:relative; display:inline-block;">
        <svg width="22" height="22" viewBox="0 0 24 24" style="width:22px; height:22px; fill:currentColor; flex-shrink:0;">
          <path d="M19 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.11 0 2-.9 2-2V5c0-1.1-.89-2-2-2zm-4 14H7v-2h8v2zm2-4H7v-2h10v2zm0-4H7V7h10v2z"/>
        </svg>
        <?php if ($totalActiveOrdersBadge > 0): ?>
          <span style="position:absolute; top:-4px; right:-6px; background:var(--brand, #2196F3); color:#fff; font-size:0.55rem; font-weight:800; padding:1px 5px; border-radius:10px; min-width:14px; text-align:center; box-shadow:0 0 6px rgba(33,150,243,0.6);"><?= (int)$totalActiveOrdersBadge ?></span>
        <?php endif; ?>
      </div>
      <span class="dock-label" style="font-size:0.65rem; font-weight:700; margin-top:3px;">Orders</span>
    </a>

    <!-- 3. MODE SWITCHER ACTION (Slot 3 - Center Elevate) -->
    <?php if ($dockShopState === 'approved'): ?>
      <a href="/partner/dashboard.php" id="dock-item-mode" class="b-nav-item b-nav-center-pill" title="Switch to Shop Mode" style="display:flex; flex-direction:column; align-items:center; justify-content:center; min-height:48px; min-width:44px; text-decoration:none;">
        <div class="dock-center-circle mode-shop">
          <span class="circle-icon">🏪</span>
        </div>
        <span class="dock-label" style="font-size:0.65rem; font-weight:800; margin-top:2px; color:#f59e0b;">Shop Mode</span>
      </a>
    <?php elseif ($dockShopState === 'pending'): ?>
      <button type="button" id="dock-item-mode" onclick="openShopReviewModal()" class="b-nav-item b-nav-center-pill" title="Shop Application Under Review" style="display:flex; flex-direction:column; align-items:center; justify-content:center; min-height:48px; min-width:44px; text-decoration:none; background:none; border:none; padding:0; cursor:pointer;">
        <div class="dock-center-circle mode-review">
          <span class="circle-icon pulse-gold">⏳</span>
        </div>
        <span class="dock-label" style="font-size:0.65rem; font-weight:800; margin-top:2px; color:#f59e0b;">In Review</span>
      </button>
    <?php else: ?>
      <button type="button" id="dock-item-mode" onclick="openQuickShopDrawer()" class="b-nav-item b-nav-center-pill" title="Open Free Shop" style="display:flex; flex-direction:column; align-items:center; justify-content:center; min-height:48px; min-width:44px; text-decoration:none; background:none; border:none; padding:0; cursor:pointer;">
        <div class="dock-center-circle mode-create">
          <span class="circle-icon">➕</span>
        </div>
        <span class="dock-label" style="font-size:0.65rem; font-weight:800; margin-top:2px; color:#10b981;">Free Shop</span>
      </button>
    <?php endif; ?>

    <!-- 4. WALLET (Slot 4) -->
    <a href="/user/wallet.php" id="dock-item-wallet" class="b-nav-item <?= $isWalletTab ? 'active' : '' ?>" title="My Wallet" style="display:flex; flex-direction:column; align-items:center; justify-content:center; min-height:48px; min-width:44px; text-decoration:none;">
      <svg width="22" height="22" viewBox="0 0 24 24" style="width:22px; height:22px; fill:currentColor; flex-shrink:0; filter:drop-shadow(0 0 6px rgba(252,185,0,0.3));">
        <path d="M21 18v1c0 1.1-.9 2-2 2H5c-1.11 0-2-.9-2-2V5c0-1.1.89-2 2-2h14c1.1 0 2 .9 2 2v1h-9c-1.11 0-2 .9-2 2v8c0 1.1.89 2 2 2h9zm-9-2h10V8H12v8zm4-2.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/>
      </svg>
      <span class="dock-label" style="font-size:0.65rem; font-weight:800; margin-top:3px; color:var(--gold, #fcb900);">Wallet</span>
    </a>

    <!-- 5. PROFILE (Slot 5) -->
    <a href="/user/profile.php" id="dock-item-profile" class="b-nav-item <?= $isProfileTab ? 'active' : '' ?>" title="My Profile" style="display:flex; flex-direction:column; align-items:center; justify-content:center; min-height:48px; min-width:44px; text-decoration:none;">
      <div style="width:26px; height:26px; border-radius:50%; border:2px solid var(--gold, #fcb900); overflow:hidden; display:flex; align-items:center; justify-content:center; box-shadow:0 0 8px rgba(252,185,0,0.4); background:#12121c; margin-bottom:1px; flex-shrink:0;">
        <?php 
          $bottom_pic = !empty($user['profile_pic']) ? '/' . ltrim($user['profile_pic'], '/') : '/assets/images/default_avatar.png'; 
        ?>
        <img src="<?= htmlspecialchars($bottom_pic) ?>" alt="Profile" style="width:100%; height:100%; object-fit:cover;" onerror="this.onerror=null; this.src='/assets/images/default_avatar.png';">
      </div>
      <span class="dock-label" style="font-size:0.65rem; font-weight:700; margin-top:2px;">Profile</span>
    </a>
  </div>
</div>

<style>
@media (min-width: 1025px) {
  .mobile-only-bottom-nav, .bottom-nav { display: none !important; }
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
