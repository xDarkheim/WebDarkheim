#!/usr/bin/env php
<?php
/**
 * CLI utility script for maintenance tasks
 * Usage: php scripts/maintenance.php [task]
 */

require_once __DIR__ . '/../includes/bootstrap.php';

use App\Infrastructure\Lib\Database;
use App\Infrastructure\Lib\Logger;
use App\Infrastructure\Lib\Cache;
use App\Infrastructure\Lib\Migration;

if (php_sapi_name() !== 'cli') {
    die('This script can only be run from the command line.');
}

$logger = Logger::getInstance();
$cache = Cache::getInstance();

function showHelp() {
    echo "WebEngine Darkheim Maintenance Script\n";
    echo "Usage: php maintenance.php [task]\n\n";
    echo "Available tasks:\n";
    echo "  cache:clear       - Clear all cached files\n";
    echo "  cache:clean       - Clean expired cache entries\n";
    echo "  logs:clean        - Clean old log files\n";
    echo "  db:migrate        - Run pending database migrations\n";
    echo "  db:check          - Check database connection\n";
    echo "  security:scan     - Run security checks\n";
    echo "  maintenance:on    - Enable maintenance mode\n";
    echo "  maintenance:off   - Disable maintenance mode\n";
    echo "  debug:on          - Enable debug mode\n";
    echo "  debug:off         - Disable debug mode\n";
    echo "  status            - Show current system status\n";
    echo "  help              - Show this help message\n\n";
}

function clearCache() {
    global $cache, $logger;
    
    echo "Clearing cache...\n";
    $cache->clear();
    $logger->info('Cache cleared via maintenance script');
    echo "Cache cleared successfully.\n";
}

function cleanCache() {
    global $cache, $logger;
    
    echo "Cleaning expired cache entries...\n";
    $cleaned = $cache->cleanExpired();
    $logger->info('Cache cleaned via maintenance script', ['cleaned_entries' => $cleaned]);
    echo "Cleaned {$cleaned} expired cache entries.\n";
}

function cleanLogs() {
    global $logger;
    
    echo "Cleaning old log files...\n";
    $logDir = ROOT_PATH . DS . 'logs';
    $files = glob($logDir . DS . '*.log');
    $cleaned = 0;
    $cutoffDate = strtotime('-30 days');
    
    foreach ($files as $file) {
        if (filemtime($file) < $cutoffDate && basename($file) !== 'app.log') {
            unlink($file);
            $cleaned++;
        }
    }
    
    $logger->info('Log files cleaned via maintenance script', ['cleaned_files' => $cleaned]);
    echo "Cleaned {$cleaned} old log files.\n";
}

function runMigrations() {
    global $logger;
    
    echo "Running database migrations...\n";
    $database = new Database();
    $migration = new Migration($database);
    
    $results = $migration->run();
    
    foreach ($results as $result) {
        if ($result['status'] === 'success') {
            echo "✓ {$result['migration']}\n";
        } else {
            echo "✗ {$result['migration']}: {$result['error']}\n";
        }
    }
    
    echo "Migration process completed.\n";
}

function checkDatabase() {
    echo "Checking database connection...\n";
    
    $database = new Database();
    $conn = $database->getConnection();
    
    if ($conn) {
        echo "✓ Database connection successful\n";
        
        // Check required tables
        $tables = ['users', 'articles', 'categories', 'comments', 'site_settings'];
        foreach ($tables as $table) {
            $stmt = $conn->query("SHOW TABLES LIKE '{$table}'");
            if ($stmt->rowCount() > 0) {
                echo "✓ Table '{$table}' exists\n";
            } else {
                echo "✗ Table '{$table}' missing\n";
            }
        }
    } else {
        echo "✗ Database connection failed\n";
    }
}

function runSecurityScan() {
    global $logger;
    
    echo "Running security checks...\n";
    $issues = [];
    
    // Check .env file permissions
    $envFile = ROOT_PATH . DS . '.env';
    if (file_exists($envFile)) {
        $perms = fileperms($envFile) & 0777;
        if ($perms & 0044) {
            $issues[] = ".env file is readable by others (permissions: " . decoct($perms) . ")";
        } else {
            echo "✓ .env file permissions are secure\n";
        }
    }
    
    // Check logs directory permissions
    $logsDir = ROOT_PATH . DS . 'logs';
    if (is_dir($logsDir)) {
        $perms = fileperms($logsDir) & 0777;
        if ($perms & 0044) {
            $issues[] = "Logs directory is readable by others";
        } else {
            echo "✓ Logs directory permissions are secure\n";
        }
    }
    
    // Check for debug mode in production
    if (defined('APP_ENV') && APP_ENV === 'production' && defined('APP_DEBUG') && APP_DEBUG) {
        $issues[] = "Debug mode is enabled in production environment";
    } else {
        echo "✓ Debug mode configuration is appropriate\n";
    }
    
    if (!empty($issues)) {
        echo "\nSecurity issues found:\n";
        foreach ($issues as $issue) {
            echo "⚠ {$issue}\n";
        }
        $logger->warning('Security issues found via maintenance script', ['issues' => $issues]);
    } else {
        echo "✓ No security issues found\n";
    }
}

