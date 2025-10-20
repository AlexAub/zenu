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
            display: flex;
            flex-direction: column;
        }
        
        nav {
            background: rgba(255, 255, 255, 0.98);
            padding: 15px 30px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            backdrop-filter: blur(10px);
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
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
            flex: 1;
        }
        
        .dashboard-header {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 12px;
            padding: 25px 30px;
            margin-bottom: 25px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .user-info h1 {
            font-size: 28px;
            color: #667eea;
            margin-bottom: 5px;
        }
        
        .user-email {
            color: #666;
            font-size: 14px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            text-align: center;
        }
        
        .stat-icon {
            font-size: 32px;
            margin-bottom: 8px;
        }
        
        .stat-value {
            font-size: 22px;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 3px;
        }
        
        .stat-label {
            color: #666;
            font-size: 13px;
        }
        
        .progress-bar {
            width: 100%;
            height: 6px;
            background: #e0e0e0;
            border-radius: 3px;
            margin-top: 8px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: width 0.3s;
        }
        
        .content-section {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .section-header h2 {
            font-size: 22px;
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
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 15px;
        }
        
        .image-card {
            background: rgba(248, 249, 255, 0.98);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 8px rgba(0,0,0,0.06);
            transition: transform 0.2s;
        }
        
        .image-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .image-preview {
            width: 100%;
            height: 180px;
            background: #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            cursor: pointer;
            transition: opacity 0.2s;
        }
        
        .image-preview:hover {
            opacity: 0.9;
        }
        
        .image-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .image-info {
            padding: 12px;
        }
        
        .image-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 6px;
            font-size: 13px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            cursor: pointer;
            transition: color 0.2s;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .image-name:hover {
            color: #667eea;
        }
        
        .image-name:hover .edit-icon {
            opacity: 1;
        }
        
        .edit-icon {
            opacity: 0.5;
            font-size: 11px;
            transition: opacity 0.2s;
        }
        
        .image-meta {
            font-size: 11px;
            color: #999;
            margin-bottom: 8px;
        }
        
        .image-url {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 5px;
            font-size: 10px;
            word-break: break-all;
            margin-bottom: 8px;
            max-height: 35px;
            overflow: hidden;
        }
        
        .image-actions {
            display: flex;
            gap: 6px;
        }
        
        .btn-action {
            flex: 1;
            padding: 5px;
            border: none;
            border-radius: 5px;
            font-size: 11px;
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
        
        .rename-input {
            width: 100%;
            padding: 5px;
            border: 2px solid #667eea;
            border-radius: 4px;
            font-size: 12px;
            margin-bottom: 6px;
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .images-grid {
                grid-template-columns: 1fr;
            }
            
            .dashboard-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .btn-primary {
                width: 100%;
                text-align: center;
            }
        }
        
        @media (min-width: 769px) and (max-width: 1200px) {
            .images-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }
            
            .stats-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
        @media (min-width: 1201px) {
            .images-grid {
                grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            }
            
            .stats-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
        /* Footer minimal */
        .site-footer {
            background: rgba(255, 255, 255, 0.98);
            padding: 20px;
            margin-top: 60px;
            box-shadow: 0 -1px 3px rgba(0,0,0,0.05);
            backdrop-filter: blur(10px);
        }

        .footer-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .footer-left {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #666;
            font-size: 14px;
        }

        .footer-brand {
            font-weight: 600;
            color: #667eea;
        }

        .footer-tagline {
            color: #999;
        }

        .footer-separator {
            color: #ddd;
        }

        .footer-right {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
        }

        .footer-right a {
            color: #666;
            text-decoration: none;
            transition: color 0.3s;
        }

        .footer-right a:hover {
            color: #667eea;
        }

        .footer-copyright {
            text-align: center;
            color: #aaa;
            font-size: 12px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #f0f0f0;
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
            <div class="user-info">
                <h1>Bienvenue, <?= htmlspecialchars($user['username']) ?> 👋</h1>
                <p class="user-email"><?= htmlspecialchars($user['email']) ?></p>
            </div>
            <a href="convertisseur-prive.php" class="btn-primary">➕ Nouvelle image</a>
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
            </div>
            
            <?php if (empty($images)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">🖼️</div>
                    <h3>Aucune image pour le moment</h3>
                    <p>Utilisez le convertisseur Cloud pour sauvegarder vos premières images.</p>
                    <a href="convertisseur-prive.php" class="btn-primary">Convertir et sauvegarder</a>
                </div>
            <?php else: ?>
                <div class="images-grid">
                    <?php foreach ($images as $image): ?>
                        <div class="image-card" data-image-id="<?= $image['id'] ?>">
                            <div class="image-preview" onclick="openImage('<?= htmlspecialchars($user['username']) ?>', '<?= htmlspecialchars($image['filename']) ?>')">
                                <img src="/i/<?= htmlspecialchars($user['username']) ?>/<?= htmlspecialchars($image['filename']) ?>" 
                                     alt="<?= htmlspecialchars($image['original_filename']) ?>"
                                     loading="lazy">
                            </div>
                            <div class="image-info">
                                <div class="image-name" 
                                     data-image-id="<?= $image['id'] ?>"
                                     data-original-name="<?= htmlspecialchars($image['original_filename']) ?>"
                                     onclick="startRename(<?= $image['id'] ?>)"
                                     title="Cliquer pour renommer">
                                    <span style="overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($image['original_filename']) ?></span>
                                    <span class="edit-icon">✏️</span>
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

    <footer class="site-footer">
        <div class="footer-content">
            <div class="footer-left">
                <span class="footer-brand">🧘 Zenu</span>
                <span class="footer-separator">·</span>
                <span class="footer-tagline">Outils simples et zen</span>
            </div>
            
            <div class="footer-right">
                <a href="mentions-legales.php">Mentions légales</a>
                <span class="footer-separator">·</span>
                <a href="cgu.php">CGU</a>
                <span class="footer-separator">·</span>
                <a href="privacy.php">Confidentialité</a>
                <span class="footer-separator">·</span>
                <a href="mailto:contact@zenu.fr">Contact</a>
            </div>
        </div>
        
        <div class="footer-copyright">
            &copy; <?= date('Y') ?> Zenu
        </div>
    </footer>

    <script>
        function openImage(username, filename) {
            window.open('/i/' + username + '/' + filename, '_blank');
        }
        
        function startRename(imageId) {
            const nameDiv = document.querySelector(`.image-name[data-image-id="${imageId}"]`);
            const originalName = nameDiv.getAttribute('data-original-name');
            
            const input = document.createElement('input');
            input.type = 'text';
            input.className = 'rename-input';
            input.value = originalName;
            input.onblur = () => finishRename(imageId, input.value);
            input.onkeypress = (e) => {
                if (e.key === 'Enter') {
                    finishRename(imageId, input.value);
                } else if (e.key === 'Escape') {
                    nameDiv.style.display = 'block';
                    input.remove();
                }
            };
            
            nameDiv.style.display = 'none';
            nameDiv.parentElement.insertBefore(input, nameDiv);
            input.focus();
            input.select();
        }
        
        async function finishRename(imageId, newName) {
            const nameDiv = document.querySelector(`.image-name[data-image-id="${imageId}"]`);
            const input = nameDiv.previousElementSibling;
            
            if (!newName || newName.trim() === '') {
                nameDiv.style.display = 'block';
                input.remove();
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('image_id', imageId);
                formData.append('new_name', newName.trim());
                
                const response = await fetch('rename-image.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    const cleanName = result.new_filename.replace('.jpg', '');
                    nameDiv.innerHTML = `<span style="overflow: hidden; text-overflow: ellipsis;">${cleanName}</span><span class="edit-icon">✏️</span>`;
                    nameDiv.setAttribute('data-original-name', cleanName);
                    nameDiv.setAttribute('title', 'Cliquer pour renommer');
                    
                    const card = document.querySelector(`[data-image-id="${imageId}"]`);
                    const urlDiv = card.querySelector('.image-url');
                    if (urlDiv) {
                        urlDiv.textContent = result.new_url;
                        urlDiv.setAttribute('title', result.new_url);
                    }
                    
                    alert('✅ Image renommée !');
                } else {
                    alert('❌ Erreur : ' + result.error);
                }
            } catch (e) {
                alert('❌ Erreur réseau');
            }
            
            nameDiv.style.display = 'block';
            input.remove();
        }
        
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