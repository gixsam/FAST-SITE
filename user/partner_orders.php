<?php
// =========================================================================
// customer/partner_orders.php  â€“  Customer Shop Orders Control Panel
// =========================================================================
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /user/login.php');
    exit;
}
require_once __DIR__ . '/../config.php';

$user_id = $_SESSION['user_id'];
$coin_name = getPartnerSetting('coin_name', 'Fast Points');

$err = $msg = '';

// Step Progress Tracker Function
function renderStepper($ord) {
    $status = $ord['status'] ?? '';
    $courier_tracking_id = $ord['courier_tracking_id'] ?? null;
    $delivered_at = $ord['delivered_at'] ?? null;

    $steps = [
        ['key' => 'pending', 'label' => 'Order Placed'],
        ['key' => 'escrow_held', 'label' => 'Escrow Held'],
        ['key' => 'shipped', 'label' => 'Shipped / In Transit'],
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

// Handle Release Payment (Confirm Completion)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'release') {
    $order_id = intval($_POST['order_id'] ?? 0);
    
    if ($order_id > 0) {
        try {
            $pdo->beginTransaction();

            // Verify order belongs to customer and is waiting_confirmation (or accepted)
            $stmt = $pdo->prepare("SELECT * FROM partner_orders WHERE id = :id AND customer_id = :user_id LIMIT 1");
            $stmt->execute([':id' => $order_id, ':user_id' => $user_id]);
            $order = $stmt->fetch();

            if ($order && in_array($order['status'], ['accepted', 'in_progress', 'waiting_confirmation'])) {
                // Determine payment status to set
                $pay_status_val = $order['payment_status'];
                if ($order['payment_method'] === 'cod') {
                    $pay_status_val = 'completed';
                }

                // 1. Update order status to completed
                $pdo->prepare("UPDATE partner_orders 
                    SET status = 'completed', payment_status = :pay_status, customer_confirmed_at = :now 
                    WHERE id = :id")
                    ->execute([
                        ':pay_status' => $pay_status_val,
                        ':now'        => date('Y-m-d H:i:s'),
                        ':id'         => $order_id
                    ]);

                if ($order['payment_method'] === 'cod') {
                    // COD payment collected directly. Partner withdrawable coins balance remains neutral.
                    $pdo->prepare("UPDATE partners 
                        SET total_orders = total_orders + 1 
                        WHERE id = :partner_id")
                        ->execute([
                            ':partner_id' => $order['partner_id']
                        ]);
                    updatePartnerSellerLevel($order['partner_id'], $pdo);
                } else {
                    // Card, bKash, Nagad (or coins) - points are released to the unified user wallet (coins_balance).
                    // First, get the user_id associated with this partner
                    $stmt_u = $pdo->prepare("SELECT u.id FROM users u JOIN partners p ON u.email = p.email WHERE p.id = :partner_id LIMIT 1");
                    $stmt_u->execute([':partner_id' => $order['partner_id']]);
                    $partner_user_id = $stmt_u->fetchColumn();

                    if ($partner_user_id) {
                        $pdo->prepare("UPDATE users SET coins_balance = coins_balance + :amount WHERE id = :uid")
                            ->execute([':amount' => $order['total_coins'], ':uid' => $partner_user_id]);
                    }

                    $pdo->prepare("UPDATE partners SET total_orders = total_orders + 1 WHERE id = :partner_id")
                        ->execute([':partner_id' => $order['partner_id']]);
                    updatePartnerSellerLevel($order['partner_id'], $pdo);

                    // 3. Log coin transaction (release)
                    if ($partner_user_id) {
                        $pdo->prepare("INSERT INTO coin_transactions 
                            (user_id, type, amount, reference, status) 
                            VALUES (:user_id, 'deposit', :amount, :ref, 'completed')")
                            ->execute([
                                ':user_id' => $partner_user_id,
                                ':amount'  => $order['total_coins'],
                                ':ref'     => 'Earnings from Order #' . $order_id
                            ]);
                    }
                }

                $pdo->commit();
                $msg = 'Order completion confirmed successfully! You can now rate this service.';
            } else {
                $pdo->rollBack();
                $err = 'Order not eligible for release.';
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $err = 'Database Error: ' . $e->getMessage();
        }
    }
}

// Handle Confirm Delivery Received
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'confirm_delivery') {
    $order_id = intval($_POST['order_id'] ?? 0);
    
    if ($order_id > 0) {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("SELECT * FROM partner_orders WHERE id = :id AND customer_id = :user_id LIMIT 1");
            $stmt->execute([':id' => $order_id, ':user_id' => $user_id]);
            $order = $stmt->fetch();

            if ($order && $order['status'] === 'waiting_confirmation' && empty($order['delivered_at'])) {
                $pdo->prepare("UPDATE partner_orders 
                    SET delivered_at = CURRENT_TIMESTAMP, 
                        auto_release_deadline = DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 48 HOUR) 
                    WHERE id = :id")
                    ->execute([':id' => $order_id]);
                $pdo->commit();
                $msg = 'Delivery confirmed! The 48-hour inspection window has started.';
            } else {
                $pdo->rollBack();
                $err = 'Order not eligible for delivery confirmation.';
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $err = 'Database Error: ' . $e->getMessage();
        }
    }
}

// Handle Raise Dispute
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'dispute') {
    $order_id = intval($_POST['order_id'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');
    
    if ($order_id > 0 && $reason) {
        try {
            $pdo->beginTransaction();

            // Verify order belongs to customer and is not completed/cancelled
            $stmt = $pdo->prepare("SELECT * FROM partner_orders WHERE id = :id AND customer_id = :user_id LIMIT 1");
            $stmt->execute([':id' => $order_id, ':user_id' => $user_id]);
            $order = $stmt->fetch();

            if ($order && in_array($order['status'], ['pending', 'accepted', 'in_progress', 'waiting_confirmation'])) {
                // File Upload for evidence
                $evidence_file = null;
                if (isset($_FILES['evidence']) && $_FILES['evidence']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = '../uploads/evidence/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    $evidence_file = handleSecureUpload($_FILES['evidence'], $upload_dir, ['jpg','jpeg','png','webp','pdf'], 'ev_cust_' . $order_id);
                }

                // 1. Create Dispute Record
                $stmt_disp = $pdo->prepare("INSERT INTO partner_disputes 
                    (order_id, raised_by, reason, evidence_customer, admin_decision) 
                    VALUES (:order_id, 'customer', :reason, :evidence, 'pending')");
                $stmt_disp->execute([
                    ':order_id' => $order_id,
                    ':reason'   => $reason,
                    ':evidence' => $evidence_file
                ]);

                // 2. Update order status to disputed
                $pdo->prepare("UPDATE partner_orders SET status = 'disputed' WHERE id = :id")
                    ->execute([':id' => $order_id]);

                $pdo->commit();
                $msg = 'Dispute raised successfully. Admin will review and decide.';
            } else {
                $pdo->rollBack();
                $err = 'Order not eligible for dispute.';
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $err = 'Database Error: ' . $e->getMessage();
        }
    } else {
        $err = 'Please enter a valid reason for the dispute.';
    }
}

// Handle Rating & Review
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'rate') {
    $order_id = intval($_POST['order_id'] ?? 0);
    $rating = intval($_POST['rating'] ?? 0);
    $review = trim($_POST['review'] ?? '');

    if ($order_id > 0 && $rating >= 1 && $rating <= 5) {
        try {
            // Verify order belongs to customer and is completed
            $stmt = $pdo->prepare("SELECT * FROM partner_orders WHERE id = :id AND customer_id = :user_id AND status = 'completed' LIMIT 1");
            $stmt->execute([':id' => $order_id, ':user_id' => $user_id]);
            $order = $stmt->fetch();

            if ($order) {
                // Check if already rated
                $chk_rate = $pdo->prepare("SELECT id FROM partner_ratings WHERE order_id = :order_id LIMIT 1");
                $chk_rate->execute([':order_id' => $order_id]);
                
                if ($chk_rate->fetch()) {
                    $err = 'You have already rated this order.';
                } else {
                    $pdo->beginTransaction();

                    // 1. Insert rating
                    $stmt_ins = $pdo->prepare("INSERT INTO partner_ratings 
                        (order_id, customer_id, partner_id, rating, review) 
                        VALUES (:order_id, :customer_id, :partner_id, :rating, :review)");
                    $stmt_ins->execute([
                        ':order_id'    => $order_id,
                        ':customer_id' => $user_id,
                        ':partner_id'  => $order['partner_id'],
                        ':rating'      => $rating,
                        ':review'      => $review ?: null
                    ]);
                    
                    // GAMIFIED CUSTOMER LOYALTY: Active Reviewer Mission (Goal: 3, Reward: 100)
                    if (function_exists('updateUserMissionProgress')) {
                        updateUserMissionProgress($pdo, $user_id, 'active_reviewer', 1, 3, 100);
                    }

                    // 2. Update partner rating average
                    $stmt_avg = $pdo->prepare("SELECT AVG(rating) FROM partner_ratings WHERE partner_id = :partner_id");
                    $stmt_avg->execute([':partner_id' => $order['partner_id']]);
                    $avg_rating = floatval($stmt_avg->fetchColumn());

                    $stmt_up = $pdo->prepare("UPDATE partners SET rating = :rating WHERE id = :partner_id");
                    $stmt_up->execute([
                        ':rating'     => $avg_rating,
                        ':partner_id' => $order['partner_id']
                    ]);

                    $pdo->commit();
                    $msg = 'Thank you for your rating & feedback!';
                }
            } else {
                $err = 'Order not eligible for rating.';
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $err = 'Database Error: ' . $e->getMessage();
        }
    }
}

// Fetch Customer Orders
$stmt_orders = $pdo->prepare("SELECT o.*, p.title AS product_title, p.price AS product_price, pt.business_name AS partner_name,
    (SELECT id FROM partner_ratings WHERE order_id = o.id LIMIT 1) AS rating_id 
    FROM partner_orders o 
    JOIN partner_products p ON o.product_id = p.id 
    JOIN partners pt ON o.partner_id = pt.id 
    WHERE o.customer_id = :user_id 
    ORDER BY o.created_at DESC");
$stmt_orders->execute([':user_id' => $user_id]);
$orders = $stmt_orders->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>My Shop Orders â€” Fast Site</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Oswald:wght@600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="/assets/css/user.css">
</head>
<body>

<!-- Top Navbar -->
<?php include __DIR__ . '/../includes/user_sidebar.php'; ?>
  <div class="nav-links">
    <a href="../user/dashboard.php" class="nav-link">Main Dashboard</a>
    <a href="wallet.php" class="nav-link">My Wallet</a>
    <a href="deposit.php" class="nav-link">Buy Points</a>
    <a href="partner_orders.php" class="nav-link active">My Shop Orders</a>
  </div>
  <div style="font-size:0.85rem; color:#fff; font-weight:600;">
    Hi, <?= htmlspecialchars($_SESSION['user_id'] ? $pdo->query("SELECT name FROM users WHERE id = " . intval($_SESSION['user_id']))->fetchColumn() : 'User') ?>
  </div>
</nav>

<div class="content-wrapper">
  <h1 style="font-size: 1.6rem; font-weight: 800; margin-bottom: 1.5rem;">My Shop Orders</h1>

  <?php if($err): ?><div class="err">⚠️ï¸ <?= htmlspecialchars($err) ?></div><?php endif; ?>
  <?php if($msg): ?><div class="success">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>

  <?php if(empty($orders)): ?>
    <div style="text-align: center; padding: 4rem 2rem; background: rgba(255,255,255,0.01); border: 1px dashed rgba(255, 255, 255, 0.05); border-radius: 16px;">
      <p style="font-size: 1rem; color:var(--muted); margin-bottom: 1rem;">You have not placed any store orders yet.</p>
      <a href="../index.php" style="color:var(--brand); text-decoration:none; font-weight:700;">Explore Services & Shop âž”</a>
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
            <span class="detail-label">Product / Service</span>
            <span class="detail-value" style="font-size:1rem; font-weight:800; color:#fff;"><?= htmlspecialchars($ord['product_title']) ?></span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Vendor Shop</span>
            <span class="detail-value" style="color:var(--gold); font-weight:700;"><?= htmlspecialchars($ord['partner_name']) ?></span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Amount</span>
            <span class="detail-value" style="color:var(--green); font-weight:800;">à§³<?= number_format($ord['total_coins'], 2) ?> BDT</span>
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
              Delivery Fee: <strong>à§³<?= number_format($ord['delivery_charge'], 2) ?></strong><br>
              <?= nl2br(htmlspecialchars($ord['shipping_address'] ?? '')) ?>
            </span>
          </div>

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
          <?php if (!empty($ord['delivered_at']) && $ord['status'] !== 'completed'): ?>
            <div style="grid-column: span 2; background: rgba(255, 152, 0, 0.1); border: 1px solid rgba(255,152,0,0.2); padding: 0.8rem; border-radius: 8px; margin-top: 0.5rem; color: #ff9800; font-size: 0.85rem; font-weight: 600;">
              ⏳ 48h Auto-Release Active: Funds release on <?= date('d M Y, h:i A', strtotime($ord['auto_release_deadline'])) ?> if no dispute is filed.
            </div>
          <?php endif; ?>
        </div>

        <div class="action-panel">
          <?php if ($ord['status'] === 'pending'): ?>
            <!-- Cancel Order Trigger -->
            <button class="btn-action btn-dispute" style="background: rgba(255, 82, 82, 0.1); color: var(--red); border: 1px solid rgba(255, 82, 82, 0.25);" onclick="cancelOrder(<?= $ord['id'] ?>)">❌ Cancel Order (Refund)</button>
          <?php endif; ?>
          
          <?php if ($ord['status'] === 'waiting_confirmation' && empty($ord['delivered_at'])): ?>
            <!-- Confirm Delivery Action -->
            <form method="POST" style="display:inline;" onsubmit="return confirm('Confirm that you have received the delivery? This starts the 48-hour inspection window.');">
              <input type="hidden" name="action" value="confirm_delivery"/>
              <input type="hidden" name="order_id" value="<?= $ord['id'] ?>"/>
              <button type="submit" class="btn-action" style="background: #00e676; color:#000;">✅ Confirm Delivery Received</button>
            </form>
          <?php endif; ?>

          <?php if (in_array($ord['status'], ['accepted', 'in_progress', 'waiting_confirmation'])): ?>
            <!-- Confirm Delivery/Release Payment -->
            <form method="POST" style="display:inline;" onsubmit="return confirm('Confirm receipt of product/service? This will release points directly to the partner shop.');">
              <input type="hidden" name="action" value="release"/>
              <input type="hidden" name="order_id" value="<?= $ord['id'] ?>"/>
              <button type="submit" class="btn-action">✓ Release Payment (Confirm Received)</button>
            </form>

            <!-- Raise Dispute Trigger -->
            <button class="btn-action btn-dispute" onclick="document.getElementById('dispute-form-<?= $ord['id'] ?>').style.display = 'block';">⚠️ Raise Dispute</button>
          <?php endif; ?>

          <?php if ($ord['partner_proof']): ?>
            <div style="font-size:0.8rem;">
              <span class="detail-label">Vendor Delivery Proof:</span>
              <a href="/uploads/proofs/<?= htmlspecialchars($ord['partner_proof']) ?>" target="_blank" style="color:var(--brand); text-decoration:none; font-weight:700; margin-left:0.5rem;">View Proof ↗</a>
            </div>
          <?php endif; ?>

          <?php if ($ord['status'] === 'completed' && !$ord['rating_id']): ?>
            <!-- Rating Trigger -->
            <button class="btn-action" style="background: linear-gradient(135deg, var(--gold), #ff9100); color:#000;" onclick="document.getElementById('rate-form-<?= $ord['id'] ?>').style.display = 'block';">★ Leave Review</button>
          <?php elseif ($ord['rating_id']): ?>
            <span style="font-size:0.75rem; color:var(--green); font-weight:600;">✓ Reviewed</span>
          <?php endif; ?>
        </div>

        <!-- Dispute Form -->
        <div class="collapsible-form" id="dispute-form-<?= $ord['id'] ?>" style="display:none;">
          <span class="detail-label" style="color:var(--red); margin-bottom:0.5rem; display:block;">File a Dispute</span>
          <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="dispute"/>
            <input type="hidden" name="order_id" value="<?= $ord['id'] ?>"/>
            <textarea class="form-input" name="reason" rows="3" placeholder="Provide details on why the service was not delivered or has issues..." required></textarea>
            <div class="field" style="margin-bottom: 0.8rem;">
              <label>Upload screenshot/evidence</label>
              <input type="file" name="evidence" accept=".jpg,.jpeg,.png,.webp,.pdf" style="font-size:0.78rem; color:var(--text);"/>
              <span style="font-size:0.72rem; color:red; display:block; margin-top:2px;">recommended size: clear screenshot or document evidence (max 2mb)</span>
            </div>
            <button type="submit" class="btn-action btn-dispute" style="width:100%;">Submit Dispute Report</button>
          </form>
        </div>

        <!-- Rate Form -->
        <div class="collapsible-form" id="rate-form-<?= $ord['id'] ?>" style="display:none;">
          <span class="detail-label" style="color:var(--gold); margin-bottom:0.5rem; display:block;">Rate this vendor service</span>
          <form method="POST">
            <input type="hidden" name="action" value="rate"/>
            <input type="hidden" name="order_id" value="<?= $ord['id'] ?>"/>
            
            <div class="field">
              <label>Rating (1 to 5 Stars)</label>
              <select name="rating" class="form-input" style="margin-bottom:0;" required>
                <option value="5">★★★★★ (5 Stars - Excellent)</option>
                <option value="4">★★★★☆ (4 Stars - Very Good)</option>
                <option value="3">★★★☆☆ (3 Stars - Good)</option>
                <option value="2">★★☆☆☆ (2 Stars - Poor)</option>
                <option value="1">★☆☆☆☆ (1 Star - Terrible)</option>
              </select>
            </div>
            
            <textarea class="form-input" name="review" rows="2" placeholder="Write a short review..."></textarea>
            <button type="submit" class="btn-action" style="background: linear-gradient(135deg, var(--gold), #ff9100); color:#000; width:100%;">Submit Rating</button>
          </form>
        </div>

      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<script>
function cancelOrder(orderId) {
  if (confirm("Are you sure you want to cancel this order and refund your points/coins?")) {
    fetch('../api/customer_cancel_order.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ order_id: orderId })
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        alert(data.message);
        location.reload();
      } else {
        alert("Error: " + data.message);
      }
    })
    .catch(err => {
      console.error(err);
      alert("Failed to cancel order. Please try again.");
    });
  }
}
</script>

<?php include __DIR__ . '/../includes/cropper_modal.php'; ?>
</body>
</html>


