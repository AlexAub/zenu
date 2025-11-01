<?php
session_start();

require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

$userId = $_SESSION['user_id'];

// Récupérer les statistiques
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_images,
        COALESCE(SUM(file_size), 0) as total_size
    FROM images 
    WHERE user_id = ? AND is_deleted = 0
");
$stmt->execute([$userId]);
$stats = $stmt->fetch();

// Fonction pour formater la taille
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' Go';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' Mo';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' Ko';
    } else {
        return $bytes . ' octets';
    }
}

echo json_encode([
    'success' => true,
    'total_images' => intval($stats['total_images']),
    'total_size' => intval($stats['total_size']),
    'total_size_formatted' => formatFileSize($stats['total_size'])
]);
?>
