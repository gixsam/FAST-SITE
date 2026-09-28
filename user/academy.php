<?php
require_once __DIR__ . '/../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$settings = $pdo->query("SELECT setting_key, setting_value FROM homepage_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
$isAcademyEnabled = ($settings['feature_academy'] ?? 'off') === 'on';

if (($settings['feature_academy'] ?? 'off') === 'off') {
    die("This feature is currently disabled.");
}

$isUserLoggedIn = isset($_SESSION['user_id']);
if (!$isUserLoggedIn) {
    header('Location: /user/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Learn & Earn Academy</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Oswald:wght@600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="/assets/css/user.css">
</head>
<body>
  <div class="top-header">
    <a href="dashboard.php" class="brand">← Back to Dashboard</a>
  </div>

  <div class="container">
    <?php if (!$isAcademyEnabled): ?>
      <div class="under-construction">
        <h1>🎓 Learn & Earn Academy (Under Construction)</h1>
        <p style="color: var(--muted); max-width: 600px; margin: 0 auto; font-size: 1.1rem;">
          We are building a comprehensive training center. You'll be able to read free guides on how to provide digital services, and instantly become a Partner to start earning money!
        </p>
      </div>
    <?php else: ?>
      <div style="text-align:center; margin-bottom: 3rem;">
        <h1 style="color: var(--gold); font-size: 2.5rem; margin-bottom: 0.5rem;">🎓 Learn & Earn Academy</h1>
        <p style="color: var(--muted); max-width: 600px; margin: 0 auto; font-size: 1.1rem;">Read our quick guides to master a digital skill, then immediately start selling it on the Fast Site Marketplace!</p>
      </div>

      <div class="course-grid">
        <div class="course-card">
          <div class="course-img">🎨</div>
          <div class="course-body">
            <div class="course-title">How to Design a CV with Canva</div>
            <div class="course-desc">Learn how to create professional resumes using free Canva templates. High demand service!</div>
            <a href="../partner/product_add.php?category=Design" class="btn-earn">Start Earning with this Skill 🚀</a>
          </div>
        </div>

        <div class="course-card">
          <div class="course-img">📄</div>
          <div class="course-body">
            <div class="course-title">Online Trade License Application</div>
            <div class="course-desc">A step-by-step guide to helping businesses apply for their E-Trade license online.</div>
            <a href="../partner/product_add.php?category=Govt+Services" class="btn-earn">Start Earning with this Skill 🚀</a>
          </div>
        </div>

        <div class="course-card">
          <div class="course-img">🪪</div>
          <div class="course-body">
            <div class="course-title">NID Information Retrieval</div>
            <div class="course-desc">Master the process of downloading and correcting National ID card information.</div>
            <a href="../partner/product_add.php?category=Govt+Services" class="btn-earn">Start Earning with this Skill 🚀</a>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>
</body>
</html>
