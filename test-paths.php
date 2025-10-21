<?php
require_once 'config.php';

// Simple protection
$pass = 'test2024';
if (!isset($_GET['p']) || $_GET['p'] !== $pass) {
    die('Accès refusé. Ajoutez ?p=test2024 à l\'URL');
}

// Récupérer une image pour tester
$stmt = $pdo->query("SELECT id, file_path, filename FROM images WHERE thumbnail_path IS NULL OR thumbnail_path = '' LIMIT 1");
$image = $stmt->fetch();

if (!$image) {
    die('Aucune image sans miniature trouvée');
}

echo '<pre>';
echo "=== DIAGNOSTIC CHEMIN IMAGE ===\n\n";

echo "📊 Informations BDD:\n";
echo "ID: " . $image['id'] . "\n";
echo "Filename: " . $image['filename'] . "\n";
echo "Path BDD: " . $image['file_path'] . "\n\n";

echo "🔍 Tests de chemins:\n\n";

// Test 1: Chemin exact de la BDD
$path1 = $image['file_path'];
echo "Test 1 - Chemin BDD direct: $path1\n";
echo "Existe: " . (file_exists($path1) ? "✅ OUI" : "❌ NON") . "\n";
if (file_exists($path1)) {
    echo "Taille réelle: " . filesize($path1) . " octets\n";
}
echo "\n";

// Test 2: Avec ./ devant
$path2 = './' . $image['file_path'];
echo "Test 2 - Avec ./: $path2\n";
echo "Existe: " . (file_exists($path2) ? "✅ OUI" : "❌ NON") . "\n\n";

// Test 3: Chemin absolu
$path3 = __DIR__ . '/' . $image['file_path'];
echo "Test 3 - Chemin absolu: $path3\n";
echo "Existe: " . (file_exists($path3) ? "✅ OUI" : "❌ NON") . "\n\n";

// Test 4: Sans uploads/
$path4 = str_replace('uploads/', '', $image['file_path']);
echo "Test 4 - Sans 'uploads/': $path4\n";
echo "Existe: " . (file_exists($path4) ? "✅ OUI" : "❌ NON") . "\n\n";

echo "📂 Informations serveur:\n";
echo "__DIR__: " . __DIR__ . "\n";
echo "__FILE__: " . __FILE__ . "\n";
echo "getcwd(): " . getcwd() . "\n\n";

echo "📁 Liste du dossier uploads:\n";
if (is_dir('uploads')) {
    echo "✅ Le dossier uploads existe\n";
    $dirs = scandir('uploads');
    echo "Contenu: " . implode(', ', array_slice($dirs, 2, 10)) . "\n\n";
    
    // Vérifier le sous-dossier user
    $userFolder = dirname($image['file_path']);
    if (is_dir($userFolder)) {
        echo "✅ Le dossier $userFolder existe\n";
        $files = scandir($userFolder);
        echo "Nombre de fichiers: " . (count($files) - 2) . "\n";
        echo "Premiers fichiers: " . implode(', ', array_slice($files, 2, 5)) . "\n";
    } else {
        echo "❌ Le dossier $userFolder n'existe pas\n";
    }
} else {
    echo "❌ Le dossier uploads n'existe pas\n";
}

echo "\n";
echo "🧪 Test de génération miniature:\n";

// Trouver le bon chemin
$correctPath = null;
$testPaths = [$path1, $path2, $path3];
foreach ($testPaths as $testPath) {
    if (file_exists($testPath)) {
        $correctPath = $testPath;
        break;
    }
}

if ($correctPath) {
    echo "✅ Chemin valide trouvé: $correctPath\n\n";
    
    // Tester la génération de miniature
    echo "Tentative de génération...\n";
    
    $thumbDir = 'uploads/thumbnails/' . dirname(str_replace('uploads/', '', $image['file_path']));
    if (!is_dir($thumbDir)) {
        mkdir($thumbDir, 0755, true);
        echo "Dossier créé: $thumbDir\n";
    }
    
    $thumbPath = str_replace('/uploads/', '/uploads/thumbnails/', $correctPath);
    $thumbPath = str_replace('uploads/', 'uploads/thumbnails/', $correctPath);
    
    echo "Chemin miniature: $thumbPath\n";
    
    require_once 'image-functions.php';
    $result = generateThumbnail($correctPath, $thumbPath, 300, 300);
    
    if ($result) {
        echo "✅ Miniature générée avec succès!\n";
        echo "Taille miniature: " . filesize($thumbPath) . " octets\n";
    } else {
        echo "❌ Échec génération miniature\n";
    }
} else {
    echo "❌ Aucun chemin valide trouvé\n";
    echo "\n🔍 Vérifiez manuellement:\n";
    echo "1. Le fichier existe-t-il vraiment sur le serveur?\n";
    echo "2. Les permissions sont-elles correctes?\n";
    echo "3. Le chemin en BDD correspond-il à la réalité?\n";
}

echo '</pre>';

echo '<hr>';
echo '<a href="?p=test2024">🔄 Rafraîchir</a> | ';
echo '<a href="generate-thumbnails.php">🖼️ Générer miniatures</a> | ';
echo '<a href="diagnostic-images.php">🔍 Diagnostic complet</a>';
?>