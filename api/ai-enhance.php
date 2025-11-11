<?php
/**
 * API d'amélioration automatique d'image avec IA
 * Optimise luminosité, contraste, saturation et netteté
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
    
    // Récupérer les options
    $intensity = ($options['intensity'] ?? 50) / 100; // Convertir en 0-1
    $applyBrightness = $options['brightness'] ?? true;
    $applyContrast = $options['contrast'] ?? true;
    $applySaturation = $options['saturation'] ?? true;
    $applySharpness = $options['sharpness'] ?? true;
    
    // Analyser l'image pour déterminer les corrections nécessaires
    $analysis = analyzeImage($sourceImage, $width, $height);
    
    // Créer l'image de résultat
    $resultImage = imagecreatetruecolor($width, $height);
    
    // Préserver la transparence pour PNG
    if ($type == IMAGETYPE_PNG) {
        imagealphablending($resultImage, false);
        imagesavealpha($resultImage, true);
    }
    
    imagecopy($resultImage, $sourceImage, 0, 0, 0, 0, $width, $height);
    
    // Appliquer les améliorations de manière plus subtile pour préserver la qualité
    
    // 1. Luminosité automatique (réduite de 60% pour éviter la dégradation)
    if ($applyBrightness && $analysis['needs_brightness_adjustment']) {
        $brightnessAdjust = $analysis['brightness_adjustment'] * $intensity * 0.4;
        imagefilter($resultImage, IMG_FILTER_BRIGHTNESS, round($brightnessAdjust));
    }
    
    // 2. Contraste automatique (réduit de 50%)
    if ($applyContrast && $analysis['needs_contrast_adjustment']) {
        $contrastAdjust = $analysis['contrast_adjustment'] * $intensity * 0.5;
        imagefilter($resultImage, IMG_FILTER_CONTRAST, round($contrastAdjust));
    }
    
    // 3. Saturation des couleurs (très réduite - seulement 25% de l'intensité)
    if ($applySaturation && $analysis['needs_saturation_adjustment']) {
        // Ajuster la saturation de manière très subtile pour éviter la perte de qualité
        $saturationFactor = 1 + ($analysis['saturation_adjustment'] * $intensity * 0.25);
        adjustSaturation($resultImage, $width, $height, $saturationFactor);
    }
    
    // 4. Netteté (désactivée par défaut car cause principale de dégradation)
    if ($applySharpness && $intensity > 0.6) {
        // Appliquer la netteté UNIQUEMENT si l'intensité est > 60%
        // Et de manière très légère pour éviter les artefacts
        $sharpnessLevel = min($intensity * 0.3, 12); // Maximum 12% au lieu de 150%
        $matrix = [
            [0, -1, 0],
            [-1, 4 + $sharpnessLevel, -1],
            [0, -1, 0]
        ];
        $divisor = array_sum(array_map('array_sum', $matrix));
        if ($divisor != 0) {
            imageconvolution($resultImage, $matrix, $divisor, 0);
        }
    }
    
    // 5. NE PAS appliquer de réduction de bruit car elle dégrade la qualité
    // imagefilter($resultImage, IMG_FILTER_SMOOTH, 1); // DÉSACTIVÉ
    
    // Sauvegarder le résultat
    $tempDir = '../uploads/temp';
    if (!is_dir($tempDir)) {
        mkdir($tempDir, 0755, true);
    }
    
    $tempFilename = 'ai_enhanced_' . uniqid() . '.jpg';
    $tempPath = $tempDir . '/' . $tempFilename;
    
    // Sauvegarder avec qualité maximale (98%) pour préserver les détails
    imagejpeg($resultImage, $tempPath, 98);
    
    // Libérer la mémoire
    imagedestroy($sourceImage);
    imagedestroy($resultImage);
    
    $relativePath = 'uploads/temp/' . $tempFilename;
    
    echo json_encode([
        'success' => true,
        'processed_image' => $relativePath,
        'filename' => pathinfo($image['filename'], PATHINFO_FILENAME) . '_enhanced.jpg',
        'original_size' => filesize($fullPath),
        'processed_size' => filesize($tempPath),
        'analysis' => [
            'brightness_adjusted' => $analysis['needs_brightness_adjustment'],
            'contrast_adjusted' => $analysis['needs_contrast_adjustment'],
            'saturation_adjusted' => $analysis['needs_saturation_adjustment'],
            'average_brightness' => round($analysis['avg_brightness'], 2),
            'contrast_level' => round($analysis['contrast_level'], 2)
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Analyser l'image pour déterminer les corrections nécessaires
 */
