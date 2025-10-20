<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Politique de confidentialité - Zenu</title>
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
            background: #e3f2fd;
            padding: 15px;
            border-left: 4px solid #2196f3;
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
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        
        th {
            background: #f5f5f5;
            font-weight: 600;
        }
        
        a {
            color: #667eea;
            text-decoration: none;
        }
        
        a:hover {
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
        <h1>Politique de confidentialité</h1>
        <p class="last-update">Dernière mise à jour : <?= date('d/m/Y') ?></p>
        
        <p>Chez Zenu, nous prenons la protection de vos données personnelles très au sérieux. Cette politique de confidentialité explique quelles données nous collectons, pourquoi nous les collectons et comment nous les utilisons.</p>
        
        <h2>1. Responsable du traitement</h2>
        <p><strong>Alex Aubin</strong> (particulier)<br>
        102 rue Truffaut<br>
        75017 Paris, France<br>
        Email : <a href="mailto:contact@zenu.fr">contact@zenu.fr</a></p>
        
        <div class="highlight">
            <strong>ℹ️ Site non professionnel :</strong>
            <p style="margin-top: 10px; margin-bottom: 0;">Zenu est un site gratuit édité par un particulier à titre non professionnel. Aucun traitement de données à des fins commerciales ou marketing n'est effectué.</p>
        </div>
        
        <h2>2. Données collectées</h2>
        
        <h3>2.1 Données d'inscription</h3>
        <p>Lorsque vous créez un compte, nous collectons :</p>
        <table>
            <tr>
                <th>Donnée</th>
                <th>Finalité</th>
                <th>Base légale</th>
            </tr>
            <tr>
                <td>Nom d'utilisateur</td>
                <td>Identification, URLs personnalisées</td>
                <td>Exécution du contrat</td>
            </tr>
            <tr>
                <td>Adresse e-mail</td>
                <td>Authentification, communication</td>
                <td>Exécution du contrat</td>
            </tr>
            <tr>
                <td>Mot de passe (hashé)</td>
                <td>Sécurité du compte</td>
                <td>Exécution du contrat</td>
            </tr>
            <tr>
                <td>Date de création du compte</td>
                <td>Gestion du compte</td>
                <td>Exécution du contrat</td>
            </tr>
        </table>
        
        <h3>2.2 Données d'utilisation</h3>
        <ul>
            <li><strong>Images uploadées :</strong> Stockées sur nos serveurs pour le service de sauvegarde</li>
            <li><strong>Métadonnées des images :</strong> Nom, dimensions, taille, date d'upload</li>
            <li><strong>Adresse IP :</strong> Pour la sécurité (rate limiting, détection d'abus)</li>
            <li><strong>Logs de connexion :</strong> Date et heure des connexions, tentatives échouées</li>
        </ul>
        
        <h3>2.3 Cookies</h3>
        <p>Nous utilisons uniquement des cookies essentiels au fonctionnement du site :</p>
        <ul>
            <li><strong>Cookie de session :</strong> Pour maintenir votre connexion (supprimé à la fermeture du navigateur)</li>
        </ul>
        <p>Nous n'utilisons <strong>aucun cookie de tracking ou publicitaire</strong>.</p>
        
        <h2>3. Utilisation des données</h2>
        <p>Vos données sont utilisées exclusivement pour :</p>
        <ul>
            <li>Fournir et maintenir le Service</li>
            <li>Gérer votre compte utilisateur</li>
            <li>Stocker et afficher vos images</li>
            <li>Assurer la sécurité du Service (détection d'abus, spam)</li>
            <li>Respecter nos obligations légales</li>
        </ul>
        
        <div class="highlight">
            <strong>🔒 Engagement :</strong>
            <p style="margin-top: 10px; margin-bottom: 0;">Nous ne vendons, ne louons et ne partageons JAMAIS vos données personnelles avec des tiers à des fins commerciales ou marketing. Ce site étant gratuit et non commercial, aucune donnée n'est utilisée à des fins publicitaires.</p>
        </div>
        
        <h2>4. Partage des données</h2>
        <p>Vos données ne sont partagées qu'avec :</p>
        <ul>
            <li><strong>OVH (hébergeur) :</strong> Nécessaire pour l'hébergement du Service (serveurs situés en France)</li>
            <li><strong>Autorités légales :</strong> Uniquement si requis par la loi (décision de justice, etc.)</li>
        </ul>
        <p><strong>Aucun partage avec des partenaires commerciaux, publicitaires ou tiers.</strong></p>
        
        <h2>5. Durée de conservation</h2>
        <table>
            <tr>
                <th>Type de donnée</th>
                <th>Durée de conservation</th>
            </tr>
            <tr>
                <td>Compte utilisateur</td>
                <td>Jusqu'à suppression du compte</td>
            </tr>
            <tr>
                <td>Images uploadées</td>
                <td>Jusqu'à suppression manuelle ou du compte</td>
            </tr>
            <tr>
                <td>Logs de connexion</td>
                <td>Maximum 12 mois</td>
            </tr>
            <tr>
                <td>Adresses IP (rate limiting)</td>
                <td>Maximum 30 jours</td>
            </tr>
        </table>
        
        <h2>6. Sécurité</h2>
        <p>Nous mettons en œuvre des mesures de sécurité appropriées :</p>
        <ul>
            <li>Mots de passe hashés avec bcrypt</li>
            <li>Connexions HTTPS cryptées</li>
            <li>Protection contre les attaques par force brute (rate limiting)</li>
            <li>Serveurs sécurisés chez OVH (France)</li>
            <li>Accès restreint aux données</li>
        </ul>
        
        <h2>7. Vos droits (RGPD)</h2>
        <p>Conformément au RGPD, vous disposez des droits suivants :</p>
        
        <h3>7.1 Droit d'accès</h3>
        <p>Vous pouvez demander une copie de toutes vos données personnelles.</p>
        
        <h3>7.2 Droit de rectification</h3>
        <p>Vous pouvez modifier vos données personnelles directement depuis votre compte.</p>
        
        <h3>7.3 Droit à l'effacement</h3>
        <p>Vous pouvez supprimer votre compte et toutes vos données à tout moment.</p>
        
        <h3>7.4 Droit à la portabilité</h3>
        <p>Vous pouvez demander vos données dans un format structuré et lisible.</p>
        
        <h3>7.5 Droit d'opposition</h3>
        <p>Vous pouvez vous opposer au traitement de vos données pour des motifs légitimes.</p>
        
        <h3>7.6 Droit de réclamation</h3>
        <p>Vous pouvez déposer une plainte auprès de la CNIL : <a href="https://www.cnil.fr" target="_blank">www.cnil.fr</a></p>
        
        <div class="highlight">
            <strong>📧 Exercer vos droits :</strong>
            <p style="margin-top: 10px; margin-bottom: 0;">Pour exercer vos droits, contactez-nous à : <strong><a href="mailto:contact@zenu.fr">contact@zenu.fr</a></strong><br>
            Nous vous répondrons sous 1 mois maximum.</p>
        </div>
        
        <h2>8. Transferts internationaux</h2>
        <p>Vos données sont stockées exclusivement en France (hébergement OVH). Aucun transfert hors UE n'est effectué.</p>
        
        <h2>9. Mineurs</h2>
        <p>Notre service est accessible aux personnes de plus de 16 ans. Si vous avez moins de 16 ans, vous devez obtenir l'autorisation de vos parents ou représentants légaux.</p>
        
        <h2>10. Modifications de la politique</h2>
        <p>Nous pouvons modifier cette politique de confidentialité à tout moment. En cas de changements importants, nous vous informerons par email si possible, ou par un avis sur le site.</p>
        
        <h2>11. Contact</h2>
        <p>Pour toute question sur cette politique de confidentialité ou vos données personnelles :</p>
        <ul>
            <li><strong>Email :</strong> <a href="mailto:contact@zenu.fr">contact@zenu.fr</a></li>
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