/**
 * Gestionnaire du modal de sélection d'images - VERSION ULTRA-ROBUSTE
 * Résout définitivement le problème de sélection
 */

// Variable globale pour la sélection
let selectedImageFromLibrary = null;

/**
 * Ouvrir le modal de sélection d'images
 */
function openImageSelector() {
    console.log('🟢 [OPEN] Ouverture du modal');
    
    const modal = document.getElementById('imageSelectorModal');
    if (!modal) {
        console.error('🔴 [OPEN] Modal introuvable');
        alert('Erreur: Le modal n\'est pas présent dans la page. Vérifiez editeur.php');
        return;
    }
    
    // IMPORTANT: Réinitialiser la sélection
    selectedImageFromLibrary = null;
    console.log('🟢 [OPEN] Sélection réinitialisée à null');
    
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
    
    // Charger les images
    loadUserImages();
}

/**
 * Fermer le modal de sélection d'images
 */
function closeImageSelector(keepSelection = false) {
    console.log('🟢 [CLOSE] Fermeture du modal, keepSelection:', keepSelection);
    
    const modal = document.getElementById('imageSelectorModal');
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
    
    // NE PAS réinitialiser selectedImageFromLibrary si on veut garder la sélection
    if (!keepSelection) {
        selectedImageFromLibrary = null;
        console.log('🟢 [CLOSE] Sélection réinitialisée');
    } else {
        console.log('🟢 [CLOSE] Sélection CONSERVÉE');
    }
}

/**
 * Charger les images de l'utilisateur
 */
