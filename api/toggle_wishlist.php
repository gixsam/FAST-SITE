<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Please log in to save items.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents("php://input"), true);
$product_id = intval($data['product_id'] ?? 0);

if ($product_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid product ID']);
    exit;
}

try {
    // Check if it already exists
    $stmt = $pdo->prepare("SELECT id FROM user_wishlist WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);
    $exists = $stmt->fetchColumn();

    if ($exists) {
        // Remove it
        $stmt_del = $pdo->prepare("DELETE FROM user_wishlist WHERE id = ?");
        $stmt_del->execute([$exists]);
        echo json_encode(['success' => true, 'action' => 'removed']);
    } else {
        // Add it
        $stmt_add = $pdo->prepare("INSERT INTO user_wishlist (user_id, product_id) VALUES (?, ?)");
        $stmt_add->execute([$user_id, $product_id]);
        echo json_encode(['success' => true, 'action' => 'added']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
