<?php

/**
 * Registration Page
 *
 * This page allows users to register for a new account.
 * It includes a form for username, email, password, and confirmation.
 * It also includes a privacy policy agreement and terms of service.
 *
 * @author Dmytro Hovenko
 */

use App\Application\Core\ServiceProvider;
use App\Application\Core\SessionManager;
use App\Application\Middleware\CSRFMiddleware;
use App\Application\Services\SiteSettingsService;

// Use global services (already initialized in webengine.php)
global $flashMessageService, $tokenManager, $database_handler;

// Initialize services and secure session via our controllers
try {
    $services = ServiceProvider::getInstance();

    // Start a session via SessionManager
    $sessionManager = SessionManager::getInstance(
        $services->getLogger(),
        [],
        $services->getConfigurationManager()
    );
    $sessionManager->start();

    // Fallback: get missing services from ServiceProvider
    if (!isset($flashMessageService) || !$flashMessageService) {
        $flashMessageService = $services->getFlashMessage();
    }
    if (!isset($tokenManager) || !$tokenManager) {
        if (method_exists($services, 'getTokenManager')) {
            $tokenManager = $services->getTokenManager();
        }
    }
} catch (Throwable $e) {
    error_log("Critical: Failed to initialize services/session in register.php: " . $e->getMessage());
}

// Final check for critical services
if (!isset($flashMessageService) || !isset($tokenManager) || !isset($database_handler)) {
     error_log("Critical: Required services not available in register.php");
     die("A critical system error occurred. Please try again later.");
}

// Check if registration is enabled
try {
    $siteSettingsService = new SiteSettingsService($database_handler);
    $registrationEnabled = $siteSettingsService->get('registration_enabled', true);

    if (!$registrationEnabled) {
        // Registration is disabled - show a warning message and redirect
        $flashMessageService->addWarning('Registration is currently disabled. Please contact the administrator if you need an account.');
        header("Location: /index.php?page=login");
        exit();
    }
} catch (Exception $e) {
    error_log("Failed to check registration status: " . $e->getMessage());
    // Continue with registration if we can't check settings
}

if (isset($_SESSION['user_id'])) {
    // Redirect to dashboard if the user is already authenticated
    header("Location: /index.php?page=dashboard");
    exit();
}

