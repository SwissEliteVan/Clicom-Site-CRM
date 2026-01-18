# 🔧 Configuration de la Base de Données

## Étape 1 : Créer la base de données MySQL

### Sur votre serveur (cPanel Hostinger, phpMyAdmin, etc.)

1. **Connectez-vous à phpMyAdmin** ou à votre interface MySQL

2. **Créez une nouvelle base de données** :
   - Nom : `clicom_crm`
   - Collation : `utf8mb4_unicode_ci`

3. **Créez un utilisateur MySQL** :
   - Nom d'utilisateur : `clicom_user`
   - Mot de passe : **choisissez un mot de passe fort**
   - Hôte : `localhost`

4. **Donnez les privilèges à cet utilisateur** sur la base `clicom_crm` :
   - SELECT
   - INSERT
   - UPDATE
   - DELETE
   - CREATE
   - ALTER
   - DROP
   - INDEX
   - TRIGGER

---

## Étape 2 : Importer le schéma de la base de données

1. Dans phpMyAdmin, **sélectionnez la base de données** `clicom_crm`

2. Cliquez sur l'onglet **"Importer"**

3. **Sélectionnez le fichier** `schema.sql` qui se trouve à la racine du projet

4. Cliquez sur **"Exécuter"**

✅ Cela va créer toutes les tables ET un utilisateur admin par défaut

---

## Étape 3 : Configurer le backend

Ouvrez le fichier `/backend/config.php` et modifiez les lignes 7-10 :

```php
'db' => [
    'host' => '127.0.0.1',           // Ou 'localhost'
    'name' => 'clicom_crm',          // Le nom de votre base
    'user' => 'clicom_user',         // Votre utilisateur MySQL
    'pass' => 'VOTRE_MOT_DE_PASSE',  // ⚠️ CHANGEZ CECI !
    'charset' => 'utf8mb4',
],
```

**⚠️ IMPORTANT** : Remplacez `'VOTRE_MOT_DE_PASSE'` par le mot de passe que vous avez choisi à l'étape 1.

---

## Étape 4 : Tester la connexion

### Identifiants de connexion au CRM :

Une fois la base de données importée avec `schema.sql`, vous pouvez vous connecter au CRM avec :

- **Email** : `admin@clicom.ch`
- **Mot de passe** : `admin123`

⚠️ **CHANGEZ CE MOT DE PASSE** dès votre première connexion !

---

## 🆘 En cas d'erreur

### Erreur : "Access denied for user"

➡️ Vérifiez que :
1. L'utilisateur MySQL `clicom_user` existe
2. Le mot de passe dans `config.php` est correct
3. L'utilisateur a les privilèges sur la base `clicom_crm`

### Erreur : "Unknown database 'clicom_crm'"

➡️ La base de données n'existe pas. Retournez à l'Étape 1.

### Erreur : "Table 'users' doesn't exist"

➡️ Le schéma n'a pas été importé. Retournez à l'Étape 2.

### Erreur : "Invalid credentials" lors de la connexion au CRM

➡️ Vérifiez que :
1. Vous avez bien importé le fichier `schema.sql` (il contient l'utilisateur admin)
2. Vous utilisez `admin@clicom.ch` comme email
3. Vous utilisez `admin123` comme mot de passe

---

## 📝 Cas spécifique : Hostinger

Si vous êtes sur Hostinger :

1. **Base de données** :
   - Allez dans "Bases de données MySQL" dans cPanel
   - Créez une nouvelle base (exemple : `u123456_clicom`)
   - Notez le nom complet de la base

2. **Utilisateur** :
   - Créez un utilisateur (exemple : `u123456_admin`)
   - Notez le nom complet de l'utilisateur
   - Associez cet utilisateur à la base de données

3. **Configurez `config.php`** :
   ```php
   'db' => [
       'host' => 'localhost',
       'name' => 'u123456_clicom',      // Nom complet de votre base
       'user' => 'u123456_admin',        // Nom complet de l'utilisateur
       'pass' => 'votre_mot_de_passe',   // Le mot de passe choisi
       'charset' => 'utf8mb4',
   ],
   ```

4. **Importez le schéma** via phpMyAdmin (accessible depuis cPanel)

---

## ✅ Vérification finale

Pour vérifier que tout fonctionne, créez ce fichier de test :

**`/backend/test-db.php`** :
```php
<?php
require __DIR__ . '/config.php';

try {
    $pdo = db($CONFIG);
    echo "✅ Connexion à la base de données réussie !\n\n";

    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "👤 Nombre d'utilisateurs : " . $result['count'] . "\n";

} catch (PDOException $e) {
    echo "❌ Erreur de connexion : " . $e->getMessage() . "\n";
}
```

Exécutez ce fichier via votre navigateur : `https://votre-site.com/backend/test-db.php`

Si vous voyez "✅ Connexion réussie" et "👤 Nombre d'utilisateurs : 1", tout est bon !

---

**Besoin d'aide ?** Dites-moi à quelle étape vous bloquez.
