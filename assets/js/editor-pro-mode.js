/**
 * ÉDITEUR D'IMAGES - MODE PRO (VERSION FINALE V4)
 * Gestion des textes, formes et annotations avec Fabric.js
 * ✅ CORRECTION: Formes manquantes (arrow, star, polygon, heart)
 * ✅ CORRECTION: Alignement avec création automatique de zone de texte
 * ✅ CORRECTION: Mise à jour dynamique des propriétés des formes (bordures, styles)
 * ✅ CORRECTION: Débordement du champ de texte
 */

/**
 * Charger l'image dans le mode Pro
 */
function loadProMode(img) {
    console.log('🔵 Chargement mode Pro...');
    
    document.getElementById('fabricCanvas').style.display = 'block';
    simpleCanvas.style.display = 'none';
    document.getElementById('cropperImage').style.display = 'none';
    
    const emptyState = document.getElementById('emptyState');
    if (emptyState) {
        emptyState.style.display = 'none';
    }
    
    if (!fabricCanvas) {
        console.log('🔵 Première initialisation de Fabric.js');
        fabricCanvas = new fabric.Canvas('fabricCanvas', {
            selection: true,
            interactive: true,
            enableRetinaScaling: false,
            preserveObjectStacking: true,
            renderOnAddRemove: true,
            skipTargetFind: false,
            perPixelTargetFind: true,
            targetFindTolerance: 5,
            selectionColor: 'rgba(102, 126, 234, 0.1)',
            selectionBorderColor: '#667eea',
            selectionLineWidth: 2
        });
        
        setupFabricEventListeners();
        
        window.addEventListener('resize', function() {
            if (fabricCanvas) {
                fabricCanvas.calcOffset();
                fabricCanvas.getObjects().forEach(obj => obj.setCoords());
                fabricCanvas.renderAll();
            }
        });
        
        console.log('✅ Fabric.js initialisé');
    } else {
        console.log('🔵 Réinitialisation de Fabric.js');
        fabricCanvas.clear();
        fabricCanvas.backgroundColor = null;
        fabricCanvas.backgroundImage = null;
    }
    
    fabric.Image.fromURL(img.src, function(fabricImg) {
        console.log('🔵 Image chargée dans Fabric.js');
        
        const maxWidth = 800;
        const maxHeight = 600;
        let scale = 1;
        
        if (fabricImg.width > maxWidth || fabricImg.height > maxHeight) {
            const scaleX = maxWidth / fabricImg.width;
            const scaleY = maxHeight / fabricImg.height;
            scale = Math.min(scaleX, scaleY);
        }
        
        fabricCanvas.setWidth(fabricImg.width * scale);
        fabricCanvas.setHeight(fabricImg.height * scale);
        
        fabricImg.set({
            scaleX: scale,
            scaleY: scale,
            selectable: false,
            evented: false,
            lockMovementX: true,
            lockMovementY: true,
            lockRotation: true,
            lockScalingX: true,
            lockScalingY: true,
            hoverCursor: 'default'
        });
        
        fabricCanvas.setBackgroundImage(fabricImg, fabricCanvas.renderAll.bind(fabricCanvas));
        fabricCanvas.renderAll();
        
        console.log('✅ Mode Pro chargé');
    }, null, { crossOrigin: 'anonymous' });
}

/**
 * Configuration des événements Fabric.js
 */
function setupFabricEventListeners() {
    fabricCanvas.on('selection:created', updateSelectionIndicator);
    fabricCanvas.on('selection:updated', updateSelectionIndicator);
    fabricCanvas.on('selection:cleared', function() {
        const indicator = document.getElementById('selectionIndicator');
        const modifyControls = document.getElementById('modifyControls');
        if (indicator) indicator.style.display = 'none';
        if (modifyControls) modifyControls.style.display = 'none';
    });
    
    fabricCanvas.on('text:editing:entered', function() {
        console.log('📝 Édition de texte commencée');
    });
    
    fabricCanvas.on('text:editing:exited', function() {
        console.log('📝 Édition de texte terminée');
        fabricCanvas.renderAll();
    });
}

/**
 * Mettre à jour l'indicateur de sélection
 */
