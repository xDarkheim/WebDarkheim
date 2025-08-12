/**
 * System Log Monitor JavaScript
 * Real-time system monitoring and log analysis
 */

let searchTimeout;
let autoRefreshInterval;

// Функции автоматического мониторинга
function runManualCheck() {
    const resultsContainer = document.getElementById('manual-check-results');
    if (!resultsContainer) return;

    resultsContainer.innerHTML = '<div class="status-loading"><i class="fas fa-spinner fa-spin"></i> Running system check...</div>';

    fetch('/index.php?page=system_monitor&action=ajax&type=run_monitoring')
        .then(response => response.json())
        .then(data => {
            displayManualCheckResults(data);
        })
        .catch(error => {
            console.error('Error running manual check:', error);
            resultsContainer.innerHTML = '<div class="error-message"><i class="fas fa-exclamation-triangle"></i> Failed to run system check</div>';
        });
}

function checkCronStatus() {
    const statusContainer = document.getElementById('monitoring-status');
    if (!statusContainer) {
        console.error('monitoring-status element not found!');
        return;
    }

    console.log('Checking cron status...');
    statusContainer.innerHTML = '<div class="status-loading"><i class="fas fa-spinner fa-spin"></i> Checking status...</div>';

    const url = '/index.php?page=system_monitor&action=ajax&type=monitoring_status';
    console.log('Making AJAX request to:', url);

    fetch(url)
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Received monitoring status data:', data);
            displayMonitoringStatus(data);
        })
        .catch(error => {
            console.error('Error checking cron status:', error);
            statusContainer.innerHTML = '<div class="error-message"><i class="fas fa-exclamation-triangle"></i> Failed to check status: ' + error.message + '</div>';
        });

    // Также загружаем последние результаты
    loadLastMonitoringResults();
}

function loadLastMonitoringResults() {
    const resultsContainer = document.getElementById('last-monitoring-results');
    if (!resultsContainer) return;

    fetch('/index.php?page=system_monitor&action=ajax&type=last_monitoring')
        .then(response => response.json())
        .then(data => {
            displayLastMonitoringResults(data);
        })
        .catch(error => {
            console.error('Error loading last monitoring results:', error);
            resultsContainer.innerHTML = '<div class="error-message"><i class="fas fa-exclamation-triangle"></i> Failed to load results</div>';
        });
}

function displayManualCheckResults(data) {
    const container = document.getElementById('manual-check-results');
    if (!container) return;

    if (data.error) {
        container.innerHTML = `<div class="error-message"><i class="fas fa-exclamation-triangle"></i> ${data.error}</div>`;
        return;
    }

    const statusIcon = getStatusIcon(data.status);
    const statusClass = `status-${data.status}`;
    const timestamp = new Date(data.timestamp * 1000).toLocaleString();

    let alertsHtml = '';
    if (data.alerts && data.alerts.length > 0) {
        alertsHtml = `
            <div class="alerts-section">
                <h4><i class="fas fa-bell"></i> Alerts (${data.alerts.length})</h4>
                ${data.alerts.map(alert => `
                    <div class="alert-item ${alert.level}">
                        <strong>${alert.component}:</strong> ${alert.message}
                    </div>
                `).join('')}
            </div>
        `;
    }

    let checksHtml = '';
    if (data.checks) {
        checksHtml = `
            <div class="checks-section">
                <h4><i class="fas fa-tasks"></i> System Checks</h4>
                <div class="checks-grid">
                    ${Object.entries(data.checks).map(([component, result]) => `
                        <div class="check-item ${result.status}">
                            <span class="check-name">${component.replace('_', ' ')}</span>
                            <span class="check-status">${getStatusIcon(result.status)} ${result.status}</span>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }

    container.innerHTML = `
        <div class="monitoring-result ${statusClass}">
            <div class="result-header">
                <span class="result-status">${statusIcon} ${data.status.toUpperCase()}</span>
                <span class="result-time">${timestamp}</span>
            </div>
            ${alertsHtml}
            ${checksHtml}
        </div>
    `;
}

