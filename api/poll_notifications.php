<?php
// =========================================================================
// api/poll_notifications.php — Real-Time Notification & Alert Polling Engine
// =========================================================================
require_once __DIR__ . '/../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

$is_user = isset($_SESSION['user_id']);
$is_admin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$is_staff = isset($_SESSION['staff_id']);

if (!$is_user && !$is_admin && !$is_staff) {
    echo json_encode([
        'status' => 'guest',
        'unread_count' => 0,
        'has_new_alert' => false
    ]);
    exit;
}

$user_id = $_SESSION['user_id'] ?? 0;
$unread_count = 0;
$latest_alert = null;

try {
    if ($is_user) {
        // 1. User Notifications
        $stmt = $pdo->prepare("SELECT * FROM user_notifications WHERE user_id = ? AND is_read = 0 ORDER BY id DESC LIMIT 5");
        $stmt->execute([$user_id]);
        $unread_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $unread_count = count($unread_items);

        // 2. Partner Orders (if user owns a shop)
        $partnerStmt = $pdo->prepare("SELECT id FROM partners WHERE user_id = ? AND status = 'approved' LIMIT 1");
        $partnerStmt->execute([$user_id]);
        $partner_id = $partnerStmt->fetchColumn();

        if ($partner_id) {
            $pOrdersStmt = $pdo->prepare("SELECT COUNT(*) FROM partner_orders WHERE partner_id = ? AND status = 'pending'");
            $pOrdersStmt->execute([$partner_id]);
            $pending_shop_orders = (int)$pOrdersStmt->fetchColumn();
            $unread_count += $pending_shop_orders;

            if ($pending_shop_orders > 0 && empty($unread_items)) {
                $latest_alert = [
                    'id' => 'shop_pending_' . $partner_id,
                    'title' => '🛍️ New Shop Order Received!',
                    'message' => "You have {$pending_shop_orders} new order(s) waiting for shipment in your Shop portal.",
                    'url' => '/partner/orders.php'
                ];
            }
        }

        if (!empty($unread_items) && !$latest_alert) {
            $top = $unread_items[0];
            $latest_alert = [
                'id' => 'notif_' . $top['id'],
                'title' => $top['title'] ?? '🔔 Fast Site Update',
                'message' => $top['message'] ?? '',
                'url' => '/user/dashboard.php?tab=notifications'
            ];
        }
    } elseif ($is_admin || $is_staff) {
        // Admin / Staff pending alerts
        $st_app = $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'pending'")->fetchColumn();
        $st_wdr = $pdo->query("SELECT COUNT(*) FROM user_withdrawals WHERE status = 'pending'")->fetchColumn();
        $unread_count = (int)$st_app + (int)$st_wdr;

        if ($unread_count > 0) {
            $latest_alert = [
                'id' => 'admin_alert_' . time(),
                'title' => '⚡ Admin Action Required',
                'message' => "{$unread_count} pending request(s) awaiting administrative review.",
                'url' => '/admin/dashboard.php'
            ];
        }
    }

    echo json_encode([
        'status' => 'success',
        'unread_count' => $unread_count,
        'has_new_alert' => ($latest_alert !== null),
        'latest_alert' => $latest_alert,
        'timestamp' => time()
    ]);
    exit;

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'unread_count' => 0,
        'has_new_alert' => false
    ]);
    exit;
}
