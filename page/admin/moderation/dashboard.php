<?php

/**
 * Moderation Dashboard - DARK ADMIN THEME
 * Central admin panel for moderation overview and quick actions
 *
 * @author GitHub Copilot
 */

declare(strict_types=1);

// Get global services from DI container
global $container;

try {
    // Get ServiceProvider for accessing services
    $serviceProvider = \App\Application\Core\ServiceProvider::getInstance($container);
    
    // Get required services
    $authService = $serviceProvider->getAuth();
    $flashService = $serviceProvider->getFlashMessage();
    $logger = $serviceProvider->getLogger();
    $database = $serviceProvider->getDatabase();

    // Create moderation controller
    $moderationController = new \App\Application\Controllers\ModerationController(
        $database,
        $authService,
        $flashService,
        $logger
    );

    // Handle request
    $data = $moderationController->handleModerationDashboard();

    // Get flash messages
    $flashMessages = $moderationController->getFlashMessages();

    // Set page title
    $pageTitle = 'Moderation Dashboard - Admin Panel';

} catch (Exception $e) {
    // Handle critical errors
    if (isset($logger)) {
        $logger->critical("Critical error in moderation dashboard: " . $e->getMessage());
    }
    
    $data = [
        'error' => 'System temporarily unavailable. Please try again later.',
        'statistics' => [],
        'recent_projects' => [],
        'recent_comments' => [],
        'pending_items' => []
    ];
    $flashMessages = [];
}

