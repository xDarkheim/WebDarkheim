<?php
/**
 * Studio Projects - Client Portal - PHASE 8 - DARK ADMIN THEME
 * View active development projects from the studio
 */

declare(strict_types=1);

// Use global services from the DI architecture
global $flashMessageService, $database_handler, $container, $serviceProvider;

use App\Application\Components\AdminNavigation;

try {
    $authService = $serviceProvider->getAuth();
} catch (Exception $e) {
    error_log("Critical: Failed to get AuthenticationService instance: " . $e->getMessage());
    die("A critical system error occurred. Please try again later.");
}

// Check authentication
if (!$authService->isAuthenticated()) {
    $flashMessageService->addError('Please log in to access your projects.');
    header("Location: /index.php?page=login");
    exit();
}

$currentUser = $authService->getCurrentUser();

// Check if user can access client area
if (!in_array($currentUser['role'], ['client', 'employee', 'admin'])) {
    header('Location: /index.php?page=home');
    exit;
}

// Get studio projects for current user
try {
    $sql = "SELECT sp.*, 
                   COUNT(pm.id) as milestone_count,
                   COUNT(CASE WHEN pm.status = 'completed' THEN 1 END) as completed_milestones
            FROM studio_projects sp
            LEFT JOIN project_milestones pm ON sp.id = pm.project_id
            WHERE sp.client_id = ?
            GROUP BY sp.id
            ORDER BY sp.created_at DESC";

    $stmt = $database_handler->getConnection()->prepare($sql);
    $stmt->execute([$currentUser['id']]);
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log("Error getting studio projects: " . $e->getMessage());
    $projects = [];
}

// Create unified navigation
$adminNavigation = new AdminNavigation($authService);

// Get flash messages
$flashMessages = $flashMessageService->getAllMessages();
$pageTitle = 'Studio Projects';

