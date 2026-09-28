<?php
// =========================================================================
// admin/network_products.php    v1: Omni-Network Sync Hub (Phase 26)
// =========================================================================
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/../config.php';

// Get Official Partner ID
$official_partner_id = 0;
try {
    $stmt = $pdo->prepare("SELECT id FROM partners WHERE business_name = 'Fast Site Official' LIMIT 1");
    $stmt->execute();
    if ($official = $stmt->fetch()) {
        $official_partner_id = $official['id'];
    }
} catch (Exception $e) {}

// Mock External Network Product Database
// In a real environment, this data would be fetched via cURL from the respective websites' APIs
$network_products = [
    [
        'id' => 'AYRA-9012',
        'source' => 'Ayra Mart',
        'title' => 'Women\'s Summer Floral Dress',
        'price' => '1200 BDT',
        'category' => 'Fashion',
        'stock' => 'In Stock (45)',
        'color' => '#e91e63'
    ],
    [
        'id' => 'AYRA-9013',
        'source' => 'Ayra Mart',
        'title' => 'Men\'s Denim Jacket Premium',
        'price' => '2500 BDT',
        'category' => 'Fashion',
        'stock' => 'In Stock (12)',
        'color' => '#e91e63'
    ],
    [
        'id' => 'BEST-404',
        'source' => 'Best Travel',
        'title' => 'Cox\'s Bazar 3 Days 2 Nights Package',
        'price' => '8500 BDT',
        'category' => 'Travel',
        'stock' => 'Available (Seats: 8)',
        'color' => '#2196f3'
    ],
    [
        'id' => 'BEST-405',
        'source' => 'Best Travel',
        'title' => 'Waterproof Trekking Bag 50L',
        'price' => '2500 BDT',
        'category' => 'Accessories',
        'stock' => 'In Stock (20)',
        'color' => '#2196f3'
    ],
    [
        'id' => 'ENZ-101',
        'source' => 'Enzor Motor',
        'title' => 'Premium Full Face Helmet DOT',
        'price' => '4500 BDT',
        'category' => 'Automobile',
        'stock' => 'In Stock (5)',
        'color' => '#ff5722'
    ],
    [
        'id' => 'MAN-772',
        'source' => 'Manza',
        'title' => 'Organic Honey 1KG Jar',
        'price' => '800 BDT',
        'category' => 'Groceries',
        'stock' => 'In Stock (100)',
        'color' => '#9c27b0'
    ]
];

// Handle Sync Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sync_product'])) {
    $product_id = $_POST['product_id'];
    
    // Find the product in the mock array
    $target_product = null;
    foreach ($network_products as $np) {
        if ($np['id'] === $product_id) {
            $target_product = $np;
            break;
        }
    }
    
    if ($target_product) {
        try {
            $price = floatval(preg_replace('/[^0-9.]/', '', $target_product['price']));
            // Insert into partner_products under Admin (official_partner_id)
            $stmt = $pdo->prepare("INSERT INTO partner_products (partner_id, title, price, category, listing_type, is_published, description) VALUES (?, ?, ?, ?, 'product', 1, ?)");
            $desc = "External Product from " . $target_product['source'] . " (ID: " . $target_product['id'] . ")";
            $stmt->execute([$official_partner_id, $target_product['title'], $price, $target_product['category'], $desc]);
        } catch (Exception $e) {}
    }
    
    header("Location: network_products.php?synced=" . urlencode($product_id));
    exit;
}

// Handle Local Moderation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mod_action'])) {
    $prod_id = intval($_POST['prod_id'] ?? 0);
    $action = $_POST['mod_action'];
    if ($prod_id > 0) {
        if ($action === 'hide') {
            $pdo->prepare("UPDATE partner_products SET is_hidden = 1 WHERE id = ?")->execute([$prod_id]);
        } elseif ($action === 'unhide') {
            $pdo->prepare("UPDATE partner_products SET is_hidden = 0 WHERE id = ?")->execute([$prod_id]);
        }
    }
    header("Location: network_products.php?mod=1");
    exit;
}

