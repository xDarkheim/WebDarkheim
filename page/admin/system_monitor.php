<?php

/**
 * System Monitor Page
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

use App\Application\Controllers\LogMonitorController;

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

?>

<!-- Include isolated styles for system monitor -->
<link rel="stylesheet" href="/themes/default/css/pages/_system-monitor.css">

<div class="system-monitor-page">
<div class="admin-layout">
    <!-- Header Section -->
    <header class="page-header">
        <div class="page-header-content">
            <div class="page-header-main">
                <h1 class="page-title">
                    <i class="fas fa-monitor-heart-rate"></i>
                    <?php echo htmlspecialchars($page_title); ?>
                </h1>
                <div class="page-header-description">
                    <p>Real-time system monitoring and log analysis</p>
                </div>
            </div>
            <div class="page-header-actions">
                <a href="/index.php?page=dashboard" class="btn btn-secondary">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <button type="button" class="btn btn-warning" onclick="clearLogs()" title="Clear all log files">
                    <i class="fas fa-trash-alt"></i> Clear Logs
                </button>
                <button type="button" class="btn btn-primary" onclick="refreshAllData()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
        </div>
    </header>

    <!-- System Status Cards -->
    <div class="status-cards-grid">
        <!-- Database Status -->
        <div class="status-card" id="database-status">
            <div class="status-card-header">
                <h3><i class="fas fa-database"></i> Database</h3>
                <span class="status-indicator" data-status="<?= $systemStatus['database']['status'] ?>"></span>
            </div>
            <div class="status-card-body">
                <div class="metric">
                    <span class="metric-label">Status:</span>
                    <span class="metric-value"><?= ucfirst($systemStatus['database']['status']) ?></span>
                </div>
                <div class="metric">
                    <span class="metric-label">Tables:</span>
                    <span class="metric-value"><?= $systemStatus['database']['tables_count'] ?? 'N/A' ?></span>
                </div>
                <div class="metric">
                    <span class="metric-label">Size:</span>
                    <span class="metric-value"><?= $systemStatus['database']['database_size'] ?? 'N/A' ?></span>
                </div>
            </div>
        </div>

        <!-- Backup Status -->
        <div class="status-card" id="backup-status">
            <div class="status-card-header">
                <h3><i class="fas fa-shield-alt"></i> Backups</h3>
                <span class="status-indicator" data-status="<?= $systemStatus['backup']['status'] ?>"></span>
            </div>
            <div class="status-card-body">
                <div class="metric">
                    <span class="metric-label">Total:</span>
                    <span class="metric-value"><?= $systemStatus['backup']['total_backups'] ?></span>
                </div>
                <div class="metric">
                    <span class="metric-label">Latest:</span>
                    <span class="metric-value"><?= $systemStatus['backup']['latest_backup']['created'] ?? 'Never' ?></span>
                </div>
                <div class="metric">
                    <span class="metric-label">Size:</span>
                    <span class="metric-value"><?= $systemStatus['backup']['total_size'] ?? '0 B' ?></span>
                </div>
            </div>
        </div>

        <!-- Performance -->
        <div class="status-card" id="performance-status">
            <div class="status-card-header">
                <h3><i class="fas fa-tachometer-alt"></i> Performance</h3>
                <span class="status-indicator" data-status="ok"></span>
            </div>
            <div class="status-card-body">
                <div class="metric">
                    <span class="metric-label">Memory:</span>
                    <span class="metric-value"><?= $systemStatus['performance']['memory_usage']['current'] ?></span>
                </div>
                <div class="metric">
                    <span class="metric-label">Disk Free:</span>
                    <span class="metric-value"><?= $systemStatus['performance']['disk_space']['free'] ?></span>
                </div>
                <div class="metric">
                    <span class="metric-label">PHP:</span>
                    <span class="metric-value"><?= $systemStatus['performance']['php_version'] ?></span>
                </div>
            </div>
        </div>

        <!-- Logs Summary -->
        <div class="status-card" id="logs-status">
            <div class="status-card-header">
                <h3><i class="fas fa-file-alt"></i> Logs</h3>
                <span class="status-indicator" data-status="ok"></span>
            </div>
            <div class="status-card-body">
                <?php foreach ($logStats as $type => $stats): ?>
                <div class="metric">
                    <span class="metric-label"><?= ucfirst(str_replace('_', ' ', $type)) ?>:</span>
                    <span class="metric-value"><?= $stats['formatted_size'] ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="system-monitor-content">
        <!-- Log Viewer Section -->
        <div class="monitor-card">
            <div class="monitor-card-header">
                <div class="monitor-card-title">
                    <h2><i class="fas fa-scroll"></i> Log Viewer</h2>
                </div>
                <div class="monitor-card-actions">
                    <div class="log-controls">
                        <label for="log-type"></label><select id="log-type" class="form-control" onchange="loadLogs()">
                            <option value="app">Application Logs</option>
                            <option value="php_errors">PHP Error Logs</option>
                        </select>

                        <label for="log-level"></label><select id="log-level" class="form-control" onchange="loadLogs()">
                            <option value="">All Levels</option>
                            <option value="DEBUG">Debug</option>
                            <option value="INFO">Info</option>
                            <option value="WARNING">Warning</option>
                            <option value="ERROR">Error</option>
                            <option value="CRITICAL">Critical</option>
                        </select>

                        <label for="log-search"></label><input type="text" id="log-search" class="form-control" placeholder="Search logs..." onkeyup="debounceSearch()">

                        <label for="log-lines"></label><select id="log-lines" class="form-control" onchange="loadLogs()">
                            <option value="50">50 lines</option>
                            <option value="100" selected>100 lines</option>
                            <option value="200">200 lines</option>
                            <option value="500">500 lines</option>
                        </select>

                        <button type="button" class="btn btn-sm btn-secondary" id="toggle-deprecated" onclick="toggleDeprecatedMessages()">
                            <i class="fas fa-eye-slash"></i> Hide Deprecated
                        </button>
                    </div>
                </div>
            </div>

            <div class="monitor-card-body">
                <div id="log-container" class="log-container">
                    <div class="log-loading">
                        <i class="fas fa-spinner fa-spin"></i> Loading logs...
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Section -->
        <div class="stats-row">
            <!-- Log Statistics -->
            <div class="monitor-card stats-card">
                <div class="monitor-card-header">
                    <div class="monitor-card-title">
                        <h3><i class="fas fa-chart-bar"></i> Log Statistics</h3>
                    </div>
                </div>
                <div class="monitor-card-body" id="log-statistics">
                    <?php foreach ($logStats as $type => $stats): ?>
                    <div class="stat-item">
                        <div class="stat-header">
                            <h4><?= ucfirst(str_replace('_', ' ', $type)) ?></h4>
                        </div>
                        <div class="stat-metrics">
                            <div class="metric-small">
                                <span class="label">Size:</span>
                                <span class="value"><?= $stats['formatted_size'] ?></span>
                            </div>
                            <div class="metric-small">
                                <span class="label">Lines:</span>
                                <span class="value"><?= number_format($stats['line_count']) ?></span>
                            </div>
                            <div class="metric-small">
                                <span class="label">Errors:</span>
                                <span class="value error"><?= $stats['error_count'] ?></span>
                            </div>
                            <div class="metric-small">
                                <span class="label">Warnings:</span>
                                <span class="value warning"><?= $stats['warning_count'] ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- System Info -->
            <div class="monitor-card stats-card">
                <div class="monitor-card-header">
                    <div class="monitor-card-title">
                        <h3><i class="fas fa-info-circle"></i> System Info</h3>
                    </div>
                </div>
                <div class="monitor-card-body">
                    <div class="info-item">
                        <span class="info-label">PHP Version:</span>
                        <span class="info-value"><?= $systemStatus['performance']['php_version'] ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Memory Limit:</span>
                        <span class="info-value"><?= $systemStatus['performance']['memory_usage']['limit'] ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Peak Memory:</span>
                        <span class="info-value"><?= $systemStatus['performance']['memory_usage']['peak'] ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Disk Total:</span>
                        <span class="info-value"><?= $systemStatus['performance']['disk_space']['total'] ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<script src="/public/assets/js/log-monitor.js?v=<?= time() ?>"></script>

<script>
// Function to toggle visibility of deprecated messages
let hideDeprecated = false;

function toggleDeprecatedMessages() {
    hideDeprecated = !hideDeprecated;
    const button = document.getElementById('toggle-deprecated');
    const logEntries = document.querySelectorAll('.log-entry');

    if (hideDeprecated) {
        button.innerHTML = '<i class="fas fa-eye"></i> Show Deprecated';
        button.classList.remove('btn-secondary');
        button.classList.add('btn-warning');

        logEntries.forEach(entry => {
            if (entry.textContent.includes('E_STRICT is deprecated') || 
                entry.textContent.includes('Constant E_STRICT is deprecated')) {
                entry.style.display = 'none';
            }
        });
    } else {
        button.innerHTML = '<i class="fas fa-eye-slash"></i> Hide Deprecated';
        button.classList.remove('btn-warning');
        button.classList.add('btn-secondary');

        logEntries.forEach(entry => {
            entry.style.display = '';
        });
    }
}

// Apply filter when loading new logs
const originalLoadLogs = window.loadLogs;
window.loadLogs = function() {
    if (originalLoadLogs) {
        originalLoadLogs();
        // Small delay to apply filter after loading
        setTimeout(() => {
            if (hideDeprecated) {
                toggleDeprecatedMessages();
                toggleDeprecatedMessages(); // Double call for proper application
            }
        }, 100);
    }
};

// Function to clear logs
function clearLogs() {
    if (!confirm('Are you sure you want to clear all log files? This action cannot be undone.')) {
        return;
    }

    const button = document.querySelector('button[onclick="clearLogs()"]');
    const originalText = button.innerHTML;

    // Show loading indicator
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Clearing...';
    button.disabled = true;

    fetch('/index.php?page=system_monitor&action=ajax&type=clear_logs', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            alert('Error clearing logs: ' + data.error);
        } else {
            const message = `Logs cleared successfully!\nFiles processed: ${data.files_cleared}`;
            if (data.errors && data.errors.length > 0) {
                alert(message + '\n\nErrors:\n' + data.errors.join('\n'));
            } else {
                alert(message);
            }

            // Refresh logs and statistics
            refreshAllData();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while clearing logs: ' + error.message);
    })
    .finally(() => {
        // Restore button
        button.innerHTML = originalText;
        button.disabled = false;
    });
}
</script>
