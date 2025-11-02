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
    
    // ✅ FIX 1: Meilleure gestion des erreurs d'upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => 'Fichier trop volumineux (limite serveur)',
            UPLOAD_ERR_FORM_SIZE => 'Fichier trop volumineux (2 Mo max)',
            UPLOAD_ERR_PARTIAL => 'Le fichier n\'a été que partiellement téléchargé. Veuillez réessayer.',
            UPLOAD_ERR_NO_FILE => 'Aucun fichier sélectionné',
            UPLOAD_ERR_NO_TMP_DIR => 'Erreur serveur : dossier temporaire manquant',
            UPLOAD_ERR_CANT_WRITE => 'Erreur serveur : impossible d\'écrire le fichier',
            UPLOAD_ERR_EXTENSION => 'Erreur serveur : extension bloquée'
        ];
        $error = $errorMessages[$file['error']] ?? 'Erreur lors de l\'upload';
    } else {
        // ✅ FIX 2: Vérifier que le fichier temporaire existe encore
        if (!file_exists($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            $error = 'Le fichier n\'est plus disponible. Veuillez réessayer l\'upload.';
        } else {
            // Vérifier le type MIME
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mimeType, $allowedTypes)) {
                $error = 'Type de fichier non autorisé';
            } elseif ($file['size'] > 2 * 1024 * 1024) {
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
                
                // Vérifier les doublons
                $finalCleanName = $cleanName;
                $counter = 1;
                $maxAttempts = 100;
                
                while ($counter <= $maxAttempts) {
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
                        break;
                    }
                    
                    $counter++;
                    $finalCleanName = $cleanName . '_' . $counter;
                }
                
                if ($counter > $maxAttempts) {
                    $error = 'Trop de fichiers avec des noms similaires';
                } else {
                    // Générer un nom de fichier physique unique
                    $filename = uniqid() . '.' . $extension;
                    $filepath = $uploadDir . '/' . $filename;
                    
                    // ✅ FIX 3: Vérifier à nouveau que le fichier temporaire existe juste avant move_uploaded_file
                    if (!file_exists($file['tmp_name'])) {
                        $error = 'Le fichier a été perdu. Veuillez réessayer immédiatement après la sélection.';
                    } elseif (move_uploaded_file($file['tmp_name'], $filepath)) {
                        $metadata = getImageMetadata($filepath);
                        
                        // Créer la miniature
                        $thumbPath = null;
                        $thumbFilename = $filename;
                        $thumbFullPath = $thumbDir . '/' . $thumbFilename;
                        
                        if (generateThumbnail($filepath, $thumbFullPath, 300, 300)) {
                            $thumbPath = $thumbDir . '/' . $thumbFilename;
                        }
                        
                        // Insérer en base de données
                        $stmt = $pdo->prepare("
                            INSERT INTO images 
                            (user_id, filename, original_filename, file_path, thumbnail_path, width, height, file_size, mime_type) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        
                        $stmt->execute([
                            $userId,
                            $filename,
                            $finalCleanName,
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
                        
                        // ✅ FIX 4: Redirection immédiate
                        header("Location: dashboard.php?upload=success");
                        exit;
                    } else {
                        $error = 'Erreur lors de l\'enregistrement du fichier. Vérifiez les permissions.';
                    }
                }
            }
        }
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
        
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 14px;
            line-height: 1.6;
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
        
        /* ✅ AMÉLIORATION: Instructions visibles sur mobile */
        .mobile-tips {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 20px;
            font-size: 13px;
            line-height: 1.6;
        }
        
        .mobile-tips h4 {
            margin: 0 0 10px 0;
            color: #856404;
            font-size: 14px;
        }
        
        .mobile-tips ul {
            margin: 0;
            padding-left: 20px;
        }
        
        .mobile-tips li {
            margin-bottom: 5px;
            color: #856404;
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
        
        .preview-area {
            display: none;
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 12px;
        }
        
        .preview-image {
            max-width: 100%;
            max-height: 400px;
            border-radius: 8px;
            display: block;
            margin: 0 auto 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .preview-info {
            text-align: center;
            color: #666;
            font-size: 14px;
        }
        
        .upload-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 20px;
        }
        
        .upload-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }
        
        .upload-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .back-link:hover {
            color: #764ba2;
            transform: translateX(-5px);
        }
        
        /* ✅ RESPONSIVE MOBILE */
        @media (max-width: 768px) {
            .upload-container {
                padding: 0 15px;
                margin: 20px auto;
            }
            
            .upload-box {
                padding: 25px 20px;
                border-radius: 15px;
            }
            
            .upload-header h2 {
                font-size: 22px;
            }
            
            .upload-header p {
                font-size: 13px;
            }
            
            .drop-zone {
                padding: 40px 15px;
            }
            
            .drop-zone-icon {
                font-size: 48px;
            }
            
            .drop-zone-text {
                font-size: 16px;
            }
            
            .mobile-tips {
                display: block !important;
            }
        }
        
        /* Masquer les tips sur desktop */
        @media (min-width: 769px) {
            .mobile-tips {
                display: none;
            }
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
            
            <!-- ✅ NOUVEAU: Conseils pour mobile -->
            <div class="mobile-tips">
                <h4>📱 Conseils pour upload mobile :</h4>
                <ul>
                    <li>Uploadez <strong>immédiatement</strong> après avoir sélectionné le fichier</li>
                    <li>Ne changez pas d'application pendant l'upload</li>
                    <li>Restez sur cette page jusqu'à la fin</li>
                    <li>Si erreur, réessayez en sélectionnant à nouveau le fichier</li>
                </ul>
            </div>
            
            <form method="POST" enctype="multipart/form-data" id="uploadForm">
                <div class="drop-zone" id="dropZone">
                    <div class="drop-zone-icon">🖼️</div>
                    <div class="drop-zone-text">Glissez votre image ici</div>
                    <div class="drop-zone-hint">ou cliquez pour parcourir</div>
                    <div class="size-limit">⚠️ Limite : 2 Mo maximum</div>
                    <!-- Input file standard - pas de capture pour permettre le choix galerie/caméra -->
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
        
        const MAX_SIZE = 2 * 1024 * 1024;
        
        // ✅ FIX 6: Variable pour tracker si le fichier a été traité
        let fileProcessed = false;
        let selectedFile = null;
        
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
                // ✅ FIX 7: Créer un nouveau FileList
                const dt = new DataTransfer();
                dt.items.add(e.dataTransfer.files[0]);
                imageInput.files = dt.files;
                handleFileSelect();
            }
        });
        
        // Sélection de fichier
        imageInput.addEventListener('change', handleFileSelect);
        
        function handleFileSelect() {
            const file = imageInput.files[0];
            
            if (!file) return;
            
            // ✅ FIX 8: Stocker le fichier immédiatement
            selectedFile = file;
            fileProcessed = false;
            
            // Vérifier le type
            if (!file.type.startsWith('image/')) {
                alert('❌ Veuillez sélectionner une image valide');
                imageInput.value = '';
                selectedFile = null;
                return;
            }
            
            // Vérifier la taille
            if (file.size > MAX_SIZE) {
                alert(`❌ Fichier trop volumineux : ${(file.size / 1024 / 1024).toFixed(2)} Mo\n\nMaximum autorisé : 2 Mo`);
                imageInput.value = '';
                selectedFile = null;
                return;
            }
            
            // Afficher l'aperçu
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImage.src = e.target.result;
                previewInfo.textContent = `${file.name} - ${(file.size / 1024).toFixed(0)} Ko`;
                previewArea.style.display = 'block';
                
                // ✅ FIX 9: Auto-scroll vers le bouton d'upload sur mobile
                if (window.innerWidth <= 768) {
                    setTimeout(() => {
                        uploadBtn.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }, 100);
                }
            };
            reader.readAsDataURL(file);
            
            // ✅ FIX 10: Encourager l'upload immédiat sur mobile
            if (window.innerWidth <= 768 && !fileProcessed) {
                uploadBtn.textContent = '⚡ Cliquez maintenant pour uploader !';
                uploadBtn.style.background = 'linear-gradient(135deg, #4caf50 0%, #45a049 100%)';
                
                setTimeout(() => {
                    uploadBtn.textContent = '📤 Uploader l\'image';
                    uploadBtn.style.background = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
                }, 3000);
            }
        }
        
        // ✅ FIX 11: Validation avant envoi
        uploadForm.addEventListener('submit', function(e) {
            if (fileProcessed) {
                e.preventDefault();
                alert('⚠️ Upload déjà en cours. Veuillez patienter.');
                return false;
            }
            
            if (!selectedFile || !imageInput.files[0]) {
                e.preventDefault();
                alert('❌ Veuillez sélectionner un fichier d\'abord');
                return false;
            }
            
            fileProcessed = true;
            uploadBtn.disabled = true;
            uploadBtn.textContent = '⏳ Upload en cours...';
        });
        
        // ✅ FIX 13: Réactiver le formulaire si l'utilisateur revient sur la page
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                // Page rechargée depuis le cache (bouton retour)
                uploadBtn.disabled = false;
                uploadBtn.textContent = '📤 Uploader l\'image';
                fileProcessed = false;
            }
        });
    </script>
</body>
</html>