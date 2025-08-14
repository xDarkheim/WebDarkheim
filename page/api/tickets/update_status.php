<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

use App\Application\Controllers\SupportTicketController;
use App\Infrastructure\Lib\Database;

header('Content-Type: application/json');

// Проверяем авторизацию
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Требуется авторизация']);
    exit;
}

// Проверяем, является ли пользователь сотрудником
$userRole = $_SESSION['user']['role'] ?? '';
if (!in_array($userRole, ['admin', 'employee'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Требуется доступ сотрудника']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Метод не разрешен']);
    exit;
}

try {
    $database = new Database();
    $controller = new SupportTicketController($database);
    $result = $controller->updateTicket();

    echo json_encode($result);

} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Внутренняя ошибка сервера']);
}
