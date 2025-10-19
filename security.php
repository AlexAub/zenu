<?php
// Fonctions de sécurité pour Zenu

/**
 * Valider un mot de passe selon la politique de sécurité
 * - Minimum 8 caractères
 * - Au moins 1 majuscule
 * - Au moins 1 chiffre
 */
function validatePassword($password) {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = 'Le mot de passe doit contenir au moins 8 caractères';
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Le mot de passe doit contenir au moins une majuscule';
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Le mot de passe doit contenir au moins un chiffre';
    }
    
    return $errors;
}

/**
 * Valider un username
 * - 3 à 30 caractères
 * - Lettres, chiffres, tirets et underscores uniquement
 * - Doit commencer par une lettre
 */
function validateUsername($username) {
    $errors = [];
    
    if (strlen($username) < 3 || strlen($username) > 30) {
        $errors[] = 'Le nom d\'utilisateur doit contenir entre 3 et 30 caractères';
    }
    
    if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_-]*$/', $username)) {
        $errors[] = 'Le nom d\'utilisateur doit commencer par une lettre et ne peut contenir que des lettres, chiffres, tirets et underscores';
    }
    
    // Liste de usernames réservés
    $reserved = ['admin', 'root', 'system', 'user', 'zenu', 'uploads', 'api', 'www'];
    if (in_array(strtolower($username), $reserved)) {
        $errors[] = 'Ce nom d\'utilisateur est réservé';
    }
    
    return $errors;
}

/**
 * Nettoyer un username pour l'URL
 */
function sanitizeUsername($username) {
    // Convertir en minuscules et remplacer les espaces par des tirets
    $username = strtolower(trim($username));
    $username = preg_replace('/[^a-z0-9_-]/', '', $username);
    return $username;
}

/**
 * Obtenir l'IP du client
 */
function getClientIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

/**
 * Vérifier le rate limiting pour une action
 * @param PDO $pdo
 * @param string $action 'register' ou 'login'
 * @param int $maxAttempts Nombre max de tentatives
 * @param int $windowMinutes Fenêtre de temps en minutes
 * @return array ['allowed' => bool, 'message' => string]
 */
function checkRateLimit($pdo, $action, $maxAttempts = 3, $windowMinutes = 60) {
    $ip = getClientIP();
    
    // Nettoyer les anciennes entrées
    $cleanupTime = date('Y-m-d H:i:s', strtotime("-{$windowMinutes} minutes"));
    $stmt = $pdo->prepare("DELETE FROM rate_limits WHERE last_attempt < ? AND blocked_until IS NULL");
    $stmt->execute([$cleanupTime]);
    
    // Vérifier si l'IP est bloquée
    $stmt = $pdo->prepare("
        SELECT blocked_until, attempts 
        FROM rate_limits 
        WHERE ip_address = ? AND action_type = ?
    ");
    $stmt->execute([$ip, $action]);
    $limit = $stmt->fetch();
    
    if ($limit && $limit['blocked_until']) {
        $blockedUntil = strtotime($limit['blocked_until']);
        if ($blockedUntil > time()) {
            $minutes = ceil(($blockedUntil - time()) / 60);
            return [
                'allowed' => false,
                'message' => "Trop de tentatives. Veuillez réessayer dans {$minutes} minute(s)."
            ];
        } else {
            // Le blocage est expiré, réinitialiser
            $stmt = $pdo->prepare("DELETE FROM rate_limits WHERE ip_address = ? AND action_type = ?");
            $stmt->execute([$ip, $action]);
            $limit = null;
        }
    }
    
    if ($limit) {
        if ($limit['attempts'] >= $maxAttempts) {
            // Bloquer pour 30 minutes
            $blockedUntil = date('Y-m-d H:i:s', strtotime('+30 minutes'));
            $stmt = $pdo->prepare("
                UPDATE rate_limits 
                SET blocked_until = ?, attempts = attempts + 1 
                WHERE ip_address = ? AND action_type = ?
            ");
            $stmt->execute([$blockedUntil, $ip, $action]);
            
            return [
                'allowed' => false,
                'message' => 'Trop de tentatives. Vous êtes bloqué pour 30 minutes.'
            ];
        } else {
            // Incrémenter le compteur
            $stmt = $pdo->prepare("
                UPDATE rate_limits 
                SET attempts = attempts + 1, last_attempt = NOW() 
                WHERE ip_address = ? AND action_type = ?
            ");
            $stmt->execute([$ip, $action]);
        }
    } else {
        // Première tentative
        $stmt = $pdo->prepare("
            INSERT INTO rate_limits (ip_address, action_type, attempts) 
            VALUES (?, ?, 1)
        ");
        $stmt->execute([$ip, $action]);
    }
    
    return ['allowed' => true, 'message' => ''];
}

/**
 * Enregistrer une tentative de connexion échouée
 */
function logFailedLogin($pdo, $email) {
    $ip = getClientIP();
    $stmt = $pdo->prepare("INSERT INTO login_attempts (email, ip_address) VALUES (?, ?)");
    $stmt->execute([$email, $ip]);
    
    // Nettoyer les vieilles tentatives (plus de 24h)
    $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    $stmt->execute();
}

/**
 * Vérifier le honeypot (champ anti-bot)
 */
function checkHoneypot() {
    // Si le champ honeypot est rempli, c'est un bot
    return !empty($_POST['website']);
}
?>