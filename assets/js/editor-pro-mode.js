/**
 * ÉDITEUR D'IMAGES - MODE PRO
 * Gestion des textes, formes et annotations avec Fabric.js
 */

/**
 * Charger l'image dans le mode Pro
 */
function loadProMode(img) {
    console.log('🔵 Chargement mode Pro...');
    
    document.getElementById('fabricCanvas').style.display = 'block';
    simpleCanvas.style.display = 'none';
    document.getElementById('cropperImage').style.display = 'none';
    
    // IMPORTANT : Cacher le message empty state
    const emptyState = document.getElementById('emptyState');
    if (emptyState) {
        emptyState.style.display = 'none';
    }
    
    // Initialiser ou réinitialiser Fabric.js APRÈS que le canvas soit visible
    if (!fabricCanvas) {
        console.log('🔵 Première initialisation de Fabric.js');
        // Première initialisation
        fabricCanvas = new fabric.Canvas('fabricCanvas', {
            selection: true,
            interactive: true,
            enableRetinaScaling: false,  // DÉSACTIVÉ pour éviter le problème de scaling
            preserveObjectStacking: true,
            renderOnAddRemove: true,
            skipTargetFind: false,
            perPixelTargetFind: true,
            targetFindTolerance: 5,
            selectionColor: 'rgba(102, 126, 234, 0.1)',
            selectionBorderColor: '#667eea',
            selectionLineWidth: 2
        });
        
        // Ajouter les event listeners
        setupFabricEventListeners();
        
        console.log('✅ Fabric.js initialisé pour la première fois');
    } else {
        console.log('🔵 Réinitialisation de Fabric.js');
        // Canvas déjà initialisé, juste le nettoyer
        fabricCanvas.clear();
        fabricCanvas.backgroundColor = null;
        fabricCanvas.backgroundImage = null;
    }
    
    // Charger l'image de fond
    fabric.Image.fromURL(img.src, function(fabricImg) {
        console.log('🔵 Image chargée dans Fabric.js');
        
        // Calculer les dimensions pour que l'image tienne dans le canvas
        const maxWidth = 800;
        const maxHeight = 600;
        let scale = 1;
        
        if (fabricImg.width > maxWidth || fabricImg.height > maxHeight) {
            scale = Math.min(
                maxWidth / fabricImg.width,
                maxHeight / fabricImg.height
            );
        }
        
        // Redimensionner le canvas pour correspondre à l'image
        const canvasWidth = fabricImg.width * scale;
        const canvasHeight = fabricImg.height * scale;
        
        fabricCanvas.setWidth(canvasWidth);
        fabricCanvas.setHeight(canvasHeight);
        
        console.log('🔵 Canvas redimensionné à:', canvasWidth, 'x', canvasHeight);
        
        fabricImg.set({
            scaleX: scale,
            scaleY: scale,
            selectable: false,
            evented: false,
            hoverCursor: 'default'
        });
        
        // Utiliser setBackgroundImage au lieu de add() pour que l'image ne bloque pas
        fabricCanvas.setBackgroundImage(fabricImg, function() {
            console.log('🔵 Image de fond définie');
            
            // CRITIQUE : Réactiver complètement toutes les interactions
            fabricCanvas.selection = true;
            fabricCanvas.interactive = true;
            fabricCanvas.skipTargetFind = false;
            fabricCanvas.defaultCursor = 'default';
            
            // IMPORTANT : S'assurer que le canvas reçoit les événements de souris
            const canvasElement = fabricCanvas.getElement();
            const upperCanvas = fabricCanvas.upperCanvasEl;
            const lowerCanvas = fabricCanvas.lowerCanvasEl;
            const container = canvasElement?.parentElement;
            
            console.log('🔵 Configuration des z-index et dimensions...');
            
            if (container) {
                container.style.position = 'relative';
                container.style.zIndex = '999';
                container.style.pointerEvents = 'auto';
                console.log('✅ Container configuré');
            }
            
            if (canvasElement) {
                canvasElement.style.pointerEvents = 'auto';
                canvasElement.style.zIndex = '999';
                console.log('✅ Canvas element configuré');
            }
            
            // CRITIQUE : Forcer les dimensions du upper canvas
            if (upperCanvas) {
                upperCanvas.style.pointerEvents = 'auto';
                upperCanvas.style.position = 'absolute';
                upperCanvas.style.zIndex = '1000';
                upperCanvas.style.touchAction = 'none';
                upperCanvas.style.left = '0';
                upperCanvas.style.top = '0';
                
                // SOLUTION : Synchroniser les dimensions canvas et CSS
                // Fabric.js peut appliquer un ratio devicePixel, on force la même taille partout
                const computedStyle = window.getComputedStyle(lowerCanvas);
                const cssWidth = parseInt(computedStyle.width);
                const cssHeight = parseInt(computedStyle.height);
                
                console.log('🔵 Lower canvas CSS:', cssWidth, 'x', cssHeight);
                console.log('🔵 Upper canvas avant:', upperCanvas.width, 'x', upperCanvas.height);
                
                // Forcer les mêmes dimensions CSS que le lower canvas
                upperCanvas.style.width = cssWidth + 'px';
                upperCanvas.style.height = cssHeight + 'px';
                
                // ET forcer les mêmes dimensions physiques
                upperCanvas.width = cssWidth;
                upperCanvas.height = cssHeight;
                
                console.log('✅ Upper canvas configuré - Z-INDEX: 1000');
                console.log('✅ Upper canvas dimensions:', upperCanvas.width, 'x', upperCanvas.height);
                console.log('✅ Upper canvas CSS:', upperCanvas.style.width, 'x', upperCanvas.style.height);
            }
            
            // S'assurer que le lower canvas a aussi les bonnes dimensions
            if (lowerCanvas) {
                lowerCanvas.width = canvasWidth;
                lowerCanvas.height = canvasHeight;
                console.log('✅ Lower canvas dimensions:', canvasWidth, 'x', canvasHeight);
            }
            
            // Forcer le rendu
            fabricCanvas.renderAll();
            
            // IMPORTANT : Recalculer les offsets après avoir modifié les dimensions
            fabricCanvas.calcOffset();
            
            console.log('✅ Canvas Pro chargé - Interactions activées');
            console.log('Canvas dimensions:', fabricCanvas.width, 'x', fabricCanvas.height);
            console.log('Selection:', fabricCanvas.selection);
            console.log('Interactive:', fabricCanvas.interactive);
            
            // Test de vérification avec un délai
            setTimeout(() => {
                const rect = upperCanvas?.getBoundingClientRect();
                const computedWidth = window.getComputedStyle(upperCanvas).width;
                const computedHeight = window.getComputedStyle(upperCanvas).height;
                
                console.log('📊 ===== DIAGNOSTIC FINAL =====');
                console.log('📊 Upper canvas rect:', rect);
                console.log('📊 Upper canvas.width:', upperCanvas?.width, 'canvas.height:', upperCanvas?.height);
                console.log('📊 Upper canvas style:', upperCanvas?.style.width, 'x', upperCanvas?.style.height);
                console.log('📊 Upper canvas computed:', computedWidth, 'x', computedHeight);
                console.log('📊 Upper canvas z-index:', window.getComputedStyle(upperCanvas).zIndex);
                console.log('📊 getBoundingClientRect:', 'width=' + rect.width, 'height=' + rect.height);
                
                // Vérification finale
                if (rect.width === 0 || rect.height === 0) {
                    console.error('❌ PROBLÈME CRITIQUE : Upper canvas a une taille de 0 !');
                    console.log('🔧 Tentative de correction d\'urgence...');
                    
                    // Forcer avec !important via setAttribute
                    upperCanvas.setAttribute('style', 
                        'pointer-events: auto !important; ' +
                        'position: absolute !important; ' +
                        'z-index: 1000 !important; ' +
                        'left: 0 !important; ' +
                        'top: 0 !important; ' +
                        'width: ' + canvasWidth + 'px !important; ' +
                        'height: ' + canvasHeight + 'px !important;'
                    );
                    fabricCanvas.calcOffset();
                    
                    console.log('🔧 Correction appliquée, vérifiez à nouveau');
                } else {
                    console.log('✅ Upper canvas a les bonnes dimensions !');
                    console.log('✅ Les clics devraient maintenant fonctionner !');
                }
                console.log('📊 ===== FIN DIAGNOSTIC =====');
            }, 200);
        });
    }, { crossOrigin: 'anonymous' });
}

