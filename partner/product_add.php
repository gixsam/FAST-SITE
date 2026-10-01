<?php
// =========================================================================
// partner/product_add.php  –  Dynamic & Premium Add Product Listing Hub
// =========================================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['partner_id'])) {
    if (isset($_POST['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Session expired. Please log in again.']);
        exit;
    }
    header('Location: /user/login.php');
    exit;
}

$partner_id = (int)$_SESSION['partner_id'];
$coin_name  = getPartnerSetting('coin_name', 'Fast Points');

// Fetch partner info
$partner = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM partners WHERE id = ? LIMIT 1");
    $stmt->execute([$partner_id]);
    $partner = $stmt->fetch(PDO::FETCH_ASSOC) ?? [];
} catch (Exception $e) {}

$err = $msg = '';
$missing_fields = [];

// Form values initialization for preservation on error
$post_type           = trim($_POST['listing_type'] ?? 'product');
$post_title          = trim($_POST['title'] ?? '');
$post_description    = trim($_POST['description'] ?? '');
$post_price          = (isset($_POST['price']) && $_POST['price'] !== '') ? trim($_POST['price']) : '';
$post_category       = trim($_POST['category'] ?? 'General');
$post_stock          = trim($_POST['stock'] ?? '');
$post_shipping_type  = trim($_POST['shipping_type'] ?? 'digital');
$post_estimated_time = trim($_POST['estimated_time'] ?? '');
$post_affiliate_url  = trim($_POST['affiliate_url'] ?? '');
$post_affiliate_act  = trim($_POST['affiliate_action'] ?? '');
$post_req_sub        = isset($_POST['require_submission']) ? 1 : 0;
$post_sub_req        = isset($_POST['submission_required']) ? intval($_POST['submission_required']) : 1;
$post_sub_type       = trim($_POST['submission_type'] ?? 'text_and_files');
$post_sub_prompt     = trim($_POST['submission_prompt'] ?? '');
$post_scheduled_at   = trim($_POST['scheduled_at'] ?? '');
$post_is_published   = isset($_POST['is_published']) ? 1 : (empty($_POST) ? 1 : 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $is_ajax = isset($_POST['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
    
    $listing_type = $post_type;
    $title        = $post_title;
    $description  = $post_description;
    $price        = ($post_price !== '') ? floatval($post_price) : 0.0;
    if ($price < 0) $price = 0.0;
    $category     = $post_category;
    $scheduled_at = $post_scheduled_at;
    $is_published = $post_is_published;
    
    // Inventory & Delivery
    $stock          = ($post_stock !== '') ? intval($post_stock) : -1;
    $shipping_type  = $post_shipping_type;
    $estimated_time = $post_estimated_time;
    
    // Affiliate Fields
    $affiliate_url    = $post_affiliate_url;
    $affiliate_action = $post_affiliate_act;
    
    // Customer Document & Info Submission System (Summation System)
    $require_submission  = $post_req_sub;
    $submission_required = $post_sub_req;
    $submission_type     = $post_sub_type;
    $submission_prompt   = $post_sub_prompt;
    $required_docs       = $submission_prompt;

    // Validation - Price is OPTIONAL (defaults to 0 for FREE/Promo)
    if (empty($title)) {
        $missing_fields[] = 'Listing Title';
    }
    if ($listing_type === 'affiliate' && empty($affiliate_url)) {
        $missing_fields[] = 'Affiliate Destination URL';
    }
    if ($require_submission && empty($submission_prompt)) {
        $missing_fields[] = 'Customer Submission Instructions Prompt';
    }

    // Mandatory Product Photo Validation (Phase 103)
    $has_image_b64 = !empty($_POST['images_b64']) && is_array($_POST['images_b64']) && count(array_filter($_POST['images_b64'])) > 0;
    $has_uploaded_img = !empty($_POST['uploaded_images']) && is_array($_POST['uploaded_images']) && count(array_filter($_POST['uploaded_images'])) > 0;
    $has_file_upload = isset($_FILES['images']) && !empty($_FILES['images']['name'][0]) && $_FILES['images']['error'][0] === UPLOAD_ERR_OK;

    if (!$has_image_b64 && !$has_uploaded_img && !$has_file_upload) {
        $missing_fields[] = 'Product Photo (কমপক্ষে একটি স্পষ্ট ছবি আপলোড করা আবশ্যক)';
    }

    if (!empty($missing_fields)) {
        $err = 'Please complete the required missing field(s): ' . implode(', ', $missing_fields);
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $err, 'missing' => $missing_fields]);
            exit;
        }
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
            $pdo->exec("CREATE TABLE IF NOT EXISTS partner_product_images (
                id INT AUTO_INCREMENT PRIMARY KEY,
                product_id INT NOT NULL,
                image_url VARCHAR(255) NOT NULL,
                is_thumbnail TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (Exception $e) {}

        // Wrap in safe transaction block
        try {
            if (!$pdo->inTransaction()) {
                $pdo->beginTransaction();
            }

            // 1. Insert into partner_products
            $stmt = $pdo->prepare("INSERT INTO partner_products 
                (partner_id, title, description, price, category, scheduled_at, is_published, listing_type, stock, shipping_type, estimated_time, required_docs, require_submission, submission_type, submission_required, submission_prompt, affiliate_url, affiliate_action) 
                VALUES (:partner_id, :title, :description, :price, :category, :scheduled_at, :is_published, :listing_type, :stock, :shipping_type, :estimated_time, :required_docs, :require_sub, :sub_type, :sub_req, :sub_prompt, :aff_url, :aff_act)");
            
            $stmt->execute([
                ':partner_id'    => $partner_id,
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
                ':aff_url'       => $affiliate_url,
                ':aff_act'       => $affiliate_action
            ]);

            $product_id = (int)$pdo->lastInsertId();
            $upload_dir = __DIR__ . '/../uploads/partners/';
            $savedCount = 0;

            // 2. Handle Product Images
            // 2a. Pre-uploaded or provided image array
            $uploaded_images = $_POST['uploaded_images'] ?? [];
            if (!empty($uploaded_images) && is_array($uploaded_images)) {
                $img_stmt = $pdo->prepare("INSERT INTO partner_product_images (product_id, image_url, is_thumbnail) VALUES (?, ?, ?)");
                foreach ($uploaded_images as $index => $img_path) {
                    $is_thumb = ($savedCount === 0) ? 1 : 0;
                    $img_stmt->execute([$product_id, basename($img_path), $is_thumb]);
                    $savedCount++;
                }
            }

            // 2b. Base64 images from Crop Engine
            $b64_images = $_POST['images_b64'] ?? [];
            if (!is_array($b64_images)) $b64_images = [];

            foreach ($b64_images as $i => $b64) {
                if (!preg_match('/^data:image\/(jpeg|png|webp|gif);base64,/', $b64, $typeMatch)) continue;
                $imgType = $typeMatch[1];
                $imgData = base64_decode(preg_replace('/^data:image\/[a-z]+;base64,/', '', $b64));
                if (!$imgData || strlen($imgData) > 8 * 1024 * 1024) continue;

                $filename = 'prod_' . $product_id . '_b64_' . $i . '_' . time() . '.' . ($imgType === 'jpeg' ? 'jpg' : $imgType);
                if (file_put_contents($upload_dir . $filename, $imgData) !== false) {
                    @chmod($upload_dir . $filename, 0644);
                    $is_thumb = ($savedCount === 0) ? 1 : 0;
                    try {
                        $pdo->prepare("INSERT INTO partner_product_images (product_id, image_url, is_thumbnail) VALUES (?, ?, ?)")
                            ->execute([$product_id, $filename, $is_thumb]);
                        if ($is_thumb) {
                            $pdo->prepare("UPDATE partner_products SET image = ?, thumbnail = ? WHERE id = ?")
                                ->execute(['uploads/partners/' . $filename, 'uploads/partners/' . $filename, $product_id]);
                        }
                        $savedCount++;
                    } catch (Exception $e) {}
                }
            }

            // 2c. Direct File Upload fallback
            if ($savedCount === 0 && isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
                $files = $_FILES['images'];
                $count = count($files['name']);
                
                for ($i = 0; $i < $count; $i++) {
                    if ($files['error'][$i] === UPLOAD_ERR_OK) {
                        $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
                        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                            $filename = 'prod_' . $product_id . '_' . $i . '_' . time() . '.' . $ext;
                            if (move_uploaded_file($files['tmp_name'][$i], $upload_dir . $filename)) {
                                @chmod($upload_dir . $filename, 0644);
                                $is_thumb = ($savedCount === 0) ? 1 : 0;
                                try {
                                    $pdo->prepare("INSERT INTO partner_product_images (product_id, image_url, is_thumbnail) VALUES (?, ?, ?)")
                                        ->execute([$product_id, $filename, $is_thumb]);
                                    if ($is_thumb) {
                                        $pdo->prepare("UPDATE partner_products SET image = ?, thumbnail = ? WHERE id = ?")
                                            ->execute(['uploads/partners/' . $filename, 'uploads/partners/' . $filename, $product_id]);
                                    }
                                    $savedCount++;
                                } catch (Exception $e) {}
                            }
                        }
                    }
                }
            }

            // 3. Handle Audio File Upload
            if (isset($_FILES['audio_file']) && $_FILES['audio_file']['error'] === UPLOAD_ERR_OK) {
                $audio_upload_dir = __DIR__ . '/../uploads/audio/';
                $allowed_audio = ['mp3', 'wav', 'ogg', 'flac', 'm4a', 'aac'];
                $audio_ext = strtolower(pathinfo($_FILES['audio_file']['name'], PATHINFO_EXTENSION));
                if (in_array($audio_ext, $allowed_audio) && $_FILES['audio_file']['size'] <= 50 * 1024 * 1024) {
                    $audio_name = 'audio_prod_' . $product_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $audio_ext;
                    if (move_uploaded_file($_FILES['audio_file']['tmp_name'], $audio_upload_dir . $audio_name)) {
                        try {
                            $pdo->prepare("UPDATE partner_products SET audio_file = ? WHERE id = ?")
                                ->execute([$audio_name, $product_id]);
                        } catch (Exception $ex) {}
                    }
                }
            }

            if ($pdo->inTransaction()) {
                $pdo->commit();
            }

            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'status' => 'success',
                    'success' => true,
                    'message' => 'Product published successfully!',
                    'product_id' => $product_id,
                    'redirect' => 'products.php?msg=Product+added+successfully',
                    'redirect_url' => 'dashboard.php?tab=products&msg=published'
                ]);
                exit;
            } else {
                echo "<!DOCTYPE html><html><head><meta http-equiv='refresh' content='0;url=products.php?msg=Product+added+successfully'><script>window.location.href='products.php?msg=Product+added+successfully';</script></head><body>Redirecting to products...</body></html>";
                exit;
            }

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $err = 'Error uploading product: ' . $e->getMessage();
            if ($is_ajax) {
                http_response_code(500);
                header('Content-Type: application/json');
                echo json_encode([
                    'status' => 'error',
                    'success' => false,
                    'error' => $err,
                    'message' => $err
                ]);
                exit;
            }
        }
    }
}

