<?php

declare(strict_types=1);

/**
 * CLICOM CRM - Dashboard API
 *
 * Endpoint pour récupérer les statistiques du dashboard
 *
 * Retourne :
 * - Nombre de clients actifs
 * - Revenu du mois en cours
 * - Nombre de factures en attente
 * - Nombre de projets actifs
 * - Activité récente
 * - Tâches à faire
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

// Seule la méthode GET est supportée
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['error' => 'Method not allowed'], 405);
}

// Connexion à la base de données
$pdo = db($CONFIG);

try {
    // Récupérer toutes les statistiques
    $stats = [
        'clients_active' => getActiveClients($pdo),
        'revenue_month' => getMonthlyRevenue($pdo),
        'invoices_pending' => getPendingInvoices($pdo),
        'projects_active' => getActiveProjects($pdo),
        'recent_clients' => getRecentClients($pdo),
        'tasks_todo' => getTodoTasks($pdo),
    ];

    json_response($stats);
} catch (Exception $e) {
    error_log('Dashboard API Error: ' . $e->getMessage());
    json_response(['error' => 'Internal server error'], 500);
}

/**
 * Nombre de clients actifs
 */
function getActiveClients(PDO $pdo): int
{
    $stmt = $pdo->query("SELECT COUNT(*) FROM clients WHERE status = 'active'");
    return (int)$stmt->fetchColumn();
}

/**
 * Revenu du mois en cours
 */
function getMonthlyRevenue(PDO $pdo): float
{
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(amount), 0) as total
        FROM payments
        WHERE DATE_FORMAT(paid_at, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')
    ");
    $stmt->execute();
    $result = $stmt->fetch();
    return (float)($result['total'] ?? 0);
}

/**
 * Nombre de factures en attente
 */
function getPendingInvoices(PDO $pdo): int
{
    $stmt = $pdo->query("
        SELECT COUNT(*) FROM invoices
        WHERE status IN ('sent', 'partial', 'overdue')
    ");
    return (int)$stmt->fetchColumn();
}

/**
 * Nombre de projets actifs
 */
function getActiveProjects(PDO $pdo): int
{
    $stmt = $pdo->query("SELECT COUNT(*) FROM projects WHERE status = 'active'");
    return (int)$stmt->fetchColumn();
}

/**
 * Clients récents (10 derniers)
 */
function getRecentClients(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT id, company_name, contact_name, email, status, created_at
        FROM clients
        ORDER BY created_at DESC
        LIMIT 10
    ");
    return $stmt->fetchAll();
}

/**
 * Tâches à faire (10 prochaines)
 */
function getTodoTasks(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT
            t.id,
            t.title,
            t.description,
            t.status,
            t.priority,
            t.due_at,
            c.contact_name as client_name
        FROM tasks t
        LEFT JOIN clients c ON c.id = t.client_id
        WHERE t.status IN ('todo', 'in_progress')
        ORDER BY
            CASE t.priority
                WHEN 'high' THEN 1
                WHEN 'medium' THEN 2
                WHEN 'low' THEN 3
            END,
            t.due_at ASC
        LIMIT 10
    ");
    return $stmt->fetchAll();
}
