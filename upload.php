<?php
require_once 'config.php';
require_once 'security.php';
require_once 'image-functions.php';

// Vérifier la connexion
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$error = '';
$success = '';

// Créer le dossier thumbnails s'il n'existe pas
$thumbDir = 'uploads/thumbnails';
if (!is_dir($thumbDir)) {
    mkdir($thumbDir, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    $file = $_FILES['image'];
    
    // Vérifications de base
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'Erreur lors de l\'upload';
    } else {
        // Vérifier le type MIME
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            $error = 'Type de fichier non autorisé. Utilisez JPG, PNG, GIF ou WebP.';
        } else {
            // Vérifier la taille (10 Mo max)
            if ($file['size'] > 10 * 1024 * 1024) {
                $error = 'Fichier trop volumineux (maximum 10 Mo)';
            } else {
                // Déterminer le dossier de l'utilisateur
                $userFolder = 'user_' . $userId;
                $uploadDir = 'uploads/' . $userFolder;
                
                // Créer le dossier utilisateur si nécessaire
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                // Créer aussi le dossier thumbnails pour cet utilisateur
                $thumbDir = 'uploads/thumbnails/' . $userFolder;
                if (!is_dir($thumbDir)) {
                    mkdir($thumbDir, 0755, true);
                }
                
                // Générer un nom de fichier unique et sécurisé
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $originalFilename = $file['name'];
                $filename = sanitizeFilename($file['name']);
                $uniqueName = uniqid() . '_' . $filename;
                $uploadPath = $uploadDir . '/' . $uniqueName;
                $thumbPath = $thumbDir . '/' . $uniqueName;
                
                // Déplacer le fichier
                if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                    // Obtenir les métadonnées
                    $metadata = getImageMetadata($uploadPath);
                    
                    // Générer la miniature
                    $thumbGenerated = generateThumbnail($uploadPath, $thumbPath, 300, 300);
                    
                    // Insérer dans la base de données avec les bons noms de colonnes
                    $stmt = $pdo->prepare("
                        INSERT INTO images (user_id, filename, original_filename, file_path, thumbnail_path, width, height, file_size, mime_type)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    
                    $stmt->execute([
                        $userId,
                        $uniqueName,
                        $originalFilename,
                        $uploadPath,
                        $thumbGenerated ? $thumbPath : null,
                        $metadata['width'],
                        $metadata['height'],
                        $metadata['size'],
                        $metadata['mime']
                    ]);
                    
                    $success = 'Image uploadée avec succès !';
                    
                    // Logger seulement si la fonction existe
                    if (function_exists('logSecurityAction')) {
                        logSecurityAction($userId, 'image_uploaded', "File: $originalFilename, Size: " . formatFileSize($metadata['size']));
                    }
                    
                    // Rediriger vers le dashboard après 2 secondes
                    header("Refresh: 2; url=dashboard.php");
                } else {
                    $error = 'Erreur lors de l\'enregistrement du fichier';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload - Zenu</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .upload-container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 600px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .header h1 {
            font-size: 32px;
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .header p {
            color: #666;
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
            background: #f0f4ff;
        }
        
        .drop-zone-icon {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .drop-zone-text {
            font-size: 18px;
            color: #666;
            margin-bottom: 10px;
        }
        
        .drop-zone-hint {
            font-size: 14px;
            color: #999;
        }
        
        #fileInput {
            display: none;
        }
        
        .preview-container {
            margin-top: 30px;
            display: none;
        }
        
        .preview-container.show {
            display: block;
        }
        
        .preview-image {
            width: 100%;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .file-info {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .file-info-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .file-info-label {
            color: #666;
        }
        
        .file-info-value {
            font-weight: 600;
            color: #333;
        }
        
        .btn {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            margin-bottom: 10px;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        
        .btn-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        
        .btn-secondary {
            background: #f0f0f0;
            color: #333;
        }
        
        .btn-secondary:hover {
            background: #e0e0e0;
        }
        
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .message.success {
            background: #e8f5e9;
            color: #2e7d32;
        }
        
        .message.error {
            background: #ffebee;
            color: #c62828;
        }
        
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }
        
        .progress-bar {
            width: 100%;
            height: 6px;
            background: #e0e0e0;
            border-radius: 3px;
            overflow: hidden;
            margin-top: 15px;
            display: none;
        }
        
        .progress-bar.show {
            display: block;
        }
        
        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            width: 0%;
            transition: width 0.3s;
        }
    </style>
</head>
<body>
    <div class="upload-container">
        <div class="header">
            <h1>📤 Upload Image</h1>
            <p>Glissez-déposez ou cliquez pour sélectionner</p>
        </div>
        
        <?php if ($error): ?>
            <div class="message error">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="message success">✅ <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data" id="uploadForm">
            <div class="drop-zone" id="dropZone">
                <div class="drop-zone-icon">📷</div>
                <div class="drop-zone-text">Cliquez ou glissez une image ici</div>
                <div class="drop-zone-hint">JPG, PNG, GIF ou WebP - Maximum 10 Mo</div>
                <input type="file" 
                       name="image" 
                       id="fileInput" 
                       accept="image/jpeg,image/png,image/gif,image/webp"
                       required>
            </div>
            
            <div class="preview-container" id="previewContainer">
                <img src="" alt="Preview" class="preview-image" id="previewImage">
                
                <div class="file-info" id="fileInfo">
                    <div class="file-info-item">
                        <span class="file-info-label">Nom du fichier:</span>
                        <span class="file-info-value" id="fileName">-</span>
                    </div>
                    <div class="file-info-item">
                        <span class="file-info-label">Taille:</span>
                        <span class="file-info-value" id="fileSize">-</span>
                    </div>
                    <div class="file-info-item">
                        <span class="file-info-label">Dimensions:</span>
                        <span class="file-info-value" id="fileDimensions">-</span>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary" id="uploadBtn">
                    ✨ Uploader l'image
                </button>
                
                <button type="button" class="btn btn-secondary" onclick="resetForm()">
                    🔄 Choisir une autre image
                </button>
                
                <div class="progress-bar" id="progressBar">
                    <div class="progress-bar-fill" id="progressBarFill"></div>
                </div>
            </div>
        </form>
        
        <div class="back-link">
            <a href="dashboard.php">← Retour au dashboard</a>
        </div>
    </div>
    
    <script>
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInput');
        const previewContainer = document.getElementById('previewContainer');
        const previewImage = document.getElementById('previewImage');
        const uploadForm = document.getElementById('uploadForm');
        const uploadBtn = document.getElementById('uploadBtn');
        const progressBar = document.getElementById('progressBar');
        const progressBarFill = document.getElementById('progressBarFill');
        
        // Click sur la drop zone
        dropZone.addEventListener('click', () => {
            fileInput.click();
        });
        
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
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                handleFileSelect(files[0]);
            }
        });
        
        // Sélection de fichier
        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                handleFileSelect(e.target.files[0]);
            }
        });
        
        // Gérer la sélection de fichier
        function handleFileSelect(file) {
            // Vérifier le type
            if (!file.type.match('image.*')) {
                alert('Veuillez sélectionner une image');
                return;
            }
            
            // Vérifier la taille (10 Mo)
            if (file.size > 10 * 1024 * 1024) {
                alert('Fichier trop volumineux (maximum 10 Mo)');
                return;
            }
            
            // Afficher les infos
            document.getElementById('fileName').textContent = file.name;
            document.getElementById('fileSize').textContent = formatFileSize(file.size);
            
            // Prévisualiser l'image
            const reader = new FileReader();
            reader.onload = (e) => {
                previewImage.src = e.target.result;
                
                // Obtenir les dimensions
                const img = new Image();
                img.onload = function() {
                    document.getElementById('fileDimensions').textContent = 
                        this.width + ' × ' + this.height + ' px';
                };
                img.src = e.target.result;
                
                previewContainer.classList.add('show');
                dropZone.style.display = 'none';
            };
            reader.readAsDataURL(file);
        }
        
        // Réinitialiser le formulaire
        function resetForm() {
            fileInput.value = '';
            previewContainer.classList.remove('show');
            dropZone.style.display = 'block';
            progressBar.classList.remove('show');
            progressBarFill.style.width = '0%';
        }
        
        // Formater la taille de fichier
        function formatFileSize(bytes) {
            if (bytes >= 1073741824) {
                return (bytes / 1073741824).toFixed(2) + ' Go';
            } else if (bytes >= 1048576) {
                return (bytes / 1048576).toFixed(2) + ' Mo';
            } else if (bytes >= 1024) {
                return (bytes / 1024).toFixed(2) + ' Ko';
            } else {
                return bytes + ' octets';
            }
        }
        
        // Gérer la soumission du formulaire
        uploadForm.addEventListener('submit', (e) => {
            uploadBtn.disabled = true;
            uploadBtn.textContent = '⏳ Upload en cours...';
            progressBar.classList.add('show');
            
            // Simuler une progression (en réalité, vous pourriez utiliser XMLHttpRequest pour une vraie progression)
            let progress = 0;
            const interval = setInterval(() => {
                progress += 10;
                progressBarFill.style.width = progress + '%';
                
                if (progress >= 90) {
                    clearInterval(interval);
                }
            }, 200);
        });
    </script>
</body>
</html>