<?php
require_once 'config.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user = getCurrentUser();

// Récupérer les images de l'utilisateur (pour plus tard)
$stmt = $pdo->prepare("SELECT * FROM images WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
$stmt->execute([$_SESSION['user_id']]);
$images = $stmt->fetchAll();

// Calculer l'espace utilisé
$stmt = $pdo->prepare("SELECT SUM(size) as total_size, COUNT(*) as total_images FROM images WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$stats = $stmt->fetch();
$total_size = $stats['total_size'] ?? 0;
$total_images = $stats['total_images'] ?? 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon espace - Zenu</title>
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
        }
        
        /* Navigation */
        nav {
            background: rgba(255, 255, 255, 0.95);
            padding: 15px 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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
        
        /* Container */
        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        /* Header */
        .dashboard-header {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .dashboard-header h1 {
            font-size: 32px;
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .user-email {
            color: #666;
            font-size: 16px;
        }
        
        /* Stats */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-icon {
            font-size: 36px;
            margin-bottom: 10px;
        }
        
        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        
        /* Content sections */
        .content-section {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .content-section h2 {
            font-size: 24px;
            color: #667eea;
            margin-bottom: 20px;
        }
        
        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .empty-state h3 {
            font-size: 20px;
            margin-bottom: 10px;
            color: #666;
        }
        
        .empty-state p {
            font-size: 16px;
            margin-bottom: 20px;
        }
        
        .btn-primary {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: transform 0.2s;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
        }
        
        /* Images grid */
        .images-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .image-card {
            background: #f8f9ff;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
        }
        
        .image-card img {
            max-width: 100%;
            border-radius: 6px;
            margin-bottom: 10px;
        }
        
        .image-info {
            font-size: 13px;
            color: #666;
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <nav>
        <a href="index.php" class="logo">🧘 Zenu</a>
        <div class="nav-links">
            <a href="index.php">Accueil</a>
            <a href="dashboard.php">Mon espace</a>
            <form action="logout.php" method="POST" style="display: inline;">
                <button type="submit" class="btn-logout">Déconnexion</button>
            </form>
        </div>
    </nav>

    <div class="container">
        <div class="dashboard-header">
            <h1>Bienvenue, <?= htmlspecialchars(explode('@', $user['email'])[0]) ?> 👋</h1>
            <p class="user-email"><?= htmlspecialchars($user['email']) ?></p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📁</div>
                <div class="stat-value"><?= $total_images ?></div>
                <div class="stat-label">Images sauvegardées</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">💾</div>
                <div class="stat-value"><?= number_format($total_size / 1024, 1) ?> MB</div>
                <div class="stat-label">Espace utilisé</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">⏱️</div>
                <div class="stat-value"><?= date('d/m/Y', strtotime($user['created_at'])) ?></div>
                <div class="stat-label">Membre depuis</div>
            </div>
        </div>

        <div class="content-section">
            <h2>Mes images</h2>
            
            <?php if (empty($images)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">🖼️</div>
                    <h3>Aucune image pour le moment</h3>
                    <p>Le gestionnaire d'images sera bientôt disponible.<br>
                    En attendant, vous pouvez utiliser le convertisseur d'images.</p>
                    <a href="convertisseur.html" class="btn-primary">Convertir des images</a>
                </div>
            <?php else: ?>
                <div class="images-grid">
                    <?php foreach ($images as $image): ?>
                        <div class="image-card">
                            <img src="<?= htmlspecialchars($image['path']) ?>" alt="<?= htmlspecialchars($image['original_filename']) ?>">
                            <div class="image-info">
                                <strong><?= htmlspecialchars($image['original_filename']) ?></strong><br>
                                <?= number_format($image['size'] / 1024, 1) ?> KB<br>
                                <?= date('d/m/Y', strtotime($image['created_at'])) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>