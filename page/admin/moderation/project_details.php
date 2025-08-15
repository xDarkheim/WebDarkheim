<?php

/**
 * Project Details Moderation Page - DARK ADMIN THEME
 * Detailed view for moderating individual portfolio projects
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

// Get global services from DI container
global $container;


use App\Application\Controllers\ModerationController;
use App\Application\Core\ServiceProvider;
use App\Application\Components\AdminNavigation;

try {
    // Get ServiceProvider for accessing services
    $serviceProvider = ServiceProvider::getInstance($container);

    // Get required services
    $authService = $serviceProvider->getAuth();
    $flashService = $serviceProvider->getFlashMessage();
    $logger = $serviceProvider->getLogger();
    $database = $serviceProvider->getDatabase();

    // Get project ID from request
    $projectId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if (!$projectId) {
        throw new Exception('Project ID is required');
    }

    // Create moderation controller
    $moderationController = new ModerationController(
        $database,
        $authService,
        $flashService,
        $logger
    );

    // Handle request
    $data = $moderationController->handleProjectDetails($projectId);

    // Get flash messages
    $flashMessages = $moderationController->getFlashMessages();

    // Set page title
    $pageTitle = 'Project Details - ' . ($data['project']['title'] ?? 'Unknown') . ' - Admin Panel';

    // Create unified navigation
    $adminNavigation = new AdminNavigation($authService);

} catch (Exception $e) {
    // Handle critical errors
    if (isset($logger)) {
        $logger->critical('Critical error in project details moderation page: ' . $e->getMessage());
    }

    $data = [
        'error' => 'System temporarily unavailable. Please try again later.',
        'project' => null,
        'comments' => [],
        'moderation_history' => []
    ];
    $flashMessages = [];

    // Still create navigation even on error
    try {
        $serviceProvider = ServiceProvider::getInstance($container);
        $authService = $serviceProvider->getAuth();
        $adminNavigation = new AdminNavigation($authService);
    } catch (Exception $navException) {
        $adminNavigation = null;
    }
}

?>

    <!-- Admin Dark Theme Styles -->
    <link rel="stylesheet" href="/public/assets/css/admin.css">

    <!-- Unified Navigation -->
    <?php if (isset($adminNavigation)): ?>
        <?= $adminNavigation->render() ?>
    <?php endif; ?>

    <!-- Header -->
    <header class="admin-header">
        <div class="admin-header-container">
            <div class="admin-header-content">
                <div class="admin-header-title">
                    <i class="admin-header-icon fas fa-eye"></i>
                    <div class="admin-header-text">
                        <h1>Project Review</h1>
                        <p><?= isset($data['project']) ? htmlspecialchars($data['project']['title']) : 'Project Details' ?></p>
                    </div>
                </div>

                <div class="admin-header-actions">
                    <?php if (isset($data['project']) && ($data['project']['moderation_status'] === 'pending' || $data['project']['moderation_status'] === 'flagged')): ?>
                        <button type="button" class="admin-btn admin-btn-success" onclick="moderateProject(<?= $data['project']['id'] ?>, 'approve')">
                            <i class="fas fa-check"></i>Approve Project
                        </button>
                        <button type="button" class="admin-btn admin-btn-danger" onclick="moderateProject(<?= $data['project']['id'] ?>, 'reject')">
                            <i class="fas fa-times"></i>Reject Project
                        </button>
                        <button type="button" class="admin-btn admin-btn-warning" onclick="showReasonModal()">
                            <i class="fas fa-flag"></i>Flag Project
                        </button>
                    <?php endif; ?>
                    <a href="/index.php?page=moderate_projects" class="admin-btn admin-btn-secondary">
                        <i class="fas fa-arrow-left"></i>Back to List
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
                        <a href="/index.php?page=moderate_projects" class="admin-btn admin-btn-primary">
                            <i class="fas fa-arrow-left"></i>Back to Projects
                        </a>
                    </div>
                </div>
                <?php elseif (!isset($data['project'])): ?>
                <!-- Not Found State -->
                <div class="admin-card">
                    <div class="admin-card-body" style="text-align: center; padding: 3rem;">
                        <div style="font-size: 4rem; color: var(--admin-text-muted); margin-bottom: 1rem;">
                            <i class="fas fa-search"></i>
                        </div>
                        <h3 style="color: var(--admin-text-primary); margin-bottom: 1rem;">Project Not Found</h3>
                        <p style="color: var(--admin-text-muted); margin-bottom: 2rem;">
                            The project you're looking for doesn't exist or has been removed.
                        </p>
                        <a href="/index.php?page=moderate_projects" class="admin-btn admin-btn-primary">
                            <i class="fas fa-arrow-left"></i>Back to Projects
                        </a>
                    </div>
                </div>
                <?php else: ?>

                <!-- Project Overview -->
                <div class="admin-card admin-glow-<?= $data['project']['moderation_status'] === 'approved' ? 'success' : ($data['project']['moderation_status'] === 'rejected' ? 'error' : 'warning') ?>">
                    <div class="admin-card-header">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <h3 class="admin-card-title">
                                <i class="fas fa-folder-open"></i>Project Overview
                            </h3>
                            <span class="admin-badge admin-badge-<?=
                                $data['project']['moderation_status'] === 'approved' ? 'success' :
                                ($data['project']['moderation_status'] === 'rejected' ? 'error' :
                                ($data['project']['moderation_status'] === 'flagged' ? 'warning' : 'gray')) ?>">
                                <i class="fas fa-<?=
                                    $data['project']['moderation_status'] === 'approved' ? 'check' :
                                    ($data['project']['moderation_status'] === 'rejected' ? 'times' :
                                    ($data['project']['moderation_status'] === 'flagged' ? 'flag' : 'clock')) ?>"></i>
                                <?= ucfirst($data['project']['moderation_status'] ?? 'pending') ?>
                            </span>
                        </div>
                    </div>
                    <div class="admin-card-body">
                        <div class="admin-grid admin-grid-cols-1">
                            <!-- Project Header -->
                            <div style="display: flex; gap: 2rem; margin-bottom: 2rem;">
                                <?php if (!empty($data['project']['featured_image'])): ?>
                                    <img src="<?= htmlspecialchars($data['project']['featured_image']) ?>"
                                         alt="Project featured image"
                                         style="width: 200px; height: 150px; object-fit: cover; border-radius: var(--admin-border-radius); flex-shrink: 0;">
                                <?php else: ?>
                                    <div style="width: 200px; height: 150px; background: var(--admin-bg-secondary); border-radius: var(--admin-border-radius); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                        <i class="fas fa-image" style="color: var(--admin-text-muted); font-size: 3rem;"></i>
                                    </div>
                                <?php endif; ?>

                                <div style="flex-grow: 1;">
                                    <h2 style="margin: 0 0 1rem 0; color: var(--admin-text-primary); font-size: 1.5rem; font-weight: 700;">
                                        <?= htmlspecialchars($data['project']['title']) ?>
                                    </h2>

                                    <div style="display: flex; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem;">
                                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                                            <div class="admin-table-avatar" style="background: var(--admin-primary-bg); color: var(--admin-primary);">
                                                <?= strtoupper(substr($data['project']['author_name'] ?? 'U', 0, 1)) ?>
                                            </div>
                                            <span style="font-weight: 500;"><?= htmlspecialchars($data['project']['author_name'] ?? 'Unknown') ?></span>
                                        </div>

                                        <div style="display: flex; align-items: center; gap: 0.5rem; color: var(--admin-text-muted);">
                                            <i class="fas fa-calendar"></i>
                                            <span><?= date('M j, Y', strtotime($data['project']['created_at'])) ?></span>
                                        </div>

                                        <div style="display: flex; align-items: center; gap: 0.5rem; color: var(--admin-text-muted);">
                                            <i class="fas fa-eye"></i>
                                            <span><?= $data['project']['view_count'] ?? 0 ?> views</span>
                                        </div>
                                    </div>

                                    <?php if (!empty($data['project']['technologies'])): ?>
                                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                            <?php foreach (explode(',', $data['project']['technologies']) as $tech): ?>
                                                <span style="background: var(--admin-primary-bg); color: var(--admin-primary); padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500;">
                                                    <?= htmlspecialchars(trim($tech)) ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Project Description -->
                            <div style="background: var(--admin-bg-secondary); padding: 1.5rem; border-radius: var(--admin-border-radius); border-left: 4px solid var(--admin-primary);">
                                <h4 style="margin: 0 0 1rem 0; color: var(--admin-text-primary); font-weight: 600;">
                                    <i class="fas fa-align-left"></i> Project Description
                                </h4>
                                <div style="color: var(--admin-text-primary); line-height: 1.6; white-space: pre-wrap;">
                                    <?= htmlspecialchars($data['project']['description']) ?>
                                </div>
                            </div>

                            <!-- Project Links -->
                            <?php if (!empty($data['project']['demo_url']) || !empty($data['project']['source_url'])): ?>
                            <div>
                                <h4 style="margin: 0 0 1rem 0; color: var(--admin-text-primary); font-weight: 600;">
                                    <i class="fas fa-link"></i> Project Links
                                </h4>
                                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                                    <?php if (!empty($data['project']['demo_url'])): ?>
                                        <a href="<?= htmlspecialchars($data['project']['demo_url']) ?>"
                                           target="_blank"
                                           class="admin-btn admin-btn-primary">
                                            <i class="fas fa-external-link-alt"></i>Live Demo
                                        </a>
                                    <?php endif; ?>
                                    <?php if (!empty($data['project']['source_url'])): ?>
                                        <a href="<?= htmlspecialchars($data['project']['source_url']) ?>"
                                           target="_blank"
                                           class="admin-btn admin-btn-secondary">
                                            <i class="fas fa-code"></i>Source Code
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Moderation Notes -->
                            <?php if (!empty($data['project']['moderation_reason'])): ?>
                            <div style="background: var(--admin-warning-bg); padding: 1.5rem; border-radius: var(--admin-border-radius); border-left: 4px solid var(--admin-warning);">
                                <h4 style="margin: 0 0 1rem 0; color: var(--admin-warning); font-weight: 600;">
                                    <i class="fas fa-info-circle"></i> Moderation Notes
                                </h4>
                                <div style="color: var(--admin-text-primary); line-height: 1.6;">
                                    <?= htmlspecialchars($data['project']['moderation_reason']) ?>
                                </div>
                                <div style="margin-top: 1rem; font-size: 0.75rem; color: var(--admin-text-muted);">
                                    Added on <?= date('M j, Y g:i A', strtotime($data['project']['moderated_at'])) ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Project Comments -->
                <?php if (!empty($data['comments'])): ?>
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="fas fa-comments"></i>Project Comments (<?= count($data['comments']) ?>)
                        </h3>
                    </div>
                    <div class="admin-card-body">
                        <?php foreach ($data['comments'] as $comment): ?>
                            <div style="border-bottom: 1px solid var(--admin-border); padding: 1rem 0;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem;">
                                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                                        <div class="admin-table-avatar" style="background: var(--admin-info-bg); color: var(--admin-info);">
                                            <?= strtoupper(substr($comment['author_name'] ?? 'A', 0, 1)) ?>
                                        </div>
                                        <div>
                                            <h6 style="margin: 0; font-weight: 600; color: var(--admin-text-primary);">
                                                <?= htmlspecialchars($comment['author_name'] ?? 'Anonymous') ?>
                                            </h6>
                                            <div style="font-size: 0.75rem; color: var(--admin-text-muted);">
                                                <?= date('M j, Y g:i A', strtotime($comment['created_at'])) ?>
                                            </div>
                                        </div>
                                    </div>
                                    <span class="admin-badge admin-badge-<?=
                                        ($comment['moderation_status'] ?? 'pending') === 'approved' ? 'success' :
                                        (($comment['moderation_status'] ?? 'pending') === 'rejected' ? 'error' : 'gray') ?>">
                                        <?= ucfirst($comment['moderation_status'] ?? 'pending') ?>
                                    </span>
                                </div>
                                <div style="background: var(--admin-bg-secondary); padding: 1rem; border-radius: var(--admin-border-radius); color: var(--admin-text-primary); line-height: 1.5;">
                                    <?= htmlspecialchars($comment['content']) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="admin-card-footer">
                        <a href="/index.php?page=moderate_comments&project_id=<?= $data['project']['id'] ?>" class="admin-btn admin-btn-secondary">
                            <i class="fas fa-comments"></i>Manage All Comments
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Moderation History -->
                <?php if (!empty($data['moderation_history'])): ?>
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="fas fa-history"></i>Moderation History
                        </h3>
                    </div>
                    <div class="admin-card-body">
                        <?php foreach ($data['moderation_history'] as $history): ?>
                            <div style="display: flex; gap: 1rem; padding: 1rem 0; border-bottom: 1px solid var(--admin-border);">
                                <div style="display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 50%; background: var(--admin-<?= $history['action'] === 'approved' ? 'success' : ($history['action'] === 'rejected' ? 'error' : 'warning') ?>-bg); color: var(--admin-<?= $history['action'] === 'approved' ? 'success' : ($history['action'] === 'rejected' ? 'error' : 'warning') ?>);">
                                    <i class="fas fa-<?= $history['action'] === 'approved' ? 'check' : ($history['action'] === 'rejected' ? 'times' : 'flag') ?>"></i>
                                </div>
                                <div style="flex-grow: 1;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.25rem;">
                                        <h6 style="margin: 0; font-weight: 600; color: var(--admin-text-primary);">
                                            Project <?= ucfirst($history['action']) ?>
                                        </h6>
                                        <span style="font-size: 0.75rem; color: var(--admin-text-muted);">
                                            <?= date('M j, Y g:i A', strtotime($history['created_at'])) ?>
                                        </span>
                                    </div>
                                    <div style="font-size: 0.875rem; color: var(--admin-text-secondary); margin-bottom: 0.25rem;">
                                        by <?= htmlspecialchars($history['moderator_name'] ?? 'System') ?>
                                    </div>
                                    <?php if (!empty($history['reason'])): ?>
                                        <div style="font-size: 0.875rem; color: var(--admin-text-primary); font-style: italic;">
                                            "<?= htmlspecialchars($history['reason']) ?>"
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <aside class="admin-sidebar">
                <!-- Project Stats -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="fas fa-chart-bar"></i>Project Stats
                        </h3>
                    </div>
                    <div class="admin-card-body">
                        <?php if (isset($data['project'])): ?>
                        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-size: 0.875rem; color: var(--admin-text-secondary);">Views</span>
                                <span style="font-size: 0.875rem; color: var(--admin-text-primary); font-weight: 600;"><?= $data['project']['view_count'] ?? 0 ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-size: 0.875rem; color: var(--admin-text-secondary);">Comments</span>
                                <span style="font-size: 0.875rem; color: var(--admin-text-primary); font-weight: 600;"><?= count($data['comments'] ?? []) ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-size: 0.875rem; color: var(--admin-text-secondary);">Created</span>
                                <span style="font-size: 0.875rem; color: var(--admin-text-primary); font-weight: 600;"><?= date('M j, Y', strtotime($data['project']['created_at'])) ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-size: 0.875rem; color: var(--admin-text-secondary);">Priority</span>
                                <span class="admin-badge admin-badge-<?= ($data['project']['priority'] ?? 'normal') === 'high' ? 'error' : (($data['project']['priority'] ?? 'normal') === 'medium' ? 'warning' : 'gray') ?>">
                                    <?= ucfirst($data['project']['priority'] ?? 'normal') ?>
                                </span>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Author Info -->
                <?php if (isset($data['project'])): ?>
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="fas fa-user"></i>Author Info
                        </h3>
                    </div>
                    <div class="admin-card-body">
                        <div style="text-align: center; margin-bottom: 1rem;">
                            <div class="admin-table-avatar" style="width: 64px; height: 64px; margin: 0 auto 1rem auto; font-size: 1.5rem;">
                                <?= strtoupper(substr($data['project']['author_name'] ?? 'U', 0, 1)) ?>
                            </div>
                            <h6 style="margin: 0 0 0.25rem 0; color: var(--admin-text-primary); font-weight: 600;">
                                <?= htmlspecialchars($data['project']['author_name'] ?? 'Unknown') ?>
                            </h6>
                            <div style="font-size: 0.875rem; color: var(--admin-text-muted);">
                                <?= htmlspecialchars($data['project']['author_email'] ?? '') ?>
                            </div>
                        </div>

                        <a href="/index.php?page=user_profile&id=<?= $data['project']['author_id'] ?>" class="admin-btn admin-btn-primary" style="width: 100%; margin-bottom: 0.5rem; justify-content: flex-start;">
                            <i class="fas fa-user"></i>View Profile
                        </a>
                        <a href="/index.php?page=moderate_projects&author=<?= $data['project']['author_id'] ?>" class="admin-btn admin-btn-secondary" style="width: 100%; justify-content: flex-start;">
                            <i class="fas fa-folder"></i>Other Projects
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Quick Actions -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="fas fa-bolt"></i>Quick Actions
                        </h3>
                    </div>
                    <div class="admin-card-body">
                        <a href="/index.php?page=moderate_projects" class="admin-btn admin-btn-secondary" style="width: 100%; margin-bottom: 0.5rem; justify-content: flex-start;">
                            <i class="fas fa-arrow-left"></i>Back to Projects
                        </a>
                        <a href="/index.php?page=moderate_comments" class="admin-btn admin-btn-secondary" style="width: 100%; margin-bottom: 0.5rem; justify-content: flex-start;">
                            <i class="fas fa-comments"></i>All Comments
                        </a>
                        <a href="/index.php?page=admin_moderation_dashboard" class="admin-btn admin-btn-secondary" style="width: 100%; justify-content: flex-start;">
                            <i class="fas fa-tachometer-alt"></i>Dashboard
                        </a>
                    </div>
                </div>
            </aside>
        </div>
    </main>

    <!-- Moderation Reason Modal -->
    <div id="reasonModal" class="admin-modal admin-hidden" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div class="admin-card" style="width: 90%; max-width: 500px; margin: 0;">
            <div class="admin-card-header">
                <h3 class="admin-card-title">
                    <i class="fas fa-flag"></i>Flag Project
                </h3>
            </div>
            <div class="admin-card-body">
                <div class="admin-form-group">
                    <label class="admin-label">Reason for flagging</label>
                    <label for="flagReason"></label><textarea id="flagReason" class="admin-input admin-textarea" rows="4"
                                                              placeholder="Explain why this project needs attention..."></textarea>
                </div>
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" class="admin-btn admin-btn-secondary" data-modal-close>Cancel</button>
                    <button type="button" class="admin-btn admin-btn-warning" onclick="flagProject()">Flag Project</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Admin Scripts -->
    <script type="module" src="/public/assets/js/admin.js"></script>
    <script>
        // Project moderation functions
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
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    window.adminPanel.showFlashMessage('error', result.message || 'Action failed');
                }
            } catch (error) {
                console.error('Project moderation failed:', error);
                window.adminPanel.showFlashMessage('error', 'Action failed. Please try again.');
            }
        }

        function showReasonModal() {
            document.getElementById('flagReason').value = '';
            window.adminPanel.openModal('reasonModal');
        }

        async function flagProject() {
            const reason = document.getElementById('flagReason').value.trim();

            if (!reason) {
                alert('Please enter a reason for flagging this project');
                return;
            }

            const projectId = <?= isset($data['project']) ? $data['project']['id'] : 'null' ?>;

            try {
                const response = await fetch('/index.php?page=api_moderate_project', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        project_id: projectId,
                        action: 'flag',
                        reason: reason
                    })
                });

                const result = await response.json();

                if (result.success) {
                    window.adminPanel.showFlashMessage('success', 'Project flagged successfully');
                    window.adminPanel.closeModal(document.getElementById('reasonModal'));
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    window.adminPanel.showFlashMessage('error', result.message || 'Failed to flag project');
                }
            } catch (error) {
                console.error('Flag project failed:', error);
                window.adminPanel.showFlashMessage('error', 'Failed to flag project. Please try again.');
            }
        }
    </script>
