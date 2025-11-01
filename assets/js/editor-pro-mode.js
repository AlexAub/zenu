/**
 * ÉDITEUR D'IMAGES - MODE PRO (VERSION CORRIGÉE)
 * Gestion des textes, formes et annotations avec Fabric.js
 * 
 * ⚠️ CORRECTION : Ajout de setCoords() pour résoudre le décalage des points bleus
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
        
        // ⭐ AJOUT : Listener pour recalculer lors du resize de la fenêtre
        window.addEventListener('resize', function() {
            if (fabricCanvas) {
                fabricCanvas.calcOffset();
                fabricCanvas.getObjects().forEach(obj => obj.setCoords());
                fabricCanvas.renderAll();
            }
        });
        
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
        
        // Redimensionner le canvas aux dimensions de l'image
        const canvasWidth = Math.round(fabricImg.width * scale);
        const canvasHeight = Math.round(fabricImg.height * scale);
        
        fabricCanvas.setDimensions({
            width: canvasWidth,
            height: canvasHeight
        });
        
        // Mettre à l'échelle l'image
        fabricImg.scale(scale);
        
        // Définir comme image de fond
        fabricCanvas.setBackgroundImage(fabricImg, fabricCanvas.renderAll.bind(fabricCanvas), {
            scaleX: scale,
            scaleY: scale
        });
        
        // ⭐ AJOUT : Recalculer les offsets après chargement
        setTimeout(() => {
            fabricCanvas.calcOffset();
            fabricCanvas.getObjects().forEach(obj => obj.setCoords());
            fabricCanvas.renderAll();
            console.log('✅ Coordonnées recalculées après chargement');
        }, 100);
        
        console.log('✅ Image de fond définie');
    }, { crossOrigin: 'anonymous' });
}

/**
 * Configurer les event listeners de Fabric.js
 * ⭐ VERSION CORRIGÉE avec setCoords()
 */
