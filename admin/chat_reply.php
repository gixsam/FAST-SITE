<?php
// ============================================================
// admin/chat_reply.php  –  AJAX: Admin sends a message
// POST JSON: { session_id, message }
// Returns JSON: { success, id }
// ============================================================

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

require_once __DIR__ . '/../config.php';

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

$sessionId = trim($data['session_id'] ?? '');
$message   = trim($data['message']    ?? '');

if (!$sessionId || !$message) {
    echo json_encode(['success' => false, 'message' => 'session_id and message required.']);
    exit;
}

// Validate session
$sess = $pdo->prepare("SELECT `id` FROM `chat_sessions` WHERE `session_id`=:sid");
$sess->execute([':sid' => $sessionId]);
if (!$sess->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Session not found.']);
    exit;
}

$ins = $pdo->prepare(
    "INSERT INTO `chat_messages` (`session_id`,`sender`,`message`,`is_read`) VALUES (:sid,'admin',:msg,1)"
);
$ins->execute([':sid' => $sessionId, ':msg' => $message]);

echo json_encode(['success' => true, 'id' => (int)$pdo->lastInsertId()]);
