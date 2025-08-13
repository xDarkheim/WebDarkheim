<?php

/**
 * API endpoint for moderating comments
 * Handles approval/rejection of comments
 *
 * @author GitHub Copilot
 */

declare(strict_types=1);

header('Content-Type: application/json');

try {
    // Include bootstrap for dependencies
    require_once __DIR__ . '/../../includes/bootstrap.php';

    // Get ServiceProvider
    $serviceProvider = \App\Application\Core\ServiceProvider::getInstance($container);
    
    // Get required services
    $authService = $serviceProvider->getAuth();
    $database = $serviceProvider->getDatabase();
    $logger = $serviceProvider->getLogger();

    // Check authentication and admin access
    if (!$authService->isAuthenticated()) {
        throw new Exception('Authentication required');
    }

    $user = $authService->getCurrentUser();
    if (!$user || !in_array($user['role'] ?? '', ['admin', 'employee'])) {
        throw new Exception('Admin access required');
    }

    // Only accept POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST method allowed');
    }

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }

    // Validate required fields
    if (!isset($input['comment_id']) || !isset($input['action'])) {
        throw new Exception('Missing required fields: comment_id, action');
    }

    $commentId = (int)$input['comment_id'];
    $action = $input['action'];
    $rejectionReason = $input['rejection_reason'] ?? '';

    // Validate action
    if (!in_array($action, ['approved', 'rejected'])) {
        throw new Exception('Invalid action. Must be "approved" or "rejected"');
    }

    // Use Comments model for moderation
    $commentsModel = new \App\Domain\Models\Comments($database);

    // Perform moderation
    $result = $commentsModel->updateCommentStatus(
        $commentId, 
        $action, 
        $user['id'],
        $action === 'rejected' ? $rejectionReason : null
    );

    if ($result) {
        $logger->info("Comment moderated successfully", [
            'comment_id' => $commentId,
            'action' => $action,
            'moderated_by' => $user['id'],
            'rejection_reason' => $rejectionReason
        ]);

        echo json_encode([
            'success' => true,
            'message' => "Comment " . ($action === 'approved' ? 'approved' : 'rejected') . " successfully",
            'comment_id' => $commentId,
            'new_status' => $action
        ]);
    } else {
        throw new Exception('Failed to moderate comment');
    }

} catch (Exception $e) {
    if (isset($logger)) {
        $logger->error("API error in moderate_comment: " . $e->getMessage());
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
