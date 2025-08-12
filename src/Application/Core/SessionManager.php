<?php

/**
 * Secure Session Manager
 * Replaces direct $_SESSION access with controlled methods
 * Provides session management, CSRF protection, and user state handling
 * Uses ConfigurationManager for dynamic settings loading
 * Uses TokenManagerInterface for secure CSRF token generation
 * Handles session expiration and user state
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Application\Core;

use App\Domain\Interfaces\LoggerInterface;
use App\Domain\Interfaces\TokenManagerInterface;
use Exception;
use InvalidArgumentException;
use Random\RandomException;


class SessionManager
{
    private static ?SessionManager $instance = null;
    private LoggerInterface $logger;
    private bool $sessionStarted = false;
    private array $config;
    private ?ConfigurationManager $configManager;
    private ?TokenManagerInterface $tokenManager;

    private function __construct(LoggerInterface $logger, array $config = [], ?ConfigurationManager $configManager = null, ?TokenManagerInterface $tokenManager = null)
    {
        $this->logger = $logger;
        $this->configManager = $configManager;
        $this->tokenManager = $tokenManager;

        // Default base settings
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

        $this->config = array_merge($defaultConfig, $config);
    }

    public static function getInstance(?LoggerInterface $logger = null, array $config = [], ?ConfigurationManager $configManager = null, ?TokenManagerInterface $tokenManager = null): SessionManager
    {
        if (self::$instance === null) {
            if ($logger === null) {
                throw new InvalidArgumentException('Logger is required for SessionManager initialization');
            }
            self::$instance = new self($logger, $config, $configManager, $tokenManager);
        }
        return self::$instance;
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
     * Secure session start
     */
    public function start(): bool
    {
        if ($this->sessionStarted || session_status() === PHP_SESSION_ACTIVE) {
            return true;
        }

        // Check if headers have already been sent
        if (headers_sent()) {
            $this->logger->warning('Cannot start session: headers already sent');
            return false;
        }

        // Set session parameters only if the session is not started yet
        if (session_status() === PHP_SESSION_NONE) {
            session_name($this->config['name']);
            session_set_cookie_params([
                'lifetime' => $this->config['lifetime'],
                'path' => $this->config['path'],
                'domain' => $this->config['domain'],
                'secure' => $this->config['secure'],
                'httponly' => $this->config['httponly'],
                'samesite' => $this->config['samesite']
            ]);

            $result = session_start();
        } else {
            // Session is already active
            $result = true;
        }

        if ($result) {
            $this->sessionStarted = true;
            $this->logger->debug('Session started', [
                'session_id' => session_id(),
                'session_name' => session_name()
            ]);
        } else {
            $this->logger->error('Failed to start session');
        }

        return $result;
    }

    /**
     * Get value from a session
     */
    public function get(string $key, $default = null)
    {
        $this->ensureSessionStarted();
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Set value in session
     */
    public function set(string $key, $value): void
    {
        $this->ensureSessionStarted();
        $_SESSION[$key] = $value;
        $this->logger->debug("Session value set: $key");
    }

    /**
     * Check if key exists in session
     */
    public function has(string $key): bool
    {
        $this->ensureSessionStarted();
        return isset($_SESSION[$key]);
    }

    /**
     * Remove value from the session
     */
    public function unset(string $key): void
    {
        $this->ensureSessionStarted();
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
            $this->logger->debug("Session value unset: $key");
        }
    }

    /**
     * Get all session data
     */
    public function all(): array
    {
        $this->ensureSessionStarted();
        return $_SESSION;
    }

    /**
     * Clear all session data
     */
    public function clear(): void
    {
        $this->ensureSessionStarted();
        $_SESSION = [];
        $this->logger->info('Session cleared');
    }

    /**
     * Regenerate session ID
     */
    public function regenerateId(bool $deleteOldSession = true): bool
    {
        $this->ensureSessionStarted();
        $oldId = session_id();
        $result = session_regenerate_id($deleteOldSession);

        if ($result) {
            $this->logger->info('Session ID regenerated', [
                'old_id' => $oldId,
                'new_id' => session_id()
            ]);
        } else {
            $this->logger->error('Failed to regenerate session ID');
        }

        return $result;
    }

    /**
     * Destroy session
     */
    public function destroy(): bool
    {
        if (!$this->sessionStarted && session_status() !== PHP_SESSION_ACTIVE) {
            return true;
        }

        $sessionId = session_id();
        $result = session_destroy();

        if ($result) {
            $this->sessionStarted = false;
            $this->logger->info('Session destroyed', ['session_id' => $sessionId]);
        } else {
            $this->logger->error('Failed to destroy session');
        }

        return $result;
    }

    /**
     * Flash messages
     */
    public function flash(string $key, $value): void
    {
        $this->set("flash_$key", $value);
    }

    /**
     * Get flash message
     */
    public function getFlash(string $key, $default = null)
    {
        $flashKey = "flash_$key";
        $value = $this->get($flashKey, $default);
        $this->unset($flashKey);
        return $value;
    }

    /**
     * Check session validity
     */
    public function isValid(): bool
    {
        if (!$this->sessionStarted && session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }

        $sessionLifetime = $this->config['lifetime'];
        $lastActivity = $this->get('last_activity', 0);

        if ($lastActivity && (time() - $lastActivity) > $sessionLifetime) {
            $this->logger->warning('Session expired', [
                'last_activity' => date('Y-m-d H:i:s', $lastActivity),
                'lifetime' => $sessionLifetime
            ]);
            return false;
        }

        // Update last activity time
        $this->set('last_activity', time());
        return true;
    }

    /**
     * Get session ID
     */
    public function getId(): string
    {
        $this->ensureSessionStarted();
        return session_id();
    }

    /**
     * Get session name
     */
    public function getName(): string
    {
        return session_name();
    }

    /**
     * Check if the session is started
     */
    public function isStarted(): bool
    {
        return $this->sessionStarted || session_status() === PHP_SESSION_ACTIVE;
    }

    /**
     * Ensure the session is started
     */
    private function ensureSessionStarted(): void
    {
        if (!$this->isStarted()) {
            $this->start();
        }
    }

    /**
     * Save user state
     */
    public function setUserState(array $userData): void
    {
        $this->set('user_id', $userData['id'] ?? null);
        $this->set('username', $userData['username'] ?? null);
        $this->set('user_role', $userData['role'] ?? null);
        $this->set('user_email', $userData['email'] ?? null);
        $this->set('user_authenticated', true);

        $this->logger->info('User state saved to session', [
            'user_id' => $userData['id'] ?? null,
            'username' => $userData['username'] ?? null
        ]);
    }

    /**
     * Get user state
     */
    public function getUserState(): array
    {
        return [
            'id' => $this->get('user_id'),
            'username' => $this->get('username'),
            'role' => $this->get('user_role'),
            'email' => $this->get('user_email'),
            'authenticated' => $this->get('user_authenticated', false),
        ];
    }

    /**
     * Clear user state
     */
    public function clearUserState(): void
    {
        $this->ensureSessionStarted();

        if (isset($_SESSION['user_id'])) {
            $this->unset('user_id');
        }
        if (isset($_SESSION['username'])) {
            $this->unset('username');
        }
        if (isset($_SESSION['user_role'])) {
            $this->unset('user_role');
        }
        if (isset($_SESSION['user_email'])) {
            $this->unset('user_email');
        }
        if (isset($_SESSION['user_authenticated'])) {
            $this->unset('user_authenticated');
        }

        $this->logger->info('User state cleared from session');
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
     * Force CSRF token refresh
     * @throws RandomException
     */
    public function refreshCsrfToken(): string
    {
        $this->unset('csrf_token');
        $this->unset('csrf_token_time');
        return $this->getCsrfToken();
    }

}
