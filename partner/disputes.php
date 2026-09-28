<?php
// =========================================================================
// partner/disputes.php  â€“  Partner Dispute Resolution Center
// =========================================================================
require_once 'nav.php';

$partner_id = $_SESSION['partner_id'];
$coin_name = getPartnerSetting('coin_name', 'Fast Points');

$err = $msg = '';

// Handle evidence submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_evidence') {
    $dispute_id = intval($_POST['dispute_id'] ?? 0);
    
    if ($dispute_id > 0 && isset($_FILES['evidence_file']) && $_FILES['evidence_file']['error'] === UPLOAD_ERR_OK) {
        // Verify dispute is for an order owned by this partner and admin decision is pending
        $stmt_verify = $pdo->prepare("SELECT d.id FROM partner_disputes d 
            JOIN partner_orders o ON d.order_id = o.id 
            WHERE d.id = :dispute_id AND o.partner_id = :partner_id AND d.admin_decision = 'pending' LIMIT 1");
        $stmt_verify->execute([':dispute_id' => $dispute_id, ':partner_id' => $partner_id]);
        
        if ($stmt_verify->fetch()) {
            $upload_dir = '../uploads/evidence/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $filename = handleSecureUpload($_FILES['evidence_file'], $upload_dir, ['jpg','jpeg','png','webp','pdf'], 'ev_partner_' . $dispute_id);
            
            if ($filename) {
                $stmt_up = $pdo->prepare("UPDATE partner_disputes SET evidence_partner = :ev WHERE id = :id");
                $stmt_up->execute([':ev' => $filename, ':id' => $dispute_id]);
                $msg = 'Evidence submitted successfully! Admin will review both sides before deciding.';
            } else {
                $err = 'Failed to save uploaded file. Invalid format or size limit exceeded.';
            }
        } else {
            $err = 'Dispute not found or already resolved.';
        }
    } else {
        $err = 'Please select a valid image/document file.';
    }
}

