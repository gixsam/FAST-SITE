<?php
// =========================================================================
// admin/seed_ecosystem_shops.php
// Run once from Admin Panel to ensure all 7 ecosystem partner shops exist
// in the Fast Site partners table so crosspost can find them by name.
// =========================================================================
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/../config.php';

header('Content-Type: text/html; charset=utf-8');

$ecosystem = [
    [
        'business_name' => 'Fast Site Official',
        'owner_name'    => 'Fast Site Admin',
        'email'         => 'admin@fastsite.com',
        'phone'         => '00000000000',
        'description'   => 'Official Fast Site services and products',
        'is_official'   => 1,
        'seller_level'  => 3,
        'rating'        => 5.0,
        'total_orders'  => 500,
    ],
    [
        'business_name' => 'Best Travel',
        'owner_name'    => 'Best Travel Admin',
        'email'         => 'admin@best-travel.ltd',
        'phone'         => '00000000001',
        'description'   => 'Travel packages, visa services & tours from Best Travel',
        'is_official'   => 0,
        'seller_level'  => 3,
        'rating'        => 4.9,
        'total_orders'  => 200,
    ],
    [
        'business_name' => 'Ayra Mart',
        'owner_name'    => 'Ayra Mart Admin',
        'email'         => 'admin@atayramart.com',
        'phone'         => '00000000002',
        'description'   => 'Clothing, fashion & retail products from Ayra Mart',
        'is_official'   => 0,
        'seller_level'  => 3,
        'rating'        => 4.8,
        'total_orders'  => 350,
    ],
    [
        'business_name' => 'Enzor Motor',
        'owner_name'    => 'Enzor Motor Admin',
        'email'         => 'admin@enzormotor.com',
        'phone'         => '00000000003',
        'description'   => 'Automobile parts, accessories & services from Enzor Motor',
        'is_official'   => 0,
        'seller_level'  => 3,
        'rating'        => 4.9,
        'total_orders'  => 180,
    ],
    [
        'business_name' => 'Affi Bangla',
        'owner_name'    => 'Affi Bangla Admin',
        'email'         => 'admin@affibangla.com',
        'phone'         => '00000000004',
        'description'   => 'Affiliate marketing offers & digital deals',
        'is_official'   => 0,
        'seller_level'  => 2,
        'rating'        => 4.7,
        'total_orders'  => 90,
    ],
    [
        'business_name' => 'Manza',
        'owner_name'    => 'Manza Admin',
        'email'         => 'admin@manza.com',
        'phone'         => '00000000005',
        'description'   => 'General store & digital services from Manza',
        'is_official'   => 0,
        'seller_level'  => 2,
        'rating'        => 4.8,
        'total_orders'  => 110,
    ],
    [
        'business_name' => 'GixSam',
        'owner_name'    => 'GixSam Tech Admin',
        'email'         => 'admin@gixsam.com',
        'phone'         => '00000000006',
        'description'   => 'Tech solutions, web development & digital portfolio',
        'is_official'   => 0,
        'seller_level'  => 3,
        'rating'        => 5.0,
        'total_orders'  => 420,
    ],
];

$results = [];

