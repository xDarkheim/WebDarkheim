<?php

/**
 * Middleware for authentication
 * Checks if the user is authenticated
 * If not, redirects to the login page
 * If authenticated, allows the request to proceed
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Application\Middleware;

use App\Application\Core\ServiceProvider;
use App\Domain\Interfaces\AuthenticationInterface;
use App\Domain\Interfaces\FlashMessageInterface;
use ReflectionException;


class AuthMiddleware extends SecurityMiddleware
{
    private AuthenticationInterface $auth;
    private FlashMessageInterface $flashMessage;

    /**
     * @throws ReflectionException
     */
    public function __construct()
    {
        parent::__construct();
        $services = ServiceProvider::getInstance();
        $this->auth = $services->getAuth();
        $this->flashMessage = $services->getFlashMessage();
    }

    public function handle(): bool
    {
        if (!$this->auth->isAuthenticated()) {
            $this->logSecurityEvent('Unauthorized access attempt');

            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '/index.php';
            $this->flashMessage->addError('Please log in to access this page.');
            return false;
        }

        return true;
    }
}
