<?php

/**
 * Resend Verification Page
 *
 * This page allows users to resend their verification email.
 * It includes a form for entering their email address and a button to send the verification email.
 *
 * @author Dmytro Hovenko
 */


// Use global services (already initialized in webengine.php)
global $database_handler, $flashMessageService, $container, $tokenManager;

use App\Application\Middleware\CSRFMiddleware;
use App\Domain\Interfaces\UserRegistrationInterface;

if (!isset($container)) {
    error_log("Critical: Container not available in resend_verification.php");
    die("A critical error occurred (Container). Please contact support.");
}

if (!isset($tokenManager)) {
    error_log("Critical: TokenManager not available in resend_verification.php");
    die("A critical error occurred (Token service). Please contact support.");
}

// Get UserRegistrationService from container
try {
    $userRegistration = $container->make(UserRegistrationInterface::class);
} catch (Exception $e) {
    error_log("Critical: UserRegistrationService not available: " . $e->getMessage());
    die("A critical error occurred (Registration service). Please contact support.");
}

// Set page title
$page_title = 'Resend Email Verification';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token via new CSRFMiddleware
    if (!CSRFMiddleware::validateQuick()) {
        $flashMessageService->addError('Security error: Invalid CSRF token. Please try again.');
        error_log("Resend Verification: CSRF token validation failed. IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    } else {
        $email_to_resend = trim($_POST['email'] ?? '');
        if (empty($email_to_resend) || !filter_var($email_to_resend, FILTER_VALIDATE_EMAIL)) {
            $flashMessageService->addError("Please provide a valid email address.");
        } else {
            // Use UserRegistrationService instead of deprecated auth service
            $result = $userRegistration->resendVerification($email_to_resend);
            if ($result) {
                header("Location: /index.php?page=login");
                exit();
            }
            // Errors are already added in UserRegistrationService
        }
    }

    // Always redirect after POST processing
    header("Location: /index.php?page=resend_verification");
    exit();
}

// For GET request - get email from parameter and generate token
$email_to_resend = trim($_GET['email'] ?? '');

