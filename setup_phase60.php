<?php
require_once __DIR__ . '/config.php';

try {
    // Create affiliate_tiers table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `affiliate_tiers` (
            `id` INTEGER PRIMARY KEY AUTOINCREMENT,
            `tier_name` varchar(50) NOT NULL,
            `min_sales_required` int(11) NOT NULL DEFAULT 0,
            `commission_bonus_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
            `badge_color` varchar(20) NOT NULL DEFAULT '#ffffff'
        );
    ");

    // Insert default tiers
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

    // Create affiliate_missions table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `affiliate_missions` (
            `id` INTEGER PRIMARY KEY AUTOINCREMENT,
            `affiliate_id` int(11) NOT NULL,
            `mission_type` varchar(50) NOT NULL,
            `target` int(11) NOT NULL,
            `progress` int(11) NOT NULL DEFAULT 0,
            `status` varchar(20) NOT NULL DEFAULT 'in_progress',
            `reward_coins` int(11) NOT NULL DEFAULT 0,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp
        );
    ");

    echo "Phase 60 tables created successfully.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