function displayMonitoringStatus(data) {
    const container = document.getElementById('monitoring-status');
    if (!container) return;

    if (data.error) {
        container.innerHTML = `<div class="error-message"><i class="fas fa-exclamation-triangle"></i> ${data.error}</div>`;
        return;
    }

    const overallStatus = data.overall_status;
    const statusIcon = overallStatus === 'configured' ? '✅' : '❌';
    const statusText = overallStatus === 'configured' ? 'Configured' : 'Not Configured';
    const statusClass = overallStatus === 'configured' ? 'status-success' : 'status-warning';

    let cronInfo = '';
    if (data.cron && data.cron.configured) {
        cronInfo = `
            <div class="cron-info">
                <h4><i class="fas fa-clock"></i> Cron Schedule</h4>
                ${data.cron.entries.map(entry => `<code>${entry}</code>`).join('<br>')}
            </div>
        `;
    }

    container.innerHTML = `
        <div class="status-result ${statusClass}">
            <div class="status-header">
                <span class="status-indicator">${statusIcon} ${statusText}</span>
            </div>
            <div class="status-details">
                <div class="detail-item">
                    <span class="detail-label">Script:</span>
                    <span class="detail-value">${data.script.exists ? '✅ Exists' : '❌ Missing'}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Executable:</span>
                    <span class="detail-value">${data.script.executable ? '✅ Yes' : '❌ No'}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Cron Job:</span>
                    <span class="detail-value">${data.cron.configured ? '✅ Configured' : '❌ Not Set'}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Log File:</span>
                    <span class="detail-value">${data.log.exists ? '✅ Exists' : '❌ Missing'}</span>
                </div>
            </div>
            ${cronInfo}
        </div>
    `;
}

function displayLastMonitoringResults(data) {
    const container = document.getElementById('last-monitoring-results');
    if (!container) return;

    if (data.error) {
        container.innerHTML = `<div class="error-message"><i class="fas fa-exclamation-triangle"></i> ${data.error}</div>`;
        return;
    }

    if (data.status === 'no_data') {
        container.innerHTML = `<div class="no-data"><i class="fas fa-info-circle"></i> ${data.message}</div>`;
        return;
    }

    if (data.last_run) {
        const run = data.last_run;
        const statusIcon = getStatusIcon(run.status);
        const statusClass = `status-${run.status}`;

        container.innerHTML = `
            <div class="last-run-result ${statusClass}">
                <div class="run-header">
                    <span class="run-status">${statusIcon} ${run.status.toUpperCase()}</span>
                    <span class="run-time">${run.timestamp || 'Unknown time'}</span>
                </div>
                <div class="run-details">
                    <div class="detail-item">
                        <span class="detail-label">Alerts:</span>
                        <span class="detail-value">${run.alerts_count} alerts found</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Log File:</span>
                        <span class="detail-value">${data.log_file_size}</span>
                    </div>
                </div>
            </div>
        `;
    } else {
        container.innerHTML = '<div class="no-data"><i class="fas fa-info-circle"></i> No monitoring runs found</div>';
    }
}

function getStatusIcon(status) {
    switch (status) {
        case 'healthy':
        case 'ok':
        case 'success':
            return '✅';
        case 'warning':
            return '⚠️';
        case 'critical':
        case 'error':
            return '🚨';
        default:
            return '❓';
    }
}

/**
 * Загружает логи с текущими фильтрами
 */
function loadLogs() {
    const logType = document.getElementById('log-type').value;
    const logLevel = document.getElementById('log-level').value;
    const logLines = document.getElementById('log-lines').value;
    const searchQuery = document.getElementById('log-search').value;

    const container = document.getElementById('log-container');
    container.innerHTML = '<div class="log-loading"><i class="fas fa-spinner fa-spin"></i> Loading logs...</div>';

    const params = new URLSearchParams({
        page: 'system_monitor',
        action: 'ajax',
        type: 'logs',
        log_type: logType,
        lines: logLines,
        search: searchQuery
    });

    // Добавляем level только если он не пустой
    if (logLevel && logLevel.trim() !== '') {
        params.append('level', logLevel);
    }

    fetch(`/index.php?${params}`)
        .then(response => response.json())
        .then(data => {
            console.log('Log data received:', data); // Для отладки
            if (data.success) {
                displayLogs(data.logs);
                updateLogInfo(data);
            } else {
                container.innerHTML = `<div class="log-error">Error loading logs: ${data.error}</div>`;
            }
        })
        .catch(error => {
            console.error('Error loading logs:', error);
            container.innerHTML = '<div class="log-error">Failed to load logs. Please try again.</div>';
        });
}

