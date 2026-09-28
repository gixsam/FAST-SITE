<?php
// =========================================================================
// partner/dashboard.php  –  UNIFIED EXECUTIVE PARTNER COMMAND HUB
// =========================================================================
ob_start();
require_once __DIR__ . '/nav.php';
$_nav_html = ob_get_clean();

$partner_id = (int)($_SESSION['partner_id'] ?? 0);
$user_id    = (int)($_SESSION['user_id'] ?? 0);

// Fetch partner details & approved roles
$partner = [];
try {
    if ($partner_id > 0) {
        $pStmt = $pdo->prepare("SELECT * FROM partners WHERE id = ? LIMIT 1");
        $pStmt->execute([$partner_id]);
        $partner = $pStmt->fetch(PDO::FETCH_ASSOC) ?? [];
    }
    if (empty($partner) && $user_id > 0) {
        $pStmt = $pdo->prepare("SELECT * FROM partners WHERE user_id = ? ORDER BY id DESC LIMIT 1");
        $pStmt->execute([$user_id]);
        $partner = $pStmt->fetch(PDO::FETCH_ASSOC) ?? [];
    }
    if (empty($partner) && $user_id > 0) {
        $uStmt = $pdo->prepare("SELECT phone FROM users WHERE id = ? LIMIT 1");
        $uStmt->execute([$user_id]);
        $uPhone = $uStmt->fetchColumn();
        if ($uPhone) {
            $pStmt = $pdo->prepare("SELECT * FROM partners WHERE phone = ? ORDER BY id DESC LIMIT 1");
            $pStmt->execute([$uPhone]);
            $partner = $pStmt->fetch(PDO::FETCH_ASSOC) ?? [];
        }
    }
    if (!empty($partner['id'])) {
        $partner_id = (int)$partner['id'];
        $_SESSION['partner_id'] = $partner_id;
    }
} catch (Exception $e) {}

// Auto-register shop handler if form submitted
if (empty($partner) && isset($_POST['action']) && $_POST['action'] === 'register_shop') {
    $business_name = trim($_POST['business_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $category = trim($_POST['category'] ?? 'Retail & E-commerce');
    
    if (!empty($business_name)) {
        $shop_slug = strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '', str_replace(' ', '_', $business_name)));
        try {
            $ins = $pdo->prepare("INSERT INTO partners (user_id, business_name, shop_slug, phone, category, status) VALUES (?, ?, ?, ?, ?, 'pending')");
            $ins->execute([$user_id, $business_name, $shop_slug, $phone, $category]);
            $partner_id = (int)$pdo->lastInsertId();
            $_SESSION['partner_id'] = $partner_id;
            header("Location: /partner/dashboard.php");
            exit;
        } catch (Exception $ex) {}
    }
}

// Phase 67: Promo Codes & Announcements POST Handlers
if (!empty($partner) && isset($_POST['action'])) {
    if ($_POST['action'] === 'save_announcement') {
        $announcement_text = trim($_POST['announcement_text'] ?? '');
        $announcement_bg = trim($_POST['announcement_bg'] ?? '#fcb900');
        try {
            $upd = $pdo->prepare("UPDATE partners SET announcement_text = ?, announcement_bg = ? WHERE id = ?");
            $upd->execute([$announcement_text, $announcement_bg, $partner_id]);
            // refresh partner array
            $partner['announcement_text'] = $announcement_text;
            $partner['announcement_bg'] = $announcement_bg;
        } catch (Exception $ex) {}
    }
    
    if ($_POST['action'] === 'create_coupon') {
        $coupon_code = strtoupper(trim($_POST['coupon_code'] ?? ''));
        $discount_type = trim($_POST['discount_type'] ?? 'percentage');
        $discount_value = floatval($_POST['discount_value'] ?? 0);
        $min_order_bdt = floatval($_POST['min_order_bdt'] ?? 0);
        $usage_limit = intval($_POST['usage_limit'] ?? 100);
        $expires_at = !empty($_POST['expires_at']) ? $_POST['expires_at'] . ' 23:59:59' : null;
        
        if ($coupon_code && $discount_value > 0) {
            try {
                $ins = $pdo->prepare("INSERT INTO shop_coupons (partner_id, coupon_code, discount_type, discount_value, min_order_bdt, usage_limit, expires_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $ins->execute([$partner_id, $coupon_code, $discount_type, $discount_value, $min_order_bdt, $usage_limit, $expires_at]);
            } catch (Exception $ex) {}
        }
    }
    
    if ($_POST['action'] === 'delete_coupon') {
        $coupon_id = (int)($_POST['coupon_id'] ?? 0);
        try {
            $del = $pdo->prepare("DELETE FROM shop_coupons WHERE id = ? AND partner_id = ?");
            $del->execute([$coupon_id, $partner_id]);
        } catch (Exception $ex) {}
    }

    if ($_POST['action'] === 'save_shop_settings') {
        $upload_dir = __DIR__ . '/../uploads/partners/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $profile_pic = $partner['profile_pic'] ?? '';
        $cover_pic = $partner['cover_pic'] ?? '';
        
        if (!empty($_FILES['profile_pic']['name'])) {
            $ext = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','webp','gif'])) {
                $filename = 'prof_' . $partner_id . '_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $upload_dir . $filename)) {
                    $profile_pic = $filename;
                }
            }
        }
        
        if (!empty($_FILES['cover_pic']['name'])) {
            $ext = strtolower(pathinfo($_FILES['cover_pic']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','webp','gif'])) {
                $filename = 'cov_' . $partner_id . '_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['cover_pic']['tmp_name'], $upload_dir . $filename)) {
                    $cover_pic = $filename;
                }
            }
        }
        
        try {
            $upd = $pdo->prepare("UPDATE partners SET profile_pic = :pic, logo_url = :pic, cover_pic = :cover, banner_url = :cover WHERE id = :id");
            $upd->execute([
                ':pic' => $profile_pic,
                ':cover' => $cover_pic,
                ':id' => $partner_id
            ]);
            $partner['profile_pic'] = $profile_pic;
            $partner['logo_url'] = $profile_pic;
            $partner['cover_pic'] = $cover_pic;
            $partner['banner_url'] = $cover_pic;
        } catch (Exception $ex) {}
    }
}

// User approved roles
$user_roles = ['seller', 'user_partner'];
if (!empty($partner['roles'])) {
    $user_roles = array_merge($user_roles, explode(',', strtolower($partner['roles'])));
}

// Stat queries
$product_count = 0;
$active_orders_count = 0;
$completed_orders_count = 0;
$lifetime_revenue = 0.0;

