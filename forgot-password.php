<?php
require_once 'config.php';
require_once 'security.php';
require_once 'email-config.php';

$success = false;
$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $recaptchaToken = $_POST['recaptcha_token'] ?? '';
    
    if (empty($email)) {
        $error = 'Veuillez saisir votre adresse email';
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Adresse email invalide';
    } else {
        // Vérifier reCAPTCHA
        $recaptchaResult = verifyRecaptcha($recaptchaToken);
        
        if (!$recaptchaResult['success'] || $recaptchaResult['score'] < RECAPTCHA_MIN_SCORE) {
            $error = 'Vérification de sécurité échouée. Veuillez réessayer.';
            logSecurityAction(null, 'password_reset_recaptcha_failed', 'Email: ' . $email . ', Score: ' . $recaptchaResult['score']);
        } else {
            // Vérifier le rate limiting
            $ip = getClientIP();
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count 
                FROM verification_attempts 
                WHERE email = ? 
                AND ip_address = ? 
                AND attempt_type = 'password_reset'
                AND attempted_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ");
            $stmt->execute([$email, $ip]);
            $attempts = $stmt->fetch()['count'];
            
            if ($attempts >= 3) {
                $error = 'Trop de demandes. Veuillez réessayer dans 1 heure.';
            } else {
                // Récupérer l'utilisateur
                $stmt = $pdo->prepare("
                    SELECT id, username, email, email_verified 
                    FROM users 
                    WHERE email = ?
                ");
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                
                // Pour la sécurité, on ne révèle pas si l'email existe ou non
                if ($user && $user['email_verified']) {
                    // Générer un token de réinitialisation
                    $resetToken = bin2hex(random_bytes(32));
                    $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
                    
                    // Enregistrer le token
                    $stmt = $pdo->prepare("
                        INSERT INTO password_resets (user_id, token, expires_at, ip_address)
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([$user['id'], $resetToken, $expiresAt, $ip]);
                    
                    // Envoyer l'email
                    $emailSent = sendPasswordResetEmail($email, $user['username'], $resetToken);
                    
                    if ($emailSent) {
                        logSecurityAction($user['id'], 'password_reset_requested', 'Email: ' . $email);
                    }
                }
                
                // Logger la tentative
                $stmt = $pdo->prepare("
                    INSERT INTO verification_attempts (email, ip_address, attempt_type)
                    VALUES (?, ?, 'password_reset')
                ");
                $stmt->execute([$email, $ip]);
                
                // Toujours afficher le succès pour ne pas révéler si l'email existe
                $success = true;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe oublié - Zenu</title>
    <script src="https://www.google.com/recaptcha/api.js?render=<?= RECAPTCHA_SITE_KEY ?>"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 12px;
            padding: 40px;
            max-width: 450px;
            width: 100%;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo h1 {
            font-size: 32px;
            color: #667eea;
            margin-bottom: 5px;
        }
        
        .logo p {
            color: #666;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 600;
            font-size: 14px;
        }
        
        input[type="email"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 15px;
            transition: border 0.3s;
        }
        
        input[type="email"]:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn-submit {
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
        }
        
        .btn-submit:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .error {
            background: #ffebee;
            color: #c62828;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .success {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
            line-height: 1.6;
        }
        
        .links {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
        }
        
        .links a {
            color: #667eea;
            text-decoration: none;
        }
        
        .links a:hover {
            text-decoration: underline;
        }
        
        .info-box {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #1565c0;
            line-height: 1.6;
        }
        
        .recaptcha-notice {
            font-size: 11px;
            color: #999;
            text-align: center;
            margin-top: 15px;
        }
        
        .recaptcha-notice a {
            color: #667eea;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <h1>🧘 Zenu</h1>
            <p>Réinitialisation du mot de passe</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success">
                <strong>✅ Email envoyé !</strong><br><br>
                Si un compte existe avec cette adresse email, vous recevrez un lien 
                pour réinitialiser votre mot de passe. Le lien sera valide pendant 1 heure.
                <br><br>
                N'oubliez pas de vérifier vos spams !
            </div>
            <div class="links">
                <p><a href="login.php">← Retour à la connexion</a></p>
            </div>
        <?php else: ?>
            <div class="info-box">
                🔑 <strong>Mot de passe oublié ?</strong><br>
                Entrez votre adresse email et nous vous enverrons un lien pour 
                réinitialiser votre mot de passe.
            </div>
            
            <form method="POST" action="" id="resetForm">
                <input type="hidden" name="recaptcha_token" id="recaptchaToken">
                
                <div class="form-group">
                    <label for="email">Adresse email</label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           required
                           value="<?= htmlspecialchars($email) ?>"
                           placeholder="votre@email.com">
                </div>
                
                <button type="submit" class="btn-submit" id="submitBtn">📧 Envoyer le lien</button>
                
                <div class="recaptcha-notice">
                    Ce site est protégé par reCAPTCHA et les 
                    <a href="https://policies.google.com/privacy" target="_blank">Règles de confidentialité</a> et 
                    <a href="https://policies.google.com/terms" target="_blank">Conditions d'utilisation</a> de Google s'appliquent.
                </div>
            </form>
            
            <div class="links">
                <p style="margin-top: 15px;"><a href="login.php">← Retour à la connexion</a></p>
                <p style="margin-top: 10px;">Pas encore de compte ? <a href="register.php">S'inscrire</a></p>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        const form = document.getElementById('resetForm');
        const submitBtn = document.getElementById('submitBtn');
        
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                submitBtn.disabled = true;
                submitBtn.textContent = 'Vérification...';
                
                grecaptcha.ready(function() {
                    grecaptcha.execute('<?= RECAPTCHA_SITE_KEY ?>', {action: 'password_reset'}).then(function(token) {
                        document.getElementById('recaptchaToken').value = token;
                        form.submit();
                    });
                });
            });
        }
    </script>
</body>
</html>