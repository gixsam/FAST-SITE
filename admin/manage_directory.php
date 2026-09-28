<?php
// =========================================================================
// admin/manage_directory.php  –  Trust Directory Manager (Mobile-First)
// =========================================================================
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php'); exit;
}
require_once __DIR__ . '/../config.php';

$msg = $err = '';

// ── 1. CONTROLLER ACTIONS ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add Directory Item
    if (isset($_POST['add_item'])) {
        $name = trim($_POST['name'] ?? '');
        $url = trim($_POST['url'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $rating = trim($_POST['safety_rating'] ?? 'caution');
        $review = trim($_POST['admin_review'] ?? '');
        $redirect = trim($_POST['redirection_link'] ?? '');
        
        if (empty($name) || empty($category)) {
            $err = 'Name and Category are required.';
        } else {
            // Clean URL formatting
            if (!empty($url)) {
                $url = strtolower(str_replace(['http://', 'https://'], '', $url));
            }
            
            $stmt = $pdo->prepare("INSERT INTO trust_directory (name, url, category, safety_rating, admin_review, redirection_link) VALUES (:name, :url, :category, :rating, :review, :redirect)");
            $stmt->execute([
                ':name' => $name,
                ':url' => $url ?: null,
                ':category' => $category,
                ':rating' => $rating,
                ':review' => $review ?: null,
                ':redirect' => $redirect ?: null
            ]);
            $msg = "Directory item '$name' successfully added!";
        }
    }
    
    // Update Directory Item
    if (isset($_POST['update_item'])) {
        $id = (int)$_POST['item_id'];
        $name = trim($_POST['name'] ?? '');
        $url = trim($_POST['url'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $rating = trim($_POST['safety_rating'] ?? 'caution');
        $review = trim($_POST['admin_review'] ?? '');
        $redirect = trim($_POST['redirection_link'] ?? '');
        
        if (empty($name) || empty($category)) {
            $err = 'Name and Category cannot be empty.';
        } else {
            if (!empty($url)) {
                $url = strtolower(str_replace(['http://', 'https://'], '', $url));
            }
            
            $stmt = $pdo->prepare("UPDATE trust_directory SET name = :name, url = :url, category = :category, safety_rating = :rating, admin_review = :review, redirection_link = :redirect WHERE id = :id");
            $stmt->execute([
                ':name' => $name,
                ':url' => $url ?: null,
                ':category' => $category,
                ':rating' => $rating,
                ':review' => $review ?: null,
                ':redirect' => $redirect ?: null,
                ':id' => $id
            ]);
            $msg = 'Directory item updated successfully.';
        }
    }
}

// Delete Directory Item
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM trust_directory WHERE id = :id");
    $stmt->execute([':id' => $id]);
    header('Location: manage_directory.php?deleted=1');
    exit;
}

// Fetch directory items
$items = $pdo->query("SELECT * FROM trust_directory ORDER BY id DESC")->fetchAll();

// Count stats
$verifiedCount = 0;
$scamCount = 0;
foreach ($items as $item) {
    if ($item['safety_rating'] === 'verified') $verifiedCount++;
    if ($item['safety_rating'] === 'scam') $scamCount++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Trust Directory Manager — Fast Site Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Oswald:wght@600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>

<?php include 'nav.php'; ?>

<div class="wrap">
  <h1>🛡️ Trust Directory Manager</h1>
  <div class="sub-title">Audit online shopping sites, apps, and merchant details. Add safety ratings and guidelines to protect users from fraud.</div>

  <!-- Messages -->
  <?php if($msg): ?><div class="alert alert-ok">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if($err): ?><div class="alert alert-err">❌ <?= htmlspecialchars($err) ?></div><?php endif; ?>
  <?php if(isset($_GET['deleted'])): ?><div class="alert alert-ok">✅ Directory item deleted successfully.</div><?php endif; ?>

  <!-- Stats Grid -->
  <div class="stats">
    <div class="stat-card"><div class="stat-num"><?= count($items) ?></div><div class="stat-label">Total Sites</div></div>
    <div class="stat-card"><div class="stat-num" style="color:var(--green);"><?= $verifiedCount ?></div><div class="stat-label">Verified Legit</div></div>
    <div class="stat-card"><div class="stat-num" style="color:var(--red);"><?= $scamCount ?></div><div class="stat-label">Confirmed Scams</div></div>
  </div>

  <!-- Add Item Form -->
  <div class="premium-card">
    <h2>＋ Add New Directory Listing</h2>
    <form method="POST">
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; flex-wrap: wrap;">
        <div class="field">
          <label>Website/App Name *</label>
          <input type="text" name="name" placeholder="e.g. Daraz Bangladesh" required/>
        </div>
        <div class="field">
          <label>Category *</label>
          <input type="text" name="category" placeholder="e.g. E-Commerce, Clothing Store, Cash App" required/>
        </div>
      </div>
      
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; flex-wrap: wrap;">
        <div class="field">
          <label>Domain / URL / Identifier (Optional)</label>
          <input type="text" name="url" placeholder="e.g. daraz.com.bd"/>
        </div>
        <div class="field">
          <label>Safety Rating *</label>
          <select name="safety_rating" required>
            <option value="verified">Verified Legit (Safe to buy)</option>
            <option value="caution" selected>Proceed with Caution (Unverified)</option>
            <option value="scam">Confirmed Scam (Fraudulent Page)</option>
          </select>
        </div>
      </div>

      <div class="field">
        <label>Admin Review / Warning Notes</label>
        <textarea name="admin_review" rows="3" placeholder="Provide shopping tips, fraud history, or verification reasons..."></textarea>
      </div>

      <div class="field">
        <label>Affiliate / Direct Purchase Redirection Link (Optional)</label>
        <input type="url" name="redirection_link" placeholder="e.g. https://click.daraz.com/e/_abc123"/>
      </div>
      
      <button type="submit" name="add_item" class="btn">＋ Create Directory Listing</button>
    </form>
  </div>

  <!-- Listings Grid -->
  <div class="directory-grid">
    <?php if (empty($items)): ?>
      <div style="grid-column: span 3; text-align: center; color: var(--muted); padding: 3rem 0;">No directory listings registered yet. Add one above!</div>
    <?php else: ?>
      <?php foreach($items as $i): ?>
        <div class="item-card">
          <div class="item-card-header">
            <span class="item-title"><?= htmlspecialchars($i['name']) ?></span>
            <span class="badge badge-<?= $i['safety_rating'] ?>"><?= htmlspecialchars($i['safety_rating']) ?></span>
          </div>
          
          <form method="POST" class="item-form">
            <input type="hidden" name="item_id" value="<?= $i['id'] ?>"/>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
              <div>
                <label class="pi-label">Name</label>
                <input class="pi-input" type="text" name="name" style="width:100%;" value="<?= htmlspecialchars($i['name']) ?>" required/>
              </div>
              <div>
                <label class="pi-label">Category</label>
                <input class="pi-input" type="text" name="category" style="width:100%;" value="<?= htmlspecialchars($i['category']) ?>" required/>
              </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
              <div>
                <label class="pi-label">Domain/Identifier</label>
                <input class="pi-input" type="text" name="url" style="width:100%;" value="<?= htmlspecialchars($i['url'] ?? '') ?>"/>
              </div>
              <div>
                <label class="pi-label">Safety Rating</label>
                <select class="pi-select" name="safety_rating" style="width:100%;" required>
                  <option value="verified" <?= $i['safety_rating'] === 'verified' ? 'selected' : '' ?>>Verified Legit</option>
                  <option value="caution" <?= $i['safety_rating'] === 'caution' ? 'selected' : '' ?>>Proceed with Caution</option>
                  <option value="scam" <?= $i['safety_rating'] === 'scam' ? 'selected' : '' ?>>Confirmed Scam</option>
                </select>
              </div>
            </div>

            <label class="pi-label">Review / Warnings</label>
            <textarea class="pi-textarea" name="admin_review" rows="2"><?= htmlspecialchars($i['admin_review'] ?? '') ?></textarea>

            <label class="pi-label">Redirection Link</label>
            <input class="pi-input" type="url" name="redirection_link" value="<?= htmlspecialchars($i['redirection_link'] ?? '') ?>" placeholder="None"/>
            
            <div class="item-actions">
              <button type="submit" name="update_item" class="btn-save-sm">💾 Save Changes</button>
              <a href="manage_directory.php?delete=<?= $i['id'] ?>" class="btn-del" onclick="return confirm('Delete listing <?= htmlspecialchars($i['name']) ?>?')">🗑 Delete</a>
            </div>
          </form>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

</body>
</html>
