<?php
/**
 * Portfolio Settings - PHASE 8 - DARK ADMIN THEME
 * Modern portfolio settings interface
 */

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/includes/bootstrap.php';

global $serviceProvider, $flashMessageService, $database_handler;

try {
    $authService = $serviceProvider->getAuth();
} catch (Exception $e) {
    error_log("Critical: Failed to get AuthenticationService: " . $e->getMessage());
    die("System error occurred.");
}

// Check authentication
if (!$authService->isAuthenticated()) {
    $flashMessageService->addError('Please log in to access your portfolio.');
    header("Location: /index.php?page=login");
    exit();
}

// Check if user is client or higher
$current_user_role = $authService->getCurrentUserRole();
if (!in_array($current_user_role, ['client', 'employee', 'admin'])) {
    $flashMessageService->addError('Access denied. Client account required.');
    header("Location: /index.php?page=dashboard");
    exit();
}

$pageTitle = 'Portfolio Settings';
$current_user_id = $authService->getCurrentUserId();

// Get client profile
$stmt = $database_handler->getConnection()->prepare("SELECT * FROM client_profiles WHERE user_id = ?");
$stmt->execute([$current_user_id]);
$profileData = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$profileData) {
    $flashMessageService->addError('Please complete your profile first.');
    header('Location: /index.php?page=profile_edit');
    exit();
}

