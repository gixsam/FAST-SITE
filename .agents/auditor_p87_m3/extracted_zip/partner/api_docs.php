<?php
// =========================================================================
// partner/api_docs.php  –  API Documentation & Credentials for Dropshipping
// =========================================================================
session_start();
if (!isset($_SESSION['partner_id'])) {
    header('Location: /user/login.php');
    exit;
}
require_once __DIR__ . '/../config.php';
require_once 'nav.php';

$msg = $err = '';
$partner_id = $_SESSION['partner_id'];

// Self-healing migration: Add partner_id to api_partners if not exists
try {
    $pdo->exec("ALTER TABLE api_partners ADD COLUMN partner_id INT DEFAULT NULL");
} catch (Exception $e) {}

// Handle API Key generation/update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'generate_key') {
        $webhook = trim($_POST['webhook_url'] ?? '');
        
        // Check if partner already has an API partner record
        $stmt = $pdo->prepare("SELECT * FROM api_partners WHERE partner_id = :pid LIMIT 1");
        $stmt->execute([':pid' => $partner_id]);
        $apiPartner = $stmt->fetch();
        
        if ($apiPartner) {
            // Update existing record
            if (isset($_POST['regenerate'])) {
                $newKey = 'fs_partner_' . bin2hex(random_bytes(16));
                $up = $pdo->prepare("UPDATE api_partners SET api_key = :key, webhook_url = :webhook WHERE id = :id");
                $up->execute([':key' => $newKey, ':webhook' => $webhook, ':id' => $apiPartner['id']]);
                $msg = "API Key regenerated and webhook updated successfully!";
            } else {
                $up = $pdo->prepare("UPDATE api_partners SET webhook_url = :webhook WHERE id = :id");
                $up->execute([':webhook' => $webhook, ':id' => $apiPartner['id']]);
                $msg = "Webhook URL updated successfully!";
            }
        } else {
            // Create a new record
            $newKey = 'fs_partner_' . bin2hex(random_bytes(16));
            $ins = $pdo->prepare("INSERT INTO api_partners (name, api_key, webhook_url, partner_id, status) VALUES (:name, :key, :webhook, :pid, 'active')");
            $ins->execute([
                ':name' => 'Partner Shop: ' . ($partner['business_name'] ?? 'Unknown'),
                ':key' => $newKey,
                ':webhook' => $webhook,
                ':pid' => $partner_id
            ]);
            $msg = "API Key generated successfully!";
        }
    }
}

// Fetch current API credentials
$stmt = $pdo->prepare("SELECT * FROM api_partners WHERE partner_id = :pid LIMIT 1");
$stmt->execute([':pid' => $partner_id]);
$apiPartner = $stmt->fetch();
$apiKey = $apiPartner ? $apiPartner['api_key'] : '';
$webhookUrl = $apiPartner ? $apiPartner['webhook_url'] : '';