try {
    $stmt_prod = $pdo->prepare("SELECT COUNT(*) FROM partner_products WHERE partner_id = :id");
    $stmt_prod->execute([':id' => $partner_id]);
    $product_count = (int)$stmt_prod->fetchColumn();

    $stmt_orders = $pdo->prepare("SELECT COUNT(*) FROM partner_orders WHERE partner_id = :id AND status IN ('pending', 'accepted', 'in_progress', 'waiting_confirmation')");
    $stmt_orders->execute([':id' => $partner_id]);
    $active_orders_count = (int)$stmt_orders->fetchColumn();

    $stmt_comp = $pdo->prepare("SELECT COUNT(*) FROM partner_orders WHERE partner_id = :id AND status = 'completed'");
    $stmt_comp->execute([':id' => $partner_id]);
    $completed_orders_count = (int)$stmt_comp->fetchColumn();

    $stmt_rev = $pdo->prepare("SELECT SUM(total_coins) FROM partner_orders WHERE partner_id = :id AND status = 'completed'");
    $stmt_rev->execute([':id' => $partner_id]);
    $lifetime_revenue = floatval($stmt_rev->fetchColumn() ?? 0);
} catch (Exception $e) {}

// Fetch Wallet Balance
try {
    $stmt_earn = $pdo->prepare("SELECT coins_balance FROM users WHERE id = :uid LIMIT 1");
    $stmt_earn->execute([':uid' => $user_id]);
    $available_balance_bdt = floatval($stmt_earn->fetchColumn() ?? 0);
} catch (Exception $e) {
    $available_balance_bdt = 0;
}

