-- Mise à jour de la base de données pour l'éditeur d'images
-- Exécuter ce script pour ajouter les champs de métadonnées d'édition

-- Ajouter une colonne pour indiquer si l'image a été éditée
ALTER TABLE images 
ADD COLUMN is_edited TINYINT(1) DEFAULT 0 AFTER is_public;

-- Ajouter une colonne pour stocker le mode d'édition utilisé
ALTER TABLE images 
ADD COLUMN edit_mode VARCHAR(20) NULL AFTER is_edited;

-- Ajouter une colonne pour l'ID de l'image originale (si c'est une édition)
ALTER TABLE images 
ADD COLUMN original_image_id INT NULL AFTER edit_mode;

-- Ajouter une foreign key pour lier aux images originales
ALTER TABLE images 
ADD CONSTRAINT fk_original_image 
FOREIGN KEY (original_image_id) REFERENCES images(id) ON DELETE SET NULL;

-- Index pour améliorer les performances
ALTER TABLE images 
ADD INDEX idx_is_edited (is_edited);

ALTER TABLE images 
ADD INDEX idx_original_image (original_image_id);

-- Créer une table pour l'historique des éditions (optionnel mais recommandé)
CREATE TABLE IF NOT EXISTS image_edit_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    image_id INT NOT NULL,
    user_id INT NOT NULL,
    edit_mode VARCHAR(20) NOT NULL,
    edit_data JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (image_id) REFERENCES images(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_image_id (image_id),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ajouter des commentaires pour la documentation
ALTER TABLE images 
MODIFY COLUMN is_edited TINYINT(1) DEFAULT 0 
COMMENT '1 si l\'image a été éditée avec l\'éditeur Zenu';

ALTER TABLE images 
MODIFY COLUMN edit_mode VARCHAR(20) NULL 
COMMENT 'Mode d\'édition utilisé: simple, advanced, pro';

ALTER TABLE images 
MODIFY COLUMN original_image_id INT NULL 
COMMENT 'ID de l\'image originale si c\'est une édition';

-- Vérifier les modifications
SHOW COLUMNS FROM images LIKE '%edit%';
SELECT COUNT(*) as total_images_edited FROM images WHERE is_edited = 1;
