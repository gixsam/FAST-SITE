<?php
// ============================================================
// submit_order.php  –  v2: snapshots fee from services table
// ============================================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success'=>false,'message'=>'Method not allowed.']);
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Authentication required. Please login as a customer first.']);
    exit;
}
$userId = $_SESSION['user_id'];

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!$data) {
    echo json_encode(['success'=>false,'message'=>'Invalid JSON payload.']);
    exit;
}

function clean($v) { return ($v === null || $v === '') ? null : trim($v); }

$name     = clean($data['user_name']      ?? null);
$phone    = clean($data['user_phone']     ?? null);
$service  = clean($data['service']        ?? null);
$email    = clean($data['user_email']     ?? null);
$nid      = clean($data['nid_number']     ?? null);
$passport = clean($data['passport_number']?? null);
$dlicense = clean($data['driving_license']?? null);
$details  = clean($data['details']        ?? null);

if (!$name || !$phone || !$service) {
    echo json_encode(['success'=>false,'message'=>'Name, phone and service are required.']);
    exit;
}

// ── Look up service & fee ────────────────────────────────────
$svc = $pdo->prepare(
    "SELECT `id`, `fee` FROM `services` WHERE `name` = :n AND `is_active` = 1 LIMIT 1"
);
$svc->execute([':n' => $service]);
$svcRow = $svc->fetch();

if (!$svcRow) {
    echo json_encode(['success'=>false,'message'=>'Selected service not found or inactive.']);
    exit;
}

$service_id   = (int)$svcRow['id'];
$snapshot_fee = (float)$svcRow['fee'];

// ── Check user wallet points ─────────────────────────────────
$stmt_wallet = $pdo->prepare("SELECT balance FROM coin_wallets WHERE user_id = :uid LIMIT 1");
$stmt_wallet->execute([':uid' => $userId]);
$wallet = $stmt_wallet->fetch();

