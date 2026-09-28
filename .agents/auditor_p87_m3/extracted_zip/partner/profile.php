<?php
// =========================================================================
// partner/profile.php — Ultra-Premium Shop Profile & Brand Studio
// =========================================================================
ob_start();
require_once 'nav.php';
$_nav_html = ob_get_clean();

if (!isset($_SESSION['partner_id'])) {
    header('Location: /user/login.php');
    exit;
}

$partner_id = (int)$_SESSION['partner_id'];
$err = $msg = '';

// Fetch latest partner details
$stmt = $pdo->prepare("SELECT * FROM partners WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $partner_id]);
$partner = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$partner) {
    echo "<div class='main-content'><div class='box' style='text-align:center; padding:3rem 1rem;'><h2>⚠️ Shop Not Found</h2><p style='color:#aaa;'>Unable to locate shop record.</p><br><a href='dashboard.php' class='btn' style='background:var(--brand,#fcb900); color:#000; padding:0.6rem 1.5rem; font-weight:700; text-decoration:none; border-radius:8px;'>Back to Dashboard</a></div></div>";
    echo "</body></html>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Update Shop Details
    if (isset($_POST['update_profile'])) {
        $business_name  = trim($_POST['business_name'] ?? '');
        $owner_name     = trim($_POST['owner_name'] ?? '');
        $email          = trim($_POST['email'] ?? '');
        $phone          = trim($_POST['phone'] ?? '');
        $description    = trim($_POST['description'] ?? '');
        $payout_method  = trim($_POST['payout_method'] ?? 'bkash');
        $payout_account = trim($_POST['payout_account'] ?? '');
        $district       = trim($_POST['district'] ?? '');

        if (!$business_name || !$owner_name || !$email || !$phone) {
            $err = 'Please fill out all mandatory fields marked with an asterisk (*).';
        } else {
            // Check unique phone/email
            $chk = $pdo->prepare("SELECT id FROM partners WHERE (phone = :p OR email = :e) AND id != :id LIMIT 1");
            $chk->execute([':p' => $phone, ':e' => $email, ':id' => $partner_id]);
            if ($chk->fetch()) {
                $err = 'Phone number or email is already registered by another shop.';
            } else {
                $upload_dir = '../uploads/partners/';
                if (!is_dir($upload_dir)) {
                    @mkdir($upload_dir, 0777, true);
                }

                $profile_pic_file = $partner['profile_pic'];
                if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
                    $res = handleSecureUpload($_FILES['profile_pic'], $upload_dir, ['jpg','jpeg','png','webp'], 'profile');
                    if ($res) {
                        if ($partner['profile_pic'] && file_exists($upload_dir . $partner['profile_pic'])) {
                            @unlink($upload_dir . $partner['profile_pic']);
                        }
                        $profile_pic_file = $res;
                    }
                }

                $cover_pic_file = $partner['cover_pic'];
                if (isset($_FILES['cover_pic']) && $_FILES['cover_pic']['error'] === UPLOAD_ERR_OK) {
                    $res = handleSecureUpload($_FILES['cover_pic'], $upload_dir, ['jpg','jpeg','png','webp'], 'cover');
                    if ($res) {
                        if ($partner['cover_pic'] && file_exists($upload_dir . $partner['cover_pic'])) {
                            @unlink($upload_dir . $partner['cover_pic']);
                        }
                        $cover_pic_file = $res;
                    }
                }

                try {
                    // Self-healing columns check
                    try { @$pdo->exec("ALTER TABLE `partners` ADD COLUMN `district` VARCHAR(100) DEFAULT NULL"); } catch (Exception $e) {}
                    try { @$pdo->exec("ALTER TABLE `partners` ADD COLUMN `payout_method` VARCHAR(50) DEFAULT 'bkash'"); } catch (Exception $e) {}
                    try { @$pdo->exec("ALTER TABLE `partners` ADD COLUMN `payout_account` VARCHAR(100) DEFAULT NULL"); } catch (Exception $e) {}

                    $stmt_up = $pdo->prepare("UPDATE partners 
                        SET business_name = :bn, owner_name = :on, email = :e, phone = :p, 
                            profile_pic = :pp, cover_pic = :cp, description = :d, 
                            payout_method = :pm, payout_account = :pa, district = :dist 
                            WHERE id = :id");
                    $stmt_up->execute([
                        ':bn'  => $business_name,
                        ':on'  => $owner_name,
                        ':e'   => $email,
                        ':p'   => $phone,
                        ':pp'  => $profile_pic_file,
                        ':cp'  => $cover_pic_file,
                        ':d'   => $description ?: null,
                        ':pm'  => $payout_method,
                        ':pa'  => $payout_account ?: null,
                        ':dist'=> $district ?: null,
                        ':id'  => $partner_id
                    ]);

                    $_SESSION['partner_name'] = $business_name;
                    $msg = 'Shop Profile & Branding updated successfully!';
                    
                    // Reload data
                    $stmt->execute([':id' => $partner_id]);
                    $partner = $stmt->fetch(PDO::FETCH_ASSOC);
                } catch (PDOException $e) {
                    $err = 'Database Error: ' . $e->getMessage();
                }
            }
        }
    }

    // 2. Change Password
    if (isset($_POST['change_password'])) {
        $old_pass = $_POST['old_password'] ?? '';
        $new_pass = $_POST['new_password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        if (!$old_pass || !$new_pass || !$confirm) {
            $err = 'Please fill out all password fields.';
        } elseif ($new_pass !== $confirm) {
            $err = 'New passwords do not match.';
        } elseif (strlen($new_pass) < 6) {
            $err = 'New password must be at least 6 characters long.';
        } elseif (!password_verify($old_pass, $partner['password_hash'])) {
            $err = 'Current password is incorrect.';
        } else {
            $new_hash = password_hash($new_pass, PASSWORD_BCRYPT);
            try {
                $stmt_pw = $pdo->prepare("UPDATE partners SET password_hash = :p WHERE id = :id");
                $stmt_pw->execute([':p' => $new_hash, ':id' => $partner_id]);
                $msg = 'Password changed successfully!';
            } catch (PDOException $e) {
                $err = 'Database Error: ' . $e->getMessage();
            }
        }
    }
}

