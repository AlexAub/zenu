<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Récupérer le nom d'utilisateur
$stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    echo json_encode(['success' => false, 'error' => 'Utilisateur non trouvé']);
    exit;
}

// Vérifier si un fichier est envoyé
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'Aucun fichier reçu']);
    exit;
}

$file = $_FILES['image'];
$width = intval($_POST['width'] ?? 0);
$height = intval($_POST['height'] ?? 0);
$original_filename = $_POST['original_filename'] ?? pathinfo($file['name'], PATHINFO_FILENAME);

// Vérifier la taille (2 MB max pour sauvegarde cloud)
if ($file['size'] > 2 * 1024 * 1024) {
    echo json_encode(['success' => false, 'error' => "Fichier trop volumineux. Maximum pour la sauvegarde : 2 MB"]);
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
$user_dir = "uploads/user_" . $user_id;
if (!is_dir($user_dir)) {
    mkdir($user_dir, 0755, true);
}

// Générer un nom de fichier unique
$extension = pathinfo($original_filename, PATHINFO_EXTENSION);
if (empty($extension)) {
    $extension = 'jpg'; // Extension par défaut
}
$filename = uniqid() . '.' . $extension;
$filepath = $user_dir . '/' . $filename;

// Déplacer le fichier
if (!move_uploaded_file($file['tmp_name'], $filepath)) {
    echo json_encode(['success' => false, 'error' => 'Erreur lors de la sauvegarde du fichier']);
    exit;
}

// Nettoyer le nom original pour l'URL
$clean_name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $original_filename);
$clean_name = preg_replace('/_+/', '_', $clean_name);
$clean_name = trim($clean_name, '_');

// Vérifier si ce nom existe déjà pour cet utilisateur
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count 
    FROM images 
    WHERE user_id = ? 
    AND original_filename = ?
    AND is_deleted = 0
");
$stmt->execute([$user_id, $clean_name]);
$result = $stmt->fetch();

// Si le nom existe, ajouter un suffixe
if ($result['count'] > 0) {
    $clean_name = $clean_name . '_' . uniqid();
}

// Enregistrer en BDD
try {
    $stmt = $pdo->prepare("
        INSERT INTO images (user_id, filename, original_filename, file_path, width, height, file_size, mime_type, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([
        $user_id,
        $filename,
        $clean_name, // Utiliser le nom nettoyé comme original_filename
        $filepath,
        $width,
        $height,
        $file['size'],
        $mime_type
    ]);
    
    $image_id = $pdo->lastInsertId();
    
    // Générer l'URL propre : zenu.fr/username/nom-image
    $pretty_url = SITE_URL . '/' . $user['username'] . '/' . $clean_name;
    
    echo json_encode([
        'success' => true,
        'url' => $pretty_url,
        'image_id' => $image_id,
        'filename' => $filename,
        'original_filename' => $clean_name,
        'size' => $file['size']
    ]);
    
} catch (PDOException $e) {
    // En cas d'erreur, supprimer le fichier uploadé
    if (file_exists($filepath)) {
        unlink($filepath);
    }
    echo json_encode(['success' => false, 'error' => 'Erreur base de données: ' . $e->getMessage()]);
}
?>