<?php
session_start();
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /user/login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$is_partner = false;
$partner_id = null;

// Check if user is a partner
if (!empty($_SESSION['partner_id'])) {
    $is_partner = true;
    $partner_id = (int)$_SESSION['partner_id'];
} else {
    try {
        $uStmt = $pdo->prepare("SELECT phone FROM users WHERE id = :uid LIMIT 1");
        $uStmt->execute([':uid' => $user_id]);
        $uPhone = $uStmt->fetchColumn();
        if ($uPhone) {
            $stmt = $pdo->prepare("SELECT id FROM partners WHERE phone = :phone LIMIT 1");
            $stmt->execute([':phone' => $uPhone]);
            $partnerRow = $stmt->fetch();
            if ($partnerRow) {
                $is_partner = true;
                $partner_id = (int)$partnerRow['id'];
            }
        }
    } catch (Exception $e) {}
}

$conversation_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$start_partner_id = isset($_GET['partner_id']) ? (int)$_GET['partner_id'] : 0;

if ($start_partner_id > 0) {
    // Check if conversation exists between current user and target partner
    $stmt = $pdo->prepare("SELECT id FROM marketplace_conversations WHERE user_id = :uid AND partner_id = :pid LIMIT 1");
    $stmt->execute([':uid' => $user_id, ':pid' => $start_partner_id]);
    $conv = $stmt->fetch();
    if ($conv) {
        $conversation_id = (int)$conv['id'];
    } else {
        // Create new conversation
        $now = date('Y-m-d H:i:s');
        $stmt = $pdo->prepare("INSERT INTO marketplace_conversations (user_id, partner_id, created_at) VALUES (:uid, :pid, :cat)");
        $stmt->execute([':uid' => $user_id, ':pid' => $start_partner_id, ':cat' => $now]);
        $conversation_id = (int)$pdo->lastInsertId();
    }
}

// Handle File Upload & Message Submission via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_message') {
    $cid = (int)$_POST['conversation_id'];
    $message = trim($_POST['message'] ?? '');
    
    // Verify participation
    $stmt = $pdo->prepare("SELECT * FROM marketplace_conversations WHERE id = :cid LIMIT 1");
    $stmt->execute([':cid' => $cid]);
    $conv = $stmt->fetch();
    
    if (!$conv || ($conv['user_id'] != $user_id && $conv['partner_id'] != $partner_id)) {
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }

    $sender_type = ($conv['partner_id'] == $partner_id) ? 'partner' : 'user';
    $sender_id = ($sender_type === 'partner') ? $partner_id : $user_id;

    $file_path = null;
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/messages/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $filename = handleSecureUpload($_FILES['attachment'], $upload_dir, ['jpg','jpeg','png','webp','pdf','doc','docx','xls','xlsx','zip','rar','mp3','wav','ogg','m4a','webm'], 'msg');
        if ($filename) {
            $file_path = 'uploads/messages/' . $filename;
        }
    }

    if ($message === '' && !$file_path) {
        echo json_encode(['success' => false, 'error' => 'Empty message']);
        exit;
    }

    $now = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare("INSERT INTO marketplace_messages (conversation_id, sender_type, sender_id, message, file_path, created_at) VALUES (:cid, :stype, :sid, :msg, :file, :cat)");
    $stmt->execute([
        ':cid' => $cid,
        ':stype' => $sender_type,
        ':sid' => $sender_id,
        ':msg' => $message,
        ':file' => $file_path,
        ':cat' => $now
    ]);
    
    echo json_encode(['success' => true]);
    exit;
}

