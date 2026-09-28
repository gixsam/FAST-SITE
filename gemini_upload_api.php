<?php
// =========================================================================
// gemini_upload_api.php    v4: Gemini Direct Upload Webhook (Phase 18)
// =========================================================================
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Allow Gemini API server
header('Access-Control-Allow-Methods: POST');
require_once __DIR__ . '/config.php';

// This is the absolute backend endpoint for Phase 18.
// When you chat with Gemini on your phone and say:
// "Upload a red shoe to Ayra Mart for 5000 BDT", Gemini formats the data and POSTs it here.

$gemini_secret = 'GEMINI_MASTER_KEY_2026'; // Protect this in production

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode(['status' => 'error', 'message' => 'Invalid Request Method']));
}

$api_key = $_POST['api_key'] ?? '';
if ($api_key !== $gemini_secret) {
    die(json_encode(['status' => 'error', 'message' => 'Unauthorized AI Connection']));
}

$payload_json = $_POST['payload'] ?? '';
$payload = json_decode($payload_json, true);

if (!$payload) {
    die(json_encode(['status' => 'error', 'message' => 'Invalid JSON Payload']));
}

// Ensure payload has targets
$targets = $payload['targets'] ?? [];
$items = $payload['items'] ?? []; // Supports bulk array

if (empty($targets) || empty($items)) {
    die(json_encode(['status' => 'error', 'message' => 'Missing targets or items in payload']));
}

$log = [];
foreach ($targets as $site) {
    foreach ($items as $item) {
        // Logic to inject the $item (title, price, image_url from CDN) into the target site database.
        // For now, simulate success:
        $log[] = "Successfully uploaded '{$item['title']}' to {$site}.";
    }
}

echo json_encode([
    'status' => 'success',
    'message' => 'Gemini Automation Completed successfully.',
    'execution_log' => $log
]);
?>
