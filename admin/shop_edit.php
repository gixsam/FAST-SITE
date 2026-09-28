<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/../config.php';

$msg = $err = '';
$shop_id = intval($_GET['id'] ?? 0);

if ($shop_id <= 0) {
    die("Invalid Shop ID.");
}

// Handle Shop Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $b_name = trim($_POST['business_name'] ?? '');
    $o_name = trim($_POST['owner_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $pay_meth = trim($_POST['payout_method'] ?? '');
    $pay_acc = trim($_POST['payout_account'] ?? '');
    
    // Handle Logo Upload
    $profile_pic = $_POST['existing_profile_pic'] ?? '';
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $tmp_name = $_FILES['profile_pic']['tmp_name'];
        $name = time() . '_' . basename($_FILES['profile_pic']['name']);
        if (move_uploaded_file($tmp_name, __DIR__ . '/../uploads/partners/' . $name)) {
            $profile_pic = $name;
        }
    }

    // Handle Banner Upload
    $cover_pic = $_POST['existing_cover_pic'] ?? '';
    if (isset($_FILES['cover_pic']) && $_FILES['cover_pic']['error'] === UPLOAD_ERR_OK) {
        $tmp_name = $_FILES['cover_pic']['tmp_name'];
        $name = time() . '_cover_' . basename($_FILES['cover_pic']['name']);
        if (move_uploaded_file($tmp_name, __DIR__ . '/../uploads/partners/' . $name)) {
            $cover_pic = $name;
        }
    }

    try {
        $stmt = $pdo->prepare("UPDATE partners SET business_name=?, owner_name=?, phone=?, email=?, description=?, payout_method=?, payout_account=?, profile_pic=?, cover_pic=? WHERE id=?");
        $stmt->execute([$b_name, $o_name, $phone, $email, $desc, $pay_meth, $pay_acc, $profile_pic, $cover_pic, $shop_id]);
        $msg = "Shop profile updated successfully.";
    } catch (Exception $e) {
        $err = "Failed to update profile: " . $e->getMessage();
    }
}

// Handle Product Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    $prod_id = intval($_POST['product_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $desc = trim($_POST['description'] ?? '');

    try {
        $stmt = $pdo->prepare("UPDATE partner_products SET title=?, price=?, description=? WHERE id=? AND partner_id=?");
        $stmt->execute([$title, $price, $desc, $prod_id, $shop_id]);
        
        // Handle optional product photo upload
        if (isset($_FILES['product_photo']) && $_FILES['product_photo']['error'] === UPLOAD_ERR_OK) {
            $tmp_name = $_FILES['product_photo']['tmp_name'];
            $name = time() . '_prod_' . basename($_FILES['product_photo']['name']);
            if (move_uploaded_file($tmp_name, __DIR__ . '/../uploads/products/' . $name)) {
                // Check if image exists
                $imgChk = $pdo->prepare("SELECT id FROM partner_product_images WHERE product_id = ? LIMIT 1");
                $imgChk->execute([$prod_id]);
                if ($imgChk->fetchColumn()) {
                    $pdo->prepare("UPDATE partner_product_images SET image_url=? WHERE product_id=?")->execute([$name, $prod_id]);
                } else {
                    $pdo->prepare("INSERT INTO partner_product_images (product_id, image_url) VALUES (?, ?)")->execute([$prod_id, $name]);
                }
                try {
                    $pdo->prepare("UPDATE partner_products SET image = ? WHERE id = ?")->execute([$name, $prod_id]);
                } catch(Exception $e) {}
            }
        }
        $msg = "Product updated successfully.";
    } catch (Exception $e) {
        $err = "Failed to update product: " . $e->getMessage();
    }
}

