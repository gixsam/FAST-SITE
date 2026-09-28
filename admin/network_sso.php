<?php
// =========================================================================
// admin/network_sso.php   v4: Command Center SSO Teleporter
// =========================================================================
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    die(json_encode(['status' => 'error', 'message' => 'Unauthorized Access']));
}
require_once __DIR__ . '/../config.php';

// This script generates a Magic SSO Token and returns a secure teleport URL.

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $target_site = $_POST['site'] ?? '';
    $role = $_POST['role'] ?? ''; // admin, user, or shop

    $valid_sites = [
        'GIXSAM' => 'https://gixsam.com',
        'BEST TRAVEL' => 'https://besttravel.com',
        'ENZOR MOTOR' => 'https://enzormotor.com',
        'AYRA MART' => 'https://ayramart.com',
        'MANZA' => 'https://manza.com',
        'AFFI BANGLA' => 'https://affibangla.com'
    ];

    if (!array_key_exists($target_site, $valid_sites)) {
        die(json_encode(['status' => 'error', 'message' => 'Invalid Target Site']));
    }

    $base_url = $valid_sites[$target_site];
    
    // In a full production environment, we would insert a short-lived token into a shared DB 
    // or sign a JWT here. For now, we simulate the Secure Token generation.
    $secure_token = bin2hex(random_bytes(32)); 
    $timestamp = time();
    $signature = hash_hmac('sha256', $secure_token . $timestamp . $role, 'FAST_SITE_MASTER_SECRET_KEY');

    // The receiving endpoint on the target site (e.g. Ayra Mart) would verify this token
    $teleport_url = $base_url . "/auth/magic_login.php?token=" . $secure_token . "&ts=" . $timestamp . "&role=" . $role . "&sig=" . $signature;

    echo json_encode([
        'status' => 'success',
        'teleport_url' => $teleport_url,
        'message' => 'Magic Link Generated'
    ]);
    exit;
}
?>
