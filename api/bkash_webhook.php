<?php
// =========================================================================
// api/bkash_webhook.php — Automated IPN Webhook for bKash Deposits
// =========================================================================
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');

// Get raw POST payload from bKash Gateway
$payload = file_get_contents('php://input');
$data = json_decode($payload, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON payload']);
    exit;
}

// bKash usually sends: transactionStatus, amount, trxID, reference (we use reference as our deposit_id)
$status = $data['transactionStatus'] ?? '';
$amount = floatval($data['amount'] ?? 0);
$trx_id = $data['trxID'] ?? '';
$reference_id = intval($data['reference'] ?? 0);

if ($status !== 'Completed' || $amount <= 0 || empty($trx_id) || !$reference_id) {
    echo json_encode(['status' => 'ignored', 'message' => 'Transaction not completed or missing data.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Find the pending deposit request
    $stmt = $pdo->prepare("SELECT * FROM deposit_requests WHERE id = :id AND status = 'pending' FOR UPDATE");
    $stmt->execute([':id' => $reference_id]);
    $deposit = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$deposit) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Pending deposit not found or already processed.']);
        exit;
    }

    // Optional: Validate amount matches what the user requested
    if (floatval($deposit['amount_bdt']) != $amount) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Amount mismatch. manual review required.']);
        exit;
    }

    $user_id = $deposit['user_id'];
    $coins_to_add = floatval($deposit['coins_value']);

    // 2. Mark deposit as approved
    $updDep = $pdo->prepare("UPDATE deposit_requests SET status = 'approved', transaction_id = :trx, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
    $updDep->execute([':trx' => $trx_id, ':id' => $reference_id]);

    // 3. Update Wallet Balance
    $stmtW = $pdo->prepare("SELECT id, balance FROM coin_wallets WHERE user_id = :uid FOR UPDATE");
    $stmtW->execute([':uid' => $user_id]);
    $wallet = $stmtW->fetch(PDO::FETCH_ASSOC);
    
    if (!$wallet) {
        $pdo->prepare("INSERT INTO coin_wallets (user_id, balance) VALUES (:uid, :bal)")->execute([':uid' => $user_id, ':bal' => $coins_to_add]);
        $wallet_id = $pdo->lastInsertId();
    } else {
        $wallet_id = $wallet['id'];
        $pdo->prepare("UPDATE coin_wallets SET balance = balance + :amt WHERE id = :wid")->execute([':amt' => $coins_to_add, ':wid' => $wallet_id]);
    }

    // 4. Log Transaction
    $pdo->prepare("INSERT INTO coin_transactions (wallet_id, user_id, type, amount, description, reference_id) 
                   VALUES (:wid, :uid, 'deposit', :amt, 'Automated bKash Deposit (IPN)', :ref)")
        ->execute([':wid' => $wallet_id, ':uid' => $user_id, ':amt' => $coins_to_add, ':ref' => $reference_id]);

    // 5. Send Notification
    $pdo->prepare("INSERT INTO user_notifications (user_id, title, message) VALUES (:uid, 'Deposit Approved', :msg)")
        ->execute([
            ':uid' => $user_id,
            ':msg' => "Your deposit of " . $amount . " BDT was instantly approved via bKash! " . $coins_to_add . " Coins added to your wallet."
        ]);

    $pdo->commit();
    echo json_encode(['status' => 'success', 'message' => 'Wallet instantly credited via Webhook.']);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
}