/**
 * Configurer les event listeners de Fabric.js
 */
function setupFabricEventListeners() {
    fabricCanvas.on('mouse:down', function(e) {
        console.log('🖱️ Click détecté sur canvas', e.target ? 'sur objet: ' + e.target.type : 'sur canvas vide');
        if (e.target) {
            console.log('Type d\'objet:', e.target.type);
        }
    });
    
    fabricCanvas.on('selection:created', function(e) {
        console.log('✅ Sélection créée:', e.selected);
        updateObjectControls();
    });
    
    fabricCanvas.on('selection:updated', function(e) {
        console.log('✅ Sélection mise à jour:', e.selected);
        updateObjectControls();
    });
    
    fabricCanvas.on('selection:cleared', function() {
        console.log('❌ Sélection effacée');
        const indicator = document.getElementById('selectionIndicator');
        const modifyControls = document.getElementById('modifyControls');
        if (indicator) indicator.style.display = 'none';
        if (modifyControls) modifyControls.style.display = 'none';
    });
    
    // Test de diagnostic au niveau du canvas DOM
    const canvasEl = fabricCanvas.getElement();
    if (canvasEl) {
        canvasEl.addEventListener('click', function(e) {
            console.log('🖱️ Click natif sur canvas element', e);
        });
    }
}

