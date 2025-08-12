<?php

/**
 * Controller for automatic database backup
 * Intended only for automatic operation via cron
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Application\Controllers;

use App\Application\Core\ServiceProvider;
use App\Domain\Interfaces\DatabaseInterface;
use App\Domain\Interfaces\LoggerInterface;
use App\Infrastructure\Lib\MailerService;
use Exception;
use InvalidArgumentException;
use PDO;
use RuntimeException;

class DatabaseBackupController
{
    private ServiceProvider $services;
    private DatabaseInterface $database;
    private LoggerInterface $logger;
    private MailerService|null $mailer;

    private string $backupDirectory;
    private int $maxBackups;
    private array $allowedTables;

    public function __construct()
    {
        $this->services = ServiceProvider::getInstance();
        $this->database = $this->services->getDatabase();
        $this->logger = $this->services->getLogger();

        // Initialize mail service
        try {
            $this->mailer = new MailerService();
        } catch (Exception $e) {
            error_log("DatabaseBackupController: Failed to initialize MailerService: " . $e->getMessage());
            $this->mailer = null;
        }

        // Load backup settings from config and DB
        $this->loadBackupSettings();

        $this->ensureBackupDirectory();
    }

    /**
     * Loads backup settings from config and database
     */
    private function loadBackupSettings(): void
    {
        try {
            // Define a project base path
            $basePath = defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__, 3);
            $this->backupDirectory = $basePath . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'backups';
            $this->maxBackups = 30; // Set reasonable default value
            $this->allowedTables = []; // Empty array - back up all tables

            $this->logger->info('Backup settings loaded', [
                'backup_directory' => $this->backupDirectory,
                'max_backups' => $this->maxBackups,
                'excluded_tables_count' => count($this->allowedTables)
            ]);

        } catch (Exception $e) {
            // Fallback to safe default values
            $basePath = defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__, 3);
            $this->backupDirectory = $basePath . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'backups';
            $this->maxBackups = 30;
            $this->allowedTables = [];

            $this->logger->warning('Failed to load backup settings, using defaults', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Creates full database backup
     */
    public function createFullBackup(): array
    {
        try {
            $this->logger->info('Starting database backup process');

            $backupFilename = $this->generateBackupFilename();
            $backupPath = $this->backupDirectory . DIRECTORY_SEPARATOR . $backupFilename;

            // Get a list of all tables
            $tables = $this->getAllTables();

            if (empty($tables)) {
                throw new RuntimeException('No tables found in database');
            }

            // Create backup
            $sqlContent = $this->generateSQLBackup($tables);

            // Compress and save
            $this->saveBackup($backupPath, $sqlContent);

            // Clean old backups
            $this->cleanOldBackups();

            $backupSize = filesize($backupPath);
            $this->logger->info('Database backup completed successfully', [
                'filename' => $backupFilename,
                'size' => $backupSize,
                'tables_count' => count($tables)
            ]);

            return [
                'success' => true,
                'filename' => $backupFilename,
                'path' => $backupPath,
                'size' => $backupSize,
                'tables_count' => count($tables),
                'timestamp' => time()
            ];

        } catch (Exception $e) {
            $this->logger->error('Database backup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Creates incremental backup (structure only)
     */
    public function createStructureBackup(): array
    {
        try {
            $this->logger->info('Starting database structure backup');

            $backupFilename = $this->generateBackupFilename('structure');
            $backupPath = $this->backupDirectory . DIRECTORY_SEPARATOR . $backupFilename;

            $tables = $this->getAllTables();
            $sqlContent = $this->generateStructureSQL($tables);

            $this->saveBackup($backupPath, $sqlContent);

            $backupSize = filesize($backupPath);
            $this->logger->info('Database structure backup completed', [
                'filename' => $backupFilename,
                'size' => $backupSize
            ]);

            return [
                'success' => true,
                'filename' => $backupFilename,
                'path' => $backupPath,
                'size' => $backupSize,
                'type' => 'structure'
            ];

        } catch (Exception $e) {
            $this->logger->error('Database structure backup failed', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Gets a list of all backups (for CLI and automatic tasks only)
     */
    public function getBackupsList(): array
    {
        $backups = [];
        $files = glob($this->backupDirectory . DIRECTORY_SEPARATOR . 'backup_*.sql.gz');

        foreach ($files as $file) {
            $filename = basename($file);
            $backups[] = [
                'filename' => $filename,
                'path' => $file,
                'size' => filesize($file),
                'created_at' => filemtime($file),
                'age_days' => floor((time() - filemtime($file)) / 86400)
            ];
        }

        // Sort by creation date (newest first)
        usort($backups, function($a, $b) {
            return $b['created_at'] - $a['created_at'];
        });

        return $backups;
    }

    /**
     * Deletes specified backup (for automatic cleanup only)
     */
    public function deleteBackup(string $filename): bool
    {
        try {
            $backupPath = $this->backupDirectory . DIRECTORY_SEPARATOR . $filename;

            // Check path security
            if (!$this->isValidBackupFile($backupPath)) {
                throw new InvalidArgumentException('Invalid backup filename');
            }

            if (!file_exists($backupPath)) {
                throw new RuntimeException('Backup file not found');
            }

            if (unlink($backupPath)) {
                $this->logger->info('Backup deleted successfully', ['filename' => $filename]);
                return true;
            }

            return false;

        } catch (Exception $e) {
            $this->logger->error('Failed to delete backup', [
                'filename' => $filename,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Automatic backup (for cron)
     */
    public function autoBackup(): array
    {
        $this->logger->info('Starting automatic backup');

        $result = $this->createFullBackup();

        // Send email notification about result
        if ($result['success']) {
            $this->logger->info('Automatic backup completed successfully');
            $this->sendBackupSuccessNotification($result);
        } else {
            $this->logger->error('Automatic backup failed');
            $this->sendBackupFailureNotification($result);
        }

        return $result;
    }

    /**
     * Sends notification about successful backup
     */
    private function sendBackupSuccessNotification(array $backupData): void
    {
        try {
            // Use only FlashMessage system for web interface notifications
            // Email notifications remain only for automatic backups
            if (!isset($_SESSION)) {
                session_start();
            }

            $emailData = [
                'siteName' => 'Darkheim Development Studio',
                'siteUrl' => 'https://darkheim.net',
                'filename' => $backupData['filename'] ?? '',
                'fileSize' => $this->formatFileSize($backupData['size'] ?? 0),
                'tablesCount' => $backupData['tables_count'] ?? 0,
                'createdAt' => date('Y-m-d H:i:s', $backupData['timestamp'] ?? time()),
                'backupType' => $backupData['type'] ?? 'full'
            ];

            $adminEmail = $this->getAdminEmail();
            if ($adminEmail && $this->mailer) {
                $success = $this->mailer->sendTemplateEmail(
                    $adminEmail,
                    '✅ Database Backup Successful - Darkheim Development Studio',
                    'backup_success',
                    $emailData
                );

                if ($success) {
                    $this->logger->info('Backup success notification sent', ['to' => $adminEmail]);
                } else {
                    $this->logger->warning('Failed to send backup success notification');
                }
            }
        } catch (Exception $e) {
            $this->logger->error('Failed to send backup success notification', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Sends notification about failed backup
     */
    private function sendBackupFailureNotification(array $errorData): void
    {
        try {
            $emailData = [
                'siteName' => 'Darkheim Development Studio',
                'siteUrl' => 'https://darkheim.net',
                'errorMessage' => $errorData['error'] ?? 'Unknown error',
                'attemptedAt' => date('Y-m-d H:i:s'),
                'backupType' => $errorData['type'] ?? 'full',
                'lastSuccessfulBackup' => $this->getLastSuccessfulBackupDate()
            ];

            $adminEmail = $this->getAdminEmail();
            if ($adminEmail) {
                $success = $this->mailer->sendTemplateEmail(
                    $adminEmail,
                    '🚨 Database Backup Failed - Darkheim Development Studio',
                    'backup_failure',
                    $emailData
                );

                if ($success) {
                    $this->logger->info('Backup failure notification sent', ['to' => $adminEmail]);
                } else {
                    $this->logger->warning('Failed to send backup failure notification');
                }
            }
        } catch (Exception $e) {
            $this->logger->error('Failed to send backup failure notification', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Gets administrator email from settings
     */
    private function getAdminEmail(): string
    {
        try {
            // Try to get email from site settings
            $settingsService = $this->services->getSiteSettingsService();
            $adminEmail = $settingsService->get('admin_email');
            if ($adminEmail) {
                return $adminEmail;
            }
        } catch (Exception $e) {
            $this->logger->warning('Could not get admin email from settings', [
                'error' => $e->getMessage()
            ]);
        }

        // Fallback to default value
        return 'darkheim.studio@gmail.com';
    }

    /**
     * Formats file size in readable format
     */
    private function formatFileSize(int $bytes): string
    {
        if ($bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = floor(log($bytes, 1024));
        $size = round($bytes / pow(1024, $power), 2);

        return $size . ' ' . $units[$power];
    }

    /**
     * Gets date of last successful backup
     */
    private function getLastSuccessfulBackupDate(): string
    {
        try {
            $backups = $this->getBackupsList();
            if (!empty($backups)) {
                return date('Y-m-d H:i:s', $backups[0]['created_at']);
            }
        } catch (Exception $e) {
            $this->logger->warning('Could not get last backup date', [
                'error' => $e->getMessage()
            ]);
        }

        return 'Never';
    }

    // ===================== PRIVATE METHODS =====================

    /**
     * Ensures a backup directory exists
     */
    private function ensureBackupDirectory(): void
    {
        if (!is_dir($this->backupDirectory)) {
            if (!mkdir($this->backupDirectory, 0755, true)) {
                throw new RuntimeException('Failed to create backup directory');
            }
        }

        if (!is_writable($this->backupDirectory)) {
            throw new RuntimeException('Backup directory is not writable');
        }
    }

    /**
     * Generates backup filename
     */
    private function generateBackupFilename(string $type = 'full'): string
    {
        $timestamp = date('Y-m-d_H-i-s');
        $dbName = $_ENV['DB_NAME'] ?? 'database';
        return "backup_{$dbName}_{$type}_$timestamp.sql.gz";
    }

    /**
     * Gets a list of all tables in a database
     */
    private function getAllTables(): array
    {
        $query = "SHOW TABLES";
        $stmt = $this->database->prepare($query);
        $stmt->execute();

        $tables = [];
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $tableName = $row[0];

            // Filter tables if an allowed list is specified
            if (empty($this->allowedTables) || in_array($tableName, $this->allowedTables)) {
                $tables[] = $tableName;
            }
        }

        return $tables;
    }

    /**
     * Generates SQL for full backup
     */
    private function generateSQLBackup(array $tables): string
    {
        $dbName = $_ENV['DB_NAME'] ?? 'database';
        $sql = "-- Database Backup\n";
        $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
        $sql .= "-- Database: " . $dbName . "\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $table) {
            $sql .= $this->dumpTable($table);
        }

        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
        return $sql;
    }

    /**
     * Generates SQL for table structure only
     */
    private function generateStructureSQL(array $tables): string
    {
        $dbName = $_ENV['DB_NAME'] ?? 'database';
        $sql = "-- Database Structure Backup\n";
        $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
        $sql .= "-- Database: " . $dbName . "\n\n";

        foreach ($tables as $table) {
            $sql .= $this->dumpTableStructure($table);
        }

        return $sql;
    }

    /**
     * Creates table dump (structure + data)
     */
    private function dumpTable(string $table): string
    {
        $sql = "-- Table: $table\n";
        $sql .= "DROP TABLE IF EXISTS `$table`;\n";

        // Get table structure
        $createStmt = $this->database->prepare("SHOW CREATE TABLE `$table`");
        $createStmt->execute();
        $createRow = $createStmt->fetch(PDO::FETCH_ASSOC);
        $sql .= $createRow['Create Table'] . ";\n\n";

        // Get table data
        $dataStmt = $this->database->prepare("SELECT * FROM `$table`");
        $dataStmt->execute();

        while ($row = $dataStmt->fetch(PDO::FETCH_ASSOC)) {
            $values = [];
            foreach ($row as $value) {
                if ($value === null) {
                    $values[] = 'NULL';
                } elseif (is_numeric($value)) {
                    // Numbers don't require quotes
                    $values[] = $value;
                } else {
                    // Convert to string and escape
                    $escaped = addslashes((string)$value);
                    $values[] = "'" . $escaped . "'";
                }
            }
            $sql .= "INSERT INTO `$table` VALUES (" . implode(', ', $values) . ");\n";
        }

        $sql .= "\n";
        return $sql;
    }

    /**
     * Creates dump of table structure only
     */
    private function dumpTableStructure(string $table): string
    {
        $sql = "-- Table structure: $table\n";
        $sql .= "DROP TABLE IF EXISTS `$table`;\n";

        $createStmt = $this->database->prepare("SHOW CREATE TABLE `$table`");
        $createStmt->execute();
        $createRow = $createStmt->fetch(PDO::FETCH_ASSOC);
        $sql .= $createRow['Create Table'] . ";\n\n";

        return $sql;
    }

    /**
     * Saves backup in compressed format
     */
    private function saveBackup(string $path, string $content): void
    {
        // Use standard compression level
        $compressionLevel = 6; // Medium compression level
        $compressed = gzencode($content, $compressionLevel);

        if (file_put_contents($path, $compressed) === false) {
            throw new RuntimeException('Failed to save backup file');
        }
    }


    /**
     * Validates backup filename
     */
    private function isValidBackupFile(string $path): bool
    {
        $filename = basename($path);
        return preg_match('/^backup_.*\.sql\.gz$/', $filename) &&
               str_starts_with($path, $this->backupDirectory);
    }

    /**
     * Removes old backups
     */
    private function cleanOldBackups(): void
    {
        $backups = $this->getBackupsList();

        if (count($backups) > $this->maxBackups) {
            $toDelete = array_slice($backups, $this->maxBackups);

            foreach ($toDelete as $backup) {
                $this->deleteBackup($backup['filename']);
            }

            $this->logger->info('Cleaned old backups', [
                'deleted_count' => count($toDelete)
            ]);
        }
    }

    /**
     * Performs backup (for admin panel use)
     */
    public function performBackup(string $type = 'manual'): array
    {
        try {
            $this->logger->info('Starting manual backup from admin panel', ['type' => $type]);

            $result = $this->createFullBackup();

            if ($result['success']) {
                $this->logger->info('Manual backup completed successfully from admin panel', [
                    'filename' => $result['filename'],
                    'size' => $result['size'],
                    'type' => $type
                ]);
            }

            return $result;

        } catch (Exception $e) {
            $this->logger->error('Manual backup failed from admin panel', [
                'error' => $e->getMessage(),
                'type' => $type
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Deletes old backup files (for admin panel use)
     */
    public function cleanupOldBackups(): array
    {
        try {
            $backups = $this->getBackupsList();
            $maxBackups = 30; // Keep last 30 backups

            if (count($backups) <= $maxBackups) {
                return [
                    'success' => true,
                    'deleted_count' => 0,
                    'freed_space' => 0,
                    'message' => 'No old backups to delete'
                ];
            }

            // Determine files to delete (oldest first)
            $backupsToDelete = array_slice($backups, $maxBackups);
            $deletedCount = 0;
            $freedSpace = 0;

            foreach ($backupsToDelete as $backup) {
                if ($this->deleteBackup($backup['filename'])) {
                    $deletedCount++;
                    $freedSpace += $backup['size'];
                }
            }

            $this->logger->info('Cleanup completed from admin panel', [
                'deleted_count' => $deletedCount,
                'freed_space' => $freedSpace
            ]);

            return [
                'success' => true,
                'deleted_count' => $deletedCount,
                'freed_space' => $freedSpace,
                'message' => "Deleted $deletedCount old backup files"
            ];

        } catch (Exception $e) {
            $this->logger->error('Cleanup failed from admin panel', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
