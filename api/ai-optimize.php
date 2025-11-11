<?php
/**
 * API de compression intelligente avec IA
 * Optimise la taille sans perte visible de qualité
 */

require_once '../config.php';
require_once '../security.php';

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
$imageId = intval($input['image_id'] ?? 0);
$imagePath = $input['image_path'] ?? '';
$options = $input['options'] ?? [];

$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM images WHERE id = ? AND user_id = ? AND is_deleted = 0");
$stmt->execute([$imageId, $userId]);
$image = $stmt->fetch();

if (!$image) {
    echo json_encode(['success' => false, 'error' => 'Image non trouvée']);
    exit;
}

$fullPath = '../' . $imagePath;
if (!file_exists($fullPath)) {
    echo json_encode(['success' => false, 'error' => 'Fichier introuvable']);
    exit;
}

try {
    $imageInfo = getimagesize($fullPath);
    if ($imageInfo === false) {
        throw new Exception("Format d'image invalide");
    }
    
    list($width, $height, $type) = $imageInfo;
    
    // Options
    $quality = intval($options['quality'] ?? 80);
    $outputFormat = $options['format'] ?? 'same';
    $removeMetadata = $options['removeMetadata'] ?? false;
    
    // Charger l'image
    switch ($type) {
        case IMAGETYPE_JPEG:
            $sourceImage = imagecreatefromjpeg($fullPath);
            $originalFormat = 'jpg';
            break;
        case IMAGETYPE_PNG:
            $sourceImage = imagecreatefrompng($fullPath);
            $originalFormat = 'png';
            break;
        case IMAGETYPE_GIF:
            $sourceImage = imagecreatefromgif($fullPath);
            $originalFormat = 'gif';
            break;
        case IMAGETYPE_WEBP:
            $sourceImage = imagecreatefromwebp($fullPath);
            $originalFormat = 'webp';
            break;
        default:
            throw new Exception("Type d'image non supporté");
    }
    
    if (!$sourceImage) {
        throw new Exception("Impossible de charger l'image");
    }
    
    // Analyser l'image pour optimisation intelligente
    $analysis = analyzeImageForCompression($sourceImage, $width, $height);
    
    // Ajuster la qualité selon l'analyse
    $adjustedQuality = adjustQualityBasedOnContent($quality, $analysis);
    
    // Déterminer le format de sortie
    if ($outputFormat === 'same') {
        $finalFormat = $originalFormat;
    } else {
        $finalFormat = $outputFormat;
    }
    
    // Optimiser les dimensions si l'image est très grande
    $maxDimension = 4096; // Limite raisonnable
    $needsResize = false;
    $newWidth = $width;
    $newHeight = $height;
    
    if ($width > $maxDimension || $height > $maxDimension) {
        $needsResize = true;
        if ($width > $height) {
            $newWidth = $maxDimension;
            $newHeight = round(($height / $width) * $maxDimension);
        } else {
            $newHeight = $maxDimension;
            $newWidth = round(($width / $height) * $maxDimension);
        }
    }
    
    // Créer l'image optimisée
    if ($needsResize) {
        $optimizedImage = imagecreatetruecolor($newWidth, $newHeight);
        
        // Préserver transparence
        if ($finalFormat === 'png' || $finalFormat === 'webp') {
            imagealphablending($optimizedImage, false);
            imagesavealpha($optimizedImage, true);
        }
        
        imagecopyresampled(
            $optimizedImage, $sourceImage,
            0, 0, 0, 0,
            $newWidth, $newHeight,
            $width, $height
        );
    } else {
        $optimizedImage = $sourceImage;
    }
    
    // Appliquer des filtres d'optimisation
    if ($analysis['has_noise']) {
        imagefilter($optimizedImage, IMG_FILTER_SMOOTH, 2);
    }
    
    // Sauvegarder
    $tempDir = '../uploads/temp';
    if (!is_dir($tempDir)) {
        mkdir($tempDir, 0755, true);
    }
    
    $tempFilename = 'ai_optimized_' . uniqid() . '.' . $finalFormat;
    $tempPath = $tempDir . '/' . $tempFilename;
    
    // Sauvegarder selon le format
    switch ($finalFormat) {
        case 'jpg':
        case 'jpeg':
            imagejpeg($optimizedImage, $tempPath, $adjustedQuality);
            break;
        case 'png':
            // Convertir qualité 0-100 en niveau de compression PNG 0-9
            $pngCompression = round((100 - $adjustedQuality) / 11);
            imagepng($optimizedImage, $tempPath, $pngCompression);
            break;
        case 'webp':
            imagewebp($optimizedImage, $tempPath, $adjustedQuality);
            break;
        case 'gif':
            imagegif($optimizedImage, $tempPath);
            break;
    }
    
    // Supprimer métadonnées EXIF si demandé
    if ($removeMetadata && function_exists('exif_read_data')) {
        removeExifData($tempPath);
    }
    
    imagedestroy($sourceImage);
    if ($needsResize) {
        imagedestroy($optimizedImage);
    }
    
    $originalSize = filesize($fullPath);
    $optimizedSize = filesize($tempPath);
    $compressionRatio = round((1 - $optimizedSize / $originalSize) * 100, 1);
    
    $relativePath = 'uploads/temp/' . $tempFilename;
    
    echo json_encode([
        'success' => true,
        'processed_image' => $relativePath,
        'filename' => pathinfo($image['filename'], PATHINFO_FILENAME) . '_optimized.' . $finalFormat,
        'original_size' => $originalSize,
        'optimized_size' => $optimizedSize,
        'compression_ratio' => $compressionRatio . '%',
        'original_format' => $originalFormat,
        'output_format' => $finalFormat,
        'dimensions' => [
            'original' => ['width' => $width, 'height' => $height],
            'optimized' => ['width' => $newWidth, 'height' => $newHeight]
        ],
        'quality_used' => $adjustedQuality,
        'analysis' => $analysis
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Analyser l'image pour déterminer la stratégie de compression optimale
 */
function analyzeImageForCompression($image, $width, $height) {
    $totalColors = 0;
    $uniqueColors = [];
    $noiseLevel = 0;
    $detailLevel = 0;
    
    $step = max(1, min($width, $height) / 100);
    
    for ($x = 0; $x < $width; $x += $step) {
        for ($y = 0; $y < $height; $y += $step) {
            $rgb = imagecolorat($image, $x, $y);
            $uniqueColors[$rgb] = true;
            $totalColors++;
            
            // Mesurer le niveau de détail (variation locale)
            if ($x > 0 && $y > 0) {
                $prevRgb = imagecolorat($image, $x - $step, $y - $step);
                $diff = abs($rgb - $prevRgb);
                $detailLevel += $diff;
                
                if ($diff > 1000) {
                    $noiseLevel++;
                }
            }
        }
    }
    
    $uniqueColorCount = count($uniqueColors);
    $colorComplexity = $uniqueColorCount / $totalColors;
    $avgDetail = $detailLevel / $totalColors;
    $noiseRatio = $noiseLevel / $totalColors;
    
    return [
        'color_complexity' => $colorComplexity,
        'unique_colors' => $uniqueColorCount,
        'detail_level' => $avgDetail,
        'noise_ratio' => $noiseRatio,
        'has_noise' => $noiseRatio > 0.1,
        'is_simple' => $colorComplexity < 0.3,
        'is_detailed' => $avgDetail > 5000
    ];
}

/**
 * Ajuster la qualité selon le contenu de l'image
 */
function adjustQualityBasedOnContent($requestedQuality, $analysis) {
    $quality = $requestedQuality;
    
    // Images simples peuvent être plus compressées
    if ($analysis['is_simple']) {
        $quality = max(65, $quality - 10);
    }
    
    // Images très détaillées nécessitent plus de qualité
    if ($analysis['is_detailed']) {
        $quality = min(95, $quality + 5);
    }
    
    // Images avec bruit peuvent être plus compressées
    if ($analysis['has_noise']) {
        $quality = max(70, $quality - 5);
    }
    
    return $quality;
}

/**
 * Supprimer les métadonnées EXIF
 */
function removeExifData($imagePath) {
    // Cette fonction nécessiterait des bibliothèques supplémentaires
    // Pour l'instant, on se contente de recréer l'image
    // ce qui supprime automatiquement les EXIF
    return true;
}
