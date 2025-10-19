# 🧘 Zenu - Outils simples et zen

Zenu est une plateforme web proposant des outils simples et efficaces, sans complexité inutile.

## 📋 Fonctionnalités

### Outils publics
- **Convertisseur d'Images** : Redimensionnez et convertissez vos images en JPG
  - Aperçu en temps réel
  - Qualité ajustable
  - Traitement 100% local (aucun upload)

### Outils privés (nécessitent une connexion)
- **Gestionnaire d'Images** (à venir) : Sauvegardez et gérez vos images converties
  - Stockage personnel
  - Accès depuis n'importe où
  - Historique des conversions

## 🚀 Installation

### Prérequis
- PHP 8.2 ou supérieur
- MySQL 5.7 ou supérieur
- Serveur web Apache avec mod_rewrite

### Étapes

1. **Cloner le projet**
```bash
git clone https://github.com/votre-username/zenu.git
cd zenu
```

2. **Configurer la base de données**
```bash
# Créer la base de données MySQL
mysql -u root -p
CREATE DATABASE zenu;
exit;

# Importer le schéma
mysql -u root -p zenu < setup.sql
```

3. **Configurer l'application**
```bash
# Copier le fichier de configuration exemple
cp config.example.php config.php

# Éditer config.php avec vos informations
nano config.php
```

4. **Créer les dossiers nécessaires**
```bash
mkdir -p uploads
chmod 755 uploads
```

5. **Accéder au site**
Ouvrez votre navigateur : `http://localhost/zenu`

## 📁 Structure du projet

```
zenu/
├── .git/                  # Git
├── .gitignore            # Fichiers ignorés par Git
├── .htaccess             # Configuration Apache
├── config.php            # Configuration (non versionné)
├── config.example.php    # Template de configuration
├── setup.sql             # Script de création des tables
├── index.php             # Page d'accueil
├── login.php             # Connexion
├── register.php          # Inscription
├── logout.php            # Déconnexion
├── dashboard.php         # Espace membre
├── convertisseur.html    # Convertisseur d'images
├── README.md             # Documentation
└── uploads/              # Images uploadées (à venir)
```

## 🔒 Sécurité

- ✅ Mots de passe hashés avec `password_hash()` (bcrypt)
- ✅ Requêtes préparées (protection SQL injection)
- ✅ Protection CSRF
- ✅ Validation des entrées utilisateur
- ✅ Sessions sécurisées

## 🛠️ Technologies utilisées

- **Backend** : PHP 8.2
- **Base de données** : MySQL
- **Frontend** : HTML5, CSS3, JavaScript (Vanilla)
- **Librairies** : Canvas API pour le traitement d'images

## 📝 Configuration

### Fichier config.php

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'zenu');
define('DB_USER', 'votre_user');
define('DB_PASS', 'votre_password');
define('SITE_URL', 'https://votre-site.com');
```

### Base de données

Les tables sont créées automatiquement via `setup.sql` :
- `users` : Utilisateurs
- `images` : Images sauvegardées
- `sessions` : Sessions (optionnel)

## 🚧 Développement

### Roadmap
- [x] Convertisseur d'images (public)
- [x] Système d'authentification
- [x] Dashboard utilisateur
- [ ] Gestionnaire d'images (privé)
- [ ] Upload et stockage d'images
- [ ] Gestion des quotas utilisateur
- [ ] API REST
- [ ] Mode sombre

### Contribuer
Les contributions sont les bienvenues ! Créez une issue ou une pull request.

## 📄 Licence

Ce projet est sous licence MIT.

## 👤 Auteur

Votre nom - [@votre-handle](https://twitter.com/votre-handle)

## 🙏 Remerciements

- Claude AI pour l'assistance au développement
- La communauté open source

---

**Made with ❤️ and Zen**