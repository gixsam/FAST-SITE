<?php
// =========================================================================
// partner/orders.php  –  Partner Orders Management Board
// =========================================================================
require_once 'nav.php';

$partner_id = $_SESSION['partner_id'];
$coin_name = getPartnerSetting('coin_name', 'Fast Points');

$err = $msg = '';

// Step Progress Tracker Function
function renderStepper($ord) {
    $status = $ord['status'] ?? '';
    $courier_tracking_id = $ord['courier_tracking_id'] ?? null;
    $delivered_at = $ord['delivered_at'] ?? null;

    $steps = [
        ['key' => 'pending', 'label' => 'Order Placed'],
        ['key' => 'escrow_held', 'label' => 'Payment Secured'],
        ['key' => 'shipped', 'label' => 'Preparing Order'],
        ['key' => 'delivered', 'label' => 'Delivered'],
        ['key' => 'completed', 'label' => 'Completed']
    ];
    
    $currentIdx = 0;
    if ($status === 'completed') {
        $currentIdx = 4;
    } elseif ($delivered_at !== null) {
        $currentIdx = 3;
    } elseif ($status === 'waiting_confirmation' || !empty($courier_tracking_id)) {
        $currentIdx = 2;
    } elseif (in_array($status, ['accepted', 'in_progress'])) {
        $currentIdx = 1;
    } elseif ($status === 'pending') {
        $currentIdx = 0;
    }

    if ($status === 'cancelled') {
        return '<div style="color:var(--red); font-size:0.8rem; font-weight:700; background:rgba(255,82,82,0.06); padding:0.6rem 1rem; border-radius:8px; border:1px solid rgba(255,82,82,0.15); display:inline-block; margin-top:0.5rem; text-transform: uppercase;">❌ THIS ORDER HAS BEEN CANCELLED</div>';
    }
    elseif ($status === 'disputed') {
        return '<div style="color:#e91e63; font-size:0.8rem; font-weight:700; background:rgba(233,30,99,0.06); padding:0.6rem 1rem; border-radius:8px; border:1px solid rgba(233,30,99,0.15); display:inline-block; margin-top:0.5rem; text-transform: uppercase;">⚠️ THIS ORDER IS IN DISPUTE</div>';
    }

    $html = '<div class="stepper-container" style="display:flex; justify-content:space-between; align-items:center; position:relative; margin:1.2rem 0; padding:0 0.5rem; width:100%; box-sizing:border-box;">';
    $html .= '<div style="position:absolute; top:12px; left:0; right:0; height:4px; background:rgba(255,255,255,0.06); z-index:1; border-radius:2px;"></div>';
    
    $pct = $currentIdx * 25;
    $html .= '<div style="position:absolute; top:12px; left:0; width:' . $pct . '%; height:4px; background:linear-gradient(90deg, var(--gold), var(--green)); z-index:2; border-radius:2px; transition: width 0.4s ease;"></div>';
    
    foreach ($steps as $idx => $step) {
        $isActive = $idx <= $currentIdx;
        $isCurrent = $idx === $currentIdx;
        
        $dotBg = $isActive ? ($isCurrent ? 'var(--gold)' : 'var(--green)') : '#1c1c2e';
        $dotBorder = $isActive ? 'none' : '2px solid rgba(255,255,255,0.12)';
        $labelColor = $isActive ? ($isCurrent ? 'var(--gold)' : '#fff') : 'var(--muted)';
        $fontWeight = $isCurrent ? '800' : '600';
        
        $html .= '<div style="display:flex; flex-direction:column; align-items:center; position:relative; z-index:3; width:60px;">';
        $html .= '<div style="width:24px; height:24px; border-radius:50%; background:' . $dotBg . '; border:' . $dotBorder . '; display:flex; align-items:center; justify-content:center; box-shadow:0 0 10px rgba(0,0,0,0.5);">';
        if ($idx < $currentIdx) {
            $html .= '<span style="color:#000; font-size:0.75rem; font-weight:bold;">✓</span>';
        } else if ($isCurrent) {
            $html .= '<span style="display:inline-block; width:6px; height:6px; background:#000; border-radius:50%;"></span>';
        }
        $html .= '</div>';
        $html .= '<span style="font-size:0.65rem; margin-top:0.4rem; color:' . $labelColor . '; font-weight:' . $fontWeight . '; text-align:center; white-space:nowrap; text-transform: uppercase; letter-spacing:0.02em;">' . $step['label'] . '</span>';
        $html .= '</div>';
    }
    
    $html .= '</div>';
    return $html;
}

