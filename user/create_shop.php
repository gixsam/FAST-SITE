<?php
// =========================================================================
// user/create_shop.php – Partner Shop Registration Portal
// =========================================================================
session_start();
if (!isset($_SESSION['user_id'])) { 
    header('Location: /user/login.php'); 
    exit; 
}
require_once __DIR__ . '/../config.php';

$userId = (int)$_SESSION['user_id'];
$u = $pdo->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
$u->execute([':id' => $userId]);
$user = $u->fetch();

if (!$user) {
    header('Location: /user/login.php');
    exit;
}

// Check if user already has a shop
$has_shop = false;
try {
    $chk = $pdo->prepare("SELECT id, status FROM partners WHERE user_id = :uid OR phone = :phone LIMIT 1");
    $chk->execute([':uid' => $userId, ':phone' => $user['phone']]);
    $has_shop = $chk->fetch();
} catch (Exception $e) {
    try {
        $chk = $pdo->prepare("SELECT id, status FROM partners WHERE phone = :phone LIMIT 1");
        $chk->execute([':phone' => $user['phone']]);
        $has_shop = $chk->fetch();
    } catch (Exception $ex) {}
}

$user_extra = false;
try {
    $extra_shop = $pdo->prepare("SELECT allow_extra_shop FROM users WHERE id = :uid");
    $extra_shop->execute([':uid' => $userId]);
    $user_extra = (bool)$extra_shop->fetchColumn();
} catch (Exception $e) {}

if ($has_shop && !$user_extra) {
    header('Location: /partner/dashboard.php');
    exit;
}

