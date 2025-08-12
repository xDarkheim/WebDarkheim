<?php

/**
 * Admin authentication middleware - requires admin role
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Infrastructure\Lib;

use App\Domain\Interfaces\AuthenticationInterface;
use App\Domain\Interfaces\FlashMessageInterface;
use App\Domain\Interfaces\LoggerInterface;



readonly class AdminMiddleware implements MiddlewareInterface
{
    public function __construct(
        private AuthenticationInterface $auth,
        private FlashMessageInterface   $flashMessage,
        private LoggerInterface         $logger
    )
    {
    }

    /**
     * Handle the middleware
     */
    public function handle(array $request = [], callable $next = null): array
    {
        // First check if user is authenticated
        if (!$this->auth->isAuthenticated() || $this->auth->getCurrentUserRole() !== 'admin') {
            $this->logger->warning('Admin access denied', [
                'user_id' => $this->auth->getCurrentUserId(),
                'username' => $this->auth->getCurrentUsername(),
                'role' => $this->auth->getCurrentUserRole(),
                'requested_url' => $_SERVER['REQUEST_URI'] ?? 'unknown'
            ]);

            $this->flashMessage->addError(
                'Access denied. Administrator privileges required.'
            );

            // Redirect to login if not authenticated, otherwise to home
            $redirectUrl = $this->auth->isAuthenticated() ? '/index.php?page=home' : '/index.php?page=login';

            return [
                'redirect' => $redirectUrl,
                'status' => 'access_denied'
            ];
        }

        // User is admin, continue with request
        return $next ? $next($request) : ['status' => 'authorized'];
    }
}