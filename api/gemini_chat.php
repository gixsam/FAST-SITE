<?php
// =========================================================================
// api/gemini_chat.php    v5: Global Ecosystem Chatbot (Phase 22)
// =========================================================================
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

// Check if API Key exists in .env or environment
$api_key = getenv('GEMINI_API_KEY') ?: ($_ENV['GEMINI_API_KEY'] ?? '');
if (empty($api_key)) {
    die(json_encode(['status' => 'error', 'message' => 'Gemini API Key missing']));
}

$input = json_decode(file_get_contents('php://input'), true);
$user_message = $input['message'] ?? '';

if (empty($user_message)) {
    die(json_encode(['status' => 'error', 'message' => 'Empty message']));
}

// System Prompt configuring the AI Persona
$system_instruction = "SYSTEM INSTRUCTION: You are the Fast Site Global Escrow Assistant. You help users navigate the marketplace, understand escrow rules, and purchase premium digital assets safely. Keep answers concise, friendly, and formatted nicely. NOW RESPOND TO THE USER:";

// Models to try (Gemini 2.0 Flash -> Gemini 1.5 Flash -> Gemini 1.5 Pro)
$models_to_try = ['gemini-2.0-flash', 'gemini-1.5-flash', 'gemini-1.5-pro'];
$bot_reply = '';
$used_model = '';

foreach ($models_to_try as $model) {
    $gemini_url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . $api_key;

    $data = [
        "contents" => [
            [
                "role" => "user",
                "parts" => [
                    ["text" => $system_instruction . "\n\nUSER MESSAGE: " . $user_message]
                ]
            ]
        ]
    ];

    $ch = curl_init($gemini_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // CRITICAL FOR HOSTINGER
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // CRITICAL FOR HOSTINGER
    curl_setopt($ch, CURLOPT_TIMEOUT, 12);

    $response = curl_exec($ch);
    curl_close($ch);

    if ($response) {
        $responseData = json_decode($response, true);
        if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
            $bot_reply = $responseData['candidates'][0]['content']['parts'][0]['text'];
            $used_model = $model;
            break;
        }
    }
}

if (empty($bot_reply)) {
    $bot_reply = "I am currently offline. Please try again in a moment.";
}

echo json_encode([
    'status' => 'success',
    'reply' => $bot_reply,
    'model' => $used_model
]);
?>
