<?php
// =========================================================================
// user/reset_password.php — Ultra-Premium Password Reset Confirmation
// =========================================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config.php';

$uid = isset($_GET['uid']) ? (int)$_GET['uid'] : 0;
if (!$uid) {
    header('Location: forgot_password.php');
    exit;
}

$err = $msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entered_otp = trim($_POST['otp'] ?? '');
    $new_pass = $_POST['new_password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';
    
    if (empty($entered_otp)) {
        $err = "Please enter the OTP verification code sent by Admin.";
    } elseif (strlen($new_pass) < 6) {
        $err = "New password must be at least 6 characters long.";
    } elseif ($new_pass !== $confirm_pass) {
        $err = "Passwords do not match. Please re-enter.";
    } else {
        // Validate OTP
        $stmt = $pdo->prepare("SELECT id, otp_code FROM password_resets WHERE user_id = ? AND (status = 'approved' OR status = 'pending') ORDER BY id DESC LIMIT 1");
        $stmt->execute([$uid]);
        $reset = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $validOtp = ($reset && (!empty($reset['otp_code']) ? ($reset['otp_code'] === $entered_otp) : true));
        
        if ($validOtp) {
            $hash = password_hash($new_pass, PASSWORD_BCRYPT);
            $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$hash, $uid]);
            
            // Clear the reset request
            if ($reset) {
                $pdo->prepare("DELETE FROM password_resets WHERE id = ?")->execute([$reset['id']]);
            }
            
            $msg = "Password updated successfully! Redirecting to login...";
            echo "<script>setTimeout(function(){ window.location.href='login.php'; }, 1500);</script>";
        } else {
            $err = "Invalid OTP verification code. Please check with Fast Site Admin on WhatsApp.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Set New Password &mdash; Fast Site</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Oswald:wght@500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --gold: #fcb900;
      --card-bg: rgba(15, 18, 30, 0.88);
      --border: rgba(255, 255, 255, 0.08);
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
      margin: 0;
    }
    .auth-card {
      background: var(--card-bg);
      border: 1px solid var(--border);
      border-radius: 24px;
      padding: 2.5rem 2.2rem;
      backdrop-filter: blur(20px);
      box-shadow: 0 25px 60px rgba(0, 0, 0, 0.75), 0 0 35px rgba(252, 185, 0, 0.1);
      width: 100%;
      max-width: 440px;
      text-align: center;
    }
    .input-field {
      width: 100%;
      background: rgba(10, 12, 22, 0.9);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 12px;
      padding: 0.8rem 1rem;
      color: #fff;
      font-size: 0.9rem;
      box-sizing: border-box;
      outline: none;
      margin-bottom: 1rem;
      transition: 0.2s;
    }
    .input-field:focus {
      border-color: var(--gold);
      box-shadow: 0 0 15px rgba(252, 185, 0, 0.25);
    }
    .btn-submit {
      width: 100%;
      background: linear-gradient(135deg, var(--gold) 0%, #ff9100 100%);
      color: #000;
      font-weight: 900;
      font-family: 'Oswald', sans-serif;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      border: none;
      border-radius: 50px;
      padding: 1rem;
      font-size: 1rem;
      cursor: pointer;
      box-shadow: 0 8px 25px rgba(252, 185, 0, 0.35);
    }
  </style>
</head>
<body>

<div class="auth-card">
  <h2 style="font-family:'Oswald',sans-serif; font-size:1.7rem; color:#fff; text-transform:uppercase; margin-bottom:0.4rem;">Set New Password</h2>
  <p style="font-size:0.85rem; color:#94a3b8; margin-bottom:1.5rem;">Enter your verification OTP code and create a new secure password.</p>

  <?php if($err): ?><div style="background:rgba(255,82,82,0.12); color:#ff5252; border:1px solid rgba(255,82,82,0.3); padding:0.8rem; border-radius:10px; font-size:0.84rem; margin-bottom:1rem; text-align:left;">⚠️ <?= htmlspecialchars($err) ?></div><?php endif; ?>
  <?php if($msg): ?><div style="background:rgba(0,230,118,0.12); color:#00e676; border:1px solid rgba(0,230,118,0.3); padding:0.8rem; border-radius:10px; font-size:0.84rem; margin-bottom:1rem; text-align:left;">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>

  <form method="POST">
    <div style="text-align:left;">
      <label style="font-size:0.75rem; font-weight:700; color:#cbd5e1; text-transform:uppercase; display:block; margin-bottom:0.3rem;">OTP Verification Code</label>
      <input type="text" name="otp" class="input-field" placeholder="Enter OTP code received" required/>

      <label style="font-size:0.75rem; font-weight:700; color:#cbd5e1; text-transform:uppercase; display:block; margin-bottom:0.3rem;">New Password</label>
      <input type="password" name="new_password" class="input-field" placeholder="At least 6 characters" required/>

      <label style="font-size:0.75rem; font-weight:700; color:#cbd5e1; text-transform:uppercase; display:block; margin-bottom:0.3rem;">Confirm New Password</label>
      <input type="password" name="confirm_password" class="input-field" placeholder="Re-type new password" required/>
    </div>

    <button type="submit" class="btn-submit">⚡ Update Password</button>
  </form>

  <div style="margin-top:1.5rem; font-size:0.85rem;">
    <a href="login.php" style="color:var(--gold); text-decoration:none; font-weight:700;">← Back to Login</a>
  </div>
</div>

</body>
</html>
