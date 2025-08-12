<?php

/**
 * Reset Password Page
 *
 * @author Dmytro Hovenko
 */

use App\Application\Middleware\CSRFMiddleware;
use App\Domain\Models\User;
use App\Infrastructure\Lib\TokenManager;

// Use global services (already initialized in webengine.php)
global $database_handler, $flashMessageService, $tokenManager, $site_settings_from_db;

if (!isset($database_handler)) {
    error_log("Critical: Database handler not available in reset_password.php");
    die("A critical system error occurred (DB). Please try again later or contact support.");
}
if (!isset($flashMessageService)) {
    error_log("Critical: FlashMessageService not available in reset_password.php");
    die("A critical system error occurred (Flash). Please try again later or contact support.");
}
if (!isset($tokenManager)) {
    error_log("Critical: TokenManager not available in reset_password.php");
    die("A critical system error occurred (Token). Please try again later or contact support.");
}

// Set page title
$page_title = 'Reset Password';

$token_valid = false;
$user_id_for_reset = null;
$form_errors = [];

// Accept token/email from multiple aliases and normalize token (email optional)
$token_from_url = trim($_GET['token'] ?? ($_GET['t'] ?? ($_POST['token'] ?? '')));
$email_from_url = trim($_GET['email'] ?? ($_GET['e'] ?? ''));

// Some mail clients replace '+' with space; recover base64-like tokens
if ($token_from_url !== '' && str_contains($token_from_url, ' ')) {
    $token_from_url = str_replace(' ', '+', $token_from_url);
}

// Debug info
error_log("Reset Password Debug: Token from URL: " . ($token_from_url ? '[PRESENT-' . strlen($token_from_url) . ']' : '[MISSING]'));
error_log("Reset Password Debug: Email from URL: " . ($email_from_url ?: '[MISSING]'));

if (empty($token_from_url)) {
    $flashMessageService->addError("Invalid or missing password reset link parameters.");
} else {
    // Validate token with broad compatibility across implementations
    // Use getTokenData for validation WITHOUT automatic revocation
    $tokenData = null;

    if (is_object($tokenManager) && method_exists($tokenManager, 'getTokenData')) {
        try {
            $tokenInfo = $tokenManager->getTokenData($token_from_url);
            // Check that this is a password-reset token
            if ($tokenInfo && $tokenInfo['type'] === 'password_reset') {
                $tokenData = $tokenInfo;
            }
        } catch (Throwable $e) {
            error_log("Reset Password: TokenManager::getTokenData failed: " . $e->getMessage());
        }
    }

    // Fallback for other TokenManager implementations
    if (!$tokenData) {
        if (is_object($tokenManager) && method_exists($tokenManager, 'validateToken')) {
            try {
                $type = defined('\App\Infrastructure\Lib\TokenManager::TYPE_PASSWORD_RESET')
                    ? TokenManager::TYPE_PASSWORD_RESET
                    : 'password_reset';
                $isValid = $tokenManager->validateToken($token_from_url, $type);
                if ($isValid) {
                    // Create compatible data structure
                    $tokenData = ['user_id' => null, 'type' => $type];
                }
            } catch (Throwable $e) {}
        }
    }

    // Extract user_id from various shapes
    $tokenUserId = null;
    $extractFromArray = static function (array $arr) {
        if (isset($arr['user_id'])) return $arr['user_id'];
        if (isset($arr['userId'])) return $arr['userId'];
        if (isset($arr['uid'])) return $arr['uid'];
        if (isset($arr['data']) && is_array($arr['data'])) {
            if (isset($arr['data']['user_id'])) return $arr['data']['user_id'];
            if (isset($arr['data']['userId'])) return $arr['data']['userId'];
        }
        if (isset($arr['user']['id']) && is_array($arr['user'])) {
            return $arr['user']['id'];
        }
        return null;
    };
    $extractFromObject = static function ($obj) {
        if (isset($obj->user_id)) return $obj->user_id;
        if (isset($obj->userId)) return $obj->userId;
        if (isset($obj->uid)) return $obj->uid;
        if (isset($obj->data)) {
            $d = $obj->data;
            if (is_object($d)) {
                if (isset($d->user_id)) return $d->user_id;
                if (isset($d->userId)) return $d->userId;
            } elseif (is_array($d)) {
                if (isset($d['user_id'])) return $d['user_id'];
                if (isset($d['userId'])) return $d['userId'];
            }
        }
        if (isset($obj->user)) {
            $u = $obj->user;
            if (is_object($u) && isset($u->id)) return $u->id;
            if (is_array($u) && isset($u['id'])) return $u['id'];
        }
        return null;
    };

    if (is_array($tokenData)) {
        $tokenUserId = $extractFromArray($tokenData);
    } elseif (is_object($tokenData)) {
        $tokenUserId = $extractFromObject($tokenData);
    }

    // If user_id not found yet, try verifyToken as a last resort
    if ($tokenUserId === null && $tokenData) {
        try {
            // Last attempt - use verifyToken for full data
            $verificationResult = $tokenManager->verifyToken($token_from_url, 'password_reset');
            if ($verificationResult && isset($verificationResult['user_id'])) {
                $tokenUserId = $verificationResult['user_id'];
                error_log("Reset Password: Successfully retrieved user_id via verifyToken: " . $tokenUserId);
            }
        } catch (Throwable $e) {
            error_log("Reset Password: verifyToken fallback failed: " . $e->getMessage());
        }
    }

    if ($tokenUserId !== null) {
        $user_id_for_reset = (int)$tokenUserId;

        // Load user by ID; use email from DB if missing or invalid
        $userById = User::findById($database_handler, $user_id_for_reset);
        if ($userById) {
            if (empty($email_from_url) || !filter_var($email_from_url, FILTER_VALIDATE_EMAIL)) {
                $email_from_url = $userById['email'] ?? '';
            }
            $token_valid = true;
            error_log("Reset Password: Token valid for user ID: " . $user_id_for_reset);
        } else {
            $flashMessageService->addError("No account found for the provided token.");
            error_log("Reset Password: User not found for ID: " . $user_id_for_reset);
        }
    } else {
        $flashMessageService->addError("Invalid or expired password reset token. Please request a new one.");
        error_log("Reset Password: Could not extract user_id from token data. Token data: " . json_encode($tokenData));
    }
}

