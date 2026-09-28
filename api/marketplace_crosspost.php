<?php
// api/marketplace_crosspost.php - Fast Site Marketplace Crossposting API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    require_once __DIR__ . '/../config.php';
    
    // 1. Self-healing Database Migrations
    // Ensure redirect_url and original_website columns exist in partner_products
    try {
        $pdo->exec("ALTER TABLE partner_products ADD COLUMN redirect_url TEXT DEFAULT NULL");
    } catch (Exception $e) {
        // Ignored if column already exists
    }
    try {
        $pdo->exec("ALTER TABLE partner_products ADD COLUMN original_website TEXT DEFAULT NULL");
    } catch (Exception $e) {
        // Ignored if column already exists
    }

    // 2. Pre-register the 4 partner brands if they don't exist
    $partnersToRegister = [
        [
            'business_name' => 'Best Travel',
            'owner_name' => 'Sayam Khan',
            'email' => 'support@best-travel.ltd',
            'phone' => '01866686524',
            'password_hash' => password_hash('besttravel123', PASSWORD_BCRYPT),
            'status' => 'approved'
        ],
        [
            'business_name' => 'AT Ayra Mart',
            'owner_name' => 'Sayam Khan',
            'email' => 'support@atayramart.com',
            'phone' => '01866686524',
            'password_hash' => password_hash('ayramart123', PASSWORD_BCRYPT),
            'status' => 'approved'
        ],
        [
            'business_name' => 'Affi Bangla',
            'owner_name' => 'Sayam Khan',
            'email' => 'support@affibangla.best-travel.ltd',
            'phone' => '01866686524',
            'password_hash' => password_hash('affibangla123', PASSWORD_BCRYPT),
            'status' => 'approved'
        ],
        [
            'business_name' => 'Enzor Motors',
            'owner_name' => 'Sayam Khan',
            'email' => 'support@enzor.best-travel.ltd',
            'phone' => '01866686524',
            'password_hash' => password_hash('enzor123', PASSWORD_BCRYPT),
            'status' => 'approved'
        ]
    ];

    foreach ($partnersToRegister as $p) {
        $chk = $pdo->prepare("SELECT COUNT(*) FROM partners WHERE email = :email");
        $chk->execute([':email' => $p['email']]);
        if ($chk->fetchColumn() == 0) {
            $stmt = $pdo->prepare("INSERT INTO partners (business_name, owner_name, email, phone, password_hash, status) 
                                   VALUES (:business_name, :owner_name, :email, :phone, :password_hash, :status)");
            $stmt->execute([
                ':business_name' => $p['business_name'],
                ':owner_name' => $p['owner_name'],
                ':email' => $p['email'],
                ':phone' => $p['phone'],
                ':password_hash' => $p['password_hash'],
                ':status' => $p['status']
            ]);
        }
    }

    // 3. Process Product Crosspost
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    if (empty($input['partner_email']) || empty($input['title']) || !isset($input['price'])) {
        throw new Exception("Missing required fields: partner_email, title, price are required.");
    }

    $partnerEmail = trim($input['partner_email']);
    $title = trim($input['title']);
    $price = floatval($input['price']);
    $description = isset($input['description']) ? trim($input['description']) : '';
    $category = isset($input['category']) ? trim($input['category']) : 'General';
    $redirectUrl = isset($input['redirect_url']) ? trim($input['redirect_url']) : '';
    $imageUrl = isset($input['image_url']) ? trim($input['image_url']) : '';
    $originalWebsite = isset($input['original_website']) ? trim($input['original_website']) : '';

    // Verify partner exists
    $pQuery = $pdo->prepare("SELECT id FROM partners WHERE email = :email");
    $pQuery->execute([':email' => $partnerEmail]);
    $partnerId = $pQuery->fetchColumn();

    if (!$partnerId) {
        throw new Exception("Partner with email '$partnerEmail' is not registered.");
    }

    // Insert product
    $stmt = $pdo->prepare("INSERT INTO partner_products (partner_id, title, description, price, category, is_published, redirect_url, original_website) 
                           VALUES (:partner_id, :title, :description, :price, :category, 1, :redirect_url, :original_website)");
    $stmt->execute([
        ':partner_id' => $partnerId,
        ':title' => $title,
        ':description' => $description,
        ':price' => $price,
        ':category' => $category,
        ':redirect_url' => $redirectUrl,
        ':original_website' => $originalWebsite
    ]);
    
    $productId = $pdo->lastInsertId();

    // Insert image if provided
    if (!empty($imageUrl) && $productId) {
        $imgStmt = $pdo->prepare("INSERT INTO partner_product_images (product_id, image_url, is_thumbnail) VALUES (:product_id, :image_url, 1)");
        $imgStmt->execute([
            ':product_id' => $productId,
            ':image_url' => $imageUrl
        ]);
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Product crossposted successfully to Fast Site Marketplace.',
        'product_id' => $productId
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
