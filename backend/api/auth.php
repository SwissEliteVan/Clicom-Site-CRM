<?php

declare(strict_types=1);

require __DIR__ . '/../config.php';

cors_headers($CONFIG['cors']['allowed_origins']);
start_secure_session($CONFIG['security']);
rate_limit($_SERVER['REMOTE_ADDR'] . ':auth', $CONFIG['security']['rate_limit_per_minute'], 60);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    json_response([
        'authenticated' => !empty($_SESSION['user_id']),
        'csrf_token' => csrf_token($CONFIG['security']),
    ]);
}

require_method('POST');
verify_csrf($CONFIG['security'], $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);

$data = get_request_data();
$action = $data['action'] ?? 'login';

if ($action === 'logout') {
    session_destroy();
    json_response(['status' => 'logged_out']);
}

$email = trim((string) ($data['email'] ?? ''));
$password = (string) ($data['password'] ?? '');

if ($email === '' || $password === '') {
    json_response(['error' => 'Missing credentials.'], 422);
}

$pdo = db($CONFIG);

$stmt = $pdo->prepare('SELECT id, password_hash, failed_attempts, locked_until FROM users WHERE email = :email');
$stmt->execute([':email' => $email]);
$user = $stmt->fetch();

if (!$user) {
    json_response(['error' => 'Invalid credentials.'], 401);
}

$lockedUntil = $user['locked_until'] ? strtotime($user['locked_until']) : 0;
if ($lockedUntil && $lockedUntil > time()) {
    json_response(['error' => 'Account locked. Try later.'], 423);
}

if (!password_verify($password, $user['password_hash'])) {
    $attempts = (int) $user['failed_attempts'] + 1;
    $locked = null;

    if ($attempts >= $CONFIG['security']['lockout_attempts']) {
        $locked = date('Y-m-d H:i:s', strtotime('+' . $CONFIG['security']['lockout_minutes'] . ' minutes'));
        $attempts = 0;
    }

    $update = $pdo->prepare('UPDATE users SET failed_attempts = :attempts, locked_until = :locked WHERE id = :id');
    $update->execute([
        ':attempts' => $attempts,
        ':locked' => $locked,
        ':id' => $user['id'],
    ]);

    json_response(['error' => 'Invalid credentials.'], 401);
}

$reset = $pdo->prepare('UPDATE users SET failed_attempts = 0, locked_until = NULL WHERE id = :id');
$reset->execute([':id' => $user['id']]);

session_regenerate_id(true);
$_SESSION['user_id'] = (int) $user['id'];

log_activity($pdo, (int) $user['id'], 'login_success', [
    'email' => $email,
]);

json_response(['status' => 'authenticated']);
