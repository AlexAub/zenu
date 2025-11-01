/**
 * ÉDITEUR D'IMAGES - MODE AVANCÉ V10
 * Calcul correct du zoom basé sur naturalWidth
 */

/**
 * Charger l'image dans le mode avancé
 */
function loadAdvancedMode(img) {
    const cropperImg = document.getElementById('cropperImage');
    const canvasArea = document.getElementById('canvasArea');
    
    cropperImg.src = img.src;
    cropperImg.style.display = 'block';
    
    simpleCanvas.style.display = 'none';
    document.getElementById('fabricCanvas').style.display = 'none';
    
    const emptyState = document.getElementById('emptyState');
    if (emptyState) {
        emptyState.style.display = 'none';
    }
    
    if (cropper) {
        cropper.destroy();
    }
    
    // V10 : Calcul correct du zoom
    cropper = new Cropper(cropperImg, {
        viewMode: 0,
        dragMode: 'move',
        aspectRatio: NaN,
        autoCropArea: 1,
        restore: false,
        guides: true,
        center: true,
        highlight: false,
        cropBoxMovable: true,
        cropBoxResizable: true,
        toggleDragModeOnDblclick: false,
        background: true,
        modal: true,
        responsive: false,
        checkOrientation: true,
        
        minCropBoxWidth: 50,
        minCropBoxHeight: 50,
        zoomable: true,
        zoomOnWheel: true,
        wheelZoomRatio: 0.1,
        
        ready: function() {
            try {
                const containerData = cropper.getContainerData();
                const imageData = cropper.getImageData();
                
                console.log('=== DEBUT CALCUL V10 ===');
                console.log('Container:', containerData.width, 'x', containerData.height);
                console.log('Image naturelle:', imageData.naturalWidth, 'x', imageData.naturalHeight);
                
                // Dimensions finales souhaitées
                let targetWidth, targetHeight;
                
                if (imageData.naturalWidth < containerData.width * 0.9 && 
                    imageData.naturalHeight < containerData.height * 0.9) {
                    // Image petite : taille réelle
                    targetWidth = imageData.naturalWidth;
                    targetHeight = imageData.naturalHeight;
                    console.log('-> Image petite, affichage taille reelle');
                } else {
                    // Image grande : réduire
                    const scaleX = (containerData.width * 0.85) / imageData.naturalWidth;
                    const scaleY = (containerData.height * 0.85) / imageData.naturalHeight;
                    const scale = Math.min(scaleX, scaleY);
                    targetWidth = imageData.naturalWidth * scale;
                    targetHeight = imageData.naturalHeight * scale;
                    console.log('-> Image grande, reduction a', (scale * 100).toFixed(0) + '%');
                }
                
                console.log('Cible:', targetWidth.toFixed(1), 'x', targetHeight.toFixed(1));
                
                // CALCUL CORRECT : ratio basé sur naturalWidth
                // targetWidth = naturalWidth × ratio
                // donc ratio = targetWidth / naturalWidth
                const targetRatio = targetWidth / imageData.naturalWidth;
                console.log('Ratio cible (naturalWidth):', targetRatio.toFixed(3));
                
                // Appliquer le zoom
                cropper.zoomTo(targetRatio);
                
                // Vérification et centrage
                setTimeout(function() {
                    const canvasData = cropper.getCanvasData();
                    console.log('Canvas apres zoom:', canvasData.width.toFixed(1), 'x', canvasData.height.toFixed(1));
                    
                    // Recentrer
                    cropper.setCanvasData({
                        left: (containerData.width - canvasData.width) / 2,
                        top: (containerData.height - canvasData.height) / 2,
                        width: canvasData.width,
                        height: canvasData.height
                    });
                    
                    // Vérification finale
                    setTimeout(function() {
                        const finalCanvas = cropper.getCanvasData();
                        const finalRatio = finalCanvas.width / imageData.naturalWidth;
                        
                        console.log('=== RESULTAT FINAL ===');
                        console.log('Taille obtenue:', finalCanvas.width.toFixed(1), 'x', finalCanvas.height.toFixed(1));
                        console.log('Taille voulue:', targetWidth.toFixed(1), 'x', targetHeight.toFixed(1));
                        console.log('Ratio final:', finalRatio.toFixed(3));
                        
                        const diff = Math.abs(finalCanvas.width - targetWidth);
                        if (diff < 2) {
                            console.log('SUCCESS!');
                        } else {
                            console.log('Ecart:', diff.toFixed(1), 'px');
                        }
                        
                        // Ajuster la cropBox à la taille de l'image
                        cropper.setCropBoxData({
                            left: finalCanvas.left,
                            top: finalCanvas.top,
                            width: finalCanvas.width,
                            height: finalCanvas.height
                        });
                        console.log('CropBox ajustee a la taille de l image');
                    }, 100);
                }, 100);
                
            } catch (error) {
                console.error('Erreur:', error);
            }
        }
    });
    
    // === ROTATION : Slider + Input numérique + Synchronisation ===
    const rotateSlider = document.getElementById('cropRotate');
    const rotateInput = document.getElementById('cropRotateInput');
    const rotateValue = document.getElementById('rotateValue');
    
    if (rotateSlider && rotateValue) {
        // Supprimer les anciens listeners
        const newRotateSlider = rotateSlider.cloneNode(true);
        rotateSlider.parentNode.replaceChild(newRotateSlider, rotateSlider);
        
        // Listener pour le SLIDER
        newRotateSlider.addEventListener('input', function() {
            if (cropper) {
                try {
                    const angle = parseFloat(this.value);
                    cropper.rotateTo(angle);
                    rotateValue.textContent = angle + '°';
                    
                    // Synchroniser l'input numérique
                    if (rotateInput) {
                        rotateInput.value = angle;
                    }
                } catch (error) {
                    console.error('Erreur rotation slider:', error);
                }
            }
        });
        
        // Listener pour l'INPUT NUMÉRIQUE
        if (rotateInput) {
            const newRotateInput = rotateInput.cloneNode(true);
            rotateInput.parentNode.replaceChild(newRotateInput, rotateInput);
            
            newRotateInput.addEventListener('input', function() {
                if (cropper) {
                    try {
                        let angle = parseFloat(this.value) || 0;
                        
                        // Limiter entre -180 et 180
                        if (angle < -180) angle = -180;
                        if (angle > 180) angle = 180;
                        this.value = angle;
                        
                        cropper.rotateTo(angle);
                        rotateValue.textContent = angle + '°';
                        
                        // Synchroniser le slider
                        const slider = document.getElementById('cropRotate');
                        if (slider) {
                            slider.value = angle;
                        }
                    } catch (error) {
                        console.error('Erreur rotation input:', error);
                    }
                }
            });
            
            // Validation sur Enter
            newRotateInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    this.blur();
                }
            });
        }
    }
}

