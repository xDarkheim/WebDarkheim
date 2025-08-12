<?php

/**
 * Verify Email Page
 *
 * This page handles email verification for new users.
 * It checks the token from the URL and activates the user's account.'
 *
 * @author Dmytro Hovenko
 */

// Use global services (already initialized in webengine.php)
global $database_handler, $flashMessageService, $auth, $tokenManager;

use App\Domain\Models\User;
use App\Infrastructure\Lib\TokenManager;

if (!isset($auth)) {
    error_log("Critical: Auth service not available in verify_email.php");
    die("A critical error occurred (Auth service). Please contact support.");
}

if (!isset($tokenManager)) {
    error_log("Critical: TokenManager not available in verify_email.php");
    die("A critical error occurred (Token service). Please contact support.");
}

// Set page title
$page_title = 'Email Verification';

// Initialize variables
$verification_success = false;
$verification_message = '';
$verification_error = '';
$user_data = null;
$redirect_delay = 5; // 5 seconds for redirect

// Get token from URL
$verification_token = trim($_GET['token'] ?? '');

if (empty($verification_token)) {
    $verification_success = false;
    $verification_error = "Invalid verification link. Please check your email for the correct link.";
    $flashMessageService->addError($verification_error);
} else {
    // Validate token via TokenManager directly
    $tokenData = $tokenManager->validateToken(
        $verification_token,
        TokenManager::TYPE_EMAIL_VERIFICATION
    );

    if ($tokenData) {
        // Get user data
        $user = User::findById($database_handler, $tokenData['user_id']);

        if ($user) {
            // Activate user
            $conn = $database_handler->getConnection();
            $stmt = $conn->prepare("UPDATE users SET is_active = 1, status = 'active', user_status = 'active', email_verified_at = NOW() WHERE id = ?");
            $activated = $stmt->execute([$user['id']]);

            if ($activated) {
                // Revoke verification token
                $tokenManager->revokeToken($verification_token);

                $verification_success = true;
                $verification_message = "Your email has been successfully verified! You can now log in to your account.";
                $user_data = $user;

                $flashMessageService->addSuccess($verification_message);
            } else {
                $verification_success = false;
                $verification_error = "Failed to activate your account. Please try again or contact support.";
                $flashMessageService->addError($verification_error);
            }
        } else {
            $verification_success = false;
            $verification_error = "User account not found. Please try registering again.";
            $flashMessageService->addError($verification_error);
        }
    } else {
        $verification_success = false;
        $verification_error = "Invalid or expired verification token. Please request a new verification email.";
        $flashMessageService->addError($verification_error);
    }
}
?>

<!-- Isolated CSS for auth pages -->
<link rel="stylesheet" href="/themes/default/css/pages/_auth.css">

