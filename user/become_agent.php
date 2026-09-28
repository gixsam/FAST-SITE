<?php
// =========================================================================
// user/become_agent.php  –  Agent Program Signup
// =========================================================================
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: /user/login.php'); exit; }
require_once __DIR__ . '/../config.php';

$userId = (int)$_SESSION['user_id'];
$u = $pdo->prepare("SELECT * FROM users WHERE id=:id LIMIT 1");
$u->execute([':id'=>$userId]);
$user = $u->fetch();

// Check if user is already an agent
$chk = $pdo->prepare("SELECT id FROM agents WHERE user_id=:uid LIMIT 1");
$chk->execute([':uid' => $userId]);
if ($chk->fetch()) {
    header('Location: /user/dashboard.php');
    exit;
}

$err = $msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = $user['phone'];
    
    // Auto-approve agent account or set pending based on your logic.
    // For this rewrite, we will set it to active immediately as it's an upgrade.
    try {
        $stmt = $pdo->prepare("INSERT INTO agents (user_id, phone, status, total_earnings, created_at) VALUES (:uid, :phone, 'active', 0, CURRENT_TIMESTAMP)");
        $stmt->execute([':uid' => $userId, ':phone' => $phone]);
        
        $msg = 'You have successfully upgraded to an Agent! Redirecting...';
        header("Refresh: 2; url=dashboard.php");
    } catch (PDOException $e) {
        $err = 'Error upgrading to Agent: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Become an Agent — Fast Site</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="/assets/css/user.css">
</head>
<body>
<div class="box">
  <h2>Become an Agent 👑</h2>
  <p style="color:var(--muted); font-size: 0.85rem; margin-bottom: 1.5rem;">Upgrade your account to unlock 20% flat commissions and agent features.</p>
  
  <?php if($err): ?><div style="color: #ff5252; margin-bottom: 1rem; font-size: 0.85rem;"><?= htmlspecialchars($err) ?></div><?php endif; ?>
  <?php if($msg): ?><div style="color: #00e676; margin-bottom: 1rem; font-size: 0.85rem;"><?= htmlspecialchars($msg) ?></div><?php else: ?>
  
  <form method="POST">
    <button type="submit" class="btn">Confirm Upgrade</button>
  </form>
  <?php endif; ?>
  
  <p style="margin-top:1.5rem;"><a href="dashboard.php" style="color:var(--brand); text-decoration:none; font-size: 0.8rem;">Cancel</a></p>
</div>
</body>
</html>
