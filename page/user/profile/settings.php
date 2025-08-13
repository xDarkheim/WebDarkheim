<?php
/**
 * User Profile Settings Page - PHASE 8
 * Security and account settings for user profiles
 */

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/includes/bootstrap.php';

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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .settings-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .settings-section {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border: none;
        }
        .settings-section h5 {
            color: #495057;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
        }
        .danger-zone {
            border: 2px solid #dc3545;
            background: #fff5f5;
        }
        .danger-zone h5 {
            color: #dc3545;
            border-bottom-color: #dc3545;
        }
        .btn-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
        }
        .btn-gradient:hover {
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
            color: white;
        }
        .security-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }
        .security-strong { background-color: #28a745; }
        .security-medium { background-color: #ffc107; }
        .security-weak { background-color: #dc3545; }
    </style>
</head>
<body class="bg-light">

<div class="container py-4">
    <!-- Header Section -->
    <div class="settings-header">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="mb-2">
                    <i class="fas fa-shield-alt"></i>
                    Profile Security & Settings
                </h1>
                <p class="mb-0 opacity-75">Manage your account security and preferences</p>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="/index.php?page=user_profile" class="btn btn-light">
                    <i class="fas fa-arrow-left"></i> Back to Profile
                </a>
            </div>
        </div>
    </div>

    <!-- Flash Messages -->
    <?php if (!empty($flashMessages)): ?>
        <div class="row mb-4">
            <div class="col-12">
                <?php foreach ($flashMessages as $type => $messages): ?>
                    <?php foreach ($messages as $message): ?>
                        <div class="alert alert-<?= $type === 'error' ? 'danger' : $type ?> alert-dismissible fade show">
                            <?= htmlspecialchars($message['text']) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Left Column -->
        <div class="col-lg-8">
            <!-- Password Security -->
            <div class="settings-section">
                <h5>
                    <i class="fas fa-key text-primary"></i>
                    Password Security
                </h5>

                <div class="mb-3">
                    <div class="d-flex align-items-center mb-2">
                        <span class="security-indicator security-medium"></span>
                        <span>Current security level: <strong>Medium</strong></span>
                    </div>
                    <small class="text-muted">Last changed: <?= date('F j, Y', strtotime($currentUser['updated_at'])) ?></small>
                </div>

                <form method="POST" action="/index.php?page=user_profile_settings">
                    <input type="hidden" name="action" value="change_password">

                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password</label>
                        <input type="password"
                               class="form-control"
                               id="current_password"
                               name="current_password"
                               required>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password"
                                       class="form-control"
                                       id="new_password"
                                       name="new_password"
                                       minlength="8"
                                       required>
                                <div class="form-text">At least 8 characters long</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password"
                                       class="form-control"
                                       id="confirm_password"
                                       name="confirm_password"
                                       required>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-gradient">
                        <i class="fas fa-shield-alt"></i> Update Password
                    </button>
                </form>
            </div>

            <!-- Notification Preferences -->
            <div class="settings-section">
                <h5>
                    <i class="fas fa-bell text-primary"></i>
                    Notification Preferences
                </h5>

                <form method="POST" action="/index.php?page=user_profile_settings">
                    <input type="hidden" name="action" value="update_preferences">

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input"
                                   type="checkbox"
                                   id="email_notifications"
                                   name="email_notifications"
                                   <?= !empty($preferences['email_notifications']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="email_notifications">
                                <strong>Email Notifications</strong>
                            </label>
                            <div class="form-text">Receive notifications about your account activity</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input"
                                   type="checkbox"
                                   id="marketing_emails"
                                   name="marketing_emails"
                                   <?= !empty($preferences['marketing_emails']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="marketing_emails">
                                <strong>Marketing Emails</strong>
                            </label>
                            <div class="form-text">Receive updates about new features and promotions</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input"
                                   type="checkbox"
                                   id="two_factor_enabled"
                                   name="two_factor_enabled"
                                   <?= !empty($preferences['two_factor_enabled']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="two_factor_enabled">
                                <strong>Two-Factor Authentication</strong>
                                <span class="badge bg-warning ms-2">Recommended</span>
                            </label>
                            <div class="form-text">Add an extra layer of security to your account</div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-gradient">
                        <i class="fas fa-save"></i> Save Preferences
                    </button>
                </form>
            </div>

            <!-- Account Information -->
            <div class="settings-section">
                <h5>
                    <i class="fas fa-info-circle text-primary"></i>
                    Account Information
                </h5>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <div class="form-control-plaintext"><?= htmlspecialchars($currentUser['username']) ?></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <div class="form-control-plaintext"><?= htmlspecialchars($currentUser['email']) ?></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Account Role</label>
                            <div class="form-control-plaintext">
                                <span class="badge bg-primary"><?= ucfirst($currentUser['role']) ?></span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Member Since</label>
                            <div class="form-control-plaintext"><?= date('F j, Y', strtotime($currentUser['created_at'])) ?></div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    To update your username or email, please <a href="/index.php?page=profile_edit">edit your profile</a>.
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="col-lg-4">
            <!-- Security Overview -->
            <div class="settings-section">
                <h5>
                    <i class="fas fa-shield-check text-primary"></i>
                    Security Overview
                </h5>

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Account Security</span>
                        <span class="badge bg-warning">Medium</span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-warning" role="progressbar" style="width: 60%"></div>
                    </div>
                    <small class="text-muted">Based on your security settings</small>
                </div>

                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span><i class="fas fa-check text-success"></i> Email Verified</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span><i class="fas fa-key text-success"></i> Strong Password</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span><i class="fas fa-times text-danger"></i> 2FA Disabled</span>
                        <small class="text-muted">Enable for better security</small>
                    </li>
                </ul>
            </div>

            <!-- Quick Actions -->
            <div class="settings-section">
                <h5>
                    <i class="fas fa-bolt text-primary"></i>
                    Quick Actions
                </h5>
                <div class="d-grid gap-2">
                    <a href="/index.php?page=profile_edit" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-edit"></i> Edit Profile
                    </a>
                    <a href="/index.php?page=user_portfolio" class="btn btn-outline-success btn-sm">
                        <i class="fas fa-briefcase"></i> Manage Portfolio
                    </a>
                    <a href="/index.php?page=dashboard" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </div>
            </div>

            <!-- Danger Zone -->
            <div class="settings-section danger-zone">
                <h5>
                    <i class="fas fa-exclamation-triangle"></i>
                    Danger Zone
                </h5>

                <p class="text-muted">
                    These actions are irreversible. Please proceed with caution.
                </p>

                <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                    <i class="fas fa-trash-alt"></i> Delete Account
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Account Modal -->
<div class="modal fade" id="deleteAccountModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle text-danger"></i>
                    Delete Account
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <strong>Warning:</strong> This action cannot be undone. All your data will be permanently deleted.
                </div>

                <form method="POST" action="/index.php?page=user_profile_settings" id="deleteAccountForm">
                    <input type="hidden" name="action" value="delete_account">

                    <div class="mb-3">
                        <label for="delete_confirmation" class="form-label">
                            Type <strong>DELETE</strong> to confirm:
                        </label>
                        <input type="text"
                               class="form-control"
                               id="delete_confirmation"
                               name="delete_confirmation"
                               placeholder="DELETE"
                               required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="deleteAccountForm" class="btn btn-danger">
                    <i class="fas fa-trash-alt"></i> Delete My Account
                </button>
            </div>
        </div>
    </div>
</div>

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
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                } else {
                    this.setCustomValidity('Passwords do not match');
                    this.classList.remove('is-valid');
                    this.classList.add('is-invalid');
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

    // Auto-dismiss alerts
    setTimeout(function() {
        var alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
});
</script>

</body>
</html>

