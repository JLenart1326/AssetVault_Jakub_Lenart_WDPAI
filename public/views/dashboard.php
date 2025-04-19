<?php
require_once('../auth.php');
require_once('../config.php');
require_once('../db.php');

$userId = $_SESSION['user_id'];
$isAdmin = $_SESSION['role'] === 'admin';

$updateMessage = "";
$errors = [];

// Obsługa formularza aktualizacji danych użytkownika
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_type']) && $_POST['form_type'] === 'update_user') {
    $newUsername = trim($_POST['username']);
    $newEmail = trim($_POST['email']);
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';

    $newRole = 'user';
    if (str_ends_with($newEmail, '.admin')) {
        $newRole = 'admin';
        $newEmail = str_replace('.admin', '', $newEmail);
    }

    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username AND id != :id");
    $stmt->execute([':username' => $newUsername, ':id' => $userId]);
    if ($stmt->fetch()) {
        $updateMessage = "Taka nazwa użytkownika już istnieje.";
    } else {
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = :id");
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch();

        if (!empty($newPassword)) {
            if (password_verify($currentPassword, $user['password'])) {
                $hashedNewPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET username = :username, email = :email, password = :password, role = :role WHERE id = :id");
                $stmt->execute([
                    ':username' => $newUsername,
                    ':email' => $newEmail,
                    ':password' => $hashedNewPassword,
                    ':role' => $newRole,
                    ':id' => $userId
                ]);
                $_SESSION['username'] = $newUsername;
                $_SESSION['role'] = $newRole;
                $updateMessage = "Dane zostały zaktualizowane.";
            } else {
                $updateMessage = "Nieprawidłowe obecne hasło.";
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
            $updateMessage = "Dane zostały zaktualizowane.";
        }
    }
}

$stmt = $pdo->prepare("SELECT username, email, role FROM users WHERE id = :id");
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch();

$stmt = $pdo->prepare("
    SELECT a.*, u.username 
    FROM assets a 
    JOIN users u ON a.user_id = u.id 
    WHERE a.user_id = :id
");
$stmt->execute([':id' => $userId]);
$userAssets = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - AssetVault</title>
</head>
<body>
    <h2><?= htmlspecialchars($user['username']) ?> (<?= htmlspecialchars($user['role']) ?>)</h2>
    <p><?= htmlspecialchars($user['email']) ?></p>
    <h4>Przesłane pliki: <?= count($userAssets) ?></h4>

    <?php if ($updateMessage): ?>
        <p><strong><?= htmlspecialchars($updateMessage) ?></strong></p>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="form_type" value="update_user">
        <label>Nowa nazwa użytkownika:<br>
            <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
        </label><br><br>

        <label>Nowy e-mail:<br>
            <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
        </label><br><br>

        <label>Obecne hasło (wymagane przy zmianie):<br>
            <input type="password" name="current_password">
        </label><br><br>

        <label>Nowe hasło:<br>
            <input type="password" name="new_password">
        </label><br><br>

        <button type="submit">Zapisz zmiany</button>
    </form>

    <hr>

    <h3>Twoje assety:</h3>
    <ul>
        <?php
            $assets = $userAssets;
            include(__DIR__ . '/partials/asset_list.php');
        ?>
    </ul>

    <hr>

    <a href="upload.php?from=dashboard">Prześlij nowy asset</a><br>
    <a href="assets.php">Przeglądaj wszystkie assety</a><br>
    <a href="logout.php">Wyloguj się</a>
</body>
</html>
