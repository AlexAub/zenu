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
        padding: 5px 12px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 20px;
        font-size: 0.75em;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .ai-tool-status {
        margin-top: 15px;
        padding: 10px;
        border-radius: 8px;
        font-size: 0.85em;
        font-weight: 600;
    }
    
    .status-available {
        background: #c6f6d5;
        color: #22543d;
    }
    
    .status-beta {
        background: #fef3c7;
        color: #78350f;
    }
    
    .status-soon {
        background: #e0e7ff;
        color: #3730a3;
    }
    
    /* Modal */
    .ai-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.7);
        z-index: 10000;
        backdrop-filter: blur(5px);
    }
    
    .ai-modal-content {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: white;
        border-radius: 20px;
        padding: 40px;
        max-width: 900px;
        width: 90%;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    }
    
    .ai-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 2px solid #e2e8f0;
    }
    
    .ai-modal-title {
        font-size: 2em;
        font-weight: 700;
        color: #2d3748;
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .close-modal {
        font-size: 2em;
        color: #a0aec0;
        cursor: pointer;
        background: none;
        border: none;
        padding: 0;
        line-height: 1;
        transition: color 0.2s;
    }
    
    .close-modal:hover {
        color: #667eea;
    }
    
    .image-selector {
        margin-bottom: 30px;
    }
    
    .image-selector-label {
        font-size: 1.1em;
        font-weight: 600;
        color: #2d3748;
        margin-bottom: 15px;
        display: block;
    }
    
    .images-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 15px;
        max-height: 300px;
        overflow-y: auto;
        padding: 10px;
        background: #f7fafc;
        border-radius: 12px;
    }
    
    .image-item {
        position: relative;
        aspect-ratio: 1;
        border-radius: 10px;
        overflow: hidden;
        cursor: pointer;
        border: 3px solid transparent;
        transition: all 0.3s ease;
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
        text-align: center;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .ai-options {
        margin: 30px 0;
        padding: 25px;
        background: #f7fafc;
        border-radius: 12px;
    }
    
    .ai-option {
        margin-bottom: 20px;
    }
    
    .ai-option-label {
        font-weight: 600;
        color: #2d3748;
        margin-bottom: 10px;
        display: block;
    }
    
    .ai-option select,
    .ai-option input[type="range"] {
        width: 100%;
        padding: 10px;
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        font-size: 1em;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        padding: 15px 40px;
        border-radius: 10px;
        font-size: 1.1em;
        font-weight: 600;
        cursor: pointer;
        width: 100%;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    }
    
    .btn-primary:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    }
    
    .btn-primary:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    
    .processing-indicator {
        display: none;
        text-align: center;
        padding: 40px;
    }
    
    .spinner {
        width: 50px;
        height: 50px;
        border: 4px solid #f3f4f6;
        border-top: 4px solid #667eea;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin: 0 auto 20px;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    .result-container {
        display: none;
        padding: 25px;
        background: #f7fafc;
        border-radius: 12px;
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
        
        <!-- Upscaling (à venir) -->
        <div class="ai-tool-card" style="opacity: 0.6; cursor: not-allowed;">
            <span class="ai-tool-icon">🔍</span>
            <div class="ai-tool-title">Agrandissement IA</div>
            <div class="ai-tool-description">
                Augmentez la résolution de vos images jusqu'à 4x sans perte de qualité. 
                Super-résolution par deep learning.
            </div>
            <span class="ai-tool-badge">IA Deep Learning</span>
            <div class="ai-tool-status status-soon">
                🚀 Bientôt disponible
            </div>
        </div>
        
        <!-- Colorisation (à venir) -->
        <div class="ai-tool-card" style="opacity: 0.6; cursor: not-allowed;">
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
        </div>
    </div>
</div>

<script>
let selectedImageId = null;
let selectedImagePath = null;
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
                <div class="ai-option">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <input type="checkbox" id="removeMetadata" checked>
                        <label for="removeMetadata">Supprimer les métadonnées EXIF</label>
                    </div>
                </div>
            `
        }
    };
    
    const config = toolConfigs[tool];
    if (!config) return;
    
    document.getElementById('modalIcon').textContent = config.icon;
    document.getElementById('modalTitle').textContent = config.title;
    document.getElementById('aiOptionsContainer').innerHTML = config.options;
    
    document.getElementById('aiModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
    
    // Reset
    selectedImageId = null;
    selectedImagePath = null;
    document.getElementById('processBtn').disabled = true;
    document.getElementById('processBtn').innerHTML = '🚀 Traiter l\'image';
    document.getElementById('processingIndicator').style.display = 'none';
    document.getElementById('resultContainer').style.display = 'none';
    processedImageData = null;
    
    // Retirer la sélection de toutes les images
    document.querySelectorAll('.image-item').forEach(item => {
        item.classList.remove('selected');
    });
}

function closeAIModal() {
    document.getElementById('aiModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

function selectImage(element) {
    // Retirer la sélection précédente
    document.querySelectorAll('.image-item').forEach(item => {
        item.classList.remove('selected');
    });
    
    // Ajouter la sélection
    element.classList.add('selected');
    
    selectedImageId = element.dataset.id;
    selectedImagePath = element.dataset.path;
    
    document.getElementById('processBtn').disabled = false;
}

async function processAI() {
    if (!selectedImageId || !currentTool) return;
    
    // Récupérer les options selon l'outil
    const options = getToolOptions();
    
    // Afficher l'indicateur de traitement
    document.getElementById('processBtn').style.display = 'none';
    document.getElementById('processingIndicator').style.display = 'block';
    document.getElementById('resultContainer').style.display = 'none';
    
    try {
        const response = await fetch(`api/ai-${currentTool}.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                image_id: selectedImageId,
                image_path: selectedImagePath,
                options: options
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showResult(data);
        } else {
            alert('❌ Erreur: ' + data.error);
            resetModal();
        }
    } catch (error) {
        console.error('Erreur:', error);
        alert('❌ Erreur lors du traitement');
        resetModal();
    }
}

