<?php
require_once 'config.php';
require_once 'security.php';

// Vérifier la connexion
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$pageTitle = "Éditeur d'images";

// Récupérer l'image à éditer si spécifiée
$imageId = intval($_GET['id'] ?? 0);
$image = null;

if ($imageId > 0) {
    $stmt = $pdo->prepare("
        SELECT * FROM images 
        WHERE id = ? AND user_id = ? AND is_deleted = 0
    ");
    $stmt->execute([$imageId, $userId]);
    $image = $stmt->fetch();
}

// Inclure le header
require_once 'header.php';
?>
    
    <!-- Librairies externes -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.0/fabric.min.js"></script>
    
    <!-- Styles de l'éditeur -->
    <link rel="stylesheet" href="assets/css/editor-styles.css">
	<link rel="stylesheet" href="assets/css/editor-header-fix.css">
	<link rel="stylesheet" href="assets/css/editor-pro-styles.css">
	<link rel="stylesheet" href="assets/css/editeur-responsive.css">
	<link rel="stylesheet" href="assets/css/editor-image-selector.css">

	
    
    <div class="editor-container">
        <!-- Sélecteur de mode -->
        <div class="mode-selector">
            <div class="mode-tabs">
                <div class="mode-tab active" data-mode="simple">
                    <div class="mode-tab-title">🎨 Simple</div>
                    <div class="mode-tab-desc">Filtres & ajustements</div>
                </div>
                <div class="mode-tab" data-mode="advanced">
                    <div class="mode-tab-title">✂️ Avancé</div>
                    <div class="mode-tab-desc">Recadrage précis</div>
                </div>
                <div class="mode-tab" data-mode="pro">
                    <div class="mode-tab-title">⭐ Pro</div>
                    <div class="mode-tab-desc">Texte & annotations</div>
                </div>
            </div>
            
            <div id="modeDescription" style="color: #666; font-size: 14px; margin-bottom: 10px;">
                Mode Simple : Ajustez luminosité, contraste, saturation et effectuez des rotations simples.
            </div>
            
            <!-- Instructions spécifiques par mode -->
            <div id="modeInstructions" style="background: #e3f2fd; padding: 15px; border-radius: 8px; margin-top: 10px; font-size: 13px; line-height: 1.6;">
                <strong>💡 Comment utiliser :</strong>
                <div id="instructionsSimple" style="display: block;">
                    1️⃣ Chargez une image à gauche<br>
                    2️⃣ Utilisez les sliders à droite pour ajuster<br>
                    3️⃣ L'aperçu se met à jour en temps réel<br>
                    4️⃣ Cliquez sur "Sauvegarder" quand c'est prêt
                </div>
                <div id="instructionsAdvanced" style="display: none;">
                    1️⃣ Chargez une image à gauche<br>
                    2️⃣ Sélectionnez un ratio ou utilisez "Libre"<br>
                    3️⃣ Déplacez et redimensionnez la zone de recadrage<br>
                    4️⃣ Cliquez sur "Recadrer & Sauvegarder"
                </div>
                <div id="instructionsPro" style="display: none;">
                    1️⃣ Chargez une image à gauche<br>
                    2️⃣ Ajoutez du texte ou des formes avec les boutons<br>
                    3️⃣ <strong>Cliquez sur un élément pour le sélectionner</strong><br>
                    4️⃣ Déplacez-le en le faisant glisser<br>
                    5️⃣ Redimensionnez avec les coins<br>
                    6️⃣ Double-cliquez sur le texte pour le modifier<br>
                    7️⃣ Cliquez sur "Sauvegarder" quand c'est prêt
                </div>
            </div>
        </div>
        
        <!-- Zone d'édition -->
        <div class="editor-workspace">
            <!-- Sidebar gauche - Upload -->
            <div class="editor-sidebar">
                <div class="sidebar-title">📁 Votre image</div>
                
                <?php if ($image): ?>
                    <div style="margin-bottom: 15px; padding: 15px; background: #f8f9ff; border-radius: 8px;">
                        <div style="font-weight: 600; margin-bottom: 5px;">
                            <?= htmlspecialchars($image['original_filename'] ?? $image['filename']) ?>
                        </div>
                        <div style="font-size: 13px; color: #666;">
                            <?= $image['width'] ?>x<?= $image['height'] ?> px
                        </div>
                    </div>
                    <input type="hidden" id="originalImageId" value="<?= $image['id'] ?>">
                <?php endif; ?>
                
                <div class="upload-zone" id="uploadZone">
                    <div class="upload-icon">📤</div>
                    <div style="font-weight: 600; margin-bottom: 5px;">
                        <?= $image ? 'Changer d\'image' : 'Glissez une image' ?>
                    </div>
                    <div style="font-size: 13px; color: #666;">
                        ou cliquez pour sélectionner
                    </div>
                </div>
                <input type="file" id="fileInput" accept="image/*">
                <div style="margin-top: 15px;">
				<button class="btn btn-secondary btn-block" onclick="openImageSelector()">
					🖼️ Choisir depuis mes images
					</button>
				</div>
                <div style="margin-top: 20px;">
                    <button class="btn btn-secondary btn-block" onclick="window.location.href='dashboard.php'">
                        ↩️ Retour au dashboard
                    </button>
                </div>
            </div>
            
            <!-- Zone canvas centrale -->
            <div class="editor-canvas-area" id="canvasArea">
                <div id="emptyState" style="text-align: center; color: #999; <?= $image ? 'display: none;' : '' ?>">
                    <div style="font-size: 64px; margin-bottom: 20px;">🎨</div>
                    <div style="font-size: 18px; font-weight: 600; margin-bottom: 10px;">
                        Commencez par charger une image
                    </div>
                    <div style="font-size: 14px;">
                        Glissez-déposez ou cliquez sur la zone d'upload à gauche
                    </div>
                </div>
                
                <!-- Canvas pour mode Simple -->
                <canvas id="editorCanvas" style="display: none;"></canvas>
                
                <!-- Image pour mode Avancé (Cropper.js) -->
                <img id="cropperImage" alt="Image à recadrer">
                
                <!-- Canvas pour mode Pro (Fabric.js) -->
                <canvas id="fabricCanvas" width="800" height="600" style="display: none;"></canvas>
            </div>
            
            <!-- Sidebar droite - Contrôles -->
            <?php include 'includes/editor-controls.php'; ?>
        </div>
    </div>
    
    <!-- Scripts de l'éditeur -->
    <script src="assets/js/editor-core.js"></script>
    <script src="assets/js/editor-simple-mode.js"></script>
    <script src="assets/js/editor-advanced-mode.js"></script>
    <script src="assets/js/editor-pro-mode.js"></script>
	<script src="assets/js/editor-image-selector.js"></script>    
    <?php if ($image): ?>
    <script>
        // Charger l'image existante au démarrage
        document.addEventListener('DOMContentLoaded', function() {
            const img = new Image();
            img.crossOrigin = 'anonymous';
            img.onload = function() {
                originalImage = img;
                document.getElementById('emptyState').style.display = 'none';
                loadSimpleMode(img);
            };
            img.src = '<?= htmlspecialchars($image['file_path']) ?>';
        });
    </script>
    <?php endif; ?>
	
	<!-- Modal de sélection d'images -->
<div id="imageSelectorModal" class="image-selector-modal">
    <div class="image-selector-content">
        <!-- Header -->
        <div class="image-selector-header">
            <h3>
                <span style="font-size: 24px;">🖼️</span>
                Sélectionner une image
            </h3>
            <button class="close-modal" onclick="closeImageSelector()" title="Fermer">
                ✕
            </button>
        </div>
        
        <!-- Barre de recherche -->
        <div class="image-selector-search">
            <input 
                type="text" 
                id="imageSelectorSearch" 
                placeholder="🔍 Rechercher dans vos images..." 
                oninput="searchUserImages()">
        </div>
        
        <!-- Corps avec la grille d'images -->
        <div class="image-selector-body">
            <div id="imageSelectorGrid">
                <!-- Les images seront chargées ici par JavaScript -->
            </div>
        </div>
        
        <!-- Footer avec boutons -->
        <div class="image-selector-footer">
            <button class="btn btn-secondary" onclick="closeImageSelector()">
                Annuler
            </button>
            <button class="btn btn-primary" onclick="confirmImageSelection()">
                ✓ Utiliser cette image
            </button>
        </div>
    </div>
</div>

	
</body>
</html>
