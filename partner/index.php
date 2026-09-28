<?php
// =========================================================================
// partner/index.php  –  Main Direct Entry Point for /partner & /partner/
// =========================================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['partner_id']) && (int)$_SESSION['partner_id'] > 0) {
    header('Location: /partner/dashboard.php');
    exit;
} else {
    header('Location: /user/login.php');
    exit;
}