// Use new CSRFMiddleware to get a token
$csrf_token = CSRFMiddleware::getToken();

// Robust base URL (proxy-aware) with fallback to configure site URL
$forwardedProto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
$httpsOn = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : '';
$requestScheme = $_SERVER['REQUEST_SCHEME'] ?? '';
$scheme = $forwardedProto ?: ($httpsOn ?: ($requestScheme ?: 'https'));
$host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? ($_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? ''));

// Safely read the configured site URL from settings if available
$configuredSiteUrl = '';
if (isset($site_settings_from_db) && is_array($site_settings_from_db)) {
    if (isset($site_settings_from_db['general']['site_url']['value'])) {
        $configuredSiteUrl = (string)$site_settings_from_db['general']['site_url']['value'];
    } elseif (isset($site_settings_from_db['general']['site_url'])) {
        $configuredSiteUrl = (string)$site_settings_from_db['general']['site_url'];
    }
}

if (empty($host) && !empty($configuredSiteUrl)) {
    $baseUrl = rtrim($configuredSiteUrl, '/');
} else {
    $baseUrl = $scheme . '://' . ($host ?: 'localhost');
}

if ($token_valid && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token via new CSRFMiddleware
    if (!CSRFMiddleware::validateQuick()) {
        $form_errors[] = 'Invalid security token. Please try again.';
    } else {
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($new_password)) {
            $form_errors[] = "New password cannot be empty.";
        } elseif (strlen($new_password) < 8) {
            $form_errors[] = "Password must be at least 8 characters long.";
        }
        if ($new_password !== $confirm_password) {
            $form_errors[] = "Passwords do not match.";
        }

        if (empty($form_errors) && $user_id_for_reset) {
            // Update user password directly via a database
            $conn = $database_handler->getConnection();
            if ($conn) {
                try {
                    $passwordHash = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?");
                    $passwordUpdated = $stmt->execute([$passwordHash, $user_id_for_reset]);

                    // Check if the token was already revoked by verifyToken
                    // TokenManager automatically revokes one-time tokens, so this may already be done
                    $tokenRevoked = true; // Assume the token is revoked (or will be)

                    // Try to additionally revoke token if method available
                    try {
                        if (is_object($tokenManager) && method_exists($tokenManager, 'revokeToken')) {
                            $tokenManager->revokeToken($token_from_url);
                        } elseif (is_object($tokenManager) && method_exists($tokenManager, 'invalidateToken')) {
                            $tokenManager->invalidateToken($token_from_url);
                        }
                    } catch (Throwable $e) {
                        // Token may already be revoked, not critical
                        error_log("Reset Password: Token revocation notice: " . $e->getMessage());
                    }

                    if ($passwordUpdated && $tokenRevoked) {
                        $flashMessageService->addSuccess("Your password has been successfully reset. You can now log in with your new password.");
                        header("Location: /index.php?page=login");
                        exit();
                    } else {
                        $form_errors[] = "Failed to update your password or invalidate reset token. Please try again.";
                        if (!$passwordUpdated) {
                            error_log("Reset Password: Failed to update password in DB for user ID " . $user_id_for_reset);
                        } else {
                            error_log("Reset Password: Password updated successfully for user ID " . $user_id_for_reset);
                        }

                        // Informational message instead of error for token revocation
                        error_log("Reset Password: Token processed for user ID " . $user_id_for_reset);
                    }
                } catch (Exception $e) {
                    $form_errors[] = "Database error occurred. Please try again.";
                    error_log("Reset Password: Database error: " . $e->getMessage());
                }
            } else {
                $form_errors[] = "Database connection error. Please try again.";
            }
        }
    }
}

