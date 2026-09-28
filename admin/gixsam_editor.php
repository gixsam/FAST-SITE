<?php
// =========================================================================
// admin/gixsam_editor.php  –  Personal Landing Page Control System (GixSam)
// Manage all text, photos, bios, social links & ventures for gixsam.best-travel.ltd
// =========================================================================
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/../config.php';

// Ensure gixsam_settings table exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS gixsam_settings (
        setting_key VARCHAR(100) PRIMARY KEY,
        setting_value TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
} catch (Exception $e) {}

// Helper to get gixsam setting
function getGixsamSetting($key, $default = '') {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM gixsam_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $val = $stmt->fetchColumn();
        return $val !== false ? $val : $default;
    } catch (Exception $e) {
        return $default;
    }
}

// Helper to set gixsam setting
function setGixsamSetting($key, $value) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO gixsam_settings (setting_key, setting_value) VALUES (?, ?) 
                               ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->execute([$key, $value]);
    } catch (Exception $e) {}
}

$msg = '';
$err = '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save_gixsam') {
        $fields = [
            // Hero
            'hero_name', 'hero_headline', 'hero_subtitle', 'hero_photo_url', 'hero_location',
            // About
            'about_heading', 'about_bio_1', 'about_bio_2', 'about_quote', 'exp_years', 'ventures_count',
            // Contact & Socials
            'phone', 'email', 'whatsapp', 'facebook_url', 'instagram_url', 'twitter_url', 'youtube_url',
            // Custom CSS / Extra
            'custom_css'
        ];
        
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                setGixsamSetting($field, trim($_POST[$field]));
            }
        }

        // Handle Image Upload if file provided
        if (isset($_FILES['hero_photo_file']) && $_FILES['hero_photo_file']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['hero_photo_file']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'])) {
                $filename = 'gixsam_portrait_' . time() . '.' . $ext;
                $target = '../uploads/' . $filename;
                if (move_uploaded_file($_FILES['hero_photo_file']['tmp_name'], $target)) {
                    setGixsamSetting('hero_photo_url', 'https://fastsite.best-travel.ltd/uploads/' . $filename);
                }
            }
        }

        $msg = '🎉 GixSam Landing Page settings saved successfully! Changes are live on gixsam.best-travel.ltd.';
    }
}

// Fetch current values
$hero_name      = getGixsamSetting('hero_name', 'Sadman Hossain Sayam');
$hero_headline  = getGixsamSetting('hero_headline', 'Managing Director – Best Force Ltd & Best Travel');
$hero_subtitle  = getGixsamSetting('hero_subtitle', 'Dynamic Entrepreneur, Global Explorer & Visionary Leader');
$hero_photo_url = getGixsamSetting('hero_photo_url', 'https://gixsam.best-travel.ltd/assets/images/portrait.jpg');
$hero_location  = getGixsamSetting('hero_location', 'Dhaka, Bangladesh');

$about_heading  = getGixsamSetting('about_heading', 'Pioneering Excellence Across Security & Global Travel');
$about_bio_1    = getGixsamSetting('about_bio_1', 'Sadman Hossain Sayam is an ambitious Bangladeshi entrepreneur leading Best Force Ltd (Security & Logistics) and Best Travel (Global Tourism). With a vision to revolutionize digital marketplaces and security, he oversees an interconnected ecosystem of 7 enterprises.');
$about_bio_2    = getGixsamSetting('about_bio_2', 'Driven by innovation and trust, his mission is to build seamless B2B & B2C platforms that empower local Bangladeshis with world-class services.');
$about_quote    = getGixsamSetting('about_quote', 'Leadership is not about being in charge. It is about taking care of those in your charge.');
$exp_years      = getGixsamSetting('exp_years', '10+');
$ventures_count = getGixsamSetting('ventures_count', '7+');

