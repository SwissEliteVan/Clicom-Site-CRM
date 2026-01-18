# 🚀 Configuration pour Hostinger - Guide Rapide

## ÉTAPE 1 : Récupérez vos identifiants MySQL

1. Connectez-vous à **cPanel Hostinger**
2. Cherchez **"Bases de données MySQL"** ou **"MySQL Databases"**
3. Vous verrez :
   - **Base de données actuelle** (exemple : `u123456789_clicom`)
   - **Utilisateurs** (exemple : `u123456789_admin`)

⚠️ **Notez le nom COMPLET** avec le préfixe `u123456789_`

---

## ÉTAPE 2 : Modifiez /backend/config.php

Ouvrez le fichier `/backend/config.php` et **remplacez les lignes 7 à 10** :

### ❌ AVANT (à remplacer) :
```php
'db' => [
    'host' => '127.0.0.1',
    'name' => 'clicom_crm',
    'user' => 'clicom_user',
    'pass' => 'change_me',
    'charset' => 'utf8mb4',
],
```

### ✅ APRÈS (avec VOS identifiants) :
```php
'db' => [
    'host' => 'localhost',                    // Sur Hostinger c'est toujours 'localhost'
    'name' => 'u123456789_clicom',            // ⚠️ REMPLACEZ par VOTRE nom de base
    'user' => 'u123456789_admin',             // ⚠️ REMPLACEZ par VOTRE utilisateur
    'pass' => 'VotreMot2PasseMySQL',          // ⚠️ REMPLACEZ par VOTRE mot de passe
    'charset' => 'utf8mb4',
],
```

**Exemple concret** :
Si dans cPanel vous voyez :
- Base : `u987654321_mycrm`
- User : `u987654321_user`
- Pass : `MonMotDePasse123!`

Alors mettez :
```php
'db' => [
    'host' => 'localhost',
    'name' => 'u987654321_mycrm',
    'user' => 'u987654321_user',
    'pass' => 'MonMotDePasse123!',
    'charset' => 'utf8mb4',
],
```

⚠️ **N'oubliez pas les guillemets simples** autour des valeurs !

---

## ÉTAPE 3 : Téléchargez les fichiers sur Hostinger

Via **File Manager** dans cPanel :

1. Uploadez TOUT le dossier `/backend` vers votre serveur
2. Uploadez le dossier `/public`
3. Uploadez le fichier `schema.sql`

**Structure finale sur le serveur** :
```
public_html/
├── backend/
│   ├── config.php         ← Avec VOS identifiants
│   ├── test-db.php
│   ├── create-user.php
│   └── api/
│       ├── auth.php
│       ├── clients.php
│       ├── dashboard.php
│       ├── projects.php
│       └── tasks.php
├── public/
│   └── app/
│       ├── login.html
│       ├── index.html
│       ├── clients.html
│       ├── projects.html
│       └── tasks.html
└── schema.sql
```

---

## ÉTAPE 4 : Importez schema.sql dans phpMyAdmin

1. Dans **cPanel**, cliquez sur **phpMyAdmin**
2. Dans la colonne de gauche, **sélectionnez votre base de données** (ex: `u123456789_clicom`)
3. Cliquez sur l'onglet **"Importer"** (en haut)
4. Cliquez sur **"Choisir un fichier"**
5. Sélectionnez le fichier **`schema.sql`** que vous avez uploadé
6. Descendez en bas et cliquez sur **"Exécuter"**

✅ Vous devriez voir : **"Importation réussie"**

Cela va créer :
- 15 tables (users, clients, projects, tasks, invoices, etc.)
- 1 utilisateur admin par défaut : `admin@clicom.ch` / `admin123`

---

## ÉTAPE 5 : Testez la connexion

Ouvrez dans votre navigateur :

```
https://votre-domaine.com/backend/test-db.php
```

Remplacez `votre-domaine.com` par votre vrai domaine Hostinger.

### ✅ Si vous voyez "Connexion réussie" :
- Nombre de tables : 15
- Nombre d'utilisateurs : 1

**Parfait ! Passez à l'ÉTAPE 6**

### ❌ Si vous voyez une erreur :
Vérifiez que :
1. Le nom de la base dans `config.php` est correct
2. Le nom d'utilisateur est correct
3. Le mot de passe est correct
4. Vous avez bien importé `schema.sql`

---

## ÉTAPE 6 : Connectez-vous au CRM

Allez sur :

```
https://votre-domaine.com/public/app/login.html
```

**Identifiants par défaut** :
- Email : `admin@clicom.ch`
- Mot de passe : `admin123`

✅ **Si ça fonctionne** : Vous êtes connecté ! 🎉

---

## 🔐 SÉCURITÉ IMPORTANTE

Une fois connecté :

1. **Changez votre mot de passe** immédiatement
2. **Supprimez ces fichiers du serveur** :
   - `/backend/test-db.php`
   - `/backend/create-user.php`
   - `/schema.sql` (si vous l'avez uploadé dans public_html)

---

## 🆘 Problèmes courants sur Hostinger

### Erreur : "Access denied for user"
➡️ Le mot de passe dans `config.php` est incorrect

### Erreur : "Unknown database"
➡️ Le nom de la base dans `config.php` est incorrect (oubli du préfixe ?)

### Erreur : CORS / Impossible de charger les données
➡️ Modifiez les lignes 14-17 de `config.php` :

```php
'cors' => [
    'allowed_origins' => [
        'https://votre-domaine.com',  // Votre domaine Hostinger
        'http://localhost',           // Pour tests locaux
    ],
],
```

### La page login.html ne charge pas le CSS
➡️ Vérifiez que le chemin dans `login.html` est correct :
```html
<link rel="stylesheet" href="assets/dashboard.css">
```

---

## 📝 Checklist finale

- [ ] J'ai récupéré mes identifiants MySQL dans cPanel
- [ ] J'ai modifié `/backend/config.php` avec mes vrais identifiants
- [ ] J'ai uploadé tous les fichiers sur Hostinger
- [ ] J'ai importé `schema.sql` dans phpMyAdmin
- [ ] J'ai testé avec `test-db.php` → ✅ Connexion réussie
- [ ] Je peux me connecter au CRM avec `admin@clicom.ch` / `admin123`
- [ ] J'ai changé mon mot de passe
- [ ] J'ai supprimé `test-db.php` et `create-user.php`

---

**Besoin d'aide ? Dites-moi à quelle étape vous bloquez !**
