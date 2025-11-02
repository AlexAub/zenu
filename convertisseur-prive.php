<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$pageTitle = "Convertisseur";

// Récupérer les quotas de l'utilisateur
$stmt = $pdo->prepare("SELECT COUNT(*) as count, COALESCE(SUM(file_size), 0) as total_size FROM images WHERE user_id = ?");
$stmt->execute([$user_id]);
$quotas = $stmt->fetch();

// Inclure le header
require_once 'header.php';
?>

		<link rel="stylesheet" href="assets/css/convertisseur-prive-responsive.css">
<!DOCTYPE html>
    <title>Convertisseur Cloud - Qualité Améliorée</title>
    <style>
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
            margin: 0;
        }
        
        .container {
            max-width: 1400px;
            margin: 20px auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .main-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
            min-height: 600px;
        }
        
        .left-panel {
            padding: 40px;
            border-right: 1px solid #e0e0e0;
        }
        
        .right-panel {
            padding: 40px;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .upload-zone {
            border: 3px dashed #667eea;
            border-radius: 16px;
            padding: 60px 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: #f8f9ff;
        }
        
        .upload-zone:hover {
            border-color: #764ba2;
            background: #f0f3ff;
        }
        
        .upload-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        
        .upload-zone h3 {
            font-size: 20px;
            color: #333;
            margin-bottom: 10px;
        }
        
        .upload-zone p {
            color: #666;
            font-size: 14px;
        }
        
        #fileInput {
            display: none;
        }
        
        .controls {
            display: none;
            margin-top: 30px;
        }
        
        .control-group {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        
        .control-group label {
            display: block;
            color: #333;
            font-weight: 500;
            margin-bottom: 10px;
            font-size: 14px;
        }
        
        input[type="range"] {
            width: 100%;
            height: 8px;
            border-radius: 4px;
            background: #ddd;
            outline: none;
            -webkit-appearance: none;
        }
        
        input[type="range"]::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #667eea;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.4);
        }
        
        input[type="range"]::-moz-range-thumb {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #667eea;
            cursor: pointer;
            border: none;
        }
        
        .dimension-label {
            color: #667eea;
            font-weight: 600;
        }
        
        button {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }
        
        button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        #currentFileName {
            color: #333;
            max-width: 250px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            display: inline-block;
            vertical-align: middle;
        }
        
        .live-preview {
            width: 100%;
            max-width: 600px;
            display: none;
        }
        
        .live-preview-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 20px;
            border-radius: 12px 12px 0 0;
            font-weight: 600;
            font-size: 14px;
            border-bottom: 2px solid #5568d3;
            text-align: center;
        }
        
        .preview-container {
            background: 
                linear-gradient(45deg, #f0f0f0 25%, transparent 25%),
                linear-gradient(-45deg, #f0f0f0 25%, transparent 25%),
                linear-gradient(45deg, transparent 75%, #f0f0f0 75%),
                linear-gradient(-45deg, transparent 75%, #f0f0f0 75%);
            background-size: 20px 20px;
            background-position: 0 0, 0 10px, 10px -10px, -10px 0px;
            background-color: white;
            padding: 20px;
            border-radius: 0 0 12px 12px;
            min-height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: auto;
        }
        
        #livePreviewImg {
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            display: block;
            /* L'image s'affichera à sa taille réelle */
        }
        
        .info {
            margin-top: 20px;
            padding: 20px;
            background: #e8f5e9;
            border-radius: 12px;
            border-left: 4px solid #4caf50;
            font-size: 14px;
            line-height: 1.8;
            display: none;
        }
        
        .info.active {
            display: block;
        }
        
        .url-container {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid rgba(76, 175, 80, 0.3);
        }
        
        .url-input-group {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        
        .url-input-group input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-family: monospace;
            font-size: 13px;
        }
        
        .btn-copy-url {
            width: auto;
            padding: 10px 20px;
            margin: 0;
            background: #4caf50;
            font-size: 14px;
        }
        
        .features {
            margin-top: 40px;
            padding: 25px;
            background: linear-gradient(135deg, #f8f9ff 0%, #f0f3ff 100%);
            border-radius: 12px;
        }
        
        .features h4 {
            color: #667eea;
            margin-bottom: 15px;
            font-size: 16px;
        }
        
        .features ul {
            list-style: none;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }
        
        .features li {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            color: #555;
        }
        
        .features li span {
            font-size: 20px;
        }
        
        .quota-info {
            margin-top: 20px;
            padding: 15px;
            background: #fff3cd;
            border-radius: 8px;
            font-size: 13px;
            text-align: center;
        }
        
        .error {
            margin-top: 15px;
            padding: 15px;
            background: #ffebee;
            color: #c62828;
            border-radius: 8px;
            font-size: 14px;
            display: none;
        }
        
        @media (max-width: 1024px) {
            .main-content {
                grid-template-columns: 1fr;
            }
            
            .left-panel {
                border-right: none;
                border-bottom: 1px solid #e0e0e0;
            }
            
            .features ul {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .container {
                border-radius: 0;
            }
            
            body {
                padding: 0;
            }
            
            .left-panel, .right-panel {
                padding: 20px;
            }
            
            .upload-zone {
                padding: 40px 20px;
            }
            
            .upload-icon {
                font-size: 48px;
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
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
                        <div style="font-size: 13px; color: #666;">
                            📁 <strong id="currentFileName">Image chargée</strong>
                        </div>
                        <button id="changeImageBtn" style="width: auto; padding: 8px 16px; margin: 0; font-size: 13px; background: #6c757d;">
                            🔄 Changer d'image
                        </button>
                    </div>
                    
                    <div class="control-group">
                        <div style="background: #e3f2fd; padding: 12px; border-radius: 8px; margin-bottom: 15px; font-size: 13px; color: #1976d2;">
                            ℹ️ <strong>Réduction uniquement</strong> - L'image ne peut pas être agrandie au-delà de sa taille originale
                        </div>
                        
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
                    <h4>✨ Convertisseur d'Images Cloud - Qualité Premium</h4>
                    <ul>
                        <li><span>🎯</span><div>Algorithme de redimensionnement avancé</div></li>
                        <li><span>✨</span><div>Netteté optimisée automatiquement</div></li>
                        <li><span>📉</span><div>Réduction uniquement (pas d'agrandissement)</div></li>
                        <li><span>💾</span><div>Sauvegarde automatique sur le cloud</div></li>
                        <li><span>🔗</span><div>URL directe générée</div></li>
                        <li><span>📊</span><div>500 images · 500 MB max</div></li>
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
        const liveSize = document.getElementById('liveSize');
        const maintainAspect = document.getElementById('maintainAspect');
        const qualitySlider = document.getElementById('qualitySlider');
        const qualityValue = document.getElementById('qualityValue');
        const saveBtn = document.getElementById('saveBtn');
        const info = document.getElementById('info');
        const quotaInfo = document.getElementById('quotaInfo');
        const errorMsg = document.getElementById('errorMsg');
        const changeImageBtn = document.getElementById('changeImageBtn');
        const currentFileName = document.getElementById('currentFileName');
        
        // Fonction de redimensionnement haute qualité avec algorithme en plusieurs étapes
        function resizeImageHighQuality(sourceCanvas, targetWidth, targetHeight) {
            const sourceWidth = sourceCanvas.width;
            const sourceHeight = sourceCanvas.height;
            
            // Si l'image est agrandie, utiliser l'algorithme simple
            if (targetWidth >= sourceWidth || targetHeight >= sourceHeight) {
                const canvas = document.createElement('canvas');
                canvas.width = targetWidth;
                canvas.height = targetHeight;
                const ctx = canvas.getContext('2d');
                ctx.imageSmoothingEnabled = true;
                ctx.imageSmoothingQuality = 'high';
                ctx.drawImage(sourceCanvas, 0, 0, targetWidth, targetHeight);
                return canvas;
            }
            
            // Algorithme de redimensionnement progressif pour meilleure qualité
            let currentCanvas = sourceCanvas;
            let currentWidth = sourceWidth;
            let currentHeight = sourceHeight;
            
            // Réduire par étapes de 50% maximum jusqu'à approcher la taille cible
            while (currentWidth / 2 > targetWidth || currentHeight / 2 > targetHeight) {
                const newWidth = Math.floor(currentWidth / 2);
                const newHeight = Math.floor(currentHeight / 2);
                
                const tempCanvas = document.createElement('canvas');
                tempCanvas.width = newWidth;
                tempCanvas.height = newHeight;
                const ctx = tempCanvas.getContext('2d');
                ctx.imageSmoothingEnabled = true;
                ctx.imageSmoothingQuality = 'high';
                ctx.drawImage(currentCanvas, 0, 0, newWidth, newHeight);
                
                currentCanvas = tempCanvas;
                currentWidth = newWidth;
                currentHeight = newHeight;
            }
            
            // Dernière étape : redimensionner à la taille exacte
            const finalCanvas = document.createElement('canvas');
            finalCanvas.width = targetWidth;
            finalCanvas.height = targetHeight;
            const ctx = finalCanvas.getContext('2d');
            ctx.imageSmoothingEnabled = true;
            ctx.imageSmoothingQuality = 'high';
            ctx.drawImage(currentCanvas, 0, 0, targetWidth, targetHeight);
            
            return finalCanvas;
        }
        
        // Fonction pour augmenter la netteté (unsharp mask simplifié)
        function sharpenCanvas(canvas, amount = 0.5) {
            const ctx = canvas.getContext('2d');
            const width = canvas.width;
            const height = canvas.height;
            const imageData = ctx.getImageData(0, 0, width, height);
            const data = imageData.data;
            
            // Créer une copie pour le calcul
            const tempCanvas = document.createElement('canvas');
            tempCanvas.width = width;
            tempCanvas.height = height;
            const tempCtx = tempCanvas.getContext('2d');
            tempCtx.drawImage(canvas, 0, 0);
            const originalData = tempCtx.getImageData(0, 0, width, height).data;
            
            // Appliquer un filtre de netteté simple
            for (let i = 0; i < data.length; i += 4) {
                for (let j = 0; j < 3; j++) {
                    const original = originalData[i + j];
                    const current = data[i + j];
                    data[i + j] = Math.min(255, Math.max(0, 
                        current + (current - original) * amount
                    ));
                }
            }
            
            ctx.putImageData(imageData, 0, 0);
            return canvas;
        }
        
        loadQuotas();
        
        // Bouton pour changer d'image
        changeImageBtn.addEventListener('click', () => {
            // Réinitialiser l'interface
            originalImage = null;
            originalWidth = 0;
            originalHeight = 0;
            originalFileSize = 0;
            originalFileName = 'image';
            
            // Réafficher la zone d'upload
            uploadZone.style.display = 'block';
            controls.style.display = 'none';
            livePreview.style.display = 'none';
            
            // Réinitialiser le input file
            fileInput.value = '';
            
            // Cacher les messages
            hideError();
            info.classList.remove('active');
        });
        
        async function loadQuotas() {
            try {
                const response = await fetch('get-quotas.php');
                const data = await response.json();
                if (data.success) {
                    quotaInfo.innerHTML = `📊 Quota : ${data.count}/500 images · ${data.used_space}/500 MB utilisés`;
                } else {
                    quotaInfo.innerHTML = '⚠️ Impossible de charger les quotas';
                }
            } catch(e) {
                quotaInfo.innerHTML = '⚠️ Impossible de charger les quotas';
            }
        }
        
        uploadZone.addEventListener('click', () => fileInput.click());
        
        uploadZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadZone.style.borderColor = '#764ba2';
            uploadZone.style.background = '#f0f3ff';
        });
        
        uploadZone.addEventListener('dragleave', () => {
            uploadZone.style.borderColor = '#667eea';
            uploadZone.style.background = '#f8f9ff';
        });
        
        uploadZone.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadZone.style.borderColor = '#667eea';
            uploadZone.style.background = '#f8f9ff';
            
            if (e.dataTransfer.files.length > 0) {
                fileInput.files = e.dataTransfer.files;
                handleFile(e.dataTransfer.files[0]);
            }
        });
        
        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                handleFile(e.target.files[0]);
            }
        });
        
        function handleFile(file) {
            if (!file.type.startsWith('image/')) {
                showError('Veuillez sélectionner une image valide');
                return;
            }
            
            hideError();
            originalFileSize = file.size;
            originalFileName = file.name.replace(/\.[^/.]+$/, "");
            
            // Afficher le nom du fichier
            currentFileName.textContent = file.name;
            
            const reader = new FileReader();
            reader.onload = (e) => {
                const img = new Image();
                img.onload = () => {
                    originalImage = img;
                    originalWidth = img.width;
                    originalHeight = img.height;
                    
                    // Limiter les sliders à la taille originale maximum (pas d'agrandissement)
                    widthSlider.max = originalWidth;
                    heightSlider.max = originalHeight;
                    widthSlider.value = originalWidth;
                    heightSlider.value = originalHeight;
                    
                    uploadZone.style.display = 'none';
                    controls.style.display = 'block';
                    livePreview.style.display = 'block';
                    
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
            const quality = qualitySlider.value / 100;
            
            widthValue.textContent = width;
            heightValue.textContent = height;
            
            // Créer un canvas avec l'image originale
            const sourceCanvas = document.createElement('canvas');
            sourceCanvas.width = originalWidth;
            sourceCanvas.height = originalHeight;
            const sourceCtx = sourceCanvas.getContext('2d');
            sourceCtx.drawImage(originalImage, 0, 0);
            
            // Redimensionner avec l'algorithme haute qualité
            let resizedCanvas = resizeImageHighQuality(sourceCanvas, width, height);
            
            // Appliquer la netteté si l'image est réduite
            if (width < originalWidth || height < originalHeight) {
                resizedCanvas = sharpenCanvas(resizedCanvas, 0.3);
            }
            
            // Convertir en data URL et calculer la taille estimée
            const dataUrl = resizedCanvas.toDataURL('image/jpeg', quality);
            livePreviewImg.src = dataUrl;
            
            // Calculer la taille estimée du fichier
            const base64Length = dataUrl.length - 'data:image/jpeg;base64,'.length;
            const estimatedSize = (base64Length * 3) / 4; // Taille en octets
            const sizeKB = (estimatedSize / 1024).toFixed(2);
            const sizeMB = (estimatedSize / (1024 * 1024)).toFixed(2);
            
            let sizeText = sizeKB < 1024 ? `${sizeKB} KB` : `${sizeMB} MB`;
            
            // Ajouter un avertissement si > 2MB
            let warningText = '';
            if (estimatedSize > 2 * 1024 * 1024) {
                warningText = ' <span style="color: #f44336;">⚠️ > 2 MB</span>';
            }
            
            liveSize.innerHTML = `${width} × ${height} · ${sizeText}${warningText}`;
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
        
        saveBtn.addEventListener('click', async () => {
            if (!originalImage) return;
            
            saveBtn.disabled = true;
            saveBtn.textContent = '⏳ Traitement haute qualité...';
            hideError();
            info.classList.remove('active');
            
            const width = parseInt(widthSlider.value);
            const height = parseInt(heightSlider.value);
            const quality = qualitySlider.value / 100;
            
            // Créer un canvas avec l'image originale
            const sourceCanvas = document.createElement('canvas');
            sourceCanvas.width = originalWidth;
            sourceCanvas.height = originalHeight;
            const sourceCtx = sourceCanvas.getContext('2d');
            sourceCtx.drawImage(originalImage, 0, 0);
            
            // Redimensionner avec l'algorithme haute qualité
            let finalCanvas = resizeImageHighQuality(sourceCanvas, width, height);
            
            // Appliquer la netteté si l'image est réduite
            if (width < originalWidth || height < originalHeight) {
                finalCanvas = sharpenCanvas(finalCanvas, 0.3);
            }
            
            finalCanvas.toBlob(async (blob) => {
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
                            ✅ <strong>Image sauvegardée en haute qualité !</strong><br>
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