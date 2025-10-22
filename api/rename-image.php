<?php
session_start();

require_once '../config.php';

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

// Vérifier que l'image appartient à l'utilisateur
$stmt = $pdo->prepare("SELECT id FROM images WHERE id = ? AND user_id = ? AND is_deleted = 0");
$stmt->execute([$imageId, $userId]);

if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'error' => 'Image non trouvée']);
    exit;
}

// Mettre à jour le nom
$stmt = $pdo->prepare("UPDATE images SET original_filename = ? WHERE id = ? AND user_id = ?");
$success = $stmt->execute([$newName, $imageId, $userId]);

if ($success) {
    echo json_encode([
        'success' => true,
        'message' => 'Image renommée avec succès',
        'new_name' => $newName
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Erreur lors du renommage']);
}
?>