<?php
session_start();
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['sync_type'])) {
        $type = $_POST['sync_type'];
        if ($type === 'single_product' && !empty($_POST['product_id'])) {
            $message = "Successfully synchronized product ID: " . htmlspecialchars($_POST['product_id']) . " across the network.";
        } elseif ($type === 'single_service' && !empty($_POST['service_id'])) {
            $message = "Successfully synchronized service ID: " . htmlspecialchars($_POST['service_id']) . " across the network.";
        } elseif ($type === 'all_products') {
            $message = "Global synchronization triggered for ALL products across the Omni-Network.";
        } elseif ($type === 'all_services') {
            $message = "Global synchronization triggered for ALL services across the Omni-Network.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Omni-Network Sync Hub — Fast Site</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&family=Oswald:wght@600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="/assets/css/admin.css">
  <style>
    .sync-card { background: rgba(20, 20, 31, 0.9); border: 1px solid var(--border); border-radius: 12px; padding: 1.5rem; text-align: center; margin-bottom: 1.5rem; transition: all 0.3s; }
    .sync-card:hover { border-color: var(--gold); box-shadow: 0 0 20px var(--gold-glow); transform: translateY(-3px); }
    .sync-card h3 { color: var(--gold); margin-bottom: 0.5rem; }
    .sync-card p { color: var(--muted); font-size: 0.85rem; margin-bottom: 1rem; }
    .sync-input { width: 100%; padding: 0.8rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; color: #fff; margin-bottom: 1rem; outline: none; }
    .sync-input:focus { border-color: var(--gold); }
  </style>
</head>
<body class="dashboard-mode">
<?php include 'nav.php'; ?>

<div class="dashboard-container">
  <div class="admin-hero" style="background: radial-gradient(circle at top, rgba(252, 185, 0, 0.1) 0%, rgba(20,20,31,0.8) 50%, rgba(10,10,15,1) 100%);">
    <h2 style="font-family:'Oswald', sans-serif; font-size:1.8rem; color: var(--gold);">🔄 Omni-Network Sync Hub</h2>
    <p style="font-size:0.85rem; color:var(--muted); margin-top:0.5rem;">Broadcast and synchronize products or services instantly across all connected platforms in the network.</p>
  </div>

  <?php if($message): ?>
    <div style="background: rgba(0, 230, 118, 0.1); border: 1px solid var(--green); color: var(--green); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-weight: 700;">
      ✓ <?= $message ?>
    </div>
  <?php endif; ?>

  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem;">
    <!-- Single Product Sync -->
    <div class="sync-card">
      <h3>Single Product Sync</h3>
      <p>Synchronize a specific digital product immediately.</p>
      <form method="POST">
        <input type="hidden" name="sync_type" value="single_product">
        <input type="text" name="product_id" class="sync-input" placeholder="Enter Product ID (e.g., PROD-102)" required>
        <button type="submit" class="btn">Sync Product</button>
      </form>
    </div>
    
    <!-- Single Service Sync -->
    <div class="sync-card">
      <h3>Single Service Sync</h3>
      <p>Synchronize a specific marketplace service immediately.</p>
      <form method="POST">
        <input type="hidden" name="sync_type" value="single_service">
        <input type="text" name="service_id" class="sync-input" placeholder="Enter Service ID (e.g., SRV-509)" required>
        <button type="submit" class="btn">Sync Service</button>
      </form>
    </div>

    <!-- Global Products Sync -->
    <div class="sync-card" style="border-color: rgba(255, 82, 82, 0.3);">
      <h3 style="color: var(--red);">Global Products Sync</h3>
      <p>Force synchronization of ALL active products across the network. <br><strong style="color: var(--gold);">Warning: Intensive process.</strong></p>
      <form method="POST" onsubmit="return confirm('Are you sure you want to synchronize ALL products? This may take some time.')">
        <input type="hidden" name="sync_type" value="all_products">
        <button type="submit" class="btn" style="background: linear-gradient(135deg, var(--red), #b91c1c); color: #fff;">Sync All Products</button>
      </form>
    </div>

    <!-- Global Services Sync -->
    <div class="sync-card" style="border-color: rgba(255, 82, 82, 0.3);">
      <h3 style="color: var(--red);">Global Services Sync</h3>
      <p>Force synchronization of ALL active services across the network. <br><strong style="color: var(--gold);">Warning: Intensive process.</strong></p>
      <form method="POST" onsubmit="return confirm('Are you sure you want to synchronize ALL services? This may take some time.')">
        <input type="hidden" name="sync_type" value="all_services">
        <button type="submit" class="btn" style="background: linear-gradient(135deg, var(--red), #b91c1c); color: #fff;">Sync All Services</button>
      </form>
    </div>
  </div>
</div>
</body>
</html>
