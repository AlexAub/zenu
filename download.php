<?php
require_once 'config.php';
require_once 'security.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$imageId = intval($_GET['id'] ?? 0);

if ($imageId <= 0) {
    die('Image invalide');
}

// Récupérer l'image
$stmt = $pdo->prepare("
    SELECT file_path, original_filename, filename 
    FROM images 
    WHERE id = ? AND user_id = ? AND is_deleted = 0
");
$stmt->execute([$imageId, $userId]);
$image = $stmt->fetch();

if (!$image || !file_exists($image['file_path'])) {
    die('Image introuvable');
}

// Forcer le téléchargement
$filename = $image['original_filename'] ?? $image['filename'];
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($image['file_path']));
readfile($image['file_path']);
exit;
?>