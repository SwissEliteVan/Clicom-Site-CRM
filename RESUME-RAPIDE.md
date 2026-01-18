# ⚡ RÉSUMÉ ULTRA-RAPIDE - Déployer en 5 étapes

**Pour ceux qui veulent aller vite (mais lisez le guide complet si vous êtes débutant !)**

---

## 📌 **LES 5 ÉTAPES ESSENTIELLES**

### 1️⃣ **BASE DE DONNÉES** (Hostinger → MySQL)
```
Créer BDD : clicom_crm
Créer utilisateur : clicom_user
Importer : schema.sql via phpMyAdmin
```

### 2️⃣ **BACKEND** (Upload dans public_html/api/)
```
Upload dans api/ :
  - config.php (éditer avec infos BDD)

Upload dans api/api/ :
  - auth.php, contact.php, clients.php
  - dashboard.php, invoices.php, projects.php, tasks.php

Créer sous-domaine : api.votredomaine.ch → public_html/api
```

### 3️⃣ **FRONTEND** (Upload dans public_html/app/)
```
Upload dans app/ :
  - index.html, login.html, clients.html
  - projects.html, tasks.html, invoices.html

Upload dans app/js/ :
  - config.js (éditer avec URL API)
  - apiClient.js

Upload dans app/assets/ :
  - dashboard.css
```

### 4️⃣ **SSL** (Hostinger → SSL)
```
Activer SSL gratuit pour :
  - votredomaine.ch
  - www.votredomaine.ch
  - api.votredomaine.ch
```

### 5️⃣ **TEST**
```
Visiter : https://www.votredomaine.ch/app/login.html
Login : admin@clicom.ch / clicom2024
✅ Ça marche !
```

---

## 📁 **STRUCTURE DES FICHIERS SUR HOSTINGER**

```
public_html/
├── api/
│   ├── config.php              ← Éditer avec infos BDD
│   └── api/
│       ├── auth.php
│       ├── contact.php
│       ├── clients.php
│       ├── dashboard.php
│       ├── invoices.php
│       ├── projects.php
│       └── tasks.php
│
└── app/
    ├── index.html
    ├── login.html
    ├── clients.html
    ├── projects.html
    ├── tasks.html
    ├── invoices.html
    ├── js/
    │   ├── config.js           ← Éditer avec URL API
    │   └── apiClient.js
    └── assets/
        └── dashboard.css
```

---

## ⚙️ **FICHIERS À ÉDITER (2 fichiers seulement)**

### **1. public_html/api/config.php**
```php
'db' => [
    'host' => 'localhost',
    'name' => 'VOTRE_NOM_BDD',           // ⚠️
    'user' => 'VOTRE_USER_BDD',          // ⚠️
    'pass' => 'VOTRE_MOT_DE_PASSE_BDD',  // ⚠️
    'charset' => 'utf8mb4',
],
'allowed_origins' => [
    'https://www.votredomaine.ch',       // ⚠️
    'https://votredomaine.ch',           // ⚠️
],
```

### **2. public_html/app/js/config.js**
```javascript
API_BASE_URL: 'https://api.votredomaine.ch',  // ⚠️
```

---

## ✅ **VÉRIFICATIONS RAPIDES**

| Test | URL | Résultat attendu |
|------|-----|------------------|
| API | `https://api.votredomaine.ch/api/auth.php` | JSON visible |
| Login | `https://www.votredomaine.ch/app/login.html` | Page de login |
| Dashboard | Après login | Stats à 0 |

---

## 🆘 **PROBLÈME ?**

| Erreur | Solution |
|--------|----------|
| "Could not connect to database" | Vérifier `config.php` (mot de passe BDD) |
| "CORS policy" | Vérifier `allowed_origins` dans `config.php` |
| Page blanche | Vérifier que tous les fichiers PHP sont uploadés |
| Login ne marche pas | Vérifier que `schema.sql` a été importé |

---

**C'est tout ! Votre CRM est déployé en 5 étapes.**

Pour plus de détails → Lisez `GUIDE-DEBUTANT.md`
