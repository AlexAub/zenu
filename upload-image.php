<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Récupérer le username de l'utilisateur
$stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    echo json_encode(['success' => false, 'error' => 'Utilisateur introuvable']);
    exit;
}

$username = $user['username'];

// Vérifier les quotas (utiliser file_size au lieu de size)
$stmt = $pdo->prepare("SELECT COUNT(*) as count, COALESCE(SUM(file_size), 0) as total_size FROM images WHERE user_id = ?");
$stmt->execute([$user_id]);
$quotas = $stmt->fetch();

// Vérifier quota images (500 max)
if ($quotas['count'] >= 500) {
    echo json_encode(['success' => false, 'error' => 'Quota d\'images atteint (500 max)']);
    exit;
}

// Vérifier quota espace (500 MB max)
if ($quotas['total_size'] >= 500 * 1024 * 1024) {
    echo json_encode(['success' => false, 'error' => 'Quota d\'espace atteint (500 MB max)']);
    exit;
}

// Vérifier l'upload
if (!isset($_FILES['image'])) {
    echo json_encode(['success' => false, 'error' => 'Aucun fichier reçu', 'debug' => 'FILES empty']);
    exit;
}

if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    $error_messages = [
        UPLOAD_ERR_INI_SIZE => 'Fichier trop volumineux (php.ini)',
        UPLOAD_ERR_FORM_SIZE => 'Fichier trop volumineux (form)',
        UPLOAD_ERR_PARTIAL => 'Upload partiel',
        UPLOAD_ERR_NO_FILE => 'Aucun fichier',
        UPLOAD_ERR_NO_TMP_DIR => 'Dossier temp manquant',
        UPLOAD_ERR_CANT_WRITE => 'Erreur écriture disque',
        UPLOAD_ERR_EXTENSION => 'Extension PHP a stoppé l\'upload'
    ];
    $error_msg = $error_messages[$_FILES['image']['error']] ?? 'Erreur inconnue';
    echo json_encode(['success' => false, 'error' => $error_msg, 'code' => $_FILES['image']['error']]);
    exit;
}

$file = $_FILES['image'];
$original_filename = $_POST['original_filename'] ?? 'image';
$width = intval($_POST['width'] ?? 0);
$height = intval($_POST['height'] ?? 0);

// Vérifier la taille du fichier uploadé (2 MB max pour sauvegarde)
if ($file['size'] > 2 * 1024 * 1024) {
    $sizeMB = round($file['size'] / (1024 * 1024), 2);
    echo json_encode(['success' => false, 'error' => "Image trop volumineuse ({$sizeMB} MB). Maximum pour la sauvegarde : 2 MB"]);
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

// Enregistrer en BDD (adapter selon votre structure de table)
try {
    $stmt = $pdo->prepare("
        INSERT INTO images (user_id, filename, original_filename, file_path, width, height, file_size, mime_type, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([
        $user_id,
        $filename,
        $original_filename,
        $filepath,
        $width,
        $height,
        $file['size'],
        $mime_type
    ]);
    
    $image_id = $pdo->lastInsertId();
    
    // Générer l'URL publique
    $url = 'https://zenu.fr/' . $filepath;
    
    echo json_encode([
        'success' => true,
        'url' => $url,
        'image_id' => $image_id,
        'filename' => $filename,
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