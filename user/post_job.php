<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /user/login.php');
    exit;
}
require_once __DIR__ . '/../config.php';

$user_id = $_SESSION['user_id'];
$err = $msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $budget = floatval($_POST['budget'] ?? 0);

    if (empty($title) || empty($description) || $budget <= 0) {
        $err = "All fields are required, and budget must be greater than 0.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO jobs (user_id, title, description, budget) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$user_id, $title, $description, $budget])) {
            $msg = "Job request posted successfully! Partners will now be able to bid on it.";
        } else {
            $err = "Failed to post job.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Post a Job Request</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/user.css">
</head>
<body>
  <div class="box">
    <a href="dashboard.php" style="color: #fcb900; text-decoration: none; display: block; margin-bottom: 1rem;">&larr; Back to Dashboard</a>
    <h2>Post a Custom Job Request</h2>
    <p style="color:#aaa; font-size:0.9rem; margin-bottom: 1.5rem;">Need a special service? Set a budget and let our verified partners bid for the task.</p>
    
    <?php if ($err): ?><div class="alert alert-err"><?= htmlspecialchars($err) ?></div><?php endif; ?>
    <?php if ($msg): ?><div class="alert alert-msg"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

    <form method="post">
      <label>Title (e.g., NID Correction)</label>
      <input type="text" name="title" required placeholder="What do you need done?">
      
      <label>Description (Details)</label>
      <textarea name="description" rows="5" required placeholder="Provide as much detail as possible..."></textarea>
      
      <label>Budget (🪙)</label>
      <input type="number" step="0.01" name="budget" required placeholder="How much are you willing to pay?">
      
      <button type="submit">Post Request</button>
    </form>
  </div>
</body>
</html>
