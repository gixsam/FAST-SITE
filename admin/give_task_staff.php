<?php
// =========================================================================
// admin/give_task_staff.php — Staff Task Delegation & Workflow Hub
// =========================================================================
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/../config.php';

$isAdmin = ($_SESSION['admin_role'] ?? 'staff') === 'admin';
$msg = $err = '';

// Get current logged-in staff ID
$meStmt = $pdo->prepare("SELECT id FROM staff_users WHERE username = ?");
$meStmt->execute([$_SESSION['admin_user'] ?? 'admin']);
$myId = (int)$meStmt->fetchColumn();

// ── Handle Task Creation (Admins only) ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_task']) && $isAdmin) {
    $targetStaff = $_POST['staff_user_id'] === 'all' ? null : (int)$_POST['staff_user_id'];
    $title       = trim($_POST['title'] ?? '');
    $desc        = trim($_POST['description'] ?? '');
    
    if ($title !== '') {
        $stmt = $pdo->prepare("INSERT INTO staff_tasks (staff_user_id, title, description, status, created_at) VALUES (:staff, :title, :desc, 'pending', CURRENT_TIMESTAMP)");
        $stmt->execute([
            ':staff' => $targetStaff,
            ':title' => $title,
            ':desc'  => $desc ?: null
        ]);
        $msg = 'Task successfully assigned to staff!';
    } else {
        $err = 'Task title is required.';
    }
}

// ── Handle Toggle Status (Admins can toggle, Staff can mark complete) ─────
if (isset($_GET['complete_task'])) {
    $tid = (int)$_GET['complete_task'];
    
    $chk = $pdo->prepare("SELECT * FROM staff_tasks WHERE id = ?");
    $chk->execute([$tid]);
    $task = $chk->fetch();
    
    if ($task) {
        if ($isAdmin || $task['staff_user_id'] === null || (int)$task['staff_user_id'] === $myId) {
            $pdo->prepare("UPDATE staff_tasks SET status = 'completed' WHERE id = ?")->execute([$tid]);
            $msg = 'Task marked as completed!';
        } else {
            $err = 'You do not have permission to complete this task.';
        }
    }
}

// ── Handle Task Deletion (Admins only) ─────────────────────────────────────
if (isset($_GET['delete_task']) && $isAdmin) {
    $tid = (int)$_GET['delete_task'];
    $pdo->prepare("DELETE FROM staff_tasks WHERE id = ?")->execute([$tid]);
    $msg = 'Task successfully removed.';
}

