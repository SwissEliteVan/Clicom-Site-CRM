<?php
/**
 * Script de test de connexion à la base de données
 * Accédez à ce fichier via votre navigateur pour vérifier la configuration
 */

require __DIR__ . '/config.php';

header('Content-Type: text/html; charset=utf-8');

echo '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Test de connexion BDD</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; max-width: 600px; margin: 0 auto; }
        .success { color: #22c55e; font-weight: bold; }
        .error { color: #ef4444; font-weight: bold; }
        .info { background: #eff6ff; padding: 15px; border-radius: 6px; margin: 15px 0; }
        pre { background: #1f2937; color: #f3f4f6; padding: 15px; border-radius: 6px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Test de connexion MySQL</h1>';

try {
    // Test de connexion
    $pdo = db($CONFIG);
    echo '<p class="success">✅ Connexion à la base de données réussie !</p>';

    echo '<div class="info">';
    echo '<strong>Configuration actuelle :</strong><br>';
    echo 'Base de données : <code>' . htmlspecialchars($CONFIG['db']['name']) . '</code><br>';
    echo 'Utilisateur : <code>' . htmlspecialchars($CONFIG['db']['user']) . '</code><br>';
    echo 'Hôte : <code>' . htmlspecialchars($CONFIG['db']['host']) . '</code>';
    echo '</div>';

    // Vérifier les tables
    echo '<h2>📊 Tables de la base de données</h2>';
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

    if (empty($tables)) {
        echo '<p class="error">❌ Aucune table trouvée. Vous devez importer le fichier schema.sql !</p>';
    } else {
        echo '<p class="success">✅ ' . count($tables) . ' tables trouvées :</p>';
        echo '<pre>' . implode("\n", $tables) . '</pre>';
    }

    // Vérifier les utilisateurs
    if (in_array('users', $tables)) {
        echo '<h2>👤 Utilisateurs dans la base</h2>';
        $stmt = $pdo->query("SELECT id, email, first_name, last_name, role FROM users");
        $users = $stmt->fetchAll();

        if (empty($users)) {
            echo '<p class="error">❌ Aucun utilisateur trouvé dans la table users !</p>';
            echo '<p>Vous devez importer le fichier schema.sql qui contient l\'utilisateur admin par défaut.</p>';
        } else {
            echo '<p class="success">✅ ' . count($users) . ' utilisateur(s) trouvé(s) :</p>';
            echo '<table style="width: 100%; border-collapse: collapse;">';
            echo '<tr style="background: #f9fafb; border-bottom: 2px solid #e5e7eb;">';
            echo '<th style="padding: 10px; text-align: left;">Email</th>';
            echo '<th style="padding: 10px; text-align: left;">Nom</th>';
            echo '<th style="padding: 10px; text-align: left;">Rôle</th>';
            echo '</tr>';

            foreach ($users as $user) {
                echo '<tr style="border-bottom: 1px solid #e5e7eb;">';
                echo '<td style="padding: 10px;">' . htmlspecialchars($user['email']) . '</td>';
                echo '<td style="padding: 10px;">' . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . '</td>';
                echo '<td style="padding: 10px;"><span style="background: #dbeafe; padding: 4px 8px; border-radius: 4px;">' . htmlspecialchars($user['role']) . '</span></td>';
                echo '</tr>';
            }
            echo '</table>';

            echo '<div class="info" style="margin-top: 20px;">';
            echo '<strong>📝 Identifiants de connexion au CRM :</strong><br>';
            echo 'Email : <code>admin@clicom.ch</code><br>';
            echo 'Mot de passe : <code>admin123</code><br>';
            echo '<br><small>⚠️ Changez ce mot de passe dès votre première connexion !</small>';
            echo '</div>';
        }
    }

    // Vérifier les clients
    if (in_array('clients', $tables)) {
        echo '<h2>🏢 Clients dans la base</h2>';
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM clients");
        $result = $stmt->fetch();
        echo '<p>Nombre de clients : <strong>' . $result['count'] . '</strong></p>';
    }

    echo '<hr style="margin: 30px 0;">';
    echo '<p class="success">✅ Tout fonctionne correctement !</p>';
    echo '<p>Vous pouvez maintenant vous connecter au CRM.</p>';

} catch (PDOException $e) {
    echo '<p class="error">❌ Erreur de connexion à la base de données</p>';
    echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';

    echo '<div class="info">';
    echo '<strong>🆘 Solutions possibles :</strong><br><br>';
    echo '1. Vérifiez que MySQL est démarré<br>';
    echo '2. Vérifiez les paramètres dans /backend/config.php :<br>';
    echo '   - Nom de la base de données<br>';
    echo '   - Utilisateur MySQL<br>';
    echo '   - Mot de passe MySQL<br>';
    echo '3. Vérifiez que l\'utilisateur MySQL a les droits sur la base de données<br>';
    echo '4. Créez la base de données si elle n\'existe pas<br>';
    echo '</div>';
}

echo '    </div>
</body>
</html>';