// Fetch Recent Orders with Product & Customer details
$recent_orders = [];
try {
    $stmt_recent = $pdo->prepare("SELECT o.*, p.title AS product_title, u.name AS customer_name, u.phone AS customer_phone 
        FROM partner_orders o 
        LEFT JOIN partner_products p ON o.product_id = p.id 
        LEFT JOIN users u ON o.customer_id = u.id 
        WHERE o.partner_id = :partner_id 
        ORDER BY o.created_at DESC LIMIT 6");
    $stmt_recent->execute([':partner_id' => $partner_id]);
    $recent_orders = $stmt_recent->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Fetch Active Products for inventory tab
$my_products = [];
try {
    $stmt_my_prods = $pdo->prepare("SELECT p.*, 
        (SELECT image_url FROM partner_product_images WHERE product_id = p.id ORDER BY is_thumbnail DESC, id ASC LIMIT 1) as thumbnail 
        FROM partner_products p 
        WHERE p.partner_id = :pid 
        ORDER BY p.created_at DESC LIMIT 8");
    $stmt_my_prods->execute([':pid' => $partner_id]);
    $my_products = $stmt_my_prods->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

$shop_slug = strtolower(str_replace(' ', '_', $partner['business_name'] ?? 'partner_shop'));
$clean_shop_url = "https://fastsite.best-travel.ltd/shop/" . urlencode($shop_slug);
$shareText = urlencode("Check out official products & services on " . ($partner['business_name'] ?? 'Fast Site Store') . "!");
$encodedShopUrl = urlencode($clean_shop_url);

// Fetch Active Promo Codes
$shop_coupons = [];
try {
    $stmt_c = $pdo->prepare("SELECT * FROM shop_coupons WHERE partner_id = :pid ORDER BY created_at DESC");
    $stmt_c->execute([':pid' => $partner_id]);
    $shop_coupons = $stmt_c->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <title>Partner Command Center &mdash; Fast Site Super-App</title>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Oswald:wght@500;600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="/assets/css/admin.css">
  <style>
    :root {
      --gold: #fcb900;
      --gold-glow: rgba(252, 185, 0, 0.25);
      --teal: #00e676;
      --teal-glow: rgba(0, 230, 118, 0.25);
      --dark-card: rgba(18, 20, 32, 0.85);
      --border-glass: rgba(255, 255, 255, 0.08);
    }

    body {
      background: radial-gradient(circle at 50% 0%, #151828 0%, #090a12 100%);
      color: #e2e8f0;
      font-family: 'Inter', sans-serif;
      min-height: 100vh;
      margin: 0;
      padding: 0;
    }

    .hub-container {
      max-width: 1280px;
      margin: 2rem auto;
      padding: 0 1.2rem;
    }

    /* Executive Hero Card */
    .executive-hero {
      background: linear-gradient(135deg, rgba(252, 185, 0, 0.12) 0%, rgba(20, 24, 40, 0.8) 100%);
      border: 1px solid rgba(252, 185, 0, 0.3);
      border-radius: 20px;
      padding: 2rem 2.2rem;
      margin-bottom: 2rem;
      backdrop-filter: blur(16px);
      box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 1.5rem;
    }

    .hero-identity {
      display: flex;
      align-items: center;
      gap: 1.2rem;
    }

    .shop-avatar-large {
      width: 70px;
      height: 70px;
      border-radius: 18px;
      object-fit: cover;
      border: 2px solid var(--gold);
      box-shadow: 0 0 20px rgba(252,185,0,0.3);
    }

    .hero-title {
      font-family: 'Oswald', sans-serif;
      font-size: 2rem;
      color: #fff;
      margin: 0 0 0.2rem 0;
      text-transform: uppercase;
      letter-spacing: 0.04em;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .hero-badges {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      flex-wrap: wrap;
    }

    .badge-hero {
      font-size: 0.72rem;
      font-weight: 800;
      padding: 3px 10px;
      border-radius: 50px;
      text-transform: uppercase;
    }

    .quick-action-bar {
      display: flex;
      gap: 0.8rem;
      flex-wrap: wrap;
    }

    .btn-action-hero {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 0.75rem 1.4rem;
      border-radius: 12px;
      font-weight: 800;
      font-size: 0.88rem;
      text-decoration: none;
      transition: all 0.25s;
    }

    .btn-action-gold {
      background: linear-gradient(135deg, var(--gold) 0%, #ff9100 100%);
      color: #000;
      box-shadow: 0 6px 20px rgba(252, 185, 0, 0.35);
    }

    .btn-action-gold:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 25px rgba(252, 185, 0, 0.5);
    }

    .btn-action-glass {
      background: rgba(255, 255, 255, 0.06);
      border: 1px solid rgba(255, 255, 255, 0.12);
      color: #fff;
    }

    .btn-action-glass:hover {
      background: rgba(255, 255, 255, 0.12);
      border-color: rgba(255, 255, 255, 0.25);
      transform: translateY(-2px);
    }

    .btn-action-buyer {
      background: rgba(33, 150, 243, 0.15);
      border: 1px solid rgba(56, 189, 248, 0.4);
      color: #38bdf8;
      box-shadow: 0 4px 14px rgba(33, 150, 243, 0.15);
    }

    .btn-action-buyer:hover {
      background: rgba(33, 150, 243, 0.25);
      border-color: rgba(56, 189, 248, 0.7);
      color: #7dd3fc;
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(33, 150, 243, 0.3);
    }

    .btn-action-buyer:active {
      transform: scale(0.96);
    }

    /* KPI Metrics Grid */
    .kpi-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
      gap: 1.2rem;
      margin-bottom: 2rem;
    }

    .kpi-card {
      background: var(--dark-card);
      border: 1px solid var(--border-glass);
      border-radius: 18px;
      padding: 1.4rem 1.6rem;
      backdrop-filter: blur(12px);
      position: relative;
      overflow: hidden;
      box-shadow: 0 10px 30px rgba(0,0,0,0.3);
      transition: all 0.25s;
    }

    .kpi-card:hover {
      border-color: rgba(252, 185, 0, 0.35);
      transform: translateY(-3px);
    }

    .kpi-card:before {
      content: '';
      position: absolute;
      top: 0; left: 0; right: 0; height: 3px;
      background: linear-gradient(90deg, var(--gold), var(--teal));
    }

    .kpi-lbl {
      font-size: 0.72rem;
      font-weight: 700;
      color: #94a3b8;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      margin-bottom: 0.3rem;
    }

    .kpi-val {
      font-family: 'Oswald', sans-serif;
      font-size: 2rem;
      font-weight: 700;
      color: #fff;
      line-height: 1.2;
    }

    .kpi-sub {
      font-size: 0.76rem;
      color: #64748b;
      margin-top: 0.3rem;
    }

    /* Tab Switcher Navigation */
    .hub-tabs-bar {
      display: flex;
      gap: 0.6rem;
      background: rgba(10, 12, 20, 0.8);
      padding: 0.5rem;
      border-radius: 14px;
      border: 1px solid rgba(255, 255, 255, 0.08);
      margin-bottom: 1.8rem;
      overflow-x: auto;
    }

    .hub-tab-btn {
      background: none;
      border: none;
      color: #94a3b8;
      font-size: 0.88rem;
      font-weight: 800;
      padding: 0.75rem 1.4rem;
      border-radius: 10px;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 8px;
      transition: all 0.2s;
      white-space: nowrap;
    }

    .hub-tab-btn:hover {
      color: #fff;
      background: rgba(255, 255, 255, 0.05);
    }

    .hub-tab-btn.active {
      background: rgba(252, 185, 0, 0.15);
      color: var(--gold);
      box-shadow: 0 0 15px rgba(252, 185, 0, 0.2);
    }

    .tab-pane {
      display: none;
      animation: fadeIn 0.3s ease-out;
    }
    .tab-pane.active { display: block; }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(8px); }
      to { opacity: 1; transform: translateY(0); }
    }

    /* Content Cards */
    .panel-card {
      background: var(--dark-card);
      border: 1px solid var(--border-glass);
      border-radius: 20px;
      padding: 1.8rem;
      margin-bottom: 1.8rem;
      backdrop-filter: blur(16px);
      box-shadow: 0 15px 40px rgba(0,0,0,0.4);
    }

    .panel-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.4rem;
      border-bottom: 1px solid rgba(255, 255, 255, 0.06);
      padding-bottom: 0.8rem;
    }

    .panel-title {
      font-family: 'Oswald', sans-serif;
      font-size: 1.3rem;
      color: #fff;
      text-transform: uppercase;
      margin: 0;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    /* Products Grid */
    .products-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
      gap: 1.2rem;
    }

    .product-card-mini {
      background: rgba(10, 12, 20, 0.6);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 14px;
      overflow: hidden;
      transition: all 0.25s;
    }

    .product-card-mini:hover {
      border-color: rgba(252, 185, 0, 0.4);
      transform: translateY(-4px);
      box-shadow: 0 10px 30px rgba(0,0,0,0.5);
    }

    .prod-thumb {
      width: 100%;
      height: 140px;
      object-fit: cover;
      background: #080911;
    }

    .prod-info {
      padding: 1rem;
    }

    /* Orders Table */
    table.hub-table {
      width: 100%;
      border-collapse: collapse;
    }

    table.hub-table th, table.hub-table td {
      padding: 0.9rem 1rem;
      text-align: left;
      border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }

    table.hub-table th {
      font-size: 0.72rem;
      text-transform: uppercase;
      color: #94a3b8;
      font-weight: 700;
      letter-spacing: 0.05em;
    }

    .status-badge-mini {
      font-size: 0.68rem;
      font-weight: 800;
      padding: 2px 8px;
      border-radius: 50px;
      text-transform: uppercase;
    }

    /* ── Mobile APK Responsive Styles (< 768px & < 480px) ── */
    @media (max-width: 768px) {
      body {
        padding-left: 0 !important;
        max-width: 100vw !important;
        overflow-x: hidden !important;
      }
      .hub-container {
        padding: 0 0.8rem !important;
        margin: 1rem auto !important;
        max-width: 100vw !important;
        overflow-x: hidden !important;
        box-sizing: border-box !important;
      }
      .executive-hero {
        padding: 1.2rem 1rem !important;
        border-radius: 16px !important;
        flex-direction: column !important;
        align-items: stretch !important;
        gap: 1.2rem !important;
        box-sizing: border-box !important;
        width: 100% !important;
        max-width: 100% !important;
      }
      .hero-identity {
        gap: 0.8rem !important;
        flex-wrap: wrap !important;
        width: 100% !important;
      }
      .shop-avatar-large {
        width: 50px !important;
        height: 50px !important;
        border-radius: 12px !important;
      }
      .hero-title {
        font-size: 1.35rem !important;
        word-break: break-word !important;
        flex-wrap: wrap !important;
      }
      .quick-action-bar {
        width: 100% !important;
        display: flex !important;
        flex-direction: column !important;
        gap: 0.6rem !important;
      }
      .btn-action-hero {
        width: 100% !important;
        box-sizing: border-box !important;
        justify-content: center !important;
        padding: 0.75rem 1rem !important;
        font-size: 0.85rem !important;
      }
      .kpi-grid {
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 0.6rem !important;
        margin-bottom: 1.2rem !important;
        width: 100% !important;
        max-width: 100% !important;
        box-sizing: border-box !important;
      }
      .kpi-card {
        padding: 0.9rem 0.75rem !important;
        border-radius: 14px !important;
        min-width: 0 !important;
        box-sizing: border-box !important;
      }
      .kpi-lbl {
        font-size: 0.68rem !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
      }
      .kpi-val {
        font-size: 1.3rem !important;
        word-break: break-all !important;
      }
      .kpi-sub {
        font-size: 0.68rem !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
      }
      .hub-tabs-bar {
        width: 100% !important;
        max-width: 100% !important;
        overflow-x: auto !important;
        -webkit-overflow-scrolling: touch !important;
        flex-wrap: nowrap !important;
        padding: 0.4rem !important;
        gap: 0.4rem !important;
        box-sizing: border-box !important;
        scrollbar-width: none;
      }
      .hub-tabs-bar::-webkit-scrollbar {
        display: none;
      }
      .hub-tab-btn {
        padding: 0.6rem 0.85rem !important;
        font-size: 0.78rem !important;
        flex-shrink: 0 !important;
      }
      .panel-card {
        padding: 1.1rem 0.9rem !important;
        border-radius: 16px !important;
        box-sizing: border-box !important;
        width: 100% !important;
        max-width: 100% !important;
      }
      .products-grid {
        grid-template-columns: 1fr 1fr !important;
        gap: 0.6rem !important;
      }
      .table-responsive {
        width: 100% !important;
        overflow-x: auto !important;
        -webkit-overflow-scrolling: touch !important;
      }
      /* Disable heavy blur filters during touch scroll on mobile for 60fps fluidity */
      .hub-container, .executive-hero, .panel-card, .kpi-card {
        -webkit-backdrop-filter: none !important;
        backdrop-filter: none !important;
        transform: translateZ(0);
        -webkit-transform: translateZ(0);
      }
    }
    @media (max-width: 400px) {
      .kpi-grid {
        grid-template-columns: 1fr !important;
      }
      .products-grid {
        grid-template-columns: 1fr !important;
      }
    }
  </style>
</head>
<body>
<?php echo $_nav_html; ?>

<div class="hub-container">
  
  <!-- Executive Shop Hero Card -->
  <div class="executive-hero" style="background: linear-gradient(135deg, rgba(20,24,40,0.85) 0%, rgba(20,24,40,0.95) 100%), url('<?= resolveShopMedia($partner, 'cover') ?>') center/cover no-repeat;">
    <div class="hero-identity">
      <img src="<?= resolveShopMedia($partner, 'avatar') ?>" class="shop-avatar-large" alt="Shop Avatar" onerror="this.src='/assets/images/logo.png';"/>
      <div>
        <h1 class="hero-title">
          <span>⚡ <?= htmlspecialchars($partner['business_name'] ?? 'Fast Site Official Store') ?></span>
        </h1>
        <div class="hero-badges">
          <span class="badge-hero" style="background:rgba(0,230,118,0.15); color:#00e676; border:1px solid rgba(0,230,118,0.3);">
            🛡️ Verified Partner Store
          </span>
          <span class="badge-hero" style="background:rgba(252,185,0,0.15); color:var(--gold); border:1px solid rgba(252,185,0,0.3);">
            ⭐ 5.0 Rating (<?= intval($partner['total_reviews'] ?? 0) ?> Reviews)
          </span>
          <span class="badge-hero" style="background:rgba(255,255,255,0.06); color:#cbd5e1; border:1px solid rgba(255,255,255,0.1);">
            <?= htmlspecialchars($partner['category'] ?? 'Retail & Digital') ?>
          </span>
        </div>
      </div>
    </div>

    <div class="quick-action-bar">
      <a href="product_add.php" class="btn-action-hero btn-action-gold">
        ➕ Add New Product
      </a>
      <a href="orders.php" class="btn-action-hero btn-action-glass">
        📦 Orders (<?= $active_orders_count ?>)
      </a>
      <a href="javascript:void(0)" onclick="switchHubTab('tab-promos')" class="btn-action-hero btn-action-glass">
        🎟️ Promo Codes
      </a>
      <a href="/shop.php?id=<?= $partner_id ?>" target="_blank" class="btn-action-hero btn-action-glass">
        👁️ Live Storefront ↗
      </a>
      <a href="/user/dashboard.php" class="btn-action-hero btn-action-buyer" title="Switch to Buyer Mode">
        <span>👤</span> Switch to Buyer Mode
      </a>
    </div>
  </div>

  <!-- 6 Executive KPI Metric Cards -->
  <div class="kpi-grid">
    <div class="kpi-card">
      <div class="kpi-lbl">Wallet Balance</div>
      <div class="kpi-val" style="color:var(--teal);">৳<?= number_format($available_balance_bdt, 2) ?></div>
      <div class="kpi-sub">🪙 <?= number_format($available_balance_bdt, 0) ?> Fast Points</div>
    </div>

    <div class="kpi-card">
      <div class="kpi-lbl">Listed Products</div>
      <div class="kpi-val"><?= $product_count ?></div>
      <div class="kpi-sub">Active Catalog Items</div>
    </div>

    <div class="kpi-card">
      <div class="kpi-lbl">Orders to Fulfill</div>
      <div class="kpi-val" style="color:var(--gold);"><?= $active_orders_count ?></div>
      <div class="kpi-sub">Awaiting delivery</div>
    </div>

    <div class="kpi-card">
      <div class="kpi-lbl">Completed Orders</div>
      <div class="kpi-val"><?= $completed_orders_count ?></div>
      <div class="kpi-sub">Delivered &amp; Confirmed</div>
    </div>

    <div class="kpi-card">
      <div class="kpi-lbl">Total Sales Earned</div>
      <div class="kpi-val" style="color:var(--teal);">৳<?= number_format($lifetime_revenue, 2) ?></div>
      <div class="kpi-sub">Lifetime completed sales</div>
    </div>

    <div class="kpi-card">
      <div class="kpi-lbl">Customer Satisfaction</div>
      <div class="kpi-val" style="color:#00bcd4;">100%</div>
      <div class="kpi-sub">100% Verified score</div>
    </div>
  </div>

  <!-- Hub Tab Switcher -->
  <div class="hub-tabs-bar">
    <button class="hub-tab-btn active" onclick="switchHubTab('tab-overview')">📊 Shop Overview</button>
    <button class="hub-tab-btn" onclick="switchHubTab('tab-inventory')">🛍️ My Products (<?= $product_count ?>)</button>
    <button class="hub-tab-btn" onclick="switchHubTab('tab-orders')">📦 Customer Orders (<?= $active_orders_count ?>)</button>
    <button class="hub-tab-btn" onclick="switchHubTab('tab-marketing')">🚀 Promote &amp; Share</button>
    <button class="hub-tab-btn" onclick="switchHubTab('tab-promos')">🎟️ Promo Codes</button>
    <button class="hub-tab-btn" onclick="switchHubTab('tab-settings')">⚙️ Shop Settings</button>
  </div>

  <!-- TAB 1: SHOP OVERVIEW -->
  <div class="tab-pane active" id="tab-overview">
    
    <!-- Viral Shop Link Box -->
    <div class="panel-card" style="background: linear-gradient(135deg, rgba(252,185,0,0.06) 0%, rgba(10,12,20,0.9) 100%); border-color: rgba(252,185,0,0.3);">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.8rem; flex-wrap:wrap; gap:0.5rem;">
        <h4 style="margin:0; font-family:'Oswald',sans-serif; color:var(--gold); font-size:1.1rem; text-transform:uppercase;">
          🔗 Your Custom Shareable Storefront URL
        </h4>
        <span style="font-size:0.75rem; background:rgba(252,185,0,0.15); color:var(--gold); padding:2px 10px; border-radius:50px; font-weight:800;">
          Auto-Affiliate Active
        </span>
      </div>
      <p style="font-size:0.84rem; color:#94a3b8; margin:0 0 1rem 0;">
        Share your shop link with customers. When they make a purchase, payment is secured via platform Escrow.
      </p>

      <div style="display:flex; gap:0.6rem; flex-wrap:wrap; margin-bottom:1rem;">
        <input type="text" readonly value="<?= htmlspecialchars($clean_shop_url) ?>" id="shopLinkInput" style="flex:1; min-width:260px; background:#080911; border:1px solid rgba(255,255,255,0.15); color:var(--gold); padding:0.7rem 1rem; border-radius:10px; font-family:monospace; font-size:0.85rem;"/>
        <button onclick="copyShopLink()" id="copyShopBtn" style="background:linear-gradient(135deg, var(--gold), #ff9100); color:#000; font-weight:800; border:none; padding:0.7rem 1.4rem; border-radius:10px; cursor:pointer; font-size:0.85rem; transition:0.2s;">
          📋 Copy Link
        </button>
      </div>

      <!-- Social Share Grid -->
      <div style="display:flex; gap:0.6rem; flex-wrap:wrap;">
        <a href="https://wa.me/?text=<?= $shareText ?>%20<?= $encodedShopUrl ?>" target="_blank" style="background:#25D366; color:#fff; font-weight:700; text-decoration:none; padding:0.55rem 1rem; border-radius:8px; font-size:0.8rem; display:inline-flex; align-items:center; gap:6px;">
          💬 WhatsApp
        </a>
        <a href="https://www.facebook.com/sharer/sharer.php?u=<?= $encodedShopUrl ?>" target="_blank" style="background:#1877F2; color:#fff; font-weight:700; text-decoration:none; padding:0.55rem 1rem; border-radius:8px; font-size:0.8rem; display:inline-flex; align-items:center; gap:6px;">
          📘 Facebook
        </a>
        <a href="https://t.me/share/url?url=<?= $encodedShopUrl ?>&text=<?= $shareText ?>" target="_blank" style="background:#0088cc; color:#fff; font-weight:700; text-decoration:none; padding:0.55rem 1rem; border-radius:8px; font-size:0.8rem; display:inline-flex; align-items:center; gap:6px;">
          ✈️ Telegram
        </a>
      </div>
    </div>

    <!-- Recent Orders Panel -->
    <div class="panel-card">
      <div class="panel-header">
        <h3 class="panel-title">
          <span>📦</span> Recent Shop Orders
        </h3>
        <a href="orders.php" style="color:var(--gold); font-size:0.82rem; text-decoration:none; font-weight:700;">View All Orders →</a>
      </div>

      <?php if (empty($recent_orders)): ?>
        <div style="text-align:center; padding:3rem 1rem; color:#64748b;">
          <p style="margin:0; font-size:0.9rem;">No orders received yet. Start promoting your products!</p>
        </div>
      <?php else: ?>
        <div style="overflow-x:auto;">
          <table class="hub-table">
            <thead>
              <tr>
                <th>Order #</th>
                <th>Product</th>
                <th>Customer</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recent_orders as $ord): ?>
                <tr>
                  <td style="font-weight:800; font-family:monospace; color:#fff;">#<?= $ord['id'] ?></td>
                  <td style="color:#fff; font-weight:600;"><?= htmlspecialchars($ord['product_title'] ?? 'Custom Order') ?></td>
                  <td style="color:#94a3b8; font-size:0.85rem;">
                    👤 <?= htmlspecialchars($ord['customer_name'] ?? 'Buyer') ?><br>
                    📞 <?= htmlspecialchars($ord['customer_phone'] ?? 'N/A') ?>
                  </td>
                  <td style="color:var(--teal); font-weight:800;">৳<?= number_format($ord['total_coins'] ?? 0, 2) ?></td>
                  <td>
                    <?php 
                      $st = $ord['status'];
                      $color = '#fcb900';
                      $bg = 'rgba(252,185,0,0.15)';
                      if ($st === 'completed') { $color = '#00e676'; $bg = 'rgba(0,230,118,0.15)'; }
                      elseif ($st === 'cancelled') { $color = '#ff5252'; $bg = 'rgba(255,82,82,0.15)'; }
                    ?>
                    <span class="status-badge-mini" style="background:<?= $bg ?>; color:<?= $color ?>; border:1px solid <?= $color ?>;">
                      <?= strtoupper(str_replace('_', ' ', $st)) ?>
                    </span>
                  </td>
                  <td>
                    <a href="orders.php" style="background:rgba(255,255,255,0.08); color:#fff; padding:4px 10px; border-radius:6px; font-size:0.75rem; text-decoration:none; font-weight:700;">Manage</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

  </div>

  <!-- TAB 2: INVENTORY & PRODUCTS -->
  <div class="tab-pane" id="tab-inventory">
    <div class="panel-card">
      <div class="panel-header">
        <h3 class="panel-title">
          <span>🛍️</span> Store Catalog &amp; Active Listings
        </h3>
        <a href="product_add.php" class="btn-action-hero btn-action-gold" style="padding:0.5rem 1.2rem; font-size:0.82rem;">
          ➕ Add Product
        </a>
      </div>

      <?php if (empty($my_products)): ?>
        <div style="text-align:center; padding:3.5rem 1rem; color:#64748b;">
          <p style="font-size:1rem; margin-bottom:1rem;">You haven't listed any products yet.</p>
          <a href="product_add.php" class="btn-action-hero btn-action-gold">Upload Your First Product</a>
        </div>
      <?php else: ?>
        <div class="products-grid">
          <?php foreach ($my_products as $p): ?>
            <div class="product-card-mini">
              <img src="<?= !empty($p['thumbnail']) ? '/uploads/partners/' . htmlspecialchars($p['thumbnail']) : 'https://images.unsplash.com/photo-1523474253046-8cd2748b5fd2?auto=format&fit=crop&w=400&q=80' ?>" class="prod-thumb" alt="<?= htmlspecialchars($p['title']) ?>"/>
              <div class="prod-info">
                <div style="font-size:0.7rem; color:var(--gold); font-weight:700; text-transform:uppercase; margin-bottom:2px;">
                  <?= htmlspecialchars($p['category']) ?>
                </div>
                <div style="font-weight:800; color:#fff; font-size:0.92rem; margin-bottom:0.4rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                  <?= htmlspecialchars($p['title']) ?>
                </div>
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.8rem;">
                  <span style="color:var(--teal); font-weight:800; font-size:0.95rem;">
                    <?= $p['price'] <= 0 ? '🎁 FREE' : '🪙 ' . number_format($p['price'], 0) ?>
                  </span>
                  <span style="font-size:0.7rem; color:#94a3b8;">
                    Stock: <?= $p['stock'] === -1 ? '∞' : $p['stock'] ?>
                  </span>
                </div>
                <div style="display:flex; gap:0.4rem; margin-bottom:0.4rem;">
                  <a href="product_edit.php?id=<?= $p['id'] ?>" style="flex:1; text-align:center; background:rgba(255,255,255,0.08); color:#fff; padding:0.45rem; border-radius:8px; font-size:0.78rem; text-decoration:none; font-weight:700;">✏️ Edit</a>
                  <a href="/product_detail.php?id=<?= $p['id'] ?>" target="_blank" style="flex:1; text-align:center; background:rgba(252,185,0,0.15); color:var(--gold); padding:0.45rem; border-radius:8px; font-size:0.78rem; text-decoration:none; font-weight:700;">👁️ View</a>
                </div>
                <button onclick="openSocialModal(<?= $p['id'] ?>, '<?= urlencode("https://fastsite.best-travel.ltd/product_detail.php?id=" . $p['id']) ?>', '<?= htmlspecialchars(addslashes($p['title'])) ?>')" style="width:100%; text-align:center; background:linear-gradient(135deg, #1e3c72, #2a5298); color:#fff; padding:0.45rem; border-radius:8px; font-size:0.78rem; text-decoration:none; font-weight:700; border:none; cursor:pointer;">
                  📱 Share Social Card
                </button>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- TAB 3: CUSTOMER ORDERS -->
  <div class="tab-pane" id="tab-orders">
    <div class="panel-card">
      <div class="panel-header">
        <h3 class="panel-title">
          <span>📦</span> Customer Orders Fulfillment
        </h3>
        <a href="orders.php" class="btn-action-hero btn-action-gold" style="padding:0.5rem 1.2rem; font-size:0.82rem;">
          Go to Orders Board →
        </a>
      </div>
      <p style="color:#94a3b8; font-size:0.88rem; margin:0 0 1.5rem 0;">
        All orders placed with your shop are protected by the platform Escrow Vault. Confirm and upload delivery proof or tracking details to fulfill orders.
      </p>

      <?php if (empty($recent_orders)): ?>
        <div style="text-align:center; padding:3rem 1rem; color:#64748b;">
          No active orders at this moment.
        </div>
      <?php else: ?>
        <div style="display:flex; flex-direction:column; gap:1rem;">
          <?php foreach ($recent_orders as $ord): ?>
            <div style="background:rgba(10,12,20,0.6); border:1px solid rgba(255,255,255,0.08); border-radius:14px; padding:1.2rem; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
              <div>
                <div style="font-weight:800; color:#fff; font-size:1rem; margin-bottom:2px;">
                  Order #<?= $ord['id'] ?> &bull; <?= htmlspecialchars($ord['product_title'] ?? 'Product') ?>
                </div>
                <div style="font-size:0.8rem; color:#94a3b8;">
                  Buyer: <strong><?= htmlspecialchars($ord['customer_name'] ?? 'User') ?></strong> (<?= htmlspecialchars($ord['customer_phone'] ?? 'N/A') ?>) &bull; Amount: <strong style="color:var(--teal);">৳<?= number_format($ord['total_coins'] ?? 0, 2) ?></strong>
                </div>
              </div>
              <div>
                <a href="orders.php" style="background:linear-gradient(135deg, var(--gold), #ff9100); color:#000; font-weight:800; padding:0.5rem 1rem; border-radius:8px; text-decoration:none; font-size:0.82rem;">
                  Process Order →
                </a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- TAB 4: MARKETING & VIRAL GROWTH -->
  <div class="tab-pane" id="tab-marketing">
    <div class="panel-card">
      <div class="panel-header">
        <h3 class="panel-title">
          <span>🚀</span> Storefront Growth &amp; Viral Promo Tools
        </h3>
      </div>
      <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:1.5rem;">
        
        <div style="background:rgba(10,12,20,0.6); border:1px solid rgba(255,255,255,0.08); border-radius:16px; padding:1.5rem;">
          <h4 style="color:var(--gold); margin:0 0 0.5rem 0; font-size:1.1rem;">🎟️ Promo Code Campaign</h4>
          <p style="color:#94a3b8; font-size:0.84rem; line-height:1.4; margin:0 0 1rem 0;">
            Create discount promo codes for 5%, 10%, or 20% off to attract new customers and reward returning buyers.
          </p>
          <a href="javascript:void(0)" onclick="switchHubTab('tab-promos')" class="btn-action-hero btn-action-gold" style="width:100%; box-sizing:border-box; justify-content:center;">
            Manage Promo Codes
          </a>
        </div>

        <div style="background:rgba(10,12,20,0.6); border:1px solid rgba(255,255,255,0.08); border-radius:16px; padding:1.5rem;">
          <h4 style="color:var(--teal); margin:0 0 0.5rem 0; font-size:1.1rem;">🌟 Fast Site PRO Shop Boost</h4>
          <p style="color:#94a3b8; font-size:0.84rem; line-height:1.4; margin:0 0 1rem 0;">
            Upgrade to Fast Site PRO to get 0% platform fee, top-rank search listing boost, and an exclusive verified PRO badge.
          </p>
          <button style="background:linear-gradient(135deg, #00e676, #00b0ff); color:#000; font-weight:800; border:none; padding:0.75rem 1.4rem; border-radius:12px; font-size:0.88rem; width:100%; cursor:pointer;">
            Upgrade to PRO (500 Coins)
          </button>
        </div>

      </div>
    </div>
  </div>

  <!-- TAB 5: PROMO CODES & ANNOUNCEMENTS -->
  <div class="tab-pane" id="tab-promos">
    <!-- Announcement Bar Setup -->
    <div class="panel-card" style="margin-bottom:1.5rem;">
      <div class="panel-header">
        <h3 class="panel-title">📢 Storefront Announcement Bar</h3>
      </div>
      <form method="POST" style="display:flex; flex-direction:column; gap:1rem;">
        <input type="hidden" name="action" value="save_announcement">
        <div style="display:flex; gap:1rem; flex-wrap:wrap;">
          <div style="flex:1; min-width:250px;">
            <label style="font-size:0.8rem; color:#94a3b8; margin-bottom:5px; display:block;">Announcement Text</label>
            <input type="text" name="announcement_text" value="<?= htmlspecialchars($partner['announcement_text'] ?? '') ?>" placeholder="e.g. 🎉 10% Off with code SAVE10" style="width:100%; padding:0.8rem; background:rgba(0,0,0,0.3); border:1px solid rgba(255,255,255,0.1); border-radius:8px; color:#fff;" />
          </div>
          <div style="width:100px;">
            <label style="font-size:0.8rem; color:#94a3b8; margin-bottom:5px; display:block;">Bar Color</label>
            <input type="color" name="announcement_bg" value="<?= htmlspecialchars($partner['announcement_bg'] ?? '#fcb900') ?>" style="width:100%; height:42px; padding:0; border:none; border-radius:8px; cursor:pointer;" />
          </div>
        </div>
        <button type="submit" class="btn-action-hero btn-action-gold" style="align-self:flex-start;">💾 Save Announcement</button>
      </form>
    </div>

    <!-- Create Coupon Form -->
    <div class="panel-card" style="margin-bottom:1.5rem;">
      <div class="panel-header">
        <h3 class="panel-title">🎟️ Create Promo Code</h3>
      </div>
      <form method="POST" style="display:flex; flex-direction:column; gap:1rem;">
        <input type="hidden" name="action" value="create_coupon">
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:1rem;">
          <div>
            <label style="font-size:0.8rem; color:#94a3b8; margin-bottom:5px; display:block;">Coupon Code *</label>
            <input type="text" name="coupon_code" required placeholder="e.g. SUMMER26" style="width:100%; padding:0.8rem; background:rgba(0,0,0,0.3); border:1px solid rgba(255,255,255,0.1); border-radius:8px; color:#fff; text-transform:uppercase;" />
          </div>
          <div>
            <label style="font-size:0.8rem; color:#94a3b8; margin-bottom:5px; display:block;">Discount Type</label>
            <select name="discount_type" style="width:100%; padding:0.8rem; background:rgba(0,0,0,0.3); border:1px solid rgba(255,255,255,0.1); border-radius:8px; color:#fff;">
              <option value="percentage">Percentage (%)</option>
              <option value="fixed_bdt">Fixed Amount (৳)</option>
            </select>
          </div>
          <div>
            <label style="font-size:0.8rem; color:#94a3b8; margin-bottom:5px; display:block;">Discount Value *</label>
            <input type="number" name="discount_value" required step="0.01" placeholder="10" style="width:100%; padding:0.8rem; background:rgba(0,0,0,0.3); border:1px solid rgba(255,255,255,0.1); border-radius:8px; color:#fff;" />
          </div>
          <div>
            <label style="font-size:0.8rem; color:#94a3b8; margin-bottom:5px; display:block;">Min Order Amount (৳)</label>
            <input type="number" name="min_order_bdt" step="1" placeholder="0 for no minimum" style="width:100%; padding:0.8rem; background:rgba(0,0,0,0.3); border:1px solid rgba(255,255,255,0.1); border-radius:8px; color:#fff;" />
          </div>
          <div>
            <label style="font-size:0.8rem; color:#94a3b8; margin-bottom:5px; display:block;">Usage Limit</label>
            <input type="number" name="usage_limit" value="100" style="width:100%; padding:0.8rem; background:rgba(0,0,0,0.3); border:1px solid rgba(255,255,255,0.1); border-radius:8px; color:#fff;" />
          </div>
          <div>
            <label style="font-size:0.8rem; color:#94a3b8; margin-bottom:5px; display:block;">Expires At (Optional)</label>
            <input type="date" name="expires_at" style="width:100%; padding:0.8rem; background:rgba(0,0,0,0.3); border:1px solid rgba(255,255,255,0.1); border-radius:8px; color:#fff;" />
          </div>
        </div>
        <button type="submit" class="btn-action-hero btn-action-gold" style="align-self:flex-start;">➕ Create Promo Code</button>
      </form>
    </div>

    <!-- Active Coupons Table -->
    <div class="panel-card">
      <div class="panel-header">
        <h3 class="panel-title">Active Promo Codes</h3>
      </div>
      <?php if (empty($shop_coupons)): ?>
        <p style="color:#64748b; font-size:0.9rem;">No promo codes created yet.</p>
      <?php else: ?>
        <div style="overflow-x:auto;">
          <table class="hub-table">
            <thead>
              <tr>
                <th>Code</th>
                <th>Discount</th>
                <th>Min Order</th>
                <th>Uses</th>
                <th>Expires</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($shop_coupons as $c): ?>
                <tr>
                  <td style="font-weight:800; color:var(--teal);"><?= htmlspecialchars($c['coupon_code']) ?></td>
                  <td style="color:#fff; font-weight:700;">
                    <?= $c['discount_type'] === 'percentage' ? floatval($c['discount_value']) . '%' : '৳' . number_format($c['discount_value'], 2) ?>
                  </td>
                  <td style="color:#94a3b8;">৳<?= number_format($c['min_order_bdt'], 2) ?></td>
                  <td style="color:#94a3b8;"><?= $c['used_count'] ?> / <?= $c['usage_limit'] ?></td>
                  <td style="color:#94a3b8;"><?= $c['expires_at'] ? date('M d, Y', strtotime($c['expires_at'])) : 'Never' ?></td>
                  <td>
                    <form method="POST" onsubmit="return confirm('Delete this promo code?');">
                      <input type="hidden" name="action" value="delete_coupon">
                      <input type="hidden" name="coupon_id" value="<?= $c['id'] ?>">
                      <button type="submit" style="background:rgba(255,82,82,0.15); color:#ff5252; border:1px solid rgba(255,82,82,0.3); padding:4px 10px; border-radius:6px; cursor:pointer; font-size:0.75rem; font-weight:700;">🗑️ Delete</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- TAB 6: SHOP SETTINGS -->
  <div class="tab-pane" id="tab-settings">
    <div class="panel-card" style="border-color: rgba(255, 255, 255, 0.1);">
      <h3 style="color:#fff; font-size:1.15rem; font-weight:800; margin-bottom:1rem; display:flex; align-items:center; gap:8px;">
        ⚙️ Shop Profile & Media Settings
      </h3>
      <p style="color:var(--muted); font-size:0.85rem; margin-bottom:1.5rem;">
        Update your store's branding. Ensure images are high quality.
      </p>

      <form method="POST" enctype="multipart/form-data" style="display:flex; flex-direction:column; gap:1.2rem; max-width:600px;">
        <input type="hidden" name="action" value="save_shop_settings">
        
        <div>
          <label style="display:block; font-size:0.8rem; font-weight:700; color:var(--muted); margin-bottom:0.4rem;">Profile Picture / Logo (1:1 Square)</label>
          <input type="file" name="profile_pic" accept="image/*" style="background:#080911; color:#fff; border:1px solid rgba(255,255,255,0.15); padding:0.6rem; border-radius:8px; width:100%;">
          <?php if(!empty($partner['profile_pic']) || !empty($partner['logo_url'])): ?>
            <div style="margin-top:0.5rem;">
              <img src="<?= resolveShopMedia($partner, 'avatar') ?>" style="width:60px; height:60px; border-radius:8px; object-fit:cover;">
            </div>
          <?php endif; ?>
        </div>

        <div>
          <label style="display:block; font-size:0.8rem; font-weight:700; color:var(--muted); margin-bottom:0.4rem;">Cover Photo / Banner (16:9 Landscape)</label>
          <input type="file" name="cover_pic" accept="image/*" style="background:#080911; color:#fff; border:1px solid rgba(255,255,255,0.15); padding:0.6rem; border-radius:8px; width:100%;">
          <?php if(!empty($partner['cover_pic']) || !empty($partner['banner_url'])): ?>
            <div style="margin-top:0.5rem;">
              <img src="<?= resolveShopMedia($partner, 'cover') ?>" style="height:60px; border-radius:8px; object-fit:cover;">
            </div>
          <?php endif; ?>
        </div>

        <button type="submit" style="background:linear-gradient(135deg, var(--gold), #ff9100); color:#000; font-weight:800; border:none; padding:0.8rem 1.4rem; border-radius:10px; cursor:pointer; font-size:0.95rem; margin-top:0.5rem;">
          💾 Save Settings
        </button>
      </form>
    </div>
  </div>

</div>

<!-- Phase 71: Social Share Modal -->
<div id="socialCardModal" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.85); z-index:9999; justify-content:center; align-items:center; padding:1rem; box-sizing:border-box;">
    <div style="background:#14141f; width:100%; max-width:400px; border-radius:20px; border:1px solid rgba(255,255,255,0.1); display:flex; flex-direction:column; overflow:hidden;">
        <div style="padding:1rem; border-bottom:1px solid rgba(255,255,255,0.05); display:flex; justify-content:space-between; align-items:center;">
            <h3 style="margin:0; font-size:1.1rem;">📱 Social Story Card</h3>
            <button onclick="document.getElementById('socialCardModal').style.display='none'" style="background:none; border:none; color:#fff; font-size:1.5rem; cursor:pointer;">&times;</button>
        </div>
        <div style="padding:1rem; text-align:center; background:#080911;">
            <div id="cardLoader" style="color:#fcb900; font-size:0.9rem;">⏳ Generating Card...</div>
            <img id="cardPreviewImg" src="" style="width:100%; max-width:300px; border-radius:12px; display:none; box-shadow:0 10px 20px rgba(0,0,0,0.5); margin:0 auto;" />
            
            <!-- Hidden iframe for generating the card via HTML2Canvas -->
            <iframe id="socialCardFrame" style="position:fixed; top:-9999px; left:-9999px; width:1080px; height:1080px; border:none;" sandbox="allow-scripts allow-same-origin"></iframe>
        </div>
        <div style="padding:1rem; display:flex; flex-direction:column; gap:0.6rem;">
            <button id="btnDownloadCard" style="background:linear-gradient(135deg, #fcb900, #ff9100); color:#000; border:none; padding:0.8rem; border-radius:10px; font-weight:800; font-size:0.9rem; cursor:pointer; display:none;">
                ⬇️ Download Story Card
            </button>
            <div style="display:flex; gap:0.4rem; justify-content:space-between;">
                <a id="btnShareWa" href="#" target="_blank" style="flex:1; background:#25D366; color:#fff; padding:0.6rem; text-align:center; border-radius:8px; text-decoration:none; font-weight:700; font-size:0.8rem;">💬 WhatsApp</a>
                <a id="btnShareFb" href="#" target="_blank" style="flex:1; background:#1877F2; color:#fff; padding:0.6rem; text-align:center; border-radius:8px; text-decoration:none; font-weight:700; font-size:0.8rem;">📘 Facebook</a>
            </div>
            <button onclick="copySocialLink()" style="background:rgba(255,255,255,0.1); color:#fff; border:none; padding:0.6rem; border-radius:8px; font-weight:700; font-size:0.8rem; cursor:pointer;">
                📋 Copy Link
            </button>
            <input type="hidden" id="hiddenSocialLink" />
        </div>
    </div>
</div>

<script>
let currentDataUrl = '';
function openSocialModal(productId, encodedUrl, title) {
    document.getElementById('socialCardModal').style.display = 'flex';
    document.getElementById('cardLoader').style.display = 'block';
    document.getElementById('cardPreviewImg').style.display = 'none';
    document.getElementById('btnDownloadCard').style.display = 'none';
    
    // Setup Links
    let decodedUrl = decodeURIComponent(encodedUrl);
    document.getElementById('hiddenSocialLink').value = decodedUrl;
    document.getElementById('btnShareWa').href = `https://wa.me/?text=Check out ${encodeURIComponent(title)} on Fast Site ${encodedUrl}`;
    document.getElementById('btnShareFb').href = `https://www.facebook.com/sharer/sharer.php?u=${encodedUrl}`;
    
    // Load generator
    document.getElementById('socialCardFrame').src = `/api/generate_social_card.php?product_id=${productId}`;
}

window.addEventListener('message', function(e) {
    if(e.data && e.data.type === 'socialCardReady') {
        currentDataUrl = e.data.dataUrl;
        document.getElementById('cardLoader').style.display = 'none';
        let img = document.getElementById('cardPreviewImg');
        img.src = currentDataUrl;
        img.style.display = 'block';
        
        let btnDl = document.getElementById('btnDownloadCard');
        btnDl.style.display = 'block';
        btnDl.onclick = function() {
            let a = document.createElement('a');
            a.href = currentDataUrl;
            a.download = `FastSite_Card_${e.data.productId}.png`;
            a.click();
        };
    }
});

function copySocialLink() {
    let input = document.getElementById('hiddenSocialLink');
    if(navigator.clipboard) {
        navigator.clipboard.writeText(input.value);
        alert('Link copied!');
    } else {
        input.type = 'text';
        input.select();
        document.execCommand('copy');
        input.type = 'hidden';
        alert('Link copied!');
    }
}

function switchHubTab(tabId) {
    document.querySelectorAll('.hub-tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('active'));

    const activeBtn = Array.from(document.querySelectorAll('.hub-tab-btn')).find(b => b.getAttribute('onclick').includes(tabId));
    if (activeBtn) activeBtn.classList.add('active');

    const pane = document.getElementById(tabId);
    if (pane) pane.classList.add('active');
}

function copyShopLink() {
    const input = document.getElementById('shopLinkInput');
    input.select();
    navigator.clipboard.writeText(input.value);
    const btn = document.getElementById('copyShopBtn');
    const orig = btn.innerHTML;
    btn.innerHTML = '✅ Copied!';
    btn.style.background = '#00e676';
    btn.style.color = '#000';
    setTimeout(() => {
        btn.innerHTML = orig;
        btn.style.background = 'linear-gradient(135deg, var(--gold), #ff9100)';
        btn.style.color = '#000';
    }, 2500);
}
</script>
</body>
</html>
