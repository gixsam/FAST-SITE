<?php
// ============================================================
// get_service_fee.php  –  Return fee for a given service name
// AJAX: GET/POST with ?service=<name> or JSON {service:"..."}
// ============================================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/config.php';

// Accept both GET param and JSON body
$service = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw  = file_get_contents('php://input');
    $data = json_decode($raw, true);
    $service = trim($data['service'] ?? '');
} else {
    $service = trim($_GET['service'] ?? '');
}

if (!$service) {
    echo json_encode(['success' => false, 'message' => 'Service name required.']);
    exit;
}

$stmt = $pdo->prepare(
    "SELECT `id`, `name`, `fee`, `description`
     FROM `services`
     WHERE `name` = :name AND `is_active` = 1
     LIMIT 1"
);
$stmt->execute([':name' => $service]);
$row = $stmt->fetch();

if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Service not found or inactive.']);
    exit;
}

echo json_encode([
    'success'     => true,
    'service_id'  => (int)$row['id'],
    'name'        => $row['name'],
    'fee'         => number_format((float)$row['fee'], 2, '.', ''),
    'description' => $row['description'],
]);
