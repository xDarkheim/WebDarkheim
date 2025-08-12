<?php

/**
 * Modern CSRF Middleware using SessionManager and TokenManager
 * Updated version with improved architecture
 * Handles CSRF protection for all HTTP methods
 * Uses LoggerInterface for logging
 * Uses FlashMessageInterface for flash messages
 * Uses SessionManager for session management
 * Uses TokenManager for CSRF token generation
 * Uses ServiceProvider for dependency injection
 * Provides static methods for quick validation and token generation
 * Provides backward compatibility with legacy logic
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Application\Middleware;

use App\Application\Core\MiddlewareInterface;
use App\Application\Core\SessionManager;
use App\Application\Core\ServiceProvider;
use App\Domain\Interfaces\FlashMessageInterface;
use App\Domain\Interfaces\LoggerInterface;
use Exception;
use Random\RandomException;
use ReflectionException;


class CSRFMiddleware implements MiddlewareInterface
{
    private LoggerInterface $logger;
    private FlashMessageInterface $flashMessage;
    private SessionManager $sessionManager;

    /**
     * @throws ReflectionException
     */
    public function __construct()
    {
        $services = ServiceProvider::getInstance();
        $this->logger = $services->getLogger();
        $this->flashMessage = $services->getFlashMessage();
        $this->sessionManager = $services->getSessionManager();
    }

    /**
     * Middleware processing in the new style (MiddlewareInterface)
     * @throws RandomException
     */
    public function handle(array $request, callable $next): array
    {
        // Check CSRF token only for unsafe HTTP methods
        $method = $request['method'] ?? $_SERVER['REQUEST_METHOD'] ?? 'GET';

        if (!$this->requiresCsrfProtection($method)) {
            return $next($request);
        }

        // Get token from various sources
        $providedToken = $this->extractCsrfToken($request);

        // Validate token via SessionManager (which uses TokenManager)
        if (!$this->sessionManager->validateCsrfToken($providedToken)) {
            return $this->handleCsrfFailure($request);
        }

        $this->logger->debug('CSRF token validated successfully', [
            'method' => $method,
            'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
        ]);

        return $next($request);
    }

    /**
     * Middleware processing in legacy style (for backward compatibility)
     */
    public function handleLegacy(): bool
    {
        // Check only POST requests (old logic)
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return true;
        }

        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

        if (!$this->sessionManager->validateCsrfToken($token)) {
            $this->logSecurityEvent('CSRF validation failed');
            $this->flashMessage->addError('Security error: Invalid CSRF token.');
            return false;
        }

        return true;
    }

    /**
     * Determines if the HTTP method requires CSRF protection
     */
    private function requiresCsrfProtection(string $method): bool
    {
        return in_array(strtoupper($method), ['POST', 'PUT', 'DELETE', 'PATCH'], true);
    }

    /**
     * Extracts CSRF token from the request
     */
    private function extractCsrfToken(array $request): string
    {
        // Check multiple token sources
        return $request['csrf_token']
            ?? $_POST['csrf_token']
            ?? $_SERVER['HTTP_X_CSRF_TOKEN']
            ?? $_SERVER['HTTP_X_XSRF_TOKEN'] // Additional support for SPA
            ?? '';
    }

    /**
     * Handles failed CSRF token validation
     * @throws RandomException
     */
    private function handleCsrfFailure(array $request): array
    {
        $this->logSecurityEvent('CSRF token validation failed', [
            'provided_token_present' => !empty($this->extractCsrfToken($request)),
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
            'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'unknown'
        ]);

        // Refresh CSRF token for the next request
        $this->sessionManager->refreshCsrfToken();

        // Add error to flash messages for compatibility
        $this->flashMessage->addError('Security error: Invalid CSRF token.');

        $request['errors'][] = 'CSRF token validation failed. Please try again.';
        $request['status'] = 403;

        return $request;
    }

    /**
     * Security event logging
     */
    private function logSecurityEvent(string $event, array $context = []): void
    {
        $this->logger->warning($event, array_merge([
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'referer' => $_SERVER['HTTP_REFERER'] ?? 'unknown',
            'session_id' => $this->sessionManager->getId(),
        ], $context));
    }

    /**
     * Static method for quick CSRF token validation
     * For backward compatibility with old code
     */
    public static function validateQuick(): bool
    {
        try {
            $services = ServiceProvider::getInstance();
            $sessionManager = $services->getSessionManager();

            $token = $_POST['csrf_token']
                ?? $_SERVER['HTTP_X_CSRF_TOKEN']
                ?? '';

            return $sessionManager->validateCsrfToken($token);
        } catch (Exception) {
            return false;
        }
    }

    /**
     * Static method to get CSRF token
     * For use in templates and forms
     * @throws Exception
     */
    public static function getToken(): string
    {
        try {
            $services = ServiceProvider::getInstance();
            $sessionManager = $services->getSessionManager();

            return $sessionManager->getCsrfToken();
        } catch (Exception) {
            // Fallback to a simple generation in case of error
            try {
                return bin2hex(random_bytes(32));
            } catch (RandomException $e) {
                throw new Exception('Failed to generate CSRF token: ' . $e->getMessage());
            }
        }
    }
}
