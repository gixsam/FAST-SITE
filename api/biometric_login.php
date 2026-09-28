<?php
// =========================================================================
// api/biometric_login.php — Instant Biometric Login for Fast Site Mobile App
// =========================================================================
require_once __DIR__ . '/../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

$user_id = intval($_POST['user_id'] ?? 0);
$phone = trim($_POST['phone'] ?? '');

if ($user_id <= 0 && empty($phone)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid user credentials for biometric authentication']);
    exit;
}

try {
    if ($user_id > 0) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$user_id]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ? LIMIT 1");
        $stmt->execute([$phone]);
    }

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['status' => 'error', 'message' => 'User account not found']);
        exit;
    }

    // Set authenticated session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_phone'] = $user['phone'] ?? '';
    $_SESSION['user_role'] = $user['role'] ?? 'user';

    // Update streak
    $today = date('Y-m-d');
    $last_login = $user['last_login_date'] ?? null;
    $current = (int)($user['current_streak'] ?? 0);
    $longest = (int)($user['longest_streak'] ?? 0);
    $coins = (float)($user['coins_balance'] ?? 0);
    
    if ($last_login !== $today) {
        if ($last_login === date('Y-m-d', strtotime('-1 day'))) {
            $current++;
        } else {
            $current = 1;
        }
        if ($current > $longest) { $longest = $current; }
        
        $reward = 1;
        if ($current % 7 === 0) { $reward += 5; }
        $coins += $reward;
        
        $pdo->prepare("UPDATE users SET current_streak=?, longest_streak=?, last_login_date=?, coins_balance=? WHERE id=?")
            ->execute([$current, $longest, $today, $coins, $user['id']]);
            
        $_SESSION['streak_reward_msg'] = "🔥 Day $current Streak! You earned $reward Fast Coin(s).";
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Biometric authentication successful',
        'user_name' => $user['name'] ?? 'User',
        'redirect_url' => '/user/dashboard.php'
    ]);
    exit;

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    exit;
}
