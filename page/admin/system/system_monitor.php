<?php

/**
 * System Monitor Page - DARK ADMIN THEME
 * Administrative interface for system monitoring with modern dark theme
 *
 * @author GitHub Copilot
 */

declare(strict_types=1);

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
                break;

            case 'stats':
                $response = $logMonitor->getLogStatistics();
                break;

            case 'clear_logs':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $response = $logMonitor->clearLogs();
                } else {
                    $response = ['error' => 'Only POST requests allowed for log clearing'];
                }
                break;

            default:
                $response = ['error' => 'Unknown action type: ' . ($_GET['type'] ?? 'none')];
        }
    } catch (Exception $e) {
        $response = ['error' => 'Internal error: ' . $e->getMessage()];
    }

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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .log-container {
            background: var(--admin-bg-primary);
            border: 1px solid var(--admin-border);
            border-radius: var(--admin-border-radius);
            max-height: 400px;
            overflow-y: auto;
            padding: 1rem;
            font-family: var(--admin-font-mono);
            font-size: 0.75rem;
            line-height: 1.4;
        }
        .log-entry {
            margin-bottom: 0.25rem;
            padding: 0.25rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        .log-entry:last-child {
            border-bottom: none;
        }
        .log-error { color: var(--admin-error-light); }
        .log-warning { color: var(--admin-warning-light); }
        .log-info { color: var(--admin-info-light); }
        .log-debug { color: var(--admin-text-muted); }
        .log-loading {
            text-align: center;
            color: var(--admin-text-muted);
            padding: 2rem;
        }
        .log-loading i {
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    </style>

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
                <a href="/index.php?page=system_monitor" class="admin-nav-link" style="background-color: var(--admin-primary-bg); color: var(--admin-primary-light); border-color: var(--admin-primary-border);">
                    <i class="fas fa-chart-line"></i>
                    <span>System Monitor</span>
                </a>
                <a href="/index.php?page=backup_monitor" class="admin-nav-link">
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

        // Function to load logs via AJAX
        function loadLogs() {
            const logContainer = document.getElementById('log-container');
            const logType = document.getElementById('log-type').value;
            const logLevel = document.getElementById('log-level').value;
            const logLines = document.getElementById('log-lines').value;

            logContainer.innerHTML = '<div class="log-loading"><i class="fas fa-spinner"></i> Loading logs...</div>';

            window.adminPanel.request(`/index.php?page=system_monitor&action=ajax&type=logs&log_type=${logType}&level=${logLevel}&lines=${logLines}`)
                .then(data => {
                    if (data.error) {
                        logContainer.innerHTML = `<div style="color: var(--admin-error);">Error: ${data.error}</div>`;
                    } else if (data.logs && data.logs.length > 0) {
                        const logsHtml = data.logs.map(log =>
                            `<div class="log-entry ${log.level ? 'log-' + log.level.toLowerCase() : ''}">${escapeHtml(log.message)}</div>`
                        ).join('');
                        logContainer.innerHTML = logsHtml;

                        // Apply deprecated filter if active
                        if (hideDeprecated) {
                            toggleDeprecatedMessages();
                            toggleDeprecatedMessages(); // Call twice to reapply
                        }
                    } else {
                        logContainer.innerHTML = '<div style="color: var(--admin-text-muted); text-align: center; padding: 2rem;">No logs found</div>';
                    }
                })
                .catch(error => {
                    console.error('Error loading logs:', error);
                    logContainer.innerHTML = '<div style="color: var(--admin-error); text-align: center; padding: 2rem;">Failed to load logs</div>';
                });
        }

        // Function to clear logs
        function clearLogs() {
            if (!confirm('Are you sure you want to clear all log files? This action cannot be undone.')) {
                return;
            }

            window.adminPanel.request('/index.php?page=system_monitor&action=ajax&type=clear_logs', {
                method: 'POST'
            })
            .then(data => {
                if (data.error) {
                    window.adminPanel.showFlashMessage('error', 'Error clearing logs: ' + data.error);
                } else {
                    const message = `Logs cleared successfully! Files processed: ${data.files_cleared}`;
                    if (data.errors && data.errors.length > 0) {
                        window.adminPanel.showFlashMessage('warning', message + ' (Some errors occurred)');
                    } else {
                        window.adminPanel.showFlashMessage('success', message);
                    }
                    refreshAllData();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                window.adminPanel.showFlashMessage('error', 'An error occurred while clearing logs');
            });
        }

        // Function to refresh all data
        function refreshAllData() {
            window.adminPanel.showFlashMessage('info', 'Refreshing system data...');
            setTimeout(() => {
                location.reload();
            }, 500);
        }

        // Helper function to escape HTML
        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }

        // Load logs on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadLogs();
        });
    </script>
