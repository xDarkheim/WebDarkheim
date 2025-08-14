<?php

/**
 * Controller for monitoring system logs
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Application\Controllers;

use App\Application\Core\ServiceProvider;
use App\Domain\Interfaces\DatabaseInterface;
use App\Domain\Interfaces\LoggerInterface;
use Exception;
use FilesystemIterator;
use PDO;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionException;


class LogMonitorController
{
    private ServiceProvider $services;
    private DatabaseInterface $database;
    private LoggerInterface $logger;

    private array $logFiles = [
        'app' => '/storage/logs/app.log',
        'php_errors' => '/storage/logs/php_errors.log'
    ];

    /**
     * @throws ReflectionException
     */
    public function __construct()
    {
        $this->services = ServiceProvider::getInstance();
        $this->database = $this->services->getDatabase();
        $this->logger = $this->services->getLogger();
    }

    /**
     * Gets system information and status
     */
    public function getSystemStatus(): array
    {
        return [
            'database' => $this->getDatabaseStatus(),
            'filesystem' => $this->getFilesystemStatus(),
            'logs' => $this->getLogsStatus(),
            'backup' => $this->getBackupStatus(),
            'performance' => $this->getPerformanceMetrics()
        ];
    }

    /**
     * Gets logs with filtering
     */
    public function getLogs(string $logType = 'app', int $lines = 100, string $level = '', string $search = ''): array
    {
        $logFile = ROOT_PATH . ($this->logFiles[$logType] ?? $this->logFiles['app']);

        if (!file_exists($logFile)) {
            return [
                'success' => false,
                'error' => "Log file not found: $logType",
                'logs' => []
            ];
        }

        try {
            $logs = $this->parseLogFile($logFile, $lines, $level, $search);

            return [
                'success' => true,
                'logs' => $logs,
                'total_lines' => count($logs),
                'file_size' => $this->formatFileSize(filesize($logFile)),
                'last_modified' => date('Y-m-d H:i:s', filemtime($logFile))
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'logs' => []
            ];
        }
    }

    /**
     * Gets log statistics
     */
    public function getLogStatistics(): array
    {
        $stats = [];

        foreach ($this->logFiles as $type => $path) {
            $fullPath = ROOT_PATH . $path;
            if (file_exists($fullPath)) {
                $stats[$type] = [
                    'file_size' => filesize($fullPath),
                    'formatted_size' => $this->formatFileSize(filesize($fullPath)),
                    'last_modified' => date('Y-m-d H:i:s', filemtime($fullPath)),
                    'line_count' => $this->countLines($fullPath),
                    'error_count' => $this->countLogsByLevel($fullPath, ['ERROR', 'CRITICAL', 'EMERGENCY']),
                    'warning_count' => $this->countLogsByLevel($fullPath, ['WARNING']),
                    'info_count' => $this->countLogsByLevel($fullPath, ['INFO', 'DEBUG'])
                ];
            }
        }

        return $stats;
    }

    /**
     * Clear log files
     */
    public function clearLogs(): array
    {
        $result = ['files_cleared' => 0, 'errors' => []];

        try {
            foreach ($this->logFiles as $type => $path) {
                $fullPath = ROOT_PATH . $path;

                if (file_exists($fullPath)) {
                    // Try to ensure a file is writable
                    if (!is_writable($fullPath)) {
                        @chmod($fullPath, 0666);
                    }

                    // Check if we can write to a file
                    if (is_writable($fullPath)) {
                        // Clear a file while preserving its structure
                        if (file_put_contents($fullPath, '') !== false) {
                            $result['files_cleared']++;
                            $this->logger->info("Log file cleared: $type", ['file' => $fullPath]);
                        } else {
                            $result['errors'][] = "Failed to clear $type log file";
                        }
                    } else {
                        $result['errors'][] = "Cannot clear $type log: permission denied";
                    }
                } else {
                    $result['errors'][] = "Log file not found: $type";
                }
            }

            // Additionally, clearly rotated log files
            $this->clearRotatedLogs($result);

        } catch (Exception $e) {
            $result['errors'][] = 'Log clear failed: ' . $e->getMessage();
            $this->logger->error('Log clear failed', ['error' => $e->getMessage()]);
        }

        return $result;
    }

    /**
     * Clear rotated log files
     */
    private function clearRotatedLogs(array &$result): void
    {
        try {
            $logsDir = ROOT_PATH . '/storage/logs';

            if (is_dir($logsDir)) {
                // Clear rotated application files
                $rotatedFiles = glob($logsDir . '/app.log.*');
                foreach ($rotatedFiles as $file) {
                    if (is_file($file) && unlink($file)) {
                        $result['files_cleared']++;
                    }
                }

                // Clear rotated PHP error files
                $phpRotatedFiles = glob($logsDir . '/php_errors.log.*');
                foreach ($phpRotatedFiles as $file) {
                    if (is_file($file) && unlink($file)) {
                        $result['files_cleared']++;
                    }
                }
            }
        } catch (Exception $e) {
            $result['errors'][] = 'Failed to clear rotated logs: ' . $e->getMessage();
        }
    }

    /**
     * Checks database status
     */
    private function getDatabaseStatus(): array
    {
        try {
            $stmt = $this->database->prepare("SELECT 1");
            $stmt->execute();

            $tablesStmt = $this->database->prepare("SHOW TABLES");
            $tablesStmt->execute();
            $tables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);

            $sizeStmt = $this->database->prepare("
                SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS db_size_mb
                FROM information_schema.tables 
                WHERE table_schema = DATABASE()
            ");
            $sizeStmt->execute();
            $dbSize = $sizeStmt->fetchColumn();

            return [
                'status' => 'connected',
                'tables_count' => count($tables),
                'database_size' => $dbSize . ' MB',
                'connection_time' => microtime(true)
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
                'connection_time' => null
            ];
        }
    }

    /**
     * Checks filesystem status
     */
    private function getFilesystemStatus(): array
    {
        $paths = [
            'storage' => ROOT_PATH . '/storage',
            'logs' => ROOT_PATH . '/storage/logs',
            'backups' => ROOT_PATH . '/storage/backups',
            'uploads' => ROOT_PATH . '/storage/uploads',
            'cache' => ROOT_PATH . '/storage/cache'
        ];

        return array_map(function ($path) {
            return [
                'exists' => is_dir($path),
                'writable' => is_writable($path),
                'size' => is_dir($path) ? $this->formatFileSize($this->getDirSize($path)) : '0 B',
                'files_count' => is_dir($path) ? count(scandir($path)) - 2 : 0
            ];
        }, $paths);
    }

    /**
     * Gets logs status
     */
    private function getLogsStatus(): array
    {
        $status = [];

        foreach ($this->logFiles as $type => $path) {
            $fullPath = ROOT_PATH . $path;
            $status[$type] = [
                'exists' => file_exists($fullPath),
                'size' => file_exists($fullPath) ? $this->formatFileSize(filesize($fullPath)) : '0 B',
                'last_modified' => file_exists($fullPath) ? date('Y-m-d H:i:s', filemtime($fullPath)) : 'Never',
                'recent_errors' => $this->getRecentErrors($fullPath)
            ];
        }

        return $status;
    }

    /**
     * Gets backup status
     */
    private function getBackupStatus(): array
    {
        $backupDir = ROOT_PATH . '/storage/backups';

        if (!is_dir($backupDir)) {
            return ['status' => 'not_configured'];
        }

        $backups = glob($backupDir . '/backup_*.sql.gz');
        $latestBackup = null;

        if (!empty($backups)) {
            usort($backups, function($a, $b) {
                return filemtime($b) - filemtime($a);
            });
            $latestBackup = $backups[0];
        }

        return [
            'status' => !empty($backups) ? 'active' : 'no_backups',
            'total_backups' => count($backups),
            'latest_backup' => $latestBackup ? [
                'filename' => basename($latestBackup),
                'size' => $this->formatFileSize(filesize($latestBackup)),
                'created' => date('Y-m-d H:i:s', filemtime($latestBackup))
            ] : null,
            'total_size' => $this->formatFileSize(array_sum(array_map('filesize', $backups)))
        ];
    }

    /**
     * Gets performance metrics
     */
    private function getPerformanceMetrics(): array
    {
        return [
            'memory_usage' => [
                'current' => $this->formatFileSize(memory_get_usage(true)),
                'peak' => $this->formatFileSize(memory_get_peak_usage(true)),
                'limit' => ini_get('memory_limit')
            ],
            'disk_space' => [
                'free' => $this->formatFileSize((int)disk_free_space(ROOT_PATH)),
                'total' => $this->formatFileSize((int)disk_total_space(ROOT_PATH))
            ],
            'php_version' => PHP_VERSION,
            'server_load' => function_exists('sys_getloadavg') ? sys_getloadavg()[0] : 'N/A'
        ];
    }

    /**
     * Parses log file
     */
    private function parseLogFile(string $file, int $lines, string $level, string $search): array
    {
        $command = "tail -n $lines " . escapeshellarg($file);
        $output = shell_exec($command);

        if (!$output) {
            return [];
        }

        $logLines = explode("\n", trim($output));
        $parsedLogs = [];

        foreach ($logLines as $line) {
            if (empty(trim($line))) continue;

            $parsed = $this->parseLogLine($line);

            // Filter by level
            if ($level && !empty($parsed['level']) && strtoupper($parsed['level']) !== strtoupper($level)) {
                continue;
            }

            // Filter by search
            if ($search && stripos($line, $search) === false) {
                continue;
            }

            $parsedLogs[] = $parsed;
        }

        return array_reverse($parsedLogs);
    }

    /**
     * Parses log line
     */
    private function parseLogLine(string $line): array
    {
        // New format: [2024-01-01 12:00:00] [ERROR] [PID:123] [MEM:2MB] Message Context: {...}
        $newPattern = '/^\[([^]]+)]\s+\[([^]]+)]\s+\[([^]]+)]\s+\[([^]]+)]\s+(.*?)(?:\s+Context:\s+(.*))?$/';
        if (preg_match($newPattern, $line, $matches)) {
            return [
                'timestamp' => $matches[1],
                'level' => strtoupper($matches[2]),
                'pid' => $matches[3],
                'memory' => $matches[4], 
                'message' => $matches[5],
                'context' => $matches[6] ?? null,
                'channel' => 'app',
                'raw' => $line
            ];
        }

        // Format: [2024-01-01 12:00:00] app.ERROR: Message
        $pattern = '/^\[([^]]+)]\s+(\w+)\.(\w+):\s+(.*)$/';
        if (preg_match($pattern, $line, $matches)) {
            return [
                'timestamp' => $matches[1],
                'channel' => $matches[2],
                'level' => strtoupper($matches[3]),
                'message' => $matches[4],
                'raw' => $line
            ];
        }

        // Alternative format: [2024-01-01 12:00:00] ERROR: Message
        $altPattern = '/^\[([^]]+)]\s+(\w+):\s+(.*)$/';
        if (preg_match($altPattern, $line, $matches)) {
            return [
                'timestamp' => $matches[1],
                'channel' => 'app',
                'level' => strtoupper($matches[2]),
                'message' => $matches[3],
                'raw' => $line
            ];
        }

        // PHP error format: [01-Jan-2024 12:00:00] PHP Error message
        $phpPattern = '/^\[([^]]+)]\s+(.*)$/';
        if (preg_match($phpPattern, $line, $matches)) {
            $level = 'ERROR';
            $message = $matches[2];

            // Determine the level from a message
            if (stripos($message, 'warning') !== false) {
                $level = 'WARNING';
            } elseif (stripos($message, 'notice') !== false) {
                $level = 'NOTICE';
            } elseif (stripos($message, 'info') !== false) {
                $level = 'INFO';
            } elseif (stripos($message, 'debug') !== false) {
                $level = 'DEBUG';
            }

            return [
                'timestamp' => $matches[1],
                'channel' => 'php',
                'level' => $level,
                'message' => $message,
                'raw' => $line
            ];
        }

        // Fallback - try to determine level from content
        $level = 'INFO';
        if (stripos($line, 'error') !== false) {
            $level = 'ERROR';
        } elseif (stripos($line, 'warning') !== false) {
            $level = 'WARNING';
        } elseif (stripos($line, 'debug') !== false) {
            $level = 'DEBUG';
        } elseif (stripos($line, 'critical') !== false) {
            $level = 'CRITICAL';
        }

        return [
            'timestamp' => date('Y-m-d H:i:s'),
            'channel' => 'app',
            'level' => $level,
            'message' => $line,
            'raw' => $line
        ];
    }

    /**
     * Format file size in human-readable format
     */
    private function formatFileSize(int $bytes): string
    {
        if ($bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = floor(log($bytes, 1024));
        $power = min($power, count($units) - 1);

        $size = $bytes / pow(1024, $power);
        return round($size, 2) . ' ' . $units[$power];
    }

    /**
     * Count lines in a file
     */
    private function countLines(string $file): int
    {
        if (!file_exists($file)) {
            return 0;
        }

        $lineCount = 0;
        $handle = fopen($file, 'r');

        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                $lineCount++;
            }
            fclose($handle);
        }

        return $lineCount;
    }

    /**
     * Count logs by specific levels
     */
    private function countLogsByLevel(string $file, array $levels): int
    {
        if (!file_exists($file)) {
            return 0;
        }

        $count = 0;
        $handle = fopen($file, 'r');

        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                $parsed = $this->parseLogLine(trim($line));
                if (isset($parsed['level']) && in_array(strtoupper($parsed['level']), array_map('strtoupper', $levels))) {
                    $count++;
                }
            }
            fclose($handle);
        }

        return $count;
    }

    /**
     * Gets recent errors from log
     */
    private function getRecentErrors(string $file): array
    {
        if (!file_exists($file)) {
            return [];
        }

        $recentErrors = [];
        $command = "tail -n 50 " . escapeshellarg($file);
        $output = shell_exec($command);

        if ($output) {
            $lines = explode("\n", trim($output));
            foreach ($lines as $line) {
                if (empty(trim($line))) continue;

                $parsed = $this->parseLogLine($line);
                if (isset($parsed['level']) && in_array($parsed['level'], ['ERROR', 'CRITICAL', 'EMERGENCY'])) {
                    $recentErrors[] = [
                        'timestamp' => $parsed['timestamp'] ?? 'Unknown',
                        'level' => $parsed['level'],
                        'message' => substr($parsed['message'] ?? $line, 0, 100) . '...'
                    ];
                }
            }
        }

        return array_slice($recentErrors, -5); // Last 5 errors
    }

    /**
     * Calculate directory size recursively
     */
    private function getDirSize(string $directory): int
    {
        if (!is_dir($directory)) {
            return 0;
        }

        $size = 0;
        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $size += $file->getSize();
                }
            }
        } catch (Exception $e) {
            // If we can't read the directory, return 0
            return 0;
        }

        return $size;
    }
}
