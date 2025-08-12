<?php
/**
 * Portfolio Settings
 */

require_once __DIR__ . '/../../../includes/bootstrap.php';

// Use global services from the architecture
global $database_handler, $flashMessageService, $container;

use App\Application\Core\ServiceProvider;

// Get ServiceProvider instance
$serviceProvider = ServiceProvider::getInstance($container);
$authService = $serviceProvider->getAuth();

// Check authentication
if (!$authService->isAuthenticated()) {
    $flashMessageService->addError('Please log in to access your portfolio.');
    header("Location: /page/auth/login.php");
    exit();
}

// Check if user is client or higher
$current_user_role = $authService->getCurrentUserRole();
if (!in_array($current_user_role, ['client', 'employee', 'admin'])) {
    $flashMessageService->addError('Access denied. Client account required.');
    header("Location: /page/user/dashboard.php");
    exit();
}

$pageTitle = 'Portfolio Settings';
$current_user_id = $authService->getCurrentUserId();

// Get client profile
$stmt = $database_handler->prepare("SELECT * FROM client_profiles WHERE user_id = ?");
$stmt->execute([$current_user_id]);
$profileData = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$profileData) {
    $flashMessageService->addError('Please complete your profile first.');
    header('Location: /page/user/profile/');
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
                $stmt = $database_handler->prepare("
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
                $stmt = $database_handler->prepare("
                    UPDATE client_profiles 
                    SET portfolio_visibility = 'public', allow_contact = 1
                    WHERE user_id = ?
                ");
                $stmt->execute([$current_user_id]);

                $success = true;
                break;
        }

        // Refresh profile data
        $stmt = $database_handler->prepare("SELECT * FROM client_profiles WHERE user_id = ?");
        $stmt->execute([$current_user_id]);
        $profileData = $stmt->fetch(PDO::FETCH_ASSOC);

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get project stats for preview
$projectStats = ['total' => 0, 'total_views' => 0];
if ($profileData) {
    $stmt = $database_handler->prepare("SELECT COUNT(*) as total FROM client_portfolio WHERE client_profile_id = ?");
    $stmt->execute([$profileData['id']]);
    $projectStats['total'] = $stmt->fetchColumn();
}

include __DIR__ . '/../../../resources/views/_header.php';
?>

<div class="container mt-4">
    <!-- Breadcrumbs -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/page/user/dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="/page/user/portfolio/">Portfolio</a></li>
            <li class="breadcrumb-item active">Settings</li>
        </ol>
    </nav>

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="fas fa-cog text-primary me-2"></i>
            Portfolio Settings
        </h1>
        <a href="/page/user/portfolio/" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>
            Back to Portfolio
        </a>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            Settings saved successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Settings Navigation -->
        <div class="col-lg-3 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-list me-2"></i>
                        Settings Sections
                    </h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="#visibility" class="list-group-item list-group-item-action active" data-bs-toggle="tab">
                        <i class="fas fa-eye me-2"></i>
                        Visibility
                    </a>
                    <a href="#notifications" class="list-group-item list-group-item-action" data-bs-toggle="tab">
                        <i class="fas fa-bell me-2"></i>
                        Notifications
                    </a>
                    <a href="#advanced" class="list-group-item list-group-item-action" data-bs-toggle="tab">
                        <i class="fas fa-cogs me-2"></i>
                        Advanced
                    </a>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-chart-line me-2"></i>
                        Quick Stats
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="text-primary h4 mb-0"><?= $projectStats['total'] ?></div>
                            <small class="text-muted">Projects</small>
                        </div>
                        <div class="col-6">
                            <div class="text-success h4 mb-0"><?= number_format($projectStats['total_views']) ?></div>
                            <small class="text-muted">Views</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-lg-9">
            <div class="tab-content">
                <!-- Visibility Settings -->
                <div class="tab-pane fade show active" id="visibility">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-eye me-2"></i>
                                Portfolio Visibility Settings
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="visibility">

                                <div class="mb-4">
                                    <label class="form-label fw-bold">Portfolio Visibility</label>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="portfolio_visibility"
                                                       id="visibility_public" value="public"
                                                       <?= $profileData['portfolio_visibility'] === 'public' ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="visibility_public">
                                                    <strong>Public</strong>
                                                    <small class="text-muted d-block">Portfolio visible to all visitors</small>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="portfolio_visibility"
                                                       id="visibility_private" value="private"
                                                       <?= $profileData['portfolio_visibility'] === 'private' ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="visibility_private">
                                                    <strong>Private</strong>
                                                    <small class="text-muted d-block">Portfolio visible only to you</small>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="allow_contact"
                                               id="allow_contact" <?= $profileData['allow_contact'] ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="allow_contact">
                                            <strong>Allow Contact</strong>
                                            <small class="text-muted d-block">Visitors can contact you through contact form</small>
                                        </label>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="show_project_stats"
                                               id="show_project_stats" checked>
                                        <label class="form-check-label" for="show_project_stats">
                                            <strong>Show Project Statistics</strong>
                                            <small class="text-muted d-block">Display view counts on projects</small>
                                        </label>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>
                                    Save Visibility Settings
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Notification Settings -->
                <div class="tab-pane fade" id="notifications">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-bell me-2"></i>
                                Notification Settings
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="notifications">

                                <div class="mb-4">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="email_notifications"
                                               id="email_notifications" checked>
                                        <label class="form-check-label" for="email_notifications">
                                            <strong>Email Notifications</strong>
                                            <small class="text-muted d-block">Receive notifications via email</small>
                                        </label>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="moderation_notifications"
                                               id="moderation_notifications" checked>
                                        <label class="form-check-label" for="moderation_notifications">
                                            <strong>Moderation Notifications</strong>
                                            <small class="text-muted d-block">Notifications about project approval/rejection</small>
                                        </label>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="view_notifications"
                                               id="view_notifications">
                                        <label class="form-check-label" for="view_notifications">
                                            <strong>View Milestone Notifications</strong>
                                            <small class="text-muted d-block">Notifications when reaching view milestones (100, 500, 1000+)</small>
                                        </label>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="comment_notifications"
                                               id="comment_notifications" checked>
                                        <label class="form-check-label" for="comment_notifications">
                                            <strong>Comment Notifications</strong>
                                            <small class="text-muted d-block">Notifications about new project comments</small>
                                        </label>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>
                                    Save Notification Settings
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Advanced Settings -->
                <div class="tab-pane fade" id="advanced">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-cogs me-2"></i>
                                Advanced Settings
                            </h5>
                        </div>
                        <div class="card-body">
                            <!-- Public portfolio link -->
                            <div class="mb-4">
                                <label class="form-label fw-bold">Your Portfolio Link</label>
                                <div class="input-group">
                                    <input type="text" class="form-control"
                                           value="<?= (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] ?>/page/public/client/portfolio.php?client_id=<?= $profileData['id'] ?>"
                                           readonly>
                                    <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard(this.previousElementSibling.value)">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                                <div class="form-text">Share this link to showcase your portfolio</div>
                            </div>

                            <!-- Reset settings -->
                            <div class="mb-4">
                                <label class="form-label fw-bold text-danger">Reset Settings</label>
                                <div>
                                    <form method="POST" action="" class="d-inline"
                                          onsubmit="return confirm('Are you sure you want to reset all portfolio settings to defaults?')">
                                        <input type="hidden" name="action" value="reset_settings">
                                        <button type="submit" class="btn btn-outline-danger btn-sm">
                                            <i class="fas fa-undo me-1"></i>
                                            Reset to Default Settings
                                        </button>
                                    </form>
                                    <div class="form-text">Restore all portfolio settings to their default values</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Copy to clipboard function
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        // Show success notification
        const toast = document.createElement('div');
        toast.className = 'toast align-items-center text-white bg-success border-0 position-fixed top-0 end-0 m-3';
        toast.setAttribute('role', 'alert');
        toast.style.zIndex = '9999';
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-check me-2"></i>
                    Link copied to clipboard!
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        document.body.appendChild(toast);

        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();

        // Remove element after hiding
        toast.addEventListener('hidden.bs.toast', function() {
            document.body.removeChild(toast);
        });
    });
}

// Initialize tabs
document.addEventListener('DOMContentLoaded', function() {
    // Handle tab switching
    const tabLinks = document.querySelectorAll('[data-bs-toggle="tab"]');
    tabLinks.forEach(function(tabLink) {
        tabLink.addEventListener('shown.bs.tab', function(e) {
            // Update active state in navigation
            tabLinks.forEach(link => link.classList.remove('active'));
            e.target.classList.add('active');
        });
    });

    // Auto-switch to tab from URL hash
    if (window.location.hash) {
        const targetTab = document.querySelector(`[href="${window.location.hash}"]`);
        if (targetTab) {
            const tab = new bootstrap.Tab(targetTab);
            tab.show();
        }
    }
});
</script>

<?php include __DIR__ . '/../../../resources/views/_footer.php'; ?>
