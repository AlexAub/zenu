<footer class="site-footer">
    <div class="footer-content">
        <div class="footer-section">
            <h4>🧘 Zenu</h4>
            <p>Outils simples et zen pour votre quotidien</p>
        </div>
        
        <div class="footer-section">
            <h4>Liens utiles</h4>
            <ul>
                <li><a href="index.php">Accueil</a></li>
                <li><a href="convertisseur.php">Convertisseur gratuit</a></li>
                <?php if (isset($user) && $user): ?>
                    <li><a href="dashboard.php">Mon espace</a></li>
                    <li><a href="convertisseur-prive.php">Convertisseur privé</a></li>
                <?php else: ?>
                    <li><a href="register.php">S'inscrire</a></li>
                    <li><a href="login.php">Se connecter</a></li>
                <?php endif; ?>
            </ul>
        </div>
        
        <div class="footer-section">
            <h4>Légal</h4>
            <ul>
                <li><a href="mentions-legales.php">Mentions légales</a></li>
                <li><a href="cgu.php">CGU</a></li>
                <li><a href="privacy.php">Confidentialité</a></li>
            </ul>
        </div>
        
        <div class="footer-section">
            <h4>Contact</h4>
            <ul>
                <li><a href="mailto:contact@zenu.fr">contact@zenu.fr</a></li>
                <li>102 rue Truffaut<br>75017 Paris</li>
            </ul>
        </div>
    </div>
    
    <div class="footer-bottom">
        <p>&copy; <?= date('Y') ?> Zenu - Tous droits réservés</p>
    </div>
</footer>
