<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php'); exit;
}
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payout_ids']) && is_array($_POST['payout_ids'])) {
    
    // Prepare CSV headers
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="mass_payout_' . date('Ymd_Hi') . '.csv"');
    
    $output = fopen('php://output', 'w');
    // bKash mass payout typically expects: 
    // Phone Number, Amount, Reference (Optional)
    fputcsv($output, ['Phone Number', 'Amount', 'Reference', 'Payout ID']);
    
    $ids = array_map('intval', $_POST['payout_ids']);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    
    $stmt = $pdo->prepare("SELECT p.id, p.amount, p.bkash_number, a.name 
                           FROM agent_payouts p
                           JOIN agents a ON p.agent_id = a.id
                           WHERE p.id IN ($placeholders) AND p.status = 'pending'");
    $stmt->execute($ids);
    
    $total_marked = 0;
    while ($row = $stmt->fetch()) {
        fputcsv($output, [$row['bkash_number'], $row['amount'], "Ref_".$row['id'], $row['id']]);
        
        if (isset($_POST['mark_paid']) && $_POST['mark_paid'] == '1') {
            $pdo->prepare("UPDATE agent_payouts SET status='paid', paid_at=NOW(), admin_note='Mass Payout Export' WHERE id=?")
                ->execute([$row['id']]);
            $total_marked++;
        }
    }
    
    fclose($output);
    exit;
} else {
    header('Location: payouts.php?err=No records selected');
    exit;
}
