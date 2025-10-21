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

// Soft delete
$success = softDeleteImage($pdo, $imageId, $userId);

if ($success) {
    if (function_exists('logSecurityAction')) {
        logSecurityAction($userId, 'image_soft_deleted', "Image $imageId moved to trash");
    }
    echo json_encode(['success' => true, 'message' => 'Image déplacée dans la corbeille']);
} else {
    echo json_encode(['success' => false, 'error' => 'Impossible de supprimer l\'image']);
}
?>