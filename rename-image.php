<?php
require_once 'config.php';
require_once 'security.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Support pour les deux formats de requête (POST JSON ou POST form)
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$image_id = intval($input['image_id'] ?? 0);
$new_name = trim($input['new_name'] ?? '');

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
    echo json_encode(['success' => false, 'error' => 'Nom invalide après nettoyage']);
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

// Déterminer l'extension actuelle du fichier
$current_extension = pathinfo($image['filename'], PATHINFO_EXTENSION);
if (empty($current_extension)) {
    $current_extension = pathinfo($image['file_path'], PATHINFO_EXTENSION);
}
if (empty($current_extension)) {
    $current_extension = 'jpg'; // Par défaut
}

// Vérifier si un fichier avec ce nom existe déjà (dans original_filename)
$new_filename = $clean_name . '.' . $current_extension;
$stmt = $pdo->prepare("
    SELECT id, original_filename 
    FROM images 
    WHERE user_id = ? 
    AND (original_filename = ? OR filename = ?)
    AND id != ?
");
$stmt->execute([$user_id, $clean_name, $new_filename, $image_id]);
$existing = $stmt->fetch();

$renamed_with_suffix = false;
$original_clean_name = $clean_name;

// Si le nom existe déjà, ajouter un numéro
if ($existing) {
    $counter = 2;
    $max_attempts = 100; // Limiter les tentatives
    
    while ($counter <= $max_attempts) {
        $clean_name = $original_clean_name . '-' . $counter;
        $new_filename = $clean_name . '.' . $current_extension;
        
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM images 
            WHERE user_id = ? 
            AND (original_filename = ? OR filename = ?)
            AND id != ?
        ");
        $stmt->execute([$user_id, $clean_name, $new_filename, $image_id]);
        
        if ($stmt->fetchColumn() == 0) {
            $renamed_with_suffix = true;
            break;
        }
        $counter++;
    }
    
    if ($counter > $max_attempts) {
        echo json_encode([
            'success' => false, 
            'error' => 'Impossible de trouver un nom unique. Trop de fichiers avec des noms similaires.'
        ]);
        exit;
    }
}

// Renommer le fichier physique
$old_path = $image['file_path'];
$new_path = dirname($old_path) . '/' . $new_filename;

// Vérifier que le nouveau chemin n'existe pas déjà physiquement
if (file_exists($new_path) && $new_path !== $old_path) {
    echo json_encode([
        'success' => false, 
        'error' => 'Un fichier avec ce nom existe déjà sur le serveur'
    ]);
    exit;
}

// Renommer si le fichier existe
if (file_exists($old_path) && $old_path !== $new_path) {
    if (!rename($old_path, $new_path)) {
        echo json_encode(['success' => false, 'error' => 'Impossible de renommer le fichier physique']);
        exit;
    }
}

// Mettre à jour la base de données
$stmt = $pdo->prepare("
    UPDATE images 
    SET filename = ?, original_filename = ?, file_path = ? 
    WHERE id = ? AND user_id = ?
");
$success = $stmt->execute([$new_filename, $clean_name, $new_path, $image_id, $user_id]);

if (!$success) {
    // Annuler le renommage du fichier si la BDD échoue
    if (file_exists($new_path)) {
        rename($new_path, $old_path);
    }
    echo json_encode(['success' => false, 'error' => 'Erreur lors de la mise à jour de la base de données']);
    exit;
}

// Récupérer le username pour l'URL
$stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Générer les URLs (avec et sans extension)
$new_url_with_ext = SITE_URL . '/' . $user['username'] . '/' . $new_filename;
$new_url_without_ext = SITE_URL . '/' . $user['username'] . '/' . $clean_name;

// Message de succès personnalisé
$message = 'Image renommée avec succès !';
if ($renamed_with_suffix) {
    $message = "⚠️ Le nom '{$original_clean_name}' existait déjà. L'image a été renommée en '{$clean_name}'.";
}

echo json_encode([
    'success' => true,
    'message' => $message,
    'new_filename' => $new_filename,
    'new_original_filename' => $clean_name,
    'new_url' => $new_url_with_ext,
    'new_url_short' => $new_url_without_ext,
    'renamed_with_suffix' => $renamed_with_suffix,
    'final_name' => $clean_name
]);
?>