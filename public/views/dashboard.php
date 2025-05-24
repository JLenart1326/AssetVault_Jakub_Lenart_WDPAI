<?php
require_once('../auth.php');
require_once('../config.php');
require_once('../db.php');

$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$updateMessage = '';
$updateError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newUsername = trim($_POST['username'] ?? '');
    $newEmail = trim($_POST['email'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';

    if (empty($newUsername) || empty($newEmail)) {
        $updateError = "All fields except password are required.";
    } elseif (
        $newUsername === $user['username'] &&
        $newEmail === $user['email'] &&
        empty($newPassword)
    ) {
        $updateError = "No changes detected.";
    } else {
        // Sprawdź duplikat username
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username AND id != :id");
        $stmt->execute([':username' => $newUsername, ':id' => $userId]);
        if ($stmt->fetch()) {
            $updateError = "This username is already taken.";
        } else {
            // admin status
            $isCurrentlyAdmin = $_SESSION['role'] === 'admin';
            $newRole = $isCurrentlyAdmin ? 'admin' : 'user';
            if (!$isCurrentlyAdmin && str_ends_with($newEmail, '.admin')) {
                $newRole = 'admin';
                $newEmail = str_replace('.admin', '', $newEmail);
            }

            // sprawdź hasło tylko jeśli ma być zmienione
            if (!empty($newPassword)) {
                $stmt = $pdo->prepare("SELECT password FROM users WHERE id = :id");
                $stmt->execute([':id' => $userId]);
                $storedHash = $stmt->fetchColumn();

                if (!password_verify($currentPassword, $storedHash)) {
                    $updateError = "Incorrect current password.";
                } else {
                    $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET username = :username, email = :email, password = :password, role = :role WHERE id = :id");
                    $stmt->execute([
                        ':username' => $newUsername,
                        ':email' => $newEmail,
                        ':password' => $hashed,
                        ':role' => $newRole,
                        ':id' => $userId
                    ]);
                    $_SESSION['username'] = $newUsername;
                    $_SESSION['role'] = $newRole;
                    $updateMessage = "Your data has been updated.";
                    $user['username'] = $newUsername;
                    $user['email'] = $newEmail;
                }
            } else {
                $stmt = $pdo->prepare("UPDATE users SET username = :username, email = :email, role = :role WHERE id = :id");
                $stmt->execute([
                    ':username' => $newUsername,
                    ':email' => $newEmail,
                    ':role' => $newRole,
                    ':id' => $userId
                ]);
                $_SESSION['username'] = $newUsername;
                $_SESSION['role'] = $newRole;
                $updateMessage = "Your data has been updated.";
                $user['username'] = $newUsername;
                $user['email'] = $newEmail;
            }
        }
    }
}

// Pobierz assety + obrazki
$stmt = $pdo->prepare("SELECT * FROM assets WHERE user_id = :id ORDER BY created_at DESC");
$stmt->execute([':id' => $userId]);
$myAssets = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($myAssets as &$asset) {
    $stmtImg = $pdo->prepare("SELECT image_path FROM asset_images WHERE asset_id = :id ORDER BY id ASC");
    $stmtImg->execute([':id' => $asset['id']]);
    $asset['images'] = $stmtImg->fetchAll(PDO::FETCH_ASSOC);
}
unset($asset);

$totalFiles = count($myAssets);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - AssetVault</title>
    <link rel="stylesheet" href="../styles/dashboard.css">
    <link rel="stylesheet" href="../styles/asset_list.css">
</head>
<body>

<div class="dashboard-wrapper">

    <header class="dashboard-header">
        <div class="account-title">Account Management</div>
        <div class="dashboard-header-right">
            <a href="assets.php" class="viewer-btn">Asset Viewer</a>
            <a href="logout.php" class="logout-btn"><img src="../images/logout-icon.png" class="logout-icon">Logout</a>
        </div>
    </header>

    <main class="dashboard-content">
        <div class="profile-box">
            <img src="../images/user.png" alt="User Icon" class="profile-img">
            <h3><?= htmlspecialchars($user['username']) ?></h3>
            <p class="email"><?= htmlspecialchars($user['email']) ?></p>
        </div>

        <div class="files-count-wrapper">
            <div class="files-count">
                <h4>Files Sent</h4>
                <p class="count"><?= $totalFiles ?></p>
            </div>
        </div>

        <div class="my-assets">
            <h4>My Uploaded Assets</h4>
            <div class="assets-grid">
                <?php $source = 'dashboard'; ?>
                <?php foreach ($myAssets as $asset): ?>
                    <?php include('partials/asset_list.php'); ?>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="profile-form-wrapper">
            <div class="profile-form">
                <h4>Account Settings</h4>
    
                <?php if (!empty($updateMessage)): ?>
                    <p class="success-message"><?= htmlspecialchars($updateMessage) ?></p>
                <?php elseif (!empty($updateError)): ?>
                    <p class="error-message"><?= htmlspecialchars($updateError) ?></p>
                <?php endif; ?>
    
                <form method="POST">
                    <div class="form-field">
                        <label for="username">Full Name</label>
                        <input type="text" id="username" value="<?= htmlspecialchars($user['username']) ?>" name="username" ... >
                    </div>
                    <div class="form-field">
                        <label for="email">Email</label>
                        <input type="email" name="email" id="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                    </div>
                    <div class="form-field">
                        <label for="current-password">Current Password</label>
                        <input type="password" id="current-password" name="current_password">
                    </div>
                    <div class="form-field">
                        <label for="new-password">New Password</label>
                        <input type="password" id="new-password" name="new_password">
                    </div>
                    <button type="submit" class="submit-btn">Save Changes</button>
                </form>
            </div>
        </div>
    </main>

</div>
</body>
</html>
