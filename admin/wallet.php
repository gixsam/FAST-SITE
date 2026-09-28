<?php
// =========================================================================
// admin/wallet.php — Admin Master Wallet & Liquidity Hub (Desktop & Mobile Optimized)
// =========================================================================
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/../config.php';

// Fetch global economy metrics safely
$total_coins = 0;
$total_pending_withdraw = 0;
$total_paid_out = 0;
$official_revenue = 0;
$recent_txs = [];

try {
    $total_coins = (float)($pdo->query("SELECT SUM(coins_balance) FROM users")->fetchColumn() ?: 0);
} catch (Exception $e) {}

try {
    $total_pending_withdraw = (float)($pdo->query("SELECT SUM(amount_coins) FROM user_withdrawals WHERE status = 'pending'")->fetchColumn() ?: 0);
} catch (Exception $e) {}

try {
    $total_paid_out = (float)($pdo->query("SELECT SUM(amount_coins) FROM user_withdrawals WHERE status IN ('paid', 'approved', 'completed')")->fetchColumn() ?: 0);
} catch (Exception $e) {}

try {
    $stmt_off = $pdo->query("SELECT total_earned FROM partners WHERE is_official = 1 LIMIT 1");
    if ($stmt_off && ($off = $stmt_off->fetch())) {
        $official_revenue = (float)($off['total_earned'] ?? 0);
    }
} catch (Exception $e) {}

