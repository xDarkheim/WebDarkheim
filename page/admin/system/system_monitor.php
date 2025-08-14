<?php

/**
 * System Monitor Page - DARK ADMIN THEME
 * Administrative interface for system monitoring with modern dark theme
 *
 * @author GitHub Copilot
 */

declare(strict_types=1);

// Include required components
use App\Application\Components\AdminNavigation;

use App\Application\Controllers\LogMonitorController;

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

// Create unified navigation after authentication check
$adminNavigation = new AdminNavigation($serviceProvider->getAuth());

$page_title = "System Monitor";

// Initialize monitor controller
$logMonitor = new LogMonitorController();

// Handle AJAX requests
if (isset($_GET['action']) && $_GET['action'] === 'ajax') {
    header('Content-Type: application/json');

    $response = [];

    try {
        switch ($_GET['type'] ?? '') {
            case 'status':
                $response = $logMonitor->getSystemStatus();
                break;

            case 'logs':
                $logType = $_GET['log_type'] ?? 'app';
                $lines = (int)($_GET['lines'] ?? 100);
                $level = $_GET['level'] ?? '';
                $search = $_GET['search'] ?? '';
                $response = $logMonitor->getLogs($logType, $lines, $level, $search);

                // Don't add success flash messages for regular log loading
                // Only add messages for errors or special cases
                if (isset($response['error'])) {
                    $flashMessageService->addError('Failed to load logs: ' . $response['error']);
                }
                break;

            case 'stats':
                $response = $logMonitor->getLogStatistics();
                break;

            case 'clear_logs':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $result = $logMonitor->clearLogs();

                    if (isset($result['error'])) {
                        $flashMessageService->addError('Error clearing logs: ' . $result['error']);
                        $response = $result;
                    } else {
                        $message = 'Logs cleared successfully! Files processed: ' . ($result['files_cleared'] ?? 0);
                        if (!empty($result['errors'])) {
                            $flashMessageService->addWarning($message . ' (Some errors occurred)');
                        } else {
                            $flashMessageService->addSuccess($message);
                        }
                        $response = $result;
                    }
                } else {
                    $flashMessageService->addError('Only POST requests allowed for log clearing');
                    $response = ['error' => 'Only POST requests allowed for log clearing'];
                }
                break;

            case 'export':
                try {
                    $exportData = [
                        'system_status' => $logMonitor->getSystemStatus(),
                        'log_statistics' => $logMonitor->getLogStatistics(),
                        'recent_logs' => $logMonitor->getLogs('app', 100),
                        'export_time' => date('Y-m-d H:i:s'),
                        'server_info' => [
                            'php_version' => PHP_VERSION,
                            'server_name' => $_SERVER['SERVER_NAME'] ?? 'Unknown',
                            'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown'
                        ]
                    ];

                    $flashMessageService->addSuccess('System data exported successfully');
                    $response = $exportData;
                } catch (Exception $e) {
                    $flashMessageService->addError('Failed to export system data: ' . $e->getMessage());
                    $response = ['error' => 'Export failed: ' . $e->getMessage()];
                }
                break;

            case 'refresh':
                try {
                    $response = [
                        'system_status' => $logMonitor->getSystemStatus(),
                        'log_statistics' => $logMonitor->getLogStatistics(),
                        'logs' => $logMonitor->getLogs('app', 50)
                    ];
                    $flashMessageService->addSuccess('System data refreshed successfully');
                } catch (Exception $e) {
                    $flashMessageService->addError('Failed to refresh system data: ' . $e->getMessage());
                    $response = ['error' => 'Refresh failed: ' . $e->getMessage()];
                }
                break;

            default:
                $flashMessageService->addError('Unknown action type requested');
                $response = ['error' => 'Unknown action type: ' . ($_GET['type'] ?? 'none')];
        }
    } catch (Exception $e) {
        $flashMessageService->addError('System error: ' . $e->getMessage());
        $response = ['error' => 'Internal error: ' . $e->getMessage()];
    }

    // Add flash messages to AJAX response
    $response['flash_messages'] = $flashMessageService->getAllMessages();

    echo json_encode($response);
    exit();
}

