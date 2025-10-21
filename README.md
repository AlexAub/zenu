## 🚀 Installation

### 1. Cloner le repository
bash
git clone https://github.com/votre-username/zenu.git
cd zenu


### 2. Configurer les fichiers sensibles

**Copier les fichiers d'exemple :**
bash
cp config-example.php config.php
cp email-config.example.php email-config.php


**Éditer les fichiers et remplir vos vraies valeurs :**
- \`config.php\` : Identifiants base de données
- \`email-config.php\` : Configuration email et reCAPTCHA

### 3. Base de données
bash
# Importer la structure
mysql -u root -p nom_base < setup-sql.sql
mysql -u root -p nom_base < update-security.sql
mysql -u root -p nom_base < security-update.sql

### 4. Configuration reCAPTCHA
1. Créer un site sur https://www.google.com/recaptcha/admin
2. Copier les clés dans \`email-config.php\`
