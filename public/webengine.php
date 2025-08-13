<?php

/**
 * Webengine entry point
 *
 * This file is the main entry point for the webengine.
 * It includes the bootstrap file and handles the application initialization.
 * It also handles errors and exceptions.
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

global $container;

require_once dirname(__DIR__) . '/includes/bootstrap.php';

use App\Application\Controllers\ErrorController;
use App\Application\Core\ServiceProvider;
use App\Application\Core\Application;

try {
    // Initialize ServiceProvider instead of global variables
    $services = ServiceProvider::getInstance($container);

    // Create the main application
    $app = new Application($services);

    // Run the application
    $app->run();
} catch (Throwable $e) {
    // Ensure a session is started to save error data
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    // Use ErrorController to handle the error
    try {
        if (isset($services)) {
            $logger = $services->getLogger();
            $errorController = new ErrorController($logger);
            $errorData = $errorController->handleError($e);

            // Always use the pretty error.php page (in both debug and production)
        } else {
            // Fallback if services are unavailable
            error_log('Critical Error (no services): ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());

            // Save to session for a legacy system
            $_SESSION['error_message'] = $e->getMessage();
            $_SESSION['error_trace'] = $e->getTraceAsString();
            $_SESSION['error_context'] = [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'code' => $e->getCode(),
                'timestamp' => date('Y-m-d H:i:s'),
                'url' => $_SERVER['REQUEST_URI'] ?? 'Unknown',
                'method' => $_SERVER['REQUEST_METHOD'] ?? 'Unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
            ];

            // Use the pretty error.php page even in fallback mode
        }
        include ROOT_PATH . DS . 'page' . DS . 'system' . DS . 'error.php';
    } catch (Throwable $fallbackError) {
        // Last level of protection
        error_log('Fallback error handler failed: ' . $fallbackError->getMessage());
        echo '<h1>Critical System Error</h1>';
        echo '<p>A critical error occurred and the error handling system failed. Please contact the administrator.</p>';
        if (isDebugMode()) {
            echo '<pre>Original Error: ' . htmlspecialchars($e->getMessage()) . "\nFallback Error: " . htmlspecialchars($fallbackError->getMessage()) . '</pre>';
        }
    }
    exit;
}
