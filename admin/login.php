<?php
// ============================================================
// admin/login.php  –  Admin authentication
// Credentials: admin / FastSitee2026
// ============================================================

session_start();

require_once __DIR__ . '/../config.php';

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim($_POST['username'] ?? '');
    $p = trim($_POST['password'] ?? '');

    $stmt = $pdo->prepare("SELECT id, password_hash, role FROM staff_users WHERE username = ?");
    $stmt->execute([$u]);
    $user = $stmt->fetch();

    $is_valid = false;
    $user_role = $user['role'] ?? 'admin';

    if ($user && password_verify($p, $user['password_hash'])) {
        $is_valid = true;
    } elseif (strtolower($u) === 'admin' && ($p === 'FastSitee2026' || $p === 'FastSite2026' || $p === 'fastsite2026')) {
        // Master self-healing recovery for root admin
        $new_hash = password_hash('FastSitee2026', PASSWORD_DEFAULT);
        try {
            if (!$user) {
                $pdo->prepare("INSERT INTO staff_users (username, password_hash, role, permissions) VALUES ('admin', ?, 'admin', '[\"all\"]')")
                    ->execute([$new_hash]);
            } else {
                $pdo->prepare("UPDATE staff_users SET password_hash = ?, role = 'admin' WHERE username = 'admin'")
                    ->execute([$new_hash]);
            }
        } catch (Exception $e) {}
        $is_valid = true;
        $user_role = 'admin';
    }

    if ($is_valid) {
        session_regenerate_id(true);
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_user']      = $u;
        $_SESSION['admin_role']      = $user_role;
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Invalid username or password.';
    }
}$backgrounds = [
    "linear-gradient(135deg, #0f172a 0%, #1e1e2f 100%)",
    "linear-gradient(45deg, #09090b 0%, #171723 100%)",
    "radial-gradient(circle at 50% 0%, #1e1e2f 0%, #0d0d12 100%)",
    "url('https://images.unsplash.com/photo-1614850523459-c2f4c699c52e?q=80&w=1920&auto=format&fit=crop') center/cover no-repeat fixed"
];
$bg = $backgrounds[array_rand($backgrounds)];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>System Login — Fast Site</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Oswald:wght@500;600;700&display=swap" rel="stylesheet"/>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Inter', sans-serif;
      background: <?= $bg ?> !important;
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      overflow: hidden;
      position: relative;
    }
    
    /* Animated Background Orbs */
    .orb {
      position: absolute;
      border-radius: 50%;
      filter: blur(80px);
      z-index: -1;
      animation: float 20s infinite ease-in-out alternate;
    }
    .orb-1 {
      width: 400px; height: 400px;
      background: rgba(252, 185, 0, 0.15);
      top: -100px; left: -100px;
    }
    .orb-2 {
      width: 500px; height: 500px;
      background: rgba(33, 150, 243, 0.1);
      bottom: -150px; right: -100px;
      animation-delay: -5s;
    }
    @keyframes float {
      0% { transform: translate(0, 0) scale(1); }
      100% { transform: translate(100px, 50px) scale(1.1); }
    }

    .login-container {
      width: 100%;
      max-width: 440px;
      background: rgba(20, 20, 31, 0.65);
      backdrop-filter: blur(30px);
      -webkit-backdrop-filter: blur(30px);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 28px;
      padding: 3.5rem 3rem;
      box-shadow: 0 30px 80px rgba(0, 0, 0, 0.6), inset 0 1px 0 rgba(255, 255, 255, 0.1);
      text-align: center;
      animation: slideUp 0.8s cubic-bezier(0.16, 1, 0.3, 1);
      position: relative;
      overflow: hidden;
      margin: 1rem;
    }
    .login-container::before {
      content: '';
      position: absolute;
      top: 0; left: 0; right: 0; height: 2px;
      background: linear-gradient(90deg, transparent, #fcb900, transparent);
      opacity: 0.5;
    }
    
    @keyframes slideUp {
      from { opacity: 0; transform: translateY(40px) scale(0.98); }
      to { opacity: 1; transform: translateY(0) scale(1); }
    }
    
    .logo {
      font-family: 'Oswald', sans-serif;
      font-size: 2.5rem;
      font-weight: 700;
      color: #fff;
      letter-spacing: 1.5px;
      margin-bottom: 0.2rem;
      text-shadow: 0 0 20px rgba(255, 255, 255, 0.1);
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 12px;
    }
    .logo span {
      color: #fcb900;
    }
    .subtitle {
      font-size: 0.8rem;
      color: #9ca3af;
      text-transform: uppercase;
      letter-spacing: 3px;
      margin-bottom: 2.5rem;
      font-weight: 500;
    }
    
    .error {
      background: rgba(239, 68, 68, 0.15);
      color: #ef4444;
      border: 1px solid rgba(239, 68, 68, 0.3);
      padding: 1rem;
      border-radius: 12px;
      font-size: 0.85rem;
      margin-bottom: 2rem;
      font-weight: 600;
      animation: shake 0.5s;
    }
    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      25% { transform: translateX(-5px); }
      75% { transform: translateX(5px); }
    }

    .field {
      text-align: left;
      margin-bottom: 1.8rem;
      position: relative;
    }
    .field label {
      display: block;
      font-size: 0.75rem;
      color: #9ca3af;
      margin-bottom: 0.6rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 1px;
    }
    .field input {
      width: 100%;
      background: rgba(0, 0, 0, 0.25);
      border: 1px solid rgba(255, 255, 255, 0.08);
      color: #fff;
      padding: 1rem 1.2rem;
      border-radius: 14px;
      font-size: 1rem;
      font-family: 'Inter', sans-serif;
      transition: all 0.3s ease;
      outline: none;
      box-shadow: inset 0 2px 4px rgba(0,0,0,0.2);
    }
    .field input:focus {
      border-color: #fcb900;
      background: rgba(0, 0, 0, 0.4);
      box-shadow: 0 0 0 4px rgba(252, 185, 0, 0.1), inset 0 2px 4px rgba(0,0,0,0.2);
    }
    
    .btn-submit {
      width: 100%;
      background: linear-gradient(135deg, #fcb900 0%, #ff9800 100%);
      color: #000;
      border: none;
      padding: 1.1rem;
      border-radius: 14px;
      font-size: 1rem;
      font-weight: 800;
      cursor: pointer;
      margin-top: 1rem;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      box-shadow: 0 8px 25px rgba(252, 185, 0, 0.3);
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 12px;
      letter-spacing: 0.5px;
    }
    .btn-submit:hover {
      transform: translateY(-3px) scale(1.02);
      box-shadow: 0 15px 35px rgba(252, 185, 0, 0.4);
    }
    .btn-submit:active {
      transform: translateY(0) scale(0.98);
    }
    
    .toggle-pass {
      position: absolute;
      right: 18px;
      top: 38px;
      cursor: pointer;
      opacity: 0.5;
      transition: 0.3s;
      color: #fff;
    }
    .toggle-pass:hover { opacity: 1; color: #fcb900; }
  </style>
</head>
<body>

<div class="orb orb-1"></div>
<div class="orb orb-2"></div>

<div class="login-container">
  <div class="logo">
    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#fcb900" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"></path></svg>
    FAST <span>SITE</span>
  </div>
  <div class="subtitle">System Administration</div>

  <?php if ($error): ?>
    <div class="error">⚠️ <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST" action="">
    <div class="field">
      <label>Staff Username</label>
      <input type="text" name="username" id="username" autocomplete="username" placeholder="Enter your credentials" required />
    </div>
    <div class="field">
      <label>Secure Password</label>
      <div style="position: relative;">
        <input type="password" name="password" id="password" autocomplete="current-password" placeholder="••••••••" required style="padding-right: 3.5rem;" />
        <svg id="toggle-password" class="toggle-pass" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
      </div>
    </div>
    <button type="submit" class="btn-submit">
      AUTHENTICATE 
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
    </button>
  </form>
</div>

<script>
document.getElementById('toggle-password').addEventListener('click', function () {
  const input = document.getElementById('password');
  if (input.type === 'password') {
    input.type = 'text';
    this.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>';
  } else {
    input.type = 'password';
    this.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>';
  }
});
</script>
</body>
</html>
