<?php
require_once('../auth.php');
require_once('../config.php');
require_once('../db.php');

// Obsługa filtrowania
$filter = $_GET['type'] ?? 'All';

// Pobierz wszystkie assety
$sql = "SELECT a.*, u.username FROM assets a JOIN users u ON a.user_id = u.id";
$params = [];

if ($filter && $filter !== 'All') {
    $sql .= " WHERE type = :type";
    $params[':type'] = $filter;
}

$sql .= " ORDER BY a.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$assets = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>AssetVault - Lista assetów</title>
</head>
<body>

    <div class="topbar">
        <form method="GET" style="display: inline;">
            <label for="type">Filtruj według typu: </label>
            <select name="type" id="type" onchange="this.form.submit()">
                <option value="All" <?= $filter === 'All' ? 'selected' : '' ?>>Wszystkie</option>
                <option value="Model 3D" <?= $filter === 'Model 3D' ? 'selected' : '' ?>>Model 3D</option>
                <option value="Tekstura" <?= $filter === 'Tekstura' ? 'selected' : '' ?>>Tekstura</option>
                <option value="Audio" <?= $filter === 'Audio' ? 'selected' : '' ?>>Audio</option>
            </select>
        </form>

        <button onclick="window.location.href='upload.php?from=assets'">➕ Dodaj asset</button>
        <button onclick="window.location.href='dashboard.php'">📋 Dashboard</button>
        <button onclick="window.location.href='logout.php'">🚪 Wyloguj</button>
    </div>

    <h2>Wszystkie Assety</h2>

    <?php if (count($assets) === 0): ?>
        <p>Brak assetów do wyświetlenia.</p>
    <?php endif; ?>

    <?php
        $allAssets = $assets; // np. wynik z SELECT * FROM assets ...
        include 'partials/asset_list.php';
    ?>
</body>
</html>
