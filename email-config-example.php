<?php
// Configuration des emails pour Zenu

// Configuration SMTP (à personnaliser selon votre hébergeur)
define('SMTP_HOST', 'ssl0.ovh.net'); // Serveur SMTP OVH
define('SMTP_PORT', 465); // Port SSL
define('SMTP_SECURE', 'ssl'); // ou 'tls'
define('SMTP_USERNAME', 'noreply@XXX.fr'); // Votre email
define('SMTP_PASSWORD', 'XXXXXXX'); // Mot de passe email
define('SMTP_FROM_EMAIL', 'noreply@XXX.fr');
define('SMTP_FROM_NAME', 'Zenu');

// Configuration reCAPTCHA v3
define('RECAPTCHA_SITE_KEY', 'VOTRE_SITE_KEY'); // Clé publique
define('RECAPTCHA_SECRET_KEY', 'VOTRE_SECRET_KEY'); // Clé secrète
define('RECAPTCHA_MIN_SCORE', 0.5); // Score minimum (0.0 à 1.0)

/**
 * Envoyer un email via SMTP
 */
function sendEmail($to, $subject, $htmlBody, $textBody = '') {
    // Headers
    $headers = [
        'From: ' . SMTP_FROM_NAME . ' <' . SMTP_FROM_EMAIL . '>',
        'Reply-To: ' . SMTP_FROM_EMAIL,
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8'
    ];
    
    // Pour OVH et la plupart des hébergeurs, on peut utiliser mail() directement
    // car ils redirigent via leur SMTP
    $success = mail($to, $subject, $htmlBody, implode("\r\n", $headers));
    
    // Logger l'envoi
    if ($success) {
        error_log("Email envoyé à: $to - Sujet: $subject");
    } else {
        error_log("Erreur envoi email à: $to - Sujet: $subject");
    }
    
    return $success;
}

/**
 * Template d'email de base
 */
function getEmailTemplate($title, $content, $buttonText = '', $buttonUrl = '') {
    $button = '';
    if ($buttonText && $buttonUrl) {
        $button = '<table border="0" cellpadding="0" cellspacing="0" style="margin: 30px 0;">
            <tr>
                <td style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 6px; text-align: center;">
                    <a href="' . htmlspecialchars($buttonUrl) . '" 
                       style="display: inline-block; padding: 12px 30px; color: #ffffff; text-decoration: none; font-weight: 600; font-size: 16px;">
                        ' . htmlspecialchars($buttonText) . '
                    </a>
                </td>
            </tr>
        </table>';
    }
    
    return '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($title) . '</title>
</head>
<body style="margin: 0; padding: 0; font-family: \'Segoe UI\', Arial, sans-serif; background-color: #f5f5f5;">
    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f5f5f5; padding: 20px;">
        <tr>
            <td align="center">
                <table border="0" cellpadding="0" cellspacing="0" width="600" style="background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 28px;">🧘 Zenu</h1>
                            <p style="margin: 10px 0 0; color: rgba(255,255,255,0.9); font-size: 14px;">Outils simples et zen</p>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <h2 style="margin: 0 0 20px; color: #333; font-size: 24px;">' . htmlspecialchars($title) . '</h2>
                            <div style="color: #555; font-size: 16px; line-height: 1.6;">
                                ' . $content . '
                            </div>
                            ' . $button . '
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8f9fa; padding: 20px 30px; text-align: center; border-top: 1px solid #e0e0e0;">
                            <p style="margin: 0; color: #999; font-size: 12px;">
                                Cet email a été envoyé par Zenu<br>
                                Si vous n\'avez pas demandé cet email, vous pouvez l\'ignorer en toute sécurité.
                            </p>
                            <p style="margin: 15px 0 0; color: #999; font-size: 12px;">
                                <a href="' . SITE_URL . '" style="color: #667eea; text-decoration: none;">Zenu.fr</a> · 
                                <a href="' . SITE_URL . '/privacy.php" style="color: #667eea; text-decoration: none;">Confidentialité</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
}

