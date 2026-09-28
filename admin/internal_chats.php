<?php
// =========================================================================
// admin/internal_chats.php  –  Inter-Teammate Internal Communication Center
// =========================================================================
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php'); exit;
}
require_once __DIR__ . '/../config.php';

// Get my staff user record
$stmt = $pdo->prepare("SELECT id, username, role FROM staff_users WHERE username = ?");
$stmt->execute([$_SESSION['admin_user']]);
$me = $stmt->fetch();
if (!$me) { die('User not found.'); }
$myId = (int)$me['id'];

// Target recipient ID (null means Global group chat room)
$targetId = isset($_GET['chat_with']) ? (int)$_GET['chat_with'] : null;

$msg = '';

// Handle Send Message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_msg'])) {
    $text = trim($_POST['message'] ?? '');
    if ($text !== '') {
        $stmt = $pdo->prepare("INSERT INTO internal_messages (sender_id, receiver_id, message) VALUES (:sender, :receiver, :text)");
        $stmt->execute([
            ':sender'   => $myId,
            ':receiver' => $targetId ?: null,
            ':text'     => $text
        ]);
        header('Location: internal_chats.php?chat_with=' . ($targetId ?: '') . '#latest');
        exit;
    }
}

// Fetch all staff users for sidebar
$staffMembers = $pdo->query("SELECT id, username, role FROM staff_users ORDER BY role, id ASC")->fetchAll();

// Fetch Messages
if ($targetId === null) {
    // Group Chat Room
    $messages = $pdo->query("
        SELECT m.*, s.username AS sender_name, s.role AS sender_role
        FROM internal_messages m
        JOIN staff_users s ON m.sender_id = s.id
        WHERE m.receiver_id IS NULL
        ORDER BY m.created_at ASC
    ")->fetchAll();
    $chatTitle = '💬 Global Teammate Room';
} else {
    // Direct Messaging Thread
    $stmt = $pdo->prepare("
        SELECT m.*, s.username AS sender_name, s.role AS sender_role
        FROM internal_messages m
        JOIN staff_users s ON m.sender_id = s.id
        WHERE (m.sender_id = :myId AND m.receiver_id = :targetId)
           OR (m.sender_id = :targetId AND m.receiver_id = :myId)
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([':myId' => $myId, ':targetId' => $targetId]);
    $messages = $stmt->fetchAll();
    
    // Recipient Name
    $rs = $pdo->prepare("SELECT username FROM staff_users WHERE id = ?");
    $rs->execute([$targetId]);
    $recipientName = $rs->fetchColumn();
    $chatTitle = '👤 Direct Chat: ' . htmlspecialchars($recipientName);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Teammate Chats — Fast Site Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Oswald:wght@600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>

<?php include 'nav.php'; ?>

<div class="chat-layout">
  <!-- SIDEBAR: List Teammates -->
  <div class="sidebar">
    <div class="sidebar-header">📍 Teammate Channels</div>
    
    <a href="internal_chats.php" class="sidebar-link <?= $targetId === null ? 'active' : '' ?>">
      <span>💬 Global Group Room</span>
      <span class="sidebar-role">PUBLIC</span>
    </a>
    
    <div class="sidebar-header" style="border-top: 1px solid rgba(255,255,255,0.04);">👥 Direct Message</div>
    <?php foreach ($staffMembers as $member): ?>
      <?php if ($member['id'] !== $myId): ?>
        <a href="internal_chats.php?chat_with=<?= $member['id'] ?>" class="sidebar-link <?= $targetId === $member['id'] ? 'active' : '' ?>">
          <span>👤 <?= htmlspecialchars($member['username']) ?></span>
          <span class="sidebar-role"><?= strtoupper($member['role']) ?></span>
        </a>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>
  
  <!-- MAIN CHAT PANEL -->
  <div class="chat-pane">
    <div class="chat-pane-header"><?= $chatTitle ?></div>
    
    <div class="message-container" id="msg-container">
      <?php if (empty($messages)): ?>
        <div style="text-align:center; margin:auto; color:var(--muted); font-size:0.85rem;">
          👋 No messages in this thread yet. Send a message to start the conversation!
        </div>
      <?php else: ?>
        <?php foreach ($messages as $idx => $m): ?>
          <?php 
            $isMe = $m['sender_id'] === $myId; 
            $isLast = ($idx === count($messages) - 1);
            $anchor = $isLast ? 'id="latest"' : '';
          ?>
          <div class="msg-bubble <?= $isMe ? 'me' : '' ?>" <?= $anchor ?>>
            <div class="msg-meta <?= $isMe ? 'me' : '' ?>">
              <span><?= htmlspecialchars($m['sender_name']) ?> <span style="font-size:0.55rem; opacity:0.6;">(<?= strtoupper($m['sender_role']) ?>)</span></span>
            </div>
            <div class="msg-text"><?= htmlspecialchars($m['message']) ?></div>
            <span class="msg-time"><?= date('h:i A, d M', strtotime($m['created_at'])) ?></span>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
    
    <!-- Send form box -->
    <div class="msg-form-box">
      <form class="msg-form" method="POST" action="">
        <textarea name="message" id="message-text" placeholder="Type a teammate message..." required></textarea>
        <button type="submit" name="send_msg" class="send-btn">Send ➔</button>
      </form>
    </div>
  </div>
</div>

<script>
// Auto scroll messages container to bottom
window.onload = () => {
  const container = document.getElementById('msg-container');
  container.scrollTop = container.scrollHeight;
  
  // Submit message on Enter key (without shift)
  const tx = document.getElementById('message-text');
  tx.addEventListener('keydown', e => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      tx.form.submit();
    }
  });
};
</script>

</body>
</html>
