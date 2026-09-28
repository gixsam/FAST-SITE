<?php
// =========================================================================
// user/forgot_password.php — Ultra-Premium Account Password Recovery
// =========================================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config.php';

// Self-healing database creation for password_resets
try {
    if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
        $pdo->exec("CREATE TABLE IF NOT EXISTS password_resets (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            status TEXT DEFAULT 'pending',
            otp_code TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
    } else {
        $pdo->exec("CREATE TABLE IF NOT EXISTS password_resets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            status VARCHAR(20) DEFAULT 'pending',
            otp_code VARCHAR(20) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
} catch (Exception $e) {}

$err = $msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = trim($_POST['phone'] ?? '');
    // Clean phone input
    $phone_clean = preg_replace('/[^0-9]/', '', $phone);
    if (strpos($phone_clean, '880') === 0 && strlen($phone_clean) === 13) {
        $phone_clean = '0' . substr($phone_clean, 3);
    }

    if (empty($phone_clean)) {
        $err = "Please enter your registered phone number.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, name, phone, whatsapp, email FROM users WHERE phone = ? OR whatsapp = ? LIMIT 1");
            $stmt->execute([$phone_clean, $phone_clean]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // Check if there's already a pending request
                $chk = $pdo->prepare("SELECT id FROM password_resets WHERE user_id = ? AND status = 'pending' LIMIT 1");
                $chk->execute([$user['id']]);
                if ($chk->fetch()) {
                    $msg = "A password reset request is already active for your account. Fast Site Admin is processing your request. You can also message on WhatsApp for instant assistance.";
                } else {
                    $pdo->prepare("INSERT INTO password_resets (user_id, status) VALUES (?, 'pending')")
                        ->execute([$user['id']]);
                    $msg = "Password reset request submitted successfully! Our automated security officer or Admin will verify your identity and send an OTP/recovery link to your registered phone (" . htmlspecialchars($user['phone']) . ").";
                }
            } else {
                $err = "No account found matching the phone number (" . htmlspecialchars($phone) . "). Please verify and try again.";
            }
        } catch (Exception $e) {
            $err = "System Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Password &mdash; Fast Site</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Oswald:wght@500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --gold: #fcb900;
      --gold-glow: rgba(252, 185, 0, 0.25);
      --teal: #00e676;
      --teal-glow: rgba(0, 230, 118, 0.25);
      --dark: #080911;
      --card-bg: rgba(15, 18, 30, 0.88);
      --border: rgba(255, 255, 255, 0.08);
      --border-focus: rgba(252, 185, 0, 0.45);
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      background: radial-gradient(circle at 50% 15%, #181d33 0%, #080911 100%);
      color: #f1f5f9;
      font-family: 'Inter', sans-serif;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1.5rem 1rem;
      position: relative;
      overflow-x: hidden;
    }

    /* Ambient Background Glows */
    .ambient-glow-1 {
      position: absolute;
      top: -10%;
      left: 50%;
      transform: translateX(-50%);
      width: 550px;
      height: 450px;
      background: radial-gradient(circle, rgba(252, 185, 0, 0.12) 0%, transparent 70%);
      pointer-events: none;
      z-index: 0;
    }

    .ambient-glow-2 {
      position: absolute;
      bottom: -10%;
      right: 15%;
      width: 400px;
      height: 400px;
      background: radial-gradient(circle, rgba(0, 230, 118, 0.08) 0%, transparent 70%);
      pointer-events: none;
      z-index: 0;
    }

    .auth-container {
      position: relative;
      z-index: 10;
      width: 100%;
      max-width: 460px;
    }

    .auth-card {
      background: var(--card-bg);
      border: 1px solid var(--border);
      border-radius: 24px;
      padding: 2.5rem 2.2rem;
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      box-shadow: 0 25px 60px rgba(0, 0, 0, 0.75), 0 0 35px rgba(252, 185, 0, 0.1);
      text-align: center;
      transition: transform 0.3s ease;
    }

    .logo-badge {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 80px;
      height: 80px;
      border-radius: 20px;
      background: #0d101d;
      border: 2px solid var(--gold);
      box-shadow: 0 0 25px rgba(252, 185, 0, 0.35);
      margin-bottom: 1.4rem;
      overflow: hidden;
    }

    .logo-badge img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .auth-title {
      font-family: 'Oswald', sans-serif;
      font-size: 1.8rem;
      font-weight: 700;
      color: #fff;
      text-transform: uppercase;
      letter-spacing: 0.04em;
      margin-bottom: 0.4rem;
    }

    .auth-subtitle {
      font-size: 0.85rem;
      color: #94a3b8;
      line-height: 1.5;
      margin-bottom: 1.8rem;
    }

    .field-wrap {
      text-align: left;
      margin-bottom: 1.4rem;
    }

    .field-wrap label {
      display: block;
      font-size: 0.76rem;
      font-weight: 700;
      color: #cbd5e1;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      margin-bottom: 0.5rem;
    }

    .phone-input-group {
      display: flex;
      align-items: center;
      background: rgba(10, 12, 22, 0.9);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 14px;
      overflow: hidden;
      transition: all 0.25s ease;
    }

    .phone-input-group:focus-within {
      border-color: var(--gold);
      box-shadow: 0 0 18px rgba(252, 185, 0, 0.25);
      background: rgba(14, 17, 28, 0.98);
    }

    .prefix-badge {
      display: flex;
      align-items: center;
      gap: 6px;
      padding: 0.85rem 0.9rem;
      background: rgba(255, 255, 255, 0.04);
      border-right: 1px solid rgba(255, 255, 255, 0.08);
      font-size: 0.85rem;
      font-weight: 700;
      color: var(--gold);
      user-select: none;
    }

    .phone-input-group input {
      flex: 1;
      border: none;
      background: transparent;
      padding: 0.85rem 1rem;
      color: #fff;
      font-size: 0.95rem;
      font-family: inherit;
      outline: none;
    }

    .phone-input-group input::placeholder {
      color: #64748b;
    }

    .btn-recover {
      width: 100%;
      background: linear-gradient(135deg, var(--gold) 0%, #ff9100 100%);
      color: #000;
      font-weight: 900;
      font-family: 'Oswald', sans-serif;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      border: none;
      border-radius: 50px;
      padding: 1rem 1.5rem;
      font-size: 1.05rem;
      cursor: pointer;
      min-height: 52px;
      transition: all 0.25s ease;
      box-shadow: 0 8px 25px rgba(252, 185, 0, 0.35);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }

    .btn-recover:hover {
      transform: translateY(-2px);
      box-shadow: 0 12px 32px rgba(252, 185, 0, 0.55);
    }

    .alert-err {
      background: rgba(255, 82, 82, 0.12);
      color: #ff5252;
      border: 1px solid rgba(255, 82, 82, 0.3);
      padding: 0.9rem 1.1rem;
      border-radius: 12px;
      font-size: 0.84rem;
      font-weight: 600;
      text-align: left;
      margin-bottom: 1.4rem;
      line-height: 1.4;
    }

    .alert-msg {
      background: rgba(0, 230, 118, 0.12);
      color: #00e676;
      border: 1px solid rgba(0, 230, 118, 0.3);
      padding: 1rem 1.1rem;
      border-radius: 12px;
      font-size: 0.84rem;
      font-weight: 600;
      text-align: left;
      margin-bottom: 1.4rem;
      line-height: 1.45;
    }

    .whatsapp-assist-box {
      margin-top: 1.6rem;
      padding: 1rem;
      background: rgba(37, 211, 102, 0.06);
      border: 1px dashed rgba(37, 211, 102, 0.25);
      border-radius: 14px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.8rem;
    }

    .wa-link {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: #25d366;
      color: #000;
      font-weight: 800;
      font-size: 0.78rem;
      padding: 0.45rem 0.9rem;
      border-radius: 50px;
      text-decoration: none;
      white-space: nowrap;
      transition: all 0.2s;
    }

    .wa-link:hover {
      transform: scale(1.04);
      box-shadow: 0 4px 15px rgba(37, 211, 102, 0.4);
    }

    .back-login {
      margin-top: 1.8rem;
      font-size: 0.85rem;
      color: #94a3b8;
    }

    .back-login a {
      color: var(--gold);
      text-decoration: none;
      font-weight: 700;
      transition: color 0.2s;
    }

    .back-login a:hover {
      text-decoration: underline;
      color: #fff;
    }
  </style>
</head>
<body>

<div class="ambient-glow-1"></div>
<div class="ambient-glow-2"></div>

<div class="auth-container">
  <div class="auth-card">
    
    <div class="logo-badge">
      <img src="/assets/images/logo.png" alt="Fast Site Logo" onerror="this.onerror=null; this.src='/assets/img/fast site logo only.jpeg';"/>
    </div>

    <h2 class="auth-title">Password Recovery</h2>
    <p class="auth-subtitle">
      Enter your registered account phone number to receive verification assistance and reset credentials.
    </p>

    <?php if($err): ?>
      <div class="alert-err">⚠️ <?= htmlspecialchars($err) ?></div>
    <?php endif; ?>

    <?php if($msg): ?>
      <div class="alert-msg">✅ <?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <form method="POST" action="forgot_password.php">
      <div class="field-wrap">
        <label>Registered Phone Number *</label>
        <div class="phone-input-group">
          <span class="prefix-badge">🇧🇩 +880</span>
          <input type="tel" name="phone" placeholder="1XXXXXXXXX" required autofocus/>
        </div>
      </div>

      <button type="submit" class="btn-recover">
        <span>⚡ Request Password Reset</span>
      </button>
    </form>

    <!-- WhatsApp Instant Direct Assist -->
    <div class="whatsapp-assist-box">
      <div style="text-align:left;">
        <div style="color:#fff; font-weight:700; font-size:0.8rem;">Need Instant Help?</div>
        <div style="color:#94a3b8; font-size:0.72rem;">Chat with Fast Site Support directly</div>
      </div>
      <a href="https://wa.me/8801337320544?text=Hello%20Fast%20Site%20Admin%2C%20I%20need%20to%20reset%20my%20account%20password." target="_blank" class="wa-link">
        <span>💬 WhatsApp</span>
      </a>
    </div>

    <div class="back-login">
      <a href="login.php">← Back to User Login</a>
    </div>

  </div>
</div>

</body>
</html>
