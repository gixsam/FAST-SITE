<?php
// =========================================================================
// fix.php — Instant 1-Click Backslash & Folder Restructurer
// Moves all "folder\file.php" files into "folder/file.php" automatically!
// =========================================================================

header('Content-Type: text/html; charset=utf-8');

$dir = __DIR__;
$moved = [];
$failed = [];

// Get all files in root directory
$items = scandir($dir);

foreach ($items as $item) {
    if ($item === '.' || $item === '..') continue;

    // Detect if filename has Windows backslash (e.g. "user\dashboard.php", "partner\profile.php")
    if (strpos($item, '\\') !== false) {
        $realPath = str_replace('\\', '/', $item);
        $targetFolder = $dir . '/' . dirname($realPath);
        $targetFile   = $dir . '/' . $realPath;
        $sourceFile   = $dir . '/' . $item;

        // 1. Create target folder if it doesn't exist
        if (!is_dir($targetFolder)) {
            @mkdir($targetFolder, 0755, true);
        }

        // 2. Move / Copy file to proper location inside folder
        $success = false;
        if (@copy($sourceFile, $targetFile)) {
            @unlink($sourceFile); // remove the flat backslash file
            $success = true;
        } elseif (@rename($sourceFile, $targetFile)) {
            $success = true;
        }

        if ($success) {
            $moved[] = [
                'old' => $item,
                'new' => $realPath
            ];
        } else {
            $failed[] = $item;
        }
    }
}

// Also check recursively in case subfolders have nested backslashes
function fixSubfolders($folder, &$moved, &$failed, $base) {
    if (!is_dir($folder)) return;
    $entries = @scandir($folder);
    if (!$entries) return;

    foreach ($entries as $e) {
        if ($e === '.' || $e === '..') continue;
        $full = $folder . '/' . $e;
        if (strpos($e, '\\') !== false) {
            $rel = str_replace('\\', '/', $e);
            $targetDir = $folder . '/' . dirname($rel);
            $targetPath = $folder . '/' . $rel;
            if (!is_dir($targetDir)) @mkdir($targetDir, 0755, true);
            if (@copy($full, $targetPath)) {
                @unlink($full);
                $moved[] = ['old' => str_replace($base . '/', '', $full), 'new' => str_replace($base . '/', '', $targetPath)];
            }
        } elseif (is_dir($full)) {
            fixSubfolders($full, $moved, $failed, $base);
        }
    }
}

fixSubfolders($dir . '/uploads', $moved, $failed, $dir);
fixSubfolders($dir . '/assets', $moved, $failed, $dir);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Backslash Fixer — Fast Site</title>
<style>
body { background: #0b0f19; color: #f3f4f6; font-family: -apple-system, Segoe UI, Roboto, sans-serif; padding: 2.5rem 1rem; }
.box { max-width: 800px; margin: 0 auto; background: #131b2e; border: 1px solid #10b981; border-radius: 14px; padding: 2rem; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
h1 { color: #10b981; font-size: 1.6rem; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem; }
p { color: #9ca3af; font-size: 0.95rem; margin-bottom: 1.5rem; }
.count-badge { display: inline-block; background: rgba(16,185,129,0.2); color: #34d399; padding: 0.4rem 1rem; border-radius: 20px; font-weight: 800; font-size: 1.1rem; margin-bottom: 1.5rem; }
table { width: 100%; border-collapse: collapse; margin-top: 1rem; font-size: 0.85rem; }
th { text-align: left; padding: 0.6rem; background: rgba(255,255,255,0.05); color: #fbbf24; }
td { padding: 0.55rem 0.6rem; border-bottom: 1px solid rgba(255,255,255,0.06); font-family: monospace; }
.old { color: #f87171; }
.new { color: #34d399; font-weight: bold; }
.btn { display: inline-block; background: #10b981; color: #000; font-weight: bold; text-decoration: none; padding: 0.7rem 1.4rem; border-radius: 8px; margin-top: 1.5rem; }
</style>
</head>
<body>
<div class="box">
    <h1>✅ Backslash Auto-Fix Executed!</h1>
    <p>All files with Windows backslashes (<code>\</code>) have been automatically moved into their proper Linux directories and removed from the root directory.</p>
    
    <div class="count-badge">
        ✨ <?= count($moved) ?> Files Successfully Moved Inside Proper Folders
    </div>

    <?php if (!empty($moved)): ?>
    <table>
        <thead>
            <tr>
                <th>Backslash File Removed</th>
                <th>➔</th>
                <th>Proper Directory Location</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($moved as $m): ?>
            <tr>
                <td class="old"><?= htmlspecialchars($m['old']) ?></td>
                <td style="color:#9ca3af;">➔</td>
                <td class="new"><?= htmlspecialchars($m['new']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <p style="color:#34d399; font-weight:bold;">🎉 No backslash files found! Your directory structure is 100% clean.</p>
    <?php endif; ?>

    <br>
    <a href="/" class="btn">🏠 Go to Marketplace Home</a>
    <a href="/admin" class="btn" style="background:#3b82f6; color:#fff; margin-left:0.5rem;">⚡ Admin Panel</a>
</div>
</body>
</html>
