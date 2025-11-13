<?php
/**
 * API d'amélioration automatique - Version optimisée
 * Améliore l'image sans dégrader la qualité ni augmenter la taille
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
    
    // Charger l'image
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
    
    // Options
    $intensity = ($options['intensity'] ?? 50) / 100;
    $applyBrightness = $options['brightness'] ?? true;
    $applyContrast = $options['contrast'] ?? true;
    $applySaturation = $options['saturation'] ?? true;
    $applySharpness = $options['sharpness'] ?? false; // Désactivé par défaut
    
    // Analyser l'image
    $analysis = analyzeImage($sourceImage, $width, $height);
    
    // Créer l'image de résultat
    $resultImage = imagecreatetruecolor($width, $height);
    
    // Préserver la transparence pour PNG
    if ($type == IMAGETYPE_PNG) {
        imagealphablending($resultImage, false);
        imagesavealpha($resultImage, true);
    }
    
    imagecopy($resultImage, $sourceImage, 0, 0, 0, 0, $width, $height);
    
    // Appliquer les corrections de manière très subtile
    
    // 1. Luminosité (seulement si vraiment nécessaire)
    if ($applyBrightness && $analysis['needs_brightness']) {
        $adjust = $analysis['brightness_adjust'] * $intensity * 0.5;
        if (abs($adjust) >= 5) { // Ne rien faire si ajustement < 5
            imagefilter($resultImage, IMG_FILTER_BRIGHTNESS, round($adjust));
        }
    }
    
    // 2. Contraste (très subtil)
    if ($applyContrast && $analysis['needs_contrast']) {
        $adjust = $analysis['contrast_adjust'] * $intensity * 0.4;
        if (abs($adjust) >= 3) {
            imagefilter($resultImage, IMG_FILTER_CONTRAST, round($adjust));
        }
    }
    
    // 3. Saturation (minimal)
    if ($applySaturation && $analysis['needs_saturation']) {
        $adjust = $analysis['saturation_adjust'] * $intensity;
        if (abs($adjust) >= 5) {
            // Ajustement très subtil de la saturation
            adjustSaturationSubtle($resultImage, $width, $height, $adjust);
        }
    }
    
    // 4. Netteté (optionnel et très léger)
    if ($applySharpness && $intensity > 0.5) {
        // Netteté ultra-légère
        $matrix = [
            [-1, -1, -1],
            [-1, 16, -1],
            [-1, -1, -1]
        ];
        imageconvolution($resultImage, $matrix, 8, 0);
    }
    
    // Sauvegarder selon le format original
    $tempDir = '../uploads/temp';
    if (!is_dir($tempDir)) {
        mkdir($tempDir, 0755, true);
    }
    
    $originalExt = strtolower(pathinfo($image['filename'], PATHINFO_EXTENSION));
    $tempFilename = 'ai_enhanced_' . uniqid() . '.' . $originalExt;
    $tempPath = $tempDir . '/' . $tempFilename;
    
    // Sauvegarder avec qualité maximale
    switch ($type) {
        case IMAGETYPE_JPEG:
            imagejpeg($resultImage, $tempPath, 95); // Qualité 95 au lieu de 98 pour équilibrer taille/qualité
            break;
        case IMAGETYPE_PNG:
            imagepng($resultImage, $tempPath, 6); // Compression 6/9 pour équilibrer
            break;
        case IMAGETYPE_GIF:
            imagegif($resultImage, $tempPath);
            break;
        case IMAGETYPE_WEBP:
            imagewebp($resultImage, $tempPath, 95);
            break;
    }
    
    imagedestroy($sourceImage);
    imagedestroy($resultImage);
    
    $relativePath = 'uploads/temp/' . $tempFilename;
    
    echo json_encode([
        'success' => true,
        'processed_image' => $relativePath,
        'filename' => pathinfo($image['filename'], PATHINFO_FILENAME) . '_enhanced.' . $originalExt,
        'original_size' => filesize($fullPath),
        'processed_size' => filesize($tempPath),
        'analysis' => [
            'brightness_adjusted' => $analysis['needs_brightness'],
            'contrast_adjusted' => $analysis['needs_contrast'],
            'saturation_adjusted' => $analysis['needs_saturation'],
            'avg_brightness' => round($analysis['avg_brightness'], 2),
            'recommendations' => $analysis['recommendations']
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Analyser l'image - Version optimisée avec seuils stricts
 */
