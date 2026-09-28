<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Please log in first.']);
    exit;
}

require_once __DIR__ . '/../config.php';
$userId = (int)$_SESSION['user_id'];

// Fetch current user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch();

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'User not found.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $whatsapp = trim($_POST['whatsapp'] ?? '');
    $facebook = trim($_POST['facebook'] ?? '');
    $instagram = trim($_POST['instagram'] ?? '');
    $twitter = trim($_POST['twitter'] ?? '');
    $youtube = trim($_POST['youtube'] ?? '');
    $newPassword = $_POST['password'] ?? '';

    if (!$name) {
        echo json_encode(['success' => false, 'message' => 'Full Name is required.']);
        exit;
    }

    $updateFields = [
        'name = :name',
        'email = :email',
        'whatsapp = :whatsapp',
        'facebook = :facebook',
        'instagram = :instagram',
        'twitter = :twitter',
        'youtube = :youtube'
    ];

    $params = [
        ':name' => $name,
        ':email' => $email ?: null,
        ':whatsapp' => $whatsapp ?: null,
        ':facebook' => $facebook ?: null,
        ':instagram' => $instagram ?: null,
        ':twitter' => $twitter ?: null,
        ':youtube' => $youtube ?: null,
        ':id' => $userId
    ];

    // Password Update
    if ($newPassword !== '') {
        if (strlen($newPassword) < 6) {
            echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.']);
            exit;
        }
        $updateFields[] = 'password_hash = :password_hash';
        $params[':password_hash'] = password_hash($newPassword, PASSWORD_BCRYPT);
    }

    // Handle Profile Picture Upload
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $fileTmp = $_FILES['profile_pic']['tmp_name'];
        $fileName = $_FILES['profile_pic']['name'];
        $fileSize = $_FILES['profile_pic']['size'];
        $fileType = $_FILES['profile_pic']['type'];

        $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExts)) {
            echo json_encode(['success' => false, 'message' => 'Invalid file format. Allowed: JPG, PNG, GIF, WEBP.']);
            exit;
        }

        // Limit size to 5MB
        if ($fileSize > 5 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'File size exceeds 5MB limit.']);
            exit;
        }

        // Create target directory if it doesn't exist
        $uploadDir = '../uploads/profiles/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $newFileName = 'profile_' . $userId . '_' . time() . '.' . $ext;
        $destPath = $uploadDir . $newFileName;

        if (move_uploaded_file($fileTmp, $destPath)) {
            // Delete old profile picture if exists
            if ($user['profile_pic'] && file_exists('../' . $user['profile_pic'])) {
                @unlink('../' . $user['profile_pic']);
            }

            $updateFields[] = 'profile_pic = :profile_pic';
            $params[':profile_pic'] = 'uploads/profiles/' . $newFileName;
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save uploaded image.']);
            exit;
        }
    }

    // Perform database update
    $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = :id";
    try {
        $pdo->prepare($sql)->execute($params);
        echo json_encode(['success' => true, 'message' => 'Profile updated successfully!']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database update error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
