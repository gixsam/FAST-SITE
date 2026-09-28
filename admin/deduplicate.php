<?php
// admin/deduplicate.php - Removes duplicate products from the live database
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    die("Unauthorized");
}
require_once __DIR__ . '/../config.php';

try {
    // Find duplicates based on exact title (ignoring partner_id changes)
    $duplicates = $pdo->query("
        SELECT title, MIN(id) as keep_id 
        FROM partner_products 
        GROUP BY title 
        HAVING COUNT(id) > 1
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    $deleted_count = 0;
    
    foreach ($duplicates as $dup) {
        $stmt = $pdo->prepare("DELETE FROM partner_products WHERE title = :title AND id != :keep_id");
        $stmt->execute([
            ':title' => $dup['title'],
            ':keep_id' => $dup['keep_id']
        ]);
        $deleted_count += $stmt->rowCount();
    }
    
    echo "<h1>Deduplication Complete!</h1>";
    echo "<p>Successfully deleted <strong>$deleted_count</strong> duplicate product listings from the database.</p>";
    echo "<a href='dashboard.php'>Return to Admin Dashboard</a>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
