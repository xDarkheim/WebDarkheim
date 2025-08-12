<?php

/**
 * Profile Settings Page
 *
 * This page allows users to manage their account settings, including
 * preferences, privacy settings, and account configuration.
 *
 * @author Dmytro Hovenko
 */

use App\Application\Controllers\ProfileController;
use App\Domain\Interfaces\TokenManagerInterface;

// Use global services from the new DI architecture
global $flashMessageService, $database_handler, $container, $serviceProvider;

// Get AuthenticationService instead of direct SessionManager access
try {
    $authService = $serviceProvider->getAuth();
} catch (Exception $e) {
    error_log("Critical: Failed to get AuthenticationService instance in settings.php: " . $e->getMessage());
    die("A critical system error occurred. Please try again later.");
}

// Check authentication via AuthenticationService
if (!$authService->isAuthenticated()) {
    $flashMessageService->addError('Please log in to access your settings.');
    header("Location: /index.php?page=login");
    exit();
}

// Get user data via AuthenticationService
$current_user_id = $authService->getCurrentUserId();
$current_user_role = $authService->getCurrentUserRole();
$current_username = $authService->getCurrentUsername();
$userData_from_auth = $authService->getCurrentUser();

// Check required services
if (!isset($flashMessageService)) {
    error_log("Critical: FlashMessageService not available in settings.php");
    die("A critical system error occurred. Please try again later.");
}

if (!isset($database_handler)) {
    error_log("Critical: Database handler not available in settings.php");
    $flashMessageService->addError("Database system not available. Please try again later.");
    header('Location: /index.php?page=dashboard');
    exit;
}

if (!isset($container)) {
    error_log("Critical: Container not available in settings.php");
    $flashMessageService->addError("System error occurred. Please try again later.");
    header('Location: /index.php?page=dashboard');
    exit;
}

$userId = $current_user_id;

// Create ProfileController to get user data
try {
    $profileController = new ProfileController(
        $database_handler,
        $userId,
        $flashMessageService,
        $container->make(TokenManagerInterface::class)
    );
} catch (Exception $e) {
    error_log("Critical: Failed to create ProfileController in settings.php: " . $e->getMessage());
    $flashMessageService->addError("Failed to initialize profile system. Please try again later.");
    header('Location: /index.php?page=dashboard');
    exit;
}

// Get user data for progress calculation
$userData = $profileController->getCurrentUserData();

if (!$userData) {
    $userData = [
        'username' => $current_username ?? 'User',
        'email' => 'N/A',
        'location' => '', 'user_status' => '', 'bio' => '', 'website_url' => ''
    ];
    $flashMessageService->addError('Failed to load user data.');
    error_log("Settings Page: Could not load user data for user ID: " . $userId);
}

// Function to calculate account completeness progress
function calculateAccountCompleteness($userData): array
{
    $fields = [
        'email' => !empty($userData['email']) && $userData['email'] !== 'N/A' ? 1 : 0,
        'location' => !empty($userData['location']) ? 1 : 0,
        'bio' => !empty($userData['bio']) ? 1 : 0,
        'user_status' => !empty($userData['user_status']) ? 1 : 0,
        'website_url' => !empty($userData['website_url']) ? 1 : 0
    ];

    $completed = array_sum($fields);
    $total = count($fields);
    $percentage = round(($completed / $total) * 100);

    return [
        'percentage' => $percentage,
        'completed' => $completed,
        'total' => $total,
        'missing_fields' => array_keys(array_filter($fields, function($v) { return $v === 0; }))
    ];
}

$accountProgress = calculateAccountCompleteness($userData);

// Function to get user statistics
function getUserStats($database_handler, $userId): array
{
    $stats = [
        'articles' => 0,
        'comments' => 0,
        'profile_views' => 0,
        'last_login' => 'Active now',
        'member_since' => 'This year'
    ];

    if ($database_handler && $pdo = $database_handler->getConnection()) {
        try {
            // Article statistics
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM articles WHERE user_id = ?");
            $stmt->execute([$userId]);
            $stats['articles'] = (int)$stmt->fetchColumn();

            // Comment statistics
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE user_id = ?");
            $stmt->execute([$userId]);
            $stats['comments'] = (int)$stmt->fetchColumn();

            // Other stats can be added here when tables are created

        } catch (PDOException $e) {
            error_log("User stats error: " . $e->getMessage());
        }
    }

    return $stats;
}

