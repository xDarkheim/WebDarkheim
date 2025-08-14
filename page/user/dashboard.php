<?php

/**
 * Client Portal Dashboard - PHASE 8 - DARK ADMIN THEME
 * Updated to use unified AdminNavigation component
 */

declare(strict_types=1);

// Use global services from the new DI architecture
global $flashMessageService, $database_handler, $container, $serviceProvider;

// Include profile completion helper
require_once __DIR__ . '/../../includes/profile_completion_helper.php';

// Include the unified AdminNavigation component
require_once __DIR__ . '/../../src/Application/Components/AdminNavigation.php';
use App\Application\Components\AdminNavigation;

// Get AuthenticationService instead of direct SessionManager access
try {
    $authService = $serviceProvider->getAuth();
} catch (Exception $e) {
    error_log("Critical: Failed to get AuthenticationService instance: " . $e->getMessage());
    die("A critical system error occurred. Please try again later.");
}

// Check authentication via AuthenticationService
if (!$authService->isAuthenticated()) {
    $flashMessageService->addError('Please log in to access your dashboard.');
    header("Location: /index.php?page=login");
    exit();
}

// Get user data via AuthenticationService
$current_user_id = $authService->getCurrentUserId();
$current_user_role = $authService->getCurrentUserRole();
$current_username = $authService->getCurrentUsername();
$currentUser = $authService->getCurrentUser();

// Check if user can access client area
if (!in_array($current_user_role, ['client', 'employee', 'admin'])) {
    header('Location: /index.php?page=home');
    exit;
}

// Get dashboard data with error handling
try {
    // Support tickets stats
    $ticketStats = getSupportTicketStats($database_handler, $current_user_id, $current_user_role);

    // Portfolio stats
    $portfolioStats = getPortfolioStatsData($database_handler, $current_user_id);

    // Studio projects stats
    $studioProjectsStats = getStudioProjectsStats($database_handler, $current_user_id);

    // Recent activity
    $recentActivity = getRecentActivity($database_handler, $current_user_id);

    // Profile completion - using unified helper function
    $userData = $currentUser;
    $clientProfile = getClientProfileData($database_handler, $current_user_id);
    $profileCompletion = calculateProfileCompletion($userData, $clientProfile);

    // Get recent tickets (simplified)
    $recentTickets = getRecentTicketsSimple($database_handler, $current_user_id, 3);

    // Get invoices stats - ДОБАВЛЕНО: статистика инвойсов
    $invoicesStats = getInvoicesStats($database_handler, $current_user_id, $current_user_role);

    // Get recent invoices - ДОБАВЛЕНО: последние инвойсы
    $recentInvoices = getRecentInvoices($database_handler, $current_user_id, 3);

    // Create unified navigation with badge counts
    $adminNavigation = new AdminNavigation($authService);
    if (($ticketStats['open'] ?? 0) > 0) {
        $adminNavigation->setBadgeCount('user_tickets', $ticketStats['open']);
    }

} catch (Exception $e) {
    error_log("Dashboard data error: " . $e->getMessage());

    // Fallback empty data
    $ticketStats = ['total' => 0, 'open' => 0, 'in_progress' => 0, 'waiting_client' => 0, 'critical' => 0, 'today' => 0];
    $portfolioStats = ['total_projects' => 0, 'published' => 0, 'pending' => 0, 'drafts' => 0, 'total_views' => 0];
    $studioProjectsStats = ['total_projects' => 0, 'in_development' => 0, 'planning' => 0, 'completed' => 0, 'avg_progress' => 0];
    $recentActivity = [];
    $profileCompletion = ['percentage' => 0, 'missing' => []];
    $recentTickets = [];
    $invoicesStats = ['total' => 0, 'paid' => 0, 'due' => 0, 'overdue' => 0, 'draft' => 0, 'total_amount' => 0, 'paid_amount' => 0, 'outstanding_amount' => 0];
    $recentInvoices = [];

    // Still create navigation even on error
    $adminNavigation = new AdminNavigation($authService);
}

// Get flash messages
$flashMessages = $flashMessageService->getAllMessages();

// Set page title
$pageTitle = 'Client Portal Dashboard';