// Use new CSRFMiddleware to get token
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
                    <!-- Resend Verification Header -->
                    <div class="resend-verification-header">
                        <div class="verification-icon">
                            <span style="font-size: 3rem; color: #8b5cf6;">📧</span>
                        </div>
                        <h2 style="color: #e2e8f0; margin-bottom: 0.5rem; text-align: center; font-size: 1.5rem;">Resend Email Verification</h2>
                        <p style="color: #94a3b8; text-align: center; margin-bottom: 2rem; font-size: 0.95rem;">
                            Haven't received your verification email? We'll send you another one.
                        </p>
                    </div>

                    <form action="<?php echo rtrim(SITE_URL, '/'); ?>/index.php?page=resend_verification" method="POST" id="resendVerificationForm">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                        <div class="form-group">
                            <label for="email" class="form-label">Your Registered Email Address:</label>
                            <div class="input-group">
                                <span class="input-group-icon">✉️</span>
                                <input type="email" id="email" name="email" class="form-control"
                                       value="<?php echo htmlspecialchars($email_to_resend ?? ''); ?>"
                                       placeholder="Enter the email you used to register" required autocomplete="email">
                            </div>
                            <div class="email-hint" style="margin-top: 0.5rem; font-size: 0.85rem; color: #64748b;">
                                This must be the exact email address you used during registration
                            </div>
                        </div>

                        <!-- Verification Status Info -->
                        <div class="verification-status" style="background: rgba(139, 92, 246, 0.1); border: 1px solid rgba(139, 92, 246, 0.3); border-radius: 0.5rem; padding: 1rem; margin: 1.5rem 0;">
                            <div style="display: flex; align-items: center; margin-bottom: 0.75rem;">
                                <span style="color: #a78bfa; margin-right: 0.5rem; font-size: 1.1rem;">📝</span>
                                <h4 style="color: #a78bfa; font-size: 0.9rem; margin: 0; font-weight: 600;">Verification Status</h4>
                            </div>
                            <p style="margin: 0; font-size: 0.85rem; color: #c4b5fd;">
                                Your account is currently <strong>unverified</strong>. You need to verify your email address to activate your account and access all features.
                            </p>
                        </div>

                        <!-- Before You Resend -->
                        <div class="resend-checklist" style="background: rgba(71, 85, 105, 0.3); border-radius: 0.5rem; padding: 1rem; margin: 1.5rem 0;">
                            <h4 style="color: #cbd5e1; font-size: 0.9rem; margin-bottom: 0.75rem; font-weight: 600;">✅ Before you resend, please check:</h4>
                            <ul style="margin: 0; padding-left: 1.25rem; font-size: 0.85rem; color: #94a3b8;">
                                <li style="margin-bottom: 0.5rem;">Your email inbox (including spam/junk folder)</li>
                                <li style="margin-bottom: 0.5rem;">Make sure you're using the correct email address</li>
                                <li style="margin-bottom: 0.5rem;">Wait a few minutes—emails can be delayed</li>
                                <li>Check if you've already verified your email</li>
                            </ul>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="button button-primary button-block" id="submitBtn">
                                <span class="button-icon">📨</span>
                                <span class="button-text">Send New Verification Email</span>
                            </button>
                        </div>
                    </form>

                    <div class="auth-form-footer">
                        <div style="text-align: center; margin-top: 2rem;">
                            <p style="color: #64748b; font-size: 0.9rem; margin-bottom: 1rem;">
                                Already verified your email?
                            </p>
                            <div class="footer-actions" style="display: flex; flex-direction: column; gap: 0.75rem; align-items: center;">
                                <a href="<?php echo rtrim(SITE_URL, '/'); ?>/index.php?page=login" class="button button-secondary button-with-icon" style="min-width: 180px;">
                                    <span class="button-icon">🔑</span>
                                    <span class="button-text">Try to Login</span>
                                </a>
                                <div style="color: #475569; font-size: 0.85rem;">
                                    Need to use a different email? <a href="<?php echo rtrim(SITE_URL, '/'); ?>/index.php?page=register" style="color: #3b82f6; text-decoration: none;">Register again</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="auth-column-info">
                <div class="auth-info-content">
                    <h3 style="color: #8b5cf6; margin-bottom: 1rem;">📧 Email Verification Help</h3>
                    <p style="color: #e2e8f0; margin-bottom: 1.5rem;">
                        Email verification is required to activate your account and ensure security. Here's what you need to know:
                    </p>

                    <div class="verification-benefits" style="margin: 1.5rem 0;">
                        <h4 style="color: #cbd5e1; font-size: 0.95rem; margin-bottom: 1rem; font-weight: 600;">Why verify your email?</h4>

                        <div class="benefit-item" style="display: flex; align-items: flex-start; margin-bottom: 1rem;">
                            <span style="color: #10b981; margin-right: 0.75rem; font-size: 1.1rem;">🔐</span>
                            <div>
                                <h5 style="color: #cbd5e1; font-size: 0.9rem; margin-bottom: 0.25rem;">Account Security</h5>
                                <p style="color: #94a3b8; font-size: 0.85rem; margin: 0;">Protects your account from unauthorized access</p>
                            </div>
                        </div>

                        <div class="benefit-item" style="display: flex; align-items: flex-start; margin-bottom: 1rem;">
                            <span style="color: #3b82f6; margin-right: 0.75rem; font-size: 1.1rem;">💌</span>
                            <div>
                                <h5 style="color: #cbd5e1; font-size: 0.9rem; margin-bottom: 0.25rem;">Important Notifications</h5>
                                <p style="color: #94a3b8; font-size: 0.85rem; margin: 0;">Receive updates, security alerts, and account information</p>
                            </div>
                        </div>

                        <div class="benefit-item" style="display: flex; align-items: flex-start; margin-bottom: 1rem;">
                            <span style="color: #f59e0b; margin-right: 0.75rem; font-size: 1.1rem;">🔄</span>
                            <div>
                                <h5 style="color: #cbd5e1; font-size: 0.9rem; margin-bottom: 0.25rem;">Password Recovery</h5>
                                <p style="color: #94a3b8; font-size: 0.85rem; margin: 0;">Ability to reset your password if you forget it</p>
                            </div>
                        </div>
                    </div>

                    <div class="email-tips" style="background: rgba(139, 92, 246, 0.1); border: 1px solid rgba(139, 92, 246, 0.3); border-radius: 0.5rem; padding: 1rem; margin-top: 1.5rem;">
                        <h4 style="color: #a78bfa; font-size: 0.9rem; margin-bottom: 0.75rem; font-weight: 600;">💡 Email Tips</h4>
                        <ul style="margin: 0; padding-left: 1.25rem; font-size: 0.85rem; color: #c4b5fd;">
                            <li style="margin-bottom: 0.5rem;">Check your spam/junk folder—verification emails sometimes end up there</li>
                            <li style="margin-bottom: 0.5rem;">Add our domain to your email allowlist for future messages</li>
                            <li style="margin-bottom: 0.5rem;">Verification links expire after 24 hours for security</li>
                            <li>If you continue having issues, please contact our support team</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<script src="/themes/default/js/auth-common.js" defer></script>
