<?php
/**
 * API de recadrage intelligent avec IA - VERSION AMÉLIORÉE
 * Détection de visages améliorée avec clustering et heuristiques
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
    
    // Détecter la zone d'intérêt avec le nouvel algorithme
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
        'roi_detected' => $roi,
        'detection_method' => $detectionPriority
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
            return detectFacesImproved($image, $width, $height);
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
 * NOUVELLE VERSION AMÉLIORÉE - Détection de visages avec clustering et heuristiques
 */
function detectFacesImproved($image, $width, $height) {
    // Étape 1: Détecter toutes les régions de teinte chair
    $skinRegions = detectSkinRegions($image, $width, $height);
    
    if (empty($skinRegions)) {
        // Pas de peau détectée, fallback sur détection de sujet
        return detectSubject($image, $width, $height);
    }
    
    // Étape 2: Regrouper les pixels en clusters (régions connexes)
    $clusters = clusterSkinRegions($skinRegions, $width, $height);
    
    if (empty($clusters)) {
        return detectSubject($image, $width, $height);
    }
    
    // Étape 3: Filtrer pour garder seulement les clusters qui ressemblent à des visages
    $faceCandidates = filterFaceCandidates($clusters, $image, $width, $height);
    
    if (empty($faceCandidates)) {
        // Aucun visage probable détecté, fallback
        return detectSubject($image, $width, $height);
    }
    
    // Étape 4: Sélectionner le meilleur candidat (le plus probable d'être un visage)
    $bestFace = selectBestFaceCandidate($faceCandidates, $width, $height);
    
    return [
        'x' => $bestFace['centerX'],
        'y' => $bestFace['centerY'],
        'weight' => $bestFace['score'],
        'bbox' => $bestFace['bbox']
    ];
}

/**
 * Détecter toutes les régions de teinte chair avec un échantillonnage dense
 */
function detectSkinRegions($image, $width, $height) {
    $skinPixels = [];
    // Échantillonnage plus dense pour meilleure précision
    $step = max(2, min($width, $height) / 100);
    
    for ($y = 0; $y < $height; $y += $step) {
        for ($x = 0; $x < $width; $x += $step) {
            if ($x >= $width || $y >= $height) continue;
            
            $rgb = imagecolorat($image, $x, $y);
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;
            
            if (isSkinToneImproved($r, $g, $b)) {
                $skinPixels[] = ['x' => $x, 'y' => $y, 'r' => $r, 'g' => $g, 'b' => $b];
            }
        }
    }
    
    return $skinPixels;
}

/**
 * Détection de teinte chair améliorée avec plusieurs critères
 */
function isSkinToneImproved($r, $g, $b) {
    // Critère 1: RGB classique
    $rgbCheck = ($r > 95 && $g > 40 && $b > 20 &&
                 $r > $g && $r > $b &&
                 abs($r - $g) > 15 &&
                 max($r, $g, $b) - min($r, $g, $b) > 15);
    
    if (!$rgbCheck) return false;
    
    // Critère 2: HSV pour exclure les faux positifs
    $hsv = rgbToHsv($r, $g, $b);
    $h = $hsv['h'];
    $s = $hsv['s'];
    $v = $hsv['v'];
    
    // Teinte chair typique: 0-50 degrés (rouge-orange-jaune)
    // Saturation modérée: 0.2-0.6
    // Luminosité: 0.4-0.95
    $hsvCheck = (($h >= 0 && $h <= 50) || ($h >= 340 && $h <= 360)) &&
                ($s >= 0.2 && $s <= 0.68) &&
                ($v >= 0.35 && $v <= 0.95);
    
    return $hsvCheck;
}

/**
 * Convertir RGB en HSV
 */
function rgbToHsv($r, $g, $b) {
    $r /= 255;
    $g /= 255;
    $b /= 255;
    
    $max = max($r, $g, $b);
    $min = min($r, $g, $b);
    $diff = $max - $min;
    
    // Hue
    if ($diff == 0) {
        $h = 0;
    } elseif ($max == $r) {
        $h = 60 * fmod((($g - $b) / $diff), 6);
    } elseif ($max == $g) {
        $h = 60 * ((($b - $r) / $diff) + 2);
    } else {
        $h = 60 * ((($r - $g) / $diff) + 4);
    }
    
    if ($h < 0) $h += 360;
    
    // Saturation
    $s = ($max == 0) ? 0 : ($diff / $max);
    
    // Value
    $v = $max;
    
    return ['h' => $h, 's' => $s, 'v' => $v];
}

/**
 * Regrouper les pixels de peau en clusters (algorithme de clustering simple)
 */
function clusterSkinRegions($skinPixels, $width, $height) {
    if (empty($skinPixels)) return [];
    
    // Distance maximale pour considérer deux pixels comme voisins
    $maxDistance = max(15, min($width, $height) / 20);
    
    $clusters = [];
    $visited = [];
    
    foreach ($skinPixels as $pixel) {
        $key = $pixel['x'] . '_' . $pixel['y'];
        if (isset($visited[$key])) continue;
        
        // Créer un nouveau cluster
        $cluster = [$pixel];
        $visited[$key] = true;
        $queue = [$pixel];
        
        // BFS pour trouver tous les pixels connectés
        while (!empty($queue)) {
            $current = array_shift($queue);
            
            // Chercher les voisins
            foreach ($skinPixels as $neighbor) {
                $nKey = $neighbor['x'] . '_' . $neighbor['y'];
                if (isset($visited[$nKey])) continue;
                
                $distance = sqrt(
                    pow($current['x'] - $neighbor['x'], 2) + 
                    pow($current['y'] - $neighbor['y'], 2)
                );
                
                if ($distance <= $maxDistance) {
                    $cluster[] = $neighbor;
                    $visited[$nKey] = true;
                    $queue[] = $neighbor;
                }
            }
        }
        
        // Garder seulement les clusters suffisamment grands
        $minClusterSize = 10; // Minimum de pixels
        if (count($cluster) >= $minClusterSize) {
            $clusters[] = $cluster;
        }
    }
    
    return $clusters;
}

