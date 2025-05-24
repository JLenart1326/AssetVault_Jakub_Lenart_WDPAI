<?php
require_once('../auth.php');
require_once('../config.php');
require_once('../db.php');

if (!isset($_GET['id'])) {
    header("Location: assets.php");
    exit();
}

$assetId = (int)$_GET['id'];
$stmt = $pdo->prepare("SELECT a.*, u.username FROM assets a JOIN users u ON a.user_id = u.id WHERE a.id = :id");
$stmt->execute([':id' => $assetId]);
$asset = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$asset) {
    header("Location: assets.php");
    exit();
}

// Dostęp edycji/usuwania
$userId = $_SESSION['user_id'];
$isAdmin = $_SESSION['role'] === 'admin';
$isOwner = $asset['user_id'] == $userId;

$stmt = $pdo->prepare("SELECT * FROM asset_images WHERE asset_id = :id");
$stmt->execute([':id' => $assetId]);
$images = $stmt->fetchAll(PDO::FETCH_ASSOC);

$fileSizeMB = round(filesize("../" . $asset['file_path']) / 1048576, 1);
$extension = strtoupper(pathinfo($asset['file_path'], PATHINFO_EXTENSION));
$createdAt = date("Y-m-d H:i", strtotime($asset['created_at']));
$returnTo = 'assets';
if (isset($_GET['from']) && in_array($_GET['from'], ['dashboard', 'assets'])) {
    $returnTo = $_GET['from'];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($asset['name']) ?> - AssetVault</title>
    <link rel="stylesheet" href="../styles/asset.css">
</head>
<body>

<header class="header">
    <a href="<?= $returnTo ?>.php" class="back-btn">
        <img src="../images/back-arrow.png" alt="Back" class="back-icon">
    </a>
    <img src="../images/logo-black.png" alt="AssetVault Logo" class="logo-icon">
    <div class="logo">AssetVault</div>
</header>


<main class="asset-container">
    <section class="asset-header">
        <h1><?= htmlspecialchars($asset['name']) ?></h1>
        <div class="asset-meta">
            <span>Size: <?= $fileSizeMB ?> MB</span>
        </div>
        <a href="../<?= htmlspecialchars($asset['file_path']) ?>" class="download-btn" download>Download</a>
    </section>

    <hr class="asset-divider">

    <section class="asset-body">
    <?php if ($images): ?>
    <div class="carousel-wrapper">
        <button class="carousel-arrow left" onclick="showPrev()">←</button>
        <div class="carousel-content">
            <?php foreach ($images as $index => $img): ?>
                <img src="../<?= htmlspecialchars($img['image_path']) ?>" class="carousel-img <?= $index === 0 ? 'active' : '' ?>">
            <?php endforeach; ?>
        </div>
        <button class="carousel-arrow right" onclick="showNext()">→</button>
    </div>
<?php else: ?>
    <div class="carousel-wrapper">
        <button class="carousel-arrow left" disabled>←</button>
        <div class="carousel-content">
            <img src="../images/default-thumb.png" class="carousel-img active">
        </div>
        <button class="carousel-arrow right" disabled>→</button>
    </div>
<?php endif; ?>


        <div class="asset-details-right">
        <div class="asset-details">
            <div class="info-box">
                <h3>Asset Information</h3>
                <p><strong>Type:</strong> <?= htmlspecialchars($asset['type']) ?></p>
                <p><strong>Extension:</strong> <?= $extension ?></p>
                <p><strong>Created:</strong> <?= $createdAt ?></p>
                <p><strong>Author:</strong> <?= htmlspecialchars($asset['username']) ?></p>
                <p><strong>Description:</strong> <?= htmlspecialchars($asset['description']) ?></p>
                <?php if ($asset['type'] === 'Audio'): ?>
                <div class="asset-audio">
                    <audio controls>
                        <source src="../<?= htmlspecialchars($asset['file_path']) ?>" type="audio/<?= strtolower($extension) ?>">
                        Your browser does not support the audio element.
                    </audio>
                </div>
                <?php endif; ?>
            </div>
        </div>


        <?php if ($isOwner || $isAdmin): ?>
            <div class="asset-actions">
                <a href="edit_asset.php?id=<?= $assetId ?>&from=<?= htmlspecialchars($returnTo) ?>" class="action-btn">Edit</a>
                <form method="POST" action="delete_asset.php" onsubmit="return confirm('Are you sure you want to delete this asset?');" style="display:inline;">
                    <input type="hidden" name="id" value="<?= $assetId ?>">
                    <input type="hidden" name="from" value="<?= htmlspecialchars($returnTo) ?>">
                    <button type="submit" class="action-btn danger">Delete</button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </section>
</main>
<script>
    let currentIndex = 0;
    const images = document.querySelectorAll('.carousel-img');

    function showImage(index) {
        images.forEach((img, i) => {
            img.classList.toggle('active', i === index);
        });
    }

    function showNext() {
        currentIndex = (currentIndex + 1) % images.length;
        showImage(currentIndex);
    }

    function showPrev() {
        currentIndex = (currentIndex - 1 + images.length) % images.length;
        showImage(currentIndex);
    }
</script>


</body>
</html>
