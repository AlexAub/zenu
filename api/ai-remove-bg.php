<?php
/**
 * API de suppression de fond avec algorithme amélioré
 * Utilise plusieurs techniques combinées pour de meilleurs résultats
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
    
    // Options de détection
    $detectionMode = $options['detectionMode'] ?? 'auto';
    $edgeQuality = $options['edgeQuality'] ?? 'medium';
    
    // Créer une image de résultat avec transparence
    $resultImage = imagecreatetruecolor($width, $height);
    imagealphablending($resultImage, false);
    imagesavealpha($resultImage, true);
    
    // Algorithme de suppression de fond amélioré
    $result = removeBackgroundAdvanced($sourceImage, $width, $height, $detectionMode, $edgeQuality);
    
    // Copier le résultat
    imagecopy($resultImage, $result, 0, 0, 0, 0, $width, $height);
    imagedestroy($result);
    
    // Sauvegarder le résultat temporaire
    $tempDir = '../uploads/temp';
    if (!is_dir($tempDir)) {
        mkdir($tempDir, 0755, true);
    }
    
    $tempFilename = 'ai_nobg_' . uniqid() . '.png';
    $tempPath = $tempDir . '/' . $tempFilename;
    
    imagepng($resultImage, $tempPath, 9); // Compression maximale PNG
    
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
        'method' => 'advanced_multi_pass'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Algorithme avancé de suppression de fond
 * Utilise plusieurs passes et techniques combinées
 */
function removeBackgroundAdvanced($image, $width, $height, $mode, $quality) {
    // Créer l'image de sortie
    $output = imagecreatetruecolor($width, $height);
    imagealphablending($output, false);
    imagesavealpha($output, true);
    
    // Étape 1 : Détection des bords et du sujet principal
    $subjectMask = detectSubject($image, $width, $height, $mode);
    
    // Étape 2 : Affiner les bords selon la qualité demandée
    $refinedMask = refineMask($subjectMask, $width, $height, $quality);
    
    // Étape 3 : Appliquer le masque avec feathering (dégradé sur les bords)
    applyMaskWithFeathering($image, $output, $refinedMask, $width, $height, $quality);
    
    return $output;
}

/**
 * Détecter le sujet principal de l'image
 * Combine plusieurs techniques
 */
function detectSubject($image, $width, $height, $mode) {
    $mask = [];
    
    // Échantillonnage adaptatif
    $step = max(1, floor(min($width, $height) / 200));
    
    // Analyser les bords pour détecter le fond
    $borderColors = analyzeBorderColors($image, $width, $height);
    
    // Détecter le centre de masse du sujet (zones contrastées)
    $subjectCenter = findSubjectCenter($image, $width, $height);
    
    for ($x = 0; $x < $width; $x += $step) {
        for ($y = 0; $y < $height; $y += $step) {
            $rgb = imagecolorat($image, $x, $y);
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;
            
            // Calculer la probabilité que ce pixel soit le sujet
            $isSubject = calculateSubjectProbability(
                $r, $g, $b,
                $x, $y,
                $width, $height,
                $borderColors,
                $subjectCenter,
                $mode
            );
            
            // Remplir le masque par blocs
            for ($dx = 0; $dx < $step && ($x + $dx) < $width; $dx++) {
                for ($dy = 0; $dy < $step && ($y + $dy) < $height; $dy++) {
                    $mask[$x + $dx][$y + $dy] = $isSubject;
                }
            }
        }
    }
    
    // Post-traitement : éliminer les petits îlots isolés
    $mask = removeSmallIslands($mask, $width, $height);
    
    return $mask;
}

/**
 * Analyser les couleurs des bords de l'image
 */
function analyzeBorderColors($image, $width, $height) {
    $borderPixels = [];
    $borderSize = max(5, min($width, $height) / 50);
    
    // Échantillonner les 4 bords
    for ($i = 0; $i < $width; $i += 5) {
        // Haut
        for ($j = 0; $j < $borderSize; $j++) {
            $borderPixels[] = imagecolorat($image, $i, $j);
        }
        // Bas
        for ($j = $height - $borderSize; $j < $height; $j++) {
            $borderPixels[] = imagecolorat($image, $i, $j);
        }
    }
    
    for ($j = 0; $j < $height; $j += 5) {
        // Gauche
        for ($i = 0; $i < $borderSize; $i++) {
            $borderPixels[] = imagecolorat($image, $i, $j);
        }
        // Droite
        for ($i = $width - $borderSize; $i < $width; $i++) {
            $borderPixels[] = imagecolorat($image, $i, $j);
        }
    }
    
    // Calculer les statistiques des couleurs de bord
    $avgR = $avgG = $avgB = 0;
    $count = count($borderPixels);
    
    foreach ($borderPixels as $color) {
        $avgR += ($color >> 16) & 0xFF;
        $avgG += ($color >> 8) & 0xFF;
        $avgB += $color & 0xFF;
    }
    
    return [
        'r' => $avgR / $count,
        'g' => $avgG / $count,
        'b' => $avgB / $count,
        'samples' => $borderPixels
    ];
}