$userStats = getUserStats($database_handler, $userId);

$page_title = "Account & Site Settings";

// Generate CSRF token for future forms on this page
if (!isset($_SESSION['csrf_token_account_settings'])) {
    $_SESSION['csrf_token_account_settings'] = bin2hex(random_bytes(32));
}

// POST request handling will be added here as functionality is implemented
// if ($_SERVER["REQUEST_METHOD"] == "POST") {
//     // ...
// }

?>

<div class="settings-page-wrapper">
<div class="admin-layout">
    <!-- Enhanced Main Header Section -->
    <header class="page-header">
        <div class="page-header-content">
            <div class="page-header-main">
                <h1 class="page-title">
                    <i class="fas fa-cog"></i>
                    <?php echo htmlspecialchars($page_title); ?>
                </h1>
                <div class="page-header-description">
                    <p>Manage your preferences, privacy settings, and account configuration</p>
                </div>
            </div>
            <div class="page-header-actions">
                <a href="/index.php?page=dashboard" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
                <a href="/index.php?page=profile_edit" class="btn btn-primary">
                    <i class="fas fa-user-edit"></i> Edit Profile
                </a>
            </div>
        </div>
    </header>

    <!-- Flash Messages -->
    <?php
    if (isset($flashMessageService) && $flashMessageService->hasMessages()) {
        $messages = $flashMessageService->getMessages();
        if (!empty($messages)) {
    ?>
        <div class="flash-messages-container">
            <?php foreach ($messages as $type => $messageList): ?>
                <?php foreach ($messageList as $message): ?>
                    <div class="message message--<?php echo htmlspecialchars($type); ?>">
                        <p><?php echo $message['is_html'] ? $message['text'] : htmlspecialchars($message['text']); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    <?php
        }
    }
    ?>

    <!-- Main Content Layout -->
    <div class="content-layout">
        <!-- Primary Content Area -->
        <main class="main-content" style="max-width: 800px;">
            <!-- Account Preferences Card -->
            <div class="form-wrapper">
                <div class="card card-primary">
                    <div class="card-header">
                        <div class="card-header-content">
                            <h2 class="card-title">
                                <i class="fas fa-user-cog"></i> Account Preferences
                            </h2>
                            <div class="card-header-meta">
                                <div class="creation-info">
                                    <small class="creation-date">
                                        <i class="fas fa-calendar-alt"></i>
                                        Settings: <?php echo date('M j, Y \a\t g:i A'); ?>
                                    </small>
                                    <small class="author-info">
                                        <i class="fas fa-user"></i>
                                        User: <?php echo htmlspecialchars($userData['username'] ?? 'Unknown'); ?>
                                    </small>
                                </div>
                                <div class="article-status">
                                    <span class="status-badge status-settings">
                                        <i class="fas fa-bell"></i>
                                        Notifications
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="card-header-actions">
                            <button type="button" class="btn-icon btn-toggle-help" onclick="toggleHelp()" title="Toggle Help">
                                <i class="fas fa-question-circle"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <form class="article-creation-form placeholder-form">
                            <!-- Step 1: Notification Settings -->
                            <div class="form-section" data-section="1">
                                <div class="section-header">
                                    <h3 class="section-title">
                                        <i class="fas fa-bell"></i> Notification Preferences
                                    </h3>
                                    <p class="section-description">Choose how you want to receive notifications and updates</p>
                                </div>
                                <div class="form-grid">
                                    <div class="form-group form-group-half">
                                        <label for="notification_preferences" class="form-label">
                                            Email Notifications
                                        </label>
                                        <select id="notification_preferences" name="notification_preferences" class="form-control" disabled>
                                            <option>All notifications</option>
                                            <option>Important only</option>
                                            <option>Disabled</option>
                                        </select>
                                        <div class="form-help-text">
                                            <i class="fas fa-envelope"></i>
                                            Choose how you want to receive email notifications
                                        </div>
                                    </div>

                                    <div class="form-group form-group-half">
                                        <label for="activity_digest" class="form-label">
                                            Activity Digest
                                        </label>
                                        <select id="activity_digest" name="activity_digest" class="form-control" disabled>
                                            <option>Weekly</option>
                                            <option selected>Monthly</option>
                                            <option>Disabled</option>
                                        </select>
                                        <div class="form-help-text">
                                            <i class="fas fa-calendar-week"></i>
                                            Get periodic summaries of your account activity
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Step 2: Localization Settings -->
                            <div class="form-section" data-section="2">
                                <div class="section-header">
                                    <h3 class="section-title">
                                        <i class="fas fa-globe"></i> Localization
                                    </h3>
                                    <p class="section-description">Configure language and regional preferences</p>
                                </div>
                                <div class="form-grid">
                                    <div class="form-group form-group-half">
                                        <label for="language_preference" class="form-label">
                                            Language
                                        </label>
                                        <select id="language_preference" name="language_preference" class="form-control" disabled>
                                            <option selected>English</option>
                                            <option>Russian</option>
                                            <option>Ukrainian</option>
                                        </select>
                                        <div class="form-help-text">
                                            <i class="fas fa-language"></i>
                                            Choose your preferred interface language
                                        </div>
                                    </div>

                                    <div class="form-group form-group-half">
                                        <label for="timezone" class="form-label">
                                            Timezone
                                        </label>
                                        <select id="timezone" name="timezone" class="form-control" disabled>
                                            <option selected>UTC+0 (GMT)</option>
                                            <option>UTC+3 (Moscow)</option>
                                            <option>UTC-5 (EST)</option>
                                        </select>
                                        <div class="form-help-text">
                                            <i class="fas fa-clock"></i>
                                            Set your local timezone for timestamps
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Coming Soon Notice -->
                            <div class="form-actions-redesigned">
                                <div class="coming-soon-notice">
                                    <div class="notice-icon">
                                        <i class="fas fa-rocket"></i>
                                    </div>
                                    <div class="notice-content">
                                        <h4>Coming Soon</h4>
                                        <p>These settings will be available in a future update. Stay tuned for more customization options!</p>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Privacy & Security Card -->
            <div class="form-wrapper">
                <div class="card card-primary">
                    <div class="card-header">
                        <div class="card-header-content">
                            <h2 class="card-title">
                                <i class="fas fa-shield-alt"></i> Privacy & Security
                            </h2>
                            <div class="card-header-meta">
                                <div class="article-status">
                                    <span class="status-badge status-security">
                                        <i class="fas fa-lock"></i>
                                        Security
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <form class="article-creation-form placeholder-form">
                            <!-- Privacy Settings -->
                            <div class="form-section" data-section="1">
                                <div class="section-header">
                                    <h3 class="section-title">
                                        <i class="fas fa-eye"></i> Privacy Controls
                                    </h3>
                                    <p class="section-description">Control who can see your profile and activity</p>
                                </div>
                                <div class="form-grid">
                                    <div class="form-group form-group-half">
                                        <label for="profile_visibility" class="form-label">
                                            Profile Visibility
                                        </label>
                                        <select id="profile_visibility" name="profile_visibility" class="form-control" disabled>
                                            <option selected>Public</option>
                                            <option>Friends only</option>
                                            <option>Private</option>
                                        </select>
                                        <div class="form-help-text">
                                            <i class="fas fa-users"></i>
                                            Who can see your profile information
                                        </div>
                                    </div>

                                    <div class="form-group form-group-half">
                                        <label for="activity_visibility" class="form-label">
                                            Activity Visibility
                                        </label>
                                        <select id="activity_visibility" name="activity_visibility" class="form-control" disabled>
                                            <option selected>Public</option>
                                            <option>Limited</option>
                                            <option>Private</option>
                                        </select>
                                        <div class="form-help-text">
                                            <i class="fas fa-chart-line"></i>
                                            Who can see your activity and contributions
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Security Options -->
                            <div class="form-section" data-section="2">
                                <div class="section-header">
                                    <h3 class="section-title">
                                        <i class="fas fa-shield-check"></i> Security Options
                                    </h3>
                                    <p class="section-description">Advanced security features for your account</p>
                                </div>
                                <div class="security-options-grid">
                                    <div class="security-option-card">
                                        <div class="option-header">
                                            <div class="option-icon">
                                                <i class="fas fa-mobile-alt"></i>
                                            </div>
                                            <div class="option-info">
                                                <h4>Two-Factor Authentication</h4>
                                                <p>Add an extra layer of security to your account</p>
                                            </div>
                                        </div>
                                        <div class="option-control">
                                            <span class="status-badge status-inactive">Disabled</span>
                                            <button type="button" class="btn btn-outline btn-sm" disabled>Enable</button>
                                        </div>
                                    </div>

                                    <div class="security-option-card">
                                        <div class="option-header">
                                            <div class="option-icon">
                                                <i class="fas fa-bell"></i>
                                            </div>
                                            <div class="option-info">
                                                <h4>Login Notifications</h4>
                                                <p>Get notified when someone logs into your account</p>
                                            </div>
                                        </div>
                                        <div class="option-control">
                                            <span class="status-badge status-active">Enabled</span>
                                            <button type="button" class="btn btn-outline btn-sm" disabled>Disable</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Design Customization Card -->
            <div class="form-wrapper">
                <div class="card card-primary">
                    <div class="card-header">
                        <div class="card-header-content">
                            <h2 class="card-title">
                                <i class="fas fa-palette"></i> Design Customization
                            </h2>
                            <div class="card-header-meta">
                                <div class="article-status">
                                    <span class="status-badge status-design">
                                        <i class="fas fa-paint-brush"></i>
                                        Theme
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <form class="article-creation-form placeholder-form">
                            <div class="form-section" data-section="1">
                                <div class="section-header">
                                    <h3 class="section-title">
                                        <i class="fas fa-brush"></i> Appearance Settings
                                    </h3>
                                    <p class="section-description">Customize the appearance and theme of your interface</p>
                                </div>
                                <div class="form-grid">
                                    <div class="form-group form-group-half">
                                        <label for="theme_selection" class="form-label">
                                            Theme Selection
                                        </label>
                                        <select id="theme_selection" name="theme_selection" class="form-control" disabled>
                                            <option>Auto (System)</option>
                                            <option selected>Dark Theme</option>
                                            <option>Light Theme</option>
                                        </select>
                                        <div class="form-help-text">
                                            <i class="fas fa-moon"></i>
                                            Choose your preferred site theme
                                        </div>
                                    </div>

                                    <div class="form-group form-group-half">
                                        <label for="layout_preference" class="form-label">
                                            Layout Density
                                        </label>
                                        <select id="layout_preference" name="layout_preference" class="form-control" disabled>
                                            <option>Compact</option>
                                            <option selected>Standard</option>
                                            <option>Spacious</option>
                                        </select>
                                        <div class="form-help-text">
                                            <i class="fas fa-th-large"></i>
                                            Select your preferred layout density
                                        </div>
                                    </div>
                                </div>

                                <div class="theme-preview-section">
                                    <div class="preview-label">Current Theme Preview:</div>
                                    <div class="theme-preview-container">
                                        <div class="theme-preview-mockup dark-preview">
                                            <div class="preview-header"></div>
                                            <div class="preview-content">
                                                <div class="preview-text"></div>
                                                <div class="preview-text short"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>

        <!-- Enhanced Compact Sidebar -->
        <aside class="sidebar-content" style="min-width: 280px; max-width: 320px;">
            <!-- Quick Actions Card -->
            <div class="card card-compact sidebar-card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-bolt"></i> Quick Actions
                    </h3>
                </div>
                <div class="card-body">
                    <div class="quick-actions-grid">
                        <a href="/index.php?page=profile_edit" class="action-item-card">
                            <div class="action-icon">
                                <i class="fas fa-user-edit"></i>
                            </div>
                            <div class="action-content">
                                <span class="action-title">Edit Profile</span>
                                <span class="action-description">Update bio, location, and contact details</span>
                            </div>
                        </a>

                        <a href="/index.php?page=profile_edit#security" class="action-item-card">
                            <div class="action-icon">
                                <i class="fas fa-key"></i>
                            </div>
                            <div class="action-content">
                                <span class="action-title">Change Password</span>
                                <span class="action-description">Update your account password</span>
                            </div>
                        </a>

                        <a href="/index.php?page=dashboard" class="action-item-card">
                            <div class="action-icon">
                                <i class="fas fa-tachometer-alt"></i>
                            </div>
                            <div class="action-content">
                                <span class="action-title">Dashboard</span>
                                <span class="action-description">View activity and manage content</span>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Account Overview Card -->
            <div class="card card-compact sidebar-card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-info-circle"></i> Account Overview
                    </h3>
                </div>
                <div class="card-body">
                    <div class="account-overview-stats">
                        <div class="overview-item">
                            <div class="overview-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="overview-content">
                                <span class="overview-label">Account Status</span>
                                <span class="status-active">Active</span>
                            </div>
                        </div>

                        <div class="overview-item">
                            <div class="overview-icon">
                                <i class="fas fa-user-tag"></i>
                            </div>
                            <div class="overview-content">
                                <span class="overview-label">User Role</span>
                                <span class="role-badge role-<?php echo strtolower($current_user_role ?? 'user'); ?>">
                                    <?php echo ucfirst($current_user_role ?? 'User'); ?>
                                </span>
                            </div>
                        </div>

                        <div class="overview-item">
                            <div class="overview-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="overview-content">
                                <span class="overview-label">Last Login</span>
                                <span class="overview-value"><?php echo date('M j, Y \a\t H:i'); ?></span>
                            </div>
                        </div>

                        <div class="overview-item">
                            <div class="overview-icon">
                                <i class="fas fa-shield-check"></i>
                            </div>
                            <div class="overview-content">
                                <span class="overview-label">Security Score</span>
                                <div class="security-score-display">
                                    <span class="score-value">7/10</span>
                                    <div class="score-bar">
                                        <div class="score-fill" style="width: 70%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Activity Stats Card -->
            <div class="card card-compact sidebar-card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-chart-line"></i> Activity Stats
                    </h3>
                </div>
                <div class="card-body">
                    <div class="writing-stats">
                        <div class="stat-item">
                            <div class="stat-icon">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <div class="stat-info">
                                <span class="stat-number"><?php echo $userStats['articles']; ?></span>
                                <span class="stat-label">Articles</span>
                            </div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-icon">
                                <i class="fas fa-comments"></i>
                            </div>
                            <div class="stat-info">
                                <span class="stat-number"><?php echo $userStats['comments']; ?></span>
                                <span class="stat-label">Comments</span>
                            </div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-icon">
                                <i class="fas fa-percentage"></i>
                            </div>
                            <div class="stat-info">
                                <span class="stat-number"><?php echo $accountProgress['percentage']; ?>%</span>
                                <span class="stat-label">Complete</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Options Card -->
            <div class="card card-compact sidebar-card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-wrench"></i> Additional Options
                    </h3>
                </div>
                <div class="card-body">
                    <div class="additional-options-list">
                        <div class="option-item-link">
                            <i class="fas fa-download"></i>
                            <span>Export Account Data</span>
                            <small>Download your data</small>
                        </div>

                        <div class="option-item-link">
                            <i class="fas fa-question-circle"></i>
                            <span>Help & Support</span>
                            <small>Get assistance</small>
                        </div>

                        <div class="option-item-link danger">
                            <i class="fas fa-trash-alt"></i>
                            <span>Delete Account</span>
                            <small>Permanently remove</small>
                        </div>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</div>
</div>

<script>
// Tips carousel functionality
let currentTip = 0;
const tips = document.querySelectorAll('.tip-item');

function showTip(index) {
    tips.forEach((tip, i) => {
        tip.classList.toggle('active', i === index);
    });
}

function nextTip() {
    currentTip = (currentTip + 1) % tips.length;
    showTip(currentTip);
}

function previousTip() {
    currentTip = (currentTip - 1 + tips.length) % tips.length;
    showTip(currentTip);
}

// Auto-rotate tips every 5 seconds
setInterval(nextTip, 5000);

// Helper functions for consistency
function toggleHelp() {
    // Implementation for help toggle
    console.log('Help toggled');
}

// Theme preview interaction
document.getElementById('theme_selection')?.addEventListener('change', function() {
    const preview = document.querySelector('.theme-preview-mockup');
    const selectedTheme = this.value;

    preview.className = 'theme-preview-mockup';
    if (selectedTheme.includes('Dark')) {
        preview.classList.add('dark-preview');
    } else if (selectedTheme.includes('Light')) {
        preview.classList.add('light-preview');
    } else {
        preview.classList.add('auto-preview');
    }
});
</script>