$msg = '';
// Handle Admin Gifting Coins
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'gift_coins') {
    $target_user = trim($_POST['target_user'] ?? ''); // can be ID, email, or phone
    $amount = floatval($_POST['amount'] ?? 0);
    $note = trim($_POST['note'] ?? 'Admin Gift');
    
    if (!empty($target_user) && $amount > 0) {
        $stmt_find = $pdo->prepare("SELECT id, name FROM users WHERE id = ? OR email = ? OR phone = ? LIMIT 1");
        $stmt_find->execute([$target_user, $target_user, $target_user]);
        $foundUser = $stmt_find->fetch(PDO::FETCH_ASSOC);
        
        if ($foundUser) {
            $found_user_id = $foundUser['id'];
            $found_user_name = $foundUser['name'] ?: "User #$found_user_id";
            try {
                $pdo->beginTransaction();
                $pdo->prepare("UPDATE users SET coins_balance = coins_balance + ? WHERE id = ?")->execute([$amount, $found_user_id]);
                
                // Defensive transaction recording: try 'reference' column first, then 'description'
                $giftNote = "Admin Gift: " . $note;
                $txInserted = false;
                try {
                    $pdo->prepare("INSERT INTO coin_transactions (user_id, type, amount, reference, status, created_at) VALUES (?, 'deposit', ?, ?, 'completed', CURRENT_TIMESTAMP)")
                        ->execute([$found_user_id, $amount, $giftNote]);
                    $txInserted = true;
                } catch (Exception $e1) {
                    try {
                        $pdo->prepare("INSERT INTO coin_transactions (user_id, type, amount, description) VALUES (?, 'deposit', ?, ?)")
                            ->execute([$found_user_id, $amount, $giftNote]);
                        $txInserted = true;
                    } catch (Exception $e2) {}
                }

                $pdo->commit();
                $msg = "<div style='color:#00e676; margin-bottom:1.5rem; padding:1rem 1.4rem; background:rgba(0,230,118,0.12); border-radius:14px; font-weight:700; border:1px solid rgba(0,230,118,0.3); display:flex; align-items:center; gap:8px;'>✅ Successfully gifted " . number_format($amount, 2) . " Coins to $found_user_name (#$found_user_id)!</div>";
                
                // Refresh total
                $total_coins = (float)($pdo->query("SELECT SUM(coins_balance) FROM users")->fetchColumn() ?: 0);
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $msg = "<div style='color:#ff5252; margin-bottom:1.5rem; padding:1rem 1.4rem; background:rgba(255,82,82,0.12); border-radius:14px; font-weight:700; border:1px solid rgba(255,82,82,0.3); display:flex; align-items:center; gap:8px;'>⚠️ Failed to gift coins: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        } else {
            $msg = "<div style='color:#ff5252; margin-bottom:1.5rem; padding:1rem 1.4rem; background:rgba(255,82,82,0.12); border-radius:14px; font-weight:700; border:1px solid rgba(255,82,82,0.3); display:flex; align-items:center; gap:8px;'>⚠️ User not found by provided ID, Email, or Phone!</div>";
        }
    }
}

// Handle Admin Manual Coin Debit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'admin_debit_coins') {
    $target_user = trim($_POST['target_user'] ?? '');
    $amount = floatval($_POST['amount'] ?? 0);
    $note = trim($_POST['note'] ?? 'Admin Manual Debit');
    
    if (!empty($target_user) && $amount > 0) {
        $stmt_find = $pdo->prepare("SELECT id, name, coins_balance FROM users WHERE id = ? OR email = ? OR phone = ? LIMIT 1");
        $stmt_find->execute([$target_user, $target_user, $target_user]);
        $foundUser = $stmt_find->fetch(PDO::FETCH_ASSOC);
        
        if ($foundUser) {
            $found_user_id = $foundUser['id'];
            $found_user_name = $foundUser['name'] ?: "User #$found_user_id";
            try {
                $pdo->beginTransaction();
                // Safe deduct (prevents negative balance)
                $pdo->prepare("UPDATE users SET coins_balance = GREATEST(0, coins_balance - ?) WHERE id = ?")->execute([$amount, $found_user_id]);
                
                $debitNote = "Admin Debit: " . $note;
                try {
                    $pdo->prepare("INSERT INTO coin_transactions (user_id, type, amount, reference, status, created_at) VALUES (?, 'admin_debit', ?, ?, 'completed', CURRENT_TIMESTAMP)")
                        ->execute([$found_user_id, $amount, $debitNote]);
                } catch (Exception $e1) {
                    try {
                        $pdo->prepare("INSERT INTO coin_transactions (user_id, type, amount, description) VALUES (?, 'admin_debit', ?, ?)")
                            ->execute([$found_user_id, $amount, $debitNote]);
                    } catch (Exception $e2) {}
                }

                $pdo->commit();
                $msg = "<div style='color:#ef4444; margin-bottom:1.5rem; padding:1rem 1.4rem; background:rgba(239,68,68,0.12); border-radius:14px; font-weight:700; border:1px solid rgba(239,68,68,0.3); display:flex; align-items:center; gap:8px;'>✅ Successfully debited " . number_format($amount, 2) . " Coins from $found_user_name (#$found_user_id)!</div>";
                
                // Refresh total
                $total_coins = (float)($pdo->query("SELECT SUM(coins_balance) FROM users")->fetchColumn() ?: 0);
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $msg = "<div style='color:#ff5252; margin-bottom:1.5rem; padding:1rem 1.4rem; background:rgba(255,82,82,0.12); border-radius:14px; font-weight:700; border:1px solid rgba(255,82,82,0.3); display:flex; align-items:center; gap:8px;'>⚠️ Failed to debit coins: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        } else {
            $msg = "<div style='color:#ff5252; margin-bottom:1.5rem; padding:1rem 1.4rem; background:rgba(255,82,82,0.12); border-radius:14px; font-weight:700; border:1px solid rgba(255,82,82,0.3); display:flex; align-items:center; gap:8px;'>⚠️ User not found!</div>";
        }
    }
}

// Fetch recent global transactions safely with User & Shop names
$recent_txs = [];
try {
    $recent_txs = $pdo->query("
        SELECT 
            c.id as request_id,
            c.type,
            c.amount,
            c.created_at,
            c.status,
            c.reference as ref_id,
            c.description as account_info,
            c.user_id,
            u.name as user_name,
            u.phone as user_phone
        FROM coin_transactions c
        LEFT JOIN users u ON c.user_id = u.id
        ORDER BY c.created_at DESC LIMIT 15
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0"/>
  <title>Master Wallet & Revenue — Fast Site Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Oswald:wght@600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="/assets/css/admin.css?v=<?= time() ?>"/>
  <style>
    /* Responsive Master Wallet Styles */
    .wallet-hero-actions {
      display: flex;
      gap: 0.6rem;
      flex-wrap: wrap;
      margin-top: 1rem;
    }
    .wallet-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      gap: 1.2rem;
      margin-bottom: 2rem;
    }
    .wallet-stat-card {
      background: rgba(20, 20, 31, 0.85);
      backdrop-filter: blur(15px);
      border-radius: 18px;
      padding: 1.6rem;
      position: relative;
      overflow: hidden;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
      border: 1px solid rgba(255, 255, 255, 0.08);
      transition: transform 0.2s, box-shadow 0.2s;
    }
    .wallet-stat-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5);
    }
    .stat-label {
      font-size: 0.75rem;
      font-weight: 800;
      color: var(--muted);
      text-transform: uppercase;
      letter-spacing: 0.8px;
      margin-bottom: 0.4rem;
    }
    .stat-value {
      font-size: 2.2rem;
      font-weight: 800;
      font-family: 'Oswald', sans-serif;
      line-height: 1.1;
      display: flex;
      align-items: center;
      gap: 0.4rem;
    }
    .stat-bg-icon {
      position: absolute;
      right: -10px;
      bottom: -15px;
      font-size: 5.5rem;
      opacity: 0.06;
      pointer-events: none;
    }
    
    .panel-card {
      background: rgba(20, 20, 31, 0.85);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 20px;
      padding: 1.8rem;
      margin-bottom: 2rem;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.4);
    }
    .gift-form-grid {
      display: grid;
      grid-template-columns: 2fr 1.5fr 2fr auto;
      gap: 1rem;
      align-items: end;
    }
    @media (max-width: 900px) {
      .gift-form-grid {
        grid-template-columns: 1fr 1fr;
      }
      .gift-form-grid button {
        grid-column: span 2;
      }
    }
    @media (max-width: 600px) {
      .gift-form-grid {
        grid-template-columns: 1fr;
      }
      .gift-form-grid button {
        grid-column: span 1;
      }
      .stat-value {
        font-size: 1.8rem;
      }
    }

    .custom-input {
      width: 100%;
      background: rgba(0, 0, 0, 0.4) !important;
      border: 1px solid rgba(255, 255, 255, 0.12) !important;
      padding: 0.75rem 1rem !important;
      border-radius: 10px !important;
      color: #fff !important;
      font-size: 0.9rem !important;
      outline: none !important;
    }
    .custom-input:focus {
      border-color: var(--gold) !important;
      box-shadow: 0 0 10px rgba(252, 185, 0, 0.25) !important;
    }

    .tx-item-link {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1.1rem 1.3rem;
      background: rgba(0, 0, 0, 0.28);
      border-radius: 14px;
      border: 1px solid rgba(255, 255, 255, 0.05);
      margin-bottom: 0.8rem;
      transition: all 0.2s ease;
      text-decoration: none;
      color: inherit;
    }
    .tx-item-link:hover {
      background: rgba(255, 255, 255, 0.04);
      border-color: rgba(252, 185, 0, 0.3);
      transform: translateX(4px);
    }
  </style>
