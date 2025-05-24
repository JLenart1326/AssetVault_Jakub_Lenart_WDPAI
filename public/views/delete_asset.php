<?php
require_once('../auth.php');
require_once('../config.php');
require_once('../db.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'])) {
    header('Location: assets.php');
    exit();
}

$assetId = $_POST['id'];
$returnTo = $_POST['from'] ?? 'assets';

// Sprawdź, czy asset istnieje i kto jest właścicielem
$stmt = $pdo->prepare("SELECT * FROM assets WHERE id = :id");
$stmt->execute([':id' => $assetId]);
$asset = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$asset) {
    header("Location: {$returnTo}.php");
    exit();
}

// Sprawdź, czy użytkownik może usunąć
$userId = $_SESSION['user_id'];
$isAdmin = $_SESSION['role'] === 'admin';
$isOwner = $asset['user_id'] == $userId;

if (!$isAdmin && !$isOwner) {
    header("Location: {$returnTo}.php");
    exit();
}

// Usuń miniaturki z dysku
$stmt = $pdo->prepare("SELECT image_path FROM asset_images WHERE asset_id = :id");
$stmt->execute([':id' => $assetId]);
$images = $stmt->fetchAll(PDO::FETCH_COLUMN);

foreach ($images as $imgPath) {
    $fullPath = "../" . $imgPath;
    if (file_exists($fullPath)) {
        unlink($fullPath);
    }
}

// Usuń plik główny
$mainFile = "../" . $asset['file_path'];
if (file_exists($mainFile)) {
    unlink($mainFile);
}

// Usuń wpisy z bazy
$stmt = $pdo->prepare("DELETE FROM asset_images WHERE asset_id = :id");
$stmt->execute([':id' => $assetId]);

$stmt = $pdo->prepare("DELETE FROM assets WHERE id = :id");
$stmt->execute([':id' => $assetId]);

// Powrót
header("Location: {$returnTo}.php");
exit();