// Start visual template
ob_start();
require_once 'nav.php';
$_nav_html = ob_get_clean();

if ($partner['status'] === 'pending') {
    echo "<div class='main-content'><div class='box' style='text-align:center; padding:3rem 1rem;'><h2>⏳ Shop Under Review</h2><p style='color:#aaa;'>Your shop is currently pending admin approval. You can upload products once your shop is approved.</p><br><a href='dashboard.php' class='btn' style='background:var(--brand,#fcb900); color:#000; padding:0.6rem 1.5rem; font-weight:700; text-decoration:none; border-radius:8px;'>Back to Dashboard</a></div></div>";
    include __DIR__ . '/../includes/cropper_modal.php';
    echo "</body></html>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Add Product &mdash; Fast Site Partner Hub</title>
  <link rel="stylesheet" href="../assets/css/admin.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Oswald:wght@500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --gold: #fcb900;
      --gold-glow: rgba(252, 185, 0, 0.25);
      --teal: #00e676;
      --teal-glow: rgba(0, 230, 118, 0.25);
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

    .form-hero p {
      margin: 0.3rem 0 0 0;
      color: #94a3b8;
      font-size: 0.88rem;
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
      padding: 0.8rem;
      border-radius: 10px;
      font-size: 0.85rem;
      font-weight: 700;
      color: #94a3b8;
      border: 1px solid transparent;
      transition: all 0.25s ease;
      user-select: none;
    }

    .type-pill input {
      display: none;
    }

    .type-pill:hover {
      background: rgba(255, 255, 255, 0.04);
      color: #fff;
    }

    .type-pill.active {
      background: rgba(252, 185, 0, 0.15);
      color: var(--gold);
      border-color: rgba(252, 185, 0, 0.4);
      box-shadow: 0 0 20px rgba(252, 185, 0, 0.2);
    }

    .field {
      margin-bottom: 1.4rem;
      position: relative;
    }

    .field label {
      display: block;
      font-size: 0.76rem;
      font-weight: 700;
      color: #94a3b8;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      margin-bottom: 0.5rem;
    }

    .field input,
    .field select,
    .field textarea {
      width: 100%;
      background: rgba(10, 12, 20, 0.8);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 12px;
      padding: 0.85rem 1.1rem;
      color: #fff;
      font-size: 0.92rem;
      font-family: inherit;
      box-sizing: border-box;
      transition: all 0.2s ease;
    }

    .field input:focus,
    .field select:focus,
    .field textarea:focus {
      outline: none;
      border-color: var(--gold);
      box-shadow: 0 0 15px rgba(252, 185, 0, 0.25);
      background: rgba(14, 16, 26, 0.95);
    }

    .field-error {
      border-color: #ff5252 !important;
      box-shadow: 0 0 15px rgba(255, 82, 82, 0.45) !important;
      animation: fieldShake 0.4s ease-in-out;
    }

    @keyframes fieldShake {
      0%, 100% { transform: translateX(0); }
      20%, 60% { transform: translateX(-6px); }
      40%, 80% { transform: translateX(6px); }
    }

    .field-error-msg {
      color: #ff5252;
      font-size: 0.75rem;
      font-weight: 700;
      margin-top: 0.35rem;
      display: flex;
      align-items: center;
      gap: 4px;
    }

    .row-flex {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1.2rem;
    }

    @media (max-width: 700px) {
      .row-flex { grid-template-columns: 1fr; }
      .type-switcher { grid-template-columns: 1fr; }
    }

    /* Submission System Box */
    .submission-box {
      background: rgba(252, 185, 0, 0.04);
      border: 1px solid rgba(252, 185, 0, 0.3);
      border-radius: 16px;
      padding: 1.4rem;
      margin-bottom: 1.8rem;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.25);
      transition: all 0.3s ease;
    }

    .submission-box.inactive {
      opacity: 0.6;
      border-color: rgba(255, 255, 255, 0.08);
      background: rgba(0, 0, 0, 0.2);
    }

    .submission-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 0.8rem;
    }

    .sub-title {
      font-size: 0.95rem;
      font-weight: 800;
      color: var(--gold);
      display: flex;
      align-items: center;
      gap: 8px;
    }

    /* Switch Component */
    .switch-wrap {
      display: flex;
      align-items: center;
      gap: 0.8rem;
      cursor: pointer;
    }

    .switch {
      position: relative;
      display: inline-block;
      width: 48px;
      height: 26px;
      flex-shrink: 0;
    }

    .switch input {
      opacity: 0;
      width: 0;
      height: 0;
    }

    .slider {
      position: absolute;
      cursor: pointer;
      inset: 0;
      background-color: rgba(255, 255, 255, 0.15);
      border-radius: 34px;
      transition: 0.3s;
      border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .slider:before {
      position: absolute;
      content: "";
      height: 18px;
      width: 18px;
      left: 3px;
      bottom: 3px;
      background-color: white;
      border-radius: 50%;
      transition: 0.3s;
    }

    input:checked + .slider {
      background-color: var(--gold);
      border-color: var(--gold);
    }

    input:checked + .slider:before {
      transform: translateX(22px);
      background-color: #000;
    }

    /* Dropzone */
    .gc-dropzone {
      border: 2px dashed rgba(252, 185, 0, 0.35);
      border-radius: 16px;
      padding: 2.2rem 1.5rem;
      text-align: center;
      background: rgba(252, 185, 0, 0.02);
      cursor: pointer;
      transition: all 0.25s;
      position: relative;
    }

    .gc-dropzone:hover {
      background: rgba(252, 185, 0, 0.06);
      border-color: var(--gold);
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(252, 185, 0, 0.15);
    }

    .gc-dropzone-icon {
      font-size: 2.6rem;
      display: block;
      margin-bottom: 0.6rem;
    }

    .gc-browse-btn {
      display: inline-block;
      margin-top: 0.8rem;
      background: linear-gradient(135deg, var(--gold), #ff9100);
      color: #000;
      font-weight: 800;
      font-size: 0.82rem;
      padding: 0.55rem 1.4rem;
      border-radius: 50px;
      pointer-events: none;
      box-shadow: 0 4px 12px rgba(252,185,0,0.3);
    }

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

    .err {
      background: rgba(255, 82, 82, 0.12);
      color: #ff5252;
      border: 1px solid rgba(255, 82, 82, 0.3);
      padding: 0.9rem 1.2rem;
      border-radius: 12px;
      font-size: 0.88rem;
      margin-bottom: 1.5rem;
      font-weight: 600;
    }

    .success {
      background: rgba(0, 230, 118, 0.12);
      color: #00e676;
      border: 1px solid rgba(0, 230, 118, 0.3);
      padding: 0.9rem 1.2rem;
      border-radius: 12px;
      font-size: 0.88rem;
      margin-bottom: 1.5rem;
      font-weight: 600;
    }

    /* =========================================================
       ANIMATED 1-TO-100% EXECUTIVE HUD PROGRESS LOADER MODAL
       ========================================================= */
    #upload-hud-modal {
      position: fixed;
      inset: 0;
      z-index: 999999;
      background: rgba(4, 6, 12, 0.88);
      backdrop-filter: blur(18px);
      display: none;
      align-items: center;
      justify-content: center;
      font-family: 'Inter', sans-serif;
      opacity: 0;
      transition: opacity 0.3s ease;
    }

    #upload-hud-modal.active {
      display: flex;
      opacity: 1;
    }

    .hud-box {
      background: radial-gradient(circle at 50% 0%, #1c2237 0%, #0d101d 100%);
      border: 1px solid rgba(252, 185, 0, 0.4);
      border-radius: 28px;
      padding: 2.5rem 2rem;
      width: 420px;
      max-width: 90vw;
      text-align: center;
      box-shadow: 0 25px 60px rgba(0, 0, 0, 0.8), 0 0 40px rgba(252, 185, 0, 0.2);
      transform: scale(0.9);
      transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    #upload-hud-modal.active .hud-box {
      transform: scale(1);
    }

    /* Circular SVG Ring */
    .hud-circle-wrap {
      position: relative;
      width: 160px;
      height: 160px;
      margin: 0 auto 1.5rem auto;
    }

    .hud-svg {
      transform: rotate(-90deg);
      width: 160px;
      height: 160px;
    }

    .hud-bg-ring {
      fill: none;
      stroke: rgba(255, 255, 255, 0.06);
      stroke-width: 10;
    }

    .hud-progress-ring {
      fill: none;
      stroke: url(#hudGradient);
      stroke-width: 10;
      stroke-linecap: round;
      stroke-dasharray: 440;
      stroke-dashoffset: 440;
      transition: stroke-dashoffset 0.15s ease-out;
      filter: drop-shadow(0 0 10px rgba(252, 185, 0, 0.6));
    }

    .hud-score-center {
      position: absolute;
      inset: 0;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
    }

    .hud-score-num {
      font-family: 'Oswald', sans-serif;
      font-size: 2.8rem;
      font-weight: 700;
      color: #fff;
      line-height: 1;
      text-shadow: 0 0 20px rgba(252, 185, 0, 0.5);
    }

    .hud-score-pct {
      font-size: 1.1rem;
      color: var(--gold);
      margin-left: 2px;
    }

    .hud-status-title {
      font-family: 'Oswald', sans-serif;
      font-size: 1.35rem;
      color: #fff;
      text-transform: uppercase;
      letter-spacing: 0.04em;
      margin: 0 0 0.4rem 0;
    }

    .hud-status-desc {
      color: #94a3b8;
      font-size: 0.85rem;
      line-height: 1.45;
      min-height: 38px;
      margin-bottom: 1.2rem;
    }

    /* Pulse radar ring */
    .hud-radar-glow {
      position: absolute;
      inset: -10px;
      border-radius: 50%;
      border: 1px solid rgba(252, 185, 0, 0.2);
      animation: radarPulse 2s infinite ease-out;
      pointer-events: none;
    }

    @keyframes radarPulse {
      0% { transform: scale(0.95); opacity: 0.8; }
      100% { transform: scale(1.3); opacity: 0; }
    }
  </style>
</head>
<body>
<?php echo $_nav_html; ?>

<div class="content-wrapper" style="max-width: 1100px; margin: 2rem auto; padding: 0 1rem;">
  
  <!-- Hero Section -->
  <div class="form-hero">
    <div>
      <h1>⚡ Add New Product or Service</h1>
      <p>Create digital products, professional services, or promotional free items for your storefront</p>
    </div>
    <a href="products.php" style="display:inline-flex; align-items:center; gap:6px; background:rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.15); color:#fff; padding:0.6rem 1.2rem; border-radius:10px; text-decoration:none; font-size:0.85rem; font-weight:700; transition:0.2s;">
      ← Manage Products
    </a>
  </div>

  <div class="form-panel">
    <div id="client-error-banner" class="err" style="display: <?= $err ? 'block' : 'none' ?>;">
      ⚠️ <span id="error-banner-text"><?= htmlspecialchars($err) ?></span>
    </div>
    <?php if($msg): ?><div class="success">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>

    <form id="add-product-form" method="POST" enctype="multipart/form-data" action="product_add.php" onsubmit="return handleProductFormSubmit(event)">
      
      <!-- 1. Listing Type Switcher -->
      <label style="display:block; font-size:0.76rem; font-weight:700; color:#94a3b8; text-transform:uppercase; margin-bottom:0.6rem; letter-spacing:0.06em;">
        Select Listing Type *
      </label>
      <div class="type-switcher">
        <label class="type-pill <?= $post_type === 'product' ? 'active' : '' ?>" id="pill-product" onclick="setType('product')">
          <input type="radio" name="listing_type" value="product" <?= $post_type === 'product' ? 'checked' : '' ?>>
          <span>📦 Digital Asset / Code</span>
        </label>
        <label class="type-pill <?= $post_type === 'service' ? 'active' : '' ?>" id="pill-service" onclick="setType('service')">
          <input type="radio" name="listing_type" value="service" <?= $post_type === 'service' ? 'checked' : '' ?>>
          <span>🤝 Professional Service</span>
        </label>
        <label class="type-pill <?= $post_type === 'affiliate' ? 'active' : '' ?>" id="pill-affiliate" onclick="setType('affiliate')">
          <input type="radio" name="listing_type" value="affiliate" <?= $post_type === 'affiliate' ? 'checked' : '' ?>>
          <span>🔗 External Affiliate Link</span>
        </label>
      </div>

      <!-- 2. Basic Information -->
      <div class="field" id="field-title-wrap">
        <label>Listing Title *</label>
        <input type="text" name="title" id="input-title" placeholder="e.g. NID Age Adjustment / Netflix 1-Month / Android App Source" value="<?= htmlspecialchars($post_title) ?>" oninput="clearFieldError(this)"/>
        <div class="field-error-msg" id="err-title" style="display:none;">⚠️ Please enter a listing title</div>
      </div>

      <div class="field">
        <label>Detailed Description & Customer Delivery Instructions</label>
        <textarea name="description" id="input-description" rows="4" placeholder="Explain what the customer will receive, delivery timelines, terms of service..."><?= htmlspecialchars($post_description) ?></textarea>
      </div>

      <div class="row-flex">
        <div class="field">
          <label>Price (in <?= htmlspecialchars($coin_name) ?>) <span style="font-size:0.68rem; color:#10b981; font-weight:normal; text-transform:none;">(Optional — Leave 0 or blank for FREE / Promo)</span></label>
          <input type="number" step="0.1" min="0" name="price" id="input-price" placeholder="0.00 (Free / Promotional)" value="<?= htmlspecialchars($post_price) ?>"/>
        </div>
        <div class="field">
          <label>Category</label>
          <select name="category" id="category-select">
            <option value="General" <?= $post_category === 'General' ? 'selected' : '' ?>>General</option>
            <option value="Subscriptions" <?= $post_category === 'Subscriptions' ? 'selected' : '' ?>>Subscriptions</option>
            <option value="Gaming & Top-up" <?= $post_category === 'Gaming & Top-up' ? 'selected' : '' ?>>Gaming & Top-up</option>
            <option value="Software & Keys" <?= $post_category === 'Software & Keys' ? 'selected' : '' ?>>Software & Keys</option>
            <option value="Document Correction" <?= $post_category === 'Document Correction' ? 'selected' : '' ?>>Document Correction</option>
            <option value="NID Services" <?= $post_category === 'NID Services' ? 'selected' : '' ?>>NID Services</option>
            <option value="Passport & Visa" <?= $post_category === 'Passport & Visa' ? 'selected' : '' ?>>Passport & Visa</option>
          </select>
        </div>
      </div>

      <!-- PRODUCT SPECIFIC FIELDS -->
      <div id="product-fields" style="display:<?= $post_type === 'product' ? 'block' : 'none' ?>;">
        <div class="row-flex">
          <div class="field">
            <label>Stock Quantity (Leave blank for unlimited)</label>
            <input type="number" name="stock" placeholder="e.g. 100" value="<?= htmlspecialchars($post_stock) ?>"/>
          </div>
          <div class="field">
            <label>Delivery Method</label>
            <select name="shipping_type">
              <option value="digital" <?= $post_shipping_type === 'digital' ? 'selected' : '' ?>>⚡ Instant Digital Delivery (Email / Chat / Download)</option>
              <option value="physical" <?= $post_shipping_type === 'physical' ? 'selected' : '' ?>>📦 Physical Parcel Courier (Requires Address)</option>
            </select>
          </div>
        </div>
      </div>

      <!-- SERVICE SPECIFIC FIELDS -->
      <div id="service-fields" style="display:<?= $post_type === 'service' ? 'block' : 'none' ?>;">
        <div class="field">
          <label>Estimated Service Completion Time</label>
          <input type="text" name="estimated_time" placeholder="e.g. 24 Hours, 2-3 Business Days" value="<?= htmlspecialchars($post_estimated_time) ?>"/>
        </div>
      </div>

      <!-- AFFILIATE SPECIFIC FIELDS -->
      <div id="affiliate-fields" style="display:<?= $post_type === 'affiliate' ? 'block' : 'none' ?>;">
        <div class="field" id="field-aff-url-wrap">
          <label>Affiliate / Referral Destination URL *</label>
          <input type="url" name="affiliate_url" id="input-affiliate-url" placeholder="https://example.com/ref?id=yourcode" value="<?= htmlspecialchars($post_affiliate_url) ?>" oninput="clearFieldError(this)"/>
          <div class="field-error-msg" id="err-aff-url" style="display:none;">⚠️ Please provide the destination link</div>
        </div>
        <div class="field">
          <label>Reward Action Instructions</label>
          <textarea name="affiliate_action" rows="2" placeholder="e.g. Click link, complete registration, and submit screenshot for Fast Points reward..."><?= htmlspecialchars($post_affiliate_act) ?></textarea>
        </div>
      </div>

      <!-- 3. INSTRUCTIONS & REQUIREMENTS FOR BUYER -->
      <div class="submission-box <?= $post_req_sub ? '' : 'inactive' ?>" id="submission-box">
        <div class="submission-header">
          <div class="sub-title">
            <span>📑 Instructions &amp; Requirements for Buyer</span>
            <span style="font-size:0.72rem; color:var(--teal); background:rgba(0,230,118,0.12); padding:2px 8px; border-radius:50px; border:1px solid rgba(0,230,118,0.3);">
              Shop Feature
            </span>
          </div>
          <div class="switch-wrap" onclick="toggleSubmissionSystem()">
            <label class="switch">
              <input type="checkbox" name="require_submission" id="require_submission" <?= $post_req_sub ? 'checked' : '' ?> onchange="toggleSubmissionSystem()">
              <span class="slider"></span>
            </label>
            <span style="font-size:0.82rem; font-weight:700; color:#fff;" id="sub-toggle-label"><?= $post_req_sub ? 'Enabled for this Product' : 'Enable for this Product' ?></span>
          </div>
        </div>

        <p style="font-size:0.8rem; color:#94a3b8; margin:0.6rem 0 0 0;">
          Need details or documents from the buyer (e.g. account ID, old certificates, photos) to fulfill this order? Turn this on.
        </p>

        <!-- Expandable Configuration -->
        <div id="submission-config-area" style="display:<?= $post_req_sub ? 'block' : 'none' ?>; margin-top:1.2rem; border-top:1px dashed rgba(255,255,255,0.1); padding-top:1.2rem;">
          
          <div class="row-flex" style="margin-bottom:1rem;">
            <div>
              <label style="display:block; font-size:0.75rem; font-weight:700; color:var(--gold); text-transform:uppercase; margin-bottom:0.4rem;">
                Submission Requirement Level
              </label>
              <div style="display:flex; gap:1rem; background:rgba(0,0,0,0.3); padding:0.6rem 0.8rem; border-radius:10px;">
                <label style="margin:0; display:flex; align-items:center; gap:6px; cursor:pointer; font-size:0.82rem; font-weight:700; color:#fff;">
                  <input type="radio" name="submission_required" value="1" <?= $post_sub_req === 1 ? 'checked' : '' ?> style="accent-color:var(--gold);">
                  <span>⚠️ Must Submit (Mandatory)</span>
                </label>
                <label style="margin:0; display:flex; align-items:center; gap:6px; cursor:pointer; font-size:0.82rem; font-weight:700; color:#aaa;">
                  <input type="radio" name="submission_required" value="0" <?= $post_sub_req === 0 ? 'checked' : '' ?> style="accent-color:var(--gold);">
                  <span>ℹ️ Optional</span>
                </label>
              </div>
            </div>

            <div>
              <label style="display:block; font-size:0.75rem; font-weight:700; color:var(--gold); text-transform:uppercase; margin-bottom:0.4rem;">
                Allowed Submission Format
              </label>
              <select name="submission_type" style="background:#111422; border:1px solid rgba(255,255,255,0.12); border-radius:10px; padding:0.6rem; color:#fff; font-size:0.85rem; width:100%;">
                <option value="text_and_files" <?= $post_sub_type === 'text_and_files' ? 'selected' : '' ?>>📄 Form Info & Document Attachments (NID, Photos, PDFs)</option>
                <option value="files_only" <?= $post_sub_type === 'files_only' ? 'selected' : '' ?>>📁 Document / File Uploads Only</option>
                <option value="text_only" <?= $post_sub_type === 'text_only' ? 'selected' : '' ?>>📝 Text Information & ID Numbers Only</option>
              </select>
            </div>
          </div>

          <div class="field" id="field-prompt-wrap" style="margin-bottom:0;">
            <label style="color:var(--gold);">Instructions &amp; Requirements for Buyer *</label>
            <textarea name="submission_prompt" id="input-submission-prompt" rows="3" placeholder="e.g. Please submit: 1. National ID (NID) Number, 2. Father's Name, 3. Upload clear photo/scan of old birth registration certificate." oninput="clearFieldError(this)"><?= htmlspecialchars($post_sub_prompt) ?></textarea>
            <div class="field-error-msg" id="err-prompt" style="display:none;">⚠️ Please explain what documents/info the buyer must provide</div>
          </div>
        </div>
      </div>

      <!-- 4. Cover Images Dropzone — Android APK WebView Fix: universal image/* accept and clean touch overlay -->
      <div class="field">
        <label>Cover Images (Up to 5) — First image = Storefront Thumbnail ★</label>

        <div class="gc-dropzone" id="gc-dropzone" style="position:relative; overflow:hidden; cursor:pointer;">
          <!-- Universal image/* accept string ensures all Android photo pickers & cameras open without filtering -->
          <input type="file" id="images-input" name="images[]" accept="image/*" multiple
            style="position:absolute; top:0; left:0; width:100%; height:100%; opacity:0; cursor:pointer; z-index:20; margin:0; padding:0;"/>
          <!-- Visual content sits strictly below with pointer-events:none so native touch goes 100% to input -->
          <div style="pointer-events:none;">
            <span class="gc-dropzone-icon">🖼️</span>
            <div style="color:#fff; font-size:0.95rem; font-weight:800; margin-bottom:4px;">
              Click or drag product photos here
            </div>
            <div style="color:#94a3b8; font-size:0.8rem; line-height:1.4;">
              Cropper engine opens automatically — adjust 1:1 square ratio for maximum store visibility.<br>
              <span style="color:var(--gold); font-weight:700;">Recommended: 600×600 px or larger (JPG, PNG, WEBP)</span>
            </div>
            <div style="margin-top:0.8rem;">
              <span class="gc-browse-btn">📁 Choose Photos from Device</span>
            </div>
          </div>
        </div>

        <!-- Live Preview Grid -->
        <div class="gc-preview-grid" id="gc-preview-grid"></div>
        <!-- Hidden base64 inputs for cropped images -->
        <div id="gc-hidden-inputs" style="display:none;"></div>
        <div class="field-error-msg" id="err-images" style="display:none; color:#ff5252; font-size:0.82rem; font-weight:700; margin-top:8px; align-items:center; gap:6px;">
          ⚠️ অনুগ্রহ করে পণ্যের অন্তত একটি স্পষ্ট ছবি আপলোড করুন (Please upload at least 1 product photo)
        </div>
      </div>

      <!-- 5. Audio Upload for Digital Tracks — Android APK WebView Fix -->
      <div class="field" id="audio-upload-field" style="display:<?= $post_type === 'product' ? 'block' : 'none' ?>;">
        <label>🎵 Optional Audio Preview / Digital Track (MP3, WAV, FLAC — Max 50MB)</label>
        <div style="border:2px dashed rgba(0,230,118,0.25); border-radius:14px; padding:0; background:rgba(0,230,118,0.02); text-align:center; overflow:hidden;">
          <!-- Audio drop label shows when no file selected -->
          <div id="audio-drop-label" style="position:relative; padding:1.5rem 1rem; cursor:pointer;">
            <!-- Transparent overlay input with universal audio/* accept -->
            <input type="file" name="audio_file" id="audio-file-input" accept="audio/*"
              style="position:absolute; top:0; left:0; width:100%; height:100%; opacity:0; cursor:pointer; z-index:20; margin:0; padding:0;"/>
            <!-- Visual content with pointer-events:none -->
            <div style="pointer-events:none;">
              <div style="font-size:2.2rem; margin-bottom:0.5rem;">🎵</div>
              <div style="color:#94a3b8; font-size:0.85rem; margin-bottom:0.75rem;">
                <strong style="color:var(--teal);">Tap to select an audio track</strong><br>
                <span style="font-size:0.75rem;">(MP3, WAV, FLAC)</span>
              </div>
              <span style="display:inline-block; background:rgba(0,230,118,0.12); border:1px solid rgba(0,230,118,0.4); color:var(--teal); padding:0.6rem 1.4rem; border-radius:8px; font-size:0.85rem; font-weight:800;">
                🎵 Choose Audio File
              </span>
            </div>
          </div>
          <div id="audio-file-preview" style="display:none; padding:1rem;"></div>
        </div>
      </div>

      <!-- 6. Scheduling & Immediate Publish -->
      <div class="row-flex" style="align-items:center; margin-top:1.5rem;">
        <div class="field" style="margin-bottom:0;">
          <label>Schedule Publish Time (Optional)</label>
          <input type="datetime-local" name="scheduled_at" value="<?= htmlspecialchars($post_scheduled_at) ?>"/>
        </div>
        <div style="display:flex; align-items:center; gap:0.8rem; background:rgba(0,0,0,0.25); padding:0.8rem 1.2rem; border-radius:12px; border:1px solid rgba(255,255,255,0.06); height:100%; box-sizing:border-box;">
          <label class="switch">
            <input type="checkbox" name="is_published" <?= $post_is_published ? 'checked' : '' ?>>
            <span class="slider"></span>
          </label>
          <span style="font-size:0.88rem; font-weight:700; color:#fff;">Publish Immediately</span>
        </div>
      </div>

      <!-- Submit Button -->
      <button type="submit" class="btn-submit" id="btn-submit-prod">
        🚀 Publish Product Listing
      </button>

    </form>
  </div>
</div>

<!-- =========================================================
     ANIMATED 1-TO-100% EXECUTIVE HUD PROGRESS LOADER MODAL
     ========================================================= -->
<div id="upload-hud-modal">
  <div class="hud-box">
    
    <div class="hud-circle-wrap">
      <div class="hud-radar-glow"></div>
      <svg class="hud-svg" viewBox="0 0 160 160">
        <defs>
          <linearGradient id="hudGradient" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stop-color="#fcb900" />
            <stop offset="50%" stop-color="#00e676" />
            <stop offset="100%" stop-color="#00b0ff" />
          </linearGradient>
        </defs>
        <circle class="hud-bg-ring" cx="80" cy="80" r="70" />
        <circle class="hud-progress-ring" id="hud-progress-circle" cx="80" cy="80" r="70" />
      </svg>
      <div class="hud-score-center">
        <div>
          <span class="hud-score-num" id="hud-score-display">0</span><span class="hud-score-pct">%</span>
        </div>
      </div>
    </div>

    <h3 class="hud-status-title" id="hud-status-title">Uploading Product...</h3>
    <div class="hud-status-desc" id="hud-status-desc">
      ⚡ Initializing listing payload &amp; validating inputs...
    </div>

    <div id="hud-error-action" style="display:none; margin-top:1rem;">
      <button type="button" onclick="closeHud()" style="background:#ff5252; color:#fff; border:none; padding:0.6rem 1.5rem; border-radius:10px; font-weight:800; cursor:pointer;">
        ✕ Close &amp; Correct Details
      </button>
    </div>

  </div>
</div>

<script>
let currentListingType = '<?= $post_type ?>';

function setType(type) {
    currentListingType = type;
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
    const audioField = document.getElementById('audio-upload-field');

    if (prodFields) prodFields.style.display = (type === 'product') ? 'block' : 'none';
    if (servFields) servFields.style.display = (type === 'service') ? 'block' : 'none';
    if (affFields)  affFields.style.display  = (type === 'affiliate') ? 'block' : 'none';
    if (audioField) audioField.style.display = 'block';
}

function toggleSubmissionSystem() {
    const chk = document.getElementById('require_submission');
    const area = document.getElementById('submission-config-area');
    const box = document.getElementById('submission-box');
    const lbl = document.getElementById('sub-toggle-label');

    if (chk && area) {
        if (chk.checked) {
            area.style.display = 'block';
            if (box) box.classList.remove('inactive');
            if (lbl) lbl.textContent = 'Enabled for this Product';
        } else {
            area.style.display = 'none';
            if (box) box.classList.add('inactive');
            if (lbl) lbl.textContent = 'Enable for this Product';
        }
    }
}

function clearFieldError(el) {
    if (!el) return;
    el.classList.remove('field-error');
    const errId = el.id.replace('input-', 'err-');
    const errMsg = document.getElementById(errId);
    if (errMsg) errMsg.style.display = 'none';
}

function validateFields() {
    let hasError = false;
    let firstErrorElement = null;

    const titleInput = document.getElementById('input-title');
    const titleVal = titleInput.value.trim();
    if (!titleVal) {
        hasError = true;
        titleInput.classList.add('field-error');
        document.getElementById('err-title').style.display = 'flex';
        if (!firstErrorElement) firstErrorElement = titleInput;
    }

    if (currentListingType === 'affiliate') {
        const affInput = document.getElementById('input-affiliate-url');
        if (!affInput.value.trim()) {
            hasError = true;
            affInput.classList.add('field-error');
            document.getElementById('err-aff-url').style.display = 'flex';
            if (!firstErrorElement) firstErrorElement = affInput;
        }
    }

    const reqSubChk = document.getElementById('require_submission');
    if (reqSubChk && reqSubChk.checked) {
        const promptInput = document.getElementById('input-submission-prompt');
        if (!promptInput.value.trim()) {
            hasError = true;
            promptInput.classList.add('field-error');
            document.getElementById('err-prompt').style.display = 'flex';
            if (!firstErrorElement) firstErrorElement = promptInput;
        }
    }

    // Mandatory Product Photo Validation (Phase 103)
    const hiddenB64 = document.querySelectorAll('#gc-hidden-inputs input[name="images_b64[]"]');
    const hiddenUploaded = document.querySelectorAll('#gc-hidden-inputs input[name="uploaded_images[]"]');
    const imagesFileInput = document.getElementById('images-input');
    const hasRawFiles = imagesFileInput && imagesFileInput.files && imagesFileInput.files.length > 0;
    const hasCroppedImages = (hiddenB64 && hiddenB64.length > 0) || (hiddenUploaded && hiddenUploaded.length > 0);

    if (!hasRawFiles && !hasCroppedImages) {
        hasError = true;
        const dropzone = document.getElementById('gc-dropzone');
        if (dropzone) {
            dropzone.style.borderColor = '#ff5252';
            dropzone.style.background = 'rgba(255, 82, 82, 0.05)';
        }
        const errImg = document.getElementById('err-images');
        if (errImg) errImg.style.display = 'flex';
        if (!firstErrorElement) firstErrorElement = dropzone;
    }

    if (hasError) {
        const banner = document.getElementById('client-error-banner');
        const bannerText = document.getElementById('error-banner-text');
        bannerText.textContent = 'Please fill in all mandatory required fields highlighted below.';
        banner.style.display = 'block';
        
        if (firstErrorElement) {
            firstErrorElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
            firstErrorElement.focus();
        }
        return false;
    }

    return true;
}

// HUD Progress Controller
let hudInterval = null;
let currentScore = 0;

function setHudProgress(targetScore, statusTitle, statusDesc) {
    const circle = document.getElementById('hud-progress-circle');
    const scoreDisplay = document.getElementById('hud-score-display');
    const titleDisplay = document.getElementById('hud-status-title');
    const descDisplay = document.getElementById('hud-status-desc');

    if (statusTitle) titleDisplay.textContent = statusTitle;
    if (statusDesc) descDisplay.innerHTML = statusDesc;

    const circumference = 440; // 2 * PI * 70

    if (hudInterval) clearInterval(hudInterval);

    hudInterval = setInterval(() => {
        if (currentScore < targetScore) {
            currentScore++;
            scoreDisplay.textContent = currentScore;
            const offset = circumference - (currentScore / 100) * circumference;
            circle.style.strokeDashoffset = offset;
        } else {
            clearInterval(hudInterval);
        }
    }, 18);
}

function openHud() {
    currentScore = 0;
    const hud = document.getElementById('upload-hud-modal');
    hud.classList.add('active');
    document.getElementById('hud-error-action').style.display = 'none';
    setHudProgress(25, "Uploading Product...", "⚡ Initializing listing payload &amp; validating details...");
}

function closeHud() {
    const hud = document.getElementById('upload-hud-modal');
    hud.classList.remove('active');
    if (hudInterval) clearInterval(hudInterval);
}

function showUploadError(msg) {
    if (hudInterval) clearInterval(hudInterval);
    const titleEl = document.getElementById('hud-status-title');
    const descEl = document.getElementById('hud-status-desc');
    const actionEl = document.getElementById('hud-error-action');
    if (titleEl) titleEl.textContent = "Upload Failed";
    if (descEl) descEl.innerHTML = `<span style="color:#ff5252;">⚠️ ${msg || 'Unknown error occurred'}</span>`;
    if (actionEl) actionEl.style.display = 'block';
}

function updateProgress(score, title, desc) {
    setHudProgress(score, title || 'Uploading...', desc || 'Processing listing data...');
}

// Handle AJAX Product Submission with XHR real-time progress & error handling
function handleProductFormSubmit(e) {
    e.preventDefault();

    if (!validateFields()) {
        return false;
    }

    const form = document.getElementById('add-product-form');
    const formData = new FormData(form);
    formData.append('ajax', '1');

    openHud();

    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'product_add.php', true);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

    if (xhr.upload) {
        xhr.upload.onprogress = function(evt) {
            if (evt.lengthComputable) {
                const percent = Math.min(85, Math.round((evt.loaded / evt.total) * 85));
                setHudProgress(percent, "Uploading Payload...", `⚡ Uploading photos & listing details (${Math.round(evt.loaded/1024)} KB / ${Math.round(evt.total/1024)} KB)...`);
            }
        };
    }

    xhr.onload = function() {
        try {
            const res = JSON.parse(xhr.responseText);
            if (xhr.status === 200 && (res.status === 'success' || res.success)) {
                setHudProgress(100, 'Listing Published!', '✅ Product Published Successfully!');
                setTimeout(() => {
                    window.location.href = res.redirect_url || res.redirect || 'products.php?msg=Product+added+successfully';
                }, 800);
            } else {
                showUploadError(res.message || res.error || 'Upload failed. Please check form details.');
            }
        } catch (err) {
            if (xhr.status === 200) {
                setHudProgress(100, 'Listing Published!', '✅ Product Published Successfully!');
                setTimeout(() => {
                    window.location.href = 'products.php?msg=Product+added+successfully';
                }, 800);
            } else {
                showUploadError('Server Response Error: ' + xhr.responseText.substring(0, 100));
            }
        }
    };

    xhr.onerror = function() {
        showUploadError('Connection lost or network error occurred during upload.');
    };

    xhr.send(formData);
}

// Image input listener to clear error state on selection
const imagesInputEl = document.getElementById('images-input');
if (imagesInputEl) {
    imagesInputEl.addEventListener('change', function() {
        if (this.files && this.files.length > 0) {
            const dropzone = document.getElementById('gc-dropzone');
            if (dropzone) {
                dropzone.style.borderColor = 'rgba(252, 185, 0, 0.35)';
                dropzone.style.background = 'rgba(252, 185, 0, 0.02)';
            }
            const errImg = document.getElementById('err-images');
            if (errImg) errImg.style.display = 'none';
        }
    });
}

// Audio preview
const audioInput = document.getElementById('audio-file-input');
if (audioInput) {
    audioInput.addEventListener('change', function() {
        const file = this.files[0];
        if (!file) return;
        const preview = document.getElementById('audio-file-preview');
        const label = document.getElementById('audio-drop-label');
        label.style.display = 'none';
        preview.style.display = 'block';
        const url = URL.createObjectURL(file);
        preview.innerHTML = `
          <div style="display:flex; align-items:center; gap:0.8rem; background:rgba(0,230,118,0.08); border:1px solid rgba(0,230,118,0.2); border-radius:10px; padding:0.8rem 1rem;">
            <span style="font-size:2rem; flex-shrink:0;">🎵</span>
            <div style="flex:1; min-width:0; text-align:left;">
              <div style="color:#fff; font-weight:700; font-size:0.85rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${file.name}</div>
              <div style="color:#9ca3af; font-size:0.72rem; margin:2px 0;">${(file.size/1024/1024).toFixed(2)} MB</div>
              <audio controls style="width:100%; margin-top:6px; height:32px; border-radius:6px;">
                <source src="${url}">
              </audio>
            </div>
            <button type="button" onclick="clearAudio()" style="background:none;border:none;color:#ff5252;cursor:pointer;font-size:1.2rem;padding:4px;flex-shrink:0;" title="Remove">✕</button>
          </div>`;
    });
}

function clearAudio() {
    const audioInput = document.getElementById('audio-file-input');
    const preview = document.getElementById('audio-file-preview');
    const label = document.getElementById('audio-drop-label');
    if (audioInput) audioInput.value = '';
    if (preview) { preview.style.display = 'none'; preview.innerHTML = ''; }
    if (label) label.style.display = 'block';
}

document.addEventListener('DOMContentLoaded', function() {
    setType('<?= $post_type ?>');
    toggleSubmissionSystem();
});
</script>

<?php include __DIR__ . '/../includes/cropper_modal.php'; ?>
</body>
</html>
