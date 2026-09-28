<?php
// =========================================================================
// user/login.php  –  v2: Premium Mobile-First Overhaul
// =========================================================================
session_start();
if (isset($_SESSION['user_id'])) { header('Location: /user/dashboard.php'); exit; }
require_once __DIR__ . '/../config.php';

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = trim($_POST['phone'] ?? '');
    $pass = $_POST['password'] ?? '';

    $u = $pdo->prepare("SELECT * FROM users WHERE phone=:p LIMIT 1");
    $u->execute([':p'=>$phone]);
    $user = $u->fetch();

    if ($user && password_verify($pass, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];

        // --- PHASE 3: Gamified User Streaks Logic ---
        $today = date('Y-m-d');
        $last_login = $user['last_login_date'] ?? null;
        $current = (int)($user['current_streak'] ?? 0);
        $longest = (int)($user['longest_streak'] ?? 0);
        $coins = (float)($user['coins_balance'] ?? 0);
        
        if ($last_login !== $today) {
            if ($last_login === date('Y-m-d', strtotime('-1 day'))) {
                // Consecutive login
                $current++;
            } else {
                // Streak broken or first time
                $current = 1;
            }
            
            if ($current > $longest) { $longest = $current; }
            
            // Reward system: 1 coin daily, 5 coin bonus on 7-day streak
            $reward = 1;
            if ($current % 7 === 0) { $reward += 5; }
            $coins += $reward;
            
            $pdo->prepare("UPDATE users SET current_streak=?, longest_streak=?, last_login_date=?, coins_balance=? WHERE id=?")
                ->execute([$current, $longest, $today, $coins, $user['id']]);
                
            $_SESSION['streak_reward_msg'] = "🔥 Day $current Streak! You earned $reward Fast Coin(s).";
        }

        header('Location: /user/dashboard.php'); exit;
    } else {
        $err = 'Invalid phone number or password.';
    }
}

