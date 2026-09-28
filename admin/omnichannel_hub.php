<?php
// =========================================================================
// admin/omnichannel_hub.php    v4: Omnichannel Multi-Site Publisher (Phase 15)
// =========================================================================
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php'); exit;
}
require_once __DIR__ . '/../config.php';

$sites = [
    'GIXSAM', 'BEST TRAVEL', 'ENZOR MOTOR', 'AYRA MART', 'MANZA', 'AFFI BANGLA'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"/>
  <title>Omnichannel Hub — Fast Site</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="/assets/css/admin.css">
  <style>
    /* Strict Mobile-Fast Rules */
    body { max-width: 100vw; overflow-x: hidden; }
    .hub-container { padding: 20px; max-width: 1000px; margin: 0 auto; }
    
    .card-glass {
        background: var(--dark-card, #1c1c24);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 12px;
        padding: 25px;
        margin-bottom: 25px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    }
    .upload-zone {
        border: 2px dashed rgba(255,255,255,0.2);
        border-radius: 12px;
        padding: 40px;
        text-align: center;
        background: rgba(0,0,0,0.2);
        cursor: pointer;
        transition: all 0.2s;
    }
    .upload-zone:hover { border-color: var(--brand, #fcb900); background: rgba(252,185,0,0.05); }
    .upload-zone h3 { margin: 0; color: #fff; }
    .upload-zone p { margin: 5px 0 0 0; color: #888; font-size: 0.9rem; }
    
    .target-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 15px;
        margin-top: 15px;
    }
    .checkbox-btn {
        display: block;
        position: relative;
        cursor: pointer;
        user-select: none;
    }
    .checkbox-btn input {
        position: absolute; opacity: 0; cursor: pointer; height: 0; width: 0;
    }
    .checkmark {
        display: flex; align-items: center; justify-content: center;
        padding: 12px;
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 8px;
        color: #fff;
        font-weight: 600;
        transition: all 0.2s;
    }
    .checkbox-btn input:checked ~ .checkmark {
        background: rgba(252,185,0,0.15);
        border-color: var(--brand, #fcb900);
        color: var(--brand, #fcb900);
    }
    
    .btn-ai-generate {
        width: 100%; padding: 15px;
        background: var(--brand, #fcb900); color: #000;
        border: none; border-radius: 8px;
        font-size: 1.1rem; font-weight: 800; cursor: pointer;
        display: flex; align-items: center; justify-content: center; gap: 10px;
        transition: transform 0.2s;
    }
    .btn-ai-generate:hover { transform: scale(1.02); }
    
    /* Review Drafts Section (Hidden initially) */
    #review-section { display: none; }
    .draft-card {
        background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1);
        border-radius: 8px; padding: 15px; margin-bottom: 15px;
    }
    .draft-title { font-weight: bold; color: var(--gold, #fcb900); margin-bottom: 10px; }
    .input-row { margin-bottom: 10px; }
    .input-row label { display: block; font-size: 0.8rem; color: #888; margin-bottom: 3px; }
    .input-row input, .input-row textarea {
        width: 100%; padding: 10px; border-radius: 6px;
        background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);
        color: #fff; font-family: 'Inter', sans-serif;
    }
    
    .btn-final-publish {
        background: #10b981; color: #fff;
        width: 100%; padding: 15px; border: none; border-radius: 8px;
        font-size: 1.1rem; font-weight: 800; cursor: pointer;
    }
  </style>
</head>
<body>
<?php include 'nav.php'; ?>

<div class="hub-container">
    <div style="margin-bottom: 25px;">
        <h1 style="color:#fff; font-weight:900; margin:0;">Omnichannel Hub</h1>
        <p style="color:#aaa; margin-top:5px;">Upload once. Broadcast everywhere via Gemini AI.</p>
    </div>

    <div class="card-glass">
        <h3 style="color:#fff; margin-top:0;">1. Source File</h3>
        <div class="upload-zone" onclick="document.getElementById('fileInput').click()">
            <h3 id="uploadText">📸 Click or Drag Photo Here</h3>
            <p>File will be sent to the Shared CDN</p>
            <input type="file" id="fileInput" style="display:none;" accept="image/*" onchange="document.getElementById('uploadText').innerText = this.files[0].name;">
        </div>
        
        <div class="input-row" style="margin-top: 15px;">
            <label>Base Product Name / Idea</label>
            <input type="text" id="baseIdea" placeholder="e.g. Leather Riding Gloves">
        </div>
    </div>

    <div class="card-glass">
        <h3 style="color:#fff; margin-top:0;">2. Select Targets</h3>
        <div class="target-grid">
            <?php foreach($sites as $site): ?>
            <label class="checkbox-btn">
                <input type="checkbox" name="targets" value="<?= $site ?>">
                <span class="checkmark"><?= $site ?></span>
            </label>
            <?php endforeach; ?>
        </div>
    </div>

    <button class="btn-ai-generate" onclick="generateDrafts()">
        ✨ Generate AI Drafts
    </button>

    <div id="review-section" class="card-glass" style="margin-top:25px; border-color: #10b981;">
        <h3 style="color:#fff; margin-top:0;">3. Review AI Drafts</h3>
        <p style="color:#aaa; font-size: 0.9rem;">Gemini has uniquely tailored the copy for each target platform.</p>
        
        <div id="draft-container">
            <!-- Populated via JS -->
        </div>
        
        <button class="btn-final-publish" onclick="finalPublish()">🚀 PUBLISH TO ALL CHECKED SITES</button>
    </div>
</div>

<script>
function generateDrafts() {
    const checkboxes = document.querySelectorAll('input[name="targets"]:checked');
    if(checkboxes.length === 0) {
        alert("Please select at least one target website.");
        return;
    }
    const baseIdea = document.getElementById('baseIdea').value;
    if(!baseIdea) {
        alert("Please enter a base product name.");
        return;
    }
    
    // Simulate Gemini API processing time
    const btn = document.querySelector('.btn-ai-generate');
    btn.innerHTML = '⏳ Gemini is writing drafts...';
    btn.disabled = true;
    
    setTimeout(() => {
        const container = document.getElementById('draft-container');
        container.innerHTML = '';
        
        checkboxes.forEach(box => {
            let site = box.value;
            let mockTitle = site === 'BEST TRAVEL' ? `Essential ${baseIdea} for your next Trip!` : `Premium ${baseIdea}`;
            let mockDesc = site === 'BEST TRAVEL' ? `Never travel without these ${baseIdea}. Perfect for the outdoors.` : `Top quality ${baseIdea} now available in stock at an unbeatable price.`;
            
            container.innerHTML += `
                <div class="draft-card">
                    <div class="draft-title">${site}</div>
                    <div class="input-row">
                        <label>Title</label>
                        <input type="text" value="${mockTitle}">
                    </div>
                    <div class="input-row">
                        <label>Description</label>
                        <textarea rows="2">${mockDesc}</textarea>
                    </div>
                    <div class="input-row">
                        <label>Price</label>
                        <input type="text" value="Contact for Price">
                    </div>
                </div>
            `;
        });
        
        btn.innerHTML = '✨ Generate AI Drafts';
        btn.disabled = false;
        document.getElementById('review-section').style.display = 'block';
        document.getElementById('review-section').scrollIntoView({behavior: 'smooth'});
    }, 1500);
}

function finalPublish() {
    alert("Publishing Sequence Initiated!\n1. File uploading to CDN...\n2. Records injecting into external databases...\n3. Silent Sync triggered for Global Marketplace.");
    // In production, this posts the data to omnichannel_api.php
}
</script>

</body>
</html>
