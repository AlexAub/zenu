<?php
require_once 'config.php';
require_once 'security.php';
require_once 'image-functions.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$imageId = intval($_GET['id'] ?? 0);

if ($imageId <= 0) {
    header('Location: dashboard-enhanced.php');
    exit;
}

// Récupérer l'image
$stmt = $pdo->prepare("
    SELECT * FROM images 
    WHERE id = ? AND user_id = ? AND is_deleted = 0
");
$stmt->execute([$imageId, $userId]);
$image = $stmt->fetch();

if (!$image) {
    header('Location: dashboard-enhanced.php');
    exit;
}

$imageDimensions = $image['dimensions'] ?? ($image['width'] . 'x' . $image['height']);
$imageFilename = $image['original_filename'] ?? $image['filename'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($imageFilename) ?> - Zenu</title>
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
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">🧘 Zenu</div>
        <div class="actions">
            <a href="<?= htmlspecialchars($image['file_path']) ?>" download class="btn btn-secondary">
                ⬇️ Télécharger
            </a>
            <a href="dashboard-enhanced.php" class="btn btn-primary">
                ← Dashboard
            </a>
        </div>
    </div>
    
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
                    <span class="meta-label">Dimensions</span>
                    <span class="meta-value"><?= htmlspecialchars($imageDimensions) ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Taille</span>
                    <span class="meta-value"><?= formatFileSize($image['file_size'] ?? 0) ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Uploadé le</span>
                    <span class="meta-value"><?= date('d/m/Y', strtotime($image['created_at'])) ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Statut</span>
                    <span class="meta-value"><?= $image['is_public'] ? '🔓 Public' : '🔒 Privé' ?></span>
                </div>
            </div>
        </div>
    </div>
</body>
</html>