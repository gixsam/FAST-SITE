<?php
// =========================================================================
// api/partner_apply_role.php — 1-Click Partner Role Application API
// Allows partners to request role access (Dropshipper, API, Agent) inside SPA
// =========================================================================
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

require_once __DIR__ . '/../config.php';

$user_id = $_SESSION['user_id'];
$role = strtolower(trim($_POST['role'] ?? ''));

$valid_roles = ['dropship', 'api_gateway', 'agent_node', 'seller'];
if (!in_array($role, $valid_roles)) {
    echo json_encode(['success' => false, 'message' => 'Invalid partner role.']);
    exit;
}

try {
    // Ensure table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS partner_role_applications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        requested_role VARCHAR(50) NOT NULL,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY user_role (user_id, requested_role)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $stmt = $pdo->prepare("INSERT INTO partner_role_applications (user_id, requested_role, status) 
                           VALUES (?, ?, 'pending')
                           ON DUPLICATE KEY UPDATE status = 'pending', created_at = NOW()");
    $stmt->execute([$user_id, $role]);

    echo json_encode([
        'success' => true, 
        'message' => "Application for " . strtoupper(str_replace('_', ' ', $role)) . " submitted! Admin will review shortly."
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
