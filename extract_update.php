<?php
/**
 * Fast Site 1-Click Server-Side Zip Extractor
 * Automatically detects and extracts any fastsite_*.zip on Hostinger.
 */

// Auto-detect any fastsite_*.zip, prioritizing newest
$allZips = glob(__DIR__ . '/fastsite_*.zip');
$foundZip = null;

if (!empty($allZips)) {
    usort($allZips, function($a, $b) {
        return filemtime($b) - filemtime($a);
    });
    $foundZip = $allZips[0];
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
        .card { background: rgba(18, 20, 34, 0.95); border: 1px solid rgba(252, 185, 0, 0.4); border-radius: 16px; padding: 30px; max-width: 540px; width: 100%; box-shadow: 0 15px 35px rgba(0,0,0,0.7); text-align: center; }
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
            <p style="color:#ff5252; font-weight:bold;">❌ Error: No fastsite_*.zip archive found in public_html! Please upload your latest phase zip first.</p>
        <?php else: ?>
            <div class="log">
                <?php
                $zip = new ZipArchive;
                $res = $zip->open($foundZip);
                if ($res === TRUE) {
                    echo "📦 Opening: " . basename($foundZip) . "<br>";
                    echo "📁 Total files in archive: " . $zip->numFiles . "<br>";
                    $extracted = 0;
                    $skipped = 0;
                    for ($i = 0; $i < $zip->numFiles; $i++) {
                        $filename = $zip->getNameIndex($i);
                        $lower = strtolower($filename);
                        // Strict security skip for .env and uploads/
                        if (strpos($lower, '.env') !== false || strpos($lower, 'uploads/') === 0) {
                            $skipped++;
                            continue;
                        }
                        $zip->extractTo(__DIR__, $filename);
                        $extracted++;
                    }
                    $zip->close();
                    echo "✅ <strong>Extracted $extracted files successfully!</strong><br>";
                    if ($skipped > 0) {
                        echo "🛡️ Safely protected $skipped sensitive files (.env / uploads).<br>";
                    }
                    echo "⚡ Codebase updated to latest build.<br>";
                } else {
                    echo "❌ Failed to open zip file (Error code: $res)<br>";
                }
                ?>
            </div>
            <p style="margin-top:20px;"><a href="/" class="btn">🚀 Open Marketplace</a></p>
        <?php endif; ?>
    <?php else: ?>
        <?php if ($foundZip): ?>
            <p><span class="badge">ZIP DETECTED</span> <strong><?= basename($foundZip) ?></strong> (<?= round(filesize($foundZip) / 1024, 2) ?> KB)</p>
            <p style="color:#94a3b8; font-size:0.85rem;">Last modified: <?= date('Y-m-d H:i:s', filemtime($foundZip)) ?></p>
            <form method="GET">
                <input type="hidden" name="run" value="extract">
                <button type="submit" class="btn">⚡ Extract & Overwrite Files Now</button>
            </form>
        <?php else: ?>
            <p style="color:#ffb74d;">⚠️ Please upload <strong>fastsite_phase105.zip</strong> to <code>public_html/</code> and refresh this page.</p>
        <?php endif; ?>
    <?php endif; ?>
</div>
</body>
</html>
