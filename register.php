<?php
require_once 'config.php';
require_once 'security.php';

// Si déjà connecté, rediriger
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';
$fieldErrors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifier le honeypot (anti-bot)
    if (checkHoneypot()) {
        // C'est un bot, on fait semblant que tout va bien mais on ne crée pas le compte
        $success = 'Compte créé avec succès ! Vous pouvez maintenant vous connecter.';
    } else {
        // Vérifier le rate limiting
        $rateCheck = checkRateLimit($pdo, 'register', 3, 60);
        if (!$rateCheck['allowed']) {
            $error = $rateCheck['message'];
        } else {
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            // Validation
            if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
                $error = 'Veuillez remplir tous les champs';
            } else {
                // Valider le username
                $usernameErrors = validateUsername($username);
                if (!empty($usernameErrors)) {
                    $fieldErrors['username'] = $usernameErrors;
                }
                
                // Valider l'email
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $fieldErrors['email'] = ['Email invalide'];
                }
                
                // Valider le mot de passe
                $passwordErrors = validatePassword($password);
                if (!empty($passwordErrors)) {
                    $fieldErrors['password'] = $passwordErrors;
                }
                
                if ($password !== $confirm_password) {
                    $fieldErrors['confirm_password'] = ['Les mots de passe ne correspondent pas'];
                }
                
                if (empty($fieldErrors)) {
                    // Vérifier si le username existe déjà
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                    $stmt->execute([sanitizeUsername($username)]);
                    if ($stmt->fetch()) {
                        $fieldErrors['username'] = ['Ce nom d\'utilisateur est déjà pris'];
                    }
                    
                    // Vérifier si l'email existe déjà
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                    $stmt->execute([$email]);
                    if ($stmt->fetch()) {
                        $fieldErrors['email'] = ['Cet email est déjà utilisé'];
                    }
                    
                    if (empty($fieldErrors)) {
                        // Créer l'utilisateur
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $clean_username = sanitizeUsername($username);
                        
                        $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                        
                        if ($stmt->execute([$clean_username, $email, $hashed_password])) {
                            $success = 'Compte créé avec succès ! Vous pouvez maintenant vous connecter.';
                        } else {
                            $error = 'Une erreur est survenue lors de la création du compte';
                        }
                    }
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
    <title>Inscription - Zenu</title>
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
        
        .register-container {
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
        
        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 15px;
            transition: border 0.3s;
        }
        
        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
        }
        
        input.error {
            border-color: #f44336;
        }
        
        /* Honeypot - champ invisible pour les bots */
        .hp {
            position: absolute;
            left: -9999px;
            width: 1px;
            height: 1px;
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
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
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
        
        .divider {
            text-align: center;
            margin: 20px 0;
            color: #999;
        }
        
        .password-requirements {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }
        
        .password-requirements ul {
            margin: 5px 0 0 20px;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="logo">
            <h1>🧘 Zenu</h1>
            <p>Créer votre compte</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <?php if (!$success): ?>
        <form method="POST" action="">
            <!-- Honeypot - champ invisible pour piéger les bots -->
            <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off">
            
            <div class="form-group">
                <label for="username">Nom d'utilisateur</label>
                <input type="text" 
                       id="username" 
                       name="username" 
                       required 
                       class="<?= isset($fieldErrors['username']) ? 'error' : '' ?>"
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                       placeholder="ex: jean-dupont">
                <?php if (isset($fieldErrors['username'])): ?>
                    <div class="field-errors">
                        <ul>
                            <?php foreach ($fieldErrors['username'] as $err): ?>
                                <li><?= htmlspecialchars($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                <div class="password-requirements">
                    3-30 caractères, lettres, chiffres, tirets, underscores
                </div>
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" 
                       id="email" 
                       name="email" 
                       required 
                       class="<?= isset($fieldErrors['email']) ? 'error' : '' ?>"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                <?php if (isset($fieldErrors['email'])): ?>
                    <div class="field-errors">
                        <ul>
                            <?php foreach ($fieldErrors['email'] as $err): ?>
                                <li><?= htmlspecialchars($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="password">Mot de passe</label>
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
            
            <button type="submit" class="btn-submit">S'inscrire</button>
        </form>
        
        <div class="divider">ou</div>
        <?php endif; ?>
        
        <div class="links">
            <p>Déjà un compte ? <a href="login.php">Se connecter</a></p>
            <p style="margin-top: 15px;"><a href="index.php">← Retour à l'accueil</a></p>
        </div>
    </div>
</body>
</html>