/**
 * Mettre à jour les contrôles quand un objet est sélectionné
 */
function updateObjectControls() {
    const indicator = document.getElementById('selectionIndicator');
    const modifyControls = document.getElementById('modifyControls');
    const obj = fabricCanvas.getActiveObject();
    
    if (indicator) indicator.style.display = 'block';
    if (modifyControls && obj) {
        modifyControls.style.display = 'block';
        document.getElementById('objectOpacity').value = (obj.opacity || 1) * 100;
        document.getElementById('objectRotation').value = obj.angle || 0;
        
        // Si c'est un texte, charger ses propriétés
        if (obj.type === 'i-text' || obj.type === 'textbox') {
            document.getElementById('textFont').value = obj.fontFamily || 'Arial';
            document.getElementById('textSize').value = obj.fontSize || 40;
            document.getElementById('textColor').value = obj.fill || '#ffffff';
            document.getElementById('textStrokeColor').value = obj.stroke || '#000000';
            document.getElementById('textStrokeWidth').value = obj.strokeWidth || 0;
        }
    }
}

// ===== FONCTIONS TEXTE =====

/**
 * Ajouter du texte sur le canvas
 */
function addText() {
    const textInput = document.getElementById('textInput');
    const textColor = document.getElementById('textColor');
    const textSize = document.getElementById('textSize');
    const textFont = document.getElementById('textFont');
    const textStrokeColor = document.getElementById('textStrokeColor');
    const textStrokeWidth = document.getElementById('textStrokeWidth');
    const textShadow = document.getElementById('textShadow');
    
    if (!textInput.value) {
        alert('⚠️ Veuillez entrer un texte');
        return;
    }
    
    const textOptions = {
        left: 100,
        top: 100,
        fontSize: parseInt(textSize.value),
        fill: textColor.value,
        stroke: textStrokeColor.value,
        strokeWidth: parseInt(textStrokeWidth.value),
        fontFamily: textFont.value,
        editable: true,
        selectable: true,
        evented: true,
        hasControls: true,
        hasBorders: true,
        lockUniScaling: false,
        borderColor: '#667eea',
        cornerColor: '#667eea',
        cornerSize: 12,
        cornerStyle: 'circle',
        transparentCorners: false,
        borderOpacityWhenMoving: 0.5,
        hoverCursor: 'move',
        moveCursor: 'move'
    };
    
    // Ajouter l'ombre si activée
    if (textShadow.checked) {
        textOptions.shadow = {
            color: 'rgba(0,0,0,0.9)',
            blur: 20,
            offsetX: 10,
            offsetY: 10
        };
    }
    
    const text = new fabric.IText(textInput.value, textOptions);
    
    fabricCanvas.add(text);
    fabricCanvas.setActiveObject(text);
    fabricCanvas.centerObject(text);
    
    // IMPORTANT : Mettre à jour les coordonnées de l'objet
    text.setCoords();
    
    // Forcer le rendu complet
    fabricCanvas.renderAll();
    fabricCanvas.requestRenderAll();
    
    // Recalculer les offsets du canvas après ajout
    setTimeout(() => {
        fabricCanvas.calcOffset();
        text.setCoords();
        fabricCanvas.requestRenderAll();
        console.log('✅ Texte ajouté et coordonnées mises à jour');
    }, 50);
    
    textInput.value = '';
}

