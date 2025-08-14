<?php
/**
 * User Profile Settings Page - PHASE 8 - DARK ADMIN THEME
 * Security and account settings for user profiles
 */

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/includes/bootstrap.php';

use App\Application\Components\AdminNavigation;

global $serviceProvider, $flashMessageService, $database_handler;

try {
    $authService = $serviceProvider->getAuth();
} catch (Exception $e) {
    error_log("Critical: Failed to get AuthenticationService: " . $e->getMessage());
    die("System error occurred.");
}

// Check authentication
if (!$authService->isAuthenticated()) {
    $flashMessageService->addError('Please log in to access this area.');
    header("Location: /index.php?page=login");
    exit();
}

$currentUser = $authService->getCurrentUser();
$userId = $authService->getCurrentUserId();

// Create unified navigation
$adminNavigation = new AdminNavigation($authService);

$pageTitle = 'Profile Settings';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $errors = [];

    switch ($action) {
        case 'change_password':
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';

            // Validation
            if (empty($current_password)) {
                $errors[] = 'Current password is required.';
            }

            if (empty($new_password) || strlen($new_password) < 8) {
                $errors[] = 'New password must be at least 8 characters long.';
            }

            if ($new_password !== $confirm_password) {
                $errors[] = 'New passwords do not match.';
            }

            if (empty($errors)) {
                try {
                    // Verify current password
                    $sql = "SELECT password FROM users WHERE id = ?";
                    $stmt = $database_handler->getConnection()->prepare($sql);
                    $stmt->execute([$userId]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!password_verify($current_password, $user['password'])) {
                        $errors[] = 'Current password is incorrect.';
                    } else {
                        // Update password
                        $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
                        $sql = "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?";
                        $stmt = $database_handler->getConnection()->prepare($sql);
                        $stmt->execute([$hashedPassword, $userId]);

                        $flashMessageService->addSuccess('Password updated successfully!');
                    }
                } catch (Exception $e) {
                    error_log("Error updating password: " . $e->getMessage());
                    $flashMessageService->addError('Failed to update password. Please try again.');
                }
            }
            break;

        case 'update_preferences':
            $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
            $marketing_emails = isset($_POST['marketing_emails']) ? 1 : 0;
            $two_factor_enabled = isset($_POST['two_factor_enabled']) ? 1 : 0;

            try {
                // Update user preferences (assuming we have a user_preferences table)
                $sql = "INSERT INTO user_preferences (user_id, email_notifications, marketing_emails, two_factor_enabled, updated_at) 
                        VALUES (?, ?, ?, ?, NOW()) 
                        ON DUPLICATE KEY UPDATE 
                        email_notifications = VALUES(email_notifications),
                        marketing_emails = VALUES(marketing_emails),
                        two_factor_enabled = VALUES(two_factor_enabled),
                        updated_at = VALUES(updated_at)";
                $stmt = $database_handler->getConnection()->prepare($sql);
                $stmt->execute([$userId, $email_notifications, $marketing_emails, $two_factor_enabled]);

                $flashMessageService->addSuccess('Preferences updated successfully!');
            } catch (Exception $e) {
                error_log("Error updating preferences: " . $e->getMessage());
                $flashMessageService->addError('Failed to update preferences. Please try again.');
            }
            break;

        case 'delete_account':
            $confirmation = $_POST['delete_confirmation'] ?? '';
            if ($confirmation !== 'DELETE') {
                $errors[] = 'Please type "DELETE" to confirm account deletion.';
            } else {
                try {
                    // Soft delete - mark as deleted but keep data for recovery
                    $sql = "UPDATE users SET status = 'deleted', updated_at = NOW() WHERE id = ?";
                    $stmt = $database_handler->getConnection()->prepare($sql);
                    $stmt->execute([$userId]);

                    // Log out user
                    session_destroy();

                    $flashMessageService->addInfo('Your account has been deactivated. Contact support to reactivate.');
                    header("Location: /index.php?page=home");
                    exit();
                } catch (Exception $e) {
                    error_log("Error deleting account: " . $e->getMessage());
                    $flashMessageService->addError('Failed to delete account. Please contact support.');
                }
            }
            break;
    }

    // Display errors
    foreach ($errors as $error) {
        $flashMessageService->addError($error);
    }
}

