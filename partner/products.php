<?php
// =========================================================================
// partner/products.php  –  Partner Products List
// =========================================================================
require_once 'nav.php';

$partner_id = intval($_SESSION['partner_id'] ?? 0);
if (!$partner_id) {
    header('Location: /partner/dashboard.php');
    exit;
}
$coin_name = getPartnerSetting('coin_name', 'Fast Points');

// Fetch partner products
$stmt = $pdo->prepare("SELECT p.*, 
    (SELECT image_url FROM partner_product_images WHERE product_id = p.id AND is_thumbnail = 1 LIMIT 1) AS thumbnail 
    FROM partner_products p 
    WHERE p.partner_id = :partner_id 
    ORDER BY p.created_at DESC");
$stmt->execute([':partner_id' => $partner_id]);
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <style>
    .header-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 2rem;
    }

    .btn-add {
      background: linear-gradient(135deg, var(--brand), #ff9100);
      color: #000;
      text-decoration: none;
      font-weight: 700;
      padding: 0.6rem 1.2rem;
      border-radius: 50px;
      font-size: 0.88rem;
      transition: var(--transition);
    }

    .btn-add:hover {
      transform: translateY(-1px);
      box-shadow: 0 5px 15px rgba(252, 185, 0, 0.4);
    }

    .prod-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 1.5rem;
    }

    .prod-card {
      background: rgba(255, 255, 255, 0.02);
      border: 1px solid rgba(255, 255, 255, 0.05);
      border-radius: 16px;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      transition: var(--transition);
    }

    .prod-card:hover {
      border-color: rgba(252, 185, 0, 0.2);
      transform: translateY(-4px);
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
    }

    .prod-img-container {
      position: relative;
      width: 100%;
      height: 180px;
      background: #101015;
    }

    .prod-img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .prod-status {
      position: absolute;
      top: 12px;
      right: 12px;
      background: rgba(8, 8, 12, 0.8);
      backdrop-filter: blur(4px);
      color: #fff;
      padding: 0.25rem 0.6rem;
      border-radius: 4px;
      font-size: 0.7rem;
      font-weight: 700;
      border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .prod-body {
      padding: 1.2rem;
      flex-grow: 1;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }

    .prod-title {
      font-size: 1.05rem;
      font-weight: 700;
      color: #fff;
      margin-bottom: 0.4rem;
      line-height: 1.4;
    }

    .prod-desc {
      font-size: 0.8rem;
      color: var(--muted);
      margin-bottom: 1rem;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }

    .prod-footer {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-top: auto;
    }

    .prod-price {
      font-size: 1.15rem;
      font-weight: 800;
      color: var(--brand);
    }

    .prod-actions {
      display: flex;
      gap: 0.6rem;
    }

    .action-icon {
      background: rgba(255,255,255,0.03);
      border: 1px solid rgba(255,255,255,0.05);
      border-radius: 8px;
      width: 32px;
      height: 32px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--text);
      text-decoration: none;
      transition: var(--transition);
    }

    .action-view:hover {
      background: rgba(56, 189, 248, 0.15);
      border-color: #38bdf8;
      color: #38bdf8;
    }

    .action-edit:hover {
      background: rgba(252, 185, 0, 0.1);
      border-color: var(--brand);
      color: var(--brand);
    }

    .action-delete:hover {
      background: rgba(255, 82, 82, 0.1);
      border-color: var(--red);
      color: var(--red);
    }

    .empty-state {
      text-align: center;
      padding: 4rem 2rem;
      background: rgba(255,255,255,0.01);
      border: 1px dashed rgba(255, 255, 255, 0.05);
      border-radius: 16px;
      grid-column: 1 / -1;
    }
  </style>
</head>
<body>

