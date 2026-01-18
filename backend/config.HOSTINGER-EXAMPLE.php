<?php

declare(strict_types=1);

/**
 * ⚠️ CONFIGURATION POUR HOSTINGER
 *
 * 1. Copiez ce fichier et renommez-le en "config.php"
 * 2. Remplacez les valeurs ci-dessous par VOS identifiants MySQL
 * 3. Uploadez sur votre serveur Hostinger
 */

$CONFIG = [
    'db' => [
        'host' => 'localhost',                    // Sur Hostinger, c'est toujours 'localhost'
        'name' => 'u123456789_clicom',            // ⚠️ REMPLACEZ par le nom de VOTRE base de données
        'user' => 'u123456789_admin',             // ⚠️ REMPLACEZ par VOTRE utilisateur MySQL
        'pass' => 'VotreMot2PasseMySQL',          // ⚠️ REMPLACEZ par VOTRE mot de passe MySQL
        'charset' => 'utf8mb4',
    ],
    'cors' => [
        'allowed_origins' => [
            'https://votre-domaine.com',          // ⚠️ REMPLACEZ par VOTRE domaine Hostinger
            'https://www.votre-domaine.com',
            'http://localhost',                   // Pour tests locaux
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

/**
 * EXEMPLE CONCRET :
 *
 * Si dans cPanel vous voyez :
 * - Base de données : u987654321_mycrm
 * - Utilisateur : u987654321_user
 * - Mot de passe : SecretPass123!
 * - Votre site : https://monsite.com
 *
 * Alors configurez comme ceci :
 *
 * $CONFIG = [
 *     'db' => [
 *         'host' => 'localhost',
 *         'name' => 'u987654321_mycrm',
 *         'user' => 'u987654321_user',
 *         'pass' => 'SecretPass123!',
 *         'charset' => 'utf8mb4',
 *     ],
 *     'cors' => [
 *         'allowed_origins' => [
 *             'https://monsite.com',
 *             'https://www.monsite.com',
 *             'http://localhost',
 *         ],
 *     ],
 *     ...
 * ];
 */

// ========================================
// NE MODIFIEZ PAS LE CODE CI-DESSOUS
// ========================================

function cors_headers(array $allowedOrigins): void
{
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if ($origin && in_array($origin, $allowedOrigins, true)) {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

function db(array $config): PDO
{
    static $pdo;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        $config['db']['host'],
        $config['db']['name'],
        $config['db']['charset']
    );

    $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    return $pdo;
}

function start_secure_session(array $security): void
{
    session_name($security['session_name']);

    session_set_cookie_params([
        'httponly' => true,
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'samesite' => 'Strict',
    ]);

    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function csrf_token(array $security): string
{
    if (empty($_SESSION[$security['csrf_key']])) {
        $_SESSION[$security['csrf_key']] = bin2hex(random_bytes(32));
    }

    return $_SESSION[$security['csrf_key']];
}

function verify_csrf(array $security, ?string $token): void
{
    $sessionToken = $_SESSION[$security['csrf_key']] ?? '';
    if (!$token || !hash_equals($sessionToken, $token)) {
        json_response(['error' => 'Invalid CSRF token.'], 403);
    }
}

function json_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function get_request_data(): array
{
    $input = file_get_contents('php://input');
    if ($input) {
        $data = json_decode($input, true);
        if (is_array($data)) {
            return $data;
        }
    }

    return $_POST;
}

function rate_limit(string $key, int $limit, int $windowSeconds): void
{
    $bucket = sys_get_temp_dir() . '/clicom_' . hash('sha256', $key);
    $now = time();

    $data = [
        'reset' => $now + $windowSeconds,
        'count' => 0,
    ];

    if (file_exists($bucket)) {
        $stored = json_decode((string) file_get_contents($bucket), true);
        if (is_array($stored)) {
            $data = $stored;
        }
    }

    if ($now > ($data['reset'] ?? 0)) {
        $data = [
            'reset' => $now + $windowSeconds,
            'count' => 0,
        ];
    }

    $data['count']++;

    file_put_contents($bucket, json_encode($data));

    if ($data['count'] > $limit) {
        json_response(['error' => 'Rate limit exceeded.'], 429);
    }
}

function log_activity(PDO $pdo, ?int $userId, string $action, array $context = []): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO activity_log (user_id, ip_address, action, context) VALUES (:user_id, :ip, :action, :context)'
    );
    $stmt->execute([
        ':user_id' => $userId,
        ':ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
        ':action' => $action,
        ':context' => json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    ]);
}

function require_method(string $method): void
{
    if ($_SERVER['REQUEST_METHOD'] !== $method) {
        json_response(['error' => 'Method not allowed.'], 405);
    }
}
