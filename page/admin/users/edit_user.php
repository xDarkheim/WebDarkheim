<?php

/**
 * Edit User Page - MODERN DARK ADMIN INTERFACE
 *
 * Modern dark administrative interface for editing user details
 * with improved UX and consistent styling
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

use App\Domain\Models\User;
use App\Application\Components\AdminNavigation;

// Use global services from bootstrap.php
global $flashMessageService, $database_handler, $serviceProvider;

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Get AuthenticationService
try {
    $authService = $serviceProvider->getAuth();
} catch (Exception $e) {
    error_log("Critical: Failed to get AuthenticationService instance: " . $e->getMessage());
    die("A critical system error occurred. Please try again later.");
}

// Check authentication and admin rights
if (!$authService->isAuthenticated() || !$authService->hasRole('admin')) {
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
$available_roles = ['client', 'employee', 'admin', 'guest'];

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
    $currentUser = $authService->getCurrentUser();
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

// Generate CSRF token
if (empty($_SESSION['csrf_token_edit_user_' . $user_id])) {
    $_SESSION['csrf_token_edit_user_' . $user_id] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token_edit_user_' . $user_id];

$page_title = "Edit User";
$currentUser = $authService->getCurrentUser();
$is_editing_self = $currentUser && $user_id == $currentUser['id'];

// Get flash messages
$flashMessages = $flashMessageService->getAllMessages();

// Create unified navigation
$adminNavigation = new AdminNavigation($authService);

?>
    <!-- Admin Dark Theme Styles -->
    <link rel="stylesheet" href="/public/assets/css/admin.css">

    <!-- Navigation -->
    <?= $adminNavigation->render() ?>

    <!-- Header -->
    <header class="admin-header">
        <div class="admin-header-container">
            <div class="admin-header-content">
                <div class="admin-header-title">
                    <i class="admin-header-icon fas fa-user-edit"></i>
                    <div class="admin-header-text">
                        <h1>Edit User <?= $user_to_edit ? '#' . htmlspecialchars((string)$user_to_edit['id']) : '' ?></h1>
                        <p>
                            <?php if ($is_editing_self): ?>
                                <span style="color: var(--admin-warning);">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    You are editing your own account
                                </span>
                            <?php else: ?>
                                Modify user account details and permissions
                            <?php endif; ?>
                        </p>
                    </div>
                </div>

                <div class="admin-header-actions">
                    <a href="/index.php?page=manage_users" class="admin-btn admin-btn-secondary">
                        <i class="fas fa-arrow-left"></i>Back to Users
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Flash Messages -->
    <?php if (!empty($flashMessages)): ?>
    <div class="admin-flash-messages">
        <?php foreach ($flashMessages as $type => $messages): ?>
            <?php foreach ($messages as $message): ?>
            <div class="admin-flash-message admin-flash-<?= $type ?>">
                <i class="fas fa-<?= $type === 'error' ? 'exclamation-circle' : ($type === 'success' ? 'check-circle' : ($type === 'warning' ? 'exclamation-triangle' : 'info-circle')) ?>"></i>
                <div>
                    <?= $message['is_html'] ? $message['text'] : htmlspecialchars($message['text']) ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Main Content -->
    <main>
        <div class="admin-layout-main">
            <div class="admin-content">
                <?php if ($user_to_edit): ?>

                    <?php if ($is_editing_self): ?>
                    <!-- Security Warning for Self-Edit -->
                    <div class="admin-card admin-glow-warning">
                        <div class="admin-card-header">
                            <h3 class="admin-card-title">
                                <i class="fas fa-shield-alt"></i>Security Notice
                            </h3>
                        </div>
                        <div class="admin-card-body">
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <div style="color: var(--admin-warning); font-size: 2rem;">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                                <div>
                                    <p style="margin: 0; color: var(--admin-text-primary); font-weight: 500;">
                                        You are editing your own administrator account.
                                    </p>
                                    <p style="margin: 0.5rem 0 0 0; color: var(--admin-text-secondary); font-size: 0.875rem;">
                                        Be careful when changing your role or you may lose administrative access.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Edit User Form -->
                    <div class="admin-card">
                        <div class="admin-card-header">
                            <h3 class="admin-card-title">
                                <i class="fas fa-user-edit"></i>User Details
                            </h3>
                        </div>
                        <div class="admin-card-body">
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

                                <div class="admin-grid admin-grid-cols-1">
                                    <!-- Username Field -->
                                    <div class="admin-form-group">
                                        <label for="username" class="admin-label">
                                            <i class="fas fa-user"></i>Username
                                        </label>
                                        <input type="text"
                                               id="username"
                                               name="username"
                                               class="admin-input"
                                               value="<?= htmlspecialchars($user_to_edit['username']) ?>"
                                               required
                                               placeholder="Enter username">
                                        <div class="admin-help-text">
                                            Username must be unique and will be used for login
                                        </div>
                                    </div>

                                    <!-- Email Field -->
                                    <div class="admin-form-group">
                                        <label for="email" class="admin-label">
                                            <i class="fas fa-envelope"></i>Email Address
                                        </label>
                                        <input type="email"
                                               id="email"
                                               name="email"
                                               class="admin-input"
                                               value="<?= htmlspecialchars($user_to_edit['email']) ?>"
                                               required
                                               placeholder="Enter email address">
                                        <div class="admin-help-text">
                                            Email must be unique and will be used for notifications
                                        </div>
                                    </div>

                                    <!-- Role Field -->
                                    <div class="admin-form-group">
                                        <label for="role" class="admin-label">
                                            <i class="fas fa-shield-alt"></i>User Role
                                        </label>
                                        <select id="role" name="role" class="admin-input admin-select" required>
                                            <?php foreach ($available_roles as $role): ?>
                                                <option value="<?= htmlspecialchars($role) ?>"
                                                        <?= $user_to_edit['role'] === $role ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars(ucfirst($role)) ?>
                                                    <?php if ($role === 'admin'): ?> - Full Access<?php endif; ?>
                                                    <?php if ($role === 'employee'): ?> - Staff Access<?php endif; ?>
                                                    <?php if ($role === 'client'): ?> - Client Portal<?php endif; ?>
                                                    <?php if ($role === 'guest'): ?> - Limited Access<?php endif; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="admin-help-text">
                                            Choose the appropriate role for this user's access level
                                        </div>
                                    </div>
                                </div>

                                <!-- Form Actions -->
                                <div class="admin-card-footer">
                                    <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                                        <a href="/index.php?page=manage_users" class="admin-btn admin-btn-secondary">
                                            <i class="fas fa-times"></i>Cancel
                                        </a>
                                        <button type="submit" class="admin-btn admin-btn-success">
                                            <i class="fas fa-save"></i>Update User
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- User Information Card -->
                    <div class="admin-card">
                        <div class="admin-card-header">
                            <h3 class="admin-card-title">
                                <i class="fas fa-info-circle"></i>User Information
                            </h3>
                        </div>
                        <div class="admin-card-body">
                            <div class="admin-grid admin-grid-cols-2">
                                <div>
                                    <div style="display: flex; align-items: center; margin-bottom: 1rem;">
                                        <div style="width: 48px; height: 48px; background: var(--admin-primary-bg); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 1rem;">
                                            <i class="fas fa-user" style="color: var(--admin-primary); font-size: 1.25rem;"></i>
                                        </div>
                                        <div>
                                            <div style="font-weight: 600; color: var(--admin-text-primary);">
                                                <?= htmlspecialchars($user_to_edit['username']) ?>
                                            </div>
                                            <div style="color: var(--admin-text-secondary); font-size: 0.875rem;">
                                                User ID: #<?= htmlspecialchars((string)$user_to_edit['id']) ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <div style="margin-bottom: 1rem;">
                                        <div style="color: var(--admin-text-muted); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 0.25rem;">
                                            Current Role
                                        </div>
                                        <span class="admin-badge admin-badge-<?= $user_to_edit['role'] === 'admin' ? 'warning' : 'gray' ?>">
                                            <i class="fas fa-<?= $user_to_edit['role'] === 'admin' ? 'crown' : 'user' ?>"></i>
                                            <?= htmlspecialchars(ucfirst($user_to_edit['role'])) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                <?php else: ?>
                    <!-- Error State -->
                    <div class="admin-card admin-glow-error">
                        <div class="admin-card-body" style="text-align: center; padding: 3rem;">
                            <div style="font-size: 4rem; color: var(--admin-error); margin-bottom: 1rem;">
                                <i class="fas fa-user-times"></i>
                            </div>
                            <h3 style="color: var(--admin-text-primary); margin-bottom: 1rem;">User Not Found</h3>
                            <p style="color: var(--admin-text-muted); margin-bottom: 2rem;">
                                The user you're trying to edit could not be found or has been deleted.
                            </p>
                            <a href="/index.php?page=manage_users" class="admin-btn admin-btn-primary admin-btn-lg">
                                <i class="fas fa-arrow-left"></i>Back to Users
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <aside class="admin-sidebar">
                <!-- User Statistics -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="fas fa-info-circle"></i>Edit Guidelines
                        </h3>
                    </div>
                    <div class="admin-card-body">
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <div>
                                <h4 style="display: flex; align-items: center; margin: 0 0 0.5rem 0; font-size: 0.875rem; font-weight: 600;">
                                    <i class="fas fa-user-shield" style="color: var(--admin-warning); margin-right: 0.5rem;"></i>Security
                                </h4>
                                <p style="font-size: 0.875rem; color: var(--admin-text-secondary); margin: 0;">Be careful when changing user roles and permissions.</p>
                            </div>
                            <div>
                                <h4 style="display: flex; align-items: center; margin: 0 0 0.5rem 0; font-size: 0.875rem; font-weight: 600;">
                                    <i class="fas fa-envelope" style="color: var(--admin-primary); margin-right: 0.5rem;"></i>Email
                                </h4>
                                <p style="font-size: 0.875rem; color: var(--admin-text-secondary); margin: 0;">Email addresses must be unique in the system.</p>
                            </div>
                            <div>
                                <h4 style="display: flex; align-items: center; margin: 0 0 0.5rem 0; font-size: 0.875rem; font-weight: 600;">
                                    <i class="fas fa-user" style="color: var(--admin-success); margin-right: 0.5rem;"></i>Username
                                </h4>
                                <p style="font-size: 0.875rem; color: var(--admin-text-secondary); margin: 0;">Usernames are used for login and must be unique.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Role Information -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="fas fa-users-cog"></i>Role Permissions
                        </h3>
                    </div>
                    <div class="admin-card-body">
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <div>
                                <h4 style="display: flex; align-items: center; margin: 0 0 0.5rem 0; font-size: 0.875rem; font-weight: 600;">
                                    <i class="fas fa-crown" style="color: var(--admin-warning); margin-right: 0.5rem;"></i>Admin
                                </h4>
                                <p style="font-size: 0.875rem; color: var(--admin-text-secondary); margin: 0;">Full system access, can manage all users and content.</p>
                            </div>
                            <div>
                                <h4 style="display: flex; align-items: center; margin: 0 0 0.5rem 0; font-size: 0.875rem; font-weight: 600;">
                                    <i class="fas fa-user-tie" style="color: var(--admin-primary); margin-right: 0.5rem;"></i>Employee
                                </h4>
                                <p style="font-size: 0.875rem; color: var(--admin-text-secondary); margin: 0;">Staff access, can create and manage content.</p>
                            </div>
                            <div>
                                <h4 style="display: flex; align-items: center; margin: 0 0 0.5rem 0; font-size: 0.875rem; font-weight: 600;">
                                    <i class="fas fa-user" style="color: var(--admin-success); margin-right: 0.5rem;"></i>Client
                                </h4>
                                <p style="font-size: 0.875rem; color: var(--admin-text-secondary); margin: 0;">Client portal access, can manage their profile and projects.</p>
                            </div>
                            <div>
                                <h4 style="display: flex; align-items: center; margin: 0 0 0.5rem 0; font-size: 0.875rem; font-weight: 600;">
                                    <i class="fas fa-user-slash" style="color: var(--admin-text-muted); margin-right: 0.5rem;"></i>Guest
                                </h4>
                                <p style="font-size: 0.875rem; color: var(--admin-text-secondary); margin: 0;">Limited access, basic browsing only.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="fas fa-bolt"></i>Quick Actions
                        </h3>
                    </div>
                    <div class="admin-card-body">
                        <a href="/index.php?page=manage_users" class="admin-btn admin-btn-primary" style="width: 100%; margin-bottom: 0.5rem; justify-content: flex-start;">
                            <i class="fas fa-users"></i>
                            All Users
                        </a>
                        <a href="/index.php?page=register" class="admin-btn admin-btn-secondary" style="width: 100%; margin-bottom: 0.5rem; justify-content: flex-start;">
                            <i class="fas fa-user-plus"></i>
                            Add New User
                        </a>
                        <a href="/index.php?page=dashboard" class="admin-btn admin-btn-secondary" style="width: 100%; justify-content: flex-start;">
                            <i class="fas fa-tachometer-alt"></i>
                            Dashboard
                        </a>
                    </div>
                </div>
            </aside>
        </div>
    </main>

    <!-- Admin Scripts -->
    <script type="module" src="/public/assets/js/admin.js"></script>

