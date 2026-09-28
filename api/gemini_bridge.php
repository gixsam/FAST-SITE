<?php
// =========================================================================
// api/gemini_bridge.php — Mobile & Web Gemini AI Bridge (Gemini 2.0 Flash)
// =========================================================================
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config.php';

// Check if API Key exists in .env or environment
$api_key = getenv('GEMINI_API_KEY') ?: ($_ENV['GEMINI_API_KEY'] ?? '');
if (empty($api_key)) {
    die(json_encode(['status' => 'error', 'message' => 'Gemini API Key missing']));
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_REQUEST;
}

$user_message = trim($input['message'] ?? '');
$source       = trim($input['source'] ?? 'website'); // 'mobile', 'website', 'antigravity'
$custom_prompt = trim($input['system_instruction'] ?? '');

if (empty($user_message)) {
    die(json_encode(['status' => 'error', 'message' => 'Empty message']));
}

// System Prompt configuring the AI Persona
$system_instruction = !empty($custom_prompt) ? $custom_prompt : 
    "SYSTEM INSTRUCTION: You are the Fast Site Global Escrow & AI Assistant. You help users navigate the marketplace, understand escrow rules, purchase premium digital assets safely, and answer general assistance queries. Keep answers concise, friendly, and formatted nicely with markdown. NOW RESPOND TO THE USER:";

// Priority Models (Gemini 2.0 Flash -> Gemini 1.5 Flash -> Gemini 1.5 Pro)
$models_to_try = [
    'gemini-2.0-flash',
    'gemini-1.5-flash',
    'gemini-1.5-pro'
];

$bot_reply = '';
$used_model = '';
$error_details = [];

foreach ($models_to_try as $model) {
    $gemini_url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . $api_key;

    $data = [
        "contents" => [
            [
                "role" => "user",
                "parts" => [
                    ["text" => $system_instruction . "\n\nUSER MESSAGE (" . strtoupper($source) . "): " . $user_message]
                ]
            ]
        ]
    ];

    $ch = curl_init($gemini_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 12);

    $response = curl_exec($ch);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($response) {
        $responseData = json_decode($response, true);
        if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
            $bot_reply = $responseData['candidates'][0]['content']['parts'][0]['text'];
            $used_model = $model;
            break;
        } else if (isset($responseData['error'])) {
            $error_details[$model] = $responseData['error']['message'] ?? 'API Error';
        }
    } else {
        $error_details[$model] = 'Connection Failed: ' . $curl_error;
    }
}

if (!empty($bot_reply)) {
    echo json_encode([
        'status' => 'success',
        'reply' => $bot_reply,
        'model' => $used_model,
        'source' => $source,
        'bridge' => 'connected'
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Gemini AI Service currently unavailable.',
        'errors' => $error_details
    ]);
}
