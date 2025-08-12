<?php

/**
 * Login Page
 *
 * This page handles the login process for users.
 * It includes a form for user input and validation.
 *
 * @author Dmytro Hovenko
 */

use App\Application\Core\ServiceProvider;
use App\Application\Core\SessionManager;

// Use global services (already initialized in webengine.php)
global $flashMessageService, $tokenManager;

if (!isset($flashMessageService) || !isset($tokenManager)) {
    error_log("Critical: Required services not available in login.php");
    die("A critical system error occurred. Please try again later.");
}

if (isset($_SESSION['user_id'])) {
    // Redirect to dashboard if the user is already authenticated
    header("Location: /index.php?page=dashboard");
    exit();
}

// Get CSRF token via SessionManager
global $container;
$services = ServiceProvider::getInstance($container);
$configManager = $services->getConfigurationManager();
$sessionManager = SessionManager::getInstance($services->getLogger(), [], $configManager);
$csrf_token = $sessionManager->getCsrfToken();

// Get form data from the session (if any)
$submitted_username_or_email = $_SESSION['form_data_login_username'] ?? '';
$submitted_remember_me = $_SESSION['form_data_login_remember_me'] ?? false;

// Clear form data from the session
unset($_SESSION['form_data_login_username'], $_SESSION['form_data_login_remember_me']);
?>

<!-- Isolated CSS for auth pages -->
<link rel="stylesheet" href="/themes/default/css/pages/_auth.css">

