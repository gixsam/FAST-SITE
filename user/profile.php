<?php
session_start();
if (!isset($_SESSION['user_id'])) { 
    header('Location: /user/login.php'); 
    exit; 
}
require_once __DIR__ . '/../config.php';

$user_id = (int)$_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute([':id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header('Location: /user/login.php'); 
    exit;
}

$msg = '';
$err = '';

// Calculate profile completion percentage
$total_fields = 13;
$filled_fields = 0;
$fields_to_check = ['name', 'phone', 'email', 'dob', 'gender', 'profile_pic', 'nid', 'etin', 'passport', 'driving_license', 'extra_details', 'whatsapp', 'address'];
foreach ($fields_to_check as $field) {
    if (!empty($user[$field])) {
        $filled_fields++;
    }
}
$completion_percentage = round(($filled_fields / $total_fields) * 100);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $dob = trim($_POST['dob'] ?? '');
        $gender = trim($_POST['gender'] ?? '');
        $whatsapp = trim($_POST['whatsapp'] ?? '');
        $facebook = trim($_POST['facebook'] ?? '');
        $instagram = trim($_POST['instagram'] ?? '');
        $twitter = trim($_POST['twitter'] ?? '');
        $youtube = trim($_POST['youtube'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $district = trim($_POST['district'] ?? '');
        $extra_details = trim($_POST['extra_details'] ?? '');
        
        $new_password = trim($_POST['password'] ?? '');

        // Handle file uploads
        $upload_dir = '../uploads/kyc/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        // Upload Profile Picture
        $profile_pic = $user['profile_pic'] ?? '';
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','webp'])) {
                $pic_name = 'pp_' . time() . '_' . rand(1000,9999) . '.' . $ext;
                if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $upload_dir . $pic_name)) {
                    $profile_pic = 'uploads/kyc/' . $pic_name;
                }
            }
        }

        $nid = $user['nid'] ?? '';
        if (isset($_FILES['nid']) && $_FILES['nid']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['nid']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','webp','pdf'])) {
                $f_name = 'nid_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['nid']['tmp_name'], $upload_dir . $f_name)) $nid = 'uploads/kyc/' . $f_name;
            }
        }

        $etin = $user['etin'] ?? '';
        if (isset($_FILES['etin']) && $_FILES['etin']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['etin']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','webp','pdf'])) {
                $f_name = 'etin_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['etin']['tmp_name'], $upload_dir . $f_name)) $etin = 'uploads/kyc/' . $f_name;
            }
        }

        $passport = $user['passport'] ?? '';
        if (isset($_FILES['passport']) && $_FILES['passport']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['passport']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','webp','pdf'])) {
                $f_name = 'pass_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['passport']['tmp_name'], $upload_dir . $f_name)) $passport = 'uploads/kyc/' . $f_name;
            }
        }

        $driving_license = $user['driving_license'] ?? '';
        if (isset($_FILES['driving_license']) && $_FILES['driving_license']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['driving_license']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','webp','pdf'])) {
                $f_name = 'dl_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['driving_license']['tmp_name'], $upload_dir . $f_name)) $driving_license = 'uploads/kyc/' . $f_name;
            }
        }

        if ($name) {
            $nid_number = trim($_POST['nid_number'] ?? ($user['nid_number'] ?? ''));
            $new_kyc_status = $user['kyc_status'] ?? 'pending';
            if ($new_kyc_status !== 'approved' && (!empty($nid) || !empty($passport) || !empty($etin) || !empty($driving_license))) {
                $new_kyc_status = 'submitted';
            }

            $sql = "UPDATE users SET name=:n, email=:e, dob=:d, gender=:g, whatsapp=:wa, facebook=:fb, instagram=:ig, twitter=:tw, youtube=:yt, address=:addr, district=:dist, extra_details=:ed, nid=:nid, etin=:etin, passport=:pass, driving_license=:dl, profile_pic=:pic, nid_number=:nid_num, kyc_status=:kst";
            $params = [
                ':n' => $name, ':e' => $email, ':d' => $dob, ':g' => $gender, ':wa' => $whatsapp,
                ':fb' => $facebook, ':ig' => $instagram, ':tw' => $twitter, ':yt' => $youtube,
                ':addr' => $address, ':dist' => $district, ':ed' => $extra_details,
                ':nid' => $nid, ':etin' => $etin, ':pass' => $passport, ':dl' => $driving_license, ':pic' => $profile_pic,
                ':nid_num' => $nid_number, ':kst' => $new_kyc_status,
                ':id' => $user_id
            ];

            if (!empty($new_password)) {
                $sql .= ", password_hash=:ph";
                $params[':ph'] = password_hash($new_password, PASSWORD_DEFAULT);
            }

            $sql .= " WHERE id=:id";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $msg = "Profile updated successfully!" . ($new_kyc_status === 'submitted' ? " Your ID verification documents have been submitted for admin review." : "");
            
            // Refresh user data
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
            $stmt->execute([':id' => $user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Recalculate
            $filled_fields = 0;
            foreach ($fields_to_check as $field) {
                if (!empty($user[$field])) {
                    $filled_fields++;
                }
            }
            $completion_percentage = round(($filled_fields / $total_fields) * 100);
        } else {
            $err = "Name is required.";
        }
    }
}

