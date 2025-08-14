<?php

/**
 * API Endpoint - Navigation Stats
 * Provides real-time navigation badge counts for AJAX updates
 */

declare(strict_types=1);

// Get global services from DI container
global $container;

use App\Application\Core\ServiceProvider;
use App\Application\Components\AdminNavigation;

try {
    // Set JSON response headers
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

    // Check if it's an AJAX request
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
        http_response_code(403);
        echo json_encode(['error' => 'Direct access not allowed']);
        exit;
    }

    // Get ServiceProvider for accessing services
    $serviceProvider = ServiceProvider::getInstance($container);
    
    // Get required services
    $authService = $serviceProvider->getAuth();
    $database = $serviceProvider->getDatabase();

    // Check authentication
    $currentUser = $authService->getCurrentUser();
    if (!$currentUser) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        exit;
    }

    // Create AdminNavigation to get badge counts - it will auto-inject database
    $adminNavigation = new AdminNavigation($authService);

    $userRole = $currentUser['role'] ?? 'guest';
    $stats = [];

    // Get moderation statistics for admin/employee
    if (in_array($userRole, ['admin', 'employee'])) {
        $moderationCount = $adminNavigation->getBadgeCount('admin_moderation_dashboard') ?? 0;
        $stats['admin_moderation_dashboard'] = [
            'count' => $moderationCount,
            'breakdown' => []
        ];
    }

    // Get ticket statistics for clients
    if ($userRole === 'client') {
        $ticketCount = $adminNavigation->getBadgeCount('user_tickets') ?? 0;
        $stats['user_tickets'] = [
            'count' => $ticketCount,
            'breakdown' => []
        ];
    }

    // Return JSON response
    echo json_encode($stats);

} catch (Exception $e) {
    error_log("Navigation stats API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
