<?php

/**
 * Edit User Page
 *
 * This page allows admins to edit user details, including username, email, and role.
 * It also provides a security warning for editing the current user's account.
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

use App\Domain\Models\User;

// Use global services from bootstrap.php
global $flashMessageService, $database_handler, $auth;

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check authentication and admin rights
if (!$auth || !$auth->isAuthenticated() || !$auth->hasRole('admin')) {
    if (isset($flashMessageService)) {
        $flashMessageService->addError("Access Denied. You do not have permission to view this page.");
    }
    header('Location: /index.php?page=login');
    exit();
}

// Check required services
if (!isset($flashMessageService)) {
    error_log("Critical: FlashMessageService not available in edit_user.php");
    die("A critical system error occurred. Please try again later.");
}

if (!isset($database_handler)) {
    error_log("Critical: Database handler not available in edit_user.php");
    $flashMessageService->addError("Database connection error. Please try again later.");
    header('Location: /index.php?page=manage_users');
    exit();
}

$user_to_edit = null;
$user_id = null;
$available_roles = ['user', 'admin'];

if (isset($_GET['id'])) {
    $user_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if ($user_id === false || $user_id <= 0) {
        $flashMessageService->addError("Invalid User ID provided.");
        header('Location: /index.php?page=manage_users');
        exit();
    }
} else {
    $flashMessageService->addError("No User ID provided.");
    header('Location: /index.php?page=manage_users');
    exit();
}

// Handle POST request (Update User)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token_edit_user_' . $user_id] ?? '', $_POST['csrf_token'])) {
        $flashMessageService->addError("Invalid security token. Action aborted.");
        header('Location: /index.php?page=edit_user&id=' . $user_id);
        exit();
    }

    $updated_username = trim($_POST['username'] ?? '');
    $updated_email = trim($_POST['email'] ?? '');
    $updated_role = $_POST['role'] ?? '';

    // Validation
    $validation_passed = true;

    if (empty($updated_username)) {
        $flashMessageService->addError("Username cannot be empty.");
        $validation_passed = false;
    }
    if (empty($updated_email)) {
        $flashMessageService->addError("Email cannot be empty.");
        $validation_passed = false;
    } elseif (!filter_var($updated_email, FILTER_VALIDATE_EMAIL)) {
        $flashMessageService->addError("Invalid email format.");
        $validation_passed = false;
    }
    if (empty($updated_role) || !in_array($updated_role, $available_roles)) {
        $flashMessageService->addError("Invalid role selected.");
        $validation_passed = false;
    }

    // Prevent admin from removing their own admin privileges
    $currentUser = $auth->getCurrentUser();
    if ($currentUser && $user_id == $currentUser['id'] && $currentUser['role'] === 'admin' && $updated_role !== 'admin') {
        $flashMessageService->addError("You cannot remove your own administrator privileges.");
        $validation_passed = false;
    }

    if ($validation_passed) {
        try {
            // Check if another user already takes a username or email
            if (User::existsByUsernameOrEmailExcludingId($database_handler, $updated_username, $updated_email, $user_id)) {
                $flashMessageService->addError("Username or Email already taken by another user.");
            } else {
                // Update user using model
                $success = User::updateById($database_handler, $user_id, $updated_username, $updated_email, $updated_role);

                if ($success) {
                    $flashMessageService->addSuccess("User (ID: $user_id) updated successfully.");
                    header('Location: /index.php?page=manage_users');
                    exit();
                } else {
                    $flashMessageService->addError("Failed to update user. Please try again.");
                    error_log("Edit User Page - Failed to update user ID: $user_id");
                }
            }
        } catch (Exception $e) {
            $flashMessageService->addError("Database error while updating user: " . $e->getMessage());
            error_log("Edit User Page - Exception updating user ID $user_id: " . $e->getMessage());
        }
    }

    // Repopulate form with submitted data on error
    $user_to_edit = [
        'id' => $user_id,
        'username' => $updated_username ?? '',
        'email' => $updated_email ?? '',
        'role' => $updated_role ?? ''
    ];
}

// Fetch user data for editing if not already set by POST error
if ($user_id && !$user_to_edit) {
    try {
        $user = User::findById($database_handler, $user_id);

        if ($user) {
            $user_to_edit = [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'role' => $user['role']
            ];
        } else {
            $flashMessageService->addError("User not found.");
            header('Location: /index.php?page=manage_users');
            exit();
        }
    } catch (Exception $e) {
        $flashMessageService->addError("Database error fetching user details: " . $e->getMessage());
        error_log("Edit User Page - Exception fetching user ID $user_id: " . $e->getMessage());
        header('Location: /index.php?page=manage_users');
        exit();
    }
}

// Generate CSRF token for the form
$csrf_token_key = 'csrf_token_edit_user_' . $user_id;
if (empty($_SESSION[$csrf_token_key])) {
    $_SESSION[$csrf_token_key] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION[$csrf_token_key];

$page_title = "Edit User";

?>

<div class="admin-page-wrapper">
<div class="admin-layout">
    <!-- Enhanced Main Header Section -->
    <header class="page-header">
        <div class="page-header-content">
            <div class="page-header-main">
                <h1 class="page-title">
                    <i class="fas fa-user-edit"></i>
                    <?php echo htmlspecialchars($page_title); ?>
                    <?php if ($user_to_edit): ?>
                        <span class="user-id-badge">#<?php echo $user_to_edit['id']; ?></span>
                    <?php endif; ?>
                </h1>
                <div class="page-header-description">
                    <p>Modify user details, permissions, and account settings</p>
                </div>
            </div>
            <div class="page-header-actions">
                <a href="/index.php?page=manage_users" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Users
                </a>
                <?php if ($user_to_edit): ?>
                    <a href="/index.php?page=user_profile&id=<?php echo $user_to_edit['id']; ?>" class="btn btn-outline">
                        <i class="fas fa-eye"></i> View Profile
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Flash Messages -->
    <?php
    $flashMessages = $flashMessageService->getMessages();
    if (!empty($flashMessages)):
    ?>
        <div class="flash-messages-container">
            <?php foreach ($flashMessages as $type => $messages): ?>
                <?php foreach ($messages as $message): ?>
                    <div class="message message--<?php echo htmlspecialchars($type); ?>">
                        <p><?php echo $message['is_html'] ? $message['text'] : htmlspecialchars($message['text']); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Main Content Layout -->
    <div class="content-layout">
        <!-- Primary Content Area -->
        <main class="main-content" style="max-width: 800px;">
            <?php if ($user_to_edit): ?>
                <!-- User Edit Form -->
                <div class="form-wrapper">
                    <div class="card card-primary">
                        <div class="card-header">
                            <div class="card-header-content">
                                <h2 class="card-title">
                                    <i class="fas fa-user-cog"></i> User Details
                                </h2>
                                <div class="card-header-meta">
                                    <div class="user-info">
                                        <small class="user-id">
                                            <i class="fas fa-hashtag"></i>
                                            User ID: <?php echo htmlspecialchars((string)$user_to_edit['id']); ?>
                                        </small>
                                        <small class="current-role">
                                            <i class="fas fa-user-tag"></i>
                                            Current Role: <?php echo htmlspecialchars(ucfirst($user_to_edit['role'] ?? 'Unknown')); ?>
                                        </small>
                                    </div>
                                    <div class="user-status">
                                        <span class="status-badge status-editing">
                                            <i class="fas fa-edit"></i>
                                            Editing
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="card-header-actions">
                                <button type="button" class="btn-icon btn-toggle-help" onclick="toggleHelp()" title="Toggle Help">
                                    <i class="fas fa-question-circle"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <form action="/index.php?page=edit_user&id=<?php echo htmlspecialchars((string)$user_id); ?>" method="POST" class="user-edit-form">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                                <!-- Basic Information Section -->
                                <div class="form-section" data-section="1">
                                    <div class="section-header">
                                        <h3 class="section-title">
                                            <i class="fas fa-user"></i> Basic Information
                                        </h3>
                                        <p class="section-description">Update the user's basic account information</p>
                                    </div>
                                    <div class="form-grid">
                                        <div class="form-group form-group-full">
                                            <label for="username" class="form-label form-label-prominent">
                                                Username <span class="required-indicator">*</span>
                                            </label>
                                            <div class="input-wrapper">
                                                <input type="text"
                                                       id="username"
                                                       name="username"
                                                       class="form-control form-control-prominent"
                                                       value="<?php echo htmlspecialchars($user_to_edit['username'] ?? ''); ?>"
                                                       placeholder="Enter username"
                                                       required>
                                                <div class="input-icon">
                                                    <i class="fas fa-user"></i>
                                                </div>
                                            </div>
                                            <div class="form-help-text">
                                                <i class="fas fa-info-circle"></i>
                                                Username must be unique and will be used for login
                                            </div>
                                        </div>

                                        <div class="form-group form-group-full">
                                            <label for="email" class="form-label form-label-prominent">
                                                Email Address <span class="required-indicator">*</span>
                                            </label>
                                            <div class="input-wrapper">
                                                <input type="email"
                                                       id="email"
                                                       name="email"
                                                       class="form-control form-control-prominent"
                                                       value="<?php echo htmlspecialchars($user_to_edit['email'] ?? ''); ?>"
                                                       placeholder="Enter email address"
                                                       required>
                                                <div class="input-icon">
                                                    <i class="fas fa-envelope"></i>
                                                </div>
                                            </div>
                                            <div class="form-help-text">
                                                <i class="fas fa-info-circle"></i>
                                                Email address must be unique and valid
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Role & Permissions Section -->
                                <div class="form-section" data-section="2">
                                    <div class="section-header">
                                        <h3 class="section-title">
                                            <i class="fas fa-shield-alt"></i> Role & Permissions
                                        </h3>
                                        <p class="section-description">Configure user role and access permissions</p>
                                    </div>
                                    <div class="form-grid">
                                        <div class="form-group form-group-full">
                                            <label for="role" class="form-label form-label-prominent">
                                                User Role <span class="required-indicator">*</span>
                                            </label>
                                            <div class="select-wrapper">
                                                <select id="role" name="role" class="form-control form-control-prominent" required>
                                                    <?php foreach ($available_roles as $role_value): ?>
                                                        <option value="<?php echo htmlspecialchars($role_value); ?>"
                                                                <?php echo (isset($user_to_edit['role']) && $user_to_edit['role'] === $role_value) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars(ucfirst($role_value)); ?>
                                                            <?php if ($role_value === 'admin'): ?>
                                                                - Full System Access
                                                            <?php else: ?>
                                                                - Standard User Access
                                                            <?php endif; ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <div class="select-icon">
                                                    <i class="fas fa-chevron-down"></i>
                                                </div>
                                            </div>
                                            <div class="role-permissions-info">
                                                <div class="permission-item" data-role="user">
                                                    <i class="fas fa-user"></i>
                                                    <span><strong>User:</strong> Can create and manage own content</span>
                                                </div>
                                                <div class="permission-item" data-role="admin">
                                                    <i class="fas fa-crown"></i>
                                                    <span><strong>Admin:</strong> Full system access and user management</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Security Warning Section -->
                                <?php
                                $currentUser = $auth->getCurrentUser();
                                if ($currentUser && $user_id == $currentUser['id'] && $currentUser['role'] === 'admin'):
                                ?>
                                <div class="form-section security-warning" data-section="3">
                                    <div class="section-header">
                                        <h3 class="section-title">
                                            <i class="fas fa-exclamation-triangle"></i> Security Notice
                                        </h3>
                                        <p class="section-description">Important security information about this account</p>
                                    </div>
                                    <div class="warning-box">
                                        <div class="warning-icon">
                                            <i class="fas fa-shield-alt"></i>
                                        </div>
                                        <div class="warning-content">
                                            <h4>You are editing your own administrator account</h4>
                                            <p>Be careful when changing your role or permissions. Removing administrator privileges from your own account could lock you out of the system.</p>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <!-- Form Actions -->
                                <div class="form-actions-redesigned">
                                    <div class="form-actions-container">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i>
                                            <span>Save Changes</span>
                                        </button>

                                        <button type="button" class="btn btn-secondary" onclick="window.history.back()">
                                            <i class="fas fa-arrow-left"></i>
                                            <span>Back</span>
                                        </button>

                                        <a href="/index.php?page=manage_users" class="btn btn-cancel">
                                            <i class="fas fa-times"></i>
                                            <span>Cancel</span>
                                        </a>
                                    </div>

                                    <div class="form-actions-help">
                                        <div class="keyboard-shortcuts">
                                            <small>
                                                <i class="fas fa-keyboard"></i>
                                                <strong>Shortcuts:</strong>
                                                <kbd>Ctrl+Enter</kbd> Save changes
                                                <kbd>Esc</kbd> Cancel editing
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Error State -->
                <div class="card card-primary">
                    <div class="card-body">
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <h3 class="empty-state-title">User Not Found</h3>
                            <p class="empty-state-description">
                                The requested user could not be loaded. Please check the user ID and try again.
                            </p>
                            <a href="/index.php?page=manage_users" class="btn btn-primary">
                                <i class="fas fa-arrow-left"></i> Back to User List
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>

        <!-- Enhanced Compact Sidebar -->
        <aside class="sidebar-content" style="min-width: 280px; max-width: 320px;">
            <!-- User Info Card -->
            <?php if ($user_to_edit): ?>
            <div class="card card-compact sidebar-card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-user"></i> User Information
                    </h3>
                </div>
                <div class="card-body">
                    <div class="user-profile-info">
                        <div class="user-profile-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="user-profile-details">
                            <h4><?php echo htmlspecialchars($user_to_edit['username']); ?></h4>
                            <p><?php echo htmlspecialchars($user_to_edit['email']); ?></p>
                            <span class="role-badge role-<?php echo htmlspecialchars($user_to_edit['role']); ?>">
                                <i class="fas fa-<?php echo $user_to_edit['role'] === 'admin' ? 'crown' : 'user'; ?>"></i>
                                <?php echo ucfirst($user_to_edit['role']); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Quick Actions Card -->
            <div class="card card-compact sidebar-card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-bolt"></i> Quick Actions
                    </h3>
                </div>
                <div class="card-body">
                    <nav class="quick-nav">
                        <a href="/index.php?page=manage_users" class="quick-nav-link">
                            <i class="fas fa-users"></i>
                            <span>All Users</span>
                        </a>
                        <a href="/index.php?page=register" class="quick-nav-link">
                            <i class="fas fa-user-plus"></i>
                            <span>Add New User</span>
                        </a>
                        <a href="/index.php?page=site_settings" class="quick-nav-link">
                            <i class="fas fa-cog"></i>
                            <span>Site Settings</span>
                        </a>
                        <a href="/index.php?page=dashboard" class="quick-nav-link">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </nav>
                </div>
            </div>

            </div>
</div>
</div>

<!-- JavaScript for user editing -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Role selection change handler
    const roleSelect = document.getElementById('role');
    if (roleSelect) {
        roleSelect.addEventListener('change', function() {
            const selectedRole = this.value;
            const permissionItems = document.querySelectorAll('.permission-item');

            permissionItems.forEach(item => {
                if (item.getAttribute('data-role') === selectedRole) {
                    item.style.fontWeight = 'bold';
                    item.style.color = 'var(--primary-color)';
                } else {
                    item.style.fontWeight = 'normal';
                    item.style.color = 'var(--text-muted)';
                }
            });
        });

        // Trigger initial state
        roleSelect.dispatchEvent(new Event('change'));
    }

    // Form submission with Ctrl+Enter
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.key === 'Enter') {
            e.preventDefault();
            const form = document.querySelector('.user-edit-form');
            if (form) {
                form.submit();
            }
        }
    });

    // Cancel with an Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            e.preventDefault();
            window.history.back();
        }
    });

    // Auto-dismiss flash messages
    setTimeout(function() {
        const messages = document.querySelectorAll('.message');
        messages.forEach(function(message) {
            message.style.opacity = '0';
            setTimeout(function() {
                message.remove();
            }, 300);
        });
    }, 5000);
});

function toggleHelp() {
    console.log('Help toggle clicked');
}
</script>
