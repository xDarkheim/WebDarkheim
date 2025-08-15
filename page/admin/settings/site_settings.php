<?php

/**
 * Site Settings Page - MODERN DARK ADMIN INTERFACE
 *
 * Modern dark administrative interface for site settings
 * with improved UX and consistent styling
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

use App\Application\Core\ServiceProvider;
use App\Application\Services\SiteSettingsService;
use App\Application\Middleware\CSRFMiddleware;
use App\Application\Components\AdminNavigation;

// Use global services from bootstrap.php
global $database_handler, $serviceProvider, $flashMessageService;

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Get AuthenticationService
try {
    $authService = $serviceProvider->getAuth();
} catch (Exception $e) {
    error_log("Critical: Failed to get AuthenticationService instance: " . $e->getMessage());
    die("A critical system error occurred. Please try again later.");
}

// Check authentication and admin rights
if (!$authService->isAuthenticated() || !$authService->hasRole('admin')) {
    $flashMessageService->addError("Access Denied. You do not have permission to view this page.");
    header('Location: /index.php?page=login');
    exit();
}

$page_title = "Site Settings";

// Get settings service
$settingsService = null;
$allSettings = [];

try {
    $settingsService = new SiteSettingsService($database_handler);
    $allSettings = $settingsService->getAllForAdmin();
} catch (Exception $e) {
    error_log("Failed to initialize SiteSettingsService: " . $e->getMessage());
    $flashMessageService->addError("Failed to load site settings. Please try again later.");
    $allSettings = [];
}

// Create unified navigation
$adminNavigation = new AdminNavigation($authService);

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Use global CSRF validation via CSRFMiddleware
    if (!CSRFMiddleware::validateQuick()) {
        $flashMessageService->addError("Invalid CSRF token. Settings not saved.");
    } elseif (!$settingsService) {
        $flashMessageService->addError("Settings service is not available. Cannot save settings.");
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
                                    $flashMessageService->addSuccess("Email test completed successfully.");
                                } else {
                                    $flashMessageService->addError("Email test failed. Please check your email configuration.");
                                }
                            } else {
                                $flashMessageService->addError("Email test feature is not available.");
                            }
                        } catch (Exception $e) {
                            error_log("Email test failed: " . $e->getMessage());
                            $flashMessageService->addError("Email test failed: " . $e->getMessage());
                        }
                        break;

                    case 'clear_cache':
                        try {
                            if (method_exists($settingsService, 'clearCache')) {
                                $result = $settingsService->clearCache();
                                if (!empty($result['errors'])) {
                                    $flashMessageService->addError(implode(', ', $result['errors']));
                                } else {
                                    $flashMessageService->addSuccess("Cache cleared successfully. Removed {$result['files_cleared']} files.");
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
                                    $flashMessageService->addSuccess("Cache cleared successfully. Removed $cleared files.");
                                } else {
                                    $flashMessageService->addWarning("Cache directory not found.");
                                }
                            }
                        } catch (Exception $e) {
                            error_log("Failed to clear cache: " . $e->getMessage());
                            $flashMessageService->addError("Failed to clear cache: " . $e->getMessage());
                        }
                        break;

                    default:
                        $flashMessageService->addError("Unknown action: " . htmlspecialchars($_POST['action']));
                        break;
                }
            } else {
                // Handle settings update
                $settingsToUpdate = [];

                foreach ($_POST as $key => $value) {
                    if ($key === 'csrf_token' || $key === 'action') {
                        continue;
                    }
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
                    if (method_exists($settingsService, 'updateSettings')) {
                        try {
                            $result = $settingsService->updateSettings($settingsToUpdate);
                            if ($result) {
                                $flashMessageService->addSuccess("Settings updated successfully.");
                            } else {
                                $flashMessageService->addError("Failed to update settings. Please try again.");
                            }
                        } catch (Exception $e) {
                            error_log("Failed to update settings: " . $e->getMessage());
                            $flashMessageService->addError("Failed to update settings: " . $e->getMessage());
                        }
                    } else {
                        $flashMessageService->addError("Settings update functionality is not available.");
                        error_log("updateSettings method not found in SiteSettingsService");
                    }
                } else {
                    $flashMessageService->addWarning("No settings to update.");
                }
            }

            // Reload settings after update
            if ($settingsService && method_exists($settingsService, 'getAllForAdmin')) {
                $allSettings = $settingsService->getAllForAdmin();
            }

        } catch (Exception $e) {
            error_log("Error processing POST request: " . $e->getMessage());
            $flashMessageService->addError("An error occurred while processing your request: " . $e->getMessage());
        } catch (Error $e) {
            error_log("Fatal error processing POST request: " . $e->getMessage());
            $flashMessageService->addError("A critical error occurred while processing your request.");
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

// Check if settings are loaded
if (empty($allSettings)) {
    error_log("ERROR: No settings loaded from database");
    $flashMessageService->addError("No settings found. Please check database connection.");
}

// Get flash messages
$flashMessages = $flashMessageService->getAllMessages();

?>

    <!-- Admin Dark Theme Styles -->
    <link rel="stylesheet" href="/public/assets/css/admin.css">
    <link rel="stylesheet" href="/public/assets/css/admin-navigation.css">

    <!-- Unified Navigation -->
    <?= $adminNavigation->render() ?>

    <!-- Header -->
    <header class="admin-header">
        <div class="admin-header-container">
            <div class="admin-header-content">
                <div class="admin-header-title">
                    <i class="admin-header-icon fas fa-cogs"></i>
                    <div class="admin-header-text">
                        <h1>Site Settings</h1>
                        <p>Configure your website settings and preferences</p>
                    </div>
                </div>
                
                <div class="admin-header-actions">
                    <button type="button" class="admin-btn admin-btn-secondary" onclick="exportSettings()">
                        <i class="fas fa-download"></i>Export Settings
                    </button>
                    <button type="button" class="admin-btn admin-btn-warning" onclick="clearCache()">
                        <i class="fas fa-broom"></i>Clear Cache
                    </button>
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
        <div class="admin-layout-main">
            <div class="admin-content">
                <!-- Quick Actions -->
                <div class="admin-card admin-glow-warning">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="fas fa-bolt"></i>Quick Actions
                        </h3>
                    </div>
                    <div class="admin-card-body">
                        <div class="admin-grid admin-grid-cols-2">
                            <form method="post" class="inline">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                                <input type="hidden" name="action" value="test_email">
                                <button type="submit" class="admin-btn admin-btn-success" style="width: 100%;">
                                    <i class="fas fa-envelope"></i>Test Email Configuration
                                </button>
                            </form>

                            <form method="post" class="inline">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                                <input type="hidden" name="action" value="clear_cache">
                                <button type="submit" class="admin-btn admin-btn-warning" style="width: 100%;">
                                    <i class="fas fa-broom"></i>Clear System Cache
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Settings Configuration -->
                <?php if (!empty($allSettings)): ?>
                <form method="post" class="admin-grid admin-grid-cols-1">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">

                    <?php 
                    $categoryNames = [
                        'general' => ['title' => 'General Settings', 'icon' => 'fas fa-cog'],
                        'contact' => ['title' => 'Contact Information', 'icon' => 'fas fa-address-card'],
                        'social' => ['title' => 'Social Media', 'icon' => 'fas fa-share-alt'],
                        'seo' => ['title' => 'SEO Settings', 'icon' => 'fas fa-search'],
                        'legal' => ['title' => 'Legal & Privacy', 'icon' => 'fas fa-gavel'],
                        'security' => ['title' => 'Security Settings', 'icon' => 'fas fa-lock'],
                        'email' => ['title' => 'Email Configuration', 'icon' => 'fas fa-envelope'],
                        'cache' => ['title' => 'Cache Settings', 'icon' => 'fas fa-tachometer-alt'],
                        'content' => ['title' => 'Content Settings', 'icon' => 'fas fa-file-alt'],
                        'features' => ['title' => 'System Features', 'icon' => 'fas fa-toggle-on']
                    ];

                    foreach ($allSettings as $category => $settings): 
                        $categoryInfo = $categoryNames[$category] ?? ['title' => ucfirst($category), 'icon' => 'fas fa-cog'];
                    ?>
                    <div class="admin-card admin-card-hover-effect">
                        <div class="admin-card-header">
                            <h3 class="admin-card-title">
                                <i class="<?= $categoryInfo['icon'] ?>"></i>
                                <?= htmlspecialchars($categoryInfo['title']) ?>
                            </h3>
                        </div>
                        <div class="admin-card-body">
                            <?php if (!empty($settings)): ?>
                            <div class="admin-grid admin-grid-cols-2">
                                <?php foreach ($settings as $settingKey => $settingData): ?>
                                <div class="admin-form-group">
                                    <label for="<?= htmlspecialchars($settingKey) ?>" class="admin-label">
                                        <?= htmlspecialchars(ucwords(str_replace('_', ' ', $settingKey))) ?>
                                    </label>
                                    
                                    <?php
                                    $settingType = $settingData['type'] ?? 'string';
                                    $settingValue = $settingData['value'] ?? '';
                                    ?>
                                    
                                    <?php if ($settingType === 'boolean'): ?>
                                        <div style="display: flex; align-items: center; margin-top: 0.5rem;">
                                            <input type="checkbox"
                                                   id="<?= htmlspecialchars($settingKey) ?>"
                                                   name="<?= htmlspecialchars($settingKey) ?>"
                                                   value="1"
                                                   style="margin-right: 0.5rem;"
                                                   <?= $settingValue ? 'checked' : '' ?>>
                                            <label for="<?= htmlspecialchars($settingKey) ?>" style="margin-bottom: 0; color: var(--admin-text-secondary);">
                                                Enable this feature
                                            </label>
                                        </div>
                                    <?php elseif ($settingType === 'text'): ?>
                                        <textarea id="<?= htmlspecialchars($settingKey) ?>"
                                                  name="<?= htmlspecialchars($settingKey) ?>"
                                                  rows="3"
                                                  class="admin-input admin-textarea"><?= htmlspecialchars((string)$settingValue) ?></textarea>
                                    <?php else: ?>
                                        <input type="<?= $settingType === 'integer' ? 'number' : 'text' ?>"
                                               id="<?= htmlspecialchars($settingKey) ?>"
                                               name="<?= htmlspecialchars($settingKey) ?>"
                                               class="admin-input"
                                               value="<?= htmlspecialchars((string)$settingValue) ?>"
                                               <?= $settingType === 'integer' ? 'min="0"' : '' ?>>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($settingData['description'])): ?>
                                        <div class="admin-help-text">
                                            <?= htmlspecialchars($settingData['description']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php else: ?>
                                <div class="admin-text-center" style="padding: 2rem 0;">
                                    <i class="fas fa-info-circle" style="font-size: 2rem; color: var(--admin-text-muted); margin-bottom: 1rem;"></i>
                                    <p style="color: var(--admin-text-muted);">No settings available in this category</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <!-- Submit Button -->
                    <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 2rem;">
                        <button type="button" class="admin-btn admin-btn-secondary" onclick="resetSettings()">
                            <i class="fas fa-undo"></i>Reset Changes
                        </button>
                        <button type="submit" class="admin-btn admin-btn-primary admin-glow-primary">
                            <i class="fas fa-save"></i>Save All Settings
                        </button>
                    </div>
                </form>
                <?php else: ?>
                <!-- No Settings State -->
                <div class="admin-card">
                    <div class="admin-card-body admin-text-center">
                        <i class="fas fa-cogs" style="font-size: 4rem; color: var(--admin-text-muted); margin-bottom: 1rem;"></i>
                        <h3 style="color: var(--admin-text-primary); margin-bottom: 0.5rem;">No Settings Available</h3>
                        <p style="color: var(--admin-text-muted); margin-bottom: 1rem;">
                            No settings were found in the database. Please check your database configuration.
                        </p>
                        <a href="/index.php?page=site_settings&debug=1" class="admin-btn admin-btn-primary">
                            <i class="fas fa-bug"></i>View Debug Info
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Sidebar -->
            <aside class="admin-sidebar">
                <!-- System Status -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="fas fa-server"></i>System Status
                        </h3>
                    </div>
                    <div class="admin-card-body">
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="color: var(--admin-text-secondary); font-size: 0.875rem;">PHP Version</span>
                                <span class="admin-badge admin-badge-success"><?= PHP_VERSION ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="color: var(--admin-text-secondary); font-size: 0.875rem;">Memory Usage</span>
                                <span class="admin-badge admin-badge-primary"><?= round(memory_get_usage(true) / 1024 / 1024, 1) ?>MB</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="color: var(--admin-text-secondary); font-size: 0.875rem;">Database</span>
                                <span class="admin-badge admin-badge-success">Connected</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="color: var(--admin-text-secondary); font-size: 0.875rem;">Cache Status</span>
                                <span class="admin-badge admin-badge-warning">Active</span>
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
                        <a href="/index.php?page=manage_users" class="admin-btn admin-btn-secondary" style="width: 100%; margin-bottom: 0.5rem; justify-content: flex-start;">
                            <i class="fas fa-users"></i>Manage Users
                        </a>
                        <a href="/index.php?page=system_monitor" class="admin-btn admin-btn-secondary" style="width: 100%; margin-bottom: 0.5rem; justify-content: flex-start;">
                            <i class="fas fa-chart-line"></i>System Monitor
                        </a>
                        <a href="/index.php?page=backup_monitor" class="admin-btn admin-btn-secondary" style="width: 100%; margin-bottom: 0.5rem; justify-content: flex-start;">
                            <i class="fas fa-database"></i>Backup Monitor
                        </a>
                        <a href="/index.php?page=dashboard" class="admin-btn admin-btn-secondary" style="width: 100%; justify-content: flex-start;">
                            <i class="fas fa-tachometer-alt"></i>Dashboard
                        </a>
                    </div>
                </div>

                <!-- Settings Tips -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="fas fa-lightbulb"></i>Configuration Tips
                        </h3>
                    </div>
                    <div class="admin-card-body">
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <div>
                                <h4 style="display: flex; align-items: center; margin: 0 0 0.5rem 0; font-size: 0.875rem; font-weight: 600; color: var(--admin-text-primary);">
                                    <i class="fas fa-shield-alt" style="color: var(--admin-success); margin-right: 0.5rem;"></i>Security First
                                </h4>
                                <p style="font-size: 0.875rem; color: var(--admin-text-muted); margin: 0;">Always test configuration changes in a staging environment first</p>
                            </div>
                            <div>
                                <h4 style="display: flex; align-items: center; margin: 0 0 0.5rem 0; font-size: 0.875rem; font-weight: 600; color: var(--admin-text-primary);">
                                    <i class="fas fa-backup" style="color: var(--admin-warning); margin-right: 0.5rem;"></i>Backup Settings
                                </h4>
                                <p style="font-size: 0.875rem; color: var(--admin-text-muted); margin: 0;">Export your settings before making major changes</p>
                            </div>
                            <div>
                                <h4 style="display: flex; align-items: center; margin: 0 0 0.5rem 0; font-size: 0.875rem; font-weight: 600; color: var(--admin-text-primary);">
                                    <i class="fas fa-performance" style="color: var(--admin-info); margin-right: 0.5rem;"></i>Performance
                                </h4>
                                <p style="font-size: 0.875rem; color: var(--admin-text-muted); margin: 0;">Enable caching and optimize settings for better performance</p>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </main>

    <!-- Admin Scripts -->
    <script type="module" src="/public/assets/js/admin.js"></script>
    
    <script>
    function exportSettings() {
        // Implement settings export functionality
        alert('Settings export functionality will be implemented');
    }

    function clearCache() {
        if (confirm('Are you sure you want to clear all cache files?')) {
            // Submit form with clear_cache action
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                <input type="hidden" name="action" value="clear_cache">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }

    function resetSettings() {
        if (confirm('Are you sure you want to reset all settings to their default values?')) {
            location.reload();
        }
    }

    // Auto-save draft functionality
    let autoSaveTimer;
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('input', function() {
            clearTimeout(autoSaveTimer);
            autoSaveTimer = setTimeout(() => {
                console.log('Auto-saving draft...');
            }, 5000);
        });
    }
    </script>

