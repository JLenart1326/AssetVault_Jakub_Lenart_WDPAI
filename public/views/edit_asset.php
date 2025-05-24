<?php
require_once('../auth.php');
require_once('../config.php');
require_once('../db.php');

if (!isset($_GET['id'])) {
    header('Location: assets.php');
    exit();
}

$assetId = (int)$_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM assets WHERE id = :id");
$stmt->execute([':id' => $assetId]);
$asset = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$asset) {
    header('Location: assets.php');
    exit();
}

$errors = [];
$msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $type = trim($_POST['type'] ?? '');
    $updateMainAsset = isset($_POST['update_main']);
    $updateScreenshots = isset($_POST['update_screenshots']);

    if (empty($name)) $errors[] = "Asset Name is required.";
    if (empty($description)) $errors[] = "Description is required.";
    if (empty($type)) $errors[] = "Type is required.";

    // Walidacja plików jeśli checkboxy są zaznaczone
    if ($updateMainAsset) {
        if (!isset($_FILES['asset_file']) || $_FILES['asset_file']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "You must upload a new main asset file.";
        } else {
            // sprawdzamy format pliku
            $allowedMain = [];
            if ($type == "Model 3D") $allowedMain = ['fbx', 'obj', 'blend'];
            elseif ($type == "Texture") $allowedMain = ['jpg', 'jpeg', 'png', 'tga'];
            elseif ($type == "Audio") $allowedMain = ['mp3', 'wav', 'ogg'];

            $ext = strtolower(pathinfo($_FILES['asset_file']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedMain)) {
                $errors[] = "Invalid main file format.";
            }
        }
    }

    if ($updateScreenshots) {
        if (!isset($_FILES['new_showcase_files']) || empty($_FILES['new_showcase_files']['name'][0])) {
            $errors[] = "You must upload at least one showcase image.";
        } else {
            foreach ($_FILES['new_showcase_files']['name'] as $thumbName) {
                $ext = strtolower(pathinfo($thumbName, PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg', 'jpeg', 'png'])) {
                    $errors[] = "Only JPG/PNG images allowed for showcase.";
                    break;
                }
            }
        }
    }
    

    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE assets SET name = :name, description = :description, type = :type WHERE id = :id");
        $stmt->execute([
            ':name' => $name,
            ':description' => $description,
            ':type' => $type,
            ':id' => $assetId
        ]);

        // Aktualizacja pliku głównego
        if ($updateMainAsset) {
            $originalName = basename($_FILES['asset_file']['name']);
            $newFileName = uniqid() . '.' . strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            $uploadPath = UPLOAD_DIR . $newFileName;
            move_uploaded_file($_FILES['asset_file']['tmp_name'], '../' . $uploadPath);

            $stmt = $pdo->prepare("UPDATE assets SET file_path = :file_path WHERE id = :id");
            $stmt->execute([
                ':file_path' => $uploadPath,
                ':id' => $assetId
            ]);
        }

        // Aktualizacja miniatur
        if ($updateScreenshots) {
            $stmt = $pdo->prepare("DELETE FROM asset_images WHERE asset_id = :id");
            $stmt->execute([':id' => $assetId]);

            if (!empty($_FILES['new_showcase_files']['name'][0])) {
                for ($i = 0; $i < min(3, count($_FILES['new_showcase_files']['name'])); $i++) {
                    if ($_FILES['new_showcase_files']['error'][$i] === 0) {
                        $ext = strtolower(pathinfo($_FILES['new_showcase_files']['name'][$i], PATHINFO_EXTENSION));
                        if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                            $thumbName = uniqid('thumb_') . '.' . $ext;
                            $thumbPath = THUMBNAIL_DIR . $thumbName;
                            move_uploaded_file($_FILES['new_showcase_files']['tmp_name'][$i], '../' . $thumbPath);

                            $stmtImg = $pdo->prepare("INSERT INTO asset_images (asset_id, image_path) VALUES (:asset_id, :image_path)");
                            $stmtImg->execute([
                                ':asset_id' => $assetId,
                                ':image_path' => $thumbPath
                            ]);
                        }
                    }
                }
            }
        }

        $msg = "Changes saved successfully!";
        header('Location: asset.php?id=' . $assetId);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Asset - AssetVault</title>
    <link rel="stylesheet" href="../styles/upload_edit.css">
</head>
<body>

