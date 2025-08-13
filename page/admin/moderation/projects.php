<?php

/**
 * Projects Moderation Page
 * Administrative interface for moderating client portfolio projects
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

    // Create moderation controller
    $moderationController = new \App\Application\Controllers\ModerationController(
        $database,
        $authService,
        $flashService,
        $logger
    );

    // Handle request
    $data = $moderationController->handleProjectsModeration();

    // Get flash messages
    $flashMessages = $moderationController->getFlashMessages();

    // Set page title
    $pageTitle = 'Projects Moderation - Admin Panel';

} catch (Exception $e) {
    // Handle critical errors
    if (isset($logger)) {
        $logger->critical("Critical error in projects moderation page: " . $e->getMessage());
    }

    $data = [
        'error' => 'System temporarily unavailable. Please try again later.',
        'projects' => [],
        'statistics' => []
    ];
    $flashMessages = [];
    $pageTitle = 'Projects Moderation - Admin Panel';
}

// Include the view
$viewData = [
    'pageTitle' => $pageTitle,
    'projects' => $data['projects'] ?? [],
    'statistics' => $data['statistics'] ?? [],
    'filters' => $data['filters'] ?? [],
    'pagination' => $data['pagination'] ?? [],
    'flashMessages' => $flashMessages,
    'currentUser' => $authService->getCurrentUser()
];

// Load admin layout with moderation content
include __DIR__ . '/../../../resources/views/admin/moderation/projects_list.php';
