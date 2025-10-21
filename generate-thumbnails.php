<?php
require_once 'config.php';
require_once 'image-functions.php';

// SÉCURITÉ : Décommentez cette ligne après avoir utilisé le script
// die('Script désactivé. Supprimez ce fichier après utilisation.');

// Option 1 : Vérifier qu'on est connecté (recommandé)
session_start();
$isLoggedIn = isset($_SESSION['user_id']);

// Option 2 : Utiliser un mot de passe temporaire
$accessPassword = 'miniatures2024'; // Changez ce mot de passe
$hasAccess = false;

if (isset($_POST['password']) && $_POST['password'] === $accessPassword) {
    $_SESSION['thumbnail_access'] = true;
}

if (isset($_SESSION['thumbnail_access']) || $isLoggedIn) {
    $hasAccess = true;
}

// Si pas d'accès, afficher le formulaire de mot de passe
if (!$hasAccess) {
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Accès - Génération miniatures</title>
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
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .login-box {
                background: white;
                border-radius: 12px;
                padding: 40px;
                max-width: 400px;
                width: 100%;
                box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            }
            h1 {
                color: #667eea;
                margin-bottom: 20px;
                text-align: center;
            }
            p {
                color: #666;
                margin-bottom: 20px;
                text-align: center;
            }
            input {
                width: 100%;
                padding: 12px;
                border: 2px solid #ddd;
                border-radius: 6px;
                font-size: 15px;
                margin-bottom: 15px;
            }
            input:focus {
                outline: none;
                border-color: #667eea;
            }
            button {
                width: 100%;
                padding: 12px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                border: none;
                border-radius: 6px;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                transition: transform 0.2s;
            }
            button:hover {
                transform: translateY(-2px);
            }
            .error {
                background: #ffebee;
                color: #c62828;
                padding: 10px;
                border-radius: 6px;
                margin-bottom: 15px;
                text-align: center;
            }
            .hint {
                background: #fff3e0;
                color: #e65100;
                padding: 10px;
                border-radius: 6px;
                margin-top: 15px;
                font-size: 12px;
                text-align: center;
            }
        </style>
    </head>
    <body>
        <div class="login-box">
            <h1>🔒 Accès Sécurisé</h1>
            <p>Entrez le mot de passe pour générer les miniatures</p>
            
            <?php if (isset($_POST['password'])): ?>
                <div class="error">❌ Mot de passe incorrect</div>
            <?php endif; ?>
            
            <form method="POST">
                <input type="password" 
                       name="password" 
                       placeholder="Mot de passe" 
                       required 
                       autofocus>
                <button type="submit">🚀 Accéder</button>
            </form>
            
            <div class="hint">
                💡 Le mot de passe par défaut est : <strong>miniatures2024</strong><br>
                (Changez-le dans le code ligne 9)
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Le reste du code continue ici...

// Créer le dossier thumbnails s'il n'existe pas
$thumbDir = 'uploads/thumbnails';
if (!is_dir($thumbDir)) {
    mkdir($thumbDir, 0755, true);
}

// Récupérer toutes les images sans miniature
$stmt = $pdo->query("
    SELECT id, file_path, filename
    FROM images 
    WHERE (thumbnail_path IS NULL OR thumbnail_path = '')
    AND is_deleted = 0
");

$images = $stmt->fetchAll();

// Ne PAS modifier les chemins, ils sont déjà corrects avec user_X/

$generated = 0;
$errors = 0;
$skipped = 0;

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Génération de miniatures - Zenu</title>
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
            padding: 40px 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        
        h1 {
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 30px;
        }
        
        .progress-section {
            margin-bottom: 30px;
        }
        
        .progress-bar {
            width: 100%;
            height: 30px;
            background: #f0f0f0;
            border-radius: 15px;
            overflow: hidden;
            margin-bottom: 10px;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            width: 0%;
            transition: width 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }
        
        .log-section {
            background: #f5f5f5;
            border-radius: 8px;
            padding: 20px;
            max-height: 400px;
            overflow-y: auto;
            margin-bottom: 20px;
        }
        
        .log-item {
            padding: 8px;
            margin-bottom: 5px;
            border-radius: 4px;
            font-size: 14px;
            font-family: monospace;
        }
        
        .log-item.success {
            background: #e8f5e9;
            color: #2e7d32;
        }
        
        .log-item.error {
            background: #ffebee;
            color: #c62828;
        }
        
        .log-item.skip {
            background: #fff3e0;
            color: #e65100;
        }
        
        .summary {
            background: #e3f2fd;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .summary h3 {
            color: #1976d2;
            margin-bottom: 15px;
        }
        
        .stat {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #ddd;
        }
        
        .stat:last-child {
            border-bottom: none;
        }
        
        .stat-label {
            color: #666;
        }
        
        .stat-value {
            font-weight: 600;
            color: #333;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: transform 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .warning {
            background: #fff3e0;
            border-left: 4px solid #ff9800;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            color: #e65100;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🖼️ Génération des miniatures</h1>
        <p class="subtitle">Script de migration pour les images existantes</p>
        
        <?php if (count($images) === 0): ?>
            <div class="summary">
                <h3>✅ Tout est à jour !</h3>
                <p>Toutes vos images ont déjà des miniatures.</p>
            </div>
            <a href="dashboard-enhanced.php" class="btn">← Retour au dashboard</a>
        <?php else: ?>
            <div class="warning">
                <strong>⚠️ Important :</strong> Ce script va générer des miniatures pour <?= count($images) ?> image(s). 
                Cela peut prendre quelques minutes. Ne fermez pas cette page.
            </div>
            
            <div class="progress-section">
                <div class="progress-bar">
                    <div class="progress-fill" id="progressFill">0%</div>
                </div>
                <p id="progressText">En attente...</p>
            </div>
            
            <div class="log-section" id="logSection">
                <div class="log-item">Démarrage du traitement...</div>
            </div>
            
            <div class="summary" id="summary" style="display: none;">
                <h3>📊 Résumé</h3>
                <div class="stat">
                    <span class="stat-label">✅ Miniatures générées :</span>
                    <span class="stat-value" id="generatedCount">0</span>
                </div>
                <div class="stat">
                    <span class="stat-label">⏭️ Images ignorées :</span>
                    <span class="stat-value" id="skippedCount">0</span>
                </div>
                <div class="stat">
                    <span class="stat-label">❌ Erreurs :</span>
                    <span class="stat-value" id="errorCount">0</span>
                </div>
            </div>
            
            <a href="dashboard-enhanced.php" class="btn" id="doneBtn" style="display: none;">
                ✨ Voir le dashboard
            </a>
        <?php endif; ?>
    </div>
    
    <?php if (count($images) > 0): ?>
    <script>
        const images = <?= json_encode($images) ?>;
        const total = images.length;
        let processed = 0;
        let generated = 0;
        let skipped = 0;
        let errors = 0;
        
        const progressFill = document.getElementById('progressFill');
        const progressText = document.getElementById('progressText');
        const logSection = document.getElementById('logSection');
        const summary = document.getElementById('summary');
        const doneBtn = document.getElementById('doneBtn');
        
        function addLog(message, type = 'info') {
            const logItem = document.createElement('div');
            logItem.className = 'log-item ' + type;
            logItem.textContent = message;
            logSection.appendChild(logItem);
            logSection.scrollTop = logSection.scrollHeight;
        }
        
        function updateProgress() {
            const percentage = Math.round((processed / total) * 100);
            progressFill.style.width = percentage + '%';
            progressFill.textContent = percentage + '%';
            progressText.textContent = `${processed} / ${total} images traitées`;
        }
        
        function showSummary() {
            document.getElementById('generatedCount').textContent = generated;
            document.getElementById('skippedCount').textContent = skipped;
            document.getElementById('errorCount').textContent = errors;
            summary.style.display = 'block';
            doneBtn.style.display = 'inline-block';
        }
        
        async function processImage(image) {
            try {
                const response = await fetch('api/generate-thumbnail.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        image_id: image.id,
                        file_path: image.file_path
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    if (data.skipped) {
                        addLog(`⏭️ ${image.filename} - Fichier introuvable`, 'skip');
                        skipped++;
                    } else {
                        addLog(`✅ ${image.filename} - Miniature générée`, 'success');
                        generated++;
                    }
                } else {
                    addLog(`❌ ${image.filename} - ${data.error}`, 'error');
                    errors++;
                }
            } catch (error) {
                addLog(`❌ ${image.filename} - Erreur réseau`, 'error');
                errors++;
            }
            
            processed++;
            updateProgress();
            
            if (processed === total) {
                addLog('🎉 Traitement terminé !', 'success');
                showSummary();
            }
        }
        
        // Traiter les images une par une pour éviter la surcharge
        async function processAll() {
            addLog(`Traitement de ${total} image(s)...`);
            
            for (const image of images) {
                await processImage(image);
                // Petite pause pour éviter de surcharger le serveur
                await new Promise(resolve => setTimeout(resolve, 100));
            }
        }
        
        // Démarrer le traitement
        processAll();
    </script>
    <?php endif; ?>
</body>
</html>