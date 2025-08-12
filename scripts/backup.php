#!/usr/bin/env php
<?php
/**
 * CLI script for monitoring automatic database backups
 * Usage: php scripts/backup.php [command]
 * For monitoring and statistics only
 */

declare(strict_types=1);

// Load autoloader and configuration
require_once dirname(__DIR__) . '/includes/bootstrap.php';

use App\Application\Controllers\DatabaseBackupController;
use App\Application\Core\ServiceProvider;

/**
 * Class for monitoring automatic backups
 */
class BackupMonitor
{
    private DatabaseBackupController $backupController;
    private array $commands;

    public function __construct()
    {
        $this->backupController = new DatabaseBackupController();

        $this->commands = [
            'list' => 'Show backup list',
            'stats' => 'Show backup statistics',
            'health' => 'Check backup system health',
            'help' => 'Show help'
        ];
    }

    public function run(array $argv): void
    {
        $command = $argv[1] ?? 'help';

        switch ($command) {
            case 'list':
                $this->listBackups();
                break;
            case 'stats':
                $this->showStats();
                break;
            case 'health':
                $this->checkHealth();
                break;
            case 'help':
            default:
                $this->showHelp();
                break;
        }
    }

    private function listBackups(): void
    {
        $backups = $this->backupController->getBackupsList();

        if (empty($backups)) {
            echo "📭 No backups found\n";
            return;
        }

        echo "📋 Automatic backups list:\n";
        echo str_repeat("-", 80) . "\n";
        printf("%-35s %-12s %-8s %s\n", "Filename", "Size", "Age", "Created at");
        echo str_repeat("-", 80) . "\n";

        foreach ($backups as $backup) {
            printf(
                "%-35s %-12s %-8s %s\n",
                $backup['filename'],
                $this->formatBytes($backup['size']),
                $backup['age_days'] . 'd',
                date('Y-m-d H:i:s', $backup['created_at'])
            );
        }
    }

    private function showStats(): void
    {
        $backups = $this->backupController->getBackupsList();
        $totalSize = array_sum(array_column($backups, 'size'));

        echo "📊 Automatic backup statistics:\n";
        echo str_repeat("-", 40) . "\n";
        echo "📦 Total backups: " . count($backups) . "\n";
        echo "💾 Total size: " . $this->formatBytes($totalSize) . "\n";

        if (!empty($backups)) {
            $averageSize = $totalSize / count($backups);
            echo "📈 Average size: " . $this->formatBytes($averageSize) . "\n";
            echo "🕒 Latest: " . date('Y-m-d H:i:s', $backups[0]['created_at']) . "\n";
            echo "📅 Oldest: " . date('Y-m-d H:i:s', end($backups)['created_at']) . "\n";
        }
    }

    private function checkHealth(): void
    {
        $backups = $this->backupController->getBackupsList();

        echo "🏥 Automatic backup system status: ";

        $warnings = [];
        $errors = [];

        // Check for recent backups
        if (empty($backups)) {
            $errors[] = 'No backups available';
        } else {
            $latestBackup = reset($backups);
            $daysSinceLastBackup = $latestBackup['age_days'];

            if ($daysSinceLastBackup > 7) {
                $errors[] = "Last backup created {$daysSinceLastBackup} days ago";
            } elseif ($daysSinceLastBackup > 1) {
                $warnings[] = "Last backup created {$daysSinceLastBackup} days ago";
            }
        }

        // Check disk space
        $backupDir = ROOT_PATH . DS . 'storage' . DS . 'backups';
        if (is_dir($backupDir)) {
            $freeSpace = disk_free_space($backupDir);
            $totalSpace = disk_total_space($backupDir);
            $usedPercent = (($totalSpace - $freeSpace) / $totalSpace) * 100;

            if ($usedPercent > 90) {
                $errors[] = 'Disk space critically low';
            } elseif ($usedPercent > 80) {
                $warnings[] = 'Disk space running low';
            }
        }

        if (empty($errors) && empty($warnings)) {
            echo "✅ Healthy\n";
        } elseif (!empty($errors)) {
            echo "❌ Errors detected\n";
        } else {
            echo "⚠️ Warnings detected\n";
        }

        if (!empty($warnings)) {
            echo "\n⚠️ Warnings:\n";
            foreach ($warnings as $warning) {
                echo "  - {$warning}\n";
            }
        }

        if (!empty($errors)) {
            echo "\n❌ Errors:\n";
            foreach ($errors as $error) {
                echo "  - {$error}\n";
            }
            exit(1);
        }
    }

    private function showHelp(): void
    {
        echo "🔍 Automatic Database Backup Monitor\n";
        echo str_repeat("=", 50) . "\n";
        echo "Usage: php backup.php <command>\n\n";
        echo "Available commands:\n";

        foreach ($this->commands as $command => $description) {
            echo sprintf("  %-10s %s\n", $command, $description);
        }

        echo "\nExamples:\n";
        echo "  php backup.php list     # List backups\n";
        echo "  php backup.php stats    # Show statistics\n";
        echo "  php backup.php health   # Check system health\n";

        echo "\nInformation:\n";
        echo "  • Automatic backups are created daily at 2:00 AM\n";
        echo "  • Old backups are automatically cleaned every Sunday\n";
        echo "  • Logs are available at /var/log/darkheim_backup.log\n";
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        return round($bytes / (1024 ** $pow), 2) . ' ' . $units[$pow];
    }
}

// Запуск CLI
try {
    $monitor = new BackupMonitor();
    $monitor->run($argv);
} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
    exit(1);
}