$phone          = getGixsamSetting('phone', '+880 1627-127534');
$email          = getGixsamSetting('email', 'khangroup01@gmail.com');
$whatsapp       = getGixsamSetting('whatsapp', '8801627127534');
$facebook_url   = getGixsamSetting('facebook_url', 'https://www.facebook.com/share/18QWLZABMs/');
$instagram_url  = getGixsamSetting('instagram_url', 'https://www.instagram.com/sadman_sayam/');
$twitter_url    = getGixsamSetting('twitter_url', 'https://x.com/sadmansaya12282');
$youtube_url    = getGixsamSetting('youtube_url', 'https://youtube.com/@princesayam6');

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>GixSam Page Editor — Fast Site Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Oswald:wght@600;700&display=swap" rel="stylesheet"/>
  <style>
    :root {
      --gold: #fcb900;
      --gold-glow: rgba(252, 185, 0, 0.2);
      --dark: #08080c;
      --card-bg: rgba(18, 18, 26, 0.75);
      --border: rgba(255, 255, 255, 0.08);
      --text: #f8f8f8;
      --muted: #9ca3af;
      --green: #10b981;
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Inter', sans-serif; background: var(--dark); color: var(--text); padding-bottom: 5rem; }

    .editor-container {
      max-width: 1000px;
      margin: 2rem auto;
      padding: 0 1.5rem;
    }

    .header-card {
      background: linear-gradient(135deg, rgba(252,185,0,0.12), rgba(16,185,129,0.08));
      border: 1px solid rgba(252,185,0,0.25);
      border-radius: 18px;
      padding: 1.8rem;
      margin-bottom: 2rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 1rem;
    }

    .header-card h1 {
      font-family: 'Oswald', sans-serif;
      font-size: 1.6rem;
      color: var(--gold);
      letter-spacing: 0.05em;
    }

    .header-card p {
      font-size: 0.85rem;
      color: var(--muted);
      margin-top: 0.3rem;
    }

    .btn-preview {
      background: var(--gold);
      color: #000;
      font-weight: 800;
      font-size: 0.8rem;
      padding: 0.6rem 1.4rem;
      border-radius: 50px;
      text-decoration: none;
      transition: all 0.2s;
    }
    .btn-preview:hover { transform: scale(1.05); }

    .alert-msg {
      background: rgba(16, 185, 129, 0.15);
      border: 1px solid rgba(16, 185, 129, 0.4);
      color: #10b981;
      padding: 1rem;
      border-radius: 12px;
      margin-bottom: 1.5rem;
      font-weight: 600;
      font-size: 0.9rem;
    }

    .form-card {
      background: var(--card-bg);
      border: 1px solid var(--border);
      border-radius: 18px;
      padding: 2rem;
      margin-bottom: 2rem;
    }

    .section-title {
      font-size: 1.1rem;
      font-weight: 800;
      color: var(--gold);
      margin-bottom: 1.5rem;
      padding-bottom: 0.6rem;
      border-bottom: 1px solid rgba(255,255,255,0.06);
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .form-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 1.2rem;
    }

    .form-group {
      display: flex;
      flex-direction: column;
      gap: 0.4rem;
    }

    .form-group.full-width {
      grid-column: 1 / -1;
    }

    label {
      font-size: 0.78rem;
      font-weight: 700;
      color: var(--muted);
      text-transform: uppercase;
      letter-spacing: 0.04em;
    }

    input[type="text"], input[type="url"], input[type="email"], input[type="tel"], textarea {
      background: rgba(255, 255, 255, 0.04);
      border: 1px solid rgba(255, 255, 255, 0.12);
      border-radius: 10px;
      padding: 0.75rem 1rem;
      color: #fff;
      font-size: 0.9rem;
      outline: none;
      transition: border-color 0.2s;
    }

    input:focus, textarea:focus {
      border-color: var(--gold);
      background: rgba(252, 185, 0, 0.04);
    }

    textarea {
      resize: vertical;
      min-height: 100px;
    }

    .preview-img {
      width: 80px;
      height: 80px;
      border-radius: 12px;
      object-fit: cover;
      border: 2px solid var(--gold);
      margin-top: 0.5rem;
    }

    .btn-save {
      background: linear-gradient(135deg, var(--gold), #ff8a00);
      color: #000;
      border: none;
      font-weight: 900;
      font-size: 1rem;
      padding: 0.9rem 2.5rem;
      border-radius: 50px;
      cursor: pointer;
      box-shadow: 0 6px 20px rgba(252, 185, 0, 0.3);
      transition: all 0.2s;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
    }
    .btn-save:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(252, 185, 0, 0.5);
    }
  </style>
</head>
<body>

<?php include 'nav.php'; ?>

<div class="editor-container">

  <div class="header-card">
    <div>
      <h1>🌐 GIXSAM LANDING PAGE EDITOR</h1>
      <p>Edit text, titles, bio, photo &amp; social links for <strong>https://gixsam.best-travel.ltd</strong></p>
    </div>
    <a href="https://gixsam.best-travel.ltd" target="_blank" class="btn-preview">🔗 View Live GixSam Site →</a>
  </div>

  <?php if (!empty($msg)): ?>
    <div class="alert-msg"><?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="action" value="save_gixsam"/>

    <!-- SECTION 1: HERO & BRANDING -->
    <div class="form-card">
      <div class="section-title">👤 HERO &amp; PORTRAIT SECTION</div>
      <div class="form-grid">
        <div class="form-group">
          <label>Full Name</label>
          <input type="text" name="hero_name" value="<?= htmlspecialchars($hero_name) ?>" required/>
        </div>
        <div class="form-group">
          <label>Job Title / Headline</label>
          <input type="text" name="hero_headline" value="<?= htmlspecialchars($hero_headline) ?>" required/>
        </div>
        <div class="form-group full-width">
          <label>Tagline / Subtitle</label>
          <input type="text" name="hero_subtitle" value="<?= htmlspecialchars($hero_subtitle) ?>"/>
        </div>
        <div class="form-group">
          <label>Location</label>
          <input type="text" name="hero_location" value="<?= htmlspecialchars($hero_location) ?>"/>
        </div>
        <div class="form-group">
          <label>Portrait Photo URL</label>
          <input type="text" name="hero_photo_url" value="<?= htmlspecialchars($hero_photo_url) ?>"/>
        </div>
        <div class="form-group">
          <label>Upload New Portrait Image</label>
          <input type="file" name="hero_photo_file" accept="image/*"/>
          <?php if (!empty($hero_photo_url)): ?>
            <img src="<?= htmlspecialchars($hero_photo_url) ?>" class="preview-img" alt="Current Portrait"/>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- SECTION 2: ABOUT ME & STATS -->
    <div class="form-card">
      <div class="section-title">📖 ABOUT ME &amp; BIOGRAPHY</div>
      <div class="form-grid">
        <div class="form-group full-width">
          <label>About Heading Title</label>
          <input type="text" name="about_heading" value="<?= htmlspecialchars($about_heading) ?>"/>
        </div>
        <div class="form-group full-width">
          <label>Main Bio Paragraph 1</label>
          <textarea name="about_bio_1"><?= htmlspecialchars($about_bio_1) ?></textarea>
        </div>
        <div class="form-group full-width">
          <label>Main Bio Paragraph 2</label>
          <textarea name="about_bio_2"><?= htmlspecialchars($about_bio_2) ?></textarea>
        </div>
        <div class="form-group full-width">
          <label>Personal Quote / Motto</label>
          <input type="text" name="about_quote" value="<?= htmlspecialchars($about_quote) ?>"/>
        </div>
        <div class="form-group">
          <label>Years of Experience Stat</label>
          <input type="text" name="exp_years" value="<?= htmlspecialchars($exp_years) ?>"/>
        </div>
        <div class="form-group">
          <label>Total Ventures Stat</label>
          <input type="text" name="ventures_count" value="<?= htmlspecialchars($ventures_count) ?>"/>
        </div>
      </div>
    </div>

    <!-- SECTION 3: CONTACT & SOCIAL LINKS -->
    <div class="form-card">
      <div class="section-title">📞 CONTACT &amp; SOCIAL MEDIA LINKS</div>
      <div class="form-grid">
        <div class="form-group">
          <label>Phone Number</label>
          <input type="text" name="phone" value="<?= htmlspecialchars($phone) ?>"/>
        </div>
        <div class="form-group">
          <label>Email Address</label>
          <input type="email" name="email" value="<?= htmlspecialchars($email) ?>"/>
        </div>
        <div class="form-group">
          <label>WhatsApp Number</label>
          <input type="text" name="whatsapp" value="<?= htmlspecialchars($whatsapp) ?>"/>
        </div>
        <div class="form-group">
          <label>Facebook Page / Profile URL</label>
          <input type="url" name="facebook_url" value="<?= htmlspecialchars($facebook_url) ?>"/>
        </div>
        <div class="form-group">
          <label>Instagram Profile URL</label>
          <input type="url" name="instagram_url" value="<?= htmlspecialchars($instagram_url) ?>"/>
        </div>
        <div class="form-group">
          <label>Twitter / X Profile URL</label>
          <input type="url" name="twitter_url" value="<?= htmlspecialchars($twitter_url) ?>"/>
        </div>
        <div class="form-group">
          <label>YouTube Channel URL</label>
          <input type="url" name="youtube_url" value="<?= htmlspecialchars($youtube_url) ?>"/>
        </div>
      </div>
    </div>

    <!-- SAVE BUTTON -->
    <div style="text-align: center; margin-top: 1rem;">
      <button type="submit" class="btn-save">💾 Save &amp; Publish Changes Live</button>
    </div>

  </form>

</div>

</body>
</html>
