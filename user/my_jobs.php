<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /user/login.php');
    exit;
}
require_once __DIR__ . '/../config.php';

$user_id = $_SESSION['user_id'];
$err = $msg = '';

// Handle Accepting a Bid (Escrow)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'accept_bid') {
    $bid_id = intval($_POST['bid_id'] ?? 0);
    
    // Fetch bid and job
    $stmt = $pdo->prepare("SELECT b.*, j.budget, j.status as job_status FROM job_bids b JOIN jobs j ON b.job_id = j.id WHERE b.id = ? AND j.user_id = ? LIMIT 1");
    $stmt->execute([$bid_id, $user_id]);
    $bid = $stmt->fetch();
    
    if ($bid && $bid['job_status'] === 'open') {
        // Check user balance
        try {
            $usr_stmt = $pdo->prepare("SELECT coins_balance FROM users WHERE id = ?");
            $usr_stmt->execute([$user_id]);
            $balance = (float)$usr_stmt->fetchColumn();
        } catch (Exception $e) {
            $balance = 0;
        }
        
        $cost = (float)$bid['bid_amount'];
        
        if ($balance >= $cost) {
            $pdo->beginTransaction();
            try {
                // 1. Deduct escrow from user
                $pdo->prepare("UPDATE users SET coins_balance = coins_balance - ? WHERE id = ?")->execute([$cost, $user_id]);
                
                // 2. Log transaction
                $pdo->prepare("INSERT INTO coin_transactions (user_id, type, amount, reference, status) VALUES (?, 'payment', ?, ?, 'completed')")
                    ->execute([$user_id, $cost, 'Escrow for Job #' . $bid['job_id']]);
                
                // 3. Mark Job as awarded
                $pdo->prepare("UPDATE jobs SET status = 'awarded', awarded_to = ? WHERE id = ?")->execute([$bid['partner_id'], $bid['job_id']]);
                
                // 4. Mark Bid as accepted, others rejected
                $pdo->prepare("UPDATE job_bids SET status = 'rejected' WHERE job_id = ?")->execute([$bid['job_id']]);
                $pdo->prepare("UPDATE job_bids SET status = 'accepted' WHERE id = ?")->execute([$bid_id]);
                
                // 5. Create a standard partner_order so the flow works normally
                // (We need a dummy product_id, or handle it cleanly. For now, let's create a placeholder order)
                // Assuming product_id=0 for custom jobs
                $pdo->prepare("INSERT INTO partner_orders (partner_id, customer_id, product_id, total_price, total_coins, payment_method, payment_status, status) VALUES (?, ?, 0, ?, ?, 'coins', 'completed', 'pending')")
                    ->execute([$bid['partner_id'], $user_id, $cost, $cost]);
                
                $pdo->commit();
                $msg = "Bid accepted successfully! Escrow deducted. The partner will begin working.";
            } catch (Exception $e) {
                $pdo->rollBack();
                $err = "Transaction failed: " . $e->getMessage();
            }
        } else {
            $err = "Insufficient Coins balance. You need 🪙 " . number_format($cost, 2) . " but you have 🪙 " . number_format($balance, 2) . ". <a href='deposit.php'>Deposit here.</a>";
        }
    } else {
        $err = "Invalid bid or job is already closed.";
    }
}

// Fetch all jobs by this user
$stmt_jobs = $pdo->prepare("SELECT * FROM jobs WHERE user_id = ? ORDER BY created_at DESC");
$stmt_jobs->execute([$user_id]);
$jobs = $stmt_jobs->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Job Requests</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/user.css">
</head>
<body>
  <div class="container">
    <a href="dashboard.php" style="color: #fcb900; text-decoration: none; display: block; margin-bottom: 1rem;">&larr; Back to Dashboard</a>
    <h2>My Job Requests</h2>
    
    <?php if ($err): ?><div class="alert alert-err"><?= $err ?></div><?php endif; ?>
    <?php if ($msg): ?><div class="alert alert-msg"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

    <?php if (empty($jobs)): ?>
        <p style="color:#aaa;">You haven't posted any job requests yet.</p>
        <a href="post_job.php" style="color:#fcb900;">Post one now!</a>
    <?php else: ?>
        <?php foreach ($jobs as $job): ?>
            <div class="job-card">
                <div class="job-title"><?= htmlspecialchars($job['title']) ?></div>
                <div style="margin-bottom: 1rem;">
                    <span class="status-badge <?= $job['status'] === 'open' ? 'status-open' : 'status-awarded' ?>"><?= htmlspecialchars($job['status']) ?></span>
                    <span style="color:#aaa; font-size:0.9rem; margin-left: 10px;">Budget: 🪙 <?= number_format($job['budget'], 2) ?></span>
                </div>
                
                <?php
                // Fetch bids for this job
                $b_stmt = $pdo->prepare("SELECT b.*, p.business_name, p.seller_level, p.rating FROM job_bids b JOIN partners p ON b.partner_id = p.id WHERE b.job_id = ? ORDER BY b.created_at DESC");
                $b_stmt->execute([$job['id']]);
                $bids = $b_stmt->fetchAll();
                ?>
                
                <?php if (empty($bids)): ?>
                    <p style="color:#aaa; font-size:0.85rem; font-style:italic;">Waiting for partners to bid...</p>
                <?php else: ?>
                    <h4 style="margin-top:1.5rem; margin-bottom:0.5rem; border-bottom:1px solid rgba(255,255,255,0.1); padding-bottom:0.3rem;">Partner Bids</h4>
                    <?php foreach ($bids as $bid): ?>
                        <div class="bid-card">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.5rem;">
                                <strong style="color:#fff;"><?= htmlspecialchars($bid['business_name']) ?> (⭐ <?= number_format($bid['rating'] ?: 5.0, 1) ?>)</strong>
                                <strong style="color:#00e676;">🪙 <?= number_format($bid['bid_amount'], 2) ?></strong>
                            </div>
                            <p style="color:#ccc; font-size:0.9rem; margin-bottom:1rem;"><?= nl2br(htmlspecialchars($bid['proposal'])) ?></p>
                            
                            <?php if ($job['status'] === 'open'): ?>
                            <form method="post" style="text-align:right;">
                                <input type="hidden" name="action" value="accept_bid">
                                <input type="hidden" name="bid_id" value="<?= $bid['id'] ?>">
                                <button type="submit" class="accept-btn" onclick="return confirm('Accept this bid? 🪙 <?= number_format($bid['bid_amount'], 2) ?> will be deducted and held in Escrow.')">Accept Bid (Escrow)</button>
                            </form>
                            <?php elseif ($bid['status'] === 'accepted'): ?>
                                <div style="text-align:right; color:#00e676; font-weight:bold;">✓ Accepted</div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
  </div>
</body>
</html>
