<?php

/**
 * Service for managing database backups
 * This service handles the creation and management of database backups.
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Interfaces\LoggerInterface;
use Exception;


class DatabaseBackupService
{
    private LoggerInterface $logger;
    private string $backupDir;

    /**
     * @throws Exception
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;

        // Determine the base project path
        $basePath = defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__, 3);
        $this->backupDir = $basePath . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'backups';

        // Create the directory if it does not exist
        if (!is_dir($this->backupDir)) {
            if (!@mkdir($this->backupDir, 0755, true)) {
                $this->logger->error('Failed to create backup directory', ['path' => $this->backupDir]);
                throw new Exception('Failed to create backup directory');
            }
            $this->logger->info('Backup directory created', ['path' => $this->backupDir]);
        }
    }

}