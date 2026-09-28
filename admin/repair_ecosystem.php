<?php
// admin/repair_ecosystem.php - Magically reconnects broken products to their ecosystem shops
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    die("Unauthorized");
}
require_once __DIR__ . '/../config.php';

echo "<h1>Ecosystem Auto-Repair</h1>";

// 1. Get current valid ecosystem partner IDs
$ecosystem = [];
$stmt = $pdo->query("SELECT id, business_name FROM partners WHERE is_official = 1 OR business_name IN ('Best Travel', 'Ayra Mart', 'Enzor Motor', 'Affi Bangla', 'Manza', 'GixSam')");
foreach ($stmt->fetchAll() as $row) {
    $ecosystem[$row['business_name']] = $row['id'];
}

$repaired = 0;

// 2. Fetch all products to inspect
$products = $pdo->query("SELECT p.id, p.partner_id, p.title, p.affiliate_url, i.image_url 
                         FROM partner_products p 
                         LEFT JOIN partner_product_images i ON p.id = i.product_id")->fetchAll();

foreach ($products as $p) {
    // If the partner_id doesn't exist in the active ecosystem list, it's orphaned or broken
    if (!in_array($p['partner_id'], $ecosystem)) {
        
        $target_shop = null;
        $urlData = strtolower($p['image_url'] . ' ' . $p['affiliate_url']);
        
        // Intelligent mapping based on URLs or Titles
        if (strpos($urlData, 'atayramart') !== false) {
            $target_shop = 'Ayra Mart';
        } elseif (strpos($urlData, 'best-travel.ltd') !== false && strpos($urlData, 'enzor') === false && strpos($urlData, 'affibangla') === false) {
            $target_shop = 'Best Travel';
        } elseif (strpos($urlData, 'enzor') !== false) {
            $target_shop = 'Enzor Motor';
        } elseif (strpos($urlData, 'affibangla') !== false) {
            $target_shop = 'Affi Bangla';
        } elseif (strpos($urlData, 'gixsam') !== false) {
            $target_shop = 'GixSam';
        }
        
        // If we found a target shop and we have its new ID
        if ($target_shop && isset($ecosystem[$target_shop])) {
            $new_id = $ecosystem[$target_shop];
            
            // Re-link the product to the correct shop
            $upd = $pdo->prepare("UPDATE partner_products SET partner_id = :nid WHERE id = :pid");
            $upd->execute([':nid' => $new_id, ':pid' => $p['id']]);
            $repaired++;
            echo "✅ Repaired: <strong>{$p['title']}</strong> -> re-linked to $target_shop<br>";
        }
    }
}

if ($repaired > 0) {
    echo "<h3>Successfully repaired $repaired orphaned products! They will now show up under their correct shops on the homepage.</h3>";
} else {
    echo "<h3>No orphaned products needed repair.</h3>";
}

echo "<br><a href='dashboard.php'>Return to Admin Dashboard</a>";
?>
