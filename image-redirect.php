<?php
require_once 'config.php';

$username = $_GET['username'] ?? '';
$filename = $_GET['filename'] ?? '';

if (empty($username) || empty($filename)) {
    header('HTTP/1.0 404 Not Found');
    exit('Image non trouvée');
}

// Chercher l'utilisateur
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

if (!$user) {
    header('HTTP/1.0 404 Not Found');
    exit('Utilisateur non trouvé');
}

// Chercher l'image par son nom original
$stmt = $pdo->prepare("
    SELECT file_path, mime_type 
    FROM images 
    WHERE user_id = ? 
    AND (original_filename = ? OR filename = ?)
    AND is_deleted = 0
    LIMIT 1
");
$stmt->execute([$user['id'], $filename, $filename]);
$image = $stmt->fetch();

if (!$image || !file_exists($image['file_path'])) {
    header('HTTP/1.0 404 Not Found');
    exit('Image non trouvée');
}

// Servir l'image
$mimeType = $image['mime_type'] ?? mime_content_type($image['file_path']);
header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($image['file_path']));
header('Cache-Control: public, max-age=31536000'); // Cache 1 an
readfile($image['file_path']);
exit;
?>