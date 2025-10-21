<?php
require_once 'config.php';
require_once 'security.php';
require_once 'image-functions.php';

// Vérifier la connexion
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Récupérer les paramètres de recherche/filtre
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'created_at';
$order = $_GET['order'] ?? 'DESC';
$visibility = $_GET['visibility'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;

// Construire la requête
$params = [
    'search' => $search,
    'sort' => $sort,
    'order' => $order,
    'visibility' => $visibility,
    'page' => $page,
    'per_page' => $perPage
];

$result = searchImages($pdo, $userId, $params);
$images = $result['images'];

// Compter le total pour la pagination
$countQuery = "SELECT COUNT(*) as total FROM images WHERE user_id = ? AND is_deleted = 0";
$countBindings = [$userId];

if (!empty($search)) {
    $countQuery .= " AND (filename LIKE ? OR original_filename LIKE ?)";
    $searchTerm = '%' . $search . '%';
    $countBindings[] = $searchTerm;
    $countBindings[] = $searchTerm;
}

if ($visibility === 'public') {
    $countQuery .= " AND is_public = 1";
} elseif ($visibility === 'private') {
    $countQuery .= " AND is_public = 0";
}

$stmt = $pdo->prepare($countQuery);
$stmt->execute($countBindings);
$totalImages = $stmt->fetch()['total'];
$totalPages = ceil($totalImages / $perPage);

// Récupérer les stats utilisateur
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_images,
        SUM(CASE WHEN is_public = 1 THEN 1 ELSE 0 END) as public_images,
        SUM(file_size) as total_size,
        SUM(views) as total_views
    FROM images 
    WHERE user_id = ? AND is_deleted = 0
");
$stmt->execute([$userId]);
$stats = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes images - Zenu</title>
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
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        
        .controls {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .controls-row {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .search-box {
            flex: 1;
            min-width: 250px;
            position: relative;
        }
        
        .search-box input {
            width: 100%;
            padding: 12px 40px 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
            transition: border 0.3s;
        }
        
        .search-box input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .search-icon {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            font-size: 18px;
        }
        
        .filter-group {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .filter-group select {
            padding: 10px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            background: white;
            cursor: pointer;
            transition: border 0.3s;
        }
        
        .filter-group select:focus {
            outline: none;
            border-color: #667eea;
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
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: #f0f0f0;
            color: #333;
        }
        
        .btn-secondary:hover {
            background: #e0e0e0;
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
        }
        
        .image-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }
        
        .image-card:hover .image-preview img {
            transform: scale(1.05);
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
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: #999;
            margin-bottom: 10px;
        }
        
        .image-badges {
            display: flex;
            gap: 5px;
            margin-bottom: 10px;
            flex-wrap: wrap;
        }
        
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .badge-public {
            background: #e8f5e9;
            color: #2e7d32;
        }
        
        .badge-private {
            background: #fce4ec;
            color: #c2185b;
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
        
        .icon-btn.delete:hover {
            background: #ffebee;
            color: #c62828;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin: 30px 0;
        }
        
        .pagination a, .pagination span {
            padding: 8px 15px;
            border-radius: 6px;
            text-decoration: none;
            color: #333;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.2s;
        }
        
        .pagination a:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
        }
        
        .pagination .active {
            background: #667eea;
            color: white;
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
        
        .visibility-toggle {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(255,255,255,0.95);
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            z-index: 10;
        }
        
        .visibility-toggle:hover {
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        
        @media (max-width: 768px) {
            .controls-row {
                flex-direction: column;
            }
            
            .search-box, .filter-group {
                width: 100%;
            }
            
            .images-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <h1>🧘 Zenu Dashboard</h1>
                </div>
                <div class="nav">
                    <a href="upload.php">📤 Upload</a>
                    <a href="trash.php">🗑️ Corbeille</a>
                    <a href="logout.php">🚪 Déconnexion</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container">
        <!-- Statistiques -->
        <div class="stats">
            <div class="stat-card">
                <div class="stat-value"><?= number_format($stats['total_images']) ?></div>
                <div class="stat-label">Images totales</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= number_format($stats['public_images']) ?></div>
                <div class="stat-label">Images publiques</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= formatFileSize($stats['total_size'] ?? 0) ?></div>
                <div class="stat-label">Espace utilisé</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= number_format($stats['total_views']) ?></div>
                <div class="stat-label">Vues totales</div>
            </div>
        </div>
        
        <!-- Contrôles de recherche et filtres -->
        <div class="controls">
            <form method="GET" action="">
                <div class="controls-row">
                    <div class="search-box">
                        <input type="text" 
                               name="search" 
                               placeholder="🔍 Rechercher par nom..." 
                               value="<?= htmlspecialchars($search) ?>">
                        <span class="search-icon">🔍</span>
                    </div>
                    
                    <div class="filter-group">
                        <select name="visibility" onchange="this.form.submit()">
                            <option value="">Toutes</option>
                            <option value="public" <?= $visibility === 'public' ? 'selected' : '' ?>>Publiques</option>
                            <option value="private" <?= $visibility === 'private' ? 'selected' : '' ?>>Privées</option>
                        </select>
                        
                        <select name="sort" onchange="this.form.submit()">
                            <option value="created_at" <?= $sort === 'created_at' ? 'selected' : '' ?>>Date</option>
                            <option value="filename" <?= $sort === 'filename' ? 'selected' : '' ?>>Nom</option>
                            <option value="file_size" <?= $sort === 'file_size' ? 'selected' : '' ?>>Taille</option>
                            <option value="views" <?= $sort === 'views' ? 'selected' : '' ?>>Vues</option>
                        </select>
                        
                        <select name="order" onchange="this.form.submit()">
                            <option value="DESC" <?= $order === 'DESC' ? 'selected' : '' ?>>↓ Décroissant</option>
                            <option value="ASC" <?= $order === 'ASC' ? 'selected' : '' ?>>↑ Croissant</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Rechercher</button>
                    <?php if ($search || $visibility): ?>
                        <a href="dashboard-enhanced.php" class="btn btn-secondary">Réinitialiser</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <!-- Grille d'images -->
        <?php if (empty($images)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">📷</div>
                <h3><?= $search ? 'Aucun résultat' : 'Aucune image' ?></h3>
                <p><?= $search ? 'Essayez avec d\'autres mots-clés' : 'Commencez par uploader votre première image' ?></p>
                <?php if (!$search): ?>
                    <a href="upload.php" class="btn btn-primary">📤 Upload une image</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="images-grid">
                <?php foreach ($images as $image): ?>
                    <div class="image-card" data-image-id="<?= $image['id'] ?>">
                        <div class="image-preview">
                            <div class="visibility-toggle" 
                                 onclick="toggleVisibility(<?= $image['id'] ?>, <?= $image['is_public'] ?>)"
                                 title="<?= $image['is_public'] ? 'Rendre privée' : 'Rendre publique' ?>">
                                <?= $image['is_public'] ? '🔓' : '🔒' ?>
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
                                <span><?= $image['dimensions'] ?? ($image['width'] . 'x' . $image['height']) ?></span>
                                <span><?= formatFileSize($image['file_size'] ?? 0) ?></span>
                            </div>
                            <div class="image-badges">
                                <span class="badge <?= $image['is_public'] ? 'badge-public' : 'badge-private' ?>">
                                    <?= $image['is_public'] ? '🌐 Public' : '🔒 Privé' ?>
                                </span>
                                <?php if ($image['views'] > 0): ?>
                                    <span class="badge" style="background: #e3f2fd; color: #1976d2;">
                                        👁️ <?= $image['views'] ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="image-actions">
                                <button class="icon-btn" onclick="viewImage(<?= $image['id'] ?>)" title="Voir">
                                    👁️
                                </button>
                                <?php if ($image['is_public']): ?>
                                    <button class="icon-btn" onclick="copyShareLink(<?= $image['id'] ?>, '<?= $image['share_token'] ?>')" title="Copier lien">
                                        🔗
                                    </button>
                                <?php endif; ?>
                                <button class="icon-btn" onclick="downloadImage(<?= $image['id'] ?>)" title="Télécharger">
                                    ⬇️
                                </button>
                                <button class="icon-btn delete" onclick="deleteImage(<?= $image['id'] ?>)" title="Supprimer">
                                    🗑️
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&sort=<?= $sort ?>&order=<?= $order ?>&visibility=<?= $visibility ?>">
                            ← Précédent
                        </a>
                    <?php endif; ?>
                    
                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    
                    for ($i = $startPage; $i <= $endPage; $i++):
                    ?>
                        <?php if ($i === $page): ?>
                            <span class="active"><?= $i ?></span>
                        <?php else: ?>
                            <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&sort=<?= $sort ?>&order=<?= $order ?>&visibility=<?= $visibility ?>">
                                <?= $i ?>
                            </a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&sort=<?= $sort ?>&order=<?= $order ?>&visibility=<?= $visibility ?>">
                            Suivant →
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <script>
        // Toggle visibilité public/privé
        async function toggleVisibility(imageId, isCurrentlyPublic) {
            const newState = !isCurrentlyPublic;
            
            try {
                const response = await fetch('api/toggle-visibility.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        image_id: imageId,
                        is_public: newState
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    location.reload();
                } else {
                    alert('Erreur: ' + (data.error || 'Une erreur est survenue'));
                }
            } catch (error) {
                alert('Erreur réseau');
            }
        }
        
        // Copier le lien de partage
        function copyShareLink(imageId, shareToken) {
            const shareUrl = '<?= SITE_URL ?>/share.php?t=' + shareToken;
            
            navigator.clipboard.writeText(shareUrl).then(() => {
                alert('✅ Lien copié dans le presse-papier !');
            }).catch(() => {
                prompt('Copier ce lien:', shareUrl);
            });
        }
        
        // Voir l'image
        function viewImage(imageId) {
            window.open('view.php?id=' + imageId, '_blank');
        }
        
        // Télécharger l'image
        function downloadImage(imageId) {
            window.location.href = 'download.php?id=' + imageId;
        }
        
        // Supprimer l'image (soft delete)
        async function deleteImage(imageId) {
            if (!confirm('Êtes-vous sûr de vouloir supprimer cette image ? Elle sera déplacée dans la corbeille.')) {
                return;
            }
            
            try {
                const response = await fetch('api/delete-image.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ image_id: imageId })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Retirer visuellement la carte
                    const card = document.querySelector(`[data-image-id="${imageId}"]`);
                    if (card) {
                        card.style.animation = 'fadeOut 0.3s';
                        setTimeout(() => card.remove(), 300);
                    }
                } else {
                    alert('Erreur: ' + (data.error || 'Impossible de supprimer'));
                }
            } catch (error) {
                alert('Erreur réseau');
            }
        }
        
        // Animation de suppression
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
</html>