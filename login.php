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
$warning = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifier le honeypot
    if (checkHoneypot()) {
        sleep(2);
        $error = 'Email ou mot de passe incorrect';
    } else {
        // Vérifier le rate limiting
        $rateCheck = checkRateLimit($pdo, 'login', 5, 15);
        if (!$rateCheck['allowed']) {
            $error = $rateCheck['message'];
        } else {
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            
            if (empty($email) || empty($password)) {
                $error = 'Veuillez remplir tous les champs';
            } else {
                $stmt = $pdo->prepare("SELECT id, password, email_verified, username FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                
                if ($user && password_verify($password, $user['password'])) {
                    // Vérifier si l'email est vérifié
                    if (!$user['email_verified']) {
                        $warning = 'Votre email n\'est pas encore vérifié. Veuillez vérifier votre boîte mail ou <a href="resend-verification.php" style="color: #e65100; text-decoration: underline;">renvoyer l\'email de vérification</a>.';
                        logSecurityAction($user['id'], 'login_attempt_unverified', 'Email: ' . $email);
                    } else {
                        // Connexion réussie
                        $_SESSION['user_id'] = $user['id'];
                        
                        // Réinitialiser le rate limit pour cette IP
                        $ip = getClientIP();
                        $stmt = $pdo->prepare("DELETE FROM rate_limits WHERE ip_address = ? AND action_type = 'login'");
                        $stmt->execute([$ip]);
                        
                        // Logger la connexion
                        logSecurityAction($user['id'], 'login_success', 'Email: ' . $email);
                        
                        header('Location: dashboard.php');
                        exit;
                    }
                } else {
                    // Connexion échouée
                    if ($user) {
                        logFailedLogin($pdo, $email);
                        logSecurityAction($user['id'], 'login_failed', 'Wrong password');
                    }
                    $error = 'Email ou mot de passe incorrect';
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
    <title>Connexion - Zenu</title>
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
        
        .login-container {
            background: white;
            border-radius: 12px;
            padding: 40px;
            max-width: 400px;
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
        
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 15px;
            transition: border 0.3s;
        }
        
        input[type="email"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
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
        
        .error {
            background: #ffebee;
            color: #c62828;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .warning {
            background: #fff3e0;
            color: #e65100;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
            line-height: 1.5;
        }
        
        .warning a {
            color: #e65100;
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
        
        .forgot-password {
            text-align: right;
            margin-top: 8px;
        }
        
        .forgot-password a {
            color: #667eea;
            font-size: 13px;
            text-decoration: none;
        }
        
        .forgot-password a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1>🧘 Zenu</h1>
            <p>Connexion à votre espace</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($warning): ?>
            <div class="warning"><?= $warning ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <!-- Honeypot -->
            <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off">
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required 
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required>
                <div class="forgot-password">
                    <a href="forgot-password.php">Mot de passe oublié ?</a>
                </div>
            </div>
            
            <button type="submit" class="btn-submit">Se connecter</button>
        </form>
        
        <div class="divider">ou</div>
        
        <div class="links">
            <p>Pas encore de compte ? <a href="register.php">S'inscrire</a></p>
            <p style="margin-top: 15px;"><a href="index.php">← Retour à l'accueil</a></p>
        </div>
    </div>
</body>
</html>