/**
 * Basculer le style de texte (gras, italique, etc.)
 */
function toggleTextStyle(style) {
    const activeObject = fabricCanvas.getActiveObject();
    if (!activeObject || (activeObject.type !== 'i-text' && activeObject.type !== 'textbox')) {
        alert('⚠️ Veuillez sélectionner un texte d\'abord');
        return;
    }
    
    switch(style) {
        case 'bold':
            activeObject.set('fontWeight', activeObject.fontWeight === 'bold' ? 'normal' : 'bold');
            break;
        case 'italic':
            activeObject.set('fontStyle', activeObject.fontStyle === 'italic' ? 'normal' : 'italic');
            break;
        case 'underline':
            activeObject.set('underline', !activeObject.underline);
            break;
        case 'linethrough':
            activeObject.set('linethrough', !activeObject.linethrough);
            break;
    }
    
    fabricCanvas.renderAll();
}

/**
 * Définir l'alignement du texte
 */
function setTextAlign(align) {
    const activeObject = fabricCanvas.getActiveObject();
    if (!activeObject || (activeObject.type !== 'i-text' && activeObject.type !== 'textbox')) {
        alert('⚠️ Veuillez sélectionner un texte d\'abord');
        return;
    }
    
    activeObject.set('textAlign', align);
    activeObject.setCoords();
    fabricCanvas.renderAll();
}

/**
 * Mettre à jour la police du texte sélectionné
 */
function updateSelectedTextFont() {
    const activeObject = fabricCanvas.getActiveObject();
    if (!activeObject || (activeObject.type !== 'i-text' && activeObject.type !== 'textbox')) return;
    
    const font = document.getElementById('textFont').value;
    activeObject.set('fontFamily', font);
    fabricCanvas.renderAll();
}

/**
 * Mettre à jour la taille du texte sélectionné
 */
function updateSelectedTextSize() {
    const activeObject = fabricCanvas.getActiveObject();
    if (!activeObject || (activeObject.type !== 'i-text' && activeObject.type !== 'textbox')) return;
    
    const size = parseInt(document.getElementById('textSize').value);
    activeObject.set('fontSize', size);
    fabricCanvas.renderAll();
}

/**
 * Mettre à jour la couleur du texte sélectionné
 */
function updateSelectedTextColor() {
    const activeObject = fabricCanvas.getActiveObject();
    if (!activeObject || (activeObject.type !== 'i-text' && activeObject.type !== 'textbox')) return;
    
    const color = document.getElementById('textColor').value;
    activeObject.set('fill', color);
    fabricCanvas.renderAll();
}

/**
 * Mettre à jour le contour du texte sélectionné
 */
function updateSelectedTextStroke() {
    const activeObject = fabricCanvas.getActiveObject();
    if (!activeObject || (activeObject.type !== 'i-text' && activeObject.type !== 'textbox')) return;
    
    const strokeColor = document.getElementById('textStrokeColor').value;
    const strokeWidth = parseInt(document.getElementById('textStrokeWidth').value);
    activeObject.set({
        stroke: strokeColor,
        strokeWidth: strokeWidth
    });
    fabricCanvas.renderAll();
}

/**
 * Mettre à jour l'ombre du texte sélectionné
 */
function updateSelectedTextShadow() {
    const activeObject = fabricCanvas.getActiveObject();
    if (!activeObject || (activeObject.type !== 'i-text' && activeObject.type !== 'textbox')) return;
    
    const hasShadow = document.getElementById('textShadow').checked;
    
    if (hasShadow) {
        activeObject.set('shadow', {
            color: 'rgba(0,0,0,0.9)',
            blur: 20,
            offsetX: 10,
            offsetY: 10
        });
    } else {
        activeObject.set('shadow', null);
    }
    
    fabricCanvas.renderAll();
}

