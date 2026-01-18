# CLICOM CRM - Customer Relationship Management

![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![License](https://img.shields.io/badge/license-Proprietary-red.svg)
![PHP](https://img.shields.io/badge/PHP-8.1+-purple.svg)
![MySQL](https://img.shields.io/badge/MySQL-8.0+-orange.svg)

Solution CRM complète développée en PHP/MySQL avec interface web moderne. Conçue pour les agences digitales suisses avec conformité LPD (Loi sur la Protection des Données).

## 📋 Table des matières

- [Vue d'ensemble](#-vue-densemble)
- [Architecture](#-architecture)
- [Fonctionnalités](#-fonctionnalités)
- [Installation locale](#-installation-locale)
- [Déploiement sur Hostinger](#-déploiement-sur-hostinger)
- [Structure du projet](#-structure-du-projet)
- [Sécurité](#-sécurité)
- [API Documentation](#-api-documentation)
- [Contribution](#-contribution)

---

## 🎯 Vue d'ensemble

CLICOM est un système CRM (Customer Relationship Management) complet avec :

- **Site vitrine multilingue** (FR, EN, DE, IT)
- **CRM Dashboard** sécurisé pour la gestion des clients
- **API REST PHP** avec authentification sécurisée
- **Base de données MySQL** avec triggers et vues automatisées
- **Design responsive** et interface moderne

### Captures d'écran

| Site vitrine | Dashboard CRM |
|--------------|---------------|
| Interface multilingue | Gestion clients sécurisée |

---

## 🏗 Architecture

Le projet suit une architecture **Frontend/Backend séparée** :

```
┌─────────────────────┐         ┌──────────────────────┐
│                     │         │                      │
│   SITE VITRINE      │         │   DASHBOARD CRM      │
│   www.clicom.ch     │         │   www.clicom.ch/app  │
│                     │         │                      │
│   - HTML/CSS/JS     │         │   - HTML/CSS/JS      │
│   - Multilingue     │         │   - Authentification │
│   - Responsive      │         │   - Gestion clients  │
│                     │         │                      │
└──────────┬──────────┘         └──────────┬───────────┘
           │                               │
           │    ┌─────────────────────────┘
           │    │
           ▼    ▼
    ┌─────────────────────┐
    │                     │
    │   API BACKEND       │
    │   api.clicom.ch     │
    │                     │
    │   - PHP 8.1+        │
    │   - REST API        │
    │   - CORS sécurisé   │
    │   - Sessions/CSRF   │
    │                     │
    └──────────┬──────────┘
               │
               ▼
    ┌─────────────────────┐
    │                     │
    │   BASE DE DONNÉES   │
    │   MySQL 8.0+        │
    │                     │
    │   - Triggers        │
    │   - Vues            │
    │   - Transactions    │
    │                     │
    └─────────────────────┘
```

### URLs de production

| Composant | URL | Hébergement |
|-----------|-----|-------------|
| Site vitrine | `https://www.clicom.ch` | Hostinger `public_html/` |
| Dashboard CRM | `https://www.clicom.ch/app` | Hostinger `public_html/app/` |
| API Backend | `https://api.clicom.ch` | Hostinger `public_html/api/` (sous-domaine) |

---

## ✨ Fonctionnalités

### Site Vitrine
- ✅ Multilingue (FR/EN/DE/IT)
- ✅ Design responsive premium
- ✅ Formulaire de contact avec protection anti-spam
- ✅ SEO optimisé
- ✅ Conformité RGPD/LPD

### Dashboard CRM
- ✅ **Authentification sécurisée** (sessions, CSRF, rate limiting)
- ✅ **Gestion des clients** (leads, actifs, inactifs)
- ✅ **Gestion des factures** (draft, sent, paid, overdue)
- ✅ **Gestion des projets** (planifiés, actifs, terminés)
- ✅ **Gestion des tâches** (todo, in progress, done)
- ✅ **Logs d'activité** pour audit
- ✅ **Portail client** avec tokens sécurisés
- ✅ **Automatisation** (règles personnalisées)

### API Backend
- ✅ RESTful API en PHP
- ✅ Authentification par session + CSRF token
- ✅ CORS configuré pour domaines autorisés
- ✅ Rate limiting (5 requêtes/minute)
- ✅ Account lockout après 5 tentatives
- ✅ Logs d'activité centralisés

---

## 💻 Installation locale

### Prérequis

- **PHP 8.1+** avec extensions : `pdo_mysql`, `mbstring`, `json`
- **MySQL 8.0+** ou MariaDB 10.5+
- **Serveur web** : Apache ou Nginx
- **Composer** (optionnel, pour dépendances futures)

### Étapes d'installation

1. **Cloner le dépôt**

```bash
git clone https://github.com/SwissEliteVan/Clicom-Site-CRM.git
cd Clicom-Site-CRM
```

2. **Configurer la base de données**

```bash
# Se connecter à MySQL
mysql -u root -p

# Créer la base de données et l'utilisateur
CREATE DATABASE clicom_crm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'clicom_user'@'localhost' IDENTIFIED BY 'votre_mot_de_passe_securise';
GRANT ALL PRIVILEGES ON clicom_crm.* TO 'clicom_user'@'localhost';
FLUSH PRIVILEGES;

# Importer le schéma
USE clicom_crm;
SOURCE schema.sql;
```

3. **Configurer le backend**

Éditez `backend/config.php` :

```php
$CONFIG = [
    'db' => [
        'host' => '127.0.0.1',
        'name' => 'clicom_crm',
        'user' => 'clicom_user',
        'pass' => 'votre_mot_de_passe_securise', // ⚠️ Changez ceci !
        'charset' => 'utf8mb4',
    ],
    'cors' => [
        'allowed_origins' => [
            'http://localhost',  // Pour le développement local
            'http://127.0.0.1',
        ],
    ],
    // ...
];
```

4. **Configurer le frontend**

Éditez `public/app/js/config.js` pour pointer vers votre API locale :

```javascript
const CONFIG = {
  API_BASE_URL: 'http://localhost/backend/api',
  // ...
};
```

5. **Démarrer le serveur local**

**Option 1 : Serveur PHP intégré** (développement rapide)

```bash
# Terminal 1 : Backend API
cd backend
php -S localhost:8000

# Terminal 2 : Frontend
cd public
php -S localhost:3000
```

**Option 2 : Apache/XAMPP/MAMP**

Placez le dossier dans `htdocs/` et accédez à :
- Site : `http://localhost/Clicom-Site-CRM/public/`
- CRM : `http://localhost/Clicom-Site-CRM/public/app/`

6. **Connexion par défaut**

- **Email** : `admin@clicom.ch`
- **Mot de passe** : `clicom2024`

⚠️ **Changez immédiatement le mot de passe après la première connexion !**

---

## 🚀 Déploiement sur Hostinger

### Architecture de déploiement

```
public_html/
├── index.html          # Redirection vers /public/
├── public/             # Site vitrine
│   ├── index.html
│   ├── fr/
│   ├── en/
│   ├── de/
│   ├── it/
│   ├── assets/
│   └── app/            # Dashboard CRM
│       ├── index.html
│       ├── login.html
│       ├── clients.html
│       ├── js/
│       └── assets/
└── api/                # Backend API (sous-domaine)
    ├── config.php
    └── api/
        ├── auth.php
        ├── contact.php
        └── ...
```

### Étapes de déploiement

#### 1. Préparer les fichiers

```bash
# Créer une archive des fichiers frontend
zip -r frontend.zip public/

# Créer une archive du backend
zip -r backend.zip backend/
```

#### 2. Uploader via FTP/SFTP

Utilisez FileZilla ou le gestionnaire de fichiers Hostinger :

- Uploadez `public/*` vers `public_html/public/`
- Uploadez `backend/*` vers `public_html/api/`

#### 3. Configurer la base de données

Dans le panneau Hostinger :

1. Allez dans **Bases de données MySQL**
2. Créez une nouvelle base : `u123456789_clicom`
3. Créez un utilisateur : `u123456789_clicom`
4. Importez `schema.sql` via phpMyAdmin

#### 4. Configurer le backend

Éditez `public_html/api/config.php` via le gestionnaire de fichiers :

```php
$CONFIG = [
    'db' => [
        'host' => 'localhost',
        'name' => 'u123456789_clicom',
        'user' => 'u123456789_clicom',
        'pass' => 'MotDePasseSecuriseGenereParHostinger',
        'charset' => 'utf8mb4',
    ],
    'cors' => [
        'allowed_origins' => [
            'https://www.clicom.ch',
            'https://clicom.ch',
        ],
    ],
    // ...
];
```

#### 5. Configurer le sous-domaine API

Dans le panneau Hostinger :

1. Allez dans **Domaines → Sous-domaines**
2. Créez le sous-domaine : `api.clicom.ch`
3. Pointez vers : `public_html/api/`

#### 6. Configurer le frontend

Éditez `public_html/public/app/js/config.js` :

```javascript
const CONFIG = {
  API_BASE_URL: 'https://api.clicom.ch',
  // ...
};
```

#### 7. Configurer HTTPS

Dans Hostinger, activez le **SSL gratuit** (Let's Encrypt) pour :
- `www.clicom.ch`
- `api.clicom.ch`

#### 8. Tester le déploiement

- Site vitrine : `https://www.clicom.ch`
- Dashboard : `https://www.clicom.ch/app`
- Login : `admin@clicom.ch` / `clicom2024`

---

## 📁 Structure du projet

```
Clicom-Site-CRM/
│
├── backend/                    # Backend PHP (API)
│   ├── config.php             # Configuration globale (DB, CORS, sécurité)
│   └── api/                   # Endpoints REST
│       ├── auth.php           # Authentification (login/logout)
│       └── contact.php        # Formulaire de contact
│
├── public/                    # Frontend (site + app)
│   ├── index.html             # Page d'accueil (sélection langue)
│   ├── fr/                    # Version française
│   ├── en/                    # Version anglaise
│   ├── de/                    # Version allemande
│   ├── it/                    # Version italienne
│   ├── assets/                # Assets communs (CSS, JS, images)
│   │   ├── styles.css         # Styles du site vitrine
│   │   └── main.js            # Script du site vitrine
│   │
│   └── app/                   # Dashboard CRM
│       ├── index.html         # Dashboard principal
│       ├── login.html         # Page de connexion
│       ├── clients.html       # Gestion clients
│       ├── invoices.html      # Gestion factures
│       ├── projects.html      # Gestion projets
│       ├── tasks.html         # Gestion tâches
│       ├── js/
│       │   ├── config.js      # Configuration (URLs API)
│       │   └── apiClient.js   # Client API (wrapper fetch)
│       └── assets/
│           └── dashboard.css  # Styles du dashboard
│
├── schema.sql                 # Schéma de base de données MySQL
├── README.md                  # Ce fichier
└── .gitignore                 # Fichiers à ignorer (config locale)
```

---

## 🔒 Sécurité

### Mesures de sécurité implémentées

| Mesure | Description |
|--------|-------------|
| **HTTPS obligatoire** | Toutes les connexions chiffrées (TLS 1.2+) |
| **Sessions sécurisées** | `httponly`, `secure`, `samesite=strict` |
| **CSRF Protection** | Token unique par session |
| **Rate Limiting** | 5 requêtes/minute par IP |
| **Account Lockout** | Blocage après 5 tentatives de login |
| **Password Hashing** | bcrypt avec coût 12 |
| **Prepared Statements** | Protection contre SQL Injection |
| **CORS configuré** | Seuls les domaines autorisés |
| **Logs d'activité** | Traçabilité complète |

### Bonnes pratiques

⚠️ **À faire IMMÉDIATEMENT en production** :

1. **Changer le mot de passe admin**
   ```sql
   UPDATE users
   SET password_hash = PASSWORD('NouveauMotDePasseSecurise')
   WHERE email = 'admin@clicom.ch';
   ```

2. **Modifier les secrets dans `config.php`**
   - Mot de passe DB
   - Clé CSRF (optionnel)

3. **Restreindre les permissions de fichiers**
   ```bash
   chmod 644 config.php
   chmod 755 api/
   ```

4. **Activer HTTPS uniquement**
   Rediriger HTTP → HTTPS via `.htaccess`

---

## 📡 API Documentation

### Endpoints disponibles

#### `POST /api/auth.php`

**Login**

```javascript
POST https://api.clicom.ch/auth.php
Headers: {
  "Content-Type": "application/json",
  "X-CSRF-Token": "token_obtenu_via_GET"
}
Body: {
  "action": "login",
  "email": "admin@clicom.ch",
  "password": "clicom2024"
}

Response 200:
{
  "status": "authenticated"
}
```

**Logout**

```javascript
POST https://api.clicom.ch/auth.php
Body: {
  "action": "logout"
}

Response 200:
{
  "status": "logged_out"
}
```

#### `GET /api/auth.php`

Vérifie l'authentification et récupère le CSRF token.

```javascript
GET https://api.clicom.ch/auth.php

Response 200:
{
  "authenticated": true,
  "csrf_token": "abc123..."
}
```

#### `POST /api/contact.php`

Soumet un formulaire de contact (crée un client + tâche).

```javascript
POST https://api.clicom.ch/contact.php
Body: {
  "name": "John Doe",
  "email": "john@example.com",
  "phone": "+41 79 123 45 67",
  "company": "Acme Corp",
  "message": "Je souhaite un devis"
}

Response 200:
{
  "status": "ok"
}
```

### Codes d'erreur

| Code | Signification |
|------|---------------|
| 200 | Succès |
| 401 | Non authentifié |
| 403 | CSRF invalide ou accès refusé |
| 422 | Données invalides |
| 429 | Rate limit dépassé |
| 500 | Erreur serveur |

---

## 🛠 Technologies utilisées

| Catégorie | Technologie |
|-----------|-------------|
| **Backend** | PHP 8.1+, PDO MySQL |
| **Frontend** | HTML5, CSS3 (variables CSS), JavaScript (Vanilla) |
| **Base de données** | MySQL 8.0+ (Triggers, Views, Transactions) |
| **Sécurité** | bcrypt, CSRF tokens, Sessions, CORS |
| **Hébergement** | Hostinger (cPanel, SSL Let's Encrypt) |

---

## 👥 Contribution

### Workflow Git

1. Créer une branche pour chaque feature
   ```bash
   git checkout -b feature/nom-de-la-feature
   ```

2. Commit avec messages clairs
   ```bash
   git commit -m "Add: Gestion des devis"
   ```

3. Push et créer une Pull Request
   ```bash
   git push origin feature/nom-de-la-feature
   ```

### Conventions de code

- **PHP** : PSR-12 (indentation 4 espaces)
- **JavaScript** : 2 espaces, camelCase
- **CSS** : BEM naming convention (optionnel)

---

## 📝 Licence

© 2024 CLICOM. Tous droits réservés.

Ce projet est propriétaire et confidentiel. Toute redistribution ou utilisation sans autorisation est interdite.

---

## 📞 Support

Pour toute question ou problème :

- **Email** : support@clicom.ch
- **Documentation** : Ce README
- **Issues GitHub** : [Créer un ticket](https://github.com/SwissEliteVan/Clicom-Site-CRM/issues)

---

## 🗓 Roadmap

### Version 1.1 (À venir)
- [ ] Endpoints API complets (clients, factures, projets)
- [ ] Tableau de bord avec statistiques en temps réel
- [ ] Export PDF des factures
- [ ] Module d'emailing automatisé
- [ ] Portail client avec accès sécurisé
- [ ] Multi-utilisateurs avec rôles (admin, manager, staff)

### Version 1.2
- [ ] Mode sombre (dark mode)
- [ ] Notifications push
- [ ] API REST complète avec documentation Swagger
- [ ] Module de reporting avancé

---

**Développé avec ❤️ en Suisse**
