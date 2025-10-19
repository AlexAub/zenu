<?php
require_once 'config.php';

header('Content-Type: application/json');

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Vérifier les quotas
$stmt = $pdo->prepare("SELECT COUNT(*) as count, COALESCE(SUM(size), 0) as total_size FROM images WHERE user_id = ?");
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
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'Erreur lors de l\'upload']);
    exit;
}

$file = $_FILES['image'];
$original_filename = $_POST['original_filename'] ?? 'image';
$width = intval($_POST['width'] ?? 0);
$height = intval($_POST['height'] ?? 0);

// Vérifier la taille (2 MB max)
if ($file['size'] > 2 * 1024 * 1024) {
    echo json_encode(['success' => false, 'error' => 'Image trop volumineuse (2 MB max)']);
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
if (!file_exists($user_dir)) {
    mkdir($user_dir, 0755, true);
}

// Générer un nom de fichier unique
$filename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '', basename($file['name']));
$filepath = $user_dir . '/' . $filename;

// Déplacer le fichier
if (!move_uploaded_file($file['tmp_name'], $filepath)) {
    echo json_encode(['success' => false, 'error' => 'Erreur lors de la sauvegarde du fichier']);
    exit;
}

// Enregistrer dans la BDD
try {
    $stmt = $pdo->prepare("
        INSERT INTO images (user_id, filename, original_filename, path, size, width, height)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $user_id,
        $filename,
        $original_filename,
        $filepath,
        $file['size'],
        $width,
        $height
    ]);
    
    // Construire l'URL complète
    $url = SITE_URL . '/' . $filepath;
    
    echo json_encode([
        'success' => true,
        'url' => $url,
        'filename' => $filename,
        'size' => $file['size']
    ]);
    
} catch(PDOException $e) {
    // Supprimer le fichier en cas d'erreur BDD
    unlink($filepath);
    echo json_encode(['success' => false, 'error' => 'Erreur base de données']);
}
?>