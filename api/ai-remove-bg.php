<?php
/**
 * API de suppression de fond - Version finale
 * Priorité : Remove.bg API (qualité professionnelle)
 * Fallback : Algorithme local amélioré
 */

ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/ai-errors.log');

set_time_limit(120);
ini_set('memory_limit', '768M');

function logError($message) {
    $logFile = __DIR__ . '/../logs/ai-errors.log';
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    @file_put_contents($logFile, date('[Y-m-d H:i:s] ') . $message . "\n", FILE_APPEND);
}

try {
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
    if (!$input) {
        throw new Exception('JSON invalide');
    }
    
    $imageId = intval($input['image_id'] ?? 0);
    $imagePath = $input['image_path'] ?? '';
    $options = $input['options'] ?? [];
    
    $userId = $_SESSION['user_id'];
    
    $stmt = $pdo->prepare("SELECT * FROM images WHERE id = ? AND user_id = ? AND is_deleted = 0");
    $stmt->execute([$imageId, $userId]);
    $image = $stmt->fetch();
    
    if (!$image) {
        throw new Exception('Image non trouvée');
    }
    
    $fullPath = '../' . $imagePath;
    if (!file_exists($fullPath)) {
        throw new Exception('Fichier introuvable');
    }
    
    $fileSize = filesize($fullPath);
    
    logError("Processing image $imageId - Size: " . round($fileSize / 1024) . "KB");
    
    // Vérifier si Remove.bg API est configurée
    $removeBgKey = defined('REMOVE_BG_API_KEY') ? REMOVE_BG_API_KEY : '';
    $useRemoveBg = !empty($removeBgKey) && $fileSize <= (12 * 1024 * 1024); // Max 12MB
    
    $method = 'local';
    $resultPath = null;
    
    // Essayer Remove.bg en priorité
    if ($useRemoveBg) {
        try {
            logError("Trying Remove.bg API");
            $resultPath = processWithRemoveBg($fullPath, $removeBgKey);
            $method = 'remove_bg_api';
            logError("Remove.bg API success");
        } catch (Exception $e) {
            logError("Remove.bg API failed: " . $e->getMessage());
            // Continuer avec l'algorithme local
        }
    }
    
    // Fallback sur algorithme local
    if (!$resultPath) {
        logError("Using local algorithm");
        $resultPath = processLocalAlgorithm($fullPath, $options);
        $method = 'local_improved';
    }
    
    if (!$resultPath || !file_exists($resultPath)) {
        throw new Exception("Échec du traitement");
    }
    
    $relativePath = str_replace('../', '', $resultPath);
    
    echo json_encode([
        'success' => true,
        'processed_image' => $relativePath,
        'filename' => pathinfo($image['filename'], PATHINFO_FILENAME) . '_nobg.png',
        'original_size' => $fileSize,
        'processed_size' => filesize($resultPath),
        'method' => $method
    ]);
    
} catch (Exception $e) {
    logError("ERROR: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Traiter avec Remove.bg API
 */
function processWithRemoveBg($imagePath, $apiKey) {
    if (!function_exists('curl_init')) {
        throw new Exception('cURL not available');
    }
    
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://api.remove.bg/v1.0/removebg',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'X-Api-Key: ' . $apiKey
        ],
        CURLOPT_POSTFIELDS => [
            'image_file' => new CURLFile($imagePath),
            'size' => 'auto'
        ],
        CURLOPT_TIMEOUT => 30
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    if ($error) {
        throw new Exception("cURL error: $error");
    }
    
    if ($httpCode !== 200) {
        $errorData = @json_decode($response, true);
        $errorMsg = $errorData['errors'][0]['title'] ?? 'API Error';
        throw new Exception("Remove.bg API error ($httpCode): $errorMsg");
    }
    
    // Sauvegarder le résultat
    $tempDir = '../uploads/temp';
    if (!is_dir($tempDir)) {
        @mkdir($tempDir, 0755, true);
    }
    
    $tempFilename = 'ai_nobg_' . uniqid() . '_' . time() . '.png';
    $tempPath = $tempDir . '/' . $tempFilename;
    
    if (file_put_contents($tempPath, $response) === false) {
        throw new Exception("Failed to save result");
    }
    
    return $tempPath;
}

/**
 * Traiter avec algorithme local amélioré
 */
function processLocalAlgorithm($imagePath, $options) {
    $imageInfo = @getimagesize($imagePath);
    if ($imageInfo === false) {
        throw new Exception("Format d'image invalide");
    }
    
    list($width, $height, $type) = $imageInfo;
    
    if ($width > 2000 || $height > 2000) {
        throw new Exception("Image trop grande (max 2000x2000)");
    }
    
    // Charger l'image
    switch ($type) {
        case IMAGETYPE_JPEG:
            $sourceImage = @imagecreatefromjpeg($imagePath);
            break;
        case IMAGETYPE_PNG:
            $sourceImage = @imagecreatefrompng($imagePath);
            break;
        case IMAGETYPE_GIF:
            $sourceImage = @imagecreatefromgif($imagePath);
            break;
        case IMAGETYPE_WEBP:
            $sourceImage = @imagecreatefromwebp($imagePath);
            break;
        default:
            throw new Exception("Type d'image non supporté");
    }
    
    if (!$sourceImage) {
        throw new Exception("Impossible de charger l'image");
    }
    
    $edgeQuality = $options['edgeQuality'] ?? 'medium';
    
    // Algorithme local simple mais efficace
    $resultImage = simpleBackgroundRemoval($sourceImage, $width, $height, $edgeQuality);
    
    // Sauvegarder
    $tempDir = '../uploads/temp';
    if (!is_dir($tempDir)) {
        @mkdir($tempDir, 0755, true);
    }
    
    $tempFilename = 'ai_nobg_' . uniqid() . '_' . time() . '.png';
    $tempPath = $tempDir . '/' . $tempFilename;
    
    if (!@imagepng($resultImage, $tempPath, 9)) {
        throw new Exception("Impossible de sauvegarder");
    }
    
    @imagedestroy($sourceImage);
    @imagedestroy($resultImage);
    
    return $tempPath;
}

/**
 * Algorithme local simplifié mais efficace
 */
function simpleBackgroundRemoval($source, $width, $height, $quality) {
    $output = imagecreatetruecolor($width, $height);
    imagealphablending($output, false);
    imagesavealpha($output, true);
    
    $transparent = imagecolorallocatealpha($output, 0, 0, 0, 127);
    imagefill($output, 0, 0, $transparent);
    
    // Analyser les 4 coins pour détecter le fond
    $cornerSize = max(20, min(50, floor(min($width, $height) / 10)));
    
    $corners = [
        getCornerColor($source, 0, 0, $cornerSize, $width, $height),
        getCornerColor($source, $width - $cornerSize, 0, $cornerSize, $width, $height),
        getCornerColor($source, 0, $height - $cornerSize, $cornerSize, $width, $height),
        getCornerColor($source, $width - $cornerSize, $height - $cornerSize, $cornerSize, $width, $height)
    ];
    
    // Calculer tolérance adaptative
    $maxDiff = 0;
    for ($i = 0; $i < 4; $i++) {
        for ($j = $i + 1; $j < 4; $j++) {
            $diff = colorDistance($corners[$i], $corners[$j]);
            $maxDiff = max($maxDiff, $diff);
        }
    }
    
    $tolerance = 60 + ($maxDiff * 0.5);
    
    if ($quality === 'high') {
        $tolerance *= 0.9;
    } elseif ($quality === 'low') {
        $tolerance *= 1.2;
    }
    
    logError("Local algorithm - Tolerance: $tolerance");
    
    // Zone centrale privilégiée
    $margin = 0.2;
    $centerX1 = floor($width * $margin);
    $centerY1 = floor($height * $margin);
    $centerX2 = floor($width * (1 - $margin));
    $centerY2 = floor($height * (1 - $margin));
    
    // Créer le masque
    $blurRadius = ($quality === 'high') ? 2 : (($quality === 'low') ? 0 : 1);
    
    for ($y = 0; $y < $height; $y++) {
        for ($x = 0; $x < $width; $x++) {
            $pixel = getPixelColor($source, $x, $y);
            
            // Distance minimale à l'un des coins
            $minDist = PHP_INT_MAX;
            foreach ($corners as $corner) {
                $dist = colorDistance($pixel, $corner);
                $minDist = min($minDist, $dist);
            }
            
            // Bonus de centralité
            $inCenter = ($x >= $centerX1 && $x <= $centerX2 && $y >= $centerY1 && $y <= $centerY2);
            $centerBonus = $inCenter ? 15 : 0;
            
            $isSubject = ($minDist + $centerBonus) > $tolerance;
            
            if ($isSubject) {
                $alpha = 0;
                
                // Anti-aliasing sur les bords
                if ($blurRadius > 0) {
                    $bgNeighbors = 0;
                    $total = 0;
                    
                    for ($dy = -$blurRadius; $dy <= $blurRadius; $dy++) {
                        for ($dx = -$blurRadius; $dx <= $blurRadius; $dx++) {
                            $ny = $y + $dy;
                            $nx = $x + $dx;
                            
                            if ($ny >= 0 && $ny < $height && $nx >= 0 && $nx < $width) {
                                $nPixel = getPixelColor($source, $nx, $ny);
                                $nMinDist = PHP_INT_MAX;
                                foreach ($corners as $corner) {
                                    $nDist = colorDistance($nPixel, $corner);
                                    $nMinDist = min($nMinDist, $nDist);
                                }
                                
                                $total++;
                                if ($nMinDist <= $tolerance) {
                                    $bgNeighbors++;
                                }
                            }
                        }
                    }
                    
                    if ($bgNeighbors > 0 && $total > 0) {
                        $ratio = $bgNeighbors / $total;
                        $alpha = (int)(127 * $ratio * 0.6);
                    }
                }
                
                $color = imagecolorallocatealpha($output, $pixel['r'], $pixel['g'], $pixel['b'], $alpha);
                imagesetpixel($output, $x, $y, $color);
            }
        }
    }
    
    return $output;
}

function getCornerColor($image, $startX, $startY, $size, $width, $height) {
    $colors = [];
    for ($y = 0; $y < $size; $y++) {
        for ($x = 0; $x < $size; $x++) {
            $px = max(0, min($width - 1, $startX + $x));
            $py = max(0, min($height - 1, $startY + $y));
            $colors[] = getPixelColor($image, $px, $py);
        }
    }
    return averageColor($colors);
}

function getPixelColor($image, $x, $y) {
    $rgb = imagecolorat($image, $x, $y);
    return [
        'r' => ($rgb >> 16) & 0xFF,
        'g' => ($rgb >> 8) & 0xFF,
        'b' => $rgb & 0xFF
    ];
}

function colorDistance($c1, $c2) {
    return sqrt(
        pow($c1['r'] - $c2['r'], 2) +
        pow($c1['g'] - $c2['g'], 2) +
        pow($c1['b'] - $c2['b'], 2)
    );
}

function averageColor($colors) {
    $r = 0; $g = 0; $b = 0;
    foreach ($colors as $c) {
        $r += $c['r'];
        $g += $c['g'];
        $b += $c['b'];
    }
    $count = count($colors);
    return [
        'r' => (int)($r / $count),
        'g' => (int)($g / $count),
        'b' => (int)($b / $count)
    ];
}
?>