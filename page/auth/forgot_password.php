<?php

/**
 * Forgot Password Page
 *
 * Handles user requests to reset their password by sending a reset link to their email.
 * This page uses the CSRFMiddleware to protect against CSRF attacks.
 *
 * @author Dmytro Hovenko
 */

use App\Application\Core\ServiceProvider;
use App\Application\Core\SessionManager;
use App\Application\Middleware\CSRFMiddleware;
use App\Domain\Models\User;
use App\Infrastructure\Lib\TokenManager;

// Use global services (already initialized in webengine.php)
global $flashMessageService, $database_handler, $site_settings_from_db, $mailerService, $tokenManager;

// Initialize ServiceProvider and session via our session controller
try {
    $services = ServiceProvider::getInstance();
    // Start a session safely via SessionManager
    $sessionManager = SessionManager::getInstance(
        $services->getLogger(),
        [],
        $services->getConfigurationManager()
    );
    $sessionManager->start();
} catch (Throwable $e) {
    error_log("Critical: Failed to initialize services/session in forgot_password.php: " . $e->getMessage());
}

// Check for critical services and get them from ServiceProvider if needed
if (!isset($flashMessageService)) {
    error_log("Critical: FlashMessageService not available in forgot_password.php");
    die("A system error occurred. Please try again later.");
}
if (!isset($database_handler)) {
    error_log("Critical: Database handler not available in forgot_password.php");
    $flashMessageService->addError("A system error occurred. Please try again later.");
    header("Location: /index.php?page=forgot_password");
    exit();
}

// Mailer fallback via ServiceProvider
if (!isset($mailerService) || !$mailerService) {
    try {
        if (method_exists($services, 'getMailerService')) {
            $mailerService = $services->getMailerService();
        } elseif (method_exists($services, 'getMailer')) {
            $mailerService = $services->getMailer();
        }
    } catch (Throwable $e) {
        error_log("Warning: Failed to resolve Mailer service from ServiceProvider: " . $e->getMessage());
    }
}
if (!isset($mailerService) || !$mailerService) {
    error_log("Critical: MailerService not available in forgot_password.php");
    $flashMessageService->addError("A system error occurred (Mail). Please try again later or contact support.");
    header("Location: /index.php?page=forgot_password");
    exit();
}

// TokenManager fallback via ServiceProvider
if (!isset($tokenManager) || !$tokenManager) {
    try {
        if (method_exists($services, 'getTokenManager')) {
            $tokenManager = $services->getTokenManager();
        }
    } catch (Throwable $e) {
        error_log("Warning: Failed to resolve TokenManager from ServiceProvider: " . $e->getMessage());
    }
}
if (!isset($tokenManager) || !$tokenManager) {
    error_log("Critical: TokenManager not available in forgot_password.php");
    $flashMessageService->addError("A system error occurred (Token). Please try again later or contact support.");
    header("Location: /index.php?page=forgot_password");
    exit();
}

