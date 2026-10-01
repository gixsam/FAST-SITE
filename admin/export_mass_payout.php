<?php
// =========================================================================
// admin/export_mass_payout.php — Universal MFS Bulk Disbursement CSV Exporter
// Formatted for official bKash Merchant Bulk Disburse & Nagad Corporate Disburse
// =========================================================================
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/../config.php';

$payout_type = trim($_POST['payout_type'] ?? 'agent'); // 'agent' or 'user'
$format = strtolower(trim($_POST['format'] ?? 'bkash')); // 'bkash' or 'nagad'

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payout_ids']) && is_array($_POST['payout_ids']) && count($_POST['payout_ids']) > 0) {
    
    $ids = array_map('intval', $_POST['payout_ids']);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    
    $filename = "mfs_disburse_{$format}_{$payout_type}_" . date('Ymd_His') . ".csv";

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    // UTF-8 BOM for Microsoft Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    if ($format === 'nagad') {
        // Nagad Corporate Disburse Standard Format
        fputcsv($output, ['Account Number', 'Amount (BDT)', 'Reference', 'Customer Name', 'Disbursement ID']);
    } else {
        // bKash Merchant Bulk Disburse Standard Format
        fputcsv($output, ['Receiver MSISDN', 'Amount', 'Reference', 'Merchant Invoice No', 'Recipient Name']);
    }
    
    if ($payout_type === 'user') {
        // Export from user_withdrawals
        $stmt = $pdo->prepare("SELECT w.id, w.amount_coins, w.payout_method, w.payout_account, u.name as user_name, u.phone as user_phone
                               FROM user_withdrawals w
                               JOIN users u ON w.user_id = u.id
                               WHERE w.id IN ($placeholders) AND w.status = 'pending'");
        $stmt->execute($ids);
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $phone = preg_replace('/[^0-9]/', '', $row['payout_account']);
            if (strlen($phone) === 10 && substr($phone, 0, 1) === '1') {
                $phone = '0' . $phone;
            }
            $amt = number_format((float)$row['amount_coins'], 2, '.', '');
            $ref = "WDR-" . $row['id'];
            
            if ($format === 'nagad') {
                fputcsv($output, [$phone, $amt, $ref, $row['user_name'], $row['id']]);
            } else {
                fputcsv($output, [$phone, $amt, $ref, "INV-" . $row['id'], $row['user_name']]);
            }
            
            if (isset($_POST['mark_paid']) && $_POST['mark_paid'] == '1') {
                $pdo->prepare("UPDATE user_withdrawals SET status='approved', processed_at=CURRENT_TIMESTAMP WHERE id=?")
                    ->execute([$row['id']]);
            }
        }
    } else {
        // Export from agent_payouts
        $stmt = $pdo->prepare("SELECT p.id, p.amount, p.bkash_number, a.name, a.ref_code 
                               FROM agent_payouts p
                               JOIN agents a ON p.agent_id = a.id
                               WHERE p.id IN ($placeholders) AND p.status = 'pending'");
        $stmt->execute($ids);
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $phone = preg_replace('/[^0-9]/', '', $row['bkash_number']);
            if (strlen($phone) === 10 && substr($phone, 0, 1) === '1') {
                $phone = '0' . $phone;
            }
            $amt = number_format((float)$row['amount'], 2, '.', '');
            $ref = "AGT-" . $row['id'];
            
            if ($format === 'nagad') {
                fputcsv($output, [$phone, $amt, $ref, $row['name'], $row['id']]);
            } else {
                fputcsv($output, [$phone, $amt, $ref, "AGT-INV-" . $row['id'], $row['name']]);
            }
            
            if (isset($_POST['mark_paid']) && $_POST['mark_paid'] == '1') {
                $pdo->prepare("UPDATE agent_payouts SET status='paid', paid_at=CURRENT_TIMESTAMP, admin_note='Bulk Disburse Export' WHERE id=?")
                    ->execute([$row['id']]);
            }
        }
    }
    
    fclose($output);
    exit;
} else {
    header('Location: payouts.php?err=No records selected for mass payout export');
    exit;
}
