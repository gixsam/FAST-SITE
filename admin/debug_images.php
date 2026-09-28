<?php
// =========================================================================
// admin/debug_images.php — Image Diagnostic Tool
// Checks all product images in DB and verifies they exist on disk
// =========================================================================
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php'); exit;
}
require_once __DIR__ . '/../config.php';
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Image Diagnostic — Fast Site Admin</title>
<style>
body { font-family: 'Segoe UI', sans-serif; background: #0a0a0f; color: #e2e8f0; padding: 2rem; }
h1 { color: #fcb900; margin-bottom: 0.5rem; }
h2 { color: #10b981; margin-top: 2rem; }
table { width: 100%; border-collapse: collapse; margin-top: 1rem; font-size: 0.85rem; }
th { background: #1a1a2e; color: #fcb900; padding: 0.7rem 1rem; text-align: left; }
td { padding: 0.6rem 1rem; border-bottom: 1px solid rgba(255,255,255,0.08); vertical-align: middle; }
tr:hover td { background: rgba(255,255,255,0.03); }
.ok   { color: #10b981; font-weight: 700; }
.fail { color: #ef4444; font-weight: 700; }
.warn { color: #f59e0b; font-weight: 700; }
.none { color: #64748b; }
img.thumb { width: 80px; height: 55px; object-fit: cover; border-radius: 6px; border: 1px solid rgba(255,255,255,0.15); }
.pill { display: inline-block; padding: 0.2rem 0.6rem; border-radius: 20px; font-size: 0.75rem; font-weight: 700; }
.pill-ok   { background: rgba(16,185,129,0.15); color: #10b981; border: 1px solid #10b981; }
.pill-fail { background: rgba(239,68,68,0.15);  color: #ef4444;  border: 1px solid #ef4444; }
.pill-none { background: rgba(100,116,139,0.15); color: #94a3b8; border: 1px solid #64748b; }
.summary-box { background: #1a1a2e; border: 1px solid rgba(252,185,0,0.3); border-radius: 12px; padding: 1.5rem; margin: 1rem 0; display: flex; gap: 2rem; }
.summary-num { font-size: 2rem; font-weight: 900; }
.summary-label { font-size: 0.8rem; color: #94a3b8; margin-top: 0.2rem; }
</style>
</head>
<body>
<h1>🔍 Image Diagnostic Report</h1>
<p style="color:#94a3b8;">Server: <strong style="color:#fff;"><?= php_uname('n') ?></strong> &nbsp;|&nbsp; 
   Root: <strong style="color:#fff;"><?= __DIR__ ?></strong> &nbsp;|&nbsp; 
   Time: <strong style="color:#fff;"><?= date('Y-m-d H:i:s') ?></strong></p>

<?php
// ── 1. Check partner_product_images table ──────────────────────────────────
$total = 0; $ok = 0; $missing = 0; $noRecord = 0;
$rows = [];

try {
    $stmt = $pdo->query("
        SELECT ppi.id, ppi.product_id, ppi.image_url, ppi.is_thumbnail,
               pp.title AS product_title, pp.category, pt.business_name AS shop_name
        FROM partner_product_images ppi
        LEFT JOIN partner_products pp ON pp.id = ppi.product_id
        LEFT JOIN partners pt ON pt.id = pp.partner_id
        ORDER BY ppi.product_id ASC, ppi.is_thumbnail DESC
        LIMIT 200
    ");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total = count($rows);
} catch (Exception $e) {
    echo "<p class='fail'>❌ DB Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// ── 2. Check products with NO images at all ────────────────────────────────
$noImageProducts = [];
try {
    $stmt2 = $pdo->query("
        SELECT pp.id, pp.title, pp.category, pt.business_name AS shop_name
        FROM partner_products pp
        LEFT JOIN partner_product_images ppi ON ppi.product_id = pp.id
        LEFT JOIN partners pt ON pt.id = pp.partner_id
        WHERE ppi.id IS NULL AND pp.is_published = 1
        ORDER BY pp.created_at DESC
        LIMIT 100
    ");
    $noImageProducts = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    $noRecord = count($noImageProducts);
} catch (Exception $e) {}

// ── 3. Analyse each image_url ──────────────────────────────────────────────
$checkedRows = [];
foreach ($rows as $row) {
    $url = trim($row['image_url'] ?? '');
    $status = 'none';
    $resolvedPath = '';
    $displayUrl = '';

    if (empty($url)) {
        $status = 'empty';
    } elseif (preg_match('#^https?://#i', $url)) {
        // Remote URL — check if it's accessible
        $status = 'remote';
        $displayUrl = $url;
        // Local path — check multiple possible disk locations
        $clean = ltrim($url, '/');
        $possiblePaths = [
            dirname(__DIR__) . '/' . $clean,
            dirname(__DIR__) . '/uploads/partners/' . basename($clean),
            dirname(__DIR__) . '/uploads/products/' . basename($clean),
            dirname(__DIR__) . '/uploads/' . basename($clean),
            dirname(__DIR__) . '/assets/images/services/' . basename($clean)
        ];

        $found = false;
        foreach ($possiblePaths as $p) {
            if (file_exists($p) && is_file($p)) {
                $status = 'ok';
                $ok++;
                $resolvedPath = $p;
                $displayUrl = '/' . ltrim(str_replace('\\', '/', substr($p, strlen(dirname(__DIR__)))), '/');
                $found = true;
                break;
            }
        }

        if (!$found) {
            $status = 'missing';
            $missing++;
            $displayUrl = '/uploads/partners/' . basename($clean);
        }
    }

    $row['_status']       = $status;
    $row['_resolved']     = $resolvedPath;
    $row['_display_url']  = $displayUrl;
    $checkedRows[]        = $row;
}
?>

<!-- Summary -->
<div class="summary-box">
    <div>
        <div class="summary-num" style="color:#fcb900;"><?= $total ?></div>
        <div class="summary-label">Total Image Records</div>
    </div>
    <div>
        <div class="summary-num" style="color:#10b981;"><?= $ok ?></div>
        <div class="summary-label">✅ Files Found on Disk</div>
    </div>
    <div>
        <div class="summary-num" style="color:#ef4444;"><?= $missing ?></div>
        <div class="summary-label">❌ Files MISSING on Disk</div>
    </div>
    <div>
        <div class="summary-num" style="color:#f59e0b;"><?= $noRecord ?></div>
        <div class="summary-label">⚠️ Products With NO Image Record</div>
    </div>
</div>

<!-- ── SVG Fallback Check ────────────────────────────────────────── -->
<h2>🎨 SVG Fallback Assets Check</h2>
<?php
$svgFiles = [
    'nid_service.svg', 'driving_license.svg', 'passport_service.svg',
    'birth_certificate.svg', 'land_service.svg', 'logo_design.svg',
    'video_animation.svg', 'web_dev.svg', 'default_service.svg'
];
$svgBase = dirname(__DIR__) . '/assets/images/services/';
echo '<table><tr><th>SVG File</th><th>Disk Status</th><th>URL</th><th>Preview</th></tr>';
foreach ($svgFiles as $svg) {
    $path = $svgBase . $svg;
    $exists = file_exists($path);
    $url = '/assets/images/services/' . $svg;
    echo "<tr>";
    echo "<td><code>$svg</code></td>";
    echo "<td>" . ($exists ? "<span class='ok'>✅ EXISTS</span>" : "<span class='fail'>❌ MISSING</span>") . "</td>";
    echo "<td><code>$url</code></td>";
    echo "<td>" . ($exists ? "<img src='$url' class='thumb'>" : "—") . "</td>";
    echo "</tr>";
}
echo '</table>';
?>

<!-- ── Image Records Table ─────────────────────────────────────── -->
<h2>📋 partner_product_images Records (up to 200)</h2>
<?php if (empty($checkedRows)): ?>
<p class="warn">⚠️ No records found in partner_product_images table!</p>
<?php else: ?>
<table>
<tr>
    <th>ID</th><th>Product</th><th>Shop</th><th>Category</th>
    <th>image_url (stored)</th><th>Disk Status</th><th>Thumb</th>
</tr>
<?php foreach ($checkedRows as $r): 
    $st = $r['_status'];
    $stLabel = match($st) {
        'ok'      => "<span class='pill pill-ok'>✅ ON DISK</span>",
        'missing' => "<span class='pill pill-fail'>❌ MISSING</span>",
        'remote'  => "<span class='pill pill-ok'>🌐 REMOTE URL</span>",
        'empty'   => "<span class='pill pill-none'>— EMPTY</span>",
        default   => "<span class='pill pill-none'>?</span>",
    };
?>
<tr>
    <td><?= $r['id'] ?></td>
    <td><?= htmlspecialchars(substr($r['product_title'] ?? '—', 0, 35)) ?><?= ($r['is_thumbnail'] ? ' <span style="color:#fcb900;font-size:0.7rem;">★ THUMB</span>' : '') ?></td>
    <td><?= htmlspecialchars($r['shop_name'] ?? '—') ?></td>
    <td><?= htmlspecialchars($r['category'] ?? '—') ?></td>
    <td style="font-size:0.75rem; color:#94a3b8; max-width:280px; word-break:break-all;">
        <?= htmlspecialchars($r['image_url'] ?? '—') ?>
    </td>
    <td><?= $stLabel ?></td>
    <td>
        <?php if ($st === 'ok' || $st === 'remote'): ?>
            <img src="<?= htmlspecialchars($r['_display_url'] ?: $r['image_url']) ?>" class="thumb" onerror="this.style.opacity=0.3">
        <?php else: ?>—<?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>

<!-- ── Products With No Image Records ───────────────────────── -->
<h2>⚠️ Published Products With NO Image in DB (<?= $noRecord ?>)</h2>
<?php if (empty($noImageProducts)): ?>
<p class="ok">✅ All published products have at least one image record.</p>
<?php else: ?>
<table>
<tr><th>Product ID</th><th>Title</th><th>Shop</th><th>Category</th></tr>
<?php foreach ($noImageProducts as $np): ?>
<tr>
    <td><?= $np['id'] ?></td>
    <td><?= htmlspecialchars($np['title']) ?></td>
    <td><?= htmlspecialchars($np['shop_name'] ?? '—') ?></td>
    <td><?= htmlspecialchars($np['category'] ?? '—') ?></td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>

<br><br>
<a href="/admin" style="color:#fcb900; text-decoration:none;">← Back to Admin Dashboard</a>
</body>
</html>
