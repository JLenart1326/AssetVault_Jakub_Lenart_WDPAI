<?php
require_once('../auth.php');
require_once('../config.php');
require_once('../db.php');

$typeFilter = $_GET['type'] ?? 'All';

// Pobierz wszystkie assety
$stmt = $pdo->prepare("SELECT * FROM assets ORDER BY created_at DESC");
$stmt->execute();
$assets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Dołącz miniatury do każdego assetu
foreach ($assets as &$asset) {
    $stmtImg = $pdo->prepare("SELECT image_path FROM asset_images WHERE asset_id = :id ORDER BY id ASC");
    $stmtImg->execute([':id' => $asset['id']]);
    $asset['images'] = $stmtImg->fetchAll(PDO::FETCH_ASSOC);
}
unset($asset); // rozłącz referencję
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Assets - AssetVault</title>
    <link rel="stylesheet" href="../styles/assets.css">
    <link rel="stylesheet" href="../styles/asset_list.css">
</head>
<body>

<header class="assets-header">
    <div class="assets-header-left">
        <img src="../images/logo-black.png" alt="AssetVault Logo" class="logo-icon">
        <div class="logo">AssetVault</div>
    </div>
    <div class="assets-header-right">
        <a href="upload.php?from=assets" class="upload-button"><img src="../images/upload-icon.png" class="upload-icon">Upload</a>
        <a href="dashboard.php?from=assets">
            <img src="../images/user.png" alt="User Icon" class="user-icon">
        </a>
    </div>
</header>

<main class="assets-main">
    <div class="filter-bar">
        <a href="?type=All" class="filter-btn <?= $typeFilter === 'All' ? 'active' : '' ?>">All</a>
        <a href="?type=Model 3D" class="filter-btn <?= $typeFilter === 'Model 3D' ? 'active' : '' ?>">3D Model</a>
        <a href="?type=Audio" class="filter-btn <?= $typeFilter === 'Audio' ? 'active' : '' ?>">Audio</a>
        <a href="?type=Texture" class="filter-btn <?= $typeFilter === 'Texture' ? 'active' : '' ?>">Textures</a>
    </div>

    <div class="assets-grid">
        <?php $source = 'assets'; ?>
        <?php foreach ($assets as $asset): ?>
            <?php if ($typeFilter === 'All' || $asset['type'] === $typeFilter): ?>
                <?php include('partials/asset_list.php'); ?>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</main>

<footer class="mobile-footer">
    <a href="?type=All" class="mobile-nav-item <?= $typeFilter === 'All' ? 'active' : '' ?>"><span>All</span></a>
    <a href="?type=Model 3D" class="mobile-nav-item <?= $typeFilter === 'Model 3D' ? 'active' : '' ?>"><span>Models</span></a>
    <a href="?type=Audio" class="mobile-nav-item <?= $typeFilter === 'Audio' ? 'active' : '' ?>"><span>Audio</span></a>
    <a href="?type=Texture" class="mobile-nav-item <?= $typeFilter === 'Texture' ? 'active' : '' ?>"><span>Textures</span></a>
</footer>

</body>
</html>