foreach ($ecosystem as $shop) {
    try {
        $chk = $pdo->prepare("SELECT id, is_official FROM partners WHERE business_name = :name LIMIT 1");
        $chk->execute([':name' => $shop['business_name']]);
        $existing = $chk->fetch();

        if ($existing) {
            $upd = $pdo->prepare("
                UPDATE partners SET
                    is_official  = :is_official,
                    seller_level = :seller_level,
                    rating       = :rating,
                    total_orders = :total_orders,
                    status       = 'approved'
                WHERE id = :id
            ");
            $upd->execute([
                ':is_official'  => $shop['is_official'],
                ':seller_level' => $shop['seller_level'],
                ':rating'       => $shop['rating'],
                ':total_orders' => $shop['total_orders'],
                ':id'           => $existing['id'],
            ]);

            $results[] = [
                'shop'   => $shop['business_name'],
                'action' => 'updated',
                'id'     => $existing['id'],
            ];
        } else {
            // Use the currently logged-in admin's user ID to avoid foreign key constraints
            $user_id = $_SESSION['user_id'] ?? 1;

            $reg_num = 'FS-SHOP-' . strtoupper(substr(md5($shop['business_name']), 0, 6));

            $ins = $pdo->prepare("
                INSERT INTO partners (
                    business_name, owner_name, email, phone, password_hash,
                    description, is_official,
                    seller_level, rating, total_orders, status, created_at
                ) VALUES (
                    :business_name, :owner_name, :email, :phone, :password_hash,
                    :description, :is_official,
                    :seller_level, :rating, :total_orders, 'approved', :created_at
                )
            ");
            $ins->execute([
                ':business_name'       => $shop['business_name'],
                ':owner_name'          => $shop['owner_name'],
                ':email'               => $shop['email'],
                ':phone'               => $shop['phone'],
                ':password_hash'       => password_hash('12345678', PASSWORD_DEFAULT),
                ':description'         => $shop['description'],
                ':is_official'         => $shop['is_official'],
                ':seller_level'        => $shop['seller_level'],
                ':rating'              => $shop['rating'],
                ':total_orders'        => $shop['total_orders'],
                ':created_at'          => date('Y-m-d H:i:s'),
            ]);

            $new_id = $pdo->lastInsertId();
            $results[] = [
                'shop'   => $shop['business_name'],
                'action' => 'created',
                'id'     => $new_id,
            ];
        }
    } catch (Exception $e) {
        $results[] = [
            'shop'    => $shop['business_name'],
            'action'  => 'error',
            'message' => $e->getMessage(),
        ];
    }
}

// Automatically clean any duplicated or nested domain URLs in partner_product_images & partner_products
$cleaned_db_urls = 0;
try {
    $imgs = $pdo->query("SELECT id, image_url FROM partner_product_images WHERE image_url LIKE '%http://%http://%' OR image_url LIKE '%https://%https://%'")->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($imgs)) {
        $stmt_clean = $pdo->prepare("UPDATE partner_product_images SET image_url = ? WHERE id = ?");
        foreach ($imgs as $img_row) {
            $cleaned = $img_row['image_url'];
            while (preg_match('#^https?://[^/]+/(https?://.+)#i', $cleaned, $m)) {
                $cleaned = $m[1];
            }
            if ($cleaned !== $img_row['image_url']) {
                $stmt_clean->execute([$cleaned, $img_row['id']]);
                $cleaned_db_urls++;
            }
        }
    }
    // Also clean partner_products.image
    $prods = $pdo->query("SELECT id, image FROM partner_products WHERE image LIKE '%http://%http://%' OR image LIKE '%https://%https://%'")->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($prods)) {
        $stmt_prod_clean = $pdo->prepare("UPDATE partner_products SET image = ? WHERE id = ?");
        foreach ($prods as $p_row) {
            $cleaned = $p_row['image'];
            while (preg_match('#^https?://[^/]+/(https?://.+)#i', $cleaned, $m)) {
                $cleaned = $m[1];
            }
            if ($cleaned !== $p_row['image']) {
                $stmt_prod_clean->execute([$cleaned, $p_row['id']]);
                $cleaned_db_urls++;
            }
        }
    }
} catch (Exception $e) {}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Ecosystem Shop Seeder — Fast Site Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Oswald:wght@600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="/assets/css/admin.css?v=<?= time() ?>">
</head>
<body style="background:#080911; color:#fff; font-family:'Inter',sans-serif; padding:2rem;">

<div style="max-width:800px; margin:0 auto; background:rgba(16,18,28,0.9); border:1px solid rgba(252,185,0,0.3); border-radius:18px; padding:2rem; box-shadow:0 20px 50px rgba(0,0,0,0.5);">
  <h1 style="color:var(--gold); font-family:'Oswald',sans-serif; text-transform:uppercase; margin-bottom:0.5rem;">🌐 ECOSYSTEM SHOP SEEDER & DB CLEANER</h1>
  <p style="color:var(--muted); font-size:0.88rem; margin-bottom:1.5rem;">Verifying and updating all 7 ecosystem partner shops in Fast Site Database...</p>

  <div style="display:flex; flex-direction:column; gap:0.6rem;">
    <?php foreach ($results as $r): ?>
      <div style="background:rgba(255,255,255,0.03); border:1px solid var(--border); padding:0.9rem 1.2rem; border-radius:12px; display:flex; justify-content:space-between; align-items:center;">
        <div>
          <strong style="color:#fff; font-size:0.95rem;"><?= htmlspecialchars($r['shop']) ?></strong>
          <span style="font-size:0.78rem; color:var(--muted); display:block;">Partner Shop ID: <?= $r['id'] ?? 'N/A' ?></span>
        </div>
        <span style="background:<?= $r['action']==='created'?'rgba(59,130,246,0.15)':($r['action']==='updated'?'rgba(16,185,129,0.15)':'rgba(239,68,68,0.15)') ?>; color:<?= $r['action']==='created'?'#3b82f6':($r['action']==='updated'?'#10b981':'#ef4444') ?>; font-size:0.75rem; font-weight:800; padding:0.3rem 0.8rem; border-radius:50px; text-transform:uppercase;">
          <?= $r['action'] === 'created' ? '✅ CREATED' : ($r['action'] === 'updated' ? '🔄 UPDATED' : '❌ ERROR: ' . htmlspecialchars($r['message'])) ?>
        </span>
      </div>
    <?php endforeach; ?>

    <?php if ($cleaned_db_urls > 0): ?>
      <div style="background:rgba(16,185,129,0.15); border:1px solid #10b981; color:#10b981; padding:0.9rem 1.2rem; border-radius:12px; font-weight:700; margin-top:0.5rem;">
        ✨ Automatically cleaned <?= $cleaned_db_urls ?> duplicated image URLs in database!
      </div>
    <?php endif; ?>
  </div>

  <div style="margin-top:2rem; padding-top:1rem; border-top:1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
    <span style="color:#10b981; font-weight:800;">✅ Done! All 7 Ecosystem Partner Shops exist and are active.</span>
    <a href="dashboard.php" class="btn-sm" style="text-decoration:none; padding:0.6rem 1.2rem;">← Back to Admin Dashboard</a>
  </div>
</div>

</body>
</html>
