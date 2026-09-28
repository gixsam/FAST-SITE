<?php
// =========================================================================
// admin/chats.php — Unified Chat, WhatsApp & Gemini AI Control Hub
// =========================================================================
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php'); exit;
}
require_once __DIR__ . '/../config.php';

$msg = $err = '';

// Handle Master Chat & AI System Toggles Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_chat_settings'])) {
    $settings = [
        'chat_internal_enabled' => isset($_POST['chat_internal_enabled']) ? '1' : '0',
        'chat_whatsapp_enabled' => isset($_POST['chat_whatsapp_enabled']) ? '1' : '0',
        'chat_gemini_enabled'   => isset($_POST['chat_gemini_enabled']) ? '1' : '0',
        'gemini_model'          => trim($_POST['gemini_model'] ?? 'gemini-2.0-flash'),
        'gemini_persona'        => trim($_POST['gemini_persona'] ?? ''),
    ];

    try {
        $stmt = $pdo->prepare("INSERT INTO homepage_settings (setting_key, setting_value) 
            VALUES (:key, :val) 
            ON DUPLICATE KEY UPDATE setting_value = :val");

        foreach ($settings as $k => $v) {
            try {
                $stmt->execute([':key' => $k, ':val' => strval($v)]);
            } catch (Exception $e2) {
                $pdo->prepare("REPLACE INTO homepage_settings (setting_key, setting_value) VALUES (?, ?)")
                    ->execute([$k, strval($v)]);
            }
        }
        $msg = 'Chat and Gemini AI Settings saved successfully!';
    } catch (Exception $e) {
        $err = 'Error saving settings: ' . $e->getMessage();
    }
}

