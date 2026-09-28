<?php
require_once __DIR__ . '/../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    echo '<div style="text-align:center; color:var(--red);">Not logged in</div>';
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Fetch notifications
    $stmt = $pdo->prepare("SELECT * FROM user_notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
    $stmt->execute([$user_id]);
    $notifications = $stmt->fetchAll();

    // Mark as read
    $stmt_up = $pdo->prepare("UPDATE user_notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
    $stmt_up->execute([$user_id]);

} catch (Exception $e) {
    echo '<div style="color:var(--red);">Error loading notifications</div>';
    exit;
}

if (empty($notifications)) {
    echo '<div style="text-align:center; padding:2rem; color:var(--muted);">
            <div style="font-size:2rem; margin-bottom:0.5rem; opacity:0.5;">🔕</div>
            No new notifications
          </div>';
    exit;
}
?>

<div style="display:flex; flex-direction:column; gap:0.8rem;">
<?php foreach ($notifications as $notif): ?>
    <div style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.06); border-radius:10px; padding:1rem; position:relative;">
        <?php if (!$notif['is_read']): ?>
            <span style="position:absolute; top:12px; right:12px; width:8px; height:8px; background:var(--gold); border-radius:50%; box-shadow:0 0 8px var(--gold);"></span>
        <?php endif; ?>
        <h4 style="margin:0 0 0.4rem 0; font-size:0.9rem; color:#fff; padding-right:1rem;"><?= htmlspecialchars($notif['title']) ?></h4>
        <p style="margin:0 0 0.6rem 0; font-size:0.8rem; color:var(--muted); line-height:1.4;"><?= nl2br(htmlspecialchars($notif['message'])) ?></p>
        <div style="font-size:0.7rem; color:var(--brand); font-weight:600; text-align:right;">
            <?= date('d M Y, h:i A', strtotime($notif['created_at'])) ?>
        </div>
    </div>
<?php endforeach; ?>
</div>
