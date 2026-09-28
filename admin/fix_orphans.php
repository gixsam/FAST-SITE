<?php
// admin/fix_orphans.php - Assigns any hidden/orphaned products to Fast Site Official
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    die("Unauthorized");
}
require_once __DIR__ . '/../config.php';

echo "<h1>Fixing Orphaned Products</h1>";

try {
    // Get the Fast Site Official ID
    $stmt = $pdo->query("SELECT id FROM partners WHERE business_name = 'Fast Site Official' LIMIT 1");
    $official_shop = $stmt->fetch();
    
    if (!$official_shop) {
        die("Error: Fast Site Official shop not found in partners table.");
    }
    
    $official_id = $official_shop['id'];
    
    // Find all products whose partner_id doesn't exist in the partners table
    $orphans = $pdo->query("
        SELECT p.id, p.title, p.partner_id 
        FROM partner_products p 
        LEFT JOIN partners pt ON p.partner_id = pt.id 
        WHERE pt.id IS NULL
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($orphans)) {
        echo "<h3>No orphaned products found in the database.</h3>";
        
        // If there are literally 0 products in partner_products, we should copy them from services!
        $count = $pdo->query("SELECT COUNT(*) FROM partner_products")->fetchColumn();
        echo "<p>Total products in partner_products table: $count</p>";
        
        if ($count == 0) {
            echo "<p>Your partner_products table is completely empty! You need to restore the default services.</p>";
            echo "<form method='POST'><button type='submit' name='restore_defaults' style='padding:10px 20px; background:#fcb900; border:none; cursor:pointer;'>Restore Default Services from old table</button></form>";
            
            if (isset($_POST['restore_defaults'])) {
                $services = $pdo->query("SELECT * FROM services")->fetchAll();
                $inserted = 0;
                foreach ($services as $srv) {
                    $ins = $pdo->prepare("INSERT INTO partner_products (partner_id, title, price, category, listing_type, is_published, affiliate_url) VALUES (:pid, :title, :price, 'Services', 'service', 1, '')");
                    $ins->execute([
                        ':pid' => $official_id,
                        ':title' => $srv['name'],
                        ':price' => $srv['fee'] ?? 1000
                    ]);
                    $new_id = $pdo->lastInsertId();
                    
                    if (!empty($srv['logo_url'])) {
                        $pdo->prepare("INSERT INTO partner_product_images (product_id, image_url, is_thumbnail) VALUES (?, ?, 1)")->execute([$new_id, 'https://fastsite.best-travel.ltd/uploads/services/' . $srv['logo_url']]);
                    }
                    $inserted++;
                }
                echo "<p style='color:green'>Successfully restored $inserted services!</p>";
            }
        }
    } else {
        $updated = 0;
        foreach ($orphans as $orp) {
            $upd = $pdo->prepare("UPDATE partner_products SET partner_id = :new_id WHERE id = :id");
            $upd->execute([':new_id' => $official_id, ':id' => $orp['id']]);
            $updated++;
            echo "Re-linked: <strong>" . htmlspecialchars($orp['title']) . "</strong> (was ID {$orp['partner_id']}, now $official_id)<br>";
        }
        echo "<h3>Successfully re-linked $updated products to Fast Site Official!</h3>";
    }
    
    echo "<br><a href='dashboard.php'>Return to Admin Dashboard</a>";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
