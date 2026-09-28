<?php
// =========================================================================
// user/register.php — Fast Site User Registration (Ultra-Clean & Mobile-First)
// =========================================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    header('Location: /user/dashboard.php');
    exit;
}

require_once __DIR__ . '/../config.php';

// Defensive self-healing schema migrations for users table (individual try/catch for each column)
if (isset($pdo)) {
    if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
        try { $pdo->exec("ALTER TABLE users ADD COLUMN whatsapp TEXT DEFAULT NULL"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE users ADD COLUMN dob DATE DEFAULT NULL"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE users ADD COLUMN gender TEXT DEFAULT NULL"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE users ADD COLUMN nid_number TEXT DEFAULT NULL"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE users ADD COLUMN registration_number TEXT DEFAULT NULL"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE users ADD COLUMN ref_by TEXT DEFAULT NULL"); } catch (Exception $e) {}
        try { $pdo->exec("CREATE TABLE IF NOT EXISTS coin_wallets (user_id INTEGER PRIMARY KEY, balance REAL DEFAULT 0.00)"); } catch (Exception $e) {}
    } else {
        try { $pdo->exec("ALTER TABLE `users` ADD COLUMN `whatsapp` VARCHAR(50) DEFAULT NULL"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE `users` ADD COLUMN `dob` DATE DEFAULT NULL"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE `users` ADD COLUMN `gender` VARCHAR(20) DEFAULT NULL"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE `users` ADD COLUMN `nid_number` VARCHAR(50) DEFAULT NULL"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE `users` ADD COLUMN `registration_number` VARCHAR(50) DEFAULT NULL"); } catch (Exception $e) {}
        try { $pdo->exec("ALTER TABLE `users` ADD COLUMN `ref_by` VARCHAR(50) DEFAULT NULL"); } catch (Exception $e) {}
        try { $pdo->exec("CREATE TABLE IF NOT EXISTS `coin_wallets` (`user_id` INT PRIMARY KEY, `balance` DECIMAL(10,2) DEFAULT 0.00)"); } catch (Exception $e) {}
    }
}

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name       = trim($_POST['name'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');
    $whatsapp   = trim($_POST['whatsapp'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $pass       = $_POST['password'] ?? '';
    $dob        = trim($_POST['dob'] ?? '');
    $gender     = trim($_POST['gender'] ?? '');
    $nid_number = trim($_POST['nid_number'] ?? '');
    $profile_pic_b64 = $_POST['profile_pic_b64'] ?? '';

    // Normalizing phone
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($phone) === 10 && substr($phone, 0, 1) === '1') {
        $phone = '0' . $phone;
    }

    if (empty($name) || empty($phone) || empty($pass) || empty($dob) || empty($gender) || empty($email) || empty($nid_number)) {
        $err = 'Please fill out all required fields marked with an asterisk (*).';
    } elseif (strlen($pass) < 6) {
        $err = 'Password must be at least 6 characters long.';
    } else {
        // Check Unique Phone
        $chkP = $pdo->prepare("SELECT id, name, password_hash, registration_number FROM users WHERE phone = :p LIMIT 1");
        $chkP->execute([':p' => $phone]);
        $existingPhoneUser = $chkP->fetch();

        if ($existingPhoneUser) {
            // Self-healing recovery: If password matches the registered record, complete session and log in!
            if (password_verify($pass, $existingPhoneUser['password_hash'])) {
                $uid = (int)$existingPhoneUser['id'];
                if (empty($existingPhoneUser['registration_number'])) {
                    $reg_number = 'FS-USER-' . str_pad($uid, 5, '0', STR_PAD_LEFT);
                    try { $pdo->prepare("UPDATE users SET registration_number = ? WHERE id = ?")->execute([$reg_number, $uid]); } catch (Exception $e) {}
                }
                try {
                    $pdo->prepare("INSERT INTO coin_wallets (user_id, balance) VALUES (?, 50.00) ON DUPLICATE KEY UPDATE balance = balance")->execute([$uid]);
                } catch (Exception $wEx) {}

                $_SESSION['user_id'] = $uid;
                $_SESSION['user_name'] = $existingPhoneUser['name'] ?: $name;
                header('Location: /user/dashboard.php?welcome=1');
                exit;
            }
            $err = 'This phone number (' . htmlspecialchars($phone) . ') is already registered. Please <a href="/user/login.php" style="color:var(--gold); font-weight:bold; text-decoration:underline;">login here</a>.';
        } else {
            // Check Unique Email
            $chkE = $pdo->prepare("SELECT id, name, password_hash, registration_number FROM users WHERE email = :e LIMIT 1");
            $chkE->execute([':e' => $email]);
            $existingEmailUser = $chkE->fetch();

            if ($existingEmailUser) {
                if (password_verify($pass, $existingEmailUser['password_hash'])) {
                    $uid = (int)$existingEmailUser['id'];
                    if (empty($existingEmailUser['registration_number'])) {
                        $reg_number = 'FS-USER-' . str_pad($uid, 5, '0', STR_PAD_LEFT);
                        try { $pdo->prepare("UPDATE users SET registration_number = ? WHERE id = ?")->execute([$reg_number, $uid]); } catch (Exception $e) {}
                    }
                    try {
                        $pdo->prepare("INSERT INTO coin_wallets (user_id, balance) VALUES (?, 50.00) ON DUPLICATE KEY UPDATE balance = balance")->execute([$uid]);
                    } catch (Exception $wEx) {}

                    $_SESSION['user_id'] = $uid;
                    $_SESSION['user_name'] = $existingEmailUser['name'] ?: $name;
                    header('Location: /user/dashboard.php?welcome=1');
                    exit;
                }
                $err = 'This email address (' . htmlspecialchars($email) . ') is already registered. Please <a href="/user/login.php" style="color:var(--gold); font-weight:bold; text-decoration:underline;">login here</a>.';
            } else {
                // Check Unique NID Number
                $chkN = $pdo->prepare("SELECT id FROM users WHERE nid_number = :n LIMIT 1");
                $chkN->execute([':n' => $nid_number]);
                if ($chkN->fetch()) {
                    $err = 'This NID Number is already registered.';
                } else {
                    // Process Base64 Profile Picture from Cropper
                    $profile_pic_path = null;
                    if (!empty($profile_pic_b64) && preg_match('/^data:image\/(jpeg|png|webp|gif);base64,/', $profile_pic_b64, $mType)) {
                        $imgData = base64_decode(preg_replace('/^data:image\/[a-z]+;base64,/', '', $profile_pic_b64));
                        if ($imgData && strlen($imgData) <= 8 * 1024 * 1024) {
                            $targetDir = __DIR__ . '/../uploads/profiles/';
                            if (!is_dir($targetDir)) {
                                @mkdir($targetDir, 0777, true);
                            }
                            $fileName = 'profile_' . time() . '_' . rand(1000, 9999) . '.png';
                            if (file_put_contents($targetDir . $fileName, $imgData) !== false) {
                                $profile_pic_path = 'uploads/profiles/' . $fileName;
                            }
                        }
                    }

                    // Process NID Document / Photo
                    $nid_photo_path = null;
                    if (isset($_FILES['nid_photo']) && $_FILES['nid_photo']['error'] === UPLOAD_ERR_OK) {
                        $destDir = __DIR__ . '/../uploads/documents/';
                        if (!is_dir($destDir)) {
                            @mkdir($destDir, 0777, true);
                        }
                        if (function_exists('handleSecureUpload')) {
                            $uploadedDoc = handleSecureUpload($_FILES['nid_photo'], $destDir, ['jpg', 'jpeg', 'png', 'webp', 'pdf'], 'nid_doc');
                            if ($uploadedDoc) {
                                $nid_photo_path = 'uploads/documents/' . $uploadedDoc;
                            }
                        }
                    }

                    // Generate Unique Referral Code and Hash Password
                    $ref_code = 'USER_' . time() . rand(10, 99);
                    $ref_by   = $_COOKIE['fastsite_ref'] ?? ($_GET['ref'] ?? null);
                    $hash     = password_hash($pass, PASSWORD_BCRYPT);

                    try {
                        $pdo->beginTransaction();

                        $stmtIns = $pdo->prepare("INSERT INTO users (name, phone, whatsapp, email, dob, gender, password_hash, ref_code, ref_by, nid_number, profile_pic, nid, coins_balance, role, is_active) 
                            VALUES (:n, :p, :wa, :e, :d, :g, :h, :r, :ref_by, :nn, :pp, :nd, 50.00, 'user', 1)");

                        $stmtIns->execute([
                            ':n'       => $name,
                            ':p'       => $phone,
                            ':wa'      => $whatsapp ?: $phone,
                            ':e'       => $email,
                            ':d'       => $dob,
                            ':g'       => $gender,
                            ':h'       => $hash,
                            ':r'       => $ref_code,
                            ':ref_by'  => $ref_by ?: null,
                            ':nn'      => $nid_number,
                            ':pp'      => $profile_pic_path,
                            ':nd'      => $nid_photo_path
                        ]);

                        $new_id = (int)$pdo->lastInsertId();

                        // Assign Unique Registration Number
                        $reg_number = 'FS-USER-' . str_pad($new_id, 5, '0', STR_PAD_LEFT);
                        $pdo->prepare("UPDATE users SET registration_number = ? WHERE id = ?")->execute([$reg_number, $new_id]);

                        // Initialize Coin Wallet with 50 Welcome Fast Points (DML only, no DDL inside transaction)
                        try {
                            $pdo->prepare("INSERT INTO coin_wallets (user_id, balance) VALUES (?, 50.00) ON DUPLICATE KEY UPDATE balance = balance")->execute([$new_id]);
                        } catch (Exception $wEx) {}

                        if ($pdo->inTransaction()) {
                            $pdo->commit();
                        }

                        $_SESSION['user_id'] = $new_id;
                        $_SESSION['user_name'] = $name;
                        header('Location: /user/dashboard.php?welcome=1');
                        exit;

                    } catch (Exception $dbEx) {
                        if ($pdo->inTransaction()) {
                            $pdo->rollBack();
                        }
                        $err = 'Database registration error: ' . $dbEx->getMessage();
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Create User Account — Fast Site</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Oswald:wght@600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="/assets/css/user.css?v=<?= time() ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/croppie/2.6.5/croppie.min.css" />
  <style>
    :root {
      --brand: #fcb900;
      --brand-glow: rgba(252, 185, 0, 0.25);
      --dark: #080911;
      --surface: rgba(16, 18, 28, 0.85);
      --border: rgba(255, 255, 255, 0.08);
      --text: #ffffff;
      --muted: #94a3b8;
      --green: #00e676;
      --red: #ff5252;
    }

    body.auth-mode {
      background: radial-gradient(circle at 50% 10%, #171a2e 0%, #080911 100%) !important;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: 'Inter', sans-serif;
      padding: 2rem 1rem;
      color: var(--text);
    }

    .auth-card {
      width: 100%;
      max-width: 520px;
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 24px;
      padding: 2.2rem;
      box-shadow: 0 25px 60px rgba(0,0,0,0.6);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      position: relative;
    }

    .brand-header {
      text-align: center;
      margin-bottom: 1.8rem;
    }

    .brand-logo {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      font-family: 'Oswald', sans-serif;
      font-size: 1.8rem;
      font-weight: 700;
      color: var(--brand);
      text-transform: uppercase;
      letter-spacing: 1px;
      text-shadow: 0 0 20px var(--brand-glow);
    }

    .brand-tagline {
      font-size: 0.85rem;
      color: var(--muted);
      margin-top: 0.3rem;
    }

    .welcome-gift-badge {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      background: rgba(0, 230, 118, 0.1);
      border: 1px solid rgba(0, 230, 118, 0.25);
      border-radius: 12px;
      padding: 0.6rem 1rem;
      color: var(--green);
      font-size: 0.82rem;
      font-weight: 700;
      margin-bottom: 1.5rem;
    }

    .err-box {
      background: rgba(255, 82, 82, 0.12);
      border: 1px solid rgba(255, 82, 82, 0.3);
      color: #ff8585;
      padding: 0.8rem 1rem;
      border-radius: 12px;
      font-size: 0.85rem;
      margin-bottom: 1.5rem;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .form-group {
      margin-bottom: 1.2rem;
    }

    .form-group label {
      display: block;
      font-size: 0.8rem;
      font-weight: 700;
      color: #cbd5e1;
      margin-bottom: 0.4rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .form-control {
      width: 100%;
      background: rgba(255, 255, 255, 0.04);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 12px;
      padding: 0.8rem 1.1rem;
      color: #ffffff;
      font-size: 0.92rem;
      outline: none;
      transition: all 0.2s;
      font-family: inherit;
    }

    .form-control:focus {
      border-color: var(--brand);
      background: rgba(255, 255, 255, 0.07);
      box-shadow: 0 0 15px var(--brand-glow);
    }

    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1rem;
    }

    @media (max-width: 540px) {
      .form-row { grid-template-columns: 1fr; }
      .auth-card { padding: 1.5rem; }
    }

    .btn-submit {
      width: 100%;
      background: linear-gradient(135deg, var(--brand), #ff9100);
      color: #000;
      border: none;
      padding: 0.95rem 1.5rem;
      border-radius: 12px;
      font-weight: 900;
      font-size: 1rem;
      cursor: pointer;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      transition: all 0.2s;
      box-shadow: 0 8px 25px rgba(252, 185, 0, 0.3);
      margin-top: 1rem;
    }

    .btn-submit:hover {
      transform: translateY(-2px);
      box-shadow: 0 12px 30px rgba(252, 185, 0, 0.45);
    }

    .password-wrap {
      position: relative;
    }

    .password-toggle {
      position: absolute;
      right: 14px;
      top: 50%;
      transform: translateY(-50%);
      cursor: pointer;
      font-size: 1.1rem;
      user-select: none;
      opacity: 0.6;
      transition: opacity 0.2s;
    }

    .password-toggle:hover { opacity: 1; }

    .avatar-picker-wrap {
      text-align: center;
      margin-bottom: 1.5rem;
    }

    .avatar-preview-box {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      border: 2px dashed rgba(252, 185, 0, 0.5);
      margin: 0 auto 0.8rem auto;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
      background: rgba(0,0,0,0.3);
      cursor: pointer;
      transition: all 0.2s;
    }

    .avatar-preview-box:hover {
      border-color: var(--brand);
      box-shadow: 0 0 15px var(--brand-glow);
    }

    .avatar-preview-box img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
  </style>
</head>
<body class="auth-mode">

<div class="auth-card">
  <div class="brand-header">
    <a href="/index.php" style="text-decoration:none;">
      <div class="brand-logo">⚡ FAST SITE</div>
    </a>
    <div class="brand-tagline">Join the #1 Escrow Marketplace &amp; Multi-Vendor Ecosystem</div>
  </div>

  <div class="welcome-gift-badge">
    <span>🎁</span>
    <span>Special Bonus: Receive <strong>50 Free Fast Points</strong> instantly on signup!</span>
  </div>

  <?php if (!empty($err)): ?>
    <div class="err-box">
      <span>⚠️</span>
      <span><?= htmlspecialchars($err) ?></span>
    </div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data" id="regForm">
    <input type="hidden" name="profile_pic_b64" id="profile_pic_b64">

    <!-- Avatar Picker -->
    <div class="avatar-picker-wrap">
      <div class="avatar-preview-box" onclick="document.getElementById('profile_pic_input').click()" title="Click to upload profile photo">
        <img id="avatar_img_preview" src="/assets/images/default_avatar.png" alt="Avatar Preview" onerror="this.src='/assets/img/fast site logo only.jpeg'">
      </div>
      <div style="font-size:0.75rem; color:var(--muted);">Click to upload profile photo (Optional)</div>
      <input type="file" id="profile_pic_input" accept="image/*" style="display:none;" />
      
      <div id="croppie-container" style="display:none; margin: 1rem auto; width: 200px; height: 200px;"></div>
      <button type="button" id="crop_btn" class="form-control" style="display:none; width:auto; margin:0.5rem auto; background:rgba(252,185,0,0.15); color:var(--brand); border-color:var(--brand); font-weight:700; cursor:pointer;">✂️ Crop &amp; Save Avatar</button>
    </div>

    <!-- Full Name -->
    <div class="form-group">
      <label>Full Name *</label>
      <input type="text" name="name" class="form-control" placeholder="e.g. Sabbir Hossain" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required autofocus/>
    </div>

    <!-- Phone & WhatsApp -->
    <div class="form-row">
      <div class="form-group">
        <label>Phone Number *</label>
        <input type="tel" name="phone" class="form-control" placeholder="017XXXXXXXX" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" required/>
      </div>
      <div class="form-group">
        <label>WhatsApp Number *</label>
        <input type="tel" name="whatsapp" class="form-control" placeholder="017XXXXXXXX" value="<?= htmlspecialchars($_POST['whatsapp'] ?? '') ?>" required/>
      </div>
    </div>

    <!-- DOB & Gender -->
    <div class="form-row">
      <div class="form-group">
        <label>Date of Birth *</label>
        <input type="date" name="dob" class="form-control" value="<?= htmlspecialchars($_POST['dob'] ?? '') ?>" required/>
      </div>
      <div class="form-group">
        <label>Gender *</label>
        <select name="gender" class="form-control" required style="background:#131522;">
          <option value="" disabled <?= empty($_POST['gender']) ? 'selected' : '' ?>>Select Gender</option>
          <option value="Male" <?= (($_POST['gender'] ?? '') === 'Male') ? 'selected' : '' ?>>Male</option>
          <option value="Female" <?= (($_POST['gender'] ?? '') === 'Female') ? 'selected' : '' ?>>Female</option>
          <option value="Other" <?= (($_POST['gender'] ?? '') === 'Other') ? 'selected' : '' ?>>Other</option>
        </select>
      </div>
    </div>

    <!-- Email -->
    <div class="form-group">
      <label>Email Address *</label>
      <input type="email" name="email" class="form-control" placeholder="name@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required/>
    </div>

    <!-- NID Number & Photo -->
    <div class="form-row">
      <div class="form-group">
        <label>NID Number *</label>
        <input type="text" name="nid_number" class="form-control" placeholder="10 or 17 digit NID" value="<?= htmlspecialchars($_POST['nid_number'] ?? '') ?>" required/>
      </div>
      <div class="form-group">
        <label>NID Photo (Optional)</label>
        <input type="file" name="nid_photo" class="form-control" accept="image/*,application/pdf" style="padding: 0.55rem 1rem;"/>
      </div>
    </div>

    <!-- Password -->
    <div class="form-group">
      <label>Choose Password *</label>
      <div class="password-wrap">
        <input type="password" name="password" id="password" class="form-control" placeholder="Minimum 6 characters" required style="padding-right: 2.8rem;"/>
        <span class="password-toggle" id="toggle-password" title="Toggle visibility">👁️</span>
      </div>
    </div>

    <button type="submit" class="btn-submit">⚡ Create Free Account</button>
  </form>

  <p style="text-align:center; margin-top:1.8rem; font-size:0.85rem; color:var(--muted);">
    Already have an account? <a href="login.php" style="color:var(--brand); text-decoration:none; font-weight:700;">Login here →</a>
  </p>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/croppie/2.6.5/croppie.min.js"></script>
<script>
// Password toggle
document.getElementById('toggle-password').addEventListener('click', function () {
  const passwordInput = document.getElementById('password');
  const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
  passwordInput.setAttribute('type', type);
  this.textContent = type === 'password' ? '👁️' : '🙈';
});

// Avatar Cropper
let croppieInstance = null;
const profileInput = document.getElementById('profile_pic_input');
const croppieContainer = document.getElementById('croppie-container');
const cropBtn = document.getElementById('crop_btn');
const avatarPreview = document.getElementById('avatar_img_preview');
const profileB64 = document.getElementById('profile_pic_b64');

profileInput.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) return;

    if (file.size > 8 * 1024 * 1024) {
        alert("File size exceeds 8MB. Please choose a smaller image.");
        profileInput.value = '';
        return;
    }

    if (croppieInstance) {
        croppieInstance.destroy();
    }

    croppieContainer.style.display = 'block';
    cropBtn.style.display = 'block';

    croppieInstance = new Croppie(croppieContainer, {
        viewport: { width: 140, height: 140, type: 'circle' },
        boundary: { width: 190, height: 190 },
        showZoomer: true
    });

    const reader = new FileReader();
    reader.onload = function(event) {
        croppieInstance.bind({
            url: event.target.result
        });
    }
    reader.readAsDataURL(file);
});

cropBtn.addEventListener('click', function() {
    if (!croppieInstance) return;

    croppieInstance.result({
        type: 'base64',
        size: 'viewport',
        format: 'png'
    }).then(function(base64) {
        profileB64.value = base64;
        avatarPreview.src = base64;
        croppieContainer.style.display = 'none';
        cropBtn.style.display = 'none';
    });
});
</script>

</body>
</html>
