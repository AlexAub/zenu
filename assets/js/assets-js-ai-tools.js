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
    
    // Réinitialiser la sélection
    selectedImageId = null;
    selectedImagePath = null;
    selectedImageWidth = null;
    selectedImageHeight = null;
    document.querySelectorAll('.image-item').forEach(item => {
        item.classList.remove('selected');
    });
    document.getElementById('processBtn').disabled = true;
    document.getElementById('resultContainer').style.display = 'none';
    
    // Ajouter l'écouteur pour mettre à jour les infos d'upscale
    if (tool === 'upscale') {
        const scaleSelect = document.getElementById('scaleFactor');
        if (scaleSelect) {
            scaleSelect.addEventListener('change', updateUpscaleInfo);
        }
    }
    
    document.getElementById('aiModal').style.display = 'block';
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
    document.getElementById('processBtn').disabled = false;
    
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
            
            document.getElementById('processingIndicator').style.display = 'none';
            document.getElementById('resultContainer').style.display = 'block';
        } else {
            throw new Error(result.error || 'Erreur de traitement');
        }
        
    } catch (error) {
        console.error('Erreur:', error);
        alert('Erreur lors du traitement: ' + error.message);
        
        document.getElementById('processingIndicator').style.display = 'none';
        document.getElementById('processBtn').style.display = 'block';
    }
}

function continueEditing() {
    document.getElementById('resultContainer').style.display = 'none';
    document.getElementById('processBtn').style.display = 'block';
    
    // Désélectionner l'image
    document.querySelectorAll('.image-item').forEach(item => {
        item.classList.remove('selected');
    });
    selectedImageId = null;
    selectedImagePath = null;
    document.getElementById('processBtn').disabled = true;
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
    
    try {
        // TODO: Implémenter la sauvegarde dans la base de données
        alert('Fonctionnalité de sauvegarde à venir. Pour l\'instant, utilisez le bouton Télécharger.');
    } catch (error) {
        console.error('Erreur de sauvegarde:', error);
        alert('Erreur lors de la sauvegarde');
    }
}

// Fermer le modal en cliquant en dehors
document.getElementById('aiModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeAIModal();
    }
});
