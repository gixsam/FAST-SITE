<?php
// =========================================================================
// admin/index.php  –  Main Direct Entry Point for /admin & /admin/
// =========================================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: /admin/dashboard.php');
    exit;
} else {
    header('Location: /admin/login.php');
    exit;
}
