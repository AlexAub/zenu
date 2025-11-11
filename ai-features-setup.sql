-- Mise à jour pour fonctionnalités IA
-- À exécuter via phpMyAdmin ou ligne de commande MySQL

-- Table pour tracker les opérations IA
CREATE TABLE IF NOT EXISTS ai_operations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    image_id INT NOT NULL,
    operation_type ENUM('remove-bg', 'enhance', 'smart-crop', 'optimize', 'upscale', 'colorize') NOT NULL,
    options JSON NULL,
    processing_time DECIMAL(10,2) NULL COMMENT 'En secondes',
    original_size INT NULL COMMENT 'Taille du fichier original en octets',
    result_size INT NULL COMMENT 'Taille du fichier résultat en octets',
    compression_ratio DECIMAL(5,2) NULL COMMENT 'Ratio de compression en pourcentage',
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    error_message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (image_id) REFERENCES images(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_image_id (image_id),
    INDEX idx_operation_type (operation_type),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table pour les statistiques d'utilisation IA par utilisateur
CREATE TABLE IF NOT EXISTS ai_usage_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    operation_type ENUM('remove-bg', 'enhance', 'smart-crop', 'optimize', 'upscale', 'colorize') NOT NULL,
    usage_count INT DEFAULT 0,
    last_used_at TIMESTAMP NULL,
    total_processing_time DECIMAL(10,2) DEFAULT 0 COMMENT 'Temps total en secondes',
    total_images_processed INT DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_operation (user_id, operation_type),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table pour les quotas IA (pour futures limitations si nécessaire)
CREATE TABLE IF NOT EXISTS ai_quotas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    quota_type ENUM('daily', 'monthly', 'total') NOT NULL,
    operation_type ENUM('remove-bg', 'enhance', 'smart-crop', 'optimize', 'upscale', 'colorize', 'all') NOT NULL,
    max_operations INT NOT NULL DEFAULT 100,
    used_operations INT DEFAULT 0,
    reset_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_quota (user_id, quota_type, operation_type),
    INDEX idx_user_id (user_id),
    INDEX idx_reset_at (reset_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Créer un dossier temporaire pour les traitements IA
-- Note : Ce dossier doit être créé manuellement sur le serveur avec les bonnes permissions
-- mkdir -p uploads/temp
-- chmod 755 uploads/temp

-- Événement pour nettoyer les fichiers temporaires (plus de 24h)
DELIMITER $$
CREATE EVENT IF NOT EXISTS cleanup_ai_temp_files
ON SCHEDULE EVERY 1 DAY
DO BEGIN
    -- Cette requête ne peut pas supprimer les fichiers physiques,
    -- mais vous pouvez créer un script PHP séparé qui sera appelé par cron
    -- pour supprimer les fichiers dans uploads/temp/ plus vieux que 24h
    
    -- Pour l'instant, on nettoie juste les entrées d'opérations échouées
    DELETE FROM ai_operations 
    WHERE status = 'failed' 
    AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY);
    
    -- Nettoyer les opérations en attente depuis plus de 1h (probablement bloquées)
    UPDATE ai_operations 
    SET status = 'failed', 
        error_message = 'Timeout - opération abandonnée'
    WHERE status IN ('pending', 'processing')
    AND created_at < DATE_SUB(NOW(), INTERVAL 1 HOUR);
END$$
DELIMITER ;

-- Insérer des quotas par défaut pour les nouveaux utilisateurs
-- (Ceci est optionnel, à activer seulement si vous voulez limiter l'utilisation)
/*
DELIMITER $$
CREATE TRIGGER ai_quotas_after_user_insert
AFTER INSERT ON users
FOR EACH ROW
BEGIN
    -- Quota journalier de 50 opérations IA au total
    INSERT INTO ai_quotas (user_id, quota_type, operation_type, max_operations, reset_at)
    VALUES (NEW.id, 'daily', 'all', 50, DATE_ADD(NOW(), INTERVAL 1 DAY));
    
    -- Quota mensuel de 500 opérations
    INSERT INTO ai_quotas (user_id, quota_type, operation_type, max_operations, reset_at)
    VALUES (NEW.id, 'monthly', 'all', 500, DATE_ADD(NOW(), INTERVAL 1 MONTH));
END$$
DELIMITER ;
*/

-- Vue pour obtenir facilement les statistiques IA par utilisateur
CREATE OR REPLACE VIEW v_ai_user_stats AS
SELECT 
    u.id as user_id,
    u.username,
    u.email,
    COUNT(DISTINCT ao.id) as total_operations,
    COUNT(DISTINCT CASE WHEN ao.operation_type = 'remove-bg' THEN ao.id END) as remove_bg_count,
    COUNT(DISTINCT CASE WHEN ao.operation_type = 'enhance' THEN ao.id END) as enhance_count,
    COUNT(DISTINCT CASE WHEN ao.operation_type = 'smart-crop' THEN ao.id END) as smart_crop_count,
    COUNT(DISTINCT CASE WHEN ao.operation_type = 'optimize' THEN ao.id END) as optimize_count,
    SUM(ao.processing_time) as total_processing_time,
    AVG(ao.processing_time) as avg_processing_time,
    MAX(ao.created_at) as last_operation_date
FROM users u
LEFT JOIN ai_operations ao ON u.id = ao.user_id AND ao.status = 'completed'
GROUP BY u.id, u.username, u.email;

-- Afficher un résumé pour vérifier
SELECT 'Installation des tables IA terminée' as status;
SELECT COUNT(*) as ai_operations_count FROM ai_operations;
SELECT COUNT(*) as ai_usage_stats_count FROM ai_usage_stats;
SELECT COUNT(*) as ai_quotas_count FROM ai_quotas;
