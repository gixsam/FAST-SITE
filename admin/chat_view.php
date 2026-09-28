<?php
// ============================================================
// admin/chat_view.php  –  Full conversation view + admin reply
// ============================================================

session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php'); exit;
}
require_once __DIR__ . '/../config.php';

$sessionId = trim($_GET['id'] ?? '');
if (!$sessionId) { header('Location: chats.php'); exit; }

$sess = $pdo->prepare("SELECT * FROM `chat_sessions` WHERE `session_id`=:sid");
$sess->execute([':sid' => $sessionId]);
$session = $sess->fetch();
if (!$session) { header('Location: chats.php'); exit; }

// Mark user messages as read
$pdo->prepare("UPDATE `chat_messages` SET `is_read`=1 WHERE `session_id`=:sid AND `sender`='user'")
    ->execute([':sid' => $sessionId]);

// Fetch all messages
$msgs = $pdo->prepare("SELECT * FROM `chat_messages` WHERE `session_id`=:sid ORDER BY `id` ASC");
$msgs->execute([':sid' => $sessionId]);
$messages = $msgs->fetchAll();

$lastId = $messages ? (int)end($messages)['id'] : 0;

function senderLabel(string $sender, string $lang): string {
    return match($sender) {
        'user'    => '👤 User',
        'chatbot' => '🤖 MONU',
        'admin'   => '👨‍💼 SHAMBHI',
        default   => $sender,
    };
}
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Chat — Fast Site Admin</title>
  <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>
<?php include 'nav.php'; ?>

<div class="chat-wrap">
  <!-- Session Info Bar -->
  <div class="session-info">
    <div class="info-item">
      <span class="info-label">Status</span>
      <span class="badge-status <?= $session['status'] === 'with_agent' ? 'badge-agent' : 'badge-bot' ?>">
        <?= $session['status'] === 'with_agent' ? '👨‍💼 With SHAMBHI' : '🤖 BOT-MONU' ?>
      </span>
    </div>
    <div class="info-item">
      <span class="info-label">Service</span>
      <span class="info-val"><?= htmlspecialchars($session['service_identified'] ?? 'Not identified') ?></span>
    </div>
    <div class="info-item">
      <span class="info-label">Language</span>
      <span class="info-val"><?= strtoupper($session['language']) ?></span>
    </div>
    <div class="info-item">
      <span class="info-label">Started</span>
      <span class="info-val"><?= date('d M Y H:i', strtotime($session['created_at'])) ?></span>
    </div>
    <?php if ($session['application_id']): ?>
    <div class="info-item">
      <span class="info-label">Linked Order</span>
      <a class="info-val" href="dashboard.php" style="color:#00e676;">FS-<?= str_pad($session['application_id'],6,'0',STR_PAD_LEFT) ?></a>
    </div>
    <?php endif; ?>
  </div>

  <!-- Messages -->
  <div class="msg-area" id="msg-area">
    <?php foreach ($messages as $m): ?>
    <div class="msg-row from-<?= htmlspecialchars($m['sender']) ?>" data-id="<?= $m['id'] ?>">
      <span class="msg-sender"><?= senderLabel($m['sender'], $session['language']) ?></span>
      <div class="msg-bubble"><?= nl2br(htmlspecialchars($m['message'])) ?></div>
      <span class="msg-time"><?= date('H:i', strtotime($m['created_at'])) ?></span>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Reply Bar -->
  <?php if ($session['status'] === 'with_agent'): ?>
  <div class="reply-bar">
    <textarea id="reply-input" placeholder="Type your reply as SHAMBHI…" rows="2"
      onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();sendAdminReply();}"></textarea>
    <button class="send-reply" id="send-btn" onclick="sendAdminReply()">Send ↑</button>
  </div>
  <?php else: ?>
  <div style="text-align:center;color:#8888aa;font-size:.82rem;padding:.8rem;">
    ⏳ Chat is still with BOT-MONU — reply area will appear after handoff.
  </div>
  <?php endif; ?>
</div>

<button class="new-indicator" id="new-btn" onclick="scrollToBottom()">↓ New messages</button>

<script>
const SESSION_ID = <?= json_encode($sessionId) ?>;
let lastId = <?= $lastId ?>;

const area = document.getElementById('msg-area');
scrollToBottom();

function scrollToBottom() {
  area.scrollTop = area.scrollHeight;
  document.getElementById('new-btn').style.display = 'none';
}

function appendMsg(msg) {
  const labels = { user: '👤 User', chatbot: '🤖 MONU', admin: '👨‍💼 SHAMBHI' };
  const div = document.createElement('div');
  div.className = 'msg-row from-' + msg.sender;
  div.dataset.id = msg.id;
  const t = new Date(msg.created_at).toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'});
  const txt = msg.message.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'<br>');
  div.innerHTML = `<span class="msg-sender">${labels[msg.sender]||msg.sender}</span><div class="msg-bubble">${txt}</div><span class="msg-time">${t}</span>`;
  area.appendChild(div);
  const atBottom = (area.scrollHeight - area.scrollTop - area.clientHeight) < 80;
  if (atBottom) {
    scrollToBottom();
  } else {
    document.getElementById('new-btn').style.display = 'block';
  }
  if (msg.id > lastId) lastId = msg.id;
}

// Poll for new messages every 3 seconds
async function pollMessages() {
  try {
    const r = await fetch(`../chat_poll.php?session_id=${encodeURIComponent(SESSION_ID)}&last_id=${lastId}`);
    const j = await r.json();
    if (j.success && j.messages.length > 0) {
      j.messages.forEach(m => appendMsg(m));
    }
  } catch(e) {}
  setTimeout(pollMessages, 3000);
}
pollMessages();

// Admin reply
async function sendAdminReply() {
  const inp = document.getElementById('reply-input');
  const btn = document.getElementById('send-btn');
  const msg = inp.value.trim();
  if (!msg) return;

  btn.disabled = true;
  btn.textContent = '…';
  inp.value = '';

  try {
    const r = await fetch('chat_reply.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ session_id: SESSION_ID, message: msg })
    });
    const j = await r.json();
    if (j.success) {
      appendMsg({ id: j.id, sender: 'admin', message: msg, created_at: new Date().toISOString() });
    }
  } catch(e) {}
  finally {
    btn.disabled = false;
    btn.textContent = 'Send ↑';
    inp.focus();
  }
}
</script>
</body>
</html>
