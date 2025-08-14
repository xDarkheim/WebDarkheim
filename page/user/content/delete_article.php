<?php
/**
 * Delete Article Page - MODERN DARK ADMIN INTERFACE
 *
 * Modern dark administrative interface for deleting articles
 * with improved UX and consistent styling
 */

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/includes/bootstrap.php';

global $serviceProvider, $flashMessageService;

use App\Application\Components\AdminNavigation;

try {
    $authService = $serviceProvider->getAuth();
} catch (Exception $e) {
    error_log("Critical: Failed to get AuthenticationService: " . $e->getMessage());
    die("System error occurred.");
}

// Check authentication and permissions
if (!$authService->isAuthenticated()) {
    $flashMessageService->addError('Please log in to access this area.');
    header("Location: /index.php?page=login");
    exit();
}

$userRole = $authService->getCurrentUserRole();
if (!in_array($userRole, ['admin', 'employee'])) {
    $flashMessageService->addError('Access denied. Insufficient permissions.');
    header("Location: /index.php?page=dashboard");
    exit();
}

$articleId = $_GET['id'] ?? null;
if (!$articleId) {
    $flashMessageService->addError('Article ID is required.');
    header("Location: /index.php?page=manage_articles");
    exit();
}

$pageTitle = 'Delete Article';

// Create unified navigation
$adminNavigation = new AdminNavigation($authService);

