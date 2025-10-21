<?php
require_once '../config.php';
require_once '../security.php';
require_once '../image-functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit;
}

$userId = $_SESSION['user_id'];

// Récupérer toutes les images supprimées
$stmt = $pdo->prepare("
    SELECT id, file_path, thumbnail_path 
    FROM images 
    WHERE user_id = ? AND is_deleted = 1
");
$stmt->execute([$userId]);
$images = $stmt->fetchAll();

$deletedCount = 0;

foreach ($images as $image) {
    // Supprimer les fichiers physiques
    if (file_exists($image['file_path'])) {
        unlink($image['file_path']);
    }
    
    if ($image['thumbnail_path'] && file_exists($image['thumbnail_path'])) {
        unlink($image['thumbnail_path']);
    }
    
    $deletedCount++;
}

// Supprimer de la base de données
$stmt = $pdo->prepare("DELETE FROM images WHERE user_id = ? AND is_deleted = 1");
$stmt->execute([$userId]);

logSecurityAction($userId, 'trash_emptied', "$deletedCount images permanently deleted");

echo json_encode([
    'success' => true,
    'deleted_count' => $deletedCount,
    'message' => "Corbeille vidée : $deletedCount image(s) supprimée(s)"
]);
?>