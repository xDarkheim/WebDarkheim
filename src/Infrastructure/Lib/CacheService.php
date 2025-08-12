<?php

/**
 * Simple file-based cache service
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Infrastructure\Lib;

use App\Domain\Interfaces\CacheInterface;


class CacheService implements CacheInterface
{
    private string $cacheDir;
    private int $defaultTtl;

    public function __construct(?string $cacheDir = null, int $defaultTtl = 3600)
    {
        $this->cacheDir = $cacheDir ?: ROOT_PATH . DS . 'storage' . DS . 'cache';
        $this->defaultTtl = $defaultTtl;
        
        // Create a cache directory if it doesn't exist
        if (!is_dir($this->cacheDir)) {
            // Check if the parent directory is writable before attempting to create
            $parentDir = dirname($this->cacheDir);
            if (is_writable($parentDir)) {
                mkdir($this->cacheDir, 0755, true);
            } else {
                // Fallback to a writable directory
                $fallbackDir = sys_get_temp_dir() . '/darkheim_cache';
                if (!is_dir($fallbackDir)) {
                    mkdir($fallbackDir, 0755, true);
                }
                $this->cacheDir = $fallbackDir;
            }
        }
    }

    /**
     * Get an item from the cache or execute a callback and store a result
     */
    public function remember(string $key, callable $callback, ?int $ttl = null): mixed
    {
        $ttl = $ttl ?? $this->defaultTtl;
        $cached = $this->get($key);
        
        if ($cached !== null) {
            return $cached;
        }
        
        $value = $callback();
        $this->set($key, $value, $ttl);
        
        return $value;
    }

    /**
     * Get an item from the cache
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $file = $this->getCacheFile($key);
        
        if (!file_exists($file)) {
            return $default;
        }
        
        $data = file_get_contents($file);
        if ($data === false) {
            return $default;
        }
        
        $cached = unserialize($data);
        
        // Check if expired
        if ($cached['expires'] < time()) {
            unlink($file);
            return $default;
        }
        
        return $cached['value'];
    }

    /**
     * Store an item in the cache
     */
    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        $ttl = $ttl ?? $this->defaultTtl;
        $file = $this->getCacheFile($key);
        
        $data = [
            'value' => $value,
            'expires' => time() + $ttl
        ];
        
        return file_put_contents($file, serialize($data), LOCK_EX) !== false;
    }

    /**
     * Remove an item from the cache
     */
    public function forget(string $key): bool
    {
        $file = $this->getCacheFile($key);
        
        if (file_exists($file)) {
            return unlink($file);
        }
        
        return true;
    }

    /**
     * Clear all cache
     */
    public function flush(): bool
    {
        $files = glob($this->cacheDir . '/*.cache');
        $success = true;
        
        foreach ($files as $file) {
            if (!unlink($file)) {
                $success = false;
            }
        }
        
        return $success;
    }

    /**
     * Clear all cache (alias for a flush)
     */
    public function clear(): bool
    {
        return $this->flush();
    }

    /**
     * Remove an item from the cache (alias for forgetting)
     */
    public function delete(string $key): bool
    {
        return $this->forget($key);
    }

    /**
     * Check if an item exists in the cache
     */
    public function has(string $key): bool
    {
        $file = $this->getCacheFile($key);

        if (!file_exists($file)) {
            return false;
        }

        $data = file_get_contents($file);
        if ($data === false) {
            return false;
        }

        $cached = unserialize($data);

        // Check if expired
        if ($cached['expires'] < time()) {
            unlink($file);
            return false;
        }

        return true;
    }

    /**
     * Clean expired cache entries
     */
    public function cleanExpired(): int
    {
        $files = glob($this->cacheDir . '/*.cache');
        $cleaned = 0;
        
        foreach ($files as $file) {
            $data = file_get_contents($file);
            if ($data !== false) {
                $cached = unserialize($data);
                
                if ($cached['expires'] < time()) {
                    unlink($file);
                    $cleaned++;
                }
            }
        }
        
        return $cleaned;
    }

    /**
     * Get the cache file path for a key
     */
    private function getCacheFile(string $key): string
    {
        $hash = md5($key);
        return $this->cacheDir . '/' . $hash . '.cache';
    }

    /**
     * {@inheritdoc}
     */
    public function getMultiple(array $keys, mixed $default = null): array
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function setMultiple(array $values, int $ttl = 3600): bool
    {
        $success = true;
        foreach ($values as $key => $value) {
            if (!$this->set($key, $value, $ttl)) {
                $success = false;
            }
        }
        return $success;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMultiple(array $keys): bool
    {
        $success = true;
        foreach ($keys as $key) {
            if (!$this->delete($key)) {
                $success = false;
            }
        }
        return $success;
    }

    /**
     * {@inheritdoc}
     */
    public function increment(string $key, int $value = 1): int|false
    {
        $current = $this->get($key, 0);

        if (!is_numeric($current)) {
            return false;
        }

        $newValue = (int)$current + $value;

        if ($this->set($key, $newValue)) {
            return $newValue;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function decrement(string $key, int $value = 1): int|false
    {
        $current = $this->get($key, 0);

        if (!is_numeric($current)) {
            return false;
        }

        $newValue = (int)$current - $value;

        if ($this->set($key, $newValue)) {
            return $newValue;
        }

        return false;
    }
}
