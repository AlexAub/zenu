<?php
require_once '../config.php';
require_once '../security.php';
require_once '../image-functions.php';

header('Content-Type: application/json');

// Array pour collecter les logs
$debugLogs = [];
$debugLogs[] = "=== DÉBUT SAUVEGARDE IMAGE ÉDITÉE ===";
$debugLogs[] = "Timestamp: " . date('Y-m-d H:i:s');

// Vérifier la connexion
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Non authentifié', 'debug' => $debugLogs]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée', 'debug' => $debugLogs]);
    exit;
}

$userId = $_SESSION['user_id'];
$mode = $_POST['mode'] ?? 'simple';

$debugLogs[] = "User ID: $userId";
$debugLogs[] = "Mode: $mode";

// LOG: Afficher TOUS les POST reçus
$debugLogs[] = "";
$debugLogs[] = "📦 DONNÉES POST REÇUES:";
foreach ($_POST as $key => $value) {
    $debugLogs[] = "   • $key: " . (is_string($value) ? "'$value'" : print_r($value, true));
}

// Vérifier si un fichier est envoyé
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    $debugLogs[] = "";
    $debugLogs[] = "❌ PROBLÈME FICHIER:";
    $debugLogs[] = "   • isset(\$_FILES['image']): " . (isset($_FILES['image']) ? 'OUI' : 'NON');
    if (isset($_FILES['image'])) {
        $debugLogs[] = "   • Error code: " . $_FILES['image']['error'];
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => 'Fichier trop volumineux (php.ini)',
            UPLOAD_ERR_FORM_SIZE => 'Fichier trop volumineux (formulaire)',
            UPLOAD_ERR_PARTIAL => 'Fichier partiellement uploadé',
            UPLOAD_ERR_NO_FILE => 'Aucun fichier envoyé',
            UPLOAD_ERR_NO_TMP_DIR => 'Dossier temporaire manquant',
            UPLOAD_ERR_CANT_WRITE => 'Échec écriture disque',
            UPLOAD_ERR_EXTENSION => 'Extension PHP a arrêté l\'upload'
        ];
        $errorCode = $_FILES['image']['error'];
        $debugLogs[] = "   • Message: " . ($errorMessages[$errorCode] ?? 'Erreur inconnue');
    }
    echo json_encode(['success' => false, 'error' => 'Aucun fichier reçu', 'debug' => $debugLogs]);
    exit;
}

$file = $_FILES['image'];

$debugLogs[] = "";
$debugLogs[] = "📁 FICHIER REÇU:";
$debugLogs[] = "   • Nom: {$file['name']}";
$debugLogs[] = "   • Type: {$file['type']}";
$debugLogs[] = "   • Taille: " . round($file['size'] / 1024, 2) . " KB";
$debugLogs[] = "   • Tmp: {$file['tmp_name']}";

// Vérifier la taille (10 MB max)
if ($file['size'] > 10 * 1024 * 1024) {
    echo json_encode(['success' => false, 'error' => 'Fichier trop volumineux (max 10 MB)', 'debug' => $debugLogs]);
    exit;
}

// Vérifier le type MIME
$allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mime_type, $allowed_types)) {
    $debugLogs[] = "❌ Type MIME non autorisé: $mime_type";
    echo json_encode(['success' => false, 'error' => 'Type de fichier non autorisé', 'debug' => $debugLogs]);
    exit;
}

$debugLogs[] = "✅ Fichier valide (MIME: $mime_type)";

// Créer les dossiers
$user_folder = "user_" . $userId;
$user_dir = "../uploads/" . $user_folder;
$thumb_dir = "../uploads/thumbnails/" . $user_folder;

if (!is_dir($user_dir)) mkdir($user_dir, 0755, true);
if (!is_dir($thumb_dir)) mkdir($thumb_dir, 0755, true);

$extension = 'jpg';

// Préfixe selon le mode
$prefix = match($mode) {
    'simple' => 'Editee',
    'advanced' => 'Recadree',
    'pro' => 'Designee',
    default => 'Modifiee'
};

$debugLogs[] = "";
$debugLogs[] = "🏷️ PRÉFIXE DÉTERMINÉ: '$prefix'";

// ⭐⭐⭐ RÉCUPÉRATION DU NOM ORIGINAL ⭐⭐⭐
$originalName = 'Image';

$debugLogs[] = "";
$debugLogs[] = "🔍 RECHERCHE DU NOM ORIGINAL:";

