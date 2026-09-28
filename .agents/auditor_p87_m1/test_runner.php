<?php
// Independent Forensic Audit Test for Phase 87 Milestone 1
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/user_sidebar.php';

echo "--- DYNAMIC REACTION TESTING ---\n";

// 1. In-memory SQLite simulation for exact state transitions
$db = new PDO('sqlite::memory:');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec("CREATE TABLE users (id INTEGER PRIMARY KEY, phone TEXT)");
$db->exec("CREATE TABLE partners (id INTEGER PRIMARY KEY, user_id INTEGER, phone TEXT, business_name TEXT, status TEXT)");
$db->exec("CREATE TABLE partner_requests (id INTEGER PRIMARY KEY, user_id INTEGER, status TEXT)");

$tests = [];

// Case 1: user_id = 0
$r1 = getUserShopState($db, 0, '');
$tests['user_zero'] = ($r1['state'] === 'none' && $r1['shop_id'] === 0);

// Case 2: no shop in partners, no request in partner_requests
$db->exec("INSERT INTO users VALUES (10, '01710000000')");
$r2 = getUserShopState($db, 10, '01710000000');
$tests['shopless_user'] = ($r2['state'] === 'none' && $r2['shop'] === null && $r2['request'] === null);

// Case 3: pending in partner_requests
$db->exec("INSERT INTO partner_requests VALUES (1, 10, 'pending')");
$r3 = getUserShopState($db, 10, '01710000000');
$tests['pending_request'] = ($r3['state'] === 'pending' && $r3['request']['status'] === 'pending');

// Case 4: approved in partner_requests
$db->exec("UPDATE partner_requests SET status = 'approved' WHERE id = 1");
$r4 = getUserShopState($db, 10, '01710000000');
$tests['approved_request'] = ($r4['state'] === 'approved');

// Case 5: partner row with 'approved'
$db->exec("INSERT INTO partners VALUES (100, 10, '01710000000', 'Gold Electronics', 'approved')");
$r5 = getUserShopState($db, 10, '01710000000');
$tests['approved_partner'] = ($r5['state'] === 'approved' && $r5['shop_name'] === 'Gold Electronics' && $r5['shop_id'] === 100);

// Case 6: partner row with 'active'
$db->exec("UPDATE partners SET status = 'active' WHERE id = 100");
$r6 = getUserShopState($db, 10, '01710000000');
$tests['active_partner'] = ($r6['state'] === 'approved');

// Case 7: partner row with 'pending'
$db->exec("UPDATE partners SET status = 'pending' WHERE id = 100");
$r7 = getUserShopState($db, 10, '01710000000');
$tests['pending_partner'] = ($r7['state'] === 'pending');

// Case 8: partner row with 'suspended' -> should fall back to partner_requests (or none)
$db->exec("UPDATE partners SET status = 'suspended' WHERE id = 100");
$db->exec("DELETE FROM partner_requests");
$r8 = getUserShopState($db, 10, '01710000000');
$tests['suspended_partner_ignored'] = ($r8['state'] === 'none');

// Case 9: phone lookup fallback when phone is not passed
$db->exec("UPDATE partners SET status = 'approved' WHERE id = 100");
$r9 = getUserShopState($db, 10, ''); // empty phone passed, should query users table
$tests['phone_fallback_query'] = ($r9['state'] === 'approved' && $r9['shop_name'] === 'Gold Electronics');

// Real DB test with actual configured DB
if (isset($pdo)) {
    $realTest = getUserShopState($pdo, 0, '');
    $tests['real_db_dummy_user'] = ($realTest['state'] === 'none');
}

echo json_encode($tests, JSON_PRETTY_PRINT) . "\n";
