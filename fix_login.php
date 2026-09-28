<?php
// =========================================================================
// fix_login.php — Instant 1-Click Admin Password Reset & Recovery
// =========================================================================
require_once __DIR__ . '/config.php';

header('Content-Type: text/html; charset=utf-8');

$new_pass = 'FastSitee2026';
$hash = password_hash($new_pass, PASSWORD_DEFAULT);

$output = [];
try {
    // 1. Check if staff_users table exists
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    if ($driver === 'sqlite') {
        $pdo->exec("CREATE TABLE IF NOT EXISTS staff_users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            email TEXT DEFAULT NULL,
            role TEXT DEFAULT 'admin',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            permissions TEXT DEFAULT NULL
        )");
    } elseif ($driver === 'pgsql') {
        $pdo->exec("CREATE TABLE IF NOT EXISTS staff_users (
            id SERIAL PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            email VARCHAR(100) DEFAULT NULL,
            role VARCHAR(50) DEFAULT 'admin',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            permissions TEXT DEFAULT NULL
        )");
    } else {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `staff_users` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `username` VARCHAR(50) NOT NULL UNIQUE,
            `password_hash` VARCHAR(255) NOT NULL,
            `email` VARCHAR(100) DEFAULT NULL,
            `role` VARCHAR(50) DEFAULT 'admin',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `permissions` TEXT DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    // 2. Check if admin exists
    $stmt = $pdo->prepare("SELECT id, username FROM staff_users WHERE username = 'admin'");
    $stmt->execute();
    $admin = $stmt->fetch();

    if ($admin) {
        $stmt_up = $pdo->prepare("UPDATE staff_users SET password_hash = :hash, role = 'admin' WHERE username = 'admin'");
        $stmt_up->execute([':hash' => $hash]);
        $output[] = "✅ Updated existing <strong>admin</strong> user with password: <code>{$new_pass}</code>";
    } else {
        $stmt_ins = $pdo->prepare("INSERT INTO staff_users (username, password_hash, role, permissions) VALUES ('admin', :hash, 'admin', '[\"all\"]')");
        $stmt_ins->execute([':hash' => $hash]);
        $output[] = "✅ Created new <strong>admin</strong> user with password: <code>{$new_pass}</code>";
    }

    // Auto-login session if requested
    if (isset($_GET['login']) && $_GET['login'] === '1') {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_user'] = 'admin';
        $_SESSION['admin_role'] = 'admin';
        header('Location: /admin/dashboard.php');
        exit;
    }

} catch (Exception $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Fast Site — Admin Password Reset Tool</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&family=Oswald:wght@700&display=swap" rel="stylesheet">
  <style>
    body {
      background: #08080c;
      color: #fff;
      font-family: 'Inter', sans-serif;
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      margin: 0;
      padding: 1.5rem;
    }
    .card {
      background: #12131e;
      border: 1px solid rgba(252, 185, 0, 0.3);
      border-radius: 20px;
      padding: 2.5rem;
      max-width: 480px;
      width: 100%;
      box-shadow: 0 20px 60px rgba(0,0,0,0.8);
      text-align: center;
    }
    h1 {
      font-family: 'Oswald', sans-serif;
      color: #fcb900;
      margin: 0 0 0.5rem 0;
      font-size: 1.8rem;
    }
    .badge-box {
      background: rgba(0, 230, 118, 0.12);
      border: 1px solid #00e676;
      border-radius: 12px;
      padding: 1.2rem;
      margin: 1.5rem 0;
      text-align: left;
      font-size: 0.95rem;
      color: #00e676;
    }
    .cred-table {
      width: 100%;
      margin: 1.5rem 0;
      background: rgba(0,0,0,0.4);
      border-radius: 12px;
      padding: 1rem;
      border: 1px solid rgba(255,255,255,0.08);
      text-align: left;
    }
    .cred-row {
      display: flex;
      justify-content: space-between;
      padding: 0.5rem 0;
      border-bottom: 1px solid rgba(255,255,255,0.05);
      font-size: 0.95rem;
    }
    .cred-row:last-child { border-bottom: none; }
    .btn {
      display: inline-block;
      background: linear-gradient(135deg, #fcb900, #ff9800);
      color: #000;
      text-decoration: none;
      font-weight: 800;
      padding: 1rem 2rem;
      border-radius: 10px;
      font-size: 1rem;
      margin-top: 1rem;
      box-shadow: 0 5px 20px rgba(252,185,0,0.3);
      transition: transform 0.2s;
    }
    .btn:hover { transform: translateY(-2px); }
  </style>
</head>
<body>

<div class="card">
  <h1>⚡ FAST SITE ADMIN RECOVERY</h1>
  <p style="color:#aaa; font-size:0.85rem; margin:0;">Database Password Synchronization System</p>

  <?php if (!empty($error)): ?>
    <div style="background:rgba(255,82,82,0.15); border:1px solid #ff5252; color:#ff5252; padding:1rem; border-radius:12px; margin:1rem 0;">
      Error: <?= htmlspecialchars($error) ?>
    </div>
  <?php else: ?>
    <div class="badge-box">
      <?php foreach ($output as $line): ?>
        <div><?= $line ?></div>
      <?php endforeach; ?>
    </div>

    <div class="cred-table">
      <div class="cred-row">
        <span style="color:#888;">Username:</span>
        <strong style="color:#fff;">admin</strong>
      </div>
      <div class="cred-row">
        <span style="color:#888;">Password:</span>
        <strong style="color:#fcb900;">FastSitee2026</strong>
      </div>
    </div>

    <div style="display:flex; gap:10px; flex-direction:column;">
      <a href="/admin/login.php" class="btn">🚀 Open Admin Login</a>
      <a href="fix_login.php?login=1" style="color:var(--gold,#fcb900); font-size:0.85rem; text-decoration:none; margin-top:0.5rem; font-weight:700;">⚡ Or 1-Click Instant Login (Bypass form)</a>
    </div>
  <?php endif; ?>
</div>

</body>
</html>
