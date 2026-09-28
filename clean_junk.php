<?php
// =========================================================================
// clean_junk.php — Hostinger Full Server Clutter Purger
// Access via: https://fastsite.best-travel.ltd/clean_junk.php
// Automatically purges backslash files, obsolete dev scripts, old zip archives,
// local sqlite databases, and dev folders from public_html.
// =========================================================================

function remove_dir_recursive($dir) {
    if (!is_dir($dir)) return false;
    $items = array_diff(scandir($dir), array('.', '..'));
    foreach ($items as $item) {
        $path = $dir . '/' . $item;
        if (is_dir($path)) {
            remove_dir_recursive($path);
        } else {
            @unlink($path);
        }
    }
    return @rmdir($dir);
}

$root = __DIR__;
$deleted_files = [];
$deleted_dirs = [];

// List of obsolete dev/test scripts and leftover files to clean from public_html
$obsolete_files = [
    'build_deploy.py', 'check.py', 'check_db.php', 'check_links.ps1', 
    'check_safety.php', 'check_schema.php', 'check_tables.php', 'clean_mess.php', 
    'cleanup.php', 'create_table.php', 'create_zip.py', 'create_zip_apk.py', 
    'db_schema.php', 'debug.php', 'diagnostic.php', 'dump_schema.php', 
    'extract_css.py', 'extract_user_css.py', 'fast_site_local.db', 
    'FASTSITE BLUE-PRINT.md', 'final_sync.php', 'fix_db.php', 'fix_kyc_db.php', 
    'get_schema.php', 'migrate_live.php', 'move_products.php', 'ref.php', 
    'run_migrations.php', 'schema_update.php', 'scratch.php', 'seed_shops.php', 
    'set_gemini.php', 'show_error.php', 'test_db.php', 'test_profile.php', 
    'unzip.php', 'update_config.py', 'update_db.php', 'update_layout.php', 
    'update_logo.php', 'upgrade_ui.py'
];

// List of obsolete dev folders to clean from public_html (if they exist)
$obsolete_dirs = [
    'AFFILIATE PARTNERS LOGO', 'apk file ADMIN', 'APK FILE USER', 
    'MEDIA PHOTO', 'SQL FILE', 'chatbot-server', 'whatsapp-bot', '_dev_tools', 'scratch'
];

if (is_dir($root)) {
    $items = scandir($root);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        
        $fullPath = $root . '/' . $item;
        $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));

        // 1. Delete backslash-corrupted files or folders
        if (strpos($item, '\\') !== false) {
            if (is_dir($fullPath)) {
                if (remove_dir_recursive($fullPath)) $deleted_dirs[] = $item;
            } else if (is_file($fullPath)) {
                if (@unlink($fullPath)) $deleted_files[] = $item;
            }
            continue;
        }

        // 2. Delete old ZIP archives sitting in root
        if ($ext === 'zip' && $item !== 'fastsite_update_20260727_Phase27.zip') {
            if (@unlink($fullPath)) $deleted_files[] = $item;
            continue;
        }

        // 3. Delete obsolete dev/test scripts
        if (in_array($item, $obsolete_files) || (strpos($item, 'create_zip_') === 0 && $ext === 'py')) {
            if (is_file($fullPath) && @unlink($fullPath)) {
                $deleted_files[] = $item;
            }
            continue;
        }

        // 4. Delete obsolete dev folders
        if (in_array($item, $obsolete_dirs) && is_dir($fullPath)) {
            if (remove_dir_recursive($fullPath)) {
                $deleted_dirs[] = $item;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <title>Hostinger Deep Server Cleaner</title>
  <style>
    body { font-family: system-ui, sans-serif; background: #0a0a0f; color: #e0e0e0; padding: 2rem; }
    h1 { color: #fcb900; }
    .box { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; padding: 1.5rem; max-width: 650px; }
    .ok { color: #10b981; font-weight: bold; }
    ul { background: #12121a; padding: 1rem 2rem; border-radius: 8px; max-height: 300px; overflow-y: auto; font-family: monospace; font-size: 0.85rem; }
    a { color: #fcb900; text-decoration: none; font-weight: bold; }
  </style>
</head>
<body>
  <div class="box">
    <h1>🧹 Hostinger Deep Server Cleaner</h1>
    
    <?php if (empty($deleted_files) && empty($deleted_dirs)): ?>
      <p class="ok">🎉 100% CLEAN! No clutter scripts, old zips, or dev folders found in public_html.</p>
    <?php else: ?>
      <?php if (!empty($deleted_files)): ?>
        <p class="ok">✅ Deleted <?= count($deleted_files) ?> obsolete clutter files &amp; old zips:</p>
        <ul>
          <?php foreach ($deleted_files as $f): ?>
            <li style="color:#10b981;"><?= htmlspecialchars($f) ?></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
      
      <?php if (!empty($deleted_dirs)): ?>
        <p class="ok">📂 Deleted <?= count($deleted_dirs) ?> unnecessary dev folders:</p>
        <ul>
          <?php foreach ($deleted_dirs as $d): ?>
            <li style="color:#3b82f6;"><?= htmlspecialchars($d) ?></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    <?php endif; ?>
    
    <p style="margin-top: 1.5rem;">
      <a href="index.php">← Go to Fast Site Homepage</a>
    </p>
  </div>
</body>
</html>
