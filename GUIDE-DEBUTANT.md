# 🚀 GUIDE DÉBUTANT - Déployer CLICOM CRM sur Hostinger

**Ce guide est conçu pour les débutants sans connaissances techniques.**

---

## 📋 **CE QUE VOUS AVEZ DÉJÀ**

✅ Un CRM complet et fonctionnel
✅ Tous les fichiers prêts à déployer
✅ Une base de données MySQL configurée

---

## 🎯 **ÉTAPES SIMPLES POUR DÉPLOYER**

### **ÉTAPE 1 : Se connecter à Hostinger**

1. Allez sur [https://hostinger.com](https://hostinger.com)
2. Cliquez sur "Connexion"
3. Entrez vos identifiants

---

### **ÉTAPE 2 : Créer la base de données**

1. Dans le tableau de bord Hostinger, cherchez **"Bases de données MySQL"**
2. Cliquez sur **"Créer une nouvelle base de données"**
3. Remplissez :
   - **Nom de la base** : `clicom_crm` (ou autre nom)
   - **Nom d'utilisateur** : `clicom_user`
   - **Mot de passe** : Cliquez sur "Générer" (copiez-le quelque part !)
4. Cliquez sur **"Créer"**

✅ **Résultat** : Vous avez maintenant une base de données vide

---

### **ÉTAPE 3 : Importer le schéma de la base de données**

1. Restez dans "Bases de données MySQL"
2. À côté de votre base de données, cliquez sur **"phpMyAdmin"**
3. Une nouvelle fenêtre s'ouvre
4. Dans le menu de gauche, cliquez sur le nom de votre base de données
5. Cliquez sur l'onglet **"Importer"** en haut
6. Cliquez sur **"Choisir un fichier"**
7. Sélectionnez le fichier `schema.sql` (il est dans votre dépôt GitHub)
8. Cliquez sur **"Exécuter"** en bas

✅ **Résultat** : Votre base de données contient maintenant toutes les tables nécessaires

---

### **ÉTAPE 4 : Uploader les fichiers Backend (API)**

1. Retournez au tableau de bord Hostinger
2. Cherchez **"Gestionnaire de fichiers"** et cliquez dessus
3. Naviguez vers le dossier `public_html`
4. Créez un nouveau dossier nommé `api` :
   - Clic droit → Nouveau dossier → Nommez-le `api`
5. Entrez dans le dossier `api`
6. Créez un sous-dossier nommé `api` (oui, encore !)
7. Uploadez ces fichiers dans `public_html/api/api/` :
   - `clients.php`
   - `dashboard.php`
   - `invoices.php`
   - `projects.php`
   - `tasks.php`
   - `auth.php` (déjà existant)
   - `contact.php` (déjà existant)
8. Uploadez `config.php` dans `public_html/api/`

**Comment uploader ?**
- Cliquez sur **"Upload"** en haut
- Glissez vos fichiers ou cliquez pour les sélectionner
- Attendez que l'upload se termine

✅ **Résultat** : Votre API est maintenant sur le serveur

---

### **ÉTAPE 5 : Configurer l'API (fichier config.php)**

1. Dans le gestionnaire de fichiers, naviguez vers `public_html/api/`
2. Clic droit sur `config.php` → **"Modifier"**
3. Trouvez la section base de données (ligne 8-14 environ)
4. Remplacez avec vos informations :

```php
'db' => [
    'host' => 'localhost',
    'name' => 'clicom_crm',           // ⚠️ Le nom exact de votre BDD
    'user' => 'clicom_user',          // ⚠️ Le nom exact de l'utilisateur
    'pass' => 'COLLEZ_ICI_LE_MOT_DE_PASSE',  // ⚠️ Le mot de passe généré
    'charset' => 'utf8mb4',
],
```

5. Trouvez la section CORS (ligne 17 environ)
6. Remplacez avec votre domaine :

```php
'allowed_origins' => [
    'https://www.votredomaine.ch',    // ⚠️ Votre vrai domaine
    'https://votredomaine.ch',
],
```

7. Cliquez sur **"Enregistrer les modifications"**

✅ **Résultat** : L'API peut maintenant se connecter à votre base de données

---

### **ÉTAPE 6 : Créer le sous-domaine API**

1. Dans Hostinger, cherchez **"Domaines"**
2. Cliquez sur **"Sous-domaines"**
3. Cliquez sur **"Créer un sous-domaine"**
4. Remplissez :
   - **Sous-domaine** : `api`
   - **Domaine** : Sélectionnez votre domaine (ex: `clicom.ch`)
   - **Document Root** : `public_html/api`
5. Cliquez sur **"Créer"**

✅ **Résultat** : Votre API est accessible à `https://api.votredomaine.ch`

---

### **ÉTAPE 7 : Uploader les fichiers Frontend (Interface)**

1. Retournez au gestionnaire de fichiers
2. Naviguez vers `public_html/`
3. Créez un dossier nommé `app` si il n'existe pas déjà
4. Uploadez ces fichiers dans `public_html/app/` :
   - `index.html` (Dashboard)
   - `login.html`
   - `clients.html`
   - `projects.html`
   - `tasks.html`
   - `invoices.html`
5. Créez un dossier `js` dans `app/`
6. Uploadez dans `public_html/app/js/` :
   - `config.js`
   - `apiClient.js`
7. Créez un dossier `assets` dans `app/`
8. Uploadez dans `public_html/app/assets/` :
   - `dashboard.css`

✅ **Résultat** : Votre interface est maintenant sur le serveur

---

### **ÉTAPE 8 : Configurer le Frontend (fichier config.js)**

1. Dans le gestionnaire de fichiers, naviguez vers `public_html/app/js/`
2. Clic droit sur `config.js` → **"Modifier"**
3. Trouvez la ligne avec `API_BASE_URL` (ligne 14 environ)
4. Remplacez avec :

```javascript
API_BASE_URL: 'https://api.votredomaine.ch',  // ⚠️ Votre vrai domaine
```

5. Cliquez sur **"Enregistrer les modifications"**

✅ **Résultat** : Votre frontend peut maintenant communiquer avec l'API

---

### **ÉTAPE 9 : Activer HTTPS (SSL)**

1. Dans Hostinger, cherchez **"SSL"**
2. Cliquez sur **"Certificats SSL"**
3. Pour chaque domaine, activez **"SSL gratuit Let's Encrypt"** :
   - ☑️ `votredomaine.ch`
   - ☑️ `www.votredomaine.ch`
   - ☑️ `api.votredomaine.ch`
4. Attendez 5-10 minutes pour l'activation

✅ **Résultat** : Votre site est sécurisé avec HTTPS

---

### **ÉTAPE 10 : TESTER !**

1. Ouvrez votre navigateur
2. Allez sur : `https://www.votredomaine.ch/app/login.html`
3. Connectez-vous avec :
   - **Email** : `admin@clicom.ch`
   - **Mot de passe** : `clicom2024`
4. Vous devriez voir le Dashboard ! 🎉

---

## ✅ **VÉRIFICATIONS POST-DÉPLOIEMENT**

### Test 1 : API fonctionne ?
Visitez : `https://api.votredomaine.ch/api/auth.php`

✅ Vous devriez voir du JSON :
```json
{"authenticated":false,"csrf_token":"..."}
```

❌ Si erreur 404 : Vérifiez que les fichiers PHP sont bien dans `public_html/api/api/`

---

### Test 2 : Base de données connectée ?
Si le login ne fonctionne pas :
1. Vérifiez le fichier `config.php`
2. Assurez-vous que le mot de passe est correct
3. Vérifiez que la base de données contient la table `users`

---

### Test 3 : Dashboard affiche des données ?
Si le dashboard est vide :
- C'est normal ! Il n'y a pas encore de données
- Créez un client via "Clients" → "+ Nouveau client"
- Retournez au dashboard, les stats vont s'afficher

---

## 🆘 **PROBLÈMES FRÉQUENTS**

### Erreur "Could not connect to database"
**Solution** : Le mot de passe dans `config.php` est incorrect
1. Allez dans phpMyAdmin
2. Réinitialisez le mot de passe de l'utilisateur
3. Mettez à jour `config.php`

---

### Erreur "CORS policy: No 'Access-Control-Allow-Origin'"
**Solution** : Le domaine n'est pas autorisé dans `config.php`
1. Éditez `public_html/api/config.php`
2. Ajoutez votre domaine exact dans `allowed_origins`

---

### Page blanche
**Solution** : Erreur PHP
1. Activez les erreurs dans `config.php` :
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```
2. Rechargez la page pour voir l'erreur
3. Contactez-moi avec le message d'erreur

---

### Le login ne fonctionne pas
**Solution** : Vérifiez que la table `users` existe
1. Allez dans phpMyAdmin
2. Sélectionnez votre base de données
3. Vérifiez qu'il y a une table `users` avec un enregistrement `admin@clicom.ch`
4. Si pas de données, ré-importez `schema.sql`

---

## 📞 **BESOIN D'AIDE ?**

Si vous êtes bloqué :
1. Notez l'étape où vous êtes bloqué
2. Notez le message d'erreur exact (screenshot si possible)
3. Contactez-moi avec ces informations

---

## 🎓 **PROCHAINES ÉTAPES (une fois déployé)**

1. **Changer le mot de passe admin** (IMPORTANT !)
2. **Créer vos premiers clients**
3. **Créer des projets**
4. **Créer des tâches**
5. **Explorer le dashboard**

---

## 🔐 **SÉCURITÉ - À FAIRE ABSOLUMENT**

### Changer le mot de passe admin

1. Connectez-vous à phpMyAdmin
2. Sélectionnez votre base de données
3. Cliquez sur la table `users`
4. Trouvez la ligne avec `admin@clicom.ch`
5. Cliquez sur "Modifier"
6. Dans le champ `password_hash`, collez ce nouveau hash :

Pour générer un hash :
- Utilisez ce site : https://bcrypt-generator.com/
- Entrez votre nouveau mot de passe
- Coût : 12
- Copiez le hash généré
- Collez-le dans phpMyAdmin

---

**🎉 BRAVO ! Votre CRM est maintenant déployé et fonctionnel !**

Pour toute question, n'hésitez pas à demander de l'aide.