/**
 * Get support tickets statistics - исправлено для работы с реальной таблицей tickets
 */
function getSupportTicketStats($database, $userId, $userRole): array
{
    try {
        if (in_array($userRole, ['admin', 'employee'])) {
            // Admin/employee see all tickets (согласно Phase 1 - hierarchy roles)
            $sql = "SELECT 
                        COUNT(*) as total,
                        COUNT(CASE WHEN status = 'open' THEN 1 END) as open,
                        COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress,
                        COUNT(CASE WHEN status = 'waiting_client' THEN 1 END) as waiting_client,
                        COUNT(CASE WHEN priority = 'urgent' THEN 1 END) as critical,
                        COUNT(CASE WHEN created_at >= CURDATE() THEN 1 END) as today
                    FROM tickets";
            $stmt = $database->getConnection()->prepare($sql);
            $stmt->execute();
        } else {
            // Clients see only their tickets (согласно Phase 8 - client access rights)
            $sql = "SELECT 
                        COUNT(*) as total,
                        COUNT(CASE WHEN status = 'open' THEN 1 END) as open,
                        COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress,
                        COUNT(CASE WHEN status = 'waiting_client' THEN 1 END) as waiting_client,
                        COUNT(CASE WHEN priority = 'urgent' THEN 1 END) as critical,
                        COUNT(CASE WHEN created_at >= CURDATE() THEN 1 END) as today
                    FROM tickets WHERE user_id = ?";
            $stmt = $database->getConnection()->prepare($sql);
            $stmt->execute([$userId]);
        }

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [
            'total' => 0, 'open' => 0, 'in_progress' => 0, 'waiting_client' => 0, 'critical' => 0, 'today' => 0
        ];
    } catch (Exception $e) {
        error_log("Error getting ticket stats: " . $e->getMessage());
        return ['total' => 0, 'open' => 0, 'in_progress' => 0, 'waiting_client' => 0, 'critical' => 0, 'today' => 0];
    }
}

/**
 * Get portfolio statistics
 */
