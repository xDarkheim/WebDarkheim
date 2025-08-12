<?php

/**
 * User registration service following the Single Responsibility Principle
 * Handles only user registration logic
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Interfaces\UserRegistrationInterface;
use App\Domain\Interfaces\DatabaseInterface;
use App\Domain\Interfaces\FlashMessageInterface;
use App\Domain\Interfaces\MailerInterface;
use App\Domain\Interfaces\TokenManagerInterface;
use App\Domain\Interfaces\LoggerInterface;
use App\Application\Core\Results;
use App\Domain\Models\User;


readonly class UserRegistrationService implements UserRegistrationInterface
{
    public function __construct(
        private DatabaseInterface     $database,
        private FlashMessageInterface $flashMessage,
        private MailerInterface       $mailer,
        private TokenManagerInterface $tokenManager,
        private PasswordManager       $passwordManager,
        private LoggerInterface       $logger,
        private SiteSettingsService   $siteSettings
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function register(array $userData): Results
    {
        // Check if registration is enabled
        $registrationEnabled = $this->siteSettings->get('registration_enabled', true);
        if (!$registrationEnabled) {
            $error = 'Registration is currently disabled. Please contact the administrator.';
            $this->flashMessage->addError($error);
            $this->logger->warning('Registration attempt blocked - registration disabled', [
                'username' => $userData['username'] ?? 'unknown',
                'email' => $userData['email'] ?? 'unknown',
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
            return new Results(false, ['error' => $error]);
        }

        $validationResult = $this->validateRegistrationData($userData);
        if (!$validationResult->isValid()) {
            $this->addErrorsToFlash($validationResult->getErrors());
            return new Results(false, $validationResult->getErrors());
        }

        // Check for existing users
        $existingUser = User::findByUsernameOrEmail(
            $this->database,
            $userData['username'],
            $userData['email']
        );

        if ($existingUser) {
            $errors = $this->getExistingUserErrors($existingUser, $userData);
            $this->addErrorsToFlash($errors);

            $this->logger->warning('Registration failed - user already exists', [
                'username' => $userData['username'],
                'email' => $userData['email']
            ]);

            return new Results(false, $errors);
        }

        // Create user
        return $this->createUser($userData);
    }

    /**
     * {@inheritdoc}
     */
    public function verifyEmail(string $token): Results
    {
        $tokenData = $this->tokenManager->verifyToken($token, 'email_verification');

        if (!$tokenData) {
            $this->flashMessage->addError('Invalid or expired verification token.');
            return new Results(false, ['error' => 'Invalid or expired verification token']);
        }

        $user = new User($this->database);
        if (!$user->load($tokenData['user_id'])) {
            $this->flashMessage->addError('User not found.');
            return new Results(false, ['error' => 'User not found']);
        }

        if ($user->isActive()) {
            $this->flashMessage->addInfo('Your email is already verified.');
            return new Results(true, ['message' => 'Email is already verified']);
        }

        // Activate user
        $user->setActive(true);
        if ($user->save()) {
            $this->tokenManager->invalidateToken($token);
            $this->flashMessage->addSuccess('Email verified successfully! Your account is now active.');

            $this->logger->info('Email verified successfully', [
                'user_id' => $user->getId(),
                'email' => $user->getEmail()
            ]);

            return new Results(true, ['message' => 'Email verified successfully']);
        }

        $this->flashMessage->addError('Failed to activate account. Please try again.');
        return new Results(false, ['error' => 'Failed to activate account']);
    }

    /**
     * {@inheritdoc}
     */
    public function resendVerification(string $email): Results
    {
        $user = User::findByUsernameOrEmail($this->database, '', $email);

        if (!$user) {
            $this->flashMessage->addError('User with this email address not found.');
            return new Results(false, ['error' => 'User with this email address not found']);
        }

        if ($user['is_active']) {
            $this->flashMessage->addInfo('Your account is already active.');
            return new Results(true, ['message' => 'Account is already active']);
        }

        // Generate a new verification token
        $verificationToken = $this->tokenManager->createVerificationToken(
            $user['id'],
            'email_verification',
            60 * 24 // 24 hours
        );

        $emailSent = $this->sendVerificationEmail($user, $verificationToken);

        if ($emailSent) {
            $this->flashMessage->addSuccess('Verification email has been sent. Please check your inbox.');
            return new Results(true, ['message' => 'Verification email sent successfully']);
        } else {
            $this->flashMessage->addError('Failed to send verification email. Please try again later.');
            return new Results(false, ['error' => 'Failed to send verification email']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function emailExists(string $email): bool
    {
        $user = User::findByUsernameOrEmail($this->database, '', $email);
        return $user !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function usernameExists(string $username): bool
    {
        $user = User::findByUsernameOrEmail($this->database, $username);
        return $user !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function validateRegistrationData(array $data): Results
    {
        $errors = [];
        $username = trim($data['username'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $passwordConfirm = $data['password_confirm'] ?? '';

        // Username validation
        if (empty($username)) {
            $errors[] = "Username is required.";
        } elseif (strlen($username) < 3 || strlen($username) > 50) {
            $errors[] = "Username must be between 3 and 50 characters.";
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $errors[] = "Username can only contain letters, numbers, and underscores.";
        }

        // Email validation
        if (empty($email)) {
            $errors[] = "Email is required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format.";
        } elseif (strlen($email) > 255) {
            $errors[] = "Email address is too long.";
        }

        // Password validation
        if (empty($password)) {
            $errors[] = "Password is required.";
        } else {
            $passwordValidation = $this->passwordManager->validatePassword($password, [$username, $email]);
            if (!$passwordValidation->isValid()) {
                $errors = array_merge($errors, $passwordValidation->getErrors());
            }
        }

        if ($password !== $passwordConfirm) {
            $errors[] = "Passwords do not match.";
        }

        return new Results(empty($errors), $errors);
    }

    /**
     * {@inheritdoc}
     */
    public function generateVerificationToken(int $userId): string
    {
        return $this->tokenManager->createVerificationToken($userId, 'email_verification', 60 * 24); // 24 hours
    }

    /**
     * {@inheritdoc}
     */
    public function getUserByVerificationToken(string $token): ?array
    {
        $tokenData = $this->tokenManager->verifyToken($token, 'email_verification');

        if (!$tokenData) {
            return null;
        }

        return User::findById($this->database, $tokenData['user_id']);
    }

    /**
     * {@inheritdoc}
     */
    public function markEmailAsVerified(int $userId): bool
    {
        $user = new User($this->database);
        if (!$user->load($userId)) {
            return false;
        }

        $user->setActive(true);
        return $user->save();
    }

    /**
     * Get errors for existing user
     */
    private function getExistingUserErrors(array $existingUser, array $userData): array
    {
        $errors = [];

        if (
            isset($existingUser['username']) &&
            strtolower($existingUser['username']) === strtolower($userData['username'])
        ) {
            $errors[] = "A user with this username already exists.";
        }

        if (
            isset($existingUser['email']) &&
            strtolower($existingUser['email']) === strtolower($userData['email'])
        ) {
            $errors[] = "A user with this email address already exists.";
        }

        return $errors;
    }

    /**
     * Create a new user
     */
    private function createUser(array $userData): Results
    {
        // Check if email verification is required
        $emailVerificationRequired = $this->siteSettings->get('email_verification_required', true);

        $user = new User($this->database);
        $user->setUsername(trim($userData['username']));
        $user->setEmail(trim($userData['email']));
        $user->setPassword($userData['password']);

        // Set user active status based on email verification requirement
        if (!$emailVerificationRequired) {
            $user->setActive(true); // User is immediately active if verification not required
        }
        // If verification is required, user remains inactive (default state)

        if (!$user->save()) {
            $error = "Failed to save user. Please try again.";
            $this->flashMessage->addError($error);
            return new Results(false, ['error' => $error]);
        }

        $this->logger->info('User registered successfully', [
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'user_id' => $user->getId(),
            'email_verification_required' => $emailVerificationRequired,
            'is_active' => $user->isActive()
        ]);

        // Handle email verification based on settings
        if ($emailVerificationRequired) {
            // Send verification email
            $verificationToken = $this->tokenManager->createVerificationToken(
                $user->getId(),
                'email_verification',
                60 * 24 // 24 hours
            );

            if ($this->sendVerificationEmail($user->toArray(), $verificationToken)) {
                $message = "Registration successful! Please check your email (" .
                          htmlspecialchars($user->getEmail()) . ") to verify your account and activate it.";
                $this->flashMessage->addSuccess($message);
                return new Results(true, ['message' => $message, 'user_id' => $user->getId()]);
            }

            // Registration successful but email failed
            $message = "Registration successful! We tried to send a verification email. " .
                      "If you don't receive it, please contact support.";
            $this->flashMessage->addWarning($message);
        } else {
            // Email verification isn't required - the user is immediately active
            $message = "Registration successful! Your account is now active and you can log in.";
            $this->flashMessage->addSuccess($message);
        }
        return new Results(true, ['message' => $message, 'user_id' => $user->getId()]);
    }

    /**
     * Send verification email
     */
    private function sendVerificationEmail(array $user, string $token): bool
    {
        // Resolve site name from settings
        $siteName = $this->siteSettings->get('site_name', 'Darkheim Development Studio');

        // Build a robust base URL (proxy-aware) with fallback to configure site_url
        $forwardedProto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
        $httpsOn = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : '';
        $requestScheme = $_SERVER['REQUEST_SCHEME'] ?? '';
        $scheme = $forwardedProto ?: ($httpsOn ?: ($requestScheme ?: 'https'));

        $host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? ($_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? ''));

        $configuredSiteUrl = $this->siteSettings->get('site_url', '');
        if (empty($host) && !empty($configuredSiteUrl)) {
            $baseUrl = rtrim((string)$configuredSiteUrl, '/');
        } else {
            $baseUrl = $scheme . '://' . ($host ?: 'localhost');
        }

        $verificationPath = "/index.php?page=verify_email&token=" . urlencode($token);
        $verificationLink = $baseUrl . $verificationPath;

        // Provide multiple aliases so any template can render the link
        $templateVars = [
            'username' => $user['username'],
            'siteName' => $siteName,
            'siteUrl' => $baseUrl,

            // Common aliases
            'verification_link' => $verificationLink,
            'verification_url' => $verificationLink,
            'action_url' => $verificationLink,
            'url' => $verificationLink,
            'link' => $verificationLink,
            'verificationLink' => $verificationLink,

            // Optional HTML helper
            'verification_link_html' => '<a href="' . htmlspecialchars($verificationLink, ENT_QUOTES, 'UTF-8') . '">Verify your email</a>',

            // Extra context
            'token' => $token,
            'expires_hours' => 24,
        ];

        $emailContent = $this->mailer->renderTemplate('registration_verification', $templateVars);

        if (empty($emailContent)) {
            $this->logger->error('Failed to render email template', [
                'template' => 'registration_verification',
                'user_id' => $user['id'] ?? null
            ]);
            return false;
        }

        $subject = "Verify Your Email Address - " . $siteName;

        if (!$this->mailer->send($user['email'], $subject, $emailContent)) {
            $this->logger->error('Failed to send verification email', [
                'email' => $user['email'],
                'error' => 'Mailer send failed'
            ]);
            return false;
        }

        return true;
    }

    /**
     * Add errors to flash messages
     */
    private function addErrorsToFlash(array $errors): void
    {
        foreach ($errors as $error) {
            $this->flashMessage->addError($error);
        }
    }
}