function analyzeImage($image, $width, $height) {
    $totalBrightness = 0;
    $pixelCount = 0;
    $brightnessValues = [];
    
    // Échantillonnage tous les 10 pixels pour la performance
    $step = max(5, min($width, $height) / 50);
    
    for ($x = 0; $x < $width; $x += $step) {
        for ($y = 0; $y < $height; $y += $step) {
            $rgb = imagecolorat($image, $x, $y);
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;
            
            $brightness = ($r + $g + $b) / 3;
            $totalBrightness += $brightness;
            $brightnessValues[] = $brightness;
            $pixelCount++;
        }
    }
    
    $avgBrightness = $totalBrightness / $pixelCount;
    
    // Calculer l'écart-type (contraste)
    $variance = 0;
    foreach ($brightnessValues as $b) {
        $variance += pow($b - $avgBrightness, 2);
    }
    $stdDev = sqrt($variance / $pixelCount);
    $contrastLevel = $stdDev / 127.5;
    
    // Décisions avec seuils TRÈS stricts (intervenir seulement si vraiment nécessaire)
    $analysis = [
        'avg_brightness' => $avgBrightness,
        'contrast_level' => $contrastLevel,
        'needs_brightness' => false,
        'brightness_adjust' => 0,
        'needs_contrast' => false,
        'contrast_adjust' => 0,
        'needs_saturation' => false,
        'saturation_adjust' => 0,
        'recommendations' => []
    ];
    
    // Luminosité : intervenir seulement si VRAIMENT sombre ou clair
    if ($avgBrightness < 60) {
        // Image extrêmement sombre
        $analysis['needs_brightness'] = true;
        $analysis['brightness_adjust'] = (100 - $avgBrightness) * 0.4;
        $analysis['recommendations'][] = 'Luminosité augmentée';
    } elseif ($avgBrightness > 220) {
        // Image extrêmement claire
        $analysis['needs_brightness'] = true;
        $analysis['brightness_adjust'] = (150 - $avgBrightness) * 0.3;
        $analysis['recommendations'][] = 'Luminosité réduite';
    }
    
    // Contraste : intervenir seulement si extrême
    if ($contrastLevel < 0.15) {
        // Contraste extrêmement faible
        $analysis['needs_contrast'] = true;
        $analysis['contrast_adjust'] = -8;
        $analysis['recommendations'][] = 'Contraste augmenté';
    } elseif ($contrastLevel > 0.85) {
        // Contraste extrêmement élevé
        $analysis['needs_contrast'] = true;
        $analysis['contrast_adjust'] = 5;
        $analysis['recommendations'][] = 'Contraste réduit';
    }
    
    // Saturation : rarement nécessaire
    // On ne touche pas sauf si l'utilisateur le demande explicitement
    
    if (empty($analysis['recommendations'])) {
        $analysis['recommendations'][] = 'Image déjà bien équilibrée';
    }
    
    return $analysis;
}

/**
 * Ajuster subtilement la saturation
 */
function adjustSaturationSubtle(&$image, $width, $height, $adjustment) {
    // Ajustement minimal pour éviter la dégradation
    $factor = 1 + ($adjustment / 100);
    
    // Limiter l'ajustement entre 0.95 et 1.05 (max 5%)
    $factor = max(0.95, min(1.05, $factor));
    
    if (abs($factor - 1) < 0.02) {
        return; // Trop faible, ne rien faire
    }
    
    for ($x = 0; $x < $width; $x++) {
        for ($y = 0; $y < $height; $y++) {
            $rgb = imagecolorat($image, $x, $y);
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;
            
            // Convertir en HSL
            $max = max($r, $g, $b);
            $min = min($r, $g, $b);
            $l = ($max + $min) / 2;
            
            if ($max == $min) {
                continue; // Gris, pas de saturation
            }
            
            $d = $max - $min;
            $s = $l > 127.5 ? $d / (510 - $max - $min) : $d / ($max + $min);
            
            // Ajuster la saturation
            $s *= $factor;
            $s = max(0, min(1, $s));
            
            // Reconvertir en RGB (simplifié)
            if ($l < 127.5) {
                $temp = $l * (1 + $s) / 127.5;
            } else {
                $temp = ($l + (255 - $l) * $s) / 127.5;
            }
            
            $r = (int)($r * $temp);
            $g = (int)($g * $temp);
            $b = (int)($b * $temp);
            
            $r = max(0, min(255, $r));
            $g = max(0, min(255, $g));
            $b = max(0, min(255, $b));
            
            $color = imagecolorallocate($image, $r, $g, $b);
            imagesetpixel($image, $x, $y, $color);
        }
    }
}
?>