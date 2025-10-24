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
        } elseif ($file['size'] > 10 * 1024 * 1024) {
            $error = 'Fichier trop volumineux (maximum 10 Mo)';
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
            $filename = uniqid() . '.' . $extension;
            $filepath = $uploadDir . '/' . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                $metadata = getImageMetadata($filepath);
                $cleanName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $originalFilename);
                $cleanName = preg_replace('/_+/', '_', $cleanName);
                $cleanName = trim($cleanName, '_');
                
                $thumbPath = null;
                if (function_exists('createThumbnail')) {
                    $thumbFilename = $filename;
                    $thumbFullPath = $thumbDir . '/' . $thumbFilename;
                    if (createThumbnail($filepath, $thumbFullPath, 300, 300)) {
                        $thumbPath = $thumbDir . '/' . $thumbFilename;
                    }
                }
                
                $stmt = $pdo->prepare("
                    INSERT INTO images 
                    (user_id, filename, original_filename, file_path, thumbnail_path, width, height, file_size, mime_type) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $userId,
                    $filename,
                    $cleanName,
                    $filepath,
                    $thumbPath,
                    $metadata['width'],
                    $metadata['height'],
                    $metadata['size'],
                    $metadata['mime']
                ]);
                
                $success = 'Image uploadée avec succès !';
                
                if (function_exists('logSecurityAction')) {
                    logSecurityAction($userId, 'image_uploaded', "File: $originalFilename, Size: " . formatFileSize($metadata['size']));
                }
                
                header("Refresh: 2; url=dashboard.php");
            } else {
                $error = 'Erreur lors de l\'enregistrement du fichier';
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
        
        .preview-container {
            display: none;
            margin-top: 30px;
        }
        
        .preview-container.show {
            display: block;
        }
        
        .preview-image {
            width: 100%;
            max-height: 400px;
            object-fit: contain;
            border-radius: 12px;
            margin-bottom: 20px;
            background: #f5f5f5;
        }
        
        .file-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        
        .file-info-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .file-info-item:last-child {
            border-bottom: none;
        }
        
        .file-info-label {
            color: #666;
            font-weight: 600;
        }
        
        .file-info-value {
            color: #333;
        }
        
        .btn {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: 10px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: #f5f5f5;
            color: #666;
        }
        
        .btn-secondary:hover {
            background: #e0e0e0;
        }
        
        .progress-bar {
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 20px;
            display: none;
        }
        
        .progress-bar.show {
            display: block;
        }
        
        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            width: 0%;
            transition: width 0.3s ease;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 500;
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
        
        @media (max-width: 768px) {
            .upload-box {
                padding: 30px 20px;
            }
            
            .drop-zone {
                padding: 40px 20px;
            }
            
            .drop-zone-icon {
                font-size: 48px;
            }
        }
    </style>
</head>
<body>
    
    <div class="upload-container">
        <div class="upload-box">
            <div class="upload-header">
                <h2>📤 Upload d'image</h2>
                <p>Uploadez vos images en toute simplicité</p>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    ✅ <?= htmlspecialchars($success) ?> Redirection vers le dashboard...
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    ❌ <?= htmlspecialchars($error) ?>
                </div>
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
                    
                    <div class="progress-bar" id="progressBar">
                        <div class="progress-bar-fill" id="progressBarFill"></div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" id="uploadBtn">
                        ✨ Uploader l'image
                    </button>
                    
                    <button type="button" class="btn btn-secondary" onclick="resetForm()">
                        🔄 Choisir une autre image
                    </button>
                </div>
            </form>
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
        
        // Drag and drop
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
        
        // Changement de fichier
        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                handleFileSelect(e.target.files[0]);
            }
        });
        
        // Gérer la sélection de fichier
        function handleFileSelect(file) {
            if (!file.type.startsWith('image/')) {
                alert('Veuillez sélectionner une image');
                return;
            }
            
            if (file.size > 10 * 1024 * 1024) {
                alert('Fichier trop volumineux (maximum 10 Mo)');
                return;
            }
            
            document.getElementById('fileName').textContent = file.name;
            document.getElementById('fileSize').textContent = formatFileSize(file.size);
            
            const reader = new FileReader();
            reader.onload = (e) => {
                previewImage.src = e.target.result;
                
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
        
        function resetForm() {
            fileInput.value = '';
            previewContainer.classList.remove('show');
            dropZone.style.display = 'block';
            progressBar.classList.remove('show');
            progressBarFill.style.width = '0%';
        }
        
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
        
        uploadForm.addEventListener('submit', (e) => {
            uploadBtn.disabled = true;
            uploadBtn.textContent = '⏳ Upload en cours...';
            progressBar.classList.add('show');
            
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