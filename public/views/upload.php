<?php
require_once('../auth.php');
require_once('../config.php');
require_once('../db.php');

$msg = "";
$errors = [];

$fromPage = isset($_GET['from']) ? $_GET['from'] : 'dashboard';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $type = trim($_POST['type'] ?? '');
    $userId = $_SESSION['user_id'];

    $file = $_FILES['asset_file'] ?? null;
    $thumbnails = $_FILES['thumbnails'] ?? null;

    if (empty($name)) $errors[] = "Field 'Asset Name' is required.";
    if (empty($description)) $errors[] = "Field 'Description' is required.";
    if (empty($type)) $errors[] = "Field 'Type' is required.";
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) $errors[] = "Main file is required.";

    if (empty($errors)) {
        $originalName = basename($file['name']);
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (in_array($extension, $allowedExtensions)) {
            $newFileName = uniqid() . '.' . $extension;
            $uploadPath = UPLOAD_DIR . $newFileName;

            if (move_uploaded_file($file['tmp_name'], '../' . $uploadPath)) {
                $stmt = $pdo->prepare("INSERT INTO assets (user_id, name, description, type, file_path) 
                                       VALUES (:user_id, :name, :description, :type, :file_path)");
                $stmt->execute([
                    ':user_id' => $userId,
                    ':name' => $name,
                    ':description' => $description,
                    ':type' => $type,
                    ':file_path' => $uploadPath
                ]);

                $assetId = $pdo->lastInsertId();

                if (!empty($thumbnails['name'][0])) {
                    for ($i = 0; $i < min(3, count($thumbnails['name'])); $i++) {
                        if ($thumbnails['error'][$i] === 0) {
                            $thumbExt = strtolower(pathinfo($thumbnails['name'][$i], PATHINFO_EXTENSION));
                            if (in_array($thumbExt, ['jpg', 'jpeg', 'png'])) {
                                $thumbName = uniqid('thumb_') . '.' . $thumbExt;
                                $thumbPath = THUMBNAIL_DIR . $thumbName;
                                move_uploaded_file($thumbnails['tmp_name'][$i], '../' . $thumbPath);

                                $stmtImg = $pdo->prepare("INSERT INTO asset_images (asset_id, image_path) 
                                                          VALUES (:asset_id, :image_path)");
                                $stmtImg->execute([
                                    ':asset_id' => $assetId,
                                    ':image_path' => $thumbPath
                                ]);
                            }
                        }
                    }
                }

                header('Location: ' . ($fromPage === 'assets' ? 'assets.php' : 'dashboard.php'));
                exit();
            } else {
                $errors[] = "Error saving file.";
            }
        } else {
            $errors[] = "Unsupported file extension.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload Asset - AssetVault</title>
    <link rel="stylesheet" href="../styles/upload_edit.css">
</head>
<body>
<div class="upload-wrapper">
    <h2>Upload Asset</h2>

    <?php if (!empty($errors)): ?>
        <ul class="error-list">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php elseif (!empty($msg)): ?>
        <p class="success-msg"><?= $msg ?></p>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="upload-form" id="uploadForm">
        <div class="drop-zone" id="dropZone">
            <p class="drop-zone-p">Drag and drop your file here</p>
            <p class="drop-zone-p">or</p>
            <label class="upload-btn">
                Browse Files
                <input type="file" name="asset_file" id="assetFileInput" required hidden>
            </label>
            <p class="max-size">Maximum file size: 1GB</p>
            <div id="fileNameDisplay"></div>
        </div>

        <label>Asset Name
            <input type="text" name="name" required>
        </label>

        <label>Description
            <textarea name="description" rows="4" required></textarea>
        </label>

        <label>Type
            <select name="type" id="typeSelect" required onchange="updateAcceptedExtensions()">
                <option value="Model 3D">Model 3D</option>
                <option value="Texture">Texture</option>
                <option value="Audio">Audio</option>
            </select>
        </label>

        <div class="add-showcase-wrapper">
            <div class="add-showcase-sub-wrapper">
                <label>Add Showcase</label>
                <small>(up to 3 images)</small>
            </div>
            <label class="upload-btn add-showcase-btn">
                Browse Files
                <input type="file" id="showcaseInput" name="thumbnails[]" multiple accept="image/png,image/jpeg" hidden>
            </label>
        </div>
        <div id="showcaseFilesList" style="margin-top: 10px;"></div>

        <div class="button-row">
            <button type="submit" class="upload-main-btn">Upload Asset</button>
            <a href="<?= ($fromPage === 'assets' ? 'assets.php' : 'dashboard.php') ?>" class="cancel-upload-btn">Cancel Upload</a>
        </div>
    </form>
</div>

<script>
const dropZone = document.getElementById('dropZone');
const assetFileInput = document.getElementById('assetFileInput');
const fileNameDisplay = document.getElementById('fileNameDisplay');
const typeSelect = document.getElementById('typeSelect');
const showcaseInput = document.getElementById('showcaseInput');
const showcaseFilesList = document.getElementById('showcaseFilesList');

// Funkcja zwracająca akceptowane rozszerzenia
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

// Aktualizacja akceptowanych typów inputa
function updateAcceptedExtensions() {
    const acceptList = getAcceptedExtensions().join(",");
    assetFileInput.setAttribute("accept", acceptList);

    // Reset pliku i wyświetlanej nazwy
    assetFileInput.value = "";
    fileNameDisplay.innerText = "";
}

typeSelect.addEventListener('change', updateAcceptedExtensions);

// Obsługa kliknięcia w strefę
dropZone.addEventListener('click', (e) => {
    if (e.target === dropZone) {
        assetFileInput.click();
    }
});

// Obsługa drag & drop
dropZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropZone.classList.add('dragover');
});

dropZone.addEventListener('dragleave', () => {
    dropZone.classList.remove('dragover');
});

showcaseInput.addEventListener('change', function() {
    showcaseFilesList.innerHTML = '';
    for (const file of showcaseInput.files) {
        const item = document.createElement('div');
        item.textContent = file.name;
        item.style.fontSize = '14px';
        item.style.marginTop = '5px';
        showcaseFilesList.appendChild(item);
    }
});

dropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropZone.classList.remove('dragover');

    const files = e.dataTransfer.files;
    const acceptedExtensions = getAcceptedExtensions();

    if (files.length && acceptedExtensions.length) {
        const file = files[0];
        const fileExt = "." + file.name.split('.').pop().toLowerCase();

        if (acceptedExtensions.includes(fileExt)) {
            assetFileInput.files = files;
            fileNameDisplay.innerText = file.name;
        } else {
            alert(`Unsupported file format: ${fileExt}. Allowed: ${acceptedExtensions.join(", ")}`);
        }
    }
});

// Obsługa zmiany pliku
assetFileInput.addEventListener('change', () => {
    fileNameDisplay.innerText = assetFileInput.files.length ? assetFileInput.files[0].name : '';
});

// Ustaw od razu poprawne accept przy załadowaniu strony
updateAcceptedExtensions();
</script>
</body>
</html>