// ===== FONCTIONS FORMES =====

/**
 * Ajouter une forme sur le canvas
 */
function addShape(type) {
    const fillColor = document.getElementById('shapeFillColor').value;
    const strokeColor = document.getElementById('shapeStrokeColor').value;
    const opacity = parseInt(document.getElementById('shapeOpacity').value) / 100;
    const strokeWidth = parseInt(document.getElementById('shapeStrokeWidth').value);
    const strokeDash = document.getElementById('shapeStrokeDash').value;
    const roundedCorners = parseInt(document.getElementById('shapeRoundedCorners').value);
    
    let shape;
    
    // Convertir le style de trait en array pour Fabric.js
    let strokeDashArray = null;
    if (strokeDash === 'dashed') {
        strokeDashArray = [10, 5];
    } else if (strokeDash === 'dotted') {
        strokeDashArray = [2, 3];
    }
    
    // Options communes
    const commonOptions = {
        fill: fillColor,
        stroke: strokeColor,
        strokeWidth: strokeWidth,
        strokeDashArray: strokeDashArray,
        opacity: opacity,
        selectable: true,
        evented: true,
        hasControls: true,
        hasBorders: true,
        lockUniScaling: false,
        borderColor: '#667eea',
        cornerColor: '#667eea',
        cornerSize: 12,
        cornerStyle: 'circle',
        transparentCorners: false,
        borderOpacityWhenMoving: 0.5,
        hoverCursor: 'move',
        moveCursor: 'move'
    };
    
    switch(type) {
        case 'rect':
            shape = new fabric.Rect({
                left: 100,
                top: 100,
                width: 150,
                height: 100,
                rx: roundedCorners,
                ry: roundedCorners,
                ...commonOptions
            });
            break;
        case 'circle':
            shape = new fabric.Circle({
                left: 100,
                top: 100,
                radius: 50,
                ...commonOptions
            });
            break;
        case 'triangle':
            shape = new fabric.Triangle({
                left: 100,
                top: 100,
                width: 100,
                height: 100,
                ...commonOptions
            });
            break;
        case 'line':
            shape = new fabric.Line([50, 100, 200, 100], {
                stroke: strokeColor,
                strokeWidth: strokeWidth,
                strokeDashArray: strokeDashArray,
                ...commonOptions,
                fill: null
            });
            break;
        case 'arrow':
            shape = new fabric.Path('M 0 0 L 100 0 L 100 -10 L 120 10 L 100 30 L 100 20 L 0 20 z', {
                left: 100,
                top: 100,
                ...commonOptions
            });
            break;
        case 'star':
            shape = new fabric.Path('M 50 0 L 61 35 L 98 35 L 68 57 L 79 91 L 50 70 L 21 91 L 32 57 L 2 35 L 39 35 z', {
                left: 100,
                top: 100,
                scaleX: 0.8,
                scaleY: 0.8,
                ...commonOptions
            });
            break;
        case 'polygon':
            // Hexagone
            const points = [];
            const sides = 6;
            const radius = 50;
            for (let i = 0; i < sides; i++) {
                points.push({
                    x: radius * Math.cos(i * 2 * Math.PI / sides),
                    y: radius * Math.sin(i * 2 * Math.PI / sides)
                });
            }
            shape = new fabric.Polygon(points, {
                left: 100,
                top: 100,
                ...commonOptions
            });
            break;
        case 'heart':
            shape = new fabric.Path('M 50 20 C 20 -10, -10 20, 20 50 L 50 80 L 80 50 C 110 20, 80 -10, 50 20 z', {
                left: 100,
                top: 100,
                scaleX: 0.7,
                scaleY: 0.7,
                ...commonOptions
            });
            break;
    }
    
    if (shape) {
        fabricCanvas.add(shape);
        fabricCanvas.setActiveObject(shape);
        fabricCanvas.centerObject(shape);
        
        // IMPORTANT : Mettre à jour les coordonnées de l'objet
        shape.setCoords();
        
        // Forcer le rendu complet
        fabricCanvas.renderAll();
        fabricCanvas.requestRenderAll();
        
        // Recalculer les offsets après ajout
        setTimeout(() => {
            fabricCanvas.calcOffset();
            shape.setCoords();
            fabricCanvas.requestRenderAll();
            console.log('✅ Forme ajoutée et coordonnées mises à jour');
        }, 50);
    }
}

