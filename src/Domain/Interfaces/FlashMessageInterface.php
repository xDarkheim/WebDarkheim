<?php

/**
 * Flash message interface following Interface Segregation Principle
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Domain\Interfaces;


interface FlashMessageInterface
{
    /**
     * Add a success message
     */
    public function addSuccess(string $message, bool $isHtml = false): void;

    /**
     * Add an error message
     */
    public function addError(string $message, bool $isHtml = false): void;

    /**
     * Add a warning message
     */
    public function addWarning(string $message, bool $isHtml = false): void;

    /**
     * Add an info message
     */
    public function addInfo(string $message, bool $isHtml = false): void;

    /**
     * Get all messages of a specific type
     */
    public function getMessages(string $type = ''): array;

    /**
     * Check if there are any messages
     */
    public function hasMessages(string $type = ''): bool;

    /**
     * Clear all messages or messages of a specific type
     */
    public function clearMessages(string $type = ''): void;

    /**
     * Display messages as HTML
     */
    public function display(string $type = ''): string;
}