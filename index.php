<?php
// =========================================================================
// index.php  –  Front Controller & Central Router
// =========================================================================
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$url = isset($_GET['url']) ? rtrim($_GET['url'], '/') : '';

// ---------------------------------------------------------
// 1. Clean URL Aliases (Custom Routes)
// ---------------------------------------------------------
$routes = [
    '' => 'home.php',
    'login' => 'user/login.php',
    'register' => 'user/register.php',
    'dashboard' => 'user/dashboard.php',
    'partner' => 'partner/dashboard.php',
    'admin' => 'admin/dashboard.php',
    'marketplace' => 'home.php'
];

if (array_key_exists($url, $routes)) {
    $file_to_load = $routes[$url];
    if (file_exists($file_to_load)) {
        require_once $file_to_load;
        exit;
    }
}

// ---------------------------------------------------------
// 2. Shop URL Route (/shop/walton)
// ---------------------------------------------------------
if (preg_match('#^shop/([a-zA-Z0-9_-]+)$#', $url, $matches)) {
    $_GET['slug'] = $matches[1];
    $_GET['shop'] = $matches[1];
    if (file_exists('shop.php')) {
        require_once 'shop.php';
    } else {
        require_once 'shop_wrapper.php';
    }
    exit;
}

// ---------------------------------------------------------
// 3. Affiliate Referral Route (/ref/AGENT01)
// ---------------------------------------------------------
if (preg_match('#^ref/([A-Za-z0-9_-]+)$#', $url, $matches)) {
    $code = $matches[1];
    
    // Verify agent or user exists (case-insensitive for user ref codes like USER_xxx)
    $stmt = $pdo->prepare("SELECT id FROM agents WHERE UPPER(ref_code) = UPPER(:c) AND status = 'active' UNION SELECT id FROM users WHERE UPPER(ref_code) = UPPER(:c)");
    $stmt->execute([':c' => $code]);
    if ($stmt->fetch()) {
        // Set cookie for 30 days
        setcookie('fastsite_ref', $code, time() + (30 * 24 * 3600), '/', '', false, true);
    }
    
    // Redirect to home (dynamic - works on localhost AND live)
    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
    header('Location: ' . $baseUrl . '/');
    exit;
}

// ---------------------------------------------------------
// 3. Fallback: Physical File Mapping (e.g. /user/profile -> user/profile.php)
// ---------------------------------------------------------
$physical_file = $url . '.php';
if (file_exists($physical_file)) {
    require_once $physical_file;
    exit;
}

// Also check if they are trying to access a directory with an index.php
if (is_dir($url) && file_exists($url . '/index.php')) {
    require_once $url . '/index.php';
    exit;
}

// If the physical file was requested directly (with .php), let it load
if (file_exists($url) && is_file($url)) {
    require_once $url;
    exit;
}

// ---------------------------------------------------------
// 4. 404 Not Found
// ---------------------------------------------------------
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>404 — Page Not Found | Fast Site</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=Oswald:wght@700&display=swap" rel="stylesheet"/>
  <style>
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:'Inter',sans-serif;background:#0a0a0f;color:#f8f8f8;min-height:100vh;display:flex;align-items:center;justify-content:center;text-align:center;padding:2rem;}
    .container{max-width:500px;}
    .code{font-family:'Oswald',sans-serif;font-size:8rem;font-weight:900;background:linear-gradient(135deg,#fff,#fcb900);-webkit-background-clip:text;-webkit-text-fill-color:transparent;line-height:1;margin-bottom:1rem;}
    h1{font-size:1.5rem;font-weight:700;margin-bottom:0.8rem;color:#fff;}
    p{color:#9ca3af;margin-bottom:2rem;line-height:1.6;}
    .btn{display:inline-block;background:linear-gradient(135deg,#fcb900,#ff9100);color:#000;font-weight:800;padding:0.9rem 2.5rem;border-radius:50px;text-decoration:none;font-size:0.95rem;box-shadow:0 4px 20px rgba(252,185,0,0.3);transition:all 0.2s;}
    .btn:hover{transform:translateY(-2px);box-shadow:0 8px 30px rgba(252,185,0,0.4);}
  </style>
</head>
<body>
  <div class="container">
    <div class="code">404</div>
    <h1>Page Not Found</h1>
    <p>The page <strong><?= htmlspecialchars($url) ?></strong> doesn't exist or has been moved.</p>
    <a href="/" class="btn">⚡ Back to Marketplace</a>
  </div>
</body>
</html>
<?php
exit;
