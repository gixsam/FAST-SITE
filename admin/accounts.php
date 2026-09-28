<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/../config.php';

$msg = $err = '';

$uploadDir = '../uploads/branding/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

// Handle Gateway Settings Update (Now includes Commission, QR, API, etc.)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_payment_settings'])) {
    // Collect boolean toggles and simple values
    $settings_data = [
        'payment_card_enabled'   => isset($_POST['payment_card_enabled']) ? '1' : '0',
        'payment_bkash_enabled'  => isset($_POST['payment_bkash_enabled']) ? '1' : '0',
        'payment_nagad_enabled'  => isset($_POST['payment_nagad_enabled']) ? '1' : '0',
        'payment_cod_enabled'    => isset($_POST['payment_cod_enabled']) ? '1' : '0',
        'delivery_inside_dhaka'  => floatval($_POST['delivery_inside_dhaka'] ?? 60),
        'delivery_outside_dhaka' => floatval($_POST['delivery_outside_dhaka'] ?? 120),
        'shipping_soft'          => floatval($_POST['shipping_soft'] ?? 0),
        'default_commission_pct' => floatval($_POST['default_commission_pct'] ?? 20),
        'min_payout'             => floatval($_POST['min_payout'] ?? 200),
        'coin_name'              => trim($_POST['coin_name'] ?? 'Fast Coin'),
        'manual_payment_number'  => trim($_POST['manual_payment_number'] ?? ''),
        'bkash_api_mode'         => trim($_POST['bkash_api_mode'] ?? 'live'),
        'bkash_merchant_number'  => trim($_POST['bkash_merchant_number'] ?? ''),
        'bkash_app_key'          => trim($_POST['bkash_app_key'] ?? ''),
        'bkash_app_secret'       => trim($_POST['bkash_app_secret'] ?? ''),
        'bkash_username'         => trim($_POST['bkash_username'] ?? ''),
        'bkash_password'         => trim($_POST['bkash_password'] ?? '')
    ];

    try {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $stmt = $pdo->prepare("INSERT INTO homepage_settings (setting_key, setting_value) 
                VALUES (:key, :val) 
                ON CONFLICT(setting_key) DO UPDATE SET setting_value = :val");
        } else {
            $stmt = $pdo->prepare("INSERT INTO homepage_settings (setting_key, setting_value) 
                VALUES (:key, :val) 
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        }

        foreach ($settings_data as $k => $v) {
            $stmt->execute([':key' => $k, ':val' => strval($v)]);
        }
        
        // Handle QR Code Uploads
        $qrUploads = ['bkash_qr_file' => 'bkash_qr_url', 'nagad_qr_file' => 'nagad_qr_url'];
        foreach ($qrUploads as $fileKey => $dbKey) {
            if (!empty($_FILES[$fileKey]['name'])) {
                $qrName = handleSecureUpload($_FILES[$fileKey], $uploadDir, ['jpg','jpeg','png','webp','gif','svg'], $dbKey);
                if ($qrName) {
                    $qrPath = 'uploads/branding/' . $qrName;
                    if ($driver === 'sqlite') {
                        $pdo->prepare("INSERT INTO homepage_settings (setting_key, setting_value) VALUES (?, ?) ON CONFLICT(setting_key) DO UPDATE SET setting_value = ?")->execute([$dbKey, $qrPath, $qrPath]);
                    } else {
                        $pdo->prepare("INSERT INTO homepage_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?")->execute([$dbKey, $qrPath, $qrPath]);
                    }
                }
            }
        }
        
        $msg = 'Money, Payment Gateway and System configuration saved successfully!';
    } catch (Exception $e) {
        $err = 'Error saving settings: ' . $e->getMessage();
    }
}

