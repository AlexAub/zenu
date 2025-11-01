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
            <div class="btn-group">
                <button class="btn btn-icon" onclick="rotate(-90)">↶ 90°</button>
                <button class="btn btn-icon" onclick="rotate(90)">↷ 90°</button>
                <button class="btn btn-icon" onclick="flipHorizontal()">↔️ Flip</button>
            </div>
        </div>
        
        <div class="control-group">
            <label class="control-label">🎨 Filtres rapides</label>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 10px;">
                <button class="btn btn-secondary" onclick="applyFilter('grayscale')">⚫ N&B</button>
                <button class="btn btn-secondary" onclick="applyFilter('sepia')">🟤 Sépia</button>
                <button class="btn btn-secondary" onclick="applyFilter('vintage')">📷 Vintage</button>
                <button class="btn btn-secondary" onclick="resetFilters()">🔄 Reset</button>
            </div>
        </div>
        
        <button class="btn btn-primary btn-block" onclick="saveImage('simple')">
            💾 Sauvegarder
        </button>
    </div>
    
    <!-- MODE AVANCÉ -->
    <div id="advancedControls" class="mode-content">
        <div class="control-group">
            <label class="control-label">📐 Ratios prédéfinis</label>
            <div class="aspect-ratio-buttons">
                <button class="aspect-btn" onclick="setCropRatio(NaN)">Libre</button>
                <button class="aspect-btn" onclick="setCropRatio(1)">1:1</button>
                <button class="aspect-btn" onclick="setCropRatio(4/3)">4:3</button>
                <button class="aspect-btn" onclick="setCropRatio(16/9)">16:9</button>
                <button class="aspect-btn" onclick="setCropRatio(3/2)">3:2</button>
                <button class="aspect-btn" onclick="setCropRatio(9/16)">9:16</button>
            </div>
        </div>
        
		<div class="control-group">
            <label class="control-label">🔄 Rotation libre</label>
            
            <!-- Slider de rotation -->
            <input type="range" class="control-slider" id="cropRotate" min="-180" max="180" value="0" step="1">
            
            <!-- Zone avec input numérique et valeur -->
            <div style="display: flex; gap: 10px; align-items: center; margin-top: 10px;">
                <!-- Input numérique -->
                <input 
                    type="number" 
                    id="cropRotateInput" 
                    min="-180" 
                    max="180" 
                    value="0" 
                    step="1"
                    style="flex: 1; padding: 8px 12px; border: 2px solid #e0e0e0; border-radius: 6px; font-size: 14px; text-align: center; font-weight: 600;"
                    placeholder="0"
                >
                
                <!-- Affichage de la valeur -->
                <span class="control-value" id="rotateValue" style="min-width: 45px; text-align: center; background: #667eea; color: white; padding: 8px 12px; border-radius: 6px; font-weight: 600;">0°</span>
            </div>
            
            <!-- Boutons de rotation rapide -->
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px; margin-top: 10px;">
                <button class="btn btn-secondary" onclick="rotateBy(-45)" style="padding: 6px; font-size: 12px;">↶ -45°</button>
                <button class="btn btn-secondary" onclick="rotateBy(0)" style="padding: 6px; font-size: 12px;">⟲ 0°</button>
                <button class="btn btn-secondary" onclick="rotateBy(45)" style="padding: 6px; font-size: 12px;">↷ +45°</button>
            </div>
        </div>
        
        <div class="control-group">
            <label class="control-label">🔍 Zoom</label>
            <div class="btn-group">
                <button class="btn btn-icon" onclick="cropZoom(-0.1)">➖</button>
                <button class="btn btn-icon" onclick="cropZoom(0)">100%</button>
                <button class="btn btn-icon" onclick="cropZoom(0.1)">➕</button>
            </div>
        </div>
        
        <button class="btn btn-primary btn-block" onclick="saveImage('advanced')">
            ✂️ Recadrer & Sauvegarder
        </button>
        
        <button class="btn btn-secondary btn-block" onclick="resetCrop()" style="margin-top: 10px;">
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
        <div class="tool-section">
            <label class="control-label">✏️ Texte</label>
            <input type="text" id="textInput" placeholder="Votre texte ici..." style="width: 100%; padding: 8px; border: 2px solid #ddd; border-radius: 6px; margin-bottom: 8px;">
            
            <!-- Police et taille -->
            <select id="textFont" onchange="updateSelectedTextFont()" style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 8px;">
                <option value="Arial">Arial</option>
                <option value="Verdana">Verdana</option>
                <option value="Times New Roman">Times New Roman</option>
                <option value="Georgia">Georgia</option>
                <option value="Courier New">Courier New</option>
                <option value="Comic Sans MS">Comic Sans MS</option>
                <option value="Impact">Impact</option>
            </select>
            
            <input type="number" id="textSize" value="40" min="10" max="200" onchange="updateSelectedTextSize()" placeholder="Taille" style="width: 100%; padding: 8px; margin-bottom: 8px;">
            
            <!-- Couleur -->
            <input type="color" id="textColor" value="#ffffff" onchange="updateSelectedTextColor()" style="width: 100%; height: 40px; margin-bottom: 8px;">
            
            <!-- Styles -->
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 5px; margin-bottom: 8px;">
                <button class="btn btn-secondary" onclick="toggleTextStyle('bold')" title="Gras">
                    <b>B</b>
                </button>
                <button class="btn btn-secondary" onclick="toggleTextStyle('italic')" title="Italique">
                    <i>I</i>
                </button>
                <button class="btn btn-secondary" onclick="toggleTextStyle('underline')" title="Souligné">
                    <u>U</u>
                </button>
                <button class="btn btn-secondary" onclick="toggleTextStyle('linethrough')" title="Barré">
                    <s>S</s>
                </button>
            </div>
            
            <!-- Alignement -->
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 5px; margin-bottom: 8px;">
                <button class="btn btn-secondary" onclick="setTextAlign('left')" title="Aligner à gauche">◀</button>
                <button class="btn btn-secondary" onclick="setTextAlign('center')" title="Centrer">▬</button>
                <button class="btn btn-secondary" onclick="setTextAlign('right')" title="Aligner à droite">▶</button>
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
            
            <button class="btn btn-primary btn-block" onclick="addText()">
                ➕ Ajouter le texte
            </button>
        </div>
        
        <!-- FORMES -->
        <div class="tool-section">
            <label class="control-label">🔷 Formes</label>
            <div class="shape-grid">
                <button class="shape-btn" onclick="addShape('rect')" title="Rectangle">⬜</button>
                <button class="shape-btn" onclick="addShape('circle')" title="Cercle">⚪</button>
                <button class="shape-btn" onclick="addShape('triangle')" title="Triangle">🔺</button>
                <button class="shape-btn" onclick="addShape('line')" title="Ligne">➖</button>
                <button class="shape-btn" onclick="addShape('arrow')" title="Flèche">➡️</button>
                <button class="shape-btn" onclick="addShape('star')" title="Étoile">⭐</button>
                <button class="shape-btn" onclick="addShape('polygon')" title="Hexagone">⬡</button>
                <button class="shape-btn" onclick="addShape('heart')" title="Cœur">❤️</button>
            </div>
            
            <!-- Couleurs forme -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 8px;">
                <div>
                    <label style="font-size: 12px; display: block; margin-bottom: 3px;">Remplissage</label>
                    <input type="color" id="shapeFillColor" value="#ff0000" style="width: 100%; height: 35px;">
                </div>
                <div>
                    <label style="font-size: 12px; display: block; margin-bottom: 3px;">Contour</label>
                    <input type="color" id="shapeStrokeColor" value="#000000" style="width: 100%; height: 35px;">
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
        <div class="tool-section" id="modifyControls" style="display: none;">
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
                <button class="btn btn-secondary" onclick="bringToFront()">⬆️ Avant</button>
                <button class="btn btn-secondary" onclick="sendToBack()">⬇️ Arrière</button>
            </div>
            
            <!-- Flip -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                <button class="btn btn-secondary" onclick="flipObjectH()">↔️ Flip H</button>
                <button class="btn btn-secondary" onclick="flipObjectV()">↕️ Flip V</button>
            </div>
        </div>
        
        <!-- ACTIONS -->
        <div class="tool-section">
            <label class="control-label">🎨 Actions</label>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                <button class="btn btn-secondary" onclick="duplicateSelected()">📋 Dupliquer</button>
                <button class="btn btn-secondary" onclick="deleteSelected()">🗑️ Supprimer</button>
            </div>
            <button class="btn btn-secondary btn-block" onclick="clearCanvas()" style="margin-top: 8px;">
                🧹 Tout effacer
            </button>
        </div>
        
        <button class="btn btn-primary btn-block" onclick="saveImage('pro')">
            💾 Sauvegarder
        </button>
    </div>
</div>