<div class="auth-page-wrapper">
<div class="page-container-full-width">
    <div class="auth-page-container">
        <h1 class="page-title auth-page-main-title"><?php echo htmlspecialchars($page_title); ?></h1>

        <div class="auth-layout-two-column">
            <div class="auth-column-form">
                <div class="auth-form-card">
                    <?php if ($verification_success): ?>
                        <!-- Successful verification -->
                        <div class="verification-success">
                            <div class="verification-icon success">
                                <span style="font-size: 4rem; color: #10b981;">✅</span>
                            </div>

                            <div class="verification-content">
                                <h2 style="color: #10b981; margin-bottom: 1rem; text-align: center;">Verification Complete!</h2>
                                <p style="color: #e2e8f0; text-align: center; margin-bottom: 1.5rem; font-size: 1.1rem;">
                                    <?php echo htmlspecialchars($verification_message ?? 'Your email has been verified successfully!'); ?>
                                </p>

                                <?php if ($user_data): ?>
                                    <div class="user-info" style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 0.5rem; padding: 1rem; margin-bottom: 1.5rem;">
                                        <p style="margin: 0.5rem 0; color: #a7f3d0;"><strong>Username:</strong> <?php echo htmlspecialchars($user_data['username']); ?></p>
                                        <p style="margin: 0.5rem 0; color: #a7f3d0;"><strong>Email:</strong> <?php echo htmlspecialchars($user_data['email']); ?></p>
                                        <p style="margin: 0.5rem 0; color: #a7f3d0;"><strong>Account Status:</strong> Active</p>
                                    </div>
                                <?php endif; ?>

                                <div class="verification-success-note" style="text-align: center; margin-bottom: 1.5rem;">
                                    <p style="color: #cbd5e1; font-size: 1rem;">
                                        You can now log in to your account and enjoy all features.
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Verification error -->
                        <div class="verification-error">
                            <div class="verification-icon error">
                                <span style="font-size: 4rem; color: #ef4444;">❌</span>
                            </div>

                            <div class="verification-content">
                                <h2 style="color: #ef4444; margin-bottom: 1rem; text-align: center;">Verification Failed</h2>
                                <p style="color: #e2e8f0; text-align: center; margin-bottom: 1.5rem; font-size: 1.1rem;">
                                    <?php echo htmlspecialchars($verification_error ?? 'An error occurred during verification.'); ?>
                                </p>

                                <div class="error-help" style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 0.5rem; padding: 1rem; margin-bottom: 1.5rem;">
                                    <p style="margin: 0.5rem 0; color: #fecaca;"><strong>What you can do:</strong></p>
                                    <ul style="color: #fecaca; margin: 0.5rem 0; padding-left: 1.5rem;">
                                        <li>Check if you're using the complete verification link from your email</li>
                                        <li>Try registering again if the token has expired</li>
                                        <li>Contact support if the problem continues</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="auth-form-footer" style="text-align: center; padding-top: 1.5rem; border-top: 1px solid rgba(71, 85, 105, 0.4);">
                        <?php if ($verification_success): ?>
                            <div class="button-group-success">
                                <a href="<?php echo rtrim(SITE_URL, '/'); ?>/index.php?page=login" class="button button-primary button-with-icon">
                                    <span class="button-icon">🚀</span>
                                    <span class="button-text">Login Now</span>
                                </a>
                                <a href="<?php echo rtrim(SITE_URL, '/'); ?>/index.php?page=home" class="button button-secondary button-with-icon">
                                    <span class="button-icon">🏠</span>
                                    <span class="button-text">Go to Home</span>
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="button-group-error">
                                <a href="<?php echo rtrim(SITE_URL, '/'); ?>/index.php?page=register" class="button button-primary button-with-icon">
                                    <span class="button-icon">📝</span>
                                    <span class="button-text">Try Register Again</span>
                                </a>
                                <a href="<?php echo rtrim(SITE_URL, '/'); ?>/index.php?page=resend_verification" class="button button-secondary button-with-icon">
                                    <span class="button-icon">📧</span>
                                    <span class="button-text">Resend Verification</span>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="auth-column-info">
                <?php if ($verification_success): ?>
                    <div class="auth-info-content">
                        <h3 style="color: #10b981; margin-bottom: 1rem;">🎉 Welcome to Our Community!</h3>
                        <p style="color: #e2e8f0; margin-bottom: 1rem;">
                            Your email has been successfully verified and your account is now fully active. You can now:
                        </p>
                        <ul style="color: #cbd5e1; margin: 1rem 0; padding-left: 1.5rem;">
                            <li>Access all features of your account</li>
                            <li>Participate in discussions</li>
                            <li>Receive important notifications</li>
                            <li>Create and manage content</li>
                        </ul>
                        <p style="color: #e2e8f0;">
                            Thank you for joining our community. We're excited to have you aboard!
                        </p>
                    </div>
                <?php else: ?>
                    <div class="auth-warning-message">
                        <p><strong>Verification Issues?</strong></p>
                        <p>Email verification links are time-sensitive and expire after 24 hours for security reasons.</p>
                        <p>If your verification link has expired or is invalid, you can request a new one or try registering again.</p>
                        <p>Make sure you're clicking the complete link from your email, including all parameters.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</div>

<script src="/themes/default/js/auth-common.js" defer></script>

<style>
/* Additional styles for verification page */
.verification-success,
.verification-error {
    text-align: center;
    padding: 2rem 0;
}

.verification-icon {
    margin-bottom: 1.5rem;
}

.verification-content h2 {
    font-size: 1.5rem;
    font-weight: 700;
}

/* ===========================================
   MODERN BUTTONS FOR VERIFY EMAIL
   =========================================== */

.button-group-success,
.button-group-error {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    align-items: center;
    margin-top: 1rem;
}

.button {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.875rem 1.75rem;
    border-radius: 0.75rem;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.95rem;
    letter-spacing: 0.025em;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: none;
    cursor: pointer;
    overflow: hidden;
    min-width: 200px;
    text-align: center;
    backdrop-filter: blur(10px);
    box-shadow:
        0 4px 6px -1px rgba(0, 0, 0, 0.1),
        0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.button::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 1;
}

.button:hover::before {
    left: 100%;
}

.button-with-icon {
    position: relative;
}

.button-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    z-index: 2;
    position: relative;
    transition: transform 0.3s ease;
}

.button-text {
    z-index: 2;
    position: relative;
    font-weight: 600;
}