// Fetch staff members for dropdown
$staffList = [];
try {
    $staffList = $pdo->query("SELECT id, username FROM staff_users ORDER BY username ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Fetch tasks based on role
$tasks = [];
try {
    if ($isAdmin) {
        $tasks = $pdo->query("
            SELECT t.*, s.username AS staff_name
            FROM staff_tasks t
            LEFT JOIN staff_users s ON t.staff_user_id = s.id
            ORDER BY t.status ASC, t.created_at DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $stmt = $pdo->prepare("
            SELECT t.*, s.username AS staff_name
            FROM staff_tasks t
            LEFT JOIN staff_users s ON t.staff_user_id = s.id
            WHERE t.staff_user_id IS NULL OR t.staff_user_id = :myId
            ORDER BY t.status ASC, t.created_at DESC
        ");
        $stmt->execute([':myId' => $myId]);
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0"/>
  <title>Staff Tasks & Delegation — Fast Site Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Oswald:wght@600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="/assets/css/admin.css?v=<?= time() ?>"/>
  <style>
    .task-grid {
      display: grid;
      grid-template-columns: 1fr 1.6fr;
      gap: 1.5rem;
      align-items: start;
    }
    @media (max-width: 900px) {
      .task-grid { grid-template-columns: 1fr; }
    }
    .panel-card {
      background: rgba(16, 18, 28, 0.85);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 18px;
      padding: 1.6rem;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
      backdrop-filter: blur(14px);
    }
    .panel-title {
      font-family: 'Oswald', sans-serif;
      font-size: 1.2rem;
      color: #fff;
      margin-bottom: 1.2rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
      text-transform: uppercase;
    }
    .task-card-item {
      background: rgba(0, 0, 0, 0.35);
      border: 1px solid rgba(255, 255, 255, 0.06);
      border-radius: 14px;
      padding: 1.2rem;
      margin-bottom: 1rem;
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 1rem;
      transition: all 0.2s;
    }
    .task-card-item:hover {
      border-color: rgba(255, 255, 255, 0.15);
      background: rgba(255, 255, 255, 0.02);
    }
    .task-card-item.completed {
      opacity: 0.65;
      border-left: 4px solid #10b981;
    }
    .task-card-item.pending {
      border-left: 4px solid var(--gold);
    }
  </style>
</head>
<body>

<!-- Unified Master Top Navigation -->
<?php include __DIR__ . '/nav.php'; ?>

<div class="dashboard-container">

  <!-- Header Admin Hero -->
  <div class="admin-hero">
    <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:1rem;">
      <div>
        <h2 class="admin-hero-title">🎯 STAFF TASKS & DELEGATION</h2>
        <p class="admin-hero-subtitle">Assign internal operational tasks, track team progress & manage staff workloads</p>
      </div>
      <div style="display:flex; gap:0.6rem; flex-wrap:wrap;">
        <a href="staff_access.php" class="btn-sm" style="display:inline-flex; align-items:center; gap:0.4rem; padding: 0.55rem 1.2rem; background: rgba(99, 102, 241, 0.15); border:1px solid #6366f1; color:#fff; font-weight:700; text-decoration:none; border-radius:10px;">
          👥 Staff Access & Roles ➔
        </a>
      </div>
    </div>
  </div>

  <!-- Status Alerts -->
  <?php if ($msg): ?>
    <div style="background:rgba(16,185,129,0.15); border:1px solid #10b981; color:#10b981; padding:0.9rem 1.3rem; border-radius:12px; margin-bottom:1.5rem; font-weight:700; display:flex; align-items:center; gap:8px;">
      ✅ <?= htmlspecialchars($msg) ?>
    </div>
  <?php endif; ?>

  <?php if ($err): ?>
    <div style="background:rgba(239,68,68,0.15); border:1px solid #ef4444; color:#ef4444; padding:0.9rem 1.3rem; border-radius:12px; margin-bottom:1.5rem; font-weight:700; display:flex; align-items:center; gap:8px;">
      ⚠️ <?= htmlspecialchars($err) ?>
    </div>
  <?php endif; ?>

  <div class="task-grid">
    
    <!-- LEFT PANEL: Task Creation (Admin Only) -->
    <div>
      <?php if ($isAdmin): ?>
        <div class="panel-card">
          <div class="panel-title">➕ Give New Task to Staff</div>
          <form method="POST">
            <div style="margin-bottom:1.2rem;">
              <label style="display:block; font-size:0.75rem; font-weight:800; color:var(--muted); text-transform:uppercase; margin-bottom:0.4rem;">Assign To Teammate</label>
              <select name="staff_user_id" style="width:100%; background:rgba(0,0,0,0.5); border:1px solid rgba(255,255,255,0.12); padding:0.75rem 1rem; border-radius:10px; color:#fff; font-size:0.9rem; outline:none;">
                <option value="all">🌐 Assign to All Staff</option>
                <?php foreach ($staffList as $st): ?>
                  <option value="<?= $st['id'] ?>">👤 <?= htmlspecialchars($st['username']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div style="margin-bottom:1.2rem;">
              <label style="display:block; font-size:0.75rem; font-weight:800; color:var(--muted); text-transform:uppercase; margin-bottom:0.4rem;">Task Title *</label>
              <input type="text" name="title" required placeholder="e.g. Audit new partner shop requests" style="width:100%; background:rgba(0,0,0,0.5); border:1px solid rgba(255,255,255,0.12); padding:0.75rem 1rem; border-radius:10px; color:#fff; font-size:0.9rem; outline:none;"/>
            </div>

            <div style="margin-bottom:1.5rem;">
              <label style="display:block; font-size:0.75rem; font-weight:800; color:var(--muted); text-transform:uppercase; margin-bottom:0.4rem;">Instructions / Details</label>
              <textarea name="description" rows="4" placeholder="Provide step-by-step guidance for the staff member..." style="width:100%; background:rgba(0,0,0,0.5); border:1px solid rgba(255,255,255,0.12); padding:0.75rem 1rem; border-radius:10px; color:#fff; font-size:0.9rem; outline:none; resize:vertical;"></textarea>
            </div>

            <button type="submit" name="create_task" class="btn" style="width:100%; background:linear-gradient(135deg, var(--gold) 0%, #f59e0b 100%); color:#000; font-weight:800; padding:0.85rem; border-radius:10px; border:none; cursor:pointer; font-size:0.95rem; box-shadow:0 4px 15px rgba(252,185,0,0.3);">
              🎯 Create & Assign Task
            </button>
          </form>
        </div>
      <?php else: ?>
        <div class="panel-card">
          <div class="panel-title">👤 Staff Workspace</div>
          <p style="color:#94a3b8; font-size:0.88rem; line-height:1.5;">
            Welcome to your active task queue. Review tasks assigned to you, follow the instructions, and mark them completed as you execute platform operations.
          </p>
        </div>
      <?php endif; ?>
    </div>

    <!-- RIGHT PANEL: Tasks Queue -->
    <div class="panel-card">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.2rem;">
        <div class="panel-title" style="margin:0;">📋 Task Queue (<?= count($tasks) ?>)</div>
      </div>

      <?php if (empty($tasks)): ?>
        <div style="text-align:center; padding:3rem 1rem; color:#94a3b8; font-size:0.9rem;">
          <div style="font-size:3rem; margin-bottom:0.5rem;">🎉</div>
          No tasks currently pending in the queue!
        </div>
      <?php else: ?>
        <div style="display:flex; flex-direction:column; gap:0.8rem;">
          <?php foreach ($tasks as $t): ?>
            <?php $isDone = ($t['status'] === 'completed'); ?>
            <div class="task-card-item <?= $isDone ? 'completed' : 'pending' ?>">
              <div style="flex:1;">
                <div style="font-weight:700; font-size:0.95rem; color:#fff; <?= $isDone ? 'text-decoration:line-through; color:#94a3b8;' : '' ?>">
                  <?= htmlspecialchars($t['title']) ?>
                </div>
                
                <?php if (!empty($t['description'])): ?>
                  <div style="font-size:0.8rem; color:#94a3b8; margin:0.4rem 0; line-height:1.4;">
                    <?= nl2br(htmlspecialchars($t['description'])) ?>
                  </div>
                <?php endif; ?>

                <div style="display:flex; gap:0.5rem; flex-wrap:wrap; margin-top:0.6rem; align-items:center;">
                  <span style="font-size:0.7rem; background:rgba(255,255,255,0.06); padding:2px 8px; border-radius:6px; color:#cbd5e1;">
                    👤 <?= $t['staff_name'] ? htmlspecialchars($t['staff_name']) : 'All Staff' ?>
                  </span>
                  <span style="font-size:0.7rem; background:<?= $isDone ? 'rgba(16,185,129,0.2)' : 'rgba(252,185,0,0.2)' ?>; color:<?= $isDone ? '#10b981' : '#fcb900' ?>; padding:2px 8px; border-radius:6px; font-weight:800; text-transform:uppercase;">
                    <?= $t['status'] ?>
                  </span>
                  <span style="font-size:0.7rem; color:#64748b;">
                    <?= date('M d, h:i A', strtotime($t['created_at'] ?? 'now')) ?>
                  </span>
                </div>
              </div>

              <div style="display:flex; gap:0.4rem; align-items:center; flex-shrink:0;">
                <?php if (!$isDone): ?>
                  <a href="give_task_staff.php?complete_task=<?= $t['id'] ?>" style="background:rgba(16,185,129,0.15); border:1px solid #10b981; color:#10b981; font-weight:700; padding:0.45rem 0.8rem; border-radius:8px; text-decoration:none; font-size:0.75rem; transition:all 0.2s;">
                    ✓ Done
                  </a>
                <?php endif; ?>

                <?php if ($isAdmin): ?>
                  <a href="give_task_staff.php?delete_task=<?= $t['id'] ?>" onclick="return confirm('Delete this task?');" style="background:rgba(239,68,68,0.12); border:1px solid rgba(239,68,68,0.3); color:#ef4444; font-weight:700; padding:0.45rem 0.8rem; border-radius:8px; text-decoration:none; font-size:0.75rem; transition:all 0.2s;">
                    🗑
                  </a>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

  </div>

</div>

</body>
</html>