/**
 * Filtrer les clusters pour garder seulement ceux qui ressemblent à des visages
 */
function filterFaceCandidates($clusters, $image, $width, $height) {
    $candidates = [];
    
    foreach ($clusters as $cluster) {
        // Calculer la bounding box du cluster
        $minX = PHP_INT_MAX;
        $maxX = 0;
        $minY = PHP_INT_MAX;
        $maxY = 0;
        
        foreach ($cluster as $pixel) {
            $minX = min($minX, $pixel['x']);
            $maxX = max($maxX, $pixel['x']);
            $minY = min($minY, $pixel['y']);
            $maxY = max($maxY, $pixel['y']);
        }
        
        $bboxWidth = $maxX - $minX;
        $bboxHeight = $maxY - $minY;
        
        if ($bboxWidth == 0 || $bboxHeight == 0) continue;
        
        // Heuristique 1: Ratio largeur/hauteur typique d'un visage (0.6 à 1.2)
        $aspectRatio = $bboxWidth / $bboxHeight;
        $aspectScore = 0;
        if ($aspectRatio >= 0.6 && $aspectRatio <= 1.2) {
            $aspectScore = 1.0 - abs($aspectRatio - 0.85) / 0.85;
        }
        
        // Heuristique 2: Taille relative (les visages font entre 5% et 50% de l'image)
        $relativeSize = ($bboxWidth * $bboxHeight) / ($width * $height);
        $sizeScore = 0;
        if ($relativeSize >= 0.05 && $relativeSize <= 0.5) {
            $sizeScore = 1.0;
        } elseif ($relativeSize >= 0.02 && $relativeSize < 0.05) {
            $sizeScore = 0.5;
        } elseif ($relativeSize > 0.5 && $relativeSize <= 0.7) {
            $sizeScore = 0.3;
        }
        
        // Heuristique 3: Compacité du cluster (densité)
        $clusterArea = $bboxWidth * $bboxHeight;
        $pixelCount = count($cluster);
        $density = $pixelCount / max(1, $clusterArea);
        $densityScore = min(1.0, $density * 2); // Plus c'est compact, mieux c'est
        
        // Heuristique 4: Position dans l'image (les visages sont souvent dans le tiers supérieur)
        $centerY = ($minY + $maxY) / 2;
        $relativeY = $centerY / $height;
        $positionScore = 1.0;
        if ($relativeY <= 0.5) {
            $positionScore = 1.0; // Moitié supérieure: excellent
        } elseif ($relativeY <= 0.7) {
            $positionScore = 0.7; // Tiers moyen: bon
        } else {
            $positionScore = 0.4; // Tiers inférieur: moins probable
        }
        
        // Heuristique 5: Compacité horizontale (les visages sont plutôt centrés)
        $centerX = ($minX + $maxX) / 2;
        $relativeX = abs(($centerX / $width) - 0.5);
        $centerScore = 1.0 - ($relativeX * 0.8);
        
        // Score composite
        $totalScore = (
            $aspectScore * 0.35 +
            $sizeScore * 0.25 +
            $densityScore * 0.20 +
            $positionScore * 0.10 +
            $centerScore * 0.10
        );
        
        // Garder seulement les candidats avec un score raisonnable
        if ($totalScore >= 0.3) {
            $candidates[] = [
                'bbox' => [
                    'minX' => $minX,
                    'maxX' => $maxX,
                    'minY' => $minY,
                    'maxY' => $maxY,
                    'width' => $bboxWidth,
                    'height' => $bboxHeight
                ],
                'centerX' => ($minX + $maxX) / 2,
                'centerY' => ($minY + $maxY) / 2,
                'score' => $totalScore,
                'pixelCount' => $pixelCount,
                'aspectRatio' => $aspectRatio,
                'relativeSize' => $relativeSize
            ];
        }
    }
    
    return $candidates;
}

/**
 * Sélectionner le meilleur candidat de visage
 */
function selectBestFaceCandidate($candidates, $width, $height) {
    if (count($candidates) == 1) {
        return $candidates[0];
    }
    
    // Trier par score décroissant
    usort($candidates, function($a, $b) {
        return $b['score'] <=> $a['score'];
    });
    
    // Retourner le candidat avec le meilleur score
    return $candidates[0];
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
    
    // Centrer sur la ROI avec contraintes
    $roiX = $roi['x'];
    $roiY = $roi['y'];
    
    // Si une bbox est disponible (cas de détection de visage), l'utiliser pour mieux cadrer
    if (isset($roi['bbox'])) {
        $bbox = $roi['bbox'];
        // S'assurer que toute la bbox est incluse dans le crop
        $bboxCenterX = ($bbox['minX'] + $bbox['maxX']) / 2;
        $bboxCenterY = ($bbox['minY'] + $bbox['maxY']) / 2;
        
        // Utiliser le centre de la bbox
        $roiX = $bboxCenterX;
        $roiY = $bboxCenterY;
    }
    
    $cropX = max(0, min($width - $cropWidth, round($roiX - $cropWidth / 2)));
    $cropY = max(0, min($height - $cropHeight, round($roiY - $cropHeight / 2)));
    
    return [
        'x' => $cropX,
        'y' => $cropY,
        'width' => $cropWidth,
        'height' => $cropHeight
    ];
}