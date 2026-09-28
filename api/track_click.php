<?php
// ============================================================
// api/track_click.php  –  Record clicks on affiliate links
// ============================================================
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config.php';

// Accept both GET ?id=X and POST JSON {id: X}
$serviceId = 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw  = file_get_contents('php://input');
    $data = json_decode($raw, true);
    $serviceId = (int)($data['id'] ?? 0);
} else {
    $serviceId = (int)($_GET['id'] ?? 0);
}

if ($serviceId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Valid service ID required.']);
    exit;
}

try {
    // Increment clicks
    $stmt = $pdo->prepare("UPDATE services SET referral_clicks = referral_clicks + 1 WHERE id = :id");
    $stmt->execute([':id' => $serviceId]);
    
    echo json_encode(['success' => true, 'message' => 'Click tracked successfully.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