// Get Base URL for API documentation
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$apiBaseUrl = $protocol . $_SERVER['HTTP_HOST'] . str_replace('partner/api_docs.php', 'api/', $_SERVER['REQUEST_URI']);
?>
<div class="content-wrapper">
  
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:2rem; flex-wrap:wrap; gap:1rem;">
    <div>
      <h1 style="font-family:'Oswald', sans-serif; font-size:2rem; text-transform:uppercase; letter-spacing:1px;">🔌 API & Dropshipping Center</h1>
      <p style="color:var(--muted); font-size:0.9rem; margin-top:0.3rem;">Integrate your webstore or ordering system directly with the Fast Site storefront.</p>
    </div>
  </div>

  <?php if ($msg): ?><div style="background:rgba(0,230,118,0.1); border-left:4px solid var(--green); padding:1rem; border-radius:8px; margin-bottom:1.5rem; color:#fff; font-size:0.9rem;">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if ($err): ?><div style="background:rgba(255,82,82,0.1); border-left:4px solid var(--red); padding:1rem; border-radius:8px; margin-bottom:1.5rem; color:#fff; font-size:0.9rem;">❌ <?= htmlspecialchars($err) ?></div><?php endif; ?>

  <div style="display:grid; grid-template-columns: 1fr 1.5fr; gap:2rem; align-items:start;">
    
    <!-- LEFT: Credentials Manager Card -->
    <div style="background:var(--dark-card); border:1px solid var(--border); border-radius:16px; padding:1.5rem; box-shadow:0 10px 30px rgba(0,0,0,0.3);">
      <h3 style="font-size:1.15rem; font-weight:700; border-bottom:1px solid rgba(252,185,0,0.15); padding-bottom:0.8rem; margin-bottom:1.2rem; color:var(--brand);">🔑 API Credentials</h3>
      
      <form method="POST">
        <input type="hidden" name="action" value="generate_key"/>
        
        <div style="margin-bottom:1.2rem;">
          <label style="display:block; font-size:0.8rem; color:var(--muted); text-transform:uppercase; font-weight:700; margin-bottom:0.4rem;">Your API Token</label>
          <?php if ($apiKey): ?>
            <div style="display:flex; gap:0.5rem; align-items:center;">
              <input type="password" id="api-key-input" readonly value="<?= htmlspecialchars($apiKey) ?>" style="flex-grow:1; background:#111; border:1px solid rgba(255,255,255,0.08); padding:0.7rem; border-radius:8px; color:#fff; font-family:monospace; font-size:0.85rem;"/>
              <button type="button" onclick="toggleApiKeyVisibility()" style="background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); color:#fff; padding:0.7rem; border-radius:8px; cursor:pointer;">👁️</button>
            </div>
            <p style="font-size:0.7rem; color:var(--muted); margin-top:0.4rem;">Keep this token secret. If compromised, regenerate it below.</p>
          <?php else: ?>
            <div style="background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.06); padding:1rem; border-radius:8px; text-align:center; color:var(--muted); font-size:0.85rem;">
              No API key generated yet.
            </div>
          <?php endif; ?>
        </div>

        <div style="margin-bottom:1.5rem;">
          <label style="display:block; font-size:0.8rem; color:var(--muted); text-transform:uppercase; font-weight:700; margin-bottom:0.4rem;">Webhook Endpoint URL</label>
          <input type="url" name="webhook_url" value="<?= htmlspecialchars($webhookUrl) ?>" placeholder="https://yourdomain.com/webhooks/fastsite" style="width:100%; background:#111; border:1px solid rgba(255,255,255,0.08); padding:0.7rem; border-radius:8px; color:#fff; font-size:0.85rem;"/>
          <p style="font-size:0.7rem; color:var(--muted); margin-top:0.4rem;">We will send POST requests here whenever orders change status.</p>
        </div>

        <?php if ($apiKey): ?>
          <button type="submit" class="btn" style="width:100%; font-weight:700; margin-bottom:0.6rem;">💾 Save Webhook URL</button>
          <button type="submit" name="regenerate" value="1" onclick="return confirm('WARNING: Regenerating your token will break any current API connections. Continue?');" style="width:100%; padding:0.7rem; background:none; border:1px solid rgba(255,82,82,0.3); color:var(--red); border-radius:8px; cursor:pointer; font-weight:600; font-size:0.85rem;">🔄 Regenerate API Key</button>
        <?php else: ?>
          <button type="submit" class="btn" style="width:100%; font-weight:700;">🔑 Generate API Credentials</button>
        <?php endif; ?>
      </form>
    </div>

    <!-- RIGHT: API Documentation Card -->
    <div style="background:var(--dark-card); border:1px solid var(--border); border-radius:16px; padding:1.5rem; box-shadow:0 10px 30px rgba(0,0,0,0.3); overflow:hidden;">
      <h3 style="font-size:1.15rem; font-weight:700; border-bottom:1px solid rgba(252,185,0,0.15); padding-bottom:0.8rem; margin-bottom:1.2rem; color:var(--brand);">📖 Integration Documentation</h3>
      
      <div style="font-size:0.88rem; line-height:1.6; color:#b0b0d0;">
        <p>Our REST API allows you to programmatically query our services and submit dropshipping orders directly from your web application.</p>
        
        <h4 style="color:#fff; margin-top:1.5rem; margin-bottom:0.5rem; font-size:0.95rem; font-weight:700;">📡 Base Endpoint</h4>
        <div style="background:#111; padding:0.6rem; border-radius:8px; font-family:monospace; font-size:0.8rem; color:var(--brand); overflow-x:auto; margin-bottom:1rem;">
          <?= htmlspecialchars($apiBaseUrl) ?>
        </div>

        <h4 style="color:#fff; margin-top:1.5rem; margin-bottom:0.5rem; font-size:0.95rem; font-weight:700;">🔑 Authentication</h4>
        <p>Send your unique API token in the request header:</p>
        <div style="background:#111; padding:0.6rem; border-radius:8px; font-family:monospace; font-size:0.8rem; color:#fff; margin-bottom:1rem;">
          X-API-KEY: your_api_token_here
        </div>

        <hr style="border:none; border-top:1px solid rgba(255,255,255,0.06); margin:1.5rem 0;"/>

        <!-- API Method 1 -->
        <h3 style="color:#fff; font-size:0.95rem; display:flex; align-items:center; gap:0.5rem; margin-bottom:0.5rem;">
          <span style="background:var(--green); color:#000; font-size:0.7rem; font-weight:800; padding:0.15rem 0.4rem; border-radius:4px;">GET</span>
          <code>/services.php</code>
        </h3>
        <p style="margin-bottom:0.8rem;">Retrieve the catalog of services with their categories and fees.</p>
        <div style="background:#111; padding:0.8rem; border-radius:8px; font-family:monospace; font-size:0.75rem; color:#a0d0a0; max-height:150px; overflow-y:auto; margin-bottom:1.5rem;">
