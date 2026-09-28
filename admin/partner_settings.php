<?php
// =========================================================================
// admin/partner_settings.php  –  Global Partner Shop & Points Settings
// =========================================================================
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php'); exit;
}
require_once __DIR__ . '/../config.php';

$msg = $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings_to_save = [
        'coin_name'          => trim($_POST['coin_name'] ?? 'Fast Points'),
        'exchange_rate'      => floatval($_POST['exchange_rate'] ?? 1),
        'min_withdrawal'     => floatval($_POST['min_withdrawal'] ?? 500),
        'auto_respond_days'  => intval($_POST['auto_respond_days'] ?? 2),
        'auto_complete_days' => intval($_POST['auto_complete_days'] ?? 7),
        'cooling_off_hours'  => intval($_POST['cooling_off_hours'] ?? 24)
    ];

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO partner_settings (setting_key, setting_value) 
            VALUES (:key, :val) 
            ON CONFLICT(setting_key) DO UPDATE SET setting_value = :val, updated_at = CURRENT_TIMESTAMP");

        // Wait! SQLite supports ON CONFLICT(setting_key) DO UPDATE SET...
        // But standard MySQL does not support ON CONFLICT, it supports ON DUPLICATE KEY UPDATE.
        // Let's check the database driver to use the correct SQL syntax!
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            $stmt = $pdo->prepare("INSERT INTO partner_settings (setting_key, setting_value) 
                VALUES (:key, :val) 
                ON CONFLICT(setting_key) DO UPDATE SET setting_value = :val, updated_at = CURRENT_TIMESTAMP");
        } else {
            $stmt = $pdo->prepare("INSERT INTO partner_settings (setting_key, setting_value) 
                VALUES (:key, :val) 
                ON DUPLICATE KEY UPDATE setting_value = :val");
        }

        foreach ($settings_to_save as $key => $val) {
            $stmt->execute([
                ':key' => $key,
                ':val' => strval($val)
            ]);
        }

        $pdo->commit();
        $msg = 'Partner Shop settings updated successfully!';
    } catch (Exception $e) {
        $pdo->rollBack();
        $err = 'Database Error: ' . $e->getMessage();
    }
}

// Fetch current values
$coin_name          = getPartnerSetting('coin_name', 'Fast Points');
$exchange_rate      = getPartnerSetting('exchange_rate', '1');
$min_withdrawal     = getPartnerSetting('min_withdrawal', '500');
$auto_respond_days  = getPartnerSetting('auto_respond_days', '2');
$auto_complete_days = getPartnerSetting('auto_complete_days', '7');
$cooling_off_hours  = getPartnerSetting('cooling_off_hours', '24');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Partner Shop Settings — Fast Site Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>
<?php include 'nav.php'; ?>

<div class="wrap">
  <h1>⚙️ Partner Shop Settings</h1>
  <p style="font-size:0.85rem; color:#8888aa; margin-bottom:1.5rem;">Configure the platform points conversion rates, payout thresholds, and automation timelines.</p>

  <?php if($msg): ?><div class="alert-ok">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if($err): ?><div class="alert-err">❌ <?= htmlspecialchars($err) ?></div><?php endif; ?>

  <div class="form-panel">
    <form method="POST">
      <div class="row-flex">
        <div class="field">
          <label>Coin / Point Name *</label>
          <input type="text" name="coin_name" value="<?= htmlspecialchars($coin_name) ?>" required/>
        </div>
        <div class="field">
          <label>Exchange Rate (BDT per Point) *</label>
          <input type="number" step="0.01" name="exchange_rate" value="<?= htmlspecialchars($exchange_rate) ?>" required/>
          <span style="font-size:0.7rem; color:#8888aa; display:block; margin-top:0.25rem;">e.g. 1 means 1 Point = 1 BDT. 0.5 means 1 Point = 0.5 BDT.</span>
        </div>
      </div>

      <div class="field">
        <label>Minimum Withdrawal Amount (Points) *</label>
        <input type="number" step="1" name="min_withdrawal" value="<?= htmlspecialchars($min_withdrawal) ?>" required/>
      </div>

      <div class="row-flex">
        <div class="field">
          <label>Auto Respond Deadline (Days) *</label>
          <input type="number" name="auto_respond_days" value="<?= htmlspecialchars($auto_respond_days) ?>" required/>
          <span style="font-size:0.7rem; color:#8888aa; display:block; margin-top:0.25rem;">Max days for a partner to accept a pending order before cancellation.</span>
        </div>
        <div class="field">
          <label>Auto Complete Deadline (Days) *</label>
          <input type="number" name="auto_complete_days" value="<?= htmlspecialchars($auto_complete_days) ?>" required/>
          <span style="font-size:0.7rem; color:#8888aa; display:block; margin-top:0.25rem;">Max days for customer to release points after proof before auto-release.</span>
        </div>
      </div>

      <div class="field">
        <label>Dispute Cooling-off Period (Hours) *</label>
        <input type="number" name="cooling_off_hours" value="<?= htmlspecialchars($cooling_off_hours) ?>" required/>
        <span style="font-size:0.7rem; color:#8888aa; display:block; margin-top:0.25rem;">Hours after delivery proof where customer can still raise a dispute.</span>
      </div>

      <button type="submit" class="btn-submit">Save Settings Configurations</button>
    </form>
  </div>
</div>

</body>
</html>
