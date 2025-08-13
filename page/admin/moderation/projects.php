<?php

/**
 * Projects Moderation Page - DARK ADMIN THEME
 * Administrative interface for moderating client portfolio projects
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
    $data = $moderationController->handleProjectsModeration();

    // Get flash messages
    $flashMessages = $moderationController->getFlashMessages();

    // Set page title
    $pageTitle = 'Projects Moderation - Admin Panel';

} catch (Exception $e) {
    // Handle critical errors
    if (isset($logger)) {
        $logger->critical("Critical error in projects moderation page: " . $e->getMessage());
    }

    $data = [
        'error' => 'System temporarily unavailable. Please try again later.',
        'projects' => [],
        'statistics' => [],
        'filters' => []
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
            <a href="/index.php?page=admin_moderation_dashboard" class="admin-nav-brand">
                <i class="fas fa-gavel"></i>
                <span>Moderation Center</span>
            </a>

            <div class="admin-nav-links">
                <a href="/index.php?page=admin_moderation_dashboard" class="admin-nav-link">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <a href="/index.php?page=moderate_projects" class="admin-nav-link" style="background-color: var(--admin-primary-bg); color: var(--admin-primary-light); border-color: var(--admin-primary-border);">
                    <i class="fas fa-clipboard-check"></i>
                    <span>Projects</span>
                    <?php if (($data['statistics']['pending_count'] ?? 0) > 0): ?>
                        <span class="admin-badge admin-badge-warning" style="margin-left: 0.5rem; padding: 0.25rem 0.5rem; font-size: 0.6rem;">
                            <?= $data['statistics']['pending_count'] ?>
                        </span>
                    <?php endif; ?>
                </a>
                <a href="/index.php?page=moderate_comments" class="admin-nav-link">
                    <i class="fas fa-comments"></i>
                    <span>Comments</span>
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
                    <i class="admin-header-icon fas fa-clipboard-check"></i>
                    <div class="admin-header-text">
                        <h1>Project Moderation</h1>
                        <p>Review and moderate client portfolio projects</p>
                    </div>
                </div>

                <div class="admin-header-actions">
                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                        <!-- Filter Dropdown -->
                        <select id="statusFilter" class="admin-input admin-select" style="width: auto; min-width: 120px;">
                            <option value="">All Status</option>
                            <option value="pending" <?= ($_GET['filter'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="approved" <?= ($_GET['filter'] ?? '') === 'approved' ? 'selected' : '' ?>>Approved</option>
                            <option value="rejected" <?= ($_GET['filter'] ?? '') === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                            <option value="flagged" <?= ($_GET['filter'] ?? '') === 'flagged' ? 'selected' : '' ?>>Flagged</option>
                        </select>

                        <!-- Search -->
                        <input type="text" id="searchProjects" class="admin-input" placeholder="Search projects..."
                               data-search-target="tbody tr" data-search-columns="1,2" style="width: 200px;">

                        <!-- Bulk Actions -->
                        <div class="admin-btn-group" style="display: none;" id="bulkActions">
                            <button type="button" class="admin-btn admin-btn-success admin-btn-sm" onclick="bulkApprove()">
                                <i class="fas fa-check"></i>Approve
                            </button>
                            <button type="button" class="admin-btn admin-btn-danger admin-btn-sm" onclick="bulkReject()">
                                <i class="fas fa-times"></i>Reject
                            </button>
                        </div>
                    </div>
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
                        <a href="/index.php?page=admin_moderation_dashboard" class="admin-btn admin-btn-primary">
                            <i class="fas fa-arrow-left"></i>Return to Moderation Dashboard
                        </a>
                    </div>
                </div>
                <?php elseif (empty($data['projects'])): ?>
                <!-- Empty State -->
                <div class="admin-card">
                    <div class="admin-card-body" style="text-align: center; padding: 3rem;">
                        <div style="font-size: 4rem; color: var(--admin-text-muted); margin-bottom: 1rem;">
                            <i class="fas fa-clipboard-check"></i>
                        </div>
                        <h3 style="color: var(--admin-text-primary); margin-bottom: 1rem;">No Projects to Review</h3>
                        <p style="color: var(--admin-text-muted); margin-bottom: 2rem;">
                            All projects have been reviewed. Great job keeping up with moderation!
                        </p>
                        <a href="/index.php?page=admin_moderation_dashboard" class="admin-btn admin-btn-primary">
                            <i class="fas fa-tachometer-alt"></i>Back to Dashboard
                        </a>
                    </div>
                </div>
                <?php else: ?>

                <!-- Projects Table -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="fas fa-list"></i>Portfolio Projects (<?= count($data['projects']) ?>)
                        </h3>
                    </div>
                    <div class="admin-card-body">
                        <div class="admin-table-container">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th style="width: 3%;">
                                            <input type="checkbox" id="selectAll" data-select-all>
                                        </th>
                                        <th style="width: 30%;">Project</th>
                                        <th style="width: 15%;">Author</th>
                                        <th style="width: 12%;">Status</th>
                                        <th style="width: 12%;">Submitted</th>
                                        <th style="width: 10%;">Priority</th>
                                        <th style="width: 18%;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($data['projects'] as $project): ?>
                                    <tr data-project-id="<?= $project['id'] ?>">
                                        <td>
                                            <input type="checkbox" data-select-row value="<?= $project['id'] ?>">
                                        </td>
                                        <td>
                                            <div style="display: flex; align-items: flex-start; gap: 1rem;">
                                                <?php if (!empty($project['featured_image'])): ?>
                                                    <img src="<?= htmlspecialchars($project['featured_image']) ?>"
                                                         alt="Project thumbnail"
                                                         style="width: 60px; height: 45px; object-fit: cover; border-radius: var(--admin-border-radius); flex-shrink: 0;">
                                                <?php else: ?>
                                                    <div style="width: 60px; height: 45px; background: var(--admin-bg-secondary); border-radius: var(--admin-border-radius); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                                        <i class="fas fa-image" style="color: var(--admin-text-muted);"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div style="min-width: 0;">
                                                    <h6 style="margin: 0 0 0.25rem 0; font-weight: 600; line-height: 1.3;">
                                                        <a href="/index.php?page=moderate_project_details&id=<?= $project['id'] ?>"
                                                           style="color: var(--admin-text-primary); text-decoration: none;">
                                                            <?= htmlspecialchars($project['title']) ?>
                                                        </a>
                                                    </h6>
                                                    <div style="font-size: 0.75rem; color: var(--admin-text-muted); margin-bottom: 0.25rem;">
                                                        <?= htmlspecialchars(substr($project['description'] ?? '', 0, 80)) ?>...
                                                    </div>
                                                    <?php if (!empty($project['technologies'])): ?>
                                                        <div style="display: flex; gap: 0.25rem; flex-wrap: wrap;">
                                                            <?php foreach (array_slice(explode(',', $project['technologies']), 0, 3) as $tech): ?>
                                                                <span style="background: var(--admin-bg-secondary); color: var(--admin-text-secondary); padding: 0.125rem 0.375rem; border-radius: 0.25rem; font-size: 0.625rem;">
                                                                    <?= htmlspecialchars(trim($tech)) ?>
                                                                </span>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                                <div class="admin-table-avatar" style="background: var(--admin-primary-bg); color: var(--admin-primary);">
                                                    <?= strtoupper(substr($project['author_name'] ?? 'U', 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <span style="font-weight: 500; font-size: 0.875rem;"><?= htmlspecialchars($project['author_name'] ?? 'Unknown') ?></span>
                                                    <div style="font-size: 0.75rem; color: var(--admin-text-muted);">
                                                        <?= htmlspecialchars($project['author_email'] ?? '') ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="admin-badge admin-badge-<?=
                                                $project['moderation_status'] === 'approved' ? 'success' :
                                                ($project['moderation_status'] === 'rejected' ? 'error' :
                                                ($project['moderation_status'] === 'flagged' ? 'warning' : 'gray')) ?>">
                                                <i class="fas fa-<?=
                                                    $project['moderation_status'] === 'approved' ? 'check' :
                                                    ($project['moderation_status'] === 'rejected' ? 'times' :
                                                    ($project['moderation_status'] === 'flagged' ? 'flag' : 'clock')) ?>"></i>
                                                <?= ucfirst($project['moderation_status'] ?? 'pending') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div style="font-size: 0.875rem;">
                                                <?= date('M j, Y', strtotime($project['created_at'])) ?>
                                            </div>
                                            <div style="font-size: 0.75rem; color: var(--admin-text-muted);">
                                                <?= date('g:i A', strtotime($project['created_at'])) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="admin-badge admin-badge-<?=
                                                ($project['priority'] ?? 'normal') === 'high' ? 'error' :
                                                (($project['priority'] ?? 'normal') === 'medium' ? 'warning' : 'gray') ?>">
                                                <?= ucfirst($project['priority'] ?? 'normal') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="admin-table-actions">
                                                <a href="/index.php?page=moderate_project_details&id=<?= $project['id'] ?>"
                                                   class="admin-btn admin-btn-primary admin-btn-sm">
                                                    <i class="fas fa-eye"></i><span>Review</span>
                                                </a>
                                                <?php if ($project['moderation_status'] === 'pending' || $project['moderation_status'] === 'flagged'): ?>
                                                    <button type="button" class="admin-btn admin-btn-success admin-btn-sm"
                                                            onclick="moderateProject(<?= $project['id'] ?>, 'approve')">
                                                        <i class="fas fa-check"></i><span>Approve</span>
                                                    </button>
                                                    <button type="button" class="admin-btn admin-btn-danger admin-btn-sm"
                                                            onclick="moderateProject(<?= $project['id'] ?>, 'reject')">
                                                        <i class="fas fa-times"></i><span>Reject</span>
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
                <!-- Moderation Statistics -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="fas fa-chart-pie"></i>Moderation Stats
                        </h3>
                    </div>
                    <div class="admin-card-body">
                        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-size: 0.875rem; color: var(--admin-text-secondary);">Pending Review</span>
                                <span style="font-size: 0.875rem; color: var(--admin-warning); font-weight: 600;"><?= $data['statistics']['pending_count'] ?? 0 ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-size: 0.875rem; color: var(--admin-text-secondary);">Approved</span>
                                <span style="font-size: 0.875rem; color: var(--admin-success); font-weight: 600;"><?= $data['statistics']['approved_count'] ?? 0 ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-size: 0.875rem; color: var(--admin-text-secondary);">Rejected</span>
                                <span style="font-size: 0.875rem; color: var(--admin-error); font-weight: 600;"><?= $data['statistics']['rejected_count'] ?? 0 ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-size: 0.875rem; color: var(--admin-text-secondary);">Flagged</span>
                                <span style="font-size: 0.875rem; color: var(--admin-warning); font-weight: 600;"><?= $data['statistics']['flagged_count'] ?? 0 ?></span>
                            </div>
                        </div>

                        <!-- Progress Bar -->
                        <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--admin-border);">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                <span style="font-size: 0.875rem; color: var(--admin-text-secondary);">Completion Rate</span>
                                <span style="font-size: 0.875rem; color: var(--admin-text-primary); font-weight: 600;"><?= $data['statistics']['completion_rate'] ?? '0%' ?></span>
                            </div>
                            <div style="background: var(--admin-bg-secondary); height: 8px; border-radius: 4px; overflow: hidden;">
                                <div style="background: var(--admin-success); height: 100%; width: <?= $data['statistics']['completion_rate'] ?? '0%' ?>; transition: all 0.3s;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Filters -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="fas fa-filter"></i>Quick Filters
                        </h3>
                    </div>
                    <div class="admin-card-body">
                        <a href="/index.php?page=moderate_projects&filter=pending" class="admin-btn admin-btn-warning" style="width: 100%; margin-bottom: 0.5rem; justify-content: space-between;">
                            <span><i class="fas fa-clock"></i>Pending Review</span>
                            <span class="admin-badge admin-badge-error"><?= $data['statistics']['pending_count'] ?? 0 ?></span>
                        </a>
                        <a href="/index.php?page=moderate_projects&filter=flagged" class="admin-btn admin-btn-danger" style="width: 100%; margin-bottom: 0.5rem; justify-content: space-between;">
                            <span><i class="fas fa-flag"></i>Flagged</span>
                            <span class="admin-badge admin-badge-warning"><?= $data['statistics']['flagged_count'] ?? 0 ?></span>
                        </a>
                        <a href="/index.php?page=moderate_projects&filter=urgent" class="admin-btn admin-btn-secondary" style="width: 100%; margin-bottom: 0.5rem; justify-content: flex-start;">
                            <i class="fas fa-exclamation-triangle"></i>Urgent Priority
                        </a>
                        <a href="/index.php?page=moderate_projects" class="admin-btn admin-btn-secondary" style="width: 100%; justify-content: flex-start;">
                            <i class="fas fa-list"></i>Show All
                        </a>
                    </div>
                </div>

                <!-- Moderation Tips -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="fas fa-lightbulb"></i>Moderation Tips
                        </h3>
                    </div>
                    <div class="admin-card-body">
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <div>
                                <h4 style="display: flex; align-items: center; margin: 0 0 0.5rem 0; font-size: 0.875rem; font-weight: 600; color: var(--admin-success);">
                                    <i class="fas fa-check" style="margin-right: 0.5rem;"></i>Quality Standards
                                </h4>
                                <p style="font-size: 0.75rem; color: var(--admin-text-secondary); margin: 0;">Check for clear descriptions, proper screenshots, and working demo links.</p>
                            </div>
                            <div>
                                <h4 style="display: flex; align-items: center; margin: 0 0 0.5rem 0; font-size: 0.875rem; font-weight: 600; color: var(--admin-warning);">
                                    <i class="fas fa-exclamation-triangle" style="margin-right: 0.5rem;"></i>Red Flags
                                </h4>
                                <p style="font-size: 0.75rem; color: var(--admin-text-secondary); margin: 0;">Watch for plagiarized content, broken links, or inappropriate material.</p>
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
        // Moderate project function
        async function moderateProject(projectId, action) {
            if (!confirm(`Are you sure you want to ${action} this project?`)) {
                return;
            }

            try {
                const response = await fetch('/index.php?page=api_moderate_project', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        project_id: projectId,
                        action: action
                    })
                });

                const result = await response.json();

                if (result.success) {
                    window.adminPanel.showFlashMessage('success', `Project ${action}d successfully`);
                    // Reload page after short delay
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    window.adminPanel.showFlashMessage('error', result.message || 'Action failed');
                }
            } catch (error) {
                console.error('Moderation action failed:', error);
                window.adminPanel.showFlashMessage('error', 'Action failed. Please try again.');
            }
        }

        // Bulk actions
        function bulkApprove() {
            const selected = getSelectedProjects();
            if (selected.length === 0) {
                alert('Please select projects to approve');
                return;
            }

            if (confirm(`Approve ${selected.length} selected projects?`)) {
                bulkModerate(selected, 'approve');
            }
        }

        function bulkReject() {
            const selected = getSelectedProjects();
            if (selected.length === 0) {
                alert('Please select projects to reject');
                return;
            }

            if (confirm(`Reject ${selected.length} selected projects?`)) {
                bulkModerate(selected, 'reject');
            }
        }

        function getSelectedProjects() {
            return Array.from(document.querySelectorAll('[data-select-row]:checked')).map(cb => cb.value);
        }

        async function bulkModerate(projectIds, action) {
            try {
                const response = await fetch('/index.php?page=api_bulk_moderate_projects', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        project_ids: projectIds,
                        action: action
                    })
                });

                const result = await response.json();

                if (result.success) {
                    window.adminPanel.showFlashMessage('success', `${projectIds.length} projects ${action}d successfully`);
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    window.adminPanel.showFlashMessage('error', result.message || 'Bulk action failed');
                }
            } catch (error) {
                console.error('Bulk moderation failed:', error);
                window.adminPanel.showFlashMessage('error', 'Bulk action failed. Please try again.');
            }
        }

        // Filter change handler
        document.getElementById('statusFilter').addEventListener('change', function() {
            const filter = this.value;
            const url = new URL(window.location);

            if (filter) {
                url.searchParams.set('filter', filter);
            } else {
                url.searchParams.delete('filter');
            }

            window.location.href = url.toString();
        });

        // Update bulk actions visibility
        document.addEventListener('change', function(e) {
            if (e.target.matches('[data-select-row], [data-select-all]')) {
                const selected = getSelectedProjects();
                const bulkActions = document.getElementById('bulkActions');
                bulkActions.style.display = selected.length > 0 ? 'flex' : 'none';
            }
        });
    </script>
