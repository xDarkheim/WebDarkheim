<?php

/**
 * Collection for working with sets of LogEntryDto
 * This class provides methods for adding, filtering, and converting items.
 * It also provides methods for getting all items, counting, and checking if the collection is empty.
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Domain\Collections;

use App\Domain\DTOs\LogEntryDto;

class LogEntryCollection
{
    /** @var LogEntryDto[] */
    private array $items = [];

    /**
     * @param LogEntryDto[] $items
     */
    public function __construct(array $items = [])
    {
        foreach ($items as $item) {
            $this->add($item);
        }
    }

    /**
     * Add an item to the collection
     */
    public function add(LogEntryDto $entry): void
    {
        $this->items[] = $entry;
    }

    /**
     * Get all items
     *
     * @return LogEntryDto[]
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * Get the number of items
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * Check if the collection is empty
     */
    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    /**
     * Filter entries by level
     */
    public function filterByLevel(string $level): self
    {
        $filtered = array_filter($this->items, function (LogEntryDto $entry) use ($level) {
            return $entry->getLevel() === strtoupper($level);
        });

        return new self(array_values($filtered));
    }

    /**
     * Get only errors
     */
    public function getErrors(): self
    {
        $errors = array_filter($this->items, function (LogEntryDto $entry) {
            return $entry->isError();
        });

        return new self(array_values($errors));
    }

    /**
     * Get only warnings
     */
    public function getWarnings(): self
    {
        $warnings = array_filter($this->items, function (LogEntryDto $entry) {
            return $entry->isWarning();
        });

        return new self(array_values($warnings));
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return array_map(function (LogEntryDto $entry) {
            return $entry->toArray();
        }, $this->items);
    }

    /**
     * Create a collection from an array
     */
    public static function fromArray(array $data): self
    {
        $items = array_map(function (array $item) {
            return LogEntryDto::fromArray($item);
        }, $data);

        return new self($items);
    }
}
