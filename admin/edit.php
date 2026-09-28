<?php
// ============================================================
// admin/edit.php  –  Edit a single application
// ============================================================

session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header('Location: dashboard.php');
    exit;
}

// Fetch application + service name
$stmt = $pdo->prepare("
    SELECT a.*, s.name AS service_name
    FROM `applications` a
    JOIN `services` s ON a.service_id = s.id
    WHERE a.id = :id
");
$stmt->execute([':id' => $id]);
$app = $stmt->fetch();

if (!$app) {
    header('Location: dashboard.php');
    exit;
}

$errors  = [];
$success = false;

// ── Handle update ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['user_name']       ?? '');
    $phone    = trim($_POST['user_phone']       ?? '');
    $email    = trim($_POST['user_email']       ?? '');
    $nid      = trim($_POST['nid_number']       ?? '');
    $passport = trim($_POST['passport_number']  ?? '');
    $dlicense = trim($_POST['driving_license']  ?? '');
    $details  = trim($_POST['details']          ?? '');

    if (!$name)  $errors[] = 'Full Name is required.';
    if (!$phone) $errors[] = 'Phone is required.';

    if (empty($errors)) {
        $upd = $pdo->prepare("
            UPDATE `applications` SET
                `user_name`        = :name,
                `user_phone`       = :phone,
                `user_email`       = :email,
                `nid_number`       = :nid,
                `passport_number`  = :passport,
                `driving_license`  = :dlicense,
                `details`          = :details
            WHERE `id` = :id
        ");
        $upd->execute([
            ':name'     => $name,
            ':phone'    => $phone,
            ':email'    => $email ?: null,
            ':nid'      => $nid      ?: null,
            ':passport' => $passport ?: null,
            ':dlicense' => $dlicense ?: null,
            ':details'  => $details  ?: null,
            ':id'       => $id,
        ]);
        header('Location: dashboard.php?updated=1');
        exit;
    }

    // Populate form with submitted values
    $app['user_name']       = $name;
    $app['user_phone']      = $phone;
    $app['user_email']      = $email;
    $app['nid_number']      = $nid;
    $app['passport_number'] = $passport;
    $app['driving_license'] = $dlicense;
    $app['details']         = $details;
}
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Edit Application #<?= $id ?> — Fast Site Admin</title>
  <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>
<?php include 'nav.php'; ?>

<div class="wrap">
  <h1>✏️ Edit Application</h1>
  <div class="ref">
    Ref: <strong>FS-<?= str_pad($id,6,'0',STR_PAD_LEFT) ?></strong> &nbsp;|&nbsp;
    Service (read-only):
  </div>
  <div class="service-readonly">🏛️ <?= htmlspecialchars($app['service_name']) ?></div>

  <?php if (!empty($errors)): ?>
    <ul class="error-list">
      <?php foreach($errors as $e): ?><li><?= htmlspecialchars($e)?></li><?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <form method="POST" action="edit.php?id=<?= $id ?>">

    <div class="row">
      <div class="field">
        <label>Full Name *</label>
        <input type="text" name="user_name" value="<?= htmlspecialchars($app['user_name']) ?>" required/>
      </div>
      <div class="field">
        <label>Phone / WhatsApp *</label>
        <input type="tel" name="user_phone" value="<?= htmlspecialchars($app['user_phone']) ?>" required/>
      </div>
    </div>

    <div class="field">
      <label>Email (optional)</label>
      <input type="email" name="user_email" value="<?= htmlspecialchars($app['user_email'] ?? '') ?>"/>
    </div>

    <div class="row">
      <div class="field">
        <label>NID Number</label>
        <input type="text" name="nid_number" value="<?= htmlspecialchars($app['nid_number'] ?? '') ?>"/>
      </div>
      <div class="field">
        <label>Passport Number</label>
        <input type="text" name="passport_number" value="<?= htmlspecialchars($app['passport_number'] ?? '') ?>"/>
      </div>
    </div>

    <div class="field">
      <label>Driving License</label>
      <input type="text" name="driving_license" value="<?= htmlspecialchars($app['driving_license'] ?? '') ?>"/>
    </div>

    <div class="field">
      <label>Additional Details</label>
      <textarea name="details"><?= htmlspecialchars($app['details'] ?? '') ?></textarea>
    </div>

    <div class="actions">
      <button type="submit" class="btn-save">💾 Save Changes</button>
      <a href="dashboard.php" class="btn-cancel">Cancel</a>
    </div>
  </form>
</div>
</body>
</html>
