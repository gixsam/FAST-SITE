<?php
// ============================================================================
// admin/run_migrations.php — Safe Auto-Migration for Hostinger (MySQL)
// Run this once after uploading the Phase 60 zip to sync the Database Schema.
// ============================================================================
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['admin_logged_in'])) {
    die("Unauthorized. Please login to admin panel first.");
}
require_once '../config.php';

echo "<h2>Running Database Migrations (Phase 43 - Phase 60)</h2>";
echo "<ul>";

try {
    // 1. user_wishlist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `user_wishlist` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `product_id` int(11) NOT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "<li>`user_wishlist` table checked/created.</li>";

    // 2. partner_ratings
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `partner_ratings` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `partner_id` int(11) NOT NULL,
            `user_id` int(11) NOT NULL,
            `order_id` int(11) NOT NULL,
            `rating` int(11) NOT NULL DEFAULT 5,
            `review_text` text,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "<li>`partner_ratings` table checked/created.</li>";

    // 3. user_notifications
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `user_notifications` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `title` varchar(150) NOT NULL,
            `message` text NOT NULL,
            `is_read` tinyint(1) NOT NULL DEFAULT 0,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "<li>`user_notifications` table checked/created.</li>";

    // 4. shop_coupons
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `shop_coupons` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `partner_id` int(11) NOT NULL,
            `code` varchar(50) NOT NULL,
            `discount_type` enum('percentage','fixed') NOT NULL DEFAULT 'percentage',
            `discount_value` decimal(10,2) NOT NULL,
            `min_purchase` decimal(10,2) NOT NULL DEFAULT 0.00,
            `expiry_date` datetime DEFAULT NULL,
            `is_active` tinyint(1) NOT NULL DEFAULT 1,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "<li>`shop_coupons` table checked/created.</li>";

    // 5. affiliate_tiers
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `affiliate_tiers` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `tier_name` varchar(50) NOT NULL,
            `min_sales_required` int(11) NOT NULL DEFAULT 0,
            `commission_bonus_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
            `badge_color` varchar(20) NOT NULL DEFAULT '#ffffff',
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    
    // Insert default tiers if empty
    $stmt = $pdo->query("SELECT COUNT(*) FROM `affiliate_tiers`");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("
            INSERT INTO `affiliate_tiers` (`tier_name`, `min_sales_required`, `commission_bonus_percent`, `badge_color`) VALUES
            ('Bronze', 0, 0.00, '#cd7f32'),
            ('Silver', 10, 2.50, '#c0c0c0'),
            ('Gold', 50, 5.00, '#fcb900'),
            ('Platinum', 200, 10.00, '#e5e4e2');
        ");
    }
    echo "<li>`affiliate_tiers` table checked/created (and seeded if empty).</li>";

    // 6. affiliate_missions
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `affiliate_missions` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `affiliate_id` int(11) NOT NULL,
            `mission_type` varchar(50) NOT NULL,
            `target` int(11) NOT NULL,
            `progress` int(11) NOT NULL DEFAULT 0,
            `status` enum('in_progress','completed','claimed') NOT NULL DEFAULT 'in_progress',
            `reward_coins` int(11) NOT NULL DEFAULT 0,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "<li>`affiliate_missions` table checked/created.</li>";

    echo "</ul>";
    echo "<h3 style='color:green;'>All Migrations Completed Successfully! 🎉</h3>";
    echo "<p>Your database schema is now fully synced up to Phase 60.</p>";
    echo "<a href='index.php'>Return to Admin Dashboard</a>";

} catch (PDOException $e) {
    echo "</ul>";
    echo "<h3 style='color:red;'>Migration Error</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
