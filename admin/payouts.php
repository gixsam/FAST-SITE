<?php
// =========================================================================
// admin/payouts.php — Master Financial Payouts & Gateway Settings Hub
// =========================================================================
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/../config.php';

$msg = $err = '';

// Handle Gateway Settings & Disbursement Number Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_payment_settings'])) {
    $settings = [
        'payment_card_enabled'   => isset($_POST['payment_card_enabled']) ? '1' : '0',
        'payment_bkash_enabled'  => isset($_POST['payment_bkash_enabled']) ? '1' : '0',
        'payment_nagad_enabled'  => isset($_POST['payment_nagad_enabled']) ? '1' : '0',
        'payment_cod_enabled'    => isset($_POST['payment_cod_enabled']) ? '1' : '0',
        'delivery_inside_dhaka'  => floatval($_POST['delivery_inside_dhaka'] ?? 60),
        'delivery_outside_dhaka' => floatval($_POST['delivery_outside_dhaka'] ?? 120),
        'official_payout_number' => trim($_POST['official_payout_number'] ?? '+8801337320544'),
    ];

    try {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $stmt = $pdo->prepare("INSERT INTO partner_settings (setting_key, setting_value) 
                VALUES (:key, :val) 
                ON CONFLICT(setting_key) DO UPDATE SET setting_value = :val, updated_at = CURRENT_TIMESTAMP");
        } else {
            $stmt = $pdo->prepare("INSERT INTO partner_settings (setting_key, setting_value) 
                VALUES (:key, :val) 
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        }

        foreach ($settings as $k => $v) {
            $stmt->execute([':key' => $k, ':val' => strval($v)]);
        }
        $msg = 'Payment Gateways & Delivery settings saved successfully!';
    } catch (Exception $e) {
        $err = 'Error saving settings: ' . $e->getMessage();
    }
}

