<?php

/**
 * Site Settings Page
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

use App\Application\Core\ServiceProvider;
use App\Application\Services\SiteSettingsService;
use App\Application\Middleware\CSRFMiddleware;

// Use global services from bootstrap.php
global $database_handler, $auth, $container;

// Get text editor component via ServiceProvider
$serviceProvider = ServiceProvider::getInstance($container);
$textEditorComponent = $serviceProvider->getTextEditorComponent();

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check authentication and admin rights
if (!$auth || !$auth->isAuthenticated() || !$auth->hasRole('admin')) {
    $_SESSION['error_message'] = "Access Denied. You do not have permission to view this page.";
    header('Location: /index.php?page=login');
    exit();
}

// Check required services
if (!isset($database_handler) || !isset($container)) {
    error_log("Critical: Required services not available in site_settings.php");
    die("A critical system error occurred. Please try again later.");
}

$page_title = "Site Settings";

// Get settings service
$settingsService = null;
$allSettings = [];

try {
    $settingsService = new SiteSettingsService($database_handler);

    // Check that the service is initialized correctly

    // Load all settings
    $allSettings = $settingsService->getAllForAdmin();

} catch (Exception $e) {
    error_log("Failed to initialize SiteSettingsService: " . $e->getMessage());
    $_SESSION['error_message'] = "Failed to load settings service: " . $e->getMessage();
    $allSettings = [];
} catch (Error $e) {
    error_log("Fatal error in SiteSettingsService: " . $e->getMessage());
    $_SESSION['error_message'] = "A critical error occurred while loading settings.";
    $allSettings = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Use global CSRF validation via CSRFMiddleware
    if (!CSRFMiddleware::validateQuick()) {
        $_SESSION['error_message'] = "Invalid CSRF token. Settings not saved.";
    } elseif (!$settingsService) {
        $_SESSION['error_message'] = "Settings service is not available. Cannot save settings.";
    } else {
        try {
            // Handle special actions
            if (isset($_POST['action'])) {
                switch ($_POST['action']) {
                    case 'test_email':
                        try {
                            if (method_exists($settingsService, 'testEmailConfiguration')) {
                                $result = $settingsService->testEmailConfiguration();
                                if ($result) {
                                    $_SESSION['success_message'] = "Email test completed successfully.";
                                } else {
                                    $_SESSION['error_message'] = "Email test failed. Please check your email configuration.";
                                }
                            } else {
                                $_SESSION['error_message'] = "Email test feature is not available.";
                            }
                        } catch (Exception $e) {
                            error_log("Email test failed: " . $e->getMessage());
                            $_SESSION['error_message'] = "Email test failed: " . $e->getMessage();
                        }
                        break;

                    case 'clear_cache':
                        try {
                            if (method_exists($settingsService, 'clearCache')) {
                                $result = $settingsService->clearCache();
                                if (!empty($result['errors'])) {
                                    $_SESSION['error_message'] = implode(', ', $result['errors']);
                                } else {
                                    $_SESSION['success_message'] = "Cache cleared successfully. Removed {$result['files_cleared']} files.";
                                }
                            } else {
                                // Fallback to original cache clearing logic
                                $cacheDir = $_SERVER['DOCUMENT_ROOT'] . '/cache';
                                $cleared = 0;
                                if (is_dir($cacheDir)) {
                                    $files = glob($cacheDir . '/*.cache');
                                    foreach ($files as $file) {
                                        if (is_file($file) && unlink($file)) {
                                            $cleared++;
                                        }
                                    }
                                    $_SESSION['success_message'] = "Cache cleared successfully. Removed $cleared files.";
                                } else {
                                    $_SESSION['warning_message'] = "Cache directory not found.";
                                }
                            }
                        } catch (Exception $e) {
                            error_log("Failed to clear cache: " . $e->getMessage());
                            $_SESSION['error_message'] = "Failed to clear cache: " . $e->getMessage();
                        }
                        break;

                    default:
                        $_SESSION['error_message'] = "Unknown action: " . htmlspecialchars($_POST['action']);
                        break;
                }
            } else {
                // Handle settings update
                $settingsToUpdate = [];

                foreach ($_POST as $key => $value) {
                    if ($key === 'csrf_token' || $key === 'action') {
                        continue;
                    }
                    // Validate and clean input
                    $settingsToUpdate[$key] = is_string($value) ? trim($value) : $value;
                }

                // Handle boolean fields (checkboxes)
                foreach ($allSettings as $category => $categorySettings) {
                    if (is_array($categorySettings)) {
                        foreach ($categorySettings as $settingKey => $settingData) {
                            if (isset($settingData['type']) && $settingData['type'] === 'boolean' && !isset($_POST[$settingKey])) {
                                $settingsToUpdate[$settingKey] = '0';
                            }
                        }
                    }
                }

                if (!empty($settingsToUpdate)) {
                    // Check that the updateSettings method exists
                    if (method_exists($settingsService, 'updateSettings')) {
                        try {
                            $result = $settingsService->updateSettings($settingsToUpdate);
                            if ($result) {
                                $_SESSION['success_message'] = "Settings updated successfully.";
                            } else {
                                $_SESSION['error_message'] = "Failed to update settings. Please try again.";
                            }
                        } catch (Exception $e) {
                            error_log("Failed to update settings: " . $e->getMessage());
                            $_SESSION['error_message'] = "Failed to update settings: " . $e->getMessage();
                        }
                    } else {
                        $_SESSION['error_message'] = "Settings update functionality is not available.";
                        error_log("updateSettings method not found in SiteSettingsService");
                    }
                } else {
                    $_SESSION['warning_message'] = "No settings to update.";
                }
            }

            // Reload settings after update
            if ($settingsService && method_exists($settingsService, 'getAllForAdmin')) {
                $allSettings = $settingsService->getAllForAdmin();
            }

        } catch (Exception $e) {
            error_log("Error processing POST request: " . $e->getMessage());
            $_SESSION['error_message'] = "An error occurred while processing your request: " . $e->getMessage();
        } catch (Error $e) {
            error_log("Fatal error processing POST request: " . $e->getMessage());
            $_SESSION['error_message'] = "A critical error occurred while processing your request.";
        }
    }

    // Redirect to prevent form resubmission
    header('Location: /index.php?page=site_settings');
    exit();
}

// Get CSRF token via global system
$csrfToken = CSRFMiddleware::getToken();

// Define category names for display
$categoryNames = [
    'general' => 'General Settings',
    'contact' => 'Contact Information',
    'social' => 'Social Media',
    'seo' => 'SEO Settings',
    'legal' => 'Legal & Privacy Settings',
    'security' => 'Security Settings',
    'email' => 'Email Settings',
    'cache' => 'Cache Settings',
    'content' => 'Content Settings',
    'features' => 'System Control',
    'uploads' => 'Upload Settings',
    'backup' => 'Backup Settings'
];

// Define category descriptions
$categoryDescriptions = [
    'general' => 'Configure general site settings and preferences',
    'contact' => 'Manage contact information and communication settings',
    'social' => 'Set up social media links and sharing options',
    'seo' => 'Optimize your site for search engines',
    'legal' => 'Configure legal pages and privacy settings',
    'security' => 'Manage security settings and access controls',
    'email' => 'Configure email settings and SMTP options',
    'cache' => 'Manage caching options and performance',
    'content' => 'Configure content display and formatting options',
    'features' => 'Control core system functionality, debug modes, and access restrictions',
    'uploads' => 'Configure file upload settings and restrictions',
    'backup' => 'Configure automatic database backup settings and notifications'
];

// Check if settings are loaded
if (empty($allSettings)) {
    error_log("ERROR: No settings loaded from database");
    $_SESSION['error_message'] = "No settings found. Please check database connection.";
}
?>

<div class="admin-page-wrapper">
<div class="admin-layout">
    <!-- Enhanced Main Header Section -->
    <header class="page-header">
        <div class="page-header-content">
            <div class="page-header-main">
                <h1 class="page-title">
                    <i class="fas fa-cogs"></i>
                    <?php echo htmlspecialchars($page_title); ?>
                </h1>
                <div class="page-header-description">
                    <p>Configure your website settings and preferences</p>
                </div>
            </div>
            <div class="page-header-actions">
                <a href="/index.php?page=dashboard" class="btn btn-secondary">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <button type="button" class="btn btn-outline" onclick="exportSettings()">
                    <i class="fas fa-download"></i> Export Settings
                </button>
            </div>
        </div>
    </header>

    <!-- Flash Messages -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="flash-messages-container">
            <div class="message message--success">
                <p><i class="fas fa-check-circle"></i> <?= htmlspecialchars($_SESSION['success_message']) ?></p>
            </div>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="flash-messages-container">
            <div class="message message--error">
                <p><i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($_SESSION['error_message']) ?></p>
            </div>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['warning_message'])): ?>
        <div class="flash-messages-container">
            <div class="message message--warning">
                <p><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($_SESSION['warning_message']) ?></p>
            </div>
        </div>
        <?php unset($_SESSION['warning_message']); ?>
    <?php endif; ?>

    <!-- Main Content Layout -->
    <div class="content-layout">
        <!-- Primary Content Area -->
        <main class="main-content">
            <!-- Quick Actions Card -->
            <div class="card card-primary">
                <div class="card-header">
                    <div class="card-header-content">
                        <h2 class="card-title">
                            <i class="fas fa-bolt"></i> Quick Actions
                        </h2>
                        <div class="card-header-meta">
                            <span class="meta-badge">
                                <i class="fas fa-tools"></i>
                                System Tools
                            </span>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="quick-actions-grid">
                        <form method="post" class="quick-action-item">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                            <input type="hidden" name="action" value="test_email">
                            <div class="quick-action-content">
                                <div class="quick-action-icon">
                                    <i class="fas fa-envelope-check"></i>
                                </div>
                                <div class="quick-action-info">
                                    <h3>Test Email Configuration</h3>
                                    <p>Send a test email to verify SMTP settings</p>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-success btn-small">
                                <i class="fas fa-paper-plane"></i> Test Email
                            </button>
                        </form>

                        <form method="post" class="quick-action-item">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                            <input type="hidden" name="action" value="clear_cache">
                            <div class="quick-action-content">
                                <div class="quick-action-icon">
                                    <i class="fas fa-broom"></i>
                                </div>
                                <div class="quick-action-info">
                                    <h3>Clear System Cache</h3>
                                    <p>Remove cached files to improve performance</p>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-warning btn-small">
                                <i class="fas fa-trash"></i> Clear Cache
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <?php if (!empty($allSettings)): ?>
            <!-- Settings Management Interface -->
            <div class="settings-management-container">
                <!-- Settings Navigation Sidebar -->
                <div class="settings-sidebar">
                    <div class="settings-sidebar-header">
                        <h3 class="settings-sidebar-title">
                            <i class="fas fa-list"></i> Categories
                        </h3>
                        <span class="settings-count"><?php echo count($allSettings); ?> categories</span>
                    </div>
                    <nav class="settings-category-nav">
                        <?php $isFirst = true; ?>
                        <?php foreach ($allSettings as $category => $settings): ?>
                            <button class="settings-category-button <?= $isFirst ? 'active' : '' ?>"
                                    data-category="<?= $category ?>"
                                    type="button">
                                <span class="category-icon">
                                    <i class="fas fa-<?= $category === 'legal' ? 'shield-check' : ($category === 'contact' ? 'address-card' : ($category === 'social' ? 'share-alt' : ($category === 'seo' ? 'search' : ($category === 'security' ? 'lock' : ($category === 'email' ? 'envelope' : ($category === 'cache' ? 'database' : ($category === 'content' ? 'file-text' : ($category === 'features' ? 'toggle-on' : ($category === 'uploads' ? 'cloud-upload' : 'cog'))))))))) ?>"></i>
                                </span>
                                <span class="category-info">
                                    <span class="category-name"><?= htmlspecialchars($categoryNames[$category] ?? ucfirst($category)) ?></span>
                                    <span class="category-count"><?= count($settings) ?> settings</span>
                                </span>
                            </button>
                            <?php $isFirst = false; ?>
                        <?php endforeach; ?>
                    </nav>
                </div>

                <!-- Settings Content Area -->
                <div class="settings-content">
                    <form method="post" class="settings-form">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                        <!-- Category Content Header -->
                        <div class="settings-content-header">
                            <h2 class="settings-content-title" id="current-category-title">
                                <i class="fas fa-cog" id="current-category-icon"></i>
                                <span id="current-category-name">General Settings</span>
                            </h2>
                            <p class="settings-content-description" id="current-category-description">
                                Configure general site settings and preferences
                            </p>
                        </div>

                        <!-- Settings Panels -->
                        <div class="settings-panels-container">
                            <?php $isFirst = true; ?>
                            <?php foreach ($allSettings as $category => $settings): ?>
                                <div class="settings-panel <?= $isFirst ? 'active' : '' ?>"
                                     id="settings-panel-<?= $category ?>"
                                     data-category="<?= $category ?>"
                                     data-title="<?= htmlspecialchars($categoryNames[$category] ?? ucfirst($category)) ?>"
                                     data-description="<?= htmlspecialchars($categoryDescriptions[$category] ?? 'Configure settings for this category') ?>"
                                     data-icon="<?= $category === 'legal' ? 'shield-check' : ($category === 'contact' ? 'address-card' : ($category === 'social' ? 'share-alt' : ($category === 'seo' ? 'search' : ($category === 'security' ? 'lock' : ($category === 'email' ? 'envelope' : ($category === 'cache' ? 'database' : ($category === 'content' ? 'file-text' : ($category === 'features' ? 'toggle-on' : ($category === 'uploads' ? 'cloud-upload' : 'cog'))))))))) ?>">

                                    <?php if (!empty($settings)): ?>
                                    <div class="settings-grid">
                                        <?php foreach ($settings as $settingKey => $settingData): ?>
                                            <div class="setting-item">
                                                <div class="setting-header">
                                                    <div class="setting-label-group">
                                                        <label for="<?= htmlspecialchars($settingKey) ?>" class="setting-label">
                                                            <?= htmlspecialchars(ucwords(str_replace('_', ' ', $settingKey))) ?>
                                                        </label>
                                                        <span class="setting-type-badge">
                                                            <?= htmlspecialchars($settingData['type'] ?? 'string') ?>
                                                        </span>
                                                    </div>
                                                    <?php if (!empty($settingData['description'])): ?>
                                                        <p class="setting-description">
                                                            <?= htmlspecialchars($settingData['description']) ?>
                                                        </p>
                                                    <?php endif; ?>
                                                </div>

                                                <div class="setting-input-container">
                                                    <?php
                                                    $settingType = $settingData['type'] ?? 'string';
                                                    $settingValue = $settingData['value'] ?? '';
                                                    ?>
                                                    <?php if ($settingType === 'boolean'): ?>
                                                        <div class="toggle-switch">
                                                            <input type="checkbox"
                                                                   id="<?= htmlspecialchars($settingKey) ?>"
                                                                   name="<?= htmlspecialchars($settingKey) ?>"
                                                                   value="1"
                                                                   class="toggle-input"
                                                                   <?= $settingValue ? 'checked' : '' ?>>
                                                            <label for="<?= htmlspecialchars($settingKey) ?>" class="toggle-label">
                                                                <span class="toggle-slider"></span>
                                                                <span class="toggle-text">
                                                                    <span class="toggle-on">ON</span>
                                                                    <span class="toggle-off">OFF</span>
                                                                </span>
                                                            </label>
                                                        </div>
                                                    <?php elseif ($settingKey === 'backup_schedule'): ?>
                                                        <!-- Special handling for backup schedule -->
                                                        <div class="backup-schedule-container">
                                                            <label for="backup_schedule_preset"></label><select id="backup_schedule_preset" class="form-control" style="margin-bottom: 10px;" onchange="updateBackupSchedule()">
                                                                <option value="">Choose a preset...</option>
                                                                <option value="0 2 * * *" <?= $settingValue === '0 2 * * *' ? 'selected' : '' ?>>Daily at 2:00 AM</option>
                                                                <option value="0 3 * * *" <?= $settingValue === '0 3 * * *' ? 'selected' : '' ?>>Daily at 3:00 AM</option>
                                                                <option value="0 1 * * 0" <?= $settingValue === '0 1 * * 0' ? 'selected' : '' ?>>Weekly (Sunday at 1:00 AM)</option>
                                                                <option value="0 2 * * 0" <?= $settingValue === '0 2 * * 0' ? 'selected' : '' ?>>Weekly (Sunday at 2:00 AM)</option>
                                                                <option value="0 3 1 * *" <?= $settingValue === '0 3 1 * *' ? 'selected' : '' ?>>Monthly (first day at 3:00 AM)</option>
                                                                <option value="0 1 1,15 * *" <?= $settingValue === '0 1 1,15 * *' ? 'selected' : '' ?>>Twice a month (1st and 15th at 1:00 AM)</option>
                                                                <option value="custom">Custom cron expression</option>
                                                            </select>
                                                            <div class="input-wrapper">
                                                                <input type="text"
                                                                       id="<?= htmlspecialchars($settingKey) ?>"
                                                                       name="<?= htmlspecialchars($settingKey) ?>"
                                                                       class="form-control"
                                                                       value="<?= htmlspecialchars((string)$settingValue) ?>"
                                                                       placeholder="0 2 * * * (minute hour day month weekday)">
                                                            </div>
                                                            <div class="cron-help" style="margin-top: 5px; font-size: 12px; color: #94a3b8;">
                                                                <strong>Cron format:</strong> minute (0–59) hour (0–23) day (1–31) month (1–12) weekday (0–6, 0=Sunday)<br>
                                                                <strong>Examples:</strong> "0 2 * * *" = daily at 2 AM, "Zero 1 * * 0" = weekly on Sunday at 1 AM
                                                            </div>
                                                        </div>
                                                    <?php elseif ($settingType === 'text'): ?>
                                                        <div class="textarea-wrapper">
                                                            <textarea id="<?= htmlspecialchars($settingKey) ?>"
                                                                      name="<?= htmlspecialchars($settingKey) ?>"
                                                                      class="form-control textarea-auto-resize"
                                                                      rows="4"
                                                                      placeholder="Enter your text here..."><?= htmlspecialchars((string)$settingValue) ?></textarea>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="input-wrapper">
                                                            <input type="<?= $settingType === 'integer' ? 'number' : 'text' ?>"
                                                                   id="<?= htmlspecialchars($settingKey) ?>"
                                                                   name="<?= htmlspecialchars($settingKey) ?>"
                                                                   class="form-control"
                                                                   value="<?= htmlspecialchars((string)$settingValue) ?>"
                                                                   placeholder="Enter <?= $settingType === 'integer' ? 'number' : 'text' ?>..."
                                                                   <?= $settingType === 'integer' ? 'min="0"' : '' ?>>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php else: ?>
                                        <div class="empty-state">
                                            <div class="empty-state-icon">
                                                <i class="fas fa-cog"></i>
                                            </div>
                                            <h3 class="empty-state-title">No Settings Available</h3>
                                            <p class="empty-state-description">
                                                No settings are found in this category.
                                            </p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php $isFirst = false; ?>
                            <?php endforeach; ?>
                        </div>

                        <!-- Form Actions -->
                        <div class="form-actions-redesigned">
                            <div class="form-actions-container">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i>
                                    <span>Save Changes</span>
                                </button>

                                <button type="button" class="btn btn-secondary" onclick="resetSettings()">
                                    <i class="fas fa-undo"></i>
                                    <span>Reset</span>
                                </button>

                                <button type="button" class="btn btn-outline" onclick="previewChanges()">
                                    <i class="fas fa-eye"></i>
                                    <span>Preview</span>
                                </button>
                            </div>

                            <div class="form-actions-help">
                                <div class="save-status">
                                    <i class="fas fa-info-circle"></i>
                                    <span>Changes will be applied immediately</span>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <?php else: ?>
                <!-- No Settings State -->
                <div class="card card-primary">
                    <div class="card-body">
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <i class="fas fa-cogs"></i>
                            </div>
                            <h3 class="empty-state-title">No Settings Available</h3>
                            <p class="empty-state-description">
                                No settings were found in the database. Please check your database configuration.
                            </p>
                            <a href="/index.php?page=site_settings&debug=1" class="btn btn-primary">
                                <i class="fas fa-bug"></i> View Debug Info
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>

        <!-- Enhanced Compact Sidebar -->
        <aside class="sidebar-content" style="min-width: 280px; max-width: 320px;">
            <!-- Settings Overview Card -->
            <div class="card card-compact sidebar-card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-chart-pie"></i> Settings Overview
                    </h3>
                </div>
                <div class="card-body">
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-icon">
                                <i class="fas fa-list"></i>
                            </div>
                            <div class="stat-info">
                                <span class="stat-number"><?php echo count($allSettings); ?></span>
                                <span class="stat-label">Categories</span>
                            </div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-icon">
                                <i class="fas fa-cogs"></i>
                            </div>
                            <div class="stat-info">
                                <span class="stat-number"><?php echo array_sum(array_map('count', $allSettings)); ?></span>
                                <span class="stat-label">Total Settings</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Navigation Card -->
            <div class="card card-compact sidebar-card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-compass"></i> Quick Navigation
                    </h3>
                </div>
                <div class="card-body">
                    <nav class="quick-nav">
                        <a href="/index.php?page=dashboard" class="quick-nav-link">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                        <a href="/index.php?page=manage_users" class="quick-nav-link">
                            <i class="fas fa-users"></i>
                            <span>Manage Users</span>
                        </a>
                        <a href="/index.php?page=manage_categories" class="quick-nav-link">
                            <i class="fas fa-tags"></i>
                            <span>Categories</span>
                        </a>
                        <a href="/index.php?page=manage_articles" class="quick-nav-link">
                            <i class="fas fa-newspaper"></i>
                            <span>Articles</span>
                        </a>
                    </nav>
                </div>
            </div>

        </aside>
    </div>
</div>
</div>

<script src="/public/assets/js/site-settings.js"></script>

<?php include __DIR__ . '/../../resources/views/_editor_scripts.php'; ?>
