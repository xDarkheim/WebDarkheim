<?php

/**
 * Authentication controller handling login, logout and registration
 * Following Single Responsibility Principle and modern PHP practices
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Application\Controllers;

use App\Domain\Interfaces\AuthenticationInterface;
use App\Domain\Interfaces\UserRegistrationInterface;
use App\Domain\Interfaces\FlashMessageInterface;
use App\Domain\Interfaces\LoggerInterface;
use App\Infrastructure\Lib\TokenManager;
use Exception;
use Throwable;


readonly class AuthController
{
    public function __construct(
        private AuthenticationInterface   $auth,
        private UserRegistrationInterface $registration,
        private FlashMessageInterface     $flashMessage,
        private LoggerInterface           $logger,
        private TokenManager              $tokenManager
    ) {
        // Validate critical dependencies
        $this->logger->debug('AuthController instantiated successfully', [
            'auth_service' => get_class($this->auth),
            'token_manager' => get_class($this->tokenManager),
            'flash_message' => get_class($this->flashMessage),
            'registration' => get_class($this->registration)
        ]);
    }

    /**
     * Handle login form submission
     */
    public function login(): void
    {
        try {
            $this->logger->debug('Login process started', [
                'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
                'session_id' => session_id(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);

            // Validate request method
            if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
                $this->logger->warning('Login attempt with invalid request method', [
                    'method' => $_SERVER['REQUEST_METHOD'] ?? 'none',
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]);
                $this->redirect('/index.php?page=login');
                return;
            }

            // CSRF protection
            try {
                if (!$this->validateCSRF()) {
                    $this->flashMessage->addError('Security error: Invalid CSRF token.');
                    $this->logger->warning('Login: CSRF validation failed', [
                        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                    ]);
                    $this->redirect('/index.php?page=login');
                    return;
                }
            } catch (Exception $csrfError) {
                $this->logger->error('CSRF validation error during login', [
                    'error' => $csrfError->getMessage(),
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]);
                $this->redirect('/index.php?page=login');
                return;
            }

            // Safely extract form data

            try {
                $identifier = isset($_POST['username_or_email']) ? trim((string)$_POST['username_or_email']) : '';
                $password = isset($_POST['password']) ? (string)$_POST['password'] : '';
                $rememberMe = !empty($_POST['remember_me']);

                $this->logger->debug('Login form data extracted', [
                    'identifier_provided' => !empty($identifier),
                    'password_provided' => !empty($password),
                    'remember_me' => $rememberMe,
                    'identifier_length' => strlen($identifier)
                ]);
            } catch (Exception $dataError) {
                $this->logger->error('Error extracting login form data', [
                    'error' => $dataError->getMessage()
                ]);
                $this->flashMessage->addError('Invalid form data provided.');
                $this->redirect('/index.php?page=login');
                return;
            }

            // Store form data for repopulation on error (safely)
            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION['form_data_login_username'] = $identifier;
                if (!isset($_SESSION['form_data_login'])) {
                    $_SESSION['form_data_login'] = [];
                }
                $_SESSION['form_data_login']['remember_me'] = $rememberMe;
            }

            // Validate input
            if (empty($identifier) || empty($password)) {
                $this->logger->debug('Login validation failed - empty fields', [
                    'identifier_empty' => empty($identifier),
                    'password_empty' => empty($password)
                ]);

                $this->flashMessage->addError('Please fill in all required fields.');
                $this->redirect('/index.php?page=login');
                return;
            }

            // Attempt authentication
            try {
                $this->logger->debug('Attempting authentication', [
                    'identifier' => substr($identifier, 0, 3) . '***' // Log partial identifier for debugging
                ]);

                $result = $this->auth->authenticate($identifier, $password);

                $this->logger->debug('Authentication attempt completed', [
                    'success' => $result->isSuccess()
                ]);

            } catch (Exception $authError) {
                $this->logger->error('Authentication service error during login', [
                    'error' => $authError->getMessage(),
                    'identifier' => substr($identifier, 0, 3) . '***',
                    'trace' => $authError->getTraceAsString()
                ]);

                $this->flashMessage->addError('Authentication service unavailable. Please try again later.');
                $this->redirect('/index.php?page=login');
                return;
            }

            if ($result->isSuccess()) {
                $this->logger->info('Login successful', [
                    'identifier' => substr($identifier, 0, 3) . '***'
                ]);

                try {
                    // Clear form data on success
                    if (session_status() === PHP_SESSION_ACTIVE) {
                        unset($_SESSION['form_data_login_username'], $_SESSION['form_data_login']);
                    }

                    // Handle remember me functionality
                    if ($rememberMe) {
                        $this->logger->debug('Processing remember me token');
                        $this->setRememberMeToken($result->getUser());
                    }

                    // Determine redirect destination
                    $redirectUrl = '/index.php?page=dashboard';
                    if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['redirect_after_login'])) {
                        $redirectUrl = $_SESSION['redirect_after_login'];
                        unset($_SESSION['redirect_after_login']);
                    }

                    $this->logger->debug('Login process completed successfully', [
                        'redirect_url' => $redirectUrl
                    ]);

                    $this->redirect($redirectUrl);

                } catch (Exception $postAuthError) {
                    $this->logger->error('Error in post-authentication processing', [
                        'error' => $postAuthError->getMessage(),
                        'trace' => $postAuthError->getTraceAsString()
                    ]);

                    // Still redirect to the dashboard even if some post-auth steps failed
                    $this->redirect('/index.php?page=dashboard');
                }
            } else {
                $this->logger->info('Login failed', [
                    'identifier' => substr($identifier, 0, 3) . '***'
                ]);
                $this->redirect('/index.php?page=login');
            }

        } catch (Throwable $e) {
            $this->logger->critical('Critical error during login process', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'session_id' => session_id(),
                'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);

            // Emergency fallback
            try {
                $this->flashMessage->addError('An unexpected error occurred. Please try again.');
                $this->redirect('/index.php?page=login');
            } catch (Throwable $fallbackError) {
                $this->logger->critical('Emergency fallback failed during login', [
                    'fallback_error' => $fallbackError->getMessage(),
                    'original_error' => $e->getMessage()
                ]);

                // Last resort response
                if (!headers_sent()) {
                    http_response_code(302);
                    header('Location: /index.php?page=login');
                }
                exit;
            }
        }
    }

    /**
     * Handle user registration
     */
    public function register(): void
    {
        // Validate request method
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?page=register');
            return;
        }

        // CSRF protection
        if (!$this->validateCSRF()) {
            $this->flashMessage->addError('Security error: Invalid CSRF token.');
            $this->logger->warning('Registration: CSRF validation failed', [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            $this->redirect('/index.php?page=register');
            return;
        }

        $userData = [
            'username' => trim($_POST['username'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'password_confirm' => $_POST['password_confirm'] ?? ''
        ];

        // Store form data for repopulation on error (excluding passwords)
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['form_data'] = [
                'username' => $userData['username'],
                'email' => $userData['email']
            ];
        }

        // Validate required fields
        if (empty($userData['username']) || empty($userData['email']) ||
            empty($userData['password']) || empty($userData['password_confirm'])) {
            $this->flashMessage->addError('Please fill in all required fields.');
            $this->redirect('/index.php?page=register');
            return;
        }

        // Validate password confirmation
        if ($userData['password'] !== $userData['password_confirm']) {
            $this->flashMessage->addError('Passwords do not match.');
            $this->redirect('/index.php?page=register');
            return;
        }

        // Attempt registration
        $result = $this->registration->register($userData);

        if ($result->isSuccess()) {
            // Clear form data on success
            if (isset($_SESSION['form_data'])) {
                unset($_SESSION['form_data']);
            }

            $this->flashMessage->addSuccess($result->getMessage());
            $this->redirect('/index.php?page=login');
        } else {
            // Store validation errors for display
            if (!empty($result->getErrors()) && session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION['validation_errors'] = $result->getErrors();
            }
            $this->redirect('/index.php?page=register');
        }
    }

    /**
     * Handle user logout
     */
    public function logout(): void
    {
        try {
            $this->logger->debug('Logout process started', [
                'session_id' => session_id(),
                'user_id' => $_SESSION['user_id'] ?? 'none',
                'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
            ]);

            // Check if a user is already logged out
            if (!isset($_SESSION['user_id'])) {
                $this->logger->info('Logout called but user already logged out', [
                    'session_id' => session_id(),
                    'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
                ]);
                // Just redirect without adding a flash message
                $this->redirect('/index.php?page=home');
                return;
            }

            // Get user info before clearing the session for logging
            $userId = $_SESSION['user_id'];
            $username = $_SESSION['username'] ?? 'Unknown';

            $this->logger->debug('Processing logout for user', [
                'user_id' => $userId,
                'username' => $username
            ]);

            // Proactively remove the alternative remember-me cookie if present
            if (!empty($_COOKIE['remember_me'])) {
                $rememberMeToken = $_COOKIE['remember_me'];

                // Try to revoke in backend
                try {
                    $this->tokenManager->revokeToken($rememberMeToken);
                    $this->logger->debug('remember_me token revoked successfully');
                } catch (Exception $e) {
                    $this->logger->error('Failed to revoke remember_me token during logout', [
                        'error' => $e->getMessage(),
                        'user_id' => $userId
                    ]);
                }

                // Remove cookie safely
                setcookie('remember_me', '', [
                    'expires' => time() - 3600,
                    'path' => '/',
                    'domain' => '',
                    'secure' => !empty($_SERVER['HTTPS']),
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]);
                unset($_COOKIE['remember_me']);
            }

            // Immediately clear the server-side session and its cookie to avoid ghost user
            try {
                if (session_status() === PHP_SESSION_NONE) {
                    @session_start();
                }

                // Unset common auth flags
                $_SESSION = [];
                // Remove individual session keys if they exist
                $sessionKeys = [
                    'user_id', 'username', 'email', 'user_role',
                    'role', 'is_admin', 'user_authenticated',
                    'current_user', 'auth_user'
                ];

                foreach ($sessionKeys as $key) {
                    if (isset($_SESSION[$key])) {
                        unset($_SESSION[$key]);
                    }
                }

                // Remove PHP session cookie
                $params = session_get_cookie_params();
                setcookie(session_name(), '', [
                    'expires' => time() - 42000,
                    'path' => $params['path'],
                    'domain' => $params['domain'],
                    'secure' => !empty($_SERVER['HTTPS']),
                    'httponly' => true,
                    'samesite' => $params['samesite']
                ]);

                // Clear the session completely first
                session_unset();
                $_SESSION = [];

                // Regenerate session ID for safety
                @session_regenerate_id(true);
            } catch (Throwable $e) {
                $this->logger->error('Session cleanup failed during logout', [
                    'error' => $e->getMessage()
                ]);
            }

            // Remove remember me token if it exists
            if (!empty($_COOKIE['remember_token'])) {
                $rememberToken = $_COOKIE['remember_token'];

                $this->logger->debug('Attempting to revoke remember token', [
                    'user_id' => $userId,
                    'token_length' => strlen($rememberToken)
                ]);

                // Revoke token in database
                try {
                    $this->tokenManager->revokeToken($rememberToken);
                    $this->logger->debug('Remember token revoked successfully');
                } catch (Exception $e) {
                    $this->logger->error('Failed to revoke remember token during logout', [
                        'error' => $e->getMessage(),
                        'user_id' => $userId,
                        'trace' => $e->getTraceAsString()
                    ]);
                    // Continue with a logout process even if token revocation fails
                }

                // Remove cookie safely
                $cookieOptions = [
                    'expires' => time() - 3600,
                    'path' => '/',
                    'domain' => '',
                    'secure' => !empty($_SERVER['HTTPS']),
                    'httponly' => true,
                    'samesite' => 'Strict'
                ];

                if (!headers_sent()) {
                    setcookie('remember_token', '', $cookieOptions);
                    $this->logger->debug('Remember token cookie removed');
                } else {
                    $this->logger->warning('Cannot remove remember token cookie - headers already sent', [
                        'user_id' => $userId
                    ]);
                }

                $this->logger->info('Remember token processing completed during logout', [
                    'user_id' => $userId,
                    'username' => $username
                ]);
            }

            // Use authentication service for logout (includes session clearing and flash message)
            try {
                $this->logger->debug('Calling auth service logout');
                $this->auth->logout();
                $this->logger->debug('Auth service logout completed');
            } catch (Exception $e) {
                $this->logger->error('Auth service logout failed', [
                    'error' => $e->getMessage(),
                    'user_id' => $userId,
                    'trace' => $e->getTraceAsString()
                ]);

                // Emergency manual cleanup
                if (session_status() === PHP_SESSION_ACTIVE) {
                    $_SESSION = [];
                    session_destroy();
                }
            }

            $this->logger->info('Logout process completed successfully', [
                'user_id' => $userId,
                'username' => $username
            ]);

            // Redirect to home page
            $this->redirect('/index.php?page=home');

        } catch (Throwable $e) {
            $this->logger->critical('Critical error during logout process', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $_SESSION['user_id'] ?? 'unknown',
                'session_id' => session_id()
            ]);

            // Emergency fallback - try to clean the session and redirect
            try {
                if (session_status() === PHP_SESSION_ACTIVE) {
                    $_SESSION = [];
                }
                $this->redirect('/index.php?page=home');
            } catch (Throwable $fallbackError) {
                $this->logger->critical('Emergency fallback failed', [
                    'fallback_error' => $fallbackError->getMessage(),
                    'original_error' => $e->getMessage()
                ]);

                // Last resort - output minimal response
                if (!headers_sent()) {
                    http_response_code(302);
                    header('Location: /index.php?page=home');
                }
                exit;
            }
        }
    }

    /**
     * Validate CSRF token
     */
    private function validateCSRF(): bool
    {
        try {
            // Check if the session is active
            if (session_status() !== PHP_SESSION_ACTIVE) {
                $this->logger->warning('CSRF validation failed - session not active');
                return false;
            }

            $token = $_POST['csrf_token'] ?? '';
            $sessionToken = $_SESSION['csrf_token'] ?? '';

            // Both tokens must be non-empty
            if (empty($token) || empty($sessionToken)) {
                $this->logger->debug('CSRF validation failed - empty tokens', [
                    'post_token_empty' => empty($token),
                    'session_token_empty' => empty($sessionToken)
                ]);
                return false;
            }

            // Use hash_equals for timing-safe comparison
            $isValid = hash_equals((string)$sessionToken, (string)$token);

            $this->logger->debug('CSRF validation completed', [
                'valid' => $isValid,
                'token_length' => strlen((string)$token),
                'session_token_length' => strlen((string)$sessionToken)
            ]);

            return $isValid;

        } catch (Exception $e) {
            $this->logger->error('Exception during CSRF validation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Set remember me token
     */
    private function setRememberMeToken(array $user): void
    {
        try {
            $token = $this->tokenManager->createVerificationToken(
                (int)$user['id'],
                'remember_me',
                43200 // 30 days in minutes
            );

            // Set secure cookie
            $cookieOptions = [
                'expires' => time() + (86400 * 30), // 30 days
                'path' => '/',
                'domain' => '',
                'secure' => !empty($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Strict'
            ];

            setcookie('remember_token', $token, $cookieOptions);

            $this->logger->info('Remember me token set', [
                'user_id' => $user['id'],
                'expires' => date('Y-m-d H:i:s', $cookieOptions['expires'])
            ]);

        } catch (Exception $e) {
            $this->logger->error('Failed to set remember me token', [
                'error' => $e->getMessage(),
                'user_id' => $user['id']
            ]);
        }
    }


    /**
     * Redirect to URL
     */
    private function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }
}
