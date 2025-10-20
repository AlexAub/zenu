<?php
require_once 'config.php';
require_once 'security.php';
require_once 'email-config.php';

$success = false;
$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = 'Veuillez saisir votre adresse email';
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Adresse email invalide';
    } else {
        // Vérifier le rate limiting
        $ip = getClientIP();
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM verification_attempts 
            WHERE email = ? 
            AND ip_address = ? 
            AND attempt_type = 'email_verification'
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
            
            if (!$user) {
                // On ne révèle pas si l'email existe ou non (sécurité)
                $success = true;
            } else if ($user['email_verified']) {
                $error = 'Cet email est déjà vérifié';
            } else {
                // Générer un nouveau token
                $verification_token = bin2hex(random_bytes(32));
                $token_expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
                
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET verification_token = ?, 
                        verification_token_expires = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$verification_token, $token_expires, $user['id']]);
                
                // Envoyer l'email
                $emailSent = sendVerificationEmail($email, $user['username'], $verification_token);
                
                // Logger la tentative
                $stmt = $pdo->prepare("
                    INSERT INTO verification_attempts (email, ip_address, attempt_type)
                    VALUES (?, ?, 'email_verification')
                ");
                $stmt->execute([$email, $ip]);
                
                if ($emailSent) {
                    $success = true;
                    logSecurityAction($user['id'], 'verification_email_resent', 'Email: ' . $email);
                } else {
                    $error = 'Erreur lors de l\'envoi de l\'email. Veuillez réessayer plus tard.';
                }
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
    <title>Renvoyer l'email de vérification - Zenu</title>
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
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <h1>🧘 Zenu</h1>
            <p>Renvoyer l'email de vérification</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success">
                <strong>✅ Email envoyé !</strong><br><br>
                Si un compte existe avec cette adresse email et n'est pas encore vérifié, 
                un nouveau lien de vérification a été envoyé. Veuillez vérifier votre boîte mail 
                (pensez à regarder dans les spams).
            </div>
            <div class="links">
                <p><a href="login.php">← Retour à la connexion</a></p>
            </div>
        <?php else: ?>
            <div class="info-box">
                💡 <strong>Besoin d'un nouveau lien ?</strong><br>
                Si vous n'avez pas reçu l'email de vérification ou si le lien a expiré, 
                entrez votre adresse email ci-dessous pour recevoir un nouveau lien.
            </div>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">Adresse email</label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           required
                           value="<?= htmlspecialchars($email) ?>"
                           placeholder="votre@email.com">
                </div>
                
                <button type="submit" class="btn-submit">📧 Renvoyer l'email</button>
            </form>
            
            <div class="links">
                <p style="margin-top: 15px;"><a href="login.php">← Retour à la connexion</a></p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>