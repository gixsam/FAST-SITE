<?php
session_start();
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$conversation_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch all conversations
$conv_query = "
    SELECT c.*, 
           u.name as user_name, 
           p.shop_name as partner_name,
           (SELECT message FROM marketplace_messages WHERE conversation_id = c.id ORDER BY id DESC LIMIT 1) as last_message
    FROM marketplace_conversations c
    LEFT JOIN users u ON c.user_id = u.id
    LEFT JOIN partners p ON c.partner_id = p.id
    ORDER BY c.created_at DESC
";
$conversations = $pdo->query($conv_query)->fetchAll();

$active_conversation = null;
$messages = [];

if ($conversation_id > 0) {
    $stmt = $pdo->prepare("SELECT c.*, u.name as user_name, p.shop_name as partner_name FROM marketplace_conversations c LEFT JOIN users u ON c.user_id = u.id LEFT JOIN partners p ON c.partner_id = p.id WHERE c.id = :cid LIMIT 1");
    $stmt->execute([':cid' => $conversation_id]);
    $active_conversation = $stmt->fetch();
    
    if ($active_conversation) {
        $stmt = $pdo->prepare("SELECT * FROM marketplace_messages WHERE conversation_id = :cid ORDER BY created_at ASC");
        $stmt->execute([':cid' => $conversation_id]);
        $messages = $stmt->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=0"/>
  <title>Admin - Message Logs</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Oswald:wght@600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>

<nav class="top-nav">
  <div>
    <a href="dashboard.php">← ADMIN DASHBOARD</a>
    <span class="brand">MESSAGE LOGS (GOD-MODE)</span>
  </div>
</nav>

<div class="chat-container">
  <!-- Sidebar -->
  <div class="chat-sidebar">
    <div class="sidebar-header">All Marketplace Chats</div>
    <div class="conv-list">
      <?php if (empty($conversations)): ?>
        <div style="padding:1rem; color:var(--muted); font-size:0.8rem;">No conversations found.</div>
      <?php endif; ?>
      <?php foreach ($conversations as $c): ?>
        <?php $is_active = ($c['id'] == $conversation_id); ?>
        <a href="messages.php?id=<?= $c['id'] ?>" class="conv-item <?= $is_active ? 'active' : '' ?>">
          <div class="conv-name"><?= htmlspecialchars($c['user_name']) ?> &harr; <?= htmlspecialchars($c['partner_name']) ?></div>
          <div class="conv-last"><?= htmlspecialchars($c['last_message'] ?: 'Attachment') ?></div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Chat Main -->
  <?php if ($active_conversation): ?>
    <div class="chat-main">
      <div class="god-mode-banner">
        👁️ YOU ARE VIEWING THIS CHAT AS AN ADMIN (READ-ONLY)
      </div>
      <div class="chat-header">
        <div>
          <a href="messages.php" style="color:var(--brand); text-decoration:none; margin-right:0.5rem; display:inline-block;" class="mobile-back">←</a>
          <?= htmlspecialchars($active_conversation['user_name']) ?> (User) &harr; <?= htmlspecialchars($active_conversation['partner_name']) ?> (Shop)
        </div>
      </div>
      
      <div class="messages-view" id="messages-view">
        <?php foreach ($messages as $msg): ?>
          <div class="msg-bubble <?= $msg['sender_type'] ?>">
            <strong style="display:block; margin-bottom:0.3rem; font-size:0.75rem; color: <?= $msg['sender_type'] === 'user' ? 'var(--brand)' : 'var(--green)' ?>;">
              <?= $msg['sender_type'] === 'user' ? htmlspecialchars($active_conversation['user_name']) : htmlspecialchars($active_conversation['partner_name']) ?>
            </strong>
            <?php if ($msg['file_path']): ?>
              <a href="../<?= htmlspecialchars($msg['file_path']) ?>" target="_blank" style="color:inherit; text-decoration:underline;">📎 View Attachment</a><br/>
            <?php endif; ?>
            <?= nl2br(htmlspecialchars($msg['message'])) ?>
            <div class="msg-time"><?= date('M d, h:i A', strtotime($msg['created_at'])) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php else: ?>
    <div class="chat-main" style="align-items:center; justify-content:center; color:var(--muted);">
      <div style="text-align:center;">
        <div style="font-size:3rem; margin-bottom:1rem;">👁️</div>
        <div>Select a conversation to spy on</div>
      </div>
    </div>
  <?php endif; ?>
</div>

<link rel="stylesheet" href="/assets/css/admin.css">

<script>
function scrollToBottom() {
  const view = document.getElementById('messages-view');
  if(view) view.scrollTop = view.scrollHeight;
}
scrollToBottom();
</script>
</body>
</html>
