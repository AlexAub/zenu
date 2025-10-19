<?php
require_once 'config.php';
require_once 'security.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

$user_id = $_SESSION['user_id'];
$image_id = intval($_POST['image_id'] ?? 0);
$new_name = trim($_POST['new_name'] ?? '');

if ($image_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID invalide']);
    exit;
}

if (empty($new_name)) {
    echo json_encode(['success' => false, 'error' => 'Le nom ne peut pas être vide']);
    exit;
}

// Nettoyer le nouveau nom
$clean_name = preg_replace('/[^a-zA-Z0-9_-]/', '-', $new_name);
$clean_name = strtolower($clean_name);
$clean_name = preg_replace('/-+/', '-', $clean_name);
$clean_name = trim($clean_name, '-');
$clean_name = substr($clean_name, 0, 100);

if (empty($clean_name)) {
    echo json_encode(['success' => false, 'error' => 'Nom invalide']);
    exit;
}

// Récupérer l'image et vérifier qu'elle appartient à l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM images WHERE id = ? AND user_id = ?");
$stmt->execute([$image_id, $user_id]);
$image = $stmt->fetch();

if (!$image) {
    echo json_encode(['success' => false, 'error' => 'Image non trouvée']);
    exit;
}

// Vérifier si un fichier avec ce nom existe déjà
$new_filename = $clean_name . '.jpg';
$stmt = $pdo->prepare("SELECT COUNT(*) FROM images WHERE user_id = ? AND filename = ? AND id != ?");
$stmt->execute([$user_id, $new_filename, $image_id]);
$count = $stmt->fetchColumn();

// Si le nom existe déjà, ajouter un numéro
if ($count > 0) {
    $counter = 2;
    while (true) {
        $new_filename = $clean_name . '-' . $counter . '.jpg';
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM images WHERE user_id = ? AND filename = ? AND id != ?");
        $stmt->execute([$user_id, $new_filename, $image_id]);
        if ($stmt->fetchColumn() == 0) {
            break;
        }
        $counter++;
    }
}

// Renommer le fichier physique
$old_path = $image['path'];
$new_path = dirname($old_path) . '/' . $new_filename;

if (file_exists($old_path)) {
    if (!rename($old_path, $new_path)) {
        echo json_encode(['success' => false, 'error' => 'Impossible de renommer le fichier']);
        exit;
    }
}

// Mettre à jour la base de données
$stmt = $pdo->prepare("UPDATE images SET filename = ?, original_filename = ?, path = ? WHERE id = ? AND user_id = ?");
$stmt->execute([$new_filename, $clean_name, $new_path, $image_id, $user_id]);

// Récupérer le username pour l'URL
$stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$new_url = SITE_URL . '/i/' . $user['username'] . '/' . $new_filename;

echo json_encode([
    'success' => true,
    'new_filename' => $new_filename,
    'new_url' => $new_url
]);
?>