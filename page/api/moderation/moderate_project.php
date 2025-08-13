<?php

/**
 * API endpoint for moderating projects
 * Handles approval/rejection of portfolio projects
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
    if (!isset($input['project_id']) || !isset($input['action'])) {
        throw new Exception('Missing required fields: project_id, action');
    }

    $projectId = (int)$input['project_id'];
    $action = $input['action'];
    $notes = $input['notes'] ?? '';

    // Validate action
    if (!in_array($action, ['published', 'rejected'])) {
        throw new Exception('Invalid action. Must be "published" or "rejected"');
    }

    // Use direct database update instead of non-existent ProjectModerationController
    $connection = $database->getConnection();

    // Update project status
    $sql = "UPDATE client_portfolio 
            SET status = ?, moderator_id = ?, moderated_at = NOW(), moderation_notes = ?
            WHERE id = ?";

    $stmt = $connection->prepare($sql);
    $result = $stmt->execute([
        $action,
        $user['id'],
        $notes,
        $projectId
    ]);

    if ($result) {
        $logger->info("Project moderated successfully", [
            'project_id' => $projectId,
            'action' => $action,
            'moderated_by' => $user['id'],
            'notes' => $notes
        ]);

        echo json_encode([
            'success' => true,
            'message' => "Project " . ($action === 'published' ? 'approved' : 'rejected') . " successfully",
            'project_id' => $projectId,
            'new_status' => $action
        ]);
    } else {
        throw new Exception('Failed to moderate project');
    }

} catch (Exception $e) {
    if (isset($logger)) {
        $logger->error("API error in moderate_project: " . $e->getMessage());
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
