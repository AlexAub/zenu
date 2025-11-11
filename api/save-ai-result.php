<?php
/**
 * API pour sauvegarder les résultats des traitements IA dans le compte utilisateur
 */

require_once '../config.php';
require_once '../security.php';
require_once '../image-functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$imageData = $input['image_data'] ?? '';
$originalId = intval($input['original_id'] ?? 0);
$tool = $input['tool'] ?? '';

$userId = $_SESSION['user_id'];

if (empty($imageData) || $originalId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Données manquantes']);
    exit;
}

try {
    // Récupérer l'image originale
    $stmt = $pdo->prepare("SELECT * FROM images WHERE id = ? AND user_id = ?");
    $stmt->execute([$originalId, $userId]);
    $originalImage = $stmt->fetch();
    
    if (!$originalImage) {
        throw new Exception("Image originale non trouvée");
    }
    
    // Le chemin de l'image temporaire est fourni
    $tempPath = '../' . $imageData;
    
    if (!file_exists($tempPath)) {
        throw new Exception("Fichier temporaire introuvable");
    }
    
    // Créer le dossier utilisateur
    $userFolder = "user_" . $userId;
    $userDir = "../uploads/" . $userFolder;
    $thumbDir = "../uploads/thumbnails/" . $userFolder;
    
    if (!is_dir($userDir)) mkdir($userDir, 0755, true);
    if (!is_dir($thumbDir)) mkdir($thumbDir, 0755, true);
    
    // Générer un nom de fichier basé sur l'original
    $toolSuffixes = [
        'remove-bg' => 'nobg',
        'enhance' => 'enhanced',
        'smart-crop' => 'cropped',
        'optimize' => 'optimized'
    ];
    
    $suffix = $toolSuffixes[$tool] ?? 'ai';
    $originalName = pathinfo($originalImage['original_filename'] ?? $originalImage['filename'], PATHINFO_FILENAME);
    $extension = pathinfo($tempPath, PATHINFO_EXTENSION);
    
    $cleanName = $originalName . '_' . $suffix;
    
    // Vérifier les doublons
    $finalName = $cleanName;
    $counter = 1;
    
    while ($counter <= 100) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count
            FROM images 
            WHERE user_id = ? 
            AND original_filename = ?
            AND is_deleted = 0
        ");
        $stmt->execute([$userId, $finalName]);
        $result = $stmt->fetch();
        
        if ($result['count'] == 0) {
            break;
        }
        
        $counter++;
        $finalName = $cleanName . '_' . $counter;
    }
    
    // Nom du fichier physique
    $timestamp = date('YmdHis') . '_' . uniqid();
    $physicalFilename = $finalName . '_' . $timestamp . '.' . $extension;
    $finalPath = $userDir . '/' . $physicalFilename;
    $thumbPath = $thumbDir . '/' . $physicalFilename;
    
    // Copier le fichier temporaire vers son emplacement final
    if (!copy($tempPath, $finalPath)) {
        throw new Exception("Impossible de sauvegarder le fichier");
    }
    
    // Supprimer le fichier temporaire
    @unlink($tempPath);
    
    // Obtenir les dimensions
    $imageInfo = getimagesize($finalPath);
    if ($imageInfo === false) {
        throw new Exception("Impossible de lire les dimensions");
    }
    
    list($width, $height) = $imageInfo;
    $fileSize = filesize($finalPath);
    
    // Générer la miniature
    generateThumbnail($finalPath, $thumbPath, 300, 300);
    
    // Chemin relatif pour la base de données
    $relativeFilePath = 'uploads/' . $userFolder . '/' . $physicalFilename;
    $relativeThumbPath = 'uploads/thumbnails/' . $userFolder . '/' . $physicalFilename;
    
    // Insérer dans la base de données
    $stmt = $pdo->prepare("
        INSERT INTO images 
        (user_id, filename, original_filename, file_path, thumbnail_path, 
         width, height, file_size, mime_type, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $mimeType = mime_content_type($finalPath);
    
    $stmt->execute([
        $userId,
        $physicalFilename,
        $finalName,
        $relativeFilePath,
        $relativeThumbPath,
        $width,
        $height,
        $fileSize,
        $mimeType
    ]);
    
    $newImageId = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'message' => 'Image sauvegardée avec succès',
        'image_id' => $newImageId,
        'filename' => $finalName,
        'dimensions' => ['width' => $width, 'height' => $height],
        'size' => $fileSize
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
