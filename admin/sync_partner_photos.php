<?php
// =========================================================================
// admin/sync_partner_photos.php — Master Uploads & Photos Auto-Linker
// Scans /uploads/partners/ directory, matches files to products/shops,
// and ensures 100% synchronization between physical files and DB records.
// =========================================================================
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php'); exit;
}
require_once __DIR__ . '/../config.php';
header('Content-Type: text/html; charset=utf-8');

$uploadDir = dirname(__DIR__) . '/uploads/partners/';
$foundFiles = [];
$syncedProducts = [];
$syncedPartners = [];
$orphanedFiles = [];

if (is_dir($uploadDir)) {
    $items = scandir($uploadDir);
    foreach ($items as $f) {
        if ($f === '.' || $f === '..' || is_dir($uploadDir . $f)) continue;
        $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'])) {
            $foundFiles[] = [
                'name' => $f,
                'path' => '/uploads/partners/' . $f,
                'size' => filesize($uploadDir . $f),
                'mtime' => filemtime($uploadDir . $f)
            ];
        }
    }
}

// ── Action: Auto-Sync / Link Photos to DB ────────────────────────────────────
if (isset($_POST['sync_photos'])) {
    foreach ($foundFiles as $file) {
        $fname = $file['name'];

        // 1. Check if filename matches product pattern: prod_{product_id}_...
        if (preg_match('/^prod_(\d+)_/i', $fname, $m)) {
            $pid = (int)$m[1];
            
            // Verify product exists
            $chk = $pdo->prepare("SELECT id, title FROM partner_products WHERE id = ?");
            $chk->execute([$pid]);
            $prod = $chk->fetch(PDO::FETCH_ASSOC);

            if ($prod) {
                // Check if already in partner_product_images
                $imgChk = $pdo->prepare("SELECT id FROM partner_product_images WHERE product_id = ? AND (image_url = ? OR image_url = ?)");
                $imgChk->execute([$pid, $fname, '/uploads/partners/' . $fname]);
                if (!$imgChk->fetch()) {
                    // Check if product currently has any thumbnail
                    $thumbChk = $pdo->prepare("SELECT id FROM partner_product_images WHERE product_id = ? AND is_thumbnail = 1");
                    $thumbChk->execute([$pid]);
                    $isThumb = $thumbChk->fetch() ? 0 : 1;

                    $ins = $pdo->prepare("INSERT INTO partner_product_images (product_id, image_url, is_thumbnail) VALUES (?, ?, ?)");
                    $ins->execute([$pid, $fname, $isThumb]);
                    $syncedProducts[] = "Product #{$pid} ({$prod['title']}) ➔ Linked image: {$fname}";
                }
            } else {
                $orphanedFiles[] = $fname;
            }
        }

        // 2. Check if filename matches profile pic: profile_{partner_id}_... or profile_...
        elseif (preg_match('/^profile_(\d+)_/i', $fname, $m)) {
            $partnerId = (int)$m[1];
            $stmt = $pdo->prepare("UPDATE partners SET profile_pic = ? WHERE id = ?");
            $stmt->execute([$fname, $partnerId]);
            $syncedPartners[] = "Shop #{$partnerId} ➔ Updated Logo/Avatar: {$fname}";
        }

        // 3. Check if filename matches cover pic: cover_{partner_id}_... or cover_...
        elseif (preg_match('/^cover_(\d+)_/i', $fname, $m)) {
            $partnerId = (int)$m[1];
            $stmt = $pdo->prepare("UPDATE partners SET cover_pic = ? WHERE id = ?");
            $stmt->execute([$fname, $partnerId]);
            $syncedPartners[] = "Shop #{$partnerId} ➔ Updated Banner Cover: {$fname}";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Uploads & Partners Photo Sync — Fast Site Admin</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #07090e; color: #f1f5f9; padding: 2rem; }
.container { max-width: 1100px; margin: 0 auto; }
h1 { color: #fcb900; font-size: 1.8rem; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.6rem; }
p.sub { color: #94a3b8; font-size: 0.95rem; margin-bottom: 2rem; }
.kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
.kpi-box { background: #111420; border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 1.4rem; text-align: center; }
.kpi-num { font-size: 2.2rem; font-weight: 900; }
.kpi-label { font-size: 0.8rem; color: #94a3b8; margin-top: 0.3rem; text-transform: uppercase; }
.card { background: #111420; border: 1px solid rgba(255,255,255,0.08); border-radius: 14px; padding: 1.5rem; margin-bottom: 2rem; }
.card-title { color: #10b981; font-size: 1.15rem; font-weight: 800; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; }
.gallery-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 1.2rem; margin-top: 1rem; }
.gallery-card { background: #0b0d14; border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; overflow: hidden; display: flex; flex-direction: column; }
.gallery-img { width: 100%; height: 130px; object-fit: cover; background: #000; }
.gallery-info { padding: 0.8rem; font-size: 0.75rem; flex: 1; display: flex; flex-direction: column; justify-content: space-between; }
.gallery-name { word-break: break-all; color: #e2e8f0; font-weight: 600; margin-bottom: 0.4rem; font-family: monospace; }
.gallery-meta { color: #64748b; font-size: 0.7rem; }
.btn { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.5rem; border-radius: 8px; font-size: 0.95rem; font-weight: 700; text-decoration: none; cursor: pointer; border: none; }
.btn-primary { background: linear-gradient(135deg, #fcb900, #f59e0b); color: #000; }
.btn-secondary { background: rgba(255,255,255,0.08); color: #fff; }
.alert-ok { background: rgba(16,185,129,0.12); border: 1px solid #10b981; color: #34d399; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; }
</style>
</head>
<body>
<div class="container">
    <h1>📸 Uploads / Partners Photo Synchronization</h1>
    <p class="sub">Directory: <code>/uploads/partners/</code> &nbsp;|&nbsp; Server: <strong><?= php_uname('n') ?></strong></p>

    <?php if (!empty($syncedProducts) || !empty($syncedPartners)): ?>
    <div class="alert-ok">
        <h3 style="margin-bottom:0.5rem;">🎉 Synchronization Complete!</h3>
        <?php foreach ($syncedProducts as $sp): ?>
            <div>✓ <?= htmlspecialchars($sp) ?></div>
        <?php endforeach; ?>
        <?php foreach ($syncedPartners as $sp): ?>
            <div>✓ <?= htmlspecialchars($sp) ?></div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="kpi-grid">
        <div class="kpi-box">
            <div class="kpi-num" style="color:#fcb900;"><?= count($foundFiles) ?></div>
            <div class="kpi-label">Photos in uploads/partners/</div>
        </div>
        <div class="kpi-box">
            <div class="kpi-num" style="color:#10b981;"><?= count($syncedProducts) ?></div>
            <div class="kpi-label">Product Photos Linked</div>
        </div>
        <div class="kpi-box">
            <div class="kpi-num" style="color:#38bdf8;"><?= count($syncedPartners) ?></div>
            <div class="kpi-label">Shop Banners / Logos Synced</div>
        </div>
    </div>

    <form method="POST" style="margin-bottom:2rem;">
        <button type="submit" name="sync_photos" class="btn btn-primary">
            ⚡ 1-Click Auto-Link All Photos to Products & Shops
        </button>
        <a href="/admin" class="btn btn-secondary" style="margin-left:0.5rem;">← Back to Admin</a>
    </form>

    <div class="card">
        <div class="card-title">🖼️ Live Photos in <code>/uploads/partners/</code> (<?= count($foundFiles) ?>)</div>
        <?php if (empty($foundFiles)): ?>
            <p style="color:#64748b; padding:1.5rem; text-align:center;">No photo files currently found in <code>uploads/partners/</code>.</p>
        <?php else: ?>
            <div class="gallery-grid">
                <?php foreach ($foundFiles as $f): ?>
                <div class="gallery-card">
                    <img src="<?= htmlspecialchars($f['path']) ?>" class="gallery-img" alt="Photo" loading="lazy">
                    <div class="gallery-info">
                        <div class="gallery-name"><?= htmlspecialchars($f['name']) ?></div>
                        <div class="gallery-meta"><?= round($f['size'] / 1024, 1) ?> KB &nbsp;|&nbsp; <?= date('M d, H:i', $f['mtime']) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
