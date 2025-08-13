<?php
/**
 * API endpoint for getting comment threads
 * GET /page/api/comments/get_thread.php
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../../includes/bootstrap.php';

use App\Application\Core\ServiceProvider;
use App\Application\Services\CommentService;

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        exit;
    }

    // Get services
    $serviceProvider = ServiceProvider::getInstance();
    $authService = $serviceProvider->getAuth();
    $database = $serviceProvider->getDatabase();
    $logger = $serviceProvider->getLogger();
    
    $commentService = new CommentService($database, $logger);

    $commentableType = $_GET['commentable_type'] ?? '';
    $commentableId = (int)($_GET['commentable_id'] ?? 0);
    
    if (!$commentableType || !$commentableId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'commentable_type and commentable_id are required']);
        exit;
    }

    if (!in_array($commentableType, ['article', 'portfolio_project'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid commentable_type']);
        exit;
    }

    // Check if user can see unapproved comments (moderators only)
    $includeUnapproved = false;
    if ($authService->isAuthenticated()) {
        $userRole = $authService->getCurrentUserRole();
        $includeUnapproved = in_array($userRole, ['admin', 'employee']);
    }

    $comments = $commentService->getComments($commentableType, $commentableId, $includeUnapproved);

    echo json_encode([
        'success' => true,
        'comments' => $comments,
        'total' => count($comments)
    ]);

} catch (Exception $e) {
    error_log("API Error in get comment thread: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
}
?>
