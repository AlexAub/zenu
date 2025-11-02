<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CGU - Zenu</title>
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
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        
        h1 {
            color: #667eea;
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .last-update {
            color: #999;
            font-size: 14px;
            margin-bottom: 30px;
        }
        
        h2 {
            color: #667eea;
            font-size: 22px;
            margin-top: 30px;
            margin-bottom: 15px;
        }
        
        h3 {
            color: #555;
            font-size: 18px;
            margin-top: 20px;
            margin-bottom: 10px;
        }
        
        p, li {
            color: #555;
            line-height: 1.8;
            margin-bottom: 15px;
        }
        
        ul {
            margin-left: 30px;
        }
        
        .highlight {
            background: #fff3e0;
            padding: 15px;
            border-left: 4px solid #ff9800;
            border-radius: 4px;
            margin: 20px 0;
        }
        
        .info-box {
            background: #e3f2fd;
            padding: 15px;
            border-left: 4px solid #2196f3;
            border-radius: 4px;
            margin: 20px 0;
        }
        
        .success-box {
            background: #e8f5e9;
            padding: 15px;
            border-left: 4px solid #4caf50;
            border-radius: 4px;
            margin: 20px 0;
        }
        
        .back-link {
            display: inline-block;
            margin-top: 30px;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .legal-links {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
        
        .legal-links p {
            font-size: 14px;
            color: #666;
        }
        
        .legal-links a {
            color: #667eea;
            margin: 0 10px;
            text-decoration: none;
        }
        
        .legal-links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Conditions Générales d'Utilisation</h1>
        <p class="last-update">Dernière mise à jour : <?= date('d/m/Y') ?></p>
        
        <h2>1. Objet</h2>
        <p>Les présentes Conditions Générales d'Utilisation (CGU) régissent l'utilisation du site Zenu.fr (ci-après "le Service"). En utilisant le Service, vous acceptez sans réserve les présentes CGU.</p>
        
        <h2>2. Description du Service</h2>
        <p>Zenu est une plateforme gratuite proposant des outils de traitement d'images :</p>
        
        <div class="success-box">
            <strong>🆓 Service entièrement gratuit</strong>
            <p style="margin-top: 10px; margin-bottom: 0;">Tous les outils Zenu sont gratuits sans limite de temps. Aucun paiement n'est requis et aucune offre premium n'existe.</p>
        </div>
        
        <h3>2.1 Outils disponibles sans compte</h3>
        <ul>
            <li><strong>Convertisseur d'images local :</strong> Conversion et redimensionnement d'images directement dans votre navigateur (aucun upload sur nos serveurs, traitement 100% local et privé)</li>
        </ul>
        
        <h3>2.2 Outils nécessitant un compte gratuit</h3>
        <ul>
            <li><strong>Upload d'images :</strong> Téléchargement d'images sur nos serveurs avec sauvegarde persistante</li>
            <li><strong>Convertisseur Cloud :</strong> Conversion avec sauvegarde automatique des images sur nos serveurs</li>
            <li><strong>Éditeur d'images :</strong> Édition avancée avec trois modes disponibles :
                <ul>
                    <li>Mode Simple : filtres, luminosité, contraste, saturation, rotation</li>
                    <li>Mode Avancé : recadrage précis avec ratio personnalisable</li>
                    <li>Mode Pro : ajout de texte, formes et annotations avec Fabric.js</li>
                </ul>
            </li>
            <li><strong>Gestionnaire d'images :</strong> Dashboard pour gérer toutes vos images sauvegardées</li>
        </ul>
        
        <h2>3. Inscription et Compte Utilisateur</h2>
        
        <h3>3.1 Création de compte</h3>
        <p>Pour accéder aux outils nécessitant un compte, vous devez créer un compte gratuit en fournissant :</p>
        <ul>
            <li>Un nom d'utilisateur unique</li>
            <li>Une adresse e-mail valide</li>
            <li>Un mot de passe sécurisé (minimum 8 caractères, 1 majuscule, 1 chiffre)</li>
        </ul>
        
        <h3>3.2 Gratuité du compte</h3>
        <p>La création et l'utilisation d'un compte Zenu sont entièrement gratuites. Aucun moyen de paiement n'est demandé lors de l'inscription.</p>
        
        <h3>3.3 Responsabilité du compte</h3>
        <p>Vous êtes responsable de la confidentialité de vos identifiants et de toutes les activités effectuées avec votre compte. En cas d'utilisation non autorisée de votre compte, vous devez nous en informer immédiatement.</p>
        
        <h2>4. Contenu Utilisateur et Images</h2>
        
        <div class="highlight">
            <strong>⚠️ RESPONSABILITÉ IMPORTANTE :</strong>
            <p style="margin-top: 10px; margin-bottom: 0;">Vous êtes seul responsable des images que vous téléchargez, éditez et sauvegardez sur Zenu. En utilisant notre service de sauvegarde sur serveur, vous garantissez que vous disposez de tous les droits nécessaires sur ces images (droits d'auteur, droits à l'image, etc.).</p>
        </div>
        
        <h3>4.1 Contenus interdits</h3>
        <p>Il est strictement interdit de télécharger, stocker, éditer ou partager des contenus :</p>
        <ul>
            <li>Illégaux, diffamatoires, ou portant atteinte aux droits d'autrui</li>
            <li>À caractère pornographique, pédopornographique ou violent</li>
            <li>Incitant à la haine, à la violence, au terrorisme ou à la discrimination</li>
            <li>Violant les droits de propriété intellectuelle de tiers (images protégées par copyright sans autorisation)</li>
            <li>Contenant des virus, malwares ou codes malveillants</li>
            <li>Portant atteinte à la vie privée de personnes sans leur consentement</li>
            <li>Contenant des deepfakes ou des images manipulées dans le but de tromper ou de nuire</li>
        </ul>
        
        <h3>4.2 Modération et contrôle</h3>
        <p>Nous nous réservons le droit, sans obligation ni préavis, de :</p>
        <ul>
            <li>Examiner les contenus stockés sur nos serveurs</li>
            <li>Supprimer tout contenu violant les présentes CGU</li>
            <li>Suspendre ou résilier votre compte en cas de violation</li>
            <li>Signaler aux autorités compétentes tout contenu illégal</li>
            <li>Refuser le traitement ou la sauvegarde de certaines images</li>
        </ul>
        
        <h3>4.3 Propriété intellectuelle</h3>
        <p>Vous conservez tous les droits de propriété intellectuelle sur vos images originales et sur les images que vous éditez avec nos outils.</p>
        
        <p>En utilisant notre service de sauvegarde, vous nous accordez une licence non exclusive, mondiale et gratuite pour :</p>
        <ul>
            <li>Stocker vos images sur nos serveurs</li>
            <li>Afficher vos images dans votre espace personnel</li>
            <li>Traiter vos images avec nos outils d'édition</li>
        </ul>
        
        <p>Cette licence prend fin lorsque vous supprimez vos images ou votre compte.</p>
        
        <h2>5. Quotas et Limites du Service</h2>
        
        <div class="info-box">
            <strong>📊 Quotas des comptes gratuits :</strong>
            <ul style="margin-top: 10px; margin-bottom: 0;">
                <li>Maximum <strong>500 images</strong> stockées simultanément</li>
                <li>Espace de stockage total : <strong>500 MB</strong></li>
                <li>Taille maximale par image : <strong>2 MB</strong> (pour upload et sauvegarde cloud)</li>
                <li>Images éditées sauvegardées : <strong>maximum 10 MB</strong> par image</li>
            </ul>
        </div>
        
        <p>Ces limites s'appliquent uniquement aux services nécessitant un compte. Le convertisseur local (sans compte) n'a aucune limite.</p>
        
        <p>Nous nous réservons le droit de modifier ces limites à tout moment, avec un préavis raisonnable si possible.</p>
        
        <h3>5.1 Dépassement des quotas</h3>
        <p>Si vous dépassez vos quotas :</p>
        <ul>
            <li>Vous ne pourrez plus uploader de nouvelles images jusqu'à ce que vous libériez de l'espace</li>
            <li>Vos images existantes resteront accessibles</li>
            <li>Vous pourrez supprimer des images pour libérer de l'espace</li>
        </ul>
        
        <h2>6. Traitement des Images</h2>
        
        <h3>6.1 Traitement local (convertisseur sans compte)</h3>
        <p>Le convertisseur d'images public fonctionne entièrement dans votre navigateur. Vos images ne sont jamais envoyées à nos serveurs et restent privées sur votre appareil.</p>
        
        <h3>6.2 Traitement serveur (outils avec compte)</h3>
        <p>Lorsque vous utilisez les outils nécessitant un compte (upload, convertisseur cloud, éditeur), vos images sont :</p>
        <ul>
            <li>Transmises de manière sécurisée via HTTPS</li>
            <li>Stockées sur nos serveurs hébergés en France (OVH)</li>
            <li>Accessibles uniquement par vous via votre compte</li>
            <li>Conservées jusqu'à suppression manuelle ou suppression de votre compte</li>
        </ul>
        
        <h2>7. Disponibilité du Service</h2>
        <p>Nous faisons nos meilleurs efforts pour assurer la disponibilité du Service 24h/24 et 7j/7. Toutefois, nous ne garantissons pas :</p>
        <ul>
            <li>Une disponibilité ininterrompue du Service</li>
            <li>La conservation définitive de vos images</li>
            <li>L'absence de bugs, d'erreurs ou de dysfonctionnements</li>
            <li>La compatibilité avec tous les navigateurs et systèmes d'exploitation</li>
            <li>La qualité parfaite des images éditées ou converties</li>
        </ul>
        
        <div class="highlight">
            <strong>⚠️ SAUVEGARDE IMPORTANTE :</strong>
            <p style="margin-top: 10px; margin-bottom: 0;">Nous ne garantissons pas la conservation définitive de vos images. Il est de votre responsabilité de conserver une copie locale de vos fichiers importants. Zenu ne peut être tenu responsable de toute perte de données.</p>
        </div>
        
        <h2>8. Limitation de Responsabilité</h2>
        
        <div class="highlight">
            <strong>⚠️ Service fourni "tel quel" :</strong>
            <p style="margin-top: 10px; margin-bottom: 0;">Zenu est un service gratuit fourni sans garantie d'aucune sorte, expresse ou implicite. L'utilisation du Service se fait à vos risques et périls.</p>
        </div>
        
        <p>Dans les limites autorisées par la loi, Zenu et son éditeur ne pourront être tenus responsables :</p>
        <ul>
            <li>De toute perte de données, d'images ou de contenu</li>
            <li>Des dommages directs ou indirects résultant de l'utilisation du Service</li>
            <li>De la qualité des images converties, éditées ou sauvegardées</li>
            <li>Du contenu uploadé par les utilisateurs</li>
            <li>Des interruptions de service, maintenance ou pannes</li>
            <li>De l'utilisation malveillante du Service par des tiers</li>
            <li>Des bugs, erreurs ou dysfonctionnements des outils</li>
            <li>De l'incompatibilité avec certains navigateurs ou appareils</li>
            <li>Des conséquences de l'utilisation d'images sauvegardées (usage commercial, diffusion, etc.)</li>
        </ul>
        
        <h2>9. Données Personnelles et Confidentialité</h2>
        <p>Le traitement de vos données personnelles est détaillé dans notre <a href="privacy.php">Politique de confidentialité</a>.</p>
        
        <p>En résumé :</p>
        <ul>
            <li>Le convertisseur local ne collecte aucune donnée ni image</li>
            <li>Les outils avec compte stockent vos images et données de compte</li>
            <li>Vos données ne sont jamais vendues ni partagées avec des tiers</li>
            <li>Vous pouvez supprimer votre compte et toutes vos données à tout moment</li>
        </ul>
        
        <h2>10. Signalement de Contenu Illégal</h2>
        <p>Si vous constatez un contenu illégal, contraire aux présentes CGU, ou portant atteinte à vos droits, veuillez nous le signaler immédiatement à :</p>
        <p><strong>Email :</strong> contact@zenu.fr</p>
        
        <p>Votre signalement doit inclure :</p>
        <ul>
            <li>Une description précise du contenu litigieux</li>
            <li>L'URL ou l'identifiant de l'image concernée si applicable</li>
            <li>Les raisons de votre signalement</li>
            <li>Vos coordonnées pour un éventuel suivi</li>
        </ul>
        
        <h2>11. Résiliation et Suppression</h2>
        
        <h3>11.1 Résiliation par l'utilisateur</h3>
        <p>Vous pouvez supprimer votre compte à tout moment depuis votre espace personnel. La suppression entraîne :</p>
        <ul>
            <li>La suppression définitive de toutes vos images stockées</li>
            <li>La suppression de vos données de compte</li>
            <li>L'impossibilité de récupérer vos données après suppression</li>
        </ul>
        
        <h3>11.2 Résiliation par Zenu</h3>
        <p>Nous nous réservons le droit de suspendre ou supprimer votre compte sans préavis en cas de :</p>
        <ul>
            <li>Violation des présentes CGU</li>
            <li>Utilisation abusive du Service</li>
            <li>Upload de contenus illégaux ou interdits</li>
            <li>Tentative de contournement des limitations techniques</li>
            <li>Activité suspecte ou frauduleuse</li>
        </ul>
        
        <h2>12. Modifications du Service et des CGU</h2>
        
        <h3>12.1 Modifications du Service</h3>
        <p>Nous nous réservons le droit de :</p>
        <ul>
            <li>Modifier, ajouter ou supprimer des fonctionnalités</li>
            <li>Modifier les quotas et limites</li>
            <li>Suspendre temporairement ou définitivement tout ou partie du Service</li>
        </ul>
        
        <h3>12.2 Modifications des CGU</h3>
        <p>Nous nous réservons le droit de modifier les présentes CGU à tout moment. Les modifications entrent en vigueur dès leur publication sur le site.</p>
        
        <p>En cas de modification importante, nous ferons nos meilleurs efforts pour vous informer par email (si vous avez un compte) ou par un avis sur le site.</p>
        
        <p>Il est de votre responsabilité de consulter régulièrement les CGU. L'utilisation continue du Service après modification des CGU vaut acceptation des nouvelles conditions.</p>
        
        <h2>13. Propriété Intellectuelle du Service</h2>
        <p>L'ensemble du code source, du design, des logos, du contenu éditorial et de la structure du site Zenu.fr est la propriété exclusive de l'éditeur et est protégé par les lois sur la propriété intellectuelle.</p>
        
        <p>Toute reproduction, distribution, modification ou utilisation non autorisée de ces éléments est strictement interdite.</p>
        
        <h2>14. Droit applicable et Juridiction</h2>
        <p>Les présentes CGU sont régies par le droit français. En cas de litige et à défaut d'accord amiable, le litige sera porté devant les tribunaux français conformément aux règles de compétence en vigueur.</p>
        
        <h2>15. Dispositions Diverses</h2>
        
        <h3>15.1 Nullité partielle</h3>
        <p>Si une disposition des présentes CGU est jugée invalide ou inapplicable, les autres dispositions restent pleinement en vigueur.</p>
        
        <h3>15.2 Non-renonciation</h3>
        <p>Le fait de ne pas exercer un droit prévu par les présentes CGU ne constitue pas une renonciation à ce droit.</p>
        
        <h3>15.3 Intégralité de l'accord</h3>
        <p>Les présentes CGU, ainsi que la Politique de confidentialité et les Mentions légales, constituent l'intégralité de l'accord entre vous et Zenu concernant l'utilisation du Service.</p>
        
        <h2>16. Contact</h2>
        <p>Pour toute question concernant les présentes CGU ou le Service, vous pouvez nous contacter :</p>
        <ul>
            <li><strong>Email :</strong> contact@zenu.fr</li>
            <li><strong>Courrier :</strong> Alex Aubin, 102 rue Truffaut, 75017 Paris, France</li>
        </ul>
        
        <a href="index.php" class="back-link">← Retour à l'accueil</a>
        
        <div class="legal-links">
            <p>
                <a href="mentions-legales.php">Mentions légales</a> · 
                <a href="cgu.php">CGU</a> · 
                <a href="privacy.php">Confidentialité</a>
            </p>
        </div>
    </div>
</body>
</html>