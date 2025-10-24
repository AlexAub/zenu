<?php
require_once 'config.php';

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zenu - Des outils simples et zen</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .site-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .site-logo {
            font-size: 24px;
            font-weight: 700;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .site-logo:hover {
            opacity: 0.9;
        }
        
        .page-title {
            font-size: 20px;
            font-weight: 600;
            color: rgba(255,255,255,0.9);
        }
        
        .header-nav {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .nav-link {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 8px;
            transition: all 0.3s;
            font-size: 14px;
            font-weight: 500;
            background: rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .nav-link:hover {
            background: rgba(255,255,255,0.2);
            transform: translateY(-1px);
        }
        
        .nav-link.active {
            background: rgba(255,255,255,0.25);
        }
        
        .user-info {
            padding: 8px 16px;
            background: rgba(255,255,255,0.15);
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
        }
        
        .btn-login {
            background: white !important;
            color: #667eea !important;
            font-weight: 600;
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.3s;
            display: inline-block;
        }
        
        .btn-login:hover {
            background: rgba(255,255,255,0.9) !important;
            color: #667eea !important;
            transform: translateY(-1px);
        }
        
        .btn-logout {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-logout:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-1px);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 60px 40px;
        }
        
        .hero {
            text-align: center;
            color: white;
            margin-bottom: 60px;
        }
        
        .hero h1 {
            font-size: 48px;
            font-weight: 700;
            margin-bottom: 20px;
        }
        
        .hero p {
            font-size: 20px;
            opacity: 0.9;
        }
        
        .tools-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }
        
        .tool-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            text-decoration: none;
            color: #333;
            transition: all 0.3s;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            position: relative;
            overflow: hidden;
        }
        
        .tool-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }
        
        .tool-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #e0e7ff;
            color: #667eea;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .tool-badge.premium {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .tool-icon {
            font-size: 48px;
            margin-bottom: 20px;
        }
        
        .tool-card h2 {
            font-size: 24px;
            margin-bottom: 10px;
            color: #333;
        }
        
        .tool-card p {
            color: #666;
            line-height: 1.6;
        }
        
        .locked {
            opacity: 0.7;
            cursor: not-allowed;
        }
        
        .locked:hover {
            transform: none;
        }
        
        .lock-icon {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 24px;
        }
        
        .cta-section {
            text-align: center;
            margin-top: 60px;
            padding: 40px;
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            color: white;
        }
        
        .cta-section h2 {
            font-size: 32px;
            margin-bottom: 20px;
        }
        
        .cta-section p {
            font-size: 18px;
            margin-bottom: 30px;
            opacity: 0.9;
        }
        
        .cta-button {
            display: inline-block;
            background: white;
            color: #667eea;
            padding: 15px 40px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 18px;
            transition: all 0.3s;
        }
        
        .cta-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(255,255,255,0.3);
        }
        
        footer {
            margin-top: 80px;
            padding: 40px;
            background: rgba(0,0,0,0.2);
            color: white;
            text-align: center;
        }
        
        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .footer-links {
            display: flex;
            gap: 20px;
        }
        
        .footer-links a {
            color: white;
            text-decoration: none;
            opacity: 0.8;
            transition: opacity 0.3s;
        }
        
        .footer-links a:hover {
            opacity: 1;
        }
        
        .footer-left,
        .footer-right {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        
        .footer-note {
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
    <header class="site-header">
        <div class="header-container">
            <div class="header-content">
                <div class="header-left">
                    <a href="index.php" class="site-logo">🧘 Zenu</a>
                    <span class="page-title">Accueil</span>
                </div>
                
                <nav class="header-nav">
                    <?php if ($user): ?>                
                        <a href="convertisseur.php" class="nav-link">
                            🖼️ Convertisseur
                        </a>
						<a href="dashboard.php" class="nav-link">
                            📊 Mes images
                        </a>
						<span class="user-info">👤 <?= htmlspecialchars($user['username']) ?></span>
                        <form action="logout.php" method="POST" style="display: inline;">
                            <button type="submit" class="btn-logout">Déconnexion</button>
                        </form>
                    <?php else: ?>
                        <a href="convertisseur.php" class="nav-link">
                            🖼️ Convertisseur
                        </a>
                        <a href="register.php" class="nav-link">
                            ✨ S'inscrire
                        </a>
                        <a href="login.php" class="btn-login">Se connecter</a>
                    <?php endif; ?>
                </nav>
            </div>
        </div>
    </header>

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

            <!-- Message pour les utilisateurs non connectés -->
            <?php if (!$user): ?>
                <div class="tool-card locked">
                    <span class="lock-icon">🔒</span>
                    <div class="tool-icon">🖼️</div>
                    <h2>Mes Images</h2>
                    <p>Gérez toutes vos images sauvegardées. Voir, télécharger, copier les URLs et supprimer.</p>
                    <p style="margin-top: 15px; font-weight: 600; color: #667eea;">Connectez-vous pour accéder</p>
                </div>
            <?php else: ?>
                <a href="dashboard.php" class="tool-card">
                    <span class="tool-badge premium">Privé</span>
                    <div class="tool-icon">🖼️</div>
                    <h2>Mes Images</h2>
                    <p>Gérez toutes vos images sauvegardées. Voir, télécharger, copier les URLs et supprimer.</p>
                </a>
            <?php endif; ?>
        </div>

        <?php if (!$user): ?>
        <div class="cta-section">
            <h2>Prêt à commencer ?</h2>
            <p>Créez votre compte gratuitement et profitez de tous nos outils</p>
            <a href="register.php" class="cta-button">S'inscrire maintenant</a>
        </div>
        <?php endif; ?>
    </div>

    <footer>
        <div class="footer-content">
            <div class="footer-left">
                <span>© <?= date('Y') ?> Zenu</span>
                <span>•</span>
                <span>Tous droits réservés</span>
            </div>
            <div class="footer-right">
                <div class="footer-links">
                    <a href="mentions-legales.php">Mentions légales</a>
                    <a href="cgu.php">CGU</a>
                    <a href="privacy.php">Confidentialité</a>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>