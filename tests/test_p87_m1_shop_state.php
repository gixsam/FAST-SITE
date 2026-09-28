<?php
/**
 * Automated Empirical Test Suite for Phase 87 Milestone 1:
 * Comprehensive testing of getUserShopState() across required and edge case scenarios.
 */

// Step 1: Ensure getUserShopState is loaded
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/user_sidebar.php';

$totalAssertions = 0;
$passedAssertions = 0;
$failedAssertions = [];

function assert_test($condition, $description, $details = '') {
    global $totalAssertions, $passedAssertions, $failedAssertions;
    $totalAssertions++;
    if ($condition) {
        $passedAssertions++;
        echo "  [PASS] $description" . PHP_EOL;
    } else {
        $failedAssertions[] = "$description: $details";
        echo "  [FAIL] $description: $details" . PHP_EOL;
    }
}

echo "=======================================================" . PHP_EOL;
echo "1. TESTING ISOLATED IN-MEMORY SQLITE FIXTURES" . PHP_EOL;
echo "=======================================================" . PHP_EOL;

// Setup in-memory SQLite fixture
$mockPdo = new PDO('sqlite::memory:', null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);

$mockPdo->exec("
    CREATE TABLE users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT,
        phone TEXT,
        email TEXT
    );

    CREATE TABLE partners (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NULL,
        phone TEXT NULL,
        business_name TEXT,
        status TEXT NULL,
        registration_number TEXT NULL
    );

    CREATE TABLE partner_requests (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        business_name TEXT,
        status TEXT,
        created_at TEXT
    );
");