<pre>[
  {
    "id": 1,
    "name": "NID Correction",
    "fee": 500.00,
    "section_name": "জাতীয় পরিচয়পত্র সেবা"
  },
  {
    "id": 2,
    "name": "Passport Apply",
    "fee": 1800.00,
    "section_name": "পাসপোর্ট সেবা"
  }
]</pre>
        </div>

        <!-- API Method 2 -->
        <h3 style="color:#fff; font-size:0.95rem; display:flex; align-items:center; gap:0.5rem; margin-bottom:0.5rem;">
          <span style="background:var(--brand); color:#000; font-size:0.7rem; font-weight:800; padding:0.15rem 0.4rem; border-radius:4px;">POST</span>
          <code>/create_order.php</code>
        </h3>
        <p style="margin-bottom:0.8rem;">Submit a dropship application on behalf of your customer.</p>
        <div style="background:#111; padding:0.8rem; border-radius:8px; font-family:monospace; font-size:0.75rem; color:#a0d0a0; margin-bottom:1rem;">
          <strong>Request Payload JSON:</strong>
<pre>{
  "service_id": 1,
  "user_name": "Rahim Uddin",
  "user_phone": "01712345678",
  "details": "NID Corrections on father's name."
}</pre>
        </div>
        <div style="background:#111; padding:0.8rem; border-radius:8px; font-family:monospace; font-size:0.75rem; color:#a0d0a0; margin-bottom:1.5rem;">
          <strong>Response JSON:</strong>
<pre>{
  "success": true,
  "ref": "FS-1745234120",
  "message": "Order created successfully."
}</pre>
        </div>

        <!-- Webhooks Section -->
        <h4 style="color:#fff; margin-top:1.5rem; margin-bottom:0.5rem; font-size:0.95rem; font-weight:700;">🔔 Webhook Delivery Payload</h4>
        <p>Whenever an order status changes, we will POST the following JSON payload to your Webhook URL:</p>
        <div style="background:#111; padding:0.8rem; border-radius:8px; font-family:monospace; font-size:0.75rem; color:#a0d0a0;">
<pre>{
  "event": "order.status_changed",
  "order_ref": "FS-1745234120",
  "previous_status": "processing",
  "new_status": "completed",
  "notes": "Done by Admin.",
  "timestamp": "2026-06-05 18:22:00"
}</pre>
        </div>

      </div>
    </div>

  </div>

</div>

<script>
function toggleApiKeyVisibility() {
  const input = document.getElementById('api-key-input');
  if (input.type === 'password') {
    input.type = 'text';
  } else {
    input.type = 'password';
  }
}
</script>
</html>
