<?php

/**
 * System monitoring controller using DTO
 * This controller provides system status, logs, and performance information.
 * It uses DTOs to represent the data and provides methods for fetching and formatting data.
 * It also handles errors and logs them.
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Application\Controllers;

use App\Domain\DTOs\SystemStatusDto;
use App\Domain\DTOs\MonitoringResultDto;
use App\Domain\DTOs\LogEntryDto;
use App\Domain\Collections\LogEntryCollection;
use App\Domain\Interfaces\DatabaseInterface;
use App\Domain\Interfaces\LoggerInterface;
use Exception;


class SystemMonitorController
{
    private DatabaseInterface $database;
    private LoggerInterface $logger;

    public function __construct(DatabaseInterface $database, LoggerInterface $logger)
    {
        $this->database = $database;
        $this->logger = $logger;
    }

    /**
     * Get system status
     */
    public function getSystemStatus(): SystemStatusDto
    {
        try {
            // Get database information
            $dbInfo = $this->getDatabaseInfo();

            // Get backup information
            $backupInfo = $this->getBackupInfo();

            // Get performance information
            $performanceInfo = $this->getPerformanceInfo();

            return new SystemStatusDto(
                $dbInfo['status'],
                $dbInfo['tables_count'],
                $dbInfo['database_size'],
                $backupInfo['status'],
                $backupInfo['total_backups'],
                $backupInfo['latest_backup_date'],
                $backupInfo['total_size'],
                $performanceInfo['memory_usage'],
                $performanceInfo['disk_free'],
                $performanceInfo['php_version']
            );
        } catch (Exception $e) {
            $this->logger->error('Failed to get system status', ['error' => $e->getMessage()]);

            return new SystemStatusDto(
                'error',
                0,
                'N/A',
                'error',
                0,
                null,
                '0 B',
                'N/A',
                'N/A',
                PHP_VERSION
            );
        }
    }

    /**
     * Get logs with filtering
     */
    public function getLogs(string $logType): MonitoringResultDto
    {
        try {
            $logData = $this->fetchLogData();

            $logEntries = new LogEntryCollection();
            foreach ($logData['entries'] as $entry) {
                $logEntries->add(new LogEntryDto(
                    $entry['timestamp'],
                    $entry['level'],
                    $entry['message'],
                    $entry['context'] ?? [],
                    $entry['channel'] ?? null
                ));
            }

            return new MonitoringResultDto(
                $logEntries,
                $logData['total_lines'],
                $logData['error_count'],
                $logData['warning_count'],
                $logType,
                $logData['formatted_size']
            );
        } catch (Exception $e) {
            $this->logger->error('Failed to get logs', ['error' => $e->getMessage()]);

            return new MonitoringResultDto(
                new LogEntryCollection(),
                0,
                0,
                0,
                $logType,
                '0 B'
            );
        }
    }

    /**
     * Get log statistics
     */
    public function getLogStatistics(): array
    {
        // Temporary stub - return statistics in the old format
        return [
            'app' => [
                'formatted_size' => '0 B',
                'line_count' => 0,
                'error_count' => 0,
                'warning_count' => 0,
            ],
            'php_errors' => [
                'formatted_size' => '0 B',
                'line_count' => 0,
                'error_count' => 0,
                'warning_count' => 0,
            ],
        ];
    }

    private function getDatabaseInfo(): array
    {
        try {
            $tablesResult = $this->database->query('SHOW TABLES');
            $tablesCount = $tablesResult->rowCount();

            $sizeResult = $this->database->query('
                SELECT 
                    ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) AS db_size 
                FROM information_schema.tables 
                WHERE table_schema = DATABASE()
            ');
            
            $sizeData = $sizeResult->fetch();
            $dbSize = $sizeData ? (float)($sizeData['db_size'] ?? 0) : 0;

            return [
                'status' => 'ok',
                'tables_count' => $tablesCount,
                'database_size' => $dbSize . ' MB',
            ];
        } catch (Exception) {
            return [
                'status' => 'error',
                'tables_count' => 0,
                'database_size' => 'N/A',
            ];
        }
    }

    private function getBackupInfo(): array
    {
        // Simple implementation, for example
        return [
            'status' => 'ok',
            'total_backups' => 0,
            'latest_backup_date' => null,
            'total_size' => '0 B',
        ];
    }

    private function getPerformanceInfo(): array
    {
        $memoryUsage = memory_get_usage(true);
        $diskFree = disk_free_space('.') ?: 0;

        return [
            'memory_usage' => $this->formatBytes($memoryUsage),
            'disk_free' => $this->formatBytes($diskFree),
            'php_version' => PHP_VERSION,
        ];
    }

    private function fetchLogData(): array
    {
        // Simple stub, for example
        return [
            'entries' => [],
            'total_lines' => 0,
            'error_count' => 0,
            'warning_count' => 0,
            'formatted_size' => '0 B',
        ];
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
