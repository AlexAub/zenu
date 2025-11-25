<?php
require_once 'config.php';
require_once 'security.php';

// Vérifier la connexion
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$pageTitle = "Outils IA";

// Récupérer les images de l'utilisateur
$stmt = $pdo->prepare("
    SELECT id, filename, original_filename, file_path, thumbnail_path, 
           width, height, file_size, created_at
    FROM images 
    WHERE user_id = ? AND is_deleted = 0
    ORDER BY created_at DESC
    LIMIT 50
");
$stmt->execute([$userId]);
$images = $stmt->fetchAll();

require_once 'header.php';
?>
<style>
    .ai-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 20px;
    }
    
    .ai-hero {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 50px 30px;
        border-radius: 20px;
        text-align: center;
        margin-bottom: 40px;
        box-shadow: 0 10px 40px rgba(102, 126, 234, 0.3);
    }
    
    .ai-hero h1 {
        font-size: 2.5em;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 15px;
    }
    
    .ai-hero p {
        font-size: 1.2em;
        opacity: 0.95;
        margin: 0;
    }
    
    .ai-tools-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 25px;
        margin-bottom: 40px;
    }
    
    .ai-tool-card {
        background: white;
        border-radius: 16px;
        padding: 30px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        cursor: pointer;
        border: 2px solid transparent;
    }
    
    .ai-tool-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 30px rgba(102, 126, 234, 0.2);
        border-color: #667eea;
    }
    
    .ai-tool-card.disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
    
    .ai-tool-card.disabled:hover {
        transform: none;
        border-color: transparent;
    }
    
    .ai-tool-icon {
        font-size: 3em;
        margin-bottom: 15px;
        display: block;
    }
    
    .ai-tool-title {
        font-size: 1.5em;
        font-weight: 700;
        color: #2d3748;
        margin-bottom: 10px;
    }
    
    .ai-tool-description {
        color: #718096;
        font-size: 0.95em;
        line-height: 1.6;
        margin-bottom: 15px;
    }
    
    .ai-tool-badge {
        display: inline-block;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.75em;
        font-weight: 600;
        margin-bottom: 10px;
    }
    
    .ai-tool-status {
        font-size: 0.85em;
        font-weight: 600;
        padding: 8px 12px;
        border-radius: 8px;
        display: inline-block;
        margin-top: 10px;
    }
    
    .status-available {
        background: #e6ffed;
        color: #22543d;
    }
    
    .status-soon {
        background: #fff8e6;
        color: #744210;
    }
    
    /* Modal styles */
    .ai-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        z-index: 10000;
        overflow-y: auto;
        padding: 20px;
    }
    
    .ai-modal-content {
        background: white;
        max-width: 1200px;
        margin: 40px auto;
        border-radius: 20px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    }
    
    .ai-modal-header {
        padding: 30px;
        border-bottom: 2px solid #f0f0f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .ai-modal-title {
        display: flex;
        align-items: center;
        gap: 15px;
        font-size: 1.8em;
        font-weight: 700;
        color: #2d3748;
    }
    
    .close-modal {
        background: none;
        border: none;
        font-size: 2em;
        cursor: pointer;
        color: #a0aec0;
        transition: color 0.3s;
        padding: 0;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .close-modal:hover {
        color: #2d3748;
    }
    
    /* Image selector */
    .image-selector {
        padding: 30px;
    }
    
    .image-selector-label {
        font-size: 1.2em;
        font-weight: 600;
        color: #2d3748;
        margin-bottom: 20px;
        display: block;
    }
    
    .images-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 15px;
        max-height: 400px;
        overflow-y: auto;
        padding: 10px;
        border: 2px dashed #e0e0e0;
        border-radius: 12px;
    }
    
    .image-item {
        position: relative;
        cursor: pointer;
        border-radius: 10px;
        overflow: hidden;
        border: 3px solid transparent;
        transition: all 0.3s ease;
        aspect-ratio: 1;
    }
    
    .image-item:hover {
        border-color: #667eea;
        transform: scale(1.05);
    }
    
    .image-item.selected {
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
    }
    
    .image-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .image-item-name {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
        color: white;
        padding: 8px;
        font-size: 0.75em;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    /* AI Options */
    .ai-options {
        padding: 0 30px 20px;
    }
    
    .ai-option {
        margin-bottom: 20px;
    }
    
    .ai-option-label {
        display: block;
        font-weight: 600;
        color: #2d3748;
        margin-bottom: 8px;
    }
    
    .ai-option select,
    .ai-option input[type="range"] {
        width: 100%;
        padding: 10px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 1em;
    }
    
    .ai-option input[type="range"] {
        padding: 0;
    }
    
    /* Process button */
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        padding: 15px 40px;
        border-radius: 10px;
        font-size: 1.1em;
        font-weight: 600;
        cursor: pointer;
        width: calc(100% - 60px);
        margin: 0 30px 30px;
        transition: all 0.3s ease;
    }
    
    .btn-primary:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
    }
    
    .btn-primary:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    
    /* Processing indicator */
    .processing-indicator {
        display: none;
        text-align: center;
        padding: 40px 30px;
    }
    
    .spinner {
        border: 4px solid #f3f3f3;
        border-top: 4px solid #667eea;
        border-radius: 50%;
        width: 50px;
        height: 50px;
        animation: spin 1s linear infinite;
        margin: 0 auto 20px;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    /* Result container */
    .result-container {
        display: none;
        padding: 30px;
    }
    
    .result-images {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 20px;
    }
    
    .result-image {
        text-align: center;
    }
    
    .result-image img {
        max-width: 100%;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }
    
    .result-image-label {
        font-weight: 600;
        color: #2d3748;
        margin-top: 10px;
    }
    
    .result-actions {
        display: flex;
        gap: 10px;
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .btn-secondary {
        background: white;
        color: #667eea;
        border: 2px solid #667eea;
        padding: 12px 30px;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .btn-secondary:hover {
        background: #667eea;
        color: white;
    }
    
    @media (max-width: 768px) {
        .ai-hero h1 {
            font-size: 1.8em;
        }
        
        .ai-tools-grid {
            grid-template-columns: 1fr;
        }
        
        .result-images {
            grid-template-columns: 1fr;
        }
        
        .result-actions {
            flex-direction: column;
        }
    }
</style>

<div class="ai-container">
    <div class="ai-hero">
        <h1>
            <span>🤖</span>
            Outils IA et Automatisation
        </h1>
        <p>Transformez vos images avec l'intelligence artificielle</p>
    </div>
    
    <div class="ai-tools-grid">
        <!-- Suppression de fond -->
        <div class="ai-tool-card" onclick="openAITool('remove-bg')">
            <span class="ai-tool-icon">🎭</span>
            <div class="ai-tool-title">Suppression de fond</div>
            <div class="ai-tool-description">
                Retirez automatiquement l'arrière-plan de vos images en un clic. 
                Parfait pour les portraits, produits et logos.
            </div>
            <span class="ai-tool-badge">IA Avancée</span>
            <div class="ai-tool-status status-available">
                ✓ Disponible
            </div>
        </div>
        
        <!-- Amélioration automatique -->
        <div class="ai-tool-card" onclick="openAITool('enhance')">
            <span class="ai-tool-icon">✨</span>
            <div class="ai-tool-title">Amélioration automatique</div>
            <div class="ai-tool-description">
                Optimisez luminosité, contraste, netteté et couleurs automatiquement. 
                L'IA analyse votre image et l'améliore intelligemment.
            </div>
            <span class="ai-tool-badge">IA Intelligente</span>
            <div class="ai-tool-status status-available">
                ✓ Disponible
            </div>
        </div>
        
        <!-- Recadrage intelligent -->
        <div class="ai-tool-card" onclick="openAITool('smart-crop')">
            <span class="ai-tool-icon">🎯</span>
            <div class="ai-tool-title">Recadrage intelligent</div>
            <div class="ai-tool-description">
                Détection automatique du sujet principal et recadrage optimal. 
                Idéal pour créer des miniatures et images pour réseaux sociaux.
            </div>
            <span class="ai-tool-badge">IA Détection</span>
            <div class="ai-tool-status status-available">
                ✓ Disponible
            </div>
        </div>
        
        <!-- Compression intelligente -->
        <div class="ai-tool-card" onclick="openAITool('optimize')">
            <span class="ai-tool-icon">⚡</span>
            <div class="ai-tool-title">Compression intelligente</div>
            <div class="ai-tool-description">
                Réduisez la taille de vos fichiers sans perte visible de qualité. 
                Optimisation adaptative selon le contenu de l'image.
            </div>
            <span class="ai-tool-badge">IA Optimisation</span>
            <div class="ai-tool-status status-available">
                ✓ Disponible
            </div>
        </div>
        
        <!-- Upscaling - MAINTENANT DISPONIBLE -->
        <div class="ai-tool-card" onclick="openAITool('upscale')">
            <span class="ai-tool-icon">🔍</span>
            <div class="ai-tool-title">Agrandissement IA</div>
            <div class="ai-tool-description">
                Augmentez la résolution de vos images jusqu'à 4x sans perte de qualité. 
                Super-résolution par algorithmes avancés.
            </div>
            <span class="ai-tool-badge">IA Super-Resolution</span>
            <div class="ai-tool-status status-available">
                ✓ Disponible
            </div>
        </div>
        
        <!-- Colorisation (toujours à venir) -->
        <div class="ai-tool-card disabled">
            <span class="ai-tool-icon">🎨</span>
            <div class="ai-tool-title">Colorisation auto</div>
            <div class="ai-tool-description">
                Colorisez automatiquement vos photos noir et blanc. 
                L'IA prédit les couleurs naturelles avec précision.
            </div>
            <span class="ai-tool-badge">IA Génération</span>
            <div class="ai-tool-status status-soon">
                🚀 Bientôt disponible
            </div>
        </div>
    </div>
</div>

<!-- Modal pour les outils IA -->
<div id="aiModal" class="ai-modal">
    <div class="ai-modal-content">
        <div class="ai-modal-header">
            <div class="ai-modal-title">
                <span id="modalIcon">🤖</span>
                <span id="modalTitle">Outil IA</span>
            </div>
            <button class="close-modal" onclick="closeAIModal()">×</button>
        </div>
        
        <div class="image-selector">
            <label class="image-selector-label">
                📸 Sélectionnez une image
            </label>
            
            <?php if (empty($images)): ?>
                <div style="text-align: center; padding: 40px; color: #a0aec0;">
                    <p style="font-size: 3em; margin-bottom: 10px;">📁</p>
                    <p style="font-size: 1.1em; margin-bottom: 20px;">Aucune image disponible</p>
                    <a href="dashboard.php" style="color: #667eea; font-weight: 600;">
                        ← Uploadez d'abord des images
                    </a>
                </div>
            <?php else: ?>
                <div class="images-grid">
                    <?php foreach ($images as $image): ?>
                        <div class="image-item" 
                             data-id="<?= $image['id'] ?>"
                             data-path="<?= htmlspecialchars($image['file_path']) ?>"
                             data-width="<?= $image['width'] ?>"
                             data-height="<?= $image['height'] ?>"
                             onclick="selectImage(this)">
                            <img src="<?= htmlspecialchars($image['thumbnail_path'] ?? $image['file_path']) ?>" 
                                 alt="<?= htmlspecialchars($image['original_filename'] ?? $image['filename']) ?>">
                            <div class="image-item-name">
                                <?= htmlspecialchars($image['original_filename'] ?? $image['filename']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div id="aiOptionsContainer" class="ai-options">
            <!-- Les options spécifiques à chaque outil seront insérées ici -->
        </div>
        
        <button id="processBtn" class="btn-primary" onclick="processAI()" disabled>
            🚀 Traiter l'image
        </button>
        
        <div id="processingIndicator" class="processing-indicator">
            <div class="spinner"></div>
            <p style="font-weight: 600; color: #2d3748;">Traitement en cours...</p>
            <p style="color: #718096; font-size: 0.9em;">Cela peut prendre quelques secondes</p>
        </div>
        
        <div id="resultContainer" class="result-container">
            <div class="result-images">
                <div class="result-image">
                    <img id="originalImage" src="" alt="Original">
                    <div class="result-image-label">Original</div>
                </div>
                <div class="result-image">
                    <img id="processedImage" src="" alt="Traité">
                    <div class="result-image-label">Résultat IA</div>
                </div>
            </div>
            <div class="result-actions">
                <button class="btn-secondary" onclick="continueEditing()">
                    🔄 Nouvelle modification
                </button>
                <button class="btn-secondary" onclick="downloadResult()">
                    💾 Télécharger
                </button>
                <button class="btn-primary" onclick="saveResult()">
                    ✓ Sauvegarder dans mon compte
                </button>
            </div>

<script>

let selectedImageId = null;
let selectedImagePath = null;
let selectedImageWidth = null;
let selectedImageHeight = null;
let currentTool = null;
let processedImageData = null;

function openAITool(tool) {
    currentTool = tool;
    
    const toolConfigs = {
        'remove-bg': {
            icon: '🎭',
            title: 'Suppression de fond',
            options: `
                <div class="ai-option">
                    <label class="ai-option-label">Type de détection</label>
                    <select id="detectionMode">
                        <option value="auto">Automatique (recommandé)</option>
                        <option value="person">Personne / Portrait</option>
                        <option value="product">Produit / Objet</option>
                        <option value="graphic">Graphique / Logo</option>
                    </select>
                </div>
                <div class="ai-option">
                    <label class="ai-option-label">Qualité des contours</label>
                    <select id="edgeQuality">
                        <option value="low">Rapide</option>
                        <option value="medium" selected>Équilibrée</option>
                        <option value="high">Précise (plus lent)</option>
                    </select>
                </div>
            `
        },
        'enhance': {
            icon: '✨',
            title: 'Amélioration automatique',
            options: `
                <div class="ai-option">
                    <label class="ai-option-label">Intensité</label>
                    <select id="enhanceIntensity">
                        <option value="30">Subtile (30%)</option>
                        <option value="50">Modérée (50%)</option>
                        <option value="70" selected>Équilibrée (70%) - Recommandé</option>
                        <option value="85">Forte (85%)</option>
                        <option value="100">Maximale (100%)</option>
                    </select>
                    <p style="font-size: 0.85em; color: #718096; margin-top: 8px;">
                        L'IA optimise automatiquement la luminosité, le contraste, les couleurs et la netteté
                    </p>
                </div>
            `
        },
        'smart-crop': {
            icon: '🎯',
            title: 'Recadrage intelligent',
            options: `
                <div class="ai-option">
                    <label class="ai-option-label">Format de sortie</label>
                    <select id="cropAspectRatio">
                        <option value="auto">Automatique (détecté)</option>
                        <option value="1:1">Carré (1:1)</option>
                        <option value="4:3">Standard (4:3)</option>
                        <option value="16:9">Panoramique (16:9)</option>
                        <option value="9:16">Story (9:16)</option>
                    </select>
                </div>
                <div class="ai-option">
                    <label class="ai-option-label">Priorité de détection</label>
                    <select id="detectionPriority">
                        <option value="subject">Sujet principal</option>
                        <option value="face">Visage</option>
                        <option value="center">Centre de l'image</option>
                    </select>
                </div>
            `
        },
        'optimize': {
            icon: '⚡',
            title: 'Compression intelligente',
            options: `
                <div class="ai-option">
                    <label class="ai-option-label">Qualité: <span id="qualityValue">80%</span></label>
                    <input type="range" id="optimizeQuality" min="50" max="100" value="80" 
                           oninput="document.getElementById('qualityValue').textContent = this.value + '%'">
                    <p style="font-size: 0.85em; color: #718096; margin-top: 5px;">
                        Plus la qualité est élevée, plus le fichier sera volumineux
                    </p>
                </div>
                <div class="ai-option">
                    <label class="ai-option-label">Format de sortie</label>
                    <select id="outputFormat">
                        <option value="same">Conserver le format original</option>
                        <option value="webp">WebP (meilleure compression)</option>
                        <option value="jpg">JPEG</option>
                        <option value="png">PNG</option>
                    </select>
                </div>
            `
        },
        'upscale': {
            icon: '🔍',
            title: 'Agrandissement IA',
            options: `
                <div class="ai-option">
                    <label class="ai-option-label">Facteur d'agrandissement</label>
                    <select id="scaleFactor">
                        <option value="1.5">1.5x (50% plus grand)</option>
                        <option value="2.0" selected>2x (Double la taille)</option>
                        <option value="3.0">3x (Triple la taille)</option>
                        <option value="4.0">4x (Quadruple la taille)</option>
                    </select>
                    <p id="scaleInfo" style="font-size: 0.85em; color: #718096; margin-top: 8px;">
                        Sélectionnez une image pour voir les dimensions finales
                    </p>
                </div>
                <div class="ai-option">
                    <label class="ai-option-label">Qualité du traitement</label>
                    <select id="upscaleQuality">
                        <option value="fast">Rapide (2-3 secondes)</option>
                        <option value="balanced" selected>Équilibré (5-8 secondes)</option>
                        <option value="high">Haute qualité (10-15 secondes)</option>
                    </select>
                </div>
                <div class="ai-option">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <input type="checkbox" id="upscaleDenoise" checked>
                        <label for="upscaleDenoise" style="margin: 0; font-weight: normal;">
                            Réduction du bruit avant agrandissement
                        </label>
                    </div>
                </div>
                <div class="ai-option">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <input type="checkbox" id="upscaleSharpen" checked>
                        <label for="upscaleSharpen" style="margin: 0; font-weight: normal;">
                            Amélioration de la netteté
                        </label>
                    </div>
                </div>
                <div style="background: #e6f7ff; padding: 15px; border-radius: 8px; margin-top: 15px;">
                    <p style="margin: 0; color: #0066cc; font-size: 0.9em;">
                        <strong>💡 Astuce:</strong> L'algorithme de super-résolution fonctionne mieux sur des images nettes et bien exposées.
                        Pour de meilleurs résultats, améliorez d'abord votre image avec l'outil "Amélioration automatique".
                    </p>
                </div>
            `
        }
    };
    
    const config = toolConfigs[tool];
    if (!config) return;
    
    document.getElementById('modalIcon').textContent = config.icon;
    document.getElementById('modalTitle').textContent = config.title;
    document.getElementById('aiOptionsContainer').innerHTML = config.options;
    
    // Réinitialiser TOUTES les variables de sélection
    selectedImageId = null;
    selectedImagePath = null;
    selectedImageWidth = null;
    selectedImageHeight = null;
    processedImageData = null;
    
    // Réinitialiser l'interface
    document.querySelectorAll('.image-item').forEach(item => {
        item.classList.remove('selected');
    });
    
    const processBtn = document.getElementById('processBtn');
    processBtn.disabled = true;
    processBtn.innerHTML = '🚀 Traiter l\'image';
    processBtn.style.display = ''; // Enlever le style inline au lieu de mettre 'block'
    
    document.getElementById('processingIndicator').style.display = 'none';
    document.getElementById('resultContainer').style.display = 'none';
    
    // Ajouter l'écouteur pour mettre à jour les infos d'upscale
    if (tool === 'upscale') {
        const scaleSelect = document.getElementById('scaleFactor');
        if (scaleSelect) {
            scaleSelect.addEventListener('change', updateUpscaleInfo);
        }
    }
    
    document.getElementById('aiModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function updateUpscaleInfo() {
    if (!selectedImageWidth || !selectedImageHeight) return;
    
    const scaleFactor = parseFloat(document.getElementById('scaleFactor').value);
    const newWidth = Math.round(selectedImageWidth * scaleFactor);
    const newHeight = Math.round(selectedImageHeight * scaleFactor);
    
    const info = document.getElementById('scaleInfo');
    if (info) {
        info.innerHTML = `
            <strong>Original:</strong> ${selectedImageWidth} × ${selectedImageHeight}px<br>
            <strong>Après agrandissement:</strong> ${newWidth} × ${newHeight}px
        `;
    }
}

function closeAIModal() {
    document.getElementById('aiModal').style.display = 'none';
    document.body.style.overflow = 'auto';
    
    // Réinitialiser TOUTES les variables
    selectedImageId = null;
    selectedImagePath = null;
    selectedImageWidth = null;
    selectedImageHeight = null;
    currentTool = null;
    processedImageData = null;
    
    // Réinitialiser l'interface
    const processBtn = document.getElementById('processBtn');
    processBtn.disabled = true;
    processBtn.innerHTML = '🚀 Traiter l\'image';
    processBtn.style.display = ''; // Enlever le style inline
    
    document.getElementById('processingIndicator').style.display = 'none';
    document.getElementById('resultContainer').style.display = 'none';
    
    // Désélectionner toutes les images
    document.querySelectorAll('.image-item').forEach(item => {
        item.classList.remove('selected');
    });
}

function selectImage(element) {
    // Désélectionner toutes les images
    document.querySelectorAll('.image-item').forEach(item => {
        item.classList.remove('selected');
    });
    
    // Sélectionner l'image cliquée
    element.classList.add('selected');
    selectedImageId = element.dataset.id;
    selectedImagePath = element.dataset.path;
    selectedImageWidth = parseInt(element.dataset.width);
    selectedImageHeight = parseInt(element.dataset.height);
    
    // Activer le bouton de traitement
    const processBtn = document.getElementById('processBtn');
    processBtn.disabled = false;
    
    // Si le résultat est visible, on garde "Appliquer un autre effet"
    // Sinon on remet "Traiter l'image"
    const resultContainer = document.getElementById('resultContainer');
    const resultVisible = resultContainer.style.display === 'block';
    
    if (!resultVisible) {
        processBtn.innerHTML = '🚀 Traiter l\'image';
    }
    
    // Mettre à jour les infos d'upscale si nécessaire
    if (currentTool === 'upscale') {
        updateUpscaleInfo();
    }
}

async function processAI() {
    if (!selectedImageId || !selectedImagePath) {
        alert('Veuillez sélectionner une image');
        return;
    }
    
    // Cacher le bouton et afficher l'indicateur de traitement
    document.getElementById('processBtn').style.display = 'none';
    document.getElementById('processingIndicator').style.display = 'block';
    document.getElementById('resultContainer').style.display = 'none';
    
    try {
        let apiEndpoint;
        let options = {};
        
        switch (currentTool) {
            case 'remove-bg':
                apiEndpoint = 'api/ai-remove-bg.php';
                options = {
                    detectionMode: document.getElementById('detectionMode').value,
                    edgeQuality: document.getElementById('edgeQuality').value
                };
                break;
                
            case 'enhance':
                apiEndpoint = 'api/ai-enhance.php';
                options = {
                    intensity: parseInt(document.getElementById('enhanceIntensity').value)
                };
                break;
                
            case 'smart-crop':
                apiEndpoint = 'api/ai-smart-crop.php';
                options = {
                    aspectRatio: document.getElementById('cropAspectRatio').value,
                    detectionPriority: document.getElementById('detectionPriority').value
                };
                break;
                
            case 'optimize':
                apiEndpoint = 'api/ai-optimize.php';
                options = {
                    quality: parseInt(document.getElementById('optimizeQuality').value),
                    format: document.getElementById('outputFormat').value
                };
                break;
                
            case 'upscale':
                apiEndpoint = 'api/ai-upscale.php';
                options = {
                    scale: parseFloat(document.getElementById('scaleFactor').value),
                    quality: document.getElementById('upscaleQuality').value,
                    denoise: document.getElementById('upscaleDenoise').checked,
                    sharpen: document.getElementById('upscaleSharpen').checked
                };
                break;
                
            default:
                throw new Error('Outil non reconnu');
        }
        
        const response = await fetch(apiEndpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                image_id: selectedImageId,
                image_path: selectedImagePath,
                options: options
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Afficher les résultats
            document.getElementById('originalImage').src = selectedImagePath;
            document.getElementById('processedImage').src = result.processed_image;
            processedImageData = result;
            
            // Cacher l'indicateur de traitement
            document.getElementById('processingIndicator').style.display = 'none';
            
            // Afficher le résultat
            document.getElementById('resultContainer').style.display = 'block';
            
            // Réafficher le bouton pour permettre de refaire un traitement
            document.getElementById('processBtn').style.display = ''; // Enlever le style inline
            document.getElementById('processBtn').innerHTML = '🔄 Appliquer un autre effet';
            document.getElementById('processBtn').disabled = false;
        } else {
            throw new Error(result.error || 'Erreur de traitement');
        }
        
    } catch (error) {
        console.error('Erreur:', error);
        alert('Erreur lors du traitement: ' + error.message);
        
        document.getElementById('processingIndicator').style.display = 'none';
        document.getElementById('processBtn').style.display = ''; // Enlever le style inline
    }
}

function continueEditing() {
    // Cacher le résultat
    document.getElementById('resultContainer').style.display = 'none';
    
    const processBtn = document.getElementById('processBtn');
    processBtn.style.display = ''; // Enlever le style inline
    processBtn.innerHTML = '🚀 Traiter l\'image';
    
    // Réinitialiser les variables de sélection
    selectedImageId = null;
    selectedImagePath = null;
    selectedImageWidth = null;
    selectedImageHeight = null;
    processedImageData = null;
    
    // Désactiver le bouton jusqu'à nouvelle sélection
    processBtn.disabled = true;
    
    // Désélectionner toutes les images visuellement
    document.querySelectorAll('.image-item').forEach(item => {
        item.classList.remove('selected');
    });
    
    // Réinitialiser les infos d'upscaling si présentes
    const scaleInfo = document.getElementById('scaleInfo');
    if (scaleInfo) {
        scaleInfo.innerHTML = 'Sélectionnez une image pour voir les dimensions finales';
    }
}

function downloadResult() {
    if (!processedImageData) return;
    
    const link = document.createElement('a');
    link.href = processedImageData.processed_image;
    link.download = processedImageData.filename;
    link.click();
}

async function saveResult() {
    if (!processedImageData) return;
    
    // Désactiver le bouton pendant le traitement
    const saveBtn = event.target;
    const originalText = saveBtn.innerHTML;
    saveBtn.disabled = true;
    saveBtn.innerHTML = '⏳ Sauvegarde...';
    
    try {
        const response = await fetch('api/save-ai-result.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                image_data: processedImageData.processed_image,
                original_id: selectedImageId,
                tool: currentTool
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Afficher un message de succès avec option de voir l'image
            const viewImage = confirm(
                data.message + '\n\n' +
                'Voulez-vous voir l\'image dans votre bibliothèque ?'
            );
            
            if (viewImage) {
                // Rediriger vers le dashboard
                window.location.href = 'dashboard.php';
            } else {
                // Proposer de continuer
                if (confirm('Voulez-vous continuer à appliquer d\'autres effets ?')) {
                    continueEditing();
                } else {
                    closeAIModal();
                }
            }
        } else {
            throw new Error(data.error || 'Erreur de sauvegarde');
        }
    } catch (error) {
        console.error('Erreur de sauvegarde:', error);
        alert('❌ Erreur lors de la sauvegarde: ' + error.message);
        
        // Réactiver le bouton
        saveBtn.disabled = false;
        saveBtn.innerHTML = originalText;
    }
}

// Fermer le modal en cliquant en dehors
document.getElementById('aiModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeAIModal();
    }
});
</script>

<?php require_once 'footer.php'; ?>