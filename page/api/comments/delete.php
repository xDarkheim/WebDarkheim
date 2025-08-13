<?php
/**
 * API endpoint for deleting comments
 * DELETE /page/api/comments/delete.php
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../../includes/bootstrap.php';

use App\Application\Core\ServiceProvider;
use App\Application\Services\CommentService;

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        exit;
    }

    // Parse DELETE data
    parse_str(file_get_contents("php://input"), $deleteData);

    // Get services
    $serviceProvider = ServiceProvider::getInstance();
    $authService = $serviceProvider->getAuth();
    $database = $serviceProvider->getDatabase();
    $logger = $serviceProvider->getLogger();

    $commentService = new CommentService($database, $logger);

    // Check authentication
    if (!$authService->isAuthenticated()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Authentication required']);
        exit;
    }

    $commentId = (int)($deleteData['comment_id'] ?? 0);

    if (!$commentId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Comment ID is required']);
        exit;
    }

    $currentUserId = $authService->getCurrentUserId();
    $result = $commentService->deleteComment($commentId, $currentUserId);

    http_response_code($result['success'] ? 200 : 400);
    echo json_encode($result);

} catch (Exception $e) {
    error_log("API Error in delete comment: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
}
?>
