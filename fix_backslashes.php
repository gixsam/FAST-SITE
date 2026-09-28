<?php
// =========================================================================
// fix_backslashes.php — Universal Hostinger Linux Path & Backslash Normalizer
// Scans recursively for any files/directories containing Windows backslashes (\)
// and moves them into their correct Linux directory hierarchy.
// =========================================================================

header('Content-Type: text/html; charset=utf-8');
$rootDir = __DIR__;
$fixed = [];
$errors = [];
$directoriesCreated = [];

function scanAndFixBackslashes($dir, &$fixed, &$errors, &$directoriesCreated, $baseDir) {
    if (!is_dir($dir)) return;
    
    $items = @scandir($dir);
    if ($items === false) return;
    
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        
        $currentFullPath = $dir . DIRECTORY_SEPARATOR . $item;
        
        // Check if item contains backslash
        if (strpos($item, '\\') !== false) {
            $normalizedRel = str_replace('\\', '/', $item);
            
            // If scanning inside a subdirectory, compute proper target relative to that subdirectory or base
            $targetFullPath = $dir . '/' . $normalizedRel;
            $targetDir = dirname($targetFullPath);
            
            if (!is_dir($targetDir)) {
                if (@mkdir($targetDir, 0777, true)) {
                    $directoriesCreated[] = str_replace($baseDir, '', $targetDir);
                }
            }
            
            // If target file already exists and is different, remove or overwrite
            if (file_exists($targetFullPath) && $currentFullPath !== $targetFullPath) {
                @unlink($targetFullPath);
            }
            
            if (@rename($currentFullPath, $targetFullPath)) {
                $fixed[] = [
                    'from' => str_replace($baseDir, '', $currentFullPath),
                    'to'   => str_replace($baseDir, '', $targetFullPath)
                ];
                continue;
            } else {
                $errors[] = "Failed to rename: " . htmlspecialchars($item);
            }
        }
        
        // If it's a directory, recurse into it
        if (is_dir($currentFullPath) && strpos($item, '\\') === false) {
            scanAndFixBackslashes($currentFullPath, $fixed, $errors, $directoriesCreated, $baseDir);
        }
    }
}

// Execute scan
scanAndFixBackslashes($rootDir, $fixed, $errors, $directoriesCreated, $rootDir);

// If action requested to self-delete
$selfDeleted = false;
if (isset($_GET['delete_self']) && $_GET['delete_self'] == '1') {
    @unlink(__FILE__);
    $selfDeleted = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Path & Backslash Normalizer — Fast Site</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background: #090a10;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #f1f5f9;
            padding: 2.5rem 1.5rem;
            min-height: 100vh;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        .header-card {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.12), rgba(6, 182, 212, 0.08));
            border: 1px solid rgba(16, 185, 129, 0.3);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.5);
        }
        h1 {
            font-size: 1.8rem;
            font-weight: 800;
            color: #34d399;
            display: flex;
            align-items: center;
            gap: 0.6rem;
            margin-bottom: 0.5rem;
        }
        p.subtitle {
            color: #94a3b8;
            font-size: 0.95rem;
            line-height: 1.5;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat-box {
            background: #111422;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            padding: 1.2rem;
            text-align: center;
        }
        .stat-num {
            font-size: 2.2rem;
            font-weight: 900;
        }
        .stat-label {
            font-size: 0.8rem;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 0.3rem;
        }
        .card {
            background: #111422;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 14px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .card-title {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: #fbbf24;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .log-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        }
        .log-table th {
            text-align: left;
            padding: 0.6rem 0.8rem;
            background: rgba(255, 255, 255, 0.03);
            color: #94a3b8;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .log-table td {
            padding: 0.6rem 0.8rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        .tag-from {
            color: #f87171;
            background: rgba(239, 68, 68, 0.1);
            padding: 2px 6px;
            border-radius: 4px;
            display: inline-block;
        }
        .tag-to {
            color: #34d399;
            background: rgba(16, 185, 129, 0.1);
            padding: 2px 6px;
            border-radius: 4px;
            display: inline-block;
            font-weight: 600;
        }
        .arrow {
            color: #64748b;
            padding: 0 0.4rem;
        }
        .btn-group {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.4rem;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
        }
        .btn-primary {
            background: linear-gradient(135deg, #10b981, #059669);
            color: #ffffff;
        }
        .btn-primary:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        .btn-danger {
            background: rgba(239, 68, 68, 0.15);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.4);
        }
        .btn-danger:hover {
            background: rgba(239, 68, 68, 0.25);
        }
        .btn-secondary {
            background: rgba(255, 255, 255, 0.08);
            color: #f1f5f9;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-card">
            <h1>✨ Hostinger Backslash Path Normalizer</h1>
            <p class="subtitle">
                Automatically inspected your Hostinger server for Windows-style backslashes (<code>\</code>) in filenames, created corresponding Linux folders (<code>/</code>), and moved all files into their proper locations.
            </p>
        </div>

        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-num" style="color: #34d399;"><?= count($fixed) ?></div>
                <div class="stat-label">Files Fixed & Moved</div>
            </div>
            <div class="stat-box">
                <div class="stat-num" style="color: #60a5fa;"><?= count($directoriesCreated) ?></div>
                <div class="stat-label">Folders Created</div>
            </div>
            <div class="stat-box">
                <div class="stat-num" style="color: <?= count($errors) > 0 ? '#f87171' : '#94a3b8' ?>;"><?= count($errors) ?></div>
                <div class="stat-label">Errors Encountered</div>
            </div>
        </div>

        <?php if (!empty($fixed)): ?>
        <div class="card">
            <div class="card-title">📁 Moved & Restructured Files (<?= count($fixed) ?>)</div>
            <table class="log-table">
                <thead>
                    <tr>
                        <th style="width: 45%;">Original Extracted Name</th>
                        <th style="width: 10%;"></th>
                        <th style="width: 45%;">Fixed Linux Path</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($fixed as $f): ?>
                    <tr>
                        <td><span class="tag-from"><?= htmlspecialchars($f['from']) ?></span></td>
                        <td style="text-align: center;"><span class="arrow">➔</span></td>
                        <td><span class="tag-to"><?= htmlspecialchars($f['to']) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="card" style="text-align: center; padding: 2.5rem 1rem;">
            <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">🎉</div>
            <h3 style="color: #34d399; font-size: 1.3rem; margin-bottom: 0.4rem;">Clean & Healthy Directory Structure!</h3>
            <p style="color: #94a3b8; font-size: 0.9rem;">No files with backslashes found in your public_html folder. All directories and paths are in perfect Linux format.</p>
        </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
        <div class="card" style="border-color: rgba(239, 68, 68, 0.4);">
            <div class="card-title" style="color: #f87171;">⚠️ Warnings / Errors</div>
            <ul style="padding-left: 1.2rem; color: #fca5a5; font-size: 0.85rem;">
                <?php foreach ($errors as $err): ?>
                <li><?= htmlspecialchars($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <div class="btn-group">
            <a href="/" class="btn btn-primary">🏠 Go to Marketplace Home</a>
            <a href="/admin" class="btn btn-secondary">⚡ Admin Dashboard</a>
            <a href="?delete_self=1" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this fixer tool from your server?')">🗑️ Delete This Script</a>
        </div>
    </div>
</body>
</html>
