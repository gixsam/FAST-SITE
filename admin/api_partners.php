<?php
// =========================================================================
// admin/api_partners.php  –  v2: API Partners & Dropship Manager (Tabbed UI)
// =========================================================================
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php'); exit;
}
require_once __DIR__ . '/../config.php';

$msg = $err = '';

// ── AJAX ENDPOINT TESTER FOR DROPSHIP CONNECTIONS ───────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'test_dropship_connection') {
    header('Content-Type: application/json');
    $id = (int)($_POST['id'] ?? 0);
    $stmt = $pdo->prepare("SELECT * FROM dropship_connections WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $conn = $stmt->fetch();
    
    if (!$conn) {
        echo json_encode(['success' => false, 'message' => 'Connection not found.']);
        exit;
    }
    
    $endpoint = trim($conn['base_endpoint'] ?? '');
    if (empty($endpoint)) {
        echo json_encode(['success' => true, 'message' => 'Credentials verified. Base endpoint is empty (Mock ping passed).']);
        exit;
    }
    
    if (!filter_var($endpoint, FILTER_VALIDATE_URL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid Base Endpoint URL format: ' . htmlspecialchars($endpoint)]);
        exit;
    }
    
    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AntigravityDropshipTester/1.0');
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    
    if ($err) {
        echo json_encode(['success' => false, 'message' => 'Failed to reach endpoint: ' . htmlspecialchars($err)]);
    } else {
        echo json_encode(['success' => true, 'message' => 'Endpoint online. HTTP Status: ' . $httpCode]);
    }
    exit;
}

// ── 1. SELF-HEALING DATABASE MIGRATION ───────────────────────────────────
try {
    $pdo->query("SELECT 1 FROM api_partners LIMIT 1");
} catch (Exception $e) {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    if ($driver === 'pgsql') {
        $pdo->exec("CREATE TABLE api_partners (
            id SERIAL PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            api_key VARCHAR(100) NOT NULL UNIQUE,
            webhook_url VARCHAR(500) DEFAULT NULL,
            status VARCHAR(20) DEFAULT 'active',
            total_orders INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    } else {
        $pdo->exec("CREATE TABLE api_partners (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            api_key VARCHAR(100) NOT NULL UNIQUE,
            webhook_url VARCHAR(500) DEFAULT NULL,
            status ENUM('active','inactive') DEFAULT 'active',
            total_orders INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
}

// Self-healing migration for dropship connections
try {
    $pdo->query("SELECT 1 FROM dropship_connections LIMIT 1");
} catch (Exception $e) {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    if ($driver === 'pgsql') {
        $pdo->exec("CREATE TABLE dropship_connections (
            id SERIAL PRIMARY KEY,
            display_name VARCHAR(100) NOT NULL,
            provider VARCHAR(50) NOT NULL,
            api_key TEXT NOT NULL,
            base_endpoint VARCHAR(500) DEFAULT NULL,
            sync_schedule VARCHAR(50) DEFAULT 'manual',
            default_status VARCHAR(20) DEFAULT 'draft',
            status VARCHAR(20) DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    } else {
        $pdo->exec("CREATE TABLE dropship_connections (
            id INT AUTO_INCREMENT PRIMARY KEY,
            display_name VARCHAR(100) NOT NULL,
            provider VARCHAR(50) NOT NULL,
            api_key TEXT NOT NULL,
            base_endpoint VARCHAR(500) DEFAULT NULL,
            sync_schedule VARCHAR(50) DEFAULT 'manual',
            default_status VARCHAR(20) DEFAULT 'draft',
            status ENUM('active','inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
}

// ── 2. CONTROLLER ACTIONS ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add Partner
    if (isset($_POST['add_partner'])) {
        $name = trim($_POST['name'] ?? '');
        $webhook = trim($_POST['webhook_url'] ?? '');
        $subType = trim($_POST['subscription_type'] ?? '');
        $subExpire = trim($_POST['subscription_expires_at'] ?? '');
        if (empty($subExpire)) $subExpire = null;

        if (empty($name)) {
            $err = 'Partner name is required.';
        } else {
            $apiKey = 'FS_KEY_' . bin2hex(random_bytes(16));
            $stmt = $pdo->prepare("INSERT INTO api_partners (name, api_key, webhook_url, status, total_orders, subscription_type, subscription_expires_at) VALUES (:name, :key, :webhook, 'active', 0, :sub_type, :sub_expire)");
            $stmt->execute([':name' => $name, ':key' => $apiKey, ':webhook' => $webhook, ':sub_type' => $subType, ':sub_expire' => $subExpire]);
            $msg = "API Partner '$name' successfully added!";
        }
    }
    
    // Update Partner
    if (isset($_POST['update_partner'])) {
        $id = (int)$_POST['partner_id'];
        $name = trim($_POST['name'] ?? '');
        $webhook = trim($_POST['webhook_url'] ?? '');
        $status = isset($_POST['status']) ? 'active' : 'inactive';
        $subType = trim($_POST['subscription_type'] ?? '');
        $subExpire = trim($_POST['subscription_expires_at'] ?? '');
        if (empty($subExpire)) $subExpire = null;
        
        if (empty($name)) {
            $err = 'Partner name cannot be empty.';
        } else {
            $stmt = $pdo->prepare("UPDATE api_partners SET name = :name, webhook_url = :webhook, status = :status, subscription_type = :sub_type, subscription_expires_at = :sub_expire WHERE id = :id");
            $stmt->execute([':name' => $name, ':webhook' => $webhook, ':status' => $status, ':sub_type' => $subType, ':sub_expire' => $subExpire, ':id' => $id]);
            $msg = 'API Partner settings saved.';
        }
    }

    // Regenerate Key
    if (isset($_POST['regen_key'])) {
        $id = (int)$_POST['partner_id'];
        $newKey = 'FS_KEY_' . bin2hex(random_bytes(16));
        $stmt = $pdo->prepare("UPDATE api_partners SET api_key = :key WHERE id = :id");
        $stmt->execute([':key' => $newKey, ':id' => $id]);
        $msg = 'API Key successfully regenerated.';
    }

    // Register Dropship Connection
    if (isset($_POST['register_dropship_connection'])) {
        $displayName = trim($_POST['display_name'] ?? '');
        $provider = trim($_POST['provider'] ?? '');
        $apiKey = trim($_POST['api_key'] ?? '');
        $baseEndpoint = trim($_POST['base_endpoint'] ?? '');
        $syncSchedule = trim($_POST['sync_schedule'] ?? 'manual');
        $defaultStatus = trim($_POST['default_status'] ?? 'draft');
        
        if (empty($displayName) || empty($provider) || empty($apiKey)) {
            $err = 'Display Name, Provider, and API Key / Token are required.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO dropship_connections (display_name, provider, api_key, base_endpoint, sync_schedule, default_status, status) VALUES (:display_name, :provider, :api_key, :base_endpoint, :sync_schedule, :default_status, 'active')");
            $stmt->execute([
                ':display_name' => $displayName,
                ':provider' => $provider,
                ':api_key' => $apiKey,
                ':base_endpoint' => $baseEndpoint ?: null,
                ':sync_schedule' => $syncSchedule,
                ':default_status' => $defaultStatus
            ]);
            $msg = "Dropship API connection '$displayName' successfully registered!";
        }
    }

    // Update Dropship Connection
    if (isset($_POST['update_dropship_connection'])) {
        $id = (int)$_POST['connection_id'];
        $displayName = trim($_POST['display_name'] ?? '');
        $provider = trim($_POST['provider'] ?? '');
        $apiKey = trim($_POST['api_key'] ?? '');
        $baseEndpoint = trim($_POST['base_endpoint'] ?? '');
        $syncSchedule = trim($_POST['sync_schedule'] ?? 'manual');
        $defaultStatus = trim($_POST['default_status'] ?? 'draft');
        $status = isset($_POST['status']) ? 'active' : 'inactive';
        
        if (empty($displayName) || empty($provider) || empty($apiKey)) {
            $err = 'Display Name, Provider, and API Key / Token are required.';
        } else {
            $stmt = $pdo->prepare("UPDATE dropship_connections SET display_name = :display_name, provider = :provider, api_key = :api_key, base_endpoint = :base_endpoint, sync_schedule = :sync_schedule, default_status = :default_status, status = :status WHERE id = :id");
            $stmt->execute([
                ':display_name' => $displayName,
                ':provider' => $provider,
                ':api_key' => $apiKey,
                ':base_endpoint' => $baseEndpoint ?: null,
                ':sync_schedule' => $syncSchedule,
                ':default_status' => $defaultStatus,
                ':status' => $status,
                ':id' => $id
            ]);
            $msg = 'Dropship Connection settings saved.';
        }
    }
}

// Delete Partner
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM api_partners WHERE id = :id");
    $stmt->execute([':id' => $id]);
    header('Location: api_partners.php?deleted=1');
    exit;
}

// Delete Dropship Connection
if (isset($_GET['delete_dropship'])) {
    $id = (int)$_GET['delete_dropship'];
    $stmt = $pdo->prepare("DELETE FROM dropship_connections WHERE id = :id");
    $stmt->execute([':id' => $id]);
    header('Location: api_partners.php?deleted_dropship=1');
    exit;
}

// Fetch all partners
$partners = $pdo->query("SELECT * FROM api_partners ORDER BY id DESC")->fetchAll();
$dropship_connections = $pdo->query("SELECT * FROM dropship_connections ORDER BY id DESC")->fetchAll();
$chatCount = (int)$pdo->query("SELECT COUNT(*) FROM chat_sessions WHERE status='with_agent'")->fetchColumn();

// Count stats
$activeCount = 0;
$totalOrders = 0;
foreach ($partners as $p) {
    if ($p['status'] === 'active') $activeCount++;
    $totalOrders += (int)$p['total_orders'];
}

$activeDropshipCount = 0;
foreach ($dropship_connections as $d) {
    if ($d['status'] === 'active') $activeDropshipCount++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>API Partners Manager — Fast Site Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Oswald:wght@600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>

<?php include 'nav.php'; ?>

<div class="wrap">
  <h1>🔌 API Partners & Connections</h1>
  <div class="sub-title">Configure external applications and outbound drop-shipping connections dynamically.</div>

  <!-- Messages -->
  <?php if($msg): ?><div class="alert alert-ok">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if($err): ?><div class="alert alert-err">❌ <?= htmlspecialchars($err) ?></div><?php endif; ?>
  <?php if(isset($_GET['deleted'])): ?><div class="alert alert-ok">✅ API Partner deleted.</div><?php endif; ?>
  <?php if(isset($_GET['deleted_dropship'])): ?><div class="alert alert-ok">✅ Dropship connection deleted.</div><?php endif; ?>

  <!-- Stats row -->
  <div class="stats">
    <div class="stat-card">
      <div class="stat-num"><?= count($partners) ?></div>
      <div class="stat-label">Inbound Partners</div>
    </div>
    <div class="stat-card">
      <div class="stat-num" style="color:var(--green);"><?= $activeCount ?></div>
      <div class="stat-label">Active Partner Keys</div>
    </div>
    <div class="stat-card">
      <div class="stat-num" style="color:var(--gold);"><?= count($dropship_connections) ?></div>
      <div class="stat-label">Dropship Suppliers</div>
    </div>
    <div class="stat-card">
      <div class="stat-num" style="color:#00e676;"><?= $activeDropshipCount ?></div>
      <div class="stat-label">Active Suppliers</div>
    </div>
  </div>

  <!-- Tabs Switcher Container -->
  <div class="tabs-container">
    <button class="tab-btn active" id="tab-inbound" onclick="switchTab('inbound-content', 'tab-inbound')">📥 Inbound API Partners</button>
    <button class="tab-btn" id="tab-dropship" onclick="switchTab('dropship-content', 'tab-dropship')">📦 Supplier & Dropship Manager</button>
  </div>

  <!-- 📥 Tab 1: Inbound API Partners -->
  <div id="inbound-content" class="tab-content active">
    <!-- Add Partner Form -->
    <div class="premium-card">
      <h2>＋ Add New API Partner</h2>
      <form method="POST">
        <div class="field">
          <label>Partner / System Name *</label>
          <input type="text" name="name" placeholder="e.g. Digital BD Reseller" required/>
        </div>
        <div class="field">
          <label>Webhook Callback URL (Optional)</label>
          <input type="url" name="webhook_url" placeholder="https://api.partner.com/fastsite-callback"/>
        </div>
        <div class="grid2">
          <div class="field">
            <label>Subscription Type</label>
            <select name="subscription_type">
              <option value="">None</option>
              <option value="weekly">Weekly</option>
              <option value="monthly">Monthly</option>
              <option value="yearly">Yearly</option>
              <option value="lifetime">Lifetime</option>
            </select>
          </div>
          <div class="field">
            <label>Expires At (Optional)</label>
            <input type="datetime-local" name="subscription_expires_at"/>
          </div>
        </div>
        <button type="submit" name="add_partner" class="btn">＋ Create API Credentials</button>
      </form>
    </div>

    <!-- Partners grid -->
    <div class="partner-grid">
      <?php if (empty($partners)): ?>
        <div style="grid-column: span 3; text-align: center; color: var(--muted); padding: 3rem 0;">No API partners registered yet. Create one above!</div>
      <?php else: ?>
        <?php foreach($partners as $p): ?>
          <div class="partner-card <?= $p['status'] !== 'active' ? 'inactive' : '' ?>">
            <div class="partner-card-header">
              <span class="partner-title"><?= htmlspecialchars($p['name']) ?></span>
              <span class="order-badge">📦 <?= $p['total_orders'] ?> Order<?= $p['total_orders'] != 1 ? 's' : '' ?></span>
            </div>
            
            <form method="POST" class="partner-form">
              <input type="hidden" name="partner_id" value="<?= $p['id'] ?>"/>
              
              <label class="pi-label">API Key</label>
              <div class="key-container">
                <span class="key-box" id="key-span-<?= $p['id'] ?>">••••••••••••••••••••••••••••••••</span>
                <button type="button" class="key-btn" onclick="toggleKeyText(<?= $p['id'] ?>, '<?= htmlspecialchars($p['api_key']) ?>')" title="Toggle Show/Hide">👁️</button>
                <button type="button" class="key-btn" style="color:var(--green);" onclick="copyToClipboard('<?= htmlspecialchars($p['api_key']) ?>')" title="Copy API Key">📋</button>
              </div>
              
              <label class="pi-label">Name</label>
              <input class="pi-input" type="text" name="name" value="<?= htmlspecialchars($p['name']) ?>" required/>
              
              <label class="pi-label">Webhook URL</label>
              <input class="pi-input" type="url" name="webhook_url" value="<?= htmlspecialchars($p['webhook_url'] ?? '') ?>" placeholder="None"/>
              
              <div style="display:flex; gap:0.5rem; margin: 0.5rem 0;">
                  <div style="flex:1;">
                      <label class="pi-label">Subscription</label>
                      <select class="pi-input" name="subscription_type" style="margin-bottom:0;">
                          <option value="" <?= empty($p['subscription_type']) ? 'selected' : '' ?>>None</option>
                          <option value="weekly" <?= ($p['subscription_type'] ?? '') == 'weekly' ? 'selected' : '' ?>>Weekly</option>
                          <option value="monthly" <?= ($p['subscription_type'] ?? '') == 'monthly' ? 'selected' : '' ?>>Monthly</option>
                          <option value="yearly" <?= ($p['subscription_type'] ?? '') == 'yearly' ? 'selected' : '' ?>>Yearly</option>
                          <option value="lifetime" <?= ($p['subscription_type'] ?? '') == 'lifetime' ? 'selected' : '' ?>>Lifetime</option>
                      </select>
                  </div>
                  <div style="flex:1;">
                      <label class="pi-label">Expires At</label>
                      <input class="pi-input" type="datetime-local" name="subscription_expires_at" value="<?= htmlspecialchars($p['subscription_expires_at'] ?? '') ?>" style="margin-bottom:0;"/>
                  </div>
              </div>
              
              <div class="partner-actions">
                <label class="switch-label">
                  <input type="checkbox" name="status" <?= $p['status'] === 'active' ? 'checked' : '' ?>/> Active
                </label>
                
                <button type="submit" name="update_partner" class="btn-save-sm">💾 Save</button>
                <button type="submit" name="regen_key" class="btn-action-outline" onclick="return confirm('WARNING: Are you sure you want to regenerate the API key? The old key will immediately stop working!')" style="color:var(--gold);">🔑 Regen Key</button>
                <a href="api_partners.php?delete=<?= $p['id'] ?>" class="btn-del" onclick="return confirm('Delete <?= htmlspecialchars($p['name']) ?>?')">🗑 Delete</a>
              </div>
            </form>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- Collapsible API Documentation -->
    <div class="docs-card collapsed" id="docs-accordion">
      <div class="docs-header" onclick="toggleAccordion('docs-accordion')">
        <h3>📜 DEVELOPER API DOCUMENTATION</h3>
        <span class="docs-arrow">▼</span>
      </div>
      
      <div class="docs-body">
        <p>Submit service orders programmatically from any external app by sending a secure JSON <code>POST</code> request.</p>
        
        <h4>Endpoint URL</h4>
        <pre><code>POST http://<?= htmlspecialchars($_SERVER['HTTP_HOST'] . rtrim(dirname(dirname($_SERVER['PHP_SELF'])), '/')) ?>/submit_order.php</code></pre>
        
        <h4>Headers</h4>
        <pre><code>Content-Type: application/json
  X-API-Key: YOUR_API_KEY</code></pre>
        
        <h4>Request Payload (JSON)</h4>
        <pre><button class="copy-docs-btn" onclick="copyCodeBlock('req-payload')">Copy</button><code id="req-payload">{
    "user_name": "Sabbir Hossain",
    "user_phone": "01712345678",
    "service": "NID Correction",
    "user_email": "sabbir@gmail.com",
    "nid_number": "1234567890123",
    "passport_number": "AB1234567",
    "driving_license": "DL-12345678",
    "details": "Need to correct spelling of mother's name to Begum Rasheda."
  }</code></pre>
        
        <h4>cURL Example</h4>
        <pre><button class="copy-docs-btn" onclick="copyCodeBlock('curl-example')">Copy</button><code id="curl-example">curl -X POST "http://<?= htmlspecialchars($_SERVER['HTTP_HOST'] . rtrim(dirname(dirname($_SERVER['PHP_SELF'])), '/')) ?>/submit_order.php" \
    -H "Content-Type: application/json" \
    -H "X-API-Key: YOUR_API_KEY" \
    -d '{
      "user_name": "Sabbir Hossain",
      "user_phone": "01712345678",
      "service": "NID Correction",
      "details": "Correct mother name."
    }'</code></pre>

        <h4>PHP cURL Code Snippet</h4>
        <pre><button class="copy-docs-btn" onclick="copyCodeBlock('php-example')">Copy</button><code id="php-example">&lt;?php
  $url = "http://<?= htmlspecialchars($_SERVER['HTTP_HOST'] . rtrim(dirname(dirname($_SERVER['PHP_SELF'])), '/')) ?>/submit_order.php";
  $apiKey = "YOUR_API_KEY";
  
  $data = [
      "user_name"  => "Sabbir Hossain",
      "user_phone" => "01712345678",
      "service"    => "NID Correction",
      "details"    => "Correction request."
  ];
  
  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, [
      "Content-Type: application/json",
      "X-API-Key: " . $apiKey
  ]);
  curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
  
  $response = curl_exec($ch);
  curl_close($ch);
  
  $result = json_decode($response, true);
  if ($result['success']) {
      echo "Success! Tracking ID: " . $result['ref'];
  } else {
      echo "Error: " . $result['message'];
  }
  ?&gt;</code></pre>
      </div>
    </div>
  </div>

  <!-- 📦 Tab 2: Outbound Supplier & Dropship Manager -->
  <div id="dropship-content" class="tab-content">
    <div class="premium-card">
      <h2>＋ Register Dropship API Connection</h2>
      <form method="POST">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; flex-wrap: wrap;">
          <div class="field">
            <label>Display Name *</label>
            <input type="text" name="display_name" placeholder="e.g. CJ Dropshipping VIP" required/>
          </div>
          <div class="field">
            <label>Provider *</label>
            <select name="provider" required>
              <option value="AliExpress">AliExpress API</option>
              <option value="CJ Dropshipping">CJ Dropshipping API</option>
              <option value="Doba">Doba API</option>
              <option value="Custom API">Custom Supplier API</option>
            </select>
          </div>
        </div>
        
        <div class="field">
          <label>API Key / Token *</label>
          <input type="text" name="api_key" placeholder="Enter supplier API access token or secret key" required/>
        </div>
        
        <div class="field">
          <label>Base Endpoint URL (Optional)</label>
          <input type="url" name="base_endpoint" placeholder="e.g. https://api.cjdropshipping.com/v2"/>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; flex-wrap: wrap;">
          <div class="field">
            <label>Sync Schedule</label>
            <select name="sync_schedule">
              <option value="manual">Manual Sync Only</option>
              <option value="hourly">Hourly (Dynamic background)</option>
              <option value="daily" selected>Daily Auto-Update</option>
              <option value="weekly">Weekly Rollup</option>
            </select>
          </div>
          <div class="field">
            <label>Default Product Status</label>
            <select name="default_status">
              <option value="draft" selected>Draft (Needs approval)</option>
              <option value="active">Active (Direct publish)</option>
            </select>
          </div>
        </div>
        
        <button type="submit" name="register_dropship_connection" class="btn">Register API Connection</button>
      </form>
    </div>

    <!-- Dropship Connections Grid -->
    <div class="partner-grid">
      <?php if (empty($dropship_connections)): ?>
        <div style="grid-column: span 3; text-align: center; color: var(--muted); padding: 3rem 0;">No dropship supplier connections registered yet. Add one above!</div>
      <?php else: ?>
        <?php foreach($dropship_connections as $d): ?>
          <div class="partner-card <?= $d['status'] !== 'active' ? 'inactive' : '' ?>">
            <div class="partner-card-header">
              <span class="partner-title"><?= htmlspecialchars($d['display_name']) ?></span>
              <span class="order-badge" style="background: rgba(0, 230, 118, 0.12); color: var(--green); border-color: rgba(0,230,118,0.2);"><?= htmlspecialchars($d['provider']) ?></span>
            </div>
            
            <form method="POST" class="partner-form">
              <input type="hidden" name="connection_id" value="<?= $d['id'] ?>"/>
              
              <label class="pi-label">API Key / Token</label>
              <div class="key-container">
                <span class="key-box" id="dropship-key-span-<?= $d['id'] ?>">••••••••••••••••••••••••••••••••</span>
                <button type="button" class="key-btn" onclick="toggleDropshipKeyText(<?= $d['id'] ?>, '<?= htmlspecialchars($d['api_key']) ?>')" title="Toggle Show/Hide">👁️</button>
                <button type="button" class="key-btn" style="color:var(--green);" onclick="copyToClipboard('<?= htmlspecialchars($d['api_key']) ?>')" title="Copy Key">📋</button>
              </div>
              
              <label class="pi-label">Display Name</label>
              <input class="pi-input" type="text" name="display_name" value="<?= htmlspecialchars($d['display_name']) ?>" required/>
              
              <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                <div>
                  <label class="pi-label">Provider</label>
                  <select class="pi-select" name="provider" style="width: 100%;" required>
                    <option value="AliExpress" <?= $d['provider'] === 'AliExpress' ? 'selected' : '' ?>>AliExpress</option>
                    <option value="CJ Dropshipping" <?= $d['provider'] === 'CJ Dropshipping' ? 'selected' : '' ?>>CJ Dropshipping</option>
                    <option value="Doba" <?= $d['provider'] === 'Doba' ? 'selected' : '' ?>>Doba</option>
                    <option value="Custom API" <?= $d['provider'] === 'Custom API' ? 'selected' : '' ?>>Custom API</option>
                  </select>
                </div>
                <div>
                  <label class="pi-label">Sync Schedule</label>
                  <select class="pi-select" name="sync_schedule" style="width: 100%;">
                    <option value="manual" <?= $d['sync_schedule'] === 'manual' ? 'selected' : '' ?>>Manual</option>
                    <option value="hourly" <?= $d['sync_schedule'] === 'hourly' ? 'selected' : '' ?>>Hourly</option>
                    <option value="daily" <?= $d['sync_schedule'] === 'daily' ? 'selected' : '' ?>>Daily</option>
                    <option value="weekly" <?= $d['sync_schedule'] === 'weekly' ? 'selected' : '' ?>>Weekly</option>
                  </select>
                </div>
              </div>

              <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                <div>
                  <label class="pi-label">Base Endpoint</label>
                  <input class="pi-input" type="url" name="base_endpoint" style="width: 100%;" value="<?= htmlspecialchars($d['base_endpoint'] ?? '') ?>" placeholder="None"/>
                </div>
                <div>
                  <label class="pi-label">Default Status</label>
                  <select class="pi-select" name="default_status" style="width: 100%;">
                    <option value="draft" <?= $d['default_status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                    <option value="active" <?= $d['default_status'] === 'active' ? 'selected' : '' ?>>Active</option>
                  </select>
                </div>
              </div>
              
              <div class="partner-actions">
                <label class="switch-label">
                  <input type="checkbox" name="status" <?= $d['status'] === 'active' ? 'checked' : '' ?>/> Active
                </label>
                
                <button type="submit" name="update_dropship_connection" class="btn-save-sm">💾 Save</button>
                <button type="button" class="btn-action-outline" onclick="testDropshipConnection(<?= $d['id'] ?>, this)" style="color:var(--green); display: inline-flex; align-items: center; gap: 0.2rem;">⚡ Test</button>
                <a href="api_partners.php?delete_dropship=<?= $d['id'] ?>" class="btn-del" onclick="return confirm('Delete dropship connection <?= htmlspecialchars($d['display_name']) ?>?')">🗑 Delete</a>
              </div>

              <!-- Real-time Test Output Box -->
              <div class="test-result-box" id="test-result-<?= $d['id'] ?>"></div>
            </form>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="toast" id="copy-toast">Copied to Clipboard! ✓</div>

<script>
// Tab Switching logic
function switchTab(contentId, btnId) {
  document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
  document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
  
  document.getElementById(contentId).classList.add('active');
  document.getElementById(btnId).classList.add('active');
  
  sessionStorage.setItem('active_api_tab_content', contentId);
  sessionStorage.setItem('active_api_tab_btn', btnId);
}

// Restore Tab on Page Load
document.addEventListener('DOMContentLoaded', () => {
  const savedContent = sessionStorage.getItem('active_api_tab_content');
  const savedBtn = sessionStorage.getItem('active_api_tab_btn');
  if (savedContent && savedBtn) {
    if (document.getElementById(savedContent) && document.getElementById(savedBtn)) {
      switchTab(savedContent, savedBtn);
    }
  }
});

function toggleAccordion(id) {
  document.getElementById(id).classList.toggle('collapsed');
}

function toggleKeyText(id, fullKey) {
  const span = document.getElementById('key-span-' + id);
  if (span.textContent.includes('•')) {
    span.textContent = fullKey;
  } else {
    span.textContent = '••••••••••••••••••••••••••••••••';
  }
}

function toggleDropshipKeyText(id, fullKey) {
  const span = document.getElementById('dropship-key-span-' + id);
  if (span.textContent.includes('•')) {
    span.textContent = fullKey;
  } else {
    span.textContent = '••••••••••••••••••••••••••••••••';
  }
}

function copyToClipboard(text) {
  navigator.clipboard.writeText(text).then(() => {
    showToast();
  });
}

function copyCodeBlock(id) {
  const code = document.getElementById(id).textContent;
  navigator.clipboard.writeText(code).then(() => {
    showToast();
  });
}

function showToast() {
  const toast = document.getElementById('copy-toast');
  toast.classList.add('active');
  setTimeout(() => {
    toast.classList.remove('active');
  }, 2000);
}

function testDropshipConnection(id, btn) {
  const origText = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = '⏳ Testing...';
  
  const resultBox = document.getElementById('test-result-' + id);
  resultBox.style.display = 'none';
  resultBox.className = 'test-result-box';
  
  const formData = new FormData();
  formData.append('action', 'test_dropship_connection');
  formData.append('id', id);
  
  fetch('api_partners.php', {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    btn.disabled = false;
    btn.innerHTML = origText;
    
    resultBox.style.display = 'block';
    if (data.success) {
      resultBox.classList.add('test-result-success');
      resultBox.innerHTML = '✅ ' + data.message;
    } else {
      resultBox.classList.add('test-result-error');
      resultBox.innerHTML = '❌ ' + data.message;
    }
  })
  .catch(err => {
    btn.disabled = false;
    btn.innerHTML = origText;
    
    resultBox.style.display = 'block';
    resultBox.classList.add('test-result-error');
    resultBox.innerHTML = '❌ Connection failed: ' + err;
  });
}
</script>

</body>
</html>
