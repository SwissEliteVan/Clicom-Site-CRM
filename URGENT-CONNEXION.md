# 🆘 AIDE CONNEXION - Impossible de se connecter

Vous obtenez `{"authenticated":false}` ? Voici comment résoudre le problème **étape par étape**.

---

## 🎯 SOLUTION RAPIDE (5 minutes)

### Étape 1 : Testez la connexion à la base de données

Ouvrez dans votre navigateur :

```
https://votre-site.com/backend/test-db.php
```

Remplacez `votre-site.com` par votre domaine.

---

### Étape 2 : Analysez le résultat

#### ✅ Cas 1 : Vous voyez "✅ Connexion réussie"

**→ La base de données fonctionne !**

Passez à l'Étape 3.

#### ❌ Cas 2 : Vous voyez "❌ Erreur de connexion"

**→ La base de données n'est pas configurée correctement**

**Solutions :**

1. **Ouvrez `/backend/config.php`** et vérifiez les lignes 7-10 :

   ```php
   'db' => [
       'host' => '127.0.0.1',           // Ou 'localhost'
       'name' => 'clicom_crm',          // Le nom de votre BDD
       'user' => 'clicom_user',         // Votre utilisateur MySQL
       'pass' => 'change_me',           // ⚠️ CHANGEZ CECI !
   ],
   ```

2. **Vérifiez que la base de données existe** :
   - Connectez-vous à phpMyAdmin
   - Cherchez une base qui s'appelle `clicom_crm` (ou un nom similaire)
   - Si elle n'existe pas, créez-la

3. **Vérifiez l'utilisateur MySQL** :
   - Dans phpMyAdmin, allez dans "Comptes utilisateurs"
   - Cherchez `clicom_user` (ou créez-le)
   - Assurez-vous qu'il a les droits sur la base `clicom_crm`

4. **Mettez à jour `config.php`** avec les bons paramètres

5. **Rechargez `test-db.php`** pour vérifier

---

### Étape 3 : Vérifiez que la table users existe

Si `test-db.php` affiche :

```
❌ Aucune table trouvée
```

**→ Vous devez importer le schéma de la base de données**

**Solution :**

1. Ouvrez phpMyAdmin
2. Sélectionnez votre base de données `clicom_crm`
3. Cliquez sur "Importer"
4. Sélectionnez le fichier `schema.sql` (à la racine du projet)
5. Cliquez sur "Exécuter"

✅ Cela va créer toutes les tables ET un utilisateur admin par défaut

---

### Étape 4 : Créez votre utilisateur admin

Ouvrez dans votre navigateur :

```
https://votre-site.com/backend/create-user.php
```

Ce script va :
- Créer un utilisateur admin avec l'email `admin@clicom.ch`
- Définir le mot de passe `admin123` (vous pourrez le changer après)

**Cliquez sur "Créer cet utilisateur"**

---

### Étape 5 : Connectez-vous au CRM

Allez sur :

```
https://votre-site.com/public/app/login.html
```

Identifiants :
- **Email** : `admin@clicom.ch`
- **Mot de passe** : `admin123`

---

## 🔐 Après la connexion

**IMPORTANT - Sécurité :**

1. **Changez votre mot de passe** depuis les paramètres du CRM
2. **SUPPRIMEZ ces fichiers du serveur** :
   - `/backend/test-db.php`
   - `/backend/create-user.php`

Ces fichiers sont utiles pour la configuration mais **doivent être supprimés** pour la sécurité.

---

## 🆘 Problèmes spécifiques

### Erreur : "Access denied for user"

**Cause** : Le mot de passe MySQL dans `config.php` est incorrect

**Solution** :
1. Vérifiez le mot de passe de votre utilisateur MySQL
2. Mettez-le à jour dans `/backend/config.php` ligne 10

---

### Erreur : "Unknown database 'clicom_crm'"

**Cause** : La base de données n'existe pas

**Solution** :
1. Créez la base de données dans phpMyAdmin
2. Nom : `clicom_crm`
3. Importez le fichier `schema.sql`

---

### Erreur : "Table 'users' doesn't exist"

**Cause** : Le schéma n'a pas été importé

**Solution** :
1. Dans phpMyAdmin, sélectionnez votre base
2. Onglet "Importer"
3. Sélectionnez `schema.sql`
4. Exécutez

---

### J'obtiens toujours "Invalid credentials"

**Causes possibles** :
1. Le schéma n'a pas été importé (pas d'utilisateur dans la table)
2. Vous utilisez le mauvais email ou mot de passe

**Solution** :
1. Exécutez `test-db.php` pour vérifier si des utilisateurs existent
2. Si aucun utilisateur n'existe, utilisez `create-user.php`
3. Essayez avec `admin@clicom.ch` / `admin123`

---

## 📞 Besoin d'aide ?

Si vous bloquez, dites-moi :

1. **Que voyez-vous quand vous ouvrez `test-db.php` ?**
   - Message d'erreur complet
   - Nombre de tables trouvées
   - Nombre d'utilisateurs trouvés

2. **Votre configuration** :
   - Vous êtes sur Hostinger / Local / Autre hébergeur ?
   - Nom de votre base de données
   - Nom de votre utilisateur MySQL

3. **Ce que vous avez essayé** :
   - Avez-vous importé `schema.sql` ?
   - Avez-vous utilisé `create-user.php` ?

---

## 📚 Guides détaillés

Pour plus de détails, consultez :

- **`SETUP-DATABASE.md`** - Guide complet de configuration de la BDD
- **`GUIDE-DEBUTANT.md`** - Guide pas-à-pas pour débutants
- **`README.md`** - Documentation technique complète