if (isset($_POST['original_image_id']) && !empty($_POST['original_image_id'])) {
    $origId = (int)$_POST['original_image_id'];
    $debugLogs[] = "   • original_image_id reçu: $origId";
    
    $stmt = $pdo->prepare("SELECT id, original_filename, filename FROM images WHERE id = ? AND user_id = ?");
    $stmt->execute([$origId, $userId]);
    $origImage = $stmt->fetch();
    
    if ($origImage) {
        $debugLogs[] = "   ✅ Image trouvée en BDD:";
        $debugLogs[] = "      - ID: {$origImage['id']}";
        $debugLogs[] = "      - original_filename: '{$origImage['original_filename']}'";
        $debugLogs[] = "      - filename: '{$origImage['filename']}'";
        
        // Récupérer le nom et enlever l'extension
        $originalName = pathinfo($origImage['original_filename'], PATHINFO_FILENAME);
        
        // Retirer les préfixes existants
        $originalName = preg_replace('/^(Editee|Recadree|Designee|Modifiee)_/', '', $originalName);
        
        $debugLogs[] = "   ✅ Nom récupéré et nettoyé: '$originalName'";
    } else {
        $debugLogs[] = "   ⚠️ Aucune image trouvée avec cet ID pour user_id=$userId";
        $debugLogs[] = "   → Utilisation du nom par défaut: '$originalName'";
    }
} else {
    $debugLogs[] = "   ⚠️ Aucun original_image_id fourni dans POST";
    $debugLogs[] = "   → Utilisation du nom par défaut: '$originalName'";
}

// Nettoyer le nom
$cleanName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $originalName);
$cleanName = preg_replace('/_+/', '_', $cleanName);
$cleanName = trim($cleanName, '_');

// Option : Suffixe selon le mode (au lieu de préfixe)
$suffix = match($mode) {
    'simple' => 'edit',
    'advanced' => 'crop',
    'pro' => 'design',
    default => 'mod'
};

// Construire : marguerite_robe_verte_edit, marguerite_robe_verte_crop, etc.
$baseName = $cleanName . '_' . $suffix;

$debugLogs[] = "";
$debugLogs[] = "📝 NOM DE BASE CONSTRUIT: '$baseName'";

// ⭐⭐⭐ VÉRIFICATION DES DOUBLONS ⭐⭐⭐
$finalName = $baseName;
$counter = 1;
$maxAttempts = 100;

$debugLogs[] = "";
$debugLogs[] = "🔄 VÉRIFICATION DES DOUBLONS dans 'original_filename':";

// BOUCLE DE VÉRIFICATION
while ($counter <= $maxAttempts) {
    // Vérifier si ce nom existe déjà
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM images 
        WHERE user_id = ? 
        AND original_filename = ?
        AND is_deleted = 0
    ");
    $stmt->execute([$userId, $finalName]);
    $result = $stmt->fetch();
    $count = $result['count'];
    
    $debugLogs[] = "   🔎 Tentative #$counter - Test '$finalName': $count résultat(s) en BDD";
    
    if ($count == 0) {
        // Nom disponible !
        if ($counter > 1) {
            $debugLogs[] = "   ✅ NOM UNIQUE TROUVÉ après $counter tentatives: '$finalName'";
        } else {
            $debugLogs[] = "   ✅ NOM UNIQUE dès la 1ère tentative: '$finalName'";
        }
        break;
    }
    
    // Nom occupé, essayer avec suffixe
    $counter++;
    $finalName = $baseName . '_' . $counter;
    $debugLogs[] = "   ↪️ Nom occupé, prochain essai: '$finalName'";
}

if ($counter > $maxAttempts) {
    $debugLogs[] = "❌ ÉCHEC: Plus de $maxAttempts tentatives";
    echo json_encode([
        'success' => false,
        'error' => 'Trop de fichiers similaires',
        'debug' => $debugLogs
    ]);
    exit;
}

// ⭐⭐⭐ NOMS FINAUX ⭐⭐⭐
$original_filename_bdd = $finalName;  // ← CE NOM EST UNIQUE !
$timestamp = date('YmdHis') . '_' . uniqid();
$physical_filename = $finalName . '_' . $timestamp . '.jpg';

$debugLogs[] = "";
$debugLogs[] = "📋 === RÉSUMÉ DES NOMS FINAUX ===";
$debugLogs[] = "   • original_filename (BDD, UNIQUE): '$original_filename_bdd'";
$debugLogs[] = "   • filename (physique): '$physical_filename'";
$debugLogs[] = "   • Suffixe ajouté: " . ($counter > 1 ? "OUI (_$counter)" : "NON");
$debugLogs[] = "";

$filepath = $user_dir . '/' . $physical_filename;
$thumb_path = $thumb_dir . '/' . $physical_filename;

// Déplacer le fichier
if (!move_uploaded_file($file['tmp_name'], $filepath)) {
    $debugLogs[] = "❌ Échec move_uploaded_file";
    $debugLogs[] = "   Source: {$file['tmp_name']}";
    $debugLogs[] = "   Dest: $filepath";
    echo json_encode(['success' => false, 'error' => 'Erreur déplacement fichier', 'debug' => $debugLogs]);
    exit;
}

