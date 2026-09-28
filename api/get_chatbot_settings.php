<?php
// api/get_chatbot_settings.php - Fetch Chatbot Customization Names
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    require_once __DIR__ . '/../config.php';
    
    // Fetch all chatbot name settings
    $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM homepage_settings WHERE setting_key LIKE 'chatbot_name_%'");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Define fallbacks
    $settings = [
      'fast-site' => $rows['chatbot_name_fast_site'] ?? 'Fast Site Assistant',
      'best-travel' => $rows['chatbot_name_best_travel'] ?? 'Best Travel Guide',
      'ayra-mart' => $rows['chatbot_name_ayra_mart'] ?? 'Ayra Mart Fashion Bot',
      'affi-bangla' => $rows['chatbot_name_affi_bangla'] ?? 'Affi Bangla Deal Finder',
      'enzor' => $rows['chatbot_name_enzor'] ?? 'Enzor Motors Advisor'
    ];
    
    echo json_encode(['status' => 'success', 'data' => $settings]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
