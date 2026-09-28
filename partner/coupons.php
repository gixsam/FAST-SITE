<?php
// =========================================================================
// partner/coupons.php  –  Manage Shop Promo Codes (Ultra-Premium Hub)
// =========================================================================
ob_start();
require_once 'nav.php';
$_nav_html = ob_get_clean();

$partner_id = (int)($_SESSION['partner_id'] ?? 0);
$err = $msg = '';

// Self-healing database table check
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS shop_coupons (
        id INT AUTO_INCREMENT PRIMARY KEY,
        partner_id INT NOT NULL,
        code VARCHAR(50) NOT NULL UNIQUE,
        discount_amount DECIMAL(10,2) NOT NULL,
        is_percentage TINYINT(1) DEFAULT 0,
        max_uses INT DEFAULT 0,
        current_uses INT DEFAULT 0,
        expires_at DATETIME DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS shop_coupons (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            partner_id INTEGER NOT NULL,
            code TEXT NOT NULL UNIQUE,
            discount_amount REAL NOT NULL,
            is_percentage INTEGER DEFAULT 0,
            max_uses INTEGER DEFAULT 0,
            current_uses INTEGER DEFAULT 0,
            expires_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
    } catch (Exception $ex) {}
}

if ($partner['status'] === 'pending') {
    echo "<div class='main-content'><div class='box' style='text-align:center; padding:3rem 1rem;'><h2>⏳ Shop Under Review</h2><p style='color:#aaa;'>Your shop is currently pending admin approval. You can create coupons once your shop is approved.</p><br><a href='dashboard.php' class='btn' style='background:var(--brand,#fcb900); color:#000; padding:0.6rem 1.5rem; font-weight:700; text-decoration:none; border-radius:8px;'>Back to Dashboard</a></div></div>";
    echo "</body></html>";
    exit;
}

// Handle Create Coupon
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_coupon'])) {
    $code = strtoupper(preg_replace('/[^A-Z0-9_-]/', '', trim($_POST['code'] ?? '')));
    $discount_amount = floatval($_POST['discount_amount'] ?? 0);
    $is_percentage = isset($_POST['is_percentage']) ? 1 : 0;
    $max_uses = intval($_POST['max_uses'] ?? 0);
    $expires_at = trim($_POST['expires_at'] ?? '');

    if (empty($code) || $discount_amount <= 0) {
        $err = "Please provide a valid coupon code and discount amount greater than 0.";
    } elseif ($is_percentage && $discount_amount > 100) {
        $err = "Percentage discount cannot exceed 100%.";
    } else {
        $exp_val = !empty($expires_at) ? date('Y-m-d H:i:s', strtotime($expires_at)) : null;

        try {
            $stmt = $pdo->prepare("INSERT INTO shop_coupons (partner_id, code, discount_amount, is_percentage, max_uses, expires_at) VALUES (:pid, :code, :amt, :pct, :max, :exp)");
            $stmt->execute([
                ':pid'  => $partner_id,
                ':code' => $code,
                ':amt'  => $discount_amount,
                ':pct'  => $is_percentage,
                ':max'  => $max_uses,
                ':exp'  => $exp_val
            ]);
            $msg = "Promo Code '$code' created successfully!";
        } catch (Exception $e) {
            if (strpos($e->getMessage(), '1062 Duplicate entry') !== false || strpos($e->getMessage(), 'UNIQUE constraint failed') !== false) {
                $err = "Promo code '$code' already exists. Please choose a unique code.";
            } else {
                $err = "Error creating coupon: " . $e->getMessage();
            }
        }
    }
}

// Handle Delete Coupon
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $did = (int)$_GET['delete'];
    try {
        $pdo->prepare("DELETE FROM shop_coupons WHERE id = :id AND partner_id = :pid")->execute([':id' => $did, ':pid' => $partner_id]);
        header('Location: coupons.php?deleted=1');
        exit;
    } catch (Exception $e) {
        $err = "Could not delete coupon.";
    }
}
if (isset($_GET['deleted'])) {
    $msg = "Coupon deleted successfully.";
}

