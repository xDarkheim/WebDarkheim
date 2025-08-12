<?php

/**
 * Controller for handling the registration form
 * This controller handles the registration process for new users.
 * It validates the form data, creates a new user account, and redirects to the login page on success.
 * It also handles errors and displays appropriate messages.
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Application\Controllers;


use Exception;
use ReflectionException;

class RegisterFormController extends BaseFormController
{
    /**
     * @throws ReflectionException
     */
    public function handle(): void
    {
        // Check if registration is enabled
        try {
            $siteSettingsService = $this->services->getSiteSettingsService();
            $registrationEnabled = $siteSettingsService->get('registration_enabled', true);

            if (!$registrationEnabled) {
                $this->handleValidationError(
                    'Registration is currently disabled.',
                    '/index.php?page=login'
                );
                return;
            }
        } catch (Exception $e) {
            error_log('Failed to check registration status in RegisterFormController: ' . $e->getMessage());
            // Continue with registration if we can't check settings
        }

        // Check request method
        if (!$this->requirePostMethod()) {
            $this->redirect('/index.php?page=register');
            return;
        }

        // CSRF validation
        if (!$this->validateCSRF()) {
            $this->handleValidationError(
                'Security error: Invalid CSRF token.',
                '/index.php?page=register'
            );
            return;
        }

        $userData = [
            'username' => trim($_POST['username'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'password_confirm' => $_POST['password_confirm'] ?? ''
        ];

        // Save form data for repopulation on error (excluding passwords)
        $_SESSION['form_data_register'] = [
            'username' => $userData['username'],
            'email' => $userData['email']
        ];

        // Validate required fields
        if (empty($userData['username']) || empty($userData['email']) ||
            empty($userData['password']) || empty($userData['password_confirm'])) {
            $this->handleValidationError(
                'Please fill in all required fields.',
                '/index.php?page=register'
            );
            return;
        }

        // Validate password match
        if ($userData['password'] !== $userData['password_confirm']) {
            $this->handleValidationError(
                'Passwords do not match.',
                '/index.php?page=register'
            );
            return;
        }

        // Basic email validation
        if (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
            $this->handleValidationError(
                'Please enter a valid email address.',
                '/index.php?page=register'
            );
            return;
        }

        // Validate username length
        if (strlen($userData['username']) < 3 || strlen($userData['username']) > 50) {
            $this->handleValidationError(
                'Username must be between 3 and 50 characters.',
                '/index.php?page=register'
            );
            return;
        }

        // Validate password complexity
        if (strlen($userData['password']) < 8) {
            $this->handleValidationError(
                'Password must be at least 8 characters long.',
                '/index.php?page=register'
            );
            return;
        }

        try {
            // Attempt registration via UserRegistrationService
            $userRegistration = $this->services->getUserRegistration();
            $result = $userRegistration->register($userData);

            if ($result->isSuccess()) {
                // Clear form data on success
                unset($_SESSION['form_data_register']);

                $this->logger->info('User registered successfully', [
                    'username' => $userData['username'],
                    'email' => $userData['email']
                ]);

                // Message already added in UserRegistrationService, do not duplicate
                $this->redirect('/index.php?page=login');
            } else {
                // Handle registration errors
                $errors = $result->getErrors();
                if (!empty($errors)) {
                    foreach ($errors as $error) {
                        $this->flashMessage->addError($error);
                    }
                } else {
                    $this->flashMessage->addError($result->getMessage() ?: 'Registration failed. Please try again.');
                }

                $this->redirect('/index.php?page=register');
            }
        } catch (Exception $e) {
            $this->logger->error('Registration process failed', [
                'username' => $userData['username'],
                'email' => $userData['email'],
                'error' => $e->getMessage()
            ]);

            $this->handleValidationError(
                'Registration failed due to a system error. Please try again later.',
                '/index.php?page=register'
            );
        }
    }
}
