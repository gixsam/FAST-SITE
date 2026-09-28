<?php
require 'config.php';
$res = $pdo->query("PRAGMA table_info(partner_products)")->fetchAll(PDO::FETCH_ASSOC);
print_r($res);
