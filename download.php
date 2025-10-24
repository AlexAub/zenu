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

// Construire le nom de fichier avec extension
$original_name = $image['original_filename'] ?? pathinfo($image['filename'], PATHINFO_FILENAME);

// Récupérer l'extension du fichier réel
$extension = pathinfo($image['file_path'], PATHINFO_EXTENSION);

// Si original_filename n'a pas d'extension, l'ajouter
if (pathinfo($original_name, PATHINFO_EXTENSION) === '') {
    $download_filename = $original_name . '.' . $extension;
} else {
    $download_filename = $original_name;
}

// Forcer le téléchargement avec le bon nom
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $download_filename . '"');
header('Content-Length: ' . filesize($image['file_path']));
header('Cache-Control: must-revalidate');
header('Pragma: public');

readfile($image['file_path']);
exit;
?>