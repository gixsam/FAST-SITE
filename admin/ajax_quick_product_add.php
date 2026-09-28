<?php
// =========================================================================
// admin/ajax_quick_product_add.php – Admin Dashboard Quick Product Upload API
// =========================================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Admin authorization required']);
    exit;
}

require_once __DIR__ . '/../config.php';

// Find or initialize Official Partner ID
$stmt = $pdo->prepare("SELECT id FROM partners WHERE is_official = 1 OR email = 'admin@fastsite.com' LIMIT 1");
$stmt->execute();
$officialShop = $stmt->fetch();
if (!$officialShop) {
    $hash = password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT);
    try { @$pdo->exec("ALTER TABLE `partners` ADD COLUMN `registration_number` VARCHAR(50) DEFAULT NULL"); } catch (Exception $e) {}
    $pdo->prepare("INSERT INTO partners (business_name, owner_name, email, phone, password_hash, status, is_official, seller_level, rating, total_orders, registration_number) VALUES ('Fast Site Official', 'Fast Site Admin', 'admin@fastsite.com', '00000000000', ?, 'approved', 1, 3, 5.0, 500, 'FS-OFFICIAL-1')")
        ->execute([$hash]);
    $partner_id = (int)$pdo->lastInsertId();
} else {
    $partner_id = (int)$officialShop['id'];
}

$title        = trim($_POST['title'] ?? '');
$description  = trim($_POST['description'] ?? '');
$price_raw    = trim($_POST['price'] ?? '');
$price        = ($price_raw !== '') ? floatval($price_raw) : 0.0;
if ($price < 0) $price = 0.0;

$category     = trim($_POST['category'] ?? 'General');
$listing_type = trim($_POST['listing_type'] ?? 'product');
$stock        = (isset($_POST['stock']) && $_POST['stock'] !== '') ? intval($_POST['stock']) : -1;
$shipping_type= trim($_POST['shipping_type'] ?? 'digital');

$require_submission  = isset($_POST['require_submission']) ? 1 : 0;
$submission_prompt   = trim($_POST['submission_prompt'] ?? '');
$affiliate_url       = trim($_POST['affiliate_url'] ?? '');

if (empty($title)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Please enter a product / service title.']);
    exit;
}

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
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS partner_product_images (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        image_url VARCHAR(255) NOT NULL,
        is_thumbnail TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) {}

try {
    if (!$pdo->inTransaction()) {
        $pdo->beginTransaction();
    }

    $stmt_ins = $pdo->prepare("INSERT INTO partner_products 
        (partner_id, title, description, price, category, is_published, listing_type, stock, shipping_type, require_submission, submission_prompt, affiliate_url) 
        VALUES (:pid, :title, :desc, :price, :cat, 1, :type, :stock, :ship, :req_sub, :prompt, :aff_url)");
    
    $stmt_ins->execute([
        ':pid'      => $partner_id,
        ':title'    => $title,
        ':desc'     => $description ?: null,
        ':price'    => $price,
        ':cat'      => $category ?: 'General',
        ':type'     => $listing_type ?: 'product',
        ':stock'    => $stock,
        ':ship'     => $shipping_type ?: 'digital',
        ':req_sub'  => $require_submission,
        ':prompt'   => $submission_prompt ?: null,
        ':aff_url'  => $affiliate_url ?: null
    ]);

    $product_id = (int)$pdo->lastInsertId();

    // Handle Image Upload if provided
    $upload_dir = __DIR__ . '/../uploads/partners/';

    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['cover_image'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
            $filename = 'prod_' . $product_id . '_thumb_' . time() . '.' . $ext;
            if (move_uploaded_file($file['tmp_name'], $upload_dir . $filename)) {
                try {
                    $pdo->prepare("INSERT INTO partner_product_images (product_id, image_url, is_thumbnail) VALUES (?, ?, 1)")
                        ->execute([$product_id, $filename]);
                } catch (Exception $e) {}
            }
        }
    }

    if ($pdo->inTransaction()) {
        $pdo->commit();
    }

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'message' => 'Product published successfully to Official Fast Site Catalog!',
        'product_id' => $product_id,
        'store_url' => '/shop.php?id=' . $partner_id,
        'product_url' => '/product_detail.php?id=' . $product_id
    ]);
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    exit;
}
