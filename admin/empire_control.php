<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/../config.php';
include 'nav.php'; // Includes standard Fast Site admin styling

// The Empire Matrix - All 7 Websites
$empire_sites = [
    [
        'name' => 'Ayra Mart',
        'domain' => 'https://atayramart.com',
        'desc' => 'Live Fashion Ecommerce',
        'icon' => '👗',
        'color' => '#e91e63'
    ],
    [
        'name' => 'Enzor Motor',
        'domain' => 'https://enzor.best-travel.ltd',
        'desc' => 'Automobile Parts',
        'icon' => '🚗',
        'color' => '#ff5722'
    ],
    [
        'name' => 'Best Travel',
        'domain' => 'https://best-travel.ltd',
        'desc' => 'Travel Agency',
        'icon' => '✈️',
        'color' => '#2196f3'
    ],
    [
        'name' => 'Affi Bangla',
        'domain' => 'https://affibangla.best-travel.ltd',
        'desc' => 'Affiliate Aggregator',
        'icon' => '🌐',
        'color' => '#4caf50'
    ],
    [
        'name' => 'Manza',
        'domain' => 'https://manza.best-travel.ltd',
        'desc' => 'General Store',
        'icon' => '🛒',
        'color' => '#9c27b0'
    ],
    [
        'name' => 'GixSam (Sayam)',
        'domain' => 'https://gixsam.best-travel.ltd',
        'desc' => 'Personal Landing Page',
        'icon' => '👨‍💻',
        'color' => '#607d8b',
        'editor_link' => 'gixsam_editor.php'
    ]
];

// Generate Master SSO Token
// In the future, the child sites will verify this token using their fast_site_receiver.php file.
$master_token = hash('sha256', 'FAST_SITE_MASTER_' . time() . rand(1000,9999));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Empire Master Control | Fast Site Admin</title>
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>
    <div class="empire-wrap">
        <div class="empire-header">
            <h1>👑 EMPIRE MASTER CONTROL</h1>
            <p>Access and manage all 7 of your websites instantly from one centralized Super Admin hub.</p>
        </div>
        
        <div class="grid">
            <?php foreach ($empire_sites as $site): ?>
            <div class="site-card">
                <div class="site-header">
                    <div class="site-icon" style="color: <?= $site['color'] ?>; border-color: <?= $site['color'] ?>55; background: <?= $site['color'] ?>22;">
                        <?= $site['icon'] ?>
                    </div>
                    <div class="site-info">
                        <h3><?= $site['name'] ?></h3>
                        <p><?= $site['desc'] ?></p>
                    </div>
                </div>
                <div style="font-family:monospace; font-size:0.75rem; color:var(--muted); margin-bottom:1.5rem; background:rgba(0,0,0,0.2); padding:0.5rem; border-radius:6px;">
                    <?= $site['domain'] ?>
                </div>
                <!-- 
                In a full production environment, the URL below would append ?sso_token=<?= $master_token ?> 
                For now, we load the base domain since some sites use different admin paths or return 404 for /admin/.
                -->
                <div style="display: flex; gap: 0.5rem; margin-top: auto; flex-wrap: wrap;">
                    <button class="btn-launch" onclick="launchSite('<?= $site['name'] ?>', '<?= $site['domain'] ?>')" style="flex: 2; min-width: 120px;">
                        ⚡ Open Frame
                    </button>
                    <?php if (!empty($site['editor_link'])): ?>
                    <a href="<?= $site['editor_link'] ?>" class="btn-launch" style="flex: 1.5; background: var(--gold); color: #000; text-decoration: none; font-weight: 800; text-align: center;">
                        ✏️ Edit Content
                    </a>
                    <?php endif; ?>
                    <a href="<?= $site['domain'] ?>" target="_blank" class="btn-launch" style="flex: 1; background: rgba(255,255,255,0.1); color: #fff; text-decoration: none; text-align: center;">
                        ↗️ Live
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Iframe Modal -->
    <div class="iframe-modal" id="masterModal">
        <div class="iframe-header">
            <h2 id="modalTitle">Loading...</h2>
            <button class="btn-close" onclick="closeModal()">✖ Close Connection</button>
        </div>
        <iframe id="masterIframe" src=""></iframe>
    </div>

    <script>
        function launchSite(name, url) {
            document.getElementById('modalTitle').innerText = 'Controlling: ' + name;
            document.getElementById('masterIframe').src = url;
            document.getElementById('masterModal').classList.add('active');
        }
        function closeModal() {
            document.getElementById('masterModal').classList.remove('active');
            document.getElementById('masterIframe').src = '';
        }
    </script>
</body>
</html>
