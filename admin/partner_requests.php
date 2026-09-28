<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/../config.php';

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = $_POST['request_id'] ?? null;
    $action = $_POST['action'] ?? null;
    
    if ($request_id && in_array($action, ['approve', 'reject'])) {
        // Get the request
        $req_stmt = $pdo->prepare("SELECT user_id FROM partner_requests WHERE id = ?");
        $req_stmt->execute([$request_id]);
        $req = $req_stmt->fetch();
        
        if ($req) {
            $user_id = $req['user_id'];
            if ($action === 'approve') {
                try { $pdo->exec("ALTER TABLE partners ADD COLUMN status VARCHAR(50) DEFAULT 'pending'"); } catch (Exception $e) {}
                try { $pdo->exec("ALTER TABLE partners ADD COLUMN is_hidden TINYINT(1) DEFAULT 0"); } catch (Exception $e) {}
                $pdo->prepare("UPDATE partner_requests SET status = 'approved' WHERE id = ?")->execute([$request_id]);
                $pdo->prepare("UPDATE users SET role = 'partner' WHERE id = ?")->execute([$user_id]);
                $pdo->prepare("UPDATE partners SET status = 'approved', is_hidden = 0 WHERE user_id = ?")->execute([$user_id]);
                try {
                    $pdo->prepare("INSERT INTO user_notifications (user_id, title, message, type) VALUES (?, ?, ?, 'partner')")
                        ->execute([$user_id, 'Partner Request Approved! 🎉', 'Your partner application has been approved! You can now manage your shop from the Partner Hub.', 'system']);
                } catch (Exception $eNotif) {}
                $msg = "Partner request approved! User is now a partner.";
            } else if ($action === 'reject') {
                $pdo->prepare("UPDATE partner_requests SET status = 'rejected' WHERE id = ?")->execute([$request_id]);
                $pdo->prepare("UPDATE partners SET status = 'rejected' WHERE user_id = ?")->execute([$user_id]);
                $msg = "Partner request rejected.";
            }
        }
    }
}

// Fetch all pending requests
$pending_stmt = $pdo->query("SELECT pr.*, u.name, u.phone, u.email, u.address, u.profile_pic, u.nid, u.etin, u.passport, u.driving_license 
                             FROM partner_requests pr 
                             JOIN users u ON pr.user_id = u.id 
                             WHERE pr.status = 'pending' 
                             ORDER BY pr.created_at DESC");
$pending_requests = $pending_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch recent processed requests (last 20)
$processed_stmt = $pdo->query("SELECT pr.*, u.name, u.phone 
                               FROM partner_requests pr 
                               JOIN users u ON pr.user_id = u.id 
                               WHERE pr.status != 'pending' 
                               ORDER BY pr.created_at DESC LIMIT 20");
$processed_requests = $processed_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Partner Requests — Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>
    <div class="topbar">
        <div><strong>FAST SITE ADMIN</strong></div>
        <a href="dashboard.php">← Back to Dashboard</a>
    </div>

    <div class="container">
        <?php if ($msg): ?><div class="msg"><?= $msg ?></div><?php endif; ?>
        
        <div class="card">
            <h2>Pending Partner Requests</h2>
            <?php if (empty($pending_requests)): ?>
                <p style="color:#9ca3af;">No pending requests at the moment.</p>
            <?php else: ?>
                <?php foreach ($pending_requests as $req): ?>
                    <div class="req-item">
                        <?php if ($req['profile_pic']): ?>
                            <img src="/<?= htmlspecialchars(ltrim($req['profile_pic'] ?? '', '/')) ?>" alt="PP" class="req-pic" onerror="this.onerror=null; this.src='data:image/svg+xml;utf8,<svg xmlns=&apos;http://www.w3.org/2000/svg&apos; viewBox=&apos;0 0 24 24&apos; fill=&apos;%23fcb900&apos;><path d=&apos;M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-3.79 1.79-3.79 4 1.79 4 3.79 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z&apos;/></svg>';">
                        <?php else: ?>
                            <div class="req-pic" style="display:flex;align-items:center;justify-content:center;font-size:2rem;background:#333;color:#fcb900;">
                                <?= strtoupper(substr($req['name'],0,1)) ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="req-info">
                            <h3><?= htmlspecialchars($req['name']) ?></h3>
                            <p><strong>Phone:</strong> <?= htmlspecialchars($req['phone']) ?></p>
                            <p><strong>Email:</strong> <?= htmlspecialchars($req['email'] ?: 'N/A') ?></p>
                            <p><strong>Address:</strong> <?= htmlspecialchars($req['address']) ?></p>
                            
                            <div class="req-docs">
                                KYC Uploaded: 
                                <?php
                                $docs = [];
                                if ($req['nid']) $docs[] = 'NID';
                                if ($req['etin']) $docs[] = 'eTIN';
                                if ($req['passport']) $docs[] = 'Passport';
                                if ($req['driving_license']) $docs[] = 'Driving License';
                                echo empty($docs) ? '<span style="color:#ff5252">None</span>' : implode(', ', $docs);
                                ?>
                            </div>
                            <p style="font-size:0.8rem; margin-top:0.5rem;">Requested on: <?= date('d M Y, h:i A', strtotime($req['created_at'])) ?></p>
                        </div>
                        
                        <div class="req-actions">
                            <form method="POST" style="margin:0;">
                                <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                <input type="hidden" name="action" value="approve">
                                <button type="submit" class="btn btn-approve" onclick="return confirm('Are you sure you want to approve this partner?');">Approve Request</button>
                            </form>
                            <form method="POST" style="margin:0;">
                                <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                <input type="hidden" name="action" value="reject">
                                <button type="submit" class="btn btn-reject" onclick="return confirm('Are you sure you want to reject this request?');">Reject Request</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>Recently Processed Requests</h2>
            <?php if (empty($processed_requests)): ?>
                <p style="color:#9ca3af;">No processed requests yet.</p>
            <?php else: ?>
                <div class="desktop-table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>User Name</th>
                                <th>Phone</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($processed_requests as $req): ?>
                                <tr>
                                    <td><?= htmlspecialchars($req['name']) ?></td>
                                    <td><?= htmlspecialchars($req['phone']) ?></td>
                                    <td class="status-<?= strtolower($req['status']) ?>"><?= ucfirst(htmlspecialchars($req['status'])) ?></td>
                                    <td style="color:#9ca3af; font-size:0.85rem;"><?= date('d M Y, h:i A', strtotime($req['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Stacked Cards -->
                <div class="mobile-cards-wrap">
                    <?php foreach ($processed_requests as $req): ?>
                    <div class="box" style="margin-bottom:1rem; background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.05); padding:1rem; border-radius:12px;">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:5px;">
                            <div style="font-weight:700; font-size:1.1rem; color:#fff;"><?= htmlspecialchars($req['name']) ?></div>
                            <span class="status-<?= strtolower($req['status']) ?>" style="padding:2px 8px; border-radius:4px; font-size:0.8rem; font-weight:bold;"><?= ucfirst(htmlspecialchars($req['status'])) ?></span>
                        </div>
                        <div style="font-size:0.9rem; color:var(--muted); margin-bottom:5px;">
                            <strong>Phone:</strong> <?= htmlspecialchars($req['phone']) ?>
                        </div>
                        <div style="font-size:0.8rem; color:var(--muted);">
                            <strong>Date:</strong> <?= date('d M Y, h:i A', strtotime($req['created_at'])) ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