// Handle settings save
$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'visibility':
                $portfolio_visibility = $_POST['portfolio_visibility'] ?? 'public';
                $allow_contact = isset($_POST['allow_contact']) ? 1 : 0;
                $show_project_stats = isset($_POST['show_project_stats']) ? 1 : 0;

                // Update basic visibility settings
                $stmt = $database_handler->getConnection()->prepare("
                    UPDATE client_profiles 
                    SET portfolio_visibility = ?, allow_contact = ? 
                    WHERE user_id = ?
                ");
                $stmt->execute([$portfolio_visibility, $allow_contact, $current_user_id]);

                $success = true;
                break;

            case 'notifications':
                $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
                $moderation_notifications = isset($_POST['moderation_notifications']) ? 1 : 0;
                $view_notifications = isset($_POST['view_notifications']) ? 1 : 0;
                $comment_notifications = isset($_POST['comment_notifications']) ? 1 : 0;

                // For now, just set success - actual notification system can be implemented later
                $success = true;
                break;

            case 'reset_settings':
                // Reset settings to defaults
                $stmt = $database_handler->getConnection()->prepare("
                    UPDATE client_profiles 
                    SET portfolio_visibility = 'public', allow_contact = 1
                    WHERE user_id = ?
                ");
                $stmt->execute([$current_user_id]);

                $success = true;
                break;
        }

        // Refresh profile data
        $stmt = $database_handler->getConnection()->prepare("SELECT * FROM client_profiles WHERE user_id = ?");
        $stmt->execute([$current_user_id]);
        $profileData = $stmt->fetch(PDO::FETCH_ASSOC);

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get project stats for preview
$projectStats = ['total' => 0, 'total_views' => 0];
if ($profileData) {
    $stmt = $database_handler->getConnection()->prepare("SELECT COUNT(*) as total FROM client_portfolio WHERE client_profile_id = ?");
    $stmt->execute([$profileData['id']]);
    $projectStats['total'] = $stmt->fetchColumn();
}

// Get flash messages
$flashMessages = $flashMessageService->getAllMessages();
?>

    <link rel="stylesheet" href="/public/assets/css/admin.css">
    <style>
        .settings-nav {
            position: sticky;
            top: 2rem;
        }
        .settings-nav .admin-card-body {
            padding: 0;
        }
        .settings-nav-item {
            display: block;
            padding: 0.75rem 1rem;
            color: var(--admin-text-secondary);
            text-decoration: none;
            border-bottom: 1px solid var(--admin-border);
            transition: var(--admin-transition);
        }
        .settings-nav-item:last-child {
            border-bottom: none;
        }
        .settings-nav-item:hover,
        .settings-nav-item.active {
            background: var(--admin-bg-secondary);
            color: var(--admin-text-primary);
        }
        .settings-nav-item i {
            margin-right: 0.5rem;
            width: 16px;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
    </style>

<div class="admin-container">
    <!-- Navigation -->
    <nav class="admin-nav">
        <div class="admin-nav-container">
            <a href="/index.php?page=dashboard" class="admin-nav-brand">
                <i class="fas fa-briefcase"></i>
                Portfolio Management
            </a>
            <div class="admin-nav-links">
                <a href="/index.php?page=dashboard" class="admin-nav-link">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="/index.php?page=user_portfolio" class="admin-nav-link">
                    <i class="fas fa-briefcase"></i> Portfolio
                </a>
                <a href="/index.php?page=user_profile" class="admin-nav-link">
                    <i class="fas fa-user"></i> Profile
                </a>
            </div>
        </div>
    </nav>

    <!-- Header -->
    <header class="admin-header">
        <div class="admin-header-container">
            <div class="admin-header-content">
                <div class="admin-header-title">
                    <i class="admin-header-icon fas fa-cog"></i>
                    <div class="admin-header-text">
                        <h1>Portfolio Settings</h1>
                        <p>Manage your portfolio visibility and preferences</p>
                    </div>
                </div>
                <div class="admin-header-actions">
                    <a href="/index.php?page=user_portfolio" class="admin-btn admin-btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Portfolio
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Flash Messages -->
    <?php if (!empty($flashMessages) || $success || $error): ?>
        <div class="admin-flash-messages">
            <?php if ($success): ?>
                <div class="admin-flash-message admin-flash-success">
                    <i class="fas fa-check-circle"></i>
                    <div>Settings saved successfully!</div>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="admin-flash-message admin-flash-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <div><?= htmlspecialchars($error) ?></div>
                </div>
            <?php endif; ?>

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
        <!-- Settings Navigation -->
        <div class="admin-sidebar">
            <div class="admin-card settings-nav">
                <div class="admin-card-header">
                    <h5 class="admin-card-title">
                        <i class="fas fa-list"></i>
                        Settings Sections
                    </h5>
                </div>
                <div class="admin-card-body">
                    <a href="#visibility" class="settings-nav-item active" data-tab="visibility">
                        <i class="fas fa-eye"></i>
                        Visibility & Privacy
                    </a>
                    <a href="#notifications" class="settings-nav-item" data-tab="notifications">
                        <i class="fas fa-bell"></i>
                        Notifications
                    </a>
                    <a href="#advanced" class="settings-nav-item" data-tab="advanced">
                        <i class="fas fa-cogs"></i>
                        Advanced Settings
                    </a>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h6 class="admin-card-title" style="font-size: 0.875rem;">
                        <i class="fas fa-chart-line"></i>
                        Portfolio Stats
                    </h6>
                </div>
                <div class="admin-card-body">
                    <div class="admin-stats-grid" style="grid-template-columns: 1fr 1fr;">
                        <div style="text-align: center;">
                            <div style="font-size: 1.5rem; font-weight: 700; color: var(--admin-primary); margin-bottom: 0.25rem;">
                                <?= $projectStats['total'] ?>
                            </div>
                            <small style="color: var(--admin-text-muted);">Projects</small>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 1.5rem; font-weight: 700; color: var(--admin-success); margin-bottom: 0.25rem;">
                                <?= number_format($projectStats['total_views']) ?>
                            </div>
                            <small style="color: var(--admin-text-muted);">Views</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="admin-content">
            <!-- Visibility Settings -->
            <div class="tab-content active" id="visibility-tab">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h5 class="admin-card-title">
                            <i class="fas fa-eye"></i>
                            Portfolio Visibility Settings
                        </h5>
                    </div>
                    <div class="admin-card-body">
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="visibility">

                            <div class="admin-form-group">
                                <label class="admin-label">Portfolio Visibility</label>
                                <div class="admin-grid admin-grid-cols-2">
                                    <div>
                                        <div style="display: flex; align-items: flex-start; gap: 0.75rem; padding: 1rem; border: 2px solid var(--admin-border); border-radius: var(--admin-border-radius); margin-bottom: 1rem; <?= $profileData['portfolio_visibility'] === 'public' ? 'border-color: var(--admin-primary); background: var(--admin-primary-bg);' : '' ?>">
                                            <input type="radio" name="portfolio_visibility" id="visibility_public" value="public"
                                                   style="margin-top: 0.125rem;"
                                                   <?= $profileData['portfolio_visibility'] === 'public' ? 'checked' : '' ?>>
                                            <div style="flex: 1;">
                                                <label for="visibility_public" style="margin: 0; color: var(--admin-text-primary); font-weight: 600; cursor: pointer;">
                                                    <i class="fas fa-globe" style="color: var(--admin-success); margin-right: 0.5rem;"></i>
                                                    Public Portfolio
                                                </label>
                                                <div style="color: var(--admin-text-muted); font-size: 0.875rem; margin-top: 0.25rem;">
                                                    Portfolio visible to all visitors and search engines
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <div style="display: flex; align-items: flex-start; gap: 0.75rem; padding: 1rem; border: 2px solid var(--admin-border); border-radius: var(--admin-border-radius); <?= $profileData['portfolio_visibility'] === 'private' ? 'border-color: var(--admin-primary); background: var(--admin-primary-bg);' : '' ?>">
                                            <input type="radio" name="portfolio_visibility" id="visibility_private" value="private"
                                                   style="margin-top: 0.125rem;"
                                                   <?= $profileData['portfolio_visibility'] === 'private' ? 'checked' : '' ?>>
                                            <div style="flex: 1;">
                                                <label for="visibility_private" style="margin: 0; color: var(--admin-text-primary); font-weight: 600; cursor: pointer;">
                                                    <i class="fas fa-lock" style="color: var(--admin-warning); margin-right: 0.5rem;"></i>
                                                    Private Portfolio
                                                </label>
                                                <div style="color: var(--admin-text-muted); font-size: 0.875rem; margin-top: 0.25rem;">
                                                    Portfolio visible only to you
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="admin-form-group">
                                <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                                    <input type="checkbox" name="allow_contact" id="allow_contact"
                                           style="margin-top: 0.25rem;"
                                           <?= $profileData['allow_contact'] ? 'checked' : '' ?>>
                                    <div style="flex: 1;">
                                        <label for="allow_contact" style="margin: 0; color: var(--admin-text-primary); font-weight: 500;">
                                            <i class="fas fa-envelope" style="color: var(--admin-info); margin-right: 0.5rem;"></i>
                                            Allow Contact Messages
                                        </label>
                                        <div class="admin-help-text" style="margin-top: 0.25rem;">
                                            Visitors can contact you through a contact form on your portfolio
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="admin-form-group">
                                <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                                    <input type="checkbox" name="show_project_stats" id="show_project_stats"
                                           style="margin-top: 0.25rem;" checked>
                                    <div style="flex: 1;">
                                        <label for="show_project_stats" style="margin: 0; color: var(--admin-text-primary); font-weight: 500;">
                                            <i class="fas fa-chart-bar" style="color: var(--admin-primary); margin-right: 0.5rem;"></i>
                                            Show Project Statistics
                                        </label>
                                        <div class="admin-help-text" style="margin-top: 0.25rem;">
                                            Display view counts and engagement metrics on your projects
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="admin-btn admin-btn-primary">
                                <i class="fas fa-save"></i> Save Visibility Settings
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Notification Settings -->
            <div class="tab-content" id="notifications-tab">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h5 class="admin-card-title">
                            <i class="fas fa-bell"></i>
                            Notification Preferences
                        </h5>
                    </div>
                    <div class="admin-card-body">
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="notifications">

                            <div class="admin-form-group">
                                <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                                    <input type="checkbox" name="email_notifications" id="email_notifications"
                                           style="margin-top: 0.25rem;" checked>
                                    <div style="flex: 1;">
                                        <label for="email_notifications" style="margin: 0; color: var(--admin-text-primary); font-weight: 500;">
                                            <i class="fas fa-envelope" style="color: var(--admin-primary); margin-right: 0.5rem;"></i>
                                            Email Notifications
                                        </label>
                                        <div class="admin-help-text" style="margin-top: 0.25rem;">
                                            Receive notifications about portfolio activity via email
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="admin-form-group">
                                <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                                    <input type="checkbox" name="moderation_notifications" id="moderation_notifications"
                                           style="margin-top: 0.25rem;" checked>
                                    <div style="flex: 1;">
                                        <label for="moderation_notifications" style="margin: 0; color: var(--admin-text-primary); font-weight: 500;">
                                            <i class="fas fa-shield-alt" style="color: var(--admin-warning); margin-right: 0.5rem;"></i>
                                            Moderation Notifications
                                        </label>
                                        <div class="admin-help-text" style="margin-top: 0.25rem;">
                                            Get notified when your projects are approved or rejected by moderators
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="admin-form-group">
                                <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                                    <input type="checkbox" name="view_notifications" id="view_notifications"
                                           style="margin-top: 0.25rem;">
                                    <div style="flex: 1;">
                                        <label for="view_notifications" style="margin: 0; color: var(--admin-text-primary); font-weight: 500;">
                                            <i class="fas fa-eye" style="color: var(--admin-success); margin-right: 0.5rem;"></i>
                                            View Milestone Notifications
                                        </label>
                                        <div class="admin-help-text" style="margin-top: 0.25rem;">
                                            Get notified when your projects reach view milestones (100, 500, 1000+ views)
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="admin-form-group">
                                <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                                    <input type="checkbox" name="comment_notifications" id="comment_notifications"
                                           style="margin-top: 0.25rem;" checked>
                                    <div style="flex: 1;">
                                        <label for="comment_notifications" style="margin: 0; color: var(--admin-text-primary); font-weight: 500;">
                                            <i class="fas fa-comments" style="color: var(--admin-info); margin-right: 0.5rem;"></i>
                                            Comment Notifications
                                        </label>
                                        <div class="admin-help-text" style="margin-top: 0.25rem;">
                                            Get notified when someone comments on your projects
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="admin-btn admin-btn-primary">
                                <i class="fas fa-save"></i> Save Notification Settings
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Advanced Settings -->
            <div class="tab-content" id="advanced-tab">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h5 class="admin-card-title">
                            <i class="fas fa-cogs"></i>
                            Advanced Settings
                        </h5>
                    </div>
                    <div class="admin-card-body">
                        <!-- Public portfolio link -->
                        <div class="admin-form-group">
                            <label class="admin-label">Your Portfolio Link</label>
                            <div style="display: flex; gap: 0.5rem;">
                                <input type="text" class="admin-input" id="portfolio-link"
                                       value="<?= (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] ?>/index.php?page=public_client_portfolio&client_id=<?= $profileData['id'] ?>"
                                       readonly style="flex: 1;">
                                <button type="button" class="admin-btn admin-btn-secondary" onclick="copyToClipboard()">
                                    <i class="fas fa-copy"></i> Copy
                                </button>
                            </div>
                            <div class="admin-help-text">Share this link to showcase your portfolio publicly</div>
                        </div>

                        <!-- Export portfolio -->
                        <div class="admin-form-group">
                            <label class="admin-label">Export Portfolio Data</label>
                            <div style="display: flex; gap: 0.5rem;">
                                <button type="button" class="admin-btn admin-btn-secondary admin-btn-sm">
                                    <i class="fas fa-download"></i> Export as JSON
                                </button>
                                <button type="button" class="admin-btn admin-btn-secondary admin-btn-sm">
                                    <i class="fas fa-file-pdf"></i> Export as PDF
                                </button>
                            </div>
                            <div class="admin-help-text">Download your portfolio data for backup or migration</div>
                        </div>

                        <!-- Reset settings -->
                        <div class="admin-form-group">
                            <label class="admin-label" style="color: var(--admin-error);">Danger Zone</label>
                            <div style="border: 2px solid var(--admin-error); border-radius: var(--admin-border-radius); padding: 1rem; background: var(--admin-error-bg);">
                                <div style="margin-bottom: 1rem;">
                                    <strong style="color: var(--admin-error-light);">Reset All Settings</strong>
                                    <div style="color: var(--admin-error-light); font-size: 0.875rem; margin-top: 0.25rem;">
                                        This will restore all portfolio settings to their default values
                                    </div>
                                </div>
                                <form method="POST" action="" style="display: inline;"
                                      onsubmit="return confirm('Are you sure you want to reset all portfolio settings to defaults? This action cannot be undone.')">
                                    <input type="hidden" name="action" value="reset_settings">
                                    <button type="submit" class="admin-btn admin-btn-danger admin-btn-sm">
                                        <i class="fas fa-undo"></i> Reset to Default Settings
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="/public/assets/js/admin.js"></script>
<script>
// Tab switching
document.addEventListener('DOMContentLoaded', function() {
    const tabLinks = document.querySelectorAll('.settings-nav-item');
    const tabContents = document.querySelectorAll('.tab-content');

    tabLinks.forEach(function(tabLink) {
        tabLink.addEventListener('click', function(e) {
            e.preventDefault();

            const targetTab = this.getAttribute('data-tab');

            // Update active nav item
            tabLinks.forEach(link => link.classList.remove('active'));
            this.classList.add('active');

            // Show target tab content
            tabContents.forEach(content => content.classList.remove('active'));
            document.getElementById(targetTab + '-tab').classList.add('active');

            // Update URL hash
            window.location.hash = targetTab;
        });
    });

    // Auto-switch to tab from URL hash
    if (window.location.hash) {
        const hashTab = window.location.hash.substring(1);
        const targetLink = document.querySelector(`[data-tab="${hashTab}"]`);
        if (targetLink) {
            targetLink.click();
        }
    }

    // Auto-dismiss flash messages
    setTimeout(function() {
        document.querySelectorAll('.admin-flash-message').forEach(function(msg) {
            msg.style.opacity = '0';
            setTimeout(() => msg.remove(), 300);
        });
    }, 5000);
});

// Copy to clipboard function
function copyToClipboard() {
    const portfolioLink = document.getElementById('portfolio-link');
    portfolioLink.select();
    portfolioLink.setSelectionRange(0, 99999); // For mobile devices

    try {
        document.execCommand('copy');
        window.adminPanel.showFlashMessage('success', 'Portfolio link copied to clipboard!');
    } catch (err) {
        window.adminPanel.showFlashMessage('error', 'Failed to copy link. Please select and copy manually.');
    }
}
</script>

