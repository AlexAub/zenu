<?php
require_once 'config.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user = getCurrentUser();

// Récupérer les images de l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM images WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$images = $stmt->fetchAll();

// Calculer l'espace utilisé
$stmt = $pdo->prepare("SELECT SUM(size) as total_size, COUNT(*) as total_images FROM images WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$stats = $stmt->fetch();
$total_size = $stats['total_size'] ?? 0;
$total_images = $stats['total_images'] ?? 0;
$used_space_mb = round($total_size / (1024 * 1024), 2);
$used_space_percent = ($total_size / (500 * 1024 * 1024)) * 100;
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
        
        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
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
        
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            margin-top: 10px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: width 0.3s;
        }
        
        .content-section {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .section-header h2 {
            font-size: 24px;
            color: #667eea;
        }
        
        .btn-primary {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 10px 25px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: transform 0.2s;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
        }
        
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
        
        .images-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .image-card {
            background: #f8f9ff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        
        .image-card:hover {
            transform: translateY(-5px);
        }
        
        .image-preview {
            width: 100%;
            height: 200px;
            background: #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .image-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .image-info {
            padding: 15px;
        }
        
        .image-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            font-size: 14px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .image-meta {
            font-size: 12px;
            color: #999;
            margin-bottom: 10px;
        }
        
        .image-url {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 6px;
            font-size: 11px;
            word-break: break-all;
            margin-bottom: 10px;
            max-height: 40px;
            overflow: hidden;
        }
        
        .image-actions {
            display: flex;
            gap: 8px;
        }
        
        .btn-action {
            flex: 1;
            padding: 6px;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            transition: transform 0.2s;
            font-weight: 600;
        }
        
        .btn-action:hover {
            transform: translateY(-2px);
        }
        
        .btn-copy {
            background: #2196f3;
            color: white;
        }
        
        .btn-download {
            background: #4caf50;
            color: white;
        }
        
        .btn-delete {
            background: #f44336;
            color: white;
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .images-grid {
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
            <h1>Bienvenue, <?= htmlspecialchars($user['username']) ?> 👋</h1>
            <p class="user-email"><?= htmlspecialchars($user['email']) ?></p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📁</div>
                <div class="stat-value"><?= $total_images ?> / 500</div>
                <div class="stat-label">Images</div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?= ($total_images / 500) * 100 ?>%"></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">💾</div>
                <div class="stat-value"><?= $used_space_mb ?> MB / 500 MB</div>
                <div class="stat-label">Espace utilisé</div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?= min($used_space_percent, 100) ?>%"></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">⏱️</div>
                <div class="stat-value"><?= date('d/m/Y', strtotime($user['created_at'])) ?></div>
                <div class="stat-label">Membre depuis</div>
            </div>
        </div>

        <div class="content-section">
            <div class="section-header">
                <h2>Mes images</h2>
                <a href="convertisseur-prive.php" class="btn-primary">➕ Nouvelle image</a>
            </div>
            
            <?php if (empty($images)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">🖼️</div>
                    <h3>Aucune image pour le moment</h3>
                    <p>Utilisez le convertisseur privé pour sauvegarder vos premières images.</p>
                    <a href="convertisseur-prive.php" class="btn-primary">Convertir et sauvegarder</a>
                </div>
            <?php else: ?>
                <div class="images-grid">
                    <?php foreach ($images as $image): ?>
                        <div class="image-card" data-image-id="<?= $image['id'] ?>">
                            <div class="image-preview">
                                <img src="/i/<?= htmlspecialchars($user['username']) ?>/<?= htmlspecialchars($image['filename']) ?>" 
                                     alt="<?= htmlspecialchars($image['original_filename']) ?>"
                                     loading="lazy">
                            </div>
                            <div class="image-info">
                                <div class="image-name" title="<?= htmlspecialchars($image['original_filename']) ?>">
                                    <?= htmlspecialchars($image['original_filename']) ?>
                                </div>
                                <div class="image-meta">
                                    <?= $image['width'] ?> × <?= $image['height'] ?> px · 
                                    <?= number_format($image['size'] / 1024, 1) ?> KB<br>
                                    <?= date('d/m/Y H:i', strtotime($image['created_at'])) ?>
                                </div>
                                <div class="image-url" title="<?= SITE_URL ?>/i/<?= htmlspecialchars($user['username']) ?>/<?= htmlspecialchars($image['filename']) ?>">
                                    <?= SITE_URL ?>/i/<?= htmlspecialchars($user['username']) ?>/<?= htmlspecialchars($image['filename']) ?>
                                </div>
                                <div class="image-actions">
                                    <button class="btn-action btn-copy" onclick="copyUrl('<?= SITE_URL ?>/i/<?= htmlspecialchars($user['username']) ?>/<?= htmlspecialchars($image['filename']) ?>')">
                                        📋 Copier
                                    </button>
                                    <a href="/i/<?= htmlspecialchars($user['username']) ?>/<?= htmlspecialchars($image['filename']) ?>" 
                                       download="<?= htmlspecialchars($image['original_filename']) ?>.jpg"
                                       class="btn-action btn-download" style="text-decoration: none; text-align: center;">
                                        ⬇️ DL
                                    </a>
                                    <button class="btn-action btn-delete" onclick="deleteImage(<?= $image['id'] ?>)">
                                        🗑️ Sup
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function copyUrl(url) {
            navigator.clipboard.writeText(url).then(() => {
                alert('✅ URL copiée dans le presse-papiers !');
            }).catch(() => {
                // Fallback pour anciens navigateurs
                const input = document.createElement('input');
                input.value = url;
                document.body.appendChild(input);
                input.select();
                document.execCommand('copy');
                document.body.removeChild(input);
                alert('✅ URL copiée !');
            });
        }

        async function deleteImage(imageId) {
            if (!confirm('Êtes-vous sûr de vouloir supprimer cette image ?')) {
                return;
            }

            try {
                const formData = new FormData();
                formData.append('image_id', imageId);

                const response = await fetch('delete-image.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    // Retirer la carte de l'image
                    const card = document.querySelector(`[data-image-id="${imageId}"]`);
                    card.style.opacity = '0';
                    setTimeout(() => {
                        card.remove();
                        // Recharger si plus d'images
                        if (document.querySelectorAll('.image-card').length === 0) {
                            location.reload();
                        }
                    }, 300);
                    alert('✅ Image supprimée !');
                } else {
                    alert('❌ Erreur : ' + result.error);
                }
            } catch (e) {
                alert('❌ Erreur réseau');
            }
        }
    </script>
</body>
</html>