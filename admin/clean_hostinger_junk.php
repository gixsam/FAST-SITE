<?php
// =========================================================================
// admin/clean_hostinger_junk.php
// Automatically cleans up backslash-corrupted files in public_html
// caused by Windows Zip extractions.
// =========================================================================
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$target_dir = __DIR__ . '/..'; // public_html root
$deleted_files = [];

if (is_dir($target_dir)) {
    $files = scandir($target_dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        // Match files containing backslashes in their names (e.g. admin\accounts.php)
        if (strpos($file, '\\') !== false) {
            $filePath = $target_dir . '/' . $file;
            if (is_file($filePath)) {
                if (@unlink($filePath)) {
                    $deleted_files[] = $file;
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <title>Hostinger Clutter Cleaner — Fast Site Admin</title>
  <style>
    body { font-family: system-ui, sans-serif; background: #0a0a0f; color: #e0e0e0; padding: 2rem; }
    h1 { color: #fcb900; }
    .box { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; padding: 1.5rem; max-width: 600px; }
    .ok { color: #10b981; font-weight: bold; }
    ul { background: #12121a; padding: 1rem 2rem; border-radius: 8px; max-height: 250px; overflow-y: auto; font-family: monospace; font-size: 0.85rem; }
    a { color: #fcb900; text-decoration: none; font-weight: bold; }
  </style>
</head>
<body>
  <div class="box">
    <h1>🧹 Hostinger Clutter Cleaner</h1>
    <?php if (empty($deleted_files)): ?>
      <p class="ok">✅ No corrupted backslash files found in public_html! Your server is clean.</p>
    <?php else: ?>
      <p class="ok">✅ Successfully deleted <?= count($deleted_files) ?> clutter files with backslashes:</p>
      <ul>
        <?php foreach ($deleted_files as $df): ?>
          <li><?= htmlspecialchars($df) ?></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
    <p style="margin-top: 1.5rem;">
      <a href="dashboard.php">← Back to Admin Dashboard</a>
    </p>
  </div>
</body>
</html>
