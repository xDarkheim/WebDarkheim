<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

use App\Application\Controllers\SupportTicketController;
use App\Infrastructure\Lib\Database;

header('Content-Type: application/json');

// Проверяем авторизацию
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Требуется авторизация']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $database = new Database();
    $controller = new SupportTicketController($database);
    $result = $controller->createTicket();

    echo json_encode($result);

} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
}
