<?php
// d:\WEBSITE\FAST SITE\fast site\api\sync_credentials.php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

$secret = $_POST['secret'] ?? '';
$shop_name = $_POST['shop_name'] ?? '';
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

// Verify simple secret for internal syncing
if ($secret !== 'FAST_SYNC_SECRET_829') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (!$shop_name || !$username || !$password) {
    echo json_encode(['success' => false, 'message' => 'Missing data']);
    exit;
}

try {
    // Check if partner shop exists in FAST SITE
    $stmt = $pdo->prepare("SELECT * FROM partners WHERE business_name = ? LIMIT 1");
    $stmt->execute([$shop_name]);
    $partner = $stmt->fetch();

    $hash = password_hash($password, PASSWORD_DEFAULT);

    if ($partner) {
        // Update existing partner credentials
        $upd = $pdo->prepare("UPDATE partners SET password_hash = ? WHERE id = ?");
        $upd->execute([$hash, $partner['id']]);
        
        // Also update the linked user account if it exists
        $user_upd = $pdo->prepare("UPDATE users SET password_hash = ? WHERE phone = ? OR email = ?");
        $user_upd->execute([$hash, $partner['phone'], $partner['email']]);
        
        echo json_encode(['success' => true, 'message' => 'Credentials synced']);
    } else {
        // Automatically create partner shop if it doesn't exist yet
        $ins = $pdo->prepare("INSERT INTO partners (business_name, owner_name, phone, email, password_hash, status) VALUES (?, ?, ?, ?, ?, 'Approved')");
        $ins->execute([$shop_name, 'Admin', $username, $username . '@partner.local', $hash]);
        echo json_encode(['success' => true, 'message' => 'Shop created and credentials synced']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
