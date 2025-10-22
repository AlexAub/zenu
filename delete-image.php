<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

$user_id = $_SESSION['user_id'];
$image_id = intval($_POST['image_id'] ?? 0);

if ($image_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID invalide']);
    exit;
}

// Récupérer l'image
$stmt = $pdo->prepare("SELECT * FROM images WHERE id = ? AND user_id = ?");
$stmt->execute([$image_id, $user_id]);
$image = $stmt->fetch();

if (!$image) {
    echo json_encode(['success' => false, 'error' => 'Image non trouvée']);
    exit;
}

// Supprimer le fichier physique
if (file_exists($image['file_path'])) {
    unlink($image['file_path']);
}

// Supprimer de la BDD
$stmt = $pdo->prepare("DELETE FROM images WHERE id = ? AND user_id = ?");
$stmt->execute([$image_id, $user_id]);

echo json_encode(['success' => true]);
?>