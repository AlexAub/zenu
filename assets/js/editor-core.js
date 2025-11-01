/**
 * ÉDITEUR D'IMAGES - CORE
 * Gestion de l'initialisation, du chargement de fichiers et du changement de modes
 */

// Variables globales
let originalImage = null;
let currentMode = 'simple';
let simpleCanvas, simpleCtx;
let cropper = null;
let fabricCanvas = null;
let currentFileName = 'edited-image.jpg'; // Nom du fichier actuel

// Initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    initializeEditor();
});

/**
 * Initialisation de l'éditeur
 */
function initializeEditor() {
    // Créer le canvas pour le mode simple
    simpleCanvas = document.getElementById('editorCanvas');
    simpleCtx = simpleCanvas.getContext('2d', { willReadFrequently: true });
    
    // Initialiser Fabric.js pour le mode Pro
    fabricCanvas = new fabric.Canvas('fabricCanvas', {
        width: 800,
        height: 600,
        backgroundColor: '#ffffff'
    });
    
    // Événements de l'upload
    const uploadZone = document.getElementById('uploadZone');
    const fileInput = document.getElementById('fileInput');
    
    uploadZone.addEventListener('click', () => fileInput.click());
    fileInput.addEventListener('change', handleFileSelect);
    
    // Drag & Drop
    uploadZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadZone.classList.add('drag-over');
    });
    
    uploadZone.addEventListener('dragleave', () => {
        uploadZone.classList.remove('drag-over');
    });
    
    uploadZone.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadZone.classList.remove('drag-over');
        
        if (e.dataTransfer.files.length > 0) {
            handleFile(e.dataTransfer.files[0]);
        }
    });
    
    // Événements des tabs de mode
    document.querySelectorAll('.mode-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            switchMode(tab.dataset.mode);
        });
    });
    
    console.log('✅ Éditeur initialisé');
}

/**
 * Gestion de la sélection de fichier
 */
function handleFileSelect(e) {
    const file = e.target.files[0];
    if (file) {
        handleFile(file);
    }
}

/**
 * Traitement du fichier uploadé
 */
function handleFile(file) {
    if (!file.type.startsWith('image/')) {
        alert('⚠️ Veuillez sélectionner une image valide');
        return;
    }
    
    // Sauvegarder le nom du fichier
    currentFileName = file.name;
    
    const reader = new FileReader();
    reader.onload = function(e) {
        const img = new Image();
        img.onload = function() {
            originalImage = img;
            
            // IMPORTANT : Cacher SEULEMENT le message "empty state" au centre
            // NE PAS cacher la zone d'upload à gauche (uploadZone)
            const emptyState = document.getElementById('emptyState');
            if (emptyState) {
                emptyState.style.display = 'none';
            }
            
            // Charger l'image dans le mode actuel
            switch(currentMode) {
                case 'simple':
                    loadSimpleMode(img);
                    break;
                case 'advanced':
                    loadAdvancedMode(img);
                    break;
                case 'pro':
                    loadProMode(img);
                    break;
            }
        };
        img.src = e.target.result;
    };
    reader.readAsDataURL(file);
}

/**
 * Chargement d'une nouvelle image
 */
function loadNewImage() {
    document.getElementById('fileInput').click();
}

/**
 * Changement de mode d'édition
 */
function switchMode(mode) {
    if (mode === currentMode) return;
    
    currentMode = mode;
    
    // Mettre à jour les tabs
    document.querySelectorAll('.mode-tab').forEach(tab => {
        tab.classList.toggle('active', tab.dataset.mode === mode);
    });
    
    // Mettre à jour les contrôles
    document.querySelectorAll('.mode-content').forEach(content => {
        content.classList.remove('active');
    });
    document.getElementById(mode + 'Controls').classList.add('active');
    
    // Mettre à jour la description
    const descriptions = {
        simple: 'Mode Simple : Ajustez luminosité, contraste, saturation et effectuez des rotations simples.',
        advanced: 'Mode Avancé : Recadrez votre image avec précision et choisissez un ratio d\'aspect.',
        pro: 'Mode Pro : Ajoutez du texte, des formes et des annotations à votre image.'
    };
    document.getElementById('modeDescription').textContent = descriptions[mode];
    
    // Mettre à jour les instructions
    document.getElementById('instructionsSimple').style.display = mode === 'simple' ? 'block' : 'none';
    document.getElementById('instructionsAdvanced').style.display = mode === 'advanced' ? 'block' : 'none';
    document.getElementById('instructionsPro').style.display = mode === 'pro' ? 'block' : 'none';
    
    // Nettoyer avant de recharger
    if (currentMode !== 'simple') {
        // Réinitialiser les filtres du mode simple
        if (typeof resetSimpleMode === 'function') {
            resetSimpleMode();
        }
    }
    
    // Détruire cropper si on quitte le mode avancé
    if (cropper && mode !== 'advanced') {
        cropper.destroy();
        cropper = null;
    }
    
    // Nettoyer le canvas Fabric si on quitte le mode pro
    if (mode !== 'pro' && fabricCanvas) {
        fabricCanvas.clear();
    }
    
    // Cacher tous les canvas
    simpleCanvas.style.display = 'none';
    document.getElementById('cropperImage').style.display = 'none';
    document.getElementById('fabricCanvas').style.display = 'none';
    
    // Recharger l'image dans le nouveau mode
    if (originalImage) {
        switch(mode) {
            case 'simple':
                loadSimpleMode(originalImage);
                break;
            case 'advanced':
                loadAdvancedMode(originalImage);
                break;
            case 'pro':
                loadProMode(originalImage);
                break;
        }
    }
}

/**
 * Sauvegarde de l'image éditée
 */
function saveImage(mode) {
    if (!originalImage) {
        alert('Aucune image chargée');
        return;
    }
    
    let canvas;
    
    switch(mode) {
        case 'simple':
            canvas = simpleCanvas;
            break;
        case 'advanced':
            if (!cropper) return;
            canvas = cropper.getCroppedCanvas();
            break;
        case 'pro':
            canvas = fabricCanvas;
            break;
    }
    
    canvas.toBlob(function(blob) {
        const formData = new FormData();
        
        // Utiliser le nom du fichier original avec un préfixe
        const filename = 'edited_' + currentFileName;
        formData.append('image', blob, filename);
        formData.append('mode', mode);
        
        // Ajouter l'ID de l'image originale si on édite une image existante
        const originalImageId = document.getElementById('originalImageId');
        if (originalImageId && originalImageId.value) {
            formData.append('original_image_id', originalImageId.value);
        }
        
        // Envoyer au serveur
        fetch('api/save-edited-image.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ Image sauvegardée avec succès !');
                window.location.href = 'dashboard.php';
            } else {
                alert('❌ Erreur : ' + data.error);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('❌ Erreur lors de la sauvegarde');
        });
    }, 'image/jpeg', 0.9);
}