$err = $msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $business_name  = trim($_POST['business_name'] ?? '');
    $owner_name     = trim($_POST['owner_name'] ?? $user['name']);
    $email          = trim($_POST['email'] ?? $user['email']);
    $phone          = trim($_POST['phone'] ?? $user['phone']);
    $description    = trim($_POST['description'] ?? '');
    $payout_method  = trim($_POST['payout_method'] ?? 'bkash');
    $payout_account = trim($_POST['payout_account'] ?? '');

    if (!isset($_POST['accept_terms'])) {
        $err = 'You must accept the Terms and Conditions and Privacy Policy to become a partner.';
    } elseif (!$business_name || !$owner_name || !$phone) {
        $err = 'Please fill in all required fields marked with *';
    } else {
        // Process File Uploads
        $upload_dir = '../uploads/partners/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $nid_file = null;
        if (!empty($_POST['existing_nid'])) {
            $nid_file = $_POST['existing_nid'];
        } elseif (isset($_FILES['nid']) && $_FILES['nid']['error'] === UPLOAD_ERR_OK) {
            $res = handleSecureUpload($_FILES['nid'], $upload_dir, ['jpg','jpeg','png','webp','pdf'], 'nid');
            if ($res) $nid_file = $res;
        }

        $trade_license_file = null;
        if (isset($_FILES['trade_license']) && $_FILES['trade_license']['error'] === UPLOAD_ERR_OK) {
            $res = handleSecureUpload($_FILES['trade_license'], $upload_dir, ['jpg','jpeg','png','webp','pdf'], 'license');
            if ($res) $trade_license_file = $res;
        }

        $profile_pic_file = null;
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
            $res = handleSecureUpload($_FILES['profile_pic'], $upload_dir, ['jpg','jpeg','png','webp'], 'profile');
            if ($res) $profile_pic_file = $res;
        }

        $cover_pic_file = null;
        if (isset($_FILES['cover_pic']) && $_FILES['cover_pic']['error'] === UPLOAD_ERR_OK) {
            $res = handleSecureUpload($_FILES['cover_pic'], $upload_dir, ['jpg','jpeg','png','webp'], 'cover');
            if ($res) $cover_pic_file = $res;
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO partners 
                (user_id, business_name, owner_name, email, phone, password_hash, nid, trade_license, profile_pic, cover_pic, description, payout_method, payout_account, status) 
                VALUES (:uid, :bn, :on, :e, :p, :h, :nid, :tl, :pp, :cp, :d, :pm, :pa, 'pending')");
            
            $stmt->execute([
                ':uid' => $userId,
                ':bn'  => $business_name,
                ':on'  => $owner_name,
                ':e'   => $email,
                ':p'   => $phone,
                ':h'   => $user['password_hash'],
                ':nid' => $nid_file,
                ':tl'  => $trade_license_file,
                ':pp'  => $profile_pic_file,
                ':cp'  => $cover_pic_file,
                ':d'   => $description ?: null,
                ':pm'  => $payout_method,
                ':pa'  => $payout_account ?: null
            ]);

            $new_partner_id = (int)$pdo->lastInsertId();
            $_SESSION['partner_id'] = $new_partner_id;

            $msg = 'Shop application submitted successfully! Please wait for admin approval.';
        } catch (PDOException $e) {
            $err = 'Database Error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Open a Free Shop — Fast Site</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Oswald:wght@600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="/assets/css/user.css">
  <style>
    .auth-box {
      max-width: 650px;
      margin: 2rem auto;
      background: rgba(20, 20, 31, 0.95);
      border: 1px solid rgba(33, 150, 243, 0.3);
      border-radius: 20px;
      padding: 2.5rem;
      box-shadow: 0 15px 50px rgba(0,0,0,0.6);
    }
    .custom-input {
      width: 100%;
      background: #0b0b12 !important;
      border: 1px solid rgba(255, 255, 255, 0.12) !important;
      color: #fff !important;
      padding: 0.75rem 1rem !important;
      border-radius: 10px !important;
      font-size: 0.9rem !important;
      outline: none !important;
    }
    .custom-input:focus {
      border-color: var(--brand) !important;
      box-shadow: 0 0 10px rgba(33, 150, 243, 0.3) !important;
    }
  </style>
</head>
<body class="dashboard-mode">

<?php include __DIR__ . '/../includes/user_sidebar.php'; ?>

<div class="dashboard-container" style="padding-top: 85px; max-width: 700px; margin: 0 auto; padding-bottom: 5rem;">
  
  <div style="margin-bottom: 1.5rem;">
    <a href="dashboard.php" class="btn" style="background:rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.15); color:#fff; width:auto; padding:0.5rem 1.2rem; font-size:0.85rem; text-decoration:none;">
      ← Back to Dashboard
    </a>
  </div>

  <div class="auth-box">
    <div style="text-align:center; margin-bottom:2rem;">
      <div style="font-size:3rem; margin-bottom:0.5rem;">🏪</div>
      <h2 style="color:#fff; font-size:1.6rem; font-weight:800; margin:0 0 0.5rem 0;">Open Your Free Shop</h2>
      <p style="color:var(--muted); font-size:0.9rem; margin:0;">
        Start selling your products and digital services to thousands of buyers on Fast Site.
      </p>
    </div>
    
    <?php if($err): ?>
      <div style="background:rgba(255,82,82,0.12); color:#ff5252; padding:1rem 1.5rem; border-radius:12px; margin-bottom:1.5rem; border:1px solid rgba(255,82,82,0.3); font-weight:700;">
        ⚠️ <?= htmlspecialchars($err) ?>
      </div>
    <?php endif; ?>

    <?php if($msg): ?>
      <div style="background:rgba(0, 230, 118, 0.12); color:#00e676; padding:1.5rem; border-radius:12px; margin-bottom:1.5rem; border:1px solid rgba(0, 230, 118, 0.3); text-align:center;">
        <div style="font-size:2.5rem; margin-bottom:0.5rem;">🎉</div>
        <h3 style="color:#00e676; margin:0 0 0.5rem 0;">Application Submitted!</h3>
        <p style="color:#fff; font-size:0.9rem; margin-bottom:1.2rem;"><?= htmlspecialchars($msg) ?></p>
        <a href="/partner/dashboard.php" class="btn" style="background:linear-gradient(135deg, var(--brand), #007bb5); color:#fff; font-weight:800; padding:0.8rem 2rem; border-radius:30px; text-decoration:none; display:inline-block;">
          Go to Partner Dashboard ➔
        </a>
      </div>
    <?php else: ?>
    
    <form method="POST" enctype="multipart/form-data">
      <div class="field" style="margin-bottom:1.2rem;">
        <label style="display:block; font-size:0.75rem; font-weight:700; color:var(--muted); text-transform:uppercase; margin-bottom:0.4rem;">Business Name *</label>
        <input type="text" name="business_name" class="custom-input" placeholder="e.g. Sayam Tech Shop" required autofocus/>
      </div>
      
      <div class="field" style="margin-bottom:1.2rem;">
        <label style="display:block; font-size:0.75rem; font-weight:700; color:var(--muted); text-transform:uppercase; margin-bottom:0.4rem;">Owner Full Name *</label>
        <input type="text" name="owner_name" class="custom-input" value="<?= htmlspecialchars($user['name']) ?>" required readonly style="opacity: 0.7; cursor: not-allowed;"/>
      </div>

      <div class="field" style="margin-bottom:1.2rem;">
        <label style="display:block; font-size:0.75rem; font-weight:700; color:var(--muted); text-transform:uppercase; margin-bottom:0.4rem;">Email Address *</label>
        <input type="email" name="email" class="custom-input" value="<?= htmlspecialchars($user['email']) ?>" required readonly style="opacity: 0.7; cursor: not-allowed;"/>
      </div>

      <div class="field" style="margin-bottom:1.2rem;">
        <label style="display:block; font-size:0.75rem; font-weight:700; color:var(--muted); text-transform:uppercase; margin-bottom:0.4rem;">Phone Number *</label>
        <input type="tel" name="phone" class="custom-input" value="<?= htmlspecialchars($user['phone']) ?>" required readonly style="opacity: 0.7; cursor: not-allowed;"/>
      </div>

      <div class="field" style="margin-bottom:1.2rem;">
        <label style="display:block; font-size:0.75rem; font-weight:700; color:var(--muted); text-transform:uppercase; margin-bottom:0.4rem;">Business Description</label>
        <textarea name="description" class="custom-input" placeholder="Briefly describe what your shop sells..." rows="3"></textarea>
      </div>

      <div class="field" style="margin-bottom:1.2rem;">
        <label style="display:block; font-size:0.75rem; font-weight:700; color:var(--muted); text-transform:uppercase; margin-bottom:0.4rem;">NID Copy (JPEG/PNG/PDF)</label>
        <?php if (!empty($user['nid'])): ?>
            <div style="margin-bottom: 0.5rem; background: rgba(0, 230, 118, 0.05); padding: 10px; border-radius: 8px; border: 1px solid rgba(0, 230, 118, 0.2);">
                <span style="font-size:0.8rem; color:var(--green); font-weight:700;">✅ Verified NID on file</span>
            </div>
            <input type="hidden" name="existing_nid" value="<?= htmlspecialchars($user['nid']) ?>" />
        <?php else: ?>
            <input type="file" name="nid" accept=".jpg,.jpeg,.png,.webp,.pdf" style="color:var(--muted); font-size:0.85rem;"/>
        <?php endif; ?>
      </div>

      <div class="field" style="margin-bottom:1.2rem;">
        <label style="display:block; font-size:0.75rem; font-weight:700; color:var(--muted); text-transform:uppercase; margin-bottom:0.4rem;">Trade License Copy (Optional)</label>
        <input type="file" name="trade_license" accept=".jpg,.jpeg,.png,.webp,.pdf" style="color:var(--muted); font-size:0.85rem;"/>
      </div>

      <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1.2rem;">
        <div class="field">
          <label style="display:block; font-size:0.75rem; font-weight:700; color:var(--muted); text-transform:uppercase; margin-bottom:0.4rem;">Payout Method</label>
          <select name="payout_method" class="custom-input" style="cursor:pointer;">
            <option value="bkash">bKash</option>
            <option value="nagad">Nagad</option>
          </select>
        </div>

        <div class="field">
          <label style="display:block; font-size:0.75rem; font-weight:700; color:var(--muted); text-transform:uppercase; margin-bottom:0.4rem;">Payout Wallet Number</label>
          <input type="tel" name="payout_account" class="custom-input" placeholder="e.g. 017XXXXXXXX"/>
        </div>
      </div>

      <div class="field" style="display: flex; flex-direction: row; align-items: center; gap: 10px; margin-top: 1.5rem; margin-bottom: 1.5rem; background: rgba(255,255,255,0.03); padding: 1rem; border-radius: 10px; border: 1px solid rgba(255,255,255,0.08);">
        <input type="checkbox" name="accept_terms" id="accept_terms" required style="width: auto; margin: 0; cursor: pointer; transform: scale(1.2);">
        <label for="accept_terms" style="margin: 0; font-size: 0.85rem; color: #fff; cursor: pointer;">
          I accept the <a href="/policy.php" target="_blank" style="color: var(--brand); text-decoration: underline;">Terms & Conditions</a> and Seller Rules.
        </label>
      </div>

      <button type="submit" class="btn" style="background:linear-gradient(135deg, var(--brand), #007bb5); color:#fff; font-weight:800; padding:1rem; border-radius:50px; font-size:1rem; cursor:pointer;">
        🚀 Register My Shop
      </button>
    </form>
    <?php endif; ?>
  </div>
</div>

</body>
</html>