// Handle Accept / Reject Unified Withdrawals
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['withdrawal_id'])) {
    $id = intval($_POST['withdrawal_id']);
    $action = $_POST['action'] ?? '';
    
    if ($id > 0 && in_array($action, ['paid', 'rejected'])) {
        if ($action === 'rejected') {
            // Refund the coins
            try {
                $pdo->beginTransaction();
                $wStmt = $pdo->prepare("SELECT user_id, amount_coins, status FROM user_withdrawals WHERE id = ? FOR UPDATE");
                $wStmt->execute([$id]);
                $w = $wStmt->fetch();
                if ($w && $w['status'] === 'pending') {
                    $pdo->prepare("UPDATE users SET coins_balance = coins_balance + ? WHERE id = ?")->execute([$w['amount_coins'], $w['user_id']]);
                    $pdo->prepare("UPDATE user_withdrawals SET status = 'rejected', processed_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$id]);
                }
                $pdo->commit();
                $msg = 'Withdrawal Rejected and funds refunded to user wallet.';
            } catch(Exception $e) { if($pdo->inTransaction()) $pdo->rollBack(); $err = 'Error processing rejection.'; }
        } else {
            // Mark as Paid
            $pdo->prepare("UPDATE user_withdrawals SET status = 'paid', processed_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$id]);
            $msg = 'Withdrawal marked as Paid successfully.';
        }
    }
}

// Handle Accept / Reject Deposit Requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deposit_action'])) {
    $req_id = intval($_POST['deposit_id'] ?? 0);
    $action = $_POST['deposit_action'];
    $notes = trim($_POST['admin_notes'] ?? '');
    
    if ($req_id > 0) {
        $stmt = $pdo->prepare("SELECT * FROM deposit_requests WHERE id = ? AND status = 'pending'");
        $stmt->execute([$req_id]);
        $req = $stmt->fetch();
        
        if ($req) {
            if ($action === 'approve') {
                $pdo->beginTransaction();
                try {
                    $pdo->prepare("UPDATE deposit_requests SET status = 'approved', admin_notes = ? WHERE id = ?")->execute([$notes, $req_id]);
                    $pdo->prepare("UPDATE users SET coins_balance = coins_balance + ? WHERE id = ?")->execute([$req['amount'], $req['user_id']]);
                    $pdo->commit();
                    $msg = "Deposit request approved. Coins added to user.";
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $err = "Error approving request.";
                }
            } elseif ($action === 'reject') {
                $pdo->prepare("UPDATE deposit_requests SET status = 'rejected', admin_notes = ? WHERE id = ?")->execute([$notes, $req_id]);
                $msg = "Deposit request rejected.";
            }
        }
    }
}

// Handle Admin Direct Coin Grant
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['grant_coins'])) {
    $u = trim($_POST['grant_user']);
    $amt = floatval($_POST['grant_amount']);
    if ($amt > 0) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ? OR email = ? LIMIT 1");
        $stmt->execute([$u, $u]);
        $u_id = $stmt->fetchColumn();
        if ($u_id) {
            try {
                $pdo->beginTransaction();
                $pdo->prepare("UPDATE users SET coins_balance = coins_balance + ? WHERE id = ?")->execute([$amt, $u_id]);
                $trx = 'GRANT-' . strtoupper(substr(uniqid(), -6));
                $pdo->prepare("INSERT INTO deposit_requests (user_id, amount, sender_number, transaction_id, status, admin_notes) VALUES (?, ?, 'ADMIN', ?, 'approved', 'Manual Admin Grant')")->execute([$u_id, $amt, $trx]);
                $pdo->commit();
                $msg = "Successfully granted $amt Coins to user!";
            } catch (Exception $e) {
                $pdo->rollBack();
                $err = "Error granting coins.";
            }
        } else {
            $err = "User not found with that Phone/Email.";
        }
    } else {
        $err = "Invalid amount.";
    }
}

