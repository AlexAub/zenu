-- Mise à jour de sécurité : Email verification, Password reset, CAPTCHA

-- 1. Ajouter les champs de vérification email à la table users
ALTER TABLE users 
ADD COLUMN email_verified TINYINT(1) DEFAULT 0 AFTER email,
ADD COLUMN verification_token VARCHAR(64) NULL AFTER email_verified,
ADD COLUMN verification_token_expires DATETIME NULL AFTER verification_token;

-- 2. Table pour les tokens de réinitialisation de mot de passe
CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_expires (expires_at),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Table pour les tentatives de vérification (anti-spam)
CREATE TABLE IF NOT EXISTS verification_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    attempt_type ENUM('email_verification', 'password_reset') NOT NULL,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email_ip (email, ip_address),
    INDEX idx_attempted (attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Table pour les logs de sécurité
CREATE TABLE IF NOT EXISTS security_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    user_agent VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Nettoyer les anciens tokens expirés (à exécuter périodiquement)
-- Créer un événement pour nettoyage automatique si EVENTS est activé
DELIMITER $$
CREATE EVENT IF NOT EXISTS cleanup_expired_tokens
ON SCHEDULE EVERY 1 DAY
DO BEGIN
    -- Supprimer les tokens de réinitialisation expirés depuis plus de 7 jours
    DELETE FROM password_resets 
    WHERE expires_at < DATE_SUB(NOW(), INTERVAL 7 DAY);
    
    -- Supprimer les vieilles tentatives de vérification (plus de 30 jours)
    DELETE FROM verification_attempts 
    WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
    
    -- Supprimer les vieux logs de sécurité (plus de 90 jours)
    DELETE FROM security_logs 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
END$$
DELIMITER ;

-- Note: Si les événements MySQL ne sont pas activés sur votre hébergement,
-- vous devrez exécuter manuellement ces requêtes de nettoyage périodiquement