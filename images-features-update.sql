-- Mise à jour pour nouvelles fonctionnalités images
-- Adapté à votre structure existante

-- 1. Ajouter les nouveaux champs à la table images existante
ALTER TABLE images 
ADD COLUMN is_public TINYINT(1) DEFAULT 0 AFTER path,
ADD COLUMN is_deleted TINYINT(1) DEFAULT 0 AFTER is_public,
ADD COLUMN deleted_at DATETIME NULL AFTER is_deleted,
ADD COLUMN thumbnail_path VARCHAR(500) NULL AFTER path,
ADD COLUMN mime_type VARCHAR(100) NULL AFTER height,
ADD COLUMN views INT DEFAULT 0 AFTER mime_type,
ADD COLUMN share_token VARCHAR(64) NULL AFTER views;

-- 2. Ajouter les index pour performances
ALTER TABLE images
ADD INDEX idx_user_deleted (user_id, is_deleted),
ADD INDEX idx_share_token (share_token),
ADD INDEX idx_deleted_at (deleted_at),
ADD INDEX idx_is_public (is_public);

-- 3. Créer une colonne calculée pour dimensions (compatible avec vos colonnes width/height)
ALTER TABLE images 
ADD COLUMN dimensions VARCHAR(20) 
GENERATED ALWAYS AS (CONCAT(width, 'x', height)) STORED AFTER height;

-- 4. Renommer la colonne 'size' en 'file_size' pour cohérence (optionnel)
-- Si vous préférez garder 'size', commentez cette ligne
ALTER TABLE images 
CHANGE COLUMN size file_size INT NOT NULL;

-- 5. Renommer 'path' en 'file_path' pour cohérence (optionnel)
-- Si vous préférez garder 'path', commentez cette ligne
ALTER TABLE images 
CHANGE COLUMN path file_path VARCHAR(500) NOT NULL;

-- 6. Table pour les tags d'images (pour recherche avancée future)
CREATE TABLE IF NOT EXISTS image_tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    image_id INT NOT NULL,
    tag VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (image_id) REFERENCES images(id) ON DELETE CASCADE,
    INDEX idx_tag (tag),
    INDEX idx_image_id (image_id),
    UNIQUE KEY unique_image_tag (image_id, tag)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Table pour les statistiques de partage
CREATE TABLE IF NOT EXISTS image_shares (
    id INT AUTO_INCREMENT PRIMARY KEY,
    image_id INT NOT NULL,
    share_token VARCHAR(64) NOT NULL,
    views INT DEFAULT 0,
    last_viewed_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (image_id) REFERENCES images(id) ON DELETE CASCADE,
    INDEX idx_share_token (share_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Mettre à jour les images existantes avec des valeurs par défaut
UPDATE images 
SET is_public = 0, 
    is_deleted = 0
WHERE is_public IS NULL 
   OR is_deleted IS NULL;

-- 9. Nettoyer automatiquement les images supprimées depuis plus de 30 jours
-- Note: Vérifiez que event_scheduler est activé sur votre serveur
DELIMITER $$

DROP EVENT IF EXISTS cleanup_deleted_images$$

CREATE EVENT cleanup_deleted_images
ON SCHEDULE EVERY 1 DAY
DO BEGIN
    -- Supprimer définitivement les images après 30 jours dans la corbeille
    DELETE FROM images 
    WHERE is_deleted = 1 
    AND deleted_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
END$$

DELIMITER ;

-- 10. Vérifier que l'événement est bien créé
-- SHOW EVENTS LIKE 'cleanup_deleted_images';

-- 11. Si vous voulez désactiver l'auto-nettoyage, exécutez :
-- DROP EVENT IF EXISTS cleanup_deleted_images;