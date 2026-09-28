<?php
require_once __DIR__ . '/../config.php';

try {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    if ($driver === 'sqlite') {
        $pdo->exec("ALTER TABLE partner_products ADD COLUMN affiliate_link TEXT DEFAULT NULL");
    } else {
        $pdo->exec("ALTER TABLE partner_products ADD COLUMN affiliate_link VARCHAR(1000) NULL DEFAULT NULL");
    }
    echo "Migration Success!\n";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false || strpos($e->getMessage(), 'duplicate column') !== false) {
        echo "Column already exists!\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