$debugLogs[] = "✅ Fichier déplacé vers: $filepath";

// Dimensions
$imageInfo = getimagesize($filepath);
if ($imageInfo === false) {
    unlink($filepath);
    $debugLogs[] = "❌ getimagesize() a échoué";
    echo json_encode(['success' => false, 'error' => 'Fichier invalide', 'debug' => $debugLogs]);
    exit;
}
list($width, $height) = $imageInfo;
$debugLogs[] = "📐 Dimensions: {$width}x{$height} px";

// Miniature
$thumbSuccess = generateThumbnail($filepath, $thumb_path, 300, 300);
$debugLogs[] = ($thumbSuccess ? "✅" : "⚠️") . " Miniature " . ($thumbSuccess ? "créée" : "échec");

// Chemins BDD
$db_filepath = 'uploads/' . $user_folder . '/' . $physical_filename;
$db_thumb_path = $thumbSuccess ? 'uploads/thumbnails/' . $user_folder . '/' . $physical_filename : null;

// ⭐⭐⭐ INSERTION EN BDD ⭐⭐⭐
try {
    $debugLogs[] = "";
    $debugLogs[] = "💾 === INSERTION EN BASE DE DONNÉES ===";
    $debugLogs[] = "   VALUES à insérer:";
    $debugLogs[] = "   • user_id: $userId";
    $debugLogs[] = "   • filename: '$physical_filename'";
    $debugLogs[] = "   • original_filename: '$original_filename_bdd' ⭐ DOIT ÊTRE UNIQUE !";
    $debugLogs[] = "   • file_path: '$db_filepath'";
    $debugLogs[] = "   • thumbnail_path: " . ($db_thumb_path ? "'$db_thumb_path'" : "NULL");
    $debugLogs[] = "   • file_size: " . filesize($filepath);
    $debugLogs[] = "   • width: $width";
    $debugLogs[] = "   • height: $height";
    $debugLogs[] = "   • mime_type: 'image/jpeg'";
    
    $stmt = $pdo->prepare("
        INSERT INTO images (
            user_id, 
            filename, 
            original_filename, 
            file_path, 
            thumbnail_path,
            file_size, 
            width, 
            height, 
            mime_type,
            is_public,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NOW())
    ");
    
    $stmt->execute([
        $userId,
        $physical_filename,
        $original_filename_bdd,  // ⭐ LE NOM UNIQUE !
        $db_filepath,
        $db_thumb_path,
        filesize($filepath),
        $width,
        $height,
        'image/jpeg'
    ]);
    
    $imageId = $pdo->lastInsertId();
    $debugLogs[] = "✅ INSERTION RÉUSSIE avec ID: $imageId";
    
    // Vérification post-insertion
    $stmt = $pdo->prepare("SELECT original_filename FROM images WHERE id = ?");
    $stmt->execute([$imageId]);
    $inserted = $stmt->fetch();
    $debugLogs[] = "🔍 Vérification post-insertion: original_filename = '{$inserted['original_filename']}'";
    
    if ($inserted['original_filename'] !== $original_filename_bdd) {
        $debugLogs[] = "⚠️ ATTENTION: Le nom en BDD ne correspond pas !";
        $debugLogs[] = "   Attendu: '$original_filename_bdd'";
        $debugLogs[] = "   Obtenu: '{$inserted['original_filename']}'";
    } else {
        $debugLogs[] = "✅ Nom correctement enregistré en BDD";
    }
    
    // Username pour URL
    $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    $prettyUrl = SITE_URL . '/' . $user['username'] . '/' . urlencode($original_filename_bdd);
    
    $message = '✅ Image sauvegardée avec succès !';
    if ($counter > 1) {
        $message = "✅ Image sauvegardée sous le nom '$original_filename_bdd' (suffixe _$counter ajouté car nom déjà utilisé)";
    }
    
    $debugLogs[] = "";
    $debugLogs[] = "=== ✅ SUCCÈS COMPLET ===";
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'image_id' => $imageId,
        'url' => $prettyUrl,
        'display_name' => $original_filename_bdd,
        'file_path' => $db_filepath,
        'had_suffix' => $counter > 1,
        'attempts' => $counter,
        'debug' => $debugLogs  // ⭐ TOUS LES LOGS SONT ENVOYÉS !
    ]);
    
} catch (PDOException $e) {
    // En cas d'erreur, supprimer le fichier
    unlink($filepath);
    if (file_exists($thumb_path)) unlink($thumb_path);
    
    $debugLogs[] = "";
    $debugLogs[] = "❌ ERREUR PDO: " . $e->getMessage();
    $debugLogs[] = "Code: " . $e->getCode();
    
    echo json_encode([
        'success' => false,
        'error' => 'Erreur BDD: ' . $e->getMessage(),
        'debug' => $debugLogs
    ]);
}
?>