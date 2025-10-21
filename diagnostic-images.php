<?php
require_once 'config.php';

// Mot de passe simple pour accès
$password = 'diagnostic2024';
session_start();

if (isset($_POST['password']) && $_POST['password'] === $password) {
    $_SESSION['diag_access'] = true;
}

if (!isset($_SESSION['diag_access']) && !isset($_SESSION['user_id'])) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Diagnostic</title>
        <style>
            body { font-family: Arial; display: flex; align-items: center; justify-content: center; min-height: 100vh; background: #667eea; }
            .box { background: white; padding: 30px; border-radius: 10px; }
            input, button { width: 100%; padding: 10px; margin: 5px 0; }
            button { background: #667eea; color: white; border: none; cursor: pointer; }
        </style>
    </head>
    <body>
        <div class="box">
            <h2>Diagnostic Images</h2>
            <form method="POST">
                <input type="password" name="password" placeholder="Mot de passe: diagnostic2024" required>
                <button type="submit">Accéder</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Récupérer quelques images pour diagnostic
$stmt = $pdo->query("SELECT id, filename, file_path, thumbnail_path FROM images LIMIT 5");
$images = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnostic Images - Zenu</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Courier New', monospace;
            background: #1a1a1a;
            color: #0f0;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: #000;
            border: 2px solid #0f0;
            border-radius: 8px;
            padding: 20px;
        }
        h1 {
            color: #0f0;
            margin-bottom: 20px;
            text-align: center;
            text-shadow: 0 0 10px #0f0;
        }
        .section {
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #0f0;
            border-radius: 4px;
        }
        .section h2 {
            color: #0ff;
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            padding: 8px;
            text-align: left;
            border: 1px solid #0f0;
        }
        th {
            background: #003300;
            color: #0ff;
        }
        .status-ok {
            color: #0f0;
        }
        .status-error {
            color: #f00;
        }
        .status-warning {
            color: #ff0;
        }
        .path {
            font-size: 12px;
            word-break: break-all;
        }
        .btn {
            background: #0f0;
            color: #000;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            margin: 10px 5px;
        }
        .btn:hover {
            background: #0ff;
        }
        pre {
            background: #003300;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 DIAGNOSTIC IMAGES - ZENU</h1>
        
        <div class="section">
            <h2>📊 Statistiques Base de Données</h2>
            <?php
            $stats = $pdo->query("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN thumbnail_path IS NOT NULL AND thumbnail_path != '' THEN 1 ELSE 0 END) as with_thumbs,
                    SUM(CASE WHEN is_deleted = 1 THEN 1 ELSE 0 END) as deleted
                FROM images
            ")->fetch();
            ?>
            <table>
                <tr>
                    <th>Total images</th>
                    <td><?= $stats['total'] ?></td>
                </tr>
                <tr>
                    <th>Avec miniatures</th>
                    <td><?= $stats['with_thumbs'] ?></td>
                </tr>
                <tr>
                    <th>Sans miniatures</th>
                    <td class="status-warning"><?= $stats['total'] - $stats['with_thumbs'] - $stats['deleted'] ?></td>
                </tr>
                <tr>
                    <th>Supprimées</th>
                    <td><?= $stats['deleted'] ?></td>
                </tr>
            </table>
        </div>
        
        <div class="section">
            <h2>📁 Vérification Dossiers</h2>
            <table>
                <tr>
                    <th>Dossier</th>
                    <th>Existe</th>
                    <th>Permissions</th>
                </tr>
                <?php
                $folders = [
                    'uploads' => 'uploads',
                    'uploads/thumbnails' => 'uploads/thumbnails'
                ];
                foreach ($folders as $name => $path) {
                    $exists = is_dir($path);
                    $perms = $exists ? substr(sprintf('%o', fileperms($path)), -4) : 'N/A';
                    $status = $exists ? 'status-ok' : 'status-error';
                    echo "<tr>";
                    echo "<td>$name</td>";
                    echo "<td class='$status'>" . ($exists ? '✅ OUI' : '❌ NON') . "</td>";
                    echo "<td>$perms</td>";
                    echo "</tr>";
                }
                ?>
            </table>
        </div>
        
        <div class="section">
            <h2>🖼️ Échantillon d'Images (5 premières)</h2>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Filename</th>
                    <th>Chemin BDD</th>
                    <th>Fichier existe</th>
                    <th>Miniature</th>
                </tr>
                <?php foreach ($images as $img): ?>
                    <?php
                    $fileExists = file_exists($img['file_path']);
                    $thumbExists = $img['thumbnail_path'] && file_exists($img['thumbnail_path']);
                    ?>
                    <tr>
                        <td><?= $img['id'] ?></td>
                        <td><?= htmlspecialchars($img['filename']) ?></td>
                        <td class="path"><?= htmlspecialchars($img['file_path']) ?></td>
                        <td class="<?= $fileExists ? 'status-ok' : 'status-error' ?>">
                            <?= $fileExists ? '✅ OUI' : '❌ NON' ?>
                        </td>
                        <td class="<?= $thumbExists ? 'status-ok' : 'status-warning' ?>">
                            <?= $thumbExists ? '✅ OUI' : ($img['thumbnail_path'] ? '⚠️ Chemin invalide' : '❌ Non généré') ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
        
        <div class="section">
            <h2>🔧 Structure des Chemins</h2>
            <?php
            $samplePath = $pdo->query("SELECT file_path FROM images LIMIT 1")->fetch();
            ?>
            <p><strong>Exemple de chemin en BDD :</strong></p>
            <pre><?= htmlspecialchars($samplePath['file_path'] ?? 'Aucune image') ?></pre>
            
            <p style="margin-top: 15px;"><strong>Les chemins devraient être :</strong></p>
            <pre>uploads/nomfichier.jpg
OU
uploads/1234567890_nomfichier.jpg</pre>
            
            <?php if ($samplePath && strpos($samplePath['file_path'], 'uploads/') === false): ?>
                <p class="status-error" style="margin-top: 15px;">
                    ⚠️ PROBLÈME DÉTECTÉ : Les chemins ne contiennent pas "uploads/"
                </p>
                <p style="margin-top: 10px;">Solution : Exécuter ce SQL :</p>
                <pre>UPDATE images SET file_path = CONCAT('uploads/', file_path) WHERE file_path NOT LIKE 'uploads/%';</pre>
            <?php endif; ?>
        </div>
        
        <div class="section">
            <h2>🚀 Actions</h2>
            <a href="generate-thumbnails.php" class="btn">Générer les miniatures</a>
            <a href="dashboard-enhanced.php" class="btn">Dashboard</a>
            <button onclick="if(confirm('Êtes-vous sûr ?')) window.location.href='?fix=paths'" class="btn">
                Corriger les chemins automatiquement
            </button>
        </div>
        
        <?php
        // Correction automatique des chemins
        if (isset($_GET['fix']) && $_GET['fix'] === 'paths') {
            echo '<div class="section">';
            echo '<h2>🔧 Correction des chemins...</h2>';
            
            $updated = $pdo->exec("
                UPDATE images 
                SET file_path = CONCAT('uploads/', file_path) 
                WHERE file_path NOT LIKE 'uploads/%'
            ");
            
            echo "<p class='status-ok'>✅ $updated chemin(s) corrigé(s)</p>";
            echo '<p>Rechargez la page pour voir les changements.</p>';
            echo '</div>';
        }
        ?>
    </div>
</body>
</html>