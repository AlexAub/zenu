<?php
require_once 'config.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Convertisseur Privé - Zenu</title>
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
            padding: 10px;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .container {
            background: white;
            border-radius: 20px;
            padding: 15px;
            max-width: 1400px;
            width: 100%;
            margin: 0 auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        .container.welcome-mode {
            max-width: 550px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .header h1 {
            color: #667eea;
            font-size: 18px;
        }
        
        .header a {
            color: #667eea;
            text-decoration: none;
            font-size: 13px;
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
        
        .upload-zone.welcome-mode .upload-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        
        .upload-zone.welcome-mode h3 {
            font-size: 18px;
            margin-bottom: 8px;
        }
        
        .upload-zone.welcome-mode p {
            font-size: 14px;
        }
        
        .upload-zone:hover {
            border-color: #764ba2;
            background: #f0f1ff;
        }
        
        .upload-zone.dragover {
            background: #e8e9ff;
            border-color: #764ba2;
        }
        
        .upload-icon {
            font-size: 28px;
            margin-bottom: 5px;
        }
        
        .upload-zone h3 {
            font-size: 15px;
            margin-bottom: 3px;
        }
        
        .upload-zone p {
            font-size: 12px;
            margin: 0;
        }
        
        input[type="file"] {
            display: none;
        }
        
        .controls {
            margin-top: 15px;
            display: none;
        }
        
        .controls.active {
            display: block;
        }
        
        .control-group {
            margin-bottom: 12px;
        }
        
        label {
            display: block;
            margin-bottom: 4px;
            color: #555;
            font-weight: 600;
            font-size: 13px;
        }
        
        input[type="range"] {
            width: 100%;
            margin: 8px 0;
        }
        
        .dimension-label {
            display: inline-block;
            min-width: 60px;
            color: #667eea;
            font-weight: bold;
        }
        
        button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            width: 100%;
            margin-top: 10px;
        }
        
        button:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }
        
        button:active {
            transform: translateY(0);
        }
        
        button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
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
            margin-top: 8px;
            padding: 10px;
            background: #e8f5e9;
            border-radius: 6px;
            color: #2e7d32;
            font-size: 11px;
            font-weight: 600;
            flex-shrink: 0;
            display: none;
            text-align: left;
        }
        
        .info.active {
            display: block;
        }
        
        .url-container {
            margin-top: 8px;
            padding: 8px;
            background: #fff3e0;
            border-radius: 6px;
            border: 1px solid #ff9800;
        }
        
        .url-container strong {
            color: #e65100;
            display: block;
            margin-bottom: 5px;
            font-size: 11px;
        }
        
        .url-input-group {
            display: flex;
            gap: 5px;
        }
        
        .url-container input {
            flex: 1;
            padding: 6px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 11px;
            min-width: 0;
        }
        
        .btn-copy-url {
            background: #ff9800;
            padding: 6px 12px;
            font-size: 11px;
            margin: 0;
            width: auto;
            white-space: nowrap;
        }
        
        .quota-info {
            background: #e3f2fd;
            padding: 10px;
            border-radius: 6px;
            font-size: 12px;
            color: #1565c0;
            margin-top: 10px;
        }
        
        .error {
            background: #ffebee;
            color: #c62828;
            padding: 10px;
            border-radius: 6px;
            font-size: 12px;
            margin-top: 10px;
            display: none;
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
            padding: 0;
            position: relative;
            display: flex;
            align-items: flex-start;
            gap: 8px;
        }
        
        .features li span {
            font-size: 18px;
            flex-shrink: 0;
        }
        
        @media (max-width: 1024px) {
            .main-content,
            .main-content.welcome-mode {
                grid-template-columns: 1fr;
            }
            
            .right-panel {
                position: relative;
                top: 0;
            }
            
            .live-preview {
                height: auto;
                max-height: 500px;
            }
            
            .features ul {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 600px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .header h1 {
                font-size: 16px;
            }
            
            .features ul {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container" id="container">
        <div class="header">
            <h1>🔒 Convertisseur Privé - Sauvegarde Cloud</h1>
            <a href="dashboard.php">← Mes images</a>
        </div>
        
        <div class="main-content" id="mainContent">
            <div class="left-panel">
                <div class="upload-zone" id="uploadZone">
                    <div class="upload-icon">📁</div>
                    <h3>Glissez votre image ici</h3>
                    <p>ou cliquez pour sélectionner</p>
                    <p style="margin-top: 10px; color: #888;">
                        PNG, JPG, WebP, GIF, BMP (taille illimitée)
                    </p>
                </div>
                
                <input type="file" id="fileInput" accept="image/*">
                
                <div class="controls" id="controls">
                    <div class="control-group">
                        <label>📏 Largeur : <span class="dimension-label" id="widthValue">0</span> px</label>
                        <input type="range" id="widthSlider" min="10" max="4000" value="800">
                        
                        <label style="margin-top: 12px;">📏 Hauteur : <span class="dimension-label" id="heightValue">0</span> px</label>
                        <input type="range" id="heightSlider" min="10" max="4000" value="600">
                        
                        <label style="margin-top: 8px;">
                            <input type="checkbox" id="maintainAspect" checked> Maintenir les proportions
                        </label>
                    </div>
                    
                    <div class="control-group">
                        <label>✨ Qualité : <span class="dimension-label" id="qualityValue">100%</span></label>
                        <input type="range" id="qualitySlider" min="1" max="100" value="100">
                    </div>
                    
                    <button id="saveBtn">💾 Sauvegarder sur le cloud</button>
                    
                    <div id="quotaInfo" class="quota-info"></div>
                    <div id="errorMsg" class="error"></div>
                </div>
                
                <div class="features" id="featuresSection">
                    <h4>Convertisseur d'Images Cloud</h4>
                    <ul>
                        <li><span>💾</span><div>Sauvegarde automatique sur le cloud</div></li>
                        <li><span>🔗</span><div>URL directe générée</div></li>
                        <li><span>🌍</span><div>Accessible partout</div></li>
                        <li><span>📊</span><div>500 images · 500 MB max</div></li>
                        <li><span>⚡</span><div>Aperçu temps réel</div></li>
                        <li><span>🎯</span><div>Max 2 MB par image</div></li>
                    </ul>
                </div>
            </div>
            
            <div class="right-panel">
                <div class="live-preview" id="livePreview">
                    <div class="live-preview-header">
                        Aperçu · <span class="dimension-label" id="liveSize">0 × 0</span>
                    </div>
                    <div class="preview-container">
                        <img id="livePreviewImg" alt="Aperçu">
                    </div>
                    <div class="info" id="info"></div>
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
        let originalFileName = 'image'; // Nom sans extension
        
        const uploadZone = document.getElementById('uploadZone');
        const fileInput = document.getElementById('fileInput');
        const controls = document.getElementById('controls');
        const livePreview = document.getElementById('livePreview');
        const livePreviewImg = document.getElementById('livePreviewImg');
        const widthSlider = document.getElementById('widthSlider');
        const heightSlider = document.getElementById('heightSlider');
        const widthValue = document.getElementById('widthValue');
        const heightValue = document.getElementById('heightValue');
        const liveSize = document.getElementById('liveSize');
        const maintainAspect = document.getElementById('maintainAspect');
        const qualitySlider = document.getElementById('qualitySlider');
        const qualityValue = document.getElementById('qualityValue');
        const saveBtn = document.getElementById('saveBtn');
        const info = document.getElementById('info');
        const quotaInfo = document.getElementById('quotaInfo');
        const errorMsg = document.getElementById('errorMsg');
        const container = document.getElementById('container');
        const mainContent = document.getElementById('mainContent');
        const featuresSection = document.getElementById('featuresSection');
        
        // Mode welcome par défaut
        container.classList.add('welcome-mode');
        mainContent.classList.add('welcome-mode');
        uploadZone.classList.add('welcome-mode');
        
        loadQuotas();
        
        async function loadQuotas() {
            try {
                const response = await fetch('get-quotas.php');
                const data = await response.json();
                quotaInfo.innerHTML = `
                    📊 <strong>${data.used_space}</strong> / 500 MB · 
                    📁 <strong>${data.image_count}</strong> / 500 images
                `;
            } catch(e) {
                console.error('Erreur chargement quotas:', e);
            }
        }
        
        uploadZone.addEventListener('click', () => fileInput.click());
        
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
        
        fileInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                handleFile(file);
            }
        });
        
        function handleFile(file) {
            // On accepte toutes les tailles au chargement
            // La vérification se fera à la sauvegarde
            originalFileSize = file.size;
            originalFileName = file.name.replace(/\.[^/.]+$/, ""); // Garder le nom sans extension
            hideError();
            loadImage(file);
        }
        
        function loadImage(file) {
            const reader = new FileReader();
            reader.onload = (e) => {
                const img = new Image();
                img.onload = () => {
                    originalImage = img;
                    originalWidth = img.width;
                    originalHeight = img.height;
                    
                    widthSlider.max = originalWidth;
                    heightSlider.max = originalHeight;
                    widthSlider.value = originalWidth;
                    heightSlider.value = originalHeight;
                    
                    // Passer en mode application
                    container.classList.remove('welcome-mode');
                    mainContent.classList.remove('welcome-mode');
                    uploadZone.classList.remove('welcome-mode');
                    featuresSection.style.display = 'none';
                    
                    controls.classList.add('active');
                    livePreview.classList.add('active');
                    
                    updatePreview();
                };
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        }
        
        function updatePreview() {
            if (!originalImage) return;
            
            const width = parseInt(widthSlider.value);
            const height = parseInt(heightSlider.value);
            
            widthValue.textContent = width;
            heightValue.textContent = height;
            liveSize.textContent = `${width} × ${height} px`;
            
            const canvas = document.createElement('canvas');
            canvas.width = width;
            canvas.height = height;
            
            const ctx = canvas.getContext('2d');
            ctx.imageSmoothingEnabled = true;
            ctx.imageSmoothingQuality = 'high';
            ctx.drawImage(originalImage, 0, 0, width, height);
            
            livePreviewImg.src = canvas.toDataURL('image/jpeg', qualitySlider.value / 100);
        }
        
        widthSlider.addEventListener('input', () => {
            if (maintainAspect.checked && originalWidth > 0 && !isUpdating) {
                isUpdating = true;
                const ratio = originalHeight / originalWidth;
                heightSlider.value = Math.round(widthSlider.value * ratio);
                isUpdating = false;
            }
            updatePreview();
        });
        
        heightSlider.addEventListener('input', () => {
            if (maintainAspect.checked && originalHeight > 0 && !isUpdating) {
                isUpdating = true;
                const ratio = originalWidth / originalHeight;
                widthSlider.value = Math.round(heightSlider.value * ratio);
                isUpdating = false;
            }
            updatePreview();
        });
        
        qualitySlider.addEventListener('input', () => {
            qualityValue.textContent = qualitySlider.value + '%';
            updatePreview();
        });
        
        livePreviewImg.addEventListener('click', () => {
            if (!originalImage) return;
            
            const width = parseInt(widthSlider.value);
            const height = parseInt(heightSlider.value);
            const quality = qualitySlider.value / 100;
            
            const canvas = document.createElement('canvas');
            canvas.width = width;
            canvas.height = height;
            
            const ctx = canvas.getContext('2d');
            ctx.imageSmoothingEnabled = true;
            ctx.imageSmoothingQuality = 'high';
            ctx.drawImage(originalImage, 0, 0, width, height);
            
            canvas.toBlob((blob) => {
                const url = URL.createObjectURL(blob);
                window.open(url, '_blank');
            }, 'image/jpeg', quality);
        });
        
        saveBtn.addEventListener('click', async () => {
            if (!originalImage) return;
            
            saveBtn.disabled = true;
            saveBtn.textContent = '⏳ Sauvegarde...';
            hideError();
            info.classList.remove('active');
            
            const width = parseInt(widthSlider.value);
            const height = parseInt(heightSlider.value);
            const quality = qualitySlider.value / 100;
            
            const canvas = document.createElement('canvas');
            canvas.width = width;
            canvas.height = height;
            
            const ctx = canvas.getContext('2d');
            ctx.imageSmoothingEnabled = true;
            ctx.imageSmoothingQuality = 'high';
            ctx.drawImage(originalImage, 0, 0, width, height);
            
            canvas.toBlob(async (blob) => {
                // Vérifier la taille du blob final (2MB max pour sauvegarde)
                if (blob.size > 2 * 1024 * 1024) {
                    const sizeMB = (blob.size / (1024 * 1024)).toFixed(2);
                    showError(`Image convertie trop volumineuse (${sizeMB} MB). La sauvegarde est limitée à 2 MB. Réduisez la qualité ou les dimensions.`);
                    saveBtn.disabled = false;
                    saveBtn.textContent = '💾 Sauvegarder sur le cloud';
                    return;
                }
                
                const formData = new FormData();
                formData.append('image', blob, `${originalFileName}.jpg`);
                formData.append('width', width);
                formData.append('height', height);
                formData.append('original_filename', originalFileName);
                
                try {
                    const response = await fetch('upload-image.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        const size = (blob.size / 1024).toFixed(2);
                        info.innerHTML = `
                            ✅ <strong>Image sauvegardée !</strong><br>
                            ${width} × ${height} px · ${size} KB
                            <div class="url-container">
                                <strong>🔗 URL de votre image :</strong>
                                <div class="url-input-group">
                                    <input type="text" id="imageUrl" value="${result.url}" readonly onclick="this.select()">
                                    <button class="btn-copy-url" onclick="copyUrl()">📋 Copier</button>
                                </div>
                            </div>
                        `;
                        info.classList.add('active');
                        loadQuotas();
                    } else {
                        showError(result.error || 'Erreur lors de la sauvegarde');
                    }
                } catch(e) {
                    showError('Erreur réseau : ' + e.message);
                }
                
                saveBtn.disabled = false;
                saveBtn.textContent = '💾 Sauvegarder sur le cloud';
            }, 'image/jpeg', quality);
        });
        
        function copyUrl() {
            const urlInput = document.getElementById('imageUrl');
            urlInput.select();
            
            navigator.clipboard.writeText(urlInput.value).then(() => {
                alert('✅ URL copiée !');
            }).catch(() => {
                document.execCommand('copy');
                alert('✅ URL copiée !');
            });
        }
        
        function showError(message) {
            errorMsg.textContent = '❌ ' + message;
            errorMsg.style.display = 'block';
        }
        
        function hideError() {
            errorMsg.style.display = 'none';
        }
    </script>
</body>
</html>