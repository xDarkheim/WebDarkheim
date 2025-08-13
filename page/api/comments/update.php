<?php
/**
 * API endpoint for updating comments
 * PUT /page/api/comments/update.php
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../../includes/bootstrap.php';

use App\Application\Core\ServiceProvider;
use App\Application\Services\CommentService;

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        exit;
    }

    // Parse PUT data
    parse_str(file_get_contents("php://input"), $putData);

    // Get services
    $serviceProvider = ServiceProvider::getInstance();
    $authService = $serviceProvider->getAuth();
    $database = $serviceProvider->getDatabase();
    $logger = $serviceProvider->getLogger();

    // Check authentication
    if (!$authService->isAuthenticated()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Authentication required']);
        exit;
    }

    $commentId = (int)($putData['comment_id'] ?? 0);
    $content = trim($putData['content'] ?? '');

    if (!$commentId || empty($content)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Comment ID and content are required']);
        exit;
    }

    // Check if user can edit this comment
    $db = $database->getConnection();
    $stmt = $db->prepare("SELECT user_id FROM comments WHERE id = ?");
    $stmt->execute([$commentId]);
    $comment = $stmt->fetch();

    if (!$comment) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Comment not found']);
        exit;
    }

    $currentUserId = $authService->getCurrentUserId();
    $userRole = $authService->getCurrentUserRole();

    // Only comment owner or admin/employee can edit
    if ($comment['user_id'] != $currentUserId && !in_array($userRole, ['admin', 'employee'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Permission denied']);
        exit;
    }

    // Update comment
    $stmt = $db->prepare("UPDATE comments SET content = ?, updated_at = NOW() WHERE id = ?");
    $result = $stmt->execute([htmlspecialchars($content, ENT_QUOTES, 'UTF-8'), $commentId]);

    if ($result) {
        $logger->info("Comment updated", ['comment_id' => $commentId, 'user_id' => $currentUserId]);
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to update comment']);
    }

} catch (Exception $e) {
    error_log("API Error in update comment: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
}
?>
