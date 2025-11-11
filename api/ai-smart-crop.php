<?php
/**
 * API de recadrage intelligent avec IA
 * Détecte le sujet principal et recadre de manière optimale
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
    
    // Options
    $aspectRatio = $options['aspectRatio'] ?? 'auto';
    $detectionPriority = $options['detectionPriority'] ?? 'subject';
    
    // Détecter la zone d'intérêt
    $roi = detectRegionOfInterest($sourceImage, $width, $height, $detectionPriority);
    
    // Calculer les dimensions de recadrage selon le ratio demandé
    $cropDimensions = calculateCropDimensions($width, $height, $aspectRatio, $roi);
    
    // Créer l'image recadrée
    $croppedImage = imagecreatetruecolor($cropDimensions['width'], $cropDimensions['height']);
    
    // Préserver la transparence
    if ($type == IMAGETYPE_PNG) {
        imagealphablending($croppedImage, false);
        imagesavealpha($croppedImage, true);
    }
    
    imagecopyresampled(
        $croppedImage, $sourceImage,
        0, 0,
        $cropDimensions['x'], $cropDimensions['y'],
        $cropDimensions['width'], $cropDimensions['height'],
        $cropDimensions['width'], $cropDimensions['height']
    );
    
    // Sauvegarder
    $tempDir = '../uploads/temp';
    if (!is_dir($tempDir)) {
        mkdir($tempDir, 0755, true);
    }
    
    $tempFilename = 'ai_cropped_' . uniqid() . '.jpg';
    $tempPath = $tempDir . '/' . $tempFilename;
    
    imagejpeg($croppedImage, $tempPath, 95);
    
    imagedestroy($sourceImage);
    imagedestroy($croppedImage);
    
    $relativePath = 'uploads/temp/' . $tempFilename;
    
    echo json_encode([
        'success' => true,
        'processed_image' => $relativePath,
        'filename' => pathinfo($image['filename'], PATHINFO_FILENAME) . '_cropped.jpg',
        'original_dimensions' => ['width' => $width, 'height' => $height],
        'cropped_dimensions' => [
            'width' => $cropDimensions['width'],
            'height' => $cropDimensions['height']
        ],
        'crop_position' => ['x' => $cropDimensions['x'], 'y' => $cropDimensions['y']],
        'roi_detected' => $roi
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Détecter la région d'intérêt dans l'image
 */
function detectRegionOfInterest($image, $width, $height, $priority) {
    switch ($priority) {
        case 'face':
            return detectFaces($image, $width, $height);
        case 'subject':
            return detectSubject($image, $width, $height);
        case 'center':
            return ['x' => $width / 2, 'y' => $height / 2, 'weight' => 1];
        case 'rule-thirds':
            return applyRuleOfThirds($width, $height);
        default:
            return detectSubject($image, $width, $height);
    }
}

/**
 * Détection simplifiée de visages (par contraste et couleur chair)
 */
function detectFaces($image, $width, $height) {
    $skinToneRegions = [];
    $step = max(1, min($width, $height) / 50);
    
    for ($x = 0; $x < $width; $x += $step) {
        for ($y = 0; $y < $height; $y += $step) {
            $rgb = imagecolorat($image, $x, $y);
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;
            
            // Détection simplifiée de teinte chair
            if (isSkinTone($r, $g, $b)) {
                $skinToneRegions[] = ['x' => $x, 'y' => $y];
            }
        }
    }
    
    if (empty($skinToneRegions)) {
        return detectSubject($image, $width, $height);
    }
    
    // Calculer le centre de masse des régions de peau
    $centerX = array_sum(array_column($skinToneRegions, 'x')) / count($skinToneRegions);
    $centerY = array_sum(array_column($skinToneRegions, 'y')) / count($skinToneRegions);
    
    return ['x' => $centerX, 'y' => $centerY, 'weight' => count($skinToneRegions)];
}

/**
 * Vérifier si une couleur correspond à un teinte chair
 */
function isSkinTone($r, $g, $b) {
    // Critères simplifiés pour détecter la peau
    return ($r > 95 && $g > 40 && $b > 20 &&
            $r > $g && $r > $b &&
            abs($r - $g) > 15 &&
            $r - $b > 15);
}

/**
 * Détecter le sujet principal par analyse de contraste et de netteté
 */
