<?php

/**
 * Support Tickets - Client Portal - PHASE 8
 * Main page for managing support tickets in client portal
 */

declare(strict_types=1);

// Use global services from the new DI architecture
global $flashMessageService, $database_handler, $container, $serviceProvider;

use App\Application\Components\AdminNavigation;

// Get AuthenticationService
try {
    $authService = $serviceProvider->getAuth();
} catch (Exception $e) {
    error_log("Critical: Failed to get AuthenticationService instance: " . $e->getMessage());
    die("A critical system error occurred. Please try again later.");
}

// Check authentication
if (!$authService->isAuthenticated()) {
    $flashMessageService->addError('Please log in to access your tickets.');
    header("Location: /index.php?page=login");
    exit();
}

// Get user data
$current_user_id = $authService->getCurrentUserId();
$current_user_role = $authService->getCurrentUserRole();
$currentUser = $authService->getCurrentUser();

// Check if user can access client area
if (!in_array($current_user_role, ['client', 'employee', 'admin'])) {
    header('Location: /index.php?page=home');
    exit;
}

use App\Domain\Models\Ticket;

try {
    // Get filters from GET parameters
    $filters = [];
    if (!empty($_GET['status'])) $filters['status'] = $_GET['status'];
    if (!empty($_GET['category'])) $filters['category'] = $_GET['category'];
    if (!empty($_GET['priority'])) $filters['priority'] = $_GET['priority'];

    // Pagination
    $page = max(1, (int)($_GET['page_num'] ?? 1));
    $perPage = 10;
    $filters['limit'] = $perPage;
    $filters['offset'] = ($page - 1) * $perPage;

    // Get tickets and stats
    $tickets = Ticket::findByUserId($database_handler, $current_user_id, $filters);
    $stats = Ticket::getUserStats($database_handler, $current_user_id);

    // Available options for filters
    $statuses = Ticket::getStatuses();
    $priorities = Ticket::getPriorities();
    $categories = Ticket::getCategories();

} catch (Exception $e) {
    error_log("Error loading tickets: " . $e->getMessage());
    $flashMessageService->addError('Error loading tickets. Please try again.');
    $tickets = [];
    $stats = [];
}

$pageTitle = "Support Tickets";

// Get flash messages
$flashMessages = $flashMessageService->getAllMessages();

// Create unified navigation
$adminNavigation = new AdminNavigation($authService);
?>

<!-- Admin Dark Theme Styles -->
<link rel="stylesheet" href="/public/assets/css/admin.css">

<!-- Unified Navigation -->
<?= $adminNavigation->render() ?>

