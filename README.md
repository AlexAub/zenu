# 🚀 Zenu - Outils Simples et Zen

Bienvenue dans **Zenu**, une collection d'outils simples et intuitifs pour simplifier vos tâches quotidiennes. Ce projet est conçu pour être facile à configurer et à utiliser, tout en offrant une expérience fluide et efficace. 🌟

## 📋 Table des matières
- [Installation](#-installation)
- [Prérequis](#-prérequis)
- [Configuration](#-configuration)
- [Contribuer](#-contribuer)
- [Licence](#-licence)

## 🛠 Prérequis
Avant de commencer, assurez-vous d'avoir les éléments suivants installés :
- **PHP** (>= 8.2 recommandé)
- **MySQL** ou un autre système de gestion de base de données compatible
- **Git** pour cloner le dépôt
- Un compte Google pour configurer **reCAPTCHA**

## 🚀 Installation

### 1. Cloner le dépôt
Clonez le dépôt GitHub et naviguez dans le dossier du projet :
```bash
git clone https://github.com/AlexAub/zenu.git
cd zenu
```

### 2. Configurer les fichiers sensibles
Les fichiers de configuration doivent être copiés à partir des exemples fournis et remplis avec vos informations.

**Copier les fichiers d'exemple :**
```bash
cp config-example.php config.php
cp email-config.example.php email-config.php
```

**Éditer les fichiers de configuration :**
- **`config.php`** : Ajoutez vos identifiants de base de données (nom d'utilisateur, mot de passe, nom de la base, etc.).
- **`email-config.php`** : Configurez les paramètres d'email (SMTP, etc.) et les clés reCAPTCHA.

### 3. Configurer la base de données
Importez les fichiers SQL pour créer et mettre à jour la structure de la base de données.

**Importer les fichiers SQL :**
```bash
mysql -u root -p nom_base < setup-sql.sql
mysql -u root -p nom_base < update-security.sql
mysql -u root -p nom_base < security-update.sql
mysql -u root -p nom_base < editor-database-update.sql
```

**Note** : Remplacez `nom_base` par le nom de votre base de données. Assurez-vous que l'utilisateur MySQL spécifié a les permissions nécessaires.

### 4. Configurer reCAPTCHA
Pour protéger vos formulaires contre les abus, configurez Google reCAPTCHA :
1. Rendez-vous sur [https://www.google.com/recaptcha/admin](https://www.google.com/recaptcha/admin) pour créer un site.
2. Sélectionnez **reCAPTCHA v3**.
3. Copiez les **clés publique et privée** fournies.
4. Collez ces clés dans le fichier `email-config.php` aux emplacements indiqués.

## 🤝 Contribuer
Nous accueillons les contributions ! Si vous souhaitez améliorer Zenu :
1. Forkez le dépôt.
2. Créez une branche pour votre fonctionnalité (`git checkout -b feature/nouvelle-fonction`).
3. Commitez vos modifications (`git commit -m "Ajout de nouvelle fonctionnalité"`).
4. Poussez votre branche (`git push origin feature/nouvelle-fonction`).
5. Ouvrez une Pull Request sur GitHub.

Pour signaler des bugs ou suggérer des idées, ouvrez une issue sur [GitHub](https://github.com/AlexAub/zenu/issues).

## 📜 Licence
Ce projet est sous licence **MIT**. Consultez le fichier [LICENSE](LICENSE) pour plus de détails.

---

*Zenu - Conçu pour la simplicité et l'efficacité.* ✨
