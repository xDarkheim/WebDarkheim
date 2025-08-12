<?php

/**
 * Base controller for all controllers
 * This class provides common functionality and methods for all controllers.
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Application\Controllers;

use App\Application\Core\ServiceProvider;
use App\Application\Core\SessionManager;
use App\Domain\Interfaces\FlashMessageInterface;
use App\Domain\Interfaces\LoggerInterface;
use ReflectionException;

abstract class BaseFormController
{
    protected ServiceProvider $services;
    protected FlashMessageInterface $flashMessage;
    protected LoggerInterface $logger;

    /**
     * @throws ReflectionException
     */
    public function __construct()
    {
        $this->services = ServiceProvider::getInstance();
        $this->flashMessage = $this->services->getFlashMessage();
        $this->logger = $this->services->getLogger();
    }

    /**
     * Валидация CSRF токена
     * @throws ReflectionException
     */
    protected function validateCSRF(): bool
    {
        // 1) Get the token from different sources (POST -> Header -> Cookie)
        $token = $_POST['csrf_token'] ?? '';

        if ($token === '' && isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            $token = (string) $_SERVER['HTTP_X_CSRF_TOKEN'];
        }
        if ($token === '' && isset($_SERVER['HTTP_X_CSRFTOKEN'])) {
            $token = (string) $_SERVER['HTTP_X_CSRFTOKEN'];
        }
        if ($token === '' && isset($_COOKIE['csrf_token'])) {
            $token = (string) $_COOKIE['csrf_token'];
        }
        if ($token === '' && isset($_COOKIE['csrf_token_auth'])) {
            $token = (string) $_COOKIE['csrf_token_auth'];
        }

        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        if (empty($token)) {
            $this->logger->warning('CSRF validation failed: no token provided', [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
            return false;
        }

        // 2) Standard check via SessionManager
        $configManager = $this->services->getConfigurationManager();
        $sessionManager = SessionManager::getInstance($this->logger, [], $configManager);

        $sessionToken = $sessionManager->get('csrf_token');
        $sessionTokenTime = $sessionManager->get('csrf_token_time', 0);

        $this->logger->debug('CSRF validation details', [
            'post_token' => $token,
            'session_token' => $sessionToken,
            'session_token_time' => $sessionTokenTime,
            'token_age' => time() - $sessionTokenTime,
            'session_id' => session_id(),
            'session_status' => session_status()
        ]);

        $isValid = $sessionManager->validateCsrfToken($token);

        if ($isValid) {
            return true;
        }

        // 3) Fallback: direct comparison with common session keys
        // This covers cases where the token was issued by another subsystem (e\.g\. maintenance/CSRFMiddleware)
        $fallbackSessionTokens = [];
        if (isset($_SESSION['csrf_token'])) {
            $fallbackSessionTokens[] = (string) $_SESSION['csrf_token'];
        }
        if (isset($_SESSION['csrf_token_login'])) {
            $fallbackSessionTokens[] = (string) $_SESSION['csrf_token_login'];
        }
        if (isset($_SESSION['csrf_token_auth'])) {
            $fallbackSessionTokens[] = (string) $_SESSION['csrf_token_auth'];
        }
        if (isset($_SESSION['csrf']['token'])) {
            $fallbackSessionTokens[] = (string) $_SESSION['csrf']['token'];
        }

        foreach ($fallbackSessionTokens as $idx => $sessTok) {
            if (!empty($sessTok) && hash_equals($sessTok, $token)) {
                $this->logger->info('CSRF validation passed via fallback session token', [
                    'source_index' => $idx,
                    'session_id' => session_id()
                ]);
                return true;
            }
        }

        // 4) If we reached here, none of the checks passed
        $this->logger->warning('CSRF validation failed', [
            'post_token' => $token,
            'session_token' => $sessionToken,
            'tokens_match' => hash_equals($sessionToken ?: '', $token),
            'token_age' => time() - $sessionTokenTime,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);

        return false;
    }

    /**
     * Request method check
     */

    protected function requirePostMethod(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    /**
     * Simple redirect
     */

    protected function redirect(string $url): void
    {
        // Валидация URL для предотвращения open redirect
        if (!str_starts_with($url, '/') && !str_starts_with($url, 'http')) {
            $url = '/index.php';
        }

        header("Location: $url");
        exit;
    }

    /**
     * Handle validation error
     */

    protected function handleValidationError(string $message, string $redirectUrl): void
    {
        $this->flashMessage->addError($message);
        $this->redirect($redirectUrl);
    }
}
