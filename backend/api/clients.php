<?php

declare(strict_types=1);

/**
 * CLICOM CRM - Clients API
 *
 * Endpoint pour gérer les clients (CRUD complet)
 *
 * Méthodes supportées :
 * - GET    : Liste tous les clients ou un client spécifique
 * - POST   : Crée un nouveau client
 * - PUT    : Met à jour un client existant
 * - DELETE : Supprime un client
 */

require __DIR__ . '/../config.php';

// Headers CORS
cors_headers($CONFIG['cors']['allowed_origins']);

// Gestion de la requête OPTIONS (preflight CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Session sécurisée
start_secure_session($CONFIG['security']);

// Vérifier l'authentification
if (empty($_SESSION['user_id'])) {
    json_response(['error' => 'Unauthorized. Please log in.'], 401);
}

// Connexion à la base de données
$pdo = db($CONFIG);

// Router les requêtes
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
    error_log('Clients API Error: ' . $e->getMessage());
    json_response(['error' => 'Internal server error'], 500);
}

/**
 * GET - Récupérer les clients
 */
function handleGet(PDO $pdo): void
{
    // Si un ID est fourni, récupérer un client spécifique
    if (isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $stmt = $pdo->prepare('
            SELECT id, company_name, contact_name, email, phone, status, source, notes, created_at, updated_at
            FROM clients
            WHERE id = ?
        ');
        $stmt->execute([$id]);
        $client = $stmt->fetch();

        if (!$client) {
            json_response(['error' => 'Client not found'], 404);
        }

        json_response(['client' => $client]);
        return;
    }

    // Filtres
    $status = $_GET['status'] ?? null;
    $search = $_GET['search'] ?? null;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

    // Construction de la requête
    $sql = 'SELECT id, company_name, contact_name, email, phone, status, source, notes, created_at, updated_at FROM clients WHERE 1=1';
    $params = [];

    // Filtre par statut
    if ($status && in_array($status, ['lead', 'active', 'inactive'])) {
        $sql .= ' AND status = ?';
        $params[] = $status;
    }

    // Recherche
    if ($search) {
        $sql .= ' AND (contact_name LIKE ? OR email LIKE ? OR company_name LIKE ?)';
        $searchTerm = '%' . $search . '%';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    // Tri et pagination
    $sql .= ' ORDER BY created_at DESC LIMIT ? OFFSET ?';
    $params[] = $limit;
    $params[] = $offset;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $clients = $stmt->fetchAll();

    // Compter le total
    $countSql = 'SELECT COUNT(*) FROM clients WHERE 1=1';
    $countParams = [];

    if ($status && in_array($status, ['lead', 'active', 'inactive'])) {
        $countSql .= ' AND status = ?';
        $countParams[] = $status;
    }

    if ($search) {
        $countSql .= ' AND (contact_name LIKE ? OR email LIKE ? OR company_name LIKE ?)';
        $searchTerm = '%' . $search . '%';
        $countParams[] = $searchTerm;
        $countParams[] = $searchTerm;
        $countParams[] = $searchTerm;
    }

    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($countParams);
    $total = (int)$countStmt->fetchColumn();

    json_response([
        'clients' => $clients,
        'total' => $total,
        'limit' => $limit,
        'offset' => $offset,
    ]);
}

/**
 * POST - Créer un nouveau client
 */
function handlePost(PDO $pdo): void
{
    $data = json_decode(file_get_contents('php://input'), true);

    // Validation
    $errors = validateClientData($data, false);
    if (!empty($errors)) {
        json_response(['error' => 'Validation failed', 'details' => $errors], 422);
    }

    // Vérifier l'unicité de l'email
    $stmt = $pdo->prepare('SELECT id FROM clients WHERE email = ?');
    $stmt->execute([$data['email']]);
    if ($stmt->fetch()) {
        json_response(['error' => 'Email already exists'], 422);
    }

    // Insérer le client
    $stmt = $pdo->prepare('
        INSERT INTO clients (company_name, contact_name, email, phone, status, source, notes)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ');

    $stmt->execute([
        $data['company_name'] ?? null,
        $data['contact_name'],
        $data['email'],
        $data['phone'] ?? null,
        $data['status'] ?? 'lead',
        $data['source'] ?? null,
        $data['notes'] ?? null,
    ]);

    $clientId = (int)$pdo->lastInsertId();

    // Log de l'activité
    log_activity($pdo, $_SESSION['user_id'], 'client_created', [
        'client_id' => $clientId,
        'email' => $data['email'],
    ]);

    // Récupérer le client créé
    $stmt = $pdo->prepare('SELECT * FROM clients WHERE id = ?');
    $stmt->execute([$clientId]);
    $client = $stmt->fetch();

    json_response(['client' => $client, 'message' => 'Client created successfully'], 201);
}

/**
 * PUT - Mettre à jour un client
 */
function handlePut(PDO $pdo): void
{
    $data = json_decode(file_get_contents('php://input'), true);

    // Validation
    if (empty($data['id'])) {
        json_response(['error' => 'Client ID is required'], 422);
    }

    $id = (int)$data['id'];

    // Vérifier que le client existe
    $stmt = $pdo->prepare('SELECT id FROM clients WHERE id = ?');
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        json_response(['error' => 'Client not found'], 404);
    }

    // Validation des données
    $errors = validateClientData($data, true);
    if (!empty($errors)) {
        json_response(['error' => 'Validation failed', 'details' => $errors], 422);
    }

    // Vérifier l'unicité de l'email (si changé)
    if (isset($data['email'])) {
        $stmt = $pdo->prepare('SELECT id FROM clients WHERE email = ? AND id != ?');
        $stmt->execute([$data['email'], $id]);
        if ($stmt->fetch()) {
            json_response(['error' => 'Email already exists'], 422);
        }
    }

    // Construire la requête de mise à jour dynamique
    $fields = [];
    $params = [];

    $allowedFields = ['company_name', 'contact_name', 'email', 'phone', 'status', 'source', 'notes'];

    foreach ($allowedFields as $field) {
        if (array_key_exists($field, $data)) {
            $fields[] = "$field = ?";
            $params[] = $data[$field];
        }
    }

    if (empty($fields)) {
        json_response(['error' => 'No fields to update'], 422);
    }

    $params[] = $id;

    $sql = 'UPDATE clients SET ' . implode(', ', $fields) . ' WHERE id = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // Log de l'activité
    log_activity($pdo, $_SESSION['user_id'], 'client_updated', [
        'client_id' => $id,
    ]);

    // Récupérer le client mis à jour
    $stmt = $pdo->prepare('SELECT * FROM clients WHERE id = ?');
    $stmt->execute([$id]);
    $client = $stmt->fetch();

    json_response(['client' => $client, 'message' => 'Client updated successfully']);
}

/**
 * DELETE - Supprimer un client
 */
function handleDelete(PDO $pdo): void
{
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['id'])) {
        json_response(['error' => 'Client ID is required'], 422);
    }

    $id = (int)$data['id'];

    // Vérifier que le client existe
    $stmt = $pdo->prepare('SELECT id, email FROM clients WHERE id = ?');
    $stmt->execute([$id]);
    $client = $stmt->fetch();

    if (!$client) {
        json_response(['error' => 'Client not found'], 404);
    }

    // Supprimer le client (CASCADE supprimera les entités liées)
    $stmt = $pdo->prepare('DELETE FROM clients WHERE id = ?');
    $stmt->execute([$id]);

    // Log de l'activité
    log_activity($pdo, $_SESSION['user_id'], 'client_deleted', [
        'client_id' => $id,
        'email' => $client['email'],
    ]);

    json_response(['message' => 'Client deleted successfully']);
}

