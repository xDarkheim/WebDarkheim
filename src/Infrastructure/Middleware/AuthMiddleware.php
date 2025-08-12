<?php

/**
 * Authentication middleware
 * Checks if the user is authenticated
 * If not, redirects to the login page
 *
 * @author
 */

namespace App\Infrastructure\Middleware;

class AuthMiddleware {

    public function handle(): bool {
        // Check if the user is authenticated
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
            global $logger;

            if ($logger) {
                $logger->warning('Unauthorized access attempt', [
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                    'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
                ]);
            }

            // Redirect to login page
            header('Location: /index.php?page=login');
            exit;
        }

        global $logger;
        if ($logger) {
            $logger->debug('Auth middleware passed', [
                'user_id' => $_SESSION['user_id'],
                'username' => $_SESSION['username']
            ]);
        }

        return true;
    }
}