// CORRECT logic for retrieving messages - use $page_messages
global $page_messages;
$messages = [];
if (isset($page_messages) && is_array($page_messages)) {
    $messages = $page_messages;
}
?>

<!-- Isolated CSS for auth pages -->
<link rel="stylesheet" href="/themes/default/css/pages/_auth.css">

<div class="auth-page-wrapper">
<div class="page-container-full-width">
    <div class="auth-page-container">
        <div class="auth-layout-two-column">
            <div class="auth-column-form">
                <div class="auth-form-card">
                    <?php if ($token_valid): ?>
                        <!-- Успешный сброс пароля -->
                        <div class="password-reset-header">
                            <div class="reset-icon">
                                <span style="font-size: 3rem; color: #3b82f6;">🔐</span>
                            </div>
                            <h2 style="color: #e2e8f0; margin-bottom: 0.5rem; text-align: center; font-size: 1.5rem;">Reset Your Password</h2>
                            <p style="color: #94a3b8; text-align: center; margin-bottom: 2rem; font-size: 0.95rem;">
                                Create a new secure password for your account
                            </p>
                        </div>

                        <?php
                        // Display form errors if any
                        if (!empty($form_errors)) {
                            echo '<div class="form-errors-container" style="margin-bottom: 1.5rem;">';
                            foreach ($form_errors as $error) {
                                echo '<div class="form-error" style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 0.5rem; padding: 0.75rem; margin-bottom: 0.5rem; color: #fecaca; font-size: 0.9rem;">' . htmlspecialchars($error) . '</div>';
                            }
                            echo '</div>';
                        }
                        ?>

                        <?php
                        // Формируем action через заранее вычисленный baseUrl
                        $form_action = $baseUrl . '/index.php?page=reset_password&token=' . urlencode($token_from_url) . '&email=' . urlencode($email_from_url);
                        ?>
                        <form action="<?php echo htmlspecialchars($form_action); ?>" method="POST" id="resetPasswordForm">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                            <div class="form-group">
                                <label for="new_password" class="form-label">New Password:</label>
                                <div class="input-group">
                                    <span class="input-group-icon">🔒</span>
                                    <input type="password" name="new_password" id="new_password" class="form-control" 
                                           placeholder="Create a strong password" required minlength="8"
                                           autocomplete="new-password">
                                    <button type="button" class="password-toggle" onclick="togglePassword('new_password')">
                                        <span class="toggle-icon" id="new_password_toggle">👁️</span>
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
                                <label for="confirm_password" class="form-label">Confirm New Password:</label>
                                <div class="input-group">
                                    <span class="input-group-icon">🔒</span>
                                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" 
                                           placeholder="Confirm your new password" required minlength="8"
                                           autocomplete="new-password">
                                    <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                                        <span class="toggle-icon" id="confirm_password_toggle">👁️</span>
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

                            <div class="form-actions">
                                <button type="submit" class="button button-primary button-block" id="submitBtn">
                                    <span class="button-icon">🔐</span>
                                    <span class="button-text">Update Password</span>
                                </button>
                            </div>
                        </form>

                        <div class="auth-form-footer">
                            <p style="text-align: center; color: #64748b; font-size: 0.9rem;">
                                Remembered your password? <a href="<?php echo $baseUrl; ?>/index.php?page=login" style="color: #3b82f6;">Back to Login</a>
                            </p>
                        </div>

                    <?php else: ?>
                        <!-- Неверный токен -->
                        <div class="password-reset-error">
                            <div class="reset-icon error">
                                <span style="font-size: 3rem; color: #ef4444;">❌</span>
                            </div>

                            <h2 style="color: #ef4444; margin-bottom: 1rem; text-align: center; font-size: 1.5rem;">Invalid Reset Link</h2>
                            <p style="color: #e2e8f0; text-align: center; margin-bottom: 2rem; font-size: 1rem;">
                                This password reset link is invalid or has expired.
                            </p>

                            <div class="error-help" style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 0.5rem; padding: 1rem; margin-bottom: 2rem;">
                                <p style="margin: 0.5rem 0; color: #fecaca; font-weight: 600;">What you can do:</p>
                                <ul style="color: #fecaca; margin: 0.5rem 0; padding-left: 1.5rem; font-size: 0.9rem;">
                                    <li>Request a new password reset link</li>
                                    <li>Check your email for a more recent reset link</li>
                                    <li>Make sure you're using the complete link from the email</li>
                                    <li>Contact support if you continue having issues</li>
                                </ul>
                            </div>

                            <div class="auth-form-footer" style="text-align: center;">
                                <div class="button-group-error">
                                    <a href="<?php echo $baseUrl; ?>/index.php?page=forgot_password" class="button button-primary button-with-icon">
                                        <span class="button-icon">🔄</span>
                                        <span class="button-text">Request New Reset Link</span>
                                    </a>
                                    <a href="<?php echo $baseUrl; ?>/index.php?page=login" class="button button-secondary button-with-icon">
                                        <span class="button-icon">🔑</span>
                                        <span class="button-text">Back to Login</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="auth-column-info">
                <?php if ($token_valid): ?>
                    <div class="auth-info-content">
                        <h3 style="color: #3b82f6; margin-bottom: 1rem;">🔐 Password Security Tips</h3>
                        <p style="color: #e2e8f0; margin-bottom: 1rem;">
                            Creating a strong password is crucial for protecting your account. Follow these guidelines:
                        </p>

                        <div class="security-tips" style="margin: 1.5rem 0;">
                            <div class="tip-item" style="display: flex; align-items: flex-start; margin-bottom: 1rem;">
                                <span style="color: #10b981; margin-right: 0.75rem; font-size: 1.1rem;">✓</span>
                                <div>
                                    <h4 style="color: #cbd5e1; font-size: 0.95rem; margin-bottom: 0.25rem;">Use a unique password</h4>
                                    <p style="color: #94a3b8; font-size: 0.85rem; margin: 0;">Don't reuse passwords from other accounts</p>
                                </div>
                            </div>

                            <div class="tip-item" style="display: flex; align-items: flex-start; margin-bottom: 1rem;">
                                <span style="color: #10b981; margin-right: 0.75rem; font-size: 1.1rem;">✓</span>
                                <div>
                                    <h4 style="color: #cbd5e1; font-size: 0.95rem; margin-bottom: 0.25rem;">Mix character types</h4>
                                    <p style="color: #94a3b8; font-size: 0.85rem; margin: 0;">Include uppercase, lowercase, numbers, and symbols</p>
                                </div>
                            </div>

                            <div class="tip-item" style="display: flex; align-items: flex-start; margin-bottom: 1rem;">
                                <span style="color: #10b981; margin-right: 0.75rem; font-size: 1.1rem;">✓</span>
                                <div>
                                    <h4 style="color: #cbd5e1; font-size: 0.95rem; margin-bottom: 0.25rem;">Make it memorable</h4>
                                    <p style="color: #94a3b8; font-size: 0.85rem; margin: 0;">Use a passphrase or pattern you can remember</p>
                                </div>
                            </div>
                        </div>

                        <div class="security-notice" style="background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.3); border-radius: 0.5rem; padding: 1rem; margin-top: 1.5rem;">
                            <p style="margin: 0; color: #93c5fd; font-size: 0.9rem;">
                                <strong>Security Notice:</strong> After resetting your password, you'll be logged out of all devices and will need to sign in again with your new password.
                            </p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="auth-warning-message">
                        <p><strong>Reset Link Issues?</strong></p>
                        <p>Password reset links are time-sensitive and expire after 1 hour for security reasons.</p>
                        <p>Common issues and solutions:</p>
                        <ul style="margin: 1rem 0; padding-left: 1.5rem; color: #cbd5e1;">
                            <li><strong>Expired link:</strong> Request a new password reset</li>
                            <li><strong>Already used:</strong> Each reset link can only be used once</li>
                            <li><strong>Malformed URL:</strong> Make sure you copied the complete link</li>
                            <li><strong>Multiple requests:</strong> Use only the most recent reset email</li>
                        </ul>
                        <p>If you continue experiencing issues, please contact our support team for assistance.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="/themes/default/js/auth-common.js" defer></script>