function setupFabricEventListeners() {
    fabricCanvas.on('mouse:down', function(e) {
        console.log('🖱️ Click détecté sur canvas', e.target ? 'sur objet: ' + e.target.type : 'sur canvas vide');
        if (e.target) {
            console.log('Type d\'objet:', e.target.type);
        }
    });
    
    // ⭐ CORRECTION : Ajouter setCoords() pendant le déplacement
    fabricCanvas.on('object:moving', function(e) {
        console.log('Objet en mouvement:', e.target.type);
        e.target.setCoords(); // IMPORTANT : Recalculer les coordonnées pendant le déplacement
    });
    
    // ⭐ CORRECTION : Ajouter setCoords() pendant le redimensionnement
    fabricCanvas.on('object:scaling', function(e) {
        console.log('Objet en redimensionnement:', e.target.type);
        e.target.setCoords(); // IMPORTANT : Recalculer les coordonnées pendant le scaling
    });
    
    // ⭐ AJOUT : Event pour la rotation
    fabricCanvas.on('object:rotating', function(e) {
        console.log('Objet en rotation:', e.target.type);
        e.target.setCoords(); // IMPORTANT : Recalculer les coordonnées pendant la rotation
    });
    
    // ⭐ AJOUT : Event après modification terminée
    fabricCanvas.on('object:modified', function(e) {
        console.log('Objet modifié:', e.target.type);
        e.target.setCoords(); // IMPORTANT : Recalculer les coordonnées après modification
        fabricCanvas.renderAll();
    });
    
    fabricCanvas.on('selection:created', function(e) {
        console.log('✅ Sélection créée:', e.selected);
        // ⭐ AJOUT : Recalculer les coords lors de la sélection
        if (e.selected && e.selected.length > 0) {
            e.selected.forEach(obj => obj.setCoords());
        }
        updateObjectControls();
    });
    
    fabricCanvas.on('selection:updated', function(e) {
        console.log('✅ Sélection mise à jour:', e.selected);
        // ⭐ AJOUT : Recalculer les coords lors de la mise à jour de sélection
        if (e.selected && e.selected.length > 0) {
            e.selected.forEach(obj => obj.setCoords());
        }
        updateObjectControls();
    });
    
    fabricCanvas.on('selection:cleared', function() {
        console.log('❌ Sélection effacée');
        const indicator = document.getElementById('selectionIndicator');
        const modifyControls = document.getElementById('modifyControls');
        if (indicator) indicator.style.display = 'none';
        if (modifyControls) modifyControls.style.display = 'none';
    });
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

// ===== FONCTIONS DE GESTION DU TEXTE =====

/**
 * Ajouter du texte au canvas
 * ⭐ VERSION CORRIGÉE avec setCoords()
 */
function addText() {
    const textInput = document.getElementById('textInput');
    const textFont = document.getElementById('textFont');
    const textSize = document.getElementById('textSize');
    const textColor = document.getElementById('textColor');
    const textStrokeColor = document.getElementById('textStrokeColor');
    const textStrokeWidth = document.getElementById('textStrokeWidth');
    const textShadow = document.getElementById('textShadow');
    
    if (!textInput.value.trim()) {
        alert('⚠️ Veuillez entrer du texte');
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
    
    // ⭐ CORRECTION : Recalculer immédiatement les coordonnées
    text.setCoords();
    
    fabricCanvas.renderAll();
    fabricCanvas.requestRenderAll();
    
    // ⭐ CORRECTION : Recalculer aussi après un court délai pour être sûr
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
 * ⭐ VERSION CORRIGÉE avec setCoords()
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
    
    // ⭐ CORRECTION : Recalculer après changement de style
    activeObject.setCoords();
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
    
    // Convertir IText en Textbox pour l'alignement si nécessaire
    if (activeObject.type === 'i-text' && !activeObject.text.includes('\n')) {
        const textbox = new fabric.Textbox(activeObject.text, {
            left: activeObject.left,
            top: activeObject.top,
            width: Math.max(activeObject.width * 2, 300),
            fontSize: activeObject.fontSize,
            fill: activeObject.fill,
            stroke: activeObject.stroke,
            strokeWidth: activeObject.strokeWidth,
            fontFamily: activeObject.fontFamily,
            fontWeight: activeObject.fontWeight,
            fontStyle: activeObject.fontStyle,
            underline: activeObject.underline,
            linethrough: activeObject.linethrough,
            textAlign: align,
            editable: true,
            selectable: true,
            evented: true,
            hasControls: true,
            hasBorders: true,
            borderColor: '#667eea',
            cornerColor: '#667eea',
            cornerSize: 12,
            hoverCursor: 'move',
            moveCursor: 'move'
        });
        
        if (activeObject.shadow) {
            textbox.set('shadow', activeObject.shadow);
        }
        
        fabricCanvas.remove(activeObject);
        fabricCanvas.add(textbox);
        fabricCanvas.setActiveObject(textbox);
        
        // ⭐ CORRECTION : Recalculer après conversion
        textbox.setCoords();
        fabricCanvas.renderAll();
        
        alert('💡 Texte converti en zone de texte pour l\'alignement.');
    } else {
        activeObject.set('textAlign', align);
        // ⭐ CORRECTION : Recalculer après changement d'alignement
        activeObject.setCoords();
        fabricCanvas.renderAll();
    }
}

/**
 * Mettre à jour la police du texte sélectionné
 * ⭐ VERSION CORRIGÉE avec setCoords()
 */
function updateSelectedTextFont() {
    const activeObject = fabricCanvas.getActiveObject();
    if (!activeObject || (activeObject.type !== 'i-text' && activeObject.type !== 'textbox')) return;
    
    const font = document.getElementById('textFont').value;
    activeObject.set('fontFamily', font);
    
    // ⭐ CORRECTION : Recalculer après changement de police
    activeObject.setCoords();
    fabricCanvas.renderAll();
}

/**
 * Mettre à jour la taille du texte sélectionné
 * ⭐ VERSION CORRIGÉE avec setCoords()
 */
function updateSelectedTextSize() {
    const activeObject = fabricCanvas.getActiveObject();
    if (!activeObject || (activeObject.type !== 'i-text' && activeObject.type !== 'textbox')) return;
    
    const size = parseInt(document.getElementById('textSize').value);
    activeObject.set('fontSize', size);
    
    // ⭐ CORRECTION : Recalculer après changement de taille (TRÈS IMPORTANT)
    activeObject.setCoords();
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
    
    // Pas besoin de setCoords() pour la couleur (ne change pas les dimensions)
    fabricCanvas.renderAll();
}

/**
 * Mettre à jour le contour du texte sélectionné
 * ⭐ VERSION CORRIGÉE avec setCoords()
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
    
    // ⭐ CORRECTION : Le stroke peut changer les dimensions visuelles
    activeObject.setCoords();
    fabricCanvas.renderAll();
}

/**
 * Activer/désactiver l'ombre du texte
 */
function updateSelectedTextShadow() {
    const activeObject = fabricCanvas.getActiveObject();
    if (!activeObject || (activeObject.type !== 'i-text' && activeObject.type !== 'textbox')) return;
    
    const shadowEnabled = document.getElementById('textShadow').checked;
    
    if (shadowEnabled) {
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

// ===== FONCTIONS DE GESTION DES FORMES =====

/**
 * Ajouter une forme au canvas
 * ⭐ VERSION CORRIGÉE avec setCoords()
 */
function addShape(type) {
    const shapeFillColor = document.getElementById('shapeFillColor');
    const shapeStrokeColor = document.getElementById('shapeStrokeColor');
    const shapeStrokeWidth = document.getElementById('shapeStrokeWidth');
    const shapeOpacity = document.getElementById('shapeOpacity');
    const shapeStrokeDash = document.getElementById('shapeStrokeDash');
    const shapeRoundedCorners = document.getElementById('shapeRoundedCorners');
    
    const fillColor = shapeFillColor.value;
    const strokeColor = shapeStrokeColor.value;
    const strokeWidth = parseInt(shapeStrokeWidth.value);
    const opacity = parseInt(shapeOpacity.value) / 100;
    
    // Définir le style de trait
    let strokeDashArray = null;
    switch(shapeStrokeDash.value) {
        case 'dashed':
            strokeDashArray = [10, 5];
            break;
        case 'dotted':
            strokeDashArray = [2, 5];
            break;
    }
    
    const roundedCorners = parseInt(shapeRoundedCorners.value);
    
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
    
    let shape = null;
    
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
        
        // ⭐ CORRECTION : Recalculer immédiatement les coordonnées
        shape.setCoords();
        
        fabricCanvas.renderAll();
        fabricCanvas.requestRenderAll();
        
        // ⭐ CORRECTION : Recalculer aussi après un court délai
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
            if (obj !== fabricCanvas.backgroundImage) {
                fabricCanvas.remove(obj);
            }
        });
        fabricCanvas.renderAll();
        alert('✅ Canvas nettoyé');
    }
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
    
    const angle = parseInt(document.getElementById('objectRotation').value);
    activeObject.set('angle', angle);
    
    // ⭐ CORRECTION : Recalculer après rotation
    activeObject.setCoords();
    fabricCanvas.renderAll();
}

/**
 * Amener l'objet sélectionné au premier plan
 */
function bringToFront() {
    const activeObject = fabricCanvas.getActiveObject();
    if (activeObject) {
        fabricCanvas.bringToFront(activeObject);
        fabricCanvas.renderAll();
    } else {
        alert('⚠️ Aucun élément sélectionné');
    }
}

/**
 * Envoyer l'objet sélectionné à l'arrière-plan
 */
function sendToBack() {
    const activeObject = fabricCanvas.getActiveObject();
    if (activeObject) {
        fabricCanvas.sendToBack(activeObject);
        fabricCanvas.renderAll();
    } else {
        alert('⚠️ Aucun élément sélectionné');
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
        
        // ⭐ CORRECTION : Recalculer après duplication
        cloned.setCoords();
        fabricCanvas.renderAll();
    });
}