/**
 * Trouver le centre du sujet
 */
function findSubjectCenter($image, $width, $height) {
    $weightedX = 0;
    $weightedY = 0;
    $totalWeight = 0;
    
    $step = max(1, floor(min($width, $height) / 100));
    
    for ($x = $step; $x < $width - $step; $x += $step) {
        for ($y = $step; $y < $height - $step; $y += $step) {
            // Calculer le gradient (détection de bords)
            $gradient = calculateGradient($image, $x, $y, $width, $height);
            
            if ($gradient > 20) {
                $weightedX += $x * $gradient;
                $weightedY += $y * $gradient;
                $totalWeight += $gradient;
            }
        }
    }
    
    if ($totalWeight > 0) {
        return [
            'x' => $weightedX / $totalWeight,
            'y' => $weightedY / $totalWeight
        ];
    }
    
    return ['x' => $width / 2, 'y' => $height / 2];
}

/**
 * Calculer le gradient (Sobel simplifié)
 */
function calculateGradient($image, $x, $y, $width, $height) {
    if ($x <= 0 || $y <= 0 || $x >= $width - 1 || $y >= $height - 1) {
        return 0;
    }
    
    $colors = [];
    for ($dx = -1; $dx <= 1; $dx++) {
        for ($dy = -1; $dy <= 1; $dy++) {
            $rgb = imagecolorat($image, $x + $dx, $y + $dy);
            $gray = (($rgb >> 16) & 0xFF) + (($rgb >> 8) & 0xFF) + ($rgb & 0xFF);
            $colors[] = $gray;
        }
    }
    
    $gx = abs(-$colors[0] + $colors[2] - 2*$colors[3] + 2*$colors[5] - $colors[6] + $colors[8]);
    $gy = abs(-$colors[0] - 2*$colors[1] - $colors[2] + $colors[6] + 2*$colors[7] + $colors[8]);
    
    return sqrt($gx * $gx + $gy * $gy);
}

/**
 * Calculer la probabilité qu'un pixel soit le sujet
 */
function calculateSubjectProbability($r, $g, $b, $x, $y, $width, $height, $borderColors, $center, $mode) {
    $score = 0;
    
    // 1. Distance avec les couleurs de bord (plus c'est différent, plus c'est probablement le sujet)
    $colorDiff = sqrt(
        pow($r - $borderColors['r'], 2) +
        pow($g - $borderColors['g'], 2) +
        pow($b - $borderColors['b'], 2)
    );
    $score += min($colorDiff / 100, 1) * 40; // 40% du score
    
    // 2. Distance au centre du sujet (plus c'est proche, plus c'est probablement le sujet)
    $distToCenter = sqrt(pow($x - $center['x'], 2) + pow($y - $center['y'], 2));
    $maxDist = sqrt(pow($width, 2) + pow($height, 2)) / 2;
    $centerScore = 1 - ($distToCenter / $maxDist);
    $score += $centerScore * 35; // 35% du score
    
    // 3. Distance aux bords de l'image (plus c'est loin, plus c'est probablement le sujet)
    $distToBorder = min(
        $x,
        $y,
        $width - $x,
        $height - $y
    );
    $maxBorderDist = min($width, $height) / 2;
    $borderScore = min($distToBorder / $maxBorderDist, 1);
    $score += $borderScore * 25; // 25% du score
    
    // 4. Bonus pour couleurs saturées (le sujet est souvent plus coloré que le fond)
    $saturation = calculateSaturation($r, $g, $b);
    if ($saturation > 0.3) {
        $score += min($saturation, 1) * 20; // Bonus jusqu'à 20 points
    }
    
    // 5. Pénalité pour couleurs grises/neutres (souvent le fond)
    if (isGrayish($r, $g, $b)) {
        $score -= 15; // Pénalité pour gris
    }
    
    // 6. Mode spécifique (portrait, produit, etc.)
    if ($mode === 'person' || $mode === 'portrait') {
        // Bonus si c'est une couleur chair
        if (isSkinTone($r, $g, $b)) {
            $score += 25; // Augmenté de 15 à 25
        }
        // Bonus pour couleurs vives (vêtements, accessoires)
        if ($saturation > 0.5) {
            $score += 10;
        }
    }
    
    return max(0, $score); // Ne jamais retourner de score négatif
}

/**
 * Vérifier si une couleur est une teinte chair
 */
function isSkinTone($r, $g, $b) {
    // Critères pour détecter la peau humaine
    return ($r > 95 && $g > 40 && $b > 20 &&
            $r > $g && $r > $b &&
            abs($r - $g) > 15 &&
            $r - $b > 15);
}