// Fetch existing coupons & stats
$coupons = [];
$total_coupons = 0;
$active_coupons = 0;
$total_redeemed = 0;

try {
    $stmt = $pdo->prepare("SELECT * FROM shop_coupons WHERE partner_id = :pid ORDER BY created_at DESC");
    $stmt->execute([':pid' => $partner_id]);
    $coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total_coupons = count($coupons);
    foreach ($coupons as $c) {
        $is_exp = ($c['expires_at'] && strtotime($c['expires_at']) < time()) || ($c['max_uses'] > 0 && $c['current_uses'] >= $c['max_uses']);
        if (!$is_exp) $active_coupons++;
        $total_redeemed += intval($c['current_uses']);
    }
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Promo Codes &amp; Discounts &mdash; Fast Site Partner Hub</title>
  <link rel="stylesheet" href="../assets/css/admin.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Oswald:wght@500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --gold: #fcb900;
      --gold-glow: rgba(252, 185, 0, 0.25);
      --teal: #00e676;
      --teal-glow: rgba(0, 230, 118, 0.25);
      --dark-card: rgba(18, 20, 32, 0.75);
      --border-glass: rgba(255, 255, 255, 0.08);
    }
    
    body {
      background: radial-gradient(circle at 50% 0%, #151828 0%, #090a12 100%);
      color: #e2e8f0;
      font-family: 'Inter', sans-serif;
      min-height: 100vh;
      margin: 0;
      padding: 0;
    }

    .hero-banner {
      background: linear-gradient(135deg, rgba(252, 185, 0, 0.12) 0%, rgba(20, 24, 40, 0.6) 100%);
      border: 1px solid rgba(252, 185, 0, 0.25);
      border-radius: 18px;
      padding: 1.8rem 2rem;
      margin-bottom: 2rem;
      backdrop-filter: blur(12px);
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 1rem;
    }

    .hero-banner h1 {
      margin: 0;
      font-family: 'Oswald', sans-serif;
      font-size: 1.8rem;
      color: #fff;
      text-transform: uppercase;
      letter-spacing: 0.04em;
    }

    .kpi-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1rem;
      margin-bottom: 2rem;
    }

    .kpi-card {
      background: var(--dark-card);
      border: 1px solid var(--border-glass);
      border-radius: 16px;
      padding: 1.2rem 1.5rem;
      backdrop-filter: blur(12px);
      position: relative;
      overflow: hidden;
    }

    .kpi-card:before {
      content: '';
      position: absolute;
      top: 0; left: 0; right: 0; height: 3px;
      background: linear-gradient(90deg, var(--gold), #ff9100);
    }

    .kpi-val {
      font-family: 'Oswald', sans-serif;
      font-size: 1.9rem;
      font-weight: 700;
      color: #fff;
      margin: 0.2rem 0;
    }

    .kpi-lbl {
      font-size: 0.72rem;
      color: #94a3b8;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }

    .grid-wrap {
      display: grid;
      grid-template-columns: 360px 1fr;
      gap: 1.8rem;
    }

    @media (max-width: 950px) {
      .grid-wrap { grid-template-columns: 1fr; }
    }

    .glass-card {
      background: var(--dark-card);
      border: 1px solid var(--border-glass);
      border-radius: 18px;
      padding: 1.8rem;
      backdrop-filter: blur(16px);
      box-shadow: 0 15px 40px rgba(0,0,0,0.4);
    }

    .field { margin-bottom: 1.2rem; }
    .field label {
      display: block;
      font-size: 0.76rem;
      font-weight: 700;
      color: #94a3b8;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      margin-bottom: 0.45rem;
    }

    .field input, .field select {
      width: 100%;
      background: rgba(10, 12, 20, 0.8);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 12px;
      padding: 0.8rem 1rem;
      color: #fff;
      font-size: 0.9rem;
      font-family: inherit;
      box-sizing: border-box;
      transition: 0.2s;
    }

    .field input:focus, .field select:focus {
      outline: none;
      border-color: var(--gold);
      box-shadow: 0 0 15px rgba(252,185,0,0.25);
    }

    .preset-chips {
      display: flex;
      gap: 0.4rem;
      flex-wrap: wrap;
      margin-top: 0.4rem;
    }

    .preset-chip {
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.1);
      color: #cbd5e1;
      padding: 3px 8px;
      border-radius: 6px;
      font-size: 0.72rem;
      font-weight: 700;
      cursor: pointer;
      transition: 0.2s;
    }

    .preset-chip:hover {
      background: rgba(252, 185, 0, 0.15);
      color: var(--gold);
      border-color: rgba(252, 185, 0, 0.4);
    }

    .btn-create {
      background: linear-gradient(135deg, var(--gold) 0%, #ff9100 100%);
      color: #000;
      font-weight: 900;
      font-family: 'Oswald', sans-serif;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      border: none;
      border-radius: 50px;
      padding: 0.9rem 1.5rem;
      width: 100%;
      font-size: 1rem;
      cursor: pointer;
      transition: all 0.25s;
      box-shadow: 0 8px 25px rgba(252, 185, 0, 0.35);
      margin-top: 0.6rem;
    }

    .btn-create:hover {
      transform: translateY(-2px);
      box-shadow: 0 12px 30px rgba(252, 185, 0, 0.5);
    }

    /* Coupon Item Cards */
    .coupon-item {
      background: rgba(10, 12, 20, 0.6);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 14px;
      padding: 1.2rem;
      margin-bottom: 1rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 1rem;
      flex-wrap: wrap;
      transition: all 0.2s;
    }

    .coupon-item:hover {
      border-color: rgba(252, 185, 0, 0.3);
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(0,0,0,0.3);
    }

    .coupon-code-badge {
      font-family: monospace;
      font-size: 1.15rem;
      font-weight: 800;
      color: var(--gold);
      background: rgba(252, 185, 0, 0.1);
      border: 1px dashed rgba(252, 185, 0, 0.4);
      padding: 4px 10px;
      border-radius: 8px;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }

    .badge-status {
      font-size: 0.7rem;
      font-weight: 800;
      padding: 3px 8px;
      border-radius: 50px;
      text-transform: uppercase;
    }
    .badge-active { background: rgba(0, 230, 118, 0.15); color: #00e676; border: 1px solid rgba(0, 230, 118, 0.3); }
    .badge-expired { background: rgba(255, 82, 82, 0.15); color: #ff5252; border: 1px solid rgba(255, 82, 82, 0.3); }

    .err {
      background: rgba(255, 82, 82, 0.12);
      color: #ff5252;
      border: 1px solid rgba(255, 82, 82, 0.3);
      padding: 0.9rem 1.2rem;
      border-radius: 12px;
      font-size: 0.88rem;
      margin-bottom: 1.5rem;
      font-weight: 600;
    }

    .success {
      background: rgba(0, 230, 118, 0.12);
      color: #00e676;
      border: 1px solid rgba(0, 230, 118, 0.3);
      padding: 0.9rem 1.2rem;
      border-radius: 12px;
      font-size: 0.88rem;
      margin-bottom: 1.5rem;
      font-weight: 600;
    }
  </style>
</head>
<body>
<?php echo $_nav_html; ?>

<div class="content-wrapper" style="max-width: 1200px; margin: 2rem auto; padding: 0 1rem;">
  
  <!-- Hero Section -->
  <div class="hero-banner">
    <div>
      <h1>🎟️ Shop Promo Codes &amp; Coupons</h1>
      <p style="margin:0.3rem 0 0 0; color:#94a3b8; font-size:0.88rem;">
        Generate promotional discount codes to boost customer conversion and reward loyal buyers
      </p>
    </div>
    <a href="dashboard.php" style="display:inline-flex; align-items:center; gap:6px; background:rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.15); color:#fff; padding:0.6rem 1.2rem; border-radius:10px; text-decoration:none; font-size:0.85rem; font-weight:700;">
      ← Dashboard
    </a>
  </div>

  <!-- KPI Cards -->
  <div class="kpi-grid">
    <div class="kpi-card">
      <div class="kpi-lbl">Total Promo Codes</div>
      <div class="kpi-val"><?= $total_coupons ?></div>
      <div style="font-size:0.75rem; color:#94a3b8;">Created in Store</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-lbl">Active &amp; Ready</div>
      <div class="kpi-val" style="color:var(--teal);"><?= $active_coupons ?></div>
      <div style="font-size:0.75rem; color:#94a3b8;">Currently Redeemable</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-lbl">Total Redemptions</div>
      <div class="kpi-val" style="color:var(--gold);"><?= $total_redeemed ?></div>
      <div style="font-size:0.75rem; color:#94a3b8;">Times Used by Buyers</div>
    </div>
  </div>

  <?php if($err): ?><div class="err">⚠️ <?= htmlspecialchars($err) ?></div><?php endif; ?>
  <?php if($msg): ?><div class="success">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>

  <div class="grid-wrap">
    
    <!-- Left: Create Coupon Form -->
    <div class="glass-card">
      <h3 style="margin:0 0 1.2rem 0; font-family:'Oswald',sans-serif; color:var(--gold); font-size:1.2rem; text-transform:uppercase; display:flex; align-items:center; gap:8px;">
        <span>✨</span> Create New Promo Code
      </h3>

      <form method="POST" action="coupons.php">
        <div class="field">
          <label>Coupon Code *</label>
          <input type="text" name="code" id="coupon-code-input" placeholder="e.g. MEGA50 / FAST2026" required style="text-transform:uppercase; font-family:monospace; font-weight:700; letter-spacing:0.05em;"/>
          <div class="preset-chips">
            <span class="preset-chip" onclick="setCode('SAVE10')">+ SAVE10</span>
            <span class="preset-chip" onclick="setCode('SPECIAL20')">+ SPECIAL20</span>
            <span class="preset-chip" onclick="setCode('FAST50')">+ FAST50</span>
            <span class="preset-chip" onclick="setCode('VIPPRO')">+ VIPPRO</span>
          </div>
        </div>

        <div class="field">
          <label>Discount Value *</label>
          <input type="number" step="0.01" min="0.1" name="discount_amount" id="discount-amount-input" placeholder="e.g. 20 (for 20% or 20 Coins)" required/>
          <div class="preset-chips">
            <span class="preset-chip" onclick="setDiscount(5)">5</span>
            <span class="preset-chip" onclick="setDiscount(10)">10</span>
            <span class="preset-chip" onclick="setDiscount(20)">20</span>
            <span class="preset-chip" onclick="setDiscount(50)">50</span>
            <span class="preset-chip" onclick="setDiscount(100)">100</span>
          </div>
        </div>

        <div class="field" style="background:rgba(0,0,0,0.25); padding:0.8rem 1rem; border-radius:12px; border:1px solid rgba(255,255,255,0.06);">
          <label style="margin-bottom:0.4rem;">Discount Type</label>
          <div style="display:flex; gap:1.5rem; margin-top:0.3rem;">
            <label style="display:flex; align-items:center; gap:6px; font-size:0.85rem; font-weight:700; cursor:pointer; color:#fff;">
              <input type="radio" name="is_percentage" value="1" checked style="accent-color:var(--gold); width:auto;">
              <span>Percentage (%)</span>
            </label>
            <label style="display:flex; align-items:center; gap:6px; font-size:0.85rem; font-weight:700; cursor:pointer; color:#aaa;">
              <input type="radio" name="is_percentage" value="0" style="accent-color:var(--gold); width:auto;">
              <span>Flat Coins (🪙)</span>
            </label>
          </div>
        </div>

        <div class="field">
          <label>Maximum Allowed Uses (0 = Unlimited)</label>
          <input type="number" name="max_uses" value="0" min="0" placeholder="0 for unlimited"/>
        </div>

        <div class="field">
          <label>Expiry Date &amp; Time (Optional)</label>
          <input type="datetime-local" name="expires_at"/>
        </div>

        <button type="submit" name="create_coupon" class="btn-create">
          🚀 Generate &amp; Activate Code
        </button>
      </form>
    </div>

    <!-- Right: Coupons Ledger List -->
    <div class="glass-card">
      <h3 style="margin:0 0 1.2rem 0; font-family:'Oswald',sans-serif; color:#fff; font-size:1.2rem; text-transform:uppercase; display:flex; align-items:center; justify-content:space-between;">
        <span>📋 Active Promo Codes Ledger</span>
        <span style="font-size:0.8rem; color:#94a3b8; font-weight:normal;"><?= count($coupons) ?> Codes Total</span>
      </h3>

      <?php if (empty($coupons)): ?>
        <div style="text-align:center; padding:3.5rem 1.5rem; background:rgba(255,255,255,0.01); border:1px dashed rgba(255,255,255,0.08); border-radius:16px;">
          <div style="font-size:2.5rem; margin-bottom:0.5rem;">🎟️</div>
          <h4 style="color:#fff; margin:0 0 0.4rem 0;">No Promo Codes Created Yet</h4>
          <p style="color:#94a3b8; font-size:0.84rem; margin:0;">
            Use the form on the left to create your first store promo code and share it with customers!
          </p>
        </div>
      <?php else: ?>
        <div style="display:flex; flex-direction:column; gap:0.8rem;">
          <?php foreach ($coupons as $c): 
            $is_expired = ($c['expires_at'] && strtotime($c['expires_at']) < time()) || ($c['max_uses'] > 0 && $c['current_uses'] >= $c['max_uses']);
          ?>
            <div class="coupon-item">
              <div>
                <div style="display:flex; align-items:center; gap:0.6rem; margin-bottom:0.3rem;">
                  <span class="coupon-code-badge" id="code-val-<?= $c['id'] ?>"><?= htmlspecialchars($c['code']) ?></span>
                  <button type="button" onclick="copyCode('<?= htmlspecialchars($c['code']) ?>', this)" style="background:rgba(255,255,255,0.08); border:none; color:#fff; padding:4px 8px; border-radius:6px; cursor:pointer; font-size:0.75rem;" title="Copy Code">
                    📋 Copy
                  </button>
                  <span class="badge-status <?= $is_expired ? 'badge-expired' : 'badge-active' ?>">
                    <?= $is_expired ? 'Expired' : 'Active' ?>
                  </span>
                </div>
                <div style="font-size:0.82rem; color:#94a3b8; line-height:1.4;">
                  Discount: <strong style="color:var(--teal); font-size:0.9rem;"><?= $c['is_percentage'] ? floatval($c['discount_amount']).'%' : number_format($c['discount_amount'], 2).' Coins' ?></strong>
                  &nbsp;•&nbsp; Uses: <strong><?= $c['current_uses'] ?></strong> / <?= $c['max_uses'] > 0 ? $c['max_uses'] : '∞' ?>
                  <?php if (!empty($c['expires_at'])): ?>
                    &nbsp;•&nbsp; Expires: <?= date('d M Y', strtotime($c['expires_at'])) ?>
                  <?php endif; ?>
                </div>
              </div>

              <div style="display:flex; align-items:center; gap:0.6rem;">
                <a href="coupons.php?delete=<?= $c['id'] ?>" onclick="return confirm('Are you sure you want to delete promo code <?= htmlspecialchars($c['code']) ?>?');" style="background:rgba(255,82,82,0.12); border:1px solid rgba(255,82,82,0.25); color:#ff5252; padding:0.5rem 0.8rem; border-radius:8px; text-decoration:none; font-size:0.8rem; font-weight:700; display:inline-flex; align-items:center; gap:4px; transition:0.2s;">
                  🗑️ Delete
                </a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

  </div>
</div>

<script>
function setCode(code) {
    const input = document.getElementById('coupon-code-input');
    if (input) input.value = code;
}

function setDiscount(amt) {
    const input = document.getElementById('discount-amount-input');
    if (input) input.value = amt;
}

function copyCode(text, btn) {
    navigator.clipboard.writeText(text).then(() => {
        const orig = btn.innerHTML;
        btn.innerHTML = '✅ Copied!';
        btn.style.background = '#00e676';
        btn.style.color = '#000';
        setTimeout(() => {
            btn.innerHTML = orig;
            btn.style.background = 'rgba(255,255,255,0.08)';
            btn.style.color = '#fff';
        }, 2000);
    });
}
</script>
</body>
</html>