// Handle Product Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product'])) {
    $prod_id = intval($_POST['product_id'] ?? 0);
    try {
        $pdo->prepare("DELETE FROM partner_products WHERE id=? AND partner_id=?")->execute([$prod_id, $shop_id]);
        $pdo->prepare("DELETE FROM partner_product_images WHERE product_id=?")->execute([$prod_id]);
        $msg = "Product deleted successfully.";
    } catch (Exception $e) {
        $err = "Failed to delete product.";
    }
}

// Fetch Shop Details
$stmt = $pdo->prepare("SELECT * FROM partners WHERE id = ?");
$stmt->execute([$shop_id]);
$shop = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$shop) {
    die("Shop not found.");
}

// Fetch Products
$prodStmt = $pdo->prepare("SELECT p.*, (SELECT image_url FROM partner_product_images WHERE product_id = p.id LIMIT 1) as image_url FROM partner_products p WHERE p.partner_id = ?");
$prodStmt->execute([$shop_id]);
$products = $prodStmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Shop: <?= htmlspecialchars($shop['business_name']) ?> - Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Oswald:wght@500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<div class="admin-layout">
  <?php include 'nav.php'; ?>
  <div class="admin-content">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 1rem;">
      <div>
        <a href="partner_shops.php" style="color:#60a5fa; text-decoration:none; font-size:0.85rem; font-weight:700;">&larr; Back to Shops</a>
        <h1 style="margin:0.5rem 0 0 0; font-family:'Oswald',sans-serif; color:var(--gold);">⚙️ SHOP MODERATION HUB</h1>
      </div>
    </div>

    <?php if ($msg): ?>
      <div style="background:rgba(16,185,129,0.15); border:1px solid #10b981; color:#10b981; padding:0.8rem 1.2rem; border-radius:12px; margin-bottom:1.5rem; font-weight:700;">✅ <?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>
    <?php if ($err): ?>
      <div style="background:rgba(239,68,68,0.15); border:1px solid #ef4444; color:#ef4444; padding:0.8rem 1.2rem; border-radius:12px; margin-bottom:1.5rem; font-weight:700;">❌ <?= htmlspecialchars($err) ?></div>
    <?php endif; ?>

    <!-- Section A: Profile Editor -->
    <div class="admin-card">
      <h3 style="margin-top:0; color:var(--gold);">🏬 Section A: Shop Profile Editor</h3>
      <form method="POST" enctype="multipart/form-data" style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem;">
        
        <div>
          <label style="display:block; margin-bottom:0.4rem; color:#94a3b8; font-size:0.85rem;">Business Name</label>
          <input type="text" name="business_name" value="<?= htmlspecialchars($shop['business_name']) ?>" required style="width:100%; padding:0.75rem; background:#080911; border:1px solid var(--border); color:#fff; border-radius:8px;">
        </div>

        <div>
          <label style="display:block; margin-bottom:0.4rem; color:#94a3b8; font-size:0.85rem;">Owner Name</label>
          <input type="text" name="owner_name" value="<?= htmlspecialchars($shop['owner_name']) ?>" required style="width:100%; padding:0.75rem; background:#080911; border:1px solid var(--border); color:#fff; border-radius:8px;">
        </div>

        <div>
          <label style="display:block; margin-bottom:0.4rem; color:#94a3b8; font-size:0.85rem;">Phone Number</label>
          <input type="text" name="phone" value="<?= htmlspecialchars($shop['phone']) ?>" required style="width:100%; padding:0.75rem; background:#080911; border:1px solid var(--border); color:#fff; border-radius:8px;">
        </div>

        <div>
          <label style="display:block; margin-bottom:0.4rem; color:#94a3b8; font-size:0.85rem;">Email Address</label>
          <input type="email" name="email" value="<?= htmlspecialchars($shop['email']) ?>" required style="width:100%; padding:0.75rem; background:#080911; border:1px solid var(--border); color:#fff; border-radius:8px;">
        </div>

        <div style="grid-column: span 2;">
          <label style="display:block; margin-bottom:0.4rem; color:#94a3b8; font-size:0.85rem;">Shop Description</label>
          <textarea name="description" rows="3" style="width:100%; padding:0.75rem; background:#080911; border:1px solid var(--border); color:#fff; border-radius:8px; resize:vertical;"><?= htmlspecialchars($shop['description'] ?? '') ?></textarea>
        </div>

        <div>
          <label style="display:block; margin-bottom:0.4rem; color:#94a3b8; font-size:0.85rem;">Payout Method</label>
          <select name="payout_method" style="width:100%; padding:0.75rem; background:#080911; border:1px solid var(--border); color:#fff; border-radius:8px;">
            <option value="bkash" <?= $shop['payout_method'] === 'bkash' ? 'selected' : '' ?>>bKash</option>
            <option value="nagad" <?= $shop['payout_method'] === 'nagad' ? 'selected' : '' ?>>Nagad</option>
            <option value="rocket" <?= $shop['payout_method'] === 'rocket' ? 'selected' : '' ?>>Rocket</option>
            <option value="bank" <?= $shop['payout_method'] === 'bank' ? 'selected' : '' ?>>Bank Transfer</option>
          </select>
        </div>

        <div>
          <label style="display:block; margin-bottom:0.4rem; color:#94a3b8; font-size:0.85rem;">Payout Account Number</label>
          <input type="text" name="payout_account" value="<?= htmlspecialchars($shop['payout_account'] ?? '') ?>" style="width:100%; padding:0.75rem; background:#080911; border:1px solid var(--border); color:#fff; border-radius:8px;">
        </div>

        <div>
          <label style="display:block; margin-bottom:0.4rem; color:#94a3b8; font-size:0.85rem;">Profile Logo (Optional)</label>
          <input type="file" name="profile_pic" accept="image/*" style="width:100%; padding:0.5rem; background:#080911; border:1px solid var(--border); color:#fff; border-radius:8px;">
          <input type="hidden" name="existing_profile_pic" value="<?= htmlspecialchars($shop['profile_pic'] ?? '') ?>">
          <?php if (!empty($shop['profile_pic'])): ?>
            <img src="<?= htmlspecialchars(resolveShopMedia($shop, 'avatar')) ?>" alt="Logo" style="width:40px; height:40px; margin-top:0.5rem; border-radius:6px; object-fit:cover;">
          <?php endif; ?>
        </div>

        <div>
          <label style="display:block; margin-bottom:0.4rem; color:#94a3b8; font-size:0.85rem;">Cover Banner (Optional)</label>
          <input type="file" name="cover_pic" accept="image/*" style="width:100%; padding:0.5rem; background:#080911; border:1px solid var(--border); color:#fff; border-radius:8px;">
          <input type="hidden" name="existing_cover_pic" value="<?= htmlspecialchars($shop['cover_pic'] ?? '') ?>">
          <?php if (!empty($shop['cover_pic'])): ?>
            <img src="<?= htmlspecialchars(resolveShopMedia($shop, 'cover')) ?>" alt="Banner" style="width:100px; height:40px; margin-top:0.5rem; border-radius:6px; object-fit:cover;">
          <?php endif; ?>
        </div>

        <div style="grid-column: span 2;">
          <button type="submit" name="update_profile" class="btn-approve" style="background:var(--gold); color:#000; padding:0.8rem 1.5rem; border-radius:8px; font-weight:800; border:none; cursor:pointer;">💾 Save Profile Changes</button>
        </div>
      </form>
    </div>

    <!-- Section B: Products Moderation -->
    <div class="admin-card" style="margin-top:2rem;">
      <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.8rem; margin-bottom:1.2rem;">
        <h3 style="margin:0; color:#60a5fa;">📦 Section B: Shop Products Moderation</h3>
        <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
          <?php if (strpos(strtolower($shop['business_name'] ?? ''), 'best travel') !== false): ?>
            <a href="sync_best_travel.php" class="btn-sm" style="background:linear-gradient(135deg, var(--gold), #f59e0b); color:#000; font-weight:800; text-decoration:none; padding:0.5rem 1rem; border-radius:8px; display:inline-flex; align-items:center; gap:0.4rem;">✈️ Auto-Sync / Restore HD Photos</a>
          <?php endif; ?>
          <a href="sync_partner_photos.php" class="btn-sm" style="background:rgba(255,255,255,0.08); border:1px solid var(--border); color:#fff; text-decoration:none; padding:0.5rem 1rem; border-radius:8px; font-weight:700;">📸 Auto-Link Photos</a>
        </div>
      </div>
      <?php if (empty($products)): ?>
        <p style="color:#94a3b8;">This shop has not published any products yet.</p>
      <?php else: ?>
        <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(300px, 1fr)); gap:1rem;">
          <?php foreach ($products as $prod): ?>
            <div style="background:rgba(255,255,255,0.03); border:1px solid var(--border); padding:1rem; border-radius:12px;">
              <form method="POST" enctype="multipart/form-data" style="display:flex; flex-direction:column; gap:0.8rem;">
                <input type="hidden" name="product_id" value="<?= $prod['id'] ?>">
                
                <?php if (!empty($prod['image_url'])): ?>
                  <img src="<?= htmlspecialchars(resolveProductArtwork($prod['image_url'] ?? '', '', $shop['business_name'] ?? '', $prod['category'] ?? '', $prod['title'] ?? '')) ?>" onerror="this.onerror=null; this.src='/assets/images/services/default_service.svg';" alt="Prod" style="width:100%; height:120px; object-fit:cover; border-radius:8px;">
                <?php elseif (!empty($prod['image'])): ?>
                  <img src="<?= htmlspecialchars(resolveProductArtwork($prod['image'] ?? '', '', $shop['business_name'] ?? '', $prod['category'] ?? '', $prod['title'] ?? '')) ?>" onerror="this.onerror=null; this.src='/assets/images/services/default_service.svg';" alt="Prod" style="width:100%; height:120px; object-fit:cover; border-radius:8px;">
                <?php endif; ?>
                
                <input type="text" name="title" value="<?= htmlspecialchars($prod['title']) ?>" required style="background:#080911; border:1px solid var(--border); color:#fff; padding:0.5rem; border-radius:6px; width:100%; box-sizing:border-box;">
                
                <div style="display:flex; gap:0.5rem; align-items:center;">
                  <span style="color:var(--gold); font-weight:700;">৳</span>
                  <input type="number" name="price" value="<?= $prod['price'] ?>" required style="background:#080911; border:1px solid var(--border); color:#fff; padding:0.5rem; border-radius:6px; flex:1;">
                </div>

                <textarea name="description" rows="2" style="background:#080911; border:1px solid var(--border); color:#fff; padding:0.5rem; border-radius:6px; width:100%; box-sizing:border-box;"><?= htmlspecialchars($prod['description'] ?? '') ?></textarea>
                
                <input type="file" name="product_photo" accept="image/*" style="font-size:0.75rem; color:#94a3b8;">

                <div style="display:flex; gap:0.5rem; margin-top:0.5rem;">
                  <button type="submit" name="update_product" class="btn-sm" style="flex:1; background:rgba(96,165,250,0.2); color:#60a5fa; border:1px solid rgba(96,165,250,0.4); padding:0.5rem; border-radius:6px; font-weight:700; cursor:pointer;">💾 Update</button>
                  <button type="submit" name="delete_product" class="btn-sm" onclick="return confirm('Delete this product permanently?')" style="background:rgba(239,68,68,0.2); color:#ef4444; border:1px solid rgba(239,68,68,0.4); padding:0.5rem; border-radius:6px; font-weight:700; cursor:pointer;">🗑️ Delete</button>
                </div>
              </form>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

  </div>
</div>
</body>
</html>
