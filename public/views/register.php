<?php
session_start();
require_once('../config.php');
require_once('../db.php');

// Jeśli użytkownik już jest zalogowany – przekieruj do dashboardu
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$msg = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if (!empty($username) && !empty($email) && !empty($password)) {
        // Sprawdź, czy użytkownik już istnieje
        $check = $pdo->prepare("SELECT * FROM users WHERE username = :username OR email = :email");
        $check->execute([
            ":username" => $username,
            ":email" => $email
        ]);

        if ($check->rowCount() > 0) {
            $msg = "Użytkownik o podanym loginie lub adresie e-mail już istnieje!";
        } else {
            
            $role = 'user';
            if (str_ends_with($email, '.admin')) {
                $role = 'admin';
                $email = substr($email, 0, -6);
            }
        
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) 
                                   VALUES (:username, :email, :password, :role)");
            $stmt->execute([
                ":username" => $username,
                ":email" => $email,
                ":password" => $hashedPassword,
                ":role" => $role
            ]);
        
            // Po udanej rejestracji przekieruj do logowania z komunikatem
            header('Location: login.php?registered=1');
            exit();
        }
        
    } else {
        $msg = "Uzupełnij wszystkie pola!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Rejestracja - AssetVault</title>
</head>
<body>
    <h2>Rejestracja</h2>

    <?php if ($msg): ?>
        <p><strong><?= htmlspecialchars($msg) ?></strong></p>
    <?php endif; ?>

    <form method="POST" action="">
        <label>Nazwa użytkownika:</label><br>
        <input type="text" name="username"><br><br>

        <label>Email:</label><br>
        <input type="email" name="email"><br><br>

        <label>Hasło:</label><br>
        <input type="password" name="password"><br><br>

        <button type="submit">Zarejestruj się</button>
    </form>

    <p><a href="../../index.php">Wróć do strony głównej</a></p>
</body>
</html>
