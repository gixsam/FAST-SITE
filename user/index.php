<?php
// =========================================================================
// user/index.php  –  Main Direct Entry Point for /user & /user/
// =========================================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0) {
    header('Location: /user/dashboard.php');
    exit;
} else {
    header('Location: /user/login.php');
    exit;
}
