<?php
require_once 'config.php';

// Récupérer l'URL demandée
// Format: /i/username/filename.jpg
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);

// Extraire username et filename
// Enlever /i/ du début
$path = preg_replace('#^/i/#', '', $path);
$parts = explode('/', $path);

if (count($parts) < 2) {
    header('HTTP/1.0 404 Not Found');
    die('Image non trouvée');
}

$username = $parts[0];
$filename = $parts[1];

// Chercher l'image dans la base de données
$stmt = $pdo->prepare("
    SELECT i.*, u.username, u.id as user_id
    FROM images i
    JOIN users u ON i.user_id = u.id
    WHERE u.username = ? AND i.filename = ?
");
$stmt->execute([$username, $filename]);
$image = $stmt->fetch();

if (!$image) {
    header('HTTP/1.0 404 Not Found');
    die('Image non trouvée');
}

// Vérifier que le fichier existe physiquement
if (!file_exists($image['path'])) {
    header('HTTP/1.0 404 Not Found');
    die('Fichier introuvable sur le serveur');
}

// Définir les headers appropriés
$mime_type = 'image/jpeg'; // Toutes nos images sont en JPG
header('Content-Type: ' . $mime_type);
header('Content-Length: ' . filesize($image['path']));
header('Cache-Control: public, max-age=31536000'); // Cache 1 an
header('Content-Disposition: inline; filename="' . basename($image['original_filename']) . '.jpg"');

// Envoyer le fichier
readfile($image['path']);
exit;
?>