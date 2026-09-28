<?php
// =========================================================================
// user/visa_apply.php — Best Travel Dynamic Visa Requirement & Upload Engine
// Features country checklists, PDF/JPG file dropzone, tracking code generator
// =========================================================================
session_start();
require_once __DIR__ . '/../config.php';

$user_id = $_SESSION['user_id'] ?? 0;
$msg = ''; $err = ''; $tracking_code = ''; $application_submitted = false;

// Supported Visa Countries
$countries = [
    'TH' => ['name' => 'Thailand', 'fee' => '5,500 BDT', 'time' => '5-7 Working Days', 'flag' => '🇹🇭'],
    'MY' => ['name' => 'Malaysia', 'fee' => '4,200 BDT', 'time' => '3-5 Working Days', 'flag' => '🇲🇾'],
    'AE' => ['name' => 'United Arab Emirates (UAE)', 'fee' => '14,500 BDT', 'time' => '48-72 Hours', 'flag' => '🇦🇪'],
    'SA' => ['name' => 'Saudi Arabia (Tourist Umrah)', 'fee' => '18,000 BDT', 'time' => '24-48 Hours', 'flag' => '🇸🇦'],
    'SG' => ['name' => 'Singapore', 'fee' => '6,800 BDT', 'time' => '5 Working Days', 'flag' => '🇸🇬'],
    'GB' => ['name' => 'United Kingdom (UK)', 'fee' => '22,500 BDT', 'time' => '15 Working Days', 'flag' => '🇬🇧']
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_visa'])) {
    $applicant_name = trim($_POST['applicant_name'] ?? '');
    $applicant_phone = trim($_POST['applicant_phone'] ?? '');
    $country_code = trim($_POST['country_code'] ?? 'TH');

    if (empty($applicant_name) || empty($applicant_phone)) {
        $err = 'Please enter applicant name and phone number.';
    } else {
        try {
            // Generate unique tracking code (e.g. BT-VISA-A1B2C3D4)
            $tracking_code = 'BT-VISA-' . strtoupper(substr(md5(uniqid()), 0, 8));

            // Create visa_applications table if missing
            $pdo->exec("CREATE TABLE IF NOT EXISTS visa_applications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                tracking_code VARCHAR(100) NOT NULL UNIQUE,
                applicant_name VARCHAR(150) NOT NULL,
                applicant_phone VARCHAR(50) NOT NULL,
                country_code VARCHAR(10) NOT NULL,
                status ENUM('pending', 'documents_verified', 'embassy_processing', 'approved', 'rejected') DEFAULT 'pending',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            $ins = $pdo->prepare("INSERT INTO visa_applications (tracking_code, applicant_name, applicant_phone, country_code, status) VALUES (?, ?, ?, ?, 'pending')");
            $ins->execute([$tracking_code, $applicant_name, $applicant_phone, $country_code]);

            $application_submitted = true;
            $msg = "Visa Application submitted successfully! Your tracking code is: {$tracking_code}";
        } catch (Exception $e) {
            $err = 'Visa application error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Best Travel & Visa Portal — Fast Site Ecosystem</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Oswald:wght@600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="/assets/css/admin.css">
  <style>
    body { background: #080911; color: #fff; font-family: 'Inter', sans-serif; }
    .visa-wrap { max-width: 1000px; margin: 3rem auto; padding: 2rem; background: rgba(16, 18, 28, 0.95); border: 1px solid rgba(252,185,0,0.3); border-radius: 20px; box-shadow: 0 12px 40px rgba(0,0,0,0.6); }
    .country-card { background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); border-radius: 14px; padding: 1.2rem; cursor: pointer; transition: all 0.25s; }
    .country-card:hover, .country-card.selected { border-color: var(--gold,#fcb900); background: rgba(252,185,0,0.1); }
    .dropzone { border: 2px dashed rgba(252,185,0,0.4); border-radius: 14px; padding: 2rem; text-align: center; background: rgba(0,0,0,0.3); margin-top: 1rem; }
  </style>
</head>
<body>

<div class="visa-wrap">
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
    <div>
      <h1 style="color:var(--gold,#fcb900); font-family:'Oswald',sans-serif; text-transform:uppercase; margin:0;">✈️ BEST TRAVEL VISA PORTAL</h1>
      <p style="color:#aaa; font-size:0.9rem; margin-top:4px;">Dynamic Document Checklists & Tracking for Bangladeshi Passport Holders</p>
    </div>
    <a href="../index.php" style="text-decoration:none;"><button class="btn" style="background:rgba(255,255,255,0.1); color:#fff; border:1px solid rgba(255,255,255,0.2);">← Back to Hub</button></a>
  </div>

  <?php if($msg): ?><div style="background:rgba(16,185,129,0.15); border:1px solid #10b981; color:#10b981; padding:1rem; border-radius:12px; margin-bottom:1.5rem; font-weight:700;">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if($err): ?><div style="background:rgba(239,68,68,0.15); border:1px solid #ef4444; color:#ef4444; padding:1rem; border-radius:12px; margin-bottom:1.5rem; font-weight:700;">❌ <?= htmlspecialchars($err) ?></div><?php endif; ?>

  <?php if($application_submitted): ?>
    <div style="text-align:center; padding:3rem; background:rgba(16,185,129,0.05); border:1px solid #10b981; border-radius:18px;">
      <h2 style="color:#10b981; font-family:'Oswald',sans-serif;">🎉 APPLICATION SUBMITTED!</h2>
      <p style="color:#fff; font-size:1.2rem; font-weight:800;">Tracking Code: <span style="color:var(--gold,#fcb900);"><?= htmlspecialchars($tracking_code) ?></span></p>
      <p style="color:#aaa; max-width:500px; margin:0.8rem auto;">Our travel specialists will verify your uploaded documents within 24 hours.</p>
      <a href="visa_apply.php" style="text-decoration:none;"><button class="btn" style="margin-top:1.5rem;">Apply for Another Visa</button></a>
    </div>
  <?php else: ?>
    <form method="POST" enctype="multipart/form-data">
      <h3 style="color:var(--gold,#fcb900); font-family:'Oswald',sans-serif;">1. SELECT DESTINATION COUNTRY</h3>
      <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:1rem; margin-bottom:2rem;">
        <?php foreach($countries as $code => $c): ?>
          <label class="country-card">
            <input type="radio" name="country_code" value="<?= $code ?>" <?= $code === 'TH' ? 'checked' : '' ?> style="margin-right:8px;"/>
            <span style="font-size:1.2rem;"><?= $c['flag'] ?></span>
            <strong style="color:#fff;"><?= $c['name'] ?></strong>
            <div style="font-size:0.78rem; color:var(--gold,#fcb900); margin-top:4px; font-weight:700;"><?= $c['fee'] ?> • <?= $c['time'] ?></div>
          </label>
        <?php endforeach; ?>
      </div>

      <h3 style="color:var(--gold,#fcb900); font-family:'Oswald',sans-serif;">2. APPLICANT DETAILS</h3>
      <div class="grid2">
        <div class="field"><label>Applicant Full Name (As in Passport)</label><input type="text" name="applicant_name" required placeholder="e.g. Sayam Hossain"/></div>
        <div class="field"><label>Applicant Contact Number</label><input type="tel" name="applicant_phone" required placeholder="e.g. 01963601472"/></div>
      </div>

      <h3 style="color:var(--gold,#fcb900); font-family:'Oswald',sans-serif; margin-top:1.5rem;">3. REQUIRED DOCUMENTS DROPZONE</h3>
      <div style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08); border-radius:12px; padding:1rem; margin-bottom:1rem; font-size:0.85rem; color:#ccc;">
        📌 <strong>Checklist:</strong> 1. Original Passport (Min 6 months validity) • 2. Photo 35x45mm (White Background) • 3. 6-Month Bank Statement & Solvency • 4. Trade License / NOC
      </div>

      <div class="dropzone">
        <label style="color:var(--gold,#fcb900); font-weight:800; display:block; margin-bottom:8px;">📁 Upload Documents (PDF / JPG / PNG)</label>
        <input type="file" name="visa_docs[]" multiple accept=".pdf,.jpg,.jpeg,.png"/>
        <small style="display:block; color:#888; margin-top:6px;">Select all required files together (Max 10MB total)</small>
      </div>

      <button type="submit" name="submit_visa" class="btn" style="width:100%; margin-top:2rem; justify-content:center;">✈️ Submit Visa Application & Get Tracking Code</button>
    </form>
  <?php endif; ?>
</div>

</body>
</html>