// Get form data from the session (if there are errors) via SessionManager
$form_data = ['username' => '', 'email' => ''];
try {
    if (isset($sessionManager)) {
        $form_data = $sessionManager->get('form_data_register', $form_data);
        $sessionManager->unset('form_data_register');
    } else {
        // Fallback to direct $_SESSION access
        $form_data = $_SESSION['form_data_register'] ?? $form_data;
        unset($_SESSION['form_data_register']);
    }
} catch (Throwable $e) {
    // In case of error, just use default values
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
                    <!-- Registration Header -->
                    <div class="registration-header">
                        <div class="registration-icon">
                            <span style="font-size: 3rem; color: #8b5cf6;">✨</span>
                        </div>
                        <h2 style="color: #e2e8f0; margin-bottom: 0.5rem; text-align: center; font-size: 1.5rem;">Join Our Community</h2>
                        <p style="color: #94a3b8; text-align: center; margin-bottom: 2rem; font-size: 0.95rem;">
                            Create your developer account and start your journey with us
                        </p>
                    </div>

                    <form action="/index.php?page=form_register" method="POST" id="registerForm">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                        <div class="form-group">
                            <label for="username" class="form-label">Username:</label>
                            <div class="input-group">
                                <span class="input-group-icon">👤</span>
                                <input type="text" name="username" id="username" class="form-control" 
                                       placeholder="Choose a unique username (3-50 characters)" required 
                                       value="<?php echo htmlspecialchars($form_data['username']); ?>"
                                       pattern="[a-zA-Z0-9_]{3,50}" autocomplete="username">
                            </div>
                            <div class="username-validation" id="username_validation" style="margin-top: 0.5rem; font-size: 0.85rem; color: #64748b;">
                                Username must be 3–50 characters (letters, numbers, underscores only)
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="email" class="form-label">Email Address:</label>
                            <div class="input-group">
                                <span class="input-group-icon">✉️</span>
                                <input type="email" name="email" id="email" class="form-control" 
                                       placeholder="Enter your email address" required 
                                       value="<?php echo htmlspecialchars($form_data['email']); ?>"
                                       autocomplete="email">
                            </div>
                            <div class="email-hint" style="margin-top: 0.5rem; font-size: 0.85rem; color: #64748b;">
                                We'll send a verification email to this address
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="password" class="form-label">Password:</label>
                            <div class="input-group">
                                <span class="input-group-icon">🔒</span>
                                <input type="password" name="password" id="password" class="form-control" 
                                       placeholder="Create a strong password" required autocomplete="new-password">
                                <button type="button" class="password-toggle" onclick="togglePassword('password')">
                                    <span class="toggle-icon" id="password_toggle">👁️</span>
                                </button>
                            </div>
                            <div class="password-strength" id="password_strength" style="margin-top: 0.5rem;">
                                <div class="strength-bar">
                                    <div class="strength-fill" id="strength_fill"></div>
                                </div>
                                <div class="strength-text" id="strength_text" style="font-size: 0.85rem; color: #94a3b8; margin-top: 0.25rem;">
                                    Enter a password to see strength
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="password_confirm" class="form-label">Confirm Password:</label>
                            <div class="input-group">
                                <span class="input-group-icon">🔒</span>
                                <input type="password" name="password_confirm" id="password_confirm" class="form-control" 
                                       placeholder="Confirm your password" required autocomplete="new-password">
                                <button type="button" class="password-toggle" onclick="togglePassword('password_confirm')">
                                    <span class="toggle-icon" id="password_confirm_toggle">👁️</span>
                                </button>
                            </div>
                            <div class="password-match" id="password_match" style="margin-top: 0.5rem; font-size: 0.85rem; color: #94a3b8;">
                                Passwords will be compared here
                            </div>
                        </div>

                        <!-- Password Requirements -->
                        <div class="password-requirements" style="background: rgba(71, 85, 105, 0.3); border-radius: 0.5rem; padding: 1rem; margin: 1.5rem 0;">
                            <h4 style="color: #cbd5e1; font-size: 0.9rem; margin-bottom: 0.75rem; font-weight: 600;">Password Requirements:</h4>
                            <ul id="requirements_list" style="margin: 0; padding-left: 1.25rem; font-size: 0.85rem; color: #94a3b8;">
                                <li id="req_length">At least eight characters long</li>
                                <li id="req_letter">Contains letters (a-z)</li>
                                <li id="req_number">Contains at least one number</li>
                                <li id="req_special">Contains special characters (!@#$%^&*)</li>
                            </ul>
                        </div>

                        <!-- Legal Agreement Section -->
                        <div class="form-group legal-agreements" style="background: rgba(139, 92, 246, 0.05); border: 1px solid rgba(139, 92, 246, 0.2); border-radius: 0.5rem; padding: 1rem; margin: 1.5rem 0;">
                            <h4 style="color: #a78bfa; font-size: 0.9rem; margin-bottom: 0.75rem; font-weight: 600;">📋 Legal Requirements</h4>

                            <div class="legal-agreement-item">
                                <div class="form-check" style="display: flex; align-items: flex-start; gap: 0.75rem; margin-bottom: 1rem;">
                                    <input type="checkbox" name="accept_privacy" id="accept_privacy" class="form-check-input" required style="margin-top: 0.25rem;">
                                    <label for="accept_privacy" class="form-check-label" style="color: #c4b5fd; font-size: 0.9rem; line-height: 1.4;">
                                        I have read and agree to the <a href="/index.php?page=privacy" target="_blank" rel="noopener noreferrer" style="color: #8b5cf6; text-decoration: underline;">Privacy Policy</a> <span class="required-asterisk" style="color: #ef4444;">*</span>
                                    </label>
                                </div>
                            </div>

                            <div class="legal-agreement-item">
                                <div class="form-check" style="display: flex; align-items: flex-start; gap: 0.75rem;">
                                    <input type="checkbox" name="accept_terms" id="accept_terms" class="form-check-input" required style="margin-top: 0.25rem;">
                                    <label for="accept_terms" class="form-check-label" style="color: #c4b5fd; font-size: 0.9rem; line-height: 1.4;">
                                        I have read and agree to the <a href="/index.php?page=terms" target="_blank" rel="noopener noreferrer" style="color: #8b5cf6; text-decoration: underline;">Terms of Service</a> <span class="required-asterisk" style="color: #ef4444;">*</span>
                                    </label>
                                </div>
                            </div>

                            <div class="legal-notice" style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgba(139, 92, 246, 0.2);">
                                <p style="color: #a78bfa; font-size: 0.85rem; margin: 0;">
                                    By creating an account, you agree to our terms and acknowledge that you've read our privacy policy.
                                </p>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="button button-primary button-block" id="submitBtn" disabled>
                                <span class="button-icon">✨</span>
                                <span class="button-text">Create Account</span>
                            </button>
                        </div>
                    </form>

                    <div class="auth-form-footer">
                        <div style="text-align: center; margin-top: 2rem;">
                            <div class="divider" style="position: relative; margin: 1.5rem 0;">
                                <div style="position: absolute; inset: 0; display: flex; align-items: center;">
                                    <div style="width: 100%; border-top: 1px solid rgba(71, 85, 105, 0.4);"></div>
                                </div>
                                <div style="position: relative; display: flex; justify-content: center;">
                                    <span style="background: #1e293b; padding: 0 1rem; font-size: 0.85rem; color: #64748b;">or</span>
                                </div>
                            </div>

                            <div class="footer-actions" style="display: flex; flex-direction: column; gap: 0.75rem; align-items: center;">
                                <div style="color: #64748b; font-size: 0.9rem;">
                                    Already have an account?
                                </div>
                                <a href="/index.php?page=login" class="button button-secondary button-with-icon" style="min-width: 180px;">
                                    <span class="button-icon">🚀</span>
                                    <span class="button-text">Sign In</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="auth-column-info">
                <div class="auth-info-content">
                    <h3 style="color: #8b5cf6; margin-bottom: 1rem;">🎯 Registration Guide</h3>
                    <p style="color: #e2e8f0; margin-bottom: 1.5rem;">
                        Follow these steps to create your developer account and join our community of innovators.
                    </p>

                    <div class="registration-steps" style="margin: 1.5rem 0;">
                        <div class="registration-step" style="display: flex; align-items: flex-start; margin-bottom: 1.25rem;">
                            <div style="background: rgba(139, 92, 246, 0.2); border-radius: 50%; padding: 0.5rem; margin-right: 1rem; min-width: 2.5rem; height: 2.5rem; display: flex; align-items: center; justify-content: center;">
                                <span style="color: #a78bfa; font-weight: bold; font-size: 0.9rem;">1</span>
                            </div>
                            <div>
                                <h4 style="color: #cbd5e1; font-size: 0.95rem; margin-bottom: 0.25rem;">Choose Username</h4>
                                <p style="color: #94a3b8; font-size: 0.85rem; margin: 0;">Pick a unique identifier (3–50 characters, letters, numbers, underscores)</p>
                            </div>
                        </div>

                        <div class="registration-step" style="display: flex; align-items: flex-start; margin-bottom: 1.25rem;">
                            <div style="background: rgba(139, 92, 246, 0.2); border-radius: 50%; padding: 0.5rem; margin-right: 1rem; min-width: 2.5rem; height: 2.5rem; display: flex; align-items: center; justify-content: center;">
                                <span style="color: #a78bfa; font-weight: bold; font-size: 0.9rem;">2</span>
                            </div>
                            <div>
                                <h4 style="color: #cbd5e1; font-size: 0.95rem; margin-bottom: 0.25rem;">Verify Email</h4>
                                <p style="color: #94a3b8; font-size: 0.85rem; margin: 0;">Use a valid email address for account verification</p>
                            </div>
                        </div>

                        <div class="registration-step" style="display: flex; align-items: flex-start; margin-bottom: 1.25rem;">
                            <div style="background: rgba(139, 92, 246, 0.2); border-radius: 50%; padding: 0.5rem; margin-right: 1rem; min-width: 2.5rem; height: 2.5rem; display: flex; align-items: center; justify-content: center;">
                                <span style="color: #a78bfa; font-weight: bold; font-size: 0.9rem;">3</span>
                            </div>
                            <div>
                                <h4 style="color: #cbd5e1; font-size: 0.95rem; margin-bottom: 0.25rem;">Secure Password</h4>
                                <p style="color: #94a3b8; font-size: 0.85rem; margin: 0;">Create a strong password with mixed characters</p>
                            </div>
                        </div>

                        <div class="registration-step" style="display: flex; align-items: flex-start; margin-bottom: 1.25rem;">
                            <div style="background: rgba(139, 92, 246, 0.2); border-radius: 50%; padding: 0.5rem; margin-right: 1rem; min-width: 2.5rem; height: 2.5rem; display: flex; align-items: center; justify-content: center;">
                                <span style="color: #a78bfa; font-weight: bold; font-size: 0.9rem;">4</span>
                            </div>
                            <div>
                                <h4 style="color: #cbd5e1; font-size: 0.95rem; margin-bottom: 0.25rem;">Accept Terms</h4>
                                <p style="color: #94a3b8; font-size: 0.85rem; margin: 0;">Review and accept our policies to complete registration</p>
                            </div>
                        </div>
                    </div>

                    <div class="account-benefits" style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 0.5rem; padding: 1rem; margin-top: 1.5rem;">
                        <h4 style="color: #10b981; font-size: 0.9rem; margin-bottom: 0.75rem; font-weight: 600;">🌟 Account Benefits</h4>
                        <ul style="margin: 0; padding-left: 1.25rem; font-size: 0.85rem; color: #6ee7b7;">
                            <li style="margin-bottom: 0.5rem;">Access to development resources and tutorials</li>
                            <li style="margin-bottom: 0.5rem;">Participate in community discussions</li>
                            <li style="margin-bottom: 0.5rem;">Receive project updates and announcements</li>
                            <li style="margin-bottom: 0.5rem;">Submit feedback and feature requests</li>
                            <li>Connect with other developers</li>
                        </ul>
                    </div>
                </div>

                <!-- Quick Social Registration (Enhanced) -->
                <div class="social-auth-section" style="margin-top: 2rem;">
                    <h3 class="social-auth-title" style="color: #cbd5e1; font-size: 1rem; margin-bottom: 1rem; text-align: center;">🌐 Quick Registration</h3>
                    <p style="color: #94a3b8; font-size: 0.85rem; text-align: center; margin-bottom: 1rem;">
                        Or continue with your preferred platform
                    </p>
                    <div class="social-auth-buttons">
                        <a href="#" class="social-btn social-btn--google" title="Continue with Google">
                            <span class="social-btn-icon">
                                <svg width="16" height="16" viewBox="0 0 24 24">
                                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                                </svg>
                            </span>
                            Google
                        </a>
                        <a href="#" class="social-btn social-btn--meta" title="Continue with Meta">
                            <span class="social-btn-icon">
                                <svg width="16" height="16" viewBox="0 0 24 24">
                                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                </svg>
                            </span>
                            Meta
                        </a>
                        <a href="#" class="social-btn social-btn--telegram" title="Continue with Telegram">
                            <span class="social-btn-icon">
                                <svg width="16" height="16" viewBox="0 0 24 24">
                                    <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
                                </svg>
                            </span>
                            Telegram
                        </a>
                        <a href="#" class="social-btn social-btn--microsoft" title="Continue with Microsoft">
                            <span class="social-btn-icon">
                                <svg width="16" height="16" viewBox="0 0 24 24">
                                    <path d="M0 0h11.377v11.372H0zm12.623 0H24v11.372H12.623zM0 12.623h11.377V24H0zm12.623 0H24V24H12.623"/>
                                </svg>
                            </span>
                            Microsoft
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<!-- Modern auth functionality with registration-specific features -->
<script src="/themes/default/js/auth-common.js" defer></script>
<script src="/themes/default/js/auth-password.js" defer></script>
<script src="/themes/default/js/auth-register.js" defer></script>
<script src="/themes/default/js/registration-form.js" defer></script>
