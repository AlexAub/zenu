<?php
require_once '../config.php';
require_once '../security.php';
require_once '../image-functions.php';

header('Content-Type: application/json');

// Vérifier la connexion
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit;
}

$userId = $_SESSION['user_id'];
$mode = $_POST['mode'] ?? 'simple';

// Vérifier si un fichier est envoyé
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'Aucun fichier reçu']);
    exit;
}

$file = $_FILES['image'];

// Vérifier la taille (10 MB max)
if ($file['size'] > 10 * 1024 * 1024) {
    echo json_encode(['success' => false, 'error' => 'Fichier trop volumineux (max 10 MB)']);
    exit;
}

// Vérifier le type MIME
$allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mime_type, $allowed_types)) {
    echo json_encode(['success' => false, 'error' => 'Type de fichier non autorisé']);
    exit;
}

// Créer le dossier utilisateur si nécessaire
$user_folder = "user_" . $userId;
$user_dir = "../uploads/" . $user_folder;
if (!is_dir($user_dir)) {
    mkdir($user_dir, 0755, true);
}

// Créer le dossier thumbnails
$thumb_dir = "../uploads/thumbnails/" . $user_folder;
if (!is_dir($thumb_dir)) {
    mkdir($thumb_dir, 0755, true);
}

// Générer un nom de fichier unique avec préfixe du mode
$extension = 'jpg'; // Toujours en JPG pour la qualité
$prefix = match($mode) {
    'simple' => 'edited',
    'advanced' => 'cropped',
    'pro' => 'designed',
    default => 'modified'
};

// Si une image originale est éditée, récupérer son nom
$originalName = 'image';
if (isset($_POST['original_image_id']) && !empty($_POST['original_image_id'])) {
    $stmt = $pdo->prepare("SELECT original_filename, filename FROM images WHERE id = ? AND user_id = ?");
    $stmt->execute([(int)$_POST['original_image_id'], $userId]);
    $origImage = $stmt->fetch();
    if ($origImage) {
        // Utiliser le nom original sans extension
        $originalName = pathinfo($origImage['original_filename'], PATHINFO_FILENAME);
    }
}

// Nettoyer le nom pour éviter les caractères problématiques
$cleanName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $originalName);
$cleanName = preg_replace('/_+/', '_', $cleanName);
$cleanName = trim($cleanName, '_');

// Nom de fichier : prefix_nom_timestamp.jpg
$timestamp = date('YmdHis');
$filename = $prefix . '_' . $cleanName . '_' . $timestamp . '.' . $extension;
$filepath = $user_dir . '/' . $filename;
$thumb_path = $thumb_dir . '/' . $filename;

// Déplacer le fichier
if (!move_uploaded_file($file['tmp_name'], $filepath)) {
    echo json_encode(['success' => false, 'error' => 'Erreur lors de la sauvegarde du fichier']);
    exit;
}

// Obtenir les dimensions de l'image
$imageInfo = getimagesize($filepath);
if ($imageInfo === false) {
    unlink($filepath);
    echo json_encode(['success' => false, 'error' => 'Fichier image invalide']);
    exit;
}

list($width, $height) = $imageInfo;

// Générer la miniature
$thumbSuccess = generateThumbnail($filepath, $thumb_path, 300, 300);

// Chemin relatif pour la BDD (sans ../)
$db_filepath = 'uploads/' . $user_folder . '/' . $filename;
$db_thumb_path = $thumbSuccess ? 'uploads/thumbnails/' . $user_folder . '/' . $filename : null;

// Nom d'affichage : utiliser le nom nettoyé au lieu de "Image éditée date"
$display_name = $cleanName;

// Si le nom est trop générique, ajouter le préfixe du mode
if (in_array($cleanName, ['image', 'photo', 'img'])) {
    $modeLabel = match($mode) {
        'simple' => 'Editee',
        'advanced' => 'Recadree',
        'pro' => 'Designee',
        default => 'Modifiee'
    };
    $display_name = $cleanName . '_' . $modeLabel;
}

$original_filename = $display_name;

// Insérer dans la base de données
try {
    $stmt = $pdo->prepare("
        INSERT INTO images (
            user_id, 
            filename, 
            original_filename, 
            file_path, 
            thumbnail_path,
            file_size, 
            width, 
            height, 
            mime_type,
            is_public,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NOW())
    ");
    
    $stmt->execute([
        $userId,
        $filename,
        $original_filename,
        $db_filepath,
        $db_thumb_path,
        filesize($filepath),
        $width,
        $height,
        $mime_type
    ]);
    
    $imageId = $pdo->lastInsertId();
    
    // Récupérer le username pour l'URL
    $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    // Générer l'URL propre
    $prettyUrl = SITE_URL . '/' . $user['username'] . '/' . urlencode($original_filename);
    
    echo json_encode([
        'success' => true,
        'message' => 'Image sauvegardée avec succès',
        'image_id' => $imageId,
        'url' => $prettyUrl,
        'file_path' => $db_filepath,
        'thumbnail_path' => $db_thumb_path
    ]);
    
} catch (PDOException $e) {
    // Supprimer le fichier en cas d'erreur SQL
    unlink($filepath);
    if (file_exists($thumb_path)) {
        unlink($thumb_path);
    }
    
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors de l\'enregistrement : ' . $e->getMessage()
    ]);
}
?>