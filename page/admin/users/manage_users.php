<?php

/**
 * Manage Users Page - MODERN DARK ADMIN INTERFACE
 *
 * Modern dark administrative interface for user management
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

// Get AuthenticationService instead of direct auth access
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
    error_log("Critical: FlashMessageService not available in manage_users.php");
    die("A critical system error occurred. Please try again later.");
}

if (!isset($database_handler)) {
    error_log("Critical: Database handler not available in manage_users.php");
    $flashMessageService->addError("Database connection error. Please try again later.");
    header('Location: /index.php?page=dashboard');
    exit();
}

$page_title = "User Management";

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token_manage_users'] ?? '', $_POST['csrf_token'])) {
        $flashMessageService->addError("Invalid security token. Action aborted.");
        header('Location: /index.php?page=manage_users');
        exit();
    }

    if (isset($_POST['action']) && $_POST['action'] === 'delete_user' && isset($_POST['user_id_to_delete'])) {
        $user_id_to_delete = filter_var($_POST['user_id_to_delete'], FILTER_VALIDATE_INT);

        if ($user_id_to_delete === false || $user_id_to_delete <= 0) {
            $flashMessageService->addError("Invalid user ID for deletion.");
        } else {
            // Get current user
            $currentUser = $authService->getCurrentUser();
            if ($currentUser && $user_id_to_delete == $currentUser['id']) {
                $flashMessageService->addError("You cannot delete your own account.");
            } else {
                try {
                    $conn = $database_handler->getConnection();
                    if (!$conn) {
                        $flashMessageService->addError("Database connection not available.");
                    } else {
                        $stmt_delete = $conn->prepare("DELETE FROM users WHERE id = :id");
                        $stmt_delete->bindParam(':id', $user_id_to_delete, PDO::PARAM_INT);

                        if ($stmt_delete->execute()) {
                            if ($stmt_delete->rowCount() > 0) {
                                $flashMessageService->addSuccess("User (ID: $user_id_to_delete) deleted successfully.");
                            } else {
                                $flashMessageService->addError("User (ID: $user_id_to_delete) not found or already deleted.");
                            }
                        } else {
                            $flashMessageService->addError("Failed to delete user. Please try again.");
                            error_log("Manage Users Page - Failed to execute delete statement for user ID: $user_id_to_delete. Error: " . print_r($stmt_delete->errorInfo(), true));
                        }
                    }
                } catch (PDOException $e) {
                    $flashMessageService->addError("Database error while deleting user: " . $e->getMessage());
                    error_log("Manage Users Page - PDOException deleting user ID $user_id_to_delete: " . $e->getMessage());
                }
            }
        }
        
        header('Location: /index.php?page=manage_users');
        exit();
    }
}

// Get list of users
$users = [];

try {
    $conn = $database_handler->getConnection();
    if (!$conn) {
        $flashMessageService->addError("Database connection is not available.");
        error_log("Manage Users Page: Database connection not available.");
    } else {
        $stmt = $conn->query("SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $flashMessageService->addError("Error fetching users: " . $e->getMessage());
    error_log("Manage Users Page - PDOException fetching users: " . $e->getMessage());
}

// Generate CSRF token
if (empty($_SESSION['csrf_token_manage_users'])) {
    $_SESSION['csrf_token_manage_users'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token_manage_users'];

// Get flash messages
$flashMessages = $flashMessageService->getAllMessages();

// Create unified navigation
$adminNavigation = new AdminNavigation($authService);

?>

    <!-- Admin Dark Theme Styles -->
    <link rel="stylesheet" href="/public/assets/css/admin.css">
    <link rel="stylesheet" href="/public/assets/css/admin-navigation.css">

    <!-- Unified Navigation -->
    <?= $adminNavigation->render() ?>

    <!-- Header -->
    <header class="admin-header">
        <div class="admin-header-container">
            <div class="admin-header-content">
                <div class="admin-header-title">
                    <i class="admin-header-icon fas fa-users"></i>
                    <div class="admin-header-text">
                        <h1>User Management</h1>
                        <p>Manage system users, roles, and permissions</p>
                    </div>
                </div>

                <div class="admin-header-actions">
                    <a href="/index.php?page=register" class="admin-btn admin-btn-primary">
                        <i class="fas fa-user-plus"></i>Add New User
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
                <?php if (empty($users)): ?>
                    <!-- Empty State -->
                    <div class="admin-card admin-glow-primary">
                        <div class="admin-card-body" style="text-align: center; padding: 3rem;">
                            <div style="font-size: 4rem; color: var(--admin-text-muted); margin-bottom: 1rem;">
                                <i class="fas fa-users"></i>
                            </div>
                            <h3 style="color: var(--admin-text-primary); margin-bottom: 1rem;">No Users Found</h3>
                            <p style="color: var(--admin-text-muted); margin-bottom: 2rem;">
                                The user management system is ready to use. Create your first user to get started.
                            </p>
                            <a href="/index.php?page=register" class="admin-btn admin-btn-primary admin-btn-lg">
                                <i class="fas fa-user-plus"></i>Create First User
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Users Table -->
                    <div class="admin-card">
                        <div class="admin-card-header">
                            <h3 class="admin-card-title">
                                <i class="fas fa-users"></i>System Users
                                <span class="admin-badge admin-badge-primary">
                                    <?= count($users) ?> Total
                                </span>
                            </h3>
                        </div>
                        <div class="admin-card-body" style="padding: 0;">
                            <div class="admin-table-container">
                                <table class="admin-table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>User</th>
                                            <th>Email</th>
                                            <th>Role</th>
                                            <th>Registered</th>
                                            <th style="text-align: center;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $currentUser = $authService->getCurrentUser();
                                        foreach ($users as $user):
                                        ?>
                                            <tr>
                                                <td>
                                                    <span class="admin-badge admin-badge-gray">
                                                        #<?= htmlspecialchars((string)$user['id']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div style="display: flex; align-items: center;">
                                                        <div style="width: 32px; height: 32px; background: var(--admin-primary-bg); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 0.75rem;">
                                                            <i class="fas fa-user" style="color: var(--admin-primary);"></i>
                                                        </div>
                                                        <div>
                                                            <div style="font-weight: 600; color: var(--admin-text-primary);">
                                                                <?= htmlspecialchars($user['username']) ?>
                                                            </div>
                                                            <?php if ($currentUser && $currentUser['id'] == $user['id']): ?>
                                                                <span class="admin-badge admin-badge-success" style="font-size: 0.625rem;">
                                                                    <i class="fas fa-check"></i>You
                                                                </span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span style="color: var(--admin-text-secondary);">
                                                        <?= htmlspecialchars($user['email']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="admin-badge admin-badge-<?= $user['role'] === 'admin' ? 'warning' : 'gray' ?>">
                                                        <i class="fas fa-<?= $user['role'] === 'admin' ? 'crown' : 'user' ?>"></i>
                                                        <?= htmlspecialchars(ucfirst($user['role'])) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div>
                                                        <div style="color: var(--admin-text-primary); font-size: 0.875rem;">
                                                            <?= htmlspecialchars(date("M j, Y", strtotime($user['created_at']))) ?>
                                                        </div>
                                                        <div style="color: var(--admin-text-muted); font-size: 0.75rem;">
                                                            <?= htmlspecialchars(date("g:i A", strtotime($user['created_at']))) ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td style="text-align: center;">
                                                    <div style="display: flex; gap: 0.5rem; justify-content: center;">
                                                        <a href="/index.php?page=edit_user&id=<?= $user['id'] ?>"
                                                           class="admin-btn admin-btn-secondary admin-btn-sm">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <?php if (!$currentUser || $currentUser['id'] !== $user['id']): ?>
                                                        <form method="POST" style="display: inline;"
                                                              onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.');">
                                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                                            <input type="hidden" name="user_id_to_delete" value="<?= $user['id'] ?>">
                                                            <input type="hidden" name="action" value="delete_user">
                                                            <button type="submit" class="admin-btn admin-btn-danger admin-btn-sm">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                        <?php else: ?>
                                                        <button type="button" class="admin-btn admin-btn-secondary admin-btn-sm"
                                                                disabled style="opacity: 0.5; cursor: not-allowed;">
                                                            <i class="fas fa-shield-alt"></i>
                                                        </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
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
                            <i class="fas fa-chart-bar"></i>User Statistics
                        </h3>
                    </div>
                    <div class="admin-card-body">
                        <?php
                        $adminCount = count(array_filter($users, fn($u) => $u['role'] === 'admin'));
                        $employeeCount = count(array_filter($users, fn($u) => $u['role'] === 'employee'));
                        $clientCount = count(array_filter($users, fn($u) => $u['role'] === 'client'));
                        $guestCount = count(array_filter($users, fn($u) => $u['role'] === 'guest'));
                        $recentUsers = count(array_filter($users, fn($u) => strtotime($u['created_at']) > strtotime('-7 days')));
                        ?>

                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                            <div style="display: flex; align-items: center;">
                                <i class="fas fa-crown" style="margin-right: 0.5rem; color: var(--admin-warning);"></i>
                                <span style="font-size: 0.875rem; color: var(--admin-text-secondary);">Administrators</span>
                            </div>
                            <span style="font-size: 1.125rem; font-weight: 600; color: var(--admin-text-primary);"><?= $adminCount ?></span>
                        </div>

                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                            <div style="display: flex; align-items: center;">
                                <i class="fas fa-user-tie" style="margin-right: 0.5rem; color: var(--admin-primary);"></i>
                                <span style="font-size: 0.875rem; color: var(--admin-text-secondary);">Employees</span>
                            </div>
                            <span style="font-size: 1.125rem; font-weight: 600; color: var(--admin-text-primary);"><?= $employeeCount ?></span>
                        </div>

                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                            <div style="display: flex; align-items: center;">
                                <i class="fas fa-user" style="margin-right: 0.5rem; color: var(--admin-success);"></i>
                                <span style="font-size: 0.875rem; color: var(--admin-text-secondary);">Clients</span>
                            </div>
                            <span style="font-size: 1.125rem; font-weight: 600; color: var(--admin-text-primary);"><?= $clientCount ?></span>
                        </div>

                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                            <div style="display: flex; align-items: center;">
                                <i class="fas fa-user-slash" style="margin-right: 0.5rem; color: var(--admin-text-muted);"></i>
                                <span style="font-size: 0.875rem; color: var(--admin-text-secondary);">Guests</span>
                            </div>
                            <span style="font-size: 1.125rem; font-weight: 600; color: var(--admin-text-primary);"><?= $guestCount ?></span>
                        </div>

                        <hr style="border: none; border-top: 1px solid var(--admin-border); margin: 1rem 0;">

                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div style="display: flex; align-items: center;">
                                <i class="fas fa-clock" style="margin-right: 0.5rem; color: var(--admin-info);"></i>
                                <span style="font-size: 0.875rem; color: var(--admin-text-secondary);">This Week</span>
                            </div>
                            <span style="font-size: 1.125rem; font-weight: 600; color: var(--admin-text-primary);"><?= $recentUsers ?></span>
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
                        <a href="/index.php?page=register" class="admin-btn admin-btn-primary" style="width: 100%; margin-bottom: 0.5rem; justify-content: flex-start;">
                            <i class="fas fa-user-plus"></i>
                            Add New User
                        </a>
                        <a href="/index.php?page=manage_articles" class="admin-btn admin-btn-secondary" style="width: 100%; margin-bottom: 0.5rem; justify-content: flex-start;">
                            <i class="fas fa-newspaper"></i>
                            Manage Articles
                        </a>
                        <a href="/index.php?page=site_settings" class="admin-btn admin-btn-secondary" style="width: 100%; margin-bottom: 0.5rem; justify-content: flex-start;">
                            <i class="fas fa-cogs"></i>
                            Site Settings
                        </a>
                        <a href="/index.php?page=dashboard" class="admin-btn admin-btn-secondary" style="width: 100%; justify-content: flex-start;">
                            <i class="fas fa-tachometer-alt"></i>
                            Dashboard
                        </a>
                    </div>
                </div>

                <!-- Role Information -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="fas fa-info-circle"></i>Role Information
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
            </aside>
        </div>
    </main>

    <!-- Admin Scripts -->
    <script type="module" src="/public/assets/js/admin.js"></script>