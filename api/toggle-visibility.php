<?php
// Démarrer la session en premier
session_start();

require_once '../config.php';
require_once '../image-functions.php';

header('Content-Type: application/json');

// Vérifier la session
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
$isPublic = (bool)($input['is_public'] ?? false);
$userId = $_SESSION['user_id'];

if ($imageId <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID image invalide']);
    exit;
}

// Vérifier que l'image appartient à l'utilisateur
$stmt = $pdo->prepare("SELECT id FROM images WHERE id = ? AND user_id = ? AND is_deleted = 0");
$stmt->execute([$imageId, $userId]);

if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'error' => 'Image non trouvée']);
    exit;
}

// Changer la visibilité
$result = toggleImageVisibility($pdo, $imageId, $userId, $isPublic);

// Logger l'action seulement si la fonction existe
if (function_exists('logSecurityAction')) {
    logSecurityAction($userId, 'image_visibility_changed', "Image $imageId: " . ($isPublic ? 'public' : 'private'));
}

echo json_encode($result);
?>