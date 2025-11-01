<?php
require_once 'config.php';
require_once 'image-functions.php';

if (file_exists('security.php')) {
    require_once 'security.php';
}

if (!function_exists('checkAuth')) {
    function checkAuth() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: login.php');
            exit;
        }
    }
}

checkAuth();

$userId = $_SESSION['user_id'];
$pageTitle = "Corbeille";

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

// Inclure le header
require_once 'header.php';
?>
    <title>Corbeille - Zenu</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            margin: 0;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px 20px;
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
            font-weight: 600;
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
            transition: all 0.3s;
        }
        
        .image-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        
        .image-preview {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: #f5f5f5;
        }
        
        .image-info {
            padding: 15px;
        }
        
        .image-name {
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .image-meta {
            font-size: 13px;
            color: #999;
            margin-bottom: 12px;
        }
        
        .days-remaining {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 8px;
        }
        
        .days-remaining.critical {
            background: #ffebee;
            color: #c62828;
        }
        
        .days-remaining.warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .days-remaining.safe {
            background: #e8f5e9;
            color: #2e7d32;
        }
        
        .image-actions {
            display: flex;
            gap: 8px;
        }
        
        .icon-btn {
            flex: 1;
            padding: 10px;
            border: none;
            background: #f5f5f5;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 14px;
            font-weight: 600;
        }
        
        .icon-btn:hover {
            transform: translateY(-1px);
        }
        
        .icon-btn.restore {
            background: #e8f5e9;
            color: #2e7d32;
        }
        
        .icon-btn.restore:hover {
            background: #c8e6c9;
        }
        
        .icon-btn.delete-permanent {
            background: #ffebee;
            color: #c62828;
        }
        
        .icon-btn.delete-permanent:hover {
            background: #ffcdd2;
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
                <div class="stat-label">Espace occupé</div>
            </div>
        </div>
        
        <?php if (!empty($deletedImages)): ?>
            <div class="actions-bar">
                <button class="btn btn-danger" onclick="emptyTrash()">
                    🗑️ Vider la corbeille
                </button>
            </div>
            
            <div class="images-grid">
                <?php foreach ($deletedImages as $image): ?>
                    <div class="image-card" id="image-<?= $image['id'] ?>">
                        <img src="<?= htmlspecialchars($image['thumbnail_path'] ?? $image['file_path']) ?>" 
                             alt="<?= htmlspecialchars($image['original_filename'] ?? $image['filename']) ?>"
                             class="image-preview">
                        <div class="image-info">
                            <div class="image-name">
                                <?= htmlspecialchars($image['original_filename'] ?? $image['filename']) ?>
                            </div>
                            <div class="image-meta">
                                <?= $image['width'] ?>×<?= $image['height'] ?> · 
                                <?= formatFileSize($image['file_size']) ?>
                                <br>
                                Supprimé: <?= date('d/m/Y', strtotime($image['deleted_at'])) ?>
                                <?php
                                $daysRemaining = $image['days_remaining'];
                                $class = $daysRemaining <= 7 ? 'critical' : ($daysRemaining <= 14 ? 'warning' : 'safe');
                                ?>
                                <span class="days-remaining <?= $class ?>">
                                    <?= $daysRemaining ?> jour<?= $daysRemaining > 1 ? 's' : '' ?> restant<?= $daysRemaining > 1 ? 's' : '' ?>
                                </span>
                            </div>
                            <div class="image-actions">
                                <button class="icon-btn restore" onclick="restoreImage(<?= $image['id'] ?>)">
                                    ♻️ Restaurer
                                </button>
                                <button class="icon-btn delete-permanent" onclick="deleteImagePermanently(<?= $image['id'] ?>)">
                                    ❌ Supprimer
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">🗑️</div>
                <h3>La corbeille est vide</h3>
                <p>Aucune image supprimée</p>
                <a href="dashboard.php" class="btn" style="background: #667eea; color: white;">
                    Retour au dashboard
                </a>
            </div>
        <?php endif; ?>
    </div>

<script>
        async function restoreImage(imageId) {
            if (!confirm('Voulez-vous restaurer cette image ?')) return;
            
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
                    // ✅ Afficher un message personnalisé si l'image a été renommée
                    if (data.renamed) {
                        alert('✅ ' + data.message);
                    }
                    
                    const card = document.getElementById(`image-${imageId}`);
                    card.style.animation = 'fadeOut 0.3s ease-out';
                    setTimeout(() => {
                        card.remove();
                        location.reload();
                    }, 300);
                } else {
                    alert('Erreur: ' + (data.error || 'Impossible de restaurer l\'image'));
                }
            } catch (error) {
                console.error('Erreur:', error);
                alert('Erreur réseau');
            }
        }
        
        async function deleteImagePermanently(imageId) {
            if (!confirm('⚠️ ATTENTION: Cette action est IRRÉVERSIBLE!\n\nVoulez-vous vraiment supprimer définitivement cette image ?')) return;
            
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
                    const card = document.getElementById(`image-${imageId}`);
                    card.style.animation = 'fadeOut 0.3s ease-out';
                    setTimeout(() => {
                        card.remove();
                        
                        // Recharger si plus aucune image
                        if (document.querySelectorAll('.image-card').length === 0) {
                            location.reload();
                        }
                    }, 300);
                } else {
                    alert('Erreur: ' + (data.error || 'Impossible de supprimer l\'image'));
                }
            } catch (error) {
                console.error('Erreur:', error);
                alert('Erreur réseau');
            }
        }
        
        async function emptyTrash() {
            if (!confirm('⚠️ ATTENTION: Cette action est IRRÉVERSIBLE!\n\nVoulez-vous vraiment vider la corbeille et supprimer TOUTES les images définitivement ?')) return;
            
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
                    alert('Erreur: ' + (data.error || 'Impossible de vider la corbeille'));
                }
            } catch (error) {
                console.error('Erreur:', error);
                alert('Erreur réseau');
            }
        }
    </script>
</body>
</html>