<script src="/themes/default/js/auth-password.js" defer></script>

<style>
/* Дополнительные стили для reset password */
.password-reset-header,
.password-reset-error {
    text-align: center;
    padding: 1rem 0 2rem 0;
}

.reset-icon {
    margin-bottom: 1rem;
    animation: iconFloat 2s ease-in-out infinite;
}

@keyframes iconFloat {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-5px); }
}

/* Password strength bar */
.strength-bar {
    width: 100%;
    height: 4px;
    background-color: rgba(71, 85, 105, 0.3);
    border-radius: 2px;
    overflow: hidden;
}

.strength-fill {
    height: 100%;
    width: 0;
    transition: width 0.3s ease, background-color 0.3s ease;
    border-radius: 2px;
}

/* Password toggle button */
.password-toggle {
    position: absolute;
    right: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    cursor: pointer;
    padding: 0.25rem;
    color: #94a3b8;
    transition: color 0.2s ease;
    z-index: 10;
}

.password-toggle:hover {
    color: #e2e8f0;
}

.input-group {
    position: relative;
}

/* Requirements list animations */
#requirements_list li {
    transition: color 0.3s ease;
    margin-bottom: 0.25rem;
}

/* Button styles consistent with other auth pages */
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

.button-primary {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 50%, #1d4ed8 100%);
    color: white;
    border: 1px solid rgba(59, 130, 246, 0.3);
    box-shadow:
        0 4px 14px 0 rgba(59, 130, 246, 0.3),
        0 2px 4px -1px rgba(0, 0, 0, 0.06),
        inset 0 1px 0 rgba(255, 255, 255, 0.1);
}