$user_avatar = !empty($user['profile_pic']) ? '/' . ltrim($user['profile_pic'], '/') : '/assets/images/default_avatar.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>My Profile — Fast Site</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Oswald:wght@600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="/assets/css/user.css?v=<?= time() ?>">
  <style>
    .profile-card-header {
      background: linear-gradient(135deg, rgba(33, 150, 243, 0.12) 0%, rgba(20, 20, 31, 0.8) 100%);
      border: 1px solid rgba(33, 150, 243, 0.3);
      border-radius: 20px;
      padding: 1.8rem;
      margin-bottom: 2rem;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
      display: flex;
      align-items: center;
      gap: 1.5rem;
      flex-wrap: wrap;
    }
    .profile-avatar-wrap {
      position: relative;
      width: 100px;
      height: 100px;
      border-radius: 50%;
      border: 3px solid var(--gold);
      box-shadow: 0 0 20px rgba(252, 185, 0, 0.3);
      overflow: hidden;
      background: #0d0d14;
      flex-shrink: 0;
    }
    .profile-avatar-wrap img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    .avatar-upload-label {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: rgba(255, 255, 255, 0.08);
      border: 1px solid rgba(255, 255, 255, 0.15);
      color: #fff;
      padding: 0.4rem 0.9rem;
      border-radius: 20px;
      font-size: 0.75rem;
      font-weight: 700;
      cursor: pointer;
      margin-top: 0.5rem;
      transition: background 0.2s;
    }
    .avatar-upload-label:hover {
      background: rgba(33, 150, 243, 0.2);
      border-color: var(--brand);
    }
    .section-card {
      background: rgba(20, 20, 31, 0.85);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 16px;
      padding: 1.5rem;
      margin-bottom: 1.5rem;
      box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3);
    }
    .section-card h3 {
      font-size: 1.15rem;
      color: #fff;
      margin-bottom: 1.2rem;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .form-grid-2 {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 1.2rem;
    }
    @media (max-width: 768px) {
      .form-grid-2 {
        grid-template-columns: 1fr;
        gap: 0.8rem;
      }
      .profile-card-header {
        flex-direction: column;
        text-align: center;
      }
    }
    .custom-input {
      width: 100%;
      background: #0b0b12 !important;
      border: 1px solid rgba(255, 255, 255, 0.12) !important;
      color: #fff !important;
      padding: 0.75rem 1rem !important;
      border-radius: 10px !important;
      font-size: 0.9rem !important;
      outline: none !important;
      transition: border-color 0.2s, box-shadow 0.2s;
    }
    .custom-input:focus {
      border-color: var(--brand) !important;
      box-shadow: 0 0 12px rgba(33, 150, 243, 0.3) !important;
    }
    .custom-select {
      width: 100%;
      background: #0b0b12 !important;
      border: 1px solid rgba(255, 255, 255, 0.12) !important;
      color: #fff !important;
      padding: 0.75rem 1rem !important;
      border-radius: 10px !important;
      font-size: 0.9rem !important;
      outline: none !important;
      cursor: pointer;
    }
    .custom-select option {
      background: #10101a !important;
      color: #fff !important;
    }
    .doc-upload-box {
      background: rgba(255, 255, 255, 0.02);
      border: 1px dashed rgba(255, 255, 255, 0.15);
      border-radius: 12px;
      padding: 1rem;
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
    }
    .doc-upload-box input[type="file"] {
      font-size: 0.8rem;
      color: var(--muted);
    }
  </style>
</head>
<body class="dashboard-mode">

<?php include __DIR__ . '/../includes/user_sidebar.php'; ?>

<div class="dashboard-container" style="padding-top: 85px; max-width: 960px; margin: 0 auto; padding-bottom: 5rem;">
  
  <!-- Navigation bar -->
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 1.5rem;">
      <a href="dashboard.php" class="btn" style="background:rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.15); color:#fff; width:auto; padding:0.5rem 1.2rem; font-size:0.85rem; text-decoration:none;">
        ← Back to Dashboard
      </a>
      <button onclick="window.print()" class="btn" style="background:rgba(33, 150, 243, 0.15); border:1px solid var(--brand); color:var(--brand); width:auto; padding:0.5rem 1.2rem; font-size:0.85rem;">
        📄 Download CV (PDF)
      </button>
  </div>

  <?php if($msg): ?>
    <div style="background:rgba(0, 230, 118, 0.12); color:#00e676; padding:1rem 1.5rem; border-radius:12px; margin-bottom:1.5rem; border:1px solid rgba(0, 230, 118, 0.3); font-weight:700; display:flex; align-items:center; gap:8px;">
      ✅ <?= htmlspecialchars($msg) ?>
    </div>
  <?php endif; ?>
  
  <?php if($err): ?>
    <div style="background:rgba(255, 82, 82, 0.12); color:#ff5252; padding:1rem 1.5rem; border-radius:12px; margin-bottom:1.5rem; border:1px solid rgba(255, 82, 82, 0.3); font-weight:700; display:flex; align-items:center; gap:8px;">
      ⚠️ <?= htmlspecialchars($err) ?>
    </div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data" id="profileForm">
    
    <!-- ── Profile Header Card with Avatar & Progress ── -->
    <div class="profile-card-header">
      <div class="profile-avatar-wrap">
        <img id="avatarPreview" src="<?= htmlspecialchars($user_avatar) ?>" alt="Avatar" onerror="this.onerror=null; this.src='/assets/images/default_avatar.png';">
      </div>
      <div style="flex:1; min-width:240px;">
        <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px; flex-wrap:wrap;">
          <h2 style="color:#fff; font-size:1.4rem; font-weight:800; margin:0;"><?= htmlspecialchars($user['name'] ?? 'User Profile') ?></h2>
          <?php if (!empty($user['registration_number'])): ?>
            <span style="background:rgba(252,185,0,0.15); color:var(--gold); border:1px solid rgba(252,185,0,0.3); padding:2px 8px; border-radius:4px; font-size:0.7rem; font-weight:700;">
              REG: <?= htmlspecialchars($user['registration_number']) ?>
            </span>
          <?php endif; ?>
        </div>
        <p style="color:var(--muted); font-size:0.85rem; margin-bottom:0.8rem;">
          📱 Registered Phone: <strong style="color:#fff;"><?= htmlspecialchars($user['phone']) ?></strong>
        </p>

        <!-- Upload Button for Avatar -->
        <div>
          <label class="avatar-upload-label" for="profilePicInput">
            📷 Change Profile Photo
          </label>
          <input type="file" id="profilePicInput" name="profile_pic" accept="image/*" style="display:none;" onchange="previewAvatar(event)">
        </div>

        <!-- Completion bar -->
        <div style="margin-top: 1rem; max-width: 380px;">
          <div style="display:flex; justify-content:space-between; font-size:0.78rem; font-weight:700; margin-bottom:4px;">
            <span style="color:var(--muted);">Profile Completion</span>
            <span style="color:var(--gold);"><?= $completion_percentage ?>%</span>
          </div>
          <div style="background:rgba(255,255,255,0.1); height:8px; border-radius:4px; overflow:hidden;">
            <div style="width:<?= $completion_percentage ?>%; height:100%; background:linear-gradient(90deg, #2196F3, #fcb900); box-shadow:0 0 10px rgba(252,185,0,0.4);"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Card 1: Personal Information ── -->
    <div class="section-card">
      <h3>👤 Personal Details</h3>
      <div class="form-grid-2">
        <div class="field">
          <label>Full Name *</label>
          <input type="text" name="name" class="custom-input" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required placeholder="e.g. John Doe">
        </div>
        <div class="field">
          <label>Email Address</label>
          <input type="email" name="email" class="custom-input" value="<?= htmlspecialchars($user['email'] ?? '') ?>" placeholder="user@example.com">
        </div>
        <div class="field">
          <label>Date of Birth</label>
          <input type="date" name="dob" class="custom-input" value="<?= htmlspecialchars($user['dob'] ?? '') ?>">
        </div>
        <div class="field">
          <label>Gender</label>
          <select name="gender" class="custom-select">
            <option value="" disabled <?= empty($user['gender']) ? 'selected' : '' ?>>Select Gender</option>
            <option value="Male" <?= ($user['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
            <option value="Female" <?= ($user['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
            <option value="Other" <?= ($user['gender'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
          </select>
        </div>
        <div class="field">
          <label>District / Region</label>
          <select name="district" class="custom-select">
            <option value="" disabled <?= empty($user['district']) ? 'selected' : '' ?>>Select District</option>
            <?php 
              $districts = ['Dhaka', 'Chittagong', 'Sylhet', 'Rajshahi', 'Khulna', 'Barisal', 'Rangpur', 'Mymensingh', 'Gazipur', 'Narayanganj', 'Comilla'];
              foreach ($districts as $dist):
            ?>
              <option value="<?= $dist ?>" <?= ($user['district'] ?? '') === $dist ? 'selected' : '' ?>><?= $dist ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label>Extra Notes / Bio</label>
          <input type="text" name="extra_details" class="custom-input" value="<?= htmlspecialchars($user['extra_details'] ?? '') ?>" placeholder="Short bio or skills...">
        </div>
      </div>
      
      <div class="field" style="margin-top: 1rem;">
        <label>Full Residential Address</label>
        <textarea name="address" rows="2" class="custom-input" placeholder="House, Road, Area, City"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
      </div>
    </div>

    <!-- ── Card 2: Social & Contact Links ── -->
    <div class="section-card">
      <h3>📱 Social & Direct Contact</h3>
      <div class="form-grid-2">
        <div class="field">
          <label>WhatsApp Number</label>
          <input type="text" name="whatsapp" class="custom-input" value="<?= htmlspecialchars($user['whatsapp'] ?? '') ?>" placeholder="+8801XXXXXXXXX">
        </div>
        <div class="field">
          <label>Facebook Profile URL</label>
          <input type="url" name="facebook" class="custom-input" value="<?= htmlspecialchars($user['facebook'] ?? '') ?>" placeholder="https://facebook.com/yourname">
        </div>
        <div class="field">
          <label>Instagram Handle / Link</label>
          <input type="text" name="instagram" class="custom-input" value="<?= htmlspecialchars($user['instagram'] ?? '') ?>" placeholder="@username or URL">
        </div>
        <div class="field">
          <label>Twitter/X Handle / Link</label>
          <input type="text" name="twitter" class="custom-input" value="<?= htmlspecialchars($user['twitter'] ?? '') ?>" placeholder="@username or URL">
        </div>
        <div class="field" style="grid-column: 1 / -1;">
          <label>YouTube Channel URL</label>
          <input type="url" name="youtube" class="custom-input" value="<?= htmlspecialchars($user['youtube'] ?? '') ?>" placeholder="https://youtube.com/@channel">
        </div>
      </div>
    </div>

    <!-- ── Card 3: Security & Password ── -->
    <div class="section-card">
      <h3>🔐 Account Security</h3>
      <div class="field">
        <label>Update Password (Leave blank to keep unchanged)</label>
        <input type="password" name="password" class="custom-input" placeholder="Enter new strong password">
      </div>
    </div>

    <!-- ── Card 4: ID & Profile Verification ── -->
    <div class="section-card">
      <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px; margin-bottom:0.8rem;">
        <h3 style="margin:0;">🪪 ID &amp; Profile Verification (আইডি ও প্রোফাইল ভেরিফিকেশন)</h3>
        <?php 
          $kst = strtolower($user['kyc_status'] ?? 'pending');
          if ($kst === 'approved'):
        ?>
          <span style="background:rgba(16,185,129,0.15); color:#10b981; border:1px solid #10b981; padding:4px 12px; border-radius:20px; font-weight:800; font-size:0.78rem;">
            🛡️ Verified Account
          </span>
        <?php elseif ($kst === 'submitted'): ?>
          <span style="background:rgba(245,158,11,0.15); color:#f59e0b; border:1px solid #f59e0b; padding:4px 12px; border-radius:20px; font-weight:800; font-size:0.78rem;">
            ⏳ Verification Under Review
          </span>
        <?php elseif ($kst === 'rejected'): ?>
          <span style="background:rgba(239,68,68,0.15); color:#ef4444; border:1px solid #ef4444; padding:4px 12px; border-radius:20px; font-weight:800; font-size:0.78rem;">
            ✕ Verification Rejected (Re-upload)
          </span>
        <?php else: ?>
          <span style="background:rgba(255,255,255,0.06); color:#cbd5e1; border:1px solid rgba(255,255,255,0.15); padding:4px 12px; border-radius:20px; font-weight:700; font-size:0.78rem;">
            ⚠️ Not Verified
          </span>
        <?php endif; ?>
      </div>

      <p style="color:var(--muted); font-size:0.85rem; margin-bottom:1.5rem;">
        আপনার জাতীয় পরিচয়পত্র (NID) বা পাসপোর্ট দিয়ে প্রোফাইল ভেরিফাই করুন। ভেরিফাইড প্রোফাইল মার্কেটপ্লেসে সর্বোচ্চ নিরাপত্তা ও দ্রুত ক্যাশআউট সুবিধা পায়।
      </p>

      <div class="field" style="margin-bottom:1.2rem;">
        <label>National ID / Passport Number</label>
        <input type="text" name="nid_number" class="custom-input" value="<?= htmlspecialchars($user['nid_number'] ?? '') ?>" placeholder="e.g. 1995829104820">
      </div>

      <div class="form-grid-2">
        <div class="doc-upload-box">
          <div style="display:flex; justify-content:space-between; align-items:center;">
            <label style="font-weight:700; color:#fff; font-size:0.85rem;">National ID (NID)</label>
            <?php if(!empty($user['nid'])): ?>
              <span style="color:#00e676; font-size:0.75rem; font-weight:700; background:rgba(0,230,118,0.1); padding:2px 8px; border-radius:4px;">✓ Uploaded</span>
            <?php endif; ?>
          </div>
          <input type="file" name="nid" accept="image/*,.pdf">
        </div>

        <div class="doc-upload-box">
          <div style="display:flex; justify-content:space-between; align-items:center;">
            <label style="font-weight:700; color:#fff; font-size:0.85rem;">e-TIN Certificate</label>
            <?php if(!empty($user['etin'])): ?>
              <span style="color:#00e676; font-size:0.75rem; font-weight:700; background:rgba(0,230,118,0.1); padding:2px 8px; border-radius:4px;">✓ Uploaded</span>
            <?php endif; ?>
          </div>
          <input type="file" name="etin" accept="image/*,.pdf">
        </div>

        <div class="doc-upload-box">
          <div style="display:flex; justify-content:space-between; align-items:center;">
            <label style="font-weight:700; color:#fff; font-size:0.85rem;">Passport Copy</label>
            <?php if(!empty($user['passport'])): ?>
              <span style="color:#00e676; font-size:0.75rem; font-weight:700; background:rgba(0,230,118,0.1); padding:2px 8px; border-radius:4px;">✓ Uploaded</span>
            <?php endif; ?>
          </div>
          <input type="file" name="passport" accept="image/*,.pdf">
        </div>

        <div class="doc-upload-box">
          <div style="display:flex; justify-content:space-between; align-items:center;">
            <label style="font-weight:700; color:#fff; font-size:0.85rem;">Driving License</label>
            <?php if(!empty($user['driving_license'])): ?>
              <span style="color:#00e676; font-size:0.75rem; font-weight:700; background:rgba(0,230,118,0.1); padding:2px 8px; border-radius:4px;">✓ Uploaded</span>
            <?php endif; ?>
          </div>
          <input type="file" name="driving_license" accept="image/*,.pdf">
        </div>
      </div>
    </div>

    <!-- Submit CTA Button -->
    <div style="text-align:center; margin-top:2rem;">
      <button type="submit" name="update_profile" class="btn" style="background:linear-gradient(135deg, var(--brand), #007bb5); color:#fff; font-weight:800; font-size:1.1rem; padding:1rem 3rem; border-radius:50px; box-shadow:0 8px 30px rgba(33,150,243,0.4); width:auto; min-width:240px; cursor:pointer;">
        💾 Save Profile Changes
      </button>
    </div>

  </form>
</div>

<script>
function previewAvatar(event) {
  const file = event.target.files[0];
  if (file) {
    const reader = new FileReader();
    reader.onload = function(e) {
      document.getElementById('avatarPreview').src = e.target.result;
    }
    reader.readAsDataURL(file);
  }
}
</script>

</body>
</html>