// Get flash messages
$flashMessages = $flashMessageService->getAllMessages();
?>

    <!-- Admin Dark Theme Styles -->
    <link rel="stylesheet" href="/public/assets/css/admin.css">

    <!-- Unified Navigation -->
    <?= $adminNavigation->render() ?>

    <!-- Header -->
    <header class="admin-header">
        <div class="admin-header-container">
            <div class="admin-header-content">
                <div class="admin-header-title">
                    <i class="admin-header-icon fas fa-trash-alt" style="color: var(--admin-error);"></i>
                    <div class="admin-header-text">
                        <h1>Delete Article</h1>
                        <p>Permanently remove article from the system</p>
                    </div>
                </div>

                <div class="admin-header-actions">
                    <a href="/index.php?page=manage_articles" class="admin-btn admin-btn-secondary">
                        <i class="fas fa-arrow-left"></i>Back to Articles
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
        <div class="admin-layout-main" style="max-width: 800px; margin: 0 auto;">
            <div class="admin-content">

                <!-- Critical Warning -->
                <div class="admin-card admin-glow-error">
                    <div class="admin-card-body">
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <div style="color: var(--admin-error); font-size: 3rem;">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div>
                                <h3 style="margin: 0; color: var(--admin-error);">Critical Action Warning</h3>
                                <p style="margin: 0.5rem 0 0 0; color: var(--admin-text-secondary);">
                                    This action cannot be undone. The article and all associated data will be permanently deleted from the system.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Article Information -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="fas fa-info-circle"></i>Article Information
                        </h3>
                    </div>
                    <div class="admin-card-body">
                        <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem;">
                            <div style="width: 64px; height: 64px; background: var(--admin-error-bg); border-radius: var(--admin-border-radius); display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-newspaper" style="color: var(--admin-error); font-size: 1.5rem;"></i>
                            </div>
                            <div>
                                <h4 style="margin: 0; color: var(--admin-text-primary);">Article #<?= htmlspecialchars($articleId) ?></h4>
                                <p style="margin: 0.25rem 0 0 0; color: var(--admin-text-secondary);">
                                    You are about to delete this article permanently
                                </p>
                            </div>
                        </div>

                        <div class="admin-grid admin-grid-cols-2">
                            <div>
                                <div style="margin-bottom: 1rem;">
                                    <div style="color: var(--admin-text-muted); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 0.25rem;">
                                        Article ID
                                    </div>
                                    <div style="color: var(--admin-text-primary); font-weight: 600;">
                                        #<?= htmlspecialchars($articleId) ?>
                                    </div>
                                </div>
                                <div style="margin-bottom: 1rem;">
                                    <div style="color: var(--admin-text-muted); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 0.25rem;">
                                        Current User
                                    </div>
                                    <div style="color: var(--admin-text-primary); font-weight: 500;">
                                        <?= htmlspecialchars($authService->getCurrentUsername()) ?>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <div style="margin-bottom: 1rem;">
                                    <div style="color: var(--admin-text-muted); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 0.25rem;">
                                        Action
                                    </div>
                                    <div style="color: var(--admin-error); font-weight: 600;">
                                        Permanent Deletion
                                    </div>
                                </div>
                                <div style="margin-bottom: 1rem;">
                                    <div style="color: var(--admin-text-muted); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 0.25rem;">
                                        Timestamp
                                    </div>
                                    <div style="color: var(--admin-text-primary); font-weight: 500;">
                                        <?= date('M j, Y g:i A') ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- What Will Be Deleted -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="fas fa-list"></i>Data That Will Be Deleted
                        </h3>
                    </div>
                    <div class="admin-card-body">
                        <div style="display: grid; gap: 1rem;">
                            <div style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem; background: var(--admin-bg-secondary); border-radius: var(--admin-border-radius);">
                                <i class="fas fa-file-alt" style="color: var(--admin-error);"></i>
                                <div>
                                    <div style="color: var(--admin-text-primary); font-weight: 500;">Article Content</div>
                                    <div style="color: var(--admin-text-muted); font-size: 0.875rem;">Title, content, excerpt, and metadata</div>
                                </div>
                            </div>

                            <div style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem; background: var(--admin-bg-secondary); border-radius: var(--admin-border-radius);">
                                <i class="fas fa-comments" style="color: var(--admin-error);"></i>
                                <div>
                                    <div style="color: var(--admin-text-primary); font-weight: 500;">Associated Comments</div>
                                    <div style="color: var(--admin-text-muted); font-size: 0.875rem;">All user comments and replies</div>
                                </div>
                            </div>

                            <div style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem; background: var(--admin-bg-secondary); border-radius: var(--admin-border-radius);">
                                <i class="fas fa-tags" style="color: var(--admin-error);"></i>
                                <div>
                                    <div style="color: var(--admin-text-primary); font-weight: 500;">Category Associations</div>
                                    <div style="color: var(--admin-text-muted); font-size: 0.875rem;">Links to categories and tags</div>
                                </div>
                            </div>

                            <div style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem; background: var(--admin-bg-secondary); border-radius: var(--admin-border-radius);">
                                <i class="fas fa-chart-line" style="color: var(--admin-error);"></i>
                                <div>
                                    <div style="color: var(--admin-text-primary); font-weight: 500;">Analytics Data</div>
                                    <div style="color: var(--admin-text-muted); font-size: 0.875rem;">View counts and engagement metrics</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Confirmation Form -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="fas fa-shield-alt"></i>Confirm Deletion
                        </h3>
                    </div>
                    <div class="admin-card-body">
                        <div style="background: var(--admin-error-bg); border: 1px solid var(--admin-error); border-radius: var(--admin-border-radius); padding: 1rem; margin-bottom: 1.5rem;">
                            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                                <i class="fas fa-exclamation-triangle" style="color: var(--admin-error);"></i>
                                <strong style="color: var(--admin-error);">Confirmation Required</strong>
                            </div>
                            <p style="margin: 0; color: var(--admin-text-primary); font-size: 0.875rem;">
                                To proceed with deletion, please type <strong>DELETE</strong> in the field below and click the confirmation button.
                            </p>
                        </div>

                        <form method="POST" action="/index.php?page=api_delete_article" id="deleteForm">
                            <input type="hidden" name="article_id" value="<?= htmlspecialchars($articleId) ?>">

                            <div class="admin-form-group">
                                <label for="confirmation" class="admin-label admin-label-required">
                                    <i class="fas fa-keyboard"></i>Type DELETE to confirm
                                </label>
                                <input type="text"
                                       id="confirmation"
                                       name="confirmation"
                                       class="admin-input"
                                       placeholder="Type DELETE to enable deletion"
                                       autocomplete="off"
                                       required>
                                <div class="admin-help-text">
                                    This field is case-sensitive. Type exactly: DELETE
                                </div>
                            </div>

                            <div class="admin-form-group">
                                <label for="reason" class="admin-label">
                                    <i class="fas fa-comment"></i>Reason for Deletion (Optional)
                                </label>
                                <textarea id="reason"
                                          name="reason"
                                          class="admin-input"
                                          rows="3"
                                          placeholder="Provide a reason for this deletion (for audit trail)"></textarea>
                                <div class="admin-help-text">
                                    This reason will be logged for administrative records
                                </div>
                            </div>

                            <!-- Form Actions -->
                            <div style="display: flex; gap: 1rem; justify-content: space-between; align-items: center; margin-top: 2rem;">
                                <div>
                                    <span style="color: var(--admin-text-muted); font-size: 0.875rem;">
                                        <i class="fas fa-info-circle"></i>
                                        This action cannot be undone
                                    </span>
                                </div>
                                <div style="display: flex; gap: 1rem;">
                                    <a href="/index.php?page=manage_articles" class="admin-btn admin-btn-secondary">
                                        <i class="fas fa-times"></i>Cancel
                                    </a>
                                    <button type="submit" class="admin-btn admin-btn-danger" id="deleteButton" disabled>
                                        <i class="fas fa-trash-alt"></i>Delete Article Permanently
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Safety Notice -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="fas fa-shield-alt"></i>Safety Information
                        </h3>
                    </div>
                    <div class="admin-card-body">
                        <div class="admin-grid admin-grid-cols-2">
                            <div>
                                <h4 style="color: var(--admin-text-primary); margin-bottom: 0.5rem;">Before Deleting</h4>
                                <ul style="color: var(--admin-text-secondary); margin: 0; padding-left: 1.5rem;">
                                    <li>Consider archiving instead of deleting</li>
                                    <li>Export any important data</li>
                                    <li>Verify this is the correct article</li>
                                    <li>Check for external references</li>
                                </ul>
                            </div>
                            <div>
                                <h4 style="color: var(--admin-text-primary); margin-bottom: 0.5rem;">Alternative Actions</h4>
                                <ul style="color: var(--admin-text-secondary); margin: 0; padding-left: 1.5rem;">
                                    <li>Set article status to "Draft"</li>
                                    <li>Remove from public visibility</li>
                                    <li>Move to archive category</li>
                                    <li>Update content instead</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Admin Scripts -->
    <script src="/public/assets/js/admin.js"></script>

    <script>
        // Enable delete button only when correct confirmation is typed
        const confirmationInput = document.getElementById('confirmation');
        const deleteButton = document.getElementById('deleteButton');
        const deleteForm = document.getElementById('deleteForm');

        confirmationInput.addEventListener('input', function() {
            if (this.value === 'DELETE') {
                deleteButton.disabled = false;
                deleteButton.classList.remove('admin-btn-disabled');
                this.style.borderColor = 'var(--admin-success)';
            } else {
                deleteButton.disabled = true;
                deleteButton.classList.add('admin-btn-disabled');
                this.style.borderColor = 'var(--admin-border)';
            }
        });

        // Final confirmation before form submission
        deleteForm.addEventListener('submit', function(e) {
            if (confirmationInput.value !== 'DELETE') {
                e.preventDefault();
                alert('Please type DELETE in the confirmation field.');
                return false;
            }

            const finalConfirm = confirm(
                'This is your final warning!\n\n' +
                'The article will be permanently deleted and cannot be recovered.\n\n' +
                'Are you absolutely sure you want to proceed?'
            );

            if (!finalConfirm) {
                e.preventDefault();
                return false;
            }

            // Show loading state
            deleteButton.disabled = true;
            deleteButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i>Deleting...';
        });

        // Prevent accidental page refresh
        window.addEventListener('beforeunload', function(e) {
            const confirmationValue = confirmationInput.value;
            if (confirmationValue && confirmationValue !== 'DELETE') {
                e.preventDefault();
                e.returnValue = '';
            }
        });
    </script>