/**
 * Rotation rapide (pour les boutons -45°, 0°, +45°)
 */
function rotateBy(angle) {
    if (!cropper) return;
    
    const rotateSlider = document.getElementById('cropRotate');
    const rotateInput = document.getElementById('cropRotateInput');
    const rotateValue = document.getElementById('rotateValue');
    
    try {
        if (angle === 0) {
            // Reset à 0
            cropper.rotateTo(0);
            if (rotateSlider) rotateSlider.value = 0;
            if (rotateInput) rotateInput.value = 0;
            if (rotateValue) rotateValue.textContent = '0°';
        } else {
            // Rotation cumulative
            const currentAngle = parseFloat(rotateSlider?.value || 0);
            let newAngle = currentAngle + angle;
            
            // Normaliser entre -180 et 180
            while (newAngle > 180) newAngle -= 360;
            while (newAngle < -180) newAngle += 360;
            
            cropper.rotateTo(newAngle);
            if (rotateSlider) rotateSlider.value = newAngle;
            if (rotateInput) rotateInput.value = newAngle;
            if (rotateValue) rotateValue.textContent = newAngle + '°';
        }
    } catch (error) {
        console.error('Erreur rotateBy:', error);
    }
}

/**
 * Définir le ratio d'aspect du recadrage
 */
function setCropRatio(ratio) {
    if (cropper) {
        try {
            cropper.setAspectRatio(ratio);
            
            document.querySelectorAll('.aspect-btn').forEach(function(btn) {
                btn.classList.remove('active');
            });
            
            const buttons = document.querySelectorAll('.aspect-btn');
            buttons.forEach(function(btn) {
                const onclick = btn.getAttribute('onclick');
                if (onclick && onclick.includes(String(ratio))) {
                    btn.classList.add('active');
                }
            });
        } catch (error) {
            console.error('Erreur setCropRatio:', error);
        }
    }
}

/**
 * Zoom sur le recadrage
 */
function cropZoom(delta) {
    if (!cropper) return;
    
    try {
        if (delta === 0) {
            // Reset
            const containerData = cropper.getContainerData();
            const imageData = cropper.getImageData();
            
            let targetWidth;
            if (imageData.naturalWidth < containerData.width * 0.9 && 
                imageData.naturalHeight < containerData.height * 0.9) {
                targetWidth = imageData.naturalWidth;
            } else {
                const scaleX = (containerData.width * 0.85) / imageData.naturalWidth;
                const scaleY = (containerData.height * 0.85) / imageData.naturalHeight;
                const scale = Math.min(scaleX, scaleY);
                targetWidth = imageData.naturalWidth * scale;
            }
            
            const targetRatio = targetWidth / imageData.naturalWidth;
            cropper.zoomTo(targetRatio);
            
            setTimeout(function() {
                const canvasData = cropper.getCanvasData();
                cropper.setCanvasData({
                    left: (containerData.width - canvasData.width) / 2,
                    top: (containerData.height - canvasData.height) / 2,
                    width: canvasData.width,
                    height: canvasData.height
                });
            }, 50);
        } else {
            cropper.zoom(delta);
        }
    } catch (error) {
        console.error('Erreur cropZoom:', error);
    }
}

/**
 * Réinitialiser le recadrage
 */
function resetCrop() {
    if (cropper) {
        try {
            cropZoom(0);
            
            const rotateSlider = document.getElementById('cropRotate');
            const rotateValue = document.getElementById('rotateValue');
            if (rotateSlider && rotateValue) {
                rotateSlider.value = 0;
                rotateValue.textContent = '0°';
            }
            
            cropper.rotateTo(0);
            
            document.querySelectorAll('.aspect-btn').forEach(function(btn) {
                btn.classList.remove('active');
            });
        } catch (error) {
            console.error('Erreur resetCrop:', error);
        }
    }
}