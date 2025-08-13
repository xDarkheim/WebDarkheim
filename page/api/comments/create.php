<?php
/**
 * API endpoint for creating new comments
 * POST /page/api/comments/create.php
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
    $logger = $serviceProvider->getLogger();
    $database = $serviceProvider->getDatabase();

    $commentService = new CommentService($database, $logger);

    // Prepare comment data
    $data = [
        'commentable_type' => $_POST['commentable_type'] ?? '',
        'commentable_id' => (int)($_POST['commentable_id'] ?? 0),
        'content' => trim($_POST['content'] ?? ''),
        'parent_id' => !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null
    ];

    // Get user info if authenticated
    if ($authService->isAuthenticated()) {
        $userId = $authService->getCurrentUserId();
        $userEmail = $authService->getCurrentUserEmail();
        $username = $authService->getCurrentUsername();

        $data['user_id'] = $userId;
        $data['author_name'] = $username;
        $data['author_email'] = $userEmail;
    } else {
        // Guest comment
        $data['author_name'] = trim($_POST['author_name'] ?? '');
        $data['author_email'] = trim($_POST['author_email'] ?? '');

        if (empty($data['author_name']) || empty($data['author_email'])) {
            echo json_encode(['success' => false, 'error' => 'Name and email required for guest comments']);
            exit;
        }

        if (!filter_var($data['author_email'], FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'error' => 'Invalid email address']);
            exit;
        }
    }

    $result = $commentService->createComment($data);

    http_response_code($result['success'] ? 201 : 400);
    echo json_encode($result);

} catch (Exception $e) {
    error_log("API Error in create comment: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
}
?>
