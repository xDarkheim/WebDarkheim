#!/usr/bin/env php
<?php

declare(strict_types=1);

// Clear all log files
require_once dirname(__DIR__) . '/includes/bootstrap.php';

$logPaths = [
    ROOT_PATH . '/storage/logs/app.log',
    ROOT_PATH . '/storage/logs/php_errors.log',
    ROOT_PATH . '/storage/logs/system_monitor_cron.log',
    ROOT_PATH . '/storage/logs/error.log',
    ROOT_PATH . '/storage/logs/access.log'
];

echo "🗑️  Clearing log files...\n\n";

foreach ($logPaths as $logPath) {
    if (file_exists($logPath)) {
        $size = filesize($logPath);
        $sizeFormatted = formatFileSize($size);

        // Очищаем файл
        file_put_contents($logPath, '');

        echo "✅ Cleared: " . basename($logPath) . " (was {$sizeFormatted})\n";
    } else {
        echo "⚠️  Not found: " . basename($logPath) . "\n";
    }
}

// Создаем директорию логов если не существует
$logsDir = ROOT_PATH . '/storage/logs';
if (!is_dir($logsDir)) {
    mkdir($logsDir, 0755, true);
    echo "📁 Created logs directory: {$logsDir}\n";
}

// Создаем пустые файлы логов
foreach ($logPaths as $logPath) {
    if (!file_exists($logPath)) {
        touch($logPath);
        chmod($logPath, 0644);
        echo "📄 Created empty log file: " . basename($logPath) . "\n";
    }
}

echo "\n✨ All logs cleared successfully!\n";
echo "💡 Run this script: php " . __FILE__ . "\n";

function formatFileSize(int $bytes): string
{
    if ($bytes === 0) return '0 B';

    $units = ['B', 'KB', 'MB', 'GB'];
    $power = floor(log($bytes, 1024));

    return round($bytes / pow(1024, $power), 2) . ' ' . $units[$power];
}
