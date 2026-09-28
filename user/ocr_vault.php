<?php
session_start();
require_once __DIR__ . '/../config.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: /user/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_ocr') {
    $nid_number = trim($_POST['nid_number'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    
    // Save to users table or a separate vault table
    // For simplicity, we'll store in extra_details or create columns. 
    // We already have extra_details, let's append it there if columns don't exist.
    $details = json_decode($user['extra_details'] ?: '{}', true);
    if(!is_array($details)) $details = [];
    $details['ocr_nid'] = $nid_number;
    $details['ocr_name'] = $full_name;
    
    $upd = $pdo->prepare("UPDATE users SET extra_details = ? WHERE id = ?");
    if ($upd->execute([json_encode($details), $user_id])) {
        $msg = "Vault data saved successfully!";
        // Refresh
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
    } else {
        $err = "Failed to save data.";
    }
}
$details = json_decode($user['extra_details'] ?: '{}', true) ?: [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NID & Document OCR Fill</title>
    <link rel="stylesheet" href="/assets/css/user.css">
    <!-- Load Tesseract.js -->
    <script src="https://cdn.jsdelivr.net/npm/tesseract.js@4/dist/tesseract.min.js"></script>
</head>
<body>
    <div class="container">
        <a href="profile.php" style="color:var(--brand); text-decoration:none; display:inline-block; margin-bottom:1rem;">← Back to Profile</a>
        <h2>NID & Document OCR Vault</h2>
        <p style="color:#888;">Upload your NID or Passport to automatically extract and save your details.</p>

        <?php if($msg) echo "<div class='msg succ'>$msg</div>"; ?>
        <?php if($err) echo "<div class='msg err'>$err</div>"; ?>

        <div class="upload-area" onclick="document.getElementById('docInput').click()">
            <div id="uploadText">Click or Tap here to select document</div>
            <img id="preview" src="#" alt="Document Preview">
        </div>
        <input type="file" id="docInput" accept="image/*" style="display:none;" onchange="handleFile(this)">
        
        <div id="loader">Running AI OCR Scan... Please wait.</div>

        <form method="POST" id="ocrForm" style="display:none;">
            <input type="hidden" name="action" value="save_ocr">
            <label>Extracted Name</label>
            <input type="text" name="full_name" id="ocrName" value="<?= htmlspecialchars($details['ocr_name'] ?? '') ?>" required>
            
            <label>Extracted ID Number</label>
            <input type="text" name="nid_number" id="ocrNid" value="<?= htmlspecialchars($details['ocr_nid'] ?? '') ?>" required>
            
            <button type="submit" class="btn">Save to Secure Vault</button>
        </form>
        
        <?php if(isset($details['ocr_nid'])): ?>
            <div style="margin-top:2rem; padding-top:1rem; border-top:1px solid rgba(255,255,255,0.1);">
                <h3 style="margin-top:0;">Saved Vault Data</h3>
                <p><strong>Name:</strong> <?= htmlspecialchars($details['ocr_name']) ?></p>
                <p><strong>ID Number:</strong> <?= htmlspecialchars($details['ocr_nid']) ?></p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function handleFile(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('preview').src = e.target.result;
                    document.getElementById('preview').style.display = 'block';
                    document.getElementById('uploadText').style.display = 'none';
                    runOCR(input.files[0]);
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        function runOCR(file) {
            document.getElementById('loader').style.display = 'block';
            document.getElementById('ocrForm').style.display = 'none';
            
            Tesseract.recognize(
                file,
                'eng', // Load English
                { logger: m => console.log(m) }
            ).then(({ data: { text } }) => {
                document.getElementById('loader').style.display = 'none';
                document.getElementById('ocrForm').style.display = 'block';
                
                // Simple regex extraction logic for demo purposes
                // Real NID parsing requires strict regex per country format.
                const lines = text.split('\n').filter(l => l.trim().length > 0);
                
                let foundName = '';
                let foundNid = '';
                
                lines.forEach(line => {
                    const upper = line.toUpperCase();
                    if(upper.includes('NAME') && !foundName) {
                        foundName = line.replace(/.*NAME/i, '').replace(/[:;-]/g, '').trim();
                    }
                    if((upper.includes('NO') || upper.includes('ID') || upper.includes('NID')) && /\d{9,}/.test(line)) {
                        foundNid = line.match(/\d{9,}/)[0];
                    }
                });
                
                document.getElementById('ocrName').value = foundName;
                document.getElementById('ocrNid').value = foundNid;
                
                if(!foundName && !foundNid) {
                    alert("OCR couldn't automatically find Name/ID. You can fill it manually based on the image.");
                }
            }).catch(err => {
                document.getElementById('loader').style.display = 'none';
                document.getElementById('ocrForm').style.display = 'block';
                console.error(err);
                alert("OCR Error. Please enter details manually.");
            });
        }
        
        <?php if(!isset($details['ocr_nid'])): ?>
        // Show form empty initially if no saved data
        document.getElementById('ocrForm').style.display = 'block';
        <?php endif; ?>
    </script>
<?php include __DIR__ . '/../includes/cropper_modal.php'; ?>
</body>
</html>

