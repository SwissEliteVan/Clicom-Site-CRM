<?php
/**
 * Script pour créer un nouvel utilisateur admin
 * Utilisez ce script si vous ne pouvez pas vous connecter ou si vous avez oublié votre mot de passe
 */

require __DIR__ . '/config.php';

header('Content-Type: text/html; charset=utf-8');

// Configuration du nouvel utilisateur
$newUser = [
    'email' => 'admin@clicom.ch',
    'password' => 'admin123',  // ⚠️ CHANGEZ CECI !
    'first_name' => 'Admin',
    'last_name' => 'CLICOM',
    'role' => 'admin'
];

echo '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Créer un utilisateur admin</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; max-width: 600px; margin: 0 auto; }
        .success { color: #22c55e; font-weight: bold; }
        .error { color: #ef4444; font-weight: bold; }
        .warning { background: #fef3c7; padding: 15px; border-radius: 6px; margin: 15px 0; border-left: 4px solid #f59e0b; }
        .info { background: #eff6ff; padding: 15px; border-radius: 6px; margin: 15px 0; }
        code { background: #1f2937; color: #f3f4f6; padding: 2px 6px; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>👤 Créer un utilisateur admin</h1>';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
    try {
        $pdo = db($CONFIG);

        // Générer le hash du mot de passe
        $passwordHash = password_hash($newUser['password'], PASSWORD_BCRYPT, ['cost' => 12]);

        // Vérifier si l'utilisateur existe déjà
        $check = $pdo->prepare('SELECT id FROM users WHERE email = :email');
        $check->execute([':email' => $newUser['email']]);
        $existing = $check->fetch();

        if ($existing) {
            // Mettre à jour l'utilisateur existant
            $stmt = $pdo->prepare('
                UPDATE users
                SET password_hash = :password,
                    first_name = :first_name,
                    last_name = :last_name,
                    role = :role,
                    failed_attempts = 0,
                    locked_until = NULL
                WHERE email = :email
            ');

            $stmt->execute([
                ':password' => $passwordHash,
                ':first_name' => $newUser['first_name'],
                ':last_name' => $newUser['last_name'],
                ':role' => $newUser['role'],
                ':email' => $newUser['email']
            ]);

            echo '<p class="success">✅ Utilisateur mis à jour avec succès !</p>';
        } else {
            // Créer un nouvel utilisateur
            $stmt = $pdo->prepare('
                INSERT INTO users (email, password_hash, first_name, last_name, role)
                VALUES (:email, :password, :first_name, :last_name, :role)
            ');

            $stmt->execute([
                ':email' => $newUser['email'],
                ':password' => $passwordHash,
                ':first_name' => $newUser['first_name'],
                ':last_name' => $newUser['last_name'],
                ':role' => $newUser['role']
            ]);

            echo '<p class="success">✅ Nouvel utilisateur créé avec succès !</p>';
        }

        echo '<div class="info">';
        echo '<strong>📝 Vos identifiants de connexion :</strong><br><br>';
        echo 'Email : <code>' . htmlspecialchars($newUser['email']) . '</code><br>';
        echo 'Mot de passe : <code>' . htmlspecialchars($newUser['password']) . '</code><br>';
        echo '</div>';

        echo '<div class="warning">';
        echo '<strong>⚠️ SÉCURITÉ IMPORTANTE</strong><br><br>';
        echo '1. Connectez-vous immédiatement au CRM<br>';
        echo '2. Changez votre mot de passe depuis les paramètres<br>';
        echo '3. <strong>SUPPRIMEZ ce fichier create-user.php du serveur</strong><br>';
        echo '</div>';

        echo '<p><a href="/public/app/login.html" style="display: inline-block; background: #3366ff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px; margin-top: 10px;">Se connecter au CRM →</a></p>';

    } catch (PDOException $e) {
        echo '<p class="error">❌ Erreur : ' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '<p>Vérifiez que la table users existe dans votre base de données.</p>';
    }

} else {
    // Afficher le formulaire de confirmation
    echo '<div class="warning">';
    echo '<strong>⚠️ ATTENTION</strong><br><br>';
    echo 'Ce script va créer (ou réinitialiser) l\'utilisateur admin suivant :';
    echo '</div>';

    echo '<div class="info">';
    echo 'Email : <code>' . htmlspecialchars($newUser['email']) . '</code><br>';
    echo 'Mot de passe : <code>' . htmlspecialchars($newUser['password']) . '</code><br>';
    echo 'Rôle : <code>admin</code>';
    echo '</div>';

    echo '<p><strong>Si vous voulez un mot de passe différent :</strong></p>';
    echo '<ol>';
    echo '<li>Ouvrez le fichier <code>/backend/create-user.php</code></li>';
    echo '<li>Modifiez la ligne 11 : <code>\'password\' => \'admin123\'</code></li>';
    echo '<li>Remplacez <code>admin123</code> par votre mot de passe</li>';
    echo '<li>Sauvegardez et rechargez cette page</li>';
    echo '</ol>';

    echo '<form method="POST">';
    echo '<input type="hidden" name="confirm" value="1">';
    echo '<button type="submit" style="background: #22c55e; color: white; padding: 12px 24px; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; margin-top: 20px;">
        ✅ Créer cet utilisateur
    </button>';
    echo '</form>';
}

echo '    </div>
</body>
</html>';
