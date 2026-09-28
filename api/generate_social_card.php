<?php
require_once __DIR__ . '/../config.php';

$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
if (!$product_id) exit("Invalid product");

$stmt = $pdo->prepare("
    SELECT p.title, p.price, p.thumbnail, s.business_name, s.shop_slug 
    FROM partner_products p 
    JOIN partners s ON p.partner_id = s.id 
    WHERE p.id = ?
");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$product) exit("Product not found");

$thumb = $product['thumbnail'] ?? '';
if (empty($thumb)) {
    $img_url = 'https://images.unsplash.com/photo-1523474253046-8cd2748b5fd2?auto=format&fit=crop&w=600&q=80';
} else if (strpos($thumb, 'http') === 0) {
    $img_url = $thumb;
} else {
    $img_url = '/uploads/partners/' . htmlspecialchars($thumb);
}

$title = htmlspecialchars($product['title'] ?? '');
$price = ($product['price'] ?? 0) <= 0 ? 'FREE' : number_format($product['price'], 0) . ' BDT';
$shop_name = htmlspecialchars($product['business_name'] ?? '');
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<script src="https://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@700;900&display=swap" rel="stylesheet">
<style>
body { margin:0; padding:0; background: #080911; overflow: hidden; display: flex; justify-content: center; align-items: center; }
#card {
    width: 1080px; height: 1080px;
    background: linear-gradient(135deg, #10121c, #080911);
    position: relative;
    font-family: 'Inter', sans-serif;
    border: 15px solid #fcb900;
    box-sizing: border-box;
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    color: #fff;
}
.glass {
    background: rgba(255,255,255,0.03);
    backdrop-filter: blur(20px);
    border: 2px solid rgba(255,255,255,0.08);
    border-radius: 40px;
    padding: 50px;
    display: flex; flex-direction: column; align-items: center;
    width: 80%;
    box-shadow: 0 30px 60px rgba(0,0,0,0.6);
}
.thumb {
    width: 550px; height: 550px; object-fit: cover;
    border-radius: 30px; margin-bottom: 40px;
    border: 8px solid rgba(255,255,255,0.1);
    box-shadow: 0 20px 40px rgba(0,0,0,0.5);
}
.title { font-size: 55px; font-weight: 900; text-align: center; margin-bottom: 20px; line-height: 1.2; }
.price { font-size: 70px; font-weight: 900; color: #fcb900; margin-bottom: 25px; text-shadow: 0 0 20px rgba(252,185,0,0.4); }
.badge { font-size: 32px; background: rgba(0,230,118,0.15); color: #00e676; padding: 15px 40px; border-radius: 100px; border: 2px solid #00e676; font-weight: 700; }
.shop-banner { position: absolute; bottom: 40px; font-size: 32px; color: #94a3b8; font-weight: 700; }
</style>
</head>
<body>
<div id="card">
    <div class="glass">
        <img src="<?= $img_url ?>" class="thumb" crossorigin="anonymous">
        <div class="title"><?= $title ?></div>
        <div class="price"><?= $price ?></div>
        <div class="badge">🛡️ Escrow Protected</div>
    </div>
    <div class="shop-banner">🏪 <?= $shop_name ?> &bull; fastsite.best-travel.ltd/shop/<?= htmlspecialchars($product['shop_slug'] ?? '') ?></div>
</div>
<script>
window.onload = () => {
    setTimeout(() => {
        html2canvas(document.getElementById('card'), { scale: 1, useCORS: true, backgroundColor: '#080911', allowTaint: true }).then(canvas => {
            let b64 = canvas.toDataURL('image/png');
            window.parent.postMessage({ type: 'socialCardReady', dataUrl: b64, productId: <?= (int)$product_id ?> }, '*');
        }).catch(err => {
            console.error('Canvas error:', err);
        });
    }, 500); // Give fonts and images time to load
};
</script>
</body>
</html>
