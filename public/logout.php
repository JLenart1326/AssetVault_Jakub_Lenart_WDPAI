<?php
session_start();

// Usunięcie wszystkich danych z sesji
session_unset();

// Zniszczenie sesji
session_destroy();

// Przekierowanie do strony logowania
header('Location: views/login.php');
exit();
