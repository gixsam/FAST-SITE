<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config.php';

if (empty($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process form
    $stmt = $pdo->prepare("REPLACE INTO homepage_settings (setting_key, setting_value) VALUES (?, ?)");
    foreach ($_POST as $key => $value) {
        if ($key === 'action') continue;
        $stmt->execute([$key, $value]);
    }
    $msg = 'Content Settings Updated successfully!';
}

// Load current settings
$settings = $pdo->query("SELECT setting_key, setting_value FROM homepage_settings")->fetchAll(PDO::FETCH_KEY_PAIR);

// Fallbacks if not set
$t = $settings['promo_title'] ?? '🎰 Hot Deal';
$s1 = $settings['promo_subtitle_1'] ?? 'MELBAT';
$s2 = $settings['promo_subtitle_2'] ?? 'APK';
$url = $settings['promo_url'] ?? 'https://omg10.com/4/10744356';
$img = $settings['promo_image'] ?? 'https://fastsitee.wordpress.com/wp-content/uploads/2026/02/att.lhaeh6rlmszydbv5r8aj5rxosjlq2txh6jdeqd_dmcq.png.jpeg';
$code = $settings['promo_code_text'] ?? '🎁 Use promo code <code>ml_2165959</code> to get up to <strong>12,000 BDT</strong> welcome bonus on first deposit.';

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>Content Manager — Admin</title>
<link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>

<div class="nav-bar">
    <a href="dashboard.php" class="nav-btn">← Dashboard</a>
    <a href="content_manager.php" class="nav-btn active">🖼️ Content Editor</a>
</div>

<?php if ($msg) echo "<div class='msg'>$msg</div>"; ?>

<div class="card">
    <h2>Edit Homepage Promo Section</h2>
    <form method="POST">
        <input type="hidden" name="action" value="update">
        
        <label>Promo Label (e.g. 🎰 Hot Deal)</label>
        <input type="text" name="promo_title" value="<?= htmlspecialchars($t) ?>" required>

        <label>Badge Highlight Word (e.g. MELBAT)</label>
        <input type="text" name="promo_subtitle_1" value="<?= htmlspecialchars($s1) ?>" required>

        <label>Badge Normal Word (e.g. APK)</label>
        <input type="text" name="promo_subtitle_2" value="<?= htmlspecialchars($s2) ?>" required>

        <label>Download Button / Promo URL Link</label>
        <input type="url" name="promo_url" value="<?= htmlspecialchars($url) ?>" required>

        <label>Promo Image Banner URL (Current Image Preview below)</label>
        <input type="url" name="promo_image" id="promo_image" value="<?= htmlspecialchars($img) ?>" required oninput="document.getElementById('p-img').src=this.value">
        <img src="<?= htmlspecialchars($img) ?>" id="p-img" class="preview-img">

        <label style="margin-top:1rem;">Promo Text / Code Box (Supports HTML <br> e.g `&lt;strong&gt;` blocks)</label>
        <textarea name="promo_code_text" rows="4" required><?= htmlspecialchars($code) ?></textarea>

        <button class="btn">💾 Save Changes to Website</button>
    </form>
</div>

</body>
</html>
