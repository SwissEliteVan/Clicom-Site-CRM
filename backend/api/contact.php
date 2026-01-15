<?php

declare(strict_types=1);

require __DIR__ . '/../config.php';

cors_headers($CONFIG['cors']['allowed_origins']);
start_secure_session($CONFIG['security']);
rate_limit($_SERVER['REMOTE_ADDR'] . ':contact', $CONFIG['security']['rate_limit_per_minute'], 60);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    json_response(['csrf_token' => csrf_token($CONFIG['security'])]);
}

require_method('POST');
verify_csrf($CONFIG['security'], $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);

$data = get_request_data();

$honeypot = trim((string) ($data['website'] ?? ''));
if ($honeypot !== '') {
    json_response(['status' => 'ok'], 204);
}

$contactName = trim((string) ($data['name'] ?? ''));
$email = trim((string) ($data['email'] ?? ''));
$phone = trim((string) ($data['phone'] ?? ''));
$company = trim((string) ($data['company'] ?? ''));
$message = trim((string) ($data['message'] ?? ''));

if ($contactName === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['error' => 'Invalid contact data.'], 422);
}

$pdo = db($CONFIG);

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        'INSERT INTO clients (company_name, contact_name, email, phone, status, source, notes)
        VALUES (:company, :contact, :email, :phone, :status, :source, :notes)
        ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), contact_name = VALUES(contact_name), phone = VALUES(phone)'
    );
    $stmt->execute([
        ':company' => $company ?: null,
        ':contact' => $contactName,
        ':email' => $email,
        ':phone' => $phone ?: null,
        ':status' => 'lead',
        ':source' => 'website',
        ':notes' => $message ?: null,
    ]);

    $clientId = (int) $pdo->lastInsertId();
    if ($clientId === 0) {
        $lookup = $pdo->prepare('SELECT id FROM clients WHERE email = :email');
        $lookup->execute([':email' => $email]);
        $clientId = (int) $lookup->fetchColumn();
    }

    $task = $pdo->prepare(
        'INSERT INTO tasks (client_id, title, description, priority, due_at)
        VALUES (:client_id, :title, :description, :priority, :due_at)'
    );
    $task->execute([
        ':client_id' => $clientId,
        ':title' => 'Rappeler le prospect',
        ':description' => $message ?: 'Demande via formulaire de contact.',
        ':priority' => 'high',
        ':due_at' => date('Y-m-d', strtotime('+2 days')),
    ]);

    log_activity($pdo, null, 'contact_created', [
        'client_id' => $clientId,
        'email' => $email,
    ]);

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    json_response(['error' => 'Unable to create contact.'], 500);
}

json_response(['status' => 'ok']);
