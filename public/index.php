<?php
session_start();

// Sprawdzenie, czy użytkownik jest zalogowany
if (isset($_SESSION['user_id'])) {
    // Użytkownik zalogowany – przekierowanie do dashboardu
    header('Location: views/dashboard.php');
    exit();
} else {
    // Użytkownik niezalogowany – przekierowanie do logowania
    header('Location: views/login.php');
    exit();
}
