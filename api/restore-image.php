<?php
session_start();

require_once '../config.php';
require_once '../image-functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$imageId = intval($input['image_id'] ?? 0);
$userId = $_SESSION['user_id'];

if ($imageId <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID image invalide']);
    exit;
}

// Récupérer l'image à restaurer
$stmt = $pdo->prepare("
    SELECT id, original_filename, filename, file_path 
    FROM images 
    WHERE id = ? AND user_id = ? AND is_deleted = 1
");
$stmt->execute([$imageId, $userId]);
$image = $stmt->fetch();

if (!$image) {
    echo json_encode(['success' => false, 'error' => 'Image non trouvée dans la corbeille']);
    exit;
}

$originalName = $image['original_filename'];

// ✅ VÉRIFICATION DES DOUBLONS : Vérifier si un fichier avec ce nom existe déjà (NON supprimé)
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count
    FROM images 
    WHERE user_id = ? 
    AND original_filename = ?
    AND is_deleted = 0
    AND id != ?
");
$stmt->execute([$userId, $originalName, $imageId]);
$result = $stmt->fetch();

$finalName = $originalName;
$counter = 1;
$maxAttempts = 100;
$renamed = false;

// Si le nom existe déjà, ajouter un suffixe
if ($result['count'] > 0) {
    while ($counter <= $maxAttempts) {
        $counter++;
        $finalName = $originalName . '_' . $counter;
        
        // Vérifier si ce nouveau nom est disponible
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count
            FROM images 
            WHERE user_id = ? 
            AND original_filename = ?
            AND is_deleted = 0
        ");
        $stmt->execute([$userId, $finalName]);
        $checkResult = $stmt->fetch();
        
        if ($checkResult['count'] == 0) {
            // Nom disponible !
            $renamed = true;
            break;
        }
    }
    
    if ($counter > $maxAttempts) {
        echo json_encode([
            'success' => false, 
            'error' => 'Impossible de trouver un nom unique pour la restauration'
        ]);
        exit;
    }
}

// Restaurer l'image avec le nom (éventuellement modifié)
try {
    $stmt = $pdo->prepare("
        UPDATE images 
        SET is_deleted = 0, 
            deleted_at = NULL,
            original_filename = ?
        WHERE id = ? AND user_id = ?
    ");
    
    $success = $stmt->execute([$finalName, $imageId, $userId]);
    
    if ($success) {
        if (function_exists('logSecurityAction')) {
            logSecurityAction($userId, 'image_restored', "Image $imageId restored from trash" . ($renamed ? " (renamed to $finalName)" : ""));
        }
        
        $message = 'Image restaurée avec succès';
        if ($renamed) {
            $message = "Image restaurée sous le nom '$finalName' (un fichier avec le nom original existait déjà)";
        }
        
        echo json_encode([
            'success' => true, 
            'message' => $message,
            'renamed' => $renamed,
            'new_name' => $finalName
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Impossible de restaurer l\'image']);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false, 
        'error' => 'Erreur base de données: ' . $e->getMessage()
    ]);
}
?>