/**
 * Valider les données du client
 */
function validateClientData(array $data, bool $isUpdate): array
{
    $errors = [];

    // Champs requis pour la création
    if (!$isUpdate) {
        if (empty($data['contact_name'])) {
            $errors['contact_name'] = 'Contact name is required';
        }

        if (empty($data['email'])) {
            $errors['email'] = 'Email is required';
        }
    }

    // Validation de l'email
    if (isset($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    }

    // Validation du statut
    if (isset($data['status']) && !in_array($data['status'], ['lead', 'active', 'inactive'])) {
        $errors['status'] = 'Status must be: lead, active, or inactive';
    }

    // Validation des longueurs
    if (isset($data['contact_name']) && strlen($data['contact_name']) > 190) {
        $errors['contact_name'] = 'Contact name too long (max 190 characters)';
    }

    if (isset($data['company_name']) && strlen($data['company_name']) > 190) {
        $errors['company_name'] = 'Company name too long (max 190 characters)';
    }

    if (isset($data['phone']) && strlen($data['phone']) > 50) {
        $errors['phone'] = 'Phone too long (max 50 characters)';
    }

    return $errors;
}

/**
 * Logger une activité
 */
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
        // Ne pas interrompre l'exécution si le log échoue
        error_log('Failed to log activity: ' . $e->getMessage());
    }
}
