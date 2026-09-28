<?php
// =========================================================================
// shop_wrapper.php    v4: Super-App Iframe Wrapper (Phase 16)
// =========================================================================
session_start();
require_once __DIR__ . '/config.php';

$shop_id = $_GET['shop'] ?? '';
$shop_slug = str_replace('-', ' ', strtolower($shop_id));

// Fetch from partners
$stmt = $pdo->prepare("SELECT business_name, theme_color, website_url FROM partners WHERE LOWER(business_name) = :s AND status = 'approved' LIMIT 1");
$stmt->execute([':s' => $shop_slug]);
$partner = $stmt->fetch();

if ($partner) {
    $shop_data = [
        'name' => $partner['business_name'],
        'url'  => $partner['website_url'] ?? 'index.php?search=' . urlencode($partner['business_name']),
        'theme_color' => $partner['theme_color'] ?? '#fcb900'
    ];
} else {
    // Fallback or Official Shop
    if ($shop_slug === 'fast site official' || $shop_slug === 'fast-site-official') {
        $shop_data = [
            'name' => 'Fast Site Official',
            'url'  => 'index.php?search=Fast+Site+Official',
            'theme_color' => '#fcb900'
        ];
    } else {
        die("Shop not found or not approved.");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"/>
  <title><?= htmlspecialchars($shop_data['name']) ?> — Fast Site Global Marketplace</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"/>
  <style>
    /* Strict Mobile-Fast Rules */
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body, html { width: 100vw; height: 100vh; overflow: hidden; background: #0a0a0f; font-family: 'Inter', sans-serif; }
    
    .super-app-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 15px 20px;
        background: rgba(18, 18, 26, 0.95);
        border-bottom: 1px solid rgba(255,255,255,0.05);
        backdrop-filter: blur(10px);
        height: 60px;
        position: relative;
        z-index: 100;
    }
    .back-btn {
        color: #fff; text-decoration: none; font-size: 1.5rem; font-weight: 900;
    }
    .shop-title {
        color: #fff; font-weight: 800; font-size: 1.1rem;
        display: flex; align-items: center; gap: 8px;
    }
    .shop-badge {
        width: 10px; height: 10px; border-radius: 50%;
        background: <?= $shop_data['theme_color'] ?>;
        box-shadow: 0 0 10px <?= $shop_data['theme_color'] ?>;
    }
    .menu-icon { color: #fff; font-size: 1.2rem; cursor: pointer; }
    
    .wrapper-container {
        width: 100%;
        height: calc(100vh - 60px);
        position: relative;
    }
    
    .loading-overlay {
        position: absolute; top: 0; left: 0; width: 100%; height: 100%;
        background: #0a0a0f; display: flex; flex-direction: column;
        align-items: center; justify-content: center; z-index: 50;
        transition: opacity 0.5s ease;
    }
    .loading-text {
        color: <?= $shop_data['theme_color'] ?>;
        font-weight: 800; margin-top: 15px; letter-spacing: 1px;
    }
    .spinner {
        width: 40px; height: 40px; border: 4px solid rgba(255,255,255,0.1);
        border-left-color: <?= $shop_data['theme_color'] ?>; border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    @keyframes spin { 100% { transform: rotate(360deg); } }
    
    .dynamic-iframe {
        width: 100%; height: 100%; border: none; opacity: 0; transition: opacity 0.5s ease;
    }
  </style>
</head>
<body>

<div class="super-app-header">
    <a href="index.php" class="back-btn">&larr;</a>
    <div class="shop-title">
        <div class="shop-badge"></div>
        <?= htmlspecialchars($shop_data['name']) ?> 
    </div>
    <div class="menu-icon">⋮</div>
</div>

<div class="wrapper-container">
    <div class="loading-overlay" id="loading">
        <div class="spinner"></div>
        <div class="loading-text">Connecting to Live Dropship Inventory...</div>
    </div>
    <iframe src="<?= htmlspecialchars($shop_data['url']) ?>" class="dynamic-iframe" id="shopFrame" onload="hideLoading()"></iframe>
</div>

<script>
function hideLoading() {
    document.getElementById('loading').style.opacity = '0';
    setTimeout(() => {
        document.getElementById('loading').style.display = 'none';
        document.getElementById('shopFrame').style.opacity = '1';
    }, 500);
}
</script>

</body>
</html>
