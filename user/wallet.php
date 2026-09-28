<?php
// =========================================================================
// user/wallet.php – Unified Fast Wallet, Instant Deposit & Withdraw Hub
// =========================================================================
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /user/login.php');
    exit;
}
require_once __DIR__ . '/../config.php';

$user_id = (int)$_SESSION['user_id'];

// Get user profile details
$stmt_user = $pdo->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
$stmt_user->execute([':id' => $user_id]);
$user = $stmt_user->fetch();

if (!$user) {
    unset($_SESSION['user_id']);
    header('Location: /user/login.php');
    exit;
}

// Get coin name and exchange rate
$coin_name = getPartnerSetting('coin_name', 'Fast Points');
$exchange_rate = floatval(getPartnerSetting('exchange_rate', '1'));

// Unified Wallet Balance
$wallet_balance = floatval($user['coins_balance'] ?? 0.0);
$wallet_balance_bdt = $wallet_balance * $exchange_rate;

$err = $msg = '';

// ── Handle Inline Deposit Submission ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_deposit') {
    $sender_number  = trim($_POST['sender_number'] ?? '');
    $transaction_id = trim($_POST['transaction_id'] ?? '');
    $amount         = floatval($_POST['amount'] ?? 0);

    if (!$sender_number || !$transaction_id || $amount <= 0) {
        $err = 'Please enter a valid sender phone number, transaction ID, and amount.';
    } else {
        $screenshot_file = null;
        if (isset($_FILES['screenshot']) && $_FILES['screenshot']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/deposits/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $screenshot_file = handleSecureUpload($_FILES['screenshot'], $upload_dir, ['jpg','jpeg','png','webp','pdf'], 'dep_' . $user_id);
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO deposit_requests 
                (user_id, sender_number, transaction_id, amount, screenshot_url, status) 
                VALUES (:user_id, :sender_number, :transaction_id, :amount, :screenshot_url, 'pending')");
            $stmt->execute([
                ':user_id'        => $user_id,
                ':sender_number'  => $sender_number,
                ':transaction_id' => $transaction_id,
                ':amount'         => $amount,
                ':screenshot_url' => $screenshot_file
            ]);

            $msg = 'Deposit request submitted successfully! Your Fast Points will be credited once verified.';
        } catch (PDOException $e) {
            $err = 'Database Error: ' . $e->getMessage();
        }
    }
}

// ── Handle Inline Withdrawal Submission ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_withdraw') {
    $amount_coins   = floatval($_POST['amount_coins'] ?? 0);
    $payout_method  = trim($_POST['payout_method'] ?? 'bKash');
    $payout_account = trim($_POST['payout_account'] ?? '');

    $min_withdraw = 100;

    if ($amount_coins < $min_withdraw) {
        $err = "Minimum withdrawal is {$min_withdraw} Coins.";
    } elseif ($amount_coins > $wallet_balance) {
        $err = "Insufficient balance! Your current balance is " . number_format($wallet_balance, 2) . " {$coin_name}.";
    } elseif (!$payout_account) {
        $err = "Please enter your {$payout_method} account number.";
    } else {
        try {
            $pdo->beginTransaction();

            // Deduct from users table
            $pdo->prepare("UPDATE users SET coins_balance = coins_balance - :amt WHERE id = :uid AND coins_balance >= :amt")
                ->execute([':amt' => $amount_coins, ':uid' => $user_id]);

            // Insert into user_withdrawals
            $pdo->prepare("INSERT INTO user_withdrawals (user_id, amount_coins, payout_method, payout_account, status) VALUES (:uid, :amt, :method, :acc, 'pending')")
                ->execute([
                    ':uid'    => $user_id,
                    ':amt'    => $amount_coins,
                    ':method' => $payout_method,
                    ':acc'    => $payout_account
                ]);

            // Record transaction ledger entry safely with reference and description
            $pdo->prepare("INSERT INTO coin_transactions (user_id, type, amount, reference, description, status) VALUES (?, 'withdrawal', ?, ?, ?, 'pending')")
                ->execute([
                    $user_id,
                    $amount_coins,
                    "Withdrawal Request via {$payout_method}",
                    "Account: {$payout_account}"
                ]);

            $pdo->commit();
            $msg = "Withdrawal request for " . number_format($amount_coins, 2) . " {$coin_name} submitted successfully! Payout will be sent to your {$payout_method} account.";
            
            // Refresh user balance
            $stmt_user->execute([':id' => $user_id]);
            $user = $stmt_user->fetch();
            $wallet_balance = floatval($user['coins_balance'] ?? 0.0);
            $wallet_balance_bdt = $wallet_balance * $exchange_rate;
        } catch (Exception $e) {
            $pdo->rollBack();
            $err = "Error processing withdrawal: " . $e->getMessage();
        }
    }
}

