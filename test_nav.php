<?php
session_start();
$_SESSION['user_id'] = 1; // Assuming user 1 exists
require __DIR__ . '/config.php';
require 'partner/nav.php';
echo "SUCCESS";
