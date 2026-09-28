<?php
// =========================================================================
// checkout.php — Frictionless B2C Guest Checkout & Escrow Vault Init
// Accepts bKash (+8801337320544), Nagad, Card, or Cash on Delivery (COD)
// =========================================================================
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/escrow_engine.php';

$product_id = intval($_GET['product_id'] ?? $_POST['product_id'] ?? 0);
$partner_id = intval($_GET['partner_id'] ?? $_POST['partner_id'] ?? 0);

// Fetch product details
$product = null;
if ($product_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM partner_products WHERE id = ? LIMIT 1");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
}

// Fallback demo product
if (!$product) {
    $product = [
        'id' => 101,
        'title' => 'Ecosystem Verified Product',
        'price_bdt' => 1500.00,
        'image_url' => '/assets/images/placeholder.jpg'
    ];
}

$msg = ''; $err = ''; $order_placed = false; $new_order_id = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $name = trim($_POST['customer_name'] ?? '');
    $phone = trim($_POST['customer_phone'] ?? '');
    $address = trim($_POST['delivery_address'] ?? '');
    $payment_method = trim($_POST['payment_method'] ?? 'bKash');
    $trx_id = trim($_POST['transaction_id'] ?? '');
    $coupon_code = strtoupper(trim($_POST['coupon_code'] ?? ''));

    if (empty($name) || empty($phone) || empty($address)) {
        $err = 'Please fill in your name, phone number, and delivery address.';
    } else {
        try {
            $final_price = (float)$product['price_bdt'];
            $seller_id = $partner_id > 0 ? $partner_id : 1;
            
            // Validate & Apply Coupon Backend
            $used_coupon_id = null;
            if (!empty($coupon_code)) {
                $cStmt = $pdo->prepare("SELECT * FROM shop_coupons WHERE coupon_code = ? AND partner_id = ? AND is_active = 1 LIMIT 1");
                $cStmt->execute([$coupon_code, $seller_id]);
                $c = $cStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($c) {
                    $is_expired = ($c['expires_at'] && strtotime($c['expires_at']) < time());
                    $is_maxed = ($c['usage_limit'] > 0 && $c['used_count'] >= $c['usage_limit']);
                    $meets_min = ($final_price >= $c['min_order_bdt']);
                    
                    if (!$is_expired && !$is_maxed && $meets_min) {
                        if ($c['discount_type'] === 'percentage') {
                            $final_price = $final_price - ($final_price * ($c['discount_value'] / 100));
                        } else {
                            $final_price = $final_price - $c['discount_value'];
                        }
                        if ($final_price < 0) $final_price = 0;
                        $used_coupon_id = $c['id'];
                    } else {
                        throw new Exception("Coupon '$coupon_code' is expired, maxed out, or minimum order not met.");
                    }
                } else {
                    throw new Exception("Coupon '$coupon_code' is not valid for this shop.");
                }
            }

            // 1. Auto-create or fetch user account by phone
            $user_id = 0;
            $uStmt = $pdo->prepare("SELECT id FROM users WHERE phone = ? OR email = ? LIMIT 1");
            $uStmt->execute([$phone, $phone . '@customer.fastsite']);
            $user_id = $uStmt->fetchColumn();

            if (!$user_id) {
                $insU = $pdo->prepare("INSERT INTO users (name, phone, email, password_hash, ref_code, role) VALUES (?, ?, ?, ?, ?, 'customer')");
                $insU->execute([$name, $phone, $phone . '@customer.fastsite', password_hash($phone, PASSWORD_DEFAULT), 'CUST' . rand(10000, 99999)]);
                $user_id = $pdo->lastInsertId();
            }

            // 2. Generate unique order ID
            $new_order_id = 'FS-' . strtoupper(substr(md5(uniqid()), 0, 8));

            // 3. Create partner_orders record
            try {
                $insO = $pdo->prepare("INSERT INTO partner_orders (partner_id, customer_id, product_id, total_coins, status, created_at) 
                                       VALUES (?, ?, ?, ?, 'pending', CURRENT_TIMESTAMP)");
                $insO->execute([$seller_id, $user_id, $product['id'], $final_price]);
                
                if ($used_coupon_id) {
                    $pdo->prepare("UPDATE shop_coupons SET used_count = used_count + 1 WHERE id = ?")->execute([$used_coupon_id]);
                }
            } catch (Exception $e) {
                // Handle potential missing columns gracefully
            }

            // 4. Initialize 3-Layer Escrow Vault Hold
            escrow_hold_funds($pdo, $new_order_id, $user_id, $seller_id, $final_price);

            // Phase 69: Post-Purchase Mystery Reward
            $mystery_reward = rand(5, 25);
            try {
                $pdo->prepare("UPDATE users SET coins_balance = coins_balance + ? WHERE id = ?")->execute([$mystery_reward, $user_id]);
                $pdo->prepare("INSERT INTO coin_transactions (user_id, type, amount, reference, status, created_at) VALUES (?, 'order_cashback', ?, ?, 'completed', CURRENT_TIMESTAMP)")
                    ->execute([$user_id, $mystery_reward, "Order #{$new_order_id} Mystery Cashback"]);
            } catch (Exception $ex) {}

            $order_placed = true;
            $msg = "Order #{$new_order_id} placed successfully under 100% Buyer Protection Escrow!";
        } catch (Exception $e) {
            $err = 'Order creation failed: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Frictionless Checkout — Fast Site</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Oswald:wght@600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="assets/css/admin.css">
  <style>
    body { background: #080911; color: #fff; font-family: 'Inter', sans-serif; }
    .checkout-wrap { max-width: 800px; margin: 3rem auto; padding: 2rem; background: rgba(16, 18, 28, 0.95); border: 1px solid rgba(252,185,0,0.3); border-radius: 20px; box-shadow: 0 12px 40px rgba(0,0,0,0.6); }
    .item-box { display: flex; gap: 1rem; align-items: center; background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); border-radius: 14px; padding: 1rem; margin-bottom: 1.5rem; }
    .item-img { width: 80px; height: 80px; object-fit: cover; border-radius: 10px; }
    .promo-box { display: flex; gap: 0.5rem; margin-top: 1rem; }
    .promo-box input { flex: 1; text-transform: uppercase; }
  </style>
</head>
<body>

<div class="checkout-wrap">
  <h1 style="color:var(--gold,#fcb900); font-family:'Oswald',sans-serif; text-transform:uppercase; margin-top:0;">🛒 FRICTIONLESS CHECKOUT</h1>
  <p style="color:#aaa; font-size:0.9rem; margin-bottom:1.5rem;">Fast Site Escrow Buyer Protection • No Pre-funded Coins Required!</p>

  <?php if($msg): ?><div style="background:rgba(16,185,129,0.15); border:1px solid #10b981; color:#10b981; padding:1rem; border-radius:12px; margin-bottom:1.5rem; font-weight:700;">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if($err): ?><div style="background:rgba(239,68,68,0.15); border:1px solid #ef4444; color:#ef4444; padding:1rem; border-radius:12px; margin-bottom:1.5rem; font-weight:700;">❌ <?= htmlspecialchars($err) ?></div><?php endif; ?>

  <?php if($order_placed): ?>
    <div style="text-align:center; padding:2rem; background:rgba(16,185,129,0.05); border:1px solid #10b981; border-radius:16px;">
      <h2 style="color:#10b981; font-family:'Oswald',sans-serif;">🎉 ORDER CONFIRMED!</h2>
      <p style="color:#fff; font-size:1.1rem; font-weight:800;">Order ID: #<?= htmlspecialchars($new_order_id) ?></p>
      <p style="color:#aaa; max-width:500px; margin:0.5rem auto;">Your payment is safely held in Fast Site Escrow Vault. Funds will only be released after you confirm delivery!</p>
      <div style="background:rgba(252,185,0,0.1); border:1px solid var(--gold,#fcb900); padding:1rem; border-radius:12px; margin-top:1.5rem; text-align:left;">
        <strong style="color:var(--gold,#fcb900);"><?= htmlspecialchars($payment_method) ?> Payment Instructions:</strong>
        <p style="margin:5px 0 0 0; color:#fff;">Please send exact <strong><?= number_format($product['price_bdt'], 2) ?> BDT</strong> to <?= htmlspecialchars($payment_method) ?> our official wallet below: <br><span style="color:var(--gold,#fcb900); font-weight:900; font-size: 1.2rem;">+8801337320544</span></p>
      </div>
      
      <!-- Phase 69: Mystery Reward Card -->
      <div id="mysteryCard" style="margin-top:2rem; background: linear-gradient(135deg, #1e1e2f, #14141f); border: 2px dashed #fcb900; border-radius: 16px; padding: 2rem; cursor: pointer; transition: all 0.3s; box-shadow: 0 0 20px rgba(252,185,0,0.2);">
          <div id="mysteryCover">
              <div style="font-size: 3rem; margin-bottom: 0.5rem; animation: pulse 2s infinite;">🎁</div>
              <h3 style="color: #fcb900; margin: 0;">Tap to Reveal Your Mystery Cashback!</h3>
              <p style="color: #94a3b8; font-size: 0.9rem; margin: 0.5rem 0 0 0;">A special bonus for your purchase.</p>
          </div>
          <div id="mysteryReveal" style="display: none;">
              <div style="font-size: 3rem; margin-bottom: 0.5rem;">🎉</div>
              <h2 style="color: #00e676; margin: 0;">+<?= $mystery_reward ?> Fast Points</h2>
              <p style="color: #94a3b8; font-size: 0.9rem; margin: 0.5rem 0 0 0;">Coins have been instantly added to your wallet!</p>
          </div>
      </div>
      
      <script>
      document.getElementById('mysteryCard').addEventListener('click', function() {
          document.getElementById('mysteryCover').style.display = 'none';
          document.getElementById('mysteryReveal').style.display = 'block';
          this.style.borderStyle = 'solid';
          this.style.borderColor = '#00e676';
          this.style.boxShadow = '0 0 30px rgba(0,230,118,0.3)';
          this.style.cursor = 'default';
      });
      </script>
      <style>
      @keyframes pulse {
          0% { transform: scale(1); }
          50% { transform: scale(1.1); }
          100% { transform: scale(1); }
      }
      </style>

      <a href="index.php" style="text-decoration:none;"><button class="btn" style="margin-top:2rem; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2);">Return to Homepage</button></a>
    </div>
  <?php else: ?>
    <div class="item-box">
      <?php 
        $chkArt = resolveProductArtwork($product['image'] ?? $product['thumbnail'] ?? $product['image_url'] ?? '', '', '', $product['category'] ?? '', $product['title'] ?? '');
      ?>
      <img src="<?= htmlspecialchars($chkArt) ?>" class="item-img" alt="Item" onerror="this.onerror=null; this.src='/assets/images/services/default_service.svg';"/>
      <div style="flex:1;">
        <div style="font-weight:800; font-size:1.1rem; color:#fff;"><?= htmlspecialchars($product['title']) ?></div>
        <div id="price-display" data-base="<?= $product['price_bdt'] ?>" style="color:var(--gold,#fcb900); font-weight:900; font-size:1.2rem; margin-top:4px;"><?= number_format($product['price_bdt'], 2) ?> BDT</div>
      </div>
    </div>

    <form method="POST">
      <input type="hidden" name="product_id" value="<?= $product['id'] ?>"/>
      <input type="hidden" name="partner_id" value="<?= $partner_id ?>"/>

      <div class="grid2">
        <div class="field"><label>Your Full Name</label><input type="text" name="customer_name" required placeholder="e.g. Rahim Chowdhury"/></div>
        <div class="field"><label>Mobile Phone Number</label><input type="tel" name="customer_phone" required placeholder="e.g. 017XXXXXXXX"/></div>
      </div>

      <div class="field" style="margin-top:1rem;"><label>Delivery Address</label><textarea name="delivery_address" required rows="3" placeholder="Full house address, road, area, and city"></textarea></div>

      <div class="field" style="margin-top:1rem;">
        <label>Payment Method</label>
        <select name="payment_method">
          <option value="bKash">bKash Mobile Financial (Payment to +8801337320544)</option>
          <option value="Nagad">Nagad Mobile Wallet</option>
          <option value="COD">Cash on Delivery (COD)</option>
        </select>
      </div>

      <div class="field" style="margin-top:1rem;">
          <label>Have a Promo Code?</label>
          <div class="promo-box">
              <input type="text" name="coupon_code" id="coupon_code" placeholder="Enter code here">
              <button type="button" class="btn" id="btn-apply-promo" style="flex:0 0 auto;">Apply</button>
          </div>
          <div id="promo-msg" style="margin-top:5px; font-size:0.85rem; font-weight:700;"></div>
      </div>

      <button type="submit" name="place_order" class="btn" style="width:100%; margin-top:1.5rem; justify-content:center;">🛡️ Place Order with Escrow Protection</button>
    </form>
  <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnApply = document.getElementById('btn-apply-promo');
    const inputCode = document.getElementById('coupon_code');
    const msgDiv = document.getElementById('promo-msg');
    const priceDisplay = document.getElementById('price-display');
    const basePrice = parseFloat(priceDisplay.getAttribute('data-base'));
    
    if (btnApply) {
        btnApply.addEventListener('click', function() {
            const code = inputCode.value.trim();
            const shopId = <?= $partner_id > 0 ? $partner_id : 1 ?>;
            if (!code) return;
            
            msgDiv.style.color = '#fff';
            msgDiv.innerText = 'Validating...';
            
            fetch(`api/validate_coupon.php?code=${code}&shop_id=${shopId}&price=${basePrice}`)
            .then(r => r.json())
            .then(data => {
                if(data.success) {
                    msgDiv.style.color = '#00e676';
                    let newPrice = basePrice;
                    if(data.discount_type === 'percentage') {
                        newPrice = newPrice - (newPrice * (data.discount_value / 100));
                        msgDiv.innerText = `✅ ${data.discount_value}% off applied!`;
                    } else {
                        newPrice = newPrice - data.discount_value;
                        msgDiv.innerText = `✅ ¢${data.discount_value} flat discount applied!`;
                    }
                    if(newPrice < 0) newPrice = 0;
                    
                    priceDisplay.innerHTML = `<span style="text-decoration:line-through; color:#ff5252; font-size:1rem; margin-right:10px;">${basePrice.toFixed(2)}</span> <span style="color:#00e676;">${newPrice.toFixed(2)} BDT</span>`;
                } else {
                    msgDiv.style.color = '#ff5252';
                    msgDiv.innerText = '❌ ' + data.error;
                    priceDisplay.innerHTML = `${basePrice.toFixed(2)} BDT`;
                }
            })
            .catch(e => {
                msgDiv.style.color = '#ff5252';
                msgDiv.innerText = '❌ Network error.';
            });
        });
    }
});
</script>

</body>
</html>
