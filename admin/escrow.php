<?php
// =========================================================================
// admin/escrow.php — Layer 3 Escrow Vault & Dispute Override Center
// =========================================================================
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php'); exit;
}
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/escrow_engine.php';

escrow_ensure_tables($pdo);

$msg = ''; $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $order_id = $_POST['order_id'] ?? '';

    if ($action === 'force_release' && !empty($order_id)) {
        $res = escrow_admin_force_release($pdo, $order_id);
        if ($res['success']) $msg = $res['message']; else $err = $res['message'];
    } elseif ($action === 'refund_buyer' && !empty($order_id)) {
        $res = escrow_admin_refund_buyer($pdo, $order_id);
        if ($res['success']) $msg = $res['message']; else $err = $res['message'];
    }
}

// Fetch live escrow records
$escrow_records = [];
try {
    $stmt = $pdo->query("SELECT v.*, u1.name AS buyer_name, u2.name AS seller_name 
                         FROM escrow_vault v
                         LEFT JOIN users u1 ON v.buyer_id = u1.id
                         LEFT JOIN users u2 ON v.seller_id = u2.id
                         ORDER BY v.id DESC LIMIT 50");
    $escrow_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Calculate totals
$total_held = 0;
foreach ($escrow_records as $r) {
    if (in_array($r['status'], ['held', 'shipped', 'disputed'])) {
        $total_held += floatval($r['amount_bdt']);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"/>
  <title>Trust & Escrow Center — Fast Site</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="/assets/css/admin.css">
  <style>
    body { max-width: 100vw; overflow-x: hidden; }
    .escrow-container { padding: 20px; max-width: 1200px; margin: 0 auto; }
    
    .stats-grid {
        display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 25px;
    }
    .stat-card {
        background: rgba(16, 18, 28, 0.95); border: 1px solid rgba(252, 185, 0, 0.3);
        border-radius: 14px; padding: 20px; text-align: center;
        box-shadow: 0 4px 20px rgba(0,0,0,0.4);
    }
    .stat-val { font-size: 2rem; font-weight: 900; color: #10b981; }
    .stat-lbl { font-size: 0.85rem; color: #888; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; }
    
    .panel-glass {
        background: rgba(16, 18, 28, 0.95); border: 1px solid rgba(255,255,255,0.1);
        border-radius: 14px; padding: 25px; margin-bottom: 25px;
    }
    .panel-title { color: var(--gold, #fcb900); font-weight: 900; margin-top: 0; display: flex; align-items: center; gap: 10px; }
    
    .data-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    .data-table th, .data-table td { 
        padding: 12px 15px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.05); 
        color: #ddd; font-size: 0.88rem;
    }
    .data-table th { color: #888; font-weight: 700; text-transform: uppercase; font-size: 0.78rem; }
    
    .btn-action {
        background: linear-gradient(135deg, #fcb900 0%, #f59e0b 100%); border: none;
        color: #000; padding: 6px 14px; border-radius: 8px; font-weight: 800; cursor: pointer; transition: all 0.2s;
    }
    .btn-action:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(252,185,0,0.4); }
    .btn-danger { background: rgba(239, 68, 68, 0.2); color: #ef4444; border: 1px solid #ef4444; } 
    .btn-danger:hover { background: #ef4444; color: #fff; }
  </style>
</head>
<body>
<?php include 'nav.php'; ?>

<div class="escrow-container">
    <div style="margin-bottom: 25px;">
        <h1 style="color:#fff; font-weight:900; margin:0;">🛡️ Trust & Escrow Center</h1>
        <p style="color:#aaa; margin-top:5px;">3-Layer Escrow Vault & Dispute Override Management</p>
    </div>

    <?php if($msg): ?><div style="background:rgba(16,185,129,0.15); border:1px solid #10b981; color:#10b981; padding:0.8rem; border-radius:10px; margin-bottom:1rem; font-weight:700;">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <?php if($err): ?><div style="background:rgba(239,68,68,0.15); border:1px solid #ef4444; color:#ef4444; padding:0.8rem; border-radius:10px; margin-bottom:1rem; font-weight:700;">❌ <?= htmlspecialchars($err) ?></div><?php endif; ?>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-val"><?= number_format($total_held, 2) ?> BDT</div>
            <div class="stat-lbl">Active Funds in Vault</div>
        </div>
        <div class="stat-card">
            <div class="stat-val" style="color:var(--gold, #fcb900)"><?= count($escrow_records) ?></div>
            <div class="stat-lbl">Total Escrow Orders</div>
        </div>
    </div>

    <div class="panel-glass">
        <h3 class="panel-title">💰 Escrow Vault Ledger</h3>
        
        <div class="desktop-table-wrap">
            <table class="data-table">
                <tr><th>Order ID</th><th>Buyer</th><th>Seller</th><th>Amount</th><th>Status</th><th>Actions</th></tr>
                <?php if(empty($escrow_records)): ?>
                <tr><td colspan="6" style="text-align:center; color:#777;">No active escrow records found. Demo vault ready.</td></tr>
                <?php else: ?>
                <?php foreach($escrow_records as $r): ?>
                <tr>
                    <td><strong>#<?= htmlspecialchars($r['order_id']) ?></strong></td>
                    <td><?= htmlspecialchars($r['buyer_name'] ?? 'Buyer #' . $r['buyer_id']) ?></td>
                    <td><?= htmlspecialchars($r['seller_name'] ?? 'Seller #' . $r['seller_id']) ?></td>
                    <td style="font-weight:800; color:#10b981;"><?= number_format($r['amount_bdt'], 2) ?> BDT</td>
                    <td><span style="font-weight:800; text-transform:uppercase; font-size:0.75rem; padding:3px 8px; border-radius:6px; background:rgba(255,255,255,0.05);"><?= htmlspecialchars($r['status']) ?></span></td>
                    <td>
                        <?php if(in_array($r['status'], ['held', 'shipped', 'disputed'])): ?>
                        <form method="POST" style="display:inline-flex; gap:6px;">
                            <input type="hidden" name="order_id" value="<?= htmlspecialchars($r['order_id']) ?>"/>
                            <button type="submit" name="action" value="force_release" class="btn-action">Release to Seller</button>
                            <button type="submit" name="action" value="refund_buyer" class="btn-action btn-danger">Refund Buyer</button>
                        </form>
                        <?php else: ?>
                        <span style="color:#777; font-size:0.8rem;">Finalized</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>

</body>
</html>