<div class="admin-container">
    <!-- Header -->
    <div class="admin-header">
        <div class="admin-header-container">
            <div class="admin-header-content">
                <div class="admin-header-title">
                    <i class="admin-header-icon fas fa-ticket-alt"></i>
                    <div class="admin-header-text">
                        <h1>Support Tickets</h1>
                        <p>Manage your support requests and get help from our team</p>
                    </div>
                </div>

                <div class="admin-header-actions">
                    <a href="/index.php?page=user_tickets_create" class="admin-btn admin-btn-primary">
                        <i class="fas fa-plus"></i>Create Ticket
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="admin-main">
        <div style="max-width: 1280px; margin: 0 auto; padding: 0 1rem;">

            <!-- Statistics Cards -->
            <div class="admin-stats-grid stats-grid-4" style="margin-bottom: 2rem;">
                <div class="admin-stat-card admin-glow-primary">
                    <div class="admin-stat-content">
                        <div class="admin-stat-icon admin-stat-icon-primary">
                            <i class="fas fa-ticket-alt"></i>
                        </div>
                        <div class="admin-stat-details">
                            <h3>Total Tickets</h3>
                            <p style="color: var(--admin-text-primary); font-size: 1.5rem; font-weight: 700;"><?= $stats['total'] ?? 0 ?></p>
                        </div>
                    </div>
                </div>

                <div class="admin-stat-card admin-glow-success">
                    <div class="admin-stat-content">
                        <div class="admin-stat-icon admin-stat-icon-success">
                            <i class="fas fa-folder-open"></i>
                        </div>
                        <div class="admin-stat-details">
                            <h3>Open</h3>
                            <p style="color: var(--admin-text-primary); font-size: 1.5rem; font-weight: 700;"><?= $stats['open'] ?? 0 ?></p>
                        </div>
                    </div>
                </div>

                <div class="admin-stat-card admin-glow-warning">
                    <div class="admin-stat-content">
                        <div class="admin-stat-icon admin-stat-icon-warning">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="admin-stat-details">
                            <h3>Waiting Response</h3>
                            <p style="color: var(--admin-text-primary); font-size: 1.5rem; font-weight: 700;"><?= $stats['waiting_client'] ?? 0 ?></p>
                        </div>
                    </div>
                </div>

                <div class="admin-stat-card admin-glow-info">
                    <div class="admin-stat-content">
                        <div class="admin-stat-icon admin-stat-icon-info">
                            <i class="fas fa-cog fa-spin"></i>
                        </div>
                        <div class="admin-stat-details">
                            <h3>In Progress</h3>
                            <p style="color: var(--admin-text-primary); font-size: 1.5rem; font-weight: 700;"><?= $stats['in_progress'] ?? 0 ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="admin-card" style="margin-bottom: 1.5rem;">
                <div class="admin-card-body" style="padding: 1rem;">
                    <form method="GET" class="admin-filters">
                        <input type="hidden" name="page" value="user_tickets">

                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; align-items: end;">
                            <div class="admin-form-group">
                                <label>Status</label>
                                <select name="status" class="admin-form-control">
                                    <option value="">All Statuses</option>
                                    <?php foreach ($statuses as $key => $label): ?>
                                        <option value="<?= $key ?>" <?= ($_GET['status'] ?? '') === $key ? 'selected' : '' ?>><?= $label ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="admin-form-group">
                                <label>Priority</label>
                                <select name="priority" class="admin-form-control">
                                    <option value="">All Priorities</option>
                                    <?php foreach ($priorities as $key => $label): ?>
                                        <option value="<?= $key ?>" <?= ($_GET['priority'] ?? '') === $key ? 'selected' : '' ?>><?= $label ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="admin-form-group">
                                <label>Category</label>
                                <select name="category" class="admin-form-control">
                                    <option value="">All Categories</option>
                                    <?php foreach ($categories as $key => $label): ?>
                                        <option value="<?= $key ?>" <?= ($_GET['category'] ?? '') === $key ? 'selected' : '' ?>><?= $label ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="admin-form-group">
                                <button type="submit" class="admin-btn admin-btn-secondary">
                                    <i class="fas fa-filter"></i> Filter
                                </button>
                                <a href="/index.php?page=user_tickets" class="admin-btn admin-btn-outline">
                                    <i class="fas fa-times"></i> Clear
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tickets Table -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3>Your Support Tickets</h3>
                </div>
                <div class="admin-card-body" style="padding: 0;">
                    <?php if (empty($tickets)): ?>
                        <div style="padding: 3rem; text-align: center; color: var(--admin-text-secondary);">
                            <i class="fas fa-ticket-alt" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                            <h3>No tickets found</h3>
                            <p>You haven't created any support tickets yet.</p>
                            <a href="/index.php?page=user_tickets_create" class="admin-btn admin-btn-primary" style="margin-top: 1rem;">
                                <i class="fas fa-plus"></i> Create Your First Ticket
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="admin-table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Subject</th>
                                        <th>Status</th>
                                        <th>Priority</th>
                                        <th>Category</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tickets as $ticket): ?>
                                        <tr>
                                            <td>
                                                <span class="admin-badge admin-badge-secondary">#<?= $ticket['id'] ?></span>
                                            </td>
                                            <td>
                                                <strong><?= htmlspecialchars($ticket['subject']) ?></strong>
                                            </td>
                                            <td>
                                                <?php
                                                $statusColors = [
                                                    'open' => 'success',
                                                    'in_progress' => 'info',
                                                    'waiting_client' => 'warning',
                                                    'resolved' => 'primary',
                                                    'closed' => 'secondary'
                                                ];
                                                $statusColor = $statusColors[$ticket['status']] ?? 'secondary';
                                                ?>
                                                <span class="admin-badge admin-badge-<?= $statusColor ?>">
                                                    <?= $statuses[$ticket['status']] ?? $ticket['status'] ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php
                                                $priorityColors = [
                                                    'low' => 'secondary',
                                                    'medium' => 'info',
                                                    'high' => 'warning',
                                                    'critical' => 'error'
                                                ];
                                                $priorityColor = $priorityColors[$ticket['priority']] ?? 'secondary';
                                                ?>
                                                <span class="admin-badge admin-badge-<?= $priorityColor ?>">
                                                    <?= $priorities[$ticket['priority']] ?? $ticket['priority'] ?>
                                                </span>
                                            </td>
                                            <td><?= $categories[$ticket['category']] ?? $ticket['category'] ?></td>
                                            <td>
                                                <span style="color: var(--admin-text-secondary); font-size: 0.875rem;">
                                                    <?= date('M j, Y', strtotime($ticket['created_at'])) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="/index.php?page=user_tickets_view&id=<?= $ticket['id'] ?>" class="admin-btn admin-btn-sm admin-btn-outline">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
