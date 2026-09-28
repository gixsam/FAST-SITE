<?php
require_once __DIR__ . '/../config.php';
$stmt = $pdo->query('PRAGMA table_info(services)');
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach($cols as $c) echo $c['name'] . "\n";
