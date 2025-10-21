<?php
/**
 * Fonctions utilitaires pour la gestion des images
 */

/**
 * Générer une miniature d'une image
 * @param string $sourcePath Chemin de l'image source
 * @param string $destPath Chemin de destination de la miniature
 * @param int $maxWidth Largeur maximale
 * @param int $maxHeight Hauteur maximale
 * @return bool Succès ou échec
 */
function generateThumbnail($sourcePath, $destPath, $maxWidth = 300, $maxHeight = 300) {
    if (!file_exists($sourcePath)) {
        return false;
    }
    
    // Obtenir les informations de l'image
    $imageInfo = getimagesize($sourcePath);
    if ($imageInfo === false) {
        return false;
    }
    
    list($width, $height, $type) = $imageInfo;
    
    // Créer l'image source selon le type
    switch ($type) {
        case IMAGETYPE_JPEG:
            $source = imagecreatefromjpeg($sourcePath);
            break;
        case IMAGETYPE_PNG:
            $source = imagecreatefrompng($sourcePath);
            break;
        case IMAGETYPE_GIF:
            $source = imagecreatefromgif($sourcePath);
            break;
        case IMAGETYPE_WEBP:
            $source = imagecreatefromwebp($sourcePath);
            break;
        default:
            return false;
    }
    
    if (!$source) {
        return false;
    }
    
    // Calculer les nouvelles dimensions en gardant le ratio
    $ratio = min($maxWidth / $width, $maxHeight / $height);
    $newWidth = round($width * $ratio);
    $newHeight = round($height * $ratio);
    
    // Créer la miniature
    $thumbnail = imagecreatetruecolor($newWidth, $newHeight);
    
    // Préserver la transparence pour PNG et GIF
    if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
        imagealphablending($thumbnail, false);
        imagesavealpha($thumbnail, true);
        $transparent = imagecolorallocatealpha($thumbnail, 255, 255, 255, 127);
        imagefilledrectangle($thumbnail, 0, 0, $newWidth, $newHeight, $transparent);
    }
    
    // Redimensionner
    imagecopyresampled($thumbnail, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    
    // Créer le dossier de destination si nécessaire
    $destDir = dirname($destPath);
    if (!is_dir($destDir)) {
        mkdir($destDir, 0755, true);
    }
    
    // Sauvegarder la miniature
    $success = false;
    switch ($type) {
        case IMAGETYPE_JPEG:
            $success = imagejpeg($thumbnail, $destPath, 85);
            break;
        case IMAGETYPE_PNG:
            $success = imagepng($thumbnail, $destPath, 8);
            break;
        case IMAGETYPE_GIF:
            $success = imagegif($thumbnail, $destPath);
            break;
        case IMAGETYPE_WEBP:
            $success = imagewebp($thumbnail, $destPath, 85);
            break;
    }
    
    // Libérer la mémoire
    imagedestroy($source);
    imagedestroy($thumbnail);
    
    return $success;
}

/**
 * Formater la taille d'un fichier
 * @param int $bytes Taille en octets
 * @return string Taille formatée
 */
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

/**
 * Générer un token de partage unique
 * @return string Token de 32 caractères
 */
function generateShareToken() {
    return bin2hex(random_bytes(16));
}

/**
 * Obtenir les métadonnées complètes d'une image
 * @param string $filePath Chemin du fichier
 * @return array|false Métadonnées ou false
 */
function getImageMetadata($filePath) {
    if (!file_exists($filePath)) {
        return false;
    }
    
    $imageInfo = getimagesize($filePath);
    if ($imageInfo === false) {
        return false;
    }
    
    list($width, $height, $type) = $imageInfo;
    
    return [
        'width' => $width,
        'height' => $height,
        'type' => $type,
        'mime' => $imageInfo['mime'] ?? 'unknown',
        'size' => filesize($filePath),
        'file_size' => filesize($filePath), // Compatibilité
        'dimensions' => $width . 'x' . $height
    ];
}

/**
 * Supprimer une image et sa miniature (soft delete)
 * @param PDO $pdo Connexion PDO
 * @param int $imageId ID de l'image
 * @param int $userId ID de l'utilisateur (pour vérifier les droits)
 * @return bool Succès ou échec
 */
