# Guide de déploiement CLICOM CRM

Ce guide vous explique comment déployer CLICOM CRM sur Hostinger étape par étape.

## 📋 Prérequis

- Compte Hostinger avec accès cPanel
- Accès FTP/SFTP ou gestionnaire de fichiers
- Accès phpMyAdmin pour la base de données
- Domaine configuré (ex: clicom.ch)

---

## 🚀 Déploiement en 8 étapes

### Étape 1 : Préparer les fichiers localement

```bash
# Cloner le projet (si pas déjà fait)
git clone https://github.com/SwissEliteVan/Clicom-Site-CRM.git
cd Clicom-Site-CRM

# Vérifier que tous les fichiers sont à jour
git pull origin main
```

### Étape 2 : Configurer la base de données

1. Connectez-vous à **cPanel Hostinger**
2. Allez dans **MySQL® Databases**
3. Créez une nouvelle base de données :
   - Nom : `u123456789_clicom` (Hostinger ajoute un préfixe)
   - Notez le nom exact

4. Créez un utilisateur :
   - Nom : `u123456789_clicom`
   - Mot de passe : Générez un mot de passe fort (notez-le !)

5. Associez l'utilisateur à la base de données avec **ALL PRIVILEGES**

6. Allez dans **phpMyAdmin**
7. Sélectionnez votre base de données
8. Cliquez sur **Import**
9. Uploadez le fichier `schema.sql`
10. Cliquez sur **Go**

✅ Vérifiez que 15 tables ont été créées

### Étape 3 : Configurer le sous-domaine API

1. Dans cPanel, allez dans **Domains → Subdomains**
2. Créez un nouveau sous-domaine :
   - **Subdomain** : `api`
   - **Domain** : `clicom.ch`
   - **Document Root** : `public_html/api`
3. Cliquez sur **Create**

✅ Le sous-domaine `api.clicom.ch` est maintenant créé

### Étape 4 : Uploader les fichiers

**Via FTP (FileZilla recommandé)**

1. Connectez-vous via FTP avec les identifiants Hostinger
2. Uploadez les fichiers :

```
local: public/*               → remote: public_html/
local: backend/config.php     → remote: public_html/api/config.php
local: backend/api/*          → remote: public_html/api/api/
```

**Via gestionnaire de fichiers Hostinger**

1. Allez dans **File Manager**
2. Naviguez vers `public_html/`
3. Uploadez `public.zip` et décompressez
4. Uploadez `backend.zip` dans `api/` et décompressez

### Étape 5 : Configurer le backend

Éditez `public_html/api/config.php` :

```php
<?php

declare(strict_types=1);

$CONFIG = [
    'db' => [
        'host' => 'localhost',
        'name' => 'u123456789_clicom',      // ⚠️ Nom exact de votre BDD
        'user' => 'u123456789_clicom',      // ⚠️ Nom exact de l'utilisateur
        'pass' => 'VOTRE_MOT_DE_PASSE_BDD', // ⚠️ Mot de passe généré
        'charset' => 'utf8mb4',
    ],
    'cors' => [
        'allowed_origins' => [
            'https://www.clicom.ch',
            'https://clicom.ch',
        ],
    ],
    'security' => [
        'session_name' => 'clicom_session',
        'csrf_key' => 'clicom_csrf',
        'lockout_attempts' => 5,
        'lockout_minutes' => 15,
        'rate_limit_per_minute' => 5,
    ],
];

// ... reste du fichier inchangé
```

### Étape 6 : Configurer le frontend

Éditez `public_html/app/js/config.js` :

```javascript
const CONFIG = {
  // ⚠️ Changez ceci en production
  API_BASE_URL: 'https://api.clicom.ch',

  // ... reste inchangé
};
```

### Étape 7 : Activer HTTPS (SSL)

1. Dans cPanel, allez dans **SSL/TLS Status**
2. Activez le **SSL gratuit Let's Encrypt** pour :
   - ☑ `clicom.ch`
   - ☑ `www.clicom.ch`
   - ☑ `api.clicom.ch`
3. Attendez 5-10 minutes pour la propagation

### Étape 8 : Forcer HTTPS avec .htaccess

Créez/éditez `public_html/.htaccess` :

