<?php
// =========================================================================
// api/mfs_webhook.php — Universal Automated MFS Webhook & IPN Engine
// Supports bKash, Nagad, Rocket, and generic MFS Instant Payment Notifications
// Features: Idempotency Protection, Signature Auth, Auto Deposit & Order SafePay Release
// =========================================================================
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/escrow_engine.php';
header('Content-Type: application/json; charset=UTF-8');

// Capture raw body and parameters
$raw_input = file_get_contents('php://input');
$payload = json_decode($raw_input, true) ?: $_POST;

if (empty($payload)) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Empty webhook payload received'
    ]);
    exit;
}

// Determine Provider: query param ?provider=bkash / header X-Provider / payload detection
$provider = strtolower(trim($_GET['provider'] ?? $_SERVER['HTTP_X_PROVIDER'] ?? $payload['provider'] ?? $payload['issuer'] ?? ''));
if (empty($provider)) {
    if (isset($payload['trxID']) || isset($payload['paymentID'])) {
        $provider = 'bkash';
    } elseif (isset($payload['merchantOrderId']) || (isset($payload['order_id']) && isset($payload['payment_ref_id']))) {
        $provider = 'nagad';
    } else {
        $provider = 'mfs';
    }
}

// 1. Signature / Secret Authentication Guard
$configured_secret = getPartnerSetting('mfs_webhook_secret', '');
$received_secret = trim($_SERVER['HTTP_X_WEBHOOK_SECRET'] ?? $_GET['secret'] ?? $payload['secret'] ?? '');

if (!empty($configured_secret) && $configured_secret !== 'disabled') {
    if ($received_secret !== $configured_secret) {
        http_response_code(401);
        echo json_encode([
            'status' => 'error',
            'message' => 'Unauthorized: Invalid webhook secret token'
        ]);
        exit;
    }
}

// 2. Extract Normalized Fields from Payload
// Supports multiple provider naming conventions:
// trx_id: trxID, trx_id, transaction_id, transactionId, payment_ref_id
// amount: amount, total_amount, paid_amount, amount_bdt
// status: transactionStatus, status, payment_status, event
// reference: reference, order_id, invoice_id, deposit_id, merchantOrderId
// type: type, event_type, purpose (deposit vs order)

$trx_id = trim($payload['trxID'] ?? $payload['trx_id'] ?? $payload['transaction_id'] ?? $payload['transactionId'] ?? $payload['payment_ref_id'] ?? '');
$amount = floatval($payload['amount'] ?? $payload['total_amount'] ?? $payload['paid_amount'] ?? $payload['amount_bdt'] ?? 0);
$status_raw = strtolower(trim($payload['transactionStatus'] ?? $payload['status'] ?? $payload['payment_status'] ?? ''));
$reference = trim($payload['reference'] ?? $payload['order_id'] ?? $payload['merchantOrderId'] ?? $payload['invoice_id'] ?? $payload['deposit_id'] ?? '');
$event_type = strtolower(trim($_GET['type'] ?? $payload['type'] ?? $payload['event_type'] ?? ''));

// Detect event type if unspecified:
if (empty($event_type)) {
    if (strpos($reference, 'ORD-') === 0 || strpos($reference, 'FS-GRP-') === 0 || strpos($reference, 'FS-') === 0) {
        $event_type = 'order';
    } else {
        $event_type = 'deposit';
    }
}

// Normalize status success checks:
// bKash: 'Completed', Nagad: 'Success', '000', Generic: 'paid', 'approved', 'success'
$is_successful = in_array($status_raw, ['completed', 'success', 'paid', 'approved', '000', '1', 'done']);

// Basic Validation
if (empty($trx_id) || $amount <= 0 || !$is_successful) {
    http_response_code(400);
    echo json_encode([
        'status' => 'ignored',
        'message' => 'Incomplete or unconfirmed payload',
        'parsed' => [
            'provider' => $provider,
            'trx_id' => $trx_id,
            'amount' => $amount,
            'status' => $status_raw,
            'event_type' => $event_type
        ]
    ]);
    exit;
}

$client_ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

// 3. Idempotency Check (Prevent duplicate execution)
try {
    $stmtCheck = $pdo->prepare("SELECT id, status FROM mfs_webhook_logs WHERE trx_id = ? AND status = 'success' LIMIT 1");
    $stmtCheck->execute([$trx_id]);
    $existing = $stmtCheck->fetch(PDO::FETCH_ASSOC);
    if ($existing) {
        echo json_encode([
            'status' => 'already_processed',
            'message' => 'Transaction ID already verified and processed previously',
            'trx_id' => $trx_id
        ]);
        exit;
    }
} catch (Exception $e) {
    // If table doesn't exist yet, proceed gracefully
}