$districts = [
    "Dhaka", "Chattogram", "Rajshahi", "Khulna", "Barishal", "Sylhet", "Rangpur", "Mymensingh",
    "Bagerhat", "Bandarban", "Barguna", "Bhola", "Bogura", "Brahmanbaria", "Chandpur",
    "Chapai Nawabganj", "Chuadanga", "Cox's Bazar", "Cumilla", "Dinajpur", "Faridpur",
    "Feni", "Gaibandha", "Gazipur", "Gopalganj", "Habiganj", "Jamalpur", "Jashore",
    "Jhalakathi", "Jhenaidah", "Joypurhat", "Khagrachhari", "Kishoreganj", "Kurigram",
    "Kushtia", "Lakshmipur", "Lalmonirhat", "Madaripur", "Magura", "Manikganj",
    "Meherpur", "Moulvibazar", "Munshiganj", "Naogaon", "Narail", "Narayanganj",
    "Narsingdi", "Natore", "Netrokona", "Nilphamari", "Noakhali", "Pabna", "Panchagarh",
    "Patuakhali", "Pirojpur", "Rajbari", "Rangamati", "Satkhira", "Shariatpur",
    "Sherpur", "Sirajganj", "Sunamganj", "Tangail", "Thakurgaon"
];

$coverImgUrl = !empty($partner['cover_pic']) ? '/uploads/partners/' . htmlspecialchars($partner['cover_pic']) : 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?auto=format&fit=crop&w=1200&q=80';
$profileImgUrl = !empty($partner['profile_pic']) ? '/uploads/partners/' . htmlspecialchars($partner['profile_pic']) : '/assets/images/logo.png';
$shopRegNum = !empty($partner['registration_number']) ? $partner['registration_number'] : (!empty($partner['is_official']) ? 'FS-OFFICIAL-1' : ('FS-SHOP-' . str_pad($partner['id'], 5, '0', STR_PAD_LEFT)));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Shop Profile Studio &mdash; Fast Site Partner Hub</title>
  <link rel="stylesheet" href="../assets/css/admin.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Oswald:wght@500;600;700&display=swap" rel="stylesheet">
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

    .studio-container {
      max-width: 1200px;
      margin: 2rem auto 4rem auto;
      padding: 0 1rem;
    }

    /* Executive Hero Brand Studio */
    .brand-hero {
      position: relative;
      background: var(--dark-card);
      border: 1px solid var(--border-glass);
      border-radius: 24px;
      overflow: hidden;
      margin-bottom: 2rem;
      backdrop-filter: blur(16px);
      box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
    }

    .cover-banner-wrap {
      width: 100%;
      height: 220px;
      position: relative;
      background: #0d101d;
    }

    .cover-banner-img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      opacity: 0.9;
    }

    .cover-banner-overlay {
      position: absolute;
      inset: 0;
      background: linear-gradient(180deg, rgba(0,0,0,0.1) 0%, rgba(13,16,29,0.95) 100%);
    }

    .brand-info-bar {
      padding: 0 2rem 1.8rem 2rem;
      display: flex;
      justify-content: space-between;
      align-items: flex-end;
      flex-wrap: wrap;
      gap: 1.5rem;
      position: relative;
      margin-top: -60px;
    }

    .avatar-details-group {
      display: flex;
      align-items: flex-end;
      gap: 1.4rem;
      flex-wrap: wrap;
    }

    .avatar-wrap {
      position: relative;
      width: 110px;
      height: 110px;
      border-radius: 24px;
      background: #080911;
      border: 3px solid var(--gold);
      box-shadow: 0 0 30px rgba(252, 185, 0, 0.35);
      overflow: hidden;
      flex-shrink: 0;
    }

    .avatar-img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .shop-meta-titles h2 {
      margin: 0 0 0.3rem 0;
      font-family: 'Oswald', sans-serif;
      font-size: 2rem;
      color: #fff;
      text-transform: uppercase;
      letter-spacing: 0.04em;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .badge-chip {
      font-size: 0.72rem;
      font-weight: 800;
      padding: 3px 10px;
      border-radius: 50px;
      text-transform: uppercase;
      display: inline-flex;
      align-items: center;
      gap: 5px;
    }

    .badge-official {
      background: rgba(252, 185, 0, 0.15);
      color: var(--gold);
      border: 1px solid rgba(252, 185, 0, 0.35);
    }

    .badge-reg {
      background: rgba(0, 230, 118, 0.12);
      color: var(--teal);
      border: 1px solid rgba(0, 230, 118, 0.3);
      font-family: monospace;
    }

    /* 2-Column Master Layout */
    .studio-grid {
      display: grid;
      grid-template-columns: 1fr 380px;
      gap: 2rem;
    }

    @media (max-width: 950px) {
      .studio-grid { grid-template-columns: 1fr; }
    }

    .glass-card {
      background: var(--dark-card);
      border: 1px solid var(--border-glass);
      border-radius: 20px;
      padding: 2rem;
      backdrop-filter: blur(16px);
      box-shadow: 0 15px 40px rgba(0,0,0,0.4);
      margin-bottom: 2rem;
    }

    .card-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.5rem;
      border-bottom: 1px solid rgba(255, 255, 255, 0.06);
      padding-bottom: 0.8rem;
    }

    .card-title {
      font-family: 'Oswald', sans-serif;
      font-size: 1.25rem;
      color: var(--gold);
      text-transform: uppercase;
      letter-spacing: 0.04em;
      margin: 0;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .field {
      margin-bottom: 1.3rem;
      position: relative;
    }

    .field label {
      display: block;
      font-size: 0.76rem;
      font-weight: 700;
      color: #94a3b8;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      margin-bottom: 0.45rem;
    }

    .field input,
    .field select,
    .field textarea {
      width: 100%;
      background: rgba(10, 12, 20, 0.8);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 12px;
      padding: 0.8rem 1.1rem;
      color: #fff;
      font-size: 0.92rem;
      font-family: inherit;
      box-sizing: border-box;
      transition: all 0.2s ease;
    }

    .field input:focus,
    .field select:focus,
    .field textarea:focus {
      outline: none;
      border-color: var(--gold);
      box-shadow: 0 0 15px rgba(252, 185, 0, 0.25);
      background: rgba(14, 16, 26, 0.95);
    }

    .row-flex {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1.2rem;
    }

    @media (max-width: 600px) {
      .row-flex { grid-template-columns: 1fr; }
    }

    .btn-gold {
      background: linear-gradient(135deg, var(--gold) 0%, #ff9100 100%);
      color: #000;
      font-weight: 900;
      font-family: 'Oswald', sans-serif;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      border: none;
      border-radius: 50px;
      padding: 0.95rem 1.8rem;
      width: 100%;
      font-size: 1rem;
      cursor: pointer;
      min-height: 48px;
      transition: all 0.25s;
      box-shadow: 0 8px 25px rgba(252, 185, 0, 0.35);
    }

    .btn-gold:hover {
      transform: translateY(-2px);
      box-shadow: 0 12px 30px rgba(252, 185, 0, 0.5);
    }

    .btn-live-shop {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: rgba(255, 255, 255, 0.08);
      border: 1px solid rgba(255, 255, 255, 0.15);
      color: #fff;
      padding: 0.65rem 1.2rem;
      border-radius: 12px;
      text-decoration: none;
      font-weight: 700;
      font-size: 0.85rem;
      transition: 0.2s;
    }

    .btn-live-shop:hover {
      background: rgba(252, 185, 0, 0.15);
      color: var(--gold);
      border-color: rgba(252, 185, 0, 0.3);
    }

    /* Media Upload Preview Boxes */
    .media-uploader-box {
      border: 2px dashed rgba(252, 185, 0, 0.3);
      border-radius: 14px;
      padding: 1.2rem;
      background: rgba(252, 185, 0, 0.02);
      text-align: center;
      cursor: pointer;
      transition: all 0.2s;
    }

    .media-uploader-box:hover {
      background: rgba(252, 185, 0, 0.06);
      border-color: var(--gold);
    }

    .pass-toggle-wrap {
      position: relative;
    }

    .pass-toggle-btn {
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      color: #94a3b8;
      cursor: pointer;
      font-size: 1.1rem;
    }

    .err {
      background: rgba(255, 82, 82, 0.12);
      color: #ff5252;
      border: 1px solid rgba(255, 82, 82, 0.3);
      padding: 0.9rem 1.2rem;
      border-radius: 12px;
      font-size: 0.88rem;
      margin-bottom: 1.5rem;
      font-weight: 600;
    }

    .success {
      background: rgba(0, 230, 118, 0.12);
      color: #00e676;
      border: 1px solid rgba(0, 230, 118, 0.3);
      padding: 0.9rem 1.2rem;
      border-radius: 12px;
      font-size: 0.88rem;
      margin-bottom: 1.5rem;
      font-weight: 600;
    }
  </style>
</head>
<body>
<?php echo $_nav_html; ?>

<div class="studio-container">

  <?php if($err): ?><div class="err">⚠️ <?= htmlspecialchars($err) ?></div><?php endif; ?>
  <?php if($msg): ?><div class="success">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>

  <!-- Executive Brand Studio Hero Banner -->
  <div class="brand-hero">
    <div class="cover-banner-wrap">
      <img src="<?= $coverImgUrl ?>" id="hero-cover-preview" class="cover-banner-img" alt="Cover Banner" onerror="this.src='https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?auto=format&fit=crop&w=1200&q=80';"/>
      <div class="cover-banner-overlay"></div>
    </div>

    <div class="brand-info-bar">
      <div class="avatar-details-group">
        <div class="avatar-wrap">
          <img src="<?= $profileImgUrl ?>" id="hero-logo-preview" class="avatar-img" alt="Shop Logo" onerror="this.src='/assets/images/logo.png';"/>
        </div>
        <div class="shop-meta-titles">
          <h2>
            <span><?= htmlspecialchars($partner['business_name']) ?></span>
          </h2>
          <div style="display:flex; gap:0.5rem; flex-wrap:wrap; align-items:center;">
            <span class="badge-chip badge-official">
              ⭐ Official Partner (Level <?= intval($partner['seller_level'] ?? 1) ?>)
            </span>
            <span class="badge-chip badge-reg">
              🆔 <?= htmlspecialchars($shopRegNum) ?>
            </span>
            <span style="font-size:0.8rem; color:#94a3b8;">
              👤 Owner: <strong style="color:#fff;"><?= htmlspecialchars($partner['owner_name']) ?></strong>
            </span>
          </div>
        </div>
      </div>

      <div style="display:flex; gap:0.6rem;">
        <a href="/shop.php?id=<?= $partner_id ?>" target="_blank" class="btn-live-shop">
          👁️ View Live Storefront ↗
        </a>
      </div>
    </div>
  </div>

  <div class="studio-grid">
    
    <!-- Left Column: Core Brand & Details Form -->
    <div class="glass-card">
      <div class="card-header">
        <h3 class="card-title">
          <span>✏️</span> Core Brand Identity &amp; Contact Info
        </h3>
        <span style="font-size:0.75rem; color:#94a3b8;">* Required Fields</span>
      </div>

      <form method="POST" enctype="multipart/form-data" action="profile.php">
        
        <div class="row-flex">
          <div class="field">
            <label>Business / Store Name *</label>
            <input type="text" name="business_name" value="<?= htmlspecialchars($partner['business_name']) ?>" required/>
          </div>
          <div class="field">
            <label>Owner / Manager Full Name *</label>
            <input type="text" name="owner_name" value="<?= htmlspecialchars($partner['owner_name']) ?>" required/>
          </div>
        </div>

        <div class="row-flex">
          <div class="field">
            <label>Contact Phone Number *</label>
            <input type="tel" name="phone" value="<?= htmlspecialchars($partner['phone']) ?>" required/>
          </div>
          <div class="field">
            <label>Official Email Address *</label>
            <input type="email" name="email" value="<?= htmlspecialchars($partner['email']) ?>" required/>
          </div>
        </div>

        <div class="field">
          <label>District / Region (For Local Storefront Discovery)</label>
          <select name="district">
            <option value="">Select District</option>
            <?php foreach($districts as $d): ?>
              <option value="<?= $d ?>" <?= ($partner['district'] ?? '') === $d ? 'selected' : '' ?>><?= $d ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="field">
          <label>Shop Bio &amp; Customer Slogan</label>
          <textarea name="description" rows="3" placeholder="Tell buyers what your shop offers, specialty services, quality guarantee..."><?= htmlspecialchars($partner['description'] ?? '') ?></textarea>
        </div>

        <!-- Brand Media Uploaders -->
        <div class="row-flex" style="margin-bottom:1.5rem;">
          <div>
            <label style="display:block; font-size:0.76rem; font-weight:700; color:var(--gold); text-transform:uppercase; margin-bottom:0.4rem;">
              Brand Logo / Icon (1:1 Square)
            </label>
            <div class="media-uploader-box" onclick="document.getElementById('logo-file-input').click()">
              <input type="file" name="profile_pic" id="logo-file-input" accept="image/*" style="display:none;" onchange="previewLogo(this)"/>
              <div style="font-size:1.8rem; margin-bottom:4px;">🖼️</div>
              <div style="font-size:0.8rem; color:#fff; font-weight:700;">Click to Choose Logo</div>
              <div style="font-size:0.7rem; color:#94a3b8;">PNG, JPG, WEBP (Max 5MB)</div>
            </div>
          </div>

          <div>
            <label style="display:block; font-size:0.76rem; font-weight:700; color:var(--gold); text-transform:uppercase; margin-bottom:0.4rem;">
              Storefront Banner (16:9 Wide)
            </label>
            <div class="media-uploader-box" onclick="document.getElementById('cover-file-input').click()">
              <input type="file" name="cover_pic" id="cover-file-input" accept="image/*" style="display:none;" onchange="previewCover(this)"/>
              <div style="font-size:1.8rem; margin-bottom:4px;">🌆</div>
              <div style="font-size:0.8rem; color:#fff; font-weight:700;">Click to Choose Banner</div>
              <div style="font-size:0.7rem; color:#94a3b8;">Recommended 1200×400 px</div>
            </div>
          </div>
        </div>

        <!-- Payout Settlement Details -->
        <div style="background:rgba(0,0,0,0.25); border:1px solid rgba(255,255,255,0.08); border-radius:14px; padding:1.2rem; margin-bottom:1.5rem;">
          <h4 style="margin:0 0 0.8rem 0; color:var(--teal); font-size:0.9rem; text-transform:uppercase; font-weight:800; display:flex; align-items:center; gap:6px;">
            <span>💳</span> Payout &amp; Earnings Settlement Account
          </h4>
          <div class="row-flex">
            <div class="field" style="margin-bottom:0;">
              <label>Payout Gateway</label>
              <select name="payout_method">
                <option value="bkash" <?= ($partner['payout_method'] ?? '') === 'bkash' ? 'selected' : '' ?>>bKash Personal / Merchant</option>
                <option value="nagad" <?= ($partner['payout_method'] ?? '') === 'nagad' ? 'selected' : '' ?>>Nagad Personal</option>
                <option value="rocket" <?= ($partner['payout_method'] ?? '') === 'rocket' ? 'selected' : '' ?>>Rocket</option>
                <option value="bank" <?= ($partner['payout_method'] ?? '') === 'bank' ? 'selected' : '' ?>>Bank Wire Transfer</option>
              </select>
            </div>
            <div class="field" style="margin-bottom:0;">
              <label>Account / Wallet Number</label>
              <input type="text" name="payout_account" value="<?= htmlspecialchars($partner['payout_account'] ?? '') ?>" placeholder="e.g. 01700000000 or Bank Acc details"/>
            </div>
          </div>
        </div>

        <button type="submit" name="update_profile" class="btn-gold">
          💾 Save Shop Profile Details
        </button>

      </form>
    </div>

    <!-- Right Column: Password Vault & Security -->
    <div>
      
      <!-- Change Password Card -->
      <div class="glass-card">
        <div class="card-header">
          <h3 class="card-title">
            <span>🔑</span> Security &amp; Password
          </h3>
        </div>

        <form method="POST" action="profile.php">
          <div class="field">
            <label>Current Password *</label>
            <div class="pass-toggle-wrap">
              <input type="password" name="old_password" id="old-pass-input" required/>
              <button type="button" class="pass-toggle-btn" onclick="togglePass('old-pass-input')">👁️</button>
            </div>
          </div>

          <div class="field">
            <label>New Password *</label>
            <div class="pass-toggle-wrap">
              <input type="password" name="new_password" id="new-pass-input" required minlength="6"/>
              <button type="button" class="pass-toggle-btn" onclick="togglePass('new-pass-input')">👁️</button>
            </div>
          </div>

          <div class="field">
            <label>Confirm New Password *</label>
            <div class="pass-toggle-wrap">
              <input type="password" name="confirm_password" id="conf-pass-input" required minlength="6"/>
              <button type="button" class="pass-toggle-btn" onclick="togglePass('conf-pass-input')">👁️</button>
            </div>
          </div>

          <button type="submit" name="change_password" class="btn-gold" style="background:linear-gradient(135deg, #38bdf8 0%, #0284c7 100%); color:#fff; box-shadow:0 8px 25px rgba(2,132,199,0.35);">
            🔒 Update Password
          </button>
        </form>
      </div>

      <!-- Trust & Escrow Protection Card -->
      <div class="glass-card" style="background:linear-gradient(135deg, rgba(0,230,118,0.06) 0%, rgba(18,20,32,0.85) 100%); border-color:rgba(0,230,118,0.25);">
        <h4 style="color:var(--teal); margin:0 0 0.5rem 0; font-size:1rem; font-family:'Oswald',sans-serif; text-transform:uppercase; display:flex; align-items:center; gap:6px;">
          <span>🛡️</span> Fast Site Escrow Shield Active
        </h4>
        <p style="color:#94a3b8; font-size:0.82rem; line-height:1.45; margin:0;">
          Your shop is authenticated in the Fast Site partner ecosystem. Payments for digital products and services are secured until order completion.
        </p>
      </div>

    </div>

  </div>

</div>

<script>
function togglePass(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.type = (el.type === 'password') ? 'text' : 'password';
}

function previewLogo(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('hero-logo-preview').src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function previewCover(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('hero-cover-preview').src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
</body>
</html>
