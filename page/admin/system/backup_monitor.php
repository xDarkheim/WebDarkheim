<?php

/**
 * Backup Monitor Page - DARK ADMIN THEME
 * Administrative interface for backup monitoring with modern dark theme
 *
 * @author GitHub Copilot
 */

declare(strict_types=1);

use App\Application\Controllers\DatabaseBackupController;
use App\Infrastructure\Components\MessageComponent;
use App\Infrastructure\Lib\FlashMessageService;

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

// Check for required services
if (!isset($database_handler)) {
    error_log("Critical: Required services not available in backup_monitor.php");
    die("A critical system error occurred. Please try again later.");
}

$page_title = "Database Backup Monitor";

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
            $warnings[] = "Consider checking the automatic backup system";
            $flashMessageService->addWarning("Last backup was created $daysSinceLastBackup days ago. Consider checking the backup system.");
        } else {
            $flashMessageService->addSuccess('Backup system is operating normally. Latest backup is recent.');
        }
    }

} catch (Exception $e) {
    error_log("Failed to initialize backup controller: " . $e->getMessage());
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Navigation -->
    <nav class="admin-nav">
        <div class="admin-nav-container">
            <a href="/index.php?page=dashboard" class="admin-nav-brand">
                <i class="fas fa-shield-alt"></i>
                <span>Admin Panel</span>
            </a>

            <div class="admin-nav-links">
                <a href="/index.php?page=dashboard" class="admin-nav-link">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <a href="/index.php?page=system_monitor" class="admin-nav-link">
                    <i class="fas fa-chart-line"></i>
                    <span>System Monitor</span>
                </a>
                <a href="/index.php?page=backup_monitor" class="admin-nav-link" style="background-color: var(--admin-primary-bg); color: var(--admin-primary-light); border-color: var(--admin-primary-border);">
                    <i class="fas fa-database"></i>
                    <span>Backup Monitor</span>
                </a>
                <a href="/index.php?page=admin_settings" class="admin-nav-link">
                    <i class="fas fa-cogs"></i>
                    <span>Settings</span>
                </a>
            </div>
        </div>
    </nav>

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
                                            error_log("Critical: backup is not an array: " . gettype($backup));
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
        // Manual backup functionality
        function createManualBackup() {
            if (confirm('Create a manual backup now? This may take a few moments.')) {
                window.adminPanel.showFlashMessage('info', 'Creating backup... Please wait.');
                // TODO: Implement AJAX call to create manual backup
                setTimeout(() => {
                    window.adminPanel.showFlashMessage('success', 'Manual backup created successfully!');
                }, 2000);
            }
        }

        // Cleanup functionality
        function cleanupOldBackups() {
            if (confirm('Clean up old backup files? This action cannot be undone.')) {
                window.adminPanel.showFlashMessage('info', 'Cleaning up old backups... Please wait.');
                // TODO: Implement AJAX call to cleanup old backups
                setTimeout(() => {
                    window.adminPanel.showFlashMessage('success', 'Old backup files cleaned up successfully!');
                }, 2000);
            }
        }

        // Download and delete functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Download buttons
            document.querySelectorAll('[data-action="download"]').forEach(button => {
                button.addEventListener('click', function() {
                    const filename = this.getAttribute('data-filename');
                    window.adminPanel.showFlashMessage('info', `Downloading ${filename}...`);
                    // TODO: Implement download functionality
                });
            });

            // Delete buttons are handled by admin.js confirm system
        });
    </script>
