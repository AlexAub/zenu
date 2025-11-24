<?php
/**
 * API pour sauvegarder les résultats des traitements IA
 * Sauvegarde l'image traitée dans le compte utilisateur
 */

require_once '../config.php';
require_once '../security.php';

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

$input = json_decode(file_get_contents('php://input'), true);
$imageData = $input['image_data'] ?? ''; // Chemin de l'image temporaire
$originalId = intval($input['original_id'] ?? 0);
$tool = $input['tool'] ?? 'unknown';

$userId = $_SESSION['user_id'];

try {
    // Vérifier que l'image temporaire existe
    $tempPath = '../' . $imageData;
    if (!file_exists($tempPath)) {
        throw new Exception("Fichier temporaire introuvable");
    }
    
    // Récupérer l'image originale pour avoir le nom
    $stmt = $pdo->prepare("SELECT * FROM images WHERE id = ? AND user_id = ?");
    $stmt->execute([$originalId, $userId]);
    $originalImage = $stmt->fetch();
    
    if (!$originalImage) {
        throw new Exception("Image originale non trouvée");
    }
    
    // Récupérer le username pour le dossier
    $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        throw new Exception("Utilisateur non trouvé");
    }
    
    // Créer le nom du fichier avec suffixe selon l'outil
    $toolSuffixes = [
        'remove-bg' => '_nobg',
        'enhance' => '_enhanced',
        'smart-crop' => '_cropped',
        'optimize' => '_optimized',
        'upscale' => '_upscaled'
    ];
    
    $suffix = $toolSuffixes[$tool] ?? '_ai';
    
    // Extraire le nom et l'extension
    $originalFilename = pathinfo($originalImage['original_filename'] ?? $originalImage['filename'], PATHINFO_FILENAME);
    $extension = pathinfo($tempPath, PATHINFO_EXTENSION);
    
    // Construire le nom final
    $baseFilename = $originalFilename . $suffix;
    $filename = $baseFilename . '.' . $extension;
    $originalFilenameClean = $baseFilename;
    
    // Vérifier les doublons et ajouter un compteur si nécessaire
    $counter = 1;
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM images 
        WHERE user_id = ? 
        AND (original_filename = ? OR filename = ?)
        AND is_deleted = 0
    ");
    $stmt->execute([$userId, $originalFilenameClean, $filename]);
    
    while ($stmt->fetchColumn() > 0 && $counter < 100) {
        $originalFilenameClean = $baseFilename . '_' . $counter;
        $filename = $originalFilenameClean . '.' . $extension;
        $counter++;
        
        $stmt->execute([$userId, $originalFilenameClean, $filename]);
    }
    
    // Créer le dossier utilisateur si nécessaire
    $userDir = '../uploads/' . $user['username'];
    if (!is_dir($userDir)) {
        mkdir($userDir, 0755, true);
    }
    
    // Chemin de destination
    $destinationPath = $userDir . '/' . $filename;
    $dbFilepath = 'uploads/' . $user['username'] . '/' . $filename;
    
    // Copier le fichier temporaire vers la destination finale
    if (!copy($tempPath, $destinationPath)) {
        throw new Exception("Impossible de copier le fichier");
    }
    
    // Obtenir les dimensions de l'image
    $imageInfo = getimagesize($destinationPath);
    if ($imageInfo === false) {
        unlink($destinationPath);
        throw new Exception("Impossible de lire les dimensions");
    }
    
    list($width, $height) = $imageInfo;
    $fileSize = filesize($destinationPath);
    
    // Générer la miniature
    $thumbnailPath = null;
    try {
        $thumbDir = '../uploads/thumbnails/' . $user['username'];
        if (!is_dir($thumbDir)) {
            mkdir($thumbDir, 0755, true);
        }
        
        $thumbFilename = 'thumb_' . $filename;
        $thumbPath = $thumbDir . '/' . $thumbFilename;
        
        // Créer la miniature
        $thumb = imagecreatetruecolor(200, 200);
        
        switch ($imageInfo[2]) {
            case IMAGETYPE_JPEG:
                $source = imagecreatefromjpeg($destinationPath);
                break;
            case IMAGETYPE_PNG:
                $source = imagecreatefrompng($destinationPath);
                imagealphablending($thumb, false);
                imagesavealpha($thumb, true);
                break;
            case IMAGETYPE_GIF:
                $source = imagecreatefromgif($destinationPath);
                break;
            case IMAGETYPE_WEBP:
                $source = imagecreatefromwebp($destinationPath);
                break;
            default:
                $source = null;
        }
        
        if ($source) {
            imagecopyresampled($thumb, $source, 0, 0, 0, 0, 200, 200, $width, $height);
            imagejpeg($thumb, $thumbPath, 85);
            imagedestroy($source);
            imagedestroy($thumb);
            
            $thumbnailPath = 'uploads/thumbnails/' . $user['username'] . '/' . $thumbFilename;
        }
    } catch (Exception $e) {
        // Continuer même si la miniature échoue
        error_log("Thumbnail creation failed: " . $e->getMessage());
    }
    
    // Insérer dans la base de données
    $stmt = $pdo->prepare("
        INSERT INTO images (
            user_id, filename, original_filename, file_path, thumbnail_path,
            width, height, file_size, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $success = $stmt->execute([
        $userId,
        $filename,
        $originalFilenameClean,
        $dbFilepath,
        $thumbnailPath,
        $width,
        $height,
        $fileSize
    ]);
    
    if (!$success) {
        // Supprimer les fichiers en cas d'erreur BDD
        unlink($destinationPath);
        if ($thumbnailPath && file_exists('../' . $thumbnailPath)) {
            unlink('../' . $thumbnailPath);
        }
        throw new Exception("Erreur lors de l'enregistrement en base de données");
    }
    
    $imageId = $pdo->lastInsertId();
    
    // Construire l'URL jolie
    $prettyUrl = SITE_URL . '/' . $user['username'] . '/' . urlencode($originalFilenameClean);
    
    // Message de succès
    $message = '✅ Image sauvegardée avec succès !';
    if ($counter > 1) {
        $message = "✅ Image sauvegardée sous le nom '$originalFilenameClean'";
    }
    
    // Supprimer le fichier temporaire
    @unlink($tempPath);
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'image_id' => $imageId,
        'url' => $prettyUrl,
        'filename' => $originalFilenameClean,
        'file_path' => $dbFilepath,
        'thumbnail_path' => $thumbnailPath,
        'had_suffix' => $counter > 1
    ]);
    
} catch (Exception $e) {
    error_log("Save AI result error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>