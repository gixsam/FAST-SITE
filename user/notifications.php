<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: /user/login.php'); exit; }
require_once __DIR__ . '/../config.php';

$userId = (int)$_SESSION['user_id'];
$u = $pdo->prepare("SELECT * FROM users WHERE id=:id LIMIT 1");
$u->execute([':id'=>$userId]);
$user = $u->fetch();

$settings = $pdo->query("SELECT setting_key, setting_value FROM homepage_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
$siteName = $settings['site_name'] ?? 'FAST SITE';

// Mark all as read
$pdo->prepare("UPDATE user_notifications SET is_read=1 WHERE user_id=?")->execute([$userId]);

// Fetch notifications
$stmt = $pdo->prepare("SELECT * FROM user_notifications WHERE user_id=? ORDER BY id DESC LIMIT 50");
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Notifications â€” <?= htmlspecialchars($siteName) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/user.css">
</head>
<body>

<?php include __DIR__ . '/../includes/user_sidebar.php'; ?>

<div class="wrap" style="padding-top:1rem;">
  <?php if(empty($notifications)): ?>
  <div class="empty-state">
    <svg viewBox="0 0 24 24"><path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.89 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/></svg>
    <h3>You're all caught up!</h3>
    <p>We'll notify you here when you receive important updates about your orders and rewards.</p>
  </div>
  <?php else: ?>
    <?php foreach($notifications as $n): ?>
      <div style="background:var(--dark-card); padding:1rem; border-radius:12px; margin-bottom:1rem; border:1px solid rgba(255,255,255,0.05);">
        <h4 style="margin-bottom:0.3rem; color:var(--brand);"><?= htmlspecialchars($n['title']) ?></h4>
        <p style="font-size:0.85rem; line-height:1.4; margin-bottom:0.5rem;"><?= nl2br(htmlspecialchars($n['message'])) ?></p>
        <span style="font-size:0.7rem; color:var(--muted);"><?= date('d M Y, h:i A', strtotime($n['created_at'])) ?></span>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<!-- Bottom Navigation Bar (Hidden on Desktop) -->
<div class="bottom-nav mobile-only-bottom-nav">
  <div class="bottom-nav-inner">
    <a href="dashboard.php" class="b-nav-item" title="Home">
      <svg width="24" height="24" viewBox="0 0 24 24" style="width:24px; height:24px; max-width:24px; max-height:24px; fill:currentColor; flex-shrink:0;"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>
    </a>
    <a href="/index.php" class="b-nav-item" title="Marketplace">
      <svg width="24" height="24" viewBox="0 0 24 24" style="width:24px; height:24px; max-width:24px; max-height:24px; fill:currentColor; flex-shrink:0;"><path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/></svg>
    </a>
    <a href="profile.php" class="b-nav-item" style="flex:0.8;" title="Profile">
      <div class="profile-pic-btn">
        <?php 
          $pp_src = !empty($user['profile_pic']) ? '/' . ltrim($user['profile_pic'], '/') : "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23fcb900'><path d='M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-3.79 1.79-3.79 4 1.79 4 3.79 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z'/></svg>";
        ?>
        <img src="<?= htmlspecialchars($pp_src) ?>" alt="Profile" onerror="this.onerror=null; this.src='data:image/svg+xml;utf8,<svg xmlns=&apos;http://www.w3.org/2000/svg&apos; viewBox=&apos;0 0 24 24&apos; fill=&apos;%23fcb900&apos;><path d=&apos;M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-3.79 1.79-3.79 4 1.79 4 3.79 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z&apos;/></svg>';">
      </div>
    </a>

    <a href="javascript:history.back()" class="b-nav-item" title="Go Back">
      <svg width="24" height="24" viewBox="0 0 24 24" style="width:24px; height:24px; max-width:24px; max-height:24px; fill:currentColor; flex-shrink:0;"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
    </a>
  </div>
</div>

<style>
@media (min-width: 1025px) {
  .mobile-only-bottom-nav, .bottom-nav { display: none !important; }
}
</style>

</body>
</html>