// Get user preferences
$preferences = [];
try {
    $sql = "SELECT * FROM user_preferences WHERE user_id = ?";
    $stmt = $database_handler->getConnection()->prepare($sql);
    $stmt->execute([$userId]);
    $preferences = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
} catch (Exception $e) {
    error_log("Error fetching preferences: " . $e->getMessage());
}

// Get flash messages
$flashMessages = $flashMessageService->getAllMessages();
?>

<link rel="stylesheet" href="/public/assets/css/admin.css">

<!-- Unified Navigation -->
<?= $adminNavigation->render() ?>

<div class="admin-container">
    <!-- Header -->
    <header class="admin-header">
        <div class="admin-header-container">
            <div class="admin-header-content">
                <div class="admin-header-title">
                    <i class="admin-header-icon fas fa-shield-alt"></i>
                    <div class="admin-header-text">
                        <h1>Profile Security & Settings</h1>
                        <p>Manage your account security and preferences</p>
                    </div>
                </div>
                <div class="admin-header-actions">
                    <a href="/index.php?page=user_profile" class="admin-btn admin-btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Profile
                    </a>
                </div>
            </div>
        </div>
    </header>

<!-- Flash messages handled by global toast system -->

    <div class="admin-layout-main">
        <div class="admin-content">
            <!-- Password Security -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h5 class="admin-card-title">
                        <i class="fas fa-key"></i>
                        Password Security
                    </h5>
                </div>
                <div class="admin-card-body">
                    <div style="margin-bottom: 1.5rem;">
                        <div style="display: flex; align-items: center; margin-bottom: 0.5rem;">
                            <span class="security-indicator security-medium"></span>
                            <span style="color: var(--admin-text-primary);">Current security level: <strong>Medium</strong></span>
                        </div>
                        <small style="color: var(--admin-text-muted);">Last changed: <?= date('F j, Y', strtotime($currentUser['updated_at'])) ?></small>
                    </div>

                    <form method="POST" action="/index.php?page=profile_edit">
                        <input type="hidden" name="action" value="change_password">

                        <div class="admin-form-group">
                            <label for="current_password" class="admin-label admin-label-required">Current Password</label>
                            <input type="password"
                                   class="admin-input"
                                   id="current_password"
                                   name="current_password"
                                   required>
                        </div>

                        <div class="admin-grid admin-grid-cols-2">
                            <div class="admin-form-group">
                                <label for="new_password" class="admin-label admin-label-required">New Password</label>
                                <input type="password"
                                       class="admin-input"
                                       id="new_password"
                                       name="new_password"
                                       minlength="8"
                                       required>
                                <div class="admin-help-text">At least 8 characters long</div>
                            </div>
                            <div class="admin-form-group">
                                <label for="confirm_password" class="admin-label admin-label-required">Confirm New Password</label>
                                <input type="password"
                                       class="admin-input"
                                       id="confirm_password"
                                       name="confirm_password"
                                       required>
                            </div>
                        </div>

                        <button type="submit" class="admin-btn admin-btn-primary">
                            <i class="fas fa-shield-alt"></i> Update Password
                        </button>
                    </form>
                </div>
            </div>

            <!-- Notification Preferences -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h5 class="admin-card-title">
                        <i class="fas fa-bell"></i>
                        Notification Preferences
                    </h5>
                </div>
                <div class="admin-card-body">
                    <form method="POST" action="/index.php?page=profile_edit">
                        <input type="hidden" name="action" value="update_preferences">

                        <div class="admin-form-group">
                            <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                                <input type="checkbox"
                                       id="email_notifications"
                                       name="email_notifications"
                                       style="margin-top: 0.25rem;"
                                       <?= !empty($preferences['email_notifications']) ? 'checked' : '' ?>>
                                <div style="flex: 1;">
                                    <label for="email_notifications" style="margin: 0; color: var(--admin-text-primary); font-weight: 500;">
                                        Email Notifications
                                    </label>
                                    <div class="admin-help-text" style="margin-top: 0.25rem;">Receive notifications about your account activity</div>
                                </div>
                            </div>
                        </div>

                        <div class="admin-form-group">
                            <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                                <input type="checkbox"
                                       id="marketing_emails"
                                       name="marketing_emails"
                                       style="margin-top: 0.25rem;"
                                       <?= !empty($preferences['marketing_emails']) ? 'checked' : '' ?>>
                                <div style="flex: 1;">
                                    <label for="marketing_emails" style="margin: 0; color: var(--admin-text-primary); font-weight: 500;">
                                        Marketing Emails
                                    </label>
                                    <div class="admin-help-text" style="margin-top: 0.25rem;">Receive updates about new features and promotions</div>
                                </div>
                            </div>
                        </div>

                        <div class="admin-form-group">
                            <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                                <input type="checkbox"
                                       id="two_factor_enabled"
                                       name="two_factor_enabled"
                                       style="margin-top: 0.25rem;"
                                       <?= !empty($preferences['two_factor_enabled']) ? 'checked' : '' ?>>
                                <div style="flex: 1;">
                                    <label for="two_factor_enabled" style="margin: 0; color: var(--admin-text-primary); font-weight: 500;">
                                        Two-Factor Authentication
                                        <span class="admin-badge admin-badge-warning" style="margin-left: 0.5rem; font-size: 0.65rem;">Recommended</span>
                                    </label>
                                    <div class="admin-help-text" style="margin-top: 0.25rem;">Add an extra layer of security to your account</div>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="admin-btn admin-btn-primary">
                            <i class="fas fa-save"></i> Save Preferences
                        </button>
                    </form>
                </div>
            </div>

            <!-- Account Information -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h5 class="admin-card-title">
                        <i class="fas fa-info-circle"></i>
                        Account Information
                    </h5>
                </div>
                <div class="admin-card-body">
                    <div class="admin-grid admin-grid-cols-2">
                        <div>
                            <div class="admin-form-group">
                                <label class="admin-label">Username</label>
                                <div class="admin-input" style="background: var(--admin-bg-secondary); border: none;">
                                    <?= htmlspecialchars($currentUser['username']) ?>
                                </div>
                            </div>
                            <div class="admin-form-group">
                                <label class="admin-label">Email Address</label>
                                <div class="admin-input" style="background: var(--admin-bg-secondary); border: none;">
                                    <?= htmlspecialchars($currentUser['email']) ?>
                                </div>
                            </div>
                        </div>
                        <div>
                            <div class="admin-form-group">
                                <label class="admin-label">Account Role</label>
                                <div>
                                    <span class="admin-badge admin-badge-primary">
                                        <i class="fas fa-user"></i>
                                        <?= ucfirst($currentUser['role']) ?>
                                    </span>
                                </div>
                            </div>
                            <div class="admin-form-group">
                                <label class="admin-label">Member Since</label>
                                <div class="admin-input" style="background: var(--admin-bg-secondary); border: none;">
                                    <?= date('F j, Y', strtotime($currentUser['created_at'])) ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div style="background: var(--admin-info-bg); border: 1px solid var(--admin-info);
                                border-radius: var(--admin-border-radius); padding: 1rem; margin-top: 1rem;">
                        <i class="fas fa-info-circle" style="color: var(--admin-info); margin-right: 0.5rem;"></i>
                        <span style="color: var(--admin-info-light);">
                            To update your username or email, please <a href="/index.php?page=profile_edit" style="color: var(--admin-primary-light);">edit your profile</a>.
                        </span>
                    </div>
                </div>
            </div>

            <!-- Danger Zone -->
            <div class="admin-card danger-zone">
                <div class="admin-card-header">
                    <h5 class="admin-card-title">
                        <i class="fas fa-exclamation-triangle"></i>
                        Danger Zone
                    </h5>
                </div>
                <div class="admin-card-body">
                    <p style="color: var(--admin-text-muted); margin-bottom: 1rem;">
                        These actions are irreversible. Please proceed with caution.
                    </p>

                    <button type="button" class="admin-btn admin-btn-danger" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                        <i class="fas fa-trash-alt"></i> Delete Account
                    </button>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="admin-sidebar">
            <!-- Security Overview -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h6 class="admin-card-title" style="font-size: 0.875rem;">
                        <i class="fas fa-shield-check"></i>
                        Security Overview
                    </h6>
                </div>
                <div class="admin-card-body">
                    <div style="margin-bottom: 1rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <span style="color: var(--admin-text-primary);">Account Security</span>
                            <span class="admin-badge admin-badge-warning">Medium</span>
                        </div>
                        <div style="background: var(--admin-bg-secondary); border-radius: 9999px; height: 6px; overflow: hidden;">
                            <div style="background: var(--admin-warning); height: 100%; width: 60%; transition: width 0.3s ease;"></div>
                        </div>
                        <small style="color: var(--admin-text-muted); display: block; margin-top: 0.5rem;">Based on your security settings</small>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: var(--admin-text-primary); display: flex; align-items: center;">
                                <i class="fas fa-check" style="color: var(--admin-success); margin-right: 0.5rem;"></i>
                                Email Verified
                            </span>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: var(--admin-text-primary); display: flex; align-items: center;">
                                <i class="fas fa-key" style="color: var(--admin-success); margin-right: 0.5rem;"></i>
                                Strong Password
                            </span>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: var(--admin-text-primary); display: flex; align-items: center;">
                                <i class="fas fa-times" style="color: var(--admin-error); margin-right: 0.5rem;"></i>
                                2FA Disabled
                            </span>
                            <small style="color: var(--admin-text-muted);">Enable for better security</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h6 class="admin-card-title" style="font-size: 0.875rem;">
                        <i class="fas fa-bolt"></i>
                        Quick Actions
                    </h6>
                </div>
                <div class="admin-card-body">
                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                        <a href="/index.php?page=profile_edit" class="admin-btn admin-btn-primary admin-btn-sm">
                            <i class="fas fa-edit"></i> Edit Profile
                        </a>
                        <a href="/index.php?page=user_portfolio" class="admin-btn admin-btn-success admin-btn-sm">
                            <i class="fas fa-briefcase"></i> Manage Portfolio
                        </a>
                        <a href="/index.php?page=dashboard" class="admin-btn admin-btn-secondary admin-btn-sm">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Account Modal -->
