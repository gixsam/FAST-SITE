<?php
// =========================================================================
// admin/tasks.php — Admin Task Creation, Delegation & Proof Verification Hub
// =========================================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config.php';

$isAdmin = ($_SESSION['admin_role'] ?? 'staff') === 'admin';
$msg = $err = '';

// ── 1. HANDLE TASK CREATION ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_task'])) {
    $targetRole  = trim($_POST['target_role'] ?? 'user');
    $title       = trim($_POST['title'] ?? '');
    $desc        = trim($_POST['description'] ?? '');
    $category    = trim($_POST['category'] ?? 'General');
    $reward      = floatval($_POST['reward_points'] ?? 50.0);
    $actionUrl   = trim($_POST['action_url'] ?? '');
    $proofType   = trim($_POST['proof_type'] ?? 'screenshot');
    $maxComp     = max(1, (int)($_POST['max_completions'] ?? 1));
    $deadline    = !empty($_POST['deadline']) ? $_POST['deadline'] : null;

    if (empty($title)) {
        $err = "Please enter a task title.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO tasks 
                (target_role, title, description, category, reward_points, action_url, proof_type, max_completions, deadline, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
            $stmt->execute([$targetRole, $title, $desc ?: null, $category, $reward, $actionUrl ?: null, $proofType, $maxComp, $deadline]);
            $msg = "Task '{$title}' created successfully and published live to the User & Agent Portal!";
        } catch (Exception $e) {
            $err = "Database Error: " . $e->getMessage();
        }
    }
}

