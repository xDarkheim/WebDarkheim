<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

use App\Domain\Models\Ticket;
use App\Infrastructure\Lib\Database;

header('Content-Type: application/json');

// Проверяем авторизацию
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Требуется авторизация']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Метод не разрешен']);
    exit;
}

try {
    $database = new Database();
    $userId = (int)$_SESSION['user_id'];

    // Получаем фильтры из GET параметров
    $filters = [];
    if (!empty($_GET['status'])) $filters['status'] = $_GET['status'];
    if (!empty($_GET['category'])) $filters['category'] = $_GET['category'];
    if (!empty($_GET['priority'])) $filters['priority'] = $_GET['priority'];
    if (!empty($_GET['limit'])) $filters['limit'] = (int)$_GET['limit'];
    if (!empty($_GET['offset'])) $filters['offset'] = (int)$_GET['offset'];

    $tickets = Ticket::findByUserId($database, $userId, $filters);
    $stats = Ticket::getUserStats($database, $userId);

    echo json_encode([
        'success' => true,
        'tickets' => array_map(function($ticket) {
            return [
                'id' => $ticket->getId(),
                'subject' => $ticket->getSubject(),
                'status' => $ticket->getStatus(),
                'priority' => $ticket->getPriority(),
                'category' => $ticket->getCategory(),
                'created_at' => $ticket->getCreatedAt(),
                'updated_at' => $ticket->getUpdatedAt()
            ];
        }, $tickets),
        'stats' => $stats
    ]);

} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Внутренняя ошибка сервера']);
}