function getToolOptions() {
    const options = {};
    
    switch(currentTool) {
        case 'remove-bg':
            options.detectionMode = document.getElementById('detectionMode')?.value || 'auto';
            options.edgeQuality = document.getElementById('edgeQuality')?.value || 'medium';
            break;
            
        case 'enhance':
            options.intensity = parseInt(document.getElementById('enhanceIntensity')?.value || 70);
            // ⭐ TOUTES les améliorations sont activées automatiquement
            options.brightness = true;
            options.contrast = true;
            options.saturation = true;
            options.sharpness = true; // ⭐ Netteté TOUJOURS activée
            break;
            
        case 'smart-crop':
            options.aspectRatio = document.getElementById('cropAspectRatio')?.value || 'auto';
            options.detectionPriority = document.getElementById('detectionPriority')?.value || 'subject';
            break;
            
        case 'optimize':
            options.quality = parseInt(document.getElementById('optimizeQuality')?.value || 80);
            options.format = document.getElementById('outputFormat')?.value || 'same';
            options.removeMetadata = document.getElementById('removeMetadata')?.checked || false;
            break;
    }
    
    return options;
}

// ⭐ NOUVELLE FONCTION: Permet de continuer à modifier après avoir vu un résultat
function continueEditing() {
    // Cacher le résultat
    document.getElementById('resultContainer').style.display = 'none';
    
    // Réafficher le bouton de traitement
    document.getElementById('processBtn').style.display = 'block';
    document.getElementById('processBtn').innerHTML = '🚀 Traiter l\'image';
    document.getElementById('processBtn').disabled = false;
    
    // Réinitialiser les données du résultat
    processedImageData = null;
    
    console.log('✅ Prêt pour un nouveau traitement');
}

// ⭐ FONCTION MODIFIÉE: Affiche le résultat ET permet de continuer
function showResult(data) {
    document.getElementById('processingIndicator').style.display = 'none';
    document.getElementById('resultContainer').style.display = 'block';
    
    document.getElementById('originalImage').src = selectedImagePath;
    document.getElementById('processedImage').src = data.processed_image;
    
    processedImageData = data;
    
    // ⭐ IMPORTANT: Réafficher le bouton pour permettre d'appliquer un autre effet
    document.getElementById('processBtn').style.display = 'block';
    document.getElementById('processBtn').innerHTML = '🔄 Appliquer un autre effet';
    document.getElementById('processBtn').disabled = false;
}

// ⭐ FONCTION MODIFIÉE: Réinitialisation complète
function resetModal() {
    document.getElementById('processBtn').style.display = 'block';
    document.getElementById('processBtn').innerHTML = '🚀 Traiter l\'image';
    document.getElementById('processBtn').disabled = selectedImageId === null;
    document.getElementById('processingIndicator').style.display = 'none';
    document.getElementById('resultContainer').style.display = 'none';
    processedImageData = null;
}

// ⭐ FONCTION MODIFIÉE: Proposer de continuer après téléchargement
function downloadResult() {
    if (!processedImageData) return;
    
    const link = document.createElement('a');
    link.href = processedImageData.processed_image;
    link.download = processedImageData.filename || 'ai-processed.jpg';
    link.click();
    
    // ⭐ NOUVEAU: Proposer de continuer après téléchargement
    setTimeout(() => {
        if (confirm('📥 Image téléchargée !\n\nVoulez-vous continuer à appliquer d\'autres effets ?')) {
            continueEditing();
        }
    }, 500);
}

// ⭐ FONCTION MODIFIÉE: Proposer de continuer après sauvegarde
async function saveResult() {
    if (!processedImageData) return;
    
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
            // ⭐ NOUVEAU: Demander si on veut continuer ou fermer
            if (confirm('✅ Image sauvegardée avec succès !\n\nVoulez-vous continuer à appliquer d\'autres effets ?')) {
                continueEditing();
            } else {
                closeAIModal();
                setTimeout(() => location.reload(), 500);
            }
        } else {
            alert('❌ Erreur lors de la sauvegarde: ' + data.error);
        }
    } catch (error) {
        console.error('Erreur:', error);
        alert('❌ Erreur lors de la sauvegarde');
    }
}

// Fermer le modal en cliquant à l'extérieur
document.getElementById('aiModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeAIModal();
    }
});
</script>

<?php require_once 'footer.php'; ?>