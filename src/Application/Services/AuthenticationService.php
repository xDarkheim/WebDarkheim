<?php

/**
 * Authentication service following Single Responsibility Principle
 * Handles only user authentication logic
 * Uses SessionManager for session management
 * Uses StateManager for global state management
 * Uses LoggerInterface for logging
 * Uses PasswordManager for password hashing and validation
 * Uses ServiceProvider for dependency injection
 * Uses DatabaseInterface for database interaction
 * Uses FlashMessageInterface for flash messages
 * Uses TokenManagerInterface for CSRF token generation
 * Uses ConfigurationManager for dynamic settings loading
 * Uses MailerInterface for email sending
 * Uses UserRegistrationInterface for user registration
 * Uses TextEditorInterface for text editing
 * Uses PasswordManagerInterface for password hashing and validation
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\Core\Results;
use App\Domain\Interfaces\AuthenticationInterface;
use App\Domain\Interfaces\DatabaseInterface;
use App\Domain\Interfaces\FlashMessageInterface;
use App\Domain\Interfaces\LoggerInterface;
use App\Application\Core\SessionManager;
use App\Application\Core\StateManager;
use App\Application\Core\ServiceProvider;
use App\Domain\Models\User;
use Exception;


class AuthenticationService implements AuthenticationInterface
{
    private ?array $currentUser = null;

    public function __construct(
        private readonly DatabaseInterface $database,
        private readonly FlashMessageInterface $flashMessage,
        private readonly PasswordManager $passwordManager,
        private readonly LoggerInterface $logger
    ) {
        $this->loadCurrentUser();
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate(string $email, string $password): Results
    {
        if (empty($email) || empty($password)) {
            $this->logger->warning('Login attempt with empty credentials', [
                'email' => $email,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);

            $error = 'Username and password are required.';
            $this->flashMessage->addError($error);
            return new Results(false, ['error' => $error]);
        }

        // Find the user by username or email
        $user = User::findByUsernameOrEmail($this->database, $email, $email);

        if (!$user || !$this->passwordManager->verifyPassword($password, $user['password_hash'])) {
            $this->logger->warning('Failed login attempt', [
                'email' => $email,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_found' => $user !== null
            ]);

            $error = 'Invalid username or password.';
            $this->flashMessage->addError($error);
            return new Results(false, ['error' => $error]);
        }

        if (!$user['is_active']) {
            $this->logger->info('Login attempt by inactive user', [
                'email' => $email,
                'user_id' => $user['id']
            ]);

            $resendLink = '<a href="/index.php?page=resend_verification&email=' .
                         urlencode($user['email']) . '">resend verification email</a>';
            $error = 'Your account is not active. Please verify your email or ' . $resendLink . '.';
            $this->flashMessage->addError($error, true);
            return new Results(false, ['error' => $error]);
        }

        // Successful authentication
        $this->startUserSession($user);

        $this->logger->info('User logged in successfully', [
            'user_id' => $user['id'],
            'username' => $user['username'],
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);

        $this->flashMessage->addSuccess('Login successful. Welcome back, ' .
                                       htmlspecialchars($user['username']) . '!');

        return new Results(true, ['user' => $user]);
    }

    /**
     * {@inheritdoc}
     */
    public function isAuthenticated(): bool
    {
        return $this->currentUser !== null && isset($_SESSION['user_id']);
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentUser(): ?array
    {
        return $this->currentUser;
    }

    /**
     * {@inheritdoc}
     */
    public function logout(): void
    {
        if ($this->currentUser) {
            $this->logger->info('User logged out', [
                'user_id' => $this->currentUser['id'],
                'username' => $this->currentUser['username']
            ]);
        }

        // Get SessionManager for secure session handling
        try {
            $serviceProvider = ServiceProvider::getInstance();
            $configManager = $serviceProvider->getConfigurationManager();
            $sessionManager = SessionManager::getInstance($this->logger, [], $configManager);
        } catch (Exception) {
            // Fallback if ConfigurationManager is not available
            $sessionManager = SessionManager::getInstance($this->logger);
        }

        // Add flash message BEFORE clearing session data
        $this->flashMessage->addSuccess('You have been logged out successfully.');

        // Clear user state via SessionManager
        $sessionManager->clearUserState();

        // Clear the current user
        $this->currentUser = null;

        // Update global application state if available
        try {
            $stateManager = StateManager::getInstance($this->logger);
            $stateManager->updateSection('user', [
                'authenticated' => false,
                'id' => null,
                'username' => null,
                'role' => null,
                'email' => null,
            ]);
        } catch (Exception) {
            // StateManager may not be initialized, continue without it
            $this->logger->debug('StateManager not available during logout');
        }

        // Regenerate session ID for security (this will preserve flash messages)
        $sessionManager->regenerateId();
    }

    /**
     * Start a user session after successful authentication
     */
    private function startUserSession(array $user): void
    {
        // Get SessionManager for secure session handling
        try {
            $serviceProvider = ServiceProvider::getInstance();
            $configManager = $serviceProvider->getConfigurationManager();
            $sessionManager = SessionManager::getInstance($this->logger, [], $configManager);
        } catch (Exception) {
            // Fallback if ConfigurationManager is not available
            $sessionManager = SessionManager::getInstance($this->logger);
        }

        // Regenerate session ID for security
        $sessionManager->regenerateId();

        // Save user state via SessionManager
        $sessionManager->setUserState($user);

        // Update local state
        $this->currentUser = $user;

        // Update global application state if available
        try {
            $stateManager = StateManager::getInstance($this->logger);
            $stateManager->updateSection('user', [
                'authenticated' => true,
                'id' => $user['id'],
                'username' => $user['username'],
                'role' => $user['role'],
                'email' => $user['email'] ?? null,
            ]);
        } catch (Exception) {
            // StateManager may not be initialized, continue without it
            $this->logger->debug('StateManager not available during authentication');
        }
    }

    /**
     * Load current user from session
     */
    private function loadCurrentUser(): void
    {
        if (!isset($_SESSION['user_id'])) {
            return;
        }

        $user = User::findById($this->database, (int)$_SESSION['user_id']);

        if (!$user || !$user['is_active']) {
            // User isn't found or inactive, clear session
            $this->logout();
            return;
        }

        // Check if role in session differs from database
        if (($_SESSION['user_role'] ?? null) !== $user['role']) {
            // Update session with correct role from database
            $_SESSION['user_role'] = $user['role'];
        }

        $this->currentUser = $user;
    }

    /**
     * {@inheritdoc}
     */
    public function loginUser(int $userId): Results
    {
        $user = User::findById($this->database, $userId);

        if (!$user) {
            return new Results(false, ['error' => 'User not found']);
        }

        if (!$user['is_active']) {
            return new Results(false, ['error' => 'User account is not active']);
        }

        $this->startUserSession($user);

        $this->logger->info('User logged in via loginUser method', [
            'user_id' => $userId,
            'username' => $user['username']
        ]);

        return new Results(true, ['message' => 'User logged in successfully']);
    }

    /**
     * {@inheritdoc}
     */
    public function getUserRoles(): array
    {
        if (!$this->currentUser) {
            return [];
        }

        // In a simple implementation, return the role as an array
        return [$this->currentUser['role'] ?? 'user'];
    }

    /**
     * {@inheritdoc}
     */
    public function verifyEmail(string $token): Results
    {
        try {
            // Find a user by verification token
            $sql = "SELECT * FROM users WHERE email_verification_token = ? AND is_active = 0";
            $user = $this->database->fetch($sql, [$token]);

            if (!$user) {
                return new Results(false, ['error' => 'Invalid or expired verification token']);
            }

            // Activate user
            $updateSql = "UPDATE users SET is_active = 1, email_verification_token = NULL, email_verified_at = NOW() WHERE id = ?";
            $success = $this->database->execute($updateSql, [$user['id']]);

            if ($success) {
                $this->logger->info('Email verified successfully', [
                    'user_id' => $user['id'],
                    'email' => $user['email']
                ]);

                return new Results(true, ['message' => 'Email verified successfully']);
            }

            return new Results(false, ['error' => 'Failed to verify email']);

        } catch (Exception $e) {
            $this->logger->error('Email verification failed', [
                'token' => $token,
                'error' => $e->getMessage()
            ]);

            return new Results(false, ['error' => 'Email verification failed']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isAdmin(): bool
    {
        return $this->currentUser !== null && 
               ($this->currentUser['role'] === 'admin' || $this->currentUser['role'] === 'super_admin');
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentUserId(): ?int
    {
        return $this->currentUser['id'] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentUsername(): ?string
    {
        return $this->currentUser['username'] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentUserRole(): ?string
    {
        return $this->currentUser['role'] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function hasRole(string $role): bool
    {
        return $this->currentUser !== null && $this->currentUser['role'] === $role;
    }
}
