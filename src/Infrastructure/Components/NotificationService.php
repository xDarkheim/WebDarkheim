<?php

/**
 * Internal Notification System
 * Manages in-app notifications for system events
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Infrastructure\Components;

use App\Domain\Interfaces\LoggerInterface;
use Exception;



class NotificationService
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Create a backup success notification
     */
    public function notifyBackupSuccess(array $backupData): void
    {
        try {
            $message = sprintf(
                "Database backup completed successfully. File: %s, Size: %s, Tables: %d",
                $backupData['filename'] ?? 'Unknown',
                $this->formatFileSize($backupData['size'] ?? 0),
                $backupData['tables_count'] ?? 0
            );

            $this->createNotification('backup_success', $message, [
                'backup_type' => $backupData['backup_type'] ?? 'manual',
                'filename' => $backupData['filename'] ?? null,
                'size' => $backupData['size'] ?? 0,
                'tables_count' => $backupData['tables_count'] ?? 0,
                'timestamp' => $backupData['timestamp'] ?? time()
            ]);

            $this->logger->info('Backup success notification created', [
                'filename' => $backupData['filename'] ?? 'Unknown'
            ]);

        } catch (Exception $e) {
            $this->logger->error('Failed to create backup success notification', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Create a backup failure notification
     */
    public function notifyBackupFailure(array $errorData): void
    {
        try {
            $message = sprintf(
                "Database backup failed: %s",
                $errorData['error'] ?? 'Unknown error'
            );

            $this->createNotification('backup_failure', $message, [
                'backup_type' => $errorData['backup_type'] ?? 'manual',
                'error' => $errorData['error'] ?? 'Unknown error',
                'timestamp' => time()
            ]);

            $this->logger->warning('Backup failure notification created', [
                'error' => $errorData['error'] ?? 'Unknown error'
            ]);

        } catch (Exception $e) {
            $this->logger->error('Failed to create backup failure notification', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Create a system notification
     */
    private function createNotification(string $type, string $message, array $data = []): void
    {
        // Log the notification (this replaces email notifications)
        $this->logger->info("System Notification [$type]: $message", $data);

        // In a real implementation, you could store notifications in a database 
        // For now; we just log them as this is simpler and more reliable
        
        // Optional: Store in a simple notifications log file
        $this->storeNotificationLog($type, $message, $data);
    }

    /**
     * Store notification in a dedicated log file
     */
    private function storeNotificationLog(string $type, string $message, array $data): void
    {
        try {
            $logDir = dirname(__DIR__, 3) . '/storage/logs';
            $logFile = $logDir . '/notifications.log';

            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }

            $logEntry = [
                'timestamp' => date('Y-m-d H:i:s'),
                'type' => $type,
                'message' => $message,
                'data' => $data
            ];

            $logLine = json_encode($logEntry) . "\n";
            file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);

        } catch (Exception $e) {
            $this->logger->error('Failed to write notification log', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Format file size for display
     */
    private function formatFileSize(int $bytes): string
    {
        if ($bytes === 0) return '0 B';
        
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        return round($bytes / (1024 ** $pow), 2) . ' ' . $units[$pow];
    }

    /**
     * Get recent notifications (optional utility method)
     */
    public function getRecentNotifications(int $limit = 10): array
    {
        try {
            $logFile = dirname(__DIR__, 3) . '/storage/logs/notifications.log';
            
            if (!file_exists($logFile)) {
                return [];
            }

            $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $lines = array_slice($lines, -$limit); // Get last N lines
            $lines = array_reverse($lines); // Newest first

            $notifications = [];
            foreach ($lines as $line) {
                $notification = json_decode($line, true);
                if ($notification) {
                    $notifications[] = $notification;
                }
            }

            return $notifications;

        } catch (Exception $e) {
            $this->logger->error('Failed to read notifications log', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
}
