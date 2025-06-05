<?php
require_once('../auth.php');
require_once('../classes/Asset.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'])) {
    header("Location: assets.php");
    exit();
}

$assetId = (int)$_POST['id'];
$returnTo = 'assets';
if (isset($_POST['from']) && in_array($_POST['from'], ['dashboard', 'assets'])) {
    $returnTo = $_POST['from'];
}

$userId = $_SESSION['user_id'];
$isAdmin = ($_SESSION['role'] === 'admin');

$assetObj = new Asset();
$asset = $assetObj->getById($assetId);

if (!$asset) {
    header("Location: {$returnTo}.php");
    exit();
}

if ($isAdmin || $asset['user_id'] == $userId) {
    if (!empty($asset['file_path']) && file_exists('../' . $asset['file_path'])) {
        @unlink('../' . $asset['file_path']);
    }
    if (!empty($asset['images'])) {
        foreach ($asset['images'] as $img) {
            if (!empty($img['image_path']) && file_exists('../' . $img['image_path'])) {
                @unlink('../' . $img['image_path']);
            }
        }
    }
    $assetObj->delete($assetId, $userId, $isAdmin);
}

header("Location: {$returnTo}.php");
exit();
?>
