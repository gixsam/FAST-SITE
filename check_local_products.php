<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=fastsite_local', 'root', '');
    $count = $pdo->query('SELECT COUNT(*) FROM partner_products')->fetchColumn();
    echo "Local XAMPP MySQL products: " . $count . "\n";
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