// Check that the user is not authenticated
if (isset($_SESSION['user_id'])) {
    header("Location: /index.php?page=dashboard");
    exit();
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token_from_post = $_POST['csrf_token'] ?? '';

    // Validate CSRF token via new CSRFMiddleware
    if (!CSRFMiddleware::validateQuick()) {
        $flashMessageService->addError('Security error: Invalid CSRF token. Please try again.');
        error_log("Forgot Password: CSRF token validation failed. IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    } else {
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $email = filter_var($email, FILTER_VALIDATE_EMAIL);

        if (!$email) {
            $flashMessageService->addError('Please enter a valid email address.');
        } else {
            // Use static method to find a user
            $user = User::findByUsernameOrEmail($database_handler, '', $email);

            if ($user) {
                // Generate password reset token via our TokenManager (supports different implementations)
                $reset_token = null;
                try {
                    if (is_object($tokenManager) && method_exists($tokenManager, 'createVerificationToken')) {
                        // Parameters: user_id, purpose, lifetime (in minutes)
                        $reset_token = $tokenManager->createVerificationToken(
                            (int)$user['id'],
                            'password_reset' // 60 minutes
                        );
                    } elseif (is_object($tokenManager) && method_exists($tokenManager, 'generateToken')) {
                        // Alternative implementation
                        $tokenType = defined('\App\Infrastructure\Lib\TokenManager::TYPE_PASSWORD_RESET')
                            ? TokenManager::TYPE_PASSWORD_RESET
                            : 'password_reset';
                        $reset_token = $tokenManager->generateToken(
                            $tokenType,
                            (int)$user['id']
                        );
                    }
                } catch (Throwable $e) {
                    error_log("Forgot Password: Token generation error for user {$user['id']}: " . $e->getMessage());
                }

                if ($reset_token) {
                    // Build a robust base URL (supports proxies and fallbacks)
                    $forwardedProto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
                    $httpsOn = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : '';
                    $requestScheme = $_SERVER['REQUEST_SCHEME'] ?? '';
                    $scheme = $forwardedProto ?: ($httpsOn ?: ($requestScheme ?: 'https'));

                    $host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? ($_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? 'localhost'));

                    // Fallback to configure site URL if the host is missing
                    $configuredSiteUrl = $site_settings_from_db['general']['site_url']['value'] ?? '';
                    if (empty($host) && !empty($configuredSiteUrl)) {
                        $baseUrl = rtrim($configuredSiteUrl, '/');
                    } else {
                        $baseUrl = $scheme . '://' . $host;
                    }

                    $reset_path = "/index.php?page=reset_password&token=" . urlencode($reset_token) . "&email=" . urlencode((string)$email);
                    $reset_link = $baseUrl . $reset_path;

                    $siteName = $site_settings_from_db['general']['site_name']['value'] ?? 'Darkheim Development Studio';
                    $subject = "Password Reset Request - " . $siteName;

                    $username_for_email = $user['username'] ?: 'User';

                    // Send it with multiple variable aliases so any template can render a link
                    $templateData = [
                        'username' => $username_for_email,
                        'siteName' => $siteName,
                        'siteUrl' => $baseUrl,
                        // Common aliases for the link (priority order):
                        'resetLink' => $reset_link,        // Primary template variable
                        'reset_link' => $reset_link,
                        'reset_url' => $reset_link,
                        'action_url' => $reset_link,
                        'url' => $reset_link,
                        'link' => $reset_link,
                        // Optional HTML helper variant:
                        'reset_link_html' => '<a href="' . htmlspecialchars($reset_link, ENT_QUOTES, 'UTF-8') . '">Reset your password</a>',
                        // Extra context:
                        'token' => $reset_token,
                        'email' => (string)$email,
                        'expires_minutes' => 60
                    ];

                    if ($mailerService->sendTemplateEmail(
                        $user['email'],
                        $subject,
                        'password_reset_request',
                        $templateData
                    )) {
                        $flashMessageService->addSuccess('If an account with that email address exists, a password reset link has been sent. Please check your inbox (and spam folder).');
                    } else {
                        $flashMessageService->addError('Failed to send password reset email. Please try again later or contact support.');
                        error_log("Forgot Password: Failed to send email to " . $user['email'] . " - " . $mailerService->getLastError());
                    }
                } else {
                    $flashMessageService->addError('Failed to generate password reset token. Please try again.');
                    error_log("Forgot Password: TokenManager::generateToken() failed for user ID: " . $user['id']);
                }
            } else {
                // Show the same success message for security
                $flashMessageService->addSuccess('If an account with that email address exists, a password reset link has been sent. Please check your inbox (and spam folder).');
            }
        }
    }

    // Always redirect after POST processing
    header("Location: /index.php?page=forgot_password");
    exit();
}

// Use new CSRFMiddleware to get a token
$csrf_token = CSRFMiddleware::getToken();
?>

<!-- Isolated CSS for auth pages -->
<link rel="stylesheet" href="/themes/default/css/pages/_auth.css">

