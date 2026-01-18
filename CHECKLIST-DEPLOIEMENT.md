# ✅ CHECKLIST DE DÉPLOIEMENT - CLICOM CRM

**Cochez chaque case au fur et à mesure que vous avancez.**

---

## 📦 **PRÉPARATION (à faire une seule fois)**

- [ ] J'ai un compte Hostinger actif
- [ ] J'ai un nom de domaine (ex: clicom.ch)
- [ ] Je peux me connecter à mon tableau de bord Hostinger
- [ ] J'ai téléchargé tous les fichiers du projet depuis GitHub

---

## 🗄️ **PARTIE 1 : BASE DE DONNÉES (10 minutes)**

### Créer la base de données
- [ ] Je suis allé dans "Bases de données MySQL" sur Hostinger
- [ ] J'ai cliqué sur "Créer une nouvelle base de données"
- [ ] J'ai donné un nom : `clicom_crm`
- [ ] J'ai créé un utilisateur : `clicom_user`
- [ ] J'ai généré un mot de passe fort
- [ ] ⚠️ **J'AI COPIÉ LE MOT DE PASSE QUELQUE PART** (notepad, papier)

### Importer le schéma
- [ ] J'ai cliqué sur "phpMyAdmin" à côté de ma base de données
- [ ] J'ai sélectionné ma base de données dans le menu de gauche
- [ ] J'ai cliqué sur l'onglet "Importer"
- [ ] J'ai sélectionné le fichier `schema.sql`
- [ ] J'ai cliqué sur "Exécuter"
- [ ] ✅ J'ai vu le message "Importation réussie"

---

## 🔧 **PARTIE 2 : BACKEND (API) (15 minutes)**

### Créer les dossiers
- [ ] Je suis allé dans "Gestionnaire de fichiers"
- [ ] J'ai navigué vers `public_html`
- [ ] J'ai créé un dossier `api`
- [ ] Dans `api`, j'ai créé un sous-dossier `api`

### Uploader les fichiers PHP
- [ ] J'ai uploadé dans `public_html/api/` :
  - [ ] `config.php`
- [ ] J'ai uploadé dans `public_html/api/api/` :
  - [ ] `auth.php`
  - [ ] `contact.php`
  - [ ] `clients.php`
  - [ ] `dashboard.php`
  - [ ] `invoices.php`
  - [ ] `projects.php`
  - [ ] `tasks.php`

### Configurer config.php
- [ ] J'ai ouvert `config.php` en édition
- [ ] J'ai remplacé le nom de la base de données
- [ ] J'ai remplacé le nom d'utilisateur
- [ ] J'ai collé le mot de passe de la base de données
- [ ] J'ai remplacé `allowed_origins` avec mon vrai domaine
- [ ] J'ai enregistré les modifications

### Créer le sous-domaine
- [ ] Je suis allé dans "Domaines" → "Sous-domaines"
- [ ] J'ai créé le sous-domaine `api`
- [ ] Document Root : `public_html/api`
- [ ] J'ai cliqué sur "Créer"

---

## 💻 **PARTIE 3 : FRONTEND (Interface) (10 minutes)**

### Créer les dossiers
- [ ] Dans `public_html`, j'ai créé un dossier `app`
- [ ] Dans `app`, j'ai créé un dossier `js`
- [ ] Dans `app`, j'ai créé un dossier `assets`

### Uploader les fichiers HTML
- [ ] J'ai uploadé dans `public_html/app/` :
  - [ ] `index.html`
  - [ ] `login.html`
  - [ ] `clients.html`
  - [ ] `projects.html`
  - [ ] `tasks.html`
  - [ ] `invoices.html`

### Uploader les fichiers JavaScript
- [ ] J'ai uploadé dans `public_html/app/js/` :
  - [ ] `config.js`
  - [ ] `apiClient.js`

### Uploader les fichiers CSS
- [ ] J'ai uploadé dans `public_html/app/assets/` :
  - [ ] `dashboard.css`

### Configurer config.js
- [ ] J'ai ouvert `config.js` en édition
- [ ] J'ai remplacé `API_BASE_URL` avec `https://api.mondomaine.ch`
- [ ] J'ai enregistré les modifications

---

## 🔒 **PARTIE 4 : SÉCURITÉ (5 minutes)**

### Activer SSL (HTTPS)
- [ ] Je suis allé dans "SSL" → "Certificats SSL"
- [ ] J'ai activé le SSL gratuit pour :
  - [ ] `mondomaine.ch`
  - [ ] `www.mondomaine.ch`
  - [ ] `api.mondomaine.ch`
- [ ] J'ai attendu 5-10 minutes

---

## ✅ **PARTIE 5 : TESTS (5 minutes)**

### Test 1 : API fonctionne
- [ ] J'ai visité : `https://api.mondomaine.ch/api/auth.php`
- [ ] ✅ J'ai vu du JSON avec `"authenticated":false`

### Test 2 : Login fonctionne
- [ ] J'ai visité : `https://www.mondomaine.ch/app/login.html`
- [ ] J'ai entré : `admin@clicom.ch` / `clicom2024`
- [ ] ✅ J'ai été redirigé vers le Dashboard

### Test 3 : Dashboard affiche
- [ ] ✅ Je vois le dashboard avec les statistiques (même si à 0)

### Test 4 : Créer un client
- [ ] J'ai cliqué sur "Clients"
- [ ] J'ai cliqué sur "+ Nouveau client"
- [ ] J'ai rempli le formulaire
- [ ] ✅ Le client apparaît dans la liste

---

## 🎉 **FINITIONS (5 minutes)**

### Changer le mot de passe admin
- [ ] Je suis allé sur https://bcrypt-generator.com/
- [ ] J'ai entré mon nouveau mot de passe (fort !)
- [ ] Coût : 12
- [ ] J'ai copié le hash généré
- [ ] Je suis allé dans phpMyAdmin
- [ ] J'ai ouvert la table `users`
- [ ] J'ai modifié la ligne `admin@clicom.ch`
- [ ] J'ai collé le nouveau hash dans `password_hash`
- [ ] ✅ J'ai enregistré

### Test final
- [ ] Je me suis déconnecté
- [ ] ✅ Je peux me reconnecter avec mon NOUVEAU mot de passe

---

## 🏆 **FÉLICITATIONS !**

Si toutes les cases sont cochées, votre CRM est déployé et prêt à l'emploi !

### Prochaines étapes :
1. Créer vos vrais clients
2. Créer vos projets
3. Créer vos tâches
4. Explorer toutes les fonctionnalités

---

## 📞 **BESOIN D'AIDE ?**

Si une étape ne fonctionne pas :
1. Notez le numéro de l'étape qui bloque
2. Faites un screenshot de l'erreur
3. Contactez-moi avec ces informations

**Votre CRM est maintenant opérationnel ! 🎊**
