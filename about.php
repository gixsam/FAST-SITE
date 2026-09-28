<?php
session_start();
require_once __DIR__ . '/config.php';

$settings = $pdo->query("SELECT setting_key, setting_value FROM homepage_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
$siteName = $settings['site_name'] ?? 'FAST SITE';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Company Profile — <?= htmlspecialchars($siteName) ?></title>
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
      --green: #10b981;
      --brand: #3b82f6;
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    
    body {
      font-family: 'Inter', sans-serif;
      background: var(--dark);
      color: var(--text);
      min-height: 100vh;
      line-height: 1.6;
      overflow-x: hidden;
      background-image: radial-gradient(circle at top center, rgba(59, 130, 246, 0.1), transparent 50%),
                        radial-gradient(circle at bottom right, rgba(252, 185, 0, 0.05), transparent 50%);
    }

    .hero {
      text-align: center;
      padding: 6rem 1.5rem 4rem;
      position: relative;
    }

    .hero h1 {
      font-family: 'Oswald', sans-serif;
      font-size: 3.5rem;
      font-weight: 800;
      margin-bottom: 1rem;
      background: linear-gradient(90deg, #fff, var(--muted));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      line-height: 1.2;
    }
    
    .hero h1 span {
      background: linear-gradient(90deg, var(--gold), #ffda6a);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .hero p {
      color: var(--muted);
      font-size: 1.1rem;
      max-width: 600px;
      margin: 0 auto 2rem;
    }

    .container {
      max-width: 1000px;
      margin: 0 auto;
      padding: 0 1.5rem;
    }

    .grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 2rem;
      margin-bottom: 4rem;
    }

    .card {
      background: var(--dark-card);
      backdrop-filter: blur(12px);
      border: 1px solid var(--border);
      border-radius: 16px;
      padding: 2.5rem;
      transition: transform 0.3s, box-shadow 0.3s;
      position: relative;
      overflow: hidden;
    }

    .card::before {
      content: '';
      position: absolute;
      top: 0; left: 0; right: 0; height: 3px;
      background: linear-gradient(90deg, var(--gold), var(--brand));
      opacity: 0;
      transition: opacity 0.3s;
    }

    .card:hover {
      transform: translateY(-5px);
      box-shadow: 0 20px 40px rgba(0,0,0,0.4);
    }
    .card:hover::before { opacity: 1; }

    .card h3 {
      font-size: 1.5rem;
      margin-bottom: 1rem;
      color: #fff;
    }

    .card p {
      color: var(--muted);
      font-size: 0.95rem;
    }

    .stats {
      display: flex;
      justify-content: space-around;
      flex-wrap: wrap;
      background: rgba(0,0,0,0.3);
      border: 1px solid var(--border);
      border-radius: 16px;
      padding: 3rem 1.5rem;
      margin: 4rem 0;
      text-align: center;
    }

    .stat-item h2 {
      font-size: 3rem;
      color: var(--gold);
      font-weight: 900;
      margin-bottom: 0.5rem;
    }
    .stat-item p {
      color: var(--muted);
      text-transform: uppercase;
      letter-spacing: 2px;
      font-size: 0.85rem;
      font-weight: 600;
    }

    @media (max-width: 768px) {
      .hero h1 { font-size: 2.5rem; }
      .stat-item { margin-bottom: 2rem; width: 100%; }
      .stat-item:last-child { margin-bottom: 0; }
    }
  </style>
</head>
<body>
  <?php include __DIR__ . '/includes/nav_public.php'; ?>

  <div class="hero">
    <h1>The Ultimate <span>Digital Empire</span></h1>
    <p>Welcome to <?= htmlspecialchars($siteName) ?>. We are redefining the marketplace experience through verified escrow transactions, high-speed digital services, and an interconnected partner network.</p>
  </div>

  <div class="container">
    <div class="grid">
      <div class="card">
        <h3>Our Mission</h3>
        <p>To provide a secure, seamless, and fully automated platform where users can safely transact, and entrepreneurs can grow their businesses through our massive interconnected empire.</p>
      </div>
      
      <div class="card">
        <h3>Why Choose Us?</h3>
        <p>With an integrated escrow system backed by <?= htmlspecialchars($settings['coin_name'] ?? 'Fast Coins') ?>, instant API provisioning, and strict verification layers, we ensure absolute trust in every transaction.</p>
      </div>

      <div class="card">
        <h3>The Master Network</h3>
        <p>We operate 7 interconnected platforms under one Master Admin Control Hub. Our architecture allows instant data flow and unified product discovery across multiple domains.</p>
      </div>
    </div>

    <div class="stats">
      <div class="stat-item">
        <h2>100%</h2>
        <p>Verified Escrow</p>
      </div>
      <div class="stat-item">
        <h2>7+</h2>
        <p>Connected Platforms</p>
      </div>
      <div class="stat-item">
        <h2>24/7</h2>
        <p>Automated Systems</p>
      </div>
    </div>
  </div>

  <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