// Random dynamic greetings
$greetings = ["Welcome Back!", "Secure Portal", "Enter Ecosystem"];
$greet = $greetings[array_rand($greetings)];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover"/>
  <meta name="apple-mobile-web-app-capable" content="yes"/>
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent"/>
  <meta name="theme-color" content="#0A0D1A"/>
  <title>User Login — Fast Site</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Oswald:wght@600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="/assets/css/native_mobile.css?v=<?= time() ?>">
  <link rel="stylesheet" href="/assets/css/user.css?v=<?= time() ?>">
  <script src="/assets/js/app_environment.js" defer></script>
  <style>
    body.auth-mode {
      background: linear-gradient(-45deg, #09090e, #13131f, #0d1218, #0a0a0f);
      background-size: 400% 400%;
      animation: gradientBG 15s ease infinite;
      position: relative;
    }
    @keyframes gradientBG {
      0% { background-position: 0% 50%; }
      50% { background-position: 100% 50%; }
      100% { background-position: 0% 50%; }
    }
    .floating-orbs {
      position: fixed; top: 0; left: 0; width: 100%; height: 100%;
      pointer-events: none; z-index: 0; overflow: hidden;
    }
    .orb {
      position: absolute; border-radius: 50%; filter: blur(100px); opacity: 0.4;
      animation: floatOrb 20s infinite ease-in-out alternate;
    }
    .orb-1 { width: 40vw; height: 40vw; background: #fcb900; top: -10vw; left: -10vw; }
    .orb-2 { width: 50vw; height: 50vw; background: #00d2ff; bottom: -20vw; right: -10vw; animation-delay: -5s; }
    .orb-3 { width: 30vw; height: 30vw; background: #ff0055; top: 40%; left: 50%; animation-delay: -10s; }
    @keyframes floatOrb {
      100% { transform: translateY(100px) translateX(100px) scale(1.2); }
    }
    .box {
      z-index: 1; position: relative;
      background: rgba(20, 20, 30, 0.7) !important;
      backdrop-filter: blur(25px);
      -webkit-backdrop-filter: blur(25px);
      border: 1px solid rgba(255, 255, 255, 0.08);
      box-shadow: 0 25px 50px rgba(0,0,0,0.5);
    }
  </style>
</head>
<body class="auth-mode">

<div class="floating-orbs">
  <div class="orb orb-1"></div><div class="orb orb-2"></div><div class="orb orb-3"></div>
</div>

<div class="box">
  <div class="brand-logo">⚡ FAST SITE</div>
  <h2><?= $greet ?></h2>
  
  <?php if($err): ?>
    <div class="err">⚠️ <?= htmlspecialchars($err) ?></div>
  <?php endif; ?>
  
  <form method="POST">
    <div class="field">
      <label>Phone Number</label>
      <input type="tel" name="phone" id="phone" placeholder="e.g. 017XXXXXXXX" required autofocus/>
    </div>
    <div class="field">
      <label>Password</label>
      <div style="position: relative;">
        <input type="password" name="password" id="password" placeholder="Enter your password" required style="padding-right: 2.5rem;"/>
        <span id="toggle-password" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #8888aa; font-size: 1.1rem; user-select: none;">👁️</span>
      </div>
      <div style="text-align: right; margin-top: 0.5rem;">
        <a href="forgot_password.php" style="color:var(--brand); text-decoration:none; font-size:0.75rem; font-weight:600;">Forgot Password?</a>
      </div>
    </div>
    <button type="submit" class="btn">Access Dashboard</button>

    <!-- One-Tap Biometric Login Button (Shown if Biometrics Enabled) -->
    <div id="bio-login-section" style="display:none; margin-top:1rem;">
      <div style="display:flex; align-items:center; gap:8px; margin: 1rem 0 0.8rem 0;">
        <div style="flex:1; height:1px; background:rgba(255,255,255,0.1);"></div>
        <span style="font-size:0.75rem; color:var(--muted); font-weight:700; text-transform:uppercase;">or Instant Login</span>
        <div style="flex:1; height:1px; background:rgba(255,255,255,0.1);"></div>
      </div>
      <button type="button" id="bio-login-btn" onclick="triggerBiometricLogin()" style="width:100%; display:flex; align-items:center; justify-content:center; gap:10px; background:rgba(252,185,0,0.12); border:1px solid rgba(252,185,0,0.4); color:var(--gold); border-radius:12px; padding:0.85rem; font-size:0.9rem; font-weight:800; cursor:pointer; box-shadow:0 0 15px rgba(252,185,0,0.15); transition:all 0.2s;">
        <span style="font-size:1.3rem;">⚡</span>
        <span>Touch Sensor (Fingerprint / Face ID)</span>
      </button>
    </div>
  </form>
  
  <p style="margin-top:1.8rem; font-size:0.8rem; color:var(--muted);">
    Don't have an account? <a href="register.php" style="color:var(--brand); text-decoration:none; font-weight:700;">Create one here ➔</a>
  </p>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const phoneInput = document.getElementById('phone');
  const savedPhone = localStorage.getItem('fast_site_phone') || localStorage.getItem('fastsite_bio_phone');
  if (savedPhone) {
    phoneInput.value = savedPhone;
    const pwd = document.getElementById('password');
    if (pwd) pwd.focus();
  }
  
  document.querySelector('form').addEventListener('submit', function() {
    localStorage.setItem('fast_site_phone', phoneInput.value);
  });

  // Check if biometric login is available and enabled
  const bioEnabled = localStorage.getItem('fastsite_biometric_enabled') === '1';
  const bioSection = document.getElementById('bio-login-section');
  if (bioEnabled && bioSection) {
    bioSection.style.display = 'block';
  }
});

function triggerBiometricLogin() {
  const bioUserId = localStorage.getItem('fastsite_bio_user_id') || '';
  const bioPhone = localStorage.getItem('fastsite_bio_phone') || document.getElementById('phone').value || '';

  if (window.FastSiteNative && typeof window.FastSiteNative.requestBiometricAuth === 'function') {
    window.onBioLoginSuccess = function() {
      // Execute biometric login session
      const formData = new FormData();
      if (bioUserId) formData.append('user_id', bioUserId);
      if (bioPhone) formData.append('phone', bioPhone);

      fetch('/api/biometric_login.php', {
        method: 'POST',
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        if (data.status === 'success') {
          window.location.href = data.redirect_url || '/user/dashboard.php';
        } else {
          alert('❌ ' + (data.message || 'Login failed. Please enter password.'));
        }
      })
      .catch(err => {
        alert('Network Error during biometric login: ' + err);
      });
    };

    window.onBioLoginFail = function(err) {
      alert('⚠️ Biometric verification cancelled: ' + (err || 'Try again or use password'));
    };

    window.FastSiteNative.requestBiometricAuth('onBioLoginSuccess', 'onBioLoginFail');
  } else {
    // Web fallback
    if (bioUserId || bioPhone) {
      const formData = new FormData();
      if (bioUserId) formData.append('user_id', bioUserId);
      if (bioPhone) formData.append('phone', bioPhone);

      fetch('/api/biometric_login.php', {
        method: 'POST',
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        if (data.status === 'success') {
          window.location.href = data.redirect_url || '/user/dashboard.php';
        } else {
          alert('❌ ' + (data.message || 'Login failed. Please enter password.'));
        }
      })
      .catch(err => {
        alert('Error: ' + err);
      });
    } else {
      alert('Please enter your phone number and password once to link your biometric sensor.');
    }
  }
}

document.getElementById('toggle-password').addEventListener('click', function () {
  const passwordInput = document.getElementById('password');
  const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
  passwordInput.setAttribute('type', type);
  this.textContent = type === 'password' ? '👁️' : '🙈';
});
</script>
</body>
</html>
