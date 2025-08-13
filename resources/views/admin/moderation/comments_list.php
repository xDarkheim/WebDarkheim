<?php
/**
 * Comments List View for Moderation - MODERN DARK ADMIN INTERFACE
 * Modern dark administrative interface for comment moderation
 */

// Extract data from view context
$comments = $viewData['comments'] ?? [];
$pagination = $viewData['pagination'] ?? [];
$statistics = $viewData['statistics'] ?? [];
$filters = $viewData['filters'] ?? [];
$flashMessages = $viewData['flashMessages'] ?? [];
$currentUser = $viewData['currentUser'] ?? null;
?>

<!DOCTYPE html>
<html lang="en" class="admin-panel">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($viewData['pageTitle'] ?? 'Comments Moderation') ?></title>

    <!-- Admin Dark Theme Styles -->
    <link rel="stylesheet" href="/public/assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="admin-container">

    <!-- Navigation -->
    <nav class="admin-nav">
        <div class="admin-nav-container">
            <a href="/index.php?page=dashboard" class="admin-nav-brand">
                <i class="fas fa-shield-alt"></i>
                <span>Admin Panel</span>
            </a>

            <div class="admin-nav-links">
                <a href="/index.php?page=manage_articles" class="admin-nav-link">
                    <i class="fas fa-newspaper"></i>
                    <span>Articles</span>
                </a>
                <a href="/index.php?page=manage_categories" class="admin-nav-link">
                    <i class="fas fa-tags"></i>
                    <span>Categories</span>
                </a>
                <a href="/index.php?page=manage_users" class="admin-nav-link">
                    <i class="fas fa-users"></i>
                    <span>Users</span>
                </a>
                <a href="/page/admin/moderation/comments.php" class="admin-nav-link" style="background-color: var(--admin-primary-bg); color: var(--admin-primary-light); border-color: var(--admin-primary-border);">
                    <i class="fas fa-gavel"></i>
                    <span>Moderation</span>
                </a>
                <a href="/index.php?page=site_settings" class="admin-nav-link">
                    <i class="fas fa-cogs"></i>
                    <span>Settings</span>
                </a>
                <a href="/index.php?page=dashboard" class="admin-nav-link">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </div>
        </div>
    </nav>

    <!-- Header -->
    <header class="admin-header">
        <div class="admin-header-container">
            <div class="admin-header-content">
                <div class="admin-header-title">
                    <i class="admin-header-icon fas fa-comments"></i>
                    <div class="admin-header-text">
                        <h1>Comment Moderation</h1>
                        <p>Review and moderate user comments on projects and articles</p>
                    </div>
                </div>

                <div class="admin-header-actions">
                    <a href="/page/admin/moderation/dashboard.php" class="admin-btn admin-btn-secondary">
                        <i class="fas fa-tachometer-alt"></i>Moderation Dashboard
                    </a>
                    <a href="/page/admin/moderation/projects.php" class="admin-btn admin-btn-secondary">
                        <i class="fas fa-clipboard-list"></i>Projects
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
                    <?= is_array($message) ? ($message['is_html'] ? $message['text'] : htmlspecialchars($message['text'])) : htmlspecialchars($message) ?>
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

                <!-- Statistics Cards -->
                <?php if (!empty($statistics)): ?>
                <div class="admin-stats-grid">
                    <div class="admin-stat-card admin-glow-primary">
                        <div class="admin-stat-content">
                            <div class="admin-stat-icon" style="background: var(--admin-warning-bg); color: var(--admin-warning);">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="admin-stat-details">
                                <h3>Pending Review</h3>
                                <p><?= htmlspecialchars((string)($statistics['pending'] ?? 0)) ?></p>
                                <span>Comments awaiting moderation</span>
                            </div>
                        </div>
                    </div>

                    <div class="admin-stat-card admin-glow-success">
                        <div class="admin-stat-content">
                            <div class="admin-stat-icon" style="background: var(--admin-success-bg); color: var(--admin-success);">
                                <i class="fas fa-check"></i>
                            </div>
                            <div class="admin-stat-details">
                                <h3>Approved</h3>
                                <p><?= htmlspecialchars((string)($statistics['approved'] ?? 0)) ?></p>
                                <span>Comments approved today</span>
                            </div>
                        </div>
                    </div>

                    <div class="admin-stat-card admin-glow-error">
                        <div class="admin-stat-content">
                            <div class="admin-stat-icon" style="background: var(--admin-error-bg); color: var(--admin-error);">
                                <i class="fas fa-ban"></i>
                            </div>
                            <div class="admin-stat-details">
                                <h3>Spam Blocked</h3>
                                <p><?= htmlspecialchars((string)($statistics['spam'] ?? 0)) ?></p>
                                <span>Spam comments blocked</span>
                            </div>
                        </div>
                    </div>

                    <div class="admin-stat-card">
                        <div class="admin-stat-content">
                            <div class="admin-stat-icon" style="background: var(--admin-info-bg); color: var(--admin-info);">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="admin-stat-details">
                                <h3>Total Comments</h3>
                                <p><?= htmlspecialchars((string)($statistics['total'] ?? 0)) ?></p>
                                <span>All time comments</span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Comments List -->
                <?php if (empty($comments)): ?>
                    <!-- Empty State -->
                    <div class="admin-card admin-glow-primary">
                        <div class="admin-card-body" style="text-align: center; padding: 3rem;">
                            <div style="font-size: 4rem; color: var(--admin-text-muted); margin-bottom: 1rem;">
                                <i class="fas fa-comments"></i>
                            </div>
                            <h3 style="color: var(--admin-text-primary); margin-bottom: 1rem;">No Comments to Moderate</h3>
                            <p style="color: var(--admin-text-muted); margin-bottom: 2rem;">
                                All comments have been reviewed. Check back later for new submissions.
                            </p>
                            <a href="/page/admin/moderation/dashboard.php" class="admin-btn admin-btn-primary admin-btn-lg">
                                <i class="fas fa-tachometer-alt"></i>View Dashboard
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Comments Cards -->
                    <div class="admin-card">
                        <div class="admin-card-header">
                            <h3 class="admin-card-title">
                                <i class="fas fa-comments"></i>Comments for Review
                                <span class="admin-badge admin-badge-warning">
                                    <?= count($comments) ?> Pending
                                </span>
                            </h3>
                        </div>
                        <div class="admin-card-body">
                            <div class="admin-grid admin-grid-cols-1">
                                <?php foreach ($comments as $comment): ?>
                                    <div class="admin-card" style="margin-bottom: 1rem; border: 1px solid var(--admin-border);">
                                        <div class="admin-card-body">
                                            <!-- Comment Header -->
                                            <div style="display: flex; justify-content: between; align-items: flex-start; margin-bottom: 1rem;">
                                                <div style="display: flex; align-items: center; flex: 1;">
                                                    <div style="width: 40px; height: 40px; background: var(--admin-primary-bg); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 0.75rem;">
                                                        <i class="fas fa-user" style="color: var(--admin-primary);"></i>
                                                    </div>
                                                    <div>
                                                        <div style="font-weight: 600; color: var(--admin-text-primary);">
                                                            <?= htmlspecialchars($comment['author_name'] ?? 'Anonymous') ?>
                                                        </div>
                                                        <div style="color: var(--admin-text-secondary); font-size: 0.875rem;">
                                                            <?= htmlspecialchars($comment['author_email'] ?? 'No email') ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div style="display: flex; gap: 0.5rem; align-items: center;">
                                                    <span class="admin-badge admin-badge-gray">
                                                        <i class="fas fa-calendar"></i>
                                                        <?= htmlspecialchars(date("M j, Y", strtotime($comment['created_at'] ?? 'now'))) ?>
                                                    </span>
                                                    <?php
                                                    $status = $comment['status'] ?? 'pending';
                                                    $statusConfig = [
                                                        'pending' => ['class' => 'warning', 'icon' => 'clock'],
                                                        'approved' => ['class' => 'success', 'icon' => 'check'],
                                                        'rejected' => ['class' => 'error', 'icon' => 'times'],
                                                        'spam' => ['class' => 'error', 'icon' => 'ban']
                                                    ];
                                                    $config = $statusConfig[$status] ?? $statusConfig['pending'];
                                                    ?>
                                                    <span class="admin-badge admin-badge-<?= $config['class'] ?>">
                                                        <i class="fas fa-<?= $config['icon'] ?>"></i>
                                                        <?= htmlspecialchars(ucfirst($status)) ?>
                                                    </span>
                                                </div>
                                            </div>

                                            <!-- Comment Content -->
                                            <div style="background: var(--admin-bg-secondary); border-radius: var(--admin-border-radius); padding: 1rem; margin-bottom: 1rem;">
                                                <div style="color: var(--admin-text-primary); line-height: 1.6;">
                                                    <?= nl2br(htmlspecialchars($comment['content'] ?? '')) ?>
                                                </div>
                                            </div>

                                            <!-- Comment Meta -->
                                            <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 1rem; padding: 0.75rem; background: var(--admin-bg-primary); border-radius: var(--admin-border-radius);">
                                                <div style="display: flex; gap: 1rem;">
                                                    <div>
                                                        <span style="color: var(--admin-text-muted); font-size: 0.75rem;">On Project:</span>
                                                        <div style="color: var(--admin-text-primary); font-weight: 500;">
                                                            <?= htmlspecialchars($comment['project_title'] ?? 'Unknown Project') ?>
                                                        </div>
                                                    </div>
                                                    <?php if (!empty($comment['ip_address'])): ?>
                                                    <div>
                                                        <span style="color: var(--admin-text-muted); font-size: 0.75rem;">IP Address:</span>
                                                        <div style="color: var(--admin-text-secondary); font-family: var(--admin-font-mono); font-size: 0.875rem;">
                                                            <?= htmlspecialchars($comment['ip_address']) ?>
                                                        </div>
                                                    </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <!-- Actions -->
                                            <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                                                <?php if ($status === 'pending'): ?>
                                                    <form method="POST" style="display: inline;"
                                                          action="/page/api/moderation/comments.php">
                                                        <input type="hidden" name="action" value="approve">
                                                        <input type="hidden" name="comment_id" value="<?= $comment['id'] ?>">
                                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                                        <button type="submit" class="admin-btn admin-btn-success admin-btn-sm">
                                                            <i class="fas fa-check"></i>Approve
                                                        </button>
                                                    </form>

                                                    <form method="POST" style="display: inline;"
                                                          action="/page/api/moderation/comments.php"
                                                          onsubmit="return confirm('Are you sure you want to reject this comment?');">
                                                        <input type="hidden" name="action" value="reject">
                                                        <input type="hidden" name="comment_id" value="<?= $comment['id'] ?>">
                                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                                        <button type="submit" class="admin-btn admin-btn-danger admin-btn-sm">
                                                            <i class="fas fa-times"></i>Reject
                                                        </button>
                                                    </form>

                                                    <form method="POST" style="display: inline;"
                                                          action="/page/api/moderation/comments.php"
                                                          onsubmit="return confirm('Mark this comment as spam?');">
                                                        <input type="hidden" name="action" value="spam">
                                                        <input type="hidden" name="comment_id" value="<?= $comment['id'] ?>">
                                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                                        <button type="submit" class="admin-btn admin-btn-warning admin-btn-sm">
                                                            <i class="fas fa-ban"></i>Spam
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <button type="button" class="admin-btn admin-btn-secondary admin-btn-sm" disabled>
                                                        <i class="fas fa-lock"></i>Already Reviewed
                                                    </button>
                                                <?php endif; ?>

                                                <button type="button" class="admin-btn admin-btn-secondary admin-btn-sm"
                                                        onclick="viewCommentDetails(<?= $comment['id'] ?>)">
                                                    <i class="fas fa-eye"></i>Details
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <?php if (!empty($pagination) && $pagination['total_pages'] > 1): ?>
                    <div class="admin-card">
                        <div class="admin-card-body">
                            <div style="display: flex; justify-content: between; align-items: center;">
                                <div style="color: var(--admin-text-secondary);">
                                    Showing <?= $pagination['offset'] + 1 ?> to <?= min($pagination['offset'] + $pagination['limit'], $pagination['total']) ?>
                                    of <?= $pagination['total'] ?> comments
                                </div>
                                <div style="display: flex; gap: 0.5rem;">
                                    <?php if ($pagination['current_page'] > 1): ?>
                                        <a href="?page=<?= $pagination['current_page'] - 1 ?>" class="admin-btn admin-btn-secondary admin-btn-sm">
                                            <i class="fas fa-chevron-left"></i>Previous
                                        </a>
                                    <?php endif; ?>

                                    <?php for ($i = max(1, $pagination['current_page'] - 2); $i <= min($pagination['total_pages'], $pagination['current_page'] + 2); $i++): ?>
                                        <a href="?page=<?= $i ?>"
                                           class="admin-btn <?= $i === $pagination['current_page'] ? 'admin-btn-primary' : 'admin-btn-secondary' ?> admin-btn-sm">
                                            <?= $i ?>
                                        </a>
                                    <?php endfor; ?>

                                    <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                                        <a href="?page=<?= $pagination['current_page'] + 1 ?>" class="admin-btn admin-btn-secondary admin-btn-sm">
                                            Next<i class="fas fa-chevron-right"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Admin Scripts -->
    <script src="/public/assets/js/admin.js"></script>

    <script>
        // Comment moderation functions
        function viewCommentDetails(commentId) {
            // Implementation for viewing comment details
            console.log('View comment details:', commentId);
            // You can implement a modal or redirect to details page
        }

        // Auto-refresh for real-time updates
        setTimeout(() => {
            location.reload();
        }, 300000); // Refresh every 5 minutes

        // Bulk actions
        function initializeBulkActions() {
            const selectAllCheckbox = document.querySelector('[data-select-all]');
            const commentCheckboxes = document.querySelectorAll('[data-select-comment]');

            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', () => {
                    commentCheckboxes.forEach(checkbox => {
                        checkbox.checked = selectAllCheckbox.checked;
                    });
                });
            }
        }

        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', initializeBulkActions);
    </script>
</body>
</html>
