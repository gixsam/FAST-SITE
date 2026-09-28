<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    die("Access Denied");
}
require_once __DIR__ . '/../config.php';

echo "<h2 style='color:#fff; background:#10b981; padding:10px;'>Fast Site Database Repair Tool</h2>";

// 1. Auto-approve all products
$stmt = $pdo->query("UPDATE partner_products SET is_published = 1, admin_status = 'approved'");
echo "<p style='color:green;'>✅ Forced all products to published & approved.</p>";

// 2. Auto-approve all partners
$stmt2 = $pdo->query("UPDATE partners SET status = 'approved'");
echo "<p style='color:green;'>✅ Forced all partners to approved.</p>";

// 3. Find orphaned products (where partner_id doesn't match an existing partner)
$stmt3 = $pdo->query("SELECT id, title, partner_id FROM partner_products WHERE partner_id NOT IN (SELECT id FROM partners) AND partner_id != 0");
$orphaned = $stmt3->fetchAll();

if (count($orphaned) > 0) {
    echo "<p style='color:orange;'>⚠️ Found " . count($orphaned) . " orphaned products (shops were deleted/recreated). Attempting to re-link to Official Shop...</p>";
    
    // Find official shop ID
    $official = $pdo->query("SELECT id FROM partners WHERE is_official = 1 LIMIT 1")->fetch();
    if ($official) {
        $pdo->query("UPDATE partner_products SET partner_id = " . $official['id'] . " WHERE partner_id NOT IN (SELECT id FROM partners) AND partner_id != 0");
        echo "<p style='color:green;'>✅ Successfully re-linked orphaned products to Fast Site Official Shop!</p>";
    }
} else {
    echo "<p style='color:green;'>✅ No orphaned products found.</p>";
}

echo "<br><br><a href='dashboard.php' style='color:#fff; background:#3b82f6; padding:10px; text-decoration:none;'>Return to Dashboard</a>";
?>
