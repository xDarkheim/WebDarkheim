<?php

/**
 * Controller for user login
 * This controller handles the login process for users.
 * It validates the user's credentials, sets session variables, and redirects to the appropriate page.
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Application\Controllers;

use Exception;


class LoginFormController extends BaseFormController
{
    public function handle(): void
    {
        // Check if the administrator is logging in during maintenance mode
        $isMaintenanceLogin = !empty($_POST['maintenance_login']);

        // Check request method
        if (!$this->requirePostMethod()) {
            $redirectUrl = $isMaintenanceLogin ? '/index.php?maintenance=1&login=1' : '/index.php?page=login';
            $this->redirect($redirectUrl);
            return;
        }

        // CSRF validation
        if (!$this->validateCSRF()) {
            $redirectUrl = $isMaintenanceLogin ? '/index.php?maintenance=1&login=1&login_csrf=1' : '/index.php?page=login';
            $this->handleValidationError(
                'Security error: Invalid CSRF token.',
                $redirectUrl
            );
            return;
        }

        // Get form data (support two field formats)
        $identifier = trim($_POST['username_or_email'] ?? $_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $rememberMe = !empty($_POST['remember_me']);

        // Save form data for re-filling on error
        $_SESSION['form_data_login_username'] = $identifier;
        $_SESSION['form_data_login_remember_me'] = $rememberMe;

        // Input data validation
        if (empty($identifier) || empty($password)) {
            $redirectUrl = $isMaintenanceLogin ? '/index.php?maintenance=1&login=1&login_error=1' : '/index.php?page=login';
            $this->handleValidationError(
                'Please fill in all required fields.',
                $redirectUrl
            );
            return;
        }

        // Authentication attempt
        $result = $this->services->getAuth()->authenticate($identifier, $password);

        if ($result->isValid()) {
            // Clear form data on success
            unset($_SESSION['form_data_login_username'], $_SESSION['form_data_login_remember_me']);

            // Get user data from an authentication result
            $userData = $result->getUser();

            // Check administrator rights when logging in during maintenance mode
            if ($isMaintenanceLogin) {
                // Get user role from different sources for reliability
                $userRole = null;

                // Method 1: from getUser()
                if ($userData && isset($userData['role'])) {
                    $userRole = $userData['role'];
                }

                // Method 2: from getData() (if getUser() didn't work)
                if (!$userRole) {
                    $dataResult = $result->getData();
                    if ($dataResult && isset($dataResult['user']['role'])) {
                        $userRole = $dataResult['user']['role'];
                        $userData = $dataResult['user']; // Update userData
                    }
                }

                // Method 3: from session (if already set)
                if (!$userRole && isset($_SESSION['user_role'])) {
                    $userRole = $_SESSION['user_role'];
                }

                $this->flashMessage->clearMessages();
                if ($userRole !== 'admin') {
                    // Clear all previous messages before adding error
                    $this->handleValidationError(
                        'Access denied. Only administrators can access the site during maintenance.',
                        '/index.php?maintenance=1&login=1&login_role=1'
                    );
                    return;
                }

                // For successful admin login - clear all messages
                // and add only a welcome message
                $this->flashMessage->addSuccess('Welcome back, admin! You have full access during maintenance.');

                // Set additional session variables for admin
                $_SESSION['user_role'] = 'admin';
                $_SESSION['role'] = 'admin';
                $_SESSION['is_admin'] = true;
            } else {
                // For regular login also set role variables
                $userRole = $userData['role'] ?? 'user';
                $_SESSION['user_role'] = $userRole;
                $_SESSION['role'] = $userRole;
                $_SESSION['is_admin'] = ($userRole === 'admin');
            }

            // Guarantee session flags for the correct maintenance bypass
            if (isset($userData['id'])) {
                $_SESSION['user_id'] = (int) $userData['id'];
            }
            $_SESSION['user_authenticated'] = true;
            if (!isset($_SESSION['current_user'])) {
                $_SESSION['current_user'] = [
                    'id' => $userData['id'] ?? null,
                    'username' => $userData['username'] ?? null,
                    'role' => $_SESSION['user_role'] ?? ($userData['role'] ?? null),
                ];
            }

            // Handle "Remember me" functionality
            if ($rememberMe && isset($userData)) { // Fixed: check $userData instead of $userData['user']
                $this->setRememberMeToken($userData);
            }

            // Determine redirect URL
            if ($isMaintenanceLogin) {
                $redirectUrl = '/index.php?page=site_settings'; // Direct to admin panel
            } else {
                $redirectUrl = $_SESSION['redirect_after_login'] ?? '/index.php?page=dashboard';
            }
            unset($_SESSION['redirect_after_login']);

            $this->logger->info('User logged in successfully', [
                'user_id' => $userData['id'] ?? 'unknown',
                'username' => $userData['username'] ?? 'unknown',
                'maintenance_mode' => $isMaintenanceLogin
            ]);

        } else {
            // On authentication error
            $redirectUrl = $isMaintenanceLogin ? '/index.php?maintenance=1&login=1&login_invalid=1' : '/index.php?page=login';
        }
        $this->redirect($redirectUrl);
    }

    private function setRememberMeToken(array $user): void
    {
        try {
            // Use createVerificationToken instead of non-existent createRememberMeToken
            $token = $this->services->getTokenManager()->createVerificationToken(
                (int)$user['id'],
                'remember_me',
                30 * 24 * 60 // 30 days in minutes
            );

            // Main cookie
            setcookie('remember_me', $token, [
                'expires' => time() + (30 * 24 * 60 * 60),
                'path' => '/',
                'domain' => '',
                'secure' => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Lax'
            ]);

            // Duplicate cookie for compatibility with a logout scenario
            setcookie('remember_token', $token, [
                'expires' => time() + (30 * 24 * 60 * 60),
                'path' => '/',
                'domain' => '',
                'secure' => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Lax'
            ]);

            $this->logger->info('Remember me token set', [
                'user_id' => $user['id']
            ]);
        } catch (Exception $e) {
            $this->logger->error('Failed to set remember me token', [
                'user_id' => $user['id'],
                'error' => $e->getMessage()
            ]);
        }
    }
}
