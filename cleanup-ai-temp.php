<?php
/**
 * Script de nettoyage des fichiers temporaires IA
 * À exécuter via CRON quotidiennement : 0 3 * * * php /path/to/cleanup-ai-temp.php
 */

require_once 'config.php';

echo "🧹 Début du nettoyage des fichiers temporaires IA\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

$tempDir = __DIR__ . '/uploads/temp';
$deletedCount = 0;
$deletedSize = 0;
$errors = 0;

// Vérifier que le dossier existe
if (!is_dir($tempDir)) {
    echo "❌ Le dossier temporaire n'existe pas: $tempDir\n";
    exit(1);
}

// Parcourir les fichiers
$files = scandir($tempDir);

foreach ($files as $file) {
    if ($file === '.' || $file === '..') {
        continue;
    }
    
    $filePath = $tempDir . '/' . $file;
    
    // Ignorer les dossiers
    if (is_dir($filePath)) {
        continue;
    }
    
    // Vérifier l'âge du fichier
    $fileAge = time() - filemtime($filePath);
    $ageInHours = $fileAge / 3600;
    
    // Supprimer les fichiers de plus de 24 heures
    if ($ageInHours > 24) {
        $fileSize = filesize($filePath);
        
        if (unlink($filePath)) {
            $deletedCount++;
            $deletedSize += $fileSize;
            echo "✓ Supprimé: $file (âge: " . round($ageInHours, 1) . "h, taille: " . formatBytes($fileSize) . ")\n";
        } else {
            $errors++;
            echo "❌ Erreur lors de la suppression: $file\n";
        }
    }
}

echo "\n";
echo "📊 Résumé du nettoyage:\n";
echo "   • Fichiers supprimés: $deletedCount\n";
echo "   • Espace libéré: " . formatBytes($deletedSize) . "\n";
echo "   • Erreurs: $errors\n";
echo "\n";

// Nettoyer également la base de données des opérations anciennes
echo "🗄️  Nettoyage de la base de données...\n";

try {
    // Supprimer les opérations échouées de plus de 7 jours
    $stmt = $pdo->prepare("
        DELETE FROM ai_operations 
        WHERE status = 'failed' 
        AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)
    ");
    $stmt->execute();
    $deletedOps = $stmt->rowCount();
    
    echo "✓ $deletedOps opérations échouées supprimées\n";
    
    // Marquer comme échouées les opérations bloquées depuis plus d'1 heure
    $stmt = $pdo->prepare("
        UPDATE ai_operations 
        SET status = 'failed', 
            error_message = 'Timeout - opération abandonnée'
        WHERE status IN ('pending', 'processing')
        AND created_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ");
    $stmt->execute();
    $timedOut = $stmt->rowCount();
    
    if ($timedOut > 0) {
        echo "⚠️  $timedOut opérations bloquées marquées comme échouées\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Erreur base de données: " . $e->getMessage() . "\n";
}

echo "\n✅ Nettoyage terminé!\n";

/**
 * Formater les octets en unité lisible
 */
function formatBytes($bytes, $precision = 2) {
    $units = ['o', 'Ko', 'Mo', 'Go', 'To'];
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}
