<?php
require_once '../config.php';
require_once '../image-functions.php';

header('Content-Type: application/json');

// Démarrer la session pour vérifier l'accès
session_start();

// Vérifier l'accès : soit connecté, soit accès thumbnail autorisé
$hasAccess = (isset($_SESSION['user_id']) || isset($_SESSION['thumbnail_access']));

if (!$hasAccess) {
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$imageId = intval($input['image_id'] ?? 0);
$filePath = $input['file_path'] ?? '';

if ($imageId <= 0 || empty($filePath)) {
    echo json_encode(['success' => false, 'error' => 'Paramètres invalides']);
    exit;
}

// Essayer différents chemins
$possiblePaths = [
    $filePath,                          // uploads/user_3/image.jpg
    '../' . $filePath,                  // ../uploads/user_3/image.jpg (depuis api/)
    __DIR__ . '/../' . $filePath,       // Chemin absolu
];

$correctPath = null;
foreach ($possiblePaths as $path) {
    if (file_exists($path)) {
        $correctPath = $path;
        break;
    }
}

// Vérifier que le fichier existe
if (!$correctPath) {
    echo json_encode([
        'success' => false,
        'error' => 'Fichier introuvable',
        'debug' => [
            'file_path_bdd' => $filePath,
            'tested_paths' => $possiblePaths,
            'api_dir' => __DIR__,
            'exists_direct' => file_exists($filePath) ? 'oui' : 'non',
            'exists_parent' => file_exists('../' . $filePath) ? 'oui' : 'non'
        ]
    ]);
    exit;
}

// Générer le chemin de la miniature en conservant la structure user_X
$pathParts = explode('/', $filePath);
$filename = array_pop($pathParts); // Récupérer le nom du fichier
$userFolder = array_pop($pathParts); // Récupérer user_X

// Créer le chemin de la miniature avec la structure
$thumbPath = 'uploads/thumbnails/' . $userFolder . '/' . $filename;
$thumbPathFull = '../' . $thumbPath; // Depuis le dossier api/

// Créer les dossiers si nécessaire
$thumbDir = '../uploads/thumbnails/' . $userFolder;
if (!is_dir($thumbDir)) {
    mkdir($thumbDir, 0755, true);
}

// Générer la miniature
$success = generateThumbnail($correctPath, $thumbPathFull, 300, 300);

if ($success) {
    // Mettre à jour la base de données
    $stmt = $pdo->prepare("UPDATE images SET thumbnail_path = ? WHERE id = ?");
    $stmt->execute([$thumbPath, $imageId]);
    
    echo json_encode([
        'success' => true,
        'thumbnail_path' => $thumbPath,
        'message' => 'Miniature générée',
        'debug' => [
            'source_path_used' => $correctPath,
            'thumb_path' => $thumbPathFull
        ]
    ]);
} else {
    echo json_encode([
        'success' => false, 
        'error' => 'Erreur lors de la génération',
        'debug' => [
            'source_path' => $correctPath,
            'thumb_path' => $thumbPathFull,
            'source_exists' => file_exists($correctPath) ? 'oui' : 'non',
            'gd_enabled' => extension_loaded('gd') ? 'oui' : 'non'
        ]
    ]);
}
?>