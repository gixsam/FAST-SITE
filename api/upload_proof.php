<?php
// =========================================================================
// api/upload_proof.php  –  Vendor Upload Proof & Deliver API
// =========================================================================
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['partner_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

require_once __DIR__ . '/../config.php';

$partner_id = $_SESSION['partner_id'];
$order_id = intval($_POST['order_id'] ?? 0);

if ($order_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID.']);
    exit;
}

if (!isset($_FILES['proof_file']) || $_FILES['proof_file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Valid delivery proof file is required.']);
    exit;
}

try {
    // Verify order status
    $stmt = $pdo->prepare("SELECT status FROM partner_orders WHERE id = :id AND partner_id = :partner_id LIMIT 1");
    $stmt->execute([':id' => $order_id, ':partner_id' => $partner_id]);
    $status = $stmt->fetchColumn();

    if ($status === 'accepted' || $status === 'in_progress' || $status === 'pending') {
        $upload_dir = '../uploads/proofs/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $ext = pathinfo($_FILES['proof_file']['name'], PATHINFO_EXTENSION);
        $filename = 'proof_' . $order_id . '_' . time() . '.' . $ext;

        if (move_uploaded_file($_FILES['proof_file']['tmp_name'], $upload_dir . $filename)) {
            $stmt_up = $pdo->prepare("UPDATE partner_orders 
                SET partner_proof = :proof, status = 'waiting_confirmation' 
                WHERE id = :id AND partner_id = :partner_id");
            $stmt_up->execute([
                ':proof'      => $filename,
                ':id'         => $order_id,
                ':partner_id' => $partner_id
            ]);

            echo json_encode([
                'success' => true, 
                'message' => 'Proof uploaded and delivery status updated.', 
                'file_url' => 'uploads/proofs/' . $filename
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save proof file.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Order is not in a deliverable state.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