function detectSubject($image, $width, $height) {
    $edgeMap = [];
    $step = max(1, min($width, $height) / 50);
    
    for ($x = $step; $x < $width - $step; $x += $step) {
        for ($y = $step; $y < $height - $step; $y += $step) {
            // Calculer le gradient (détection de bords)
            $gradient = calculateGradient($image, $x, $y);
            
            if ($gradient > 30) { // Seuil de détection
                $edgeMap[] = ['x' => $x, 'y' => $y, 'strength' => $gradient];
            }
        }
    }
    
    if (empty($edgeMap)) {
        return ['x' => $width / 2, 'y' => $height / 2, 'weight' => 1];
    }
    
    // Pondérer par la force du gradient
    $totalWeight = array_sum(array_column($edgeMap, 'strength'));
    $centerX = 0;
    $centerY = 0;
    
    foreach ($edgeMap as $point) {
        $weight = $point['strength'] / $totalWeight;
        $centerX += $point['x'] * $weight;
        $centerY += $point['y'] * $weight;
    }
    
    return ['x' => $centerX, 'y' => $centerY, 'weight' => count($edgeMap)];
}

/**
 * Calculer le gradient (Sobel simplifié)
 */
function calculateGradient($image, $x, $y) {
    $colors = [];
    for ($dx = -1; $dx <= 1; $dx++) {
        for ($dy = -1; $dy <= 1; $dy++) {
            $rgb = imagecolorat($image, $x + $dx, $y + $dy);
            $colors[] = (($rgb >> 16) & 0xFF) + (($rgb >> 8) & 0xFF) + ($rgb & 0xFF);
        }
    }
    
    $gx = abs($colors[2] - $colors[0] + 2 * ($colors[5] - $colors[3]) + $colors[8] - $colors[6]);
    $gy = abs($colors[6] - $colors[0] + 2 * ($colors[7] - $colors[1]) + $colors[8] - $colors[2]);
    
    return sqrt($gx * $gx + $gy * $gy);
}

/**
 * Appliquer la règle des tiers
 */
function applyRuleOfThirds($width, $height) {
    // Points forts de la règle des tiers
    $points = [
        ['x' => $width / 3, 'y' => $height / 3],
        ['x' => 2 * $width / 3, 'y' => $height / 3],
        ['x' => $width / 3, 'y' => 2 * $height / 3],
        ['x' => 2 * $width / 3, 'y' => 2 * $height / 3],
    ];
    
    // Choisir le point le plus proche du centre (compromis)
    $centerX = $width / 2;
    $centerY = $height / 2;
    
    usort($points, function($a, $b) use ($centerX, $centerY) {
        $distA = sqrt(pow($a['x'] - $centerX, 2) + pow($a['y'] - $centerY, 2));
        $distB = sqrt(pow($b['x'] - $centerX, 2) + pow($b['y'] - $centerY, 2));
        return $distA - $distB;
    });
    
    return ['x' => $points[0]['x'], 'y' => $points[0]['y'], 'weight' => 1];
}

/**
 * Calculer les dimensions de recadrage optimales
 */
function calculateCropDimensions($width, $height, $aspectRatio, $roi) {
    // Ratios prédéfinis
    $ratios = [
        '1:1' => 1,
        '16:9' => 16/9,
        '9:16' => 9/16,
        '4:3' => 4/3,
        '3:4' => 3/4,
        '21:9' => 21/9,
    ];
    
    if ($aspectRatio === 'auto') {
        // Garder le ratio original
        $targetRatio = $width / $height;
    } else {
        $targetRatio = $ratios[$aspectRatio] ?? ($width / $height);
    }
    
    $currentRatio = $width / $height;
    
    if ($targetRatio > $currentRatio) {
        // Image finale plus large
        $cropWidth = $width;
        $cropHeight = round($width / $targetRatio);
    } else {
        // Image finale plus haute
        $cropHeight = $height;
        $cropWidth = round($height * $targetRatio);
    }
    
    // Centrer sur la ROI
    $cropX = max(0, min($width - $cropWidth, round($roi['x'] - $cropWidth / 2)));
    $cropY = max(0, min($height - $cropHeight, round($roi['y'] - $cropHeight / 2)));
    
    return [
        'x' => $cropX,
        'y' => $cropY,
        'width' => $cropWidth,
        'height' => $cropHeight
    ];
}
