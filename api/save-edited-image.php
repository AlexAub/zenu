<?php
require_once '../config.php';
require_once '../security.php';
require_once '../image-functions.php';

header('Content-Type: application/json');

// Array pour collecter les logs
$debugLogs = [];
$debugLogs[] = "=== DÉBUT SAUVEGARDE IMAGE ÉDITÉE ===";

// Vérifier la connexion
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Non authentifié', 'debug' => $debugLogs]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée', 'debug' => $debugLogs]);
    exit;
}

$userId = $_SESSION['user_id'];
$mode = $_POST['mode'] ?? 'simple';

$debugLogs[] = "User ID: $userId";
$debugLogs[] = "Mode: $mode";

// Vérifier si un fichier est envoyé
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    $debugLogs[] = "❌ Aucun fichier reçu";
    echo json_encode(['success' => false, 'error' => 'Aucun fichier reçu', 'debug' => $debugLogs]);
    exit;
}

$file = $_FILES['image'];

// Vérifier la taille (10 MB max)
if ($file['size'] > 10 * 1024 * 1024) {
    echo json_encode(['success' => false, 'error' => 'Fichier trop volumineux (max 10 MB)', 'debug' => $debugLogs]);
    exit;
}

// Vérifier le type MIME
$allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mime_type, $allowed_types)) {
    echo json_encode(['success' => false, 'error' => 'Type de fichier non autorisé', 'debug' => $debugLogs]);
    exit;
}

$debugLogs[] = "✅ Fichier valide";

// Créer les dossiers
$user_folder = "user_" . $userId;
$user_dir = "../uploads/" . $user_folder;
$thumb_dir = "../uploads/thumbnails/" . $user_folder;

if (!is_dir($user_dir)) mkdir($user_dir, 0755, true);
if (!is_dir($thumb_dir)) mkdir($thumb_dir, 0755, true);

$extension = 'jpg';

// Préfixe selon le mode
$prefix = match($mode) {
    'simple' => 'Editee',
    'advanced' => 'Recadree',
    'pro' => 'Designee',
    default => 'Modifiee'
};

$debugLogs[] = "🏷️ Préfixe: $prefix";

// Récupérer le nom original
$originalName = 'Image';
if (isset($_POST['original_image_id']) && !empty($_POST['original_image_id'])) {
    $origId = (int)$_POST['original_image_id'];
    $debugLogs[] = "🔍 ID image originale: $origId";
    
    $stmt = $pdo->prepare("SELECT original_filename FROM images WHERE id = ? AND user_id = ?");
    $stmt->execute([$origId, $userId]);
    $origImage = $stmt->fetch();
    
    if ($origImage) {
        $originalName = pathinfo($origImage['original_filename'], PATHINFO_FILENAME);
        // Retirer les préfixes existants
        $originalName = preg_replace('/^(Editee|Recadree|Designee|Modifiee)_/', '', $originalName);
        $debugLogs[] = "✅ Nom récupéré: '$originalName'";
    }
}

// Nettoyer le nom
$cleanName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $originalName);
$cleanName = preg_replace('/_+/', '_', $cleanName);
$cleanName = trim($cleanName, '_');

// Construire le nom de base
$baseName = $prefix . '_' . $cleanName;

$debugLogs[] = "📝 Nom de base: '$baseName'";

// ⭐⭐⭐ VÉRIFICATION DES DOUBLONS ⭐⭐⭐
$finalName = $baseName;
$counter = 1;
$maxAttempts = 100;

$debugLogs[] = "🔄 Vérification des doublons dans 'original_filename'...";

// BOUCLE DE VÉRIFICATION
while ($counter <= $maxAttempts) {
    // Vérifier si ce nom existe déjà
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM images 
        WHERE user_id = ? 
        AND original_filename = ?
        AND is_deleted = 0
    ");
    $stmt->execute([$userId, $finalName]);
    $result = $stmt->fetch();
    $count = $result['count'];
    
    $debugLogs[] = "   🔎 Test '$finalName': $count résultat(s)";
    
    if ($count == 0) {
        // Nom disponible !
        if ($counter > 1) {
            $debugLogs[] = "✅ NOM UNIQUE TROUVÉ après $counter tentatives: '$finalName'";
        } else {
            $debugLogs[] = "✅ NOM UNIQUE dès la 1ère tentative: '$finalName'";
        }
        break;
    }
    
    // Nom occupé, essayer avec suffixe
    $counter++;
    $finalName = $baseName . '_' . $counter;
    $debugLogs[] = "   ↪️ Nom occupé, essai suivant: '$finalName'";
}

