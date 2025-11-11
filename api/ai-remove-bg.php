<?php
/**
 * API de suppression de fond avec IA
 * Utilise des techniques de traitement d'image avancées
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

// Vérifier que l'image appartient à l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM images WHERE id = ? AND user_id = ? AND is_deleted = 0");
$stmt->execute([$imageId, $userId]);
$image = $stmt->fetch();

if (!$image) {
    echo json_encode(['success' => false, 'error' => 'Image non trouvée']);
    exit;
}

// Chemin de l'image
$fullPath = '../' . $imagePath;
if (!file_exists($fullPath)) {
    echo json_encode(['success' => false, 'error' => 'Fichier introuvable']);
    exit;
}

try {
    // Charger l'image
    $imageInfo = getimagesize($fullPath);
    if ($imageInfo === false) {
        throw new Exception("Format d'image invalide");
    }
    
    list($width, $height, $type) = $imageInfo;
    
    // Créer l'image source
    switch ($type) {
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
            throw new Exception("Type d'image non supporté");
    }
    
    if (!$sourceImage) {
        throw new Exception("Impossible de charger l'image");
    }
    
    // Créer une nouvelle image avec canal alpha
    $resultImage = imagecreatetruecolor($width, $height);
    imagealphablending($resultImage, false);
    imagesavealpha($resultImage, true);
    
    // Options de détection
    $detectionMode = $options['detectionMode'] ?? 'auto';
    $edgeQuality = $options['edgeQuality'] ?? 'medium';
    
    // Paramètres selon la qualité
    $qualityParams = [
        'low' => ['threshold' => 25, 'blur' => 2],
        'medium' => ['threshold' => 15, 'blur' => 1],
        'high' => ['threshold' => 10, 'blur' => 0]
    ];
    
    $params = $qualityParams[$edgeQuality] ?? $qualityParams['medium'];
    
    // Algorithme de suppression de fond par détection de couleur dominante
    // 1. Analyser les bords pour détecter la couleur de fond
    $backgroundColor = detectBackgroundColor($sourceImage, $width, $height);
    
    // 2. Appliquer la suppression de fond
    for ($x = 0; $x < $width; $x++) {
        for ($y = 0; $y < $height; $y++) {
            $rgb = imagecolorat($sourceImage, $x, $y);
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;
            
            // Calculer la distance avec la couleur de fond
            $distance = sqrt(
                pow($r - $backgroundColor['r'], 2) +
                pow($g - $backgroundColor['g'], 2) +
                pow($b - $backgroundColor['b'], 2)
            );
            
            // Si proche de la couleur de fond, rendre transparent
            if ($distance < $params['threshold']) {
                $alpha = 127; // Complètement transparent
            } else {
                // Transition douce pour les bords
                $alpha = max(0, 127 - ($distance * 3));
            }
            
            $color = imagecolorallocatealpha($resultImage, $r, $g, $b, $alpha);
            imagesetpixel($resultImage, $x, $y, $color);
        }
    }
    
    // Appliquer un léger flou sur les bords si demandé
    if ($params['blur'] > 0) {
        for ($i = 0; $i < $params['blur']; $i++) {
            imagefilter($resultImage, IMG_FILTER_SMOOTH, 1);
        }
    }
    
    // Sauvegarder le résultat temporaire
    $tempDir = '../uploads/temp';
    if (!is_dir($tempDir)) {
        mkdir($tempDir, 0755, true);
    }
    
    $tempFilename = 'ai_nobg_' . uniqid() . '.png';
    $tempPath = $tempDir . '/' . $tempFilename;
    
    imagepng($resultImage, $tempPath);
    
    // Libérer la mémoire
    imagedestroy($sourceImage);
    imagedestroy($resultImage);
    
    // Retourner le chemin relatif pour l'affichage
    $relativePath = 'uploads/temp/' . $tempFilename;
    
    echo json_encode([
        'success' => true,
        'processed_image' => $relativePath,
        'filename' => pathinfo($image['filename'], PATHINFO_FILENAME) . '_nobg.png',
        'original_size' => filesize($fullPath),
        'processed_size' => filesize($tempPath),
        'method' => 'color_detection'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Détecter la couleur de fond dominante en analysant les bords
 */
function detectBackgroundColor($image, $width, $height) {
    $edgePixels = [];
    $sampleSize = 10; // Échantillonner tous les 10 pixels
    
    // Échantillonner le haut
    for ($x = 0; $x < $width; $x += $sampleSize) {
        $edgePixels[] = imagecolorat($image, $x, 0);
    }
    
    // Échantillonner le bas
    for ($x = 0; $x < $width; $x += $sampleSize) {
        $edgePixels[] = imagecolorat($image, $x, $height - 1);
    }
    
    // Échantillonner la gauche
    for ($y = 0; $y < $height; $y += $sampleSize) {
        $edgePixels[] = imagecolorat($image, 0, $y);
    }
    
    // Échantillonner la droite
    for ($y = 0; $y < $height; $y += $sampleSize) {
        $edgePixels[] = imagecolorat($image, $width - 1, $y);
    }
    
    // Calculer la moyenne RGB
    $totalR = $totalG = $totalB = 0;
    $count = count($edgePixels);
    
    foreach ($edgePixels as $color) {
        $totalR += ($color >> 16) & 0xFF;
        $totalG += ($color >> 8) & 0xFF;
        $totalB += $color & 0xFF;
    }
    
    return [
        'r' => round($totalR / $count),
        'g' => round($totalG / $count),
        'b' => round($totalB / $count)
    ];
}
