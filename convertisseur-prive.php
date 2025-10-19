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
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
        }
        
        .container {
            background: white;
            border-radius: 20px;
            padding: 15px;
            max-width: 1400px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
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
            font-size: 20px;
        }
        
        .header a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
        }
        
        .main-content {
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            gap: 15px;
            align-items: start;
        }
        
        .left-panel {
            display: flex;
            flex-direction: column;
        }
        
        .right-panel {
            position: sticky;
            top: 20px;
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
        
        input[type="text"] {
            width: 100%;
            padding: 8px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 13px;
            transition: border 0.3s;
        }
        
        input[type="text"]:focus {
            outline: none;
            border-color: #667eea;
        }
        
        input[type="range"] {
            width: 100%;
            margin: 10px 0;
        }
        
        .dimension-label {
            display: inline-block;
            min-width: 80px;
            color: #667eea;
            font-weight: bold;
        }
        
        button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            width: 100%;
            margin-top: 10px;
        }
        
        button:hover {
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
            padding: 10px;
            background: #e8f5e9;
            border-radius: 6px;
            color: #2e7d32;
            font-size: 12px;
            font-weight: 600;
            flex-shrink: 0;
            display: none;
        }
        
        .info.active {
            display: block;
        }
        
        .url-display {
            margin-top: 8px;
            padding: 10px;
            background: #fff3e0;
            border-radius: 6px;
            border: 1px solid #ff9800;
            font-size: 11px;
            word-break: break-all;
            text-align: left;
        }
        
        .url-display strong {
            color: #e65100;
            display: block;
            margin-bottom: 5px;
        }
        
        .url-display input {
            width: 100%;
            padding: 6px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 11px;
            margin-top: 5px;
        }
        
        .btn-copy {
            background: #ff9800;
            padding: 6px 15px;
            font-size: 12px;
            margin-top: 5px;
            width: auto;
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
        }
        
        @media (max-width: 1024px) {
            .main-content {
                grid-template-columns: 1fr;
            }
            
            .right-panel {
                position: relative;
                top: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔒 Convertisseur Privé</h1>
            <a href="dashboard.php">← Retour au dashboard</a>
        </div>
        
        <div class="main-content">
            <div class="left-panel">
                <div class="upload-zone" id="uploadZone">
                    <div class="upload-icon">📁</div>
                    <h3>Glissez votre image ici</h3>
                    <p>ou cliquez pour sélectionner</p>
                    <p style="margin-top: 10px; color: #888;">
                        Formats acceptés : PNG, JPG, WebP, GIF, BMP (max 2MB)
                    </p>
                </div>
                
                <input type="file" id="fileInput" accept="image/*">
                
                <div class="controls" id="controls">
                    <div class="control-group">
                        <label>📏 Largeur : <span class="dimension-label" id="widthValue">0</span> pixels</label>
                        <input type="range" id="widthSlider" min="10" max="4000" value="800">
                        
                        <label style="margin-top: 15px;">📏 Hauteur : <span class="dimension-label" id="heightValue">0</span> pixels</label>
                        <input type="range" id="heightSlider" min="10" max="4000" value="600">
                        
                        <label style="margin-top: 10px;">
                            <input type="checkbox" id="maintainAspect" checked> Maintenir les proportions
                        </label>
                    </div>
                    
                    <div class="control-group">
                        <label>✨ Qualité JPG : <span class="dimension-label" id="qualityValue">100%</span></label>
                        <input type="range" id="qualitySlider" min="1" max="100" value="100">
                    </div>
                    
                    <div class="control-group">
                        <label>📝 Nom du fichier :</label>
                        <input type="text" id="fileNameInput" placeholder="nom-image">
                    </div>
                    
                    <button id="saveBtn">💾 Sauvegarder l'image</button>
                    
                    <div id="quotaInfo" class="quota-info"></div>
                    <div id="errorMsg" class="error" style="display: none;"></div>
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
        let originalFileName = 'image';
        let originalFileSize = 0;
        
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
        const fileNameInput = document.getElementById('fileNameInput');
        const quotaInfo = document.getElementById('quotaInfo');
        const errorMsg = document.getElementById('errorMsg');
        
        // Charger les quotas
        loadQuotas();
        
        async function loadQuotas() {
            try {
                const response = await fetch('get-quotas.php');
                const data = await response.json();
                quotaInfo.innerHTML = `
                    📊 Espace utilisé : <strong>${data.used_space}</strong> / 500 MB<br>
                    📁 Images : <strong>${data.image_count}</strong> / 500
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
            // Vérifier la taille (2MB max)
            if (file.size > 2 * 1024 * 1024) {
                showError('L\'image ne doit pas dépasser 2 MB');
                return;
            }
            
            originalFileName = file.name.replace(/\.[^/.]+$/, "");
            originalFileSize = file.size;
            fileNameInput.value = originalFileName;
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
                    
                    controls.classList.add('active');
                    livePreview.classList.add('active');
                    
                    hideError();
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
            liveSize.textContent = `${width} × ${height} pixels`;
            
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
            saveBtn.textContent = '⏳ Sauvegarde en cours...';
            hideError();
            
            const width = parseInt(widthSlider.value);
            const height = parseInt(heightSlider.value);
            const quality = qualitySlider.value / 100;
            const fileName = fileNameInput.value.trim() || 'image';
            
            const canvas = document.createElement('canvas');
            canvas.width = width;
            canvas.height = height;
            
            const ctx = canvas.getContext('2d');
            ctx.imageSmoothingEnabled = true;
            ctx.imageSmoothingQuality = 'high';
            ctx.drawImage(originalImage, 0, 0, width, height);
            
            canvas.toBlob(async (blob) => {
                const formData = new FormData();
                formData.append('image', blob, `${fileName}.jpg`);
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
                            ✅ Image sauvegardée !<br>
                            ${width} × ${height} px · ${size} KB
                            <div class="url-display">
                                <strong>🔗 URL de l'image :</strong>
                                <input type="text" id="imageUrl" value="${result.url}" readonly onclick="this.select()">
                                <button class="btn-copy" onclick="copyUrl()">📋 Copier</button>
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
                saveBtn.textContent = '💾 Sauvegarder l\'image';
            }, 'image/jpeg', quality);
        });
        
        function copyUrl() {
            const urlInput = document.getElementById('imageUrl');
            urlInput.select();
            document.execCommand('copy');
            alert('URL copiée !');
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