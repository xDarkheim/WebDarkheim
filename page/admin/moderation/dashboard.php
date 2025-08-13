<?php

/**
 * Moderation Dashboard
 * Central admin panel for moderation overview and quick actions
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
    $data = $moderationController->handleModerationDashboard();

    // Get flash messages
    $flashMessages = $moderationController->getFlashMessages();

    // Set page title
    $pageTitle = 'Moderation Dashboard - Admin Panel';

} catch (Exception $e) {
    // Handle critical errors
    if (isset($logger)) {
        $logger->critical("Critical error in moderation dashboard: " . $e->getMessage());
    }
    
    $data = [
        'error' => 'System temporarily unavailable. Please try again later.',
        'statistics' => [],
        'recent_projects' => [],
        'recent_comments' => [],
        'notifications' => []
    ];
    $flashMessages = [];
    $pageTitle = 'Moderation Dashboard - Admin Panel';
}

// Include the view
$viewData = [
    'pageTitle' => $pageTitle,
    'statistics' => $data['statistics'] ?? [],
    'recent_projects' => $data['recent_projects'] ?? [],
    'recent_comments' => $data['recent_comments'] ?? [],
    'notifications' => $data['notifications'] ?? [],
    'flashMessages' => $flashMessages,
    'currentUser' => $authService->getCurrentUser()
];

// Load admin layout with dashboard content
include __DIR__ . '/../../../resources/views/admin/moderation/dashboard.php';