/**
 * Calculer la saturation d'une couleur
 */
function calculateSaturation($r, $g, $b) {
    $max = max($r, $g, $b);
    $min = min($r, $g, $b);
    
    if ($max == 0) {
        return 0;
    }
    
    return ($max - $min) / $max;
}

/**
 * Vérifier si une couleur est grisâtre (neutre)
 */
function isGrayish($r, $g, $b) {
    // Si les 3 composantes sont proches, c'est du gris
    $maxDiff = max(abs($r - $g), abs($g - $b), abs($r - $b));
    return $maxDiff < 30; // Seuil de différence pour considérer comme gris
}

/**
 * Affiner le masque selon la qualité demandée
 */
function refineMask($mask, $width, $height, $quality) {
    $iterations = [
        'low' => 1,
        'medium' => 2,
        'high' => 3
    ];
    
    $iter = $iterations[$quality] ?? 2;
    
    // Appliquer un filtre de lissage sur le masque
    for ($i = 0; $i < $iter; $i++) {
        $mask = smoothMask($mask, $width, $height);
    }
    
    return $mask;
}

/**
 * Lisser le masque pour des bords plus doux
 */
function smoothMask($mask, $width, $height) {
    $smoothed = [];
    
    for ($x = 0; $x < $width; $x++) {
        for ($y = 0; $y < $height; $y++) {
            $sum = 0;
            $count = 0;
            
            // Moyenne 3x3
            for ($dx = -1; $dx <= 1; $dx++) {
                for ($dy = -1; $dy <= 1; $dy++) {
                    $nx = $x + $dx;
                    $ny = $y + $dy;
                    
                    if ($nx >= 0 && $nx < $width && $ny >= 0 && $ny < $height) {
                        $sum += $mask[$nx][$ny] ?? 0;
                        $count++;
                    }
                }
            }
            
            $smoothed[$x][$y] = $sum / $count;
        }
    }
    
    return $smoothed;
}

/**
 * Éliminer les petits îlots isolés dans le masque
 */
function removeSmallIslands($mask, $width, $height) {
    // Seuil pour définir un îlot "petit"
    $minIslandSize = ($width * $height) / 100; // 1% de l'image
    
    // Pour simplifier, on va juste appliquer un filtre morphologique
    // Une vraie implémentation utiliserait un algorithme de composantes connexes
    
    for ($x = 1; $x < $width - 1; $x++) {
        for ($y = 1; $y < $height - 1; $y++) {
            // Compter les voisins qui sont aussi du sujet
            $neighbors = 0;
            for ($dx = -1; $dx <= 1; $dx++) {
                for ($dy = -1; $dy <= 1; $dy++) {
                    if ($dx == 0 && $dy == 0) continue;
                    if (($mask[$x + $dx][$y + $dy] ?? 0) > 50) {
                        $neighbors++;
                    }
                }
            }
            
            // Si ce pixel est isolé (peu de voisins), le retirer
            if ($neighbors < 3 && ($mask[$x][$y] ?? 0) > 50) {
                $mask[$x][$y] = 0;
            }
            // Si ce pixel est entouré, l'inclure
            elseif ($neighbors > 5 && ($mask[$x][$y] ?? 0) < 50) {
                $mask[$x][$y] = 100;
            }
        }
    }
    
    return $mask;
}

/**
 * Appliquer le masque avec feathering (dégradé sur les bords)
 */
function applyMaskWithFeathering($source, $output, $mask, $width, $height, $quality) {
    $featherSize = [
        'low' => 1,
        'medium' => 2,
        'high' => 3
    ];
    
    $feather = $featherSize[$quality] ?? 2;
    
    for ($x = 0; $x < $width; $x++) {
        for ($y = 0; $y < $height; $y++) {
            $rgb = imagecolorat($source, $x, $y);
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;
            
            // Obtenir la valeur du masque (0-100)
            $maskValue = $mask[$x][$y] ?? 0;
            
            // Appliquer un feathering en examinant les voisins
            if ($maskValue > 10 && $maskValue < 90) {
                // Zone de transition - appliquer feathering
                $avgMask = 0;
                $count = 0;
                
                for ($dx = -$feather; $dx <= $feather; $dx++) {
                    for ($dy = -$feather; $dy <= $feather; $dy++) {
                        $nx = $x + $dx;
                        $ny = $y + $dy;
                        
                        if ($nx >= 0 && $nx < $width && $ny >= 0 && $ny < $height) {
                            $avgMask += $mask[$nx][$ny] ?? 0;
                            $count++;
                        }
                    }
                }
                
                $maskValue = $avgMask / $count;
            }
            
            // Convertir le score 0-100 en alpha 0-127
            $alpha = 127 - round(($maskValue / 100) * 127);
            
            $color = imagecolorallocatealpha($output, $r, $g, $b, $alpha);
            imagesetpixel($output, $x, $y, $color);
        }
    }
}