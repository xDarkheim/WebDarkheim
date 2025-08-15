<?php

declare(strict_types=1);

namespace App\Domain\Interfaces;

/**
 * Session Manager Interface
 * Defines contract for session management
 * Follows ISP - only session-related methods
 */
interface SessionManagerInterface
{
    /**
     * Start session
     */
    public function start(): bool;

    /**
     * Destroy session
     */
    public function destroy(): bool;

    /**
     * Regenerate session ID
     */
    public function regenerateId(bool $deleteOldSession = false): bool;

    /**
     * Get session data
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Set session data
     */
    public function set(string $key, mixed $value): void;

    /**
     * Check if session has key
     */
    public function has(string $key): bool;

    /**
     * Remove session data
     */
    public function remove(string $key): void;

    /**
     * Get all session data
     */
    public function all(): array;

    /**
     * Flash message for next request
     */
    public function flash(string $key, mixed $value): void;

    /**
     * Check if session is active
     */
    public function isActive(): bool;
}
