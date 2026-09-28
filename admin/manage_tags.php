<?php
// =========================================================================
// admin/manage_tags.php  �  Custom Shop Tags Management
// =========================================================================
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php'); exit;
}
require_once __DIR__ . '/../config.php';

// Handle Tag Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_tag') {
    $name = trim($_POST['name'] ?? '');
    $icon = trim($_POST['icon'] ?? '');
    $color = trim($_POST['color'] ?? '#ffffff');
    $bg_color = trim($_POST['bg_color'] ?? '#000000');

    if ($name !== '') {
        $stmt = $pdo->prepare("INSERT INTO shop_tags (name, icon, color, bg_color) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $icon, $color, $bg_color]);
    }
    header('Location: manage_tags.php?success=1');
    exit;
}

// Handle Tag Deletion
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM shop_tags WHERE id = ?")->execute([$id]);
    header('Location: manage_tags.php?deleted=1');
    exit;
}

// Fetch all tags
$tags = $pdo->query("SELECT * FROM shop_tags ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Manage Tags � Admin Dashboard</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Oswald:wght@600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>

<nav>
  <a href="dashboard.php" class="nav-logo"> FAST SITE ADMIN</a>
  <div class="nav-links">
    <a href="dashboard.php">DASHBOARD</a>
    <a href="partner_shops.php">PARTNERS</a>
    <a href="manage_tags.php" class="active">SHOP TAGS</a>
    <a href="logout.php">LOGOUT</a>
  </div>
</nav>

<div class="wrap">
  <div class="header-box">
    <h1> Manage Shop Tags</h1>
    <p>Create dynamic tags (like Verified, Official) to assign to Partner Shops.</p>
  </div>
  
  <div class="grid">
    <!-- CREATE TAG FORM -->
    <div class="card">
      <h3>Create New Tag</h3>
      <form method="POST">
        <input type="hidden" name="action" value="add_tag"/>
        
        <div class="form-group">
          <label>Tag Name (e.g., OFFICIAL)</label>
          <input type="text" name="name" required placeholder="OFFICIAL"/>
        </div>
        
        <div class="form-group">
          <label>Icon / Emoji (e.g.,  or )</label>
          <input type="text" name="icon" placeholder=""/>
        </div>
        
        <div class="form-group" style="display:flex; gap:1rem;">
          <div style="flex:1;">
            <label>Text Color</label>
            <input type="color" name="color" value="#fcb900"/>
          </div>
          <div style="flex:1;">
            <label>Background Color</label>
            <input type="text" name="bg_color" placeholder="rgba(252, 185, 0, 0.15)" value="rgba(252, 185, 0, 0.15)"/>
          </div>
        </div>
        
        <button type="submit" class="btn">Add Tag</button>
      </form>
    </div>
    
    <!-- TAG LIST -->
    <div class="card">
      <h3>Existing Tags</h3>
      <?php if (empty($tags)): ?>
        <p style="color:var(--muted); font-size:0.9rem;">No tags created yet.</p>
      <?php else: ?>
        <div class="tag-list">
          <?php foreach ($tags as $t): ?>
            <div class="tag-item">
              <div class="custom-tag" style="color: <?= htmlspecialchars($t['color']) ?>; background: <?= htmlspecialchars($t['bg_color']) ?>; border-color: <?= htmlspecialchars($t['color']) ?>;">
                <?= htmlspecialchars($t['icon'] . ' ' . $t['name']) ?>
              </div>
              <a href="manage_tags.php?delete=<?= $t['id'] ?>" class="btn-del" onclick="return confirm('Delete this tag?')">Delete</a>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

</body>
</html>
