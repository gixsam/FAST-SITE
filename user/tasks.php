<?php
// =========================================================================
// user/tasks.php — Gamified Task & Fast Points Rewards Hub for Users
// =========================================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: /user/login.php');
    exit;
}

require_once __DIR__ . '/../config.php';

$userId = (int)$_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: /user/logout.php');
    exit;
}

$msg = $err = '';

// Handle Task Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_task'])) {
    $taskId    = (int)($_POST['task_id'] ?? 0);
    $proofText = trim($_POST['proof_text'] ?? '');
    
    // Verify task exists and is active
    $tStmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ? AND status = 'active'");
    $tStmt->execute([$taskId]);
    $task = $tStmt->fetch();
    
    if (!$task) {
        $err = "Selected task is no longer active.";
    } else {
        // Check if user already submitted and is pending/approved
        $chkStmt = $pdo->prepare("SELECT id, status FROM task_submissions WHERE task_id = ? AND user_id = ? ORDER BY id DESC LIMIT 1");
        $chkStmt->execute([$taskId, $userId]);
        $existing = $chkStmt->fetch();
        
        if ($existing && $existing['status'] === 'pending') {
            $err = "You have already submitted proof for this task. It is currently under review by Admin.";
        } elseif ($existing && $existing['status'] === 'approved' && (int)$task['max_completions'] <= 1) {
            $err = "You have already completed this task and claimed your reward.";
        } else {
            // Handle Proof Screenshot Upload
            $proofScreenshot = null;
            if (isset($_FILES['proof_screenshot']) && $_FILES['proof_screenshot']['error'] === UPLOAD_ERR_OK) {
                $upDir = '../uploads/proofs/';
                if (!is_dir($upDir)) @mkdir($upDir, 0755, true);
                $proofScreenshot = handleSecureUpload($_FILES['proof_screenshot'], $upDir, ['jpg','jpeg','png','webp','gif'], 'proof_' . $userId);
            }
            
            if ($task['proof_type'] === 'screenshot' && !$proofScreenshot) {
                $err = "Please upload a screenshot as proof of completion.";
            } elseif ($task['proof_type'] === 'text' && empty($proofText)) {
                $err = "Please provide text details or link as proof.";
            } elseif ($task['proof_type'] === 'both' && (empty($proofText) || !$proofScreenshot)) {
                $err = "Please provide both text details and a screenshot.";
            } else {
                $ins = $pdo->prepare("INSERT INTO task_submissions (task_id, user_id, proof_text, proof_screenshot, status, submitted_at) VALUES (?, ?, ?, ?, 'pending', CURRENT_TIMESTAMP)");
                $ins->execute([$taskId, $userId, $proofText ?: null, $proofScreenshot]);
                $msg = "Task proof submitted successfully! Fast Site Admin will verify your submission and credit " . number_format($task['reward_points'], 0) . " Fast Points to your wallet.";
            }
        }
    }
}

// Fetch user stats
$earnedPoints = (float)$pdo->query("SELECT COALESCE(SUM(reward_credited), 0) FROM task_submissions WHERE user_id = $userId AND status = 'approved'")->fetchColumn();
$pendingTasks = (int)$pdo->query("SELECT COUNT(*) FROM task_submissions WHERE user_id = $userId AND status = 'pending'")->fetchColumn();
$completedCount = (int)$pdo->query("SELECT COUNT(*) FROM task_submissions WHERE user_id = $userId AND status = 'approved'")->fetchColumn();

