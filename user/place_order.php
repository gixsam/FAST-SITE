<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /user/login.php');
    exit;
}

require_once __DIR__ . '/../config.php';
$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = intval($_POST['product_id'] ?? 0);
    $partnerId = intval($_POST['partner_id'] ?? 0);
    $price = floatval($_POST['price'] ?? 0.0);
    
    $payment_method = 'coins'; // Strictly coins now
    $delivery_location = trim($_POST['delivery_location'] ?? 'inside_dhaka');
    $delivery_charge = floatval($_POST['delivery_charge'] ?? 60.0);
    $total_cost = $price + $delivery_charge;
    
    $recipient_name = trim($_POST['recipient_name'] ?? '');
    $recipient_phone = trim($_POST['recipient_phone'] ?? '');
    $address_raw = trim($_POST['shipping_address'] ?? '');
    $customer_submission = trim($_POST['customer_submission'] ?? '');

    // Handle customer document attachments
    $uploaded_files = [];
    if (isset($_FILES['submission_files']) && !empty($_FILES['submission_files']['name'][0])) {
        $upload_dir = '../uploads/order_docs/';
        if (!is_dir($upload_dir)) {
            @mkdir($upload_dir, 0777, true);
        }
        $fCount = count($_FILES['submission_files']['name']);
        for ($i = 0; $i < $fCount; $i++) {
            if ($_FILES['submission_files']['error'][$i] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['submission_files']['name'][$i], PATHINFO_EXTENSION));
                $safeExts = ['jpg', 'jpeg', 'png', 'webp', 'pdf', 'doc', 'docx', 'zip'];
                if (in_array($ext, $safeExts) && $_FILES['submission_files']['size'][$i] <= 20 * 1024 * 1024) {
                    $docName = 'doc_order_' . $userId . '_' . time() . '_' . $i . '.' . $ext;
                    if (move_uploaded_file($_FILES['submission_files']['tmp_name'][$i], $upload_dir . $docName)) {
                        $uploaded_files[] = 'uploads/order_docs/' . $docName;
                    }
                }
            }
        }
    }
    $submission_files_json = !empty($uploaded_files) ? json_encode($uploaded_files) : null;
    
    $sender_number = null;
    $transaction_id = null;
    $gateway_ref = null;

    if ($productId <= 0 || $partnerId <= 0 || $price <= 0.0) {
        die("Invalid order details.");
    }

    if (empty($recipient_name) || empty($recipient_phone) || empty($address_raw)) {
        die("Recipient details and address are required.");
    }

    $shipping_address = "Recipient: $recipient_name\nPhone: $recipient_phone\nAddress: $address_raw";

    // Strict Coin Check
    try {
        $balStmt = $pdo->prepare("SELECT coins_balance FROM users WHERE id = ?");
        $balStmt->execute([$userId]);
        $userBal = floatval($balStmt->fetchColumn());
    } catch (Exception $e) {
        $userBal = 0;
    }

    if ($userBal < $total_cost) {
        // Not enough coins!
        echo "<div style='font-family:sans-serif; text-align:center; margin-top:50px; color:#fff; background:#111; padding:20px; border-radius:12px;'>
                <h2 style='color:#ef4444;'>❌ Insufficient Coins</h2>
                <p>Your balance is 🪙 {$userBal}. The total cost is 🪙 {$total_cost}.</p>
                <a href='/user/deposit.php' style='display:inline-block; margin-top:20px; padding:10px 20px; background:#fcb900; color:#000; font-weight:bold; text-decoration:none; border-radius:6px;'>Buy Coins Now</a>
                <br><br>
                <a href='javascript:history.back()' style='color:#999;'>Go Back</a>
              </div>";
        exit;
    }

    // Deduct coins immediately
    $pdo->prepare("UPDATE users SET coins_balance = coins_balance - ? WHERE id = ?")->execute([$total_cost, $userId]);

    $payment_status = 'paid';

    try {
        try { @$pdo->exec("ALTER TABLE `partner_orders` ADD COLUMN `customer_submission` TEXT DEFAULT NULL"); } catch (Exception $e) {}
        try { @$pdo->exec("ALTER TABLE `partner_orders` ADD COLUMN `submission_files` TEXT DEFAULT NULL"); } catch (Exception $e) {}

        $stmt = $pdo->prepare("INSERT INTO partner_orders 
            (customer_id, partner_id, product_id, total_coins, payment_method, payment_status, sender_number, transaction_id, gateway_ref, delivery_location, delivery_charge, shipping_address, customer_submission, submission_files, status) 
            VALUES (:cid, :pid, :prod_id, :coins, :method, :pay_status, :sender, :trx, :ref, :location, :charge, :address, :csub, :sfiles, 'pending')");
        
        $stmt->execute([
            ':cid'              => $userId,
            ':pid'              => $partnerId,
            ':prod_id'          => $productId,
            ':coins'            => $price,
            ':method'           => $payment_method,
            ':pay_status'       => $payment_status,
            ':sender'           => $sender_number,
            ':trx'              => $transaction_id,
            ':ref'              => $gateway_ref,
            ':location'         => $delivery_location,
            ':charge'           => $delivery_charge,
            ':address'          => $shipping_address,
            ':csub'             => $customer_submission,
            ':sfiles'           => $submission_files_json
        ]);
        $newId = $pdo->lastInsertId();

        // -> Partner Notification <-
        try {
            $stmtP = $pdo->prepare("SELECT user_id FROM partners WHERE id = ?");
            $stmtP->execute([$partnerId]);
            $pUserId = $stmtP->fetchColumn();
            if ($pUserId) {
                // Ensure user_notifications table has the standard columns, using default values if 'type' is missing in schema.
                // It typically has user_id, title, message, link, is_read, created_at.
                $notif_title = "New Shop Order!";
                $notif_msg = "You have received a new order. Please check your shop dashboard.";
                $pdo->prepare("INSERT INTO user_notifications (user_id, title, message, link, is_read) VALUES (?, ?, ?, '../partner/orders.php', 0)")
                    ->execute([$pUserId, $notif_title, $notif_msg]);
            }
        } catch (Exception $e) {
            // Ignore if notification fails (e.g. schema mismatch)
        }

        // 🪙 User Cashback Reward 🪙
        try {
            $cashback_pct = 2;
            $setStmt = $pdo->prepare("SELECT setting_value FROM homepage_settings WHERE setting_key = 'cashback_pct'");
            $setStmt->execute();
            if ($setRow = $setStmt->fetch()) {
                $cashback_pct = floatval($setRow['setting_value']);
            }
            if ($cashback_pct > 0) {
                $cashback_amt = round($price * ($cashback_pct / 100), 2);
                if ($cashback_amt > 0) {
                    $pdo->prepare("UPDATE users SET coins_balance = coins_balance + :amt WHERE id = :uid")
                        ->execute([':amt' => $cashback_amt, ':uid' => $userId]);
                }
            }
        } catch (Exception $e) {}

        // GAMIFIED CUSTOMER LOYALTY: First Purchase and Big Spender Missions
        if (function_exists('updateUserMissionProgress')) {
            updateUserMissionProgress($pdo, $userId, 'first_purchase', 1, 1, 50);
            updateUserMissionProgress($pdo, $userId, 'big_spender', $total_cost, 5000, 200);
        }

        // 🔗 Affiliate commission tracking 🔗
        $refCode = isset($_COOKIE['fastsite_ref']) ? strtoupper(trim($_COOKIE['fastsite_ref'])) : '';
        if ($refCode) {
            try {
                // First check agents
                $agentStmt = $pdo->prepare("SELECT id, phone FROM agents WHERE ref_code = :c AND status = 'active' LIMIT 1");
                $agentStmt->execute([':c' => $refCode]);
                $agent = $agentStmt->fetch();

                $commission = round($price * 0.20, 2); // 20% commission default
                if ($commission > 0) {
                    if ($agent) {
                        $isSelfRef = preg_replace('/\D/', '', $agent['phone']) === preg_replace('/\D/', '', $recipient_phone ?? '');
                        if (!$isSelfRef) {
                            $pdo->prepare("INSERT INTO `agent_commissions` (`agent_id`,`application_id`,`order_ref`,`service_name`,`order_fee`,`commission_amount`,`status`) VALUES (:aid,:appid,:ref,:svc,:fee,:com,'pending')")
                                ->execute([':aid'=>$agent['id'], ':appid'=>$newId, ':ref'=>'PROD-'.$newId, ':svc'=>'Product Purchase', ':fee'=>$price, ':com'=>$commission]);
                            $pdo->prepare("UPDATE agents SET total_earned = total_earned + :com WHERE id = :id")
                                ->execute([':com' => $commission, ':id' => $agent['id']]);
                        }
                    } else {
                        // Regular user missed commission
                        $userStmt = $pdo->prepare("SELECT id, phone FROM users WHERE ref_code = :c LIMIT 1");
                        $userStmt->execute([':c' => $refCode]);
                        $usr = $userStmt->fetch();
                        if ($usr) {
                            $isSelfRef = preg_replace('/\D/', '', $usr['phone']) === preg_replace('/\D/', '', $recipient_phone ?? '');
                            if (!$isSelfRef) {
                                $pdo->prepare("UPDATE users SET missed_commissions = missed_commissions + :amt WHERE id = :uid")
                                    ->execute([':uid'=>$usr['id'], ':amt'=>$commission]);
                            }
                        }
                    }
                }
            } catch (Exception $e) {}
        }

        header("Location: partner_orders.php?success=order_placed");
        exit;
    } catch (Exception $e) {
        die("Order placement failed: " . $e->getMessage());
    }
} else {
    header('Location: /index.php');
    exit;
}
?>
