<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

use App\Application\Services\TicketService;
use App\Infrastructure\Database\DatabaseConnection;

header('Content-Type: application/json');

// Проверяем авторизацию
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Требуется авторизация']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Метод не разрешен']);
    exit;
}

try {
    $pdo = DatabaseConnection::getInstance()->getConnection();
    $ticketService = new TicketService($pdo);
    
    $ticketId = (int)($_GET['id'] ?? 0);
    if (!$ticketId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID тикета не указан']);
        exit;
    }
    
    $userId = $_SESSION['user']['id'];
    $data = $ticketService->getTicketDetails($ticketId, $userId);
    
    if (!$data) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Тикет не найден или нет доступа']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $data
    ]);
    
} catch (Exception $e) {
    error_log("API Error in get_ticket.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Внутренняя ошибка сервера'
    ]);
}
