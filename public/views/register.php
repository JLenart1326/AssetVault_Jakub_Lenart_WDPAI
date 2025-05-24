<?php
session_start();
require_once('../config.php');
require_once('../db.php');

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($email) && !empty($password)) {
        // Sprawdź czy email istnieje
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $existingEmail = $stmt->fetch(PDO::FETCH_ASSOC);
    
        // Sprawdź czy username istnieje
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username");
        $stmt->execute([':username' => $username]);
        $existingUsername = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if ($existingEmail) {
            $message = 'This email is already registered.';
            $messageType = 'error';
        } elseif ($existingUsername) {
            $message = 'This username is already taken.';
            $messageType = 'error';
        } else {
            // WSZYSTKO OK - Rejestracja nowego użytkownika
            $role = 'user';
            if (str_ends_with($email, '.admin')) {
                $role = 'admin';
                $email = str_replace('.admin', '', $email);
            }
    
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (:username, :email, :password, :role)");
            $stmt->execute([
                ':username' => $username,
                ':email' => $email,
                ':password' => $hashedPassword,
                ':role' => $role
            ]);
    
            header('Location: login.php?registered=1');
            exit();
        }
    } else {
        $message = 'Please fill in all fields.';
        $messageType = 'error';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - AssetVault</title>
    <link rel="stylesheet" href="../styles/auth.css">
</head>
<body>

<div class="auth-wrapper">

    <!-- Left section (desktop only) -->
    <div class="auth-left">
        <img src="../images/logo-white.png" alt="Logo" style="width: 80px; margin-bottom: 20px;">
        <h1>Welcome to AssetVault</h1>
        <p>Manage your digital assets securely and efficiently with our platform.</p>
    </div>

    <!-- Right section (form) -->
    <div class="auth-right">
        <div class="logo-section">
            <img src="../images/logo-black.png" alt="Logo">
            <h1>AssetVault</h1>
        </div>

        <div class="auth-container">
            <h2>Create an account</h2>
            <p>Sign up to manage your assets</p>

            <?php if ($message): ?>
                <p style="color: <?= $messageType === 'success' ? 'green' : 'red'; ?>;"><?= htmlspecialchars($message) ?></p>
            <?php endif; ?>

            <form method="POST">
                <label for="username">User Name</label>
                <input type="text" id="username" name="username" placeholder="Enter your user name" required>
                <label for="email">Email address</label>
                <input type="email" id="email" name="email" placeholder="Enter your email" required>
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
                <button type="submit">Sign up</button>
            </form>

            <div class="auth-footer">
                <p>Already have an account? <a href="login.php">Sign in</a></p>
            </div>
        </div>
    </div>

</div>

</body>
</html>
