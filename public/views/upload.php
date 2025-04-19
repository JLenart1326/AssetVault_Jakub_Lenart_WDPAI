<?php
require_once('../auth.php');
require_once('../config.php');
require_once('../db.php');

$msg = "";
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $type = trim($_POST['type'] ?? '');
    $userId = $_SESSION['user_id'];

    $file = $_FILES['asset_file'] ?? null;
    $thumbnails = $_FILES['thumbnails'] ?? null;

    // Walidacja wymaganych pól
    if (empty($name)) $errors[] = "Pole 'Nazwa pliku' jest wymagane.";
    if (empty($description)) $errors[] = "Pole 'Opis' jest wymagane.";
    if (empty($type)) $errors[] = "Pole 'Typ pliku' jest wymagane.";
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) $errors[] = "Główny plik jest wymagany.";

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

                $msg = "<span style='color: green;'>Plik został przesłany pomyślnie!</span>";
            } else {
                $errors[] = "Błąd podczas zapisywania pliku na serwerze.";
            }
        } else {
            $errors[] = "Nieobsługiwane rozszerzenie pliku.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Dodaj Asset - AssetVault</title>
</head>
<body>
    <h2>Dodaj nowy asset</h2>

    <?php if (!empty($errors)): ?>
        <ul style="color: red;">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php elseif (!empty($msg)): ?>
        <p><strong><?= $msg ?></strong></p>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <label>Nazwa pliku:<br>
            <input type="text" name="name" required>
        </label><br><br>

        <label>Opis:<br>
            <textarea name="description" rows="4" cols="40" required></textarea>
        </label><br><br>

        <label>Typ pliku:<br>
            <select name="type" id="typeSelect" required onchange="updateAcceptedExtensions()">
                <option value="">-- Wybierz typ --</option>
                <option value="Model 3D">Model 3D</option>
                <option value="Tekstura">Tekstura</option>
                <option value="Audio">Audio</option>
            </select>
        </label><br><br>

        <label>Wybierz plik główny:<br>
            <input type="file" name="asset_file" id="assetFileInput" required onchange="checkFileSize()">
        </label><br><br>

        <label>Miniatury (do 3 obrazków PNG/JPG):<br>
            <input type="file" name="thumbnails[]" multiple accept="image/png,image/jpeg">
        </label><br><br>

        <button type="submit">Prześlij asset</button>
    </form>

    <?php
        $returnTo = $_GET['from'] ?? 'dashboard';
    ?>
    
    <p><a href="<?= htmlspecialchars($returnTo) ?>.php">← Powrót do <?= $returnTo === 'dashboard' ? 'dashboardu' : 'listy assetów' ?></a></p>

<script>
function updateAcceptedExtensions() {
    const select = document.getElementById('typeSelect');
    const fileInput = document.getElementById('assetFileInput');
    const selectedType = select.value;

    let accept = "";

    if (selectedType === "Model 3D") {
        accept = ".fbx,.obj,.blend";
    } else if (selectedType === "Tekstura") {
        accept = ".jpg,.jpeg,.png,.tga";
    } else if (selectedType === "Audio") {
        accept = ".mp3,.wav,.ogg";
    } else {
        accept = "";
    }

    fileInput.setAttribute("accept", accept);
}

function checkFileSize() {
    const fileInput = document.getElementById('assetFileInput');
    const file = fileInput.files[0];

    if (file && file.size > 1024 * 1024 * 1024) {
        alert("Plik jest za duży! Maksymalny rozmiar to 1 GB.");
        fileInput.value = "";
    }
}
</script>

</body>
</html>