```apache
# Rediriger HTTP → HTTPS
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Rediriger www vers non-www (optionnel)
RewriteCond %{HTTP_HOST} ^www\.(.*)$ [NC]
RewriteRule ^(.*)$ https://%1/$1 [R=301,L]
```

Créez `public_html/api/.htaccess` :

```apache
# Forcer HTTPS pour l'API
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Headers de sécurité
Header set X-Content-Type-Options "nosniff"
Header set X-Frame-Options "DENY"
Header set X-XSS-Protection "1; mode=block"
```

---

## ✅ Vérification du déploiement

### Test 1 : Site vitrine

Visitez : `https://www.clicom.ch`

✅ Vous devriez voir la page de sélection de langue

### Test 2 : API Backend

Visitez : `https://api.clicom.ch/api/auth.php`

✅ Vous devriez voir du JSON :

```json
{
  "authenticated": false,
  "csrf_token": "abc123..."
}
```

### Test 3 : Dashboard CRM

Visitez : `https://www.clicom.ch/app/login.html`

✅ Connectez-vous avec :
- Email : `admin@clicom.ch`
- Mot de passe : `clicom2024`

### Test 4 : Formulaire de contact

1. Allez sur `https://www.clicom.ch/fr/`
2. Remplissez le formulaire de contact (si présent)
3. Vérifiez dans le dashboard CRM que le client a été créé

---

## 🔒 Sécurité post-déploiement

### ⚠️ ACTIONS OBLIGATOIRES

1. **Changer le mot de passe admin**

Connectez-vous à phpMyAdmin et exécutez :

```sql
UPDATE users
SET password_hash = '$2y$12$NOUVEAU_HASH_ICI'
WHERE email = 'admin@clicom.ch';
```

Pour générer le hash, utilisez ce script PHP :

```php
<?php
echo password_hash('VotreNouveauMotDePasse', PASSWORD_BCRYPT, ['cost' => 12]);
?>
```

2. **Restreindre les permissions de fichiers**

Via FTP ou SSH :

```bash
chmod 644 api/config.php
chmod 755 api/
```

3. **Vérifier les logs**

Consultez régulièrement :
- Logs d'erreur PHP (cPanel → Errors)
- Table `activity_log` dans la BDD

---

## 🐛 Dépannage

### Erreur "CORS policy: No 'Access-Control-Allow-Origin' header"

**Solution** : Vérifiez que `config.php` contient bien votre domaine dans `allowed_origins`

### Erreur "Connection refused" à l'API

**Solution** : Vérifiez que le sous-domaine `api.clicom.ch` pointe bien vers `public_html/api/`

### Erreur "Could not connect to database"

**Solution** :
1. Vérifiez les identifiants dans `config.php`
2. Vérifiez que l'utilisateur a les permissions sur la BDD
3. Vérifiez que `localhost` est correct (parfois c'est `127.0.0.1`)

### Page blanche au login

**Solution** :
1. Activez les erreurs PHP temporairement dans `config.php` :
   ```php
   error_reporting(E_ALL);
   ini_set('display_errors', 1);
   ```
2. Consultez les logs PHP dans cPanel

### Session ne persiste pas

**Solution** :
1. Vérifiez que les cookies sont autorisés
2. Vérifiez que HTTPS est actif (les cookies `secure` nécessitent HTTPS)

---

## 📊 Monitoring

### Logs à surveiller

1. **Activity log** (dans la BDD)
   - Connexions suspectes
   - Tentatives de login échouées

2. **Error logs** (cPanel)
   - Erreurs PHP
   - Erreurs MySQL

3. **Access logs** (cPanel)
   - Trafic suspect
   - Attaques potentielles

### Sauvegarde

**Configurer des backups automatiques** :

1. cPanel → Backup Wizard
2. Configurez :
   - Backup quotidien de la BDD
   - Backup hebdomadaire des fichiers

**Backup manuel** :

```bash
# Base de données
mysqldump -u u123456789_clicom -p u123456789_clicom > backup_$(date +%Y%m%d).sql

# Fichiers
tar -czf backup_files_$(date +%Y%m%d).tar.gz public_html/
```

---

## 📞 Support

En cas de problème :

1. Consultez ce guide
2. Consultez le `README.md`
3. Vérifiez les logs d'erreur
4. Contactez le support Hostinger si nécessaire

---

**Bonne mise en production !** 🚀
