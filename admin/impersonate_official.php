<?php
// =========================================================================
// admin/impersonate_official.php
// Single Sign-On (SSO) Bridge to Official Fast Site Shop
// =========================================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/../config.php';

// 1. Ensure the Official User exists in 'users' table
$stmt = $pdo->prepare("SELECT id FROM users WHERE phone = '00000000000' OR email = 'admin@fastsite.com' LIMIT 1");
$stmt->execute();
$officialUser = $stmt->fetch();

if (!$officialUser) {
    $hash = password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT);
    $pdo->prepare("INSERT INTO users (name, phone, whatsapp, email, dob, gender, password_hash, ref_code) VALUES ('Fast Site Official', '00000000000', '00000000000', 'admin@fastsite.com', '2000-01-01', 'other', ?, 'FS-OFFICIAL-1')")
        ->execute([$hash]);
    $user_id = $pdo->lastInsertId();
} else {
    $user_id = $officialUser['id'];
}

// 2. Ensure the Official Shop exists in 'partners' table
$stmt = $pdo->prepare("SELECT id FROM partners WHERE is_official = 1 OR email = 'admin@fastsite.com' LIMIT 1");
$stmt->execute();
$officialShop = $stmt->fetch();

if (!$officialShop) {
    $hash = password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT);
    try { @$pdo->exec("ALTER TABLE `partners` ADD COLUMN `registration_number` VARCHAR(50) DEFAULT NULL"); } catch (Exception $e) {}
    $pdo->prepare("INSERT INTO partners (business_name, owner_name, email, phone, password_hash, status, is_official, seller_level, rating, total_orders, registration_number) VALUES ('Fast Site Official', 'Fast Site Admin', 'admin@fastsite.com', '00000000000', ?, 'approved', 1, 3, 5.0, 500, 'FS-OFFICIAL-1')")
        ->execute([$hash]);
    $partner_id = $pdo->lastInsertId();
} else {
    // Ensure existing shop is approved and marked official
    try { @$pdo->exec("ALTER TABLE `partners` ADD COLUMN `registration_number` VARCHAR(50) DEFAULT NULL"); } catch (Exception $e) {}
    $pdo->prepare("UPDATE partners SET status = 'approved', is_official = 1, seller_level = 3, registration_number = 'FS-OFFICIAL-1' WHERE id = ?")
        ->execute([$officialShop['id']]);
    $partner_id = $officialShop['id'];
}

// 3. Set Sessions for SSO
$_SESSION['user_id'] = $user_id;
$_SESSION['partner_id'] = $partner_id;
$_SESSION['partner_name'] = 'Fast Site Official';
$_SESSION['is_impersonating'] = true;

// 4. Teleport to Shop
$redirect = $_GET['redirect'] ?? 'dashboard.php';
$allowed_redirects = ['dashboard.php', 'product_add.php', 'products.php', 'orders.php', 'profile.php', 'coupons.php', 'payouts.php'];
$target = in_array($redirect, $allowed_redirects) ? $redirect : 'dashboard.php';
header('Location: /partner/' . $target);
exit;