function analyzeImage($image, $width, $height) {
    $totalBrightness = 0;
    $pixelCount = 0;
    $rgbValues = ['r' => [], 'g' => [], 'b' => []];
    
    // Échantillonnage tous les 5 pixels pour la performance
    $step = max(1, min($width, $height) / 100);
    
    for ($x = 0; $x < $width; $x += $step) {
        for ($y = 0; $y < $height; $y += $step) {
            $rgb = imagecolorat($image, $x, $y);
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;
            
            $brightness = ($r + $g + $b) / 3;
            $totalBrightness += $brightness;
            $pixelCount++;
            
            $rgbValues['r'][] = $r;
            $rgbValues['g'][] = $g;
            $rgbValues['b'][] = $b;
        }
    }
    
    $avgBrightness = $totalBrightness / $pixelCount;
    
    // Calculer le contraste (écart-type de la luminosité)
    $variance = 0;
    for ($x = 0; $x < $width; $x += $step) {
        for ($y = 0; $y < $height; $y += $step) {
            $rgb = imagecolorat($image, $x, $y);
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;
            $brightness = ($r + $g + $b) / 3;
            
            $variance += pow($brightness - $avgBrightness, 2);
        }
    }
    $stdDev = sqrt($variance / $pixelCount);
    $contrastLevel = $stdDev / 127.5; // Normaliser
    
    // Calculer la saturation moyenne
    $avgSaturation = 0;
    foreach ($rgbValues['r'] as $i => $r) {
        $g = $rgbValues['g'][$i];
        $b = $rgbValues['b'][$i];
        
        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        
        if ($max > 0) {
            $avgSaturation += ($max - $min) / $max;
        }
    }
    $avgSaturation /= count($rgbValues['r']);
    
    // Déterminer les ajustements nécessaires
    $analysis = [
        'avg_brightness' => $avgBrightness,
        'contrast_level' => $contrastLevel,
        'avg_saturation' => $avgSaturation,
        'needs_brightness_adjustment' => false,
        'brightness_adjustment' => 0,
        'needs_contrast_adjustment' => false,
        'contrast_adjustment' => 0,
        'needs_saturation_adjustment' => false,
        'saturation_adjustment' => 0
    ];
    
    // Luminosité : idéal autour de 127 (milieu) - seuils plus stricts
    if ($avgBrightness < 80) {
        // Image TRÈS sombre (seuil abaissé de 100 à 80)
        $analysis['needs_brightness_adjustment'] = true;
        $analysis['brightness_adjustment'] = (127 - $avgBrightness) * 0.3; // Réduit de 0.5 à 0.3
    } elseif ($avgBrightness > 200) {
        // Image TRÈS claire (seuil relevé de 180 à 200)
        $analysis['needs_brightness_adjustment'] = true;
        $analysis['brightness_adjustment'] = (127 - $avgBrightness) * 0.2; // Réduit de 0.3 à 0.2
    }
    
    // Contraste : idéal entre 0.3 et 0.6 - seuils plus stricts
    if ($contrastLevel < 0.2) {
        // Contraste TRÈS faible (seuil abaissé de 0.25 à 0.2)
        $analysis['needs_contrast_adjustment'] = true;
        $analysis['contrast_adjustment'] = -10; // Réduit de -15 à -10
    } elseif ($contrastLevel > 0.8) {
        // Contraste TRÈS élevé (seuil relevé de 0.7 à 0.8)
        $analysis['needs_contrast_adjustment'] = true;
        $analysis['contrast_adjustment'] = 8; // Réduit de 10 à 8
    }
    
    // Saturation : idéal autour de 0.3-0.5 - seuils plus stricts
    if ($avgSaturation < 0.15) {
        // Image TRÈS désaturée (seuil abaissé de 0.2 à 0.15)
        $analysis['needs_saturation_adjustment'] = true;
        $analysis['saturation_adjustment'] = 0.15; // Réduit de 0.2 à 0.15
    } elseif ($avgSaturation > 0.8) {
        // Image TRÈS saturée (seuil relevé de 0.7 à 0.8)
        $analysis['needs_saturation_adjustment'] = true;
        $analysis['saturation_adjustment'] = -0.1; // Réduit de -0.15 à -0.1
    }
    
    return $analysis;
}

