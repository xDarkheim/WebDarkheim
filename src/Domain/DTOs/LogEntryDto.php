<?php

/**
 * Data Transfer Object for log entry
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Domain\DTOs;


class LogEntryDto
{
    private string $timestamp;
    private string $level;
    private string $message;
    private array $context;
    private ?string $channel;

    public function __construct(
        string $timestamp,
        string $level,
        string $message,
        array $context = [],
        ?string $channel = null
    ) {
        $this->timestamp = $timestamp;
        $this->level = strtoupper($level);
        $this->message = $message;
        $this->context = $context;
        $this->channel = $channel;
    }

    public function getTimestamp(): string
    {
        return $this->timestamp;
    }

    public function getLevel(): string
    {
        return $this->level;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function getChannel(): ?string
    {
        return $this->channel;
    }

    /**
     * Checks if the entry is an error
     */
    public function isError(): bool
    {
        return in_array($this->level, ['ERROR', 'CRITICAL', 'EMERGENCY']);
    }

    /**
     * Checks if the entry is a warning
     */
    public function isWarning(): bool
    {
        return $this->level === 'WARNING';
    }

    /**
     * Convert to array for compatibility
     */
    public function toArray(): array
    {
        return [
            'timestamp' => $this->timestamp,
            'level' => $this->level,
            'message' => $this->message,
            'context' => $this->context,
            'channel' => $this->channel,
        ];
    }

    /**
     * Create from array data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['timestamp'] ?? '',
            $data['level'] ?? 'INFO',
            $data['message'] ?? '',
            $data['context'] ?? [],
            $data['channel'] ?? null
        );
    }
}
