<?php

/**
 * Authentication middleware to protect routes that require login
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Infrastructure\Lib;

use App\Domain\Interfaces\AuthenticationInterface;
use App\Domain\Interfaces\FlashMessageInterface;
use App\Domain\Interfaces\LoggerInterface;


readonly class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(
        private AuthenticationInterface $auth,
        private FlashMessageInterface   $flashMessage,
        private LoggerInterface         $logger
    ) {}

    /**
     * Handle the middleware
     */
    public function handle(array $request = [], callable $next = null): array
    {
        if (!$this->auth->isAuthenticated()) {
            $this->logger->info('Authentication required - redirecting to login', [
                'requested_url' => $_SERVER['REQUEST_URI'] ?? 'unknown',
                'user_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);

            // Store the originally requested URL for redirect after login
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '/';

            $this->flashMessage->addWarning(
                'Please log in to access this page.'
            );

            return [
                'redirect' => '/index.php?page=login',
                'status' => 'unauthenticated'
            ];
        }

        // User is authenticated, continue with request
        return $next ? $next($request) : ['status' => 'authenticated'];
    }
}