// ===== FONCTIONS DE MANIPULATION D'OBJETS =====

/**
 * Supprimer l'objet sélectionné
 */
function deleteSelected() {
    const activeObject = fabricCanvas.getActiveObject();
    if (activeObject) {
        fabricCanvas.remove(activeObject);
        fabricCanvas.renderAll();
        alert('✅ Élément supprimé');
    } else {
        alert('⚠️ Aucun élément sélectionné. Cliquez d\'abord sur un élément pour le sélectionner.');
    }
}

/**
 * Effacer tous les objets du canvas (sauf l'image de fond)
 */
function clearCanvas() {
    if (confirm('⚠️ Supprimer tous les éléments (l\'image de fond sera conservée) ?')) {
        const objects = fabricCanvas.getObjects();
        objects.forEach(obj => {
            fabricCanvas.remove(obj);
        });
        fabricCanvas.renderAll();
    }
}

/**
 * Dupliquer l'objet sélectionné
 */
function duplicateSelected() {
    const activeObject = fabricCanvas.getActiveObject();
    if (!activeObject) {
        alert('⚠️ Aucun élément sélectionné');
        return;
    }
    
    activeObject.clone(function(cloned) {
        cloned.set({
            left: cloned.left + 20,
            top: cloned.top + 20
        });
        fabricCanvas.add(cloned);
        fabricCanvas.setActiveObject(cloned);
        
        // Mettre à jour les coordonnées
        cloned.setCoords();
        fabricCanvas.renderAll();
        fabricCanvas.requestRenderAll();
        
        setTimeout(() => {
            fabricCanvas.calcOffset();
            cloned.setCoords();
            fabricCanvas.requestRenderAll();
        }, 50);
    });
}

/**
 * Mettre à jour l'opacité de l'objet sélectionné
 */
function updateSelectedObjectOpacity() {
    const activeObject = fabricCanvas.getActiveObject();
    if (!activeObject) return;
    
    const opacity = parseInt(document.getElementById('objectOpacity').value) / 100;
    activeObject.set('opacity', opacity);
    fabricCanvas.renderAll();
}

/**
 * Mettre à jour la rotation de l'objet sélectionné
 */
function updateSelectedObjectRotation() {
    const activeObject = fabricCanvas.getActiveObject();
    if (!activeObject) return;
    
    const rotation = parseInt(document.getElementById('objectRotation').value);
    activeObject.set('angle', rotation);
    fabricCanvas.renderAll();
}

/**
 * Amener l'objet sélectionné au premier plan
 */
function bringToFront() {
    const activeObject = fabricCanvas.getActiveObject();
    if (!activeObject) {
        alert('⚠️ Aucun élément sélectionné');
        return;
    }
    fabricCanvas.bringToFront(activeObject);
    fabricCanvas.renderAll();
}

/**
 * Envoyer l'objet sélectionné à l'arrière-plan
 */
function sendToBack() {
    const activeObject = fabricCanvas.getActiveObject();
    if (!activeObject) {
        alert('⚠️ Aucun élément sélectionné');
        return;
    }
    fabricCanvas.sendToBack(activeObject);
    fabricCanvas.renderAll();
}

/**
 * Retourner l'objet horizontalement
 */
function flipObjectH() {
    const activeObject = fabricCanvas.getActiveObject();
    if (!activeObject) {
        alert('⚠️ Aucun élément sélectionné');
        return;
    }
    activeObject.set('flipX', !activeObject.flipX);
    fabricCanvas.renderAll();
}

/**
 * Retourner l'objet verticalement
 */
function flipObjectV() {
    const activeObject = fabricCanvas.getActiveObject();
    if (!activeObject) {
        alert('⚠️ Aucun élément sélectionné');
        return;
    }
    activeObject.set('flipY', !activeObject.flipY);
    fabricCanvas.renderAll();
}