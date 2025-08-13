<?php

/**
 * Update Ticket Status API
 * Handles status updates for support tickets (admin/employee only)
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
    
    // Handle status update
    $response = $ticketController->updateTicketStatus();
    
    // Set appropriate HTTP status code
    if ($response['success']) {
        http_response_code(200); // OK
    } else {
        http_response_code(400); // Bad Request
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    // Handle critical errors
    if (isset($logger)) {
        $logger->critical("Critical error in update status API: " . $e->getMessage());
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error'
    ]);
}
