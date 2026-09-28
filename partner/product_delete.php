<?php
// =========================================================================
// partner/product_delete.php  –  Delete Product Listing & Image Files
// =========================================================================
session_start();
if (!isset($_SESSION['partner_id'])) {
    header('Location: /user/login.php');
    exit;
}
require_once __DIR__ . '/../config.php';

$partner_id = $_SESSION['partner_id'];
$product_id = intval($_GET['id'] ?? 0);

if ($product_id > 0) {
    // Verify ownership
    $stmt = $pdo->prepare("SELECT id FROM partner_products WHERE id = :id AND partner_id = :partner_id LIMIT 1");
    $stmt->execute([':id' => $product_id, ':partner_id' => $partner_id]);
    
    if ($stmt->fetch()) {
        try {
            $pdo->beginTransaction();

            // Fetch and delete files from disk
            $stmt_imgs = $pdo->prepare("SELECT image_url FROM partner_product_images WHERE product_id = :product_id");
            $stmt_imgs->execute([':product_id' => $product_id]);
            $images = $stmt_imgs->fetchAll();

            $upload_dir = '../uploads/partners/';
            foreach ($images as $img) {
                $filepath = $upload_dir . $img['image_url'];
                if (file_exists($filepath)) {
                    @unlink($filepath);
                }
            }

            // Delete images records
            $pdo->prepare("DELETE FROM partner_product_images WHERE product_id = :product_id")->execute([':product_id' => $product_id]);

            // Delete product wishlist records
            $pdo->prepare("DELETE FROM partner_wishlist WHERE product_id = :product_id")->execute([':product_id' => $product_id]);

            // Delete product record
            $pdo->prepare("DELETE FROM partner_products WHERE id = :product_id AND partner_id = :partner_id")
                ->execute([':product_id' => $product_id, ':partner_id' => $partner_id]);

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            die("Error deleting product: " . $e->getMessage());
        }
    }
}

header('Location: products.php');
exit;
?>
