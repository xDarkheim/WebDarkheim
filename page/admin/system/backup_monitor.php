<?php

/**
 * Backup Monitor Page - DARK ADMIN THEME
 * Administrative interface for backup monitoring with a modern dark theme
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

// Include required components
use App\Application\Components\AdminNavigation;
use App\Application\Controllers\DatabaseBackupController;

// Use global services from bootstrap.php
global $flashMessageService, $database_handler, $serviceProvider;

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Get AuthenticationService
try {
    $authService = $serviceProvider->getAuth();
} catch (Exception $e) {
    error_log('Critical: Failed to get AuthenticationService instance: ' . $e->getMessage());
    die('A critical system error occurred. Please try again later.');
}

// Check authentication and admin rights
if (!$authService->isAuthenticated() || !$authService->hasRole('admin')) {
    $flashMessageService->addError('Access Denied. You do not have permission to view this page.');
    header('Location: /index.php?page=login');
    exit();
}

// Check for required services
if (!isset($database_handler)) {
    error_log('Critical: Required services not available in backup_monitor.php');
    die('A critical system error occurred. Please try again later.');
}

// Create unified navigation after an authentication check
try {
    $adminNavigation = new AdminNavigation($serviceProvider->getAuth());
} catch (ReflectionException $e) {
    error_log('Critical: Failed to create AdminNavigation instance: ' . $e->getMessage());
    die('A critical system error occurred. Please try again later.');
}

$page_title = 'Database Backup Monitor';

try {
    // Initialize backup controller
    $backupController = new DatabaseBackupController();
    $backups = $backupController->getBackupsList();

    // Get system health from backup data
    $totalBackups = count($backups);
    $totalSize = array_sum(array_column($backups, 'size'));

    // Calculate health status
    $healthStatus = 'healthy';
    $healthMessage = 'System is operating normally';
    $warnings = [];

    if (empty($backups)) {
        $healthStatus = 'error';
        $healthMessage = 'No backups found';
        $warnings[] = 'No backup files available';
        $flashMessageService->addWarning('No backup files found. Automatic backups should run daily at 2:00 AM.');
    } else {
        $latestBackup = reset($backups);
        $daysSinceLastBackup = $latestBackup['age_days'];

        if ($daysSinceLastBackup > 7) {
            $healthStatus = 'error';
            $healthMessage = "Last backup is $daysSinceLastBackup days old";
            $warnings[] = "Last backup created $daysSinceLastBackup days ago";
            $flashMessageService->addError("Critical: Last backup was created $daysSinceLastBackup days ago. Immediate attention required!");
        } elseif ($daysSinceLastBackup > 1) {
            $healthStatus = 'warning';
            $healthMessage = "Last backup is $daysSinceLastBackup days old";
            $warnings[] = 'Consider checking the automatic backup system';
            $flashMessageService->addWarning("Last backup was created $daysSinceLastBackup days ago. Consider checking the backup system.");
        } else {
            $flashMessageService->addSuccess('Backup system is operating normally. Latest backup is recent.');
        }
    }

} catch (Exception $e) {
    error_log('Failed to initialize backup controller: ' . $e->getMessage());
    $flashMessageService->addError('Failed to load backup system: ' . $e->getMessage());

    // Fallback values
    $healthStatus = 'error';
    $healthMessage = 'Failed to load backup system: ' . $e->getMessage();
    $warnings = ['Service initialization failed'];
    $totalBackups = 0;
    $totalSize = 0;
    $backups = [];
}

if (!function_exists('formatBytes')) {
    function formatBytes($bytes): string
    {
        if (!is_numeric($bytes)) {
            return '0 B';
        }
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max((int)$bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        return round($bytes / (1024 ** $pow), 2) . ' ' . $units[$pow];
    }
}

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
                    <i class="admin-header-icon fas fa-database"></i>
                    <div class="admin-header-text">
                        <h1>Database Backup Monitor</h1>
                        <p>Monitor and manage database backups</p>
                    </div>
                </div>

                <div class="admin-header-actions">
                    <button id="manual-backup-btn" class="admin-btn admin-btn-primary">
                        <i class="fas fa-database"></i>Create Manual Backup
                    </button>
                    <button id="cleanup-old-btn" class="admin-btn admin-btn-secondary">
                        <i class="fas fa-broom"></i>Cleanup Old Files
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Flash Messages - Let a global toast system handle all messages -->
    <div id="flash-messages-data" data-php-messages="<?= htmlspecialchars(json_encode($flashMessages)) ?>" style="display: none;"></div>

    <!-- Main Content -->
    <main>
        <div class="admin-layout-main">
            <div class="admin-content">

                <!-- System Health Status -->
                <div class="admin-card admin-glow-<?= $healthStatus === 'healthy' ? 'success' : ($healthStatus === 'warning' ? 'warning' : 'error') ?>">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="fas fa-<?= $healthStatus === 'healthy' ? 'check-circle' : ($healthStatus === 'warning' ? 'exclamation-triangle' : 'times-circle') ?>"></i>
                            System Health Status
                        </h3>
                    </div>
                    <div class="admin-card-body">
                        <div style="display: flex; align-items: center; margin-bottom: 1rem;">
                            <div style="background: var(--admin-<?= $healthStatus === 'healthy' ? 'success' : ($healthStatus === 'warning' ? 'warning' : 'error') ?>-bg); color: var(--admin-<?= $healthStatus === 'healthy' ? 'success' : ($healthStatus === 'warning' ? 'warning' : 'error') ?>); padding: 1rem; border-radius: 50%; margin-right: 1rem; font-size: 1.5rem;">
                                <i class="fas fa-<?= $healthStatus === 'healthy' ? 'check' : ($healthStatus === 'warning' ? 'exclamation-triangle' : 'times') ?>"></i>
                            </div>
                            <div>
                                <h4 style="margin: 0; color: var(--admin-text-primary); font-size: 1.25rem; font-weight: 600;">
                                    <?= ucfirst($healthStatus) ?>
                                </h4>
                                <p style="margin: 0; color: var(--admin-text-muted); font-size: 0.875rem;">
                                    <?= htmlspecialchars($healthMessage) ?>
                                </p>
                            </div>
                        </div>

                        <?php if (!empty($warnings)): ?>
                        <div style="background: var(--admin-warning-bg); border: 1px solid var(--admin-warning-border); border-radius: var(--admin-border-radius); padding: 1rem; margin-top: 1rem;">
                            <h5 style="margin: 0 0 0.5rem 0; color: var(--admin-warning); font-size: 0.875rem; font-weight: 600;">
                                <i class="fas fa-exclamation-triangle"></i> Recommendations:
                            </h5>
                            <ul style="margin: 0; padding-left: 1.5rem; color: var(--admin-text-secondary);">
                                <?php foreach ($warnings as $warning): ?>
                                    <li style="margin-bottom: 0.25rem;"><?= htmlspecialchars($warning) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Statistics Grid -->
                <div class="admin-stats-grid">
                    <!-- Total Backups -->
                    <div class="admin-stat-card">
                        <div class="admin-stat-content">
                            <div class="admin-stat-icon admin-stat-icon-primary">
                                <i class="fas fa-archive"></i>
                            </div>
                            <div class="admin-stat-details">
                                <h3>Total Backups</h3>
                                <p><?= $totalBackups ?></p>
                                <span>backup files</span>
                            </div>
                        </div>
                    </div>

                    <!-- Total Size -->
                    <div class="admin-stat-card">
                        <div class="admin-stat-content">
                            <div class="admin-stat-icon admin-stat-icon-success">
                                <i class="fas fa-hdd"></i>
                            </div>
                            <div class="admin-stat-details">
                                <h3>Total Size</h3>
                                <p><?= formatBytes($totalSize) ?></p>
                                <span>storage used</span>
                            </div>
                        </div>
                    </div>

                    <!-- Latest Backup -->
                    <div class="admin-stat-card">
                        <div class="admin-stat-content">
                            <div class="admin-stat-icon admin-stat-icon-warning">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="admin-stat-details">
                                <h3>Latest Backup</h3>
                                <p>
                                    <?php
                                    if (!empty($backups) && isset($backups[0]['created_at']) && is_numeric($backups[0]['created_at'])) {
                                        echo date('M j, H:i', (int)$backups[0]['created_at']);
                                    } else {
                                        echo 'None';
                                    }
                                    ?>
                                </p>
                                <span>last created</span>
                            </div>
                        </div>
                    </div>

                    <!-- Average Size -->
                    <div class="admin-stat-card">
                        <div class="admin-stat-content">
                            <div class="admin-stat-icon admin-stat-icon-error">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="admin-stat-details">
                                <h3>Average Size</h3>
                                <p><?= $totalBackups > 0 ? formatBytes($totalSize / $totalBackups) : '0 B' ?></p>
                                <span>per backup</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Backup Files Table -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="fas fa-list"></i>Backup Files
                        </h3>
                    </div>
                    <div class="admin-card-body">
                        <?php if (empty($backups)): ?>
                        <div style="text-align: center; padding: 3rem;">
                            <div style="font-size: 4rem; color: var(--admin-text-muted); margin-bottom: 1rem;">
                                <i class="fas fa-inbox"></i>
                            </div>
                            <h3 style="color: var(--admin-text-primary); margin-bottom: 1rem;">No backup files found</h3>
                            <p style="color: var(--admin-text-muted);">Automatic backups run daily at 2:00 AM</p>
                        </div>
                        <?php else: ?>
                        <div class="admin-table-container">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Filename</th>
                                        <th>Size</th>
                                        <th>Created</th>
                                        <th>Age</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($backups as $backup): ?>
                                        <?php
                                        if (!is_array($backup)) {
                                            error_log('Critical: backup is not an array: ' . gettype($backup));
                                            continue;
                                        }

                                        $filename = '';
                                        if (isset($backup['filename'])) {
                                            $filename = is_string($backup['filename']) ? $backup['filename'] : (string)$backup['filename'];
                                        }

                                        $size = isset($backup['size']) && is_numeric($backup['size']) ? (int)$backup['size'] : 0;
                                        $created_at = isset($backup['created_at']) && is_numeric($backup['created_at']) ? (int)$backup['created_at'] : time();
                                        $age_days = isset($backup['age_days']) && is_numeric($backup['age_days']) ? (int)$backup['age_days'] : 0;

                                        $path = '';
                                        if (isset($backup['path'])) {
                                            $path = is_string($backup['path']) ? $backup['path'] : (string)$backup['path'];
                                        }

                                        if (empty($filename)) {
                                            $filename = 'unknown_backup_' . $created_at;
                                        }
                                        ?>
                                        <tr>
                                            <td>
                                                <div style="display: flex; align-items: center;">
                                                    <div style="background: var(--admin-primary-bg); color: var(--admin-primary); padding: 0.5rem; border-radius: 50%; margin-right: 0.75rem;">
                                                        <i class="fas fa-file-archive"></i>
                                                    </div>
                                                    <span style="font-weight: 500;"><?= htmlspecialchars($filename) ?></span>
                                                </div>
                                            </td>
                                            <td><?= formatBytes($size) ?></td>
                                            <td><?= date('Y-m-d H:i:s', $created_at) ?></td>
                                            <td>
                                                <span class="admin-badge admin-badge-<?= $age_days > 7 ? 'error' : ($age_days > 1 ? 'warning' : 'success') ?>">
                                                    <?= $age_days ?> day<?= $age_days !== 1 ? 's' : '' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if (!empty($path) && file_exists($path)): ?>
                                                    <span class="admin-badge admin-badge-success">
                                                        <i class="fas fa-check-circle"></i>Available
                                                    </span>
                                                <?php else: ?>
                                                    <span class="admin-badge admin-badge-error">
                                                        <i class="fas fa-times-circle"></i>Missing
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="admin-table-actions">
                                                    <button class="admin-btn admin-btn-sm admin-btn-primary" data-action="download" data-filename="<?= htmlspecialchars($filename) ?>" data-tooltip="Download backup file">
                                                        <i class="fas fa-download"></i><span>Download</span>
                                                    </button>
                                                    <button class="admin-btn admin-btn-sm admin-btn-danger" data-action="delete" data-filename="<?= htmlspecialchars($filename) ?>" data-tooltip="Delete backup file" data-confirm="Are you sure you want to delete this backup file?">
                                                        <i class="fas fa-trash"></i><span>Delete</span>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <aside class="admin-sidebar">
                <!-- System Information -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="fas fa-info-circle"></i>System Information
                        </h3>
                    </div>
                    <div class="admin-card-body">
                        <div style="margin-bottom: 1.5rem;">
                            <h4 style="color: var(--admin-text-primary); font-size: 0.875rem; font-weight: 600; margin-bottom: 0.75rem;">
                                <i class="fas fa-clock" style="color: var(--admin-primary); margin-right: 0.5rem;"></i>
                                Backup Schedule
                            </h4>
                            <ul style="list-style: none; padding: 0; margin: 0; color: var(--admin-text-secondary); font-size: 0.75rem;">
                                <li style="margin-bottom: 0.5rem; padding-left: 1.5rem; position: relative;">
                                    <i class="fas fa-circle" style="position: absolute; left: 0; top: 0.25rem; color: var(--admin-success); font-size: 0.5rem;"></i>
                                    Daily backups at 2:00 AM
                                </li>
                                <li style="margin-bottom: 0.5rem; padding-left: 1.5rem; position: relative;">
                                    <i class="fas fa-circle" style="position: absolute; left: 0; top: 0.25rem; color: var(--admin-warning); font-size: 0.5rem;"></i>
                                    Weekly cleanup on Sundays at 3:00 AM
                                </li>
                                <li style="padding-left: 1.5rem; position: relative;">
                                    <i class="fas fa-circle" style="position: absolute; left: 0; top: 0.25rem; color: var(--admin-info); font-size: 0.5rem;"></i>
                                    Maximum 30 backups retained
                                </li>
                            </ul>
                        </div>

                        <div>
                            <h4 style="color: var(--admin-text-primary); font-size: 0.875rem; font-weight: 600; margin-bottom: 0.75rem;">
                                <i class="fas fa-folder" style="color: var(--admin-primary); margin-right: 0.5rem;"></i>
                                Storage Details
                            </h4>
                            <ul style="list-style: none; padding: 0; margin: 0; color: var(--admin-text-secondary); font-size: 0.75rem;">
                                <li style="margin-bottom: 0.5rem; padding-left: 1.5rem; position: relative;">
                                    <i class="fas fa-circle" style="position: absolute; left: 0; top: 0.25rem; color: var(--admin-primary); font-size: 0.5rem;"></i>
                                    Location: <code style="background: var(--admin-bg-secondary); padding: 0.125rem 0.25rem; border-radius: 0.25rem;">/storage/backups/</code>
                                </li>
                                <li style="margin-bottom: 0.5rem; padding-left: 1.5rem; position: relative;">
                                    <i class="fas fa-circle" style="position: absolute; left: 0; top: 0.25rem; color: var(--admin-info); font-size: 0.5rem;"></i>
                                    Format: Compressed SQL (gzip)
                                </li>
                                <li style="padding-left: 1.5rem; position: relative;">
                                    <i class="fas fa-circle" style="position: absolute; left: 0; top: 0.25rem; color: var(--admin-success); font-size: 0.5rem;"></i>
                                    Integrity checks enabled
                                </li>
                            </ul>
                        </div>

                        <div style="background: var(--admin-info-bg); border: 1px solid var(--admin-info-border); border-radius: var(--admin-border-radius); padding: 1rem; margin-top: 1rem;">
                            <p style="margin: 0; color: var(--admin-text-secondary); font-size: 0.75rem;">
                                <i class="fas fa-info-circle" style="color: var(--admin-info); margin-right: 0.5rem;"></i>
                                <strong>Note:</strong> This system provides comprehensive backup management with manual creation and cleanup capabilities.
                                All backup operations are logged and email notifications are sent automatically.
                            </p>
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
                        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                            <button class="admin-btn admin-btn-primary" style="width: 100%; justify-content: center;" onclick="createManualBackup()">
                                <i class="fas fa-database"></i>Create Backup
                            </button>
                            <button class="admin-btn admin-btn-warning" style="width: 100%; justify-content: center;" onclick="cleanupOldBackups()">
                                <i class="fas fa-broom"></i>Cleanup Old Files
                            </button>
                            <a href="/index.php?page=system_monitor" class="admin-btn admin-btn-secondary" style="width: 100%; justify-content: center;">
                                <i class="fas fa-chart-line"></i>System Monitor
                            </a>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </main>

    <!-- Admin Scripts -->
    <script src="/public/assets/js/admin.js"></script>
    <script>
        // Initialize the page when DOM and admin panel are ready
        document.addEventListener('DOMContentLoaded', function() {
            // Wait for an admin panel to be available
            const initializeWhenReady = () => {
                if (window.adminPanel) {
                    // Process PHP flash messages through a global toast system
                    const flashData = document.getElementById('flash-messages-data');
                    if (flashData && flashData.dataset.phpMessages) {
                        try {
                            const messages = JSON.parse(flashData.dataset.phpMessages);
                            if (window.showToast && Object.keys(messages).length > 0) {
                                Object.keys(messages).forEach(type => {
                                    if (Array.isArray(messages[type])) {
                                        messages[type].forEach(message => {
                                            const text = message.text || message;
                                            window.showToast(text, type);
                                        });
                                    }
                                });
                            }
                        } catch (error) {
                            console.warn('Failed to parse PHP flash messages:', error);
                        }
                    }

                    // Initialize button handlers
                    initializeBackupHandlers();
                } else {
                    // Retry in 100 ms if an admin panel not ready
                    setTimeout(initializeWhenReady, 100);
                }
            };

            initializeWhenReady();
        });

        // Initialize backup-specific handlers
        function initializeBackupHandlers() {
            // Manual backup button handlers
            const manualBackupBtn = document.getElementById('manual-backup-btn');
            const cleanupBtn = document.getElementById('cleanup-old-btn');

            if (manualBackupBtn) {
                manualBackupBtn.addEventListener('click', createManualBackup);
            }

            if (cleanupBtn) {
                cleanupBtn.addEventListener('click', cleanupOldBackups);
            }

            // Download buttons
            document.querySelectorAll('[data-action="download"]').forEach(button => {
                button.addEventListener('click', function() {
                    const filename = this.getAttribute('data-filename');
                    downloadBackup(filename);
                });
            });

            // Delete buttons are handled by admin.js confirm a system
            document.querySelectorAll('[data-action="delete"]').forEach(button => {
                button.addEventListener('click', function(e) {
                    if (e.target.closest('[data-confirm]')) {
                        // Let admin.js handle the confirmation, then delete
                        const filename = this.getAttribute('data-filename');
                        setTimeout(() => {
                            deleteBackup(filename);
                        }, 100);
                    }
                });
            });
        }

        // Manual backup functionality using API
        async function createManualBackup() {
            if (!confirm('Create a manual backup now? This may take a few moments.')) {
                return;
            }

            try {
                if (window.showToast) {
                    window.showToast('Creating manual backup... Please wait.', 'info');
                }

                const response = await fetch('https://darkheim.net/page/api/admin/manual_backup.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const data = await response.json();

                // Handle flash messages from API response
                if (data.flash_messages && window.showToast) {
                    Object.keys(data.flash_messages).forEach(type => {
                        const messages = data.flash_messages[type];
                        if (Array.isArray(messages)) {
                            messages.forEach(message => {
                                const text = message.text || message;
                                window.showToast(text, type);
                            });
                        }
                    });
                }

                if (data.success) {
                    // Refresh page after successful backup
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    if (window.showToast && data.message) {
                        window.showToast(data.message, 'error');
                    }
                }
            } catch (error) {
                console.error('Manual backup error:', error);
                if (window.showToast) {
                    window.showToast('Failed to create manual backup: Network error', 'error');
                }
            }
        }

        // Cleanup functionality using API
        async function cleanupOldBackups() {
            if (!confirm('Clean up old backup files? This action cannot be undone.')) {
                return;
            }

            try {
                if (window.showToast) {
                    window.showToast('Cleaning up old backups... Please wait.', 'info');
                }

                const response = await fetch('https://darkheim.net/page/api/admin/cleanup_old_backups.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const data = await response.json();

                // Handle flash messages from API response
                if (data.flash_messages && window.showToast) {
                    Object.keys(data.flash_messages).forEach(type => {
                        const messages = data.flash_messages[type];
                        if (Array.isArray(messages)) {
                            messages.forEach(message => {
                                const text = message.text || message;
                                window.showToast(text, type);
                            });
                        }
                    });
                }

                if (data.success) {
                    // Refresh page after successful cleanup
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    if (window.showToast && data.message) {
                        window.showToast(data.message, 'error');
                    }
                }
            } catch (error) {
                console.error('Cleanup error:', error);
                if (window.showToast) {
                    window.showToast('Failed to cleanup old backups: Network error', 'error');
                }
            }
        }

        // Download backup functionality using API
        async function downloadBackup(filename) {
            try {
                if (window.showToast) {
                    window.showToast(`Preparing download for ${filename}...`, 'info');
                }

                // Create a download link using API
                const downloadUrl = `https://darkheim.net/page/api/admin/download_backup.php?filename=${encodeURIComponent(filename)}`;

                // Create a temporary link and trigger download
                const link = document.createElement('a');
                link.href = downloadUrl;
                link.download = filename;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);

                if (window.showToast) {
                    window.showToast(`Download started: ${filename}`, 'success');
                }
            } catch (error) {
                console.error('Download error:', error);
                if (window.showToast) {
                    window.showToast(`Failed to download ${filename}`, 'error');
                }
            }
        }

        // Delete backup functionality using API
        async function deleteBackup(filename) {
            try {
                const response = await fetch('https://darkheim.net/page/api/admin/backup_management.php', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ filename: filename })
                });

                const data = await response.json();

                // Handle flash messages from API response
                if (data.flash_messages && window.showToast) {
                    Object.keys(data.flash_messages).forEach(type => {
                        const messages = data.flash_messages[type];
                        if (Array.isArray(messages)) {
                            messages.forEach(message => {
                                const text = message.text || message;
                                window.showToast(text, type);
                            });
                        }
                    });
                }

                if (data.success) {
                    // Remove row from the table instead of full page reload
                    const row = document.querySelector(`[data-filename="${filename}"]`).closest('tr');
                    if (row) {
                        row.remove();

                        // Check if the table is now empty
                        const tbody = document.querySelector('.admin-table tbody');
                        if (tbody && tbody.children.length === 0) {
                            setTimeout(() => {
                                location.reload();
                            }, 1000);
                        }
                    }
                } else {
                    if (window.showToast && data.message) {
                        window.showToast(data.message, 'error');
                    }
                }
            } catch (error) {
                console.error('Delete error:', error);
                if (window.showToast) {
                    window.showToast(`Failed to delete ${filename}: Network error`, 'error');
                }
            }
        }
    </script>
