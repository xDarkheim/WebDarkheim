<?php

/**
 * Create Support Ticket API
 * Handles creation of new support tickets for clients
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

    // Only allow POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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

    // Handle ticket creation
    $response = $ticketController->createTicket();

    // Set appropriate HTTP status code
    if ($response['success']) {
        http_response_code(201); // Created
    } else {
        http_response_code(400); // Bad Request
    }

    echo json_encode($response);

} catch (Exception $e) {
    // Handle critical errors
    if (isset($logger)) {
        $logger->critical("Critical error in ticket creation API: " . $e->getMessage());
    }

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error'
    ]);
}