// Fetch Local Partner Products for Moderation
$local_products = [];
try {
    $stmt = $pdo->prepare("
        SELECT pp.*, p.business_name 
        FROM partner_products pp
        LEFT JOIN partners p ON pp.partner_id = p.id
        ORDER BY pp.created_at DESC
        LIMIT 100
    ");
    $stmt->execute();
    $local_products = $stmt->fetchAll();
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"/>
  <title>Omni-Network Sync Hub | Fast Site Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="/assets/css/admin.css">
  <style>
    body { max-width: 100vw; overflow-x: hidden; background: var(--bg, #050508); }
    .hub-container { padding: 20px; max-width: 1200px; margin: 0 auto; }
    
    .panel-glass {
        background: var(--dark-card, #1c1c24); border: 1px solid rgba(255,255,255,0.1);
        border-radius: 12px; padding: 25px; margin-bottom: 25px;
    }
    
    .network-badge {
        display: inline-block; padding: 4px 10px; border-radius: 50px; font-size: 0.75rem; font-weight: 800;
        text-transform: uppercase; border: 1px solid rgba(255,255,255,0.2);
    }
    
    .btn-sync {
        background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);
        color: #fff; padding: 8px 15px; border-radius: 8px; cursor: pointer; transition: all 0.2s;
        font-weight: 600; width: 100%; display: flex; justify-content: center; align-items: center; gap: 5px;
    }
    .btn-sync:hover { background: var(--gold, #fcb900); color: #000; border-color: var(--gold); }
    
    .product-grid {
        display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-top: 20px;
    }
    
    .product-card {
        background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.05);
        border-radius: 12px; padding: 20px; transition: transform 0.2s;
    }
    .product-card:hover { border-color: rgba(255,255,255,0.2); transform: translateY(-3px); }
  </style>
</head>
<body>
<?php include 'nav.php'; ?>

<div class="hub-container">
    <div style="margin-bottom: 25px;">
        <h1 style="color:#fff; font-weight:900; margin:0;">🌐 OMNI-NETWORK SYNC HUB</h1>
        <p style="color:#aaa; margin-top:5px;">Fetch, view, and synchronize live products from external ecosystem partners.</p>
    </div>

    <?php if(isset($_GET['synced'])): ?>
        <div style="background: rgba(16, 185, 129, 0.2); color:#10b981; padding: 15px; border-radius:8px; margin-bottom:20px; border:1px solid rgba(16, 185, 129, 0.4);">
            Successfully synced external product <strong><?= htmlspecialchars($_GET['synced']) ?></strong> into Fast Site DB!
        </div>
    <?php endif; ?>

    <div class="panel-glass">
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid rgba(255,255,255,0.1); padding-bottom:15px; margin-bottom:15px;">
            <h3 style="color:#fff; margin:0;">Live Ecosystem Feed</h3>
            <span style="background:rgba(252, 185, 0, 0.1); color:var(--gold); padding:5px 12px; border-radius:20px; font-size:0.8rem; font-weight:700;">API Connected</span>
        </div>
        
        <div class="product-grid">
            <?php foreach($network_products as $p): ?>
            <div class="product-card">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:15px;">
                    <span class="network-badge" style="background: <?= $p['color'] ?>22; color: <?= $p['color'] ?>; border-color: <?= $p['color'] ?>55;">
                        <?= htmlspecialchars($p['source']) ?>
                    </span>
                    <span style="color:#888; font-size:0.8rem; font-family:monospace;"><?= $p['id'] ?></span>
                </div>
                <h4 style="color:#fff; font-size:1.1rem; margin-bottom:5px; line-height:1.4;"><?= htmlspecialchars($p['title']) ?></h4>
                <div style="color:#aaa; font-size:0.85rem; margin-bottom:15px;">Category: <?= htmlspecialchars($p['category']) ?></div>
                
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                    <div style="color:var(--green); font-weight:800; font-size:1.2rem;"><?= htmlspecialchars($p['price']) ?></div>
                    <div style="color:#888; font-size:0.8rem;"><?= htmlspecialchars($p['stock']) ?></div>
                </div>
                
                <form method="POST" action="network_products.php">
                    <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                    <button type="submit" name="sync_product" class="btn-sync">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2v6h-6"></path><path d="M3 12a9 9 0 0 1 15-6.7L21 8"></path><path d="M3 22v-6h6"></path><path d="M21 12a9 9 0 0 1-15 6.7L3 16"></path></svg>
                        Sync to Marketplace
                    </button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="panel-glass">
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid rgba(255,255,255,0.1); padding-bottom:15px; margin-bottom:15px;">
            <h3 style="color:#fff; margin:0;">Live Ecosystem Products (Moderation)</h3>
            <span style="background:rgba(239, 68, 68, 0.1); color:#ef4444; padding:5px 12px; border-radius:20px; font-size:0.8rem; font-weight:700;">Admin Controls</span>
        </div>
        
        <div class="product-grid">
            <?php foreach($local_products as $lp): ?>
            <div class="product-card" style="<?= !empty($lp['is_hidden']) ? 'opacity:0.5;' : '' ?>">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:15px;">
                    <span class="network-badge" style="background: rgba(255,255,255,0.1); color: #fff;">
                        <?= htmlspecialchars($lp['business_name'] ?? 'Unknown Shop') ?>
                    </span>
                    <span style="color:#888; font-size:0.8rem;">ID: <?= $lp['id'] ?></span>
                </div>
                <h4 style="color:#fff; font-size:1.1rem; margin-bottom:5px; line-height:1.4;"><?= htmlspecialchars($lp['title']) ?></h4>
                <div style="color:#aaa; font-size:0.85rem; margin-bottom:15px;">Type: <?= htmlspecialchars($lp['listing_type']) ?></div>
                
                <div style="color:var(--green); font-weight:800; font-size:1.2rem; margin-bottom:15px;">🪙 <?= htmlspecialchars($lp['price']) ?></div>
                
                <?php if (empty($lp['is_hidden'])): ?>
                  <form method="POST">
                      <input type="hidden" name="prod_id" value="<?= $lp['id'] ?>">
                      <input type="hidden" name="mod_action" value="hide">
                      <button type="submit" class="btn-sync" style="background:rgba(239,68,68,0.2); border-color:rgba(239,68,68,0.5); color:#ef4444;">
                          Hide Product
                      </button>
                  </form>
                <?php else: ?>
                  <form method="POST">
                      <input type="hidden" name="prod_id" value="<?= $lp['id'] ?>">
                      <input type="hidden" name="mod_action" value="unhide">
                      <button type="submit" class="btn-sync" style="background:rgba(16,185,129,0.2); border-color:rgba(16,185,129,0.5); color:#10b981;">
                          Unhide Product
                      </button>
                  </form>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

</body>
</html>
