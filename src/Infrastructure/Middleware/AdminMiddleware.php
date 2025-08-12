<?php

/**
 * Admin Middleware
 * Ensures only administrators can access admin pages
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Infrastructure\Middleware;

use App\Domain\Interfaces\AuthenticationInterface;
use App\Domain\Interfaces\FlashMessageInterface;


readonly class AdminMiddleware
{
    public function __construct(
        private AuthenticationInterface $auth,
        private FlashMessageInterface   $flashMessage
    ) {}

    public function handle(): bool
    {
        // Check if the user is authenticated
        if (!$this->auth->isAuthenticated()) {
            $this->flashMessage->addError('You must be logged in to access this page.');
            header('Location: /index.php?page=login');
            exit();
        }

        // Check if a user has an admin role
        if (!$this->auth->hasRole('admin')) {
            $this->flashMessage->addError('Access denied. Administrator privileges required.');
            header('Location: /index.php?page=dashboard');
            exit();
        }

        return true;
    }
}
