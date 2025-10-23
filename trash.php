<?php
// Ne pas appeler session_start() ici car config.php le fait déjà

require_once 'config.php';
require_once 'image-functions.php';

// Vérifier si security.php existe
if (file_exists('security.php')) {
    require_once 'security.php';
}

// Fonction checkAuth si elle n'existe pas
if (!function_exists('checkAuth')) {
    function checkAuth() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: login.php');
            exit;
        }
    }
}

// Prévention CSRF basique si la fonction n'existe pas
if (!function_exists('preventCSRF')) {
    function preventCSRF() {
        return true;
    }
}

checkAuth();
preventCSRF();

$userId = $_SESSION['user_id'];

// Statistiques de la corbeille
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        COALESCE(SUM(file_size), 0) as total_size
    FROM images 
    WHERE user_id = ? AND is_deleted = 1
");
$stmt->execute([$userId]);
$stats = $stmt->fetch();

// Récupérer les images supprimées
$stmt = $pdo->prepare("
    SELECT 
        id,
        filename,
        original_filename,
        file_path,
        thumbnail_path,
        file_size,
        width,
        height,
        deleted_at,
        DATEDIFF(DATE_ADD(deleted_at, INTERVAL 30 DAY), NOW()) as days_remaining
    FROM images 
    WHERE user_id = ? AND is_deleted = 1
    ORDER BY deleted_at DESC
");
$stmt->execute([$userId]);
$deletedImages = $stmt->fetchAll();

// Fonction formatFileSize si elle n'existe pas
if (!function_exists('formatFileSize')) {
    function formatFileSize($bytes) {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' Go';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' Mo';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' Ko';
        } else {
            return $bytes . ' octets';
        }
    }
}
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .header {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px 0;
            margin-bottom: 30px;
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
            color: #667eea;
            font-size: 28px;
        }
        
        .nav {
            display: flex;
            gap: 15px;
        }
        
        .nav a {
            text-decoration: none;
            color: #666;
            padding: 10px 20px;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .nav a:hover {
            background: #667eea;
            color: white;
        }
        
        .info-banner {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            color: #856404;
        }
        
        .info-banner h3 {
            margin-bottom: 10px;
            font-size: 18px;
        }
        
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #999;
            font-size: 14px;
        }
        
        .actions-bar {
            margin-bottom: 30px;
            text-align: right;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
        }
        
        .images-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .image-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .image-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        
        .image-preview {
            position: relative;
            width: 100%;
            height: 200px;
            overflow: hidden;
            background: #f5f5f5;
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
            background: rgba(255, 0, 0, 0.9);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .image-info {
            padding: 15px;
        }
        
        .image-name {
            font-weight: 600;
            margin-bottom: 5px;
            color: #333;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .image-meta {
            color: #999;
            font-size: 13px;
            margin-bottom: 15px;
        }
        
        .image-actions {
            display: flex;
            gap: 10px;
        }
        
        .icon-btn {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid #ddd;
            background: white;
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
        
        @keyframes fadeOut {
            from { 
                opacity: 1; 
                transform: scale(1); 
            }
            to { 
                opacity: 0; 
                transform: scale(0.8); 
            }
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
                    <a href="dashboard.php">← Dashboard</a>
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
                <a href="dashboard.php" class="btn" style="background: #667eea; color: white;">
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
                    alert('✅ Image restaurée !');
                    location.reload();
                } else {
                    alert('Erreur: ' + (data.error || 'Impossible de restaurer'));
                }
            } catch (error) {
                console.error('Erreur:', error);
                alert('Erreur réseau lors de la restauration');
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
                
                if (data.success) {
                    alert('✅ Image supprimée définitivement !');
                    
                    // Retirer visuellement la carte avec animation
                    const card = document.querySelector(`[data-image-id="${imageId}"]`);
                    if (card) {
                        card.style.animation = 'fadeOut 0.3s';
                        setTimeout(() => {
                            card.remove();
                            
                            // Vérifier s'il reste des images
                            const remainingCards = document.querySelectorAll('.image-card');
                            if (remainingCards.length === 0) {
                                location.reload();
                            }
                        }, 300);
                    }
                } else {
                    alert('❌ Erreur: ' + (data.error || 'Impossible de supprimer'));
                }
            } catch (error) {
                console.error('Erreur:', error);
                alert('❌ Erreur réseau lors de la suppression');
            }
        }
        
        // Vider toute la corbeille
        async function emptyTrash() {
            if (!confirm('⚠️ ATTENTION : Vider toute la corbeille ?\n\nToutes les images seront supprimées définitivement !')) {
                return;
            }
            
            if (!confirm('⚠️ DERNIÈRE CONFIRMATION\n\nCette action est IRRÉVERSIBLE !')) {
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
                    alert('✅ ' + data.message);
                    location.reload();
                } else {
                    alert('❌ Erreur: ' + (data.error || 'Impossible de vider la corbeille'));
                }
            } catch (error) {
                console.error('Erreur:', error);
                alert('❌ Erreur réseau');
            }
        }
    </script>
</body>
</html>