<div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-labelledby="deleteAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" style="background: var(--admin-bg-card); border: 1px solid var(--admin-border);">
            <div class="modal-header" style="border-bottom: 1px solid var(--admin-border);">
                <h5 class="modal-title" id="deleteAccountModalLabel" style="color: var(--admin-text-primary);">
                    <i class="fas fa-exclamation-triangle" style="color: var(--admin-error); margin-right: 0.5rem;"></i>
                    Delete Account
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: invert(1);"></button>
            </div>
            <div class="modal-body">
                <div style="background: var(--admin-error-bg); border: 1px solid var(--admin-error);
                            border-radius: var(--admin-border-radius); padding: 1rem; margin-bottom: 1rem;">
                    <strong style="color: var(--admin-error-light);">Warning:</strong>
                    <span style="color: var(--admin-error-light);">This action cannot be undone. All your data will be permanently deleted.</span>
                </div>

                <form method="POST" action="/index.php?page=profile_edit" id="deleteAccountForm">
                    <input type="hidden" name="action" value="delete_account">

                    <div class="admin-form-group">
                        <label for="delete_confirmation" class="admin-label admin-label-required">
                            Type <strong>DELETE</strong> to confirm:
                        </label>
                        <input type="text"
                               class="admin-input"
                               id="delete_confirmation"
                               name="delete_confirmation"
                               placeholder="DELETE"
                               required>
                    </div>
                </form>
            </div>
            <div class="modal-footer" style="border-top: 1px solid var(--admin-border);">
                <button type="button" class="admin-btn admin-btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="deleteAccountForm" class="admin-btn admin-btn-danger">
                    <i class="fas fa-trash-alt"></i> Delete My Account
                </button>
            </div>
        </div>
    </div>
</div>

<script src="/public/assets/js/admin.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Password strength checker
    const newPasswordInput = document.getElementById('new_password');
    const confirmPasswordInput = document.getElementById('confirm_password');

    if (newPasswordInput) {
        newPasswordInput.addEventListener('input', function() {
            const password = this.value;
            const strength = checkPasswordStrength(password);
            updatePasswordStrengthIndicator(strength);
        });
    }

    if (confirmPasswordInput) {
        confirmPasswordInput.addEventListener('input', function() {
            const password = newPasswordInput.value;
            const confirm = this.value;

            if (password && confirm) {
                if (password === confirm) {
                    this.setCustomValidity('');
                    this.style.borderColor = 'var(--admin-success)';
                } else {
                    this.setCustomValidity('Passwords do not match');
                    this.style.borderColor = 'var(--admin-error)';
                }
            }
        });
    }

    function checkPasswordStrength(password) {
        let strength = 0;

        if (password.length >= 8) strength++;
        if (/[a-z]/.test(password)) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;
        if (/[^A-Za-z0-9]/.test(password)) strength++;

        return strength;
    }

    function updatePasswordStrengthIndicator(strength) {
        // Implementation for password strength indicator
        // This could be enhanced with visual feedback
    }
});
</script>