// Fetch conversations
$conv_query = "
    SELECT c.*, 
           u.name as user_name, 
           COALESCE(p.business_name, p.owner_name, 'Partner Shop') as partner_name,
           p.profile_pic as partner_pic,
           (SELECT message FROM marketplace_messages WHERE conversation_id = c.id ORDER BY id DESC LIMIT 1) as last_message,
           (SELECT created_at FROM marketplace_messages WHERE conversation_id = c.id ORDER BY id DESC LIMIT 1) as last_message_time
    FROM marketplace_conversations c
    LEFT JOIN users u ON c.user_id = u.id
    LEFT JOIN partners p ON c.partner_id = p.id
    WHERE c.user_id = :uid " . ($is_partner ? "OR c.partner_id = :pid" : "") . "
    ORDER BY COALESCE((SELECT id FROM marketplace_messages WHERE conversation_id = c.id ORDER BY id DESC LIMIT 1), c.id) DESC
";

$stmt = $pdo->prepare($conv_query);
$params = [':uid' => $user_id];
if ($is_partner) $params[':pid'] = $partner_id;
$stmt->execute($params);
$conversations = $stmt->fetchAll();

// Fetch active shops for "Start New Chat" modal
$active_shops = [];
try {
    $shop_stmt = $pdo->query("SELECT id, business_name, owner_name, profile_pic FROM partners WHERE status = 'approved' ORDER BY business_name ASC LIMIT 50");
    if ($shop_stmt) {
        $active_shops = $shop_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {}

$active_conversation = null;
$messages = [];

if ($conversation_id > 0) {
    $stmt = $pdo->prepare("SELECT c.*, COALESCE(p.business_name, p.owner_name, 'Partner Shop') as partner_name, u.name as user_name FROM marketplace_conversations c LEFT JOIN partners p ON c.partner_id = p.id LEFT JOIN users u ON c.user_id = u.id WHERE c.id = :cid LIMIT 1");
    $stmt->execute([':cid' => $conversation_id]);
    $active_conversation = $stmt->fetch();
    
    if ($active_conversation && ($active_conversation['user_id'] == $user_id || $active_conversation['partner_id'] == $partner_id)) {
        // Mark as read
        $read_type = ($active_conversation['partner_id'] == $partner_id) ? 'user' : 'partner';
        $pdo->prepare("UPDATE marketplace_messages SET is_read = 1 WHERE conversation_id = :cid AND sender_type = :stype")->execute([':cid' => $conversation_id, ':stype' => $read_type]);
        
        $stmt = $pdo->prepare("SELECT * FROM marketplace_messages WHERE conversation_id = :cid ORDER BY created_at ASC");
        $stmt->execute([':cid' => $conversation_id]);
        $messages = $stmt->fetchAll();
    } else {
        $active_conversation = null;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=0"/>
  <title>Messages — Fast Site</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Oswald:wght@600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="/assets/css/user.css">
  <style>
    .chat-container {
      display: flex;
      height: calc(100vh - 70px);
      background: #08080c;
      overflow: hidden;
    }
    .chat-sidebar {
      width: 320px;
      background: rgba(13, 13, 20, 0.95);
      border-right: 1px solid rgba(255, 255, 255, 0.08);
      display: flex;
      flex-direction: column;
      flex-shrink: 0;
    }
    .chat-sidebar-header {
      padding: 1.2rem 1rem;
      border-bottom: 1px solid rgba(255, 255, 255, 0.08);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .conv-list {
      flex: 1;
      overflow-y: auto;
    }
    .conv-item {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 1rem;
      border-bottom: 1px solid rgba(255, 255, 255, 0.03);
      text-decoration: none;
      color: inherit;
      transition: background 0.2s;
    }
    .conv-item:hover, .conv-item.active {
      background: rgba(33, 150, 243, 0.12);
      border-left: 3px solid var(--brand);
    }
    .conv-avatar {
      width: 42px;
      height: 42px;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.1);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.2rem;
      flex-shrink: 0;
      overflow: hidden;
    }
    .conv-details {
      flex: 1;
      min-width: 0;
    }
    .conv-name {
      font-size: 0.9rem;
      font-weight: 700;
      color: #fff;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      margin-bottom: 2px;
    }
    .conv-last {
      font-size: 0.75rem;
      color: var(--muted);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .chat-main {
      flex: 1;
      display: flex;
      flex-direction: column;
      background: #0a0a10;
      position: relative;
    }
    .safe-trade-banner {
      background: rgba(252, 185, 0, 0.1);
      border-bottom: 1px solid rgba(252, 185, 0, 0.2);
      color: #fcb900;
      padding: 0.6rem 1rem;
      font-size: 0.75rem;
      text-align: center;
      line-height: 1.4;
    }
    .chat-header {
      padding: 1rem 1.5rem;
      border-bottom: 1px solid rgba(255, 255, 255, 0.08);
      background: rgba(13, 13, 20, 0.8);
      backdrop-filter: blur(10px);
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 1.1rem;
      font-weight: 700;
      color: #fff;
    }
    .messages-view {
      flex: 1;
      overflow-y: auto;
      padding: 1.5rem;
      display: flex;
      flex-direction: column;
      gap: 1rem;
    }
    .msg-bubble {
      max-width: 75%;
      padding: 0.8rem 1.2rem;
      border-radius: 16px;
      font-size: 0.9rem;
      line-height: 1.5;
      position: relative;
      word-wrap: break-word;
    }
    .msg-bubble.sent {
      align-self: flex-end;
      background: linear-gradient(135deg, var(--brand), #007bb5);
      color: #fff;
      border-bottom-right-radius: 4px;
    }
    .msg-bubble.received {
      align-self: flex-start;
      background: rgba(255, 255, 255, 0.06);
      border: 1px solid rgba(255, 255, 255, 0.08);
      color: #f0f0f0;
      border-bottom-left-radius: 4px;
    }
    .msg-time {
      font-size: 0.65rem;
      opacity: 0.7;
      margin-top: 4px;
      text-align: right;
    }
    .chat-input-area {
      padding: 1rem 1.5rem;
      background: rgba(13, 13, 20, 0.95);
      border-top: 1px solid rgba(255, 255, 255, 0.08);
    }
    .chat-input {
      flex: 1;
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.12);
      border-radius: 30px;
      padding: 0.8rem 1.2rem;
      color: #fff;
      font-size: 0.9rem;
      outline: none;
    }
    .chat-input:focus {
      border-color: var(--brand);
      box-shadow: 0 0 10px rgba(33, 150, 243, 0.3);
    }
    .btn-attach {
      background: rgba(255, 255, 255, 0.08);
      border: 1px solid rgba(255, 255, 255, 0.15);
      color: #fff;
      width: 42px;
      height: 42px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      font-size: 1.1rem;
      flex-shrink: 0;
    }
    .btn-send {
      background: linear-gradient(135deg, var(--brand), #007bb5);
      color: #fff;
      border: none;
      border-radius: 30px;
      padding: 0 1.5rem;
      font-weight: 800;
      cursor: pointer;
      font-size: 0.85rem;
      letter-spacing: 0.5px;
      box-shadow: 0 4px 15px rgba(33, 150, 243, 0.4);
    }

    /* Modal for New Chat */
    .shop-modal-overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.75);
      backdrop-filter: blur(8px);
      z-index: 9999;
      align-items: center;
      justify-content: center;
    }
    .shop-modal-overlay.active {
      display: flex;
    }
    .shop-modal-card {
      background: #0d0d15;
      border: 1px solid rgba(33, 150, 243, 0.3);
      border-radius: 18px;
      width: 90%;
      max-width: 480px;
      max-height: 80vh;
      display: flex;
      flex-direction: column;
      box-shadow: 0 20px 50px rgba(0, 0, 0, 0.7);
      overflow: hidden;
    }
    .shop-modal-header {
      padding: 1.2rem 1.5rem;
      border-bottom: 1px solid rgba(255, 255, 255, 0.08);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .shop-list-item {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 0.9rem 1.2rem;
      border-bottom: 1px solid rgba(255, 255, 255, 0.04);
      text-decoration: none;
      color: #fff;
      transition: background 0.2s;
    }
    .shop-list-item:hover {
      background: rgba(33, 150, 243, 0.15);
    }

    @media (max-width: 768px) {
      .chat-sidebar {
        width: 100%;
        display: <?= $active_conversation ? 'none' : 'flex' ?>;
      }
      .chat-main {
        display: <?= $active_conversation ? 'flex' : 'none' ?>;
      }
    }
  </style>
</head>
<body class="dashboard-mode">

<?php include __DIR__ . '/../includes/user_sidebar.php'; ?>

<div class="chat-container">
  <!-- Sidebar -->
  <div class="chat-sidebar">
    <div class="chat-sidebar-header">
      <h3 style="margin:0; font-size:1.1rem; color:#fff; display:flex; align-items:center; gap:8px;">
        ✉️ Messages
      </h3>
      <button onclick="openNewChatModal()" style="background: linear-gradient(135deg, var(--brand), #007bb5); color:#fff; border:none; padding:0.4rem 0.8rem; border-radius:6px; font-size:0.75rem; font-weight:700; cursor:pointer; width:auto; box-shadow:0 2px 8px rgba(33,150,243,0.3);">
        ➕ New Chat
      </button>
    </div>
    <div class="conv-list">
      <?php if (empty($conversations)): ?>
        <div style="padding:2rem 1rem; text-align:center; color:var(--muted);">
          <div style="font-size:2rem; margin-bottom:0.5rem; opacity:0.6;">💬</div>
          <p style="font-size:0.85rem; margin-bottom:1rem;">No conversations yet.</p>
          <button onclick="openNewChatModal()" style="background:rgba(33,150,243,0.15); border:1px solid var(--brand); color:var(--brand); padding:0.5rem 1rem; border-radius:8px; font-size:0.8rem; font-weight:700; cursor:pointer; width:auto;">
            Start Conversation
          </button>
        </div>
      <?php else: ?>
        <?php foreach ($conversations as $c): ?>
          <?php 
            $is_active = ($c['id'] == $conversation_id);
            $display_name = ($is_partner && $c['partner_id'] == $partner_id) ? $c['user_name'] : $c['partner_name'];
            $pic = !empty($c['partner_pic']) ? '/' . ltrim($c['partner_pic'], '/') : '/assets/images/default_avatar.png';
          ?>
          <a href="messages.php?id=<?= $c['id'] ?>" class="conv-item <?= $is_active ? 'active' : '' ?>">
            <div class="conv-avatar">
              <img src="<?= htmlspecialchars($pic) ?>" alt="" style="width:100%; height:100%; object-fit:cover;" onerror="this.onerror=null; this.src='/assets/images/default_avatar.png';">
            </div>
            <div class="conv-details">
              <div class="conv-name"><?= htmlspecialchars($display_name) ?></div>
              <div class="conv-last"><?= htmlspecialchars($c['last_message'] ?: 'Attachment') ?></div>
            </div>
          </a>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- Chat Main -->
  <?php if ($active_conversation): ?>
    <?php 
      $chat_title = ($is_partner && $active_conversation['partner_id'] == $partner_id) ? "Chat with " . htmlspecialchars($active_conversation['user_name'] ?? 'User') : "Chat with " . htmlspecialchars($active_conversation['partner_name'] ?? 'Shop'); 
    ?>
    <div class="chat-main">
      <div class="safe-trade-banner">
        ⚠️ <strong>SECURITY ALERT:</strong> Do NOT make transactions outside of Fast Site. Never share bKash/Nagad numbers in chat. ALL payments must be made using Fast Site Coins.
      </div>
      <div class="chat-header">
        <a href="messages.php" style="color:var(--brand); text-decoration:none; margin-right:0.5rem; display:inline-block; font-size:1.2rem;" class="mobile-back">←</a>
        <span><?= $chat_title ?></span>
      </div>
      
      <div class="messages-view" id="messages-view">
        <?php if(empty($messages)): ?>
          <div style="text-align:center; padding:3rem 1rem; color:var(--muted);">
            <div style="font-size:2.5rem; margin-bottom:0.5rem;">👋</div>
            <p style="font-size:0.9rem; color:#fff; font-weight:600;">Conversation started</p>
            <p style="font-size:0.8rem;">Send your first message below to start chatting!</p>
          </div>
        <?php else: ?>
          <?php foreach ($messages as $msg): ?>
            <?php 
              $is_sender = false;
              if ($is_partner && $msg['sender_type'] === 'partner' && $msg['sender_id'] == $partner_id) $is_sender = true;
              if (!$is_partner && $msg['sender_type'] === 'user' && $msg['sender_id'] == $user_id) $is_sender = true;
            ?>
            <div class="msg-bubble <?= $is_sender ? 'sent' : 'received' ?>">
              <?php if ($msg['file_path']): ?>
                <a href="../<?= htmlspecialchars($msg['file_path']) ?>" target="_blank" style="color:inherit; text-decoration:underline; display:inline-flex; align-items:center; gap:4px; font-weight:700; margin-bottom:4px;">📎 View Attachment</a><br/>
              <?php endif; ?>
              <?= nl2br(htmlspecialchars($msg['message'])) ?>
              <div class="msg-time"><?= date('h:i A', strtotime($msg['created_at'])) ?></div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <div class="chat-input-area">
        <form id="chat-form" style="display:flex; width:100%; gap:0.6rem;" onsubmit="sendMessage(event)">
          <input type="hidden" id="cid" value="<?= $active_conversation['id'] ?>" />
          <input type="file" id="attachment" style="display:none" />
          <button type="button" class="btn-attach" onclick="document.getElementById('attachment').click()" title="Attach File">📎</button>
          <input type="text" id="msg-text" class="chat-input" placeholder="Type your message..." autocomplete="off" />
          <button type="submit" class="btn-send">SEND</button>
        </form>
      </div>
    </div>
  <?php else: ?>
    <!-- Enhanced Empty State with Direct Actions to Start Conversation -->
    <div class="chat-main" style="align-items:center; justify-content:center; padding:2rem; text-align:center;">
      <div style="max-width: 420px; background:rgba(20,20,31,0.8); border:1px solid rgba(255,255,255,0.08); padding:2.5rem 2rem; border-radius:20px; box-shadow:0 10px 40px rgba(0,0,0,0.5);">
        <div style="font-size:3.5rem; margin-bottom:1rem; filter:drop-shadow(0 4px 15px rgba(33,150,243,0.4));">💬</div>
        <h2 style="color:#fff; font-size:1.3rem; font-weight:800; margin-bottom:0.5rem;">Connect & Chat with Sellers</h2>
        <p style="color:var(--muted); font-size:0.85rem; line-height:1.6; margin-bottom:1.8rem;">
          Start a direct conversation with any verified shop or seller to discuss orders, request customized services, or receive quick support.
        </p>

        <div style="display:flex; flex-direction:column; gap:0.8rem;">
          <button onclick="openNewChatModal()" class="btn" style="background:linear-gradient(135deg, var(--brand), #007bb5); color:#fff; font-weight:800; padding:0.8rem; border-radius:10px; font-size:0.9rem; display:flex; align-items:center; justify-content:center; gap:8px;">
            <span>➕</span> Start New Chat with a Shop
          </button>
          <a href="../index.php" class="btn" style="background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.12); color:#fff; text-decoration:none; font-weight:700; padding:0.8rem; border-radius:10px; font-size:0.9rem; display:flex; align-items:center; justify-content:center; gap:8px;">
            <span>🛍️</span> Browse Marketplace
          </a>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>

<!-- Modal: Start New Chat -->
<div class="shop-modal-overlay" id="newChatModal" onclick="if(event.target===this) closeNewChatModal()">
  <div class="shop-modal-card">
    <div class="shop-modal-header">
      <h3 style="margin:0; color:#fff; font-size:1.1rem;">Select a Shop to Message</h3>
      <button onclick="closeNewChatModal()" style="background:none; border:none; color:var(--muted); font-size:1.4rem; cursor:pointer; width:auto; padding:0;">&times;</button>
    </div>
    <div style="padding:1rem; border-bottom:1px solid rgba(255,255,255,0.06);">
      <input type="text" id="shopSearchInput" placeholder="Search shop name..." oninput="filterShops(this.value)" style="width:100%; background:#050508; border:1px solid rgba(255,255,255,0.1); border-radius:8px; padding:0.6rem 1rem; color:#fff; font-size:0.85rem; outline:none;" />
    </div>
    <div style="flex:1; overflow-y:auto;" id="shopListContainer">
      <?php if (empty($active_shops)): ?>
        <div style="padding:2rem; text-align:center; color:var(--muted); font-size:0.85rem;">
          No sellers found at this time. Browse the <a href="../index.php" style="color:var(--brand);">marketplace</a> to find products and message sellers directly!
        </div>
      <?php else: ?>
        <?php foreach ($active_shops as $s): ?>
          <?php $spic = !empty($s['profile_pic']) ? '/' . ltrim($s['profile_pic'], '/') : '/assets/images/default_avatar.png'; ?>
          <a href="messages.php?partner_id=<?= $s['id'] ?>" class="shop-list-item" data-name="<?= strtolower(htmlspecialchars($s['business_name'])) ?>">
            <div style="width:38px; height:38px; border-radius:50%; overflow:hidden; background:#111; flex-shrink:0; border:1px solid var(--border);">
              <img src="<?= htmlspecialchars($spic) ?>" alt="" style="width:100%; height:100%; object-fit:cover;" onerror="this.onerror=null; this.src='/assets/images/default_avatar.png';">
            </div>
            <div style="flex:1; text-align:left;">
              <div style="font-size:0.9rem; font-weight:700; color:#fff;"><?= htmlspecialchars($s['business_name']) ?></div>
              <div style="font-size:0.75rem; color:var(--muted);">Owner: <?= htmlspecialchars($s['owner_name']) ?></div>
            </div>
            <span style="font-size:0.75rem; color:var(--brand); font-weight:700;">Chat 💬</span>
          </a>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
function scrollToBottom() {
  const view = document.getElementById('messages-view');
  if(view) view.scrollTop = view.scrollHeight;
}
scrollToBottom();

function openNewChatModal() {
  document.getElementById('newChatModal').classList.add('active');
}
function closeNewChatModal() {
  document.getElementById('newChatModal').classList.remove('active');
}

function filterShops(val) {
  const q = val.toLowerCase().trim();
  const items = document.querySelectorAll('.shop-list-item');
  items.forEach(item => {
    const name = item.getAttribute('data-name') || '';
    if (name.includes(q)) {
      item.style.display = 'flex';
    } else {
      item.style.display = 'none';
    }
  });
}

async function sendMessage(e) {
  e.preventDefault();
  const text = document.getElementById('msg-text').value;
  const file = document.getElementById('attachment').files[0] || null;
  const cid = document.getElementById('cid').value;
  
  if (!text.trim() && !file) return;

  const formData = new FormData();
  formData.append('action', 'send_message');
  formData.append('conversation_id', cid);
  formData.append('message', text);
  if (file) formData.append('attachment', file);

  document.getElementById('msg-text').value = '';
  document.getElementById('attachment').value = '';

  try {
    const res = await fetch('messages.php', { method: 'POST', body: formData });
    const data = await res.json();
    if (data.success) {
      window.location.reload(); 
    } else {
      alert("Error: " + data.error);
    }
  } catch (err) {
    console.error(err);
  }
}
</script>
</body>
</html>
