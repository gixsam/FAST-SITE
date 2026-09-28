<?php
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$settings = $pdo->query("SELECT setting_key, setting_value FROM homepage_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
$isJobBoardEnabled = ($settings['feature_job_board'] ?? 'off') === 'on';

$isUserLoggedIn = isset($_SESSION['user_id']);
$user_id = $isUserLoggedIn ? $_SESSION['user_id'] : null;

// Fetch requests
$requests = [];
if ($isJobBoardEnabled) {
    try {
        $stmt = $pdo->query("SELECT j.*, u.name as user_name FROM jobs j JOIN users u ON j.user_id = u.id WHERE j.status = 'open' ORDER BY j.created_at DESC");
        $requests = $stmt->fetchAll();
    } catch (Exception $e) {}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Job Board — Request a Service</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Oswald:wght@600;700&display=swap" rel="stylesheet"/>
  <style>
    :root {
      --gold: #fcb900;
      --gold-glow: rgba(252, 185, 0, 0.2);
      --dark: #0a0a0f;
      --dark-card: rgba(18, 18, 26, 0.65);
      --border: rgba(255, 255, 255, 0.06);
      --text: #f8f8f8;
      --muted: #9ca3af;
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    
    body {
      font-family: 'Inter', sans-serif;
      background: var(--dark);
      color: var(--text);
      min-height: 100vh;
      line-height: 1.6;
    }

    .top-header {
      background: rgba(13, 13, 20, 0.9);
      backdrop-filter: blur(18px);
      border-bottom: 1px solid var(--border);
      padding: 0.8rem 1.5rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      position: sticky;
      top: 0;
      z-index: 1000;
    }

    .brand {
      font-family: 'Oswald', sans-serif;
      font-size: 1.4rem;
      font-weight: 700;
      color: var(--gold);
      text-decoration: none;
    }
    
    .container {
      max-width: 1000px;
      margin: 0 auto;
      padding: 2rem 1rem;
    }

    .under-construction {
      text-align: center;
      padding: 5rem 1rem;
      background: var(--dark-card);
      border: 1px solid var(--border);
      border-radius: 12px;
      margin-top: 2rem;
    }

    .under-construction h1 {
      color: var(--gold);
      font-size: 2.5rem;
      margin-bottom: 1rem;
    }

    .card {
      background: var(--dark-card);
      border: 1px solid var(--border);
      padding: 1.5rem;
      border-radius: 12px;
      margin-bottom: 1rem;
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
    }

    .card h3 {
      color: #fff;
      font-size: 1.2rem;
    }

    .card p {
      color: var(--muted);
      font-size: 0.95rem;
    }

    .card .meta {
      font-size: 0.85rem;
      color: var(--gold);
      font-weight: 600;
    }
  </style>
</head>
<body>
  <div class="top-header">
    <a href="index.php" class="brand">← Back to Marketplace</a>
  </div>

  <div class="container">
    <?php if (!$isJobBoardEnabled): ?>
      <div class="under-construction">
        <h1>📌 Job Board (Under Construction)</h1>
        <p style="color: var(--muted); max-width: 600px; margin: 0 auto; font-size: 1.1rem;">
          We are currently building the Request a Service Job Board! Soon, you will be able to post your needs here (e.g., "Need an NID corrected"), and our Verified Partners will bid to help you securely.
        </p>
      </div>
    <?php else: ?>
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 2rem;">
        <h1 style="color: var(--gold);">📌 Request a Service</h1>
        <a href="user/post_job.php" style="background: var(--gold); color: #000; text-decoration: none; padding: 0.8rem 1.5rem; border-radius: 8px; font-weight: 700;">+ Post Request</a>
      </div>

      <?php if(empty($requests)): ?>
        <p style="color: var(--muted); text-align:center;">No open requests right now. Be the first to post!</p>
      <?php else: ?>
        <?php foreach($requests as $r): ?>
          <div class="card">
            <h3><?= htmlspecialchars($r['title']) ?></h3>
            <p><?= htmlspecialchars($r['description']) ?></p>
            <div style="display:flex; justify-content:space-between; align-items:center; margin-top:1rem;">
              <span class="meta">Budget: 🪙 <?= number_format($r['budget'], 2) ?></span>
              <a href="partner/bid_on_job.php?id=<?= $r['id'] ?>" style="background: rgba(255,255,255,0.1); color: #fff; border: 1px solid var(--border); padding: 0.4rem 1rem; border-radius: 6px; text-decoration: none;">Bid to Help</a>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</body>
</html>
