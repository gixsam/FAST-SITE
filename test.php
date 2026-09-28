<?php
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['partner_id'] = 1;
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once "partner/dashboard.php";
