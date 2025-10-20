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
    </style>
</head>
<body>
    <div class="container">
        <h1>Conditions Générales d'Utilisation</h1>
        <p class="last-update">Dernière mise à jour : <?= date('d/m/Y') ?></p>
        
        <h2>1. Objet</h2>
        <p>Les présentes Conditions Générales d'Utilisation (CGU) régissent l'utilisation du site Zenu.fr (ci-après "le Service"). En utilisant le Service, vous acceptez sans réserve les présentes CGU.</p>
        
        <h2>2. Description du Service</h2>
        <p>Zenu est une plateforme proposant des outils de traitement d'images :</p>
        <ul>
            <li><strong>Convertisseur d'images public :</strong> Outil gratuit de conversion et redimensionnement d'images en local (aucun upload sur nos serveurs)</li>
            <li><strong>Convertisseur privé :</strong> Outil de conversion avec sauvegarde des images sur nos serveurs (nécessite un compte)</li>
            <li><strong>Gestionnaire d'images :</strong> Espace de stockage personnel pour vos images converties</li>
        </ul>
        
        <h2>3. Inscription et Compte Utilisateur</h2>
        <h3>3.1 Création de compte</h3>
        <p>Pour accéder aux services privés, vous devez créer un compte en fournissant :</p>
        <ul>
            <li>Un nom d'utilisateur unique</li>
            <li>Une adresse e-mail valide</li>
            <li>Un mot de passe sécurisé (minimum 8 caractères, 1 majuscule, 1 chiffre)</li>
        </ul>
        
        <h3>3.2 Responsabilité du compte</h3>
        <p>Vous êtes responsable de la confidentialité de vos identifiants et de toutes les activités effectuées avec votre compte.</p>
        
        <h2>4. Contenu Utilisateur</h2>
        
        <div class="highlight">
            <strong>⚠️ RESPONSABILITÉ IMPORTANTE :</strong>
            <p style="margin-top: 10px; margin-bottom: 0;">Vous êtes seul responsable des images que vous téléchargez et sauvegardez sur Zenu. En utilisant notre service, vous garantissez que vous disposez de tous les droits nécessaires sur ces images.</p>
        </div>
        
        <h3>4.1 Contenus interdits</h3>
        <p>Il est strictement interdit de télécharger, stocker ou partager des contenus :</p>
        <ul>
            <li>Illégaux, diffamatoires, ou portant atteinte aux droits d'autrui</li>
            <li>À caractère pornographique, pédopornographique ou violant</li>
            <li>Incitant à la haine, à la violence, au terrorisme ou à la discrimination</li>
            <li>Violant les droits de propriété intellectuelle de tiers</li>
            <li>Contenant des virus, malwares ou codes malveillants</li>
            <li>Portant atteinte à la vie privée de personnes sans leur consentement</li>
        </ul>
        
        <h3>4.2 Modération</h3>
        <p>Nous nous réservons le droit, sans obligation, de :</p>
        <ul>
            <li>Supprimer tout contenu violant les présentes CGU</li>
            <li>Suspendre ou résilier votre compte sans préavis</li>
            <li>Signaler aux autorités compétentes tout contenu illégal</li>
        </ul>
        
        <h3>4.3 Propriété intellectuelle</h3>
        <p>Vous conservez tous les droits de propriété intellectuelle sur vos images. En utilisant notre service, vous nous accordez une licence non exclusive pour stocker et afficher vos images uniquement dans le cadre de la fourniture du Service.</p>
        
        <h2>5. Quotas et Limites</h2>
        <p>Les comptes gratuits sont soumis aux limites suivantes :</p>
        <ul>
            <li>Maximum 500 images stockées</li>
            <li>Espace total de 500 MB</li>
            <li>Taille maximale par image : 2 MB</li>
        </ul>
        <p>Nous nous réservons le droit de modifier ces limites à tout moment.</p>
        
        <h2>6. Disponibilité du Service</h2>
        <p>Nous faisons nos meilleurs efforts pour assurer la disponibilité du Service 24h/24 et 7j/7. Toutefois, nous ne garantissons pas :</p>
        <ul>
            <li>Une disponibilité ininterrompue du Service</li>
            <li>La conservation définitive de vos images</li>
            <li>L'absence de bugs ou d'erreurs</li>
        </ul>
        
        <div class="highlight">
            <strong>⚠️ SAUVEGARDE :</strong>
            <p style="margin-top: 10px; margin-bottom: 0;">Nous ne garantissons pas la conservation définitive de vos images. Il est de votre responsabilité de conserver une copie de vos fichiers importants.</p>
        </div>
        
        <h2>7. Limitation de Responsabilité</h2>
        <p>Dans les limites autorisées par la loi, Zenu ne pourra être tenu responsable :</p>
        <ul>
            <li>De toute perte de données ou de contenu</li>
            <li>Des dommages directs ou indirects résultant de l'utilisation du Service</li>
            <li>Du contenu uploadé par les utilisateurs</li>
            <li>Des interruptions de service</li>
            <li>De l'utilisation malveillante du Service par des tiers</li>
        </ul>
        
        <h2>8. Signalement</h2>
        <p>Si vous constatez un contenu illégal ou contraire aux présentes CGU, veuillez nous le signaler immédiatement à : <strong>contact@zenu.fr</strong></p>
        
        <h2>9. Résiliation</h2>
        <p>Vous pouvez supprimer votre compte à tout moment. Nous nous réservons le droit de suspendre ou supprimer votre compte en cas de violation des présentes CGU.</p>
        
        <h2>10. Modifications des CGU</h2>
        <p>Nous nous réservons le droit de modifier les présentes CGU à tout moment. Les modifications entrent en vigueur dès leur publication sur le site. Il est de votre responsabilité de consulter régulièrement les CGU.</p>
        
        <h2>11. Droit applicable</h2>
        <p>Les présentes CGU sont régies par le droit français. Tout litige sera soumis à la compétence exclusive des tribunaux français.</p>
        
        <h2>12. Contact</h2>
        <p>Pour toute question concernant les présentes CGU, vous pouvez nous contacter :</p>
        <ul>
            <li><strong>Email :</strong> contact@zenu.fr</li>
            <li><strong>Adresse :</strong> 102 rue Truffaut, 75017 Paris, France</li>
        </ul>
        
        <a href="index.php" class="back-link">← Retour à l'accueil</a>
    </div>
</body>
</html>