function getPortfolioStatsData($database, $userId): array
{
    try {
        $sql = "SELECT 
                    COUNT(p.id) as total_projects,
                    COUNT(CASE WHEN p.status = 'published' THEN 1 END) as published,
                    COUNT(CASE WHEN p.status = 'pending' THEN 1 END) as pending,
                    COUNT(CASE WHEN p.status = 'draft' THEN 1 END) as drafts,
                    COALESCE(SUM(pv.view_count), 0) as total_views
                FROM client_profiles cp
                LEFT JOIN client_portfolio p ON cp.id = p.client_profile_id
                LEFT JOIN (
                    SELECT project_id, COUNT(*) as view_count 
                    FROM project_views 
                    GROUP BY project_id
                ) pv ON p.id = pv.project_id
                WHERE cp.user_id = ?
                GROUP BY cp.id";

        $stmt = $database->getConnection()->prepare($sql);
        $stmt->execute([$userId]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [
            'total_projects' => 0, 'published' => 0, 'pending' => 0, 'draft' => 0, 'total_views' => 0
        ];
    } catch (Exception $e) {
        error_log("Error getting portfolio stats: " . $e->getMessage());
        return ['total_projects' => 0, 'published' => 0, 'pending' => 0, 'draft' => 0, 'total_views' => 0];
    }
}

/**
 * Get studio projects statistics
 */
function getStudioProjectsStats($database, $userId): array
{
    try {
        $sql = "SELECT 
                    COUNT(*) as total_projects,
                    COUNT(CASE WHEN status = 'development' THEN 1 END) as in_development,
                    COUNT(CASE WHEN status = 'planning' THEN 1 END) as planning,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
                    AVG(progress_percentage) as avg_progress
                FROM studio_projects 
                WHERE client_id = ?";

        $stmt = $database->getConnection()->prepare($sql);
        $stmt->execute([$userId]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [
            'total_projects' => 0, 'in_development' => 0, 'planning' => 0, 'completed' => 0, 'avg_progress' => 0
        ];
    } catch (Exception $e) {
        error_log("Error getting studio projects stats: " . $e->getMessage());
        return ['total_projects' => 0, 'in_development' => 0, 'planning' => 0, 'completed' => 0, 'avg_progress' => 0];
    }
}

/**
 * Get recent tickets (simplified version) - ИСПРАВЛЕНО: корректная обработка приоритетов и полей
 */
function getRecentTicketsSimple($database, $userId, $limit = 3): array
{
    try {
        // Проверяем, какие поля есть в таблице tickets
        $sql = "SELECT t.*, 
                       CASE t.priority 
                           WHEN 'urgent' THEN 'bg-danger'
                           WHEN 'high' THEN 'bg-warning' 
                           WHEN 'medium' THEN 'bg-primary'
                           WHEN 'low' THEN 'bg-secondary'
                           ELSE 'bg-secondary'
                       END as priority_badge_class,
                       CASE t.status
                           WHEN 'open' THEN 'bg-info'
                           WHEN 'in_progress' THEN 'bg-warning'
                           WHEN 'waiting_client' THEN 'bg-secondary'
                           WHEN 'resolved' THEN 'bg-success'
                           WHEN 'closed' THEN 'bg-dark'
                           ELSE 'bg-secondary'
                       END as status_badge_class
                FROM tickets t
                WHERE t.user_id = ?
                ORDER BY t.created_at DESC 
                LIMIT ?";

        $stmt = $database->getConnection()->prepare($sql);
        $stmt->execute([$userId, $limit]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting recent tickets: " . $e->getMessage());
        return [];
    }
}

/**
 * Get recent activity - исправлено для работы с реальной таблицей tickets
 */
function getRecentActivity($database, $userId): array
{
    try {
        $activities = [];

        // Recent ticket activities - исправлено: используем таблицу tickets вместо support_tickets
        $sql = "SELECT 'ticket' as type, subject as title, created_at, status, id
                FROM tickets 
                WHERE user_id = ? 
                ORDER BY created_at DESC LIMIT 5";
        $stmt = $database->getConnection()->prepare($sql);
        $stmt->execute([$userId]);
        $ticketActivities = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($ticketActivities as $activity) {
            $activities[] = [
                'type' => 'ticket',
                'title' => $activity['title'],
                'status' => $activity['status'],
                'date' => $activity['created_at'],
                'url' => "/index.php?page=user_tickets_view&id=" . $activity['id'],
                'icon' => 'fas fa-ticket-alt',
                'color' => 'primary'
            ];
        }

        // Recent portfolio activities
        $sql = "SELECT 'portfolio' as type, p.title, p.status, p.created_at, p.id
                FROM client_portfolio p
                LEFT JOIN client_profiles cp ON p.client_profile_id = cp.user_id
                WHERE cp.user_id = ? 
                ORDER BY p.updated_at DESC LIMIT 5";
        $stmt = $database->getConnection()->prepare($sql);
        $stmt->execute([$userId]);
        $portfolioActivities = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($portfolioActivities as $activity) {
            $color = match($activity['status']) {
                'published', 'completed' => 'success',
                'pending', 'in_progress' => 'warning',
                'draft', 'planning' => 'secondary',
                'rejected' => 'danger',
                default => 'primary'
            };

            $activities[] = [
                'type' => 'portfolio',
                'title' => $activity['title'],
                'status' => $activity['status'],
                'date' => $activity['created_at'],
                'url' => "/index.php?page=user_portfolio_edit&id=" . $activity['id'],
                'icon' => 'fas fa-folder-open',
                'color' => $color
            ];
        }

        // Recent article activities
        $sql = "SELECT 'article' as type, title, status, date as created_at, id
                FROM articles 
                WHERE user_id = ? 
                ORDER BY date DESC LIMIT 5";
        $stmt = $database->getConnection()->prepare($sql);
        $stmt->execute([$userId]);
        $articleActivities = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($articleActivities as $activity) {
            $color = match($activity['status']) {
                'published' => 'success',
                'pending_review' => 'warning',
                'draft' => 'secondary',
                'rejected' => 'danger',
                default => 'primary'
            };

            $activities[] = [
                'type' => 'article',
                'title' => $activity['title'],
                'status' => $activity['status'],
                'date' => $activity['created_at'],
                'url' => "/index.php?page=edit_article&id=" . $activity['id'],
                'icon' => 'fas fa-newspaper',
                'color' => $color
            ];
        }

        // Sort by date
        usort($activities, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });

        return array_slice($activities, 0, 8);
    } catch (Exception $e) {
        error_log("Error getting recent activity: " . $e->getMessage());
        return [];
    }
}

/**
 * Get invoices statistics - ИСПРАВЛЕНО: использует правильную таблицу client_invoices
 */
function getInvoicesStats($database, $userId, $userRole): array
{
    try {
        if (in_array($userRole, ['admin', 'employee'])) {
            // Admin/employee see all invoices
            $sql = "SELECT 
                        COUNT(*) as total,
                        COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid,
                        COUNT(CASE WHEN status = 'sent' THEN 1 END) as due,
                        COUNT(CASE WHEN status = 'overdue' THEN 1 END) as overdue,
                        COUNT(CASE WHEN status = 'draft' THEN 1 END) as draft,
                        SUM(total_amount) as total_amount,
                        SUM(CASE WHEN status = 'paid' THEN total_amount ELSE 0 END) as paid_amount,
                        SUM(CASE WHEN status NOT IN ('paid', 'cancelled') THEN total_amount ELSE 0 END) as outstanding_amount
                    FROM client_invoices";
            $stmt = $database->getConnection()->prepare($sql);
            $stmt->execute();
        } else {
            // Clients see only their invoices
            $sql = "SELECT 
                        COUNT(*) as total,
                        COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid,
                        COUNT(CASE WHEN status = 'sent' THEN 1 END) as due,
                        COUNT(CASE WHEN status = 'overdue' THEN 1 END) as overdue,
                        COUNT(CASE WHEN status = 'draft' THEN 1 END) as draft,
                        SUM(total_amount) as total_amount,
                        SUM(CASE WHEN status = 'paid' THEN total_amount ELSE 0 END) as paid_amount,
                        SUM(CASE WHEN status NOT IN ('paid', 'cancelled') THEN total_amount ELSE 0 END) as outstanding_amount
                    FROM client_invoices WHERE client_id = ?";
            $stmt = $database->getConnection()->prepare($sql);
            $stmt->execute([$userId]);
        }

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [
            'total' => 0, 'paid' => 0, 'due' => 0, 'overdue' => 0, 'draft' => 0,
            'total_amount' => 0, 'paid_amount' => 0, 'outstanding_amount' => 0
        ];
    } catch (Exception $e) {
        error_log("Error getting invoices stats: " . $e->getMessage());
        return ['total' => 0, 'paid' => 0, 'due' => 0, 'overdue' => 0, 'draft' => 0,
                'total_amount' => 0, 'paid_amount' => 0, 'outstanding_amount' => 0];
    }
}

/**
 * Get recent invoices - ИСПРАВЛЕНО: использует правильную таблицу client_invoices
 */
function getRecentInvoices($database, $userId, $limit = 3): array
{
    try {
        $sql = "SELECT ci.*, 
                       CASE ci.status
                           WHEN 'paid' THEN 'bg-success'
                           WHEN 'sent' THEN 'bg-warning'
                           WHEN 'overdue' THEN 'bg-danger'
                           WHEN 'draft' THEN 'bg-secondary'
                           WHEN 'cancelled' THEN 'bg-dark'
                           ELSE 'bg-secondary'
                       END as status_badge_class
                FROM client_invoices ci
                WHERE ci.client_id = ?
                ORDER BY ci.created_at DESC 
                LIMIT ?";

        $stmt = $database->getConnection()->prepare($sql);
        $stmt->execute([$userId, $limit]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting recent invoices: " . $e->getMessage());
        return [];
    }
}
?>

    <!-- Admin Dark Theme Styles -->
    <link rel="stylesheet" href="/public/assets/css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Navigation -->
    <?= $adminNavigation->render() ?>

    <!-- Header -->
    <header class="admin-header">
        <div class="admin-header-container">
            <div class="admin-header-content">
                <div class="admin-header-title">
                    <i class="admin-header-icon fas fa-tachometer-alt"></i>
                    <div class="admin-header-text">
                        <h1>
                            <?php
                            $greeting = date('H') < 12 ? 'Good morning' : (date('H') < 18 ? 'Good afternoon' : 'Good evening');
                            echo $greeting . ', ' . htmlspecialchars($currentUser['username']) . '!';
                            ?>
                        </h1>
                        <p>Welcome to your client portal dashboard</p>
                    </div>
                </div>

                <div class="admin-header-actions">
                    <a href="/index.php?page=user_tickets_create" class="admin-btn admin-btn-primary">
                        <i class="fas fa-plus"></i>New Ticket
                    </a>
                    <a href="/index.php?page=portfolio_create" class="admin-btn admin-btn-success">
                        <i class="fas fa-briefcase"></i>Add Project
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main>
        <div class="admin-layout-main">
            <div class="admin-content">
                <!-- Statistics Cards -->
                <div class="admin-stats-grid">
                    <!-- Support Tickets -->
                    <div class="admin-stat-card">
                        <div class="admin-stat-content">
                            <div class="admin-stat-icon admin-stat-icon-primary">
                                <i class="fas fa-ticket-alt"></i>
                            </div>
                            <div class="admin-stat-details">
                                <h3>Support Tickets</h3>
                                <p style="color: var(--admin-text-primary); font-size: 1.5rem; font-weight: 700;"><?= $ticketStats['total'] ?? 0 ?></p>
                                <?php if (($ticketStats['open'] ?? 0) > 0): ?>
                                    <span class="admin-badge admin-badge-warning" style="margin-top: 0.5rem;">
                                        <i class="fas fa-exclamation-circle"></i><?= $ticketStats['open'] ?> Open
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div style="margin-top: 1rem; text-align: center;">
                            <a href="/index.php?page=user_tickets" class="admin-btn admin-btn-primary admin-btn-sm">
                                <i class="fas fa-eye"></i>View All
                            </a>
                        </div>
                    </div>

                    <!-- Portfolio Projects -->
                    <div class="admin-stat-card">
                        <div class="admin-stat-content">
                            <div class="admin-stat-icon admin-stat-icon-success">
                                <i class="fas fa-briefcase"></i>
                            </div>
                            <div class="admin-stat-details">
                                <h3>Portfolio Projects</h3>
                                <p style="color: var(--admin-text-primary); font-size: 1.5rem; font-weight: 700;"><?= $portfolioStats['total_projects'] ?? 0 ?></p>
                                <?php if (($portfolioStats['published'] ?? 0) > 0): ?>
                                    <span class="admin-badge admin-badge-success" style="margin-top: 0.5rem;">
                                        <i class="fas fa-check-circle"></i><?= $portfolioStats['published'] ?> Published
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div style="margin-top: 1rem; text-align: center;">
                            <a href="/index.php?page=user_portfolio" class="admin-btn admin-btn-success admin-btn-sm">
                                <i class="fas fa-folder-open"></i>Manage
                            </a>
                        </div>
                    </div>

                    <!-- Studio Projects -->
                    <div class="admin-stat-card">
                        <div class="admin-stat-content">
                            <div class="admin-stat-icon" style="background-color: var(--admin-info-bg); color: var(--admin-info);">
                                <i class="fas fa-code"></i>
                            </div>
                            <div class="admin-stat-details">
                                <h3>Studio Projects</h3>
                                <p style="color: var(--admin-text-primary); font-size: 1.5rem; font-weight: 700;"><?= $studioProjectsStats['total_projects'] ?? 0 ?></p>
                                <?php if (($studioProjectsStats['in_development'] ?? 0) > 0): ?>
                                    <span class="admin-badge admin-badge-warning" style="margin-top: 0.5rem;">
                                        <i class="fas fa-code"></i><?= $studioProjectsStats['in_development'] ?> In Development
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div style="margin-top: 1rem; text-align: center;">
                            <a href="/index.php?page=user_projects" class="admin-btn admin-btn-secondary admin-btn-sm">
                                <i class="fas fa-external-link-alt"></i>View Projects
                            </a>
                        </div>
                    </div>

                    <!-- Invoices - ЗАМЕНЕНО: вместо Profile Complete -->
                    <div class="admin-stat-card">
                        <div class="admin-stat-content">
                            <div class="admin-stat-icon" style="background-color: var(--admin-warning-bg); color: var(--admin-warning);">
                                <i class="fas fa-file-invoice-dollar"></i>
                            </div>
                            <div class="admin-stat-details">
                                <h3>Invoices</h3>
                                <p style="color: var(--admin-text-primary); font-size: 1.5rem; font-weight: 700;"><?= $invoicesStats['total'] ?? 0 ?></p>
                                <?php if (($invoicesStats['overdue'] ?? 0) > 0): ?>
                                    <span class="admin-badge admin-badge-error" style="margin-top: 0.5rem;">
                                        <i class="fas fa-exclamation-triangle"></i><?= $invoicesStats['overdue'] ?> Overdue
                                    </span>
                                <?php elseif (($invoicesStats['draft'] ?? 0) > 0): ?>
                                    <span class="admin-badge admin-badge-secondary" style="margin-top: 0.5rem;">
                                        <i class="fas fa-edit"></i><?= $invoicesStats['draft'] ?> Draft
                                    </span>
                                <?php elseif (($invoicesStats['paid'] ?? 0) > 0): ?>
                                    <span class="admin-badge admin-badge-success" style="margin-top: 0.5rem;">
                                        <i class="fas fa-check-circle"></i><?= $invoicesStats['paid'] ?> Paid
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div style="margin-top: 1rem; text-align: center;">
                            <a href="/index.php?page=user_invoices" class="admin-btn admin-btn-warning admin-btn-sm">
                                <i class="fas fa-file-invoice"></i>View All
                            </a>
                        </div>
                    </div>
                </div>
                <!-- Recent Activity -->
                <?php if (!empty($recentActivity)): ?>
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="fas fa-clock"></i>Recent Activity
                        </h3>
                    </div>
                    <div class="admin-card-body">
                        <?php foreach (array_slice($recentActivity, 0, 6) as $activity): ?>
                            <div style="display: flex; align-items: center; padding: 0.75rem 0; border-bottom: 1px solid var(--admin-border);">
                                <div style="margin-right: 1rem; color: var(--admin-<?= $activity['color'] ?>);">
                                    <i class="<?= $activity['icon'] ?>"></i>
                                </div>
                                <div style="flex-grow: 1;">
                                    <h6 style="margin: 0 0 0.25rem 0; font-weight: 500;">
                                        <a href="<?= $activity['url'] ?>" style="color: var(--admin-text-primary); text-decoration: none;">
                                            <?= htmlspecialchars($activity['title']) ?>
                                        </a>
                                    </h6>
                                    <div style="font-size: 0.75rem; color: var(--admin-text-muted);">
                                        <?= ucfirst($activity['type']) ?> • <?= date('M j, Y', strtotime($activity['date'])) ?>
                                        <?php if (!empty($activity['status'])): ?>
                                            • <span class="admin-badge admin-badge-<?= $activity['color'] ?>"><?= ucfirst($activity['status']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Administrative Actions for Admin/Employee -->
                <?php if (in_array($currentUser['role'], ['admin', 'employee'])): ?>
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="fas fa-tools"></i>Administrative Actions
                        </h3>
                    </div>
                    <div class="admin-card-body">
                        <!-- Content Management Section -->
                        <div style="margin-bottom: 2rem;">
                            <h4 style="color: var(--admin-text-primary); margin-bottom: 1rem; font-size: 1rem; font-weight: 600; display: flex; align-items: center;">
                                <i class="fas fa-newspaper" style="color: var(--admin-primary); margin-right: 0.5rem;"></i>
                                Content Management
                            </h4>
                            <div class="admin-grid admin-grid-cols-3">
                                <a href="/index.php?page=manage_articles" class="admin-btn admin-btn-secondary">
                                    <i class="fas fa-newspaper"></i>Manage Articles
                                </a>
                                <a href="/index.php?page=manage_categories" class="admin-btn admin-btn-secondary">
                                    <i class="fas fa-tags"></i>Manage Categories
                                </a>
                                <a href="/index.php?page=create_article" class="admin-btn admin-btn-success">
                                    <i class="fas fa-plus"></i>Create Article
                                </a>
                            </div>
                        </div>

                        <!-- Moderation Section -->
                        <div style="margin-bottom: 2rem;">
                            <h4 style="color: var(--admin-text-primary); margin-bottom: 1rem; font-size: 1rem; font-weight: 600; display: flex; align-items: center;">
                                <i class="fas fa-gavel" style="color: var(--admin-warning); margin-right: 0.5rem;"></i>
                                Moderation & Review
                            </h4>
                            <div class="admin-grid admin-grid-cols-3">
                                <a href="/index.php?page=admin_moderation_dashboard" class="admin-btn admin-btn-primary">
                                    <i class="fas fa-gavel"></i>Moderation Dashboard
                                </a>
                                <a href="/index.php?page=moderate_projects" class="admin-btn admin-btn-warning">
                                    <i class="fas fa-clipboard-check"></i>Moderate Projects
                                </a>
                                <a href="/index.php?page=moderate_comments" class="admin-btn admin-btn-warning">
                                    <i class="fas fa-comments"></i>Moderate Comments
                                </a>
                            </div>
                        </div>

                        <!-- System Management Section (Admin Only) -->
                        <?php if ($currentUser['role'] === 'admin'): ?>
                        <div style="margin-bottom: 2rem;">
                            <h4 style="color: var(--admin-text-primary); margin-bottom: 1rem; font-size: 1rem; font-weight: 600; display: flex; align-items: center;">
                                <i class="fas fa-users-cog" style="color: var(--admin-info); margin-right: 0.5rem;"></i>
                                User & System Management
                            </h4>
                            <div class="admin-grid admin-grid-cols-2">
                                <a href="/index.php?page=manage_users" class="admin-btn admin-btn-secondary">
                                    <i class="fas fa-users"></i>Manage Users
                                </a>
                                <a href="/index.php?page=site_settings" class="admin-btn admin-btn-secondary">
                                    <i class="fas fa-cogs"></i>Site Settings
                                </a>
                            </div>
                        </div>

                        <!-- System Monitoring Section (Admin Only) -->
                        <div>
                            <h4 style="color: var(--admin-text-primary); margin-bottom: 1rem; font-size: 1rem; font-weight: 600; display: flex; align-items: center;">
                                <i class="fas fa-chart-line" style="color: var(--admin-success); margin-right: 0.5rem;"></i>
                                System Monitoring
                            </h4>
                            <div class="admin-grid admin-grid-cols-2">
                                <a href="/index.php?page=system_monitor" class="admin-btn admin-btn-secondary">
                                    <i class="fas fa-chart-line"></i>System Monitor
                                </a>
                                <a href="/index.php?page=backup_monitor" class="admin-btn admin-btn-secondary">
                                    <i class="fas fa-database"></i>Backup Monitor
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <aside class="admin-sidebar">
                <!-- Profile Summary -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="fas fa-user"></i>Profile Summary
                        </h3>
                    </div>
                    <div class="admin-card-body">
                        <div style="text-align: center; margin-bottom: 1.5rem;">
                            <div style="width: 64px; height: 64px; background: var(--admin-primary-bg); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem auto;">
                                <i class="fas fa-user" style="color: var(--admin-primary); font-size: 1.5rem;"></i>
                            </div>
                            <h6 style="margin: 0 0 0.25rem 0; color: var(--admin-text-primary); font-weight: 600;">
                                <?= htmlspecialchars($currentUser['username']) ?>
                            </h6>
                            <span class="admin-badge admin-badge-primary">
                                <i class="fas fa-<?= $currentUser['role'] === 'admin' ? 'crown' : ($currentUser['role'] === 'employee' ? 'user-tie' : 'user') ?>"></i>
                                <?= ucfirst($currentUser['role']) ?>
                            </span>
                        </div>

                        <!-- Profile Completion Progress -->
                        <div style="margin-bottom: 1.5rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                <span style="font-size: 0.875rem; color: var(--admin-text-secondary);">Profile Completion</span>
                                <span style="font-size: 0.875rem; color: var(--admin-text-primary); font-weight: 600;"><?= $profileCompletion['percentage'] ?? 0 ?>%</span>
                            </div>
                            <div style="background: var(--admin-bg-secondary); height: 8px; border-radius: 4px; overflow: hidden;">
                                <div style="background: var(--admin-success); height: 100%; width: <?= $profileCompletion['percentage'] ?? 0 ?>%; transition: all 0.3s;"></div>
                            </div>
                        </div>

                        <a href="/index.php?page=user_profile" class="admin-btn admin-btn-primary" style="width: 100%; margin-bottom: 0.5rem; justify-content: flex-start;">
                            <i class="fas fa-edit"></i>Edit Profile
                        </a>
                        <a href="/index.php?page=portfolio_settings" class="admin-btn admin-btn-secondary" style="width: 100%; justify-content: flex-start;">
                            <i class="fas fa-cogs"></i>Portfolio Settings
                        </a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="fas fa-external-link-alt"></i>Quick Links
                        </h3>
                    </div>
                    <div class="admin-card-body">
                        <a href="/index.php?page=user_tickets" class="admin-btn admin-btn-secondary" style="width: 100%; margin-bottom: 0.5rem; justify-content: space-between;">
                            <span><i class="fas fa-ticket-alt"></i>Support Tickets</span>
                            <?php if (($ticketStats['open'] ?? 0) > 0): ?>
                                <span class="admin-badge admin-badge-error"><?= $ticketStats['open'] ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="/index.php?page=user_portfolio" class="admin-btn admin-btn-secondary" style="width: 100%; margin-bottom: 0.5rem; justify-content: flex-start;">
                            <i class="fas fa-briefcase"></i>My Portfolio
                        </a>
                        <a href="/index.php?page=user_projects" class="admin-btn admin-btn-secondary" style="width: 100%; margin-bottom: 0.5rem; justify-content: flex-start;">
                            <i class="fas fa-code"></i>Studio Projects
                        </a>
                        <a href="/index.php?page=user_invoices" class="admin-btn admin-btn-secondary" style="width: 100%; margin-bottom: 0.5rem; justify-content: flex-start;">
                            <i class="fas fa-file-invoice-dollar"></i>Invoices
                        </a>
                        <a href="/index.php?page=user_documents" class="admin-btn admin-btn-secondary" style="width: 100%; justify-content: flex-start;">
                            <i class="fas fa-folder"></i>Documents
                        </a>
                    </div>
                </div>

                <!-- Help & Support -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="fas fa-question-circle"></i>Help & Support
                        </h3>
                    </div>
                    <div class="admin-card-body">
                        <p style="font-size: 0.875rem; color: var(--admin-text-muted); margin-bottom: 1rem;">
                            Need assistance? Our support team is here to help you with any questions or issues.
                        </p>
                        <a href="/index.php?page=user_tickets_create" class="admin-btn admin-btn-primary" style="width: 100%; margin-bottom: 0.5rem; justify-content: flex-start;">
                            <i class="fas fa-plus"></i>Create Support Ticket
                        </a>
                        <a href="/index.php?page=contact" class="admin-btn admin-btn-secondary" style="width: 100%; margin-bottom: 0.5rem; justify-content: flex-start;">
                            <i class="fas fa-phone"></i>Contact Us
                        </a>
                        <a href="/index.php?page=about" class="admin-btn admin-btn-secondary" style="width: 100%; justify-content: flex-start;">
                            <i class="fas fa-book"></i>Documentation
                        </a>
                    </div>
                </div>
            </aside>
        </div>
    </main>

    <!-- Admin Scripts -->
    <script src="/public/assets/js/admin.js"></script>
    <script>
        // Initialize dashboard functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-dismiss flash messages after 5 seconds
            setTimeout(function() {
                document.querySelectorAll('.admin-flash-message').forEach(function(message) {
                    message.style.opacity = '0';
                    setTimeout(function() {
                        message.remove();
                    }, 300);
                });
            }, 5000);

            // Add click tracking for dashboard actions
            document.querySelectorAll('.admin-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    console.log('Dashboard action clicked:', this.textContent.trim());
                });
            });
        });
    </script>
