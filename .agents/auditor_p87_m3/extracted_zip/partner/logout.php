<?php
// =========================================================================
// partner/logout.php  –  Partner Portal Logout
// =========================================================================
session_start();
unset($_SESSION['partner_id']);
unset($_SESSION['partner_name']);
session_destroy();
header('Location: /user/login.php');
exit;
?>
