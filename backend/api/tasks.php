<?php

declare(strict_types=1);

/**
 * CLICOM CRM - Tasks API
 * Endpoint pour gérer les tâches (CRUD complet)
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
    error_log('Tasks API Error: ' . $e->getMessage());
    json_response(['error' => 'Internal server error'], 500);
}

function handleGet(PDO $pdo): void
{
    if (isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $stmt = $pdo->prepare('
            SELECT t.*, c.contact_name as client_name, p.name as project_name
            FROM tasks t
            LEFT JOIN clients c ON c.id = t.client_id
            LEFT JOIN projects p ON p.id = t.project_id
            WHERE t.id = ?
        ');
        $stmt->execute([$id]);
        $task = $stmt->fetch();

        if (!$task) {
            json_response(['error' => 'Task not found'], 404);
        }

        json_response(['task' => $task]);
        return;
    }

    // Liste des tâches
    $status = $_GET['status'] ?? null;
    $priority = $_GET['priority'] ?? null;
    $project_id = $_GET['project_id'] ?? null;
    $client_id = $_GET['client_id'] ?? null;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

    $sql = '
        SELECT t.*, c.contact_name as client_name, p.name as project_name
        FROM tasks t
        LEFT JOIN clients c ON c.id = t.client_id
        LEFT JOIN projects p ON p.id = t.project_id
        WHERE 1=1
    ';
    $params = [];

    if ($status && in_array($status, ['todo', 'in_progress', 'done'])) {
        $sql .= ' AND t.status = ?';
        $params[] = $status;
    }

    if ($priority && in_array($priority, ['low', 'medium', 'high'])) {
        $sql .= ' AND t.priority = ?';
        $params[] = $priority;
    }

    if ($project_id) {
        $sql .= ' AND t.project_id = ?';
        $params[] = (int)$project_id;
    }

    if ($client_id) {
        $sql .= ' AND t.client_id = ?';
        $params[] = (int)$client_id;
    }

    $sql .= ' ORDER BY
        CASE t.status
            WHEN \'in_progress\' THEN 1
            WHEN \'todo\' THEN 2
            WHEN \'done\' THEN 3
        END,
        CASE t.priority
            WHEN \'high\' THEN 1
            WHEN \'medium\' THEN 2
            WHEN \'low\' THEN 3
        END,
        t.due_at ASC
        LIMIT ? OFFSET ?
    ';
    $params[] = $limit;
    $params[] = $offset;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $tasks = $stmt->fetchAll();

    json_response(['tasks' => $tasks]);
}

function handlePost(PDO $pdo): void
{
    $data = json_decode(file_get_contents('php://input'), true);

    // Validation
    if (empty($data['title'])) {
        json_response(['error' => 'Task title is required'], 422);
    }

    // Créer la tâche
    $stmt = $pdo->prepare('
        INSERT INTO tasks (project_id, client_id, title, description, status, priority, due_at)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ');

    $stmt->execute([
        $data['project_id'] ?? null,
        $data['client_id'] ?? null,
        $data['title'],
        $data['description'] ?? null,
        $data['status'] ?? 'todo',
        $data['priority'] ?? 'medium',
        $data['due_at'] ?? null,
    ]);

    $taskId = (int)$pdo->lastInsertId();

    log_activity($pdo, $_SESSION['user_id'], 'task_created', ['task_id' => $taskId]);

    $stmt = $pdo->prepare('SELECT * FROM tasks WHERE id = ?');
    $stmt->execute([$taskId]);
    $task = $stmt->fetch();

    json_response(['task' => $task, 'message' => 'Task created'], 201);
}

function handlePut(PDO $pdo): void
{
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['id'])) {
        json_response(['error' => 'Task ID is required'], 422);
    }

    $id = (int)$data['id'];

    // Vérifier que la tâche existe
    $stmt = $pdo->prepare('SELECT id FROM tasks WHERE id = ?');
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        json_response(['error' => 'Task not found'], 404);
    }

    // Construire la requête de mise à jour
    $fields = [];
    $params = [];
    $allowedFields = ['project_id', 'client_id', 'title', 'description', 'status', 'priority', 'due_at'];

    foreach ($allowedFields as $field) {
        if (array_key_exists($field, $data)) {
            $fields[] = "$field = ?";
            $params[] = $data[$field];
        }
    }

    if (!empty($fields)) {
        $params[] = $id;
        $sql = 'UPDATE tasks SET ' . implode(', ', $fields) . ' WHERE id = ?';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }

    log_activity($pdo, $_SESSION['user_id'], 'task_updated', ['task_id' => $id]);

    $stmt = $pdo->prepare('SELECT * FROM tasks WHERE id = ?');
    $stmt->execute([$id]);
    $task = $stmt->fetch();

    json_response(['task' => $task, 'message' => 'Task updated']);
}

function handleDelete(PDO $pdo): void
{
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['id'])) {
        json_response(['error' => 'Task ID is required'], 422);
    }

    $id = (int)$data['id'];

    $stmt = $pdo->prepare('SELECT id FROM tasks WHERE id = ?');
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        json_response(['error' => 'Task not found'], 404);
    }

    $stmt = $pdo->prepare('DELETE FROM tasks WHERE id = ?');
    $stmt->execute([$id]);

    log_activity($pdo, $_SESSION['user_id'], 'task_deleted', ['task_id' => $id]);

    json_response(['message' => 'Task deleted']);
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
