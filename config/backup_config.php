<?php

/**
 * Backup configuration file
 *
 * This file is used to set backup-related settings
 * for the application. It initializes default values
 * and overrides them with values from the database if available.
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

use App\Application\Core\ServiceProvider;

// Initialize default configuration
$config = [
    // Performance settings
    'performance' => [
        'memory_limit' => '512M',
        'timeout' => 3600, // 1 hour
        'compression_level' => 9
    ],

    // Scheduling settings
    'schedule' => [
        'hourly_structure' => false,
        'daily_full' => true,
        'weekly_cleanup' => true
    ],

    // Advanced features
    'advanced' => [
        'verify_backup' => true,
        'create_checksum' => true,
        'parallel_processing' => false
    ],

    // Notification settings
    'notifications' => [
        'email_on_success' => true,
        'email_on_failure' => true,
        'email_address' => 'darkheim.studio@gmail.com'
    ],

    // Storage settings
    'storage' => [
        'path' => '/var/www/darkheim.net/storage/backups',
        'max_files' => 30,
        'retention_days' => 30,
        'size_limit_mb' => 500
    ]
];

try {
    // Load settings from database if available
    $services = ServiceProvider::getInstance();
    $database = $services->getDatabase();

    $stmt = $database->prepare("SELECT setting_key, setting_value FROM site_settings WHERE category = 'backup'");
    $stmt->execute();
    $dbSettings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Override defaults with database settings
    if (!empty($dbSettings)) {
        // Performance settings
        if (isset($dbSettings['backup_compression_level'])) {
            $config['performance']['compression_level'] = (int)$dbSettings['backup_compression_level'];
        }

        // Advanced settings
        if (isset($dbSettings['backup_verify_integrity'])) {
            $config['advanced']['verify_backup'] = (bool)$dbSettings['backup_verify_integrity'];
        }

        // Notification settings
        if (isset($dbSettings['backup_notifications_enabled'])) {
            $config['notifications']['email_on_success'] = (bool)$dbSettings['backup_notifications_enabled'];
            $config['notifications']['email_on_failure'] = (bool)$dbSettings['backup_notifications_enabled'];
        }
        if (isset($dbSettings['backup_notification_email'])) {
            $config['notifications']['email_address'] = $dbSettings['backup_notification_email'];
        }

        // Storage settings
        if (isset($dbSettings['backup_path'])) {
            $config['storage']['path'] = $dbSettings['backup_path'];
        }
        if (isset($dbSettings['backup_max_files'])) {
            $config['storage']['max_files'] = (int)$dbSettings['backup_max_files'];
        }
        if (isset($dbSettings['backup_retention_days'])) {
            $config['storage']['retention_days'] = (int)$dbSettings['backup_retention_days'];
        }
        if (isset($dbSettings['backup_size_limit_mb'])) {
            $config['storage']['size_limit_mb'] = (int)$dbSettings['backup_size_limit_mb'];
        }

        // Structure backup setting
        if (isset($dbSettings['backup_include_structure_only'])) {
            $config['schedule']['hourly_structure'] = (bool)$dbSettings['backup_include_structure_only'];
        }
    }

} catch (Exception $e) {
    // If a database is not available, use defaults
    error_log("Warning: Could not load backup settings from database: " . $e->getMessage());
}

return $config;