function softDeleteImage($pdo, $imageId, $userId) {
    $stmt = $pdo->prepare("
        UPDATE images 
        SET is_deleted = 1, deleted_at = NOW() 
        WHERE id = ? AND user_id = ? AND is_deleted = 0
    ");
    
    return $stmt->execute([$imageId, $userId]);
}

/**
 * Restaurer une image de la corbeille
 * @param PDO $pdo Connexion PDO
 * @param int $imageId ID de l'image
 * @param int $userId ID de l'utilisateur
 * @return bool Succès ou échec
 */
function restoreImage($pdo, $imageId, $userId) {
    $stmt = $pdo->prepare("
        UPDATE images 
        SET is_deleted = 0, deleted_at = NULL 
        WHERE id = ? AND user_id = ? AND is_deleted = 1
    ");
    
    return $stmt->execute([$imageId, $userId]);
}

/**
 * Supprimer définitivement une image (hard delete)
 * @param PDO $pdo Connexion PDO
 * @param int $imageId ID de l'image
 * @param int $userId ID de l'utilisateur
 * @return bool Succès ou échec
 */
function hardDeleteImage($pdo, $imageId, $userId) {
    // Récupérer les chemins des fichiers
    // Adapté pour votre structure (path au lieu de file_path)
    $stmt = $pdo->prepare("
        SELECT file_path, thumbnail_path 
        FROM images 
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$imageId, $userId]);
    $image = $stmt->fetch();
    
    if (!$image) {
        return false;
    }
    
    // Supprimer les fichiers physiques
    if (file_exists($image['file_path'])) {
        unlink($image['file_path']);
    }
    
    if ($image['thumbnail_path'] && file_exists($image['thumbnail_path'])) {
        unlink($image['thumbnail_path']);
    }
    
    // Supprimer de la base de données
    $stmt = $pdo->prepare("DELETE FROM images WHERE id = ? AND user_id = ?");
    return $stmt->execute([$imageId, $userId]);
}

/**
 * Changer la visibilité d'une image
 * @param PDO $pdo Connexion PDO
 * @param int $imageId ID de l'image
 * @param int $userId ID de l'utilisateur
 * @param bool $isPublic Rendre publique ou privée
 * @return array Résultat avec token de partage si publique
 */
function toggleImageVisibility($pdo, $imageId, $userId, $isPublic) {
    $shareToken = null;
    
    if ($isPublic) {
        // Vérifier si un token existe déjà
        $stmt = $pdo->prepare("SELECT share_token FROM images WHERE id = ? AND user_id = ?");
        $stmt->execute([$imageId, $userId]);
        $image = $stmt->fetch();
        
        if ($image && $image['share_token']) {
            $shareToken = $image['share_token'];
        } else {
            $shareToken = generateShareToken();
        }
        
        $stmt = $pdo->prepare("
            UPDATE images 
            SET is_public = 1, share_token = ? 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$shareToken, $imageId, $userId]);
    } else {
        $stmt = $pdo->prepare("
            UPDATE images 
            SET is_public = 0 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$imageId, $userId]);
    }
    
    return [
        'success' => true,
        'is_public' => $isPublic,
        'share_token' => $shareToken,
        'share_url' => $shareToken ? SITE_URL . '/share.php?t=' . $shareToken : null
    ];
}

/**
 * Rechercher des images
 * @param PDO $pdo Connexion PDO
 * @param int $userId ID de l'utilisateur
 * @param array $params Paramètres de recherche
 * @return array Résultats de la recherche
 */
function searchImages($pdo, $userId, $params = []) {
    $query = "SELECT * FROM images WHERE user_id = ? AND is_deleted = 0";
    $bindings = [$userId];
    
    // Recherche par nom
    if (!empty($params['search'])) {
        $query .= " AND (filename LIKE ? OR original_filename LIKE ?)";
        $searchTerm = '%' . $params['search'] . '%';
        $bindings[] = $searchTerm;
        $bindings[] = $searchTerm;
    }
    
    // Filtre par visibilité
    if (isset($params['visibility'])) {
        if ($params['visibility'] === 'public') {
            $query .= " AND is_public = 1";
        } elseif ($params['visibility'] === 'private') {
            $query .= " AND is_public = 0";
        }
    }
    
    // Filtre par taille de fichier
    if (!empty($params['min_size'])) {
        $query .= " AND file_size >= ?";
        $bindings[] = $params['min_size'];
    }
    if (!empty($params['max_size'])) {
        $query .= " AND file_size <= ?";
        $bindings[] = $params['max_size'];
    }
    
    // Tri
    $validSorts = ['created_at', 'filename', 'file_size', 'views'];
    $sort = in_array($params['sort'] ?? '', $validSorts) ? $params['sort'] : 'created_at';
    $order = ($params['order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
    $query .= " ORDER BY $sort $order";
    
    // Pagination
    $page = max(1, intval($params['page'] ?? 1));
    $perPage = min(50, max(10, intval($params['per_page'] ?? 20)));
    $offset = ($page - 1) * $perPage;
    
    $query .= " LIMIT ? OFFSET ?";
    $bindings[] = $perPage;
    $bindings[] = $offset;
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($bindings);
    
    return [
        'images' => $stmt->fetchAll(),
        'page' => $page,
        'per_page' => $perPage
    ];
}

/**
 * Nettoyer le nom de fichier pour le rendre sûr
 * @param string $filename Nom de fichier
 * @return string Nom nettoyé
 */
function sanitizeFilename($filename) {
    // Retirer l'extension
    $extension = pathinfo($filename, PATHINFO_EXTENSION);
    $name = pathinfo($filename, PATHINFO_FILENAME);
    
    // Nettoyer le nom
    $name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $name);
    $name = preg_replace('/_+/', '_', $name);
    $name = trim($name, '_');
    
    return $name . '.' . $extension;
}
?>