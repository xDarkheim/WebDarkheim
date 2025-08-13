<?php

/**
 * Get Ticket Details API
 * Retrieves detailed information about a specific ticket including messages
 */

declare(strict_types=1);

// Include bootstrap
require_once __DIR__ . '/../../../includes/bootstrap.php';

// Set JSON response header
header('Content-Type: application/json');

// Get global services
global $container;

try {
    // Get ServiceProvider
    $serviceProvider = \App\Application\Core\ServiceProvider::getInstance($container);

    // Get required services
    $authService = $serviceProvider->getAuth();
    $database = $serviceProvider->getDatabase();
    $flashService = $serviceProvider->getFlashMessage();
    $logger = $serviceProvider->getLogger();

    // Only allow GET requests
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Method not allowed'
        ]);
        exit;
    }

    // Create ticket controller
    $ticketController = new \App\Application\Controllers\SupportTicketController(
        $database,
        $authService,
        $flashService,
        $logger
    );

    // Handle ticket details retrieval
    $response = $ticketController->getTicketDetails();

    // Set appropriate HTTP status code
    if ($response['success']) {
        http_response_code(200); // OK
    } else {
        http_response_code(404); // Not Found or Access Denied
    }

    echo json_encode($response);

} catch (Exception $e) {
    // Handle critical errors
    if (isset($logger)) {
        $logger->critical("Critical error in ticket details API: " . $e->getMessage());
    }

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error'
    ]);
}