<div class="auth-page-wrapper">
<div class="page-container-full-width">
    <div class="auth-page-container">
        <div class="auth-layout-two-column">
            <div class="auth-column-form">
                <div class="auth-form-card">
                    <!-- Login Header -->
                    <div class="login-header">
                        <div class="login-icon">
                            <span style="font-size: 3rem; color: #10b981;">🚀</span>
                        </div>
                        <h2 style="color: #e2e8f0; margin-bottom: 0.5rem; text-align: center; font-size: 1.5rem;">Welcome Back!</h2>
                        <p style="color: #94a3b8; text-align: center; margin-bottom: 2rem; font-size: 0.95rem;">
                            Sign in to your account to continue your development journey
                        </p>
                    </div>

                    <form action="/index.php?page=form_login" method="POST" id="loginForm">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                        <div class="form-group">
                            <label for="login_username_or_email" class="form-label">Username or Email:</label>
                            <div class="input-group">
                                <span class="input-group-icon">👤</span>
                                <input type="text" id="login_username_or_email" name="username_or_email"
                                       class="form-control"
                                       placeholder="Enter your username or email" required
                                       autocomplete="username"
                                       value="<?php echo htmlspecialchars($submitted_username_or_email); ?>">
                            </div>
                            <div class="input-hint" style="margin-top: 0.5rem; font-size: 0.85rem; color: #64748b;">
                                You can use either your username or email address
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="login_password" class="form-label">Password:</label>
                            <div class="input-group">
                                <span class="input-group-icon">🔑</span>
                                <input type="password" id="login_password" name="password"
                                       class="form-control"
                                       autocomplete="current-password"
                                       placeholder="Enter your password" required>
                                <button type="button" class="password-toggle" onclick="togglePassword('login_password')">
                                    <span class="toggle-icon" id="login_password_toggle">👁️</span>
                                </button>
                            </div>
                        </div>

                        <div class="form-group form-options">
                            <div class="form-check">
                                <input type="checkbox" id="login_remember_me" name="remember_me" value="1" class="form-check-input"
                                       <?php echo $submitted_remember_me ? 'checked' : ''; ?>>
                                <label for="login_remember_me" class="form-check-label">
                                    <span class="checkbox-text">Remember me for 30 days</span>
                                </label>
                            </div>
                            <div class="forgot-password-link">
                                <a href="/index.php?page=forgot_password" style="color: #3b82f6; text-decoration: none; font-size: 0.9rem;">
                                    Forgot password?
                                </a>
                            </div>
                        </div>

                        <!-- Login Status Indicator -->
                        <div class="login-status" id="loginStatus" style="display: none; background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.3); border-radius: 0.5rem; padding: 0.75rem; margin: 1rem 0; font-size: 0.9rem; color: #93c5fd;">
                            <div style="display: flex; align-items: center;">
                                <span style="margin-right: 0.5rem;">⏳</span>
                                <span id="statusText">Verifying credentials...</span>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" id="submitBtn" class="button button-primary button-block">
                                <span class="button-icon">🚀</span>
                                <span class="button-text">Sign In</span>
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
                                    New to our platform?
                                </div>
                                <a href="/index.php?page=register" class="button button-secondary button-with-icon" style="min-width: 180px;">
                                    <span class="button-icon">✨</span>
                                    <span class="button-text">Create Account</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="auth-column-info">
                <div class="auth-info-content">
                    <h3 style="color: #10b981; margin-bottom: 1rem;">🛡️ Secure Access</h3>
                    <p style="color: #e2e8f0; margin-bottom: 1.5rem;">
                        Your account security is our priority. We use industry-standard encryption and security measures to protect your data.
                    </p>

                    <div class="security-features" style="margin: 1.5rem 0;">
                        <div class="security-feature" style="display: flex; align-items: flex-start; margin-bottom: 1rem;">
                            <span style="color: #10b981; margin-right: 0.75rem; font-size: 1.1rem;">🔐</span>
                            <div>
                                <h4 style="color: #cbd5e1; font-size: 0.95rem; margin-bottom: 0.25rem;">Encrypted Login</h4>
                                <p style="color: #94a3b8; font-size: 0.85rem; margin: 0;">All login data is encrypted in transit</p>
                            </div>
                        </div>

                        <div class="security-feature" style="display: flex; align-items: flex-start; margin-bottom: 1rem;">
                            <span style="color: #3b82f6; margin-right: 0.75rem; font-size: 1.1rem;">📱</span>
                            <div>
                                <h4 style="color: #cbd5e1; font-size: 0.95rem; margin-bottom: 0.25rem;">Session Management</h4>
                                <p style="color: #94a3b8; font-size: 0.85rem; margin: 0;">Automatic logout for security</p>
                            </div>
                        </div>

                        <div class="security-feature" style="display: flex; align-items: flex-start; margin-bottom: 1rem;">
                            <span style="color: #f59e0b; margin-right: 0.75rem; font-size: 1.1rem;">🔔</span>
                            <div>
                                <h4 style="color: #cbd5e1; font-size: 0.95rem; margin-bottom: 0.25rem;">Login Notifications</h4>
                                <p style="color: #94a3b8; font-size: 0.85rem; margin: 0;">Get notified of account access</p>
                            </div>
                        </div>
                    </div>

                    <div class="login-help" style="background: rgba(71, 85, 105, 0.3); border-radius: 0.5rem; padding: 1rem; margin-top: 1.5rem;">
                        <h4 style="color: #cbd5e1; font-size: 0.9rem; margin-bottom: 0.75rem; font-weight: 600;">💡 Login Tips</h4>
                        <ul style="margin: 0; padding-left: 1.25rem; font-size: 0.85rem; color: #94a3b8;">
                            <li style="margin-bottom: 0.5rem;">Use "Remember me" only on trusted devices</li>
                            <li style="margin-bottom: 0.5rem;">Always log out when using public computers</li>
                            <li style="margin-bottom: 0.5rem;">Contact support if you notice unusual activity</li>
                            <li>Your session will expire after 24 hours of inactivity</li>
                        </ul>
                    </div>
                </div>

                <!-- Quick Social Login Section (Enhanced) -->
                <div class="social-auth-section" style="margin-top: 2rem;">
                    <h3 class="social-auth-title" style="color: #cbd5e1; font-size: 1rem; margin-bottom: 1rem; text-align: center;">🌐 Quick Login</h3>
                    <p style="color: #94a3b8; font-size: 0.85rem; text-align: center; margin-bottom: 1rem;">
                        Continue with your preferred platform
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

<script src="/themes/default/js/auth-common.js" defer></script>
<script src="/themes/default/js/auth-login.js" defer></script>
</div>

<script src="/themes/default/js/auth-common.js" defer></script>