function updateSelectionIndicator() {
    const obj = fabricCanvas.getActiveObject();
    const indicator = document.getElementById('selectionIndicator');
    const modifyControls = document.getElementById('modifyControls');
    
    if (indicator) indicator.style.display = 'block';
    if (modifyControls && obj) {
        modifyControls.style.display = 'block';
        
        const objectOpacity = document.getElementById('objectOpacity');
        const objectRotation = document.getElementById('objectRotation');
        
        if (objectOpacity) objectOpacity.value = (obj.opacity || 1) * 100;
        if (objectRotation) objectRotation.value = obj.angle || 0;
        
        // ✅ Si c'est un texte, charger ses propriétés
        if (obj.type === 'i-text' || obj.type === 'textbox') {
            const textFont = document.getElementById('textFont');
            const textSize = document.getElementById('textSize');
            const textColor = document.getElementById('textColor');
            const textStrokeColor = document.getElementById('textStrokeColor');
            const textStrokeWidth = document.getElementById('textStrokeWidth');
            
            if (textFont) textFont.value = obj.fontFamily || 'Arial';
            if (textSize) textSize.value = obj.fontSize || 40;
            if (textColor) textColor.value = obj.fill || '#ffffff';
            if (textStrokeColor) textStrokeColor.value = obj.stroke || '#000000';
            if (textStrokeWidth) textStrokeWidth.value = obj.strokeWidth || 0;
        }
        
        // ✅ Si c'est une forme, charger ses propriétés
        if (obj.type !== 'i-text' && obj.type !== 'textbox') {
            const shapeFillColor = document.getElementById('shapeFillColor');
            const shapeStrokeColor = document.getElementById('shapeStrokeColor');
            const shapeStrokeWidth = document.getElementById('shapeStrokeWidth');
            const shapeOpacity = document.getElementById('shapeOpacity');
            const shapeStrokeDash = document.getElementById('shapeStrokeDash');
            
            if (shapeFillColor && obj.fill) shapeFillColor.value = obj.fill;
            if (shapeStrokeColor && obj.stroke) shapeStrokeColor.value = obj.stroke;
            if (shapeStrokeWidth) shapeStrokeWidth.value = obj.strokeWidth || 3;
            if (shapeOpacity) shapeOpacity.value = (obj.opacity || 1) * 100;
            
            // ✅ Charger le style de bordure actuel
            if (shapeStrokeDash) {
                if (obj.strokeDashArray && obj.strokeDashArray.length > 0) {
                    // Déterminer le type de bordure
                    if (obj.strokeDashArray[0] > 5) {
                        shapeStrokeDash.value = 'dashed';
                    } else {
                        shapeStrokeDash.value = 'dotted';
                    }
                } else {
                    shapeStrokeDash.value = 'solid';
                }
            }
        }
    }
}

// ===== FONCTIONS DE GESTION DU TEXTE =====

/**
 * Ajouter du texte au canvas
 */
