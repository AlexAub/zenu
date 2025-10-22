<?php
require_once 'config.php';

// Récupérer les paramètres
$username = $_GET['username'] ?? '';
$filename = $_GET['filename'] ?? '';
$imageId = $_GET['image_id'] ?? '';

if (empty($username) || (empty($filename) && empty($imageId))) {
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

// Construction de la requête SQL selon le type de lien
$image = null;

if (!empty($filename)) {
    // Cas 1: Lien avec extension (ex: artifice_ours.png)
    // Retirer l'extension pour chercher dans original_filename
    $filenameWithoutExt = preg_replace('/\.(jpg|jpeg|png|gif|webp)$/i', '', $filename);
    
    // Chercher l'image par son nom (avec ou sans extension)
    $stmt = $pdo->prepare("
        SELECT file_path, mime_type, filename 
        FROM images 
        WHERE user_id = ? 
        AND (
            original_filename = ? 
            OR original_filename = ?
            OR filename = ?
        )
        AND is_deleted = 0
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$user['id'], $filenameWithoutExt, $filename, $filename]);
    $image = $stmt->fetch();
    
} else if (!empty($imageId)) {
    // Cas 2: Lien sans extension (ex: 1000010148)
    // Essayer d'abord comme ID numérique
    if (is_numeric($imageId)) {
        $stmt = $pdo->prepare("
            SELECT file_path, mime_type, filename 
            FROM images 
            WHERE user_id = ? 
            AND id = ?
            AND is_deleted = 0
            LIMIT 1
        ");
        $stmt->execute([$user['id'], intval($imageId)]);
        $image = $stmt->fetch();
    }
    
    // Si pas trouvé par ID, chercher par nom sans extension
    if (!$image) {
        $stmt = $pdo->prepare("
            SELECT file_path, mime_type, filename 
            FROM images 
            WHERE user_id = ? 
            AND original_filename = ?
            AND is_deleted = 0
            ORDER BY created_at DESC
            LIMIT 1
        ");
        $stmt->execute([$user['id'], $imageId]);
        $image = $stmt->fetch();
    }
}

// Vérifier si l'image existe
if (!$image) {
    header('HTTP/1.0 404 Not Found');
    exit('Image non trouvée');
}

// Vérifier si le fichier existe physiquement
if (!file_exists($image['file_path'])) {
    header('HTTP/1.0 404 Not Found');
    exit('Fichier introuvable sur le serveur');
}

// Déterminer l'extension à partir du fichier
$extension = pathinfo($image['file_path'], PATHINFO_EXTENSION);

// Servir l'image avec les bons headers
$mimeType = $image['mime_type'] ?? mime_content_type($image['file_path']);
header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($image['file_path']));
header('Cache-Control: public, max-age=31536000'); // Cache 1 an
header('Content-Disposition: inline; filename="' . $image['filename'] . '"');

// Lire et envoyer le fichier
readfile($image['file_path']);
exit;
?>