<div class="auth-page-wrapper">
<div class="page-container-full-width">
    <div class="auth-page-container">
        <div class="auth-layout-two-column">
            <div class="auth-column-form">
                <div class="auth-form-card">
                    <!-- Forgot Password Header -->
                    <div class="forgot-password-header">
                        <div class="forgot-icon">
                            <span style="font-size: 3rem; color: #f59e0b;">🔑</span>
                        </div>
                        <h2 style="color: #e2e8f0; margin-bottom: 0.5rem; text-align: center; font-size: 1.5rem;">Forgot Your Password?</h2>
                        <p style="color: #94a3b8; text-align: center; margin-bottom: 2rem; font-size: 0.95rem;">
                            No worries! We'll send you a link to reset your password.
                        </p>
                    </div>

                    <form action="/index.php?page=forgot_password" method="POST" id="forgotPasswordForm">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                        <div class="form-group">
                            <label for="email" class="form-label">Your Email Address:</label>
                            <div class="input-group">
                                <span class="input-group-icon">✉️</span>
                                <input type="email" name="email" id="email" class="form-control"
                                       placeholder="Enter your registered email address" required autocomplete="email">
                            </div>
                            <div class="email-hint" style="margin-top: 0.5rem; font-size: 0.85rem; color: #64748b;">
                                We'll send reset instructions to this email
                            </div>
                        </div>

                        <!-- Security Notice -->
                        <div class="security-notice" style="background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.3); border-radius: 0.5rem; padding: 1rem; margin: 1.5rem 0;">
                            <div style="display: flex; align-items: center; margin-bottom: 0.5rem;">
                                <span style="color: #fbbf24; margin-right: 0.5rem; font-size: 1.1rem;">🔐</span>
                                <h4 style="color: #fbbf24; font-size: 0.9rem; margin: 0; font-weight: 600;">Security Information</h4>
                            </div>
                            <ul style="margin: 0; padding-left: 1.25rem; font-size: 0.85rem; color: #d97706;">
                                <li>Reset links expire after 1 hour</li>
                                <li>Only the most recent link will work</li>
                                <li>Links can only be used once</li>
                            </ul>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="button button-primary button-block" id="submitBtn">
                                <span class="button-icon">📧</span>
                                <span class="button-text">Send Reset Link</span>
                            </button>
                        </div>
                    </form>

                    <div class="auth-form-footer">
                        <div style="text-align: center; margin-top: 2rem;">
                            <p style="color: #64748b; font-size: 0.9rem; margin-bottom: 1rem;">
                                Remembered your password?
                            </p>
                            <div class="footer-actions" style="display: flex; flex-direction: column; gap: 0.75rem; align-items: center;">
                                <a href="/index.php?page=login" class="button button-secondary button-with-icon" style="min-width: 180px;">
                                    <span class="button-icon">🔑</span>
                                    <span class="button-text">Back to Login</span>
                                </a>
                                <div style="color: #475569; font-size: 0.85rem;">
                                    Don't have an account? <a href="/index.php?page=register" style="color: #3b82f6; text-decoration: none;">Create one</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="auth-column-info">
                <div class="auth-info-content">
                    <h3 style="color: #f59e0b; margin-bottom: 1rem;">🔑 Password Recovery Help</h3>
                    <p style="color: #e2e8f0; margin-bottom: 1.5rem;">
                        Having trouble accessing your account? Here's what you need to know about password recovery:
                    </p>

                    <div class="help-steps" style="margin: 1.5rem 0;">
                        <div class="help-step" style="display: flex; align-items: flex-start; margin-bottom: 1.25rem;">
                            <div style="background: rgba(245, 158, 11, 0.2); border-radius: 50%; padding: 0.5rem; margin-right: 1rem; min-width: 2.5rem; height: 2.5rem; display: flex; align-items: center; justify-content: center;">
                                <span style="color: #fbbf24; font-weight: bold; font-size: 0.9rem;">1</span>
                            </div>
                            <div>
                                <h4 style="color: #cbd5e1; font-size: 0.95rem; margin-bottom: 0.25rem;">Enter Your Email</h4>
                                <p style="color: #94a3b8; font-size: 0.85rem; margin: 0;">Use the same email address you registered with</p>
                            </div>
                        </div>

                        <div class="help-step" style="display: flex; align-items: flex-start; margin-bottom: 1.25rem;">
                            <div style="background: rgba(245, 158, 11, 0.2); border-radius: 50%; padding: 0.5rem; margin-right: 1rem; min-width: 2.5rem; height: 2.5rem; display: flex; align-items: center; justify-content: center;">
                                <span style="color: #fbbf24; font-weight: bold; font-size: 0.9rem;">2</span>
                            </div>
                            <div>
                                <h4 style="color: #cbd5e1; font-size: 0.95rem; margin-bottom: 0.25rem;">Check Your Email</h4>
                                <p style="color: #94a3b8; font-size: 0.85rem; margin: 0;">Look for our message (check spam folder too)</p>
                            </div>
                        </div>

                        <div class="help-step" style="display: flex; align-items: flex-start; margin-bottom: 1.25rem;">
                            <div style="background: rgba(245, 158, 11, 0.2); border-radius: 50%; padding: 0.5rem; margin-right: 1rem; min-width: 2.5rem; height: 2.5rem; display: flex; align-items: center; justify-content: center;">
                                <span style="color: #fbbf24; font-weight: bold; font-size: 0.9rem;">3</span>
                            </div>
                            <div>
                                <h4 style="color: #cbd5e1; font-size: 0.95rem; margin-bottom: 0.25rem;">Click the Link</h4>
                                <p style="color: #94a3b8; font-size: 0.85rem; margin: 0;">Follow the reset link to set a new password</p>
                            </div>
                        </div>
                    </div>

                    <div class="troubleshooting" style="background: rgba(71, 85, 105, 0.3); border-radius: 0.5rem; padding: 1rem; margin-top: 1.5rem;">
                        <h4 style="color: #cbd5e1; font-size: 0.9rem; margin-bottom: 0.75rem; font-weight: 600;">🔧 Troubleshooting</h4>
                        <ul style="margin: 0; padding-left: 1.25rem; font-size: 0.85rem; color: #94a3b8;">
                            <li style="margin-bottom: 0.5rem;"><strong>No email received?</strong> Check your spam/junk folder</li>
                            <li style="margin-bottom: 0.5rem;"><strong>Link expired?</strong> Request a new reset link</li>
                            <li style="margin-bottom: 0.5rem;"><strong>Multiple attempts?</strong> Use only the most recent email</li>
                            <li><strong>Still having issues?</strong> Contact our support team</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<script src="/themes/default/js/auth-common.js" defer></script>
