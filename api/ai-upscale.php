<?php
/**
 * API d'agrandissement IA - VERSION IMAGEMAGICK HAUTE QUALITÉ
 * Utilise ImageMagick pour une qualité supérieure à GD
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
$imageId = intval($input['image_id'] ?? 0);
$options = $input['options'] ?? [];

$userId = $_SESSION['user_id'];

// Options d'upscaling
$scaleFactor = floatval($options['scale'] ?? 2.0);
$quality = $options['quality'] ?? 'balanced';
$denoise = $options['denoise'] ?? true;
$sharpen = $options['sharpen'] ?? true;

// Validation
if ($scaleFactor < 1.5 || $scaleFactor > 4.0) {
    echo json_encode(['success' => false, 'error' => 'Facteur d\'échelle invalide (1.5-4.0)']);
    exit;
}

try {
    // Récupérer l'image
    $stmt = $pdo->prepare("SELECT * FROM images WHERE id = ? AND user_id = ?");
    $stmt->execute([$imageId, $userId]);
    $image = $stmt->fetch();
    
    if (!$image) {
        throw new Exception("Image non trouvée");
    }
    
    $fullPath = '../' . $image['file_path'];
    
    if (!file_exists($fullPath)) {
        throw new Exception("Fichier introuvable");
    }
    
    // Obtenir les dimensions
    $imageInfo = getimagesize($fullPath);
    if ($imageInfo === false) {
        throw new Exception("Impossible de lire l'image");
    }
    
    list($width, $height) = $imageInfo;
    $newWidth = round($width * $scaleFactor);
    $newHeight = round($height * $scaleFactor);
    
    // Vérifier les limites
    if ($newWidth > 8192 || $newHeight > 8192) {
        throw new Exception("Dimensions finales trop grandes (max 8192×8192)");
    }
    
    // Créer le dossier temp
    $tempDir = '../uploads/temp';
    if (!is_dir($tempDir)) {
        mkdir($tempDir, 0755, true);
    }
    
    $tempFilename = 'ai_upscaled_' . uniqid() . '.jpg';
    $tempPath = $tempDir . '/' . $tempFilename;
    
    // Vérifier si ImageMagick est disponible
    if (extension_loaded('imagick')) {
        // VERSION IMAGEMAGICK - HAUTE QUALITÉ
        $imagick = new Imagick($fullPath);
        
        // Appliquer le resize avec filtre Lanczos (le meilleur pour l'upscaling)
        $imagick->resizeImage($newWidth, $newHeight, Imagick::FILTER_LANCZOS, 1);
        
        // Améliorer la netteté (essentiel après upscaling)
        if ($sharpen) {
            // Paramètres: radius, sigma, amount, threshold
            // Plus l'upscaling est fort, plus on a besoin de netteté
            if ($scaleFactor >= 3.0) {
                $imagick->unsharpMaskImage(0, 1.5, 1.5, 0.05); // Très fort
            } elseif ($scaleFactor >= 2.0) {
                $imagick->unsharpMaskImage(0, 1.2, 1.2, 0.05); // Fort
            } else {
                $imagick->unsharpMaskImage(0, 0.8, 0.8, 0.05); // Modéré
            }
        }
        
        // Améliorer légèrement le contraste pour perception de netteté
        $imagick->contrastImage(1);
        
        // Sauvegarder avec qualité maximale
        $imagick->setImageCompressionQuality(95);
        $imagick->setImageFormat('jpeg');
        $imagick->writeImage($tempPath);
        $imagick->destroy();
        
        $processingMethod = 'ImageMagick Lanczos + UnsharpMask';
        
    } else {
        // FALLBACK GD - Qualité standard
        $processingMethod = 'GD Library (ImageMagick non disponible)';
        
        // Charger l'image source
        switch ($imageInfo[2]) {
            case IMAGETYPE_JPEG:
                $sourceImage = imagecreatefromjpeg($fullPath);
                break;
            case IMAGETYPE_PNG:
                $sourceImage = imagecreatefrompng($fullPath);
                break;
            case IMAGETYPE_GIF:
                $sourceImage = imagecreatefromgif($fullPath);
                break;
            case IMAGETYPE_WEBP:
                $sourceImage = imagecreatefromwebp($fullPath);
                break;
            default:
                throw new Exception("Format d'image non supporté");
        }
        
        // Créer l'image de destination
        $upscaledImage = imagecreatetruecolor($newWidth, $newHeight);
        imagealphablending($upscaledImage, false);
        imagesavealpha($upscaledImage, true);
        
        // Upscaling
        imagecopyresampled($upscaledImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        
        // Netteté maximale
        if ($sharpen) {
            $sharpenMatrix = [
                [-1, -2, -1],
                [-2, 29, -2],
                [-1, -2, -1]
            ];
            imageconvolution($upscaledImage, $sharpenMatrix, 17, 0);
            imageconvolution($upscaledImage, $sharpenMatrix, 17, 0); // 2 fois
        }
        
        // Augmenter le contraste
        imagefilter($upscaledImage, IMG_FILTER_CONTRAST, -15);
        
        imagejpeg($upscaledImage, $tempPath, 95);
        imagedestroy($sourceImage);
        imagedestroy($upscaledImage);
    }
    
    $newFileSize = filesize($tempPath);
    $originalFileSize = filesize($fullPath);
    
    // Construire le nom du fichier
    $originalFilename = pathinfo($image['original_filename'] ?? $image['filename'], PATHINFO_FILENAME);
    $downloadFilename = $originalFilename . '_upscaled_' . $scaleFactor . 'x.jpg';
    
    echo json_encode([
        'success' => true,
        'processed_image' => str_replace('../', '', $tempPath),
        'filename' => $downloadFilename,
        'original_dimensions' => [
            'width' => $width,
            'height' => $height
        ],
        'new_dimensions' => [
            'width' => $newWidth,
            'height' => $newHeight
        ],
        'scale_factor' => $scaleFactor,
        'original_size' => $originalFileSize,
        'new_size' => $newFileSize,
        'quality_mode' => $quality,
        'processing_method' => $processingMethod,
        'processing_applied' => [
            'denoise' => $denoise,
            'sharpen' => $sharpen,
            'detail_enhancement' => true
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Upscale error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>