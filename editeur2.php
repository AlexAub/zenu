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
    
    <!-- Cropper.js pour le mode Avancé -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
    
    <!-- Fabric.js pour le mode Pro -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.0/fabric.min.js"></script>
    
    <style>
        body {
            background: #f5f7fa;
        }
        
        /* Conteneur principal */
        .editor-container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 20px;
        }
        
        /* Sélecteur de mode */
        .mode-selector {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .mode-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .mode-tab {
            flex: 1;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
            background: white;
        }
        
        .mode-tab:hover {
            border-color: #667eea;
            transform: translateY(-2px);
        }
        
        .mode-tab.active {
            border-color: #667eea;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .mode-tab-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .mode-tab-desc {
            font-size: 13px;
            opacity: 0.8;
        }
        
        /* Zone d'édition */
        .editor-workspace {
            display: grid;
            grid-template-columns: 300px 1fr 300px;
            gap: 20px;
            min-height: 600px;
        }
        
        .editor-sidebar {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .editor-canvas-area {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            pointer-events: auto !important;
            position: relative;
            z-index: 1;
        }
        
        .sidebar-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #333;
        }
        
        /* Upload zone */
        .upload-zone {
            border: 3px dashed #667eea;
            border-radius: 12px;
            padding: 40px 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: #f8f9ff;
        }
        
        .upload-zone:hover {
            border-color: #764ba2;
            background: #f0f2ff;
        }
        
        .upload-zone.drag-over {
            border-color: #764ba2;
            background: #e8e9ff;
        }
        
        .upload-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        
        #fileInput {
            display: none;
        }
        
        /* Canvas */
        #editorCanvas {
            max-width: 100%;
            max-height: 600px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
        }
        
        #cropperImage {
            max-width: 100%;
            display: none;
        }
        
        #fabricCanvas {
			border: 1px solid #e0e0e0;
			/* Retirer le cursor: crosshair pour permettre aux objets de définir leurs propres curseurs */
		}
        
        /* CRITICAL : S'assurer que le canvas reçoit les événements de souris */
        .canvas-container {
            position: relative !important;
            pointer-events: auto !important;
            z-index: 10 !important;
        }
        
        .canvas-container canvas {
            pointer-events: auto !important;
        }
        
        /* S'assurer que rien ne bloque le canvas */
        .upper-canvas {
            pointer-events: auto !important;
            z-index: 12 !important;
            position: relative !important;
        }
        
        .lower-canvas {
            z-index: 11 !important;
        }
        
        /* Contrôles */
        .control-group {
            margin-bottom: 20px;
        }
        
        .control-label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 8px;
            color: #555;
        }
        
        .control-slider {
            width: 100%;
            height: 6px;
            border-radius: 3px;
            background: #e0e0e0;
            outline: none;
            -webkit-appearance: none;
        }
        
        .control-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: #667eea;
            cursor: pointer;
        }
        
        .control-slider::-moz-range-thumb {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: #667eea;
            cursor: pointer;
            border: none;
        }
        
        .control-value {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 12px;
            margin-left: 10px;
        }
        
        /* Boutons */
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            justify-content: center;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .btn-small {
            padding: 8px 16px;
            font-size: 13px;
        }
        
        .btn-full {
            width: 100%;
            margin-bottom: 10px;
        }
        
        /* Input personnalisé */
        input[type="text"],
        input[type="number"],
        input[type="color"],
        select {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        input[type="text"]:focus,
        input[type="number"]:focus,
        select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        input[type="color"] {
            height: 40px;
            cursor: pointer;
        }
        
        /* Mode caché */
        .mode-content {
            display: none;
        }
        
        .mode-content.active {
            display: block;
        }
        
        /* Alertes */
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        /* Responsive */
        @media (max-width: 1200px) {
            .editor-workspace {
                grid-template-columns: 1fr;
            }
            
            .editor-sidebar {
                order: 2;
            }
            
            .editor-canvas-area {
                order: 1;
            }
        }
        
        /* Outils de texte (mode Pro) */
        .text-tools {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .tool-btn {
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            background: white;
            cursor: pointer;
            font-size: 20px;
            transition: all 0.3s;
        }
        
        .tool-btn:hover {
            border-color: #667eea;
            background: #f8f9ff;
        }
        
        /* Boutons de style de texte */
        .style-btn {
            padding: 8px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            background: white;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .style-btn:hover {
            border-color: #667eea;
            background: #f8f9ff;
            transform: translateY(-1px);
        }
        
        .style-btn.active {
            border-color: #667eea;
            background: #667eea;
            color: white;
        }
        
        .shape-tools {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
        }
        .crop-presets {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-bottom: 15px;
        }
        
        .preset-btn {
            padding: 8px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            background: white;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.3s;
        }
        
        .preset-btn:hover {
            border-color: #667eea;
            background: #f8f9ff;
        }
    </style>
</head>
<body>   
    <!-- Conteneur principal -->
    <div class="editor-container">
        <!-- Sélecteur de mode -->
        <div class="mode-selector">
            <div class="mode-tabs">
                <div class="mode-tab active" data-mode="simple">
                    <div class="mode-tab-title">✨ Simple</div>
                    <div class="mode-tab-desc">Filtres et rotation</div>
                </div>
                <div class="mode-tab" data-mode="advanced">
                    <div class="mode-tab-title">✂️ Avancé</div>
                    <div class="mode-tab-desc">Recadrage précis</div>
                </div>
                <div class="mode-tab" data-mode="pro">
                    <div class="mode-tab-title">🚀 Pro</div>
                    <div class="mode-tab-desc">Texte & formes</div>
                </div>
            </div>
            
            <div class="alert alert-info" id="modeDescription">
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
                
                <div style="margin-top: 20px;">
                    <button class="btn btn-secondary btn-full" onclick="window.location.href='dashboard.php'">
                        ↩️ Retour au dashboard
                    </button>
                </div>
            </div>
            
            <!-- Zone canvas centrale -->
            <div class="editor-canvas-area" id="canvasArea">
                <div id="emptyState" style="text-align: center; color: #999;">
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
            <div class="editor-sidebar">
                <div class="sidebar-title">🎛️ Contrôles</div>
                
                <!-- MODE SIMPLE -->
                <div id="simpleControls" class="mode-content active">
                    <div class="control-group">
                        <label class="control-label">
                            ☀️ Luminosité 
                            <span class="control-value" id="brightnessValue">100%</span>
                        </label>
                        <input type="range" class="control-slider" id="brightness" min="0" max="200" value="100">
                    </div>
                    
                    <div class="control-group">
                        <label class="control-label">
                            🎭 Contraste 
                            <span class="control-value" id="contrastValue">100%</span>
                        </label>
                        <input type="range" class="control-slider" id="contrast" min="0" max="200" value="100">
                    </div>
                    
                    <div class="control-group">
                        <label class="control-label">
                            🌈 Saturation 
                            <span class="control-value" id="saturationValue">100%</span>
                        </label>
                        <input type="range" class="control-slider" id="saturation" min="0" max="200" value="100">
                    </div>
                    
                    <div class="control-group">
                        <label class="control-label">
                            🌫️ Flou 
                            <span class="control-value" id="blurValue">0px</span>
                        </label>
                        <input type="range" class="control-slider" id="blur" min="0" max="10" value="0">
                    </div>
                    
                    <div class="control-group">
                        <label class="control-label">🔄 Rotation & Flip</label>
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px; margin-top: 10px;">
                            <button class="btn btn-secondary btn-small" onclick="rotate(-90)">↶ 90°</button>
                            <button class="btn btn-secondary btn-small" onclick="rotate(90)">↷ 90°</button>
                            <button class="btn btn-secondary btn-small" onclick="flipHorizontal()">↔️ Flip</button>
                        </div>
                    </div>
                    
                    <div class="control-group">
                        <label class="control-label">🎨 Filtres rapides</label>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 10px;">
                            <button class="btn btn-secondary btn-small" onclick="applyFilter('grayscale')">⚫ N&B</button>
                            <button class="btn btn-secondary btn-small" onclick="applyFilter('sepia')">🟤 Sépia</button>
                            <button class="btn btn-secondary btn-small" onclick="applyFilter('vintage')">📷 Vintage</button>
                            <button class="btn btn-secondary btn-small" onclick="resetFilters()">🔄 Reset</button>
                        </div>
                    </div>
                    
                    <button class="btn btn-primary btn-full" onclick="saveImage('simple')">
                        💾 Sauvegarder
                    </button>
                </div>
                
                <!-- MODE AVANCÉ -->
                <div id="advancedControls" class="mode-content">
                    <div class="control-group">
                        <label class="control-label">📐 Ratios prédéfinis</label>
                        <div class="crop-presets">
                            <button class="preset-btn" onclick="setCropRatio(NaN)">Libre</button>
                            <button class="preset-btn" onclick="setCropRatio(1)">1:1</button>
                            <button class="preset-btn" onclick="setCropRatio(4/3)">4:3</button>
                            <button class="preset-btn" onclick="setCropRatio(16/9)">16:9</button>
                            <button class="preset-btn" onclick="setCropRatio(3/2)">3:2</button>
                            <button class="preset-btn" onclick="setCropRatio(9/16)">9:16</button>
                        </div>
                    </div>
                    
                    <div class="control-group">
                        <label class="control-label">🔄 Rotation libre</label>
                        <input type="range" class="control-slider" id="cropRotate" min="-180" max="180" value="0">
                        <div style="text-align: center; margin-top: 5px;">
                            <span class="control-value" id="rotateValue">0°</span>
                        </div>
                    </div>
                    
                    <div class="control-group">
                        <label class="control-label">🔍 Zoom</label>
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px;">
                            <button class="btn btn-secondary btn-small" onclick="cropZoom(-0.1)">➖</button>
                            <button class="btn btn-secondary btn-small" onclick="cropZoom(0)">100%</button>
                            <button class="btn btn-secondary btn-small" onclick="cropZoom(0.1)">➕</button>
                        </div>
                    </div>
                    
                    <button class="btn btn-primary btn-full" onclick="saveImage('advanced')">
                        ✂️ Recadrer & Sauvegarder
                    </button>
                    
                    <button class="btn btn-secondary btn-full" onclick="resetCrop()">
                        🔄 Réinitialiser
                    </button>
                </div>
                
                <!-- MODE PRO -->
                <div id="proControls" class="mode-content">
                    <!-- Indicateur de sélection -->
                    <div id="selectionIndicator" style="display: none; background: #d4edda; border: 2px solid #28a745; padding: 12px; border-radius: 8px; margin-bottom: 15px; font-size: 13px;">
                        <strong>✅ Élément sélectionné</strong><br>
                        <span style="font-size: 12px; color: #155724;">Modifiez ses propriétés ci-dessous</span>
                    </div>
                    
                    <!-- TEXTE -->
                    <div class="control-group">
                        <label class="control-label">✏️ Ajouter du texte</label>
                        <input type="text" id="textInput" placeholder="Votre texte..." style="margin-bottom: 8px;">
                        
                        <!-- Police -->
                        <select id="textFont" onchange="updateSelectedTextFont()" style="width: 100%; padding: 8px; margin-bottom: 8px; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="Arial">Arial</option>
                            <option value="Helvetica">Helvetica</option>
                            <option value="Times New Roman">Times New Roman</option>
                            <option value="Georgia">Georgia</option>
                            <option value="Courier New">Courier New</option>
                            <option value="Verdana">Verdana</option>
                            <option value="Impact">Impact</option>
                            <option value="Comic Sans MS">Comic Sans MS</option>
                            <option value="Trebuchet MS">Trebuchet MS</option>
                            <option value="Palatino">Palatino</option>
                            <option value="Garamond">Garamond</option>
                            <option value="Bookman">Bookman</option>
                            <option value="Avant Garde">Avant Garde</option>
                        </select>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 8px;">
                            <!-- Taille -->
                            <input type="number" id="textSize" value="40" min="10" max="300" placeholder="Taille" onchange="updateSelectedTextSize()" style="padding: 8px;">
                            <!-- Couleur texte -->
                            <input type="color" id="textColor" value="#ffffff" onchange="updateSelectedTextColor()" title="Couleur du texte">
                        </div>
                        
                        <!-- Style de texte -->
                        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 5px; margin-bottom: 8px;">
                            <button class="style-btn" onclick="toggleTextStyle('bold')" title="Gras">
                                <strong>B</strong>
                            </button>
                            <button class="style-btn" onclick="toggleTextStyle('italic')" title="Italique">
                                <em>I</em>
                            </button>
                            <button class="style-btn" onclick="toggleTextStyle('underline')" title="Souligné">
                                <u>U</u>
                            </button>
                            <button class="style-btn" onclick="toggleTextStyle('linethrough')" title="Barré">
                                <s>S</s>
                            </button>
                        </div>
                        
                        <!-- Contour -->
                        <div style="display: grid; grid-template-columns: 1fr 80px; gap: 8px; margin-bottom: 8px;">
                            <input type="color" id="textStrokeColor" value="#000000" onchange="updateSelectedTextStroke()" title="Couleur du contour">
                            <input type="number" id="textStrokeWidth" value="1" min="0" max="10" onchange="updateSelectedTextStroke()" placeholder="Épaisseur" style="padding: 8px;">
                        </div>
                        
                        <!-- Ombre -->
                        <div style="margin-bottom: 8px;">
                            <label style="display: flex; align-items: center; font-size: 13px;">
                                <input type="checkbox" id="textShadow" onchange="updateSelectedTextShadow()" style="margin-right: 5px;">
                                Ajouter une ombre
                            </label>
                        </div>
                        
                        <button class="btn btn-secondary btn-full btn-small" onclick="addText()">
                            ➕ Ajouter le texte
                        </button>
                    </div>
                    
                    <!-- FORMES -->
                    <div class="control-group">
                        <label class="control-label">🔷 Formes</label>
                        <div class="shape-tools">
                            <button class="tool-btn" onclick="addShape('rect')" title="Rectangle">⬜</button>
                            <button class="tool-btn" onclick="addShape('circle')" title="Cercle">⚪</button>
                            <button class="tool-btn" onclick="addShape('triangle')" title="Triangle">🔺</button>
                            <button class="tool-btn" onclick="addShape('line')" title="Ligne">➖</button>
                            <button class="tool-btn" onclick="addShape('arrow')" title="Flèche">➡️</button>
                            <button class="tool-btn" onclick="addShape('star')" title="Étoile">⭐</button>
                            <button class="tool-btn" onclick="addShape('polygon')" title="Hexagone">⬡</button>
                            <button class="tool-btn" onclick="addShape('heart')" title="Cœur">❤️</button>
                        </div>
                        
                        <!-- Couleurs forme -->
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 8px;">
                            <div>
                                <label style="font-size: 12px; display: block; margin-bottom: 3px;">Remplissage</label>
                                <input type="color" id="shapeFillColor" value="#ff0000" style="width: 100%;">
                            </div>
                            <div>
                                <label style="font-size: 12px; display: block; margin-bottom: 3px;">Contour</label>
                                <input type="color" id="shapeStrokeColor" value="#000000" style="width: 100%;">
                            </div>
                        </div>
                        
                        <!-- Opacité et épaisseur -->
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 8px;">
                            <div>
                                <label style="font-size: 12px; display: block; margin-bottom: 3px;">Opacité</label>
                                <input type="range" id="shapeOpacity" min="0" max="100" value="80" style="width: 100%;">
                            </div>
                            <div>
                                <label style="font-size: 12px; display: block; margin-bottom: 3px;">Bordure</label>
                                <input type="number" id="shapeStrokeWidth" value="3" min="0" max="20" style="width: 100%; padding: 4px;">
                            </div>
                        </div>
                        
                        <!-- Style de bordure -->
                        <div style="margin-top: 8px;">
                            <label style="font-size: 12px; display: block; margin-bottom: 3px;">Style bordure</label>
                            <select id="shapeStrokeDash" style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px;">
                                <option value="solid">Solide</option>
                                <option value="dashed">Pointillés</option>
                                <option value="dotted">Points</option>
                            </select>
                        </div>
                        
                        <!-- Coins arrondis pour rectangle -->
                        <div style="margin-top: 8px;">
                            <label style="font-size: 12px; display: block; margin-bottom: 3px;">Coins arrondis (rectangle)</label>
                            <input type="range" id="shapeRoundedCorners" min="0" max="50" value="0" style="width: 100%;">
                        </div>
                    </div>
                    
                    <!-- MODIFICATIONS -->
                    <div class="control-group" id="modifyControls" style="display: none;">
                        <label class="control-label">🎛️ Modifier la sélection</label>
                        
                        <!-- Opacité de l'objet sélectionné -->
                        <div style="margin-bottom: 10px;">
                            <label style="font-size: 12px; display: block; margin-bottom: 3px;">Opacité</label>
                            <input type="range" id="objectOpacity" min="0" max="100" value="100" onchange="updateSelectedObjectOpacity()" style="width: 100%;">
                        </div>
                        
                        <!-- Rotation -->
                        <div style="margin-bottom: 10px;">
                            <label style="font-size: 12px; display: block; margin-bottom: 3px;">Rotation (degrés)</label>
                            <input type="number" id="objectRotation" value="0" min="-180" max="180" onchange="updateSelectedObjectRotation()" style="width: 100%; padding: 6px;">
                        </div>
                        
                        <!-- Ordre des calques -->
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 10px;">
                            <button class="btn btn-secondary btn-small" onclick="bringToFront()">⬆️ Avant</button>
                            <button class="btn btn-secondary btn-small" onclick="sendToBack()">⬇️ Arrière</button>
                        </div>
                        
                        <!-- Flip -->
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                            <button class="btn btn-secondary btn-small" onclick="flipObjectH()">↔️ Flip H</button>
                            <button class="btn btn-secondary btn-small" onclick="flipObjectV()">↕️ Flip V</button>
                        </div>
                    </div>
                    
                    <!-- ACTIONS -->
                    <div class="control-group">
                        <label class="control-label">🎨 Actions</label>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                            <button class="btn btn-secondary btn-small" onclick="duplicateSelected()">📋 Dupliquer</button>
                            <button class="btn btn-secondary btn-small" onclick="deleteSelected()">🗑️ Supprimer</button>
                        </div>
                        <button class="btn btn-secondary btn-full btn-small" onclick="clearCanvas()" style="margin-top: 8px;">
                            🧹 Tout effacer
                        </button>
                    </div>
                    
                    <button class="btn btn-primary btn-full" onclick="saveImage('pro')">
                        💾 Sauvegarder
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Variables globales
        let currentMode = 'simple';
        let originalImage = null;
        let simpleCanvas = null;
        let simpleCtx = null;
        let cropper = null;
        let fabricCanvas = null;
        let currentRotation = 0;
        let currentFlipH = false;
        let filters = {
            brightness: 100,
            contrast: 100,
            saturation: 100,
            blur: 0
        };
        
        // Initialisation
        document.addEventListener('DOMContentLoaded', function() {
            simpleCanvas = document.getElementById('editorCanvas');
            simpleCtx = simpleCanvas.getContext('2d');
            
            // NE PAS initialiser Fabric.js ici - il sera initialisé dans loadProMode()
            // quand le canvas sera visible
            
            // Listeners d'événements seront ajoutés lors de l'initialisation de Fabric.js
            
            // Upload zone
            const uploadZone = document.getElementById('uploadZone');
            const fileInput = document.getElementById('fileInput');
            
            uploadZone.addEventListener('click', () => fileInput.click());
            fileInput.addEventListener('change', handleFileSelect);
            
            // Drag & drop
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
            
            // Mode tabs
            document.querySelectorAll('.mode-tab').forEach(tab => {
                tab.addEventListener('click', function() {
                    switchMode(this.dataset.mode);
                });
            });
            
            // Sliders mode simple
            setupSimpleControls();
            
            // Charger l'image initiale si spécifiée
            <?php if ($image): ?>
            loadImageFromUrl('<?= htmlspecialchars($image['file_path']) ?>');
            <?php endif; ?>
        });
        
        // Gestion de fichier
        function handleFileSelect(e) {
            const file = e.target.files[0];
            if (file) handleFile(file);
        }
        
        function handleFile(file) {
            if (!file.type.startsWith('image/')) {
                alert('Veuillez sélectionner une image valide');
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                loadImage(e.target.result);
            };
            reader.readAsDataURL(file);
        }
        
        function loadImageFromUrl(url) {
            const img = new Image();
            img.crossOrigin = 'anonymous';
            img.onload = function() {
                loadImage(img.src);
            };
            img.src = url;
        }
        
        function loadImage(src) {
            const img = new Image();
            img.onload = function() {
                originalImage = img;
                document.getElementById('emptyState').style.display = 'none';
                
                // Charger dans le mode actuel
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
            img.src = src;
        }
        
        // MODE SIMPLE
        function loadSimpleMode(img) {
            simpleCanvas.width = Math.min(img.width, 1000);
            simpleCanvas.height = (simpleCanvas.width / img.width) * img.height;
            simpleCanvas.style.display = 'block';
            document.getElementById('cropperImage').style.display = 'none';
            document.getElementById('fabricCanvas').style.display = 'none';
            
            drawSimpleCanvas();
        }
        
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
        
        function setupSimpleControls() {
            const controls = ['brightness', 'contrast', 'saturation', 'blur'];
            
            controls.forEach(control => {
                const slider = document.getElementById(control);
                const valueDisplay = document.getElementById(control + 'Value');
                
                slider.addEventListener('input', function() {
                    filters[control] = this.value;
                    const unit = control === 'blur' ? 'px' : '%';
                    valueDisplay.textContent = this.value + unit;
                    drawSimpleCanvas();
                });
            });
        }
        
        function rotate(degrees) {
            currentRotation = (currentRotation + degrees) % 360;
            drawSimpleCanvas();
        }
        
        function flipHorizontal() {
            currentFlipH = !currentFlipH;
            drawSimpleCanvas();
        }
        
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
        
        function resetFilters() {
            filters = { brightness: 100, contrast: 100, saturation: 100, blur: 0 };
            currentRotation = 0;
            currentFlipH = false;
            updateSliders();
            drawSimpleCanvas();
        }
        
        function updateSliders() {
            Object.keys(filters).forEach(key => {
                document.getElementById(key).value = filters[key];
                const unit = key === 'blur' ? 'px' : '%';
                document.getElementById(key + 'Value').textContent = filters[key] + unit;
            });
        }
        
        // MODE AVANCÉ
        function loadAdvancedMode(img) {
            const cropperImg = document.getElementById('cropperImage');
            cropperImg.src = img.src;
            cropperImg.style.display = 'block';
            simpleCanvas.style.display = 'none';
            document.getElementById('fabricCanvas').style.display = 'none';
            
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
            
            rotateSlider.addEventListener('input', function() {
                cropper.rotateTo(this.value);
                rotateValue.textContent = this.value + '°';
            });
        }
        
        function setCropRatio(ratio) {
            if (cropper) {
                cropper.setAspectRatio(ratio);
            }
        }
        
        function cropZoom(delta) {
            if (!cropper) return;
            
            if (delta === 0) {
                cropper.reset();
            } else {
                cropper.zoom(delta);
            }
        }
        
        function resetCrop() {
            if (cropper) {
                cropper.reset();
                document.getElementById('cropRotate').value = 0;
                document.getElementById('rotateValue').textContent = '0°';
            }
        }
        
        // MODE PRO
		function loadProMode(img) {
			document.getElementById('fabricCanvas').style.display = 'block';
			simpleCanvas.style.display = 'none';
			document.getElementById('cropperImage').style.display = 'none';
    
			// CRITIQUE : Initialiser ou réinitialiser Fabric.js APRÈS que le canvas soit visible
			if (!fabricCanvas) {
				// Première initialisation
				fabricCanvas = new fabric.Canvas('fabricCanvas', {
					selection: true,
					interactive: true,
					enableRetinaScaling: true,
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
				fabricCanvas.on('mouse:down', function(e) {
					console.log('Click détecté', e.target ? 'sur objet' : 'sur canvas vide');
					if (e.target) {
						console.log('Type d\'objet:', e.target.type);
					}
				});
				
				fabricCanvas.on('selection:created', function(e) {
					console.log('Sélection créée:', e.selected);
					const indicator = document.getElementById('selectionIndicator');
					const modifyControls = document.getElementById('modifyControls');
					if (indicator) indicator.style.display = 'block';
					if (modifyControls) {
						modifyControls.style.display = 'block';
						const obj = fabricCanvas.getActiveObject();
						if (obj) {
							document.getElementById('objectOpacity').value = (obj.opacity || 1) * 100;
							document.getElementById('objectRotation').value = obj.angle || 0;
							
							// Si c'est un texte, charger ses propriétés
							if (obj.type === 'i-text') {
								document.getElementById('textFont').value = obj.fontFamily || 'Arial';
								document.getElementById('textSize').value = obj.fontSize || 40;
								document.getElementById('textColor').value = obj.fill || '#ffffff';
								document.getElementById('textStrokeColor').value = obj.stroke || '#000000';
								document.getElementById('textStrokeWidth').value = obj.strokeWidth || 0;
							}
						}
					}
				});
				
				fabricCanvas.on('selection:updated', function(e) {
					console.log('Sélection mise à jour:', e.selected);
					const indicator = document.getElementById('selectionIndicator');
					const modifyControls = document.getElementById('modifyControls');
					if (indicator) indicator.style.display = 'block';
					if (modifyControls) {
						modifyControls.style.display = 'block';
						const obj = fabricCanvas.getActiveObject();
						if (obj) {
							document.getElementById('objectOpacity').value = (obj.opacity || 1) * 100;
							document.getElementById('objectRotation').value = obj.angle || 0;
							
							// Si c'est un texte, charger ses propriétés
							if (obj.type === 'i-text') {
								document.getElementById('textFont').value = obj.fontFamily || 'Arial';
								document.getElementById('textSize').value = obj.fontSize || 40;
								document.getElementById('textColor').value = obj.fill || '#ffffff';
								document.getElementById('textStrokeColor').value = obj.stroke || '#000000';
								document.getElementById('textStrokeWidth').value = obj.strokeWidth || 0;
							}
						}
					}
				});
				
				fabricCanvas.on('selection:cleared', function() {
					console.log('Sélection effacée');
					const indicator = document.getElementById('selectionIndicator');
					const modifyControls = document.getElementById('modifyControls');
					if (indicator) indicator.style.display = 'none';
					if (modifyControls) modifyControls.style.display = 'none';
				});
				
				fabricCanvas.on('object:moving', function(e) {
					console.log('Objet en mouvement:', e.target.type);
				});
				
				fabricCanvas.on('object:scaling', function(e) {
					console.log('Objet en redimensionnement:', e.target.type);
				});
				
				console.log('Fabric.js initialisé pour la première fois');
			} else {
				// Canvas déjà initialisé, juste le nettoyer
				fabricCanvas.clear();
			}
			
			fabricCanvas.backgroundColor = '#ffffff';
    
			fabric.Image.fromURL(img.src, function(fabricImg) {
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
			fabricCanvas.setWidth(fabricImg.width * scale);
			fabricCanvas.setHeight(fabricImg.height * scale);
        
			fabricImg.set({
				scaleX: scale,
				scaleY: scale,
				selectable: false,
				evented: false,
				hoverCursor: 'default'
        });
        
        // Utiliser setBackgroundImage au lieu de add() pour que l'image ne bloque pas
        fabricCanvas.setBackgroundImage(fabricImg, function() {
            // CRITIQUE : Réactiver complètement toutes les interactions
            fabricCanvas.selection = true;
            fabricCanvas.interactive = true;
            fabricCanvas.skipTargetFind = false;
            
            // IMPORTANT : NE PAS définir hoverCursor ici - laissez les objets le gérer
            fabricCanvas.defaultCursor = 'default';
            
            // CRITIQUE : S'assurer que le canvas reçoit les événements de souris
            const canvasElement = fabricCanvas.getElement();
            const upperCanvas = fabricCanvas.upperCanvasEl;
            const container = canvasElement?.parentElement;
            
            if (container) {
                container.style.position = 'relative';
                container.style.zIndex = '10';
                container.style.pointerEvents = 'auto';
            }
            if (canvasElement) {
                canvasElement.style.pointerEvents = 'auto';
            }
            if (upperCanvas) {
                upperCanvas.style.pointerEvents = 'auto';
                upperCanvas.style.position = 'absolute';
                upperCanvas.style.zIndex = '12';
            }
            
            // Forcer le rendu
            fabricCanvas.renderAll();
            
            console.log('Canvas Pro chargé - Interactions activées');
            console.log('Selection:', fabricCanvas.selection, 'Interactive:', fabricCanvas.interactive);
            console.log('Canvas dimensions:', fabricCanvas.width, 'x', fabricCanvas.height);
            console.log('Upper canvas dimensions:', upperCanvas?.width, 'x', upperCanvas?.height);
            
            // Vérifier que upper-canvas a bien une taille
            setTimeout(() => {
                const rect = upperCanvas?.getBoundingClientRect();
                console.log('Upper canvas rect:', rect);
            }, 100);
        });
    });
}
        
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
                // Activer l'édition
                editable: true,
                // Améliorer la sélection
                selectable: true,
                evented: true,
                hasControls: true,
                hasBorders: true,
                lockUniScaling: false,
                // Style de sélection
                borderColor: '#667eea',
                cornerColor: '#667eea',
                cornerSize: 12,
                cornerStyle: 'circle',
                transparentCorners: false,
                borderOpacityWhenMoving: 0.5,
                // Curseur
                hoverCursor: 'move',
                moveCursor: 'move'
            };
            
            // Ajouter l'ombre si activée
            if (textShadow.checked) {
                textOptions.shadow = {
                    color: 'rgba(0,0,0,0.9)',  // Très opaque pour être visible
                    blur: 20,                   // Flou important
                    offsetX: 10,                // Décalage visible
                    offsetY: 10                 // Décalage visible
                };
            }
            
            const text = new fabric.IText(textInput.value, textOptions);
            
            fabricCanvas.add(text);
            fabricCanvas.setActiveObject(text);
            fabricCanvas.renderAll();
            
            // Centrer l'objet si hors écran
            fabricCanvas.centerObject(text);
            fabricCanvas.renderAll();
            
            textInput.value = '';
        }
        
        // Nouvelles fonctions pour le style de texte
        function toggleTextStyle(style) {
            const activeObject = fabricCanvas.getActiveObject();
            if (!activeObject || activeObject.type !== 'i-text') {
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
        
        function setTextAlign(align) {
            const activeObject = fabricCanvas.getActiveObject();
            if (!activeObject || activeObject.type !== 'i-text') {
                alert('⚠️ Veuillez sélectionner un texte d\'abord');
                return;
            }
            
            // IMPORTANT : L'alignement ne fonctionne bien que pour le texte multi-ligne
            // Pour voir l'effet sur du texte court, on crée un Textbox
            if (activeObject.text && !activeObject.text.includes('\n')) {
                // Convertir IText en Textbox pour l'alignement
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
                
                // Copier l'ombre si elle existe
                if (activeObject.shadow) {
                    textbox.set('shadow', activeObject.shadow);
                }
                
                fabricCanvas.remove(activeObject);
                fabricCanvas.add(textbox);
                fabricCanvas.setActiveObject(textbox);
                fabricCanvas.renderAll();
                
                alert('💡 Texte converti en zone de texte pour l\'alignement.\nVous pouvez maintenant voir l\'effet de l\'alignement !');
            } else {
                // C'est déjà un texte multi-ligne
                activeObject.set('textAlign', align);
                activeObject.setCoords();
                fabricCanvas.renderAll();
            }
        }
        
        // Nouvelles fonctions pour modifier le texte sélectionné
        function updateSelectedTextFont() {
            const activeObject = fabricCanvas.getActiveObject();
            if (!activeObject || activeObject.type !== 'i-text') return;
            
            const font = document.getElementById('textFont').value;
            activeObject.set('fontFamily', font);
            fabricCanvas.renderAll();
        }
        
        function updateSelectedTextSize() {
            const activeObject = fabricCanvas.getActiveObject();
            if (!activeObject || activeObject.type !== 'i-text') return;
            
            const size = parseInt(document.getElementById('textSize').value);
            activeObject.set('fontSize', size);
            fabricCanvas.renderAll();
        }
        
        function updateSelectedTextColor() {
            const activeObject = fabricCanvas.getActiveObject();
            if (!activeObject || activeObject.type !== 'i-text') return;
            
            const color = document.getElementById('textColor').value;
            activeObject.set('fill', color);
            fabricCanvas.renderAll();
        }
        
        function updateSelectedTextStroke() {
            const activeObject = fabricCanvas.getActiveObject();
            if (!activeObject || activeObject.type !== 'i-text') return;
            
            const strokeColor = document.getElementById('textStrokeColor').value;
            const strokeWidth = parseInt(document.getElementById('textStrokeWidth').value);
            activeObject.set({
                stroke: strokeColor,
                strokeWidth: strokeWidth
            });
            fabricCanvas.renderAll();
        }
        
        function updateSelectedTextShadow() {
            const activeObject = fabricCanvas.getActiveObject();
            if (!activeObject || activeObject.type !== 'i-text') return;
            
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
        
        function updateSelectedTextShadow() {
            const activeObject = fabricCanvas.getActiveObject();
            if (!activeObject || activeObject.type !== 'i-text') return;
            
            const hasShadow = document.getElementById('textShadow').checked;
            
            if (hasShadow) {
                activeObject.set('shadow', {
                    color: 'rgba(0,0,0,0.8)',
                    blur: 15,
                    offsetX: 8,
                    offsetY: 8
                });
            } else {
                activeObject.set('shadow', null);
            }
            
            fabricCanvas.renderAll();
        }
        
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
            
            // Options communes pour une meilleure manipulation
            const commonOptions = {
                fill: fillColor,
                stroke: strokeColor,
                strokeWidth: strokeWidth,
                strokeDashArray: strokeDashArray,
                opacity: opacity,
                // Activer la sélection
                selectable: true,
                evented: true,
                hasControls: true,
                hasBorders: true,
                lockUniScaling: false,
                // Style de sélection
                borderColor: '#667eea',
                cornerColor: '#667eea',
                cornerSize: 12,
                cornerStyle: 'circle',
                transparentCorners: false,
                borderOpacityWhenMoving: 0.5,
                // Curseur
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
                    // Cœur en SVG path
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
                
                // Centrer l'objet si hors écran
                fabricCanvas.centerObject(shape);
                fabricCanvas.renderAll();
            }
        }
        
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
        
        function clearCanvas() {
            if (confirm('⚠️ Supprimer tous les éléments (l\'image de fond sera conservée) ?')) {
                const objects = fabricCanvas.getObjects();
                objects.forEach(obj => {
                    fabricCanvas.remove(obj);
                });
                fabricCanvas.renderAll();
            }
        }
        
        // Nouvelles fonctions de modification
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
                fabricCanvas.renderAll();
            });
        }
        
        function updateSelectedObjectOpacity() {
            const activeObject = fabricCanvas.getActiveObject();
            if (!activeObject) return;
            
            const opacity = parseInt(document.getElementById('objectOpacity').value) / 100;
            activeObject.set('opacity', opacity);
            fabricCanvas.renderAll();
        }
        
        function updateSelectedObjectRotation() {
            const activeObject = fabricCanvas.getActiveObject();
            if (!activeObject) return;
            
            const rotation = parseInt(document.getElementById('objectRotation').value);
            activeObject.set('angle', rotation);
            fabricCanvas.renderAll();
        }
        
        function bringToFront() {
            const activeObject = fabricCanvas.getActiveObject();
            if (!activeObject) {
                alert('⚠️ Aucun élément sélectionné');
                return;
            }
            fabricCanvas.bringToFront(activeObject);
            fabricCanvas.renderAll();
        }
        
        function sendToBack() {
            const activeObject = fabricCanvas.getActiveObject();
            if (!activeObject) {
                alert('⚠️ Aucun élément sélectionné');
                return;
            }
            fabricCanvas.sendToBack(activeObject);
            fabricCanvas.renderAll();
        }
        
        function flipObjectH() {
            const activeObject = fabricCanvas.getActiveObject();
            if (!activeObject) {
                alert('⚠️ Aucun élément sélectionné');
                return;
            }
            activeObject.set('flipX', !activeObject.flipX);
            fabricCanvas.renderAll();
        }
        
        function flipObjectV() {
            const activeObject = fabricCanvas.getActiveObject();
            if (!activeObject) {
                alert('⚠️ Aucun élément sélectionné');
                return;
            }
            activeObject.set('flipY', !activeObject.flipY);
            fabricCanvas.renderAll();
        }
        
        // CHANGEMENT DE MODE
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
            
            // IMPORTANT : Nettoyer complètement avant de recharger
            // Réinitialiser les filtres du mode simple
            if (currentMode !== 'simple') {
                filters = { brightness: 100, contrast: 100, saturation: 100, blur: 0 };
                currentRotation = 0;
                currentFlipH = false;
                updateSliders();
            }
            
            // Détruire cropper si on quitte le mode avancé
            if (cropper && mode !== 'advanced') {
                cropper.destroy();
                cropper = null;
            }
            
            // Nettoyer le canvas Fabric si on quitte le mode pro
            if (mode !== 'pro') {
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
        
        // SAUVEGARDE
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
                formData.append('image', blob, 'edited-image.jpg');
                formData.append('mode', mode);
                
                // Ajouter l'ID de l'image originale si on édite une image existante
                <?php if ($image): ?>
                formData.append('original_image_id', <?= $image['id'] ?>);
                <?php endif; ?>
                
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
    </script>
</body>
</html>