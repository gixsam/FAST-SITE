<?php
header('Content-Type: application/json');
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
require_once __DIR__ . '/../config.php';

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data || !isset($data['action'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$stmt = $pdo->prepare("SELECT setting_value FROM homepage_settings WHERE setting_key = 'gemini_api_key'");
$stmt->execute();
$keyRow = $stmt->fetch();
$apiKey = $keyRow ? $keyRow['setting_value'] : '';

if (empty($apiKey)) {
    echo json_encode(['success' => false, 'message' => 'Gemini API Key is not configured in settings.']);
    exit;
}

$action = $data['action'];
$userInput = $data['input'] ?? '';

// Fast Site Theme System Prompt
$systemPrompt = "You are the primary copywriter and theme assistant for 'Fast Site', an official platform for both digital government services and a unified product marketplace.

### Tone & Style:
- Professional, trustworthy, yet highly dynamic and engaging.
- Use emojis appropriately but not overwhelmingly.

### Specific Instructions based on action:
";

$prompt = "";

if ($action === 'product_desc') {
    $prompt = $systemPrompt . "- You are creating a PRODUCT description.\n- Highlight the physical/digital benefits, use a vibrant/dynamic tone.\n- Suggest a color scheme that involves bright, energetic colors (e.g. Neon, Gold, Brand Blue) for the photo template.\n\nUSER INPUT:\n" . $userInput;
} elseif ($action === 'service_desc') {
    $prompt = $systemPrompt . "- You are creating a SERVICE description (e.g., NID, Passport, driving license).\n- Tone MUST be formal, highly secure, and authoritative.\n- Suggest a color scheme that involves deep professional colors (e.g. Navy Blue, Silver, White) for the photo template.\n\nUSER INPUT:\n" . $userInput;
} elseif ($action === 'custom') {
    $prompt = $systemPrompt . "- You are answering a general request for Fast Site.\n\nUSER INPUT:\n" . $userInput;
} else {
    echo json_encode(['success' => false, 'message' => 'Unknown action']);
    exit;
}

// Call Gemini API (gemini-1.5-flash)
$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . $apiKey;

$payload = [
    "contents" => [
        [
            "parts" => [
                ["text" => $prompt]
            ]
        ]
    ],
    "generationConfig" => [
        "temperature" => 0.7,
        "topK" => 40,
        "topP" => 0.95,
        "maxOutputTokens" => 8192
    ]
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Prevent infinite hang
$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($response === false) {
    echo json_encode(['success' => false, 'message' => 'cURL Error: ' . $curlError]);
    exit;
}

if ($httpcode === 200) {
    $result = json_decode($response, true);
    $text = $result['candidates'][0]['content']['parts'][0]['text'] ?? 'No response generated.';
    echo json_encode(['success' => true, 'output' => $text]);
} else {
    echo json_encode(['success' => false, 'message' => 'Gemini API Error (HTTP ' . $httpcode . '): ' . $response]);
}
