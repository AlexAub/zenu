/**
 * ÉDITEUR D'IMAGES - MODE SIMPLE
 * Gestion des filtres, rotations et retournements
 */

// Variables du mode simple
let currentRotation = 0;
let currentFlipH = false;
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
    simpleCanvas.width = Math.min(img.width, 1000);
    simpleCanvas.height = (simpleCanvas.width / img.width) * img.height;
    simpleCanvas.style.display = 'block';
    document.getElementById('cropperImage').style.display = 'none';
    document.getElementById('fabricCanvas').style.display = 'none';
    
    // IMPORTANT : Cacher le message empty state
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
    
    simpleCtx.clearRect(0, 0, simpleCanvas.width, simpleCanvas.height);
    simpleCtx.save();
    
    // Appliquer rotation
    if (currentRotation !== 0) {
        simpleCtx.translate(simpleCanvas.width / 2, simpleCanvas.height / 2);
        simpleCtx.rotate((currentRotation * Math.PI) / 180);
        simpleCtx.translate(-simpleCanvas.width / 2, -simpleCanvas.height / 2);
    }
    
    // Appliquer flip
    if (currentFlipH) {
        simpleCtx.translate(simpleCanvas.width, 0);
        simpleCtx.scale(-1, 1);
    }
    
    // Appliquer filtres CSS
    const filterString = `
        brightness(${filters.brightness}%)
        contrast(${filters.contrast}%)
        saturate(${filters.saturation}%)
        blur(${filters.blur}px)
    `;
    simpleCtx.filter = filterString;
    
    simpleCtx.drawImage(originalImage, 0, 0, simpleCanvas.width, simpleCanvas.height);
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
    currentRotation = (currentRotation + degrees) % 360;
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
