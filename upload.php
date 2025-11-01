<?php
require_once 'config.php';
require_once 'security.php';
require_once 'image-functions.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$pageTitle = "Upload";

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    $file = $_FILES['image'];
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            $error = 'Type de fichier non autorisé';
        } elseif ($file['size'] > 2 * 1024 * 1024) {
            // ✅ CORRECTION 1 : Limite à 2 MB au lieu de 10 MB
            $error = 'Fichier trop volumineux (maximum 2 Mo)';
        } else {
            $userFolder = "user_" . $userId;
            $uploadDir = "uploads/" . $userFolder;
            $thumbDir = "uploads/thumbnails/" . $userFolder;
            
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            if (!is_dir($thumbDir)) {
                mkdir($thumbDir, 0755, true);
            }
            
            $originalFilename = pathinfo($file['name'], PATHINFO_FILENAME);
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            // Nettoyer le nom original
            $cleanName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $originalFilename);
            $cleanName = preg_replace('/_+/', '_', $cleanName);
            $cleanName = trim($cleanName, '_');
            
            // ✅ CORRECTION 2 : Vérifier les doublons dans original_filename
            $finalCleanName = $cleanName;
            $counter = 1;
            $maxAttempts = 100;
            
            while ($counter <= $maxAttempts) {
                // Vérifier si ce nom existe déjà
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) as count
                    FROM images 
                    WHERE user_id = ? 
                    AND original_filename = ?
                    AND is_deleted = 0
                ");
                $stmt->execute([$userId, $finalCleanName]);
                $result = $stmt->fetch();
                
                if ($result['count'] == 0) {
                    // Nom disponible !
                    break;
                }
                
                // Nom occupé, ajouter un suffixe
                $counter++;
                $finalCleanName = $cleanName . '_' . $counter;
            }
            
            if ($counter > $maxAttempts) {
                $error = 'Trop de fichiers avec des noms similaires';
            } else {
                // Générer un nom de fichier physique unique
                $filename = uniqid() . '.' . $extension;
                $filepath = $uploadDir . '/' . $filename;
                
                if (move_uploaded_file($file['tmp_name'], $filepath)) {
                    $metadata = getImageMetadata($filepath);
                    
                    // Créer la miniature
                    $thumbPath = null;
                    $thumbFilename = $filename;
                    $thumbFullPath = $thumbDir . '/' . $thumbFilename;
                    
                    // ✅ Utiliser generateThumbnail() (nom correct de la fonction)
                    if (generateThumbnail($filepath, $thumbFullPath, 300, 300)) {
                        $thumbPath = $thumbDir . '/' . $thumbFilename;
                    }
                    
                    // Insérer en base de données avec le nom unique
                    $stmt = $pdo->prepare("
                        INSERT INTO images 
                        (user_id, filename, original_filename, file_path, thumbnail_path, width, height, file_size, mime_type) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    
                    $stmt->execute([
                        $userId,
                        $filename,
                        $finalCleanName,  // ✅ Utilise le nom unique vérifié
                        $filepath,
                        $thumbPath,
                        $metadata['width'],
                        $metadata['height'],
                        $metadata['size'],
                        $metadata['mime']
                    ]);
                    
                    $success = 'Image uploadée avec succès !';
                    if ($counter > 1) {
                        $success = "Image uploadée sous le nom '$finalCleanName' (suffixe ajouté car nom déjà utilisé)";
                    }
                    
                    if (function_exists('logSecurityAction')) {
                        logSecurityAction($userId, 'image_uploaded', "File: $originalFilename, Size: " . formatFileSize($metadata['size']));
                    }
                    
                    // ✅ CORRECTION 3 : Redirection immédiate au lieu de 2 secondes
                    header("Location: dashboard.php");
                    exit;
                } else {
                    $error = 'Erreur lors de l\'enregistrement du fichier';
                }
            }
        }
    } else {
        // Gestion des erreurs d'upload
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => 'Fichier trop volumineux (limite serveur)',
            UPLOAD_ERR_FORM_SIZE => 'Fichier trop volumineux',
            UPLOAD_ERR_PARTIAL => 'Fichier partiellement uploadé',
            UPLOAD_ERR_NO_FILE => 'Aucun fichier envoyé',
            UPLOAD_ERR_NO_TMP_DIR => 'Dossier temporaire manquant',
            UPLOAD_ERR_CANT_WRITE => 'Échec écriture disque',
            UPLOAD_ERR_EXTENSION => 'Extension PHP a arrêté l\'upload'
        ];
        $error = $errorMessages[$file['error']] ?? 'Erreur lors de l\'upload';
    }
}

if (!function_exists('formatFileSize')) {
    function formatFileSize($bytes) {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' Go';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' Mo';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' Ko';
        } else {
            return $bytes . ' octets';
        }
    }
}

