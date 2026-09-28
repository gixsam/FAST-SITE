<?php
// =========================================================================
// partner/return_to_admin.php
// Returns from Official Shop Impersonation back to Admin Panel
// =========================================================================
session_start();

if (isset($_SESSION['is_impersonating']) && $_SESSION['is_impersonating'] === true) {
    // Clear partner session variables safely
    unset($_SESSION['partner_id']);
    unset($_SESSION['is_impersonating']);
    
    // Redirect back to Admin Dashboard
    header('Location: /admin/dashboard.php');
    exit;
}

// Fallback if not impersonating
header('Location: /partner/dashboard.php');
exit;
