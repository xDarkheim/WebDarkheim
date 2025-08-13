<?php

/**
 * Project Details Moderation Page
 * Detailed view and moderation interface for a specific client project
 *
 * @author GitHub Copilot
 */

declare(strict_types=1);

// Get global services from DI container
global $container;

try {
    // Get ServiceProvider for accessing services
    $serviceProvider = \App\Application\Core\ServiceProvider::getInstance($container);

    // Get required services
    $authService = $serviceProvider->getAuth();
    $flashService = $serviceProvider->getFlashMessage();
    $logger = $serviceProvider->getLogger();
    $database = $serviceProvider->getDatabase();

    // Get project ID from request
    $projectId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if (!$projectId) {
        throw new Exception('Project ID is required');
    }

    // Create moderation controller
    $moderationController = new \App\Application\Controllers\ModerationController(
        $database,
        $authService,
        $flashService,
        $logger
    );

    // Handle request
    $data = $moderationController->handleProjectDetails($projectId);

    // Get flash messages
    $flashMessages = $moderationController->getFlashMessages();

    // Set page title
    $pageTitle = 'Project Details - ' . ($data['project']['title'] ?? 'Unknown') . ' - Admin Panel';

} catch (Exception $e) {
    // Handle critical errors
    if (isset($logger)) {
        $logger->critical("Critical error in project details page: " . $e->getMessage());
    }

    $data = [
        'error' => $e->getMessage(),
        'project' => null,
        'comments' => [],
        'history' => []
    ];
    $flashMessages = [];
    $pageTitle = 'Project Details - Admin Panel';
}

// Include the view
$viewData = [
    'pageTitle' => $pageTitle,
    'project' => $data['project'],
    'comments' => $data['comments'] ?? [],
    'history' => $data['history'] ?? [],
    'flashMessages' => $flashMessages,
    'currentUser' => $authService->getCurrentUser()
];

// Load admin layout with project details content
include __DIR__ . '/../../../resources/views/admin/moderation/project_details.php';
