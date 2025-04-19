<style>
        .asset-box {
            border: 1px solid #ccc;
            padding: 10px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
        }
        .asset-box:hover {
            background-color: #f9f9f9;
        }
        .asset-box img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            margin-right: 20px;
        }
        .topbar {
            margin-bottom: 20px;
        }
        .topbar button {
            margin-right: 10px;
        }
    </style>
<?php
foreach ($assets as $asset):
    // Pobierz pierwszą miniaturkę lub domyślną
    $stmtImg = $pdo->prepare("SELECT image_path FROM asset_images WHERE asset_id = :id LIMIT 1");
    $stmtImg->execute([':id' => $asset['id']]);
    $thumb = $stmtImg->fetchColumn();
    $thumbUrl = $thumb ? "../" . $thumb : "../images/default-thumb.png";
?>
    <div class="asset-box" onclick="window.location.href='asset.php?id=<?= $asset['id'] ?>'">
        <img src="<?= htmlspecialchars($thumbUrl) ?>" alt="Miniatura">
        <div>
            <strong><?= htmlspecialchars($asset['name']) ?></strong><br>
            Typ: <?= htmlspecialchars($asset['type']) ?><br>
            Autor: <?= htmlspecialchars($asset['username']) ?>
        </div>
        <div>
            <a href="../<?= htmlspecialchars($asset['file_path']) ?>" download>
                <button type="button" onclick="event.stopPropagation()">Pobierz</button>
            </a>
        </div>
    </div>
<?php endforeach; ?>