// Inclure le header
require_once 'header.php';
?>
    <title>Upload - Zenu</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            margin: 0;
            min-height: 100vh;
        }
        
        .upload-container {
            max-width: 800px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .upload-box {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
        }
        
        .upload-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .upload-header h2 {
            font-size: 28px;
            color: #333;
            margin-bottom: 10px;
        }
        
        .upload-header p {
            color: #666;
            font-size: 14px;
        }
        
        .drop-zone {
            border: 3px dashed #e0e0e0;
            border-radius: 12px;
            padding: 60px 20px;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
            position: relative;
            background: #fafafa;
        }
        
        .drop-zone:hover, .drop-zone.drag-over {
            border-color: #667eea;
            background: #f8f9ff;
        }
        
        .drop-zone-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        
        .drop-zone-text {
            font-size: 18px;
            color: #333;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .drop-zone-hint {
            font-size: 14px;
            color: #999;
            margin-bottom: 5px;
        }
        
        .size-limit {
            font-size: 12px;
            color: #f44336;
            font-weight: 600;
            margin-top: 10px;
        }
        
        input[type="file"] {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            opacity: 0;
            cursor: pointer;
        }
        
        .upload-btn {
            margin-top: 20px;
            padding: 15px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            width: 100%;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .upload-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        
        .upload-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .preview-area {
            margin-top: 20px;
            display: none;
            text-align: center;
        }
        
        .preview-image {
            max-width: 100%;
            max-height: 400px;
            border-radius: 8px;
            margin: 0 auto 10px auto;
            display: block;
            object-fit: contain;
        }
        
        .preview-info {
            font-size: 13px;
            color: #666;
            text-align: center;
        }
        
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="upload-container">
        <div class="upload-box">
            <div class="upload-header">
                <h2>📤 Upload une image</h2>
                <p>Glissez-déposez votre image ou cliquez pour la sélectionner</p>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data" id="uploadForm">
                <div class="drop-zone" id="dropZone">
                    <div class="drop-zone-icon">🖼️</div>
                    <div class="drop-zone-text">Glissez votre image ici</div>
                    <div class="drop-zone-hint">ou cliquez pour parcourir</div>
                    <div class="size-limit">⚠️ Limite : 2 Mo maximum</div>
                    <input type="file" name="image" id="imageInput" accept="image/*" required>
                </div>
                
                <div class="preview-area" id="previewArea">
                    <img id="previewImage" class="preview-image" alt="Aperçu">
                    <div class="preview-info" id="previewInfo"></div>
                </div>
                
                <button type="submit" class="upload-btn" id="uploadBtn">
                    📤 Uploader l'image
                </button>
            </form>
            
            <a href="dashboard.php" class="back-link">← Retour au dashboard</a>
        </div>
    </div>
    
    <script>
        const dropZone = document.getElementById('dropZone');
        const imageInput = document.getElementById('imageInput');
        const uploadForm = document.getElementById('uploadForm');
        const uploadBtn = document.getElementById('uploadBtn');
        const previewArea = document.getElementById('previewArea');
        const previewImage = document.getElementById('previewImage');
        const previewInfo = document.getElementById('previewInfo');
        
        // Limite de taille : 2 MB
        const MAX_SIZE = 2 * 1024 * 1024;
        
        // Drag & Drop
        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('drag-over');
        });
        
        dropZone.addEventListener('dragleave', () => {
            dropZone.classList.remove('drag-over');
        });
        
        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('drag-over');
            
            if (e.dataTransfer.files.length > 0) {
                imageInput.files = e.dataTransfer.files;
                handleFileSelect();
            }
        });
        
        // Sélection de fichier
        imageInput.addEventListener('change', handleFileSelect);
        
        function handleFileSelect() {
            const file = imageInput.files[0];
            
            if (!file) return;
            
            // Vérifier le type
            if (!file.type.startsWith('image/')) {
                alert('❌ Veuillez sélectionner une image valide');
                imageInput.value = '';
                return;
            }
            
            // Vérifier la taille
            if (file.size > MAX_SIZE) {
                alert(`❌ Fichier trop volumineux : ${(file.size / 1024 / 1024).toFixed(2)} Mo\n\nMaximum autorisé : 2 Mo`);
                imageInput.value = '';
                return;
            }
            
            // Afficher l'aperçu
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImage.src = e.target.result;
                previewInfo.textContent = `${file.name} - ${(file.size / 1024).toFixed(0)} Ko`;
                previewArea.style.display = 'block';
            };
            reader.readAsDataURL(file);
        }
        
        // Désactiver le bouton pendant l'upload
        uploadForm.addEventListener('submit', function() {
            uploadBtn.disabled = true;
            uploadBtn.textContent = '⏳ Upload en cours...';
        });
    </script>
</body>
</html>