/**
 * Отображает логи в контейнере
 */
function displayLogs(logs) {
    const container = document.getElementById('log-container');

    if (!logs || logs.length === 0) {
        container.innerHTML = '<div class="log-empty">No logs found matching the current filters.</div>';
        return;
    }

    let html = '';
    logs.forEach(log => {
        const level = log.level || 'INFO';
        const levelClass = `level-${level}`;
        const timestamp = log.timestamp || '';
        const channel = log.channel || 'app';
        const message = escapeHtml(log.message || log.raw || '');

        html += `
            <div class="log-entry ${levelClass}" data-level="${level}">
                <span class="log-timestamp">${timestamp}</span>
                <span class="log-channel">[${channel}]</span>
                <span class="log-level log-level-${level}">${level}</span>
                <span class="log-message">${message}</span>
            </div>
        `;
    });

    container.innerHTML = html;

    // Прокручиваем вниз к последним записям
    container.scrollTop = container.scrollHeight;
}

/**
 * Экранирует HTML символы
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Обновляет информацию о логах
 */
function updateLogInfo(data) {
    console.log(`Loaded ${data.total_lines} log entries, file size: ${data.file_size}`);
}

/**
 * Поиск с задержкой
 */
function debounceSearch() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        loadLogs();
    }, 500);
}

/**
 * Обновляет системный статус
 */
function refreshSystemStatus() {
    fetch('/index.php?page=system_monitor&action=ajax&type=status')
        .then(response => response.json())
        .then(data => {
            updateStatusCards(data);
        })
        .catch(error => {
            console.error('Error refreshing system status:', error);
        });
}

/**
 * Загружает статистику логов
 */
function loadLogStatistics() {
    fetch('/index.php?page=system_monitor&action=ajax&type=stats')
        .then(response => response.json())
        .then(data => {
            updateLogStatistics(data);
        })
        .catch(error => {
            console.error('Error loading log statistics:', error);
        });
}

/**
 * Обновляет статистику логов
 */
function refreshLogStatistics() {
    loadLogStatistics();
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showNotification(message, type = 'info') {
    // Создаем уведомление
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : 'info-circle'}"></i>
            <span>${message}</span>
        </div>
        <button class="notification-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
/**
 * System Monitor JavaScript
 * Handles AJAX requests and UI updates for the system monitoring page
 */

// Global variables
let searchTimeout;
let currentLogType = 'app';

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeSystemMonitor();
});

function initializeSystemMonitor() {
    // Load initial data
    checkCronStatus();
    loadLastMonitoringResults();
    loadLogs();

    // Set up event listeners
    setupEventListeners();
}

function setupEventListeners() {
    // Log type selector
    const logTypeSelect = document.getElementById('log-type');
    if (logTypeSelect) {
        logTypeSelect.addEventListener('change', function() {
            currentLogType = this.value;
            loadLogs();
        });
    }

    // Log level selector
    const logLevelSelect = document.getElementById('log-level');
    if (logLevelSelect) {
        logLevelSelect.addEventListener('change', loadLogs);
    }

    // Log lines selector
    const logLinesSelect = document.getElementById('log-lines');
    if (logLinesSelect) {
        logLinesSelect.addEventListener('change', loadLogs);
    }

    // Search input
    const searchInput = document.getElementById('log-search');
    if (searchInput) {
        searchInput.addEventListener('keyup', debounceSearch);
    }
}

function refreshAllData() {
    console.log('Refreshing all system monitor data...');
    checkCronStatus();
    loadLastMonitoringResults();
    loadLogs();
    updateSystemStats();
}

function checkCronStatus() {
    const statusElement = document.getElementById('monitoring-status');
    if (!statusElement) return;

    statusElement.innerHTML = '<div class="status-loading"><i class="fas fa-spinner fa-spin"></i> Checking status...</div>';

    fetch('/index.php?page=system_monitor&action=ajax&type=monitoring_status')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success !== false && !data.error) {
                displayCronStatus(data);
            } else {
                statusElement.innerHTML = `<div class="status-error">Error: ${data.error || 'Unknown error occurred'}</div>`;
            }
        })
        .catch(error => {
            console.error('Error checking cron status:', error);
            statusElement.innerHTML = `<div class="status-error">Failed to check status: ${error.message}</div>`;
        });
}

