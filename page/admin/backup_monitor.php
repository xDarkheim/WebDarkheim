<?php

/**
 * Backup Monitor Page
 *
 * This page displays a comprehensive backup monitor for the system.
 * It includes statistics, health checks, and backup management features.
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

use App\Application\Controllers\DatabaseBackupController;
use App\Infrastructure\Components\MessageComponent;
use App\Infrastructure\Lib\FlashMessageService;

// Use global services from bootstrap.php
global $database_handler, $auth, $container;

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check authentication and admin rights
if (!$auth || !$auth->isAuthenticated() || !$auth->hasRole('admin')) {
    $_SESSION['error_message'] = "Access Denied. You do not have permission to view this page.";
    header('Location: /index.php?page=login');
    exit();
}

// Check for required services
if (!isset($database_handler) || !isset($container)) {
    error_log("Critical: Required services not available in backup_monitor.php");
    die("A critical system error occurred. Please try again later.");
}

$page_title = "Database Backup Monitor";

// Initialize FlashMessage service
$flashMessageService = new FlashMessageService();
$messageComponent = new MessageComponent($flashMessageService);

try {
    // Initialize backup controller - same as used in automatic backups
    $backupController = new DatabaseBackupController();

    // Get backup data
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
?>

<!-- Load external CSS and JS files -->
<link rel="stylesheet" href="/public/assets/css/admin-backup-monitor.css">
<meta name="csrf-token" content="">

<div class="backup-monitor-admin">
    <div class="content-container">
        <div class="admin-header">
            <div class="admin-title">
                <h1><i class="fas fa-shield-alt"></i>Database Backup Monitor</h1>
                <div class="admin-actions">
                    <button id="manual-backup-btn" class="btn btn-primary">
                        <i class="fas fa-database"></i>
                        <span class="btn-text">Create Manual Backup</span>
                        <span class="btn-loading" style="display: none;">
                            <i class="fas fa-spinner fa-spin"></i>
                            Creating...
                        </span>
                    </button>
                    <button id="cleanup-old-btn" class="btn btn-warning">
                        <i class="fas fa-broom"></i>
                        <span class="btn-text">Cleanup Old Files</span>
                        <span class="btn-loading" style="display: none;">
                            <i class="fas fa-spinner fa-spin"></i>
                            Cleaning...
                        </span>
                    </button>
                    <span class="status-badge status-<?php echo $healthStatus; ?>">
                        <?php echo ucfirst($healthStatus); ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Flash Messages Section -->
        <div class="flash-messages-container">
            <?php echo $messageComponent->renderFlashMessages(); ?>
        </div>

        <!-- System Status Section -->
        <section class="admin-section">
            <div class="admin-card status-card status-<?php echo $healthStatus; ?>">
                <div class="card-header">
                    <h2><i class="fas fa-heartbeat"></i>System Health Status</h2>
                </div>
                <div class="card-content">
                    <p class="status-message"><?php echo htmlspecialchars(string: is_string($healthMessage) ? $healthMessage : (string)$healthMessage, flags: ENT_QUOTES, encoding: 'UTF-8'); ?></p>
                    <?php if (!empty($warnings)): ?>
                        <div class="alert alert-warning">
                            <strong>Recommendations:</strong>
                            <ul>
                                <?php foreach ($warnings as $warning): ?>
                                    <li><?php echo htmlspecialchars(is_string($warning) ? $warning : (string)$warning, ENT_QUOTES, 'UTF-8'); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Statistics Section -->
        <section class="admin-section">
            <div class="stats-grid">
                <div class="stat-card stat-primary">
                    <div class="stat-content">
                        <div class="stat-info">
                            <h3>Total Backups</h3>
                            <span class="stat-number"><?php echo $totalBackups; ?></span>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-archive"></i>
                        </div>
                    </div>
                </div>

                <div class="stat-card stat-info">
                    <div class="stat-content">
                        <div class="stat-info">
                            <h3>Total Size</h3>
                            <span class="stat-number"><?php echo formatBytes($totalSize); ?></span>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-hdd"></i>
                        </div>
                    </div>
                </div>

                <div class="stat-card stat-success">
                    <div class="stat-content">
                        <div class="stat-info">
                            <h3>Latest Backup</h3>
                            <span class="stat-number"><?php
                                if (!empty($backups) && isset($backups[0]['created_at']) && is_numeric($backups[0]['created_at'])) {
                                    echo date('M j, H:i', (int)$backups[0]['created_at']);
                                } else {
                                    echo 'None';
                                }
                            ?></span>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                </div>

                <div class="stat-card stat-warning">
                    <div class="stat-content">
                        <div class="stat-info">
                            <h3>Average Size</h3>
                            <span class="stat-number"><?php echo $totalBackups > 0 ? formatBytes($totalSize / $totalBackups) : '0 B'; ?></span>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Backup Files Section -->
        <section class="admin-section">
            <div class="admin-card">
                <div class="card-header">
                    <h2><i class="fas fa-list"></i>Backup Files</h2>
                </div>
                <div class="card-content">
                    <?php if (empty($backups)): ?>
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <h3>No backup files found</h3>
                            <p>Automatic backups run daily at 2:00 AM</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
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
                                        // Detailed diagnostics for type error resolution
                                        if (!is_array($backup)) {
                                            error_log("Critical: backup is not an array: " . gettype($backup));
                                            continue;
                                        }

                                        // Enhanced protection against data type errors
                                        $filename = '';
                                        if (isset($backup['filename'])) {
                                            if (is_array($backup['filename'])) {
                                                error_log("Critical: filename is array instead of string: " . print_r($backup['filename'], true));
                                                $filename = 'error_array_filename_' . time();
                                            } else {
                                                $filename = is_string($backup['filename']) ? $backup['filename'] : (string)$backup['filename'];
                                            }
                                        }

                                        $size = isset($backup['size']) && is_numeric($backup['size']) ? (int)$backup['size'] : 0;
                                        $created_at = isset($backup['created_at']) && is_numeric($backup['created_at']) ? (int)$backup['created_at'] : time();
                                        $age_days = isset($backup['age_days']) && is_numeric($backup['age_days']) ? (int)$backup['age_days'] : 0;

                                        $path = '';
                                        if (isset($backup['path'])) {
                                            if (is_array($backup['path'])) {
                                                error_log("Critical: path is array instead of string: " . print_r($backup['path'], true));
                                                $path = '';
                                            } else {
                                                $path = is_string($backup['path']) ? $backup['path'] : (string)$backup['path'];
                                            }
                                        }

                                        // Additional check for empty filename
                                        if (empty($filename)) {
                                            error_log("Warning: Empty filename detected in backup data");
                                            $filename = 'unknown_backup_' . $created_at;
                                        }

                                        // Final check before passing to htmlspecialchars
                                        if (!is_string($filename)) {
                                            error_log("Critical: filename is still not string before overspecialises: " . gettype($filename) . " - " . print_r($filename, true));
                                            $filename = 'error_' . time();
                                        }
                                        ?>
                                        <tr>
                                            <td>
                                                <code class="filename"><?php echo htmlspecialchars($filename, ENT_QUOTES, 'UTF-8'); ?></code>
                                            </td>
                                            <td><?php echo formatBytes($size); ?></td>
                                            <td><?php echo date('Y-m-d H:i:s', $created_at); ?></td>
                                            <td>
                                                <span class="badge <?php echo $age_days > 7 ? 'badge-warning' : 'badge-success'; ?>">
                                                    <?php echo $age_days; ?> day<?php echo $age_days !== 1 ? 's' : ''; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if (!empty($path) && file_exists($path)): ?>
                                                    <span class="badge badge-success">
                                                        <i class="fas fa-check-circle"></i> Available
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge badge-danger">
                                                        <i class="fas fa-times-circle"></i> Missing
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn btn-sm btn-info" data-action="download" data-filename="<?php echo htmlspecialchars($filename, ENT_QUOTES, 'UTF-8'); ?>" title="Download backup file">
                                                        <i class="fas fa-download"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-danger" data-action="delete" data-filename="<?php echo htmlspecialchars($filename, ENT_QUOTES, 'UTF-8'); ?>" title="Delete backup file">
                                                        <i class="fas fa-trash"></i>
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
        </section>

        <!-- System Information Section -->
        <section class="admin-section">
            <div class="admin-card">
                <div class="card-header">
                    <h2><i class="fas fa-info-circle"></i>System Information</h2>
                </div>
                <div class="card-content">
                    <div class="info-grid">
                        <div class="info-column">
                            <h3>Backup Schedule</h3>
                            <ul class="info-list">
                                <li><i class="fas fa-clock"></i>Daily backups at 2:00 AM</li>
                                <li><i class="fas fa-trash-alt"></i>Weekly cleanup on Sundays at 3:00 AM</li>
                                <li><i class="fas fa-archive"></i>Maximum 30 backups retained</li>
                            </ul>
                        </div>
                        <div class="info-column">
                            <h3>Storage Details</h3>
                            <ul class="info-list">
                                <li><i class="fas fa-folder"></i>Location: <code>/storage/backups/</code></li>
                                <li><i class="fas fa-file-archive"></i>Format: Compressed SQL (gzip)</li>
                                <li><i class="fas fa-shield-check"></i>Integrity checks enabled</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <strong>Note:</strong> This system provides comprehensive backup management with manual creation and cleanup capabilities.
                        All backup operations are logged and email notifications are sent automatically. For CLI monitoring, use
                        <code>php scripts/backup.php [list|stats|health]</code>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<!-- Load external JavaScript -->
<script src="/public/assets/js/admin-backup-monitor.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, setting up enhanced debugging...');

    // Intercept all fetch requests for diagnostics
    const originalFetch = window.fetch;
    window.fetch = function(...args) {
        console.log('🌐 Fetch request:', args[0], args[1]);
        return originalFetch.apply(this, args)
            .then(response => {
                console.log('📥 Fetch response:', response.status, response.statusText);
                return response;
            })
            .catch(error => {
                console.error('❌ Fetch error:', error);
                throw error;
            });
    };

    // Add handlers to delete buttons with diagnostics
    setTimeout(function() {
        const deleteButtons = document.querySelectorAll('[data-action="delete"]');
        console.log('🔍 Found delete buttons:', deleteButtons.length);

        deleteButtons.forEach((btn, i) => {
            const filename = btn.getAttribute('data-filename');
            console.log(`🗑️ Setting up delete button ${i} for:`, filename);

            // Remove the test alert and add real functionality
            btn.replaceWith(btn.cloneNode(true));
            const newBtn = document.querySelectorAll('[data-action="delete"]')[i];

            newBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                console.log('🖱️ Delete button clicked for:', filename);

                // Checking BackupMonitor availability
                if (window.BackupMonitor && typeof window.BackupMonitor.showDeleteDialog === 'function') {
                    console.log('✅ Calling BackupMonitor.showDeleteDialog...');
                    window.BackupMonitor.showDeleteDialog(filename);
                } else {
                    console.error('❌ BackupMonitor not available');
                    alert('Error: Backup system not loaded properly');
                }
            });
        });
    }, 1000);
});
</script>
