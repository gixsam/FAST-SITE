<?php
require_once 'nav.php'; // Ensures partner is logged in and gets $partner_id

$partner_id = $partner['id'] ?? 0;

if ($partner['status'] !== 'approved') {
    echo "<div class='main-content'><div class='box' style='text-align:center;'><h2>Shop Under Review</h2><p>Your shop must be approved by the admin before you can bid on jobs.</p><br><a href='dashboard.php' class='btn'>Back to Dashboard</a></div></div></body></html>";
    exit;
}

$job_id = intval($_GET['id'] ?? 0);
if ($job_id <= 0) die("Invalid job ID");

$stmt = $pdo->prepare("SELECT j.*, u.name as user_name FROM jobs j JOIN users u ON j.user_id = u.id WHERE j.id = ? AND j.status = 'open' LIMIT 1");
$stmt->execute([$job_id]);
$job = $stmt->fetch();

if (!$job) {
    echo "<div style='color:#fff; padding:2rem; text-align:center;'>Job not found or already awarded. <a href='../marketplace_board.php' style='color:#fcb900;'>Go back</a></div>";
    exit;
}

$err = $msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bid_amount = floatval($_POST['bid_amount'] ?? 0);
    $proposal = trim($_POST['proposal'] ?? '');

    if ($bid_amount <= 0 || empty($proposal)) {
        $err = "All fields are required and bid amount must be greater than 0.";
    } else {
        // Check if already bid
        $chk = $pdo->prepare("SELECT id FROM job_bids WHERE job_id = ? AND partner_id = ?");
        $chk->execute([$job_id, $partner_id]);
        if ($chk->fetch()) {
            $err = "You have already placed a bid on this job.";
        } else {
            $ins = $pdo->prepare("INSERT INTO job_bids (job_id, partner_id, bid_amount, proposal) VALUES (?, ?, ?, ?)");
            if ($ins->execute([$job_id, $partner_id, $bid_amount, $proposal])) {
                $msg = "Bid submitted successfully! The user will review your proposal.";
            } else {
                $err = "Failed to submit bid.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Bid on Job</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    body { background: #0a0a0f; color: #fff; font-family: 'Inter', sans-serif; padding: 2rem; }
    .box { background: rgba(255,255,255,0.05); padding: 2rem; border-radius: 12px; max-width: 600px; margin: 0 auto; border: 1px solid rgba(255,255,255,0.1); }
    h2 { color: #fcb900; margin-bottom: 0.5rem; }
    .job-meta { color: #aaa; margin-bottom: 1.5rem; font-size: 0.9rem; }
    .job-desc { background: rgba(0,0,0,0.3); padding: 1rem; border-radius: 8px; margin-bottom: 2rem; line-height: 1.5; }
    
    input, textarea { width: 100%; padding: 0.8rem; margin-bottom: 1rem; background: rgba(0,0,0,0.5); border: 1px solid rgba(255,255,255,0.1); color: #fff; border-radius: 6px; box-sizing: border-box; }
    button { background: #fcb900; color: #000; border: none; padding: 1rem; width: 100%; font-weight: bold; border-radius: 6px; cursor: pointer; }
    .alert { padding: 1rem; margin-bottom: 1rem; border-radius: 6px; }
    .alert-err { background: rgba(255,0,0,0.2); border: 1px solid red; color: #ff5252; }
    .alert-msg { background: rgba(0,255,0,0.2); border: 1px solid #00e676; color: #00e676; }
  </style>
</head>
<body>
  <div class="box">
    <a href="../marketplace_board.php" style="color: #fcb900; text-decoration: none; display: block; margin-bottom: 1rem;">&larr; Back to Job Board</a>
    <h2><?= htmlspecialchars($job['title']) ?></h2>
    <div class="job-meta">
        Requested by: <strong><?= htmlspecialchars($job['user_name']) ?></strong> | 
        User Budget: <strong style="color:#00e676;">🪙 <?= number_format($job['budget'], 2) ?></strong>
    </div>
    
    <div class="job-desc">
        <?= nl2br(htmlspecialchars($job['description'])) ?>
    </div>

    <?php if ($err): ?><div class="alert alert-err"><?= htmlspecialchars($err) ?></div><?php endif; ?>
    <?php if ($msg): ?><div class="alert alert-msg"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

    <?php if (!$msg): ?>
    <form method="post">
      <label style="display:block; margin-bottom:0.5rem; color:#aaa; font-weight:bold;">Your Proposal / Pitch</label>
      <textarea name="proposal" rows="5" required placeholder="Explain why you are the best partner for this job..."></textarea>
      
      <label style="display:block; margin-bottom:0.5rem; color:#aaa; font-weight:bold;">Your Bid Amount (🪙)</label>
      <input type="number" step="0.01" name="bid_amount" required placeholder="Enter your price" value="<?= htmlspecialchars($job['budget']) ?>">
      
      <button type="submit">Submit Bid</button>
    </form>
    <?php endif; ?>
  </div>
</body>
</html>
