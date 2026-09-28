<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: /user/login.php'); exit; }
require_once __DIR__ . '/../config.php';

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute([':id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header('Location: /user/login.php'); exit;
}

if ($user['role'] === 'partner') {
    header('Location: /partner/dashboard.php'); exit;
}

$msg = '';
$err = '';

// Check profile completeness
$required_fields = ['name', 'phone', 'email', 'address', 'profile_pic'];
$missing_fields = [];
foreach ($required_fields as $f) {
    if (empty($user[$f])) {
        $missing_fields[] = ucfirst(str_replace('_', ' ', $f));
    }
}
$is_complete = count($missing_fields) === 0;

// Check existing request
$req_stmt = $pdo->prepare("SELECT * FROM partner_requests WHERE user_id = :uid ORDER BY id DESC LIMIT 1");
$req_stmt->execute([':uid' => $user_id]);
$existing_request = $req_stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_request'])) {
    if ($is_complete && (!$existing_request || $existing_request['status'] === 'rejected')) {
        $ins = $pdo->prepare("INSERT INTO partner_requests (user_id, status) VALUES (:uid, 'pending')");
        if ($ins->execute([':uid' => $user_id])) {
            $msg = "Your partner request has been submitted successfully! We will review it shortly.";
            // Refresh request status
            $req_stmt->execute([':uid' => $user_id]);
            $existing_request = $req_stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $err = "Something went wrong. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Become a Partner — Fast Site</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Oswald:wght@600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="/assets/css/user.css">
</head>
<body>
<div class="container">
  <div style="margin-bottom: 1rem;">
      <a href="dashboard.php" style="color:var(--muted); text-decoration:none; font-weight:600;">← Back to Dashboard</a>
  </div>

  <div class="card">
    <h2>🤝 Partner Program</h2>
    <p class="desc">Join our elite network of API Partners and Affiliates. Unlock the master dashboard, access exclusive tools, and scale your business with us.</p>

    <?php if ($msg): ?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>
    <?php if ($err): ?><div class="alert alert-error"><?= $err ?></div><?php endif; ?>

    <?php if ($existing_request && $existing_request['status'] === 'pending'): ?>
        <div class="alert alert-warning" style="text-align:center;">
            <h3 style="margin-top:0;">⏳ Under Review</h3>
            <p style="margin-bottom:0; font-weight:normal; font-size:0.95rem;">Your application is currently being reviewed by our administrators. Please check back later.</p>
        </div>
    <?php else: ?>

        <?php if (!$is_complete): ?>
            <div class="alert alert-error" style="text-align:left;">
                <strong>Profile Incomplete!</strong>
                <p style="margin: 0.5rem 0 0; font-size:0.9rem; font-weight:normal;">You must complete your profile before applying. Missing fields:</p>
                <ul style="margin: 0.5rem 0 0 1.5rem; font-size:0.9rem; font-weight:normal;">
                    <?php foreach($missing_fields as $mf): ?>
                        <li><?= $mf ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <a href="profile.php" class="btn btn-outline">Update Profile</a>
        
        <?php else: ?>
            <div class="status-box">
                <h4>Application Data</h4>
                <div class="data-row">
                    <span style="color:var(--muted);">Name</span>
                    <span><?= htmlspecialchars($user['name']) ?></span>
                </div>
                <div class="data-row">
                    <span style="color:var(--muted);">Phone</span>
                    <span><?= htmlspecialchars($user['phone']) ?></span>
                </div>
                <div class="data-row">
                    <span style="color:var(--muted);">Email</span>
                    <span><?= htmlspecialchars($user['email']) ?></span>
                </div>
                <div class="data-row">
                    <span style="color:var(--muted);">Profile Picture</span>
                    <span style="color:var(--green);">✓ Provided</span>
                </div>
                <div class="data-row">
                    <span style="color:var(--muted);">KYC Documents</span>
                    <span><?= ($user['nid'] || $user['etin'] || $user['passport'] || $user['driving_license']) ? '<span style="color:var(--green);">✓ Provided</span>' : '<span style="color:var(--gold);">⚠ Optional</span>' ?></span>
                </div>
            </div>

            <form method="POST">
                <button type="submit" name="submit_request" class="btn">🚀 Submit Partner Request</button>
            </form>
            <p style="font-size:0.8rem; color:var(--muted); margin-top:1rem;">By submitting this request, you agree to our Partner Terms and Conditions.</p>
        <?php endif; ?>

    <?php endif; ?>
  </div>
</div>
</body>
</html>
