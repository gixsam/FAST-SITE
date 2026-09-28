<?php
// ==========================================
// FAST SITE - INBOUND CROSSPOST API v2
// ==========================================
// Allows external websites (Ayra Mart, Enzor, Best Travel) to automatically push
// products/services to the Fast Site marketplace when they upload on their own admin panel.
//
// SHARED PHOTO STRATEGY:
// Since all 7 websites run on the same Hostinger account, we store the original
// image URL directly — no re-downloading needed. Images are shared across sites.

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Only POST allowed.']);
    exit;
}

// Support both JSON payload and standard POST data
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$api_key      = $input['api_key']      ?? '';
$shop_name    = $input['shop_name']    ?? '';
$title        = $input['title']        ?? '';
$description  = $input['description']  ?? '';
$price        = floatval($input['price'] ?? $input['price_coins'] ?? 0);
$category     = $input['category']     ?? 'General';
$photo_url    = $input['photo_url']    ?? '';
$listing_type = $input['listing_type'] ?? 'product'; // 'product' or 'service'
$delete_id    = intval($input['delete_product_id'] ?? 0);
$source_id    = $input['source_id']    ?? ''; // original ID from source site

// Master API Key
$master_api_key = "FS_MASTER_" . hash('sha256', 'super_secret_fast_site_key');

if ($api_key !== $master_api_key) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized Master API Key.']);
    exit;
}

// Handle delete crosspost request
if ($delete_id > 0) {
    try {
        $pdo->prepare("DELETE FROM partner_products WHERE id = ?")->execute([$delete_id]);
        echo json_encode(['status' => 'success', 'message' => 'Product removed from Fast Site.']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

if (empty($shop_name) || empty($title)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields: shop_name and title.']);
    exit;
}

try {
    // 1. Find the Partner/Shop ID by business_name on Fast Site
    //    FIXED: was "WHERE name LIKE ?" — column is actually "business_name"
    $shopStmt = $pdo->prepare("SELECT id, business_name, status FROM partners WHERE business_name LIKE ? AND status = 'approved' LIMIT 1");
    $shopStmt->execute(['%' . trim($shop_name) . '%']);
    $shop = $shopStmt->fetch(PDO::FETCH_ASSOC);

    if (!$shop) {
        // Try to find any approved partner with a looser match
        $shopStmt2 = $pdo->prepare("SELECT id, business_name, status FROM partners WHERE business_name LIKE ? LIMIT 1");
        $shopStmt2->execute(['%' . trim($shop_name) . '%']);
        $shop = $shopStmt2->fetch(PDO::FETCH_ASSOC);
    }

    if (!$shop) {
        echo json_encode([
            'status'  => 'error',
            'message' => "Shop '$shop_name' not found in Fast Site. Please create the partner shop first in the Fast Site Admin Panel."
        ]);
        exit;
    }

    $partner_id = $shop['id'];

    // 2. IMAGE STRATEGY: Since all sites share the same Hostinger account,
    //    we store the photo_url directly. If it's a relative path, we keep it.
    //    If it's a full URL from same server, we keep it.
    //    Only download if it's from an entirely different server.
    $final_image_url = '';

    if (!empty($photo_url)) {
        // Check if it's already a full URL or a relative path
        if (strpos($photo_url, 'http') === 0) {
            // Check if it's from a known Hostinger subdomain (same account)
            $same_server_domains = [
                'best-travel.ltd',
                'atayramart.com',
                'enzor.best-travel.ltd',
                'fastsite.best-travel.ltd',
                'manza.best-travel.ltd',
                'gixsam.best-travel.ltd',
                'affibangla.best-travel.ltd'
            ];
            $is_same_server = false;
            foreach ($same_server_domains as $domain) {
                if (strpos($photo_url, $domain) !== false) {
                    $is_same_server = true;
                    break;
                }
            }

            if ($is_same_server) {
                // Same Hostinger account — use URL directly, no re-download needed
                $final_image_url = $photo_url;
            } else {
                // External URL — download and save locally
                $image_data = @file_get_contents($photo_url);
                if ($image_data !== false) {
                    $ext = pathinfo(parse_url($photo_url, PHP_URL_PATH), PATHINFO_EXTENSION);
                    if (empty($ext) || strlen($ext) > 5) $ext = 'jpg';
                    $filename = 'crosspost_' . time() . '_' . rand(1000, 9999) . '.' . strtolower($ext);
                    $savePath = __DIR__ . '/../uploads/products/' . $filename;
                    if (!is_dir(dirname($savePath))) mkdir(dirname($savePath), 0755, true);
                    file_put_contents($savePath, $image_data);
                    $final_image_url = 'uploads/products/' . $filename;
                }
            }
        } else {
            // Relative path — use directly
            $final_image_url = $photo_url;
        }
    }

    // 3. Check for duplicate (same title + partner) — avoid double-posting
    $dupCheck = $pdo->prepare("SELECT id FROM partner_products WHERE partner_id = ? AND title = ? LIMIT 1");
    $dupCheck->execute([$partner_id, $title]);
    $existing = $dupCheck->fetch();

    if ($existing) {
        // Update the existing product instead of duplicating
        $update = $pdo->prepare("UPDATE partner_products SET 
            description = ?, price = ?, category = ?, listing_type = ?, is_published = 1
            WHERE id = ?");
        $update->execute([$description, $price, $category, $listing_type, $existing['id']]);

        // Update image if provided
        if (!empty($final_image_url)) {
            $pdo->prepare("UPDATE partner_product_images SET image_url = ? WHERE product_id = ? AND is_thumbnail = 1")
                ->execute([$final_image_url, $existing['id']]);
        }

        echo json_encode([
            'status'     => 'updated',
            'message'    => "Product updated on Fast Site under '" . $shop['business_name'] . "'",
            'product_id' => $existing['id']
        ]);
        exit;
    }

    // 4. Insert the product into Fast Site Marketplace
    //    FIXED: now uses correct partner_products columns
    $insert = $pdo->prepare("INSERT INTO partner_products 
        (partner_id, title, description, price, category, listing_type, is_published, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, 1, NOW())");

    $insert->execute([
        $partner_id,
        $title,
        $description,
        $price,
        $category,
        $listing_type
    ]);

    $new_product_id = $pdo->lastInsertId();

    // 5. Insert image into partner_product_images table
    if (!empty($final_image_url) && $new_product_id) {
        $imgInsert = $pdo->prepare("INSERT INTO partner_product_images (product_id, image_url, is_thumbnail) VALUES (?, ?, 1)");
        $imgInsert->execute([$new_product_id, $final_image_url]);
    }

    echo json_encode([
        'status'     => 'success',
        'message'    => "Product published on Fast Site Marketplace under '" . $shop['business_name'] . "'",
        'product_id' => $new_product_id,
        'shop_id'    => $partner_id
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Crosspost failed: ' . $e->getMessage()]);
}
