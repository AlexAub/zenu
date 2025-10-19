-- Mise à jour de sécurité et ajout username

-- Ajouter le champ username à la table users
ALTER TABLE users ADD COLUMN username VARCHAR(50) NULL UNIQUE AFTER email;
ALTER TABLE users ADD INDEX idx_username (username);

-- Pour les users existants, générer un username temporaire basé sur l'email
UPDATE users SET username = CONCAT('user_', id) WHERE username IS NULL;

-- Rendre le champ obligatoire maintenant que tous les users en ont un
ALTER TABLE users MODIFY username VARCHAR(50) NOT NULL;

-- Table pour le rate limiting (limite d'inscriptions/connexions)
CREATE TABLE IF NOT EXISTS rate_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    action_type ENUM('register', 'login') NOT NULL,
    attempts INT DEFAULT 1,
    last_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    blocked_until TIMESTAMP NULL,
    INDEX idx_ip_action (ip_address, action_type),
    INDEX idx_blocked (blocked_until)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table pour les tentatives de connexion échouées
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_ip (ip_address),
    INDEX idx_attempted (attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;