/* Primary Button - Success State */
.button-primary {
    background: linear-gradient(135deg, #10b981 0%, #059669 50%, #047857 100%);
    color: white;
    border: 1px solid rgba(16, 185, 129, 0.3);
    box-shadow:
        0 4px 14px 0 rgba(16, 185, 129, 0.3),
        0 2px 4px -1px rgba(0, 0, 0, 0.06),
        inset 0 1px 0 rgba(255, 255, 255, 0.1);
}

.button-primary:hover {
    transform: translateY(-3px) scale(1.02);
    box-shadow:
        0 10px 25px -3px rgba(16, 185, 129, 0.4),
        0 4px 6px -2px rgba(0, 0, 0, 0.05),
        inset 0 1px 0 rgba(255, 255, 255, 0.2);
    background: linear-gradient(135deg, #059669 0%, #047857 50%, #065f46 100%);
}

.button-primary:active {
    transform: translateY(-1px) scale(1.01);
    transition: all 0.1s ease;
}

.button-primary .button-icon {
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
}

.button-primary:hover .button-icon {
    transform: scale(1.1) rotate(5deg);
}

/* Secondary Button */
.button-secondary {
    background: linear-gradient(135deg, rgba(71, 85, 105, 0.8) 0%, rgba(51, 65, 85, 0.9) 100%);
    color: #e2e8f0;
    border: 1px solid rgba(71, 85, 105, 0.6);
    box-shadow:
        0 4px 14px 0 rgba(71, 85, 105, 0.2),
        0 2px 4px -1px rgba(0, 0, 0, 0.06),
        inset 0 1px 0 rgba(255, 255, 255, 0.05);
}

.button-secondary:hover {
    transform: translateY(-3px) scale(1.02);
    background: linear-gradient(135deg, rgba(71, 85, 105, 1) 0%, rgba(51, 65, 85, 1) 100%);
    color: #f8fafc;
    border-color: rgba(71, 85, 105, 0.8);
    box-shadow:
        0 10px 25px -3px rgba(71, 85, 105, 0.3),
        0 4px 6px -2px rgba(0, 0, 0, 0.05),
        inset 0 1px 0 rgba(255, 255, 255, 0.1);
}

.button-secondary:active {
    transform: translateY(-1px) scale(1.01);
    transition: all 0.1s ease;
}

.button-secondary .button-icon {
    filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.1));
}

.button-secondary:hover .button-icon {
    transform: scale(1.05) rotate(-2deg);
}

/* Error State Buttons (red theme) */
.button-group-error .button-primary {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 50%, #b91c1c 100%);
    border-color: rgba(239, 68, 68, 0.3);
    box-shadow:
        0 4px 14px 0 rgba(239, 68, 68, 0.3),
        0 2px 4px -1px rgba(0, 0, 0, 0.06),
        inset 0 1px 0 rgba(255, 255, 255, 0.1);
}

.button-group-error .button-primary:hover {
    background: linear-gradient(135deg, #dc2626 0%, #b91c1c 50%, #991b1b 100%);
    box-shadow:
        0 10px 25px -3px rgba(239, 68, 68, 0.4),
        0 4px 6px -2px rgba(0, 0, 0, 0.05),
        inset 0 1px 0 rgba(255, 255, 255, 0.2);
}

/* Button appearance animation */
.button-group-success,
.button-group-error {
    animation: slideInUp 0.6s ease-out;
}

@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.button {
    animation: buttonFadeIn 0.8s ease-out;
}

@keyframes buttonFadeIn {
    from {
        opacity: 0;
        transform: translateY(10px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

/* Pulsing effect for primary button */
.button-primary {
    animation: buttonFadeIn 0.8s ease-out, primaryPulse 2s ease-in-out 1s infinite;
}

@keyframes primaryPulse {
    0%, 100% {
        box-shadow:
            0 4px 14px 0 rgba(16, 185, 129, 0.3),
            0 2px 4px -1px rgba(0, 0, 0, 0.06),
            inset 0 1px 0 rgba(255, 255, 255, 0.1);
    }
    50% {
        box-shadow:
            0 6px 20px 0 rgba(16, 185, 129, 0.4),
            0 3px 6px -1px rgba(0, 0, 0, 0.08),
            inset 0 1px 0 rgba(255, 255, 255, 0.15);
    }
}

/* Loading effect for buttons */

.button.loading .button-text {
    opacity: 0.6;
}

.button.loading .button-icon {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Responsiveness */
@media (max-width: 768px) {
    .button-group-success,
    .button-group-error {
        flex-direction: column;
        width: 100%;
    }

    .button {
        width: 100%;
        min-width: auto;
        padding: 1rem 1.5rem;
        font-size: 1rem;
    }

    .button-icon {
        font-size: 1.3rem;
    }
}

@media (max-width: 480px) {
    .button {
        padding: 0.875rem 1.25rem;
        font-size: 0.9rem;
    }

    .button-icon {
        font-size: 1.2rem;
    }
}

/* Additional interactive effects */
.button:focus {
    outline: none;
    ring: 2px solid rgba(59, 130, 246, 0.5);
    ring-offset: 2px;
}

.button:focus-visible {
    outline: 2px solid rgba(59, 130, 246, 0.8);
    outline-offset: 2px;
}

/* Press effect */
.button:active {
    transform: translateY(0) scale(0.98);
}

/* Hover effects for touch devices */
@media (hover: none) {
    .button:hover {
        transform: none;
    }

    .button:active {
        transform: scale(0.98);
        transition: transform 0.1s ease;
    }
}
</style>
