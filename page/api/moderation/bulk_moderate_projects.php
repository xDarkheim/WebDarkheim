<?php

/**
 * API endpoint for bulk moderating projects
 * Handles mass approval/rejection of portfolio projects
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
    if (!isset($input['project_ids']) || !isset($input['action'])) {
        throw new Exception('Missing required fields: project_ids, action');
    }

    $projectIds = $input['project_ids'];
    $action = $input['action'];
    $notes = $input['notes'] ?? '';

    // Validate project IDs
    if (!is_array($projectIds) || empty($projectIds)) {
        throw new Exception('project_ids must be a non-empty array');
    }

    // Validate action
    $actionMapping = [
        'approve' => 'published',
        'reject' => 'rejected',
        'published' => 'published',
        'rejected' => 'rejected'
    ];

    if (!array_key_exists($action, $actionMapping)) {
        throw new Exception('Invalid action. Must be "approve", "reject", "published", or "rejected"');
    }

    $dbAction = $actionMapping[$action];

    // Use database transaction for bulk operations
    $connection = $database->getConnection();
    $connection->beginTransaction();

    try {
        $successCount = 0;
        $failedIds = [];

        foreach ($projectIds as $projectId) {
            $projectId = (int)$projectId;

            $sql = "UPDATE client_portfolio 
                    SET status = ?, moderator_id = ?, moderated_at = NOW(), moderation_notes = ?
                    WHERE id = ?";

            $stmt = $connection->prepare($sql);
            $result = $stmt->execute([
                $dbAction,
                $user['id'],
                $notes,
                $projectId
            ]);

            if ($result && $stmt->rowCount() > 0) {
                $successCount++;
            } else {
                $failedIds[] = $projectId;
            }
        }

        $connection->commit();

        $logger->info("Bulk project moderation completed", [
            'action' => $action,
            'moderated_by' => $user['id'],
            'success_count' => $successCount,
            'failed_count' => count($failedIds),
            'failed_ids' => $failedIds
        ]);

        $message = $successCount . ' project' . ($successCount !== 1 ? 's' : '') . ' ' .
                  ($action === 'approve' ? 'approved' : 'rejected') . ' successfully';

        if (!empty($failedIds)) {
            $message .= '. ' . count($failedIds) . ' project' . (count($failedIds) !== 1 ? 's' : '') . ' failed to update.';
        }

        echo json_encode([
            'success' => true,
            'message' => $message,
            'processed_count' => count($projectIds),
            'success_count' => $successCount,
            'failed_count' => count($failedIds),
            'failed_ids' => $failedIds
        ]);

    } catch (Exception $e) {
        $connection->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    if (isset($logger)) {
        $logger->error("API error in bulk_moderate_projects: " . $e->getMessage());
    }

    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