<div class="content-wrapper">
  <div class="header-bar">
    <h1 style="font-size: 1.6rem; font-weight: 800;">My Products</h1>
    <a href="product_add.php" class="btn-add">➕ Add New Product</a>
  </div>

  <div class="prod-grid">
    <?php if(empty($products)): ?>
      <div class="empty-state">
        <p style="font-size: 1.1rem; font-weight: 600; margin-bottom: 0.5rem; color:#fff;">No products found</p>
        <p style="font-size: 0.82rem; color:var(--muted); margin-bottom: 1.5rem;">Start adding items to your shop catalog.</p>
        <a href="product_add.php" class="btn-add">Add First Product</a>
      </div>
    <?php else: ?>
      <?php foreach($products as $prod): ?>
        <div class="prod-card">
          <div class="prod-img-container">
            <?php 
              $prodArtwork = resolveProductArtwork($prod['thumbnail'] ?? '', '', '', $prod['category'] ?? '', $prod['title'] ?? '', $prod['listing_type'] ?? 'product');
            ?>
            <img src="<?= htmlspecialchars($prodArtwork) ?>" alt="Product" class="prod-img" onerror="this.src='/assets/images/services/default_service.svg'"/>
            <span class="prod-status" style="<?= $prod['is_published'] ? 'color: var(--green); border-color: rgba(0, 230, 118, 0.3);' : 'color: var(--muted);' ?>">
              <?= $prod['is_published'] ? 'Published' : 'Draft' ?>
            </span>
            <?php if (($prod['listing_type'] ?? '') === 'affiliate' || !empty($prod['affiliate_url'])): ?>
              <span style="position:absolute; top:12px; left:12px; background:linear-gradient(135deg, #10b981, #00b0ff); color:#fff; padding:0.25rem 0.6rem; border-radius:4px; font-size:0.68rem; font-weight:800;">
                ⚡ Affiliate
              </span>
            <?php endif; ?>
          </div>
          <div class="prod-body">
            <div>
              <div class="prod-title"><?= htmlspecialchars($prod['title']) ?></div>
              <div class="prod-desc"><?= htmlspecialchars($prod['description'] ?: 'No description provided.') ?></div>
              <?php if($prod['scheduled_at']): ?>
                <div style="font-size:0.72rem; color:var(--muted); margin-bottom:1rem; display:flex; align-items:center; gap:0.3rem;">
                  <span>⏰ Scheduled:</span>
                  <span style="color:#00bcd4;"><?= date('d M Y, h:i A', strtotime($prod['scheduled_at'])) ?></span>
                </div>
              <?php endif; ?>
              <?php if (!empty($prod['affiliate_url'])): ?>
                <div style="font-size:0.72rem; color:#00e676; margin-bottom:0.8rem; display:flex; align-items:center; gap:0.3rem; word-break:break-all;">
                  <span>🔗 Dest:</span>
                  <a href="<?= htmlspecialchars($prod['affiliate_url']) ?>" target="_blank" style="color:#00bcd4; text-decoration:none;"><?= htmlspecialchars(substr($prod['affiliate_url'], 0, 35)) ?><?= strlen($prod['affiliate_url']) > 35 ? '...' : '' ?></a>
                </div>
              <?php endif; ?>
            </div>
            
            <div class="prod-footer">
              <div class="prod-price">
                <?php if ((float)$prod['price'] <= 0): ?>
                  <span style="background: rgba(0, 230, 118, 0.15); border: 1px solid rgba(0, 230, 118, 0.35); color: #00e676; font-weight: 800; font-size: 0.78rem; padding: 3px 8px; border-radius: 4px; letter-spacing: 0.05em;">FREE</span>
                <?php else: ?>
                  <?= number_format($prod['price'], 1) ?> <span style="font-size:0.75rem; font-weight:500; color:var(--muted);"><?= htmlspecialchars($coin_name) ?></span>
                <?php endif; ?>
              </div>
              <div class="prod-actions">
                <a href="../product_detail.php?id=<?= $prod['id'] ?>" target="_blank" class="action-icon action-view" title="View Live on Storefront">👁️</a>
                <?php if (!empty($prod['affiliate_url'])): ?>
                  <a href="<?= htmlspecialchars($prod['affiliate_url']) ?>" target="_blank" class="action-icon" style="color:#00e676;" title="Open Affiliate Destination">↗</a>
                <?php endif; ?>
                <a href="product_edit.php?id=<?= $prod['id'] ?>" class="action-icon action-edit" title="Edit Product">✏️</a>
                <a href="product_delete.php?id=<?= $prod['id'] ?>" class="action-icon action-delete" onclick="return confirm('Are you sure you want to delete this product? All images will be deleted.');" title="Delete Product">🗑️</a>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

</body>
</html>