function loadUserImages(searchTerm = '') {
    console.log('🟢 [LOAD] Chargement des images, recherche:', searchTerm || '(aucune)');
    
    const container = document.getElementById('imageSelectorGrid');
    if (!container) {
        console.error('🔴 [LOAD] Container introuvable');
        return;
    }
    
    // Afficher le loading
    container.innerHTML = `
        <div class="image-selector-loading">
            <div class="image-selector-loading-spinner"></div>
            <div>Chargement de vos images...</div>
        </div>
    `;
    
    // Construire l'URL
    let url = 'get-user-images.php';
    if (searchTerm) {
        url += '?search=' + encodeURIComponent(searchTerm);
    }
    
    console.log('🟢 [LOAD] Fetch:', url);
    
    // Récupérer les images
    fetch(url)
        .then(response => {
            console.log('🟢 [LOAD] Response status:', response.status);
            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            console.log('🟢 [LOAD] Données reçues:', data);
            
            if (data.success && data.images && data.images.length > 0) {
                console.log('🟢 [LOAD] Affichage de', data.images.length, 'images');
                displayUserImages(data.images);
            } else {
                console.log('🟡 [LOAD] Aucune image trouvée');
                container.innerHTML = `
                    <div class="image-selector-empty">
                        <div class="image-selector-empty-icon">🖼️</div>
                        <div style="font-size: 18px; font-weight: 600; margin-bottom: 10px;">
                            Aucune image trouvée
                        </div>
                        <div style="font-size: 14px;">
                            ${searchTerm ? 'Essayez avec d\'autres mots-clés' : 'Uploadez d\'abord des images sur votre compte'}
                        </div>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('🔴 [LOAD] Erreur:', error);
            container.innerHTML = `
                <div class="image-selector-empty">
                    <div class="image-selector-empty-icon">⚠️</div>
                    <div style="font-size: 18px; font-weight: 600; margin-bottom: 10px; color: #e74c3c;">
                        Erreur de chargement
                    </div>
                    <div style="font-size: 14px;">
                        ${error.message}
                    </div>
                </div>
            `;
        });
}

/**
 * Afficher les images dans la grille
 */
function displayUserImages(images) {
    console.log('🟢 [DISPLAY] Affichage de', images.length, 'images');
    
    const container = document.getElementById('imageSelectorGrid');
    if (!container) {
        console.error('🔴 [DISPLAY] Container introuvable');
        return;
    }
    
    if (images.length === 0) {
        container.innerHTML = `
            <div class="image-selector-empty">
                <div class="image-selector-empty-icon">🖼️</div>
                <div style="font-size: 18px; font-weight: 600; margin-bottom: 10px;">
                    Aucune image disponible
                </div>
            </div>
        `;
        return;
    }
    
    // Créer la grille
    container.innerHTML = '<div class="images-selector-grid" id="imagesGrid"></div>';
    const grid = document.getElementById('imagesGrid');
    
    if (!grid) {
        console.error('🔴 [DISPLAY] Grid introuvable après création');
        return;
    }
    
    console.log('🟢 [DISPLAY] Grid créé, ajout des images...');
    
    images.forEach((image, index) => {
        console.log(`🟢 [DISPLAY] Image ${index + 1}:`, image.display_name, '| Path:', image.file_path);
        
        // Créer l'élément
        const item = document.createElement('div');
        item.className = 'image-selector-item';
        
        // CRITIQUE: Créer un ID unique pour chaque élément
        const uniqueId = 'img-item-' + image.id;
        item.id = uniqueId;
        
        item.innerHTML = `
            <div class="image-selector-preview">
                <img src="${image.preview_url}" 
                     alt="${image.display_name}"
                     loading="lazy">
            </div>
            <div class="image-selector-info">
                <div class="image-selector-name" title="${image.display_name}">
                    ${image.display_name}
                </div>
                <div class="image-selector-meta">
                    <span>${image.dimensions}</span>
                    <span>${image.size_formatted}</span>
                </div>
            </div>
        `;
        
        // NOUVELLE MÉTHODE: Attacher les données ET l'événement
        // Méthode 1: Stocker dans l'élément lui-même
        item._imageData = image;
        
        // Méthode 2: Event listener avec closure
        item.addEventListener('click', (function(imageData) {
            return function() {
                console.log('🟢 [CLICK] Clic détecté sur:', imageData.display_name);
                handleImageClick(this, imageData);
            };
        })(image));
        
        // Ajouter à la grille
        grid.appendChild(item);
    });
    
    console.log('🟢 [DISPLAY] Toutes les images ajoutées. Total:', images.length);
    console.log('🟢 [DISPLAY] Éléments dans le DOM:', document.querySelectorAll('.image-selector-item').length);
}

/**
 * Gérer le clic sur une image
 */
function handleImageClick(element, imageData) {
    console.log('🟢 [SELECT] Début de la sélection');
    console.log('🟢 [SELECT] Element:', element);
    console.log('🟢 [SELECT] ImageData:', imageData);
    
    // Validation
    if (!imageData) {
        console.error('🔴 [SELECT] imageData est null ou undefined');
        alert('Erreur: Les données de l\'image sont manquantes');
        return;
    }
    
    if (!imageData.file_path) {
        console.error('🔴 [SELECT] file_path manquant dans:', imageData);
        alert('Erreur: Le chemin du fichier est manquant');
        return;
    }
    
    console.log('🟢 [SELECT] Données valides');
    console.log('🟢 [SELECT] file_path:', imageData.file_path);
    
    // Désélectionner toutes les images
    const allItems = document.querySelectorAll('.image-selector-item');
    console.log('🟢 [SELECT] Désélection de', allItems.length, 'éléments');
    allItems.forEach(item => {
        item.classList.remove('selected');
    });
    
    // Sélectionner celle-ci
    element.classList.add('selected');
    console.log('🟢 [SELECT] Classe "selected" ajoutée');
    
    // CRITIQUE: Sauvegarder dans la variable globale
    selectedImageFromLibrary = imageData;
    
    console.log('🟢 [SELECT] selectedImageFromLibrary mise à jour');
    console.log('🟢 [SELECT] Contenu de selectedImageFromLibrary:', selectedImageFromLibrary);
    console.log('🟢 [SELECT] Sélection terminée avec succès ✓');
}

/**
 * Confirmer la sélection et charger l'image dans l'éditeur
 */
function confirmImageSelection() {
    console.log('═══════════════════════════════════════════');
    console.log('🟢 [CONFIRM] Début de confirmImageSelection');
    console.log('🟢 [CONFIRM] selectedImageFromLibrary:', selectedImageFromLibrary);
    
    // TEST 1: Vérifier que selectedImageFromLibrary existe
    if (selectedImageFromLibrary === null) {
        console.error('🔴 [CONFIRM] selectedImageFromLibrary est NULL');
        console.error('🔴 [CONFIRM] Type:', typeof selectedImageFromLibrary);
        alert('❌ Veuillez d\'abord sélectionner une image en cliquant dessus.\n\nL\'image doit avoir une bordure bleue pour être sélectionnée.');
        return;
    }
    
    if (selectedImageFromLibrary === undefined) {
        console.error('🔴 [CONFIRM] selectedImageFromLibrary est UNDEFINED');
        alert('❌ Erreur: La sélection n\'a pas fonctionné.\n\nRechargez la page et réessayez.');
        return;
    }
    
    console.log('🟢 [CONFIRM] selectedImageFromLibrary existe');
    
    // TEST 2: Vérifier que file_path existe
    if (!selectedImageFromLibrary.file_path) {
        console.error('🔴 [CONFIRM] file_path manquant');
        console.error('🔴 [CONFIRM] Contenu de selectedImageFromLibrary:', JSON.stringify(selectedImageFromLibrary, null, 2));
        alert('❌ Erreur: Le chemin du fichier est manquant dans les données de l\'image.');
        return;
    }
    
    console.log('🟢 [CONFIRM] file_path existe:', selectedImageFromLibrary.file_path);
    console.log('🟢 [CONFIRM] Toutes les validations passées ✓');
    
    // IMPORTANT: Sauvegarder la sélection avant de fermer le modal
    const imageToLoad = selectedImageFromLibrary;
    console.log('🟢 [CONFIRM] Image sauvegardée localement');
    
    // Fermer le modal SANS réinitialiser la sélection
    closeImageSelector(true);
    
    // Réinitialiser maintenant (après la fermeture)
    selectedImageFromLibrary = null;
    
    // Créer l'objet Image
    console.log('🟢 [CONFIRM] Création de l\'objet Image...');
    const img = new Image();
    img.crossOrigin = 'anonymous';
    
    img.onload = function() {
        console.log('🟢 [CONFIRM] Image chargée avec succès');
        console.log('🟢 [CONFIRM] Dimensions:', img.width, 'x', img.height);
        
        // Mettre à jour originalImage
        try {
            if (typeof originalImage !== 'undefined') {
                originalImage = img;
                console.log('🟢 [CONFIRM] originalImage mise à jour (variable existe)');
            } else {
                window.originalImage = img;
                console.log('🟢 [CONFIRM] originalImage créée sur window');
            }
        } catch (e) {
            console.error('🔴 [CONFIRM] Erreur mise à jour originalImage:', e);
        }
        
        // Mettre à jour currentFileName
        try {
            const filename = imageToLoad.display_name || imageToLoad.filename;
            if (typeof currentFileName !== 'undefined') {
                currentFileName = filename;
            } else {
                window.currentFileName = filename;
            }
            console.log('🟢 [CONFIRM] currentFileName:', filename);
        } catch (e) {
            console.error('🔴 [CONFIRM] Erreur mise à jour currentFileName:', e);
        }
        
        // Cacher l'état vide
        const emptyState = document.getElementById('emptyState');
        if (emptyState) {
            emptyState.style.display = 'none';
            console.log('🟢 [CONFIRM] emptyState caché');
        }
        
        // Déterminer le mode
        const mode = (typeof currentMode !== 'undefined') ? currentMode : (window.currentMode || 'simple');
        console.log('🟢 [CONFIRM] Mode actuel:', mode);
        
        // Charger dans le mode approprié
        try {
            switch(mode) {
                case 'simple':
                    console.log('🟢 [CONFIRM] Appel loadSimpleMode...');
                    if (typeof loadSimpleMode === 'function') {
                        loadSimpleMode(img);
                        console.log('🟢 [CONFIRM] loadSimpleMode exécuté ✓');
                    } else {
                        throw new Error('loadSimpleMode n\'est pas une fonction');
                    }
                    break;
                    
                case 'advanced':
                    console.log('🟢 [CONFIRM] Appel loadAdvancedMode...');
                    if (typeof loadAdvancedMode === 'function') {
                        loadAdvancedMode(img);
                        console.log('🟢 [CONFIRM] loadAdvancedMode exécuté ✓');
                    } else {
                        throw new Error('loadAdvancedMode n\'est pas une fonction');
                    }
                    break;
                    
                case 'pro':
                    console.log('🟢 [CONFIRM] Appel loadProMode...');
                    if (typeof loadProMode === 'function') {
                        loadProMode(img);
                        console.log('🟢 [CONFIRM] loadProMode exécuté ✓');
                    } else {
                        throw new Error('loadProMode n\'est pas une fonction');
                    }
                    break;
                    
                default:
                    console.warn('🟡 [CONFIRM] Mode inconnu, fallback sur simple');
                    if (typeof loadSimpleMode === 'function') {
                        loadSimpleMode(img);
                        console.log('🟢 [CONFIRM] loadSimpleMode exécuté (fallback) ✓');
                    } else {
                        throw new Error('Aucune fonction de chargement disponible');
                    }
            }
            
            console.log('🟢 [CONFIRM] Image chargée dans l\'éditeur avec SUCCÈS ✓✓✓');
            console.log('═══════════════════════════════════════════');
            
        } catch (error) {
            console.error('🔴 [CONFIRM] ERREUR lors du chargement:', error);
            console.error('🔴 [CONFIRM] Stack:', error.stack);
            alert('❌ Erreur lors du chargement dans l\'éditeur:\n\n' + error.message);
        }
    };
    
    img.onerror = function(error) {
        console.error('🔴 [CONFIRM] ERREUR de chargement de l\'image');
        console.error('🔴 [CONFIRM] URL:', imageToLoad.file_path);
        console.error('🔴 [CONFIRM] Error:', error);
        alert('❌ Impossible de charger l\'image.\n\nChemin: ' + imageToLoad.file_path + '\n\nVérifiez que le fichier existe sur le serveur.');
    };
    
    // Charger l'image
    console.log('🟢 [CONFIRM] Démarrage du chargement...');
    console.log('🟢 [CONFIRM] URL:', imageToLoad.file_path);
    img.src = imageToLoad.file_path;
}

/**
 * Recherche dans les images
 */
let searchTimeout;
function searchUserImages() {
    const searchInput = document.getElementById('imageSelectorSearch');
    if (!searchInput) return;
    
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        loadUserImages(searchInput.value.trim());
    }, 300);
}

/**
 * Fermer le modal en cliquant à l'extérieur
 */
document.addEventListener('click', function(e) {
    const modal = document.getElementById('imageSelectorModal');
    if (modal && e.target === modal) {
        closeImageSelector();
    }
});

/**
 * Fermer avec la touche Échap
 */
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('imageSelectorModal');
        if (modal && modal.classList.contains('active')) {
            closeImageSelector();
        }
    }
});

console.log('✅ editor-image-selector.js chargé (VERSION ULTRA-ROBUSTE)');