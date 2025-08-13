<?php
/**
 * API endpoint for moderating comments (approve/reject)
 * POST /page/api/comments/moderate.php
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../../includes/bootstrap.php';

use App\Application\Core\ServiceProvider;
use App\Application\Services\CommentService;

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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

    // Check authentication and permissions
    if (!$authService->isAuthenticated()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Authentication required']);
        exit;
    }

    $userRole = $authService->getCurrentUserRole();
    if (!in_array($userRole, ['admin', 'employee'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Moderator privileges required']);
        exit;
    }

    $commentId = (int)($_POST['comment_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $rejectionReason = trim($_POST['rejection_reason'] ?? '');

    if (!$commentId || !in_array($status, ['approved', 'rejected'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Valid comment ID and status (approved/rejected) required']);
        exit;
    }

    if ($status === 'rejected' && empty($rejectionReason)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Rejection reason required when rejecting comments']);
        exit;
    }

    $moderatorId = $authService->getCurrentUserId();
    $result = $commentService->moderateComment($commentId, $status, $moderatorId, $rejectionReason);

    http_response_code($result['success'] ? 200 : 400);
    echo json_encode($result);

} catch (Exception $e) {
    error_log("API Error in moderate comment: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
}
?>
