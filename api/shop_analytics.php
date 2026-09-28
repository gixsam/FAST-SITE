<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['partner_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$partner_id = $_SESSION['partner_id'];
$filter = $_GET['filter'] ?? 'all';

$date_condition = "";
if ($filter === 'today') {
    $date_condition = " AND DATE(created_at) = CURDATE()";
} elseif ($filter === 'week') {
    $date_condition = " AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
} elseif ($filter === 'month') {
    $date_condition = " AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
}

try {
    // Products Uploaded
    $stmt_prod = $pdo->prepare("SELECT COUNT(*) FROM partner_products WHERE partner_id = :id" . $date_condition);
    $stmt_prod->execute([':id' => $partner_id]);
    $uploads = $stmt_prod->fetchColumn();

    // Active Orders
    $stmt_orders = $pdo->prepare("SELECT COUNT(*) FROM partner_orders WHERE partner_id = :id AND status IN ('pending', 'accepted', 'in_progress', 'waiting_confirmation', 'disputed')" . $date_condition);
    $stmt_orders->execute([':id' => $partner_id]);
    $active_orders = $stmt_orders->fetchColumn();

    // Total Earned (Completed or Paid orders)
    $stmt_earned = $pdo->prepare("SELECT SUM(total_price) FROM partner_orders WHERE partner_id = :id AND (status = 'completed' OR payment_status = 'paid')" . $date_condition);
    $stmt_earned->execute([':id' => $partner_id]);
    $total_earned = $stmt_earned->fetchColumn();

    if ($filter === 'all') {
        // Fallback to the total_earned column in partners table for ALL time to be completely accurate with past data
        $stmt_total = $pdo->prepare("SELECT total_earned FROM partners WHERE id = :id");
        $stmt_total->execute([':id' => $partner_id]);
        $total_earned = $stmt_total->fetchColumn();
    }

    echo json_encode([
        'success' => true,
        'uploads' => (int)$uploads,
        'active_orders' => (int)$active_orders,
        'total_earned' => floatval($total_earned)
    ]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
