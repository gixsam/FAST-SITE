<?php
// =========================================================================
// partner/product_edit.php  –  Edit Product Listing Hub
// =========================================================================
ob_start();
require_once 'nav.php';
$_nav_html = ob_get_clean();

if (!isset($_SESSION['partner_id'])) {
    header('Location: /user/login.php');
    exit;
}

$partner_id = (int)$_SESSION['partner_id'];
$product_id = intval($_GET['id'] ?? 0);
$coin_name  = getPartnerSetting('coin_name', 'Fast Points');

$err = $msg = '';

// Verify ownership and load product details
$stmt = $pdo->prepare("SELECT * FROM partner_products WHERE id = :id AND partner_id = :partner_id LIMIT 1");
$stmt->execute([':id' => $product_id, ':partner_id' => $partner_id]);
$product = $stmt->fetch();

if (!$product) {
    echo "<div class='main-content'><div class='box' style='text-align:center; padding:3rem 1rem;'><h2>⚠️ Product Not Found</h2><p style='color:#aaa;'>Product not found or unauthorized access.</p><br><a href='products.php' class='btn' style='background:var(--brand,#fcb900); color:#000; padding:0.6rem 1.5rem; font-weight:700; text-decoration:none; border-radius:8px;'>Back to Products</a></div></div>";
    echo "</body></html>";
    exit;
}

// Handle image deletion
if (isset($_GET['delete_image'])) {
    $img_id = intval($_GET['delete_image']);
    
    $stmt_img = $pdo->prepare("SELECT * FROM partner_product_images WHERE id = :img_id AND product_id = :product_id LIMIT 1");
    $stmt_img->execute([':img_id' => $img_id, ':product_id' => $product_id]);
    $image = $stmt_img->fetch();
    
    if ($image) {
        $filepath = '../uploads/partners/' . $image['image_url'];
        if (file_exists($filepath)) {
            @unlink($filepath);
        }
        
        $pdo->prepare("DELETE FROM partner_product_images WHERE id = :img_id")->execute([':img_id' => $img_id]);
        
        if ($image['is_thumbnail'] === 1) {
            $pdo->prepare("UPDATE partner_product_images SET is_thumbnail = 1 WHERE product_id = :product_id LIMIT 1")
                ->execute([':product_id' => $product_id]);
        }
        
        header("Location: product_edit.php?id=" . $product_id . "&msg=Image+deleted+successfully");
        exit;
    }
}

// Handle set thumbnail
if (isset($_GET['set_thumb'])) {
    $img_id = intval($_GET['set_thumb']);
    
    $pdo->prepare("UPDATE partner_product_images SET is_thumbnail = 0 WHERE product_id = :product_id")
        ->execute([':product_id' => $product_id]);
    $pdo->prepare("UPDATE partner_product_images SET is_thumbnail = 1 WHERE id = :img_id AND product_id = :product_id")
        ->execute([':img_id' => $img_id, ':product_id' => $product_id]);
        
    header("Location: product_edit.php?id=" . $product_id . "&msg=Thumbnail+updated");
    exit;
}

// Handle audio track deletion
if (isset($_GET['delete_audio'])) {
    $stmt_aud = $pdo->prepare("SELECT audio_file FROM partner_products WHERE id = :product_id AND partner_id = :partner_id LIMIT 1");
    $stmt_aud->execute([':product_id' => $product_id, ':partner_id' => $partner_id]);
    $old_aud = $stmt_aud->fetchColumn();
    if ($old_aud && file_exists('../uploads/audio/' . $old_aud)) {
        @unlink('../uploads/audio/' . $old_aud);
    }
    $pdo->prepare("UPDATE partner_products SET audio_file = NULL WHERE id = :product_id AND partner_id = :partner_id")
        ->execute([':product_id' => $product_id, ':partner_id' => $partner_id]);
    header("Location: product_edit.php?id=" . $product_id . "&msg=Audio+track+removed+successfully");
    exit;
}