/**
 * Envoyer l'email de vérification
 */
function sendVerificationEmail($email, $username, $token) {
    $verificationUrl = SITE_URL . '/verify-email.php?token=' . urlencode($token);
    
    $content = '<p>Bonjour <strong>' . htmlspecialchars($username) . '</strong>,</p>
    <p>Merci de vous être inscrit sur Zenu ! Pour activer votre compte et commencer à utiliser nos services, veuillez vérifier votre adresse email en cliquant sur le bouton ci-dessous :</p>
    <p style="color: #999; font-size: 14px; margin-top: 30px;">
        Ce lien est valide pendant 24 heures. Si vous n\'avez pas créé de compte sur Zenu, ignorez simplement cet email.
    </p>
    <p style="color: #999; font-size: 12px; margin-top: 20px;">
        Si le bouton ne fonctionne pas, copiez ce lien dans votre navigateur :<br>
        <a href="' . htmlspecialchars($verificationUrl) . '" style="color: #667eea; word-break: break-all;">' . htmlspecialchars($verificationUrl) . '</a>
    </p>';
    
    $html = getEmailTemplate(
        'Vérifiez votre adresse email',
        $content,
        '✅ Vérifier mon email',
        $verificationUrl
    );
    
    return sendEmail($email, '[Zenu] Vérifiez votre adresse email', $html);
}

/**
 * Envoyer l'email de réinitialisation de mot de passe
 */
function sendPasswordResetEmail($email, $username, $token) {
    $resetUrl = SITE_URL . '/reset-password.php?token=' . urlencode($token);
    
    $content = '<p>Bonjour <strong>' . htmlspecialchars($username) . '</strong>,</p>
    <p>Vous avez demandé à réinitialiser votre mot de passe sur Zenu. Cliquez sur le bouton ci-dessous pour choisir un nouveau mot de passe :</p>
    <p style="color: #999; font-size: 14px; margin-top: 30px;">
        Ce lien est valide pendant 1 heure. Si vous n\'avez pas demandé cette réinitialisation, ignorez cet email et votre mot de passe restera inchangé.
    </p>
    <p style="color: #999; font-size: 12px; margin-top: 20px;">
        Si le bouton ne fonctionne pas, copiez ce lien dans votre navigateur :<br>
        <a href="' . htmlspecialchars($resetUrl) . '" style="color: #667eea; word-break: break-all;">' . htmlspecialchars($resetUrl) . '</a>
    </p>';
    
    $html = getEmailTemplate(
        'Réinitialisation de votre mot de passe',
        $content,
        '🔑 Réinitialiser mon mot de passe',
        $resetUrl
    );
    
    return sendEmail($email, '[Zenu] Réinitialisation de mot de passe', $html);
}

/**
 * Vérifier le reCAPTCHA v3
 */
function verifyRecaptcha($token) {
    $data = [
        'secret' => RECAPTCHA_SECRET_KEY,
        'response' => $token,
        'remoteip' => getClientIP()
    ];
    
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    
    $context = stream_context_create($options);
    $result = @file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $context);
    
    if ($result === false) {
        error_log('Erreur reCAPTCHA: impossible de contacter Google');
        return ['success' => false, 'score' => 0];
    }
    
    $response = json_decode($result, true);
    
    return [
        'success' => $response['success'] ?? false,
        'score' => $response['score'] ?? 0,
        'action' => $response['action'] ?? '',
        'challenge_ts' => $response['challenge_ts'] ?? ''
    ];
}

/**
 * Logger une action de sécurité
 */
function logSecurityAction($userId, $action, $details = '') {
    global $pdo;
    
    $stmt = $pdo->prepare("
        INSERT INTO security_logs (user_id, action, details, ip_address, user_agent)
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $userId,
        $action,
        $details,
        getClientIP(),
        $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
    ]);
}
?>