function displayCronStatus(data) {
    const statusElement = document.getElementById('monitoring-status');
    if (!statusElement) return;

    let html = '<div class="status-info">';

    // Overall status
    html += '<div class="status-overview">';
    html += `<h4>Overall Status: <span class="status-badge status-${data.overall_status}">${data.status_text}</span></h4>`;
    html += '</div>';

    // Script status
    html += '<div class="status-item">';
    html += '<span class="status-label">Script:</span>';
    html += `<span class="status-value ${data.script_exists ? 'success' : 'error'}">`;
    if (data.script_exists) {
        html += data.script_executable ? '✅ Ready' : '⚠️ Not Executable';
    } else {
        html += '❌ Not Found';
    }
    html += '</span></div>';

    // Cron status
    html += '<div class="status-item">';
    html += '<span class="status-label">Cron Job:</span>';
    html += `<span class="status-value ${data.cron_job_set ? 'success' : 'error'}">`;
    html += data.cron_job_set ? '✅ Configured' : '❌ Not Set';
    html += '</span></div>';

    // Log file status
    html += '<div class="status-item">';
    html += '<span class="status-label">Log File:</span>';
    html += `<span class="status-value ${data.log_file_exists ? 'success' : 'warning'}">`;
    html += data.log_file_exists ? '✅ Exists' : '⚠️ Not Found';
    html += '</span></div>';

    // Last run info
    if (data.last_run) {
        html += '<div class="status-item">';
        html += '<span class="status-label">Last Run:</span>';
        html += `<span class="status-value info">${data.last_run}</span>`;
        html += '</div>';
    }

    html += '</div>';
    statusElement.innerHTML = html;
}

function loadLastMonitoringResults() {
    const resultsElement = document.getElementById('last-monitoring-results');
    if (!resultsElement) return;

    console.log('Loading last monitoring results...');
    resultsElement.innerHTML = '<div class="status-loading"><i class="fas fa-spinner fa-spin"></i> Loading results...</div>';

    fetch('/index.php?page=system_monitor&action=ajax&type=last_monitoring')
        .then(response => {
            console.log('Last monitoring response status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Received last monitoring data:', data);

            // Handle different response formats
            if (data.success === true || (data.success !== false && !data.error)) {
                displayLastResults(data);
            } else if (data.success === false && data.message) {
                resultsElement.innerHTML = `<div class="no-data">${data.message}</div>`;
            } else if (data.error) {
                resultsElement.innerHTML = `<div class="status-error">Error: ${data.error}</div>`;
            } else {
                resultsElement.innerHTML = '<div class="no-data">No monitoring data available</div>';
            }
        })
        .catch(error => {
            console.error('Error loading last monitoring results:', error);
            resultsElement.innerHTML = `<div class="status-error">Failed to load results: ${error.message}</div>`;
        });
}

function displayLastResults(data) {
    const resultsElement = document.getElementById('last-monitoring-results');
    if (!resultsElement) return;

    let html = '<div class="results-info">';

    if (data && data.last_run) {
        html += '<div class="result-item">';
        html += '<span class="result-label">Last Run:</span>';
        html += `<span class="result-value">${data.last_run}`;
        if (data.hours_ago !== undefined && data.hours_ago !== null) {
            html += ` (${data.hours_ago} hours ago)`;
        }
        html += '</span></div>';

        if (data.last_status && typeof data.last_status === 'string') {
            html += '<div class="result-item">';
            html += '<span class="result-label">Status:</span>';
            html += `<span class="result-value status-badge status-${data.last_status}">${data.last_status.toUpperCase()}</span>`;
            html += '</div>';
        }

        if (data.alerts_count !== undefined && data.alerts_count !== null) {
            html += '<div class="result-item">';
            html += '<span class="result-label">Alerts:</span>';
            html += `<span class="result-value ${data.alerts_count > 0 ? 'warning' : 'success'}">${data.alerts_count}</span>`;
            html += '</div>';
        }

        if (data.file_size) {
            html += '<div class="result-item">';
            html += '<span class="result-label">Log Size:</span>';
            html += `<span class="result-value">${data.file_size}</span>`;
            html += '</div>';
        }

        if (data.total_lines !== undefined && data.total_lines !== null) {
            html += '<div class="result-item">';
            html += '<span class="result-label">Total Lines:</span>';
            html += `<span class="result-value">${data.total_lines.toLocaleString()}</span>`;
            html += '</div>';
        }
    } else {
        // Handle different response types
        if (data && data.message) {
            html += `<div class="no-data">${data.message}</div>`;
        } else if (data && data.status === 'no_data') {
            html += '<div class="no-data">System monitoring has not run automatically yet</div>';
        } else {
            html += '<div class="no-data">No monitoring runs recorded</div>';
        }
    }

    html += '</div>';
    resultsElement.innerHTML = html;
}