?>

    <!-- Admin Dark Theme Styles -->
    <link rel="stylesheet" href="/public/assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Navigation -->
    <nav class="admin-nav">
        <div class="admin-nav-container">
            <a href="/index.php?page=dashboard" class="admin-nav-brand">
                <i class="fas fa-gavel"></i>
                <span>Moderation Center</span>
            </a>

            <div class="admin-nav-links">
                <a href="/index.php?page=admin_moderation_dashboard" class="admin-nav-link" style="background-color: var(--admin-primary-bg); color: var(--admin-primary-light); border-color: var(--admin-primary-border);">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <a href="/index.php?page=moderate_projects" class="admin-nav-link">
                    <i class="fas fa-clipboard-check"></i>
                    <span>Projects</span>
                    <?php if (($data['statistics']['pending_projects'] ?? 0) > 0): ?>
                        <span class="admin-badge admin-badge-warning" style="margin-left: 0.5rem; padding: 0.25rem 0.5rem; font-size: 0.6rem;">
                            <?= $data['statistics']['pending_projects'] ?>
                        </span>
                    <?php endif; ?>
                </a>
                <a href="/index.php?page=moderate_comments" class="admin-nav-link">
                    <i class="fas fa-comments"></i>
                    <span>Comments</span>
                    <?php if (($data['statistics']['pending_comments'] ?? 0) > 0): ?>
                        <span class="admin-badge admin-badge-warning" style="margin-left: 0.5rem; padding: 0.25rem 0.5rem; font-size: 0.6rem;">
                            <?= $data['statistics']['pending_comments'] ?>
                        </span>
                    <?php endif; ?>
                </a>
                <a href="/index.php?page=manage_users" class="admin-nav-link">
                    <i class="fas fa-users"></i>
                    <span>Users</span>
                </a>
                <a href="/index.php?page=dashboard" class="admin-nav-link">
                    <i class="fas fa-arrow-left"></i>
                    <span>Main Dashboard</span>
                </a>
            </div>
        </div>
    </nav>

    <!-- Header -->
    <header class="admin-header">
        <div class="admin-header-container">
            <div class="admin-header-content">
                <div class="admin-header-title">
                    <i class="admin-header-icon fas fa-gavel"></i>
                    <div class="admin-header-text">
                        <h1>Moderation Dashboard</h1>
                        <p>Review and moderate user-generated content</p>
                    </div>
                </div>

                <div class="admin-header-actions">
                    <a href="/index.php?page=moderate_projects" class="admin-btn admin-btn-warning">
                        <i class="fas fa-clipboard-check"></i>Review Projects
                    </a>
                    <a href="/index.php?page=moderate_comments" class="admin-btn admin-btn-primary">
                        <i class="fas fa-comments"></i>Review Comments
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

                <!-- Error State -->
                <?php if (isset($data['error'])): ?>
                <div class="admin-card admin-glow-error">
                    <div class="admin-card-body" style="text-align: center; padding: 3rem;">
                        <div style="font-size: 4rem; color: var(--admin-error); margin-bottom: 1rem;">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <h3 style="color: var(--admin-text-primary); margin-bottom: 1rem;">System Error</h3>
                        <p style="color: var(--admin-text-muted); margin-bottom: 2rem;">
                            <?= htmlspecialchars($data['error']) ?>
                        </p>
                        <a href="/index.php?page=dashboard" class="admin-btn admin-btn-primary">
                            <i class="fas fa-arrow-left"></i>Return to Dashboard
                        </a>
                    </div>
                </div>
                <?php else: ?>

                <!-- Moderation Statistics -->
                <div class="admin-stats-grid">
                    <!-- Pending Projects -->
                    <div class="admin-stat-card admin-glow-warning">
                        <div class="admin-stat-content">
                            <div class="admin-stat-icon admin-stat-icon-warning">
                                <i class="fas fa-clipboard-check"></i>
                            </div>
                            <div class="admin-stat-details">
                                <h3>Pending Projects</h3>
                                <p style="color: var(--admin-text-primary); font-size: 1.5rem; font-weight: 700;"><?= $data['statistics']['pending_projects'] ?? 0 ?></p>
                                <?php if (($data['statistics']['urgent_projects'] ?? 0) > 0): ?>
                                    <span class="admin-badge admin-badge-error" style="margin-top: 0.5rem;">
                                        <i class="fas fa-exclamation-circle"></i><?= $data['statistics']['urgent_projects'] ?> Urgent
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div style="margin-top: 1rem; text-align: center;">
                            <a href="/index.php?page=moderate_projects" class="admin-btn admin-btn-warning admin-btn-sm">
                                <i class="fas fa-eye"></i>Review Now
                            </a>
                        </div>
                    </div>

                    <!-- Pending Comments -->
                    <div class="admin-stat-card admin-glow-primary">
                        <div class="admin-stat-content">
                            <div class="admin-stat-icon admin-stat-icon-primary">
                                <i class="fas fa-comments"></i>
                            </div>
                            <div class="admin-stat-details">
                                <h3>Pending Comments</h3>
                                <p style="color: var(--admin-text-primary); font-size: 1.5rem; font-weight: 700;"><?= $data['statistics']['pending_comments'] ?? 0 ?></p>
                                <span style="font-size: 0.75rem; color: var(--admin-text-muted); margin-top: 0.5rem; display: block;">
                                    Today: <?= $data['statistics']['comments_today'] ?? 0 ?>
                                </span>
                            </div>
                        </div>
                        <div style="margin-top: 1rem; text-align: center;">
                            <a href="/index.php?page=moderate_comments" class="admin-btn admin-btn-primary admin-btn-sm">
                                <i class="fas fa-eye"></i>Review Now
                            </a>
                        </div>
                    </div>

                    <!-- Total Approved -->
                    <div class="admin-stat-card admin-glow-success">
                        <div class="admin-stat-content">
                            <div class="admin-stat-icon admin-stat-icon-success">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="admin-stat-details">
                                <h3>Approved Today</h3>
                                <p style="color: var(--admin-text-primary); font-size: 1.5rem; font-weight: 700;"><?= $data['statistics']['approved_today'] ?? 0 ?></p>
                                <span style="font-size: 0.75rem; color: var(--admin-text-muted); margin-top: 0.5rem; display: block;">
                                    Projects: <?= $data['statistics']['projects_approved_today'] ?? 0 ?> | Comments: <?= $data['statistics']['comments_approved_today'] ?? 0 ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Response Time -->
                    <div class="admin-stat-card">
                        <div class="admin-stat-content">
                            <div class="admin-stat-icon" style="background-color: var(--admin-info-bg); color: var(--admin-info);">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="admin-stat-details">
                                <h3>Avg. Response Time</h3>
                                <p style="color: var(--admin-text-primary); font-size: 1.5rem; font-weight: 700;"><?= $data['statistics']['avg_response_time'] ?? '0h' ?></p>
                                <span style="font-size: 0.75rem; color: var(--admin-text-muted); margin-top: 0.5rem; display: block;">
                                    Target: &lt; 24h
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Projects Pending Review -->
                <?php if (!empty($data['recent_projects'])): ?>
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="fas fa-clipboard-check"></i>Recent Projects Pending Review
                        </h3>
                    </div>
                    <div class="admin-card-body">
                        <div class="admin-table-container">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Project</th>
                                        <th>Author</th>
                                        <th>Submitted</th>
                                        <th>Priority</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($data['recent_projects'] as $project): ?>
                                    <tr>
                                        <td>
                                            <div>
                                                <h6 style="margin: 0 0 0.25rem 0; font-weight: 600;">
                                                    <?= htmlspecialchars($project['title']) ?>
                                                </h6>
                                                <div style="font-size: 0.75rem; color: var(--admin-text-muted);">
                                                    <?= htmlspecialchars(substr($project['description'] ?? '', 0, 60)) ?>...
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                                <div class="admin-table-avatar" style="background: var(--admin-primary-bg); color: var(--admin-primary);">
                                                    <?= strtoupper(substr($project['author_name'] ?? 'U', 0, 1)) ?>
                                                </div>
                                                <span style="font-weight: 500;"><?= htmlspecialchars($project['author_name'] ?? 'Unknown') ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <div style="font-size: 0.875rem;">
                                                <?= date('M j, Y', strtotime($project['submitted_at'])) ?>
                                            </div>
                                            <div style="font-size: 0.75rem; color: var(--admin-text-muted);">
                                                <?= date('g:i A', strtotime($project['submitted_at'])) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="admin-badge admin-badge-<?= $project['priority'] === 'high' ? 'error' : ($project['priority'] === 'medium' ? 'warning' : 'gray') ?>">
                                                <?= ucfirst($project['priority'] ?? 'normal') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="admin-table-actions">
                                                <a href="/index.php?page=moderate_project_details&id=<?= $project['id'] ?>" class="admin-btn admin-btn-primary admin-btn-sm">
                                                    <i class="fas fa-eye"></i><span>Review</span>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="admin-card-footer">
                        <a href="/index.php?page=moderate_projects" class="admin-btn admin-btn-secondary">
                            <i class="fas fa-list"></i>View All Projects
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Recent Comments Pending Review -->
                <?php if (!empty($data['recent_comments'])): ?>
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="fas fa-comments"></i>Recent Comments Pending Review
                        </h3>
                    </div>
                    <div class="admin-card-body">
                        <?php foreach (array_slice($data['recent_comments'], 0, 5) as $comment): ?>
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; padding: 1rem 0; border-bottom: 1px solid var(--admin-border);">
                                <div style="flex-grow: 1; margin-right: 1rem;">
                                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                                        <div class="admin-table-avatar" style="background: var(--admin-info-bg); color: var(--admin-info);">
                                            <?= strtoupper(substr($comment['author_name'] ?? 'U', 0, 1)) ?>
                                        </div>
                                        <h6 style="margin: 0; font-weight: 600;"><?= htmlspecialchars($comment['author_name'] ?? 'Anonymous') ?></h6>
                                        <span style="font-size: 0.75rem; color: var(--admin-text-muted);">
                                            on <?= htmlspecialchars($comment['project_title'] ?? 'Unknown Project') ?>
                                        </span>
                                    </div>
                                    <p style="margin: 0; color: var(--admin-text-primary); line-height: 1.4;">
                                        <?= htmlspecialchars(substr($comment['content'] ?? '', 0, 150)) ?>...
                                    </p>
                                    <div style="font-size: 0.75rem; color: var(--admin-text-muted); margin-top: 0.5rem;">
                                        <?= date('M j, Y g:i A', strtotime($comment['created_at'])) ?>
                                    </div>
                                </div>
                                <div class="admin-table-actions">
                                    <a href="/index.php?page=moderate_comments&highlight=<?= $comment['id'] ?>" class="admin-btn admin-btn-primary admin-btn-sm">
                                        <i class="fas fa-eye"></i><span>Review</span>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="admin-card-footer">
                        <a href="/index.php?page=moderate_comments" class="admin-btn admin-btn-secondary">
                            <i class="fas fa-list"></i>View All Comments
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <aside class="admin-sidebar">
                <!-- Moderation Guidelines -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="fas fa-book"></i>Moderation Guidelines
                        </h3>
                    </div>
                    <div class="admin-card-body">
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <div>
                                <h4 style="display: flex; align-items: center; margin: 0 0 0.5rem 0; font-size: 0.875rem; font-weight: 600;">
                                    <i class="fas fa-check-circle" style="color: var(--admin-success); margin-right: 0.5rem;"></i>Approve When
                                </h4>
                                <ul style="font-size: 0.75rem; color: var(--admin-text-secondary); margin: 0; padding-left: 1rem;">
                                    <li>Content follows community guidelines</li>
                                    <li>No offensive or inappropriate material</li>
                                    <li>Technical quality meets standards</li>
                                </ul>
                            </div>
                            <div>
                                <h4 style="display: flex; align-items: center; margin: 0 0 0.5rem 0; font-size: 0.875rem; font-weight: 600;">
                                    <i class="fas fa-times-circle" style="color: var(--admin-error); margin-right: 0.5rem;"></i>Reject When
                                </h4>
                                <ul style="font-size: 0.75rem; color: var(--admin-text-secondary); margin: 0; padding-left: 1rem;">
                                    <li>Contains spam or promotional content</li>
                                    <li>Violates copyright or licensing</li>
                                    <li>Poor quality or incomplete work</li>
                                </ul>
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
                        <a href="/index.php?page=moderate_projects&filter=pending" class="admin-btn admin-btn-warning" style="width: 100%; margin-bottom: 0.5rem; justify-content: space-between;">
                            <span><i class="fas fa-clipboard-check"></i>Pending Projects</span>
                            <?php if (($data['statistics']['pending_projects'] ?? 0) > 0): ?>
                                <span class="admin-badge admin-badge-error"><?= $data['statistics']['pending_projects'] ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="/index.php?page=moderate_comments&filter=pending" class="admin-btn admin-btn-primary" style="width: 100%; margin-bottom: 0.5rem; justify-content: space-between;">
                            <span><i class="fas fa-comments"></i>Pending Comments</span>
                            <?php if (($data['statistics']['pending_comments'] ?? 0) > 0): ?>
                                <span class="admin-badge admin-badge-error"><?= $data['statistics']['pending_comments'] ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="/index.php?page=moderate_projects&filter=urgent" class="admin-btn admin-btn-danger" style="width: 100%; margin-bottom: 0.5rem; justify-content: flex-start;">
                            <i class="fas fa-exclamation-triangle"></i>Urgent Reviews
                        </a>
                        <a href="/index.php?page=moderation_history" class="admin-btn admin-btn-secondary" style="width: 100%; justify-content: flex-start;">
                            <i class="fas fa-history"></i>Review History
                        </a>
                    </div>
                </div>

                <!-- Moderation Stats -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="fas fa-chart-bar"></i>This Week
                        </h3>
                    </div>
                    <div class="admin-card-body">
                        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-size: 0.875rem; color: var(--admin-text-secondary);">Projects Reviewed</span>
                                <span style="font-size: 0.875rem; color: var(--admin-text-primary); font-weight: 600;"><?= $data['statistics']['projects_reviewed_week'] ?? 0 ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-size: 0.875rem; color: var(--admin-text-secondary);">Comments Reviewed</span>
                                <span style="font-size: 0.875rem; color: var(--admin-text-primary); font-weight: 600;"><?= $data['statistics']['comments_reviewed_week'] ?? 0 ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-size: 0.875rem; color: var(--admin-text-secondary);">Approval Rate</span>
                                <span style="font-size: 0.875rem; color: var(--admin-success); font-weight: 600;"><?= $data['statistics']['approval_rate'] ?? '0%' ?></span>
                            </div>
                        </div>
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
            // Auto-refresh stats every 30 seconds
            setInterval(function() {
                // Refresh moderation statistics
                fetch('/index.php?page=api_moderation_stats')
                    .then(response => response.json())
                    .then(data => {
                        // Update badge counts
                        updateModerationBadges(data);
                    })
                    .catch(error => console.log('Stats refresh failed:', error));
            }, 30000);

            // Add click tracking for moderation actions
            document.querySelectorAll('.admin-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    console.log('Moderation action clicked:', this.textContent.trim());
                });
            });
        });

        function updateModerationBadges(data) {
            // Update navigation badges
            const projectsBadge = document.querySelector('a[href*="moderate_projects"] .admin-badge');
            const commentsBadge = document.querySelector('a[href*="moderate_comments"] .admin-badge');

            if (projectsBadge && data.pending_projects > 0) {
                projectsBadge.textContent = data.pending_projects;
            }

            if (commentsBadge && data.pending_comments > 0) {
                commentsBadge.textContent = data.pending_comments;
            }
        }
    </script>
