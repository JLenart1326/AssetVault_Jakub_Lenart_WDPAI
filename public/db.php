<?php
// Połączenie z bazą danych PostgreSQL (działa w Dockerze)

// Dane potrzebne do połączenia
$host = 'db'; // nazwa usługi z docker-compose.yml
$dbname = 'assetvault'; // nazwa bazy danych
$user = 'assetuser';    // użytkownik bazy
$pass = 'assetpass';    // hasło do bazy

// Tworzymy połączenie z bazą
$pdo = new PDO("pgsql:host=$host;dbname=$dbname", $user, $pass);

// Ustawiamy, żeby błędy były widoczne
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);

// Teraz zmienna $pdo możemy używać do zapytań SQL w innych plikach
?>
