<?php
// Inclure config.php qui gère la session et la connexion à la BDD
require_once 'config.php';

// Récupérer l'utilisateur actuel (retourne null si non connecté)
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Convertisseur d'Images JPG - Zenu</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
        }
        
        /* Header styles - inspiré du header.php */
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
            font-weight: 500;
        }
        
        .btn-login {
            background: white !important;
            color: #667eea !important;
            font-weight: 600;
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .btn-login:hover {
            background: rgba(255,255,255,0.9) !important;
            transform: translateY(-1px);
        }
        
        .btn-logout {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-logout:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-1px);
        }
        
        /* Container principal */
        .container {
            background: white;
            border-radius: 20px;
            padding: 15px;
            max-width: 1400px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            margin: 20px auto;
        }
        
        .container.welcome-mode {
            max-width: 550px;
        }
        
        .main-content {
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            gap: 15px;
            align-items: start;
        }
        
        .main-content.welcome-mode {
            grid-template-columns: 1fr;
            max-width: 500px;
            margin: 0 auto;
        }
        
        .left-panel, .right-panel {
            min-width: 0;
        }
        
        .right-panel {
            position: sticky;
            top: 20px;
        }
        
        .right-panel.hidden {
            display: none;
        }
        
        .upload-zone {
            border: 2px dashed #667eea;
            border-radius: 8px;
            padding: 20px 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: #f8f9ff;
        }
        
        .upload-zone.welcome-mode {
            padding: 40px 25px;
            border-width: 3px;
        }
        
        .upload-zone:hover {
            border-color: #764ba2;
            background: #f0f1ff;
        }
        
        .upload-zone.dragover {
            background: #e8e9ff;
            border-color: #764ba2;
            transform: scale(1.02);
        }
        
        .upload-icon {
            font-size: 48px;
            margin-bottom: 12px;
        }
        
        .upload-zone h3 {
            font-size: 18px;
            color: #333;
            margin-bottom: 8px;
        }
        
        .upload-zone p {
            color: #666;
            font-size: 13px;
        }
        
        #fileInput {
            display: none;
        }
        
        .controls {
            display: none;
            margin-top: 15px;
        }
        
        .control-group {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 12px;
        }
        
        .control-group label {
            display: block;
            color: #333;
            font-weight: 500;
            margin-bottom: 8px;
            font-size: 13px;
        }
        
        input[type="range"] {
            width: 100%;
            height: 6px;
            border-radius: 3px;
            background: #ddd;
            outline: none;
            -webkit-appearance: none;
        }
        
        input[type="range"]::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: #667eea;
            cursor: pointer;
            box-shadow: 0 2px 6px rgba(102, 126, 234, 0.4);
        }
        
        input[type="range"]::-moz-range-thumb {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: #667eea;
            cursor: pointer;
            border: none;
        }
        
        input[type="text"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 13px;
        }
        
        input[type="checkbox"] {
            margin-right: 6px;
        }
        
        .dimension-label {
            color: #667eea;
            font-weight: 600;
        }
        
        button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 8px;
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }
        
        button:active {
            transform: translateY(0);
        }
        
        .live-preview {
            padding: 8px;
            background: #f8f9ff;
            border-radius: 8px;
            text-align: center;
            display: none;
            height: calc(100vh - 30px);
            max-height: 800px;
        }
        
        .live-preview.active {
            display: flex;
            flex-direction: column;
        }
        
        .live-preview-header {
            font-size: 11px;
            color: #888;
            margin-bottom: 4px;
            flex-shrink: 0;
        }
        
        .preview-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            border-radius: 6px;
            padding: 8px;
            overflow: hidden;
        }
        
        .live-preview img {
            max-width: 100%;
            max-height: 100%;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border: 1px solid #667eea;
            object-fit: contain;
            cursor: pointer;
        }
        
        .info {
            margin-top: 4px;
            padding: 6px;
            background: #e8f5e9;
            border-radius: 4px;
            color: #2e7d32;
            font-size: 11px;
            font-weight: 600;
            flex-shrink: 0;
        }
        
        .features {
            background: linear-gradient(135deg, #f8f9ff 0%, #e8e9ff 100%);
            border-radius: 12px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .features h4 {
            color: #667eea;
            font-size: 16px;
            margin-bottom: 15px;
            text-align: center;
        }
        
        .features ul {
            list-style: none;
            font-size: 14px;
            color: #666;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        
        .features li {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .features li span {
            font-size: 18px;
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
            
            .main-content {
                grid-template-columns: 1fr;
            }
            
            .right-panel {
                position: static;
            }
            
            .features ul {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Header inspiré du header.php -->
    <header class="site-header">
        <div class="header-container">
            <div class="header-content">
                <div class="header-left">
                    <a href="index.php" class="site-logo">🧘 Zenu</a>
                    <span class="page-title">Convertisseur d'Images</span>
                </div>
                
                <nav class="header-nav">
                    <a href="index.php" class="nav-link">
                        🏠 Accueil
                    </a>
                    <a href="convertisseur.php" class="nav-link active">
                        🖼️ Convertisseur
                    </a>
                    
                    <?php if ($user): ?>
                        <a href="dashboard.php" class="nav-link">
                            📊 Mes images
                        </a>
                        <span class="user-info">👤 <?= htmlspecialchars($user['username']) ?></span>
                        <form action="logout.php" method="POST" style="display: inline;">
                            <button type="submit" class="btn-logout">Déconnexion</button>
                        </form>
                    <?php else: ?>
                        <a href="register.php" class="nav-link">
                            ✨ S'inscrire
                        </a>
                        <a href="login.php" class="btn-login">Se connecter</a>
                    <?php endif; ?>
                </nav>
            </div>
        </div>
    </header>

    <div class="container welcome-mode" id="mainContainer">
        <div class="main-content welcome-mode" id="mainContent">
            <div class="left-panel">
                <div class="upload-zone welcome-mode" id="uploadZone">
                    <div class="upload-icon">📤</div>
                    <h3>Glissez une image ici</h3>
                    <p>ou cliquez pour sélectionner</p>
                    <p style="margin-top: 8px; font-size: 11px; color: #999;">PNG, JPG, WEBP, GIF</p>
                </div>
                <input type="file" id="fileInput" accept="image/*">
                
                <div class="controls" id="controls">
                    <div class="control-group">
                        <label>📏 Largeur : <span class="dimension-label" id="widthValue">0</span> px</label>
                        <input type="range" id="widthSlider" min="1" max="4000" value="1000">
                    </div>
                    
                    <div class="control-group">
                        <label>📐 Hauteur : <span class="dimension-label" id="heightValue">0</span> px</label>
                        <input type="range" id="heightSlider" min="1" max="4000" value="1000">
                    </div>
                    
                    <div class="control-group">
                        <label style="display: flex; align-items: center; font-size: 13px;">
                           <input type="checkbox" id="maintainAspect" checked> Maintenir les proportions
                        </label>
                    </div>
                    
                    <div class="control-group">
                        <label>✨ Qualité JPG : <span class="dimension-label" id="qualityValue">100%</span></label>
                        <input type="range" class="quality-slider" id="qualitySlider" min="1" max="100" value="100">
                    </div>
                    
                    <div class="control-group">
                        <label>📝 Nom du fichier :</label>
                        <input type="text" id="fileNameInput" placeholder="nom-image">
                    </div>
                    
                    <button id="convertBtn">⬇️ Télécharger en JPG</button>
                </div>
                
                <div class="features" id="featuresSection">
                    <h4>Convertisseur d'Images JPG</h4>
                    <ul>
                        <li><span>📏</span><div>Redimensionnement précis</div></li>
                        <li><span>✨</span><div>Qualité ajustable</div></li>
                        <li><span>⚡</span><div>Aperçu temps réel</div></li>
                        <li><span>🔒</span><div>100% local et sécurisé</div></li>
                        <li><span>🚀</span><div>Aucun upload serveur</div></li>
                        <li><span>🆓</span><div>Gratuit et illimité</div></li>
                    </ul>
                </div>
            </div>
            
            <div class="right-panel hidden" id="rightPanel">
                <div class="live-preview" id="livePreview">
                    <div class="live-preview-header">
                        Aperçu · <span class="dimension-label" id="liveSize">0 × 0</span>
                    </div>
                    <div class="preview-container">
                        <img id="livePreviewImg" alt="Aperçu">
                    </div>
                    <div class="info" id="info" style="display: none;"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let originalImage = null;
        let originalWidth = 0;
        let originalHeight = 0;
        let isUpdating = false;
        let originalFileSize = 0;
        let originalFileName = 'image';
        
        const uploadZone = document.getElementById('uploadZone');
        const fileInput = document.getElementById('fileInput');
        const controls = document.getElementById('controls');
        const livePreview = document.getElementById('livePreview');
        const livePreviewImg = document.getElementById('livePreviewImg');
        const widthSlider = document.getElementById('widthSlider');
        const heightSlider = document.getElementById('heightSlider');
        const widthValue = document.getElementById('widthValue');
        const heightValue = document.getElementById('heightValue');
        const qualitySlider = document.getElementById('qualitySlider');
        const qualityValue = document.getElementById('qualityValue');
        const liveSize = document.getElementById('liveSize');
        const maintainAspect = document.getElementById('maintainAspect');
        const convertBtn = document.getElementById('convertBtn');
        const fileNameInput = document.getElementById('fileNameInput');
        const rightPanel = document.getElementById('rightPanel');
        const mainContainer = document.getElementById('mainContainer');
        const mainContent = document.getElementById('mainContent');
        const featuresSection = document.getElementById('featuresSection');
        
        // Click sur la zone de upload
        uploadZone.addEventListener('click', () => fileInput.click());
        
        // Drag & Drop
        uploadZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadZone.classList.add('dragover');
        });
        
        uploadZone.addEventListener('dragleave', () => {
            uploadZone.classList.remove('dragover');
        });
        
        uploadZone.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadZone.classList.remove('dragover');
            const file = e.dataTransfer.files[0];
            if (file && file.type.startsWith('image/')) {
                handleFile(file);
            }
        });
        
        // Sélection de fichier
        fileInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                handleFile(file);
            }
        });
        
        function handleFile(file) {
            originalFileName = file.name.split('.')[0];
            fileNameInput.value = originalFileName;
            originalFileSize = file.size;
            
            const reader = new FileReader();
            reader.onload = (e) => {
                originalImage = new Image();
                originalImage.onload = () => {
                    originalWidth = originalImage.width;
                    originalHeight = originalImage.height;
                    
                    widthSlider.max = originalWidth;
                    heightSlider.max = originalHeight;
                    widthSlider.value = originalWidth;
                    heightSlider.value = originalHeight;
                    widthValue.textContent = originalWidth;
                    heightValue.textContent = originalHeight;
                    
                    // Passer en mode travail
                    mainContainer.classList.remove('welcome-mode');
                    mainContent.classList.remove('welcome-mode');
                    uploadZone.classList.remove('welcome-mode');
                    rightPanel.classList.remove('hidden');
                    controls.style.display = 'block';
                    livePreview.classList.add('active');
                    featuresSection.style.display = 'block';
                    
                    updatePreview();
                };
                originalImage.src = e.target.result;
            };
            reader.readAsDataURL(file);
        }
        
        function updatePreview() {
            if (!originalImage || isUpdating) return;
            
            const width = parseInt(widthSlider.value);
            const height = parseInt(heightSlider.value);
            const quality = parseInt(qualitySlider.value) / 100;
            
            const canvas = document.createElement('canvas');
            canvas.width = width;
            canvas.height = height;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(originalImage, 0, 0, width, height);
            
            livePreviewImg.src = canvas.toDataURL('image/jpeg', quality);
            liveSize.textContent = `${width} × ${height}`;
            
            // Calcul taille approximative
            canvas.toBlob((blob) => {
                const newSize = blob.size;
                const reduction = ((1 - newSize / originalFileSize) * 100).toFixed(0);
                const info = document.getElementById('info');
                info.textContent = `${(newSize / 1024).toFixed(0)} KB · ${reduction}% de réduction`;
                info.style.display = 'block';
            }, 'image/jpeg', quality);
        }
        
        widthSlider.addEventListener('input', () => {
            const width = parseInt(widthSlider.value);
            widthValue.textContent = width;
            
            if (maintainAspect.checked && !isUpdating) {
                isUpdating = true;
                const height = Math.round(width * (originalHeight / originalWidth));
                heightSlider.value = height;
                heightValue.textContent = height;
                isUpdating = false;
            }
            
            updatePreview();
        });
        
        heightSlider.addEventListener('input', () => {
            const height = parseInt(heightSlider.value);
            heightValue.textContent = height;
            
            if (maintainAspect.checked && !isUpdating) {
                isUpdating = true;
                const width = Math.round(height * (originalWidth / originalHeight));
                widthSlider.value = width;
                widthValue.textContent = width;
                isUpdating = false;
            }
            
            updatePreview();
        });
        
        qualitySlider.addEventListener('input', () => {
            qualityValue.textContent = qualitySlider.value + '%';
            updatePreview();
        });
        
        maintainAspect.addEventListener('change', updatePreview);
        
        convertBtn.addEventListener('click', () => {
            const width = parseInt(widthSlider.value);
            const height = parseInt(heightSlider.value);
            const quality = parseInt(qualitySlider.value) / 100;
            const fileName = fileNameInput.value || originalFileName;
            
            const canvas = document.createElement('canvas');
            canvas.width = width;
            canvas.height = height;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(originalImage, 0, 0, width, height);
            
            canvas.toBlob((blob) => {
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `${fileName}.jpg`;
                a.click();
                URL.revokeObjectURL(url);
            }, 'image/jpeg', quality);
        });
    </script>
</body>
</html>