// Handle updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $listing_type = trim($_POST['listing_type'] ?? 'product');
    $title        = trim($_POST['title'] ?? '');
    $description  = trim($_POST['description'] ?? '');
    $price        = floatval($_POST['price'] ?? 0);
    $category     = trim($_POST['category'] ?? 'General');
    $scheduled_at = trim($_POST['scheduled_at'] ?? '');
    $is_published = isset($_POST['is_published']) ? 1 : 0;
    
    // Inventory & Delivery
    $stock          = (isset($_POST['stock']) && $_POST['stock'] !== '') ? intval($_POST['stock']) : -1;
    $shipping_type  = trim($_POST['shipping_type'] ?? 'digital');
    $estimated_time = trim($_POST['estimated_time'] ?? '');
    
    // Affiliate Fields
    $affiliate_url    = trim($_POST['affiliate_url'] ?? '');
    $affiliate_action = trim($_POST['affiliate_action'] ?? '');
    
    // Customer Document & Info Submission System (Summation System)
    $require_submission  = isset($_POST['require_submission']) ? 1 : 0;
    $submission_required = isset($_POST['submission_required']) ? 1 : 0;
    $submission_type     = trim($_POST['submission_type'] ?? 'text_and_files');
    $submission_prompt   = trim($_POST['submission_prompt'] ?? '');
    $required_docs       = $submission_prompt;

    $missing_fields = [];
    if (empty($title)) {
        $missing_fields[] = 'Listing Title';
    }
    if ($listing_type === 'affiliate' && empty($affiliate_url)) {
        $missing_fields[] = 'Affiliate Destination URL';
    }
    if ($require_submission && empty($submission_prompt)) {
        $missing_fields[] = 'Customer Submission Prompt';
    }

    if (!empty($missing_fields)) {
        $err = 'Please complete the required missing field(s): ' . implode(', ', $missing_fields);
    } else {
        $sched_val = !empty($scheduled_at) ? date('Y-m-d H:i:s', strtotime($scheduled_at)) : null;

        // Ensure upload directories exist safely
        $upload_dirs = [
            __DIR__ . '/../uploads/products/',
            __DIR__ . '/../uploads/partners/',
            __DIR__ . '/../uploads/audio/'
        ];
        foreach ($upload_dirs as $dir) {
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
        }

        // Defensive schema migrations (Executed OUTSIDE transaction to prevent MySQL implicit commit)
        try { @$pdo->exec("ALTER TABLE `partner_products` ADD COLUMN `require_submission` TINYINT(1) DEFAULT 0"); } catch (Exception $e) {}
        try { @$pdo->exec("ALTER TABLE `partner_products` ADD COLUMN `submission_type` VARCHAR(50) DEFAULT 'text_and_files'"); } catch (Exception $e) {}
        try { @$pdo->exec("ALTER TABLE `partner_products` ADD COLUMN `submission_required` TINYINT(1) DEFAULT 1"); } catch (Exception $e) {}
        try { @$pdo->exec("ALTER TABLE `partner_products` ADD COLUMN `submission_prompt` TEXT DEFAULT NULL"); } catch (Exception $e) {}
        try { @$pdo->exec("ALTER TABLE `partner_products` ADD COLUMN `affiliate_url` TEXT DEFAULT NULL"); } catch (Exception $e) {}
        try { @$pdo->exec("ALTER TABLE `partner_products` ADD COLUMN `affiliate_action` TEXT DEFAULT NULL"); } catch (Exception $e) {}
        try { @$pdo->exec("ALTER TABLE `partner_products` ADD COLUMN `audio_file` VARCHAR(255) DEFAULT NULL"); } catch (Exception $e) {}

        try {
            if (!$pdo->inTransaction()) {
                $pdo->beginTransaction();
            }

            $stmt_up = $pdo->prepare("UPDATE partner_products 
                SET title = :title, description = :description, price = :price, 
                    category = :category, scheduled_at = :scheduled_at, is_published = :is_published, listing_type = :listing_type, 
                    stock = :stock, shipping_type = :shipping_type, estimated_time = :estimated_time, required_docs = :required_docs,
                    require_submission = :require_sub, submission_type = :sub_type, submission_required = :sub_req, submission_prompt = :sub_prompt,
                    affiliate_url = :affiliate_url, affiliate_action = :affiliate_action
                WHERE id = :id AND partner_id = :partner_id");
            
            $stmt_up->execute([
                ':title'         => $title,
                ':description'   => $description ?: null,
                ':price'         => $price,
                ':category'      => $category ?: 'General',
                ':scheduled_at'  => $sched_val,
                ':is_published'  => $is_published,
                ':listing_type'  => $listing_type ?: 'product',
                ':stock'         => $stock,
                ':shipping_type' => $shipping_type ?: 'digital',
                ':estimated_time'=> $estimated_time ?? '',
                ':required_docs' => $required_docs ?? '',
                ':require_sub'   => $require_submission,
                ':sub_type'      => $submission_type,
                ':sub_req'       => $submission_required,
                ':sub_prompt'    => $submission_prompt,
                ':affiliate_url' => $affiliate_url,
                ':affiliate_action'=> $affiliate_action,
                ':id'            => $product_id,
                ':partner_id'    => $partner_id
            ]);

            // Handle new image uploads
            $upload_dir = __DIR__ . '/../uploads/partners/';

            if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
                $files = $_FILES['images'];
                $count = count($files['name']);
                
                $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM partner_product_images WHERE product_id = :product_id AND is_thumbnail = 1");
                $stmt_count->execute([':product_id' => $product_id]);
                $has_thumbnail = intval($stmt_count->fetchColumn()) > 0;

                for ($i = 0; $i < $count; $i++) {
                    if ($files['error'][$i] === UPLOAD_ERR_OK) {
                        $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
                        if (in_array($ext, ['jpg','jpeg','png','webp','gif'])) {
                            $filename = 'prod_' . $product_id . '_' . $i . '_' . time() . '.' . $ext;
                            if (move_uploaded_file($files['tmp_name'][$i], $upload_dir . $filename)) {
                                @chmod($upload_dir . $filename, 0644);
                                $is_thumb = (!$has_thumbnail && $i === 0) ? 1 : 0;
                                $pdo->prepare("INSERT INTO partner_product_images (product_id, image_url, is_thumbnail) VALUES (?, ?, ?)")
                                    ->execute([$product_id, $filename, $is_thumb]);
                                if ($is_thumb) $has_thumbnail = true;
                            }
                        }
                    }
                }
            }

            // Handle audio track upload / replacement
            if (isset($_FILES['audio_file']) && $_FILES['audio_file']['error'] === UPLOAD_ERR_OK) {
                $audio_dir = __DIR__ . '/../uploads/audio/';
                $allowed_audio = ['mp3', 'wav', 'ogg', 'flac', 'm4a', 'aac'];
                $audio_ext = strtolower(pathinfo($_FILES['audio_file']['name'], PATHINFO_EXTENSION));
                if (in_array($audio_ext, $allowed_audio) && $_FILES['audio_file']['size'] <= 50 * 1024 * 1024) {
                    $audio_filename = 'audio_prod_' . $product_id . '_' . time() . '.' . $audio_ext;
                    if (move_uploaded_file($_FILES['audio_file']['tmp_name'], $audio_dir . $audio_filename)) {
                        // Remove old audio file if exists
                        if (!empty($product['audio_file']) && file_exists($audio_dir . $product['audio_file'])) {
                            @unlink($audio_dir . $product['audio_file']);
                        }
                        $pdo->prepare("UPDATE partner_products SET audio_file = ? WHERE id = ? AND partner_id = ?")
                            ->execute([$audio_filename, $product_id, $partner_id]);
                    }
                }
            }

            if ($pdo->inTransaction()) {
                $pdo->commit();
            }

            if (isset($_POST['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'redirect' => 'products.php?msg=Product+updated+successfully']);
                exit;
            }
            echo "<!DOCTYPE html><html><head><meta http-equiv='refresh' content='0;url=products.php?msg=Product+updated+successfully'><script>window.location.href='products.php?msg=Product+updated+successfully';</script></head><body>Redirecting to products...</body></html>";
            exit;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $err = 'Error updating product: ' . $e->getMessage();
        }
    }
}

// Reload product details
$stmt->execute([':id' => $product_id, ':partner_id' => $partner_id]);
$product = $stmt->fetch();

$stmt_imgs = $pdo->prepare("SELECT * FROM partner_product_images WHERE product_id = :product_id ORDER BY is_thumbnail DESC, id ASC");
$stmt_imgs->execute([':product_id' => $product_id]);
$images = $stmt_imgs->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Edit Product &mdash; Fast Site Partner Hub</title>
  <link rel="stylesheet" href="../assets/css/admin.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Oswald:wght@500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --gold: #fcb900;
      --gold-glow: rgba(252, 185, 0, 0.25);
      --teal: #00e676;
      --dark-card: rgba(18, 20, 32, 0.75);
      --border-glass: rgba(255, 255, 255, 0.08);
    }
    
    body {
      background: radial-gradient(circle at 50% 0%, #151828 0%, #090a12 100%);
      color: #e2e8f0;
      font-family: 'Inter', sans-serif;
      min-height: 100vh;
    }

    .form-hero {
      background: linear-gradient(135deg, rgba(252, 185, 0, 0.12) 0%, rgba(20, 24, 40, 0.6) 100%);
      border: 1px solid rgba(252, 185, 0, 0.25);
      border-radius: 18px;
      padding: 1.8rem 2rem;
      margin-bottom: 2rem;
      backdrop-filter: blur(12px);
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 1rem;
    }

    .form-hero h1 {
      margin: 0;
      font-family: 'Oswald', sans-serif;
      font-size: 1.8rem;
      color: #fff;
      text-transform: uppercase;
      letter-spacing: 0.04em;
    }

    .form-panel {
      background: var(--dark-card);
      border: 1px solid var(--border-glass);
      border-radius: 20px;
      padding: 2.2rem;
      max-width: 820px;
      margin: 0 auto 3rem auto;
      backdrop-filter: blur(16px);
      box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
    }

    .type-switcher {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 0.8rem;
      margin-bottom: 1.8rem;
      background: rgba(0, 0, 0, 0.3);
      padding: 0.5rem;
      border-radius: 14px;
      border: 1px solid rgba(255, 255, 255, 0.05);
    }

    .type-pill {
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
      padding: 0.75rem 0.5rem;
      border-radius: 10px;
      font-weight: 700;
      font-size: 0.85rem;
      color: #94a3b8;
      transition: all 0.25s;
      border: 1px solid transparent;
      user-select: none;
    }

    .type-pill input { display: none; }

    .type-pill.active {
      background: linear-gradient(135deg, rgba(252, 185, 0, 0.18), rgba(252, 185, 0, 0.08));
      color: #fff;
      border-color: rgba(252, 185, 0, 0.4);
      box-shadow: 0 4px 15px var(--gold-glow);
    }

    .field { margin-bottom: 1.4rem; }

    .field label {
      display: block;
      font-size: 0.76rem;
      font-weight: 700;
      color: #94a3b8;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      margin-bottom: 0.45rem;
    }

    .field input, .field select, .field textarea {
      width: 100%;
      background: rgba(10, 12, 20, 0.6);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 12px;
      padding: 0.8rem 1.1rem;
      color: #fff;
      font-size: 0.92rem;
      font-family: inherit;
      outline: none;
      transition: all 0.2s;
      box-sizing: border-box;
    }

    .field input:focus, .field select:focus, .field textarea:focus {
      border-color: var(--gold);
      box-shadow: 0 0 15px var(--gold-glow);
      background: rgba(15, 18, 30, 0.8);
    }

    .field select option {
      background: #111422 !important;
      color: #fff !important;
      padding: 10px;
    }

    .row-flex {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1.2rem;
    }

    @media (max-width: 640px) {
      .row-flex { grid-template-columns: 1fr; }
      .type-switcher { grid-template-columns: 1fr; }
    }

    /* Images grid style */
    .images-manager {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
      gap: 1rem;
      background: rgba(0,0,0,0.3);
      border: 1px solid rgba(255,255,255,0.06);
      padding: 1rem;
      border-radius: 14px;
      margin-bottom: 1.4rem;
    }

    .img-manage-card {
      position: relative;
      background: #0d0e17;
      border-radius: 10px;
      overflow: hidden;
      aspect-ratio: 1;
      border: 2px solid rgba(255,255,255,0.08);
      display: flex;
      flex-direction: column;
    }

    .img-manage-card.thumbnail {
      border-color: var(--gold);
      box-shadow: 0 0 15px var(--gold-glow);
    }

    .img-manage-card img {
      width: 100%;
      height: 75%;
      object-fit: cover;
    }

    .img-actions-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 0.25rem 0.5rem;
      background: #141624;
      height: 25%;
    }

    .thumb-indicator {
      position: absolute;
      top: 4px; left: 4px;
      background: var(--gold);
      color: #000;
      font-size: 0.6rem;
      font-weight: 800;
      padding: 2px 6px;
      border-radius: 4px;
      text-transform: uppercase;
    }

    /* Submission Box */
    .submission-box {
      background: linear-gradient(135deg, rgba(0, 230, 118, 0.04) 0%, rgba(20, 28, 45, 0.4) 100%);
      border: 1px solid rgba(0, 230, 118, 0.25);
      border-radius: 16px;
      padding: 1.5rem;
      margin-bottom: 1.6rem;
    }

    .switch {
      position: relative;
      display: inline-block;
      width: 44px;
      height: 24px;
      flex-shrink: 0;
    }

    .switch input { opacity: 0; width: 0; height: 0; }

    .slider {
      position: absolute;
      cursor: pointer;
      top: 0; left: 0; right: 0; bottom: 0;
      background-color: rgba(255,255,255,0.15);
      transition: .3s;
      border-radius: 34px;
    }

    .slider:before {
      position: absolute;
      content: "";
      height: 18px;
      width: 18px;
      left: 3px;
      bottom: 3px;
      background-color: white;
      transition: .3s;
      border-radius: 50%;
    }

    input:checked + .slider { background-color: var(--teal); }
    input:checked + .slider:before { transform: translateX(20px); }

    .btn-submit {
      background: linear-gradient(135deg, var(--gold) 0%, #ff9100 100%);
      color: #000;
      font-weight: 900;
      font-family: 'Oswald', sans-serif;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      border: none;
      border-radius: 50px;
      padding: 1rem 2rem;
      width: 100%;
      font-size: 1.1rem;
      cursor: pointer;
      min-height: 52px;
      transition: all 0.25s;
      box-shadow: 0 8px 25px rgba(252, 185, 0, 0.35);
      margin-top: 1rem;
    }

    .btn-submit:hover {
      transform: translateY(-2px);
      box-shadow: 0 12px 30px rgba(252, 185, 0, 0.5);
    }
  </style>
</head>
<body>
<?php echo $_nav_html; ?>

<div class="content-wrapper" style="max-width: 1100px; margin: 2rem auto; padding: 0 1rem;">
  
  <div class="form-hero">
    <div>
      <h1>✏️ Edit Product Listing</h1>
      <p>Updating: <?= htmlspecialchars($product['title']) ?></p>
    </div>
    <a href="products.php" style="display:inline-flex; align-items:center; gap:6px; background:rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.15); color:#fff; padding:0.6rem 1.2rem; border-radius:10px; text-decoration:none; font-size:0.85rem; font-weight:700;">
      ← Back to Products List
    </a>
  </div>

  <div class="form-panel">
    <?php if($err): ?><div style="background:rgba(255,82,82,0.12); color:#ff5252; border:1px solid rgba(255,82,82,0.3); padding:0.9rem 1.2rem; border-radius:12px; margin-bottom:1.5rem; font-weight:600;">⚠️ <?= htmlspecialchars($err) ?></div><?php endif; ?>
    <?php if($msg): ?><div style="background:rgba(0,230,118,0.12); color:#00e676; border:1px solid rgba(0,230,118,0.3); padding:0.9rem 1.2rem; border-radius:12px; margin-bottom:1.5rem; font-weight:600;">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>

    <!-- Manage Existing Images -->
    <div style="margin-bottom: 1.5rem;">
      <label style="display:block; font-size:0.76rem; font-weight:700; color:#94a3b8; text-transform:uppercase; margin-bottom:0.6rem;">
        Current Product Images
      </label>
      <?php if(empty($images)): ?>
        <p style="font-size:0.85rem; color:#64748b; padding:0.5rem 0;">No images uploaded yet.</p>
      <?php else: ?>
        <div class="images-manager">
          <?php foreach($images as $img): ?>
            <div class="img-manage-card <?= $img['is_thumbnail'] ? 'thumbnail' : '' ?>">
              <?php if($img['is_thumbnail']): ?>
                <span class="thumb-indicator">Thumbnail</span>
              <?php endif; ?>
              <img src="/uploads/partners/<?= htmlspecialchars($img['image_url']) ?>" alt="Product Image"/>
              <div class="img-actions-row">
                <?php if(!$img['is_thumbnail']): ?>
                  <a href="product_edit.php?id=<?= $product_id ?>&set_thumb=<?= $img['id'] ?>" style="font-size:0.75rem; color:var(--gold); text-decoration:none; font-weight:700;">★ Thumb</a>
                <?php else: ?>
                  <span style="font-size:0.72rem; color:var(--gold); font-weight:800;">Active</span>
                <?php endif; ?>
                <a href="product_edit.php?id=<?= $product_id ?>&delete_image=<?= $img['id'] ?>" style="font-size:0.75rem; color:#ff5252; text-decoration:none; font-weight:700;" onclick="return confirm('Delete this image?');">✕ Delete</a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Edit Form -->
    <form id="edit-product-form" method="POST" enctype="multipart/form-data" action="product_edit.php?id=<?= $product['id'] ?>">
      
      <!-- 1. Type Switcher -->
      <label style="display:block; font-size:0.76rem; font-weight:700; color:#94a3b8; text-transform:uppercase; margin-bottom:0.6rem;">
        Listing Type *
      </label>
      <div class="type-switcher">
        <label class="type-pill <?= ($product['listing_type'] ?? 'product') === 'product' ? 'active' : '' ?>" id="pill-product" onclick="setType('product')">
          <input type="radio" name="listing_type" value="product" <?= ($product['listing_type'] ?? 'product') === 'product' ? 'checked' : '' ?>>
          <span>📦 Digital Asset</span>
        </label>
        <label class="type-pill <?= ($product['listing_type'] ?? 'product') === 'service' ? 'active' : '' ?>" id="pill-service" onclick="setType('service')">
          <input type="radio" name="listing_type" value="service" <?= ($product['listing_type'] ?? 'product') === 'service' ? 'checked' : '' ?>>
          <span>🤝 Service Gig</span>
        </label>
        <label class="type-pill <?= ($product['listing_type'] ?? 'product') === 'affiliate' ? 'active' : '' ?>" id="pill-affiliate" onclick="setType('affiliate')">
          <input type="radio" name="listing_type" value="affiliate" <?= ($product['listing_type'] ?? 'product') === 'affiliate' ? 'checked' : '' ?>>
          <span>🔗 Affiliate Link</span>
        </label>
      </div>

      <!-- 2. Basic Details -->
      <div class="field">
        <label>Listing Title *</label>
        <input type="text" name="title" value="<?= htmlspecialchars($product['title']) ?>" placeholder="e.g. NID Correction Service" required/>
      </div>

      <div class="field">
        <label>Description</label>
        <textarea name="description" rows="4" placeholder="Enter product/service details..."><?= htmlspecialchars($product['description'] ?: '') ?></textarea>
      </div>

      <div class="row-flex">
        <div class="field">
          <label>Price (in <?= htmlspecialchars($coin_name) ?>) <span style="font-size:0.68rem; color:#10b981; font-weight:normal; text-transform:none;">(Optional — Leave 0 or blank for FREE / Promo)</span></label>
          <input type="number" step="0.1" min="0" name="price" value="<?= htmlspecialchars($product['price']) ?>" placeholder="0.00 (Free / Promotional)"/>
        </div>
        <div class="field">
          <label>Category</label>
          <select name="category" id="category-select" data-selected="<?= htmlspecialchars($product['category']) ?>">
            <option value="General">General</option>
          </select>
        </div>
      </div>

      <!-- PRODUCT SPECIFIC FIELDS -->
      <div id="product-fields" style="display:block;">
        <div class="row-flex">
          <div class="field">
            <label>Stock Quantity (Leave blank for unlimited)</label>
            <input type="number" name="stock" value="<?= $product['stock'] !== -1 ? htmlspecialchars($product['stock']) : '' ?>" placeholder="Unlimited" />
          </div>
          <div class="field">
            <label>Delivery Method</label>
            <select name="shipping_type">
              <option value="digital" <?= ($product['shipping_type'] ?? 'digital') === 'digital' ? 'selected' : '' ?>>⚡ Instant Digital Delivery</option>
              <option value="physical" <?= ($product['shipping_type'] ?? 'digital') === 'physical' ? 'selected' : '' ?>>📦 Physical Parcel Courier</option>
            </select>
          </div>
        </div>
      </div>

      <!-- SERVICE SPECIFIC FIELDS -->
      <div id="service-fields" style="display:none;">
        <div class="field">
          <label>Estimated Delivery Time</label>
          <input type="text" name="estimated_time" value="<?= htmlspecialchars($product['estimated_time'] ?? '') ?>" placeholder="e.g. 24 Hours, 2-3 Business Days" />
        </div>
      </div>

      <!-- AFFILIATE SPECIFIC FIELDS -->
      <div id="affiliate-fields" style="display:<?= ($product['listing_type'] ?? '') === 'affiliate' ? 'block' : 'none' ?>;">
        <div class="field">
          <label>Affiliate / Referral Destination URL *</label>
          <input type="url" name="affiliate_url" id="input-affiliate-url" value="<?= htmlspecialchars($product['affiliate_url'] ?? '') ?>" placeholder="https://example.com/ref?id=yourcode" />
        </div>
        <div class="field">
          <label>Reward Action Instructions</label>
          <textarea name="affiliate_action" rows="2" placeholder="e.g. Click link, complete registration, and submit screenshot for Fast Points reward..."><?= htmlspecialchars($product['affiliate_action'] ?? '') ?></textarea>
        </div>
      </div>

      <!-- 3. CUSTOMER INFORMATION & DOCUMENT SUBMISSION SYSTEM -->
      <?php 
        $has_sub = !empty($product['require_submission']) || !empty($product['required_docs']) || !empty($product['submission_prompt']);
        $sub_req = !empty($product['submission_required']);
        $sub_type = $product['submission_type'] ?? 'text_and_files';
        $sub_prompt = !empty($product['submission_prompt']) ? $product['submission_prompt'] : ($product['required_docs'] ?? '');
      ?>
      <div class="submission-box" id="submission-box">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.8rem;">
          <div style="font-size:0.95rem; font-weight:800; color:#fff; display:flex; align-items:center; gap:8px;">
            <span>📑 Customer Document & Information Submission System</span>
          </div>
          <div style="display:flex; align-items:center; gap:0.6rem; cursor:pointer;" onclick="toggleSubmissionSystem()">
            <label class="switch">
              <input type="checkbox" name="require_submission" id="require_submission" <?= $has_sub ? 'checked' : '' ?> onchange="toggleSubmissionSystem()">
              <span class="slider"></span>
            </label>
            <span style="font-size:0.82rem; font-weight:700; color:#fff;" id="sub-toggle-label"><?= $has_sub ? 'Enabled for this Product' : 'Enable for this Product' ?></span>
          </div>
        </div>

        <div id="submission-config-area" style="display:<?= $has_sub ? 'block' : 'none' ?>; margin-top:1.2rem; border-top:1px dashed rgba(255,255,255,0.1); padding-top:1.2rem;">
          <div class="row-flex" style="margin-bottom:1rem;">
            <div>
              <label style="color:var(--gold);">Submission Requirement Level</label>
              <div style="display:flex; gap:1rem; background:rgba(0,0,0,0.3); padding:0.6rem 0.8rem; border-radius:10px;">
                <label style="margin:0; display:flex; align-items:center; gap:6px; cursor:pointer; font-size:0.82rem; font-weight:700; color:#fff;">
                  <input type="radio" name="submission_required" value="1" <?= $sub_req ? 'checked' : '' ?> style="accent-color:var(--gold);">
                  <span>⚠️ Must Submit (Mandatory)</span>
                </label>
                <label style="margin:0; display:flex; align-items:center; gap:6px; cursor:pointer; font-size:0.82rem; font-weight:700; color:#aaa;">
                  <input type="radio" name="submission_required" value="0" <?= !$sub_req ? 'checked' : '' ?> style="accent-color:var(--gold);">
                  <span>ℹ️ Optional</span>
                </label>
              </div>
            </div>

            <div>
              <label style="color:var(--gold);">Allowed Submission Format</label>
              <select name="submission_type" style="background:#111422; border:1px solid rgba(255,255,255,0.12); border-radius:10px; padding:0.6rem; color:#fff; font-size:0.85rem; width:100%;">
                <option value="text_and_files" <?= $sub_type === 'text_and_files' ? 'selected' : '' ?>>📄 Form Info & Document Attachments</option>
                <option value="files_only" <?= $sub_type === 'files_only' ? 'selected' : '' ?>>📁 Document / File Uploads Only</option>
                <option value="text_only" <?= $sub_type === 'text_only' ? 'selected' : '' ?>>📝 Text Information & IDs Only</option>
              </select>
            </div>
          </div>

          <div class="field" style="margin-bottom:0;">
            <label style="color:var(--gold);">Instructions / Required Documents Prompt for Customer</label>
            <textarea name="submission_prompt" rows="3" placeholder="e.g. Please submit your NID Number, Old Birth Registration Certificate copy..."><?= htmlspecialchars($sub_prompt) ?></textarea>
          </div>
        </div>
      </div>

      <!-- 4. Add More Images -->
      <div class="field">
        <label>Add More Product Images</label>
        <input type="file" name="images[]" id="images-input" accept="image/*" multiple style="background:rgba(0,0,0,0.3); border:1px solid rgba(255,255,255,0.12); border-radius:10px; padding:0.6rem; color:#fff; font-size:0.85rem;"/>
        <p style="font-size:0.75rem; color:var(--gold); margin-top:0.3rem;">★ Recommended: Square 1:1 ratio (600×600 px or larger)</p>
      </div>

      <!-- 5. Audio Preview / Voice Demo Track -->
      <div class="field" style="margin-top:1.5rem;">
        <label style="color:var(--teal); font-weight:800;">🎵 Audio Preview / Digital Track (MP3, WAV, FLAC — Max 50MB)</label>
        <?php if (!empty($product['audio_file'])): ?>
          <div style="background:rgba(0,230,118,0.06); border:1px solid rgba(0,230,118,0.3); border-radius:12px; padding:1rem; margin-bottom:1rem;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.5rem; flex-wrap:wrap; gap:0.5rem;">
              <span style="font-size:0.85rem; font-weight:700; color:#fff;">🎧 Current Attached Track: <strong style="color:var(--teal);"><?= htmlspecialchars($product['audio_file']) ?></strong></span>
              <a href="product_edit.php?id=<?= $product_id ?>&delete_audio=1" style="color:#ff5252; font-size:0.78rem; font-weight:700; text-decoration:none; background:rgba(255,82,82,0.15); padding:3px 8px; border-radius:6px;" onclick="return confirm('Remove this audio track?');">✕ Delete Audio</a>
            </div>
            <audio controls style="width:100%; border-radius:8px;">
              <source src="/uploads/audio/<?= htmlspecialchars($product['audio_file']) ?>">
              Your browser does not support audio playback.
            </audio>
          </div>
        <?php endif; ?>
        
        <div style="border:2px dashed rgba(0,230,118,0.25); border-radius:12px; padding:1rem; background:rgba(0,0,0,0.2); text-align:center;">
          <input type="file" name="audio_file" accept="audio/*" style="color:#fff; font-size:0.85rem; width:100%;"/>
          <p style="font-size:0.75rem; color:#94a3b8; margin-top:0.4rem;">Select an MP3/WAV/FLAC file to attach or replace the voice / music sample.</p>
        </div>
      </div>

      <!-- 5. Publishing Controls -->
      <div class="row-flex" style="align-items:center; margin-top:1.5rem;">
        <div class="field" style="margin-bottom:0;">
          <label>Schedule Publish Time</label>
          <input type="datetime-local" name="scheduled_at" value="<?= $product['scheduled_at'] ? date('Y-m-d\TH:i', strtotime($product['scheduled_at'])) : '' ?>"/>
        </div>
        <div style="display:flex; align-items:center; gap:0.8rem; background:rgba(0,0,0,0.25); padding:0.8rem 1.2rem; border-radius:12px; border:1px solid rgba(255,255,255,0.06); height:100%; box-sizing:border-box;">
          <label class="switch">
            <input type="checkbox" name="is_published" <?= $product['is_published'] ? 'checked' : '' ?>>
            <span class="slider"></span>
          </label>
          <span style="font-size:0.88rem; font-weight:700; color:#fff;">Published in Store</span>
        </div>
      </div>

      <button type="submit" class="btn-submit">💾 Save Product Updates</button>
    </form>
  </div>
</div>

<script>
function setType(type) {
    document.querySelectorAll('.type-pill').forEach(p => p.classList.remove('active'));
    const activePill = document.getElementById('pill-' + type);
    if (activePill) {
        activePill.classList.add('active');
        const radio = activePill.querySelector('input');
        if (radio) radio.checked = true;
    }

    const prodFields = document.getElementById('product-fields');
    const servFields = document.getElementById('service-fields');
    const affFields  = document.getElementById('affiliate-fields');

    if (prodFields) prodFields.style.display = (type === 'product') ? 'block' : 'none';
    if (servFields) servFields.style.display = (type === 'service') ? 'block' : 'none';
    if (affFields)  affFields.style.display  = (type === 'affiliate') ? 'block' : 'none';

    const catSelect = document.getElementById('category-select');
    const curSelected = catSelect.getAttribute('data-selected');
    const productCats = ["Subscriptions", "Gaming & Top-up", "Software & Keys", "Social Media", "Gift Cards", "General"];
    const serviceCats = ["Document Correction", "NID Services", "Passport & Visa", "Design & Dev", "SEO & Marketing", "General Service"];
    const affiliateCats = ["CPA Offers", "Referral Links", "Promotions", "App Installs", "General Affiliate"];

    let cats = productCats;
    if (type === 'service') cats = serviceCats;
    if (type === 'affiliate') cats = affiliateCats;

    if (catSelect) {
        catSelect.innerHTML = '';
        cats.forEach(c => {
            let opt = document.createElement('option');
            opt.value = c;
            opt.textContent = c;
            if (c === curSelected) opt.selected = true;
            catSelect.appendChild(opt);
        });
    }
}

function toggleSubmissionSystem() {
    const chk = document.getElementById('require_submission');
    const area = document.getElementById('submission-config-area');
    const lbl = document.getElementById('sub-toggle-label');

    if (chk && area) {
        area.style.display = chk.checked ? 'block' : 'none';
        if (lbl) lbl.textContent = chk.checked ? 'Enabled for this Product' : 'Enable for this Product';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    setType('<?= htmlspecialchars($product['listing_type'] ?? 'product') ?>');
});
</script>

<?php include __DIR__ . '/../includes/cropper_modal.php'; ?>
</body>
</html>
