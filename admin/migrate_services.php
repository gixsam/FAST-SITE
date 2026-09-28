<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/../config.php';

echo "Starting migration of Admin Services to Fast Site Official Shop...\n";

// 1. Get the Official Shop Partner ID
$stmt = $pdo->prepare("SELECT id FROM partners WHERE business_name = 'Fast Site Official' LIMIT 1");
$stmt->execute();
$official = $stmt->fetch();

if (!$official) {
    echo "ERROR: 'Fast Site Official' shop not found. Run impersonate_official.php first to generate it.\n";
    exit(1);
}
$partner_id = $official['id'];
echo "Found Fast Site Official partner ID: $partner_id\n";

// 2. Fetch all services
$stmt = $pdo->query("SELECT * FROM services");
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($services)) {
    echo "No services found to migrate.\n";
    exit(0);
}

echo "Found " . count($services) . " services. Migrating...\n";

$pdo->beginTransaction();

try {
    foreach ($services as $srv) {
        echo "Migrating: " . $srv['name'] . "\n";
        
        // Insert into partner_products
        $stmt_prod = $pdo->prepare("
            INSERT INTO partner_products 
            (partner_id, title, description, price, category, is_published, created_at, listing_type, affiliate_url, affiliate_action)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'affiliate', ?, ?)
        ");
        
        $stmt_prod->execute([
            $partner_id,
            $srv['name'],
            $srv['description'],
            $srv['fee'],
            $srv['section_name'], // map section to category
            $srv['is_active'],
            $srv['created_at'],
            $srv['referral_link'],
            $srv['action_text']
        ]);
        
        $new_product_id = $pdo->lastInsertId();
        
        if (!empty($srv['logo_url'])) {
            // Need to make sure the image URL is compatible with the product_detail.php
            // The old logo_url might be like '../uploads/services/file.png' or just 'file.png'
            // We'll insert it directly. We may need to patch product_detail.php later if it relies on uploads/partners/ folder structure for ALL images.
            $stmt_img = $pdo->prepare("
                INSERT INTO partner_product_images (product_id, image_url, is_thumbnail)
                VALUES (?, ?, 1)
            ");
            $stmt_img->execute([$new_product_id, $srv['logo_url']]);
        }
    }
    
    // Optional: mark all old services as inactive or delete them
    // $pdo->query("UPDATE services SET is_active = 0");
    
    $pdo->commit();
    echo "\nSUCCESS: " . count($services) . " services migrated to Global Marketplace.\n";
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "FAILED: " . $e->getMessage() . "\n";
}