if ($counter > $maxAttempts) {
    echo json_encode([
        'success' => false,
        'error' => 'Trop de fichiers similaires',
        'debug' => $debugLogs
    ]);
    exit;
}

// ⭐⭐⭐ NOMS FINAUX ⭐⭐⭐
$original_filename_bdd = $finalName;  // ← CE NOM EST UNIQUE !
$timestamp = date('YmdHis') . '_' . uniqid();
$physical_filename = $finalName . '_' . $timestamp . '.jpg';

$debugLogs[] = "";
$debugLogs[] = "📋 RÉSUMÉ DES NOMS:";
$debugLogs[] = "   • original_filename (BDD): '$original_filename_bdd'";
$debugLogs[] = "   • filename (physique): '$physical_filename'";
$debugLogs[] = "";

$filepath = $user_dir . '/' . $physical_filename;
$thumb_path = $thumb_dir . '/' . $physical_filename;

// Déplacer le fichier
if (!move_uploaded_file($file['tmp_name'], $filepath)) {
    echo json_encode(['success' => false, 'error' => 'Erreur déplacement fichier', 'debug' => $debugLogs]);
    exit;
}

$debugLogs[] = "✅ Fichier déplacé";

// Dimensions
$imageInfo = getimagesize($filepath);
if ($imageInfo === false) {
    unlink($filepath);
    echo json_encode(['success' => false, 'error' => 'Fichier invalide', 'debug' => $debugLogs]);
    exit;
}
list($width, $height) = $imageInfo;

// Miniature
$thumbSuccess = generateThumbnail($filepath, $thumb_path, 300, 300);

// Chemins BDD
$db_filepath = 'uploads/' . $user_folder . '/' . $physical_filename;
$db_thumb_path = $thumbSuccess ? 'uploads/thumbnails/' . $user_folder . '/' . $physical_filename : null;

// ⭐⭐⭐ INSERTION EN BDD ⭐⭐⭐
try {
    $debugLogs[] = "💾 INSERTION EN BDD...";
    $debugLogs[] = "   VALUES à insérer:";
    $debugLogs[] = "   • user_id: $userId";
    $debugLogs[] = "   • filename: '$physical_filename'";
    $debugLogs[] = "   • original_filename: '$original_filename_bdd' ← DOIT ÊTRE UNIQUE !";
    $debugLogs[] = "   • file_path: '$db_filepath'";
    
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
        $physical_filename,
        $original_filename_bdd,  // ⭐ LE NOM UNIQUE !
        $db_filepath,
        $db_thumb_path,
        filesize($filepath),
        $width,
        $height,
        'image/jpeg'
    ]);
    
    $imageId = $pdo->lastInsertId();
    $debugLogs[] = "✅ INSERTION RÉUSSIE avec ID: $imageId";
    
    // Vérification post-insertion
    $stmt = $pdo->prepare("SELECT original_filename FROM images WHERE id = ?");
    $stmt->execute([$imageId]);
    $inserted = $stmt->fetch();
    $debugLogs[] = "🔍 Vérification: original_filename en BDD = '{$inserted['original_filename']}'";
    
    // Username pour URL
    $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    $prettyUrl = SITE_URL . '/' . $user['username'] . '/' . urlencode($original_filename_bdd);
    
    $message = '✅ Image sauvegardée avec succès !';
    if ($counter > 1) {
        $message = "✅ Image sauvegardée sous le nom '$original_filename_bdd'";
    }
    
    $debugLogs[] = "=== FIN (SUCCÈS) ===";
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'image_id' => $imageId,
        'url' => $prettyUrl,
        'display_name' => $original_filename_bdd,
        'file_path' => $db_filepath,
        'had_suffix' => $counter > 1,
        'attempts' => $counter,
        'debug' => $debugLogs
    ]);
    
} catch (PDOException $e) {
    unlink($filepath);
    if (file_exists($thumb_path)) unlink($thumb_path);
    
    $debugLogs[] = "❌ ERREUR PDO: " . $e->getMessage();
    
    echo json_encode([
        'success' => false,
        'error' => 'Erreur BDD: ' . $e->getMessage(),
        'debug' => $debugLogs
    ]);
}
?>