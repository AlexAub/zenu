<?php
/**
 * API pour récupérer les images de l'utilisateur
 * Utilisé dans l'éditeur pour sélectionner une image existante
 */

require_once 'config.php';
require_once 'security.php';

header('Content-Type: application/json');

// Vérifier la connexion
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

$userId = $_SESSION['user_id'];

// Récupérer le terme de recherche optionnel
$search = $_GET['search'] ?? '';

// Construire la requête
$sql = "
    SELECT 
        id,
        filename,
        original_filename,
        file_path,
        thumbnail_path,
        width,
        height,
        file_size,
        created_at
    FROM images 
    WHERE user_id = ? 
    AND is_deleted = 0
";

$params = [$userId];

// Ajouter la recherche si présente
if (!empty($search)) {
    $sql .= " AND (original_filename LIKE ? OR filename LIKE ?)";
    $searchTerm = '%' . $search . '%';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$sql .= " ORDER BY created_at DESC LIMIT 50";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formater les données pour l'affichage
    foreach ($images as &$image) {
        $image['display_name'] = $image['original_filename'] ?? $image['filename'];
        $image['preview_url'] = $image['thumbnail_path'] ?? $image['file_path'];
        $image['dimensions'] = $image['width'] . 'x' . $image['height'];
        $image['size_formatted'] = formatFileSize($image['file_size']);
    }
    
    echo json_encode([
        'success' => true,
        'images' => $images,
        'count' => count($images)
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors de la récupération des images'
    ]);
}

/**
 * Formater la taille du fichier
 */
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' octets';
    }
}
?>
