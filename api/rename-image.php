<?php
session_start();

require_once '../config.php';

header('Content-Type: application/json');

// Log pour debug (à retirer en production)
error_log("Rename request received");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

// Log pour debug
error_log("Input data: " . print_r($input, true));

$imageId = intval($input['image_id'] ?? 0);
$newName = trim($input['new_name'] ?? '');
$userId = $_SESSION['user_id'];

if ($imageId <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID image invalide']);
    exit;
}

if (empty($newName)) {
    echo json_encode(['success' => false, 'error' => 'Nom invalide']);
    exit;
}

// Nettoyer le nouveau nom
$cleanName = preg_replace('/[^a-zA-Z0-9_-]/', '-', $newName);
$cleanName = strtolower($cleanName);
$cleanName = preg_replace('/-+/', '-', $cleanName);
$cleanName = trim($cleanName, '-');
$cleanName = substr($cleanName, 0, 100);

if (empty($cleanName)) {
    echo json_encode(['success' => false, 'error' => 'Nom invalide après nettoyage']);
    exit;
}

// Récupérer l'image et vérifier qu'elle appartient à l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM images WHERE id = ? AND user_id = ? AND is_deleted = 0");
$stmt->execute([$imageId, $userId]);
$image = $stmt->fetch();

if (!$image) {
    echo json_encode(['success' => false, 'error' => 'Image non trouvée']);
    exit;
}

// Déterminer l'extension actuelle
$currentExtension = pathinfo($image['filename'] ?? $image['file_path'], PATHINFO_EXTENSION);
if (empty($currentExtension)) {
    $currentExtension = 'jpg';
}

$newFilename = $cleanName . '.' . $currentExtension;
$originalCleanName = $cleanName;
$renamedWithSuffix = false;

// Vérifier les doublons dans original_filename ET filename
$stmt = $pdo->prepare("
    SELECT id, original_filename, filename
    FROM images 
    WHERE user_id = ? 
    AND (original_filename = ? OR filename = ?)
    AND id != ?
    AND is_deleted = 0
");
$stmt->execute([$userId, $cleanName, $newFilename, $imageId]);
$existing = $stmt->fetch();

// Si doublon détecté, ajouter un suffixe
if ($existing) {
    $counter = 2;
    $maxAttempts = 100;
    
    while ($counter <= $maxAttempts) {
        $cleanName = $originalCleanName . '-' . $counter;
        $newFilename = $cleanName . '.' . $currentExtension;
        
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM images 
            WHERE user_id = ? 
            AND (original_filename = ? OR filename = ?)
            AND id != ?
            AND is_deleted = 0
        ");
        $stmt->execute([$userId, $cleanName, $newFilename, $imageId]);
        
        if ($stmt->fetchColumn() == 0) {
            $renamedWithSuffix = true;
            break;
        }
        $counter++;
    }
    
    if ($counter > $maxAttempts) {
        echo json_encode([
            'success' => false,
            'error' => 'Trop de fichiers avec des noms similaires'
        ]);
        exit;
    }
}

// Renommer le fichier physique si nécessaire
$oldPath = $image['file_path'];
$newPath = dirname($oldPath) . '/' . $newFilename;

// Construire le chemin complet depuis api/
$oldPathFull = '../' . $oldPath;
$newPathFull = '../' . $newPath;

// Log pour debug
error_log("Old path: $oldPathFull");
error_log("New path: $newPathFull");

// Ne renommer que si le chemin change ET que le fichier existe
if ($oldPath !== $newPath && file_exists($oldPathFull)) {
    if (!rename($oldPathFull, $newPathFull)) {
        error_log("Failed to rename physical file");
        echo json_encode([
            'success' => false,
            'error' => 'Impossible de renommer le fichier physique'
        ]);
        exit;
    }
}

// Mettre à jour la base de données
$stmt = $pdo->prepare("
    UPDATE images 
    SET filename = ?, original_filename = ?, file_path = ? 
    WHERE id = ? AND user_id = ?
");
$success = $stmt->execute([$newFilename, $cleanName, $newPath, $imageId, $userId]);

if (!$success) {
    // Annuler le renommage si échec BDD
    if (file_exists($newPathFull) && $oldPath !== $newPath) {
        rename($newPathFull, $oldPathFull);
    }
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors de la mise à jour'
    ]);
    exit;
}

// Récupérer le username pour les URLs
$stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// Générer les URLs
$newUrl = SITE_URL . '/' . $user['username'] . '/' . $newFilename;
$newUrlShort = SITE_URL . '/' . $user['username'] . '/' . $cleanName;

// Message personnalisé selon si un suffixe a été ajouté
$message = '✅ Image renommée avec succès !';
if ($renamedWithSuffix) {
    $message = "⚠️ Le nom '$originalCleanName' existait déjà. L'image a été renommée en '$cleanName'.";
}

echo json_encode([
    'success' => true,
    'message' => $message,
    'new_name' => $cleanName,
    'new_filename' => $newFilename,
    'new_url' => $newUrl,
    'new_url_short' => $newUrlShort,
    'renamed_with_suffix' => $renamedWithSuffix,
    'final_name' => $cleanName
]);
?>