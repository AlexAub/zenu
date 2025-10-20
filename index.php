<?php
require_once 'config.php';
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zenu - Outils simples et zen</title>
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
            color: #333;
            display: flex;
            flex-direction: column;
        }
        
        /* Navigation */
        nav {
            background: rgba(255, 255, 255, 0.98);
            padding: 15px 30px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            backdrop-filter: blur(10px);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 24px;
            font-weight: 700;
            color: #667eea;
            text-decoration: none;
        }
        
        .nav-links {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        
        .nav-links a {
            color: #555;
            text-decoration: none;
            font-size: 15px;
            transition: color 0.3s;
        }
        
        .nav-links a:hover {
            color: #667eea;
        }
        
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 8px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: transform 0.2s;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            color: white;
        }
        
        .btn-logout {
            background: #e0e0e0;
            color: #555;
            padding: 8px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .btn-logout:hover {
            background: #d0d0d0;
        }
        
        /* Container principal */
        .container {
            max-width: 1200px;
            margin: 60px auto;
            padding: 0 20px;
            flex: 1;
        }
        
        /* Hero section */
        .hero {
            text-align: center;
            margin-bottom: 60px;
            color: white;
        }
        
        .hero h1 {
            font-size: 48px;
            margin-bottom: 15px;
            font-weight: 700;
        }
        
        .hero p {
            font-size: 20px;
            opacity: 0.95;
        }
        
        /* Grille d'outils */
        .tools-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        
        .tool-card {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s, box-shadow 0.3s;
            text-decoration: none;
            color: #333;
            position: relative;
        }
        
        .tool-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }
        
        .tool-card.locked {
            opacity: 0.7;
        }
        
        .tool-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        
        .tool-card h2 {
            font-size: 22px;
            margin-bottom: 10px;
            color: #667eea;
        }
        
        .tool-card p {
            color: #666;
            line-height: 1.6;
            font-size: 15px;
        }
        
        .tool-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #e8f5e9;
            color: #2e7d32;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .tool-badge.premium {
            background: #fff3e0;
            color: #e65100;
        }
        
        .lock-icon {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 24px;
        }
        
        /* Footer */
        .site-footer {
            background: rgba(255, 255, 255, 0.98);
            padding: 20px;
            margin-top: 60px;
            box-shadow: 0 -1px 3px rgba(0,0,0,0.05);
            backdrop-filter: blur(10px);
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .footer-left {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #666;
            font-size: 14px;
        }

        .footer-brand {
            font-weight: 600;
            color: #667eea;
        }

        .footer-tagline {
            color: #999;
        }

        .footer-separator {
            color: #ddd;
        }

        .footer-right {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
        }

        .footer-right a {
            color: #666;
            text-decoration: none;
            transition: color 0.3s;
        }

        .footer-right a:hover {
            color: #667eea;
        }

        .footer-copyright {
            text-align: center;
            color: #aaa;
            font-size: 12px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #f0f0f0;
        }
        
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 36px;
            }
            
            .hero p {
                font-size: 18px;
            }
            
            .tools-grid {
                grid-template-columns: 1fr;
            }
            
            .footer-content {
                flex-direction: column;
                text-align: center;
                gap: 12px;
            }
            
            .footer-left,
            .footer-right {
                flex-wrap: wrap;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <nav>
        <a href="index.php" class="logo">🧘 Zenu</a>
        <div class="nav-links">
            <?php if ($user): ?>
                <span>Bonjour, <?= htmlspecialchars($user['username']) ?></span>
                <a href="dashboard.php">Mon espace</a>
                <form action="logout.php" method="POST" style="display: inline;">
                    <button type="submit" class="btn-logout">Déconnexion</button>
                </form>
            <?php else: ?>
                <a href="login.php" class="btn-login">Se connecter</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="container">
        <div class="hero">
            <h1>Des outils simples et zen</h1>
            <p>Tout ce dont vous avez besoin, sans complexité</p>
        </div>

        <div class="tools-grid">
            <!-- Outil 1 : Convertisseur d'images (public) -->
            <a href="convertisseur.php" class="tool-card">
                <span class="tool-badge">Public</span>
                <div class="tool-icon">🖼️</div>
                <h2>Convertisseur d'Images</h2>
                <p>Redimensionnez et convertissez vos images en JPG facilement. Qualité ajustable et aperçu en temps réel.</p>
            </a>

            <!-- Outil 2 : Convertisseur privé avec sauvegarde -->
            <?php if ($user): ?>
                <a href="convertisseur-prive.php" class="tool-card">
                    <span class="tool-badge premium">Privé</span>
                    <div class="tool-icon">💾</div>
                    <h2>Convertisseur Privé</h2>
                    <p>Convertissez et sauvegardez vos images dans votre espace personnel. Accédez-y depuis n'importe où.</p>
                </a>
            <?php else: ?>
                <div class="tool-card locked">
                    <span class="lock-icon">🔒</span>
                    <div class="tool-icon">💾</div>
                    <h2>Convertisseur Privé</h2>
                    <p>Convertissez et sauvegardez vos images dans votre espace personnel. Accédez-y depuis n'importe où.</p>
                    <p style="margin-top: 15px; color: #667eea; font-weight: 600;">
                        <a href="login.php" style="color: #667eea;">Connectez-vous</a> pour accéder
                    </p>
                </div>
            <?php endif; ?>
            
            <!-- Outil 3 : Gestionnaire d'images -->
            <?php if ($user): ?>
                <a href="dashboard.php" class="tool-card">
                    <span class="tool-badge premium">Privé</span>
                    <div class="tool-icon">📁</div>
                    <h2>Mes Images</h2>
                    <p>Gérez toutes vos images sauvegardées. Voir, télécharger, copier les URLs et supprimer.</p>
                </a>
            <?php else: ?>
                <div class="tool-card locked">
                    <span class="lock-icon">🔒</span>
                    <div class="tool-icon">📁</div>
                    <h2>Mes Images</h2>
                    <p>Gérez toutes vos images sauvegardées. Voir, télécharger, copier les URLs et supprimer.</p>
                    <p style="margin-top: 15px; color: #667eea; font-weight: 600;">
                        <a href="login.php" style="color: #667eea;">Connectez-vous</a> pour accéder
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <footer class="site-footer">
        <div class="footer-content">
            <div class="footer-left">
                <span class="footer-brand">🧘 Zenu</span>
                <span class="footer-separator">·</span>
                <span class="footer-tagline">Outils simples et zen</span>
            </div>
            
            <div class="footer-right">
                <a href="mentions-legales.php">Mentions légales</a>
                <span class="footer-separator">·</span>
                <a href="cgu.php">CGU</a>
                <span class="footer-separator">·</span>
                <a href="privacy.php">Confidentialité</a>
                <span class="footer-separator">·</span>
                <a href="mailto:contact@zenu.fr">Contact</a>
            </div>
        </div>
        
        <div class="footer-copyright">
            &copy; <?= date('Y') ?> Zenu
        </div>
    </footer>
</body>
</html>