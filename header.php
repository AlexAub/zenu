<?php
// Ce fichier doit être inclus après config.php
if (!isset($pdo)) {
    die('Erreur: config.php doit être chargé avant header.php');
}

$currentUser = getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

// Normaliser les variantes du convertisseur
if (in_array($currentPage, ['convertisseur-prive', 'convertisseur-prive-ameliore'])) {
    $currentPage = 'convertisseur';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
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
        }
        
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .header-nav {
                width: 100%;
                justify-content: space-between;
            }
            
            .nav-link {
                flex: 1;
                justify-content: center;
                font-size: 12px;
                padding: 8px 10px;
            }
        }
    </style>
</head>
<body>
    <header class="site-header">
        <div class="header-container">
            <div class="header-content">
                <div class="header-left">
                    <a href="index.php" class="site-logo">
                        🧘 Zenu
                    </a>
                    <?php if (isset($pageTitle)): ?>
                        <span class="page-title"><?= htmlspecialchars($pageTitle) ?></span>
                    <?php endif; ?>
                </div>
                
                <nav class="header-nav">
                    <a href="index.php" class="nav-link">
                        🏠 Accueil
                    </a>
                    <a href="dashboard.php" class="nav-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
                        📊 Mes images
                    </a>
                    <a href="convertisseur-prive.php" class="nav-link <?= $currentPage === 'convertisseur' ? 'active' : '' ?>">
                        🎨 Convertir
                    </a>
                    <a href="upload.php" class="nav-link <?= $currentPage === 'upload' ? 'active' : '' ?>">
                        📤 Upload
                    </a>
                    <a href="editeur.php" class="nav-link <?= $currentPage === 'editeur' ? 'active' : '' ?>">
                        🎨 Éditeur
                    </a>
                    <a href="ai-tools.php" class="nav-link <?= $currentPage === 'ai-tools' ? 'active' : '' ?>">
                        🤖 Outils IA
                    </a>
                    <a href="trash.php" class="nav-link <?= $currentPage === 'trash' ? 'active' : '' ?>">
                        🗑️ Corbeille
                    </a>

                    <?php if ($currentUser): ?>
                        <span class="user-info">
                            👤 <?= htmlspecialchars($currentUser['username']) ?>
                        </span>
                        <a href="logout.php" class="nav-link" style="background: rgba(255,255,255,0.2);">
                            🚪 Déconnexion
                        </a>
                    <?php endif; ?>
                </nav>
            </div>
        </div>
    </header>