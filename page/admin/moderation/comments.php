<?php

/**
 * Comments Moderation - Updated to use unified AdminNavigation and FlashMessage
 */

declare(strict_types=1);

// Get global services
global $container, $serviceProvider, $flashMessageService;

use App\Application\Components\AdminNavigation;
use App\Application\Controllers\ModerationController;
use App\Application\Core\ServiceProvider;

// Create unified navigation
$adminNavigation = new AdminNavigation($serviceProvider->getAuth());

try {
    // Get ServiceProvider for accessing services
    $serviceProvider = ServiceProvider::getInstance($container);

    // Get required services
    $authService = $serviceProvider->getAuth();
    $logger = $serviceProvider->getLogger();
    $database = $serviceProvider->getDatabase();

    // Use global FlashMessage service instead of creating new one
    if (!isset($flashMessageService)) {
        error_log("Critical: FlashMessageService not available in comments moderation");
        die("A critical system error occurred. Please try again later.");
    }

    // Create moderation controller with global FlashMessage service
    $moderationController = new ModerationController(
        $database,
        $authService,
        $flashMessageService, // Use global service
        $logger
    );

    // Handle request
    $data = $moderationController->handleCommentsModeration();

    // Get flash messages from global service
    $flashMessages = $flashMessageService->getAllMessages();

    // Set page title
    $pageTitle = 'Comments Moderation - Admin Panel';

} catch (Exception $e) {
    // Handle critical errors
    if (isset($logger)) {
        $logger->critical('Critical error in comments moderation page: ' . $e->getMessage());
    }

    // Use global FlashMessage service for errors
    if (isset($flashMessageService)) {
        $flashMessageService->addError('System temporarily unavailable. Please try again later.');
    }

    $data = ['error' => true];
    $flashMessages = $flashMessageService->getAllMessages() ?? [];
}
?>

    <!-- Admin Styles -->
    <link rel="stylesheet" href="/public/assets/css/admin.css">
    <link rel="stylesheet" href="/public/assets/css/admin-navigation.css">

    <!-- Unified Navigation -->
    <?= $adminNavigation->render() ?>

    <!-- Header -->
    <header class="admin-header">
        <div class="admin-header-container">
            <div class="admin-header-content">
                <div class="admin-header-title">
                    <i class="admin-header-icon fas fa-comments"></i>
                    <div class="admin-header-text">
                        <h1>Comments Moderation</h1>
                        <p>Review and moderate user comments on projects</p>
                    </div>
                </div>

                <div class="admin-header-actions">
                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                        <!-- Filter Dropdown -->
                        <label for="statusFilter"></label><select id="statusFilter" class="admin-input admin-select" style="width: auto; min-width: 120px;">
                            <option value="">All Status</option>
                            <option value="pending" <?= ($_GET['filter'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="approved" <?= ($_GET['filter'] ?? '') === 'approved' ? 'selected' : '' ?>>Approved</option>
                            <option value="rejected" <?= ($_GET['filter'] ?? '') === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                            <option value="spam" <?= ($_GET['filter'] ?? '') === 'spam' ? 'selected' : '' ?>>Spam</option>
                        </select>

                        <!-- Search -->
                        <label for="searchComments"></label><input type="text" id="searchComments" class="admin-input" placeholder="Search comments..."
                                                                   data-search-target=".comment-item" style="width: 200px;">

                        <!-- Bulk Actions -->
                        <div class="admin-btn-group" style="display: none;" id="bulkActions">
                            <button type="button" class="admin-btn admin-btn-success admin-btn-sm" onclick="bulkApprove()">
                                <i class="fas fa-check"></i>Approve
                            </button>
                            <button type="button" class="admin-btn admin-btn-danger admin-btn-sm" onclick="bulkReject()">
                                <i class="fas fa-times"></i>Reject
                            </button>
                            <button type="button" class="admin-btn admin-btn-warning admin-btn-sm" onclick="bulkSpam()">
                                <i class="fas fa-exclamation-triangle"></i>Mark Spam
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
                <?php elseif (empty($data['comments'])): ?>
                <!-- Empty State -->
                <div class="admin-card">
                    <div class="admin-card-body" style="text-align: center; padding: 3rem;">
                        <div style="font-size: 4rem; color: var(--admin-text-muted); margin-bottom: 1rem;">
                            <i class="fas fa-comments"></i>
                        </div>
                        <h3 style="color: var(--admin-text-primary); margin-bottom: 1rem;">No Comments to Review</h3>
                        <p style="color: var(--admin-text-muted); margin-bottom: 2rem;">
                            All comments have been reviewed. Great job keeping the community clean!
                        </p>
                        <a href="/index.php?page=admin_moderation_dashboard" class="admin-btn admin-btn-primary">
                            <i class="fas fa-tachometer-alt"></i>Back to Dashboard
                        </a>
                    </div>
                </div>
                <?php else: ?>

                <!-- Comments List -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="fas fa-list"></i>User Comments (<?= count($data['comments']) ?>)
                        </h3>
                    </div>
                    <div class="admin-card-body">
                        <?php foreach ($data['comments'] as $comment): ?>
                            <div class="comment-item admin-card" style="margin-bottom: 1rem; padding: 0;" data-comment-id="<?= $comment['id'] ?>"
                                 data-status="<?= $comment['moderation_status'] ?? 'pending' ?>">
                                <div class="admin-card-header" style="padding: 1rem 1.5rem;">
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                        <div style="display: flex; align-items: center; gap: 1rem; flex-grow: 1;">
                                            <label>
                                                <input type="checkbox" data-select-row value="<?= $comment['id'] ?>" style="margin: 0;">
                                            </label>

                                            <div class="admin-table-avatar" style="background: var(--admin-info-bg); color: var(--admin-info);">
                                                <?= strtoupper(substr($comment['author_name'] ?? 'A', 0, 1)) ?>
                                            </div>

                                            <div style="flex-grow: 1; min-width: 0;">
                                                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                                                    <h6 style="margin: 0; font-weight: 600; color: var(--admin-text-primary);">
                                                        <?= htmlspecialchars($comment['author_name'] ?? 'Anonymous') ?>
                                                    </h6>
                                                    <span style="font-size: 0.75rem; color: var(--admin-text-muted);">
                                                        commented on
                                                    </span>
                                                    <?php if (!empty($comment['project_id']) && !empty($comment['project_title'])): ?>
                                                        <a href="/index.php?page=moderate_project_details&id=<?= $comment['project_id'] ?>"
                                                           style="font-size: 0.875rem; color: var(--admin-primary); text-decoration: none; font-weight: 500;">
                                                            <?= htmlspecialchars($comment['project_title']) ?>
                                                        </a>
                                                    <?php else: ?>
                                                        <span style="font-size: 0.875rem; color: var(--admin-text-muted); font-weight: 500;">
                                                            <?= ucfirst($comment['commentable_type'] ?? 'Unknown Item') ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <div style="font-size: 0.75rem; color: var(--admin-text-muted);">
                                                    <?= date('M j, Y g:i A', strtotime($comment['created_at'])) ?>
                                                    <?php if (!empty($comment['author_email'])): ?>
                                                        • <?= htmlspecialchars($comment['author_email']) ?>
                                                    <?php endif; ?>
                                                    <?php if (!empty($comment['ip_address'])): ?>
                                                        • IP: <?= htmlspecialchars($comment['ip_address']) ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>

                                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                                            <span class="admin-badge admin-badge-<?=
                                                ($comment['moderation_status'] ?? 'pending') === 'approved' ? 'success' :
                                                (($comment['moderation_status'] ?? 'pending') === 'rejected' ? 'error' :
                                                (($comment['moderation_status'] ?? 'pending') === 'spam' ? 'warning' : 'gray')) ?>">
                                                <i class="fas fa-<?=
                                                    ($comment['moderation_status'] ?? 'pending') === 'approved' ? 'check' :
                                                    (($comment['moderation_status'] ?? 'pending') === 'rejected' ? 'times' :
                                                    (($comment['moderation_status'] ?? 'pending') === 'spam' ? 'exclamation-triangle' : 'clock')) ?>"></i>
                                                <?= ucfirst($comment['moderation_status'] ?? 'pending') ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="admin-card-body" style="padding: 1rem 1.5rem;">
                                    <div style="background: var(--admin-bg-secondary); padding: 1rem; border-radius: var(--admin-border-radius); margin-bottom: 1rem; border-left: 3px solid var(--admin-primary);">
                                        <p style="margin: 0; color: var(--admin-text-primary); line-height: 1.6; white-space: pre-wrap;">
                                            <?= htmlspecialchars($comment['content']) ?>
                                        </p>
                                    </div>

                                    <?php if (!empty($comment['moderation_reason'])): ?>
                                        <div style="background: var(--admin-warning-bg); padding: 0.75rem; border-radius: var(--admin-border-radius); margin-bottom: 1rem; border-left: 3px solid var(--admin-warning);">
                                            <h6 style="margin: 0 0 0.25rem 0; color: var(--admin-warning); font-size: 0.875rem; font-weight: 600;">
                                                <i class="fas fa-info-circle"></i> Moderation Note
                                            </h6>
                                            <p style="margin: 0; color: var(--admin-text-primary); font-size: 0.875rem;">
                                                <?= htmlspecialchars($comment['moderation_reason']) ?>
                                            </p>
                                        </div>
                                    <?php endif; ?>

                                    <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 1rem; border-top: 1px solid var(--admin-border);">
                                        <div style="display: flex; gap: 0.5rem;">
                                            <?php if (($comment['moderation_status'] ?? 'pending') === 'pending' || ($comment['moderation_status'] ?? 'pending') === 'flagged'): ?>
                                                <button type="button" class="admin-btn admin-btn-success admin-btn-sm"
                                                        onclick="moderateComment(<?= $comment['id'] ?>, 'approve')">
                                                    <i class="fas fa-check"></i>Approve
                                                </button>
                                                <button type="button" class="admin-btn admin-btn-danger admin-btn-sm"
                                                        onclick="moderateComment(<?= $comment['id'] ?>, 'reject')">
                                                    <i class="fas fa-times"></i>Reject
                                                </button>
                                                <button type="button" class="admin-btn admin-btn-warning admin-btn-sm"
                                                        onclick="moderateComment(<?= $comment['id'] ?>, 'spam')">
                                                    <i class="fas fa-exclamation-triangle"></i>Spam
                                                </button>
                                            <?php endif; ?>

                                            <button type="button" class="admin-btn admin-btn-secondary admin-btn-sm"
                                                    onclick="showReasonModal(<?= $comment['id'] ?>)">
                                                <i class="fas fa-edit"></i>Add Note
                                            </button>
                                        </div>

                                        <div style="display: flex; gap: 0.5rem; align-items: center;">
                                            <?php if (!empty($comment['user_id'])): ?>
                                                <a href="/index.php?page=user_profile&id=<?= $comment['user_id'] ?>"
                                                   class="admin-btn admin-btn-secondary admin-btn-sm">
                                                    <i class="fas fa-user"></i>Profile
                                                </a>
                                            <?php endif; ?>
                                            <?php if (!empty($comment['project_id'])): ?>
                                                <a href="/index.php?page=moderate_project_details&id=<?= $comment['project_id'] ?>"
                                                   class="admin-btn admin-btn-primary admin-btn-sm">
                                                    <i class="fas fa-external-link-alt"></i>Project
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
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
                            <i class="fas fa-chart-pie"></i>Comment Stats
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
                                <span style="font-size: 0.875rem; color: var(--admin-text-secondary);">Spam</span>
                                <span style="font-size: 0.875rem; color: var(--admin-warning); font-weight: 600;"><?= $data['statistics']['spam_count'] ?? 0 ?></span>
                            </div>
                        </div>

                        <!-- Today's Activity -->
                        <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--admin-border);">
                            <h4 style="margin: 0 0 0.75rem 0; color: var(--admin-text-primary); font-size: 0.875rem; font-weight: 600;">Today's Activity</h4>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                <span style="font-size: 0.75rem; color: var(--admin-text-secondary);">New Comments</span>
                                <span style="font-size: 0.75rem; color: var(--admin-text-primary); font-weight: 600;"><?= $data['statistics']['comments_today'] ?? 0 ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-size: 0.75rem; color: var(--admin-text-secondary);">Reviewed</span>
                                <span style="font-size: 0.75rem; color: var(--admin-text-primary); font-weight: 600;"><?= $data['statistics']['reviewed_today'] ?? 0 ?></span>
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
                        <a href="/index.php?page=moderate_comments&filter=pending" class="admin-btn admin-btn-warning" style="width: 100%; margin-bottom: 0.5rem; justify-content: space-between;">
                            <span><i class="fas fa-clock"></i>Pending Review</span>
                            <span class="admin-badge admin-badge-error"><?= $data['statistics']['pending_count'] ?? 0 ?></span>
                        </a>
                        <a href="/index.php?page=moderate_comments&filter=spam" class="admin-btn admin-btn-danger" style="width: 100%; margin-bottom: 0.5rem; justify-content: space-between;">
                            <span><i class="fas fa-exclamation-triangle"></i>Marked as Spam</span>
                            <span class="admin-badge admin-badge-warning"><?= $data['statistics']['spam_count'] ?? 0 ?></span>
                        </a>
                        <a href="/index.php?page=moderate_comments&filter=today" class="admin-btn admin-btn-secondary" style="width: 100%; margin-bottom: 0.5rem; justify-content: flex-start;">
                            <i class="fas fa-calendar-day"></i>Today's Comments
                        </a>
                        <a href="/index.php?page=moderate_comments" class="admin-btn admin-btn-secondary" style="width: 100%; justify-content: flex-start;">
                            <i class="fas fa-list"></i>Show All
                        </a>
                    </div>
                </div>

                <!-- Spam Detection Tips -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="fas fa-shield-alt"></i>Spam Detection
                        </h3>
                    </div>
                    <div class="admin-card-body">
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <div>
                                <h4 style="display: flex; align-items: center; margin: 0 0 0.5rem 0; font-size: 0.875rem; font-weight: 600; color: var(--admin-error);">
                                    <i class="fas fa-exclamation-triangle" style="margin-right: 0.5rem;"></i>Red Flags
                                </h4>
                                <ul style="font-size: 0.75rem; color: var(--admin-text-secondary); margin: 0; padding-left: 1rem;">
                                    <li>Multiple external links</li>
                                    <li>Generic promotional content</li>
                                    <li>Irrelevant to project topic</li>
                                    <li>Repeated across projects</li>
                                </ul>
                            </div>
                            <div>
                                <h4 style="display: flex; align-items: center; margin: 0 0 0.5rem 0; font-size: 0.875rem; font-weight: 600; color: var(--admin-success);">
                                    <i class="fas fa-check" style="margin-right: 0.5rem;"></i>Legitimate Signs
                                </h4>
                                <ul style="font-size: 0.75rem; color: var(--admin-text-secondary); margin: 0; padding-left: 1rem;">
                                    <li>Specific project feedback</li>
                                    <li>Technical questions</li>
                                    <li>Constructive criticism</li>
                                    <li>User has profile history</li>
                                </ul>
                            </div>
                        </div>
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
                    <i class="fas fa-edit"></i>Add Moderation Note
                </h3>
            </div>
            <div class="admin-card-body">
                <div class="admin-form-group">
                    <label class="admin-label">Reason/Note</label>
                    <label for="moderationReason"></label><textarea id="moderationReason" class="admin-input admin-textarea" rows="4"
                                                                    placeholder="Explain the reason for this moderation decision..."></textarea>
                </div>
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" class="admin-btn admin-btn-secondary" data-modal-close>Cancel</button>
                    <button type="button" class="admin-btn admin-btn-primary" onclick="saveReason()">Save Note</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Admin Scripts -->
    <script src="/public/assets/js/admin.js"></script>
    <script>
        let currentCommentId = null;

        // Comment moderation functions
        async function moderateComment(commentId, action) {
            if (!confirm(`Are you sure you want to ${action} this comment?`)) {
                return;
            }

            try {
                const response = await fetch('/index.php?page=api_moderate_comment', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        comment_id: commentId,
                        action: action
                    })
                });

                const result = await response.json();

                if (result.success) {
                    window.adminPanel.showFlashMessage('success', `Comment ${action}d successfully`);
                    updateCommentStatus(commentId, action);
                } else {
                    window.adminPanel.showFlashMessage('error', result.message || 'Action failed');
                }
            } catch (error) {
                console.error('Comment moderation failed:', error);
                window.adminPanel.showFlashMessage('error', 'Action failed. Please try again.');
            }
        }

        function updateCommentStatus(commentId, status) {
            const commentElement = document.querySelector(`[data-comment-id="${commentId}"]`);
            if (commentElement) {
                const badge = commentElement.querySelector('.admin-badge');
                const actionButtons = commentElement.querySelectorAll('.admin-btn-success, .admin-btn-danger, .admin-btn-warning');

                // Update badge
                badge.className = `admin-badge admin-badge-${status === 'approve' ? 'success' : (status === 'reject' ? 'error' : 'warning')}`;
                badge.innerHTML = `<i class="fas fa-${status === 'approve' ? 'check' : (status === 'reject' ? 'times' : 'exclamation-triangle')}"></i>${status === 'approve' ? 'Approved' : (status === 'reject' ? 'Rejected' : 'Spam')}`;

                // Hide action buttons
                actionButtons.forEach(btn => btn.style.display = 'none');
            }
        }

        // Bulk actions
        function bulkApprove() {
            const selected = getSelectedComments();
            if (selected.length === 0) {
                alert('Please select comments to approve');
                return;
            }

            if (confirm(`Approve ${selected.length} selected comments?`)) {
                bulkModerate(selected, 'approve');
            }
        }

        function bulkReject() {
            const selected = getSelectedComments();
            if (selected.length === 0) {
                alert('Please select comments to reject');
                return;
            }

            if (confirm(`Reject ${selected.length} selected comments?`)) {
                bulkModerate(selected, 'reject');
            }
        }

        function bulkSpam() {
            const selected = getSelectedComments();
            if (selected.length === 0) {
                alert('Please select comments to mark as spam');
                return;
            }

            if (confirm(`Mark ${selected.length} selected comments as spam?`)) {
                bulkModerate(selected, 'spam');
            }
        }

        function getSelectedComments() {
            return Array.from(document.querySelectorAll('[data-select-row]:checked')).map(cb => cb.value);
        }

        async function bulkModerate(commentIds, action) {
            try {
                const response = await fetch('/index.php?page=api_bulk_moderate_comments', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        comment_ids: commentIds,
                        action: action
                    })
                });

                const result = await response.json();

                if (result.success) {
                    window.adminPanel.showFlashMessage('success', `${commentIds.length} comments ${action}d successfully`);
                    commentIds.forEach(id => updateCommentStatus(id, action));
                } else {
                    window.adminPanel.showFlashMessage('error', result.message || 'Bulk action failed');
                }
            } catch (error) {
                console.error('Bulk comment moderation failed:', error);
                window.adminPanel.showFlashMessage('error', 'Bulk action failed. Please try again.');
            }
        }

        // Reason modal functions
        function showReasonModal(commentId) {
            currentCommentId = commentId;
            document.getElementById('moderationReason').value = '';
            window.adminPanel.openModal('reasonModal');
        }

        async function saveReason() {
            const reason = document.getElementById('moderationReason').value.trim();

            if (!reason) {
                alert('Please enter a reason or note');
                return;
            }

            try {
                const response = await fetch('/index.php?page=api_comment_add_note', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        comment_id: currentCommentId,
                        reason: reason
                    })
                });

                const result = await response.json();

                if (result.success) {
                    window.adminPanel.showFlashMessage('success', 'Note added successfully');
                    window.adminPanel.closeModal(document.getElementById('reasonModal'));
                    setTimeout(() => location.reload(), 1000);
                } else {
                    window.adminPanel.showFlashMessage('error', result.message || 'Failed to add note');
                }
            } catch (error) {
                console.error('Add note failed:', error);
                window.adminPanel.showFlashMessage('error', 'Failed to add note. Please try again.');
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
            if (e.target.matches('[data-select-row]')) {
                const selected = getSelectedComments();
                const bulkActions = document.getElementById('bulkActions');
                bulkActions.style.display = selected.length > 0 ? 'flex' : 'none';
            }
        });

        // Search functionality
        document.getElementById('searchComments').addEventListener('input', function() {
            const query = this.value.toLowerCase();
            const comments = document.querySelectorAll('.comment-item');

            comments.forEach(comment => {
                const text = comment.textContent.toLowerCase();
                comment.style.display = text.includes(query) ? 'block' : 'none';
            });
        });
    </script>