// ── Handle Order Release Payment Action ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'release_order') {
    $order_id = intval($_POST['order_id'] ?? 0);
    if ($order_id > 0) {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("SELECT * FROM partner_orders WHERE id = :id AND customer_id = :user_id LIMIT 1");
            $stmt->execute([':id' => $order_id, ':user_id' => $user_id]);
            $order = $stmt->fetch();

            if ($order && in_array($order['status'], ['accepted', 'in_progress', 'waiting_confirmation'])) {
                $pay_status_val = ($order['payment_method'] === 'cod') ? 'completed' : $order['payment_status'];

                $pdo->prepare("UPDATE partner_orders 
                    SET status = 'completed', payment_status = :pay_status, customer_confirmed_at = :now 
                    WHERE id = :id")
                    ->execute([
                        ':pay_status' => $pay_status_val,
                        ':now'        => date('Y-m-d H:i:s'),
                        ':id'         => $order_id
                    ]);

                // Credit Partner Balance
                $partner_stmt = $pdo->prepare("SELECT * FROM partners WHERE id = :id LIMIT 1");
                $partner_stmt->execute([':id' => $order['partner_id']]);
                $partner = $partner_stmt->fetch();

                if ($partner) {
                    $earned_amount = floatval($order['total_amount']);
                    $pdo->prepare("UPDATE partners 
                        SET total_earned = total_earned + :amount, total_orders = total_orders + 1 
                        WHERE id = :id")
                        ->execute([':amount' => $earned_amount, ':id' => $partner['id']]);
                }

                $pdo->commit();
                $msg = "Order #{$order_id} marked as completed and funds released to seller!";
            } else {
                $pdo->rollBack();
                $err = "Cannot complete this order.";
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $err = "Error updating order: " . $e->getMessage();
        }
    }
}

// Fetch transaction history
$stmt_tx = $pdo->prepare("SELECT * FROM coin_transactions WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 50");
$stmt_tx->execute([':user_id' => $user_id]);
$transactions = $stmt_tx->fetchAll();

// Fetch deposit requests history
$stmt_dep = $pdo->prepare("SELECT * FROM deposit_requests WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 20");
$stmt_dep->execute([':user_id' => $user_id]);
$deposit_requests = $stmt_dep->fetchAll();

// Fetch user withdrawals history
$stmt_wth = $pdo->prepare("SELECT * FROM user_withdrawals WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 20");
$stmt_wth->execute([':user_id' => $user_id]);
$withdrawals = $stmt_wth->fetchAll();

// Fetch user shop orders
$stmt_orders = $pdo->prepare("
    SELECT o.*, p.title as product_title, p.category, pt.business_name, pt.phone as partner_phone
    FROM partner_orders o
    LEFT JOIN partner_products p ON o.product_id = p.id
    LEFT JOIN partners pt ON o.partner_id = pt.id
    WHERE o.customer_id = :user_id
    ORDER BY o.created_at DESC
");
$stmt_orders->execute([':user_id' => $user_id]);
$shop_orders = $stmt_orders->fetchAll();

$settings = $pdo->query("SELECT setting_key, setting_value FROM homepage_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
$whatsapp_num = $settings['whatsapp_number'] ?? '01963601472';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=0"/>
  <title>My Wallet — Fast Site</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Oswald:wght@600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="/assets/css/user.css?v=<?= time() ?>">
  <style>
    .wallet-container {
      max-width: 960px;
      margin: 0 auto;
      padding-top: 85px;
      padding-bottom: 5rem;
    }
    .premium-wallet-card {
      background: linear-gradient(135deg, rgba(33, 150, 243, 0.12) 0%, rgba(20, 20, 31, 0.95) 100%);
      border: 1px solid rgba(252, 185, 0, 0.35);
      border-radius: 24px;
      padding: 2.2rem 2rem;
      position: relative;
      overflow: hidden;
      box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5), inset 0 1px 0 rgba(255, 255, 255, 0.1);
      margin-bottom: 2rem;
    }
    .pw-badge {
      font-size: 0.85rem;
      font-weight: 800;
      color: var(--gold);
      text-transform: uppercase;
      letter-spacing: 1.5px;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      margin-bottom: 0.5rem;
    }
    .pw-balance {
      font-family: 'Oswald', sans-serif;
      font-size: 3.5rem;
      font-weight: 700;
      line-height: 1.1;
      color: #fff;
      margin-bottom: 0.4rem;
      text-shadow: 0 0 25px rgba(252, 185, 0, 0.3);
    }
    .pw-sub {
      font-size: 1rem;
      color: var(--muted);
      font-weight: 500;
    }
    .pw-actions {
      display: flex;
      gap: 1rem;
      margin-top: 1.8rem;
      flex-wrap: wrap;
    }
    .btn-deposit-toggle {
      flex: 1;
      min-width: 180px;
      padding: 1rem 1.5rem;
      border-radius: 12px;
      font-weight: 800;
      font-size: 1rem;
      background: linear-gradient(135deg, var(--gold) 0%, #ff9100 100%);
      color: #000;
      border: none;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      box-shadow: 0 10px 25px rgba(252, 185, 0, 0.35);
      transition: all 0.2s;
    }
    .btn-deposit-toggle:hover {
      transform: translateY(-2px);
      box-shadow: 0 15px 30px rgba(252, 185, 0, 0.45);
    }
    .btn-withdraw-toggle {
      flex: 1;
      min-width: 180px;
      padding: 1rem 1.5rem;
      border-radius: 12px;
      font-weight: 800;
      font-size: 1rem;
      background: rgba(255, 255, 255, 0.05);
      color: #fff;
      border: 1px solid rgba(255, 255, 255, 0.15);
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      transition: all 0.2s;
    }
    .btn-withdraw-toggle:hover {
      background: rgba(255, 255, 255, 0.1);
      border-color: rgba(255, 255, 255, 0.3);
      transform: translateY(-2px);
    }

    /* ── Dropdown Forms ── */
    .wallet-dropdown {
      display: none;
      background: #0d0d15;
      border-radius: 20px;
      padding: 2rem;
      margin-top: 1.5rem;
      animation: slideDown 0.35s ease-out forwards;
      box-shadow: 0 15px 40px rgba(0, 0, 0, 0.6);
    }
    .wallet-dropdown.active {
      display: block;
    }
    #depositDropdown {
      border: 1px solid rgba(0, 230, 118, 0.3);
    }
    #withdrawDropdown {
      border: 1px solid rgba(252, 185, 0, 0.35);
    }

    @keyframes slideDown {
      from { opacity: 0; transform: translateY(-15px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .quick-chip {
      background: rgba(255, 255, 255, 0.06);
      border: 1px solid rgba(255, 255, 255, 0.12);
      color: #fff;
      padding: 0.4rem 0.9rem;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.2s;
    }
    .quick-chip:hover {
      background: rgba(0, 230, 118, 0.15);
      border-color: #00e676;
      color: #00e676;
    }

    /* Tab Switcher */
    .wallet-tabs {
      display: flex;
      gap: 0.8rem;
      margin-bottom: 1.5rem;
      border-bottom: 1px solid rgba(255, 255, 255, 0.08);
      padding-bottom: 0.8rem;
      overflow-x: auto;
    }
    .w-tab-btn {
      background: transparent;
      border: 1px solid rgba(255, 255, 255, 0.1);
      color: var(--muted);
      padding: 0.65rem 1.4rem;
      border-radius: 30px;
      font-weight: 700;
      font-size: 0.85rem;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 8px;
      white-space: nowrap;
      transition: all 0.2s;
    }
    .w-tab-btn.active, .w-tab-btn:hover {
      background: rgba(33, 150, 243, 0.15);
      border-color: var(--brand);
      color: #fff;
    }

    .custom-input {
      width: 100%;
      background: #08080e !important;
      border: 1px solid rgba(255, 255, 255, 0.15) !important;
      color: #fff !important;
      padding: 0.75rem 1rem !important;
      border-radius: 10px !important;
      font-size: 0.9rem !important;
      outline: none !important;
    }
    .custom-input:focus {
      border-color: var(--brand) !important;
      box-shadow: 0 0 10px rgba(33, 150, 243, 0.3) !important;
    }

    .order-card {
      background: rgba(20, 20, 31, 0.85);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 16px;
      padding: 1.5rem;
      margin-bottom: 1.2rem;
      box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3);
    }

    .dropdown-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1.2rem;
      margin-bottom: 1.2rem;
    }

    /* ── Mobile APK & Smartphone Responsive Styles ── */
    @media (max-width: 768px) {
      .wallet-container {
        padding: 70px 0.8rem 4rem !important;
        max-width: 100vw !important;
        overflow-x: hidden !important;
        box-sizing: border-box !important;
      }
      .premium-wallet-card {
        padding: 1.2rem 1rem !important;
        border-radius: 18px !important;
        width: 100% !important;
        max-width: 100% !important;
        box-sizing: border-box !important;
      }
      .pw-balance {
        font-size: 2.2rem !important;
        word-break: break-all !important;
      }
      .pw-actions {
        flex-direction: column !important;
        gap: 0.6rem !important;
        width: 100% !important;
      }
      .btn-deposit-toggle, .btn-withdraw-toggle {
        width: 100% !important;
        min-width: 0 !important;
        padding: 0.85rem 1rem !important;
        box-sizing: border-box !important;
        justify-content: center !important;
      }
      .wallet-dropdown {
        padding: 1.2rem 0.9rem !important;
        border-radius: 14px !important;
        box-sizing: border-box !important;
      }
      .dropdown-grid {
        grid-template-columns: 1fr !important;
        gap: 0.8rem !important;
      }
      .wallet-tabs {
        width: 100% !important;
        max-width: 100% !important;
        overflow-x: auto !important;
        -webkit-overflow-scrolling: touch !important;
        flex-wrap: nowrap !important;
        gap: 0.4rem !important;
        padding-bottom: 0.4rem !important;
        margin-bottom: 1.2rem !important;
        scrollbar-width: none;
      }
      .wallet-tabs::-webkit-scrollbar {
        display: none;
      }
      .w-tab-btn {
        padding: 0.55rem 0.9rem !important;
        font-size: 0.78rem !important;
        flex-shrink: 0 !important;
      }
      .order-card {
        padding: 1rem 0.85rem !important;
        border-radius: 14px !important;
        box-sizing: border-box !important;
        width: 100% !important;
        max-width: 100% !important;
      }
    }
    @media (max-width: 420px) {
      .pw-balance {
        font-size: 1.8rem !important;
      }
    }
  </style>
</head>
<body class="dashboard-mode">

<?php include __DIR__ . '/../includes/user_sidebar.php'; ?>

<div class="wallet-container">
  
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 1.5rem;">
    <a href="dashboard.php" class="btn" style="background:rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.15); color:#fff; width:auto; padding:0.5rem 1.2rem; font-size:0.85rem; text-decoration:none;">
      ← Back to Dashboard
    </a>
  </div>

  <?php if($msg): ?>
    <div style="background:rgba(0, 230, 118, 0.12); color:#00e676; padding:1rem 1.5rem; border-radius:12px; margin-bottom:1.5rem; border:1px solid rgba(0, 230, 118, 0.3); font-weight:700; display:flex; align-items:center; gap:8px;">
      ✅ <?= htmlspecialchars($msg) ?>
    </div>
  <?php endif; ?>

  <?php if($err): ?>
    <div style="background:rgba(255, 82, 82, 0.12); color:#ff5252; padding:1rem 1.5rem; border-radius:12px; margin-bottom:1.5rem; border:1px solid rgba(255, 82, 82, 0.3); font-weight:700; display:flex; align-items:center; gap:8px;">
      ⚠️ <?= htmlspecialchars($err) ?>
    </div>
  <?php endif; ?>

  <!-- ── 1. PREMIUM FAST WALLET CARD ── -->
  <div class="premium-wallet-card">
    <div class="pw-badge">
      <span>💳</span> Premium Fast Wallet
    </div>
    <div class="pw-balance"><?= number_format($user['coins_balance'], 2) ?> <span style="font-size:1.8rem; color:var(--gold);"><?= htmlspecialchars($coin_name) ?></span></div>
    <div class="pw-sub">Available Balance &approx; <strong><?= number_format($wallet_balance_bdt, 2) ?> BDT</strong></div>
    
    <div class="pw-actions">
      <!-- Button 1: Deposit -->
      <button type="button" class="btn-deposit-toggle" onclick="toggleWalletDropdown('deposit')">
        <span style="font-size:1.2rem;">⚡</span>
        <span id="depositBtnText">Deposit</span>
      </button>

      <!-- Button 2: Withdraw -->
      <button type="button" class="btn-withdraw-toggle" onclick="toggleWalletDropdown('withdraw')">
        <span>💸</span>
        <span id="withdrawBtnText">Withdraw</span>
      </button>
    </div>

    <!-- ── 1.1 DYNAMIC INLINE DEPOSIT FORM DROPDOWN ── -->
    <div id="depositDropdown" class="wallet-dropdown <?= (isset($_GET['action']) && $_GET['action'] === 'deposit') ? 'active' : '' ?>">
      <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1.5rem; border-bottom:1px solid rgba(255,255,255,0.08); padding-bottom:1rem;">
        <div>
          <h3 style="color:#00e676; margin:0 0 4px 0; font-size:1.3rem; font-weight:800; display:flex; align-items:center; gap:8px;">
            ⚡ Purchase Fast Points (Deposit)
          </h3>
          <p style="color:var(--muted); font-size:0.85rem; margin:0;">Send money via bKash or Nagad to buy points. 1 BDT = 1.00 Fast Point.</p>
        </div>
        <button type="button" onclick="toggleWalletDropdown('deposit')" style="background:none; border:none; color:var(--muted); font-size:1.5rem; cursor:pointer; padding:0;">&times;</button>
      </div>

      <div style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08); border-radius:12px; padding:1.2rem; margin-bottom:1.5rem; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
        <div>
          <span style="font-size:0.75rem; font-weight:800; color:var(--gold); text-transform:uppercase;">Official Payment Wallet:</span>
          <div style="font-size:1.2rem; font-weight:800; color:#fff; margin-top:2px; font-family:monospace;"><?= htmlspecialchars($whatsapp_num) ?></div>
          <div style="font-size:0.75rem; color:var(--muted); margin-top:4px;">Type: Personal (bKash / Nagad Send Money)</div>
        </div>
        <div style="background:#fff; padding:4px; border-radius:8px; display:inline-block;">
          <img src="https://api.qrserver.com/v1/create-qr-code/?size=85x85&data=<?= urlencode('bKash/Nagad: ' . $whatsapp_num) ?>" alt="QR Code" width="85" height="85" style="display:block;" />
        </div>
      </div>

      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="submit_deposit" />

        <div class="dropdown-grid">
          <div class="field">
            <label style="display:block; font-size:0.75rem; font-weight:700; color:var(--muted); text-transform:uppercase; margin-bottom:0.4rem;">Your Sender Mobile Number *</label>
            <input type="tel" name="sender_number" class="custom-input" placeholder="e.g. 017XXXXXXXX" required />
          </div>

          <div class="field">
            <label style="display:block; font-size:0.75rem; font-weight:700; color:var(--muted); text-transform:uppercase; margin-bottom:0.4rem;">Transaction ID (TrxID) *</label>
            <input type="text" name="transaction_id" class="custom-input" placeholder="e.g. BKA876GTR5" required style="font-family:monospace; text-transform:uppercase;" />
          </div>
        </div>

        <div class="field" style="margin-bottom:1.2rem;">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.4rem;">
            <label style="font-size:0.75rem; font-weight:700; color:var(--muted); text-transform:uppercase; margin:0;">Amount Sent (BDT) *</label>
            <div style="display:flex; gap:6px;">
              <span class="quick-chip" onclick="setDepositAmount(100)">+100</span>
              <span class="quick-chip" onclick="setDepositAmount(500)">+500</span>
              <span class="quick-chip" onclick="setDepositAmount(1000)">+1,000</span>
              <span class="quick-chip" onclick="setDepositAmount(2000)">+2,000</span>
            </div>
          </div>
          <input type="number" id="depositAmountInput" name="amount" class="custom-input" placeholder="e.g. 500" min="10" step="any" required />
        </div>

        <div class="field" style="margin-bottom:1.5rem;">
          <label style="display:block; font-size:0.75rem; font-weight:700; color:var(--muted); text-transform:uppercase; margin-bottom:0.4rem;">Upload Payment Receipt / Screenshot (Optional)</label>
          <input type="file" name="screenshot" accept="image/*,.pdf" style="color:var(--muted); font-size:0.85rem;" />
        </div>

        <button type="submit" class="btn" style="background:linear-gradient(135deg, #00e676, #00bfa5); color:#000; font-weight:800; padding:0.9rem; border-radius:10px; font-size:1rem; cursor:pointer; width:100%;">
          ⚡ Submit Deposit Request
        </button>
      </form>
    </div>

    <!-- ── 1.2 DYNAMIC INLINE WITHDRAWAL FORM DROPDOWN ── -->
    <div id="withdrawDropdown" class="wallet-dropdown <?= (isset($_GET['action']) && $_GET['action'] === 'withdraw') ? 'active' : '' ?>">
      <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1.5rem; border-bottom:1px solid rgba(255,255,255,0.08); padding-bottom:1rem;">
        <div>
          <h3 style="color:var(--gold); margin:0 0 4px 0; font-size:1.3rem; font-weight:800; display:flex; align-items:center; gap:8px;">
            💸 Withdraw Funds & Rewards
          </h3>
          <p style="color:var(--muted); font-size:0.85rem; margin:0;">Convert your Fast Site Coins to BDT cash. Minimum withdrawal is 100 Coins.</p>
        </div>
        <button type="button" onclick="toggleWalletDropdown('withdraw')" style="background:none; border:none; color:var(--muted); font-size:1.5rem; cursor:pointer; padding:0;">&times;</button>
      </div>

      <div style="background:rgba(252,185,0,0.05); border:1px solid rgba(252,185,0,0.2); border-radius:12px; padding:1.2rem; margin-bottom:1.5rem; display:grid; grid-template-columns:1fr 1fr; gap:1rem; text-align:center;">
        <div>
          <div style="font-size:0.75rem; color:var(--muted); text-transform:uppercase; font-weight:700;">Available Balance</div>
          <div style="font-size:1.5rem; font-weight:800; color:var(--gold); margin-top:2px;">🪙 <?= number_format($wallet_balance, 2) ?></div>
        </div>
        <div>
          <div style="font-size:0.75rem; color:var(--muted); text-transform:uppercase; font-weight:700;">Estimated Value</div>
          <div style="font-size:1.5rem; font-weight:800; color:#fff; margin-top:2px;">৳<?= number_format($wallet_balance_bdt, 2) ?></div>
        </div>
      </div>

      <form method="POST">
        <input type="hidden" name="action" value="submit_withdraw" />

        <div class="field" style="margin-bottom:1.2rem;">
          <label style="display:block; font-size:0.75rem; font-weight:700; color:var(--muted); text-transform:uppercase; margin-bottom:0.4rem;">Withdrawal Amount (Coins) * (Min 100)</label>
          <input type="number" name="amount_coins" class="custom-input" placeholder="e.g. 500" min="100" max="<?= (int)$wallet_balance ?>" step="any" required />
        </div>

        <div class="dropdown-grid" style="margin-bottom:1.5rem;">
          <div class="field">
            <label style="display:block; font-size:0.75rem; font-weight:700; color:var(--muted); text-transform:uppercase; margin-bottom:0.4rem;">Payment Method *</label>
            <select name="payout_method" class="custom-input" style="cursor:pointer;">
              <option value="bKash">bKash (Personal)</option>
              <option value="Nagad">Nagad (Personal)</option>
              <option value="Rocket">Rocket (Personal)</option>
            </select>
          </div>

          <div class="field">
            <label style="display:block; font-size:0.75rem; font-weight:700; color:var(--muted); text-transform:uppercase; margin-bottom:0.4rem;">Account / Wallet Number *</label>
            <input type="tel" name="payout_account" class="custom-input" placeholder="e.g. 017XXXXXXXX" required />
          </div>
        </div>

        <button type="submit" class="btn" style="background:linear-gradient(135deg, var(--gold), #ff9800); color:#000; font-weight:800; padding:0.9rem; border-radius:10px; font-size:1rem; cursor:pointer; width:100%;">
          💸 Submit Withdrawal Request
        </button>
      </form>
    </div>

  </div>

  <!-- ── 2. UNIFIED WALLET TABS ── -->
  <div class="wallet-tabs">
    <button type="button" class="w-tab-btn active" id="tabBtnTx" onclick="switchWalletTab('transactions')">
      <span>📊</span> Transaction History
    </button>
    <button type="button" class="w-tab-btn" id="tabBtnOrders" onclick="switchWalletTab('orders')">
      <span>🛍️</span> My Shop Orders (<?= count($shop_orders) ?>)
    </button>
    <button type="button" class="w-tab-btn" id="tabBtnWithdrawals" onclick="switchWalletTab('withdrawals')">
      <span>💸</span> Withdrawals (<?= count($withdrawals) ?>)
    </button>
    <button type="button" class="w-tab-btn" id="tabBtnDeposits" onclick="switchWalletTab('deposits')">
      <span>⚡</span> Deposits (<?= count($deposit_requests) ?>)
    </button>
  </div>

  <!-- ── 2.1 TAB: TRANSACTION HISTORY ── -->
  <div id="sectionTransactions">
    <?php if (empty($transactions)): ?>
      <div style="background:rgba(20,20,31,0.6); border:1px dashed rgba(255,255,255,0.1); border-radius:16px; padding:3rem 1.5rem; text-align:center; color:var(--muted);">
        <div style="font-size:3rem; margin-bottom:0.5rem; opacity:0.6;">📫</div>
        <h4 style="color:#fff; margin-bottom:0.3rem;">No Transactions Yet</h4>
        <p style="font-size:0.85rem; margin:0;">Your coin deposits, rewards, and order deductions will appear here.</p>
      </div>
    <?php else: ?>
      <div style="display:flex; flex-direction:column; gap:0.8rem;">
        <?php foreach ($transactions as $tx): 
            $is_credit = ($tx['type'] === 'credit');
        ?>
          <div style="background:rgba(20,20,31,0.85); border:1px solid rgba(255,255,255,0.06); border-radius:14px; padding:1.2rem; display:flex; justify-content:space-between; align-items:center;">
            <div style="display:flex; align-items:center; gap:12px;">
              <div style="width:42px; height:42px; border-radius:10px; background:<?= $is_credit ? 'rgba(0,230,118,0.1)' : 'rgba(255,82,82,0.1)' ?>; color:<?= $is_credit ? '#00e676' : '#ff5252' ?>; display:flex; align-items:center; justify-content:center; font-size:1.2rem;">
                <?= $is_credit ? '↓' : '↑' ?>
              </div>
              <div>
                <div style="color:#fff; font-weight:700; font-size:0.95rem; margin-bottom:2px;"><?= htmlspecialchars($tx['description'] ?? 'Points Transfer') ?></div>
                <div style="color:var(--muted); font-size:0.75rem;"><?= date('M d, Y • h:i A', strtotime($tx['created_at'])) ?></div>
              </div>
            </div>
            <div style="text-align:right;">
              <div style="font-family:'Oswald',sans-serif; font-size:1.25rem; font-weight:700; color:<?= $is_credit ? '#00e676' : '#ff5252' ?>;">
                <?= $is_credit ? '+' : '-' ?><?= number_format($tx['amount'], 2) ?> <?= htmlspecialchars($coin_name) ?>
              </div>
              <span style="font-size:0.7rem; color:var(--muted); text-transform:uppercase;"><?= htmlspecialchars($tx['type']) ?></span>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- ── 2.2 TAB: MY SHOP ORDERS ── -->
  <div id="sectionOrders" style="display:none;">
    <?php if (empty($shop_orders)): ?>
      <div style="background:rgba(20,20,31,0.6); border:1px dashed rgba(255,255,255,0.1); border-radius:16px; padding:3rem 1.5rem; text-align:center; color:var(--muted);">
        <div style="font-size:3rem; margin-bottom:0.5rem; opacity:0.6;">🛍️</div>
        <h4 style="color:#fff; margin-bottom:0.3rem;">No Shop Orders Placed Yet</h4>
        <p style="font-size:0.85rem; margin-bottom:1.5rem;">Explore the marketplace to buy digital products and services using your Fast Points.</p>
        <a href="../index.php" class="btn" style="background:linear-gradient(135deg, var(--brand), #007bb5); color:#fff; font-weight:800; padding:0.6rem 1.5rem; border-radius:8px; text-decoration:none; display:inline-block;">
          Browse Marketplace ➔
        </a>
      </div>
    <?php else: ?>
      <?php foreach ($shop_orders as $ord): 
          $badge_color = '#fcb900';
          if ($ord['status'] === 'completed') $badge_color = '#00e676';
          elseif ($ord['status'] === 'cancelled') $badge_color = '#ff5252';
          elseif ($ord['status'] === 'in_progress') $badge_color = '#2196f3';
      ?>
        <div class="order-card">
          <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1rem; flex-wrap:wrap; gap:0.5rem;">
            <div>
              <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px;">
                <span style="font-size:0.85rem; font-weight:800; color:var(--brand);">ORDER #<?= $ord['id'] ?></span>
                <span style="background:rgba(255,255,255,0.06); color:var(--muted); padding:2px 8px; border-radius:4px; font-size:0.7rem;">
                  <?= date('M d, Y h:i A', strtotime($ord['created_at'])) ?>
                </span>
              </div>
              <h3 style="color:#fff; font-size:1.15rem; font-weight:800; margin:0 0 4px 0;">
                <?= htmlspecialchars($ord['product_title'] ?? 'Marketplace Item') ?>
              </h3>
              <div style="font-size:0.8rem; color:var(--muted);">
                Seller: <strong style="color:#fff;"><?= htmlspecialchars($ord['business_name'] ?? 'Partner Shop') ?></strong>
              </div>
            </div>
            <div style="text-align:right;">
              <div style="font-family:'Oswald',sans-serif; font-size:1.3rem; font-weight:800; color:var(--gold);">
                🪙 <?= number_format($ord['total_amount'], 2) ?> <?= htmlspecialchars($coin_name) ?>
              </div>
              <span style="background:rgba(255,255,255,0.05); color:<?= $badge_color ?>; border:1px solid <?= $badge_color ?>; padding:2px 8px; border-radius:20px; font-size:0.7rem; font-weight:800; text-transform:uppercase;">
                <?= htmlspecialchars($ord['status']) ?>
              </span>
            </div>
          </div>

          <?php if (!empty($ord['notes'])): ?>
            <div style="background:rgba(0,0,0,0.3); border-radius:8px; padding:0.8rem; font-size:0.8rem; color:#bbb; margin-bottom:1rem;">
              <strong>Delivery Notes / Credentials:</strong><br/>
              <?= nl2br(htmlspecialchars($ord['notes'])) ?>
            </div>
          <?php endif; ?>

          <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px solid rgba(255,255,255,0.06); padding-top:0.8rem;">
            <a href="messages.php?partner_id=<?= $ord['partner_id'] ?>" style="color:var(--brand); text-decoration:none; font-size:0.8rem; font-weight:700; display:inline-flex; align-items:center; gap:4px;">
              💬 Message Seller
            </a>

            <?php if (in_array($ord['status'], ['accepted', 'in_progress', 'waiting_confirmation'])): ?>
              <form method="POST" style="margin:0;" onsubmit="return confirm('Confirm that you have received your order successfully?');">
                <input type="hidden" name="action" value="release_order" />
                <input type="hidden" name="order_id" value="<?= $ord['id'] ?>" />
                <button type="submit" style="background:linear-gradient(135deg, #00e676, #00bfa5); color:#000; border:none; padding:0.4rem 1rem; border-radius:6px; font-weight:800; font-size:0.75rem; cursor:pointer;">
                  ✓ Confirm Delivery & Release Payment
                </button>
              </form>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- ── 2.3 TAB: WITHDRAWAL REQUESTS HISTORY ── -->
  <div id="sectionWithdrawals" style="display:none;">
    <?php if (empty($withdrawals)): ?>
      <div style="background:rgba(20,20,31,0.6); border:1px dashed rgba(255,255,255,0.1); border-radius:16px; padding:3rem 1.5rem; text-align:center; color:var(--muted);">
        <div style="font-size:3rem; margin-bottom:0.5rem; opacity:0.6;">💸</div>
        <h4 style="color:#fff; margin-bottom:0.3rem;">No Withdrawal Requests</h4>
        <p style="font-size:0.85rem; margin:0;">Click "Withdraw" above to cash out your rewards.</p>
      </div>
    <?php else: ?>
      <div style="display:flex; flex-direction:column; gap:0.8rem;">
        <?php foreach ($withdrawals as $wth): 
            $w_color = '#fcb900';
            if ($wth['status'] === 'approved' || $wth['status'] === 'completed' || $wth['status'] === 'paid') $w_color = '#00e676';
            elseif ($wth['status'] === 'rejected' || $wth['status'] === 'failed') $w_color = '#ff5252';
        ?>
          <div style="background:rgba(20,20,31,0.85); border:1px solid rgba(255,255,255,0.06); border-radius:14px; padding:1.2rem; display:flex; justify-content:space-between; align-items:center;">
            <div>
              <div style="color:#fff; font-weight:700; font-size:0.95rem; margin-bottom:2px;">
                Payout to: <strong style="color:var(--brand);"><?= htmlspecialchars($wth['payout_method']) ?></strong> (<?= htmlspecialchars($wth['payout_account']) ?>)
              </div>
              <div style="color:var(--muted); font-size:0.75rem;">
                Requested on <?= date('M d, Y • h:i A', strtotime($wth['created_at'])) ?>
              </div>
            </div>
            <div style="text-align:right;">
              <div style="font-family:'Oswald',sans-serif; font-size:1.25rem; font-weight:700; color:var(--gold);">
                🪙 <?= number_format($wth['amount_coins'], 2) ?> <?= htmlspecialchars($coin_name) ?>
              </div>
              <span style="font-size:0.7rem; font-weight:800; text-transform:uppercase; color:<?= $w_color ?>;">
                <?= htmlspecialchars($wth['status']) ?>
              </span>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- ── 2.4 TAB: DEPOSIT REQUESTS HISTORY ── -->
  <div id="sectionDeposits" style="display:none;">
    <?php if (empty($deposit_requests)): ?>
      <div style="background:rgba(20,20,31,0.6); border:1px dashed rgba(255,255,255,0.1); border-radius:16px; padding:3rem 1.5rem; text-align:center; color:var(--muted);">
        <div style="font-size:3rem; margin-bottom:0.5rem; opacity:0.6;">⚡</div>
        <h4 style="color:#fff; margin-bottom:0.3rem;">No Deposit Requests</h4>
        <p style="font-size:0.85rem; margin:0;">Click "Deposit" above to buy points.</p>
      </div>
    <?php else: ?>
      <div style="display:flex; flex-direction:column; gap:0.8rem;">
        <?php foreach ($deposit_requests as $dep): 
            $status_color = '#fcb900';
            if ($dep['status'] === 'approved') $status_color = '#00e676';
            elseif ($dep['status'] === 'rejected') $status_color = '#ff5252';
        ?>
          <div style="background:rgba(20,20,31,0.85); border:1px solid rgba(255,255,255,0.06); border-radius:14px; padding:1.2rem; display:flex; justify-content:space-between; align-items:center;">
            <div>
              <div style="color:#fff; font-weight:700; font-size:0.95rem; margin-bottom:2px;">
                TrxID: <span style="font-family:monospace; color:var(--brand);"><?= htmlspecialchars($dep['transaction_id']) ?></span>
              </div>
              <div style="color:var(--muted); font-size:0.75rem;">
                Sender: <?= htmlspecialchars($dep['sender_number']) ?> • <?= date('M d, Y • h:i A', strtotime($dep['created_at'])) ?>
              </div>
            </div>
            <div style="text-align:right;">
              <div style="font-family:'Oswald',sans-serif; font-size:1.25rem; font-weight:700; color:var(--gold);">
                ৳<?= number_format($dep['amount'], 2) ?> BDT
              </div>
              <span style="font-size:0.7rem; font-weight:800; text-transform:uppercase; color:<?= $status_color ?>;">
                <?= htmlspecialchars($dep['status']) ?>
              </span>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

</div>

<script>
function toggleWalletDropdown(type) {
  const depEl = document.getElementById('depositDropdown');
  const wthEl = document.getElementById('withdrawDropdown');

  if (type === 'deposit') {
    wthEl.classList.remove('active');
    depEl.classList.toggle('active');
    if (depEl.classList.contains('active')) {
      depEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  } else if (type === 'withdraw') {
    depEl.classList.remove('active');
    wthEl.classList.toggle('active');
    if (wthEl.classList.contains('active')) {
      wthEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  }
}

function setDepositAmount(val) {
  document.getElementById('depositAmountInput').value = val;
}

function switchWalletTab(tab) {
  const secTx = document.getElementById('sectionTransactions');
  const secOrders = document.getElementById('sectionOrders');
  const secWithdrawals = document.getElementById('sectionWithdrawals');
  const secDeposits = document.getElementById('sectionDeposits');

  const btnTx = document.getElementById('tabBtnTx');
  const btnOrders = document.getElementById('tabBtnOrders');
  const btnWithdrawals = document.getElementById('tabBtnWithdrawals');
  const btnDeposits = document.getElementById('tabBtnDeposits');

  btnTx.classList.remove('active');
  btnOrders.classList.remove('active');
  btnWithdrawals.classList.remove('active');
  btnDeposits.classList.remove('active');

  secTx.style.display = 'none';
  secOrders.style.display = 'none';
  secWithdrawals.style.display = 'none';
  secDeposits.style.display = 'none';

  if (tab === 'transactions') {
    secTx.style.display = 'block';
    btnTx.classList.add('active');
  } else if (tab === 'orders') {
    secOrders.style.display = 'block';
    btnOrders.classList.add('active');
  } else if (tab === 'withdrawals') {
    secWithdrawals.style.display = 'block';
    btnWithdrawals.classList.add('active');
  } else if (tab === 'deposits') {
    secDeposits.style.display = 'block';
    btnDeposits.classList.add('active');
  }
}
</script>

</body>
</html>
