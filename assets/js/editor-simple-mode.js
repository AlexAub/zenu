/**
 * ÉDITEUR D'IMAGES - MODE SIMPLE
 * Gestion des filtres, rotations et retournements
 */

// Variables du mode simple
let currentRotation = 0;
let currentFlipH = false;
let baseWidth = 0;  // ✅ Dimensions de base de l'image
let baseHeight = 0;
let filters = {
    brightness: 100,
    contrast: 100,
    saturation: 100,
    blur: 0
};

/**
 * Charger l'image dans le mode simple
 */
function loadSimpleMode(img) {
    // ✅ Sauvegarder les dimensions de base
    baseWidth = Math.min(img.width, 1000);
    baseHeight = (baseWidth / img.width) * img.height;
    
    // Réinitialiser la rotation
    currentRotation = 0;
    currentFlipH = false;
    
    // Définir les dimensions initiales
    simpleCanvas.width = baseWidth;
    simpleCanvas.height = baseHeight;
    
    simpleCanvas.style.display = 'block';
    document.getElementById('cropperImage').style.display = 'none';
    document.getElementById('fabricCanvas').style.display = 'none';
    
    // Cacher le message empty state
    const emptyState = document.getElementById('emptyState');
    if (emptyState) {
        emptyState.style.display = 'none';
    }
    
    drawSimpleCanvas();
}

/**
 * Dessiner le canvas avec les filtres appliqués
 */
function drawSimpleCanvas() {
    if (!originalImage) return;
    
    // ✅ Calculer les dimensions selon la rotation actuelle
    const normalizedRotation = ((currentRotation % 360) + 360) % 360; // Normaliser entre 0 et 360
    const isRotated90or270 = normalizedRotation === 90 || normalizedRotation === 270;
    
    // ✅ Inverser les dimensions si rotation de 90° ou 270°
    let canvasWidth, canvasHeight;
    if (isRotated90or270) {
        canvasWidth = baseHeight;
        canvasHeight = baseWidth;
    } else {
        canvasWidth = baseWidth;
        canvasHeight = baseHeight;
    }
    
    // ✅ Mettre à jour les dimensions du canvas
    if (simpleCanvas.width !== canvasWidth || simpleCanvas.height !== canvasHeight) {
        simpleCanvas.width = canvasWidth;
        simpleCanvas.height = canvasHeight;
    }
    
    // Nettoyer le canvas
    simpleCtx.clearRect(0, 0, simpleCanvas.width, simpleCanvas.height);
    simpleCtx.save();
    
    // ✅ Déplacer l'origine au centre pour la rotation
    simpleCtx.translate(simpleCanvas.width / 2, simpleCanvas.height / 2);
    
    // Appliquer la rotation
    if (currentRotation !== 0) {
        simpleCtx.rotate((currentRotation * Math.PI) / 180);
    }
    
    // Appliquer le flip horizontal
    if (currentFlipH) {
        simpleCtx.scale(-1, 1);
    }
    
    // Appliquer les filtres CSS
    const filterString = `
        brightness(${filters.brightness}%)
        contrast(${filters.contrast}%)
        saturate(${filters.saturation}%)
        blur(${filters.blur}px)
    `;
    simpleCtx.filter = filterString;
    
    // ✅ Dessiner l'image centrée
    // Les dimensions à dessiner sont toujours basées sur l'orientation originale
    let drawWidth, drawHeight;
    if (isRotated90or270) {
        drawWidth = canvasHeight;
        drawHeight = canvasWidth;
    } else {
        drawWidth = canvasWidth;
        drawHeight = canvasHeight;
    }
    
    simpleCtx.drawImage(
        originalImage, 
        -drawWidth / 2, 
        -drawHeight / 2, 
        drawWidth, 
        drawHeight
    );
    
    simpleCtx.restore();
}

/**
 * Configurer les contrôles du mode simple
 */
function setupSimpleControls() {
    const controls = ['brightness', 'contrast', 'saturation', 'blur'];
    
    controls.forEach(control => {
        const slider = document.getElementById(control);
        const valueDisplay = document.getElementById(control + 'Value');
        
        if (slider && valueDisplay) {
            slider.addEventListener('input', function() {
                filters[control] = this.value;
                const unit = control === 'blur' ? 'px' : '%';
                valueDisplay.textContent = this.value + unit;
                drawSimpleCanvas();
            });
        }
    });
}

/**
 * Rotation de l'image
 */
function rotate(degrees) {
    // ✅ Simplement incrémenter la rotation
    currentRotation = (currentRotation + degrees) % 360;
    
    // ✅ drawSimpleCanvas() s'occupera d'ajuster les dimensions
    drawSimpleCanvas();
}

/**
 * Retournement horizontal
 */
function flipHorizontal() {
    currentFlipH = !currentFlipH;
    drawSimpleCanvas();
}

/**
 * Appliquer un filtre prédéfini
 */
function applyFilter(filterType) {
    switch(filterType) {
        case 'grayscale':
            filters.saturation = 0;
            document.getElementById('saturation').value = 0;
            document.getElementById('saturationValue').textContent = '0%';
            break;
        case 'sepia':
            filters.saturation = 50;
            filters.brightness = 110;
            filters.contrast = 90;
            updateSliders();
            break;
        case 'vintage':
            filters.saturation = 70;
            filters.brightness = 105;
            filters.contrast = 110;
            updateSliders();
            break;
    }
    drawSimpleCanvas();
}

/**
 * Réinitialiser tous les filtres
 */
function resetFilters() {
    filters = { brightness: 100, contrast: 100, saturation: 100, blur: 0 };
    currentRotation = 0;
    currentFlipH = false;
    
    // ✅ Réinitialiser aux dimensions de base
    if (baseWidth && baseHeight) {
        simpleCanvas.width = baseWidth;
        simpleCanvas.height = baseHeight;
    }
    
    updateSliders();
    drawSimpleCanvas();
}

/**
 * Réinitialiser le mode simple (appelé lors du changement de mode)
 */
function resetSimpleMode() {
    filters = { brightness: 100, contrast: 100, saturation: 100, blur: 0 };
    currentRotation = 0;
    currentFlipH = false;
    updateSliders();
}

/**
 * Mettre à jour les sliders avec les valeurs actuelles
 */
function updateSliders() {
    Object.keys(filters).forEach(key => {
        const slider = document.getElementById(key);
        const valueDisplay = document.getElementById(key + 'Value');
        if (slider && valueDisplay) {
            slider.value = filters[key];
            const unit = key === 'blur' ? 'px' : '%';
            valueDisplay.textContent = filters[key] + unit;
        }
    });
}

// Initialiser les contrôles au chargement
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setupSimpleControls);
} else {
    setupSimpleControls();
}