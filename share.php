    <div class="main-content">
        <div class="image-container">
            <img src="<?= htmlspecialchars($image['file_path']) ?>" 
                 alt="<?= htmlspecialchars($imageFilename) ?>">
        </div>
        
        <div class="image-info">
            <div class="image-title">
                📷 <?= htmlspecialchars($imageFilename) ?>
            </div>
            
            <div class="image-meta">
                <div class="meta-item">
                    <span class="meta-label">Partagé par</span>
                    <span class="meta-value"><?= htmlspecialchars($image['username']) ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Dimensions</span>
                    <span class="meta-value"><?= htmlspecialchars($imageDimensions) ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Taille</span>
                    <span class="meta-value"><?= formatFileSize($image['file_size'] ?? 0) ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Vues</span>
                    <span class="meta-value">👁️ <?= number_format($image['views'] + 1) ?></span>
                </div>
            </div>
        </div>
    </div><?php
require_once 'config.php';

$shareToken = $_GET['t'] ?? '';

if (empty($shareToken)) {
    header('HTTP/1.0 404 Not Found');
    exit('Image non trouvée');
}

// Récupérer l'image par le token
$stmt = $pdo->prepare("
    SELECT i.*, u.username 
    FROM images i 
    JOIN users u ON i.user_id = u.id
    WHERE i.share_token = ? AND i.is_public = 1 AND i.is_deleted = 0
");
$stmt->execute([$shareToken]);
$image = $stmt->fetch();

if (!$image) {
    header('HTTP/1.0 404 Not Found');
    exit('Image non trouvée ou privée');
}

// Incrémenter le compteur de vues
$stmt = $pdo->prepare("UPDATE images SET views = views + 1 WHERE id = ?");
$stmt->execute([$image['id']]);

// Obtenir l'URL complète de l'image
$imageUrl = SITE_URL . '/' . $image['file_path'];
$imageFilename = $image['original_filename'] ?? $image['filename'];
$imageDimensions = $image['dimensions'] ?? ($image['width'] . 'x' . $image['height']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($image['filename']) ?> - Zenu</title>
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="image">
    <meta property="og:url" content="<?= htmlspecialchars(SITE_URL . '/share.php?t=' . $shareToken) ?>">
    <meta property="og:title" content="<?= htmlspecialchars($imageFilename) ?>">
    <meta property="og:description" content="Partagé via Zenu">
    <meta property="og:image" content="<?= htmlspecialchars($imageUrl) ?>">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?= htmlspecialchars(SITE_URL . '/share.php?t=' . $shareToken) ?>">
    <meta property="twitter:title" content="<?= htmlspecialchars($imageFilename) ?>">
    <meta property="twitter:description" content="Partagé via Zenu">
    <meta property="twitter:image" content="<?= htmlspecialchars($imageUrl) ?>">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #1a1a1a;
            color: white;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .header {
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(10px);
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .logo {
            font-size: 20px;
            font-weight: 600;
        }
        
        .actions {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
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
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }
        
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }
        
        .image-container {
            max-width: 90vw;
            max-height: 70vh;
            margin-bottom: 30px;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        }
        
        .image-container img {
            max-width: 100%;
            max-height: 70vh;
            display: block;
            object-fit: contain;
        }
        
        .image-info {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            padding: 20px 30px;
            border-radius: 12px;
            max-width: 600px;
            width: 100%;
        }
        
        .image-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 15px;
            word-break: break-word;
        }
        
        .image-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .meta-item {
            display: flex;
            flex-direction: column;
        }
        
        .meta-label {
            font-size: 12px;
            color: #999;
            margin-bottom: 5px;
        }
        
        .meta-value {
            font-size: 16px;
            font-weight: 600;
        }
        
        .footer {
            background: rgba(0, 0, 0, 0.5);
            padding: 20px;
            text-align: center;
            font-size: 14px;
            color: #999;
        }
        
        .footer a {
            color: #667eea;
            text-decoration: none;
        }
        
        .footer a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            .image-container {
                max-width: 95vw;
                max-height: 60vh;
            }
            
            .actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">🧘 Zenu</div>
        <div class="actions">
            <a href="<?= htmlspecialchars($image['file_path']) ?>" download class="btn btn-secondary">
                ⬇️ Télécharger
            </a>
            <a href="register.php" class="btn btn-primary">
                ✨ Créer mon compte
            </a>
        </div>
    </div>
    
    <div class="main-content">
        <div class="image-container">
            <img src="<?= htmlspecialchars($image['file_path']) ?>" 
                 alt="<?= htmlspecialchars($image['filename']) ?>">
        </div>
        
        <div class="image-info">
            <div class="image-title">
                📷 <?= htmlspecialchars($image['filename']) ?>
            </div>
            
            <div class="image-meta">
                <div class="meta-item">
                    <span class="meta-label">Partagé par</span>
                    <span class="meta-value"><?= htmlspecialchars($image['username']) ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Dimensions</span>
                    <span class="meta-value"><?= htmlspecialchars($image['dimensions']) ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Taille</span>
                    <span class="meta-value"><?= formatFileSize($image['file_size'] ?? 0) ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Vues</span>
                    <span class="meta-value">👁️ <?= number_format($image['views'] + 1) ?></span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="footer">
        <p>
            Partagé via <a href="<?= SITE_URL ?>">Zenu</a> - 
            <a href="<?= SITE_URL ?>/register.php">Créez votre compte gratuitement</a>
        </p>
    </div>
    
    <?php
    // Fonction locale si pas déjà chargée
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
</body>
</html>