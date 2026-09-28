<?php
// =========================================================================
// user/missions.php  –  Gamified Customer Loyalty Hub
// =========================================================================
session_start();
if (!isset($_SESSION['user_id'])) { 
    header('Location: /user/login.php'); 
    exit; 
}
require_once __DIR__ . '/../config.php';

$userId = (int)$_SESSION['user_id'];
$u = $pdo->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
$u->execute([':id' => $userId]);
$user = $u->fetch();

if (!$user) {
    header('Location: /user/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'claim_streak') {
    $res = claimDailyStreakReward($pdo, $userId);
    header('Content-Type: application/json');
    echo json_encode($res);
    exit;
}

// Calculate actual real-time user metrics
$purchase_count = 0;
try {
    $p_stmt = $pdo->prepare("SELECT COUNT(*) FROM partner_orders WHERE customer_id = ? AND status = 'completed'");
    $p_stmt->execute([$userId]);
    $purchase_count = (int)$p_stmt->fetchColumn();
} catch (Exception $e) {}

$total_spent = 0.0;
try {
    $s_stmt = $pdo->prepare("SELECT COALESCE(SUM(amount_coins), 0) FROM partner_orders WHERE customer_id = ? AND status = 'completed'");
    $s_stmt->execute([$userId]);
    $total_spent = (float)$s_stmt->fetchColumn();
} catch (Exception $e) {}

$ref_count = 0;
try {
    if (!empty($user['ref_code'])) {
        $r_stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE ref_by = ?");
        $r_stmt->execute([$user['ref_code']]);
        $ref_count = (int)$r_stmt->fetchColumn();
    }
} catch (Exception $e) {}

// Define Available Customer Loyalty Missions
$available_missions = [
    'first_purchase' => [
        'title' => 'First Purchase',
        'desc' => 'Complete your very first order on Fast Site.',
        'goal' => 1,
        'progress' => $purchase_count,
        'reward' => 50,
        'icon' => '🛍️',
        'color' => '#00e676',
        'action_url' => '/home.php',
        'action_label' => '🛍️ Shop Now'
    ],
    'refer_5' => [
        'title' => 'Bronze Affiliate',
        'desc' => 'Refer 5 active members to the Fast Site marketplace.',
        'goal' => 5,
        'progress' => $ref_count,
        'reward' => 100,
        'icon' => '🥉',
        'color' => '#cd7f32',
        'action_url' => '/user/dashboard.php#tab-affiliate',
        'action_label' => '🤝 Share Link'
    ],
    'big_spender' => [
        'title' => 'Big Spender',
        'desc' => 'Spend a total of 5,000 Coins or more on services & products.',
        'goal' => 5000,
        'progress' => $total_spent,
        'reward' => 200,
        'icon' => '💎',
        'color' => '#2196f3',
        'action_url' => '/home.php',
        'action_label' => '💎 Explore Items'
    ],
    'refer_25' => [
        'title' => 'Silver Affiliate',
        'desc' => 'Refer 25 friends to join the Fast Site ecosystem.',
        'goal' => 25,
        'progress' => $ref_count,
        'reward' => 500,
        'icon' => '🥈',
        'color' => '#c0c0c0',
        'action_url' => '/user/dashboard.php#tab-affiliate',
        'action_label' => '🤝 Invite Friends'
    ]
];

// Fetch User's Claimed Progress from user_missions table
$user_missions = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM user_missions WHERE user_id = ?");
    $stmt->execute([$userId]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $user_missions[$row['mission_key']] = $row;
    }
} catch (Exception $e) {}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Loyalty Hub — Fast Site</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&family=Oswald:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/user.css?v=<?= time() ?>">
    <style>
        :root {
            --bg: #080911;
            --surface: #121420;
            --surface-hover: #1c1f30;
            --brand: #2196f3;
            --gold: #fcb900;
            --green: #00e676;
            --text: #ffffff;
            --muted: #8b92a5;
        }
        
        .hero-card {
            background: linear-gradient(135deg, rgba(33, 150, 243, 0.12), rgba(252, 185, 0, 0.08));
            border: 1px solid rgba(252, 185, 0, 0.3);
            border-radius: 20px;
            padding: 2.5rem 1.5rem;
            text-align: center;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0,0,0,0.5);
        }
        .hero-card::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
            background: linear-gradient(90deg, var(--brand), var(--gold));
        }
        .hero-card h2 { font-size: 1.6rem; font-weight: 900; margin-bottom: 0.5rem; color: #fff; }
        .hero-card p { color: var(--muted); font-size: 0.95rem; line-height: 1.5; margin-bottom: 1.5rem; max-width: 480px; margin-left: auto; margin-right: auto; }
        
        .coin-balance {
            display: inline-flex; align-items: center; justify-content: center;
            gap: 8px; background: rgba(0,0,0,0.6); padding: 0.8rem 1.8rem;
            border-radius: 50px; border: 1px solid rgba(252, 185, 0, 0.4);
            font-size: 1.5rem; font-weight: 900; color: var(--gold);
            box-shadow: 0 0 20px rgba(252, 185, 0, 0.2);
        }
        
        .mission-list { display: flex; flex-direction: column; gap: 1.2rem; }
        
        .mission-card {
            background: rgba(20, 20, 31, 0.85);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 16px;
            padding: 1.5rem;
            position: relative;
            transition: all 0.2s;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }
        .mission-card:hover { transform: translateY(-2px); border-color: rgba(33, 150, 243, 0.4); }
        .mission-card.completed { border-color: rgba(0, 230, 118, 0.4); background: rgba(0, 230, 118, 0.03); }
        
        .mission-header { display: flex; align-items: flex-start; gap: 1rem; margin-bottom: 1rem; }
        .mission-icon {
            width: 52px; height: 52px; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.8rem; background: rgba(255,255,255,0.05);
            flex-shrink: 0;
        }
        .mission-card.completed .mission-icon { background: rgba(0, 230, 118, 0.15); filter: drop-shadow(0 0 10px rgba(0,230,118,0.4)); }
        
        .mission-info h3 { font-size: 1.15rem; font-weight: 800; margin-bottom: 0.3rem; color: #fff; }
        .mission-info p { font-size: 0.85rem; color: var(--muted); line-height: 1.4; }
        
        .mission-reward {
            background: rgba(252, 185, 0, 0.15); color: var(--gold);
            padding: 4px 12px; border-radius: 50px; font-size: 0.85rem; font-weight: 800;
            border: 1px solid rgba(252, 185, 0, 0.3); display: inline-flex; align-items: center; gap: 4px;
        }
        
        .progress-container { margin-top: 1rem; }
        .progress-bar { height: 8px; background: rgba(255,255,255,0.1); border-radius: 50px; overflow: hidden; position: relative; }
        .progress-fill { height: 100%; border-radius: 50px; transition: width 0.5s ease; }
        .progress-text { display: flex; justify-content: space-between; font-size: 0.75rem; color: var(--muted); margin-top: 6px; font-weight: 600; text-transform: uppercase; }
        
        .claim-btn {
            background: linear-gradient(135deg, #00e676, #00bfa5);
            color: #000;
            border: none;
            padding: 0.6rem 1.4rem;
            border-radius: 8px;
            font-weight: 800;
            font-size: 0.85rem;
            cursor: pointer;
            box-shadow: 0 0 15px rgba(0, 230, 118, 0.4);
            animation: pulse 2s infinite;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .action-link-btn {
            background: rgba(33, 150, 243, 0.15);
            color: var(--brand);
            border: 1px solid var(--brand);
            padding: 0.5rem 1.2rem;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.8rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }
        .action-link-btn:hover {
            background: var(--brand);
            color: #fff;
        }

        @keyframes pulse {
            0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(0, 230, 118, 0.7); }
            70% { transform: scale(1.03); box-shadow: 0 0 0 8px rgba(0, 230, 118, 0); }
            100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(0, 230, 118, 0); }
        }
    </style>
</head>
<body class="dashboard-mode">

<?php include __DIR__ . '/../includes/user_sidebar.php'; ?>

<div class="dashboard-container" style="padding-top: 85px; max-width: 750px; margin: 0 auto; padding-bottom: 5rem;">
    
    <div style="margin-bottom: 1.5rem;">
        <a href="/user/dashboard.php" class="btn" style="background:rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.15); color:#fff; width:auto; padding:0.5rem 1.2rem; font-size:0.85rem; text-decoration:none;">
            ← Back to Dashboard
        </a>
    </div>

    <div class="hero-card">
        <h2>🏆 Customer Loyalty Hub</h2>
        <p>Complete fun missions like ordering products and inviting friends to earn Fast Site Coins!</p>
        <div class="coin-balance" id="top-coin-balance">
            🪙 <?= number_format($user['coins_balance'] ?? 0, 0) ?>
        </div>
    </div>

    <!-- Phase 69: Daily Login Streak Card -->
    <?php
    $streak = (int)($user['daily_streak_count'] ?? 0);
    $last_checkin = $user['last_checkin_date'] ?? '';
    $today = date('Y-m-d');
    $claimed_today = ($last_checkin === $today);
    ?>
    <div class="mission-card" style="margin-bottom: 2rem; background: linear-gradient(135deg, rgba(252,185,0,0.1), rgba(20,20,31,0.9)); border: 1px solid rgba(252,185,0,0.3);">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h3 style="color:var(--gold); font-size: 1.2rem; margin:0 0 0.4rem 0;">🔥 <?= $streak ?> Day<?= $streak !== 1 ? 's' : '' ?> Streak</h3>
                <p style="color:#94a3b8; font-size: 0.85rem; margin:0;">Log in daily to earn coins. 7-day streaks get a 50 coin bonus!</p>
            </div>
            <div>
                <?php if ($claimed_today): ?>
                    <button class="btn" style="background: rgba(0,230,118,0.15); color: #00e676; border: 1px solid #00e676; cursor: default;">✅ Claimed Today</button>
                <?php else: ?>
                    <button id="btn-claim-streak" class="claim-btn">⚡ Claim Today's Coin Reward</button>
                <?php endif; ?>
            </div>
        </div>
        
        <div style="margin-top: 1.5rem; display: flex; gap: 8px; align-items: center; justify-content: space-between;">
            <?php for($i=1; $i<=7; $i++): 
                $active = (($streak % 7) >= $i) || ($streak > 0 && ($streak % 7) == 0); 
                $today_dot = (!$claimed_today && ($streak % 7) + 1 == $i);
                
                $bg = $active ? 'var(--gold)' : 'rgba(255,255,255,0.1)';
                $boxShadow = $active ? '0 0 10px rgba(252,185,0,0.5)' : 'none';
                if ($today_dot) {
                    $bg = 'rgba(252,185,0,0.3)';
                    $boxShadow = '0 0 8px rgba(252,185,0,0.5) inset';
                }
            ?>
            <div style="flex:1; height: 8px; background: <?= $bg ?>; border-radius: 4px; box-shadow: <?= $boxShadow ?>;"></div>
            <?php endfor; ?>
        </div>
        <div style="display:flex; justify-content:space-between; margin-top:6px; font-size:0.75rem; color:#64748b; font-weight:700;">
            <span>Day 1</span>
            <span style="color:var(--gold);">Day 7 (Bonus!)</span>
        </div>
    </div>
    
    <h3 style="margin-bottom: 1.2rem; font-size: 1rem; color: var(--muted); text-transform: uppercase; letter-spacing: 1px;">
        Available Missions & Rewards
    </h3>
    
    <div class="mission-list">
        <?php foreach($available_missions as $key => $mission): 
            $is_claimed = isset($user_missions[$key]) && (bool)$user_missions[$key]['is_completed'];
            $current_val = $mission['progress'];
            $goal_val = $mission['goal'];
            $pct = min(100, round(($current_val / $goal_val) * 100));
            $can_claim = !$is_claimed && ($current_val >= $goal_val);
        ?>
        <div class="mission-card <?= $is_claimed ? 'completed' : '' ?>">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1rem;">
                <div class="mission-header" style="margin-bottom:0; flex:1;">
                    <div class="mission-icon"><?= $mission['icon'] ?></div>
                    <div class="mission-info">
                        <h3><?= htmlspecialchars($mission['title']) ?></h3>
                        <p><?= htmlspecialchars($mission['desc']) ?></p>
                    </div>
                </div>
                <div class="mission-reward">🪙 +<?= $mission['reward'] ?> Coins</div>
            </div>
            
            <div class="progress-container">
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?= $pct ?>%; background: <?= $mission['color'] ?>;"></div>
                </div>
                <div class="progress-text">
                    <span>Progress: <?= number_format($current_val) ?> / <?= number_format($goal_val) ?></span>
                    <span><?= $pct ?>%</span>
                </div>
            </div>

            <div style="margin-top: 1.2rem; display: flex; justify-content: flex-end; align-items: center; gap: 0.8rem;">
                <?php if ($is_claimed): ?>
                    <span style="font-size:0.8rem; font-weight:800; color:#00e676; background:rgba(0,230,118,0.1); padding:0.4rem 1rem; border-radius:6px;">
                        Claimed ✅
                    </span>
                <?php elseif ($can_claim): ?>
                    <button onclick="claimLoyaltyReward('<?= $key ?>')" class="claim-btn">
                        🎁 Claim +<?= $mission['reward'] ?> Coins!
                    </button>
                <?php else: ?>
                    <a href="<?= $mission['action_url'] ?>" class="action-link-btn">
                        <?= $mission['action_label'] ?> ➔
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnStreak = document.getElementById('btn-claim-streak');
    if(btnStreak) {
        btnStreak.addEventListener('click', function() {
            btnStreak.innerHTML = '⚡ Claiming...';
            btnStreak.disabled = true;
            
            let fd = new FormData();
            fd.append('action', 'claim_streak');
            fetch('missions.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                if(res.success) {
                    alert(`🎉 Success! You earned ${res.reward} Coins. Streak: Day ${res.streak}`);
                    location.reload();
                } else {
                    alert('❌ ' + res.message);
                    btnStreak.innerHTML = '⚡ Claim Today\'s Coin Reward';
                    btnStreak.disabled = false;
                }
            })
            .catch(err => {
                alert('Network Error.');
                btnStreak.innerHTML = '⚡ Claim Today\'s Coin Reward';
                btnStreak.disabled = false;
            });
        });
    }
});

function claimLoyaltyReward(missionKey) {
    let fd = new FormData();
    fd.append('mission_key', missionKey);
    fetch('/api/claim_mission_reward.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
        alert(res.message);
        if(res.success) location.reload();
    })
    .catch(err => alert('Error claiming reward. Please try again.'));
}
</script>

</body>
</html>