$balance = $wallet ? floatval($wallet['balance']) : 0.0;
if ($balance < $snapshot_fee) {
    echo json_encode(['success'=>false, 'message'=>'Insufficient wallet points. Please deposit more points first.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Deduct points from wallet
    $pdo->prepare("UPDATE coin_wallets SET balance = balance - :fee WHERE user_id = :uid")
        ->execute([':fee' => $snapshot_fee, ':uid' => $userId]);

    // 2. Insert application record
    $ins = $pdo->prepare("
        INSERT INTO `applications`
            (`service_id`,`fee`,`user_name`,`user_phone`,`user_email`,
             `nid_number`,`passport_number`,`driving_license`,`details`,`status`)
        VALUES
            (:sid,:fee,:name,:phone,:email,
             :nid,:passport,:dl,:details,'pending')
    ");
    $ins->execute([
        ':sid'      => $service_id,
        ':fee'      => $snapshot_fee,
        ':name'     => $name,
        ':phone'    => $phone,
        ':email'    => $email,
        ':nid'      => $nid,
        ':passport' => $passport,
        ':dl'       => $dlicense,
        ':details'  => $details,
    ]);

    $newId = $pdo->lastInsertId();
    $ref   = 'FS-' . str_pad($newId, 6, '0', STR_PAD_LEFT);

    // 3. Log coin transaction record
    $pdo->prepare("INSERT INTO coin_transactions 
        (user_id, type, amount, reference, status) 
        VALUES (:uid, 'service_payment', :fee, :ref_log, 'completed')")
        ->execute([
            ':uid'     => $userId,
            ':fee'     => $snapshot_fee,
            ':ref_log' => "Service Order #$newId (Ref: $ref)"
        ]);

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success'=>false, 'message'=>'Transaction failed: ' . $e->getMessage()]);
    exit;
}

// ── Link to chat session if provided ─────────────────────────
$chatSession = clean($data['session_id'] ?? null);
if ($chatSession) {
    $pdo->prepare(
        "UPDATE `chat_sessions` SET `application_id`=:aid WHERE `session_id`=:sid"
    )->execute([':aid' => $newId, ':sid' => $chatSession]);
}

// ── API Partner Validation & Tracking ────────────────────────
$apiKey = clean($data['api_key'] ?? null);
if (!$apiKey) {
    // Check X-API-Key HTTP header as fallback
    $headers = array_change_key_case(getallheaders(), CASE_LOWER);
    $apiKey = clean($headers['x-api-key'] ?? null);
}

$apiPartner = null;
if ($apiKey) {
    // Check if table exists (dynamic schema check)
    try {
        $chkTable = $pdo->query("SELECT 1 FROM api_partners LIMIT 1");
        if ($chkTable) {
            $stmt = $pdo->prepare("SELECT id, name, status FROM api_partners WHERE api_key = :key LIMIT 1");
            $stmt->execute([':key' => $apiKey]);
            $apiPartner = $stmt->fetch();
            
            if (!$apiPartner) {
                echo json_encode(['success' => false, 'message' => 'Invalid API key.']);
                exit;
            }
            if ($apiPartner['status'] !== 'active') {
                echo json_encode(['success' => false, 'message' => 'API Partner account is inactive.']);
                exit;
            }
            
            // Valid API partner - increment total_orders and tag order details
            $pdo->prepare("UPDATE api_partners SET total_orders = total_orders + 1 WHERE id = :id")
                ->execute([':id' => $apiPartner['id']]);
                
            // Update the newly created application to prepend the API partner info to details
            $detailsTag = "[API Partner: " . $apiPartner['name'] . "]";
            $updatedDetails = $details ? $detailsTag . " - " . $details : $detailsTag;
            $pdo->prepare("UPDATE `applications` SET `details` = :det WHERE `id` = :id")
                ->execute([':det' => $updatedDetails, ':id' => $newId]);
        }
    } catch (Exception $e) {
        // Table doesn't exist yet, ignore
    }
}

// 🪙 User Cashback Reward 🪙
try {
    // getPartnerSetting is not globally available here, we'll fetch direct or default to 2
    $cashback_pct = 2;
    $setStmt = $pdo->prepare("SELECT setting_value FROM homepage_settings WHERE setting_key = 'cashback_pct'");
    $setStmt->execute();
    if ($setRow = $setStmt->fetch()) {
        $cashback_pct = floatval($setRow['setting_value']);
    }
    if ($cashback_pct > 0) {
        $cashback_amt = round($snapshot_fee * ($cashback_pct / 100), 2);
        if ($cashback_amt > 0) {
            $pdo->prepare("UPDATE users SET coins_balance = coins_balance + :amt WHERE id = :uid")
                ->execute([':amt' => $cashback_amt, ':uid' => $userId]);
        }
    }
} catch (Exception $e) {}

// 🔗 Affiliate commission tracking 🔗────────────────────────────
$refCode = isset($_COOKIE['fastsite_ref']) ? strtoupper(trim($_COOKIE['fastsite_ref'])) : '';
if ($refCode) {
    try {
        // First check agents
        $agentStmt = $pdo->prepare("SELECT id, phone FROM agents WHERE ref_code = :c AND status = 'active' LIMIT 1");
        $agentStmt->execute([':c' => $refCode]);
        $agent = $agentStmt->fetch();

        $commission = round($snapshot_fee * 0.20, 2); // 20% commission
        if ($commission > 0) {
            if ($agent) {
                // Block self-referral
                $isSelfRef = preg_replace('/\D/', '', $agent['phone']) === preg_replace('/\D/', '', $phone ?? '');
                if (!$isSelfRef) {
                    $pdo->prepare("INSERT INTO `agent_commissions` (`agent_id`,`application_id`,`order_ref`,`service_name`,`order_fee`,`commission_amount`,`status`) VALUES (:aid,:appid,:ref,:svc,:fee,:com,'pending')")
                        ->execute([':aid'=>$agent['id'], ':appid'=>$newId, ':ref'=>$ref, ':svc'=>$service, ':fee'=>$snapshot_fee, ':com'=>$commission]);
                    $pdo->prepare("UPDATE agents SET total_earned = total_earned + :com WHERE id = :id")
                        ->execute([':com' => $commission, ':id' => $agent['id']]);
                }
            } else {
                // Not an agent, check if it's a regular user
                $userStmt = $pdo->prepare("SELECT id, phone FROM users WHERE ref_code = :c LIMIT 1");
                $userStmt->execute([':c' => $refCode]);
                $user = $userStmt->fetch();
                if ($user) {
                    $isSelfRef = preg_replace('/\D/', '', $user['phone']) === preg_replace('/\D/', '', $phone ?? '');
                    if (!$isSelfRef) {
                        try {
                            $pdo->prepare("UPDATE users SET missed_commissions = missed_commissions + :amt WHERE id = :uid")
                                ->execute([':uid'=>$user['id'], ':amt'=>$commission]);
                        } catch(Exception $e) {}
                    }
                }
            }
        }
    } catch (Exception $e) {
        // Silent fail — don't break the order if affiliate tracking fails
    }
}

echo json_encode([
    'success' => true,
    'ref'     => $ref,
    'id'      => (int)$newId,
    'fee'     => number_format($snapshot_fee, 2),
]);
