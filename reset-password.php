<?php
require_once 'config.php';
require_once 'security.php';
require_once 'email-config.php';

$success = false;
$error = '';
$tokenValid = false;
$userId = null;
$fieldErrors = [];

// Récupérer et valider le token
$token = $_GET['token'] ?? '';

if (empty($token)) {
    $error = 'Token manquant';
} else {
    // Vérifier le token
    $stmt = $pdo->prepare("
        SELECT pr.*, u.id as user_id, u.username, u.email
        FROM password_resets pr
        JOIN users u ON pr.user_id = u.id
        WHERE pr.token = ?
        AND pr.used = 0
        AND pr.expires_at > NOW()
        ORDER BY pr.created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$token]);
    $resetData = $stmt->fetch();
    
    if (!$resetData) {
        $error = 'Ce lien de réinitialisation est invalide ou a expiré';
    } else {
        $tokenValid = true;
        $userId = $resetData['user_id'];
    }
}

// Traiter le formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenValid) {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($password) || empty($confirm_password)) {
        $error = 'Veuillez remplir tous les champs';
    } else {
        // Valider le mot de passe
        $passwordErrors = validatePassword($password);
        if (!empty($passwordErrors)) {
            $fieldErrors['password'] = $passwordErrors;
        }
        
        if ($password !== $confirm_password) {
            $fieldErrors['confirm_password'] = ['Les mots de passe ne correspondent pas'];
        }
        
        if (empty($fieldErrors)) {
            // Mettre à jour le mot de passe
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            
            if ($stmt->execute([$hashed_password, $userId])) {
                // Marquer le token comme utilisé
                $stmt = $pdo->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
                $stmt->execute([$token]);
                
                // Logger l'action
                logSecurityAction($userId, 'password_reset_completed', 'Token utilisé');
                
                $success = true;
            } else {
                $error = 'Erreur lors de la mise à jour du mot de passe';
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
    <title>Nouveau mot de passe - Zenu</title>
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
        
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 15px;
            transition: border 0.3s;
        }
        
        input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
        }
        
        input.error {
            border-color: #f44336;
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
        
        .field-errors {
            background: #ffebee;
            color: #c62828;
            padding: 8px;
            border-radius: 4px;
            margin-top: 5px;
            font-size: 12px;
        }
        
        .field-errors ul {
            margin: 5px 0 0 20px;
        }
        
        .success {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
            line-height: 1.6;
            text-align: center;
        }
        
        .success-icon {
            font-size: 48px;
            margin-bottom: 15px;
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
        
        .password-requirements {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }
        
        .password-requirements ul {
            margin: 5px 0 0 20px;
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
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <h1>🧘 Zenu</h1>
            <p>Nouveau mot de passe</p>
        </div>
        
        <?php if ($error && !$tokenValid): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
            <div class="links">
                <p><a href="forgot-password.php">Demander un nouveau lien</a></p>
                <p style="margin-top: 10px;"><a href="login.php">← Retour à la connexion</a></p>
            </div>
        <?php elseif ($success): ?>
            <div class="success-icon">✅</div>
            <div class="success">
                <strong>Mot de passe modifié avec succès !</strong><br><br>
                Votre mot de passe a été réinitialisé. Vous pouvez maintenant vous connecter 
                avec votre nouveau mot de passe.
            </div>
            <a href="login.php" class="btn-primary" style="display: block; text-align: center;">Se connecter</a>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="password">Nouveau mot de passe</label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           required
                           class="<?= isset($fieldErrors['password']) ? 'error' : '' ?>">
                    <?php if (isset($fieldErrors['password'])): ?>
                        <div class="field-errors">
                            <ul>
                                <?php foreach ($fieldErrors['password'] as $err): ?>
                                    <li><?= htmlspecialchars($err) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    <div class="password-requirements">
                        <ul>
                            <li>Minimum 8 caractères</li>
                            <li>Au moins 1 majuscule</li>
                            <li>Au moins 1 chiffre</li>
                        </ul>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirmer le mot de passe</label>
                    <input type="password" 
                           id="confirm_password" 
                           name="confirm_password" 
                           required
                           class="<?= isset($fieldErrors['confirm_password']) ? 'error' : '' ?>">
                    <?php if (isset($fieldErrors['confirm_password'])): ?>
                        <div class="field-errors">
                            <ul>
                                <?php foreach ($fieldErrors['confirm_password'] as $err): ?>
                                    <li><?= htmlspecialchars($err) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
                
                <button type="submit" class="btn-submit">🔑 Modifier le mot de passe</button>
            </form>
            
            <div class="links">
                <p style="margin-top: 15px;"><a href="login.php">← Retour à la connexion</a></p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>