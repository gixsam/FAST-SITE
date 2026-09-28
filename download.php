<?php
// =========================================================================
// download.php — Digital Product Auto-Fulfillment & App Downloader
// =========================================================================
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$order_id = intval($_GET['order_id'] ?? 0);

// =========================================================
// 1. SECURE DIGITAL PRODUCT DOWNLOAD (PURCHASED ASSETS)
// =========================================================
if ($order_id > 0) {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /user/login.php');
        exit;
    }

    $user_id = $_SESSION['user_id'];

    // Verify buyer owns this order
    $stmt = $pdo->prepare("SELECT o.*, p.download_file 
                           FROM partner_orders o
                           LEFT JOIN partner_products p ON o.product_name = p.title
                           WHERE o.id = :oid AND o.user_id = :uid LIMIT 1");
    $stmt->execute([':oid' => $order_id, ':uid' => $user_id]);
    $order = $stmt->fetch();

    if (!$order) {
        die("<div style='background:#0a0a0f; color:#ff5252; text-align:center; padding:4rem; font-family:sans-serif;'><h2>Order Not Found</h2><p>You are not authorized to download files for this order.</p><a href='/user/partner_orders.php' style='color:#fcb900;'>← Back to My Orders</a></div>");
    }

    // Check digital file path
    $filePath = !empty($order['download_file']) ? $order['download_file'] : ($order['digital_file'] ?? null);

    if (!$filePath || !file_exists($filePath)) {
        // Look in uploads/products/ or uploads/messages/
        $altPath = 'uploads/products/' . basename($filePath ?? '');
        if (file_exists($altPath)) {
            $filePath = $altPath;
        } else {
            die("<div style='background:#0a0a0f; color:#fcb900; text-align:center; padding:4rem; font-family:sans-serif;'><h2>⚡ Instant Delivery Ready</h2><p>This product uses direct seller delivery or message download. Please check your order messages.</p><a href='/user/messages.php' style='color:#fcb900;'>Go to Chat &amp; Messages →</a></div>");
        }
    }

    // Stream the file securely
    $filename = basename($filePath);
    $fsize = filesize($filePath);
    $mime = mime_content_type($filePath) ?: 'application/octet-stream';

    header('Content-Description: File Transfer');
    header('Content-Type: ' . $mime);
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . $fsize);

    readfile($filePath);
    exit;
}

// =========================================================
// 2. NATIVE FAST SITE APP DOWNLOAD (PUBLIC PAGE)
// =========================================================
$settings = $pdo->query("SELECT setting_key, setting_value FROM homepage_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
$apk_name = $settings['apk_app_name'] ?? 'FAST SITE SUPER APP';
$apk_photo = $settings['apk_app_photo'] ?? '';

$page_title = "Download App — " . ($settings['site_name'] ?? 'FAST SITE');
require_once __DIR__ . '/includes/nav_public.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= htmlspecialchars($page_title) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800;900&family=Oswald:wght@700&display=swap" rel="stylesheet"/>
  <style>
    body { background: #0a0a0f; color: #f8f8f8; font-family: 'Inter', sans-serif; }
    .dl-wrap { min-height: 75vh; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 3rem 1.5rem; text-align: center; }
    .dl-card { background: rgba(18, 18, 26, 0.7); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 20px; padding: 3rem 2rem; max-width: 550px; box-shadow: 0 20px 50px rgba(0,0,0,0.5); backdrop-filter: blur(12px); }
    .dl-icon { font-size: 3.5rem; margin-bottom: 1rem; display: flex; justify-content: center; }
    .dl-icon img { width: 100px; height: 100px; border-radius: 20px; object-fit: cover; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
    .dl-title { font-family: 'Oswald', sans-serif; font-size: 2.2rem; color: var(--gold); margin-bottom: 0.8rem; letter-spacing: 0.5px; text-transform: uppercase; }
    .dl-desc { color: #9ca3af; font-size: 0.95rem; line-height: 1.6; margin-bottom: 2rem; }
    .btn-dl { display: inline-flex; align-items: center; gap: 8px; background: linear-gradient(135deg, #fcb900, #ff9100); color: #000; font-weight: 800; padding: 1rem 2.2rem; border-radius: 50px; text-decoration: none; font-size: 1rem; box-shadow: 0 8px 25px rgba(252, 185, 0, 0.35); transition: all 0.25s ease; }
    .btn-dl:hover { transform: translateY(-3px) scale(1.03); box-shadow: 0 12px 35px rgba(252, 185, 0, 0.5); }
  </style>
</head>
<body>
<div class="dl-wrap">
    <div class="dl-card">
        <div class="dl-icon">
            <?php if (!empty($apk_photo)): ?>
                <img src="<?= htmlspecialchars($apk_photo) ?>" alt="App Icon"/>
            <?php else: ?>
                📱
            <?php endif; ?>
        </div>
        <h1 class="dl-title"><?= htmlspecialchars($apk_name) ?></h1>
        <p class="dl-desc">
            Download our official Android application for instant escrow notifications, direct messaging with shop partners, and 1-tap mobile ordering!
        </p>
        <a href="/fastsite_storefront.apk" class="btn-dl" download>
            ⚡ Download APK (Direct)
        </a>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