// Fetch disputes
$stmt_disp = $pdo->prepare("SELECT d.*, o.total_coins, o.id AS order_id, p.title AS product_title, u.name AS customer_name 
    FROM partner_disputes d 
    JOIN partner_orders o ON d.order_id = o.id 
    JOIN partner_products p ON o.product_id = p.id 
    JOIN users u ON o.customer_id = u.id 
    WHERE o.partner_id = :partner_id 
    ORDER BY d.created_at DESC");
$stmt_disp->execute([':partner_id' => $partner_id]);
$disputes = $stmt_disp->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <style>
    .dispute-card {
      background: rgba(20, 20, 31, 0.4);
      border: 1px solid rgba(255, 255, 255, 0.05);
      border-radius: 16px;
      padding: 1.5rem;
      margin-bottom: 1.5rem;
      transition: var(--transition);
    }

    .dispute-card:hover {
      border-color: rgba(255, 82, 82, 0.2);
    }

    .dispute-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 1px solid rgba(255, 255, 255, 0.05);
      padding-bottom: 0.8rem;
      margin-bottom: 1rem;
    }

    .dispute-title {
      font-size: 1.05rem;
      font-weight: 800;
      color: #fff;
    }

    .dispute-status {
      display: inline-block;
      padding: 0.2rem 0.5rem;
      border-radius: 4px;
      font-size: 0.7rem;
      font-weight: 700;
      text-transform: uppercase;
    }

    .status-pending { background: rgba(252, 185, 0, 0.1); color: var(--brand); }
    .status-refund_customer { background: rgba(255, 82, 82, 0.1); color: var(--red); }
    .status-release_to_partner { background: rgba(0, 230, 118, 0.1); color: var(--green); }

    .evidence-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1.5rem;
      background: rgba(255,255,255,0.01);
      padding: 1.2rem;
      border-radius: 12px;
      margin-top: 1rem;
      border: 1px solid rgba(255,255,255,0.02);
    }

    @media (max-width: 600px) {
      .evidence-grid {
        grid-template-columns: 1fr;
      }
    }

    .evidence-column {
      display: flex;
      flex-direction: column;
      gap: 0.4rem;
    }

    .evidence-label {
      font-size: 0.7rem;
      font-weight: 700;
      color: var(--muted);
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }

    .evidence-box {
      background: rgba(0,0,0,0.2);
      border: 1px solid rgba(255,255,255,0.05);
      border-radius: 8px;
      padding: 0.8rem;
      font-size: 0.85rem;
      min-height: 50px;
    }

    .btn-action {
      background: linear-gradient(135deg, var(--brand), #ff9100);
      color: #000;
      font-weight: 700;
      border: none;
      padding: 0.6rem 1.2rem;
      border-radius: 8px;
      font-size: 0.82rem;
      cursor: pointer;
      transition: var(--transition);
      margin-top: 0.5rem;
    }

    .btn-action:hover {
      box-shadow: 0 4px 12px rgba(252, 185, 0, 0.3);
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
  </style>
</head>
<body>

<div class="content-wrapper">
  <h1 style="font-size: 1.6rem; font-weight: 800; margin-bottom: 1.5rem;">Partner Dispute Center</h1>

  <?php if($err): ?><div class="err">⚠️ï¸ <?= htmlspecialchars($err) ?></div><?php endif; ?>
  <?php if($msg): ?><div class="success">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>

  <?php if(empty($disputes)): ?>
    <div style="text-align: center; padding: 4rem 2rem; background: rgba(255,255,255,0.01); border: 1px dashed rgba(255, 255, 255, 0.05); border-radius: 16px;">
      <p style="font-size: 1rem; color:var(--muted);">No disputes active for your orders.</p>
    </div>
  <?php else: ?>
    <?php foreach($disputes as $d): ?>
      <div class="dispute-card">
        <div class="dispute-header">
          <div class="dispute-title">Dispute on Order #<?= htmlspecialchars($d['order_id']) ?></div>
          <div>
            <span class="dispute-status status-<?= htmlspecialchars($d['admin_decision']) ?>">
              <?= str_replace('_', ' ', htmlspecialchars($d['admin_decision'])) ?>
            </span>
          </div>
        </div>

        <div style="font-size: 0.85rem; margin-bottom: 1rem; color:var(--muted);">
          <strong style="color:#fff;">Product:</strong> <?= htmlspecialchars($d['product_title']) ?> &nbsp;|&nbsp; 
          <strong style="color:#fff;">Customer:</strong> <?= htmlspecialchars($d['customer_name']) ?> &nbsp;|&nbsp; 
          <strong style="color:#fff;">Amount:</strong> <?= number_format($d['total_coins'], 1) ?> <?= htmlspecialchars($coin_name) ?>
        </div>

        <div style="margin-bottom: 0.8rem;">
          <span class="evidence-label" style="color:var(--red);">Dispute Reason</span>
          <div class="evidence-box" style="border-color: rgba(255,82,82,0.15);"><?= htmlspecialchars($d['reason'] ?: 'No reason specified.') ?></div>
        </div>

        <div class="evidence-grid">
          <!-- Customer Evidence Column -->
          <div class="evidence-column">
            <span class="evidence-label">Customer Evidence</span>
            <div class="evidence-box">
              <?php if($d['evidence_customer']): ?>
                <a href="/uploads/evidence/<?= htmlspecialchars($d['evidence_customer']) ?>" target="_blank" style="color:var(--brand); text-decoration:none; font-weight:700;">View Attachment ↗</a>
              <?php else: ?>
                No customer attachment.
              <?php endif; ?>
            </div>
          </div>

          <!-- Partner Evidence Column -->
          <div class="evidence-column">
            <span class="evidence-label">Your Evidence</span>
            <div class="evidence-box">
              <?php if($d['evidence_partner']): ?>
                <a href="/uploads/evidence/<?= htmlspecialchars($d['evidence_partner']) ?>" target="_blank" style="color:var(--brand); text-decoration:none; font-weight:700;">View Attachment ↗</a>
              <?php else: ?>
                No evidence submitted.
                
                <?php if($d['admin_decision'] === 'pending'): ?>
                  <!-- Evidence upload form -->
                  <form method="POST" enctype="multipart/form-data" style="margin-top:0.5rem; display:flex; flex-direction:column; gap:0.4rem;">
                    <input type="hidden" name="action" value="submit_evidence"/>
                    <input type="hidden" name="dispute_id" value="<?= $d['id'] ?>"/>
                    <input type="file" name="evidence_file" accept=".jpg,.jpeg,.png,.webp,.pdf" required style="font-size:0.75rem; color:var(--text);"/>
                    <span style="font-size:0.72rem; color:red; display:block; margin-top:2px;">recommended size: clear screenshot or document evidence (max 2mb)</span>
                    <button type="submit" class="btn-action">Submit Evidence Screenshot</button>
                  </form>
                <?php endif; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/cropper_modal.php'; ?>
</body>
</html>

