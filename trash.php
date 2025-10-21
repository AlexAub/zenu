<?php
require_once 'config.php';
require_once 'security.php';
require_once 'image-functions.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Récupérer les images supprimées
$stmt = $pdo->prepare("
    SELECT *, DATEDIFF(DATE_ADD(deleted_at, INTERVAL 30 DAY), NOW()) as days_remaining
    FROM images 
    WHERE user_id = ? AND is_deleted = 1
    ORDER BY deleted_at DESC
");
$stmt->execute([$userId]);
$deletedImages = $stmt->fetchAll();

// Stats
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(file_size) as total_size
    FROM images 
    WHERE user_id = ? AND is_deleted = 1
");
$stmt->execute([$userId]);
$stats = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Corbeille - Zenu</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo h1 {
            font-size: 24px;
        }
        
        .nav a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
            padding: 8px 16px;
            border-radius: 6px;
            transition: background 0.3s;
        }
        
        .nav a:hover {
            background: rgba(255,255,255,0.2);
        }
        
        .info-banner {
            background: #fff3e0;
            border-left: 4px solid #ff9800;
            padding: 20px;
            margin: 30px 0;
            border-radius: 8px;
        }
        
        .info-banner h3 {
            color: #e65100;
            margin-bottom: 10px;
        }
        
        .info-banner p {
            color: #666;
            line-height: 1.6;
        }
        
        .stats-row {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            flex: 1;
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .stat-value {
            font-size: 28px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        
        .actions-bar {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-danger {
            background: #f44336;
            color: white;
        }
        
        .btn-danger:hover {
            background: #d32f2f;
            transform: translateY(-2px);
        }
        
        .images-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .image-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            position: relative;
        }
        
        .image-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        
        .image-preview {
            width: 100%;
            height: 220px;
            overflow: hidden;
            background: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            opacity: 0.7;
        }
        
        .image-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .days-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(244, 67, 54, 0.95);
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            z-index: 10;
        }
        
        .image-info {
            padding: 15px;
        }
        
        .image-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .image-meta {
            font-size: 12px;
            color: #999;
            margin-bottom: 10px;
        }
        
        .image-actions {
            display: flex;
            gap: 8px;
        }
        
        .icon-btn {
            flex: 1;
            padding: 8px;
            border: none;
            background: #f5f5f5;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 14px;
        }
        
        .icon-btn:hover {
            background: #e0e0e0;
            transform: translateY(-1px);
        }
        
        .icon-btn.restore:hover {
            background: #e8f5e9;
            color: #2e7d32;
        }
        
        .icon-btn.delete-permanent:hover {
            background: #ffebee;
            color: #c62828;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.3;
        }
        
        .empty-state h3 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .empty-state p {
            color: #999;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <h1>🗑️ Corbeille</h1>
                </div>
                <div class="nav">
                    <a href="dashboard-enhanced.php">← Dashboard</a>
                    <a href="upload.php">📤 Upload</a>
                    <a href="logout.php">🚪 Déconnexion</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="info-banner">
            <h3>⚠️ Suppression automatique dans 30 jours</h3>
            <p>Les images dans la corbeille sont conservées pendant 30 jours avant d'être supprimées définitivement. Vous pouvez les restaurer à tout moment pendant cette période.</p>
        </div>
        
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-value"><?= number_format($stats['total']) ?></div>
                <div class="stat-label">Images dans la corbeille</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= formatFileSize($stats['total_size'] ?? 0) ?></div>
                <div class="stat-label">Espace libérable</div>
            </div>
        </div>
        
        <?php if (!empty($deletedImages)): ?>
            <div class="actions-bar">
                <button class="btn btn-danger" onclick="emptyTrash()">
                    🗑️ Vider la corbeille
                </button>
            </div>
        <?php endif; ?>
        
        <?php if (empty($deletedImages)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">✨</div>
                <h3>Corbeille vide</h3>
                <p>Aucune image supprimée</p>
                <a href="dashboard-enhanced.php" class="btn" style="background: #667eea; color: white;">
                    ← Retour au dashboard
                </a>
            </div>
        <?php else: ?>
            <div class="images-grid">
                <?php foreach ($deletedImages as $image): ?>
                    <div class="image-card" data-image-id="<?= $image['id'] ?>">
                        <div class="image-preview">
                            <div class="days-badge">
                                <?= max(0, $image['days_remaining']) ?> jours restants
                            </div>
                            <img src="<?= htmlspecialchars($image['thumbnail_path'] ?? $image['file_path']) ?>" 
                                 alt="<?= htmlspecialchars($image['original_filename'] ?? $image['filename']) ?>"
                                 loading="lazy">
                        </div>
                        <div class="image-info">
                            <div class="image-name" title="<?= htmlspecialchars($image['original_filename'] ?? $image['filename']) ?>">
                                <?= htmlspecialchars($image['original_filename'] ?? $image['filename']) ?>
                            </div>
                            <div class="image-meta">
                                Supprimé le <?= date('d/m/Y', strtotime($image['deleted_at'])) ?>
                            </div>
                            <div class="image-actions">
                                <button class="icon-btn restore" onclick="restoreImage(<?= $image['id'] ?>)" title="Restaurer">
                                    ♻️ Restaurer
                                </button>
                                <button class="icon-btn delete-permanent" onclick="deleteImagePermanently(<?= $image['id'] ?>)" title="Supprimer définitivement">
                                    ❌ Supprimer
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Restaurer une image
        async function restoreImage(imageId) {
            if (!confirm('Restaurer cette image ?')) {
                return;
            }
            
            try {
                const response = await fetch('api/restore-image.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ image_id: imageId })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    const card = document.querySelector(`[data-image-id="${imageId}"]`);
                    if (card) {
                        card.style.animation = 'fadeOut 0.3s';
                        setTimeout(() => {
                            card.remove();
                            if (document.querySelectorAll('.image-card').length === 0) {
                                location.reload();
                            }
                        }, 300);
                    }
                } else {
                    alert('Erreur: ' + (data.error || 'Impossible de supprimer'));
                }
            } catch (error) {
                alert('Erreur réseau');
            }
        }
        
        // Vider la corbeille
        async function emptyTrash() {
            if (!confirm('⚠️ ATTENTION : Cette action supprimera DÉFINITIVEMENT toutes les images de la corbeille !\n\nCette action est IRRÉVERSIBLE. Continuer ?')) {
                return;
            }
            
            try {
                const response = await fetch('api/empty-trash.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('✅ Corbeille vidée : ' + data.deleted_count + ' image(s) supprimée(s)');
                    location.reload();
                } else {
                    alert('Erreur: ' + (data.error || 'Impossible de vider la corbeille'));
                }
            } catch (error) {
                alert('Erreur réseau');
            }
        }
        
        // Animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeOut {
                from { opacity: 1; transform: scale(1); }
                to { opacity: 0; transform: scale(0.8); }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>data.success) {
                    alert('✅ Image restaurée !');
                    location.reload();
                } else {
                    alert('Erreur: ' + (data.error || 'Impossible de restaurer'));
                }
            } catch (error) {
                alert('Erreur réseau');
            }
        }
        
        // Supprimer définitivement une image
        async function deleteImagePermanently(imageId) {
            if (!confirm('⚠️ ATTENTION : Cette action est IRRÉVERSIBLE !\n\nÊtes-vous sûr de vouloir supprimer définitivement cette image ?')) {
                return;
            }
            
            try {
                const response = await fetch('api/delete-permanent.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ image_id: imageId })
                });
                
                const data = await response.json();
                
                if (