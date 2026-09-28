<?php
// =========================================================================
// admin/network.php    v4: Network Command Center (Phase 17)
// =========================================================================
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php'); exit;
}
require_once __DIR__ . '/../config.php';

$sites = [
    ['name' => 'GIXSAM', 'url' => 'https://gixsam.com', 'format' => 'Personal Landing Page'],
    ['name' => 'BEST TRAVEL', 'url' => 'https://besttravel.com', 'format' => 'Travel Blog'],
    ['name' => 'ENZOR MOTOR', 'url' => 'https://enzormotor.com', 'format' => 'E-Shop'],
    ['name' => 'AYRA MART', 'url' => 'https://ayramart.com', 'format' => 'E-Commerce'],
    ['name' => 'MANZA', 'url' => 'https://manza.com', 'format' => 'Informative'],
    ['name' => 'AFFI BANGLA', 'url' => 'https://affibangla.com', 'format' => 'Affiliate'],
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"/>
  <title>Network Command Center — Fast Site</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="/assets/css/admin.css">
  <style>
    /* Strict Mobile-Fast Rules from Phase 18 */
    body { max-width: 100vw; overflow-x: hidden; }
    .network-container {
        padding: 20px;
        max-width: 1200px;
        margin: 0 auto;
    }
    .network-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }
    .site-card {
        background: var(--dark-card, #1c1c24);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        transition: transform 0.2s;
    }
    .site-card:hover {
        transform: translateY(-5px);
        border-color: var(--brand, #fcb900);
    }
    .site-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 1px solid rgba(255,255,255,0.05);
    }
    .site-title {
        font-size: 1.2rem;
        font-weight: 800;
        color: #fff;
    }
    .site-badge {
        font-size: 0.75rem;
        padding: 4px 8px;
        background: rgba(252,185,0,0.15);
        color: var(--gold, #fcb900);
        border-radius: 4px;
        font-weight: 600;
    }
    .btn-group {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-bottom: 15px;
    }
    .btn-sso {
        flex: 1;
        text-align: center;
        padding: 8px;
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.1);
        color: #fff;
        text-decoration: none;
        border-radius: 6px;
        font-size: 0.85rem;
        font-weight: 600;
        transition: all 0.2s;
    }
    .btn-sso:hover {
        background: rgba(255,255,255,0.15);
    }
    .btn-upload {
        width: 100%;
        text-align: center;
        padding: 12px;
        background: var(--brand, #fcb900);
        color: #000;
        border: none;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: 800;
        cursor: pointer;
        transition: transform 0.2s;
    }
    .btn-upload:hover {
        transform: scale(1.02);
    }
    
    /* Upload Modal */
    .upload-modal {
        display: none;
        position: fixed;
        top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.85);
        backdrop-filter: blur(5px);
        z-index: 9999;
        align-items: center;
        justify-content: center;
    }
    .upload-modal-content {
        background: var(--dark-card, #1c1c24);
        padding: 30px;
        border-radius: 16px;
        width: 90%;
        max-width: 500px;
        border: 1px solid rgba(255,255,255,0.1);
        box-shadow: 0 10px 40px rgba(0,0,0,0.5);
    }
    .upload-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    .upload-modal-title {
        font-size: 1.3rem;
        font-weight: 800;
        color: #fff;
    }
    .close-btn {
        background: none;
        border: none;
        color: #fff;
        font-size: 1.5rem;
        cursor: pointer;
    }
    .modal-options {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }
    .modal-option-btn {
        padding: 15px;
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.1);
        color: #fff;
        border-radius: 8px;
        text-align: left;
        cursor: pointer;
        transition: all 0.2s;
    }
    .modal-option-btn:hover {
        background: rgba(252,185,0,0.1);
        border-color: var(--brand, #fcb900);
    }
    .modal-option-title {
        font-weight: 700;
        font-size: 1.1rem;
        margin-bottom: 5px;
    }
    .modal-option-desc {
        font-size: 0.85rem;
        color: #aaa;
    }
  </style>
</head>
<body>

<?php include 'nav.php'; ?>

<div class="network-container">
    <div style="margin-bottom: 20px;">
        <h1 style="color:#fff; font-weight:900; margin:0;">Network Command Center</h1>
        <p style="color:#aaa; margin-top:5px;">Manage all 6 connected platforms from this centralized hub.</p>
    </div>

    <div class="network-grid">
        <?php foreach($sites as $site): ?>
        <div class="site-card">
            <div class="site-header">
                <div class="site-title"><?= htmlspecialchars($site['name']) ?></div>
                <div class="site-badge"><?= htmlspecialchars($site['format']) ?></div>
            </div>
            
            <div style="font-size: 0.8rem; color: #888; margin-bottom: 10px;">SSO Teleport Links (No Password Required)</div>
            <div class="btn-group">
                <button class="btn-sso" onclick="triggerSSO('<?= htmlspecialchars($site['name']) ?>', 'admin')">Admin</button>
                <button class="btn-sso" onclick="triggerSSO('<?= htmlspecialchars($site['name']) ?>', 'user')">User</button>
                <button class="btn-sso" onclick="triggerSSO('<?= htmlspecialchars($site['name']) ?>', 'shop')">Shop</button>
            </div>

            <button class="btn-upload" onclick="openUploadModal('<?= htmlspecialchars($site['name']) ?>')">
                ⇧ UPLOAD
            </button>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Local Upload Modal -->
<div class="upload-modal" id="uploadModal">
    <div class="upload-modal-content">
        <div class="upload-modal-header">
            <div class="upload-modal-title" id="modalSiteName">Upload to SITE</div>
            <button class="close-btn" onclick="closeUploadModal()">&times;</button>
        </div>
        
        <div class="modal-options">
            <button class="modal-option-btn" onclick="alert('Opening Profile/Cover Cropper tool... saving directly to Shared CDN and instantly syncing.')">
                <div class="modal-option-title">📸 Update Profile / Cover Photo</div>
                <div class="modal-option-desc">Saves to Shared CDN & updates target website instantly without redirecting.</div>
            </button>
            
            <button class="modal-option-btn" onclick="alert('Opening dynamic product form for this specific website...')">
                <div class="modal-option-title">📦 Upload Product</div>
                <div class="modal-option-desc">Fill out credentials and push inventory directly to this specific website's database locally.</div>
            </button>
        </div>
    </div>
</div>

<script>
function openUploadModal(siteName) {
    document.getElementById('modalSiteName').textContent = 'Upload to ' + siteName;
    document.getElementById('uploadModal').style.display = 'flex';
}
function closeUploadModal() {
    document.getElementById('uploadModal').style.display = 'none';
}

function triggerSSO(siteName, role) {
    // Show a small loading state if desired
    const formData = new FormData();
    formData.append('site', siteName);
    formData.append('role', role);

    fetch('network_sso.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if(data.status === 'success') {
            window.open(data.teleport_url, '_blank');
        } else {
            alert('SSO Error: ' + data.message);
        }
    })
    .catch(err => {
        alert('Network Error occurred while generating Magic Link.');
        console.error(err);
    });
}
</script>

</body>
</html>