// Handle Mark as Paid
if (isset($_GET['pay']) && isset($_GET['id'])) {
    $id   = (int)$_GET['id'];
    $note = trim($_GET['note'] ?? 'Settled via Admin Gateway');
    try {
        $pdo->prepare("
            UPDATE agent_payouts
            SET status='paid', admin_note=:n, paid_at=CURRENT_TIMESTAMP
            WHERE id=:id AND status='pending'
        ")->execute([':n' => $note, ':id' => $id]);
        $msg = 'Payout marked as paid successfully!';
    } catch (Exception $e) {
        $err = 'Error marking payout paid: ' . $e->getMessage();
    }
}

// Handle Reject & Refund
if (isset($_GET['reject'])) {
    $id = (int)$_GET['reject'];
    try {
        $pr = $pdo->prepare("SELECT agent_id, amount FROM agent_payouts WHERE id=:id AND status='pending' LIMIT 1");
        $pr->execute([':id'=>$id]);
        $pr_row = $pr->fetch();
        if ($pr_row) {
            $pdo->prepare("UPDATE agents SET total_withdrawn = total_withdrawn - :amt WHERE id=:id")
                ->execute([':amt'=>$pr_row['amount'], ':id'=>$pr_row['agent_id']]);
            $pdo->prepare("UPDATE agent_payouts SET status='rejected', paid_at=CURRENT_TIMESTAMP WHERE id=:id")
                ->execute([':id'=>$id]);
            $msg = 'Payout request rejected and balance refunded to agent.';
        }
    } catch (Exception $e) {
        $err = 'Error rejecting payout: ' . $e->getMessage();
    }
}

// Fetch all agent payouts safely
$payouts = [];
try {
    $payouts = $pdo->query("
        SELECT p.*, a.name AS agent_name, a.phone AS agent_phone, a.ref_code
        FROM agent_payouts p
        JOIN agents a ON p.agent_id = a.id
        ORDER BY CASE WHEN p.status = 'pending' THEN 0 ELSE 1 END, p.requested_at DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $payouts = [];
}

$pending_payouts_count = count(array_filter($payouts, fn($p) => ($p['status'] ?? '') === 'pending'));
$pending_total = array_sum(array_map(fn($p) => ($p['status'] === 'pending' ? (float)$p['amount'] : 0), $payouts));
$paid_total = array_sum(array_map(fn($p) => ($p['status'] === 'paid' ? (float)$p['amount'] : 0), $payouts));
$official_disburse_num = getPartnerSetting('official_payout_number', '+8801337320544');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0"/>
  <title>Payouts & Gateways Hub — Fast Site Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Oswald:wght@600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="/assets/css/admin.css?v=<?= time() ?>"/>
  <style>
    .kpi-grid-4 {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 1.2rem;
      margin-bottom: 2rem;
    }
    .kpi-card {
      background: rgba(16, 18, 28, 0.85);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 18px;
      padding: 1.4rem;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
      backdrop-filter: blur(14px);
    }
    .kpi-label {
      font-size: 0.75rem;
      font-weight: 800;
      color: var(--muted);
      text-transform: uppercase;
      letter-spacing: 0.8px;
      margin-bottom: 0.4rem;
    }
    .kpi-val {
      font-size: 2rem;
      font-weight: 800;
      font-family: 'Oswald', sans-serif;
      line-height: 1.1;
      color: #fff;
    }

    .tabs-nav {
      display: flex;
      gap: 0.8rem;
      margin-top: 1.2rem;
      flex-wrap: wrap;
    }
    .tab-btn {
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.1);
      color: #cbd5e1;
      padding: 0.6rem 1.4rem;
      border-radius: 12px;
      font-weight: 700;
      font-size: 0.88rem;
      cursor: pointer;
      transition: all 0.2s;
    }
    .tab-btn:hover {
      background: rgba(255, 255, 255, 0.1);
      color: #fff;
    }
    .tab-btn.active {
      background: linear-gradient(135deg, var(--gold) 0%, #f59e0b 100%);
      color: #000;
      font-weight: 800;
      border-color: var(--gold);
      box-shadow: 0 4px 15px rgba(252, 185, 0, 0.3);
    }
    .tab-content {
      display: none;
    }
    .tab-content.active {
      display: block;
    }

    .panel-card {
      background: rgba(16, 18, 28, 0.85);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 18px;
      padding: 1.6rem;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
      backdrop-filter: blur(14px);
      margin-bottom: 2rem;
    }

    .gateway-toggle-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
      gap: 1.2rem;
      margin-bottom: 1.8rem;
    }
    .gateway-card {
      background: rgba(255, 255, 255, 0.03);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 14px;
      padding: 1.2rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      transition: all 0.2s;
    }
    .gateway-card:hover {
      border-color: rgba(255, 255, 255, 0.2);
      background: rgba(255, 255, 255, 0.05);
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

    .table-container {
      overflow-x: auto;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      text-align: left;
      font-size: 0.88rem;
    }
    th {
      border-bottom: 1px solid rgba(255, 255, 255, 0.08);
      color: var(--muted);
      font-size: 0.72rem;
      text-transform: uppercase;
      letter-spacing: 0.8px;
      padding: 0.8rem 0.6rem;
    }
    td {
      border-bottom: 1px solid rgba(255, 255, 255, 0.04);
      padding: 0.9rem 0.6rem;
      color: #fff;
      vertical-align: middle;
    }
    tr:hover td {
      background: rgba(255, 255, 255, 0.02);
    }
    .badge-pending {
      background: rgba(245, 158, 11, 0.2);
      border: 1px solid #f59e0b;
      color: #f59e0b;
      padding: 3px 8px;
      border-radius: 6px;
      font-size: 0.72rem;
      font-weight: 800;
      text-transform: uppercase;
    }
    .badge-paid {
      background: rgba(16, 185, 129, 0.2);
      border: 1px solid #10b981;
      color: #10b981;
      padding: 3px 8px;
      border-radius: 6px;
      font-size: 0.72rem;
      font-weight: 800;
      text-transform: uppercase;
    }
    .badge-rejected {
      background: rgba(239, 68, 68, 0.2);
      border: 1px solid #ef4444;
      color: #ef4444;
      padding: 3px 8px;
      border-radius: 6px;
      font-size: 0.72rem;
      font-weight: 800;
      text-transform: uppercase;
    }
  </style>
</head>
<body>

<!-- Unified Master Top Navigation -->
<?php include __DIR__ . '/nav.php'; ?>

<div class="dashboard-container">

  <!-- Header Admin Hero -->
  <div class="admin-hero">
    <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:1rem;">
      <div>
        <h2 class="admin-hero-title">💳 MASTER PAYOUTS & GATEWAY HUB</h2>
        <p class="admin-hero-subtitle">Process Agent Disbursements, Customer Cashouts & Payment Gateway Settings</p>
      </div>
      <div style="display:flex; gap:0.6rem; flex-wrap:wrap;">
        <a href="coin_deposits.php" class="btn-sm" style="display:inline-flex; align-items:center; gap:0.4rem; padding: 0.55rem 1.2rem; background: rgba(16, 185, 129, 0.15); border:1px solid #10b981; color:#10b981; font-weight:700; text-decoration:none; border-radius:10px;">
          💰 User Deposits ➔
        </a>
        <a href="user_withdrawals.php" class="btn-sm" style="display:inline-flex; align-items:center; gap:0.4rem; padding: 0.55rem 1.2rem; background: rgba(239, 68, 68, 0.15); border:1px solid #ef4444; color:#ef4444; font-weight:700; text-decoration:none; border-radius:10px;">
          💸 User Cashouts ➔
        </a>
        <a href="wallet.php" class="btn-sm" style="display:inline-flex; align-items:center; gap:0.4rem; padding: 0.55rem 1.2rem; background: rgba(252, 185, 0, 0.15); border:1px solid #fcb900; color:#fcb900; font-weight:700; text-decoration:none; border-radius:10px;">
          💼 Master Wallet ➔
        </a>
      </div>
    </div>

    <!-- Tabs Navigation -->
    <div class="tabs-nav">
      <button class="tab-btn active" onclick="switchTab(event, 'tab-agent-payouts')">
        🤝 Agent Payouts Ledger (<?= count($payouts) ?>)
      </button>
      <button class="tab-btn" onclick="switchTab(event, 'tab-gateways')">
        ⚙️ Payment Gateways & Delivery Settings
      </button>
      <button class="tab-btn" onclick="switchTab(event, 'tab-overview')">
        📊 Gateway Portals Directory
      </button>
    </div>
  </div>

  <!-- Alerts -->
  <?php if ($msg): ?>
    <div style="background:rgba(16,185,129,0.15); border:1px solid #10b981; color:#10b981; padding:0.9rem 1.3rem; border-radius:12px; margin-bottom:1.5rem; font-weight:700; display:flex; align-items:center; gap:8px;">
      ✅ <?= htmlspecialchars($msg) ?>
    </div>
  <?php endif; ?>

  <?php if ($err): ?>
    <div style="background:rgba(239,68,68,0.15); border:1px solid #ef4444; color:#ef4444; padding:0.9rem 1.3rem; border-radius:12px; margin-bottom:1.5rem; font-weight:700; display:flex; align-items:center; gap:8px;">
      ⚠️ <?= htmlspecialchars($err) ?>
    </div>
  <?php endif; ?>

  <!-- KPI Overview Cards -->
  <div class="kpi-grid-4">
    <div class="kpi-card">
      <div class="kpi-label">⏳ Pending Agent Payouts</div>
      <div class="kpi-val" style="color:var(--gold);">৳<?= number_format($pending_total, 2) ?></div>
    </div>
    <div class="kpi-card">
      <div class="kpi-label">⚡ Pending Invoices</div>
      <div class="kpi-val" style="color:#f59e0b;"><?= number_format($pending_payouts_count) ?></div>
    </div>
    <div class="kpi-card">
      <div class="kpi-label">✅ Settled Agent Payouts</div>
      <div class="kpi-val" style="color:#10b981;">৳<?= number_format($paid_total, 2) ?></div>
    </div>
    <div class="kpi-card">
      <div class="kpi-label">📱 Official Payout Sender</div>
      <div class="kpi-val" style="color:#38bdf8; font-size:1.4rem; font-family:monospace;"><?= htmlspecialchars($official_disburse_num) ?></div>
    </div>
  </div>

  <!-- ── TAB 1: AGENT PAYOUTS LEDGER ── -->
  <div id="tab-agent-payouts" class="tab-content active">
    <div class="panel-card">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.2rem; flex-wrap:wrap; gap:0.8rem;">
        <h3 style="font-family:'Oswald',sans-serif; color:#fff; font-size:1.15rem; margin:0; text-transform:uppercase;">
          📋 Registered Affiliate Agent Payout Requests (<?= count($payouts) ?>)
        </h3>
        
        <?php if (!empty($payouts)): ?>
          <form method="POST" action="export_mass_payout.php" id="massPayoutForm" style="display:flex; gap:0.8rem; align-items:center; flex-wrap:wrap;">
            <label style="color:#cbd5e1; font-size:0.82rem; display:flex; align-items:center; gap:0.4rem; cursor:pointer;">
              <input type="checkbox" name="mark_paid" value="1"/> Auto Mark as Paid
            </label>
            <button type="submit" onclick="return confirm('Export selected payouts to bKash Mass CSV?');" style="background:linear-gradient(135deg, #10b981, #059669); color:#000; font-weight:800; border:none; padding:0.5rem 1rem; border-radius:8px; font-size:0.82rem; cursor:pointer;">
              📥 Export bKash CSV
            </button>
          </form>
        <?php endif; ?>
      </div>

      <?php if (empty($payouts)): ?>
        <div style="text-align:center; padding:3rem; color:#94a3b8;">
          <div style="font-size:3rem; margin-bottom:0.5rem;">📫</div>
          No agent payout requests in the queue.
        </div>
      <?php else: ?>
        <div class="table-container">
          <table>
            <thead>
              <tr>
                <th style="width:30px;"><input type="checkbox" id="selectAll" onclick="toggleSelectAll(this)"/></th>
                <th>ID</th>
                <th>Agent Profile</th>
                <th>Ref Code</th>
                <th style="text-align:right;">Payout Amount</th>
                <th>bKash / Nagad Account</th>
                <th style="text-align:center;">Status</th>
                <th>Requested Date</th>
                <th>Settled At</th>
                <th style="text-align:right;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($payouts as $p): ?>
                <?php 
                  $st = strtolower($p['status'] ?? 'pending');
                  $badgeClass = $st === 'paid' ? 'badge-paid' : ($st === 'rejected' ? 'badge-rejected' : 'badge-pending');
                ?>
                <tr>
                  <td>
                    <?php if ($st === 'pending'): ?>
                      <input type="checkbox" name="payout_ids[]" form="massPayoutForm" value="<?= $p['id'] ?>" class="payout-cb"/>
                    <?php endif; ?>
                  </td>
                  <td style="color:#94a3b8; font-family:monospace;">#<?= $p['id'] ?></td>
                  <td>
                    <div style="font-weight:700; color:#fff;"><?= htmlspecialchars($p['agent_name']) ?></div>
                    <div style="font-size:0.75rem; color:#94a3b8; font-family:monospace;"><?= htmlspecialchars($p['agent_phone']) ?></div>
                  </td>
                  <td>
                    <span style="font-family:monospace; background:rgba(252,185,0,0.12); color:var(--gold); border:1px solid rgba(252,185,0,0.3); padding:2px 8px; border-radius:6px; font-size:0.75rem; font-weight:700;">
                      <?= htmlspecialchars($p['ref_code']) ?>
                    </span>
                  </td>
                  <td style="text-align:right; font-family:'Oswald',sans-serif; font-size:1.15rem; font-weight:800; color:var(--gold);">
                    ৳<?= number_format((float)$p['amount'], 2) ?>
                  </td>
                  <td>
                    <span style="font-family:monospace; color:#10b981; font-weight:700; background:rgba(16,185,129,0.1); padding:3px 8px; border-radius:6px;">
                      📱 <?= htmlspecialchars($p['bkash_number']) ?>
                    </span>
                  </td>
                  <td style="text-align:center;">
                    <span class="<?= $badgeClass ?>"><?= strtoupper($st) ?></span>
                  </td>
                  <td style="font-size:0.75rem; color:#94a3b8;">
                    <?= date('d M Y, h:i A', strtotime($p['requested_at'])) ?>
                  </td>
                  <td style="font-size:0.75rem; color:#94a3b8;">
                    <?= !empty($p['paid_at']) ? date('d M Y', strtotime($p['paid_at'])) : '—' ?>
                  </td>
                  <td style="text-align:right; white-space:nowrap;">
                    <?php if ($st === 'pending'): ?>
                      <div style="display:inline-flex; gap:6px;">
                        <a href="payouts.php?pay=1&id=<?= $p['id'] ?>" onclick="return confirm('Confirm disbursement of ৳<?= number_format($p['amount'], 2) ?> to <?= htmlspecialchars($p['bkash_number']) ?>?');" style="background:rgba(16,185,129,0.2); border:1px solid #10b981; color:#10b981; padding:0.35rem 0.75rem; border-radius:6px; font-weight:700; font-size:0.75rem; text-decoration:none;">
                          ✓ Mark Paid
                        </a>
                        <a href="payouts.php?reject=<?= $p['id'] ?>" onclick="return confirm('Reject and refund ৳<?= number_format($p['amount'], 2) ?> to agent balance?');" style="background:rgba(239,68,68,0.2); border:1px solid #ef4444; color:#ef4444; padding:0.35rem 0.75rem; border-radius:6px; font-weight:700; font-size:0.75rem; text-decoration:none;">
                          ✕ Reject
                        </a>
                      </div>
                    <?php else: ?>
                      <div style="font-size:0.75rem; color:#94a3b8;">
                        <?= htmlspecialchars($p['admin_note'] ?: 'Settled') ?>
                      </div>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- ── TAB 2: PAYMENT GATEWAYS & DELIVERY SETTINGS ── -->
  <div id="tab-gateways" class="tab-content">
    <div class="panel-card" style="border-color: rgba(252, 185, 0, 0.3);">
      <h3 style="color:var(--gold); font-size:1.2rem; font-weight:800; margin-bottom:0.4rem; display:flex; align-items:center; gap:8px;">
        ⚙️ Payment Gateways & Store Delivery Settings
      </h3>
      <p style="color:var(--muted); font-size:0.85rem; margin-bottom:1.5rem;">
        Enable or disable customer checkout payment methods and configure platform-wide shipping charges.
      </p>

      <form method="POST">
        <input type="hidden" name="save_payment_settings" value="1"/>

        <!-- Gateway Toggle Cards -->
        <div class="gateway-toggle-grid">
          <div class="gateway-card">
            <div>
              <div style="font-weight:700; color:#fff; font-size:0.95rem; margin-bottom:2px;">📱 bKash Merchant Gateway</div>
              <div style="font-size:0.75rem; color:var(--muted);">Direct bKash online payment & QR</div>
            </div>
            <label class="switch">
              <input type="checkbox" name="payment_bkash_enabled" value="1" <?= getPartnerSetting('payment_bkash_enabled', '1') === '1' ? 'checked' : '' ?>/>
              <span class="slider"></span>
            </label>
          </div>

          <div class="gateway-card">
            <div>
              <div style="font-weight:700; color:#fff; font-size:0.95rem; margin-bottom:2px;">📱 Nagad Payment Gateway</div>
              <div style="font-size:0.75rem; color:var(--muted);">Direct Nagad payment checkout</div>
            </div>
            <label class="switch">
              <input type="checkbox" name="payment_nagad_enabled" value="1" <?= getPartnerSetting('payment_nagad_enabled', '1') === '1' ? 'checked' : '' ?>/>
              <span class="slider"></span>
            </label>
          </div>

          <div class="gateway-card">
            <div>
              <div style="font-weight:700; color:#fff; font-size:0.95rem; margin-bottom:2px;">💳 Visa / Mastercard Gateway</div>
              <div style="font-size:0.75rem; color:var(--muted);">Credit & Debit Card processing</div>
            </div>
            <label class="switch">
              <input type="checkbox" name="payment_card_enabled" value="1" <?= getPartnerSetting('payment_card_enabled', '1') === '1' ? 'checked' : '' ?>/>
              <span class="slider"></span>
            </label>
          </div>

          <div class="gateway-card">
            <div>
              <div style="font-weight:700; color:#fff; font-size:0.95rem; margin-bottom:2px;">📦 Cash on Delivery (COD)</div>
              <div style="font-size:0.75rem; color:var(--muted);">Hand-to-hand delivery payment</div>
            </div>
            <label class="switch">
              <input type="checkbox" name="payment_cod_enabled" value="1" <?= getPartnerSetting('payment_cod_enabled', '1') === '1' ? 'checked' : '' ?>/>
              <span class="slider"></span>
            </label>
          </div>
        </div>

        <!-- Delivery Charges & Payout Phone Config -->
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:1.2rem; margin-bottom:1.5rem;">
          <div>
            <label style="display:block; font-size:0.75rem; font-weight:800; color:var(--muted); text-transform:uppercase; margin-bottom:0.4rem;">
              Delivery Charge (Inside Dhaka) ৳
            </label>
            <input type="number" step="1" name="delivery_inside_dhaka" class="custom-input" value="<?= htmlspecialchars(getPartnerSetting('delivery_inside_dhaka', '60')) ?>" required />
          </div>

          <div>
            <label style="display:block; font-size:0.75rem; font-weight:800; color:var(--muted); text-transform:uppercase; margin-bottom:0.4rem;">
              Delivery Charge (Outside Dhaka) ৳
            </label>
            <input type="number" step="1" name="delivery_outside_dhaka" class="custom-input" value="<?= htmlspecialchars(getPartnerSetting('delivery_outside_dhaka', '120')) ?>" required />
          </div>

          <div>
            <label style="display:block; font-size:0.75rem; font-weight:800; color:var(--muted); text-transform:uppercase; margin-bottom:0.4rem;">
              Fast Site Official Disbursement Number
            </label>
            <input type="text" name="official_payout_number" class="custom-input" value="<?= htmlspecialchars($official_disburse_num) ?>" placeholder="+8801337320544" required />
          </div>
        </div>

        <button type="submit" style="background:linear-gradient(135deg, var(--gold) 0%, #f59e0b 100%); color:#000; font-weight:900; padding:0.8rem 1.8rem; border-radius:10px; border:none; cursor:pointer; font-size:0.95rem;">
          💾 Save Gateway & Shipping Settings
        </button>
      </form>
    </div>
  </div>

  <!-- ── TAB 3: OVERVIEW & OTHER FINANCIAL HUBS ── -->
  <div id="tab-overview" class="tab-content">
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:1.5rem;">
      <div class="panel-card" style="border-color: rgba(16,185,129,0.3);">
        <div style="font-size:2rem; margin-bottom:0.6rem;">💰</div>
        <h4 style="color:#fff; font-size:1.1rem; font-weight:800; margin-bottom:0.4rem;">Coin Deposit Approvals</h4>
        <p style="color:var(--muted); font-size:0.85rem; margin-bottom:1.2rem;">
          Review and approve manual bKash/Nagad point deposit invoices submitted by customers.
        </p>
        <a href="coin_deposits.php" style="display:inline-block; background:rgba(16,185,129,0.15); border:1px solid #10b981; color:#10b981; padding:0.6rem 1.2rem; border-radius:8px; font-weight:700; text-decoration:none; font-size:0.85rem;">
          Open Coin Deposits Hub ➔
        </a>
      </div>

      <div class="panel-card" style="border-color: rgba(239,68,68,0.3);">
        <div style="font-size:2rem; margin-bottom:0.6rem;">💸</div>
        <h4 style="color:#fff; font-size:1.1rem; font-weight:800; margin-bottom:0.4rem;">Customer Rewards Cashouts</h4>
        <p style="color:var(--muted); font-size:0.85rem; margin-bottom:1.2rem;">
          Process and disburse customer reward withdrawals to their personal bKash/Nagad accounts.
        </p>
        <a href="user_withdrawals.php" style="display:inline-block; background:rgba(239,68,68,0.15); border:1px solid #ef4444; color:#ef4444; padding:0.6rem 1.2rem; border-radius:8px; font-weight:700; text-decoration:none; font-size:0.85rem;">
          Open User Cashouts Hub ➔
        </a>
      </div>

      <div class="panel-card" style="border-color: rgba(252,185,0,0.3);">
        <div style="font-size:2rem; margin-bottom:0.6rem;">💼</div>
        <h4 style="color:#fff; font-size:1.1rem; font-weight:800; margin-bottom:0.4rem;">Master Wallet & Revenue</h4>
        <p style="color:var(--muted); font-size:0.85rem; margin-bottom:1.2rem;">
          Monitor global coin circulation, official store earnings, and gift coins directly to users.
        </p>
        <a href="wallet.php" style="display:inline-block; background:rgba(252,185,0,0.15); border:1px solid #fcb900; color:#fcb900; padding:0.6rem 1.2rem; border-radius:8px; font-weight:700; text-decoration:none; font-size:0.85rem;">
          Open Master Wallet ➔
        </a>
      </div>
    </div>
  </div>

</div>

<script>
function switchTab(evt, tabId) {
  document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
  document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
  const target = document.getElementById(tabId);
  if (target) target.classList.add('active');
  if (evt && evt.currentTarget) evt.currentTarget.classList.add('active');
}

function toggleSelectAll(source) {
  const checkboxes = document.querySelectorAll('.payout-cb');
  checkboxes.forEach(cb => cb.checked = source.checked);
}

// Auto select tab if hash is present
window.addEventListener('DOMContentLoaded', () => {
  const hash = window.location.hash.replace('#', '');
  if (hash === 'tab-gateways' || hash === 'tab-agent-payouts' || hash === 'tab-overview') {
    const btn = Array.from(document.querySelectorAll('.tab-btn')).find(b => b.getAttribute('onclick').includes(hash));
    if (btn) btn.click();
  }
});
</script>

</body>
</html>
