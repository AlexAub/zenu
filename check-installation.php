<?php
// Protection simple
if (!isset($_GET['check']) || $_GET['check'] !== 'files') {
    die('Accès refusé. Ajoutez ?check=files');
}

$files = [
    'Racine' => [
        'config.php',
        'security.php',
        'image-functions.php',
        'dashboard.php', // Renommé depuis dashboard-enhanced.php
        'upload.php', // Renommé depuis upload-enhanced.php
        'trash.php',
        'share.php',
        'view.php',
        'download.php'
    ],
    'API (dossier api/)' => [
        'api/toggle-visibility.php',
        'api/delete-image.php',
        'api/restore-image.php',
        'api/delete-permanent.php',
        'api/empty-trash.php',
        'api/generate-thumbnail.php'
    ]
];

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Vérification installation - Zenu</title>
    <style>
        body { font-family: Arial; background: #f5f5f5; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
        h1 { color: #667eea; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #667eea; color: white; }
        .ok { color: green; font-weight: bold; }
        .missing { color: red; font-weight: bold; }
        .section { margin-top: 30px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>✅ Vérification de l'installation Zenu</h1>
        
        <?php foreach ($files as $section => $fileList): ?>
            <div class="section">
                <h2><?= $section ?></h2>
                <table>
                    <tr>
                        <th>Fichier</th>
                        <th>Status</th>
                    </tr>
                    <?php foreach ($fileList as $file): ?>
                        <?php $exists = file_exists($file); ?>
                        <tr>
                            <td><?= htmlspecialchars($file) ?></td>
                            <td class="<?= $exists ? 'ok' : 'missing' ?>">
                                <?= $exists ? '✅ OK' : '❌ MANQUANT' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        <?php endforeach; ?>
        
        <div class="section">
            <h2>📁 Dossiers</h2>
            <table>
                <tr>
                    <th>Dossier</th>
                    <th>Status</th>
                    <th>Permissions</th>
                </tr>
                <?php
                $folders = [
                    'uploads',
                    'uploads/thumbnails',
                    'api'
                ];
                foreach ($folders as $folder):
                    $exists = is_dir($folder);
                    $perms = $exists ? substr(sprintf('%o', fileperms($folder)), -4) : 'N/A';
                ?>
                    <tr>
                        <td><?= $folder ?></td>
                        <td class="<?= $exists ? 'ok' : 'missing' ?>">
                            <?= $exists ? '✅ OK' : '❌ MANQUANT' ?>
                        </td>
                        <td><?= $perms ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
        
        <p style="margin-top: 30px;">
            <a href="dashboard-enhanced.php" style="background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
                → Aller au Dashboard
            </a>
        </p>
    </div>
</body>
</html>