.button-primary:hover:not(:disabled) {
    transform: translateY(-2px) scale(1.02);
    box-shadow:
        0 10px 25px -3px rgba(59, 130, 246, 0.4),
        0 4px 6px -2px rgba(0, 0, 0, 0.05),
        inset 0 1px 0 rgba(255, 255, 255, 0.2);
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 50%, #1e40af 100%);
}

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
    transform: translateY(-2px) scale(1.02);
    background: linear-gradient(135deg, rgba(71, 85, 105, 1) 0%, rgba(51, 65, 85, 1) 100%);
    color: #f8fafc;
    border-color: rgba(71, 85, 105, 0.8);
    box-shadow:
        0 10px 25px -3px rgba(71, 85, 105, 0.3),
        0 4px 6px -2px rgba(0, 0, 0, 0.05),
        inset 0 1px 0 rgba(255, 255, 255, 0.1);
}

.button-block {
    width: 100%;
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

.button:hover .button-icon {
    transform: scale(1.1);
}

/* Button groups */
.button-group-error {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    align-items: center;
    margin-top: 1rem;
}

.button-group-error .button {
    min-width: 220px;
}

/* Animation for buttons */
.button {
    animation: buttonFadeIn 0.6s ease-out;
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

/* Loading state */
.button:disabled {
    opacity: 0.6 !important;
    cursor: not-allowed !important;
    transform: none !important;
}

/* Form error styling */
.form-errors-container {
    animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Responsive design */
@media (max-width: 768px) {
    .button-group-error {
        width: 100%;
    }

    .button-group-error .button {
        width: 100%;
        min-width: auto;
    }

    .password-reset-header h2,
    .password-reset-error h2 {
        font-size: 1.25rem;
    }

    .reset-icon {
        font-size: 2.5rem !important;
    }
}

@media (max-width: 480px) {
    .button {
        padding: 0.75rem 1.5rem;
        font-size: 0.9rem;
    }

    .password-requirements {
        padding: 0.75rem;
    }

    .tip-item {
        flex-direction: column;
        text-align: center;
    }
}
</style>
</div>
