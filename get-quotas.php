<?php
require_once 'config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false]);
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as image_count,
        COALESCE(SUM(file_size), 0) as total_size
    FROM images 
    WHERE user_id = ? AND is_deleted = 0
");
$stmt->execute([$user_id]);
$data = $stmt->fetch();

$used_space_mb = round($data['total_size'] / (1024 * 1024), 2);

echo json_encode([
    'success' => true,
    'count' => $data['image_count'],
    'total_size' => $data['total_size'],
    'used_space' => $used_space_mb . ' MB'
]);
?>