// Fetch sessions needing agent attention
$sessions = $pdo->query("
    SELECT cs.*,
           COUNT(CASE WHEN cm.is_read=0 AND cm.sender='user' THEN 1 END) AS unread,
           MAX(cm.created_at) AS last_msg_at,
           (SELECT cm2.message FROM chat_messages cm2
            WHERE cm2.session_id=cs.session_id
            ORDER BY cm2.id DESC LIMIT 1) AS last_message
    FROM   `chat_sessions` cs
    LEFT JOIN `chat_messages` cm ON cm.session_id = cs.session_id
    GROUP BY cs.session_id
    ORDER BY last_msg_at DESC
")->fetchAll();

$settings_row = $pdo->query("SELECT setting_key, setting_value FROM homepage_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
$internal_enabled = ($settings_row['chat_internal_enabled'] ?? '1') === '1';
$whatsapp_enabled = ($settings_row['chat_whatsapp_enabled'] ?? '1') === '1';
$gemini_enabled   = ($settings_row['chat_gemini_enabled'] ?? '1') === '1';
$gemini_model     = $settings_row['gemini_model'] ?? 'gemini-2.0-flash';
$gemini_persona   = $settings_row['gemini_persona'] ?? 'You are the Fast Site Global Escrow & AI Assistant...';

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Unified Chat & Gemini AI Hub — Fast Site Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Oswald:wght@600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="/assets/css/admin.css?v=<?= time() ?>">
</head>
<body>
<?php include 'nav.php'; ?>

<div class="dashboard-container">

  <!-- Header Admin Hero -->
  <div class="admin-hero">
    <h2 class="admin-hero-title">💬 UNIFIED CHAT & GEMINI AI HUB</h2>
    <p class="admin-hero-subtitle">Manage customer live support, WhatsApp Gateway, and Gemini 2.0 AI Bot settings</p>
    
    <div class="tabs-nav">
      <button class="tab-btn active" onclick="switchTab(event, 'tab-livechat')">💬 Client Live Support (<?= count($sessions) ?>)</button>
      <button class="tab-btn" onclick="switchTab(event, 'tab-whatsapp')">📲 WhatsApp Gateway</button>
      <button class="tab-btn" onclick="switchTab(event, 'tab-gemini')">🤖 Gemini AI Bot (2.0 Flash)</button>
      <button class="tab-btn" onclick="switchTab(event, 'tab-toggles')">⚙️ Master System Toggles</button>
    </div>
  </div>

  <?php if($msg): ?><div style="background:rgba(16,185,129,0.15); border:1px solid #10b981; color:#10b981; padding:0.8rem 1.2rem; border-radius:12px; margin-bottom:1rem; font-weight:700;">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if($err): ?><div style="background:rgba(239,68,68,0.15); border:1px solid #ef4444; color:#ef4444; padding:0.8rem 1.2rem; border-radius:12px; margin-bottom:1rem; font-weight:700;">❌ <?= htmlspecialchars($err) ?></div><?php endif; ?>

  <!-- TAB 1: CLIENT LIVE SUPPORT -->
  <div id="tab-livechat" class="tab-content active">
    <div class="overview-card" style="margin-bottom: 1.5rem;">
      <h3>💬 Active Customer Support Threads</h3>
      <?php if (empty($sessions)): ?>
        <div style="text-align:center; padding:3rem; color:var(--muted);">No active support requests. When users start a chat it will appear here.</div>
      <?php else: ?>
        <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(320px, 1fr)); gap:1rem;">
          <?php foreach ($sessions as $s): ?>
            <div style="background:rgba(8,9,17,0.8); border:1px solid var(--border); border-radius:14px; padding:1.2rem; display:flex; flex-direction:column; gap:0.6rem;">
              <div style="display:flex; justify-content:space-between; align-items:center;">
                <span class="id-badge">ID: <?= htmlspecialchars(substr($s['session_id'],0,12)) ?>...</span>
                <span class="status-capsule" style="background:<?= $s['status']==='with_agent'?'rgba(252,185,0,0.15)':'rgba(99,102,241,0.15)' ?>; color:<?= $s['status']==='with_agent'?'var(--gold)':'#818cf8' ?>;">
                  <?= ucfirst($s['status']) ?>
                </span>
              </div>
              <div style="font-weight:700; color:#fff; font-size:0.95rem;">
                <?= $s['user_name'] ? '👤 '.htmlspecialchars($s['user_name']) : '👤 Visitor' ?>
                <?= $s['user_phone'] ? ' ('.htmlspecialchars($s['user_phone']).')' : '' ?>
              </div>
              <div style="font-size:0.8rem; color:var(--muted); font-style:italic;">
                "<?= htmlspecialchars($s['last_message'] ?? 'No messages') ?>"
              </div>
              <div style="display:flex; justify-content:space-between; align-items:center; margin-top:0.4rem;">
                <span style="font-size:0.75rem; color:var(--muted);"><?= date('d M, h:i A', strtotime($s['last_msg_at'] ?? 'now')) ?></span>
                <a href="chat_view.php?session_id=<?= urlencode($s['session_id']) ?>" class="btn-sm" style="text-decoration:none; padding:0.4rem 0.8rem; font-size:0.78rem;">Open Thread →</a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- TAB 2: WHATSAPP GATEWAY -->
  <div id="tab-whatsapp" class="tab-content">
    <div class="overview-card">
      <h3>📲 WhatsApp Gateway Integration (Twilio / Meta API)</h3>
      <div style="background:rgba(255,255,255,0.03); border:1px solid var(--border); padding:1.2rem; border-radius:12px; margin-bottom:1rem;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.8rem;">
          <strong style="color:var(--gold);">WhatsApp System Status:</strong>
          <span class="status-capsule" style="background:<?= $whatsapp_enabled?'rgba(16,185,129,0.15)':'rgba(239,68,68,0.15)' ?>; color:<?= $whatsapp_enabled?'#10b981':'#ef4444' ?>;">
            <?= $whatsapp_enabled ? '🟢 ONLINE & ACTIVE' : '🔴 OFF' ?>
          </span>
        </div>
        <p style="font-size:0.85rem; color:var(--muted);">
          WhatsApp numbers connected: <strong>+8801337320544</strong> (Sayam Support), <strong>+8801866686524</strong> (Ayra Mart Support), <strong>+8801627127534</strong> (Enzor Support).
        </p>
      </div>
    </div>
  </div>

  <!-- TAB 3: GEMINI AI BOT -->
  <div id="tab-gemini" class="tab-content">
    <div class="overview-card">
      <h3>🤖 Gemini AI Assistant Engine (Gemini 2.0 Flash)</h3>
      <form method="POST" action="chats.php">
        <input type="hidden" name="save_chat_settings" value="1"/>
        <div style="display:flex; flex-direction:column; gap:1rem;">
          <div>
            <label style="font-size:0.82rem; color:var(--muted); font-weight:700; display:block; margin-bottom:0.4rem;">AI Model Selection</label>
            <select name="gemini_model" style="width:100%; background:#080911; color:#fff; border:1px solid var(--border); padding:0.7rem; border-radius:10px; font-weight:700;">
              <option value="gemini-2.0-flash" <?= $gemini_model==='gemini-2.0-flash'?'selected':'' ?>>⚡ Gemini 2.0 Flash (Recommended — Ultra Fast)</option>
              <option value="gemini-1.5-flash" <?= $gemini_model==='gemini-1.5-flash'?'selected':'' ?>>⚡ Gemini 1.5 Flash</option>
              <option value="gemini-1.5-pro" <?= $gemini_model==='gemini-1.5-pro'?'selected':'' ?>>🧠 Gemini 1.5 Pro (Deep Reasoning)</option>
            </select>
          </div>

          <div>
            <label style="font-size:0.82rem; color:var(--muted); font-weight:700; display:block; margin-bottom:0.4rem;">AI System Persona / Prompt</label>
            <textarea name="gemini_persona" rows="4" style="width:100%; background:#080911; color:#fff; border:1px solid var(--border); padding:0.8rem; border-radius:10px; font-size:0.85rem; outline:none;"><?= htmlspecialchars($gemini_persona) ?></textarea>
          </div>

          <button type="submit" class="btn-sm" style="padding:0.7rem;">💾 Save AI Configuration</button>
        </div>
      </form>
    </div>
  </div>

  <!-- TAB 4: MASTER SYSTEM TOGGLES -->
  <div id="tab-toggles" class="tab-content">
    <div class="overview-card">
      <h3>⚙️ 1-Click Master Chat & AI System Toggles</h3>
      <form method="POST" action="chats.php">
        <input type="hidden" name="save_chat_settings" value="1"/>
        <div style="display:flex; flex-direction:column; gap:1rem; margin-bottom:1.5rem;">
          
          <div style="display:flex; justify-content:space-between; align-items:center; background:rgba(255,255,255,0.03); padding:1rem; border-radius:12px; border:1px solid var(--border);">
            <div>
              <strong style="color:#fff; display:block;">💬 Fast Site Internal Live Chat</strong>
              <span style="font-size:0.78rem; color:var(--muted);">Enable or disable customer live chat widget on website</span>
            </div>
            <input type="checkbox" name="chat_internal_enabled" value="1" <?= $internal_enabled?'checked':'' ?> style="transform:scale(1.4); cursor:pointer;"/>
          </div>

          <div style="display:flex; justify-content:space-between; align-items:center; background:rgba(255,255,255,0.03); padding:1rem; border-radius:12px; border:1px solid var(--border);">
            <div>
              <strong style="color:#fff; display:block;">📲 WhatsApp Support Gateway</strong>
              <span style="font-size:0.78rem; color:var(--muted);">Enable or disable direct WhatsApp floating chat launcher</span>
            </div>
            <input type="checkbox" name="chat_whatsapp_enabled" value="1" <?= $whatsapp_enabled?'checked':'' ?> style="transform:scale(1.4); cursor:pointer;"/>
          </div>

          <div style="display:flex; justify-content:space-between; align-items:center; background:rgba(255,255,255,0.03); padding:1rem; border-radius:12px; border:1px solid var(--border);">
            <div>
              <strong style="color:#fff; display:block;">🤖 Gemini AI Assistant Engine (Gemini 2.0 Flash)</strong>
              <span style="font-size:0.78rem; color:var(--muted);">Enable or disable automated AI chatbot responses</span>
            </div>
            <input type="checkbox" name="chat_gemini_enabled" value="1" <?= $gemini_enabled?'checked':'' ?> style="transform:scale(1.4); cursor:pointer;"/>
          </div>

        </div>
        <button type="submit" class="btn-sm" style="padding:0.7rem 1.4rem;">💾 Save System Toggles</button>
      </form>
    </div>
  </div>

</div>

<script>
function switchTab(evt, tabId) {
  const contents = document.querySelectorAll('.tab-content');
  contents.forEach(c => c.classList.remove('active'));
  
  const buttons = document.querySelectorAll('.tab-btn');
  buttons.forEach(b => b.classList.remove('active'));
  
  const target = document.getElementById(tabId);
  if (target) target.classList.add('active');
  if (evt && evt.currentTarget) evt.currentTarget.classList.add('active');
}
</script>
</body>
</html>
