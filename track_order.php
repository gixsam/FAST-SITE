<?php
// track_order.php — Public order status lookup by reference ID
// Matches the 'orders' table used by submit_order.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/config.php';

$ref = strtoupper(trim($_GET['ref'] ?? ''));

if (!$ref) {
    echo json_encode(['success' => false, 'message' => 'No reference provided.']);
    exit;
}

try {
    // Try orders table first (submit_order.php uses this)
    $stmt = $pdo->prepare(
        "SELECT user_name, user_phone, service, status, details, created_at
         FROM orders
         WHERE ref_id = :ref
         LIMIT 1"
    );
    $stmt->execute([':ref' => $ref]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fallback: try applications table with padded FS- prefix
    if (!$order && preg_match('/^FS-(\d+)$/i', $ref, $m)) {
        $appId = (int)$m[1];
        $stmt2 = $pdo->prepare(
            "SELECT a.user_name, a.user_phone, s.name AS service, a.status, a.details, a.created_at
             FROM applications a
             JOIN services s ON a.service_id = s.id
             WHERE a.id = :id
             LIMIT 1"
        );
        $stmt2->execute([':id' => $appId]);
        $order = $stmt2->fetch(PDO::FETCH_ASSOC);
        if ($order) $order['service'] = $order['service'] ?? '—';
    }

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found.']);
        exit;
    }

    // Format date nicely
    $order['created_at'] = date('d M Y, h:i A', strtotime($order['created_at']));

    // Partially hide phone for privacy — show last 4 digits only
    $phone = $order['user_phone'];
    if (strlen($phone) > 4) {
        $order['user_phone'] = str_repeat('*', max(0, strlen($phone) - 4)) . substr($phone, -4);
    }

    echo json_encode(['success' => true, 'order' => $order]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}