?>

    <link rel="stylesheet" href="/public/assets/css/admin.css">

    <!-- Unified Navigation -->
    <?= $adminNavigation->render() ?>

    <!-- Header -->
    <header class="admin-header">
        <div class="admin-header-container">
            <div class="admin-header-content">
                <div class="admin-header-title">
                    <i class="admin-header-icon fas fa-code"></i>
                    <div class="admin-header-text">
                        <h1>Studio Projects</h1>
                        <p>Track your development projects with our studio</p>
                    </div>
                </div>
                <div class="admin-header-actions">
                    <a href="/index.php?page=contact" class="admin-btn admin-btn-primary">
                        <i class="fas fa-plus"></i> Start New Project
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
                    <div class="admin-flash-message admin-flash-<?= $type === 'error' ? 'error' : $type ?>">
                        <i class="fas fa-<?= $type === 'success' ? 'check-circle' : ($type === 'error' ? 'exclamation-circle' : 'info-circle') ?>"></i>
                        <div><?= htmlspecialchars($message['text']) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="admin-layout-main">
        <div class="admin-content">
            <!-- Projects Grid -->
            <?php if (empty($projects)): ?>
                <div class="admin-card">
                    <div class="admin-card-body">
                        <div class="empty-state">
                            <i class="fas fa-code"></i>
                            <h4 style="color: var(--admin-text-primary); margin-bottom: 1rem;">No Active Projects</h4>
                            <p style="margin-bottom: 2rem;">You don't have any active development projects with our studio yet.</p>
                            <a href="/index.php?page=contact" class="admin-btn admin-btn-primary">
                                <i class="fas fa-plus"></i> Start a Project
                            </a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="admin-grid admin-grid-cols-3">
                    <?php foreach ($projects as $project): ?>
                        <div class="admin-card project-card">
                            <div class="admin-card-body" style="position: relative;">
                                <!-- Project Type Badge -->
                                <div class="project-type-badge">
                                    <span class="admin-badge admin-badge-gray">
                                        <?= ucfirst($project['project_type'] ?? 'Project') ?>
                                    </span>
                                </div>

                                <!-- Project Title -->
                                <h5 style="color: var(--admin-text-primary); margin-bottom: 1rem; margin-top: 0;">
                                    <?= htmlspecialchars($project['project_name']) ?>
                                </h5>

                                <!-- Progress Circle -->
                                <div style="text-align: center; margin: 1.5rem 0;">
                                    <div class="progress-circle">
                                        <svg width="80" height="80" class="progress-ring">
                                            <circle cx="40" cy="40" r="30" stroke="var(--admin-border)" stroke-width="4" fill="transparent"/>
                                            <circle cx="40" cy="40" r="30" stroke="var(--admin-primary)" stroke-width="4" fill="transparent"
                                                    stroke-dasharray="<?= 2 * 3.14159 * 30 ?>"
                                                    stroke-dashoffset="<?= 2 * 3.14159 * 30 * (1 - ($project['progress_percentage'] ?? 0) / 100) ?>"/>
                                        </svg>
                                        <div class="progress-text">
                                            <?= $project['progress_percentage'] ?? 0 ?>%
                                        </div>
                                    </div>
                                </div>

                                <!-- Project Status -->
                                <div style="text-align: center; margin-bottom: 1rem;">
                                    <span class="admin-badge <?= getStatusBadgeClass($project['status'] ?? 'planning') ?>">
                                        <?= ucfirst(str_replace('_', ' ', $project['status'] ?? 'planning')) ?>
                                    </span>
                                </div>

                                <!-- Project Description -->
                                <p style="color: var(--admin-text-muted); font-size: 0.875rem; line-height: 1.4; margin-bottom: 1rem;">
                                    <?= htmlspecialchars(substr($project['description'] ?? '', 0, 100)) ?>
                                    <?= strlen($project['description'] ?? '') > 100 ? '...' : '' ?>
                                </p>

                                <!-- Project Details -->
                                <div style="font-size: 0.75rem; color: var(--admin-text-muted); margin-bottom: 1rem;">
                                    <?php if ($project['start_date']): ?>
                                        <div style="margin-bottom: 0.25rem;">
                                            <i class="fas fa-play"></i> Started: <?= date('M j, Y', strtotime($project['start_date'])) ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($project['estimated_completion']): ?>
                                        <div style="margin-bottom: 0.25rem;">
                                            <i class="fas fa-flag-checkered"></i> Est. Completion: <?= date('M j, Y', strtotime($project['estimated_completion'])) ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($project['milestone_count'] > 0): ?>
                                        <div>
                                            <i class="fas fa-tasks"></i> Milestones: <?= $project['completed_milestones'] ?>/<?= $project['milestone_count'] ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="admin-card-footer">
                                <div style="display: flex; gap: 0.5rem;">
                                    <a href="/index.php?page=user_projects_details&id=<?= $project['id'] ?>"
                                       class="admin-btn admin-btn-primary admin-btn-sm" style="flex: 1;">
                                        <i class="fas fa-eye"></i> Details
                                    </a>
                                    <a href="/index.php?page=user_projects_timeline&id=<?= $project['id'] ?>"
                                       class="admin-btn admin-btn-secondary admin-btn-sm" style="flex: 1;">
                                        <i class="fas fa-clock"></i> Timeline
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="admin-sidebar">
            <!-- Project Overview -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h6 class="admin-card-title" style="font-size: 0.875rem;">Project Overview</h6>
                </div>
                <div class="admin-card-body">
                    <div class="admin-stats-grid" style="grid-template-columns: 1fr 1fr;">
                        <div style="text-align: center;">
                            <div style="font-size: 1.5rem; font-weight: 700; color: var(--admin-primary); margin-bottom: 0.25rem;">
                                <?= count($projects) ?>
                            </div>
                            <small style="color: var(--admin-text-muted);">Total Projects</small>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 1.5rem; font-weight: 700; color: var(--admin-success); margin-bottom: 0.25rem;">
                                <?php
                                $activeProjects = array_filter($projects, fn($p) => in_array($p['status'], ['development', 'testing']));
                                echo count($activeProjects);
                                ?>
                            </div>
                            <small style="color: var(--admin-text-muted);">Active</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h6 class="admin-card-title" style="font-size: 0.875rem;">Quick Actions</h6>
                </div>
                <div class="admin-card-body">
                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                        <a href="/index.php?page=contact" class="admin-btn admin-btn-primary admin-btn-sm">
                            <i class="fas fa-plus"></i> Start New Project
                        </a>
                        <a href="/index.php?page=user_tickets_create" class="admin-btn admin-btn-secondary admin-btn-sm">
                            <i class="fas fa-ticket-alt"></i> Create Support Ticket
                        </a>
                        <a href="/index.php?page=user_documents" class="admin-btn admin-btn-secondary admin-btn-sm">
                            <i class="fas fa-folder"></i> View Documents
                        </a>
                    </div>
                </div>
            </div>

            <!-- Support Information -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h6 class="admin-card-title" style="font-size: 0.875rem;">Need Help?</h6>
                </div>
                <div class="admin-card-body">
                    <p style="color: var(--admin-text-muted); font-size: 0.75rem; margin-bottom: 1rem;">
                        Have questions about your projects? Our support team is here to help.
                    </p>
                    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                        <a href="/index.php?page=user_tickets" class="admin-btn admin-btn-success admin-btn-sm">
                            <i class="fas fa-headset"></i> Support Center
                        </a>
                        <a href="/index.php?page=contact" class="admin-btn admin-btn-secondary admin-btn-sm">
                            <i class="fas fa-envelope"></i> Contact Us
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="module" src="/public/assets/js/admin.js"></script>

<?php
function getStatusBadgeClass($status): string
{
    return match($status) {
        'planning' => 'admin-badge-gray',
        'development' => 'admin-badge-primary',
        'testing' => 'admin-badge-warning',
        'deployment' => 'admin-badge-primary',
        'completed' => 'admin-badge-success',
        'on_hold' => 'admin-badge-error',
        default => 'admin-badge-gray'
    };
}
?>