<div class="upload-wrapper">
    <h2>Edit Asset</h2>
    <h4 class="asset-name"><?= htmlspecialchars($asset['name']) ?></h4>

    <?php if (!empty($errors)): ?>
        <ul class="error-list">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php elseif (!empty($msg)): ?>
        <p class="success-msg"><?= $msg ?></p>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="upload-form">
        <div class="checkbox-wrapper">
            <label for="update_main">Update Main Asset</label>
            <input type="checkbox" id="updateMainAsset" name="update_main">
        </div>
        <div id="updateMainAssetFields" style="display: none; margin-top: 15px;">
            <label class="upload-btn">
                Browse Files
                <input type="file" id="newMainAssetFile" name="asset_file" style="display: none;">
            </label>
            <div style="font-size: 12px; color: #6b7280; margin-top: 5px;">Maximum file size: 1GB</div>
            <div id="mainAssetFileName" style="font-size: 14px; margin-top: 5px;"></div>
        </div>

        <label>Asset Name
            <input type="text" name="name" value="<?= htmlspecialchars($asset['name']) ?>" required>
        </label>

        <label>Description
            <textarea name="description" rows="4" required><?= htmlspecialchars($asset['description']) ?></textarea>
        </label>

        <label>Type
            <select name="type" id="typeSelect" required>
                <option value="Model 3D" <?= $asset['type'] == 'Model 3D' ? 'selected' : '' ?>>Model 3D</option>
                <option value="Texture" <?= $asset['type'] == 'Texture' ? 'selected' : '' ?>>Texture</option>
                <option value="Audio" <?= $asset['type'] == 'Audio' ? 'selected' : '' ?>>Audio</option>
            </select>
        </label>

        <div class="checkbox-wrapper">
            <label for="update_screenshots">Update Showcase</label>
            <input type="checkbox" id="update_screenshots" name="update_screenshots">
        </div>
        <div id="updateShowcaseFields" style="display: none; margin-top: 15px;">
            <label class="upload-btn">
                Browse Files
                <input type="file" id="newShowcaseFiles" name="new_showcase_files[]" multiple style="display: none;" accept=".jpg,.jpeg,.png">
            </label>
            <div style="font-size: 12px; color: #6b7280; margin-top: 5px;">You can upload up to 3 images.</div>
            <div id="showcaseFilesList" style="font-size: 14px; margin-top: 5px;"></div>
        </div>


        <div class="button-row">
            <button type="submit" class="upload-main-btn">Save Changes</button>
            <a href="asset.php?id=<?= $assetId ?>&from=<?= htmlspecialchars($_GET['from'] ?? 'assets') ?>" class="cancel-upload-btn">Cancel Changes</a>

        </div>

    </form>
</div>
<script>
    const updateMainAssetCheckbox = document.getElementById('updateMainAsset');
const updateMainAssetFields = document.getElementById('updateMainAssetFields');
const newMainAssetFile = document.getElementById('newMainAssetFile');
const mainAssetFileName = document.getElementById('mainAssetFileName');
const typeSelect = document.getElementById('typeSelect');
const updateShowcaseCheckbox = document.getElementById('update_screenshots');
const updateShowcaseFields = document.getElementById('updateShowcaseFields');
const newShowcaseFiles = document.getElementById('newShowcaseFiles');
const showcaseFilesList = document.getElementById('showcaseFilesList');

updateShowcaseCheckbox.addEventListener('change', () => {
    if (updateShowcaseCheckbox.checked) {
        updateShowcaseFields.style.display = 'block';
    } else {
        updateShowcaseFields.style.display = 'none';
        newShowcaseFiles.value = '';
        showcaseFilesList.innerText = '';
    }
});

newShowcaseFiles.addEventListener('change', () => {
    showcaseFilesList.innerHTML = '';
    const files = Array.from(newShowcaseFiles.files);
    files.forEach(file => {
        const ext = "." + file.name.split('.').pop().toLowerCase();
        if ([".jpg", ".jpeg", ".png"].includes(ext)) {
            const item = document.createElement('div');
            item.textContent = file.name;
            showcaseFilesList.appendChild(item);
        } else {
            alert(`Unsupported file format: ${ext}. Only JPG and PNG allowed.`);
            newShowcaseFiles.value = '';
            showcaseFilesList.innerText = '';
        }
    });
});

function getAcceptedExtensions() {
    const selectedType = typeSelect.value;
    if (selectedType === "Model 3D") {
        return [".fbx", ".obj", ".blend"];
    } else if (selectedType === "Texture") {
        return [".jpg", ".jpeg", ".png", ".tga"];
    } else if (selectedType === "Audio") {
        return [".mp3", ".wav", ".ogg"];
    }
    return [];
}

updateMainAssetCheckbox.addEventListener('change', () => {
    if (updateMainAssetCheckbox.checked) {
        updateMainAssetFields.style.display = 'block';
        updateNewMainAssetAccept();
    } else {
        updateMainAssetFields.style.display = 'none';
        newMainAssetFile.value = '';
        mainAssetFileName.innerText = '';
    }
});

// Obsługa wyboru pliku
newMainAssetFile.addEventListener('change', () => {
    const file = newMainAssetFile.files[0];
    if (file) {
        const accepted = getAcceptedExtensions();
        const fileExt = "." + file.name.split('.').pop().toLowerCase();
        if (accepted.includes(fileExt)) {
            mainAssetFileName.innerText = file.name;
        } else {
            alert(`Unsupported file format: ${fileExt}. Allowed: ${accepted.join(", ")}`);
            newMainAssetFile.value = '';
            mainAssetFileName.innerText = '';
        }
    } else {
        mainAssetFileName.innerText = '';
    }
});

typeSelect.addEventListener('change', () => {
    if (updateMainAssetCheckbox.checked) {
        updateNewMainAssetAccept();
    }
});

function updateNewMainAssetAccept() {
    const accepted = getAcceptedExtensions().join(",");
    newMainAssetFile.setAttribute('accept', accepted);
}
</script>

</body>
</html>