</head>
<body>

<?php include __DIR__ . '/nav.php'; ?>

<div class="dashboard-container">

  <!-- Header Admin Hero -->
  <div class="admin-hero">
    <h2 class="admin-hero-title">💼 MASTER WALLET & REVENUE HUB</h2>
    <p class="admin-hero-subtitle">Global Platform Liquidity, User Points Economy & Direct Coin Management</p>
    
    <div class="wallet-hero-actions">
      <a href="coin_deposits.php" class="btn-sm" style="display:inline-flex; align-items:center; gap:0.4rem; padding: 0.55rem 1.2rem; background: rgba(16, 185, 129, 0.15); border:1px solid #10b981; color:#10b981; font-weight:700; text-decoration:none; border-radius:10px;">
        💰 Manage User Deposits ➔
      </a>
      <a href="user_withdrawals.php" class="btn-sm" style="display:inline-flex; align-items:center; gap:0.4rem; padding: 0.55rem 1.2rem; background: rgba(239, 68, 68, 0.15); border:1px solid #ef4444; color:#ef4444; font-weight:700; text-decoration:none; border-radius:10px;">
        💸 User Cashouts ➔
      </a>
      <a href="payouts.php" class="btn-sm" style="display:inline-flex; align-items:center; gap:0.4rem; padding: 0.55rem 1.2rem; background: rgba(33, 150, 243, 0.15); border:1px solid #2196f3; color:#fff; font-weight:700; text-decoration:none; border-radius:10px;">
        🤝 Agent Payouts ➔
      </a>
      <a href="impersonate_official.php" class="btn-sm" style="display:inline-flex; align-items:center; gap:0.4rem; padding: 0.55rem 1.2rem; background: linear-gradient(135deg, var(--gold) 0%, #f59e0b 100%); color:#000; font-weight:800; text-decoration:none; border-radius:10px;">
        🏪 Official Shop ➔
      </a>
    </div>
  </div>

  <div class="wrap" style="padding: 0; max-width: 100%;">

    <?= $msg ?>

    <!-- ── 1. MASTER METRICS GRID ── -->
    <div class="wallet-grid">
      <!-- Total Economy -->
      <div class="wallet-stat-card" style="border-color: rgba(252, 185, 0, 0.35); background: linear-gradient(145deg, rgba(20,20,31,0.95), rgba(252,185,0,0.06));">
        <div class="stat-label">Total User Coins in Circulation</div>
        <div class="stat-value" style="color: var(--gold);">🪙 <?= number_format($total_coins, 2) ?></div>
        <div class="stat-bg-icon">🏦</div>
      </div>
      
      <!-- Official Shop Revenue -->
      <div class="wallet-stat-card" style="border-color: rgba(33, 150, 243, 0.35); background: linear-gradient(145deg, rgba(20,20,31,0.95), rgba(33,150,243,0.06));">
        <div class="stat-label">Fast Site Official Earnings</div>
        <div class="stat-value" style="color: #60a5fa;">৳ <?= number_format($official_revenue, 2) ?></div>
        <div class="stat-bg-icon">🏪</div>
      </div>

      <!-- Paid Out -->
      <div class="wallet-stat-card" style="border-color: rgba(0, 230, 118, 0.35); background: linear-gradient(145deg, rgba(20,20,31,0.95), rgba(0,230,118,0.06));">
        <div class="stat-label">Total Cash Paid Out</div>
        <div class="stat-value" style="color: #00e676;">৳ <?= number_format($total_paid_out, 2) ?></div>
        <div class="stat-bg-icon">💸</div>
      </div>

      <!-- Pending Cashout -->
      <div class="wallet-stat-card" style="border-color: rgba(255, 82, 82, 0.35); background: linear-gradient(145deg, rgba(20,20,31,0.95), rgba(255,82,82,0.06));">
        <div class="stat-label">Pending Payout Requests</div>
        <div class="stat-value" style="color: #ff5252;">৳ <?= number_format($total_pending_withdraw, 2) ?></div>
        <div class="stat-bg-icon">⏳</div>
      </div>
    </div>

    <!-- ── 2. INSTANT COIN GIFTING TOOL ── -->
    <div class="panel-card" style="border-color: rgba(0, 230, 118, 0.3);">
      <h3 style="color:#00e676; font-size:1.15rem; font-weight:800; margin-bottom:0.4rem; display:flex; align-items:center; gap:8px;">
        <span>🎁</span> Instant Fast Site Coins Credit (Admin Gift / Compensation)
      </h3>
      <p style="color:var(--muted); font-size:0.85rem; margin-bottom:1.5rem;">
        Instantly credit Fast Site Coins to any user's balance without manual invoice approvals.
      </p>

      <form method="POST" class="gift-form-grid">
        <input type="hidden" name="action" value="gift_coins"/>
        
        <div>
          <label style="display:block; font-size:0.75rem; font-weight:800; color:var(--muted); text-transform:uppercase; margin-bottom:0.4rem;">User ID, Email or Phone *</label>
          <input type="text" name="target_user" class="custom-input" placeholder="e.g. 5 or user@email.com or 01337320544" required />
        </div>

        <div>
          <label style="display:block; font-size:0.75rem; font-weight:800; color:var(--muted); text-transform:uppercase; margin-bottom:0.4rem;">Amount (Coins) *</label>
          <input type="number" step="0.01" min="0.01" name="amount" class="custom-input" placeholder="e.g. 500" required />
        </div>

        <div>
          <label style="display:block; font-size:0.75rem; font-weight:800; color:var(--muted); text-transform:uppercase; margin-bottom:0.4rem;">Note / Reason (Optional)</label>
          <input type="text" name="note" class="custom-input" placeholder="e.g. Welcome Reward / System Bonus" />
        </div>

        <button type="submit" class="btn" style="background: linear-gradient(135deg, #00e676 0%, #00bfa5 100%); color:#000; font-weight:900; padding: 0.8rem 1.6rem; border-radius: 10px; border:none; cursor:pointer; font-size:0.95rem; white-space:nowrap;">
          Send Coins 🚀
        </button>
      </form>
    </div>

    <!-- ── 2B. INSTANT COIN DEBIT TOOL ── -->
    <div class="panel-card" style="border-color: rgba(239, 68, 68, 0.3); margin-top: 2rem;">
      <h3 style="color:#ef4444; font-size:1.15rem; font-weight:800; margin-bottom:0.4rem; display:flex; align-items:center; gap:8px;">
        <span>🔴</span> Instant Fast Site Coins Debit (Admin Fine / Correction)
      </h3>
      <p style="color:var(--muted); font-size:0.85rem; margin-bottom:1.5rem;">
        Instantly deduct Fast Site Coins from any user's balance. Negative balances are prevented (minimum is 0).
      </p>

      <form method="POST" class="gift-form-grid">
        <input type="hidden" name="action" value="admin_debit_coins"/>
        
        <div>
          <label style="display:block; font-size:0.75rem; font-weight:800; color:var(--muted); text-transform:uppercase; margin-bottom:0.4rem;">User ID, Email or Phone *</label>
          <input type="text" name="target_user" class="custom-input" placeholder="e.g. 5 or user@email.com or 01337320544" required />
        </div>

        <div>
          <label style="display:block; font-size:0.75rem; font-weight:800; color:var(--muted); text-transform:uppercase; margin-bottom:0.4rem;">Amount (Coins to Deduct) *</label>
          <input type="number" step="0.01" min="0.01" name="amount" class="custom-input" placeholder="e.g. 50" required />
        </div>

        <div>
          <label style="display:block; font-size:0.75rem; font-weight:800; color:var(--muted); text-transform:uppercase; margin-bottom:0.4rem;">Note / Reason (Optional)</label>
          <input type="text" name="note" class="custom-input" placeholder="e.g. Fraud Correction / Rule Violation" />
        </div>

        <button type="submit" class="btn" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color:#fff; font-weight:900; padding: 0.8rem 1.6rem; border-radius: 10px; border:none; cursor:pointer; font-size:0.95rem; white-space:nowrap;">
          Deduct Coins 🔻
        </button>
      </form>
    </div>

    <!-- ── 3. RECENT GLOBAL TRANSACTIONS ── -->
    <div class="panel-card">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.2rem; flex-wrap:wrap; gap:0.5rem;">
        <h3 style="color:#fff; font-size:1.15rem; font-weight:800; margin:0; display:flex; align-items:center; gap:8px;">
          📜 Recent Global Financial Transactions
        </h3>
        <div style="display:flex; gap:0.6rem; align-items:center;">
          <a href="coin_deposits.php" style="color:#10b981; text-decoration:none; font-weight:700; font-size:0.82rem;">
            Deposit Approvals ➔
          </a>
          <span style="color:rgba(255,255,255,0.2);">|</span>
          <a href="payouts.php" style="color:var(--brand); text-decoration:none; font-weight:700; font-size:0.82rem;">
            Full Payouts Ledger ➔
          </a>
        </div>
      </div>

      <?php if (empty($recent_txs)): ?>
        <div style="text-align:center; padding:2.5rem; color:var(--muted);">
          <div style="font-size:2.5rem; margin-bottom:0.4rem; opacity:0.5;">📫</div>
          No financial transactions recorded yet.
        </div>
      <?php else: ?>
        <div>
          <?php foreach ($recent_txs as $tx): 
              $isDep = in_array(strtolower($tx['type']), ['deposit', 'streak_reward']);
              $st = strtolower($tx['status'] ?? 'pending');
              $statusColor = '#fcb900';
              if (in_array($st, ['approved', 'paid', 'completed'])) $statusColor = '#10b981';
              elseif (in_array($st, ['rejected', 'failed', 'cancelled'])) $statusColor = '#ef4444';
              
              $actionUrl = "javascript:void(0);";
              if (strtolower($tx['type']) === 'deposit') $actionUrl = "coin_deposits.php";
              if (strtolower($tx['type']) === 'withdrawal') $actionUrl = "user_withdrawals.php";
              
              $displayUser = !empty($tx['user_name']) ? $tx['user_name'] : (!empty($tx['user_id']) ? "Customer #{$tx['user_id']}" : "Unregistered User");
              
              $txTitle = "Platform Transaction";
              if (strtolower($tx['type']) === 'deposit') $txTitle = 'User Fast Points Purchase (Deposit)';
              elseif (strtolower($tx['type']) === 'withdrawal') $txTitle = 'User Rewards Cashout (Withdrawal)';
              elseif (strtolower($tx['type']) === 'admin_debit') $txTitle = 'Admin Manual Coin Deduction';
              elseif (strtolower($tx['type']) === 'streak_reward') $txTitle = 'Daily Streak Reward';
          ?>
            <a href="<?= $actionUrl ?>" class="tx-item-link" title="Click to view details">
              <div style="display:flex; align-items:center; gap:14px; min-width:0;">
                <div style="width:44px; height:44px; border-radius:12px; background:<?= $isDep ? 'rgba(16,185,129,0.14)' : 'rgba(239,68,68,0.14)' ?>; color:<?= $isDep ? '#10b981' : '#ef4444' ?>; display:flex; align-items:center; justify-content:center; font-size:1.3rem; flex-shrink:0;">
                  <?= $isDep ? '⬇' : '⬆' ?>
                </div>
                <div style="min-width:0;">
                  <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-bottom:3px;">
                    <span style="color:#fff; font-weight:800; font-size:0.95rem;">
                      <?= htmlspecialchars($txTitle) ?>
                    </span>
                    <span style="font-size:0.75rem; background:rgba(255,255,255,0.06); color:#cbd5e1; padding:2px 8px; border-radius:6px; font-weight:700;">
                      👤 <?= htmlspecialchars($displayUser) ?>
                    </span>
                    <?php if (!empty($tx['user_phone'])): ?>
                      <span style="font-size:0.72rem; color:#94a3b8; font-family:monospace;">
                        (<?= htmlspecialchars($tx['user_phone']) ?>)
                      </span>
                    <?php endif; ?>
                  </div>
                  <div style="color:#94a3b8; font-size:0.75rem; display:flex; gap:10px; flex-wrap:wrap;">
                    <span>📅 <?= date('d M Y, h:i A', strtotime($tx['created_at'])) ?></span>
                    <?php if (!empty($tx['ref_id'])): ?>
                      <span style="color:var(--gold);">🔖 Trx/Method: <?= htmlspecialchars($tx['ref_id']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($tx['account_info'])): ?>
                      <span style="color:#38bdf8;">📱 <?= htmlspecialchars($tx['account_info']) ?></span>
                    <?php endif; ?>
                  </div>
                </div>
              </div>

              <div style="text-align:right; flex-shrink:0; margin-left:1rem;">
                <div style="font-family:'Oswald',sans-serif; font-size:1.3rem; font-weight:700; color:<?= $isDep ? '#10b981' : 'var(--gold)' ?>;">
                  <?= $isDep ? '+' : '-' ?><?= number_format($tx['amount'], 2) ?> Coins
                </div>
                <div style="display:flex; align-items:center; justify-content:flex-end; gap:6px; margin-top:2px;">
                  <span style="font-size:0.7rem; font-weight:800; text-transform:uppercase; color:<?= $statusColor ?>; background:rgba(255,255,255,0.04); padding:2px 8px; border-radius:10px; border:1px solid <?= $statusColor ?>;">
                    <?= htmlspecialchars($tx['status']) ?>
                  </span>
                </div>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

  </div>
</div>

</body>
</html>
