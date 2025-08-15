<?php

/**
 * Enhanced Secure Session Manager
 * Implements SessionManagerInterface and follows SOLID principles
 * - Single Responsibility: Only manages session operations
 * - Open/Closed: Extensible through configuration
 * - Dependency Inversion: Depends on abstractions
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Application\Core;

use App\Domain\Interfaces\LoggerInterface;
use App\Domain\Interfaces\SessionManagerInterface;
use App\Domain\Interfaces\TokenManagerInterface;
use Exception;
use InvalidArgumentException;
use Random\RandomException;

class SessionManager implements SessionManagerInterface
{
    private static ?SessionManager $instance = null;
    private bool $sessionStarted = false;
    private array $config;
    private array $flashData = [];

    private function __construct(
        private readonly LoggerInterface $logger,
        array $config = [],
        private readonly ?ConfigurationManager $configManager = null,
        private readonly ?TokenManagerInterface $tokenManager = null
    ) {
        $this->config = $this->buildConfiguration($config);
    }

    public static function getInstance(
        LoggerInterface $logger,
        array $config = [],
        ?ConfigurationManager $configManager = null,
        ?TokenManagerInterface $tokenManager = null
    ): self {
        if (self::$instance === null) {
            self::$instance = new self($logger, $config, $configManager, $tokenManager);
        }
        return self::$instance;
    }

    /**
     * Start session with secure configuration
     */
    public function start(): bool
    {
        if ($this->sessionStarted) {
            return true;
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            $this->sessionStarted = true;
            return true;
        }

        if (headers_sent($filename, $linenum)) {
            $this->logger->warning('Cannot start session - headers already sent', [
                'file' => $filename,
                'line' => $linenum
            ]);
            return false;
        }

        $this->configureSession();

        try {
            $started = session_start();
            if ($started) {
                $this->sessionStarted = true;
                $this->validateSession();
                $this->loadFlashData();
                $this->logger->debug('Session started successfully');
            }
            return $started;
        } catch (Exception $e) {
            $this->logger->error('Failed to start session', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Destroy session completely
     */
    public function destroy(): bool
    {
        if (!$this->isActive()) {
            return true;
        }

        try {
            $_SESSION = [];

            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    [
                        'expires' => time() - 42000,
                        'path' => $params['path'],
                        'domain' => $params['domain'],
                        'secure' => $params['secure'],
                        'httponly' => $params['httponly'],
                        'samesite' => $params['samesite']
                    ]
                );
            }

            $destroyed = session_destroy();
            if ($destroyed) {
                $this->sessionStarted = false;
                $this->logger->info('Session destroyed successfully');
            }
            return $destroyed;
        } catch (Exception $e) {
            $this->logger->error('Failed to destroy session', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Regenerate session ID for security
     */
    public function regenerateId(bool $deleteOldSession = false): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        try {
            $regenerated = session_regenerate_id($deleteOldSession);
            if ($regenerated) {
                $this->logger->debug('Session ID regenerated');
            }
            return $regenerated;
        } catch (Exception $e) {
            $this->logger->error('Failed to regenerate session ID', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Get session data
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if (!$this->isActive()) {
            return $default;
        }

        return $_SESSION[$key] ?? $default;
    }

    /**
     * Set session data
     */
    public function set(string $key, mixed $value): void
    {
        if (!$this->isActive()) {
            $this->logger->warning('Attempted to set session data when session is not active', ['key' => $key]);
            return;
        }

        $_SESSION[$key] = $value;
    }

    /**
     * Check if session has key
     */
    public function has(string $key): bool
    {
        return $this->isActive() && isset($_SESSION[$key]);
    }

    /**
     * Remove session data
     */
    public function remove(string $key): void
    {
        if ($this->isActive() && isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }

    /**
     * Get all session data
     */
    public function all(): array
    {
        return $this->isActive() ? $_SESSION : [];
    }

    /**
     * Flash message for next request
     */
    public function flash(string $key, mixed $value): void
    {
        if (!$this->isActive()) {
            $this->flashData[$key] = $value;
            return;
        }

        if (!isset($_SESSION['_flash'])) {
            $_SESSION['_flash'] = [];
        }
        $_SESSION['_flash'][$key] = $value;
    }

    /**
     * Check if session is active
     */
    public function isActive(): bool
    {
        return $this->sessionStarted && session_status() === PHP_SESSION_ACTIVE;
    }

    /**
     * Build configuration array
     */
    private function buildConfiguration(array $config): array
    {
        $defaultConfig = [
            'name' => 'DARKHEIM_SESSION',
            'lifetime' => 3600, // 1 hour
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httponly' => true,
            'samesite' => 'Strict',
            'csrf_lifetime' => 1800 // 30 minutes
        ];

        // Load settings from database if available
        if ($this->configManager) {
            try {
                $dbConfig = $this->loadConfigurationFromDatabase();
                $config = array_merge($config, $dbConfig);
            } catch (Exception $e) {
                $this->logger->warning('Failed to load session configuration from database, using defaults', [
                    'error' => $e->getMessage()
                ]);
            }
        }

        return array_merge($defaultConfig, $config);
    }

    /**
     * Loads session configuration from database
     */
    private function loadConfigurationFromDatabase(): array
    {
        $config = [];
        
        if ($this->configManager) {
            // Load session lifetime
            $sessionLifetime = $this->configManager->get('security.session_lifetime');
            if ($sessionLifetime !== null) {
                $lifetimeValue = is_array($sessionLifetime) ? ($sessionLifetime['value'] ?? $sessionLifetime) : $sessionLifetime;
                $config['lifetime'] = (int)$lifetimeValue;
            }
            
            // Load CSRF token lifetime
            $csrfLifetime = $this->configManager->get('security.csrf_token_lifetime');
            if ($csrfLifetime !== null) {
                $csrfValue = is_array($csrfLifetime) ? ($csrfLifetime['value'] ?? $csrfLifetime) : $csrfLifetime;
                $config['csrf_lifetime'] = (int)$csrfValue;
            }
            
            $this->logger->debug('Session configuration loaded from database', [
                'session_lifetime' => $config['lifetime'] ?? 'not set',
                'csrf_lifetime' => $config['csrf_lifetime'] ?? 'not set'
            ]);
        }
        
        return $config;
    }

    /**
     * Configure session parameters
     */
    private function configureSession(): void
    {
        session_name($this->config['name']);
        session_set_cookie_params([
            'lifetime' => $this->config['lifetime'],
            'path' => $this->config['path'],
            'domain' => $this->config['domain'],
            'secure' => $this->config['secure'],
            'httponly' => $this->config['httponly'],
            'samesite' => $this->config['samesite']
        ]);
    }

    /**
     * Validate session data
     */
    private function validateSession(): void
    {
        $sessionLifetime = $this->config['lifetime'];
        $lastActivity = $this->get('last_activity', 0);

        if ($lastActivity && (time() - $lastActivity) > $sessionLifetime) {
            $this->logger->warning('Session expired', [
                'last_activity' => date('Y-m-d H:i:s', $lastActivity),
                'lifetime' => $sessionLifetime
            ]);
            $this->destroy();
        } else {
            // Update last activity time
            $this->set('last_activity', time());
        }
    }

    /**
     * Load flash data from session
     */
    private function loadFlashData(): void
    {
        if (isset($_SESSION['_flash'])) {
            foreach ($_SESSION['_flash'] as $key => $value) {
                $this->flashData[$key] = $value;
            }
            unset($_SESSION['_flash']);
        }
    }

    /**
     * Get flash message
     */
    public function getFlash(string $key, $default = null)
    {
        return $this->flashData[$key] ?? $default;
    }

    /**
     * CSRF token using TokenManager for enhanced security
     * @throws RandomException
     */
    public function getCsrfToken(): string
    {
        $token = $this->get('csrf_token');
        $tokenTime = $this->get('csrf_token_time', 0);
        $csrfLifetime = $this->config['csrf_lifetime'];

        // Check if the token needs to be refreshed (expired or missing)
        if (!$token || (time() - $tokenTime) > $csrfLifetime) {
            // Use TokenManager if available, otherwise fallback to the old method
            if ($this->tokenManager) {
                $token = $this->tokenManager->generateToken(64); // Longer token for CSRF
                $this->logger->debug('CSRF token generated using TokenManager');
            } else {
                $token = bin2hex(random_bytes(32));
                $this->logger->debug('CSRF token generated using fallback method');
            }

            $this->set('csrf_token', $token);
            $this->set('csrf_token_time', time());
        }

        return $token;
    }

    /**
     * CSRF token validation with enhanced security
     */
    public function validateCsrfToken(string $token): bool
    {
        $sessionToken = $this->get('csrf_token');
        $tokenTime = $this->get('csrf_token_time', 0);
        $csrfLifetime = $this->config['csrf_lifetime'];

        if (!$sessionToken || (time() - $tokenTime) > $csrfLifetime) {
            $this->logger->debug('CSRF token expired or missing', [
                'token_time' => $tokenTime ? date('Y-m-d H:i:s', $tokenTime) : 'not set',
                'csrf_lifetime' => $csrfLifetime,
                'current_time' => date('Y-m-d H:i:s')
            ]);
            return false;
        }

        $isValid = hash_equals($sessionToken, $token);

        if ($isValid) {
            $this->logger->debug('CSRF token validated successfully');
        } else {
            $this->logger->warning('CSRF token validation failed', [
                'expected_length' => strlen($sessionToken),
                'provided_length' => strlen($token)
            ]);
        }

        return $isValid;
    }

    /**
     * Get user state from session
     */
    public function getUserState(): array
    {
        return [
            'authenticated' => $this->has('user_id'),
            'user_id' => $this->get('user_id'),
            'username' => $this->get('username'),
            'user_role' => $this->get('user_role'),
            'auto_login' => $this->get('auto_login', false)
        ];
    }

    /**
     * Set user state in session
     */
    public function setUserState(array $userData): void
    {
        if (!$this->isActive()) {
            $this->logger->warning('Attempted to set user state when session is not active');
            return;
        }

        // Set user data in session
        $this->set('user_id', $userData['id'] ?? null);
        $this->set('username', $userData['username'] ?? null);
        $this->set('user_role', $userData['role'] ?? 'user');

        // Optional fields
        if (isset($userData['email'])) {
            $this->set('user_email', $userData['email']);
        }
        if (isset($userData['first_name'])) {
            $this->set('user_first_name', $userData['first_name']);
        }
        if (isset($userData['last_name'])) {
            $this->set('user_last_name', $userData['last_name']);
        }

        $this->logger->debug('User state set in session', [
            'user_id' => $userData['id'] ?? null,
            'username' => $userData['username'] ?? null
        ]);
    }

    /**
     * Clear user state from session (logout)
     */
    public function clearUserState(): void
    {
        if (!$this->isActive()) {
            return;
        }

        $userId = $this->get('user_id');

        // Remove user-related session data
        $this->remove('user_id');
        $this->remove('username');
        $this->remove('user_role');
        $this->remove('user_email');
        $this->remove('user_first_name');
        $this->remove('user_last_name');
        $this->remove('auto_login');

        $this->logger->info('User state cleared from session', [
            'user_id' => $userId
        ]);
    }

    /**
     * Force CSRF token refresh
     * @throws RandomException
     */
    public function refreshCsrfToken(): string
    {
        $this->remove('csrf_token');
        $this->remove('csrf_token_time');
        return $this->getCsrfToken();
    }

}