// Fetch available tasks
$tasks = $pdo->query("SELECT * FROM tasks WHERE status = 'active' AND (target_role = 'user' OR target_role = 'all') ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch user submissions
$mySubmissions = $pdo->prepare("
    SELECT s.*, t.title, t.category, t.reward_points, t.action_url
    FROM task_submissions s
    JOIN tasks t ON s.task_id = t.id
    WHERE s.user_id = ?
    ORDER BY s.id DESC
");
$mySubmissions->execute([$userId]);
$submissions = $mySubmissions->fetchAll(PDO::FETCH_ASSOC);

// Build map of user's task status
$userTaskStatus = [];
foreach ($submissions as $sub) {
    if (!isset($userTaskStatus[$sub['task_id']])) {
        $userTaskStatus[$sub['task_id']] = $sub['status'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Earn Fast Points & Tasks — Fast Site</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Oswald:wght@500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/user.css">
  <style>
    :root {
      --gold: #fcb900;
      --gold-glow: rgba(252, 185, 0, 0.25);
      --teal: #00e676;
      --teal-glow: rgba(0, 230, 118, 0.25);
      --card-dark: rgba(14, 17, 30, 0.9);
      --border-glass: rgba(255, 255, 255, 0.08);
    }

    body {
      background: radial-gradient(circle at 50% 0%, #171d33 0%, #080911 100%);
      color: #f1f5f9;
      font-family: 'Inter', sans-serif;
      min-height: 100vh;
      margin: 0;
      padding-bottom: 4rem;
    }

    .container {
      max-width: 1100px;
      margin: 0 auto;
      padding: 1.5rem 1rem;
    }

    /* Hero Banner */
    .task-hero {
      background: radial-gradient(circle at 80% 20%, rgba(252, 185, 0, 0.15) 0%, rgba(13, 16, 28, 0.95) 100%);
      border: 1px solid rgba(252, 185, 0, 0.3);
      border-radius: 20px;
      padding: 2rem;
      margin-bottom: 2rem;
      box-shadow: 0 20px 50px rgba(0,0,0,0.6), 0 0 25px rgba(252, 185, 0, 0.1);
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 1.5rem;
    }

    .task-hero-title {
      font-family: 'Oswald', sans-serif;
      font-size: 1.8rem;
      color: #fff;
      text-transform: uppercase;
      letter-spacing: 0.04em;
      margin: 0 0 0.4rem 0;
    }

    .task-hero-sub {
      color: #94a3b8;
      font-size: 0.88rem;
      margin: 0;
      max-width: 550px;
      line-height: 1.5;
    }

    .balance-pill {
      background: rgba(0, 0, 0, 0.5);
      border: 1px solid var(--gold);
      border-radius: 16px;
      padding: 1rem 1.4rem;
      text-align: right;
      box-shadow: 0 0 20px rgba(252, 185, 0, 0.2);
    }

    .balance-val {
      font-family: 'Oswald', sans-serif;
      font-size: 1.7rem;
      color: var(--gold);
      font-weight: 700;
    }

    /* KPI Stats Grid */
    .kpi-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 1rem;
      margin-bottom: 2rem;
    }

    .kpi-card {
      background: var(--card-dark);
      border: 1px solid var(--border-glass);
      border-radius: 16px;
      padding: 1.2rem;
      display: flex;
      align-items: center;
      gap: 1rem;
      backdrop-filter: blur(12px);
    }

    .kpi-icon {
      width: 48px;
      height: 48px;
      border-radius: 12px;
      background: rgba(255, 255, 255, 0.05);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.4rem;
      flex-shrink: 0;
    }

    .kpi-val {
      font-family: 'Oswald', sans-serif;
      font-size: 1.4rem;
      font-weight: 700;
      color: #fff;
      line-height: 1.1;
    }

    .kpi-lbl {
      font-size: 0.76rem;
      color: #94a3b8;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      font-weight: 700;
    }

    /* Tabs */
    .task-tabs {
      display: flex;
      gap: 0.6rem;
      border-bottom: 1px solid var(--border-glass);
      padding-bottom: 1rem;
      margin-bottom: 1.8rem;
    }

    .task-tab-btn {
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid var(--border-glass);
      color: #94a3b8;
      padding: 0.6rem 1.2rem;
      border-radius: 10px;
      font-size: 0.85rem;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.2s ease;
    }

    .task-tab-btn.active {
      background: rgba(252, 185, 0, 0.15);
      border-color: var(--gold);
      color: var(--gold);
      box-shadow: 0 0 15px rgba(252, 185, 0, 0.2);
    }

    /* Task Cards Grid */
    .tasks-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
      gap: 1.2rem;
    }

    .task-card {
      background: var(--card-dark);
      border: 1px solid var(--border-glass);
      border-radius: 18px;
      padding: 1.5rem;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      backdrop-filter: blur(16px);
      transition: all 0.25s ease;
      position: relative;
      overflow: hidden;
    }

    .task-card:hover {
      transform: translateY(-3px);
      border-color: rgba(252, 185, 0, 0.4);
      box-shadow: 0 15px 35px rgba(0,0,0,0.6), 0 0 20px rgba(252, 185, 0, 0.15);
    }

    .task-card-top {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 0.8rem;
      gap: 0.8rem;
    }

    .task-cat-badge {
      background: rgba(255, 255, 255, 0.06);
      color: #cbd5e1;
      padding: 0.25rem 0.6rem;
      border-radius: 6px;
      font-size: 0.72rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.04em;
    }

    .task-reward-badge {
      background: linear-gradient(135deg, rgba(252, 185, 0, 0.2) 0%, rgba(255, 145, 0, 0.2) 100%);
      border: 1px solid var(--gold);
      color: var(--gold);
      padding: 0.3rem 0.75rem;
      border-radius: 50px;
      font-family: 'Oswald', sans-serif;
      font-size: 0.95rem;
      font-weight: 700;
      display: inline-flex;
      align-items: center;
      gap: 4px;
      white-space: nowrap;
    }

    .task-title {
      font-family: 'Oswald', sans-serif;
      font-size: 1.15rem;
      color: #fff;
      margin: 0 0 0.5rem 0;
      line-height: 1.3;
    }

    .task-desc {
      font-size: 0.82rem;
      color: #94a3b8;
      line-height: 1.5;
      margin: 0 0 1.2rem 0;
    }

    .task-actions {
      display: flex;
      gap: 0.6rem;
      align-items: center;
      margin-top: auto;
    }

    .btn-action {
      background: rgba(255, 255, 255, 0.08);
      border: 1px solid rgba(255, 255, 255, 0.15);
      color: #fff;
      font-size: 0.8rem;
      font-weight: 700;
      padding: 0.6rem 1rem;
      border-radius: 10px;
      text-decoration: none;
      text-align: center;
      transition: all 0.2s;
      flex: 1;
    }

    .btn-action:hover {
      background: rgba(255, 255, 255, 0.15);
    }

    .btn-submit-proof {
      background: linear-gradient(135deg, var(--gold) 0%, #ff9100 100%);
      color: #000;
      font-size: 0.8rem;
      font-weight: 800;
      padding: 0.6rem 1.1rem;
      border-radius: 10px;
      border: none;
      cursor: pointer;
      text-align: center;
      transition: all 0.2s;
      flex: 1.2;
    }

    .btn-submit-proof:hover {
      transform: translateY(-1px);
      box-shadow: 0 4px 15px rgba(252, 185, 0, 0.4);
    }

    /* Modal */
    .proof-modal {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.85);
      backdrop-filter: blur(14px);
      z-index: 9999;
      align-items: center;
      justify-content: center;
      padding: 1rem;
    }

    .proof-modal-card {
      background: radial-gradient(circle at 50% 0%, #1c2237 0%, #0d101d 100%);
      border: 1px solid rgba(252, 185, 0, 0.35);
      border-radius: 20px;
      padding: 2rem;
      max-width: 520px;
      width: 100%;
      box-shadow: 0 25px 60px rgba(0,0,0,0.8);
      position: relative;
    }

    .close-modal-btn {
      position: absolute;
      top: 1rem;
      right: 1rem;
      background: rgba(255, 255, 255, 0.08);
      border: 1px solid rgba(255, 255, 255, 0.15);
      color: #fff;
      border-radius: 50%;
      width: 32px;
      height: 32px;
      cursor: pointer;
    }

    /* ============================================
       MOBILE APK RESPONSIVE FIXES — tasks.php
       ============================================ */
    @media (max-width: 768px) {
      .task-hero {
        flex-direction: column !important;
        gap: 1rem !important;
        padding: 1.2rem !important;
      }
      .task-hero-title {
        font-size: 1.4rem !important;
      }
      .task-hero-sub {
        font-size: 0.82rem !important;
      }
      .balance-pill {
        width: 100% !important;
        text-align: center !important;
      }
      /* Fix stat boxes — prevent word breaks like "COMPLE TED" */
      .stats-grid,
      [style*="grid-template-columns"] {
        grid-template-columns: 1fr 1fr !important;
      }
      /* Stat label text should stay on one line */
      .kpi-label, .stat-label,
      [style*="font-size:0.7"] {
        font-size: 0.7rem !important;
        word-break: keep-all !important;
        overflow-wrap: normal !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
      }
      /* Tab buttons full width */
      .tabs-bar, [style*="tab-btn"] {
        flex-wrap: wrap !important;
      }
    }
    @media (max-width: 400px) {
      .task-hero-title {
        font-size: 1.15rem !important;
      }
      .container {
        padding: 0.75rem !important;
      }
    }
  </style>
</head>
<body>

<?php include 'nav.php'; ?>

<div class="container">

  <!-- Mobile APK Back Button Bar -->
  <div style="display:flex; align-items:center; gap:0.75rem; margin-bottom:1rem;">
    <button onclick="history.back()" style="display:inline-flex; align-items:center; gap:6px; background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.12); color:#e8e8f0; padding:0.55rem 1rem; border-radius:10px; font-size:0.82rem; font-weight:700; cursor:pointer; text-decoration:none; transition:all 0.2s;">
      ← Back
    </button>
    <a href="/user/dashboard.php" style="display:inline-flex; align-items:center; gap:6px; background:rgba(252,185,0,0.08); border:1px solid rgba(252,185,0,0.2); color:var(--gold); padding:0.55rem 1rem; border-radius:10px; font-size:0.82rem; font-weight:700; cursor:pointer; text-decoration:none;">
      🏠 Dashboard
    </a>
  </div>

  <!-- Hero Header -->
  <div class="task-hero">
    <div>
      <h1 class="task-hero-title">🎯 Tasks &amp; Rewards Studio</h1>
      <p class="task-hero-sub">Complete simple community tasks, social shares, and profile milestones to earn instant Fast Points credited directly to your wallet!</p>
    </div>
    <div class="balance-pill">
      <div style="font-size:0.75rem; color:#94a3b8; text-transform:uppercase; font-weight:700;">Fast Points Balance</div>
      <div class="balance-val">⚡ <?= number_format($user['fast_points'] ?? 0, 0) ?> FP</div>
    </div>
  </div>

  <!-- KPI Grid -->
  <div class="kpi-grid">
    <div class="kpi-card">
      <div class="kpi-icon" style="color:var(--gold);">⚡</div>
      <div>
        <div class="kpi-val"><?= number_format($earnedPoints, 0) ?> FP</div>
        <div class="kpi-lbl">Total Points Earned</div>
      </div>
    </div>
    <div class="kpi-card">
      <div class="kpi-icon" style="color:#00e676;">✅</div>
      <div>
        <div class="kpi-val"><?= $completedCount ?></div>
        <div class="kpi-lbl">Completed Tasks</div>
      </div>
    </div>
    <div class="kpi-card">
      <div class="kpi-icon" style="color:#ffb74d;">⏳</div>
      <div>
        <div class="kpi-val"><?= $pendingTasks ?></div>
        <div class="kpi-lbl">Pending Approvals</div>
      </div>
    </div>
    <div class="kpi-card">
      <div class="kpi-icon" style="color:#60a5fa;">📋</div>
      <div>
        <div class="kpi-val"><?= count($tasks) ?></div>
        <div class="kpi-lbl">Available Tasks</div>
      </div>
    </div>
  </div>

  <?php if($msg): ?>
    <div style="background:rgba(0,230,118,0.12); border:1px solid rgba(0,230,118,0.3); color:#00e676; padding:1rem 1.2rem; border-radius:14px; font-weight:700; margin-bottom:1.5rem;">
      ✅ <?= htmlspecialchars($msg) ?>
    </div>
  <?php endif; ?>

  <?php if($err): ?>
    <div style="background:rgba(255,82,82,0.12); border:1px solid rgba(255,82,82,0.3); color:#ff5252; padding:1rem 1.2rem; border-radius:14px; font-weight:700; margin-bottom:1.5rem;">
      ⚠️ <?= htmlspecialchars($err) ?>
    </div>
  <?php endif; ?>

  <!-- Tabs Navigation -->
  <div class="task-tabs">
    <button class="task-tab-btn active" id="tab-btn-tasks" onclick="switchTaskTab('available-tasks')">🔥 Available Tasks (<?= count($tasks) ?>)</button>
    <button class="task-tab-btn" id="tab-btn-history" onclick="switchTaskTab('my-submissions')">📋 My Submissions (<?= count($submissions) ?>)</button>
  </div>

  <!-- TAB 1: Available Tasks -->
  <div id="tab-available-tasks" class="tab-pane">
    <div class="tasks-grid">
      <?php foreach ($tasks as $task): 
          $status = $userTaskStatus[$task['id']] ?? null;
      ?>
        <div class="task-card">
          <div>
            <div class="task-card-top">
              <span class="task-cat-badge"><?= htmlspecialchars($task['category']) ?></span>
              <span class="task-reward-badge">⚡ +<?= number_format($task['reward_points'], 0) ?> FP</span>
            </div>
            <h3 class="task-title"><?= htmlspecialchars($task['title']) ?></h3>
            <p class="task-desc"><?= nl2br(htmlspecialchars($task['description'])) ?></p>
          </div>

          <div class="task-actions">
            <?php if (!empty($task['action_url'])): ?>
              <a href="<?= htmlspecialchars($task['action_url']) ?>" target="_blank" class="btn-action">🔗 Go to Task ↗</a>
            <?php endif; ?>

            <?php if ($status === 'approved'): ?>
              <button class="btn-action" style="background:rgba(0,230,118,0.15); border-color:#00e676; color:#00e676; cursor:default;">✅ Completed</button>
            <?php elseif ($status === 'pending'): ?>
              <button class="btn-action" style="background:rgba(255,183,77,0.15); border-color:#ffb74d; color:#ffb74d; cursor:default;">⏳ Under Review</button>
            <?php else: ?>
              <button type="button" class="btn-submit-proof" onclick="openProofModal(<?= htmlspecialchars(json_encode($task)) ?>)">
                📤 Submit Proof
              </button>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- TAB 2: My Submissions History -->
  <div id="tab-my-submissions" class="tab-pane" style="display:none;">
    <?php if (empty($submissions)): ?>
      <div style="background:var(--card-dark); border:1px solid var(--border-glass); border-radius:18px; padding:3rem 1.5rem; text-align:center;">
        <div style="font-size:2.5rem; margin-bottom:0.8rem;">🎯</div>
        <h3 style="color:#fff; margin:0 0 0.4rem 0;">No Submissions Yet</h3>
        <p style="color:#94a3b8; font-size:0.85rem; margin:0;">Complete any of the available tasks above to earn Fast Points rewards.</p>
      </div>
    <?php else: ?>
      <div style="display:flex; flex-direction:column; gap:1rem;">
        <?php foreach ($submissions as $sub): ?>
          <div style="background:var(--card-dark); border:1px solid var(--border-glass); border-radius:16px; padding:1.2rem; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
            <div>
              <div style="display:flex; align-items:center; gap:8px; margin-bottom:0.3rem;">
                <span class="task-cat-badge"><?= htmlspecialchars($sub['category']) ?></span>
                <strong style="color:#fff; font-size:1rem;"><?= htmlspecialchars($sub['title']) ?></strong>
              </div>
              <div style="font-size:0.78rem; color:#94a3b8;">Submitted on: <?= date('d M Y, h:i A', strtotime($sub['submitted_at'])) ?></div>
              <?php if (!empty($sub['proof_text'])): ?>
                <div style="font-size:0.8rem; color:#cbd5e1; margin-top:0.4rem; background:rgba(0,0,0,0.3); padding:0.4rem 0.6rem; border-radius:6px;">📝 Proof: <?= htmlspecialchars($sub['proof_text']) ?></div>
              <?php endif; ?>
              <?php if (!empty($sub['admin_notes'])): ?>
                <div style="font-size:0.78rem; color:#ffb74d; margin-top:0.3rem;">💬 Admin Note: <?= htmlspecialchars($sub['admin_notes']) ?></div>
              <?php endif; ?>
            </div>

            <div style="text-align:right;">
              <div style="font-family:'Oswald',sans-serif; font-size:1.1rem; color:var(--gold); font-weight:700; margin-bottom:0.3rem;">+<?= number_format($sub['reward_points'], 0) ?> FP</div>
              <?php if ($sub['status'] === 'approved'): ?>
                <span style="background:rgba(0,230,118,0.15); border:1px solid #00e676; color:#00e676; font-size:0.75rem; font-weight:800; padding:0.25rem 0.65rem; border-radius:50px;">✅ Approved &amp; Credited</span>
              <?php elseif ($sub['status'] === 'rejected'): ?>
                <span style="background:rgba(255,82,82,0.15); border:1px solid #ff5252; color:#ff5252; font-size:0.75rem; font-weight:800; padding:0.25rem 0.65rem; border-radius:50px;">❌ Rejected</span>
              <?php else: ?>
                <span style="background:rgba(255,183,77,0.15); border:1px solid #ffb74d; color:#ffb74d; font-size:0.75rem; font-weight:800; padding:0.25rem 0.65rem; border-radius:50px;">⏳ Pending Review</span>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

</div>

<!-- Proof Submission Modal -->
<div id="proof-modal" class="proof-modal">
  <div class="proof-modal-card">
    <button type="button" class="close-modal-btn" onclick="closeProofModal()">✕</button>
    
    <div style="display:flex; align-items:center; gap:8px; margin-bottom:0.3rem;">
      <span style="font-size:1.3rem;">📤</span>
      <h3 style="color:#fff; font-size:1.3rem; margin:0; font-family:'Oswald',sans-serif;">SUBMIT TASK PROOF</h3>
    </div>
    <p style="font-size:0.8rem; color:#94a3b8; margin:0 0 1.2rem 0;" id="modal-task-title">Submit your completion details to claim Fast Points reward.</p>

    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="task_id" id="modal-task-id" value=""/>
      
      <div style="display:flex; flex-direction:column; gap:1rem;">
        <div id="modal-text-field">
          <label style="font-size:0.75rem; font-weight:700; color:#cbd5e1; text-transform:uppercase; display:block; margin-bottom:0.4rem;">Proof Details / Link / Info</label>
          <textarea name="proof_text" rows="3" placeholder="Provide username, post link, or notes regarding completion..." style="width:100%; background:#080911; border:1px solid rgba(255,255,255,0.1); color:#fff; padding:0.7rem; border-radius:10px; font-size:0.85rem; box-sizing:border-box;"></textarea>
        </div>

        <div id="modal-file-field">
          <label style="font-size:0.75rem; font-weight:700; color:#cbd5e1; text-transform:uppercase; display:block; margin-bottom:0.4rem;">Upload Proof Screenshot (JPG / PNG / WebP)</label>
          <input type="file" name="proof_screenshot" accept="image/*" style="width:100%; background:#080911; border:1px solid rgba(255,255,255,0.1); color:#fff; padding:0.6rem; border-radius:10px; font-size:0.8rem; box-sizing:border-box;"/>
        </div>

        <button type="submit" name="submit_task" class="btn-submit-proof" style="padding:0.85rem; font-size:0.95rem; font-weight:900; border-radius:50px; text-transform:uppercase; letter-spacing:0.04em;">
          🚀 Submit Proof to Admin
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function switchTaskTab(tab) {
  document.getElementById('tab-available-tasks').style.display = (tab === 'available-tasks') ? 'block' : 'none';
  document.getElementById('tab-my-submissions').style.display = (tab === 'my-submissions') ? 'block' : 'none';
  
  document.getElementById('tab-btn-tasks').classList.toggle('active', tab === 'available-tasks');
  document.getElementById('tab-btn-history').classList.toggle('active', tab === 'my-submissions');
}

function openProofModal(task) {
  document.getElementById('modal-task-id').value = task.id;
  document.getElementById('modal-task-title').textContent = task.title + ' (⚡ +' + parseInt(task.reward_points) + ' FP Reward)';
  document.getElementById('proof-modal').style.display = 'flex';
}

function closeProofModal() {
  document.getElementById('proof-modal').style.display = 'none';
}
</script>

</body>
</html>