// Insert seed data for users
$mockPdo->exec("
    INSERT INTO users (id, name, phone, email) VALUES
    (1, 'User Approved Direct', '01700000001', 'user1@test.com'),
    (2, 'User Approved Active', '01700000002', 'user2@test.com'),
    (3, 'User Pending Partner', '01700000003', 'user3@test.com'),
    (4, 'User No Partner', '01700000004', 'user4@test.com'),
    (5, 'User Phone Only Match', '01700000005', 'user5@test.com'),
    (6, 'User Null Status', '01700000006', 'user6@test.com'),
    (7, 'User Empty Status', '01700000007', 'user7@test.com'),
    (8, 'User Whitespace Status', '01700000008', 'user8@test.com'),
    (9, 'User Pending Request Only', '01700000009', 'user9@test.com'),
    (10, 'User Suspended Partner', '01700000010', 'user10@test.com'),
    (11, 'User Rejected Partner', '01700000011', 'user11@test.com');
");

// Insert seed data for partners
$mockPdo->exec("
    INSERT INTO partners (id, user_id, phone, business_name, status, registration_number) VALUES
    (101, 1, '01700000001', 'Approved Store One', 'approved', 'FS-SHOP-00101'),
    (102, 2, '01700000002', 'Active Store Two', 'active', 'FS-SHOP-00102'),
    (103, 3, '01700000003', 'Pending Partner Store', 'pending', 'FS-SHOP-00103'),
    (105, NULL, '01700000005', 'Phone Matched Store', 'approved', 'FS-SHOP-00105'),
    (106, 6, '01700000006', 'Null Status Store', NULL, 'FS-SHOP-00106'),
    (107, 7, '01700000007', 'Empty Status Store', '', 'FS-SHOP-00107'),
    (108, 8, '01700000008', 'Whitespace Status Store', '   ', 'FS-SHOP-00108'),
    (110, 10, '01700000010', 'Suspended Store', 'suspended', 'FS-SHOP-00110'),
    (111, 11, '01700000011', 'Rejected Store', 'rejected', 'FS-SHOP-00111');
");

// Insert seed data for partner_requests
$mockPdo->exec("
    INSERT INTO partner_requests (id, user_id, business_name, status, created_at) VALUES
    (201, 9, 'Pending Request Store', 'pending', '2026-09-09 12:00:00');
");

echo PHP_EOL . "--- Scenario 1: User with 0 / Negative ID ---" . PHP_EOL;
$res0 = getUserShopState($mockPdo, 0);
assert_test($res0['state'] === 'none', "User ID 0 returns state 'none'");
assert_test($res0['shop'] === null, "User ID 0 returns shop null");
assert_test($res0['shop_id'] === 0, "User ID 0 returns shop_id 0");

$resNeg1 = getUserShopState($mockPdo, -1);
assert_test($resNeg1['state'] === 'none', "User ID -1 returns state 'none'");
assert_test($resNeg1['shop'] === null, "User ID -1 returns shop null");

$resNeg99 = getUserShopState($mockPdo, -99);
assert_test($resNeg99['state'] === 'none', "User ID -99 returns state 'none'");

$resNullPdo = getUserShopState(null, 1);
assert_test($resNullPdo['state'] === 'none', "Null PDO returns state 'none'");

echo PHP_EOL . "--- Scenario 2: User with Approved Partner ---" . PHP_EOL;
$resApp1 = getUserShopState($mockPdo, 1);
assert_test($resApp1['state'] === 'approved', "User 1 (status='approved') returns state 'approved'");
assert_test($resApp1['shop_name'] === 'Approved Store One', "User 1 returns correct shop_name");
assert_test($resApp1['shop_id'] === 101, "User 1 returns correct shop_id");
assert_test(is_array($resApp1['shop']), "User 1 returns shop row array");

$resApp2 = getUserShopState($mockPdo, 2);
assert_test($resApp2['state'] === 'approved', "User 2 (status='active') returns state 'approved'");
assert_test($resApp2['shop_name'] === 'Active Store Two', "User 2 returns correct shop_name");
assert_test($resApp2['shop_id'] === 102, "User 2 returns correct shop_id");

echo PHP_EOL . "--- Scenario 3: User with Pending Partner ---" . PHP_EOL;
// Subcase A: pending status in partners table
$resPend1 = getUserShopState($mockPdo, 3);
assert_test($resPend1['state'] === 'pending', "User 3 (partners status='pending') returns state 'pending'");
assert_test($resPend1['shop_name'] === 'Pending Partner Store', "User 3 returns correct shop_name");
assert_test($resPend1['shop_id'] === 103, "User 3 returns correct shop_id");

// Subcase B: pending status in partner_requests table
$resPend2 = getUserShopState($mockPdo, 9);
assert_test($resPend2['state'] === 'pending', "User 9 (partner_requests status='pending') returns state 'pending'");
assert_test(is_array($resPend2['request']), "User 9 returns request row array");
assert_test($resPend2['request']['business_name'] === 'Pending Request Store', "User 9 returns correct request business_name");

echo PHP_EOL . "--- Scenario 4: User without Partner ---" . PHP_EOL;
$resNoPart = getUserShopState($mockPdo, 4);
assert_test($resNoPart['state'] === 'none', "User 4 (no partner, no request) returns state 'none'");
assert_test($resNoPart['shop'] === null, "User 4 returns shop null");
assert_test($resNoPart['request'] === null, "User 4 returns request null");
assert_test($resNoPart['shop_name'] === 'My Shop', "User 4 returns default shop_name 'My Shop'");
assert_test($resNoPart['shop_id'] === 0, "User 4 returns shop_id 0");

$resNonExistent = getUserShopState($mockPdo, 99999);
assert_test($resNonExistent['state'] === 'none', "Non-existent User ID 99999 returns state 'none'");

echo PHP_EOL . "--- Scenario 5: User with Phone-Only Match ---" . PHP_EOL;
// Subcase A: phone passed explicitly
$resPhoneParam = getUserShopState($mockPdo, 5, '01700000005');
assert_test($resPhoneParam['state'] === 'approved', "User 5 with explicit phone param returns state 'approved'");
assert_test($resPhoneParam['shop_name'] === 'Phone Matched Store', "User 5 returns correct shop_name");
assert_test($resPhoneParam['shop_id'] === 105, "User 5 returns correct shop_id 105");

// Subcase B: phone empty, queried automatically from users table
$resPhoneQuery = getUserShopState($mockPdo, 5, '');
assert_test($resPhoneQuery['state'] === 'approved', "User 5 with empty phone queries users table and returns state 'approved'");
assert_test($resPhoneQuery['shop_name'] === 'Phone Matched Store', "User 5 auto-query returns correct shop_name");
assert_test($resPhoneQuery['shop_id'] === 105, "User 5 auto-query returns correct shop_id 105");

// Subcase C: phone is empty string in DB - should NOT match partners with empty phone
$mockPdo->exec("INSERT INTO users (id, name, phone) VALUES (12, 'Empty Phone User', '')");
$mockPdo->exec("INSERT INTO partners (id, user_id, phone, business_name, status) VALUES (112, NULL, '', 'Empty Phone Partner', 'approved')");
$resEmptyPhone = getUserShopState($mockPdo, 12, '');
assert_test($resEmptyPhone['state'] === 'none', "Empty phone does not accidentally match empty phone partner");

echo PHP_EOL . "--- Scenario 6: User with Empty / Null / Whitespace Status ---" . PHP_EOL;
$resNull = getUserShopState($mockPdo, 6);
assert_test($resNull['state'] === 'approved', "User 6 (status=NULL) defaults to state 'approved'");
assert_test($resNull['shop_id'] === 106, "User 6 returns correct shop_id 106");

$resEmpty = getUserShopState($mockPdo, 7);
assert_test($resEmpty['state'] === 'approved', "User 7 (status='') defaults to state 'approved'");
assert_test($resEmpty['shop_id'] === 107, "User 7 returns correct shop_id 107");

$resWhite = getUserShopState($mockPdo, 8);
assert_test($resWhite['state'] === 'approved', "User 8 (status='   ') defaults to state 'approved'");
assert_test($resWhite['shop_id'] === 108, "User 8 returns correct shop_id 108");

echo PHP_EOL . "--- Scenario 7: Suspended & Rejected Partners ---" . PHP_EOL;
$resSuspended = getUserShopState($mockPdo, 10);
assert_test($resSuspended['state'] === 'none', "User 10 (status='suspended') returns state 'none'");

$resRejected = getUserShopState($mockPdo, 11);
assert_test($resRejected['state'] === 'none', "User 11 (status='rejected') returns state 'none'");

echo PHP_EOL . "=======================================================" . PHP_EOL;
echo "2. TESTING AGAINST LIVE ACTIVE DATABASE ($driver)" . PHP_EOL;
echo "=======================================================" . PHP_EOL;

// Test live PDO from config.php
$liveRes0 = getUserShopState($pdo, 0);
assert_test($liveRes0['state'] === 'none', "Live DB: User 0 returns 'none'");

$liveResNeg = getUserShopState($pdo, -5);
assert_test($liveResNeg['state'] === 'none', "Live DB: User -5 returns 'none'");

// Check if any partner exists in live DB
$livePartnerStmt = $pdo->query("SELECT user_id, phone, business_name, status FROM partners LIMIT 5");
$livePartners = $livePartnerStmt->fetchAll(PDO::FETCH_ASSOC);

if (!empty($livePartners)) {
    echo "Found " . count($livePartners) . " partner records in live DB:" . PHP_EOL;
    foreach ($livePartners as $idx => $lp) {
        $lUid = (int)($lp['user_id'] ?? 0);
        $lPhone = (string)($lp['phone'] ?? '');
        $res = getUserShopState($pdo, $lUid, $lPhone);
        echo "  - Live Partner #$idx [UID: $lUid, Phone: $lPhone, Status: {$lp['status']}]: State={$res['state']}, Name={$res['shop_name']}" . PHP_EOL;
        assert_test(in_array($res['state'], ['approved', 'pending', 'none']), "Live DB Partner #$idx returns valid enum state ('{$res['state']}')");
    }
} else {
    echo "No partner records in live DB. Testing user 999999..." . PHP_EOL;
    $resNone = getUserShopState($pdo, 999999);
    assert_test($resNone['state'] === 'none', "Live DB: Non-existent user returns 'none'");
}

echo PHP_EOL . "=======================================================" . PHP_EOL;
echo "TEST RESULTS SUMMARY:" . PHP_EOL;
echo "Total Assertions: $totalAssertions" . PHP_EOL;
echo "Passed Assertions: $passedAssertions" . PHP_EOL;
echo "Failed Assertions: " . count($failedAssertions) . PHP_EOL;
if (!empty($failedAssertions)) {
    echo "Failures:" . PHP_EOL;
    foreach ($failedAssertions as $fail) {
        echo " - $fail" . PHP_EOL;
    }
}
echo "=======================================================" . PHP_EOL;

if (count($failedAssertions) === 0) {
    exit(0);
} else {
    exit(1);
}