/**
 * Ajuster la saturation de l'image de manière optimisée
 */
function adjustSaturation(&$image, $width, $height, $factor) {
    // Si le facteur est très proche de 1, ne rien faire (évite traitement inutile)
    if (abs($factor - 1) < 0.05) {
        return;
    }
    
    // Échantillonnage pour grandes images (améliore la vitesse et préserve la qualité)
    $step = 1;
    if ($width * $height > 1000000) { // Images > 1MP
        $step = 2; // Traiter 1 pixel sur 2
    }
    
    for ($x = 0; $x < $width; $x += $step) {
        for ($y = 0; $y < $height; $y += $step) {
            $rgb = imagecolorat($image, $x, $y);
            $alpha = ($rgb >> 24) & 0xFF;
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;
            
            // Convertir en HSL, ajuster S, reconvertir en RGB
            $hsl = rgbToHsl($r, $g, $b);
            $hsl['s'] = max(0, min(1, $hsl['s'] * $factor));
            $newRgb = hslToRgb($hsl['h'], $hsl['s'], $hsl['l']);
            
            $color = imagecolorallocatealpha($image, $newRgb['r'], $newRgb['g'], $newRgb['b'], $alpha);
            
            // Si échantillonnage, appliquer au bloc 2x2
            if ($step > 1) {
                for ($dx = 0; $dx < $step && ($x + $dx) < $width; $dx++) {
                    for ($dy = 0; $dy < $step && ($y + $dy) < $height; $dy++) {
                        imagesetpixel($image, $x + $dx, $y + $dy, $color);
                    }
                }
            } else {
                imagesetpixel($image, $x, $y, $color);
            }
        }
    }
}

/**
 * Convertir RGB en HSL
 */
function rgbToHsl($r, $g, $b) {
    $r /= 255;
    $g /= 255;
    $b /= 255;
    
    $max = max($r, $g, $b);
    $min = min($r, $g, $b);
    $l = ($max + $min) / 2;
    
    if ($max == $min) {
        $h = $s = 0;
    } else {
        $d = $max - $min;
        $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);
        
        switch ($max) {
            case $r:
                $h = (($g - $b) / $d + ($g < $b ? 6 : 0)) / 6;
                break;
            case $g:
                $h = (($b - $r) / $d + 2) / 6;
                break;
            case $b:
                $h = (($r - $g) / $d + 4) / 6;
                break;
        }
    }
    
    return ['h' => $h, 's' => $s, 'l' => $l];
}

/**
 * Convertir HSL en RGB
 */
function hslToRgb($h, $s, $l) {
    if ($s == 0) {
        $r = $g = $b = $l;
    } else {
        $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
        $p = 2 * $l - $q;
        
        $r = hueToRgb($p, $q, $h + 1/3);
        $g = hueToRgb($p, $q, $h);
        $b = hueToRgb($p, $q, $h - 1/3);
    }
    
    return [
        'r' => round($r * 255),
        'g' => round($g * 255),
        'b' => round($b * 255)
    ];
}

function hueToRgb($p, $q, $t) {
    if ($t < 0) $t += 1;
    if ($t > 1) $t -= 1;
    if ($t < 1/6) return $p + ($q - $p) * 6 * $t;
    if ($t < 1/2) return $q;
    if ($t < 2/3) return $p + ($q - $p) * (2/3 - $t) * 6;
    return $p;
}