<?php
$pageTitle = "Documentation IA";
require_once 'header.php';
?>

<style>
    .docs-container {
        max-width: 1000px;
        margin: 40px auto;
        padding: 0 20px;
    }
    
    .docs-header {
        text-align: center;
        margin-bottom: 50px;
    }
    
    .docs-header h1 {
        font-size: 2.5em;
        color: #2d3748;
        margin-bottom: 15px;
    }
    
    .docs-header p {
        font-size: 1.2em;
        color: #718096;
    }
    
    .docs-nav {
        background: white;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        margin-bottom: 30px;
        position: sticky;
        top: 20px;
    }
    
    .docs-nav h3 {
        margin-top: 0;
        margin-bottom: 15px;
        color: #2d3748;
    }
    
    .docs-nav ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .docs-nav li {
        margin-bottom: 10px;
    }
    
    .docs-nav a {
        color: #667eea;
        text-decoration: none;
        display: block;
        padding: 8px 12px;
        border-radius: 6px;
        transition: all 0.2s;
    }
    
    .docs-nav a:hover {
        background: #f7fafc;
        padding-left: 20px;
    }
    
    .docs-section {
        background: white;
        padding: 40px;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        margin-bottom: 30px;
    }
    
    .docs-section h2 {
        color: #2d3748;
        border-bottom: 3px solid #667eea;
        padding-bottom: 15px;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .docs-section h3 {
        color: #4a5568;
        margin-top: 30px;
        margin-bottom: 15px;
    }
    
    .docs-section p {
        line-height: 1.8;
        color: #4a5568;
        margin-bottom: 15px;
    }
    
    .docs-section ul, .docs-section ol {
        line-height: 1.8;
        color: #4a5568;
        margin-bottom: 20px;
    }
    
    .docs-section li {
        margin-bottom: 10px;
    }
    
    .feature-box {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 20px;
        border-radius: 10px;
        margin: 20px 0;
    }
    
    .feature-box h4 {
        margin-top: 0;
        font-size: 1.2em;
    }
    
    .tip-box {
        background: #e6fffa;
        border-left: 4px solid #38b2ac;
        padding: 20px;
        border-radius: 8px;
        margin: 20px 0;
    }
    
    .tip-box strong {
        color: #2c7a7b;
    }
    
    .warning-box {
        background: #fffaf0;
        border-left: 4px solid #ed8936;
        padding: 20px;
        border-radius: 8px;
        margin: 20px 0;
    }
    
    .warning-box strong {
        color: #c05621;
    }
    
    .code-box {
        background: #2d3748;
        color: #e2e8f0;
        padding: 20px;
        border-radius: 8px;
        font-family: 'Courier New', monospace;
        margin: 20px 0;
        overflow-x: auto;
    }
    
    .comparison-table {
        width: 100%;
        border-collapse: collapse;
        margin: 20px 0;
    }
    
    .comparison-table th,
    .comparison-table td {
        padding: 15px;
        text-align: left;
        border-bottom: 1px solid #e2e8f0;
    }
    
    .comparison-table th {
        background: #f7fafc;
        font-weight: 600;
        color: #2d3748;
    }
    
    .comparison-table tr:hover {
        background: #f7fafc;
    }
    
    @media (max-width: 768px) {
        .docs-section {
            padding: 20px;
        }
        
        .docs-nav {
            position: static;
        }
    }
</style>

<div class="docs-container">
    <div class="docs-header">
        <h1>🤖 Documentation des Outils IA</h1>
        <p>Guide complet pour utiliser les fonctionnalités d'intelligence artificielle de Zenu</p>
    </div>
    
    <div class="docs-nav">
        <h3>📑 Table des matières</h3>
        <ul>
            <li><a href="#intro">Introduction</a></li>
            <li><a href="#remove-bg">Suppression de fond</a></li>
            <li><a href="#enhance">Amélioration automatique</a></li>
            <li><a href="#smart-crop">Recadrage intelligent</a></li>
            <li><a href="#optimize">Compression intelligente</a></li>
            <li><a href="#tips">Conseils et astuces</a></li>
            <li><a href="#faq">FAQ</a></li>
        </ul>
    </div>
    
    <section id="intro" class="docs-section">
        <h2>🌟 Introduction</h2>
        
        <p>Les outils IA de Zenu utilisent des algorithmes avancés de traitement d'image pour automatiser et améliorer vos tâches d'édition. Contrairement aux éditeurs manuels, ces outils analysent intelligemment vos images et appliquent les modifications optimales.</p>
        
        <div class="feature-box">
            <h4>✨ Pourquoi utiliser les outils IA ?</h4>
            <ul style="margin: 10px 0 0 0;">
                <li>Gain de temps considérable</li>
                <li>Résultats professionnels en un clic</li>
                <li>Pas besoin de compétences en édition</li>
                <li>Analyse intelligente de chaque image</li>
            </ul>
        </div>
        
        <h3>Comment ça marche ?</h3>
        <ol>
            <li>Accédez aux <a href="ai-tools.php" style="color: #667eea;">Outils IA</a></li>
            <li>Sélectionnez l'outil souhaité</li>
            <li>Choisissez une image dans votre bibliothèque</li>
            <li>Ajustez les options si nécessaire</li>
            <li>Cliquez sur "Traiter l'image"</li>
            <li>Prévisualisez le résultat et sauvegardez</li>
        </ol>
    </section>
    
    <section id="remove-bg" class="docs-section">
        <h2><span>🎭</span> Suppression de fond</h2>
        
        <p>Retirez automatiquement l'arrière-plan de vos images en conservant uniquement le sujet principal. Idéal pour les portraits, photos de produits, logos et bien plus.</p>
        
        <h3>Quand l'utiliser ?</h3>
        <ul>
            <li><strong>E-commerce :</strong> Photos de produits sur fond blanc</li>
            <li><strong>Portraits :</strong> Isoler une personne de son environnement</li>
            <li><strong>Logos :</strong> Créer des versions transparentes</li>
            <li><strong>Montages :</strong> Préparer des éléments pour compositions</li>
        </ul>
        
        <h3>Options disponibles</h3>
        <table class="comparison-table">
            <thead>
                <tr>
                    <th>Option</th>
                    <th>Description</th>
                    <th>Recommandé pour</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Automatique</strong></td>
                    <td>Détection intelligente du sujet</td>
                    <td>La plupart des cas</td>
                </tr>
                <tr>
                    <td><strong>Portrait</strong></td>
                    <td>Optimisé pour les visages</td>
                    <td>Photos de personnes</td>
                </tr>
                <tr>
                    <td><strong>Produit</strong></td>
                    <td>Idéal pour objets inanimés</td>
                    <td>E-commerce</td>
                </tr>
                <tr>
                    <td><strong>Animal</strong></td>
                    <td>Détection spécifique animaux</td>
                    <td>Photos d'animaux</td>
                </tr>
            </tbody>
        </table>
        
        <div class="tip-box">
            <strong>💡 Astuce :</strong> Pour de meilleurs résultats, utilisez des images avec un bon contraste entre le sujet et l'arrière-plan. Les fonds unis donnent les meilleurs résultats.
        </div>
        
        <h3>Qualité des bords</h3>
        <ul>
            <li><strong>Haute :</strong> Bords très précis, traitement plus lent (recommandé pour impression)</li>
            <li><strong>Moyenne :</strong> Bon équilibre qualité/vitesse (recommandé pour le web)</li>
            <li><strong>Rapide :</strong> Traitement instantané, bords légèrement moins précis</li>
        </ul>
    </section>
    
    <section id="enhance" class="docs-section">
        <h2><span>✨</span> Amélioration automatique</h2>
        
        <p>L'IA analyse votre image et optimise automatiquement la luminosité, le contraste, la saturation et la netteté pour obtenir le meilleur rendu possible.</p>
        
        <h3>Comment fonctionne l'analyse ?</h3>
        <p>L'algorithme examine votre image pixel par pixel pour détecter :</p>
        <ul>
            <li>La luminosité moyenne (trop sombre ou trop claire ?)</li>
            <li>Le niveau de contraste (image plate ou trop contrastée ?)</li>
            <li>La saturation des couleurs (délavées ou sursaturées ?)</li>
            <li>Le niveau de détail et de netteté</li>
        </ul>
        
        <p>En fonction de cette analyse, des corrections sont appliquées de manière intelligente.</p>
        
        <div class="feature-box">
            <h4>🎯 Types d'images idéales</h4>
            <ul style="margin: 10px 0 0 0;">
                <li>Photos sous-exposées ou surexposées</li>
                <li>Images prises avec un smartphone</li>
                <li>Scans de documents anciens</li>
                <li>Photos avec faible contraste</li>
                <li>Images aux couleurs ternes</li>
            </ul>
        </div>
        
        <h3>Contrôles disponibles</h3>
        <ul>
            <li><strong>Intensité :</strong> Contrôlez la force des corrections (0-100%)</li>
            <li><strong>Luminosité :</strong> Corrige l'exposition</li>
            <li><strong>Contraste :</strong> Améliore la différenciation</li>
            <li><strong>Saturation :</strong> Ravive les couleurs</li>
            <li><strong>Netteté :</strong> Rend l'image plus définie</li>
        </ul>
        
        <div class="tip-box">
            <strong>💡 Pro Tip :</strong> Commencez avec une intensité de 50% et ajustez selon vos préférences. Une intensité trop élevée peut donner un aspect artificiel.
        </div>
    </section>
    
    <section id="smart-crop" class="docs-section">
        <h2><span>🎯</span> Recadrage intelligent</h2>
        
        <p>L'IA détecte automatiquement le sujet principal de votre image et recadre de manière optimale selon le format souhaité.</p>
        
        <h3>Formats disponibles</h3>
        <table class="comparison-table">
            <thead>
                <tr>
                    <th>Format</th>
                    <th>Ratio</th>
                    <th>Utilisation</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Automatique</td>
                    <td>Optimal</td>
                    <td>L'IA choisit le meilleur recadrage</td>
                </tr>
                <tr>
                    <td>Carré</td>
                    <td>1:1</td>
                    <td>Instagram, avatars, icônes</td>
                </tr>
                <tr>
                    <td>Paysage</td>
                    <td>16:9</td>
                    <td>YouTube, TV, présentations</td>
                </tr>
                <tr>
                    <td>Portrait</td>
                    <td>9:16</td>
                    <td>Stories, TikTok, Reels</td>
                </tr>
                <tr>
                    <td>Standard</td>
                    <td>4:3</td>
                    <td>Photos classiques</td>
                </tr>
                <tr>
                    <td>Cinéma</td>
                    <td>21:9</td>
                    <td>Format ultra-large</td>
                </tr>
            </tbody>
        </table>
        
        <h3>Priorités de détection</h3>
        <ul>
            <li><strong>Visages :</strong> Priorise les personnes dans l'image</li>
            <li><strong>Sujet principal :</strong> Détecte l'élément le plus important</li>
            <li><strong>Centre :</strong> Se concentre sur le centre de l'image</li>
            <li><strong>Règle des tiers :</strong> Applique les principes de composition photographique</li>
        </ul>
        
        <div class="warning-box">
            <strong>⚠️ Important :</strong> Le recadrage intelligent réduit la taille de l'image. Vérifiez toujours le résultat avant de sauvegarder pour vous assurer qu'aucune partie importante n'a été coupée.
        </div>
    </section>
    
    <section id="optimize" class="docs-section">
        <h2><span>⚡</span> Compression intelligente</h2>
        
        <p>Réduisez drastiquement la taille de vos fichiers sans perte visible de qualité. L'algorithme adapte la compression selon le contenu de l'image.</p>
        
        <h3>Pourquoi optimiser ?</h3>
        <ul>
            <li>🚀 Chargement plus rapide sur le web</li>
            <li>💾 Économie d'espace de stockage</li>
            <li>📧 Facilite l'envoi par email</li>
            <li>📱 Moins de données mobiles consommées</li>
        </ul>
        
        <h3>Comment ça marche ?</h3>
        <p>L'IA analyse le contenu de votre image pour déterminer :</p>
        <ul>
            <li><strong>La complexité :</strong> Images simples = compression plus forte possible</li>
            <li><strong>Le niveau de détail :</strong> Photos détaillées = compression plus douce</li>
            <li><strong>Le bruit :</strong> Images bruitées peuvent être plus compressées</li>
        </ul>
        
        <div class="feature-box">
            <h4>📊 Résultats typiques</h4>
            <ul style="margin: 10px 0 0 0;">
                <li>Photos smartphone : 40-60% de réduction</li>
                <li>Captures d'écran : 60-80% de réduction</li>
                <li>Images simples : jusqu'à 85% de réduction</li>
            </ul>
        </div>
        
        <h3>Options de compression</h3>
        <ul>
            <li><strong>Qualité :</strong> 60-100% (recommandé : 80%)</li>
            <li><strong>Format :</strong>
                <ul>
                    <li><em>WebP :</em> Meilleure compression (-25% vs JPEG)</li>
                    <li><em>JPEG :</em> Compatibilité maximale</li>
                    <li><em>PNG :</em> Pour images avec transparence</li>
                </ul>
            </li>
            <li><strong>Métadonnées :</strong> Supprimer les données EXIF pour réduire la taille</li>
        </ul>
        
        <div class="tip-box">
            <strong>💡 Conseil :</strong> Pour le web, utilisez WebP avec qualité 80%. Pour l'impression, conservez JPEG à 90-95%.
        </div>
    </section>
    
    <section id="tips" class="docs-section">
        <h2>💎 Conseils et astuces</h2>
        
        <h3>Pour tous les outils</h3>
        <ul>
            <li>Travaillez toujours sur une copie de l'original</li>
            <li>Prévisualisez avant de sauvegarder</li>
            <li>Testez différentes options pour comparer</li>
            <li>Les images haute résolution donnent de meilleurs résultats</li>
        </ul>
        
        <h3>Workflow recommandé</h3>
        <ol>
            <li><strong>Amélioration automatique</strong> d'abord pour corriger luminosité/contraste</li>
            <li><strong>Recadrage intelligent</strong> ensuite pour le format final</li>
            <li><strong>Suppression de fond</strong> si nécessaire pour votre utilisation</li>
            <li><strong>Compression intelligente</strong> en dernier pour optimiser le poids</li>
        </ol>
        
        <div class="warning-box">
            <strong>⚠️ Limitation :</strong> Les traitements IA sont irréversibles une fois sauvegardés. Conservez toujours vos originaux !
        </div>
    </section>
    
    <section id="faq" class="docs-section">
        <h2>❓ Questions fréquentes</h2>
        
        <h3>Les outils IA sont-ils gratuits ?</h3>
        <p>Oui, tous les outils IA sont inclus gratuitement dans votre compte Zenu sans limitation.</p>
        
        <h3>Quelle est la taille maximale d'image ?</h3>
        <p>Les images jusqu'à 10 MB et 4096x4096 pixels sont supportées pour les traitements IA.</p>
        
        <h3>Combien de temps prend un traitement ?</h3>
        <p>En général 2-5 secondes selon la taille de l'image et la complexité du traitement.</p>
        
        <h3>Puis-je traiter plusieurs images en même temps ?</h3>
        <p>Actuellement non, mais un mode batch est prévu pour une mise à jour future.</p>
        
        <h3>Les résultats peuvent-ils être améliorés ?</h3>
        <p>Oui ! Si le résultat ne vous satisfait pas, essayez de modifier les options ou utilisez l'éditeur manuel pour des ajustements précis.</p>
        
        <h3>Mes données sont-elles privées ?</h3>
        <p>Absolument. Vos images ne sont jamais partagées et les fichiers temporaires sont supprimés après 24h.</p>
    </section>
    
    <div style="text-align: center; margin: 50px 0;">
        <a href="ai-tools.php" class="btn btn-primary" style="display: inline-block; padding: 15px 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 10px; font-weight: 600;">
            🚀 Essayer les outils IA
        </a>
    </div>
</div>

<?php require_once 'footer.php'; ?>