// Get data for initial display
$systemStatus = $logMonitor->getSystemStatus();
$logStats = $logMonitor->getLogStatistics();
$appLogs = $logMonitor->getLogs('app', 50);

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
                    <i class="admin-header-icon fas fa-chart-line"></i>
                    <div class="admin-header-text">
                        <h1>System Monitor</h1>
                        <p>Real-time system monitoring and log analysis</p>
                    </div>
                </div>

                <div class="admin-header-actions">
                    <button type="button" class="admin-btn admin-btn-warning" onclick="clearLogs()" data-tooltip="Clear all log files">
                        <i class="fas fa-trash-alt"></i>Clear Logs
                    </button>
                    <button type="button" class="admin-btn admin-btn-primary" onclick="refreshAllData()" data-tooltip="Refresh all system data">
                        <i class="fas fa-sync-alt"></i>Refresh
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Flash Messages - Let global toast system handle all messages -->
    <div id="flash-messages-data" data-php-messages="<?= htmlspecialchars(json_encode($flashMessages)) ?>" style="display: none;"></div>

    <!-- Main Content -->
    <main>
        <div class="admin-layout-main">
            <div class="admin-content">

                <!-- Status Cards Grid -->
                <div class="admin-stats-grid">
                    <!-- Database Status -->
                    <div class="admin-stat-card">
                        <div class="admin-stat-content">
                            <div class="admin-stat-icon admin-stat-icon-primary">
                                <i class="fas fa-database"></i>
                            </div>
                            <div class="admin-stat-details">
                                <h3>Database</h3>
                                <p><?= ucfirst($systemStatus['database']['status']) ?></p>
                                <span><?= $systemStatus['database']['tables_count'] ?? 'N/A' ?> tables</span>
                            </div>
                        </div>
                        <div style="position: absolute; top: 1rem; right: 1rem;">
                            <span class="admin-badge admin-badge-<?= $systemStatus['database']['status'] === 'connected' ? 'success' : 'error' ?>">
                                <i class="fas fa-<?= $systemStatus['database']['status'] === 'connected' ? 'check' : 'times' ?>"></i>
                            </span>
                        </div>
                    </div>

                    <!-- Backup Status -->
                    <div class="admin-stat-card">
                        <div class="admin-stat-content">
                            <div class="admin-stat-icon admin-stat-icon-success">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <div class="admin-stat-details">
                                <h3>Backups</h3>
                                <p><?= $systemStatus['backup']['total_backups'] ?></p>
                                <span><?= $systemStatus['backup']['total_size'] ?? '0 B' ?></span>
                            </div>
                        </div>
                        <div style="position: absolute; top: 1rem; right: 1rem;">
                            <span class="admin-badge admin-badge-<?= $systemStatus['backup']['status'] === 'ok' ? 'success' : 'warning' ?>">
                                <i class="fas fa-<?= $systemStatus['backup']['status'] === 'ok' ? 'check' : 'exclamation-triangle' ?>"></i>
                            </span>
                        </div>
                    </div>

                    <!-- Performance -->
                    <div class="admin-stat-card">
                        <div class="admin-stat-content">
                            <div class="admin-stat-icon admin-stat-icon-warning">
                                <i class="fas fa-tachometer-alt"></i>
                            </div>
                            <div class="admin-stat-details">
                                <h3>Memory</h3>
                                <p><?= $systemStatus['performance']['memory_usage']['current'] ?></p>
                                <span>PHP <?= $systemStatus['performance']['php_version'] ?></span>
                            </div>
                        </div>
                        <div style="position: absolute; top: 1rem; right: 1rem;">
                            <span class="admin-badge admin-badge-success">
                                <i class="fas fa-check"></i>
                            </span>
                        </div>
                    </div>

                    <!-- Logs Summary -->
                    <div class="admin-stat-card">
                        <div class="admin-stat-content">
                            <div class="admin-stat-icon admin-stat-icon-error">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <div class="admin-stat-details">
                                <h3>Logs</h3>
                                <p>
                                    <?php
                                    $totalErrors = 0;
                                    foreach ($logStats as $stats) {
                                        $totalErrors += $stats['error_count'];
                                    }
                                    echo $totalErrors;
                                    ?> errors
                                </p>
                                <span><?= count($logStats) ?> log files</span>
                            </div>
                        </div>
                        <div style="position: absolute; top: 1rem; right: 1rem;">
                            <span class="admin-badge admin-badge-<?= $totalErrors > 0 ? 'warning' : 'success' ?>">
                                <i class="fas fa-<?= $totalErrors > 0 ? 'exclamation-triangle' : 'check' ?>"></i>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Log Viewer -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="fas fa-scroll"></i>Log Viewer
                        </h3>
                        <div style="display: flex; gap: 0.5rem; align-items: center;">
                            <select id="log-type" class="admin-input admin-select" style="width: auto; min-width: 140px;" onchange="loadLogs()">
                                <option value="app">Application Logs</option>
                                <option value="php_errors">PHP Error Logs</option>
                            </select>

                            <select id="log-level" class="admin-input admin-select" style="width: auto; min-width: 120px;" onchange="loadLogs()">
                                <option value="">All Levels</option>
                                <option value="DEBUG">Debug</option>
                                <option value="INFO">Info</option>
                                <option value="WARNING">Warning</option>
                                <option value="ERROR">Error</option>
                                <option value="CRITICAL">Critical</option>
                            </select>

                            <select id="log-lines" class="admin-input admin-select" style="width: auto; min-width: 100px;" onchange="loadLogs()">
                                <option value="50">50 lines</option>
                                <option value="100" selected>100 lines</option>
                                <option value="200">200 lines</option>
                                <option value="500">500 lines</option>
                            </select>

                            <button type="button" class="admin-btn admin-btn-sm admin-btn-secondary" id="toggle-deprecated" onclick="toggleDeprecatedMessages()" data-tooltip="Toggle deprecated message visibility">
                                <i class="fas fa-eye-slash"></i>Hide Deprecated
                            </button>
                        </div>
                    </div>
                    <div class="admin-card-body">
                        <div id="log-container" class="log-container">
                            <div class="log-loading">
                                <i class="fas fa-spinner"></i> Loading logs...
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <aside class="admin-sidebar">
                <!-- Log Statistics -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="fas fa-chart-bar"></i>Log Statistics
                        </h3>
                    </div>
                    <div class="admin-card-body">
                        <div id="log-statistics" style="display: flex; flex-direction: column; gap: 1rem;">
                            <?php foreach ($logStats as $type => $stats): ?>
                            <div style="background: var(--admin-bg-secondary); padding: 1rem; border-radius: var(--admin-border-radius); border: 1px solid var(--admin-border);">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                    <h4 style="margin: 0; color: var(--admin-text-primary); font-size: 0.875rem; font-weight: 600;">
                                        <?= ucfirst(str_replace('_', ' ', $type)) ?>
                                    </h4>
                                    <span class="admin-badge admin-badge-gray" style="font-size: 0.65rem;">
                                        <?= $stats['formatted_size'] ?>
                                    </span>
                                </div>
                                <div style="display: flex; justify-content: space-between; font-size: 0.75rem;">
                                    <span style="color: var(--admin-text-muted);"><?= number_format($stats['line_count']) ?> lines</span>
                                    <div style="display: flex; gap: 0.75rem;">
                                        <span style="color: var(--admin-error);"><?= $stats['error_count'] ?> errors</span>
                                        <span style="color: var(--admin-warning);"><?= $stats['warning_count'] ?> warnings</span>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- System Info -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <i class="fas fa-info-circle"></i>System Information
                        </h3>
                    </div>
                    <div class="admin-card-body">
                        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 0; border-bottom: 1px solid var(--admin-border);">
                                <span style="color: var(--admin-text-muted); font-size: 0.75rem;">PHP Version:</span>
                                <span style="color: var(--admin-text-primary); font-size: 0.75rem; font-weight: 500;"><?= $systemStatus['performance']['php_version'] ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 0; border-bottom: 1px solid var(--admin-border);">
                                <span style="color: var(--admin-text-muted); font-size: 0.75rem;">Memory Limit:</span>
                                <span style="color: var(--admin-text-primary); font-size: 0.75rem; font-weight: 500;"><?= $systemStatus['performance']['memory_usage']['limit'] ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 0; border-bottom: 1px solid var(--admin-border);">
                                <span style="color: var(--admin-text-muted); font-size: 0.75rem;">Peak Memory:</span>
                                <span style="color: var(--admin-text-primary); font-size: 0.75rem; font-weight: 500;"><?= $systemStatus['performance']['memory_usage']['peak'] ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 0; border-bottom: 1px solid var(--admin-border);">
                                <span style="color: var(--admin-text-muted); font-size: 0.75rem;">Disk Free:</span>
                                <span style="color: var(--admin-text-primary); font-size: 0.75rem; font-weight: 500;"><?= $systemStatus['performance']['disk_space']['free'] ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 0;">
                                <span style="color: var(--admin-text-muted); font-size: 0.75rem;">Disk Total:</span>
                                <span style="color: var(--admin-text-primary); font-size: 0.75rem; font-weight: 500;"><?= $systemStatus['performance']['disk_space']['total'] ?></span>
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
                        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                            <button class="admin-btn admin-btn-primary" style="width: 100%; justify-content: center;" onclick="refreshAllData()">
                                <i class="fas fa-sync-alt"></i>Refresh Data
                            </button>
                            <button class="admin-btn admin-btn-warning" style="width: 100%; justify-content: center;" onclick="clearLogs()">
                                <i class="fas fa-trash-alt"></i>Clear All Logs
                            </button>
                            <a href="/index.php?page=backup_monitor" class="admin-btn admin-btn-secondary" style="width: 100%; justify-content: center;">
                                <i class="fas fa-database"></i>Backup Monitor
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
        // Function to toggle visibility of deprecated messages
        let hideDeprecated = false;

        function toggleDeprecatedMessages() {
            // Use admin panel method for consistency
            if (window.adminPanel && window.adminPanel.toggleDeprecatedMessages) {
                window.adminPanel.toggleDeprecatedMessages();
            } else {
                // Fallback for direct implementation
                hideDeprecated = !hideDeprecated;
                const button = document.getElementById('toggle-deprecated');
                const logEntries = document.querySelectorAll('.log-entry');

                if (hideDeprecated) {
                    button.innerHTML = '<i class="fas fa-eye"></i> Show Deprecated';
                    button.classList.remove('admin-btn-secondary');
                    button.classList.add('admin-btn-warning');

                    logEntries.forEach(entry => {
                        if (entry.textContent.includes('E_STRICT is deprecated') ||
                            entry.textContent.includes('Constant E_STRICT is deprecated')) {
                            entry.style.display = 'none';
                        }
                    });
                } else {
                    button.innerHTML = '<i class="fas fa-eye-slash"></i> Hide Deprecated';
                    button.classList.remove('admin-btn-warning');
                    button.classList.add('admin-btn-secondary');

                    logEntries.forEach(entry => {
                        entry.style.display = '';
                    });
                }
            }
        }

        // Function to load logs via AJAX - use admin panel method
        function loadLogs() {
            if (window.adminPanel && window.adminPanel.loadLogs) {
                window.adminPanel.loadLogs();
            }
        }

        // Function to clear logs - use admin panel method
        function clearLogs() {
            if (window.adminPanel && window.adminPanel.clearSystemLogs) {
                window.adminPanel.clearSystemLogs();
            }
        }

        // Function to refresh all data - use admin panel method
        function refreshAllData() {
            if (window.adminPanel && window.adminPanel.refreshSystemData) {
                window.adminPanel.refreshSystemData();
            }
        }

        // Function to export system data - use admin panel method
        function exportSystemData() {
            if (window.adminPanel && window.adminPanel.exportSystemData) {
                window.adminPanel.exportSystemData();
            }
        }

        // Initialize page when DOM and admin panel are ready
        document.addEventListener('DOMContentLoaded', function() {
            // Wait for admin panel to be available
            const initializeWhenReady = () => {
                if (window.adminPanel) {
                    // Process PHP flash messages through global toast system
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

                    // Load initial logs
                    loadLogs();

                    // Add export button to sidebar
                    const exportButton = document.createElement('button');
                    exportButton.className = 'admin-btn admin-btn-secondary';
                    exportButton.style.cssText = 'width: 100%; justify-content: center; margin-top: 0.75rem;';
                    exportButton.innerHTML = '<i class="fas fa-download"></i>Export Data';
                    exportButton.onclick = exportSystemData;

                    const quickActionsBody = document.querySelector('.admin-card:last-child .admin-card-body');
                    if (quickActionsBody) {
                        quickActionsBody.appendChild(exportButton);
                    }
                } else {
                    // Retry in 100ms if admin panel not ready
                    setTimeout(initializeWhenReady, 100);
                }
            };

            initializeWhenReady();
        });
    </script>
