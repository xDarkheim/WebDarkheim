<?php

/**
 * Cache interface for caching operations
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Domain\Interfaces;


interface CacheInterface
{
    /**
     * Store an item in the cache
     */
    public function set(string $key, mixed $value, int $ttl = 3600): bool;

    /**
     * Retrieve an item from the cache
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Check if an item exists in the cache
     */
    public function has(string $key): bool;

    /**
     * Remove an item from the cache
     */
    public function delete(string $key): bool;

    /**
     * Clear all items from the cache
     */
    public function clear(): bool;

    /**
     * Remember a value in the cache with callback
     */
    public function remember(string $key, callable $callback, int $ttl = 3600): mixed;

    /**
     * Increment a numeric cache value
     */
    public function increment(string $key, int $value = 1): int|false;

    /**
     * Decrement a numeric cache value
     */
    public function decrement(string $key, int $value = 1): int|false;

    /**
     * Get multiple cache values
     */
    public function getMultiple(array $keys): array;

    /**
     * Set multiple cache values
     */
    public function setMultiple(array $values, int $ttl = 3600): bool;

    /**
     * Delete multiple cache values
     */
    public function deleteMultiple(array $keys): bool;
}
