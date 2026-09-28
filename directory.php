<?php
session_start();
// =========================================================================
// directory.php  –  Trust Hub & Scam Blacklist Directory (User View)
// =========================================================================
require_once __DIR__ . '/config.php';

// Fetch all listings
$all_items = $pdo->query("SELECT * FROM trust_directory ORDER BY safety_rating DESC, name ASC")->fetchAll();

// Group by rating
$verified_items = [];
$scam_items = [];
$caution_items = [];

foreach ($all_items as $item) {
    if ($item['safety_rating'] === 'verified') $verified_items[] = $item;
    elseif ($item['safety_rating'] === 'scam') $scam_items[] = $item;
    else $caution_items[] = $item;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Trust Hub & Scam Shield — Fast Site</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Oswald:wght@500;700&display=swap" rel="stylesheet"/>
  <style>
    :root {
      --gold:   #fcb900;
      --gold2:  #ffda6a;
      --dark:   #0d0d14;
      --dark2:  #14141f;
      --dark3:  #1c1c2e;
      --panel:  rgba(255,255,255,0.04);
      --border: rgba(252,185,0,0.25);
      --text:   #e8e8f0;
      --muted:  #8888aa;
      --green:  #00e676;
      --red:    #ff5252;
      --radius: 14px;
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html { scroll-behavior: smooth; }

    body {
      font-family: 'Inter', sans-serif;
      background: var(--dark);
      color: var(--text);
      line-height: 1.7;
    }

    /* Scrollbar */
    ::-webkit-scrollbar { width: 6px; }
    ::-webkit-scrollbar-track { background: var(--dark2); }
    ::-webkit-scrollbar-thumb { background: var(--gold); border-radius: 3px; }

    /* Navbar */
    nav {
      position: sticky;
      top: 0;
      z-index: 900;
      background: rgba(13,13,20,0.85);
      backdrop-filter: blur(18px);
      border-bottom: 1px solid var(--border);
      padding: 0.8rem 2rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .nav-logo {
      font-family: 'Oswald', sans-serif;
      font-size: 1.5rem;
      font-weight: 700;
      background: linear-gradient(90deg, var(--gold), var(--gold2));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      letter-spacing: 1px;
    }

    .back-btn {
      color: var(--gold);
      text-decoration: none;
      font-size: 0.85rem;
      font-weight: 700;
      border: 1px solid var(--border);
      padding: 0.4rem 1.1rem;
      border-radius: 50px;
      transition: all 0.25s;
    }
    .back-btn:hover {
      background: rgba(252, 185, 0, 0.08);
      transform: translateY(-1px);
    }

    /* Header Panel */
    .header-hero {
      text-align: center;
      padding: 4rem 1.5rem 3rem;
      position: relative;
      overflow: hidden;
    }
    .header-hero::before {
      content: '';
      position: absolute;
      width: 400px; height: 400px;
      background: rgba(252,185,0,0.06);
      border-radius: 50%;
      filter: blur(80px);
      top: -100px; left: 50%;
      transform: translateX(-50%);
      pointer-events: none;
    }

    .shield-badge {
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      background: rgba(252,185,0,0.12);
      border: 1px solid var(--border);
      border-radius: 50px;
      padding: 0.3rem 1rem;
      font-size: 0.72rem;
      font-weight: 700;
      color: var(--gold);
      text-transform: uppercase;
      margin-bottom: 1rem;
    }

    .main-title {
      font-family: 'Oswald', sans-serif;
      font-size: clamp(1.8rem, 5vw, 2.8rem);
      font-weight: 700;
      line-height: 1.2;
      margin-bottom: 0.5rem;
      text-transform: uppercase;
    }
    .main-title span { color: var(--gold); }
    
    .desc {
      max-width: 600px;
      margin: 0 auto 2rem;
      color: var(--muted);
      font-size: 0.92rem;
    }

    /* Checker Widget */
    .checker-section {
      max-width: 500px;
      margin: 0 auto 3rem;
      padding: 0 1.2rem;
    }
    .checker-wrap {
      display: flex;
      border: 1.5px solid var(--border);
      border-radius: 50px;
      overflow: hidden;
      background: var(--dark3);
      box-shadow: 0 4px 24px rgba(0,0,0,0.4);
      transition: all 0.3s;
    }
    .checker-wrap:focus-within {
      border-color: var(--gold);
      box-shadow: 0 0 15px var(--gold-glow);
    }
    .checker-wrap input {
      flex: 1;
      background: transparent;
      border: none;
      outline: none;
      padding: 0.75rem 1.2rem;
      color: var(--text);
      font-family: inherit;
      font-size: 0.88rem;
    }
    .checker-wrap input::placeholder { color: var(--muted); }
    .checker-wrap button {
      background: linear-gradient(135deg, var(--gold), #e6a800);
      border: none;
      padding: 0.75rem 1.5rem;
      font-weight: 800;
      color: #0d0d14;
      cursor: pointer;
      border-radius: 0 50px 50px 0;
      transition: opacity 0.2s;
    }
    .checker-wrap button:hover { opacity: 0.85; }

    /* Glassmorphic result pop-up */
    .safety-alert-box {
      margin-top: 1rem;
      border-radius: var(--radius);
      padding: 1.2rem;
      text-align: left;
      display: none;
      animation: fadeInUp 0.3s ease;
    }
    .safety-alert-verified { background: rgba(0, 230, 118, 0.08); border: 1px solid rgba(0, 230, 118, 0.25); color: var(--green); }
    .safety-alert-caution { background: rgba(252, 185, 0, 0.08); border: 1px solid rgba(252, 185, 0, 0.25); color: var(--gold); }
    .safety-alert-scam { background: rgba(255, 82, 82, 0.08); border: 1px solid rgba(255, 82, 82, 0.25); color: var(--red); }

    .safety-header { font-size: 1.1rem; font-weight: 800; margin-bottom: 0.4rem; display: flex; align-items: center; gap: 0.4rem; }
    .safety-body { font-size: 0.85rem; color: var(--text); margin-bottom: 0.8rem; }
    .safety-btn {
      display: inline-block; background: var(--gold); color: #000; text-decoration: none; font-size: 0.76rem; font-weight: 800; padding: 0.4rem 1rem; border-radius: 50px;
    }

    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }

    /* Layout Sections */
    .container { max-width: 960px; margin: 0 auto; padding: 0 1.2rem; }

    .section-divider {
      height: 1px;
      background: linear-gradient(90deg, transparent, var(--border), transparent);
      margin: 3rem auto;
    }

    .sec-title {
      font-family: 'Oswald', sans-serif;
      font-size: 1.6rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      margin-bottom: 1.2rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    /* Cards Grid */
    .grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 1.2rem;
    }

    .card {
      background: var(--dark2);
      border: 1px solid rgba(255, 255, 255, 0.05);
      border-radius: var(--radius);
      padding: 1.3rem;
      transition: all 0.3s;
      position: relative;
    }
    .card:hover {
      transform: translateY(-4px);
      border-color: var(--border);
      box-shadow: 0 10px 30px rgba(0,0,0,0.4);
    }

    .card-top {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 0.8rem;
    }
    .card-title { font-weight: 700; font-size: 1.05rem; color: #fff; }
    .card-cat { font-size: 0.68rem; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: 0.05em; }

    .badge {
      font-size: 0.65rem; font-weight: 800; padding: 2px 8px; border-radius: 50px; text-transform: uppercase;
    }
    .badge-verified { background: rgba(0, 230, 118, 0.12); color: var(--green); border: 1px solid rgba(0, 230, 118, 0.2); }
    .badge-scam { background: rgba(255, 82, 82, 0.12); color: var(--red); border: 1px solid rgba(255, 82, 82, 0.2); }
    .badge-caution { background: rgba(252, 185, 0, 0.12); color: var(--gold); border: 1px solid rgba(252, 185, 0, 0.2); }

    .card-desc { font-size: 0.8rem; color: var(--muted); line-height: 1.6; margin-bottom: 1.2rem; }
    
    .card-footer {
      border-top: 1px solid rgba(255, 255, 255, 0.04);
      padding-top: 0.8rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .card-link {
      color: var(--gold); font-size: 0.78rem; font-weight: 800; text-decoration: none; display: flex; align-items: center; gap: 0.2rem;
    }
    .card-link:hover { text-decoration: underline; }

    /* Escrow CTA Panel */
    .escrow-panel {
      background: linear-gradient(135deg, rgba(252,185,0,0.06) 0%, rgba(13,13,20,0.2) 100%);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 2rem;
      text-align: center;
      margin-top: 4rem;
      box-shadow: 0 8px 30px rgba(0,0,0,0.5);
    }
    .escrow-panel h3 { font-family: 'Oswald', sans-serif; font-size: 1.4rem; color: var(--gold); text-transform: uppercase; margin-bottom: 0.6rem; }
    .escrow-panel p { max-width: 600px; margin: 0 auto 1.5rem; font-size: 0.88rem; color: var(--muted); }
    .escrow-btn {
      display: inline-flex; background: linear-gradient(135deg, var(--gold), #e6a800); color: #0d0d14; font-weight: 800; font-size: 0.9rem; padding: 0.65rem 1.6rem; border-radius: 50px; text-decoration: none; box-shadow: 0 4px 15px rgba(252,185,0,0.2);
    }

    /* Footer */
    footer {
      background: var(--dark2);
      border-top: 1px solid var(--border);
      padding: 3rem 1.5rem 2rem;
      text-align: center;
      margin-top: 5rem;
    }
    footer p { font-size: 0.8rem; color: var(--muted); }
  </style>
</head>
<body>

<?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true): ?>
  <div class="admin-view-bar" style="background: linear-gradient(90deg, #14141f, #0d0d14); border-bottom: 2px solid #fcb900; color: #fff; padding: 0.6rem 2rem; display: flex; justify-content: space-between; align-items: center; font-size: 0.82rem; font-weight: 600; box-shadow: 0 4px 15px rgba(0,0,0,0.5); z-index: 10000; position: relative; font-family: 'Inter', sans-serif;">
    <div style="display: flex; align-items: center; gap: 0.6rem;">
      <span style="display: inline-block; width: 8px; height: 8px; background: #00e676; border-radius: 50%; box-shadow: 0 0 8px #00e676; animation: adminPulse 1.5s infinite alternate;"></span>
      <span>Admin View Mode <span style="color: #8888aa;">(Logged in as: <strong style="color: #fcb900;"><?= htmlspecialchars($_SESSION['admin_user']) ?></strong>)</span></span>
    </div>
    <a href="admin/dashboard.php" style="background: linear-gradient(135deg, #fcb900, #e6a800); color: #0d0d14; text-decoration: none; padding: 0.4rem 1.2rem; border-radius: 50px; font-weight: 800; font-size: 0.76rem; display: inline-flex; align-items: center; gap: 0.3rem; box-shadow: 0 4px 12px rgba(252,185,0,0.25); transition: all 0.2s; border: 1px solid rgba(252,185,0,0.4);" onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 6px 16px rgba(252,185,0,0.4)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(252,185,0,0.25)';">
      ⚙️ Admin Panel
    </a>
  </div>
  <style>
    @keyframes adminPulse {
      0% { transform: scale(1); opacity: 0.8; }
      100% { transform: scale(1.2); opacity: 1; box-shadow: 0 0 12px #00e676; }
    }
  </style>
<?php endif; ?>

<!-- Navbar -->
<nav>
  <div class="nav-logo">⚡ FAST SITE</div>
  <a href="index.php" class="back-btn">← Back to Services</a>
</nav>

<!-- Header -->
<div class="header-hero">
  <div class="shield-badge">🛡️ Scam Shield Active</div>
  <h1 class="main-title">TRUST & SAFETY <span>DIRECTORY</span></h1>
  <p class="desc">Search any website, app, or seller account before shopping to check reviews and verify they are safe from fraud.</p>
</div>

<!-- Checker input -->
<div class="checker-section">
  <div class="checker-wrap">
    <input type="text" id="safety-input" placeholder="Enter URL or app name (e.g. gift-shop.com)" onkeyup="if(event.key === 'Enter') checkSafety()"/>
    <button onclick="checkSafety()">Verify</button>
  </div>
  
  <!-- Popup result alert -->
  <div class="safety-alert-box" id="result-box"></div>
</div>

<div class="container">
  
  <!-- Verified Safe Directory -->
  <div class="sec-title"><span>🟢</span> VERIFIED LEGIT STORES</div>
  <div class="grid" style="margin-bottom: 3rem;">
    <?php if (empty($verified_items)): ?>
      <div style="grid-column: span 3; text-align: center; color: var(--muted); padding: 2rem 0;">NO VERIFIED STORES REGISTERED YET.</div>
    <?php else: ?>
      <?php foreach($verified_items as $v): ?>
        <div class="card">
          <div class="card-top">
            <span class="card-title"><?= htmlspecialchars($v['name']) ?></span>
            <span class="badge badge-verified">VERIFIED</span>
          </div>
          <p class="card-desc"><?= htmlspecialchars($v['admin_review'] ?? 'Verified safe shopping site.') ?></p>
          <div class="card-footer">
            <span class="card-cat"><?= htmlspecialchars($v['category']) ?></span>
            <?php if (!empty($v['url'])): ?>
              <a href="http://<?= htmlspecialchars($v['url']) ?>" target="_blank" class="card-link">VISIT SITE ➔</a>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <div class="section-divider"></div>

  <!-- Confirmed Scam Blacklist -->
  <div class="sec-title" style="color: var(--red);"><span>🔴</span> BLACKLISTED FRAUD ALERTS</div>
  <div class="grid">
    <?php if (empty($scam_items)): ?>
      <div style="grid-column: span 3; text-align: center; color: var(--muted); padding: 2rem 0;">NO ACTIVE FRAUD REPORTS LISTED. GOOD NEWS!</div>
    <?php else: ?>
      <?php foreach($scam_items as $s): ?>
        <div class="card" style="border-color: rgba(255, 82, 82, 0.15);">
          <div class="card-top">
            <span class="card-title" style="color: var(--red);"><?= htmlspecialchars($s['name']) ?></span>
            <span class="badge badge-scam">FRAUD</span>
          </div>
          <p class="card-desc"><?= htmlspecialchars($s['admin_review'] ?? 'Confirmed scam app or page.') ?></p>
          <div class="card-footer">
            <span class="card-cat"><?= htmlspecialchars($s['category']) ?></span>
            <?php if(!empty($s['url'])): ?>
              <span style="font-size: 0.72rem; color: var(--red); font-weight: 700;"><?= htmlspecialchars($s['url']) ?></span>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- Escrow / Agent help CTA -->
  <div class="escrow-panel">
    <h3>⚠️ STILL SUSPICIOUS ABOUT A WEBSITE?</h3>
    <p>Don't get scammed. If you want to buy from an unverified shop safely, place an Escrow order through our system. We will verify the seller, handle the payment securely, and deliver your products safely!</p>
    <a href="index.php#dynamic-services" class="escrow-btn">💬 REQUEST ESCROW / BUY SECURELY</a>
  </div>

</div>

<!-- Footer -->
<footer>
  <p>© 2026 Fast Site Scam Shield — All rights reserved.</p>
</footer>

<script>
function checkSafety() {
  const q = document.getElementById('safety-input').value.trim();
  const box = document.getElementById('result-box');
  
  if (!q) {
    box.style.display = 'block';
    box.className = 'safety-alert-box safety-alert-caution';
    box.innerHTML = '<div class="safety-header">⚠️ Warning</div><div class="safety-body">Please type a website domain or app name first.</div>';
    return;
  }
  
  box.style.display = 'block';
  box.className = 'safety-alert-box safety-alert-caution';
  box.innerHTML = '<div class="safety-body">⏳ Checking database records...</div>';
  
  fetch('check_safety.php?q=' + encodeURIComponent(q))
    .then(res => res.json())
    .then(data => {
      box.className = 'safety-alert-box';
      if (data.success) {
        if (data.found) {
          if (data.safety_rating === 'verified') {
            box.classList.add('safety-alert-verified');
            box.innerHTML = `
              <div class="safety-header">🟢 Verified Safe: ${escapeHtml(data.name)}</div>
              <div class="safety-body">${escapeHtml(data.admin_review)}</div>
              ${data.redirection_link ? `<a href="${escapeHtml(data.redirection_link)}" target="_blank" class="safety-btn">Shop Securely Now ➔</a>` : ''}
            `;
          } else if (data.safety_rating === 'scam') {
            box.classList.add('safety-alert-scam');
            box.innerHTML = `
              <div class="safety-header">🔴 CONFIRMED SCAM: ${escapeHtml(data.name)}</div>
              <div class="safety-body"><strong>Warning:</strong> ${escapeHtml(data.admin_review)}</div>
              <a href="index.php" class="safety-btn" style="background:var(--red); color:#fff;">Report Another Scam</a>
            `;
          } else {
            box.classList.add('safety-alert-caution');
            box.innerHTML = `
              <div class="safety-header">🟡 Proceed with Caution: ${escapeHtml(data.name)}</div>
              <div class="safety-body">${escapeHtml(data.admin_review)}</div>
            `;
          }
        } else {
          box.classList.add('safety-alert-caution');
          box.innerHTML = `
            <div class="safety-header">⚠️ Unverified Source</div>
            <div class="safety-body">${escapeHtml(data.message)}</div>
            <a href="index.php" class="safety-btn">Contact Agent for Escrow</a>
          `;
        }
      } else {
        box.classList.add('safety-alert-scam');
        box.innerHTML = `<div class="safety-header">❌ Error</div><div class="safety-body">${escapeHtml(data.message)}</div>`;
      }
    })
    .catch(err => {
      box.className = 'safety-alert-box safety-alert-scam';
      box.innerHTML = `<div class="safety-header">❌ Error</div><div class="safety-body">Failed to contact checker server: ${err}</div>`;
    });
}

function escapeHtml(text) {
  if (!text) return '';
  const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
  return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}
</script>

</body>
</html>
