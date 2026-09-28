<?php
require 'config.php';
$stmt = $pdo->query('PRAGMA table_info(users)');
foreach($stmt->fetchAll() as $row) {
    echo $row['name'] . "\n";
}
