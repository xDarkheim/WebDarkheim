<?php

/**
 * Manage Users Page
 *
 * This page allows administrators to view, edit, and delete user accounts.
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

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
            $currentUser = $auth->getCurrentUser();
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

?>

<div class="admin-page-wrapper">
<div class="admin-layout">
    <!-- Enhanced Main Header Section -->
    <header class="page-header">
        <div class="page-header-content">
            <div class="page-header-main">
                <h1 class="page-title">
                    <i class="fas fa-users"></i>
                    <?php echo htmlspecialchars($page_title); ?>
                </h1>
                <div class="page-header-description">
                    <p>Manage system users, roles, and permissions</p>
                </div>
            </div>
            <div class="page-header-actions">
                <a href="/index.php?page=dashboard" class="btn btn-secondary">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="/index.php?page=register" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i> Add User
                </a>
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
        <main class="main-content">
            <?php if (empty($users)): ?>
                <!-- Empty State -->
                <div class="card card-primary">
                    <div class="card-body">
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <h3 class="empty-state-title">No Users Found</h3>
                            <p class="empty-state-description">
                                The user management system is ready to use. Create your first user to get started.
                            </p>
                            <a href="/index.php?page=register" class="btn btn-primary btn-large">
                                <i class="fas fa-user-plus"></i> Create First User
                            </a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Users Table Card -->
                <div class="card card-primary">
                    <div class="card-header">
                        <div class="card-header-content">
                            <h2 class="card-title">
                                <i class="fas fa-users"></i> System Users
                            </h2>
                            <div class="card-header-meta">
                                <span class="meta-badge">
                                    <i class="fas fa-user"></i>
                                    <?php echo count($users); ?> Users
                                </span>
                            </div>
                        </div>
                        <div class="card-header-actions">
                            <button type="button" class="btn-icon btn-filter" title="Filter Users">
                                <i class="fas fa-filter"></i>
                            </button>
                            <button type="button" class="btn-icon btn-export" title="Export Users">
                                <i class="fas fa-download"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-container">
                            <div class="table-wrapper">
                                <table class="data-table users-table">
                                    <thead>
                                        <tr>
                                            <th class="sortable" data-sort="id">
                                                <span>ID</span>
                                                <i class="fas fa-sort"></i>
                                            </th>
                                            <th class="sortable" data-sort="username">
                                                <span>Username</span>
                                                <i class="fas fa-sort"></i>
                                            </th>
                                            <th class="sortable" data-sort="email">
                                                <span>Email</span>
                                                <i class="fas fa-sort"></i>
                                            </th>
                                            <th class="sortable" data-sort="role">
                                                <span>Role</span>
                                                <i class="fas fa-sort"></i>
                                            </th>
                                            <th class="sortable" data-sort="created_at">
                                                <span>Registered</span>
                                                <i class="fas fa-sort"></i>
                                            </th>
                                            <th class="actions-column">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $currentUser = $auth->getCurrentUser();
                                        foreach ($users as $user):
                                        ?>
                                            <tr class="table-row">
                                                <td class="table-cell">
                                                    <span class="user-id"><?php echo htmlspecialchars((string)$user['id']); ?></span>
                                                </td>
                                                <td class="table-cell">
                                                    <div class="user-info">
                                                        <div class="user-avatar">
                                                            <i class="fas fa-user"></i>
                                                        </div>
                                                        <div class="user-details">
                                                            <span class="user-name"><?php echo htmlspecialchars($user['username']); ?></span>
                                                            <?php if ($currentUser && $currentUser['id'] == $user['id']): ?>
                                                                <span class="user-badge badge-current">You</span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="table-cell">
                                                    <span class="user-email"><?php echo htmlspecialchars($user['email']); ?></span>
                                                </td>
                                                <td class="table-cell">
                                                    <span class="role-badge role-<?php echo htmlspecialchars($user['role']); ?>">
                                                        <i class="fas fa-<?php echo $user['role'] === 'admin' ? 'crown' : 'user'; ?>"></i>
                                                        <?php echo htmlspecialchars(ucfirst($user['role'])); ?>
                                                    </span>
                                                </td>
                                                <td class="table-cell">
                                                    <div class="date-info">
                                                        <span class="date-primary"><?php echo htmlspecialchars(date("M j, Y", strtotime($user['created_at']))); ?></span>
                                                        <span class="date-secondary"><?php echo htmlspecialchars(date("g:i A", strtotime($user['created_at']))); ?></span>
                                                    </div>
                                                </td>
                                                <td class="table-cell actions-cell">
                                                    <div class="action-buttons">
                                                        <a href="/index.php?page=edit_user&id=<?php echo $user['id']; ?>"
                                                           class="btn btn-secondary btn-small"
                                                           title="Edit User">
                                                            <i class="fas fa-edit"></i>
                                                            <span>Edit</span>
                                                        </a>
                                                        <?php if (!$currentUser || $currentUser['id'] !== $user['id']): ?>
                                                        <form action="/index.php?page=manage_users" method="POST"
                                                              class="delete-form"
                                                              onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.');">
                                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                                            <input type="hidden" name="user_id_to_delete" value="<?php echo $user['id']; ?>">
                                                            <input type="hidden" name="action" value="delete_user">
                                                            <button type="submit" class="btn btn-danger btn-small" title="Delete User">
                                                                <i class="fas fa-trash"></i>
                                                                <span>Delete</span>
                                                            </button>
                                                        </form>
                                                        <?php else: ?>
                                                        <button type="button" class="btn btn-disabled btn-small" title="Cannot delete your own account" disabled>
                                                            <i class="fas fa-shield-alt"></i>
                                                            <span>Protected</span>
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
                </div>
            <?php endif; ?>
        </main>

        <!-- Enhanced Compact Sidebar -->
        <aside class="sidebar-content" style="min-width: 280px; max-width: 320px;">
            <!-- Quick Stats Card -->
            <div class="card card-compact sidebar-card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-chart-bar"></i> User Statistics
                    </h3>
                </div>
                <div class="card-body">
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-info">
                                <span class="stat-number"><?php echo count($users); ?></span>
                                <span class="stat-label">Total Users</span>
                            </div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-icon">
                                <i class="fas fa-crown"></i>
                            </div>
                            <div class="stat-info">
                                <span class="stat-number"><?php echo count(array_filter($users, fn($u) => $u['role'] === 'admin')); ?></span>
                                <span class="stat-label">Administrators</span>
                            </div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-icon">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="stat-info">
                                <span class="stat-number"><?php echo count(array_filter($users, fn($u) => $u['role'] === 'user')); ?></span>
                                <span class="stat-label">Regular Users</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions Card -->
            <div class="card card-compact sidebar-card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-bolt"></i> Quick Actions
                    </h3>
                </div>
                <div class="card-body">
                    <nav class="quick-nav">
                        <a href="/index.php?page=register" class="quick-nav-link">
                            <i class="fas fa-user-plus"></i>
                            <span>Add New User</span>
                        </a>
                        <a href="/index.php?page=site_settings" class="quick-nav-link">
                            <i class="fas fa-cog"></i>
                            <span>Site Settings</span>
                        </a>
                        <a href="/index.php?page=manage_categories" class="quick-nav-link">
                            <i class="fas fa-tags"></i>
                            <span>Manage Categories</span>
                        </a>
                        <a href="/index.php?page=dashboard" class="quick-nav-link">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Recent Activity Card -->
            <div class="card card-compact sidebar-card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-clock"></i> Recent Users
                    </h3>
                </div>
                <div class="card-body">
                    <div class="recent-items">
                        <?php
                        $recentUsers = array_slice($users, 0, 5);
                        foreach ($recentUsers as $user):
                        ?>
                            <div class="recent-item">
                                <div class="recent-item-icon">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div class="recent-item-info">
                                    <span class="recent-item-title"><?php echo htmlspecialchars($user['username']); ?></span>
                                    <span class="recent-item-meta">
                                        <span class="role-badge role-<?php echo htmlspecialchars($user['role']); ?>"><?php echo ucfirst($user['role']); ?></span>
                                        <span class="recent-item-date"><?php echo date('M j', strtotime($user['created_at'])); ?></span>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</div>
</div>

<!-- Simple JavaScript for user management -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Table sorting functionality
    const sortableHeaders = document.querySelectorAll('.sortable');
    sortableHeaders.forEach(header => {
        header.addEventListener('click', function() {
            // Add sorting logic here
            console.log('Sort by:', this.getAttribute('data-sort'));
        });
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
</script>

<!-- Include CSS to fix users table -->
<link rel="stylesheet" href="/themes/default/css/pages/_manage-users-table-fix.css">
