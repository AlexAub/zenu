/**
 * ÉDITEUR D'IMAGES - MODE AVANCÉ
 * Gestion du recadrage avec Cropper.js
 */

/**
 * Charger l'image dans le mode avancé
 */
function loadAdvancedMode(img) {
    const cropperImg = document.getElementById('cropperImage');
    cropperImg.src = img.src;
    cropperImg.style.display = 'block';
    simpleCanvas.style.display = 'none';
    document.getElementById('fabricCanvas').style.display = 'none';
    
    // IMPORTANT : Cacher le message empty state
    const emptyState = document.getElementById('emptyState');
    if (emptyState) {
        emptyState.style.display = 'none';
    }
    
    if (cropper) {
        cropper.destroy();
    }
    
    cropper = new Cropper(cropperImg, {
        viewMode: 1,
        dragMode: 'move',
        aspectRatio: NaN,
        autoCropArea: 1,
        restore: false,
        guides: true,
        center: true,
        highlight: false,
        cropBoxMovable: true,
        cropBoxResizable: true,
        toggleDragModeOnDblclick: false
    });
    
    // Rotation slider
    const rotateSlider = document.getElementById('cropRotate');
    const rotateValue = document.getElementById('rotateValue');
    
    if (rotateSlider && rotateValue) {
        rotateSlider.addEventListener('input', function() {
            cropper.rotateTo(this.value);
            rotateValue.textContent = this.value + '°';
        });
    }
}

/**
 * Définir le ratio d'aspect du recadrage
 */
function setCropRatio(ratio) {
    if (cropper) {
        cropper.setAspectRatio(ratio);
        
        // Mettre à jour l'état actif des boutons
        document.querySelectorAll('.aspect-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        
        const activeBtn = document.querySelector(`[onclick*="${ratio}"]`);
        if (activeBtn) {
            activeBtn.classList.add('active');
        }
    }
}

/**
 * Zoom sur le recadrage
 */
function cropZoom(delta) {
    if (!cropper) return;
    
    if (delta === 0) {
        cropper.reset();
    } else {
        cropper.zoom(delta);
    }
}

/**
 * Réinitialiser le recadrage
 */
function resetCrop() {
    if (cropper) {
        cropper.reset();
        const rotateSlider = document.getElementById('cropRotate');
        const rotateValue = document.getElementById('rotateValue');
        if (rotateSlider && rotateValue) {
            rotateSlider.value = 0;
            rotateValue.textContent = '0°';
        }
        
        // Réinitialiser les boutons d'aspect ratio
        document.querySelectorAll('.aspect-btn').forEach(btn => {
            btn.classList.remove('active');
        });
    }
}