// Handle Accept Order Action
if (isset($_POST['action']) && $_POST['action'] === 'accept') {
    $order_id = intval($_POST['order_id'] ?? 0);
    if ($order_id > 0) {
        $stmt = $pdo->prepare("UPDATE partner_orders SET status = 'accepted' WHERE id = :id AND partner_id = :partner_id AND status = 'pending'");
        $stmt->execute([':id' => $order_id, ':partner_id' => $partner_id]);
        $msg = 'Order accepted! You can now start processing it.';
    }
}

// Handle Start Processing Action
if (isset($_POST['action']) && $_POST['action'] === 'start_processing') {
    $order_id = intval($_POST['order_id'] ?? 0);
    if ($order_id > 0) {
        $stmt = $pdo->prepare("UPDATE partner_orders SET status = 'in_progress' WHERE id = :id AND partner_id = :partner_id AND status = 'accepted'");
        $stmt->execute([':id' => $order_id, ':partner_id' => $partner_id]);
        $msg = 'Order is now in processing status!';
    }
}

// Handle Upload Proof Action
if (isset($_POST['action']) && $_POST['action'] === 'upload_proof') {
    $order_id = intval($_POST['order_id'] ?? 0);
    
    if ($order_id > 0 && isset($_FILES['proof_file']) && $_FILES['proof_file']['error'] === UPLOAD_ERR_OK) {
        $stmt = $pdo->prepare("SELECT status FROM partner_orders WHERE id = :id AND partner_id = :partner_id LIMIT 1");
        $stmt->execute([':id' => $order_id, ':partner_id' => $partner_id]);
        $status = $stmt->fetchColumn();
        
        if ($status === 'accepted' || $status === 'in_progress' || $status === 'pending') {
            $upload_dir = '../uploads/proofs/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $filename = handleSecureUpload($_FILES['proof_file'], $upload_dir, ['jpg','jpeg','png','webp','pdf'], 'proof_' . $order_id);
            
            if ($filename) {
                $stmt_up = $pdo->prepare("UPDATE partner_orders 
                    SET partner_proof = :proof, status = 'waiting_confirmation' 
                    WHERE id = :id AND partner_id = :partner_id");
                $stmt_up->execute([
                    ':proof'      => $filename,
                    ':id'         => $order_id,
                    ':partner_id' => $partner_id
                ]);
                $msg = 'Proof uploaded successfully! Order status set to Waiting Confirmation.';
            } else {
                $err = 'Failed to save uploaded file. Invalid format or size limit exceeded.';
            }
        } else {
            $err = 'Invalid order status for uploading proof.';
        }
    } else {
        $err = 'Please select a valid image file.';
    }
}

// Handle Dispatch Order Action
if (isset($_POST['action']) && $_POST['action'] === 'dispatch_order') {
    $order_id = intval($_POST['order_id'] ?? 0);
    $courier_name = trim($_POST['courier_name'] ?? '');
    $courier_tracking_id = trim($_POST['courier_tracking_id'] ?? '');

    if ($order_id > 0 && $courier_name && $courier_tracking_id) {
        $stmt = $pdo->prepare("SELECT status FROM partner_orders WHERE id = :id AND partner_id = :partner_id LIMIT 1");
        $stmt->execute([':id' => $order_id, ':partner_id' => $partner_id]);
        $status = $stmt->fetchColumn();

        if ($status === 'accepted' || $status === 'in_progress') {
            $stmt_up = $pdo->prepare("UPDATE partner_orders 
                SET courier_name = :cname, courier_tracking_id = :ctrack, status = 'waiting_confirmation' 
                WHERE id = :id AND partner_id = :partner_id");
            $stmt_up->execute([
                ':cname'      => $courier_name,
                ':ctrack'     => $courier_tracking_id,
                ':id'         => $order_id,
                ':partner_id' => $partner_id
            ]);
            $msg = 'Order dispatched successfully via ' . htmlspecialchars($courier_name) . '!';
        } else {
            $err = 'Invalid order status for dispatch.';
        }
    } else {
        $err = 'Please provide courier details and tracking code.';
    }
}

// Filters
$filter = $_GET['status'] ?? 'active';

$where_clause = "o.partner_id = :partner_id";
if ($filter === 'pending') {
    $where_clause .= " AND o.status = 'pending'";
} elseif ($filter === 'active') {
    $where_clause .= " AND o.status IN ('pending', 'accepted', 'in_progress', 'waiting_confirmation')";
} elseif ($filter === 'waiting') {
    $where_clause .= " AND o.status = 'waiting_confirmation'";
} elseif ($filter === 'completed') {
    $where_clause .= " AND o.status = 'completed'";
} elseif ($filter === 'disputed') {
    $where_clause .= " AND o.status = 'disputed'";
} elseif ($filter === 'cancelled') {
    $where_clause .= " AND o.status = 'cancelled'";
}

$stmt_orders = $pdo->prepare("SELECT o.*, p.title AS product_title, p.price AS product_price, u.name AS customer_name, u.phone AS customer_phone 
    FROM partner_orders o 
    JOIN partner_products p ON o.product_id = p.id 
    JOIN users u ON o.customer_id = u.id 
    WHERE $where_clause 
    ORDER BY o.created_at DESC");
$stmt_orders->execute([':partner_id' => $partner_id]);
$orders = $stmt_orders->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <style>
    .tabs-bar {
      display: flex;
      gap: 0.5rem;
      border-bottom: 1px solid rgba(255, 255, 255, 0.05);
      margin-bottom: 2rem;
      overflow-x: auto;
      padding-bottom: 0.5rem;
    }

    .tab-link {
      color: var(--muted);
      text-decoration: none;
      font-size: 0.82rem;
      font-weight: 700;
      padding: 0.6rem 1.2rem;
      border-radius: 8px;
      border: 1px solid transparent;
      transition: var(--transition);
      white-space: nowrap;
    }

    .tab-link:hover {
      background: rgba(255, 255, 255, 0.02);
      color: #fff;
    }

    .tab-link.active {
      background: var(--brand-glow);
      border-color: rgba(252, 185, 0, 0.2);
      color: var(--brand);
    }

    .order-card {
      background: rgba(20, 20, 31, 0.4);
      border: 1px solid rgba(255, 255, 255, 0.05);
      border-radius: 16px;
      padding: 1.5rem;
      margin-bottom: 1.5rem;
      transition: var(--transition);
    }

    .order-card:hover {
      border-color: rgba(252, 185, 0, 0.2);
    }

    .order-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 1px solid rgba(255, 255, 255, 0.05);
      padding-bottom: 0.8rem;
      margin-bottom: 1rem;
    }

    .order-id {
      font-size: 1.05rem;
      font-weight: 800;
      color: #fff;
    }

    .order-date {
      font-size: 0.75rem;
      color: var(--muted);
    }

    .order-details-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1.2rem;
      margin-bottom: 1.2rem;
    }

    .detail-item {
      display: flex;
      flex-direction: column;
      gap: 0.25rem;
    }

    .detail-label {
      font-size: 0.68rem;
      font-weight: 700;
      color: var(--muted);
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }

    .detail-value {
      font-size: 0.9rem;
      color: #fff;
      font-weight: 500;
    }

    .order-actions {
      border-top: 1px solid rgba(255, 255, 255, 0.03);
      padding-top: 1rem;
      display: flex;
      flex-wrap: wrap;
      gap: 1rem;
      align-items: center;
    }

    .btn-action {
      background: linear-gradient(135deg, var(--brand), #ff9100);
      color: #000;
      font-weight: 700;
      border: none;
      padding: 0.6rem 1.2rem;
      border-radius: 8px;
      font-size: 0.85rem;
      cursor: pointer;
      transition: var(--transition);
    }

    .btn-action:hover {
      box-shadow: 0 4px 12px rgba(252, 185, 0, 0.3);
    }

    .proof-input-container {
      display: flex;
      align-items: center;
      gap: 0.8rem;
      flex-wrap: wrap;
    }

    .err {
      background: rgba(255, 82, 82, 0.08);
      color: var(--red);
      border: 1px solid rgba(255, 82, 82, 0.2);
      padding: 0.7rem;
      border-radius: 10px;
      font-size: 0.8rem;
      margin-bottom: 1.2rem;
    }

    .success {
      background: rgba(0, 230, 118, 0.08);
      color: var(--green);
      border: 1px solid rgba(0, 230, 118, 0.2);
      padding: 0.7rem;
      border-radius: 10px;
      font-size: 0.8rem;
      margin-bottom: 1.2rem;
    }

    /* ── Mobile APK Responsive Styles (< 768px) ── */
    @media (max-width: 768px) {
      .content-wrapper {
        padding: 1rem 0.8rem !important;
        max-width: 100vw !important;
        overflow-x: hidden !important;
        box-sizing: border-box !important;
      }
      .tabs-bar {
        width: 100% !important;
        max-width: 100% !important;
        overflow-x: auto !important;
        -webkit-overflow-scrolling: touch !important;
        flex-wrap: nowrap !important;
        padding-bottom: 0.4rem !important;
        margin-bottom: 1.2rem !important;
        gap: 0.4rem !important;
        scrollbar-width: none;
      }
      .tabs-bar::-webkit-scrollbar {
        display: none;
      }
      .tab-link {
        padding: 0.5rem 0.85rem !important;
        font-size: 0.78rem !important;
        flex-shrink: 0 !important;
      }
      .order-card {
        padding: 1.1rem 0.9rem !important;
        border-radius: 14px !important;
        box-sizing: border-box !important;
        width: 100% !important;
        max-width: 100% !important;
      }
      .order-details-grid {
        grid-template-columns: 1fr 1fr !important;
        gap: 0.75rem !important;
      }
      .order-actions {
        flex-direction: column !important;
        align-items: stretch !important;
      }
      .btn-action {
        width: 100% !important;
        text-align: center !important;
        justify-content: center !important;
      }
    }
    @media (max-width: 420px) {
      .order-details-grid {
        grid-template-columns: 1fr !important;
      }
    }
  </style>
</head>
<body>

<div class="content-wrapper">
  <h1 style="font-size: 1.6rem; font-weight: 800; margin-bottom: 1.5rem;">Customer Orders</h1>

  <?php if($err): ?><div class="err">⚠️ <?= htmlspecialchars($err) ?></div><?php endif; ?>
  <?php if($msg): ?><div class="success">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>

  <div class="tabs-bar">
    <a href="orders.php?status=active" class="tab-link <?= $filter === 'active' ? 'active' : '' ?>">Active Orders</a>
    <a href="orders.php?status=pending" class="tab-link <?= $filter === 'pending' ? 'active' : '' ?>">New Orders (Paid)</a>
    <a href="orders.php?status=waiting" class="tab-link <?= $filter === 'waiting' ? 'active' : '' ?>">Delivered (Awaiting Buyer)</a>
    <a href="orders.php?status=completed" class="tab-link <?= $filter === 'completed' ? 'active' : '' ?>">Completed (Funds Released)</a>
    <a href="orders.php?status=disputed" class="tab-link <?= $filter === 'disputed' ? 'active' : '' ?>">Disputed</a>
    <a href="orders.php?status=cancelled" class="tab-link <?= $filter === 'cancelled' ? 'active' : '' ?>">Cancelled</a>
  </div>

  <?php if(empty($orders)): ?>
    <div style="text-align: center; padding: 4rem 2rem; background: rgba(255,255,255,0.01); border: 1px dashed rgba(255, 255, 255, 0.05); border-radius: 16px;">
      <p style="font-size: 1rem; color:var(--muted);">No orders found in this category.</p>
    </div>
  <?php else: ?>
    <?php foreach($orders as $ord): ?>
      <div class="order-card">
        <div class="order-header">
          <div class="order-id">Order #<?= htmlspecialchars($ord['id']) ?></div>
          <div class="order-date"><?= date('d M Y, h:i A', strtotime($ord['created_at'])) ?></div>
        </div>

        <div class="order-details-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
          <div class="detail-item">
            <span class="detail-label">Product Name</span>
            <span class="detail-value" style="font-size:1rem; font-weight:800; color:#fff;"><?= htmlspecialchars($ord['product_title']) ?></span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Customer Contact</span>
            <span class="detail-value">
              👤 <strong><?= htmlspecialchars($ord['customer_name']) ?></strong><br>
              📞 <?= htmlspecialchars($ord['customer_phone']) ?>
            </span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Order Price</span>
            <span class="detail-value" style="color:var(--green); font-weight:800;">৳<?= number_format($ord['total_coins'], 2) ?> BDT</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Payment Method</span>
            <span class="detail-value">
              <strong style="color:#fff;"><?= strtoupper(htmlspecialchars($ord['payment_method'])) ?></strong>
              <span class="status-badge status-<?= htmlspecialchars($ord['payment_status']) ?>" style="font-size:0.65rem; padding:1px 5px; margin-left:5px;">
                <?= str_replace('_', ' ', htmlspecialchars($ord['payment_status'])) ?>
              </span>
            </span>
          </div>

          <?php if ($ord['sender_number'] || $ord['transaction_id'] || $ord['gateway_ref']): ?>
            <div class="detail-item" style="grid-column: span 2; background: rgba(0,0,0,0.15); padding: 0.6rem 0.8rem; border-radius: 8px; border: 1px solid rgba(255,255,255,0.03);">
              <span class="detail-label">Transaction Info</span>
              <span class="detail-value" style="font-size:0.8rem; color:var(--muted); line-height:1.4;">
                <?php if ($ord['sender_number']): ?>Sender Number: <strong style="color:#e8e8f0;"><?= htmlspecialchars($ord['sender_number']) ?></strong> &nbsp;|&nbsp; <?php endif; ?>
                <?php if ($ord['transaction_id']): ?>Transaction ID: <strong style="color:#e8e8f0;"><?= htmlspecialchars($ord['transaction_id']) ?></strong> &nbsp;|&nbsp; <?php endif; ?>
                <?php if ($ord['gateway_ref']): ?>Ref: <strong style="color:#e8e8f0;"><?= htmlspecialchars($ord['gateway_ref']) ?></strong><?php endif; ?>
              </span>
            </div>
          <?php endif; ?>

          <div class="detail-item" style="grid-column: span 2; background: rgba(0,0,0,0.15); padding: 0.6rem 0.8rem; border-radius: 8px; border: 1px solid rgba(255,255,255,0.03);">
            <span class="detail-label">Shipping Details (Location: <?= ucwords(str_replace('_', ' ', htmlspecialchars($ord['delivery_location'] ?? 'inside_dhaka'))) ?>)</span>
            <span class="detail-value" style="font-size:0.82rem; color:var(--muted); line-height:1.45;">
              Delivery Fee: <strong>৳<?= number_format($ord['delivery_charge'] ?? 0, 2) ?></strong><br>
              <?= nl2br(htmlspecialchars($ord['shipping_address'] ?? '')) ?>
            </span>
          </div>

          <?php if (!empty($ord['customer_submission']) || !empty($ord['submission_files'])): ?>
            <div class="detail-item" style="grid-column: span 2; background: rgba(0, 230, 118, 0.05); border: 1px solid rgba(0, 230, 118, 0.2); padding: 0.8rem 1rem; border-radius: 10px;">
              <span class="detail-label" style="color: var(--teal, #00e676); display: flex; align-items: center; gap: 6px;">
                <span>📑</span> Customer Submitted Information & Documents
              </span>
              <?php if (!empty($ord['customer_submission'])): ?>
                <div style="font-size: 0.85rem; color: #fff; line-height: 1.45; margin-top: 0.3rem; background: rgba(0,0,0,0.3); padding: 0.6rem 0.8rem; border-radius: 8px;">
                  <?= nl2br(htmlspecialchars($ord['customer_submission'])) ?>
                </div>
              <?php endif; ?>
              <?php 
                if (!empty($ord['submission_files'])) {
                  $subFiles = json_decode($ord['submission_files'], true);
                  if (is_array($subFiles) && count($subFiles) > 0) {
                    echo '<div style="display:flex; gap:0.5rem; flex-wrap:wrap; margin-top:0.6rem;">';
                    foreach ($subFiles as $fIdx => $sFile) {
                      $fName = basename($sFile);
                      echo '<a href="/' . htmlspecialchars(ltrim($sFile, '/')) . '" target="_blank" style="display:inline-flex; align-items:center; gap:5px; background:rgba(0,230,118,0.15); border:1px solid rgba(0,230,118,0.3); color:#00e676; padding:0.4rem 0.8rem; border-radius:6px; font-size:0.78rem; text-decoration:none; font-weight:700;">';
                      echo '📎 View Document ' . ($fIdx + 1) . ' (' . htmlspecialchars($fName) . ') ↗';
                      echo '</a>';
                    }
                    echo '</div>';
                  }
                }
              ?>
            </div>
          <?php endif; ?>

          <?php if (!empty($ord['courier_tracking_id'])): ?>
            <div class="detail-item" style="grid-column: span 2; background: rgba(255,145,0,0.1); padding: 0.6rem 0.8rem; border-radius: 8px; border: 1px solid rgba(255,145,0,0.2);">
              <span class="detail-label" style="color:var(--gold);">Courier Tracking Info</span>
              <span class="detail-value" style="font-size:0.85rem; color:#fff;">
                Courier: <strong><?= htmlspecialchars($ord['courier_name']) ?></strong> &nbsp;•&nbsp; 
                Tracking Code: <strong><?= htmlspecialchars($ord['courier_tracking_id']) ?></strong>
              </span>
            </div>
          <?php endif; ?>

          <div class="detail-item" style="grid-column: span 2; padding-top: 0.5rem;">
            <span class="detail-label">Delivery Progress Tracker</span>
            <?= renderStepper($ord) ?>
          </div>
        </div>

        <div class="order-actions">
          <?php if ($ord['status'] === 'pending'): ?>
            <!-- Accept Action -->
            <form method="POST" style="display:inline;">
              <input type="hidden" name="action" value="accept"/>
              <input type="hidden" name="order_id" value="<?= $ord['id'] ?>"/>
              <button type="submit" class="btn-action">Accept Order</button>
            </form>
          <?php endif; ?>

          <?php if ($ord['status'] === 'accepted'): ?>
            <!-- Start Processing Action -->
            <form method="POST" style="display:inline;">
              <input type="hidden" name="action" value="start_processing"/>
              <input type="hidden" name="order_id" value="<?= $ord['id'] ?>"/>
              <button type="submit" class="btn-action" style="background: linear-gradient(135deg, var(--gold), #ffda6a);">⚡ Start Processing</button>
            </form>
          <?php endif; ?>

          <?php if ($ord['status'] === 'accepted' || $ord['status'] === 'in_progress'): ?>
            <!-- Dispatch Order Action -->
            <button type="button" class="btn-action" style="background: linear-gradient(135deg, #00e676, #00c853); color: #000;" onclick="document.getElementById('dispatch-form-<?= $ord['id'] ?>').style.display = 'block';">📦 Dispatch Order</button>
            
            <div id="dispatch-form-<?= $ord['id'] ?>" style="display:none; width:100%; margin-top:1rem; background:rgba(0,0,0,0.2); padding:1rem; border-radius:10px; border:1px solid rgba(255,255,255,0.05);">
              <span class="detail-label" style="color:var(--green); margin-bottom:0.8rem; display:block;">Dispatch via Courier</span>
              <form method="POST" style="display:flex; flex-direction:column; gap:0.8rem;">
                <input type="hidden" name="action" value="dispatch_order"/>
                <input type="hidden" name="order_id" value="<?= $ord['id'] ?>"/>
                
                <div>
                  <label class="detail-label">Courier Name *</label>
                  <select name="courier_name" required style="width:100%; padding:0.6rem; border-radius:6px; background:rgba(255,255,255,0.05); color:#fff; border:1px solid rgba(255,255,255,0.1); margin-top:0.3rem;">
                    <option value="" disabled selected>Select Courier...</option>
                    <option value="Pathao">Pathao</option>
                    <option value="Steadfast">Steadfast</option>
                    <option value="Paperfly">Paperfly</option>
                    <option value="RedX">RedX</option>
                    <option value="Other Courier">Other Courier</option>
                  </select>
                </div>
                
                <div>
                  <label class="detail-label">Tracking ID / Consignment ID *</label>
                  <input type="text" name="courier_tracking_id" required placeholder="Enter tracking code" style="width:100%; padding:0.6rem; border-radius:6px; background:rgba(255,255,255,0.05); color:#fff; border:1px solid rgba(255,255,255,0.1); margin-top:0.3rem;" />
                </div>
                
                <button type="submit" class="btn-action" style="align-self:flex-start;">Confirm Dispatch</button>
              </form>
            </div>

            <!-- Upload Proof Action -->
            <form method="POST" enctype="multipart/form-data" class="proof-input-container">
              <input type="hidden" name="action" value="upload_proof"/>
              <input type="hidden" name="order_id" value="<?= $ord['id'] ?>"/>
              
              <div style="display:flex; flex-direction:column; gap:0.25rem;">
                <span class="detail-label">Upload proof of delivery (JPEG/PNG/PDF) *</span>
                <input type="file" name="proof_file" accept=".jpg,.jpeg,.png,.webp,.pdf" required style="background:none; border:none; font-size:0.8rem; color:var(--text); padding:0;"/>
                <div style="font-size: 0.78rem; color: #fcb900; background: rgba(252, 185, 0, 0.08); border: 1px solid rgba(252, 185, 0, 0.22); border-radius: 6px; padding: 0.5rem 0.8rem; margin-top: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                  <span>💡</span>
                  <span>Recommended: Clear delivery receipt photo, dispatch slip, or invoice (max 2MB).</span>
                </div>
              </div>
              <button type="submit" class="btn-action">Submit Proof & Ship</button>
            </form>
          <?php endif; ?>

          <?php if ($ord['partner_proof']): ?>
            <div style="font-size:0.8rem;">
              <span class="detail-label">Uploaded Proof:</span>
              <a href="/uploads/proofs/<?= htmlspecialchars($ord['partner_proof']) ?>" target="_blank" style="color:var(--brand); text-decoration:none; font-weight:700; margin-left:0.5rem;">View File ↗</a>
            </div>
          <?php endif; ?>
          
          <?php if ($ord['status'] === 'completed' && $ord['customer_confirmed_at']): ?>
            <div style="font-size:0.75rem; color:var(--green); font-weight:600;">
              ✓ Released by customer on <?= date('d M Y, h:i A', strtotime($ord['customer_confirmed_at'])) ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/cropper_modal.php'; ?>
</body>
</html>

