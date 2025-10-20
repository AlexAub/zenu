<?php
require_once 'config.php';
require_once 'security.php';
require_once 'email-config.php';

// Si déjà connecté, rediriger
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';
$fieldErrors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifier le honeypot
    if (checkHoneypot()) {
        sleep(2);
        $error = 'Email ou mot de passe incorrect';
    } else {
        // Vérifier le reCAPTCHA
        $recaptchaToken = $_POST['recaptcha_token'] ?? '';
        $recaptchaResult = verifyRecaptcha($recaptchaToken);
        
        if (!$recaptchaResult['success'] || $recaptchaResult['score'] < RECAPTCHA_MIN_SCORE) {
            $error = 'Vérification de sécurité échouée. Veuillez réessayer.';
            logSecurityAction(null, 'register_recaptcha_failed', 'Score: ' . $recaptchaResult['score']);
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
                            
                            // Générer un token de vérification
                            $verification_token = bin2hex(random_bytes(32));
                            $token_expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
                            
                            $stmt = $pdo->prepare("
                                INSERT INTO users (username, email, password, verification_token, verification_token_expires) 
                                VALUES (?, ?, ?, ?, ?)
                            ");
                            
                            if ($stmt->execute([$clean_username, $email, $hashed_password, $verification_token, $token_expires])) {
                                $userId = $pdo->lastInsertId();
                                
                                // Envoyer l'email de vérification
                                $emailSent = sendVerificationEmail($email, $clean_username, $verification_token);
                                
                                if ($emailSent) {
                                    $success = 'Compte créé avec succès ! Un email de vérification a été envoyé à ' . htmlspecialchars($email) . '. Veuillez vérifier votre boîte mail.';
                                    logSecurityAction($userId, 'register_success', 'Email: ' . $email);
                                } else {
                                    $success = 'Compte créé avec succès ! Cependant, l\'email de vérification n\'a pas pu être envoyé. Contactez le support.';
                                    logSecurityAction($userId, 'register_email_failed', 'Email: ' . $email);
                                }
                            } else {
                                $error = 'Une erreur est survenue lors de la création du compte';
                            }
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
    <div class="register-container">
        <div class="logo">
            <h1>🧘 Zenu</h1>
            <p>Créer votre compte</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?= $success ?></div>
        <?php endif; ?>
        
        <?php if (!$success): ?>
        <form method="POST" action="" id="registerForm">
            <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off">
            <input type="hidden" name="recaptcha_token" id="recaptchaToken">
            
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
            
            <button type="submit" class="btn-submit" id="submitBtn">S'inscrire</button>
            
            <div class="recaptcha-notice">
                Ce site est protégé par reCAPTCHA et les 
                <a href="https://policies.google.com/privacy" target="_blank">Règles de confidentialité</a> et 
                <a href="https://policies.google.com/terms" target="_blank">Conditions d'utilisation</a> de Google s'appliquent.
            </div>
        </form>
        
        <div class="divider">ou</div>
        <?php endif; ?>
        
        <div class="links">
            <p>Déjà un compte ? <a href="login.php">Se connecter</a></p>
            <p style="margin-top: 15px;"><a href="index.php">← Retour à l'accueil</a></p>
        </div>
    </div>
    
    <script>
        // Configuration reCAPTCHA v3
        const form = document.getElementById('registerForm');
        const submitBtn = document.getElementById('submitBtn');
        
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            submitBtn.disabled = true;
            submitBtn.textContent = 'Vérification...';
            
            grecaptcha.ready(function() {
                grecaptcha.execute('<?= RECAPTCHA_SITE_KEY ?>', {action: 'register'}).then(function(token) {
                    document.getElementById('recaptchaToken').value = token;
                    form.submit();
                });
            });
        });
    </script>
</body>
</html>