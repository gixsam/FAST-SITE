<?php
/**
 * Fast Site 1-Click Server-Side Zip Extractor
 * Automatically extracts fastsite_apk_update.zip or fastsite_phase45.zip on Hostinger.
 */

$zipFiles = ['fastsite_apk_update.zip', 'fastsite_phase45.zip'];
$foundZip = null;

foreach ($zipFiles as $zf) {
    if (file_exists(__DIR__ . '/' . $zf)) {
        $foundZip = __DIR__ . '/' . $zf;
        break;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fast Site Auto-Extractor</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #08080c; color: #f1f5f9; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 20px; }
        .card { background: rgba(18, 20, 34, 0.95); border: 1px solid rgba(252, 185, 0, 0.4); border-radius: 16px; padding: 30px; max-width: 500px; width: 100%; box-shadow: 0 15px 35px rgba(0,0,0,0.7); text-align: center; }
        h1 { color: #fcb900; margin-top: 0; font-size: 1.5rem; }
        .btn { display: inline-block; background: linear-gradient(135deg, #fcb900, #ff9800); color: #08080c; font-weight: 800; padding: 14px 28px; border-radius: 50px; text-decoration: none; border: none; font-size: 1rem; cursor: pointer; margin-top: 20px; transition: transform 0.2s; }
        .btn:hover { transform: scale(1.04); }
        .log { background: #030408; padding: 15px; border-radius: 10px; text-align: left; font-family: monospace; font-size: 0.85rem; max-height: 250px; overflow-y: auto; color: #00e676; margin-top: 20px; }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: bold; background: rgba(0, 230, 118, 0.15); color: #00e676; }
    </style>
</head>
<body>
<div class="card">
    <h1>⚡ Fast Site Auto-Extractor</h1>
    <p>1-Click server update tool for Hostinger.</p>

    <?php if (isset($_GET['run']) && $_GET['run'] === 'extract'): ?>
        <?php if (!$foundZip): ?>
            <p style="color:#ff5252; font-weight:bold;">❌ Error: No zip archive found in this folder! Please upload fastsite_apk_update.zip or fastsite_phase45.zip to public_html first.</p>
        <?php else: ?>
            <div class="log">
                <?php
                $zip = new ZipArchive;
                $res = $zip->open($foundZip);
                if ($res === TRUE) {
                    echo "📦 Opening: " . basename($foundZip) . "<br>";
                    echo "📁 Total files in archive: " . $zip->numFiles . "<br>";
                    $zip->extractTo(__DIR__);
                    $zip->close();
                    echo "✅ <strong>100% Extracted and Overwritten Successfully!</strong><br>";
                    echo "⚡ APK & Codebase updated to latest build.<br>";
                } else {
                    echo "❌ Failed to open zip file (Error code: $res)<br>";
                }
                ?>
            </div>
            <p style="margin-top:20px;"><a href="/" class="btn">🚀 Open Marketplace</a></p>
        <?php endif; ?>
    <?php else: ?>
        <?php if ($foundZip): ?>
            <p><span class="badge">ZIP DETECTED</span> <strong><?= basename($foundZip) ?></strong> (<?= round(filesize($foundZip) / (1024*1024), 2) ?> MB)</p>
            <form method="GET">
                <input type="hidden" name="run" value="extract">
                <button type="submit" class="btn">⚡ Extract & Overwrite Files Now</button>
            </form>
        <?php else: ?>
            <p style="color:#ffb74d;">⚠️ Please upload <strong>fastsite_apk_update.zip</strong> or <strong>fastsite_phase45.zip</strong> to <code>public_html/</code> and refresh this page.</p>
        <?php endif; ?>
    <?php endif; ?>
</div>
</body>
</html>
