<?php
require_once 'config.php';
require_once 'security.php';
require_once 'email-config.php';

$success = false;
$error = '';
$message = '';

// Récupérer le token
$token = $_GET['token'] ?? '';

if (empty($token)) {
    $error = 'Token de vérification manquant';
} else {
    // Vérifier le token dans la base de données
    $stmt = $pdo->prepare("
        SELECT id, username, email, email_verified, verification_token_expires 
        FROM users 
        WHERE verification_token = ? 
        AND email_verified = 0
    ");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if (!$user) {
        $error = 'Token invalide ou compte déjà vérifié';
    } else {
        // Vérifier si le token a expiré
        if (strtotime($user['verification_token_expires']) < time()) {
            $error = 'Ce lien de vérification a expiré. Veuillez demander un nouveau lien.';
            $userId = $user['id'];
        } else {
            // Vérifier l'email
            $stmt = $pdo->prepare("
                UPDATE users 
                SET email_verified = 1, 
                    verification_token = NULL, 
                    verification_token_expires = NULL 
                WHERE id = ?
            ");
            
            if ($stmt->execute([$user['id']])) {
                $success = true;
                $message = 'Votre email a été vérifié avec succès ! Vous pouvez maintenant vous connecter.';
                
                // Logger l'action
                logSecurityAction($user['id'], 'email_verified', 'Email: ' . $user['email']);
            } else {
                $error = 'Erreur lors de la vérification. Veuillez réessayer.';
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
    <title>Vérification d'email - Zenu</title>
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
        
        .verify-container {
            background: white;
            border-radius: 12px;
            padding: 40px;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            text-align: center;
        }
        
        .icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        
        h1 {
            font-size: 28px;
            color: #667eea;
            margin-bottom: 15px;
        }
        
        .message {
            font-size: 16px;
            color: #555;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        
        .success-box {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .error-box {
            background: #ffebee;
            color: #c62828;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .btn-primary {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            transition: transform 0.2s;
            margin: 5px;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            display: inline-block;
            background: #e0e0e0;
            color: #555;
            padding: 12px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            transition: background 0.2s;
            margin: 5px;
        }
        
        .btn-secondary:hover {
            background: #d0d0d0;
        }
        
        .resend-section {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }
        
        .resend-section p {
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="verify-container">
        <?php if ($success): ?>
            <div class="icon">✅</div>
            <h1>Email vérifié !</h1>
            <div class="success-box"><?= htmlspecialchars($message) ?></div>
            <p class="message">Votre compte est maintenant actif. Vous pouvez accéder à tous nos services.</p>
            <a href="login.php" class="btn-primary">Se connecter</a>
            <a href="index.php" class="btn-secondary">Accueil</a>
        <?php else: ?>
            <div class="icon">❌</div>
            <h1>Vérification échouée</h1>
            <div class="error-box"><?= htmlspecialchars($error) ?></div>
            
            <?php if (isset($userId)): ?>
                <div class="resend-section">
                    <p>Votre lien a expiré ? Demandez un nouveau lien de vérification :</p>
                    <a href="resend-verification.php?user_id=<?= $userId ?>" class="btn-primary">Renvoyer l'email</a>
                </div>
            <?php endif; ?>
            
            <a href="index.php" class="btn-secondary">Retour à l'accueil</a>
        <?php endif; ?>
    </div>
</body>
</html>