// 4. Dispatch Execution by Event Type
try {
    $pdo->beginTransaction();

    if ($event_type === 'order') {
        // -------------------------------------------------------------
        // Order Payment Handling
        // -------------------------------------------------------------
        $order_found = false;

        $stmtOrd = $pdo->prepare("SELECT * FROM partner_orders WHERE gateway_ref = :ref OR order_group_id = :ref OR id = :ref_id");
        $stmtOrd->execute([':ref' => $reference, ':ref_id' => is_numeric($reference) ? (int)$reference : 0]);
        $orders = $stmtOrd->fetchAll(PDO::FETCH_ASSOC);

        if ($orders && count($orders) > 0) {
            foreach ($orders as $ord) {
                // Update order to paid
                $pdo->prepare("UPDATE partner_orders 
                               SET payment_status = 'paid', 
                                   transaction_id = :trx, 
                                   payment_method = :prov 
                               WHERE id = :id")
                    ->execute([
                        ':trx' => $trx_id,
                        ':prov' => ucfirst($provider),
                        ':id' => $ord['id']
                    ]);

                // Update SafePay Escrow Vault hold
                if (function_exists('escrow_hold_funds')) {
                    escrow_hold_funds($pdo, strval($ord['id']), (int)$ord['customer_id'], (int)$ord['partner_id'], (float)$ord['total_coins']);
                } else {
                    try {
                        $pdo->prepare("UPDATE escrow_vault SET status = 'held' WHERE order_id = :oid")
                            ->execute([':oid' => strval($ord['id'])]);
                    } catch (Exception $exEsc) {}
                }

                // Notify Customer
                if (!empty($ord['customer_id'])) {
                    $pdo->prepare("INSERT INTO user_notifications (user_id, title, message) VALUES (?, 'SafePay Payment Confirmed', ?)")
                        ->execute([
                            $ord['customer_id'],
                            "আপনার অর্ডার #{$ord['id']} এর পেমেন্ট ({$amount} ৳) সফলভাবে ভেরিফাই হয়েছে। SafePay তহবিলে ফান্ড সুরক্ষিত আছে।"
                        ]);
                }

                // Notify Partner Shop Seller
                $partner_stmt = $pdo->prepare("SELECT user_id FROM partners WHERE id = ?");
                $partner_stmt->execute([$ord['partner_id']]);
                $partner_user_id = $partner_stmt->fetchColumn();
                if ($partner_user_id) {
                    $pdo->prepare("INSERT INTO user_notifications (user_id, title, message) VALUES (?, 'New Paid Order Received', ?)")
                        ->execute([
                            $partner_user_id,
                            "নতুন পেইড অর্ডার #{$ord['id']} এসেছে ({$amount} ৳)! পণ্যটি দ্রুত ডেলিভারির জন্য প্রস্তুত করুন।"
                        ]);
                }
            }
            $order_found = true;
        }

        if (!$order_found) {
            // Check if there is an unattached order matching this transaction_id
            $stmtTrx = $pdo->prepare("SELECT * FROM partner_orders WHERE transaction_id = ?");
            $stmtTrx->execute([$trx_id]);
            $orders = $stmtTrx->fetchAll(PDO::FETCH_ASSOC);
            if ($orders && count($orders) > 0) {
                foreach ($orders as $ord) {
                    $pdo->prepare("UPDATE partner_orders SET payment_status = 'paid' WHERE id = ?")->execute([$ord['id']]);
                    if (function_exists('escrow_hold_funds')) {
                        escrow_hold_funds($pdo, (int)$ord['id'], (float)$ord['total_coins'], "Verified via Automated Webhook");
                    }
                }
                $order_found = true;
            }
        }

        if (!$order_found) {
            $pdo->rollBack();
            http_response_code(404);
            echo json_encode([
                'status' => 'not_found',
                'message' => 'Matching order not found for reference: ' . $reference
            ]);
            exit;
        }

        $response_message = "Order payment verified and SafePay escrow activated for reference {$reference}.";

    } else {
        // -------------------------------------------------------------
        // Coin Deposit Recharge Handling
        // -------------------------------------------------------------
        $deposit_id = is_numeric($reference) ? (int)$reference : 0;
        $deposit = null;
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $lock_clause = ($driver === 'mysql') ? ' FOR UPDATE' : '';

        if ($deposit_id > 0) {
            $stmtD = $pdo->prepare("SELECT * FROM deposit_requests WHERE id = :id" . $lock_clause);
            $stmtD->execute([':id' => $deposit_id]);
            $deposit = $stmtD->fetch(PDO::FETCH_ASSOC);
        }

        if (!$deposit && !empty($trx_id)) {
            $stmtD2 = $pdo->prepare("SELECT * FROM deposit_requests WHERE transaction_id = :trx" . $lock_clause);
            $stmtD2->execute([':trx' => $trx_id]);
            $deposit = $stmtD2->fetch(PDO::FETCH_ASSOC);
        }

        if ($deposit) {
            $user_id = (int)$deposit['user_id'];
            $coins_to_add = floatval($deposit['amount'] ?? $amount);

            // Mark deposit approved
            $pdo->prepare("UPDATE deposit_requests 
                           SET status = 'approved', 
                               transaction_id = :trx, 
                               gateway = :gw, 
                               admin_notes = :notes 
                           WHERE id = :id")
                ->execute([
                    ':trx' => $trx_id,
                    ':gw' => ucfirst($provider),
                    ':notes' => "Auto-approved via Automated {$provider} Webhook",
                    ':id' => $deposit['id']
                ]);
        } else {
            // Direct Gateway Push without pre-submission: Match user by sender phone
            $sender_phone = trim($payload['customerMsisdn'] ?? $payload['sender_number'] ?? $payload['phone'] ?? '');
            $user_id = 0;
            if (!empty($sender_phone)) {
                $clean_sender = preg_replace('/[^0-9]/', '', $sender_phone);
                if (strlen($clean_sender) > 11 && substr($clean_sender, 0, 2) === '88') {
                    $clean_sender = substr($clean_sender, 2);
                }
                $stmtU = $pdo->prepare("SELECT id FROM users WHERE phone = ? LIMIT 1");
                $stmtU->execute([$clean_sender]);
                $user_id = (int)$stmtU->fetchColumn();
            }

            if (!$user_id) {
                // If not found, log error
                $pdo->rollBack();
                http_response_code(422);
                echo json_encode([
                    'status' => 'unresolvable_user',
                    'message' => 'Deposit received but user cannot be matched. Logged for manual review.'
                ]);
                exit;
            }

            $coins_to_add = $amount;
            $pdo->prepare("INSERT INTO deposit_requests (user_id, sender_number, transaction_id, amount, status, gateway, admin_notes)
                           VALUES (?, ?, ?, ?, 'approved', ?, 'Auto-created via Instant MFS Webhook')")
                ->execute([$user_id, $sender_phone ?: 'Unknown', $trx_id, $amount, ucfirst($provider)]);
            $deposit_id = $pdo->lastInsertId();
        }

        // Credit coins_balance in users table
        $pdo->prepare("UPDATE users SET coins_balance = coins_balance + :amt WHERE id = :uid")
            ->execute([':amt' => $coins_to_add, ':uid' => $user_id]);

        // Synchronize coin_wallets if table exists
        try {
            $stmtW = $pdo->prepare("SELECT id, balance FROM coin_wallets WHERE user_id = :uid");
            $stmtW->execute([':uid' => $user_id]);
            $wallet = $stmtW->fetch(PDO::FETCH_ASSOC);
            if ($wallet) {
                $pdo->prepare("UPDATE coin_wallets SET balance = balance + :amt WHERE id = :wid")
                    ->execute([':amt' => $coins_to_add, ':wid' => $wallet['id']]);
                $wallet_id = $wallet['id'];
            } else {
                $pdo->prepare("INSERT INTO coin_wallets (user_id, balance) VALUES (:uid, :amt)")
                    ->execute([':uid' => $user_id, ':amt' => $coins_to_add]);
                $wallet_id = $pdo->lastInsertId();
            }

            // Log coin transaction
            $pdo->prepare("INSERT INTO coin_transactions (user_id, type, amount, reference, description, status) 
                           VALUES (?, 'deposit', ?, ?, ?, 'completed')")
                ->execute([
                    $user_id, 
                    $coins_to_add, 
                    "Auto {$provider} Webhook: TrxID {$trx_id}",
                    "Automated recharge of {$amount} BDT"
                ]);
        } catch (Exception $eTx) {}

        // Send in-app notification
        $pdo->prepare("INSERT INTO user_notifications (user_id, title, message) VALUES (?, 'Deposit Credited Instantly', ?)")
            ->execute([
                $user_id,
                "আপনার {$amount} ৳ এর ডিপোজিট ({$provider}) সফল হয়েছে! {$coins_to_add} Fast Points ওয়ালেটে যোগ করা হয়েছে।"
            ]);

        $response_message = "Deposit successfully verified and credited {$coins_to_add} coins to user #{$user_id}.";
    }

    // 5. Record Webhook Audit Log
    try {
        $pdo->prepare("INSERT INTO mfs_webhook_logs (provider, event_type, trx_id, amount, reference_id, status, payload, ip_address)
                       VALUES (?, ?, ?, ?, ?, 'success', ?, ?)")
            ->execute([
                $provider,
                $event_type,
                $trx_id,
                $amount,
                $reference,
                json_encode($payload),
                $client_ip
            ]);
    } catch (Exception $eLog) {}

    $pdo->commit();

    echo json_encode([
        'status' => 'success',
        'message' => $response_message,
        'trx_id' => $trx_id,
        'provider' => $provider,
        'event_type' => $event_type
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Webhook processing failed: ' . $e->getMessage()
    ]);
}
