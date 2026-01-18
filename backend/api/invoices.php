<?php

declare(strict_types=1);

/**
 * CLICOM CRM - Invoices API
 * Endpoint pour gérer les factures (CRUD complet)
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
    error_log('Invoices API Error: ' . $e->getMessage());
    json_response(['error' => 'Internal server error'], 500);
}

function handleGet(PDO $pdo): void
{
    if (isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $stmt = $pdo->prepare('
            SELECT i.*, c.contact_name, c.company_name, c.email
            FROM invoices i
            JOIN clients c ON c.id = i.client_id
            WHERE i.id = ?
        ');
        $stmt->execute([$id]);
        $invoice = $stmt->fetch();

        if (!$invoice) {
            json_response(['error' => 'Invoice not found'], 404);
        }

        // Récupérer les lignes de la facture
        $stmt = $pdo->prepare('
            SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY id
        ');
        $stmt->execute([$id]);
        $invoice['items'] = $stmt->fetchAll();

        // Récupérer les paiements
        $stmt = $pdo->prepare('
            SELECT * FROM payments WHERE invoice_id = ? ORDER BY paid_at DESC
        ');
        $stmt->execute([$id]);
        $invoice['payments'] = $stmt->fetchAll();

        json_response(['invoice' => $invoice]);
        return;
    }

    // Liste des factures
    $status = $_GET['status'] ?? null;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

    $sql = '
        SELECT i.*, c.contact_name, c.company_name
        FROM invoices i
        JOIN clients c ON c.id = i.client_id
        WHERE 1=1
    ';
    $params = [];

    if ($status && in_array($status, ['draft', 'sent', 'partial', 'paid', 'overdue'])) {
        $sql .= ' AND i.status = ?';
        $params[] = $status;
    }

    $sql .= ' ORDER BY i.created_at DESC LIMIT ? OFFSET ?';
    $params[] = $limit;
    $params[] = $offset;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $invoices = $stmt->fetchAll();

    json_response(['invoices' => $invoices]);
}

function handlePost(PDO $pdo): void
{
    $data = json_decode(file_get_contents('php://input'), true);

    // Validation
    if (empty($data['client_id'])) {
        json_response(['error' => 'Client ID is required'], 422);
    }

    if (empty($data['reference'])) {
        json_response(['error' => 'Reference is required'], 422);
    }

    // Vérifier que le client existe
    $stmt = $pdo->prepare('SELECT id FROM clients WHERE id = ?');
    $stmt->execute([$data['client_id']]);
    if (!$stmt->fetch()) {
        json_response(['error' => 'Client not found'], 404);
    }

    // Créer la facture
    $stmt = $pdo->prepare('
        INSERT INTO invoices (client_id, reference, status, subtotal, tax_rate, tax_amount, total, issued_at, due_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ');

    $stmt->execute([
        $data['client_id'],
        $data['reference'],
        $data['status'] ?? 'draft',
        $data['subtotal'] ?? 0,
        $data['tax_rate'] ?? 7.70,
        $data['tax_amount'] ?? 0,
        $data['total'] ?? 0,
        $data['issued_at'] ?? null,
        $data['due_at'] ?? null,
    ]);

    $invoiceId = (int)$pdo->lastInsertId();

    // Ajouter les lignes de facture si fournies
    if (!empty($data['items']) && is_array($data['items'])) {
        $stmt = $pdo->prepare('
            INSERT INTO invoice_items (invoice_id, product_id, description, quantity, unit_price)
            VALUES (?, ?, ?, ?, ?)
        ');

        foreach ($data['items'] as $item) {
            $stmt->execute([
                $invoiceId,
                $item['product_id'] ?? null,
                $item['description'] ?? '',
                $item['quantity'] ?? 1,
                $item['unit_price'] ?? 0,
            ]);
        }
    }

    // Log
    log_activity($pdo, $_SESSION['user_id'], 'invoice_created', ['invoice_id' => $invoiceId]);

    // Récupérer la facture créée
    $stmt = $pdo->prepare('SELECT * FROM invoices WHERE id = ?');
    $stmt->execute([$invoiceId]);
    $invoice = $stmt->fetch();

    json_response(['invoice' => $invoice, 'message' => 'Invoice created'], 201);
}

function handlePut(PDO $pdo): void
{
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['id'])) {
        json_response(['error' => 'Invoice ID is required'], 422);
    }

    $id = (int)$data['id'];

    // Vérifier que la facture existe
    $stmt = $pdo->prepare('SELECT id FROM invoices WHERE id = ?');
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        json_response(['error' => 'Invoice not found'], 404);
    }

    // Construire la requête de mise à jour
    $fields = [];
    $params = [];
    $allowedFields = ['client_id', 'reference', 'status', 'subtotal', 'tax_rate', 'tax_amount', 'total', 'issued_at', 'due_at'];

    foreach ($allowedFields as $field) {
        if (array_key_exists($field, $data)) {
            $fields[] = "$field = ?";
            $params[] = $data[$field];
        }
    }

    if (!empty($fields)) {
        $params[] = $id;
        $sql = 'UPDATE invoices SET ' . implode(', ', $fields) . ' WHERE id = ?';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }

    log_activity($pdo, $_SESSION['user_id'], 'invoice_updated', ['invoice_id' => $id]);

    $stmt = $pdo->prepare('SELECT * FROM invoices WHERE id = ?');
    $stmt->execute([$id]);
    $invoice = $stmt->fetch();

    json_response(['invoice' => $invoice, 'message' => 'Invoice updated']);
}

function handleDelete(PDO $pdo): void
{
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['id'])) {
        json_response(['error' => 'Invoice ID is required'], 422);
    }

    $id = (int)$data['id'];

    $stmt = $pdo->prepare('SELECT id FROM invoices WHERE id = ?');
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        json_response(['error' => 'Invoice not found'], 404);
    }

    $stmt = $pdo->prepare('DELETE FROM invoices WHERE id = ?');
    $stmt->execute([$id]);

    log_activity($pdo, $_SESSION['user_id'], 'invoice_deleted', ['invoice_id' => $id]);

    json_response(['message' => 'Invoice deleted']);
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