// Fetch unified withdrawals
$withdrawals = $pdo->query("
    SELECT w.*, u.name as user_name, u.phone as user_phone 
    FROM user_withdrawals w 
    JOIN users u ON w.user_id = u.id 
    ORDER BY w.status = 'pending' DESC, w.created_at DESC
")->fetchAll();

// Fetch deposit requests
$deposits = $pdo->query("
    SELECT d.*, u.name as user_name, u.phone as user_phone 
    FROM deposit_requests d 
    JOIN users u ON d.user_id = u.id 
    ORDER BY d.status = 'pending' DESC, d.created_at DESC
")->fetchAll();

// Fetch settings for pre-filling form
$settings = $pdo->query("SELECT setting_key, setting_value FROM homepage_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Accounts & Payments - Admin</title>
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>
    <?php include 'nav.php'; ?>
    <div class="main-content">
        <div class="header">
            <h1>💳 Accounts & Payments Section</h1>
        </div>
        
        <?php if($msg): ?><div class="msg-box"><?= $msg ?></div><?php endif; ?>
        <?php if($err): ?><div class="msg-box err-box"><?= $err ?></div><?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="card">
                <div class="card-header">⚙️ï¸ System Money & Payment Settings</div>
                
                <!-- ROW 1: Currency & Commission -->
                <div class="grid-3" style="margin-bottom: 1.5rem;">
                    <div class="form-group">
                        <label>Platform Coin Name</label>
                        <input type="text" name="coin_name" class="form-control" value="<?= htmlspecialchars($settings['coin_name'] ?? 'Fast Coin') ?>">
                    </div>
                    <div class="form-group">
                        <label>Global Default Commission (%)</label>
                        <input type="number" step="0.1" name="default_commission_pct" class="form-control" value="<?= htmlspecialchars($settings['default_commission_pct'] ?? '20') ?>">
                    </div>
                    <div class="form-group">
                        <label>Min Payout Limit</label>
                        <input type="number" step="1" name="min_payout" class="form-control" value="<?= htmlspecialchars($settings['min_payout'] ?? '200') ?>">
                    </div>
                </div>

                <div class="section-divider"></div>

                <!-- ROW 2: Manual Payment & Shipping -->
                <div class="grid-2">
                    <div class="form-group">
                        <label>Manual Deposit Number (bKash/Nagad)</label>
                        <input type="text" name="manual_payment_number" class="form-control" value="<?= htmlspecialchars($settings['manual_payment_number'] ?? '01963601472') ?>">
                        <div style="font-size:0.75rem; color:var(--muted); margin-top:0.4rem;">Target number for manual checkout transfers.</div>
                    </div>
                    <div>
                        <div class="form-group">
                            <label>Shipping / Delivery Charges (BDT)</label>
                            <div style="display:flex; gap:0.5rem; margin-bottom: 0.5rem;">
                                <input type="number" step="0.1" name="delivery_inside_dhaka" class="form-control" placeholder="Inside Dhaka" value="<?= htmlspecialchars($settings['delivery_inside_dhaka'] ?? '60') ?>">
                                <input type="number" step="0.1" name="delivery_outside_dhaka" class="form-control" placeholder="Outside Dhaka" value="<?= htmlspecialchars($settings['delivery_outside_dhaka'] ?? '120') ?>">
                                <input type="number" step="0.1" name="shipping_soft" class="form-control" placeholder="Soft File" value="<?= htmlspecialchars($settings['shipping_soft'] ?? '0') ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="section-divider"></div>

                <!-- ROW 3: QR Codes -->
                <div class="grid-2">
                    <div class="form-group">
                        <label>bKash QR Code Image</label>
                        <?php if(!empty($settings['bkash_qr_url'])): ?>
                            <div style="margin-bottom:0.5rem;"><img src="/<?= htmlspecialchars(ltrim($settings['bkash_qr_url'], '/')) ?>" style="height:80px; border-radius:8px; border:1px solid rgba(255,255,255,0.1); padding:4px;" alt="bKash QR"/></div>
                        <?php endif; ?>
                        <input type="file" name="bkash_qr_file" accept=".jpg,.png,.webp" class="form-control" style="padding: 0.5rem;">
                    </div>
                    <div class="form-group">
                        <label>Nagad QR Code Image</label>
                        <?php if(!empty($settings['nagad_qr_url'])): ?>
                            <div style="margin-bottom:0.5rem;"><img src="/<?= htmlspecialchars(ltrim($settings['nagad_qr_url'], '/')) ?>" style="height:80px; border-radius:8px; border:1px solid rgba(255,255,255,0.1); padding:4px;" alt="Nagad QR"/></div>
                        <?php endif; ?>
                        <input type="file" name="nagad_qr_file" accept=".jpg,.png,.webp" class="form-control" style="padding: 0.5rem;">
                    </div>
                </div>

                <div class="section-divider"></div>

                <!-- ROW 4: bKash Merchant API -->
                <div class="form-group">
                    <label style="color:var(--brand);">š€ bKash Merchant API Configuration (Automated Checkouts)</label>
                </div>
                <div class="grid-3" style="margin-bottom:1rem;">
                    <div class="form-group">
                        <label>API Mode</label>
                        <select name="bkash_api_mode" class="form-control">
                            <option value="sandbox" <?= ($settings['bkash_api_mode'] ?? '') === 'sandbox' ? 'selected' : '' ?>>§ª Sandbox (Testing)</option>
                            <option value="live" <?= ($settings['bkash_api_mode'] ?? '') === 'live' ? 'selected' : '' ?>>š€ Live (Production)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Merchant Account Number</label>
                        <input type="text" name="bkash_merchant_number" class="form-control" value="<?= htmlspecialchars($settings['bkash_merchant_number'] ?? '+8801963601472') ?>">
                    </div>
                    <div class="form-group">
                        <label>API Username</label>
                        <input type="text" name="bkash_username" class="form-control" value="<?= htmlspecialchars($settings['bkash_username'] ?? '') ?>">
                    </div>
                </div>
                <div class="grid-3">
                    <div class="form-group">
                        <label>App Key</label>
                        <input type="password" name="bkash_app_key" class="form-control" value="<?= htmlspecialchars($settings['bkash_app_key'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>App Secret</label>
                        <input type="password" name="bkash_app_secret" class="form-control" value="<?= htmlspecialchars($settings['bkash_app_secret'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>API Password</label>
                        <input type="password" name="bkash_password" class="form-control" value="<?= htmlspecialchars($settings['bkash_password'] ?? '') ?>">
                    </div>
                </div>

                <div class="section-divider"></div>
                
                <!-- ROW 5: Enable/Disable Gateways -->
                <div class="form-group">
                    <label>Enabled Payment Methods (Checkout)</label>
                    <div class="grid-2">
                        <div>
                            <label class="switch-label">
                                <span>Credit / Debit Cards</span>
                                <input type="checkbox" name="payment_card_enabled" <?= !empty($settings['payment_card_enabled']) ? 'checked' : '' ?>>
                            </label>
                            <label class="switch-label">
                                <span>Cash on Delivery (COD)</span>
                                <input type="checkbox" name="payment_cod_enabled" <?= !empty($settings['payment_cod_enabled']) ? 'checked' : '' ?>>
                            </label>
                        </div>
                        <div>
                            <label class="switch-label">
                                <span>bKash Payment</span>
                                <input type="checkbox" name="payment_bkash_enabled" <?= !empty($settings['payment_bkash_enabled']) ? 'checked' : '' ?>>
                            </label>
                            <label class="switch-label">
                                <span>Nagad Payment</span>
                                <input type="checkbox" name="payment_nagad_enabled" <?= !empty($settings['payment_nagad_enabled']) ? 'checked' : '' ?>>
                            </label>
                        </div>
                    </div>
                </div>

                <div style="margin-top: 2rem;">
                    <button type="submit" name="save_payment_settings" class="btn btn-primary" style="width:100%; font-size: 1.1rem; padding: 1rem;">’¾ Save All Account & Money Settings</button>
                </div>
            </div>
        </form>

        <div class="card" style="border: 1px solid var(--brand);">
            <div class="card-header">🎁 Grant Coins to User (Admin Transfer)</div>
            <form method="POST" style="padding: 1rem;">
                <div class="grid-3">
                    <div class="form-group">
                        <label>User Phone or Email</label>
                        <input type="text" name="grant_user" class="form-control" required placeholder="e.g. 017XXXXXX or email">
                    </div>
                    <div class="form-group">
                        <label>Amount (Coins)</label>
                        <input type="number" step="0.1" name="grant_amount" class="form-control" required placeholder="e.g. 100">
                    </div>
                    <div class="form-group" style="display:flex; align-items:flex-end;">
                        <button type="submit" name="grant_coins" class="btn btn-primary" style="width:100%; height:45px; font-size:1rem; display:flex; justify-content:center; align-items:center;" onclick="return confirm('Confirm granting these coins?');">🚀 Grant Coins</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="card">
            <div class="card-header">📥 Manual Deposit Requests (Add Coins)</div>
            
            <!-- Desktop Table -->
            <div class="desktop-table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>User</th>
                            <th>Sender & TRXID</th>
                            <th>Amount (Coins)</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($deposits as $r): ?>
                        <tr>
                            <td style="font-size: 0.85rem; color: rgba(255,255,255,0.6);"><?= date('d M, Y h:i A', strtotime($r['created_at'])) ?></td>
                            <td>
                                <div style="font-weight: 600;"><?= htmlspecialchars($r['user_name']) ?></div>
                                <div style="font-size: 0.8rem; color: rgba(255,255,255,0.5);"><?= htmlspecialchars($r['user_phone']) ?></div>
                            </td>
                            <td>
                                <div style="color: var(--gold);">TRX: <?= htmlspecialchars($r['transaction_id']) ?></div>
                                <div style="font-size: 0.8rem; color: rgba(255,255,255,0.5);">Num: <?= htmlspecialchars($r['sender_number']) ?></div>
                            </td>
                            <td style="color: var(--gold); font-weight: bold; font-size: 1.1rem;"><?= number_format($r['amount'], 2) ?></td>
                            <td><span class="badge <?= $r['status'] ?>"><?= $r['status'] ?></span></td>
                            <td>
                                <?php if($r['status'] === 'pending'): ?>
                                <form method="POST" style="display:flex; gap:5px; flex-direction:column;">
                                    <input type="hidden" name="deposit_id" value="<?= $r['id'] ?>">
                                    <input type="text" name="admin_notes" placeholder="Notes (Optional)" style="background:rgba(255,255,255,0.1); border:none; color:#fff; padding:6px; border-radius:4px; font-size:0.75rem; width:100%;">
                                    <div style="display:flex; gap:5px;">
                                        <button type="submit" name="deposit_action" value="approve" class="btn btn-pay" onclick="return confirm('Approve deposit and credit coins?');">Approve</button>
                                        <button type="submit" name="deposit_action" value="reject" class="btn btn-reject" onclick="return confirm('Reject this deposit?');">Reject</button>
                                    </div>
                                </form>
                                <?php else: ?>
                                    <span style="font-size: 0.8rem; color: rgba(255,255,255,0.4);"><?= htmlspecialchars($r['admin_notes']) ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($deposits)): ?>
                        <tr><td colspan="6" style="text-align:center; color:rgba(255,255,255,0.5); padding:2rem;">No deposit requests found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Stacked Cards -->
            <div class="mobile-cards-wrap">
                <?php foreach($deposits as $r): ?>
                <div class="box" style="margin-bottom:1rem; background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.05); padding:1rem; border-radius:12px;">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px;">
                        <div>
                            <div style="font-weight:700; color:var(--gold); font-size:1.1rem;"><?= number_format($r['amount'], 2) ?> Coins</div>
                            <div style="font-size:0.75rem; color:var(--muted);"><?= date('d M Y, h:i A', strtotime($r['created_at'])) ?></div>
                        </div>
                        <span class="badge <?= $r['status'] ?>"><?= $r['status'] ?></span>
                    </div>
                    <div style="margin-bottom:10px; font-size:0.9rem;">
                        <div><strong style="color:var(--muted);">User:</strong> <?= htmlspecialchars($r['user_name']) ?> (<?= htmlspecialchars($r['user_phone']) ?>)</div>
                        <div><strong style="color:var(--muted);">TRXID:</strong> <?= htmlspecialchars($r['transaction_id']) ?></div>
                        <div><strong style="color:var(--muted);">Sender:</strong> <?= htmlspecialchars($r['sender_number']) ?></div>
                    </div>
                    <?php if($r['status'] === 'pending'): ?>
                        <form method="POST" style="display:flex; flex-direction:column; gap:8px;">
                            <input type="hidden" name="deposit_id" value="<?= $r['id'] ?>">
                            <input type="text" name="admin_notes" placeholder="Notes (Optional)" style="background:rgba(0,0,0,0.3); border:1px solid var(--border); color:#fff; padding:10px; border-radius:8px; font-size:0.9rem;">
                            <div style="display:flex; gap:10px;">
                                <button type="submit" name="deposit_action" value="approve" class="btn btn-pay" style="flex:1;" onclick="return confirm('Approve deposit?');">Approve</button>
                                <button type="submit" name="deposit_action" value="reject" class="btn btn-reject" style="flex:1;" onclick="return confirm('Reject deposit?');">Reject</button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div style="font-size:0.85rem; color:var(--muted);">Notes: <?= htmlspecialchars($r['admin_notes']) ?></div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                <?php if(empty($deposits)): ?>
                    <div style="text-align:center; padding:2rem; color:rgba(255,255,255,0.5);">No deposit requests found.</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header">💸 Unified Withdrawal Requests (Users & Partners)</div>
            
            <!-- Desktop Table -->
            <div class="desktop-table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>User Name</th>
                            <th>Amount (Coins)</th>
                            <th>Payout Method</th>
                            <th>Account No</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($withdrawals as $w): ?>
                            <tr>
                                <td style="font-size: 0.85rem; color: rgba(255,255,255,0.6);"><?= date('d M Y, h:i A', strtotime($w['created_at'])) ?></td>
                                <td>
                                    <div style="font-weight: 600;"><?= htmlspecialchars($w['user_name']) ?></div>
                                    <div style="font-size: 0.8rem; color: rgba(255,255,255,0.5);"><?= htmlspecialchars($w['user_phone']) ?></div>
                                </td>
                                <td style="color: var(--gold); font-weight: bold; font-size: 1.1rem;"><?= number_format($w['amount_coins'], 2) ?></td>
                                <td style="text-transform: uppercase;"><?= htmlspecialchars($w['payout_method']) ?></td>
                                <td style="font-family: monospace; font-size: 1rem;"><?= htmlspecialchars($w['payout_account']) ?></td>
                                <td><span class="badge <?= $w['status'] ?>"><?= $w['status'] ?></span></td>
                                <td>
                                    <?php if($w['status'] === 'pending'): ?>
                                        <form method="POST" style="display: flex; gap: 5px;">
                                            <input type="hidden" name="withdrawal_id" value="<?= $w['id'] ?>">
                                            <button type="submit" name="action" value="paid" class="btn btn-pay" onclick="return confirm('Confirm you sent the cash to their account?');">Mark Paid</button>
                                            <button type="submit" name="action" value="rejected" class="btn btn-reject" onclick="return confirm('Reject and refund coins to user?');">Reject</button>
                                        </form>
                                    <?php else: ?>
                                        <span style="font-size: 0.8rem; color: rgba(255,255,255,0.4);"><?= date('d M Y', strtotime($w['processed_at'])) ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if(empty($withdrawals)): ?>
                            <tr><td colspan="7" style="text-align: center; padding: 2rem; color: rgba(255,255,255,0.5);">No withdrawal requests found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Stacked Cards -->
            <div class="mobile-cards-wrap">
                <?php foreach($withdrawals as $w): ?>
                <div class="box" style="margin-bottom:1rem; background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.05); padding:1rem; border-radius:12px;">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px;">
                        <div>
                            <div style="font-weight:700; color:var(--gold); font-size:1.1rem;"><?= number_format($w['amount_coins'], 2) ?> Coins</div>
                            <div style="font-size:0.75rem; color:var(--muted);"><?= date('d M Y, h:i A', strtotime($w['created_at'])) ?></div>
                        </div>
                        <span class="badge <?= $w['status'] ?>"><?= $w['status'] ?></span>
                    </div>
                    <div style="margin-bottom:10px; font-size:0.9rem;">
                        <div><strong style="color:var(--muted);">User:</strong> <?= htmlspecialchars($w['user_name']) ?> (<?= htmlspecialchars($w['user_phone']) ?>)</div>
                        <div><strong style="color:var(--muted);">Method:</strong> <span style="text-transform:uppercase;"><?= htmlspecialchars($w['payout_method']) ?></span></div>
                        <div><strong style="color:var(--muted);">Account No:</strong> <span style="font-family:monospace;"><?= htmlspecialchars($w['payout_account']) ?></span></div>
                    </div>
                    <?php if($w['status'] === 'pending'): ?>
                        <form method="POST" style="display:flex; gap:10px;">
                            <input type="hidden" name="withdrawal_id" value="<?= $w['id'] ?>">
                            <button type="submit" name="action" value="paid" class="btn btn-pay" style="flex:1;" onclick="return confirm('Confirm Paid?');">Mark Paid</button>
                            <button type="submit" name="action" value="rejected" class="btn btn-reject" style="flex:1;" onclick="return confirm('Reject & Refund?');">Reject</button>
                        </form>
                    <?php else: ?>
                        <div style="font-size:0.85rem; color:var(--muted);">Processed: <?= date('d M Y', strtotime($w['processed_at'])) ?></div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                <?php if(empty($withdrawals)): ?>
                    <div style="text-align:center; padding:2rem; color:rgba(255,255,255,0.5);">No withdrawal requests found.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php include __DIR__ . '/../includes/cropper_modal.php'; ?>
</body>
</html>