<?php

declare(strict_types=1);

/**
 * CLICOM CRM - Projects API
 * Endpoint pour gérer les projets (CRUD complet)
 */

require __DIR__ . '/../config.php';

cors_headers($CONFIG['cors']['allowed_origins']);

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

start_secure_session($CONFIG['security']);

if (empty($_SESSION['user_id'])) {
    json_response(['error' => 'Unauthorized'], 401);
}

$pdo = db($CONFIG);
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            handleGet($pdo);
            break;
        case 'POST':
            handlePost($pdo);
            break;
        case 'PUT':
            handlePut($pdo);
            break;
        case 'DELETE':
            handleDelete($pdo);
            break;
        default:
            json_response(['error' => 'Method not allowed'], 405);
    }
} catch (Exception $e) {
    error_log('Projects API Error: ' . $e->getMessage());
    json_response(['error' => 'Internal server error'], 500);
}

function handleGet(PDO $pdo): void
{
    if (isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $stmt = $pdo->prepare('
            SELECT p.*, c.contact_name, c.company_name
            FROM projects p
            JOIN clients c ON c.id = p.client_id
            WHERE p.id = ?
        ');
        $stmt->execute([$id]);
        $project = $stmt->fetch();

        if (!$project) {
            json_response(['error' => 'Project not found'], 404);
        }

        // Récupérer les tâches du projet
        $stmt = $pdo->prepare('SELECT * FROM tasks WHERE project_id = ? ORDER BY created_at DESC');
        $stmt->execute([$id]);
        $project['tasks'] = $stmt->fetchAll();

        json_response(['project' => $project]);
        return;
    }

    // Liste des projets
    $status = $_GET['status'] ?? null;
    $client_id = $_GET['client_id'] ?? null;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

    $sql = '
        SELECT p.*, c.contact_name, c.company_name
        FROM projects p
        JOIN clients c ON c.id = p.client_id
        WHERE 1=1
    ';
    $params = [];

    if ($status && in_array($status, ['planned', 'active', 'paused', 'completed'])) {
        $sql .= ' AND p.status = ?';
        $params[] = $status;
    }

    if ($client_id) {
        $sql .= ' AND p.client_id = ?';
        $params[] = (int)$client_id;
    }

    $sql .= ' ORDER BY p.created_at DESC LIMIT ? OFFSET ?';
    $params[] = $limit;
    $params[] = $offset;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $projects = $stmt->fetchAll();

    json_response(['projects' => $projects]);
}

function handlePost(PDO $pdo): void
{
    $data = json_decode(file_get_contents('php://input'), true);

    // Validation
    if (empty($data['client_id'])) {
        json_response(['error' => 'Client ID is required'], 422);
    }

    if (empty($data['name'])) {
        json_response(['error' => 'Project name is required'], 422);
    }

    // Vérifier que le client existe
    $stmt = $pdo->prepare('SELECT id FROM clients WHERE id = ?');
    $stmt->execute([$data['client_id']]);
    if (!$stmt->fetch()) {
        json_response(['error' => 'Client not found'], 404);
    }

    // Créer le projet
    $stmt = $pdo->prepare('
        INSERT INTO projects (client_id, name, status, starts_on, ends_on)
        VALUES (?, ?, ?, ?, ?)
    ');

    $stmt->execute([
        $data['client_id'],
        $data['name'],
        $data['status'] ?? 'planned',
        $data['starts_on'] ?? null,
        $data['ends_on'] ?? null,
    ]);

    $projectId = (int)$pdo->lastInsertId();

    log_activity($pdo, $_SESSION['user_id'], 'project_created', ['project_id' => $projectId]);

    $stmt = $pdo->prepare('SELECT * FROM projects WHERE id = ?');
    $stmt->execute([$projectId]);
    $project = $stmt->fetch();

    json_response(['project' => $project, 'message' => 'Project created'], 201);
}

function handlePut(PDO $pdo): void
{
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['id'])) {
        json_response(['error' => 'Project ID is required'], 422);
    }

    $id = (int)$data['id'];

    // Vérifier que le projet existe
    $stmt = $pdo->prepare('SELECT id FROM projects WHERE id = ?');
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        json_response(['error' => 'Project not found'], 404);
    }

    // Construire la requête de mise à jour
    $fields = [];
    $params = [];
    $allowedFields = ['client_id', 'name', 'status', 'starts_on', 'ends_on'];

    foreach ($allowedFields as $field) {
        if (array_key_exists($field, $data)) {
            $fields[] = "$field = ?";
            $params[] = $data[$field];
        }
    }

    if (!empty($fields)) {
        $params[] = $id;
        $sql = 'UPDATE projects SET ' . implode(', ', $fields) . ' WHERE id = ?';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }

    log_activity($pdo, $_SESSION['user_id'], 'project_updated', ['project_id' => $id]);

    $stmt = $pdo->prepare('SELECT * FROM projects WHERE id = ?');
    $stmt->execute([$id]);
    $project = $stmt->fetch();

    json_response(['project' => $project, 'message' => 'Project updated']);
}

function handleDelete(PDO $pdo): void
{
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['id'])) {
        json_response(['error' => 'Project ID is required'], 422);
    }

    $id = (int)$data['id'];

    $stmt = $pdo->prepare('SELECT id FROM projects WHERE id = ?');
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        json_response(['error' => 'Project not found'], 404);
    }

    $stmt = $pdo->prepare('DELETE FROM projects WHERE id = ?');
    $stmt->execute([$id]);

    log_activity($pdo, $_SESSION['user_id'], 'project_deleted', ['project_id' => $id]);

    json_response(['message' => 'Project deleted']);
}

function log_activity(PDO $pdo, int $userId, string $action, array $context): void
{
    try {
        $stmt = $pdo->prepare('
            INSERT INTO activity_log (user_id, ip_address, action, context)
            VALUES (?, ?, ?, ?)
        ');
        $stmt->execute([
            $userId,
            $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            $action,
            json_encode($context),
        ]);
    } catch (Exception $e) {
        error_log('Failed to log activity: ' . $e->getMessage());
    }
}