function runManualCheck() {
    const resultsElement = document.getElementById('manual-check-results');
    if (!resultsElement) return;

    resultsElement.innerHTML = '<div class="status-loading"><i class="fas fa-spinner fa-spin"></i> Running system check...</div>';

    fetch('/index.php?page=system_monitor&action=ajax&type=run_monitoring')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                displayManualResults(data);
            } else {
                resultsElement.innerHTML = `<div class="status-error">Error: ${data.error || 'Unknown error occurred'}</div>`;
            }
        })
        .catch(error => {
            console.error('Error running manual check:', error);
            resultsElement.innerHTML = `<div class="status-error">Failed to run check: ${error.message}</div>`;
        });
}

function displayManualResults(data) {
    const resultsElement = document.getElementById('manual-check-results');
    if (!resultsElement) return;

    console.log('Displaying manual results:', data);

    let html = '<div class="manual-results">';
    html += '<div class="result-header">';
    html += '<h4>System Check Results</h4>';

    if (data.status && typeof data.status === 'string') {
        html += `<span class="result-status status-badge status-${data.status}">${data.status.toUpperCase()}</span>`;
    } else {
        html += '<span class="result-status status-badge status-unknown">UNKNOWN</span>';
    }
    html += '</div>';

    if (data.alerts && Array.isArray(data.alerts) && data.alerts.length > 0) {
        html += '<div class="alerts-section">';
        html += `<h5>Alerts (${data.alerts.length}):</h5>`;
        data.alerts.forEach(alert => {
            if (alert && alert.level && alert.component && alert.message) {
                html += `<div class="alert-item alert-${alert.level}">`;
                html += `<strong>${alert.component}:</strong> ${alert.message}`;
                html += '</div>';
            }
        });
        html += '</div>';
    } else {
        html += '<div class="success-message">✅ No issues detected - system is healthy</div>';
    }

    if (data.checks && typeof data.checks === 'object') {
        html += '<div class="checks-section">';
        html += '<h5>Component Status:</h5>';
        Object.entries(data.checks).forEach(([component, check]) => {
            if (check && check.status && typeof check.status === 'string') {
                html += `<div class="check-item">`;
                html += `<span class="check-name">${component.replace('_', ' ')}</span>`;
                html += `<span class="check-status status-badge status-${check.status}">${check.status.toUpperCase()}</span>`;
                html += '</div>';
            }
        });
        html += '</div>';
    }

    if (data.output && typeof data.output === 'string') {
        html += '<div class="output-section">';
        html += '<h5>Detailed Output:</h5>';
        html += `<pre class="monitoring-output">${escapeHtml(data.output)}</pre>`;
        html += '</div>';
    }

    html += '</div>';
    resultsElement.innerHTML = html;
}