// ── 1.5 HANDLE TASK EDIT ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_task'])) {
    $task_id = intval($_POST['task_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $reward = floatval($_POST['reward_amount'] ?? 0);
    $desc = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $deadline = trim($_POST['deadline'] ?? '');

    $stmt = $pdo->prepare("UPDATE tasks SET title = ?, reward_points = ?, description = ?, category = ?, deadline = ? WHERE id = ?");
    $stmt->execute([$title, $reward, $desc, $category, $deadline ?: null, $task_id]);
    header('Location: tasks.php?tab=active_tasks&msg=' . urlencode('Task updated successfully!'));
    exit;
}

// ── 2. HANDLE PROOF VERIFICATION (APPROVE / REJECT) ──────────────────────
if (isset($_POST['review_action'])) {
    $subId  = (int)($_POST['submission_id'] ?? 0);
    $action = $_POST['review_action']; // 'approve' or 'reject'
    $adminNotes = trim($_POST['admin_notes'] ?? '');

    if ($subId > 0 && in_array($action, ['approve', 'reject'])) {
        try {
            $pdo->beginTransaction();

            $sStmt = $pdo->prepare("
                SELECT s.*, t.title AS task_title, t.reward_points, t.target_role, u.name AS user_name, u.phone AS user_phone, u.fast_points
                FROM task_submissions s
                JOIN tasks t ON s.task_id = t.id
                JOIN users u ON s.user_id = u.id
                WHERE s.id = ?
            ");
            $sStmt->execute([$subId]);
            $sub = $sStmt->fetch(PDO::FETCH_ASSOC);

            if ($sub && $sub['status'] === 'pending') {
                $uid = (int)$sub['user_id'];
                $reward = (float)$sub['reward_points'];

                if ($action === 'approve') {
                    // 1. Mark Submission as Approved
                    $pdo->prepare("UPDATE task_submissions SET status = 'approved', admin_notes = ?, reward_credited = ?, reviewed_at = CURRENT_TIMESTAMP WHERE id = ?")
                        ->execute([$adminNotes ?: 'Approved by Fast Site Admin', $reward, $subId]);

                    // 2. Credit Fast Points to User
                    $pdo->prepare("UPDATE users SET fast_points = fast_points + ? WHERE id = ?")
                        ->execute([$reward, $uid]);

                    // 3. Log Coin / Point Transaction
                    $pdo->prepare("INSERT INTO coin_transactions (user_id, amount, type, description) VALUES (?, ?, 'task_reward', ?)")
                        ->execute([$uid, $reward, "Task Reward: " . $sub['task_title']]);

                    // 4. Send In-App Notification
                    $pdo->prepare("INSERT INTO user_notifications (user_id, title, message, type, is_read, created_at) VALUES (?, ?, ?, 'reward', 0, CURRENT_TIMESTAMP)")
                        ->execute([$uid, "🎯 Task Reward Credited!", "Your proof for '{$sub['task_title']}' was approved. +{$reward} Fast Points have been added to your wallet!"]);
                } else {
                    // Reject
                    $pdo->prepare("UPDATE task_submissions SET status = 'rejected', admin_notes = ?, reviewed_at = CURRENT_TIMESTAMP WHERE id = ?")
                        ->execute([$adminNotes ?: 'Declined. Please review requirements and re-submit.', $subId]);

                    // Notify User of Rejection
                    $pdo->prepare("INSERT INTO user_notifications (user_id, title, message, type, is_read, created_at) VALUES (?, ?, ?, 'system', 0, CURRENT_TIMESTAMP)")
                        ->execute([$uid, "❌ Task Submission Declined", "Your submission for '{$sub['task_title']}' was not approved. Note: " . ($adminNotes ?: 'Proof did not match requirements.')]);
                }

                $pdo->commit();
                header('Location: tasks.php?tab=proofs&msg=' . urlencode('Submission ' . $action . 'd successfully!'));
                exit;
            } else {
                $pdo->rollBack();
                header('Location: tasks.php?tab=proofs&err=' . urlencode('Submission not found or already reviewed.'));
                exit;
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            header('Location: tasks.php?tab=proofs&err=' . urlencode('Error processing review: ' . $e->getMessage()));
            exit;
        }
    }
}

// ── 3. HANDLE TASK TOGGLE STATUS & DELETE ────────────────────────────────
if (isset($_GET['toggle_status'])) {
    $tid = (int)$_GET['toggle_status'];
    $cur = $pdo->query("SELECT status FROM tasks WHERE id = $tid")->fetchColumn();
    $newStatus = ($cur === 'active') ? 'paused' : 'active';
    $pdo->prepare("UPDATE tasks SET status = ? WHERE id = ?")->execute([$newStatus, $tid]);
    header('Location: tasks.php?msg=' . urlencode("Task status updated to {$newStatus}"));
    exit;
}

if (isset($_GET['delete_task']) && $isAdmin) {
    $tid = (int)$_GET['delete_task'];
    $pdo->prepare("DELETE FROM tasks WHERE id = ?")->execute([$tid]);
    $pdo->prepare("DELETE FROM task_submissions WHERE task_id = ?")->execute([$tid]);
    header('Location: tasks.php?msg=' . urlencode("Task deleted successfully"));
    exit;
}

if (isset($_GET['msg'])) $msg = $_GET['msg'];

// ── 4. FETCH DATA ────────────────────────────────────────────────────────
$pendingSubmissions = $pdo->query("
    SELECT s.*, t.title AS task_title, t.reward_points, t.category, u.name AS user_name, u.phone AS user_phone, u.whatsapp AS user_wa
    FROM task_submissions s
    JOIN tasks t ON s.task_id = t.id
    JOIN users u ON s.user_id = u.id
    WHERE s.status = 'pending'
    ORDER BY s.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

$allTasks = $pdo->query("
    SELECT t.*, 
        (SELECT COUNT(*) FROM task_submissions WHERE task_id = t.id) AS total_submissions,
        (SELECT COUNT(*) FROM task_submissions WHERE task_id = t.id AND status = 'approved') AS total_completed
    FROM tasks t
    ORDER BY t.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

$totalPaidPoints = (float)$pdo->query("SELECT COALESCE(SUM(reward_credited), 0) FROM task_submissions WHERE status = 'approved'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Omni-Task &amp; Rewards Studio &mdash; Fast Site Admin</title>
  <link rel="stylesheet" href="/assets/css/admin.css">
  <link rel="stylesheet" href="/assets/css/admin-nav.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Oswald:wght@500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --gold: #fcb900;
      --gold-glow: rgba(252, 185, 0, 0.25);
      --teal: #00e676;
      --card-bg: rgba(14, 17, 30, 0.92);
      --border: rgba(255, 255, 255, 0.08);
    }

    body {
      background: radial-gradient(circle at 50% 0%, #151828 0%, #080911 100%) !important;
      color: #f1f5f9;
      font-family: 'Inter', sans-serif;
      margin: 0;
      padding-bottom: 4rem;
    }

    .wrap {
      max-width: 1200px;
      margin: 0 auto;
      padding: 1.5rem 1rem;
    }

    .hero-banner {
      background: radial-gradient(circle at 80% 20%, rgba(252, 185, 0, 0.15) 0%, rgba(13, 16, 28, 0.95) 100%);
      border: 1px solid rgba(252, 185, 0, 0.35);
      border-radius: 20px;
      padding: 1.8rem 2rem;
      margin-bottom: 2rem;
      box-shadow: 0 20px 50px rgba(0,0,0,0.6);
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 1.2rem;
    }

    .tabs-bar {
      display: flex;
      gap: 0.6rem;
      border-bottom: 1px solid var(--border);
      padding-bottom: 1rem;
      margin-bottom: 1.8rem;
      flex-wrap: wrap;
    }

    .tab-pill {
      background: rgba(255, 255, 255, 0.04);
      border: 1px solid var(--border);
      color: #94a3b8;
      padding: 0.65rem 1.2rem;
      border-radius: 12px;
      font-size: 0.85rem;
      font-weight: 700;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      transition: all 0.2s;
    }

    .tab-pill.active {
      background: rgba(252, 185, 0, 0.15);
      border-color: var(--gold);
      color: var(--gold);
      box-shadow: 0 0 15px rgba(252, 185, 0, 0.2);
    }

    .badge-count {
      background: #ff5252;
      color: #fff;
      font-size: 0.68rem;
      padding: 2px 7px;
      border-radius: 50px;
      font-weight: 900;
    }

    .card {
      background: var(--card-bg);
      border: 1px solid var(--border);
      border-radius: 18px;
      padding: 1.5rem;
      margin-bottom: 1.5rem;
      backdrop-filter: blur(16px);
    }

    .task-table {
      width: 100%;
      border-collapse: collapse;
      text-align: left;
      font-size: 0.85rem;
    }

    .task-table th {
      padding: 0.85rem 1rem;
      background: rgba(0, 0, 0, 0.3);
      color: #94a3b8;
      text-transform: uppercase;
      font-size: 0.72rem;
      letter-spacing: 0.05em;
      border-bottom: 1px solid var(--border);
    }

    .task-table td {
      padding: 1rem;
      border-bottom: 1px solid rgba(255, 255, 255, 0.04);
      vertical-align: middle;
    }

    .btn-approve {
      background: #00e676;
      color: #000;
      border: none;
      padding: 0.45rem 0.9rem;
      border-radius: 8px;
      font-weight: 800;
      font-size: 0.78rem;
      cursor: pointer;
      transition: 0.2s;
    }

    .btn-reject {
      background: rgba(255, 82, 82, 0.15);
      border: 1px solid #ff5252;
      color: #ff5252;
      padding: 0.45rem 0.9rem;
      border-radius: 8px;
      font-weight: 700;
      font-size: 0.78rem;
      cursor: pointer;
      transition: 0.2s;
    }
  </style>
</head>
<body>

<?php include 'nav.php'; ?>

<div class="wrap">

  <!-- Hero Header -->
  <div class="hero-banner">
    <div>
      <h1 style="font-family:'Oswald',sans-serif; font-size:1.8rem; margin:0 0 0.3rem 0; color:#fff; text-transform:uppercase; letter-spacing:0.04em;">🎯 OMNI-TASK &amp; REWARDS ENGINE</h1>
      <p style="color:#94a3b8; font-size:0.85rem; margin:0; max-width:600px;">Create and assign tasks to Users, Affiliates, and Staff with automated Fast Points wallet disbursements.</p>
    </div>
    <div style="display:flex; gap:1rem; flex-wrap:wrap;">
      <div style="background:rgba(0,0,0,0.4); border:1px solid var(--gold); border-radius:14px; padding:0.8rem 1.2rem; text-align:center;">
        <div style="font-size:0.72rem; color:#94a3b8; text-transform:uppercase; font-weight:700;">Total Distributed</div>
        <div style="font-family:'Oswald',sans-serif; font-size:1.4rem; color:var(--gold); font-weight:700;">⚡ <?= number_format($totalPaidPoints, 0) ?> FP</div>
      </div>
      <div style="background:rgba(0,0,0,0.4); border:1px solid rgba(255,255,255,0.15); border-radius:14px; padding:0.8rem 1.2rem; text-align:center;">
        <div style="font-size:0.72rem; color:#94a3b8; text-transform:uppercase; font-weight:700;">Pending Review</div>
        <div style="font-family:'Oswald',sans-serif; font-size:1.4rem; color:#ff5252; font-weight:700;"><?= count($pendingSubmissions) ?> Submissions</div>
      </div>
    </div>
  </div>

  <?php if($msg): ?>
    <div style="background:rgba(0,230,118,0.12); border:1px solid rgba(0,230,118,0.3); color:#00e676; padding:0.9rem 1.2rem; border-radius:12px; font-weight:700; margin-bottom:1.5rem;">
      ✅ <?= htmlspecialchars($msg) ?>
    </div>
  <?php endif; ?>

  <?php if($err): ?>
    <div style="background:rgba(255,82,82,0.12); border:1px solid rgba(255,82,82,0.3); color:#ff5252; padding:0.9rem 1.2rem; border-radius:12px; font-weight:700; margin-bottom:1.5rem;">
      ⚠️ <?= htmlspecialchars($err) ?>
    </div>
  <?php endif; ?>

  <!-- Tabs Navigation -->
  <div class="tabs-bar">
    <button class="tab-pill active" id="btn-tab-queue" onclick="switchAdminTaskTab('queue')">
      <span>📥 Proof Verification Queue</span>
      <?php if (count($pendingSubmissions) > 0): ?>
        <span class="badge-count"><?= count($pendingSubmissions) ?></span>
      <?php endif; ?>
    </button>
    <button class="tab-pill" id="btn-tab-tasks" onclick="switchAdminTaskTab('tasks')">
      <span>📋 Active Tasks Directory (<?= count($allTasks) ?>)</span>
    </button>
    <button class="tab-pill" id="btn-tab-create" onclick="switchAdminTaskTab('create')">
      <span>➕ Create New Task</span>
    </button>
  </div>

  <!-- TAB 1: Verification Queue -->
  <div id="tab-pane-queue" class="tab-pane">
    <div class="card">
      <h2 style="font-family:'Oswald',sans-serif; color:var(--gold); font-size:1.25rem; margin:0 0 1rem 0;">📥 PENDING PROOF SUBMISSIONS FOR APPROVAL</h2>
      
      <?php if (empty($pendingSubmissions)): ?>
        <div style="text-align:center; padding:3rem 1rem; color:#94a3b8;">
          <div style="font-size:2.5rem; margin-bottom:0.5rem;">✨</div>
          <h3 style="color:#fff; margin:0 0 0.3rem 0;">All Proofs Verified!</h3>
          <p style="margin:0; font-size:0.85rem;">No user task submissions currently waiting for review.</p>
        </div>
      <?php else: ?>
        <div style="overflow-x:auto;">
          <table class="task-table">
            <thead>
              <tr>
                <th>User / Member</th>
                <th>Task Title &amp; Reward</th>
                <th>Proof Details / Screenshot</th>
                <th>Submitted</th>
                <th>Review Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($pendingSubmissions as $sub): ?>
                <tr>
                  <td>
                    <strong style="color:#fff; font-size:0.95rem; display:block;"><?= htmlspecialchars($sub['user_name']) ?></strong>
                    <span style="color:#94a3b8; font-size:0.75rem;">📞 <?= htmlspecialchars($sub['user_phone']) ?></span>
                    <?php if (!empty($sub['user_wa'])): ?>
                      <a href="https://wa.me/<?= preg_replace('/[^0-9]/','',$sub['user_wa']) ?>" target="_blank" style="color:#25d366; text-decoration:none; font-size:0.75rem; display:block;">💬 WhatsApp</a>
                    <?php endif; ?>
                  </td>
                  <td>
                    <strong style="color:#fff; font-size:0.9rem; display:block;"><?= htmlspecialchars($sub['task_title']) ?></strong>
                    <span style="color:var(--gold); font-weight:800; font-family:'Oswald',sans-serif; font-size:1rem;">⚡ +<?= number_format($sub['reward_points'], 0) ?> FP</span>
                  </td>
                  <td>
                    <?php if (!empty($sub['proof_text'])): ?>
                      <div style="background:rgba(0,0,0,0.3); border:1px solid var(--border); padding:0.5rem 0.7rem; border-radius:8px; font-size:0.8rem; color:#cbd5e1; max-width:300px; margin-bottom:0.4rem;">
                        📝 <?= nl2br(htmlspecialchars($sub['proof_text'])) ?>
                      </div>
                    <?php endif; ?>
                    <?php if (!empty($sub['proof_screenshot'])): ?>
                      <a href="/uploads/proofs/<?= htmlspecialchars($sub['proof_screenshot']) ?>" target="_blank" style="display:inline-flex; align-items:center; gap:5px; background:rgba(255,255,255,0.06); padding:0.35rem 0.7rem; border-radius:6px; color:#60a5fa; text-decoration:none; font-size:0.75rem; font-weight:700;">
                        🖼️ View Screenshot ↗
                      </a>
                    <?php endif; ?>
                  </td>
                  <td style="color:#94a3b8; font-size:0.78rem;">
                    <?= date('d M Y, h:i A', strtotime($sub['submitted_at'])) ?>
                  </td>
                  <td>
                    <form method="POST" style="display:flex; flex-direction:column; gap:0.4rem; min-width:200px;">
                      <input type="hidden" name="submission_id" value="<?= $sub['id'] ?>"/>
                      <input type="text" name="admin_notes" placeholder="Feedback notes (optional)..." style="background:#080911; border:1px solid var(--border); color:#fff; padding:0.4rem 0.6rem; border-radius:6px; font-size:0.75rem; width:100%; box-sizing:border-box;"/>
                      <div style="display:flex; gap:0.4rem;">
                        <button type="submit" name="review_action" value="approve" class="btn-approve" style="flex:1;">✅ Approve (+<?= (int)$sub['reward_points'] ?>)</button>
                        <button type="submit" name="review_action" value="reject" class="btn-reject" style="flex:1;">❌ Reject</button>
                      </div>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- TAB 2: Active Tasks Directory -->
  <div id="tab-pane-tasks" class="tab-pane" style="display:none;">
    <div class="card">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.2rem; flex-wrap:wrap; gap:0.8rem;">
        <h2 style="font-family:'Oswald',sans-serif; color:var(--gold); font-size:1.25rem; margin:0;">📋 ACTIVE COMMUNITY &amp; USER TASKS</h2>
        <button type="button" onclick="switchAdminTaskTab('create')" class="btn-approve" style="background:linear-gradient(135deg, var(--gold), #ff9100); color:#000; padding:0.5rem 1rem; border-radius:8px;">
          ➕ Create New Task
        </button>
      </div>

      <div style="overflow-x:auto;">
        <table class="task-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Audience &amp; Category</th>
              <th>Task Title &amp; Details</th>
              <th>Reward</th>
              <th>Stats</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($allTasks as $t): ?>
              <tr>
                <td style="color:var(--muted); font-family:monospace;">#<?= $t['id'] ?></td>
                <td>
                  <span style="background:rgba(255,255,255,0.06); padding:0.25rem 0.5rem; border-radius:6px; font-size:0.7rem; font-weight:700; text-transform:uppercase; display:inline-block; margin-bottom:2px;">
                    <?= htmlspecialchars($t['target_role']) ?>
                  </span>
                  <div style="font-size:0.75rem; color:#94a3b8;"><?= htmlspecialchars($t['category']) ?></div>
                </td>
                <td>
                  <strong style="color:#fff; font-size:0.95rem; display:block;"><?= htmlspecialchars($t['title']) ?></strong>
                  <span style="color:#94a3b8; font-size:0.78rem;"><?= htmlspecialchars(mb_strimwidth($t['description'] ?? '', 0, 80, '...')) ?></span>
                  <?php if (!empty($t['action_url'])): ?>
                    <a href="<?= htmlspecialchars($t['action_url']) ?>" target="_blank" style="color:#60a5fa; font-size:0.75rem; text-decoration:none; display:block; margin-top:2px;">🔗 Link ↗</a>
                  <?php endif; ?>
                </td>
                <td>
                  <span style="color:var(--gold); font-family:'Oswald',sans-serif; font-size:1.1rem; font-weight:700;">⚡ <?= number_format($t['reward_points'], 0) ?> FP</span>
                </td>
                <td>
                  <span style="color:#00e676; font-weight:700; font-size:0.8rem;"><?= $t['total_completed'] ?> Completed</span>
                  <div style="color:#94a3b8; font-size:0.72rem;"><?= $t['total_submissions'] ?> Total Submits</div>
                </td>
                <td>
                  <?php if ($t['status'] === 'active'): ?>
                    <span style="background:rgba(0,230,118,0.15); border:1px solid #00e676; color:#00e676; padding:0.2rem 0.6rem; border-radius:50px; font-size:0.7rem; font-weight:800;">ACTIVE</span>
                  <?php else: ?>
                    <span style="background:rgba(255,183,77,0.15); border:1px solid #ffb74d; color:#ffb74d; padding:0.2rem 0.6rem; border-radius:50px; font-size:0.7rem; font-weight:800;">PAUSED</span>
                  <?php endif; ?>
                </td>
                <td>
                  <div style="display:flex; gap:0.4rem;">
                    <button type="button" class="btn-approve" style="padding:0.35rem 0.6rem; font-size:0.75rem; background:var(--surface); border:1px solid #60a5fa; color:#60a5fa;" onclick='openEditTaskModal(<?= htmlspecialchars(json_encode($t), ENT_QUOTES, "UTF-8") ?>)'>
                      ✏️ Edit
                    </button>
                    <a href="tasks.php?toggle_status=<?= $t['id'] ?>" class="btn-reject" style="border-color:var(--gold); color:var(--gold); text-decoration:none; padding:0.35rem 0.6rem;">
                      <?= ($t['status'] === 'active') ? '⏸️ Pause' : '▶️ Resume' ?>
                    </a>
                    <?php if ($isAdmin): ?>
                      <a href="tasks.php?delete_task=<?= $t['id'] ?>" onclick="return confirm('Are you sure you want to permanently delete this task?');" class="btn-reject" style="text-decoration:none; padding:0.35rem 0.6rem;">
                        🗑️ Delete
                      </a>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- TAB 3: Create New Task -->
  <div id="tab-pane-create" class="tab-pane" style="display:none;">
    <div class="card" style="max-width:700px; margin:0 auto;">
      <h2 style="font-family:'Oswald',sans-serif; color:var(--gold); font-size:1.3rem; margin:0 0 0.4rem 0;">➕ CREATE NEW SYSTEM TASK</h2>
      <p style="color:#94a3b8; font-size:0.82rem; margin:0 0 1.5rem 0;">Publish high-engagement reward tasks to grow social followers, referrals, reviews, or app installs.</p>

      <form method="POST" action="tasks.php">
        <div style="display:flex; flex-direction:column; gap:1.1rem;">
          
          <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
            <div>
              <label style="font-size:0.75rem; color:#cbd5e1; font-weight:700; text-transform:uppercase; display:block; margin-bottom:0.4rem;">Target Audience *</label>
              <select name="target_role" style="width:100%; background:#080911; border:1px solid var(--border); color:#fff; padding:0.75rem; border-radius:10px; font-size:0.85rem;">
                <option value="user">👥 Regular Users &amp; Customers</option>
                <option value="agent">🤝 Affiliate Agents &amp; Marketers</option>
                <option value="all">🌐 All Platform Members</option>
              </select>
            </div>
            <div>
              <label style="font-size:0.75rem; color:#cbd5e1; font-weight:700; text-transform:uppercase; display:block; margin-bottom:0.4rem;">Task Category *</label>
              <select name="category" style="width:100%; background:#080911; border:1px solid var(--border); color:#fff; padding:0.75rem; border-radius:10px; font-size:0.85rem;">
                <option value="Social Media">Social Media Follow &amp; Share</option>
                <option value="Referral">Referral &amp; Community Growth</option>
                <option value="Profile & KYC">Profile &amp; KYC Verification</option>
                <option value="App & Gaming">App Install &amp; Gaming</option>
                <option value="Reviews & Ratings">Reviews &amp; Ratings</option>
                <option value="General">General / Custom Mission</option>
              </select>
            </div>
          </div>

          <div>
            <label style="font-size:0.75rem; color:#cbd5e1; font-weight:700; text-transform:uppercase; display:block; margin-bottom:0.4rem;">Task Title *</label>
            <input type="text" name="title" required placeholder="e.g. Subscribe to Fast Site YouTube & Like 2 Videos" style="width:100%; background:#080911; border:1px solid var(--border); color:#fff; padding:0.75rem; border-radius:10px; font-size:0.88rem; box-sizing:border-box;"/>
          </div>

          <div>
            <label style="font-size:0.75rem; color:#cbd5e1; font-weight:700; text-transform:uppercase; display:block; margin-bottom:0.4rem;">Task Instructions &amp; Description</label>
            <textarea name="description" rows="3" placeholder="Explain the exact steps the user must take to qualify for approval..." style="width:100%; background:#080911; border:1px solid var(--border); color:#fff; padding:0.75rem; border-radius:10px; font-size:0.85rem; box-sizing:border-box;"></textarea>
          </div>

          <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
            <div>
              <label style="font-size:0.75rem; color:#cbd5e1; font-weight:700; text-transform:uppercase; display:block; margin-bottom:0.4rem;">Fast Points Reward (FP) *</label>
              <input type="number" step="1" min="1" name="reward_points" value="100" required style="width:100%; background:#080911; border:1px solid var(--border); color:var(--gold); font-family:'Oswald',sans-serif; font-size:1.1rem; font-weight:700; padding:0.65rem; border-radius:10px; box-sizing:border-box;"/>
            </div>
            <div>
              <label style="font-size:0.75rem; color:#cbd5e1; font-weight:700; text-transform:uppercase; display:block; margin-bottom:0.4rem;">Proof Requirement Type *</label>
              <select name="proof_type" style="width:100%; background:#080911; border:1px solid var(--border); color:#fff; padding:0.75rem; border-radius:10px; font-size:0.85rem;">
                <option value="screenshot">🖼️ Screenshot Upload Required</option>
                <option value="text">📝 Text / Link / ID Notes Required</option>
                <option value="both">🖼️ + 📝 Both Screenshot and Text</option>
                <option value="none">⚡ No Proof (Self-Service)</option>
              </select>
            </div>
          </div>

          <div style="display:grid; grid-template-columns:1.5fr 1fr; gap:1rem;">
            <div>
              <label style="font-size:0.75rem; color:#cbd5e1; font-weight:700; text-transform:uppercase; display:block; margin-bottom:0.4rem;">Destination / Action URL (Optional)</label>
              <input type="url" name="action_url" placeholder="https://..." style="width:100%; background:#080911; border:1px solid var(--border); color:#fff; padding:0.75rem; border-radius:10px; font-size:0.85rem; box-sizing:border-box;"/>
            </div>
            <div>
              <label style="font-size:0.75rem; color:#cbd5e1; font-weight:700; text-transform:uppercase; display:block; margin-bottom:0.4rem;">Deadline (Optional)</label>
              <input type="date" name="deadline" style="width:100%; background:#080911; border:1px solid var(--border); color:#fff; padding:0.75rem; border-radius:10px; font-size:0.85rem; box-sizing:border-box;"/>
            </div>
          </div>

          <button type="submit" name="create_task" class="btn-approve" style="background:linear-gradient(135deg, var(--gold) 0%, #ff9100 100%); color:#000; font-weight:900; font-size:1rem; padding:0.95rem; border-radius:50px; text-transform:uppercase; letter-spacing:0.04em; margin-top:0.5rem; box-shadow:0 8px 25px rgba(252,185,0,0.35);">
            🚀 Publish Task Live to Platform
          </button>

        </div>
      </form>
    </div>
  </div>

</div>

<!-- EDIT TASK MODAL -->
<div id="editTaskModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:9999; justify-content:center; align-items:center; padding:1rem; box-sizing:border-box;">
  <div style="background:var(--surface); border:1px solid var(--border); border-radius:16px; padding:2rem; width:100%; max-width:500px; box-shadow:0 15px 40px rgba(0,0,0,0.5);">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
      <h2 style="font-family:'Oswald',sans-serif; color:var(--gold); margin:0;">✏️ EDIT TASK</h2>
      <button type="button" onclick="document.getElementById('editTaskModal').style.display='none'" style="background:none; border:none; color:#fff; font-size:1.2rem; cursor:pointer;">✖</button>
    </div>
    <form method="POST" style="display:flex; flex-direction:column; gap:1.2rem;">
      <input type="hidden" name="task_id" id="edit_task_id">
      
      <div>
        <label style="font-size:0.75rem; color:#cbd5e1; font-weight:700; text-transform:uppercase; display:block; margin-bottom:0.4rem;">Task Title</label>
        <input type="text" name="title" id="edit_task_title" required style="width:100%; background:#080911; border:1px solid var(--border); color:#fff; padding:0.75rem; border-radius:10px; font-size:0.9rem; box-sizing:border-box;">
      </div>

      <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
        <div>
          <label style="font-size:0.75rem; color:#cbd5e1; font-weight:700; text-transform:uppercase; display:block; margin-bottom:0.4rem;">Category</label>
          <input type="text" name="category" id="edit_task_category" style="width:100%; background:#080911; border:1px solid var(--border); color:#fff; padding:0.75rem; border-radius:10px; font-size:0.9rem; box-sizing:border-box;">
        </div>
        <div>
          <label style="font-size:0.75rem; color:#cbd5e1; font-weight:700; text-transform:uppercase; display:block; margin-bottom:0.4rem;">Reward (FP)</label>
          <input type="number" step="1" min="1" name="reward_amount" id="edit_task_reward" required style="width:100%; background:#080911; border:1px solid var(--border); color:var(--gold); font-weight:700; padding:0.75rem; border-radius:10px; font-size:0.9rem; box-sizing:border-box;">
        </div>
      </div>

      <div>
        <label style="font-size:0.75rem; color:#cbd5e1; font-weight:700; text-transform:uppercase; display:block; margin-bottom:0.4rem;">Target URL / Link</label>
        <input type="text" name="description" id="edit_task_desc" placeholder="Details or URL" style="width:100%; background:#080911; border:1px solid var(--border); color:#fff; padding:0.75rem; border-radius:10px; font-size:0.9rem; box-sizing:border-box;">
      </div>

      <div>
        <label style="font-size:0.75rem; color:#cbd5e1; font-weight:700; text-transform:uppercase; display:block; margin-bottom:0.4rem;">Deadline</label>
        <input type="date" name="deadline" id="edit_task_deadline" style="width:100%; background:#080911; border:1px solid var(--border); color:#fff; padding:0.75rem; border-radius:10px; font-size:0.9rem; box-sizing:border-box;">
      </div>

      <button type="submit" name="update_task" style="background:#60a5fa; color:#000; font-weight:800; font-size:1rem; padding:0.8rem; border:none; border-radius:8px; cursor:pointer; margin-top:0.5rem; text-transform:uppercase;">
        💾 Save Changes
      </button>
    </form>
  </div>
</div>

<script>
function switchAdminTaskTab(tab) {
  document.getElementById('tab-pane-queue').style.display = (tab === 'queue') ? 'block' : 'none';
  document.getElementById('tab-pane-tasks').style.display = (tab === 'tasks') ? 'block' : 'none';
  document.getElementById('tab-pane-create').style.display = (tab === 'create') ? 'block' : 'none';
  
  document.getElementById('btn-tab-queue').classList.toggle('active', tab === 'queue');
  document.getElementById('btn-tab-tasks').classList.toggle('active', tab === 'tasks');
  document.getElementById('btn-tab-create').classList.toggle('active', tab === 'create');
}

function openEditTaskModal(task) {
  document.getElementById('edit_task_id').value = task.id || '';
  document.getElementById('edit_task_title').value = task.title || '';
  document.getElementById('edit_task_category').value = task.category || '';
  document.getElementById('edit_task_reward').value = task.reward_points || '';
  document.getElementById('edit_task_desc').value = task.description || '';
  if (task.deadline && task.deadline.length > 10) {
    document.getElementById('edit_task_deadline').value = task.deadline.substring(0, 10);
  } else {
    document.getElementById('edit_task_deadline').value = task.deadline || '';
  }
  document.getElementById('editTaskModal').style.display = 'flex';
}
</script>

</body>
</html>
