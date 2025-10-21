🚀 Zenu - Outils Simples et Zen
Bienvenue dans Zenu, une collection d'outils simples et intuitifs pour simplifier vos tâches quotidiennes. Ce projet est conçu pour être facile à configurer et à utiliser, tout en offrant une expérience fluide et efficace. 🌟
📋 Table des matières

Installation
Prérequis
Configuration
Contribuer
Licence

🛠 Prérequis
Avant de commencer, assurez-vous d'avoir les éléments suivants installés :

PHP (>= 7.4 recommandé)
MySQL ou un autre système de gestion de base de données compatible
Git pour cloner le dépôt
Un compte Google pour configurer reCAPTCHA

🚀 Installation
1. Cloner le dépôt
Clonez le dépôt GitHub et naviguez dans le dossier du projet :
git clone https://github.com/AlexAub/zenu.git
cd zenu

2. Configurer les fichiers sensibles
Les fichiers de configuration doivent être copiés à partir des exemples fournis et remplis avec vos informations.
Copier les fichiers d'exemple :
cp config-example.php config.php
cp email-config.example.php email-config.php

Éditer les fichiers de configuration :

config.php : Ajoutez vos identifiants de base de données (nom d'utilisateur, mot de passe, nom de la base, etc.).
email-config.php : Configurez les paramètres d'email (SMTP, etc.) et les clés reCAPTCHA.

3. Configurer la base de données
Importez les fichiers SQL pour créer et mettre à jour la structure de la base de données.
Importer les fichiers SQL :
mysql -u root -p nom_base < setup-sql.sql
mysql -u root -p nom_base < update-security.sql
mysql -u root -p nom_base < security-update.sql

Note : Remplacez nom_base par le nom de votre base de données. Assurez-vous que l'utilisateur MySQL spécifié a les permissions nécessaires.
4. Configurer reCAPTCHA
Pour protéger vos formulaires contre les abus, configurez Google reCAPTCHA :

Rendez-vous sur https://www.google.com/recaptcha/admin pour créer un site.
Sélectionnez reCAPTCHA v2 ou v3 selon vos besoins.
Copiez les clés publique et privée fournies.
Collez ces clés dans le fichier email-config.php aux emplacements indiqués.

🤝 Contribuer
Nous accueillons les contributions ! Si vous souhaitez améliorer Zenu :

Forkez le dépôt.
Créez une branche pour votre fonctionnalité (git checkout -b feature/nouvelle-fonction).
Commitez vos modifications (git commit -m "Ajout de nouvelle fonctionnalité").
Poussez votre branche (git push origin feature/nouvelle-fonction).
Ouvrez une Pull Request sur GitHub.

Pour signaler des bugs ou suggérer des idées, ouvrez une issue sur GitHub.
📜 Licence
Ce projet est sous licence MIT. Consultez le fichier LICENSE pour plus de détails.

Zenu - Conçu pour la simplicité et l'efficacité. ✨
