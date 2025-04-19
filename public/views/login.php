<?php
session_start();
require_once('../config.php');
require_once('../db.php');

// Jeśli użytkownik już jest zalogowany – przekieruj do dashboardu
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$message = '';
$messageType = '';

if (isset($_GET['registered']) && $_GET['registered'] == 1) {
    $message = 'Rejestracja zakończona sukcesem. Możesz się teraz zalogować.';
    $messageType = 'success';
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($email) && !empty($password)) {
        // Pobranie użytkownika o podanym adresie e-mail
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // Zalogowanie użytkownika – zapisanie danych w sesji
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // Przekierowanie na stronę dashboard
            header('Location: dashboard.php');
            exit();
        } else {
            $message = 'Nieprawidłowy e-mail lub hasło.';
        }
    } else {
        $message = 'Uzupełnij wszystkie pola.';
    }
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Logowanie - AssetVault</title>
</head>
<body>
    <h2>Logowanie</h2>

    <?php if (!empty($message)): ?>
    <p style="color: <?= $messageType === 'success' ? 'green' : 'red' ?>;">
        <?= htmlspecialchars($message) ?>
    </p>
<?php endif; ?>

    <form method="POST" action="">
        <label>E-mail:<br>
            <input type="email" name="email" required>
        </label><br><br>

        <label>Hasło:<br>
            <input type="password" name="password" required>
        </label><br><br>

        <button type="submit">Zaloguj się</button>
    </form>

    <p>Nie masz konta? <a href="register.php">Zarejestruj się</a></p>
</body>
</html>