function loadLogs() {
    const logContainer = document.getElementById('log-container');
    if (!logContainer) return;

    logContainer.innerHTML = '<div class="log-loading"><i class="fas fa-spinner fa-spin"></i> Loading logs...</div>';

    const logType = document.getElementById('log-type')?.value || 'app';
    const logLevel = document.getElementById('log-level')?.value || '';
    const logLines = document.getElementById('log-lines')?.value || '100';
    const logSearch = document.getElementById('log-search')?.value || '';

    const params = new URLSearchParams({
        page: 'system_monitor',
        action: 'ajax',
        type: 'logs',
        log_type: logType,
        level: logLevel,
        lines: logLines,
        search: logSearch
    });

    fetch(`/index.php?${params}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                displayLogs(data);
            } else {
                logContainer.innerHTML = `<div class="status-error">Error loading logs: ${data.error || 'Unknown error'}</div>`;
            }
        })
        .catch(error => {
            console.error('Error loading logs:', error);
            logContainer.innerHTML = `<div class="status-error">Failed to load logs: ${error.message}</div>`;
        });
}

function displayLogs(data) {
    const logContainer = document.getElementById('log-container');
    if (!logContainer) return;

    if (!data.logs || data.logs.length === 0) {
        logContainer.innerHTML = '<div class="no-data">No logs found matching the current filters</div>';
        return;
    }

    let html = '<div class="log-viewer">';
    html += '<div class="log-header">';
    html += `<div class="log-info">Showing ${data.logs.length} entries (File: ${data.file_size}, Modified: ${data.last_modified})</div>`;
    html += '</div>';

    html += '<div class="log-entries">';
    data.logs.forEach(log => {
        const levelClass = log.level ? log.level.toLowerCase() : 'info';
        html += `<div class="log-entry log-${levelClass}">`;
        html += `<div class="log-timestamp">${log.timestamp}</div>`;
        if (log.level) {
            html += `<div class="log-level level-${levelClass}">${log.level}</div>`;
        }
        if (log.channel && log.channel !== 'unknown') {
            html += `<div class="log-channel">${log.channel}</div>`;
        }
        html += `<div class="log-message">${escapeHtml(log.message)}</div>`;
        html += '</div>';
    });
    html += '</div>';
    html += '</div>';

    logContainer.innerHTML = html;
}

function debounceSearch() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        loadLogs();
    }, 500);
}

function updateSystemStats() {
    fetch('/index.php?page=system_monitor&action=ajax&type=stats')
        .then(response => response.json())
        .then(data => {
            updateStatsDisplay(data);
        })
        .catch(error => {
            console.error('Error updating system stats:', error);
        });
}

function updateStatsDisplay(stats) {
    const statsContainer = document.getElementById('log-statistics');
    if (!statsContainer || !stats) return;

    let html = '';
    Object.entries(stats).forEach(([type, data]) => {
        html += '<div class="stat-item">';
        html += '<div class="stat-header">';
        html += `<h4>${type.replace('_', ' ').toUpperCase()}</h4>`;
        html += '</div>';
        html += '<div class="stat-metrics">';
        html += `<div class="metric-small"><span class="label">Size:</span><span class="value">${data.formatted_size}</span></div>`;
        html += `<div class="metric-small"><span class="label">Lines:</span><span class="value">${data.line_count.toLocaleString()}</span></div>`;
        html += `<div class="metric-small"><span class="label">Errors:</span><span class="value error">${data.error_count}</span></div>`;
        html += `<div class="metric-small"><span class="label">Warnings:</span><span class="value warning">${data.warning_count}</span></div>`;
        html += '</div>';
        html += '</div>';
    });

    statsContainer.innerHTML = html;
}

// Utility functions
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showNotification(message, type = 'info') {
    // Create a simple notification system
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;

    // Add to page
    document.body.appendChild(notification);

    // Remove after 3 seconds
    setTimeout(() => {
        document.body.removeChild(notification);
    }, 3000);
}

// Export functions for global access
window.refreshAllData = refreshAllData;
window.checkCronStatus = checkCronStatus;
window.runManualCheck = runManualCheck;
window.loadLogs = loadLogs;
window.debounceSearch = debounceSearch;
    // Добавляем стили для уведомления
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: #2d3748;
        border: 1px solid ${type === 'success' ? '#38a169' : type === 'error' ? '#e53e3e' : '#3182ce'};
        border-radius: 6px;
        padding: 15px;
        color: white;
        z-index: 10000;
        max-width: 350px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    `;

    document.body.appendChild(notification);

    // Автоматически удаляем через 5 секунд
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
}

/**
 * Обновляет карточки статуса
 */
function updateStatusCards(statusData) {
    // Обновляем статус базы данных
    if (statusData.database) {
        const dbCard = document.getElementById('database-status');
        if (dbCard) {
            const indicator = dbCard.querySelector('.status-indicator');
            if (indicator) {
                indicator.setAttribute('data-status', statusData.database.status);
            }

            const metrics = dbCard.querySelectorAll('.metric-value');
            if (metrics.length >= 3) {
                metrics[0].textContent = ucfirst(statusData.database.status);
                metrics[1].textContent = statusData.database.tables_count || 'N/A';
                metrics[2].textContent = statusData.database.database_size || 'N/A';
            }
        }
    }

    // Обновляем статус резервных копий
    if (statusData.backup) {
        const backupCard = document.getElementById('backup-status');
        if (backupCard) {
            const indicator = backupCard.querySelector('.status-indicator');
            if (indicator) {
                indicator.setAttribute('data-status', statusData.backup.status);
            }

            const metrics = backupCard.querySelectorAll('.metric-value');
            if (metrics.length >= 3) {
                metrics[0].textContent = statusData.backup.total_backups;
                metrics[1].textContent = statusData.backup.latest_backup?.created || 'Never';
                metrics[2].textContent = statusData.backup.total_size || '0 B';
            }
        }
    }

    // Обновляем метрики производительности
    if (statusData.performance) {
        const perfCard = document.getElementById('performance-status');
        if (perfCard) {
            const metrics = perfCard.querySelectorAll('.metric-value');
            if (metrics.length >= 3) {
                metrics[0].textContent = statusData.performance.memory_usage.current;
                metrics[1].textContent = statusData.performance.disk_space.free;
                metrics[2].textContent = statusData.performance.php_version;
            }
        }
    }
}

/**
 * Обновляет статистику логов в сайдбаре
 */
function updateLogStatistics(statsData) {
    const container = document.getElementById('log-statistics');
    if (!container) return;

    let html = '';
    for (const [type, stats] of Object.entries(statsData)) {
        const typeName = ucfirst(type.replace('_', ' '));
        html += `
            <div class="stat-item">
                <div class="stat-header">
                    <h4>${typeName}</h4>
                </div>
                <div class="stat-metrics">
                    <div class="metric-small">
                        <span class="label">Size:</span>
                        <span class="value">${stats.formatted_size}</span>
                    </div>
                    <div class="metric-small">
                        <span class="label">Lines:</span>
                        <span class="value">${numberFormat(stats.line_count)}</span>
                    </div>
                    <div class="metric-small">
                        <span class="label">Errors:</span>
                        <span class="value error">${stats.error_count}</span>
                    </div>
                    <div class="metric-small">
                        <span class="label">Warnings:</span>
                        <span class="value warning">${stats.warning_count}</span>
                    </div>
                </div>
            </div>
        `;
    }

    container.innerHTML = html;
}

/**
 * Запускает автообновление
 */
function startAutoRefresh() {
    autoRefreshInterval = setInterval(() => {
        refreshSystemStatus();
        refreshLogStatistics();
    }, 30000); // Каждые 30 секунд
}

/**
 * Обновляет все данные
 */
function refreshAllData() {
    console.log('Refreshing all system monitor data...');

    // Показываем индикаторы загрузки
    const statusContainer = document.getElementById('monitoring-status');
    const resultsContainer = document.getElementById('last-monitoring-results');

    if (statusContainer) {
        statusContainer.innerHTML = '<div class="status-loading"><i class="fas fa-spinner fa-spin"></i> Refreshing...</div>';
    }

    if (resultsContainer) {
        resultsContainer.innerHTML = '<div class="status-loading"><i class="fas fa-spinner fa-spin"></i> Refreshing...</div>';
    }

    // Обновляем все данные
    setTimeout(() => {
        checkCronStatus();
        loadLogs();
        loadLogStatistics();
        refreshSystemStatus();
    }, 100);
}

// Утилиты
function ucfirst(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

function numberFormat(num) {
    return new Intl.NumberFormat().format(num);
}

// Инициализация при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    console.log('System Monitor page loaded - initializing...');

    // Автоматически проверяем статус мониторинга
    setTimeout(() => {
        checkCronStatus();
    }, 500);

    // Загружаем начальные логи
    setTimeout(() => {
        loadLogs();
    }, 1000);

    // Обновляем статистику
    setTimeout(() => {
        loadLogStatistics();
    }, 1500);

    // Устанавливаем автообновление каждые 30 секунд
    setInterval(function() {
        loadLogStatistics();
        // Обновляем статус мониторинга каждые 2 минуты
        if (Math.floor(Date.now() / 30000) % 4 === 0) {
            checkCronStatus();
        }
    }, 30000);

    console.log('System Monitor initialization complete');
});
