<?php

/**
 * Data Transfer Object for monitoring results
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Domain\DTOs;

use App\Domain\Collections\LogEntryCollection;


class MonitoringResultDto
{
    private LogEntryCollection $logEntries;
    private int $totalLines;
    private int $errorCount;
    private int $warningCount;
    private string $logType;
    private string $formattedSize;

    public function __construct(
        LogEntryCollection $logEntries,
        int $totalLines,
        int $errorCount,
        int $warningCount,
        string $logType,
        string $formattedSize
    ) {
        $this->logEntries = $logEntries;
        $this->totalLines = $totalLines;
        $this->errorCount = $errorCount;
        $this->warningCount = $warningCount;
        $this->logType = $logType;
        $this->formattedSize = $formattedSize;
    }

    public function getLogEntries(): LogEntryCollection
    {
        return $this->logEntries;
    }

    public function getTotalLines(): int
    {
        return $this->totalLines;
    }

    public function getErrorCount(): int
    {
        return $this->errorCount;
    }

    public function getWarningCount(): int
    {
        return $this->warningCount;
    }

    public function getLogType(): string
    {
        return $this->logType;
    }

    public function getFormattedSize(): string
    {
        return $this->formattedSize;
    }

    /**
     * Convert to array for compatibility
     */
    public function toArray(): array
    {
        return [
            'entries' => $this->logEntries->toArray(),
            'total_lines' => $this->totalLines,
            'error_count' => $this->errorCount,
            'warning_count' => $this->warningCount,
            'log_type' => $this->logType,
            'formatted_size' => $this->formattedSize,
        ];
    }
}
