/**
 * ÉDITEUR D'IMAGES - MODE AVANCÉ V10
 * Calcul correct du zoom basé sur naturalWidth
 * ✅ CORRECTION: Synchronisation du slider et de l'input numérique
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
                console.log('Image naturalWidth:', imageData.naturalWidth, 'x', imageData.naturalHeight);
                
                // Calculer la taille cible
                let targetWidth;
                const targetHeight = (targetWidth / imageData.naturalWidth) * imageData.naturalHeight;
                
                // Si l'image est déjà petite, la garder à sa taille naturelle
                if (imageData.naturalWidth < containerData.width * 0.9 && 
                    imageData.naturalHeight < containerData.height * 0.9) {
                    targetWidth = imageData.naturalWidth;
                } else {
                    const scaleX = (containerData.width * 0.85) / imageData.naturalWidth;
                    const scaleY = (containerData.height * 0.85) / imageData.naturalHeight;
                    const scale = Math.min(scaleX, scaleY);
                    targetWidth = imageData.naturalWidth * scale;
                }
                
                console.log('Taille cible:', targetWidth.toFixed(1), 'x', targetHeight.toFixed(1));
                
                // Calculer le ratio nécessaire
                const targetRatio = targetWidth / imageData.naturalWidth;
                console.log('Ratio cible:', targetRatio.toFixed(3));
                console.log('Zoom actuel (imageData.width / naturalWidth):', (imageData.width / imageData.naturalWidth).toFixed(3));
                
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
                            console.log('SUCCESS! Ecart < 2px');
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
        
        // ✅ CORRECTION: Listener pour le SLIDER - Re-sélectionner les éléments dans le listener
        newRotateSlider.addEventListener('input', function() {
            if (cropper) {
                try {
                    const angle = parseFloat(this.value);
                    cropper.rotateTo(angle);
                    
                    // ✅ RE-SÉLECTIONNER les éléments à chaque fois (CRITIQUE car ils peuvent être clonés)
                    const rotateValueSpan = document.getElementById('rotateValue');
                    const rotateInputField = document.getElementById('cropRotateInput');
                    
                    console.log('🔵 Slider changé:', angle, '- Input trouvé?', !!rotateInputField);
                    
                    // Mettre à jour l'affichage violet
                    if (rotateValueSpan) {
                        rotateValueSpan.textContent = angle + '°';
                    }
                    
                    // ✅ Synchroniser l'input numérique (le champ blanc)
                    if (rotateInputField) {
                        rotateInputField.value = angle;
                        console.log('✅ Input mis à jour:', rotateInputField.value);
                    } else {
                        console.error('❌ Input cropRotateInput non trouvé dans le DOM!');
                    }
                } catch (error) {
                    console.error('Erreur rotation slider:', error);
                }
            }
        });
        
        // ✅ Listener pour l'INPUT NUMÉRIQUE
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
                        
                        // ✅ RE-SÉLECTIONNER les éléments
                        const rotateValueSpan = document.getElementById('rotateValue');
                        const rotateSliderEl = document.getElementById('cropRotate');
                        
                        if (rotateValueSpan) {
                            rotateValueSpan.textContent = angle + '°';
                        }
                        
                        // Synchroniser le slider
                        if (rotateSliderEl) {
                            rotateSliderEl.value = angle;
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
            const rotateInput = document.getElementById('cropRotateInput');
            const rotateValue = document.getElementById('rotateValue');
            if (rotateSlider && rotateValue) {
                rotateSlider.value = 0;
                rotateValue.textContent = '0°';
            }
            if (rotateInput) {
                rotateInput.value = 0;
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