function enableMaintenanceMode() {
    global $logger;
    
    echo "Enabling maintenance mode...\n";

    try {
        $database = new Database();
        $conn = $database->getConnection();

        $stmt = $conn->prepare("UPDATE site_settings SET setting_value = '1' WHERE setting_key = 'maintenance_mode'");
        $stmt->execute();

        // Clear cache to ensure settings are refreshed
        $cache = Cache::getInstance();
        $cache->forget('site_settings');

        $logger->info('Maintenance mode enabled via maintenance script');
        echo "✓ Maintenance mode enabled.\n";
        echo "⚠ All users (except admins) will see maintenance page.\n";

    } catch (Exception $e) {
        echo "✗ Failed to enable maintenance mode: " . $e->getMessage() . "\n";
        $logger->error('Failed to enable maintenance mode', ['error' => $e->getMessage()]);
    }
}

function disableMaintenanceMode() {
    global $logger;
    
    echo "Disabling maintenance mode...\n";

    try {
        $database = new Database();
        $conn = $database->getConnection();

        $stmt = $conn->prepare("UPDATE site_settings SET setting_value = '0' WHERE setting_key = 'maintenance_mode'");
        $stmt->execute();

        // Clear cache to ensure settings are refreshed
        $cache = Cache::getInstance();
        $cache->forget('site_settings');

        $logger->info('Maintenance mode disabled via maintenance script');
        echo "✓ Maintenance mode disabled.\n";
        echo "✓ Site is now accessible to all users.\n";

    } catch (Exception $e) {
        echo "✗ Failed to disable maintenance mode: " . $e->getMessage() . "\n";
        $logger->error('Failed to disable maintenance mode', ['error' => $e->getMessage()]);
    }
}

function enableDebugMode() {
    global $logger;
    
    echo "Enabling debug mode...\n";

    try {
        $database = new Database();
        $conn = $database->getConnection();

        $stmt = $conn->prepare("UPDATE site_settings SET setting_value = '1' WHERE setting_key = 'debug_mode'");
        $stmt->execute();

        // Clear cache to ensure settings are refreshed
        $cache = Cache::getInstance();
        $cache->forget('site_settings');

        $logger->info('Debug mode enabled via maintenance script');
        echo "✓ Debug mode enabled.\n";
        echo "⚠ Detailed error information will be displayed.\n";
        echo "⚠ Performance information will be shown.\n";

    } catch (Exception $e) {
        echo "✗ Failed to enable debug mode: " . $e->getMessage() . "\n";
        $logger->error('Failed to enable debug mode', ['error' => $e->getMessage()]);
    }
}

function disableDebugMode() {
    global $logger;
    
    echo "Disabling debug mode...\n";

    try {
        $database = new Database();
        $conn = $database->getConnection();

        $stmt = $conn->prepare("UPDATE site_settings SET setting_value = '0' WHERE setting_key = 'debug_mode'");
        $stmt->execute();

        // Clear cache to ensure settings are refreshed
        $cache = Cache::getInstance();
        $cache->forget('site_settings');

        $logger->info('Debug mode disabled via maintenance script');
        echo "✓ Debug mode disabled.\n";
        echo "✓ Error reporting set to production level.\n";

    } catch (Exception $e) {
        echo "✗ Failed to disable debug mode: " . $e->getMessage() . "\n";
        $logger->error('Failed to disable debug mode', ['error' => $e->getMessage()]);
    }
}

function showStatus() {
    echo "Current system status:\n\n";

    try {
        $database = new Database();
        $conn = $database->getConnection();

        // Get maintenance mode status
        $stmt = $conn->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'maintenance_mode'");
        $stmt->execute();
        $maintenanceMode = $stmt->fetchColumn();

        // Get debug mode status
        $stmt = $conn->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'debug_mode'");
        $stmt->execute();
        $debugMode = $stmt->fetchColumn();

        // Display status with colors
        echo "🔧 Maintenance Mode: ";
        if ($maintenanceMode === '1') {
            echo "🔴 ENABLED (site is under maintenance)\n";
        } else {
            echo "🟢 DISABLED (site is operational)\n";
        }

        echo "🐛 Debug Mode: ";
        if ($debugMode === '1') {
            echo "🔴 ENABLED (detailed errors shown)\n";
        } else {
            echo "🟢 DISABLED (production error handling)\n";
        }

        // Additional system info
        echo "\n📊 System Information:\n";
        echo "   PHP Version: " . PHP_VERSION . "\n";
        echo "   Environment: " . ($_ENV['APP_ENV'] ?? 'production') . "\n";
        echo "   Memory Usage: " . round(memory_get_usage() / 1024 / 1024, 2) . " MB\n";
        echo "   Database: " . (($conn !== null) ? "✓ Connected" : "✗ Disconnected") . "\n";

    } catch (Exception $e) {
        echo "✗ Failed to get system status: " . $e->getMessage() . "\n";
    }
}

// Main execution
$task = $argv[1] ?? 'help';

switch ($task) {
    case 'cache:clear':
        clearCache();
        break;
    case 'cache:clean':
        cleanCache();
        break;
    case 'logs:clean':
        cleanLogs();
        break;
    case 'db:migrate':
        runMigrations();
        break;
    case 'db:check':
        checkDatabase();
        break;
    case 'security:scan':
        runSecurityScan();
        break;
    case 'maintenance:on':
        enableMaintenanceMode();
        break;
    case 'maintenance:off':
        disableMaintenanceMode();
        break;
    case 'debug:on':
        enableDebugMode();
        break;
    case 'debug:off':
        disableDebugMode();
        break;
    case 'status':
        showStatus();
        break;
    case 'help':
    default:
        showHelp();
        break;
}