function addText() {
    const textInput = document.getElementById('textInput');
    const textFont = document.getElementById('textFont');
    const textSize = document.getElementById('textSize');
    const textColor = document.getElementById('textColor');
    const textStrokeColor = document.getElementById('textStrokeColor');
    const textStrokeWidth = document.getElementById('textStrokeWidth');
    const textShadow = document.getElementById('textShadow');
    
    if (!textInput) {
        console.error('❌ Element textInput non trouvé');
        alert('⚠️ Erreur: champ de texte non trouvé');
        return;
    }
    
    if (!textInput.value.trim()) {
        alert('⚠️ Veuillez entrer du texte');
        return;
    }
    
    const textOptions = {
        left: 100,
        top: 100,
        fontSize: textSize ? parseInt(textSize.value) : 40,
        fill: textColor ? textColor.value : '#ffffff',
        stroke: textStrokeColor ? textStrokeColor.value : '#000000',
        strokeWidth: textStrokeWidth ? parseInt(textStrokeWidth.value) : 0,
        fontFamily: textFont ? textFont.value : 'Arial',
        selectable: true,
        editable: true,
        hasControls: true,
        hasBorders: true,
        borderColor: '#667eea',
        cornerColor: '#667eea',
        cornerSize: 12,
        cornerStyle: 'circle',
        transparentCorners: false
    };
    
    if (textShadow && textShadow.checked) {
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
    text.setCoords();
    fabricCanvas.renderAll();
    
    textInput.value = '';
    
    setTimeout(() => {
        fabricCanvas.calcOffset();
        text.setCoords();
        fabricCanvas.requestRenderAll();
    }, 50);
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
    
    activeObject.setCoords();
    fabricCanvas.renderAll();
}

/**
 * ✅ CORRIGÉ: Définir l'alignement du texte (avec création automatique de zone de texte)
 */
function setTextAlign(align) {
    const activeObject = fabricCanvas.getActiveObject();
    
    // Si un texte est déjà sélectionné, changer son alignement
    if (activeObject && (activeObject.type === 'i-text' || activeObject.type === 'textbox')) {
        activeObject.set('textAlign', align);
        activeObject.setCoords();
        fabricCanvas.renderAll();
        return;
    }
    
    // ✅ Sinon, créer une zone de texte multiligne avec l'alignement choisi
    const textInput = document.getElementById('textInput');
    const textFont = document.getElementById('textFont');
    const textSize = document.getElementById('textSize');
    const textColor = document.getElementById('textColor');
    const textStrokeColor = document.getElementById('textStrokeColor');
    const textStrokeWidth = document.getElementById('textStrokeWidth');
    
    // Utiliser le texte du champ s'il y en a un, sinon texte par défaut
    const defaultText = textInput && textInput.value.trim() ? textInput.value : 'Cliquez ici pour modifier le texte';
    
    const textboxOptions = {
        left: 150,
        top: 150,
        width: 300,
        fontSize: textSize ? parseInt(textSize.value) : 40,
        fill: textColor ? textColor.value : '#ffffff',
        stroke: textStrokeColor ? textStrokeColor.value : '#000000',
        strokeWidth: textStrokeWidth ? parseInt(textStrokeWidth.value) : 0,
        fontFamily: textFont ? textFont.value : 'Arial',
        textAlign: align,
        selectable: true,
        editable: true,
        hasControls: true,
        hasBorders: true,
        borderColor: '#667eea',
        cornerColor: '#667eea',
        cornerSize: 12,
        cornerStyle: 'circle',
        transparentCorners: false
    };
    
    const textbox = new fabric.Textbox(defaultText, textboxOptions);
    fabricCanvas.add(textbox);
    fabricCanvas.setActiveObject(textbox);
    fabricCanvas.centerObject(textbox);
    textbox.setCoords();
    fabricCanvas.renderAll();
    
    // Vider le champ si on a utilisé son contenu
    if (textInput && textInput.value.trim()) {
        textInput.value = '';
    }
    
    setTimeout(() => {
        fabricCanvas.calcOffset();
        textbox.setCoords();
        fabricCanvas.requestRenderAll();
    }, 50);
}

/**
 * Mettre à jour la police du texte sélectionné
 */
function updateSelectedTextFont() {
    const activeObject = fabricCanvas.getActiveObject();
    if (!activeObject || (activeObject.type !== 'i-text' && activeObject.type !== 'textbox')) return;
    
    const font = document.getElementById('textFont');
    if (font) {
        activeObject.set('fontFamily', font.value);
        activeObject.setCoords();
        fabricCanvas.renderAll();
    }
}

/**
 * Mettre à jour la taille du texte sélectionné
 */
function updateSelectedTextSize() {
    const activeObject = fabricCanvas.getActiveObject();
    if (!activeObject || (activeObject.type !== 'i-text' && activeObject.type !== 'textbox')) return;
    
    const size = document.getElementById('textSize');
    if (size) {
        activeObject.set('fontSize', parseInt(size.value));
        activeObject.setCoords();
        fabricCanvas.renderAll();
    }
}

/**
 * Mettre à jour la couleur du texte sélectionné
 */
function updateSelectedTextColor() {
    const activeObject = fabricCanvas.getActiveObject();
    if (!activeObject || (activeObject.type !== 'i-text' && activeObject.type !== 'textbox')) return;
    
    const color = document.getElementById('textColor');
    if (color) {
        activeObject.set('fill', color.value);
        fabricCanvas.renderAll();
    }
}

/**
 * Mettre à jour le contour du texte sélectionné
 */
function updateSelectedTextStroke() {
    const activeObject = fabricCanvas.getActiveObject();
    if (!activeObject || (activeObject.type !== 'i-text' && activeObject.type !== 'textbox')) return;
    
    const strokeColor = document.getElementById('textStrokeColor');
    const strokeWidth = document.getElementById('textStrokeWidth');
    
    if (strokeColor && strokeWidth) {
        activeObject.set({
            stroke: strokeColor.value,
            strokeWidth: parseInt(strokeWidth.value)
        });
        activeObject.setCoords();
        fabricCanvas.renderAll();
    }
}

/**
 * Activer/désactiver l'ombre du texte
 */
function updateSelectedTextShadow() {
    const activeObject = fabricCanvas.getActiveObject();
    if (!activeObject || (activeObject.type !== 'i-text' && activeObject.type !== 'textbox')) return;
    
    const shadowEnabled = document.getElementById('textShadow');
    if (shadowEnabled) {
        if (shadowEnabled.checked) {
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
}

// ===== FONCTIONS DE GESTION DES FORMES =====

/**
 * Créer une forme étoile
 */
function createStar(options) {
    const points = 5;
    const innerRadius = 30;
    const outerRadius = 60;
    const step = Math.PI / points;
    let path = '';
    
    for (let i = 0; i < points * 2; i++) {
        const radius = i % 2 === 0 ? outerRadius : innerRadius;
        const angle = i * step - Math.PI / 2;
        const x = radius * Math.cos(angle);
        const y = radius * Math.sin(angle);
        path += (i === 0 ? 'M' : 'L') + x + ',' + y;
    }
    path += 'Z';
    
    return new fabric.Path(path, options);
}

/**
 * Créer une flèche
 */
function createArrow(options) {
    const arrowPath = 'M 0,0 L 100,0 L 100,-15 L 130,10 L 100,35 L 100,20 L 0,20 Z';
    return new fabric.Path(arrowPath, options);
}

/**
 * Créer un hexagone (polygone)
 */
function createHexagon(options) {
    const sides = 6;
    const radius = 50;
    const angleStep = (Math.PI * 2) / sides;
    let path = '';
    
    for (let i = 0; i < sides; i++) {
        const angle = i * angleStep - Math.PI / 2;
        const x = radius * Math.cos(angle);
        const y = radius * Math.sin(angle);
        path += (i === 0 ? 'M' : 'L') + x + ',' + y;
    }
    path += 'Z';
    
    return new fabric.Path(path, options);
}

/**
 * Créer un cœur
 */
function createHeart(options) {
    const heartPath = 'M 50,90 C 20,60 0,40 0,25 C 0,10 10,0 25,0 C 35,0 45,5 50,15 C 55,5 65,0 75,0 C 90,0 100,10 100,25 C 100,40 80,60 50,90 Z';
    return new fabric.Path(heartPath, options);
}

/**
 * Ajouter une forme au canvas
 * ✅ CORRIGÉ: Toutes les formes disponibles
 */
function addShape(type) {
    const shapeFillColor = document.getElementById('shapeFillColor');
    const shapeStrokeColor = document.getElementById('shapeStrokeColor');
    const shapeStrokeWidth = document.getElementById('shapeStrokeWidth');
    const shapeOpacity = document.getElementById('shapeOpacity');
    const shapeStrokeDash = document.getElementById('shapeStrokeDash');
    const shapeRoundedCorners = document.getElementById('shapeRoundedCorners');
    
    const fillColor = shapeFillColor ? shapeFillColor.value : '#ff0000';
    const strokeColor = shapeStrokeColor ? shapeStrokeColor.value : '#000000';
    const strokeWidth = shapeStrokeWidth ? parseInt(shapeStrokeWidth.value) : 3;
    const opacity = shapeOpacity ? parseInt(shapeOpacity.value) / 100 : 0.8;
    
    let strokeDashArray = null;
    if (shapeStrokeDash) {
        switch(shapeStrokeDash.value) {
            case 'dashed':
                strokeDashArray = [10, 5];
                break;
            case 'dotted':
                strokeDashArray = [2, 5];
                break;
        }
    }
    
    const roundedCorners = shapeRoundedCorners ? parseInt(shapeRoundedCorners.value) : 0;
    
    const shapeOptions = {
        left: 200,
        top: 200,
        fill: fillColor,
        stroke: strokeColor,
        strokeWidth: strokeWidth,
        opacity: opacity,
        strokeDashArray: strokeDashArray,
        selectable: true,
        evented: true,
        hasControls: true,
        hasBorders: true,
        borderColor: '#667eea',
        cornerColor: '#667eea',
        cornerSize: 12,
        cornerStyle: 'circle',
        transparentCorners: false
    };
    
    let shape;
    
    switch(type) {
        case 'rect':
            shape = new fabric.Rect({
                ...shapeOptions,
                width: 150,
                height: 100,
                rx: roundedCorners,
                ry: roundedCorners
            });
            break;
            
        case 'circle':
            shape = new fabric.Circle({
                ...shapeOptions,
                radius: 60
            });
            break;
            
        case 'triangle':
            shape = new fabric.Triangle({
                ...shapeOptions,
                width: 120,
                height: 120
            });
            break;
            
        case 'line':
            shape = new fabric.Line([50, 100, 200, 100], {
                ...shapeOptions,
                fill: null,
                stroke: strokeColor,
                strokeWidth: strokeWidth
            });
            break;
        
        case 'arrow':
            shape = createArrow(shapeOptions);
            break;
            
        case 'star':
            shape = createStar(shapeOptions);
            break;
            
        case 'polygon':
            shape = createHexagon(shapeOptions);
            break;
            
        case 'heart':
            shape = createHeart(shapeOptions);
            break;
            
        default:
            console.warn('⚠️ Type de forme non supporté:', type);
            alert('⚠️ Type de forme non supporté: ' + type);
            return;
    }
    
    if (shape) {
        fabricCanvas.add(shape);
        fabricCanvas.setActiveObject(shape);
        fabricCanvas.centerObject(shape);
        shape.setCoords();
        fabricCanvas.renderAll();
        
        setTimeout(() => {
            fabricCanvas.calcOffset();
            shape.setCoords();
            fabricCanvas.requestRenderAll();
        }, 50);
    }
}

// ===== FONCTIONS DE MODIFICATION D'OBJETS =====

/**
 * Mettre au premier plan
 */
function bringToFront() {
    const obj = fabricCanvas.getActiveObject();
    if (obj) {
        fabricCanvas.bringToFront(obj);
        fabricCanvas.renderAll();
    }
}

/**
 * Mettre à l'arrière-plan
 */
function sendToBack() {
    const obj = fabricCanvas.getActiveObject();
    if (obj) {
        fabricCanvas.sendToBack(obj);
        if (fabricCanvas.backgroundImage) {
            fabricCanvas.sendToBack(fabricCanvas.backgroundImage);
        }
        fabricCanvas.renderAll();
    }
}

/**
 * Supprimer l'objet sélectionné
 */
function deleteSelected() {
    const obj = fabricCanvas.getActiveObject();
    if (obj && obj !== fabricCanvas.backgroundImage) {
        if (confirm('Supprimer cet élément ?')) {
            fabricCanvas.remove(obj);
            fabricCanvas.renderAll();
        }
    }
}

/**
 * Dupliquer l'objet sélectionné
 */
function duplicateSelected() {
    const obj = fabricCanvas.getActiveObject();
    if (obj && obj !== fabricCanvas.backgroundImage) {
        obj.clone(function(cloned) {
            cloned.set({
                left: cloned.left + 20,
                top: cloned.top + 20
            });
            fabricCanvas.add(cloned);
            fabricCanvas.setActiveObject(cloned);
            cloned.setCoords();
            fabricCanvas.renderAll();
        });
    }
}

/**
 * Tout effacer (sauf l'image de fond)
 */
function clearAllObjects() {
    if (confirm('Supprimer tous les éléments (textes et formes) ?')) {
        const objects = fabricCanvas.getObjects().filter(obj => obj !== fabricCanvas.backgroundImage);
        objects.forEach(obj => fabricCanvas.remove(obj));
        fabricCanvas.renderAll();
    }
}

/**
 * Mettre à jour l'opacité de l'objet sélectionné
 */
function updateSelectedObjectOpacity() {
    const obj = fabricCanvas.getActiveObject();
    const opacityInput = document.getElementById('objectOpacity');
    if (obj && opacityInput) {
        obj.set('opacity', parseInt(opacityInput.value) / 100);
        fabricCanvas.renderAll();
    }
}

/**
 * Mettre à jour la rotation de l'objet sélectionné
 */
function updateSelectedObjectRotation() {
    const obj = fabricCanvas.getActiveObject();
    const rotationInput = document.getElementById('objectRotation');
    if (obj && rotationInput) {
        obj.set('angle', parseInt(rotationInput.value));
        obj.setCoords();
        fabricCanvas.renderAll();
    }
}

/**
 * ✅ CORRIGÉ: Mettre à jour la couleur de remplissage d'une forme sélectionnée
 */
function updateSelectedShapeFill() {
    const obj = fabricCanvas.getActiveObject();
    const fillColor = document.getElementById('shapeFillColor');
    
    if (obj && fillColor && obj.type !== 'i-text' && obj.type !== 'textbox') {
        obj.set('fill', fillColor.value);
        fabricCanvas.renderAll();
        console.log('✅ Couleur de remplissage mise à jour:', fillColor.value);
    }
}

/**
 * ✅ CORRIGÉ: Mettre à jour le contour d'une forme sélectionnée
 */
function updateSelectedShapeStroke() {
    const obj = fabricCanvas.getActiveObject();
    const strokeColor = document.getElementById('shapeStrokeColor');
    const strokeWidth = document.getElementById('shapeStrokeWidth');
    
    if (obj && strokeColor && strokeWidth && obj.type !== 'i-text' && obj.type !== 'textbox') {
        obj.set({
            stroke: strokeColor.value,
            strokeWidth: parseInt(strokeWidth.value)
        });
        obj.setCoords();
        fabricCanvas.renderAll();
        console.log('✅ Contour mis à jour:', strokeColor.value, strokeWidth.value);
    }
}

/**
 * ✅ CORRIGÉ: Mettre à jour l'opacité d'une forme sélectionnée
 */
function updateSelectedShapeOpacity() {
    const obj = fabricCanvas.getActiveObject();
    const opacity = document.getElementById('shapeOpacity');
    
    if (obj && opacity && obj.type !== 'i-text' && obj.type !== 'textbox') {
        const opacityValue = parseInt(opacity.value) / 100;
        obj.set('opacity', opacityValue);
        fabricCanvas.renderAll();
        console.log('✅ Opacité de la forme mise à jour:', opacityValue);
    }
}

/**
 * ✅ NOUVEAU: Mettre à jour le style de bordure (solid/dashed/dotted)
 */
function updateSelectedShapeStrokeDash() {
    const obj = fabricCanvas.getActiveObject();
    const strokeDash = document.getElementById('shapeStrokeDash');
    
    if (obj && strokeDash && obj.type !== 'i-text' && obj.type !== 'textbox') {
        let strokeDashArray = null;
        
        switch(strokeDash.value) {
            case 'dashed':
                strokeDashArray = [10, 5];
                break;
            case 'dotted':
                strokeDashArray = [2, 5];
                break;
            case 'solid':
            default:
                strokeDashArray = null;
                break;
        }
        
        obj.set('strokeDashArray', strokeDashArray);
        obj.setCoords();
        fabricCanvas.renderAll();
        console.log('✅ Style de bordure mis à jour:', strokeDash.value);
    }
}

/**
 * Retourner horizontalement
 */
function flipObjectH() {
    const obj = fabricCanvas.getActiveObject();
    if (obj) {
        obj.set('flipX', !obj.flipX);
        obj.setCoords();
        fabricCanvas.renderAll();
    }
}

/**
 * Retourner verticalement
 */
function flipObjectV() {
    const obj = fabricCanvas.getActiveObject();
    if (obj) {
        obj.set('flipY', !obj.flipY);
        obj.setCoords();
        fabricCanvas.renderAll();
    }
}

console.log('✅ editor-pro-mode.js (VERSION FINALE V4) chargé - Toutes les corrections appliquées');