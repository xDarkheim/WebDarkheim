<?php

/**
 * Data Transfer Object for system status
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Domain\DTOs;


class SystemStatusDto
{
    private string $databaseStatus;
    private int $databaseTablesCount;
    private string $databaseSize;
    private string $backupStatus;
    private int $totalBackups;
    private ?string $latestBackupDate;
    private string $totalBackupSize;
    private string $memoryUsage;
    private string $diskFree;
    private string $phpVersion;

    public function __construct(
        string $databaseStatus,
        int $databaseTablesCount,
        string $databaseSize,
        string $backupStatus,
        int $totalBackups,
        ?string $latestBackupDate,
        string $totalBackupSize,
        string $memoryUsage,
        string $diskFree,
        string $phpVersion
    ) {
        $this->databaseStatus = $databaseStatus;
        $this->databaseTablesCount = $databaseTablesCount;
        $this->databaseSize = $databaseSize;
        $this->backupStatus = $backupStatus;
        $this->totalBackups = $totalBackups;
        $this->latestBackupDate = $latestBackupDate;
        $this->totalBackupSize = $totalBackupSize;
        $this->memoryUsage = $memoryUsage;
        $this->diskFree = $diskFree;
        $this->phpVersion = $phpVersion;
    }

    public function getDatabaseStatus(): string
    {
        return $this->databaseStatus;
    }

    public function getDatabaseTablesCount(): int
    {
        return $this->databaseTablesCount;
    }

    public function getDatabaseSize(): string
    {
        return $this->databaseSize;
    }

    public function getBackupStatus(): string
    {
        return $this->backupStatus;
    }

    public function getTotalBackups(): int
    {
        return $this->totalBackups;
    }

    public function getLatestBackupDate(): ?string
    {
        return $this->latestBackupDate;
    }

    public function getTotalBackupSize(): string
    {
        return $this->totalBackupSize;
    }

    public function getMemoryUsage(): string
    {
        return $this->memoryUsage;
    }

    public function getDiskFree(): string
    {
        return $this->diskFree;
    }

    public function getPhpVersion(): string
    {
        return $this->phpVersion;
    }

    /**
     * Convert to array for compatibility
     */
    public function toArray(): array
    {
        return [
            'database' => [
                'status' => $this->databaseStatus,
                'tables_count' => $this->databaseTablesCount,
                'database_size' => $this->databaseSize,
            ],
            'backup' => [
                'status' => $this->backupStatus,
                'total_backups' => $this->totalBackups,
                'latest_backup' => ['created' => $this->latestBackupDate],
                'total_size' => $this->totalBackupSize,
            ],
            'performance' => [
                'memory_usage' => ['current' => $this->memoryUsage],
                'disk_space' => ['free' => $this->diskFree],
                'php_version' => $this->phpVersion,
            ],
        ];
    }
}
