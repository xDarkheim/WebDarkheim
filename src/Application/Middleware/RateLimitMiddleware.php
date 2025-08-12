<?php

/**
 * Simple rate limiter, compatible with current middleware invocation:
 * - handle(): bool without arguments
 * - Safe default values for dependencies
 * - Logging and fallback to local cache
 * - Soft response: 429 and Retry-After header
 * - Fallback to local cache if cache is not available
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Application\Middleware;

use App\Application\Core\ServiceProvider;
use App\Domain\Interfaces\LoggerInterface;
use App\Domain\Interfaces\CacheInterface;
use Throwable;


class RateLimitMiddleware implements MiddlewareInterface
{
    private ?LoggerInterface $logger;
    private ?CacheInterface $cache;
    private int $maxAttempts;
    private int $timeWindow;

    /**
     * Local in-memory cache as a fallback if CacheInterface is not provided.
     * In production, it's better to use a real cache (Redis, APCu, etc.).
     * Format: [ cacheKey => ['value' => int, 'expires' => int] ].
     */
    private array $localCache = [];

    public function __construct(
        ?LoggerInterface $logger = null,
        ?CacheInterface $cache = null,
        int $maxAttempts = 60,
        int $timeWindow = 60
    ) {
        // Logger can be provided externally (via container) or taken from ServiceProvider on first use
        $this->logger = $logger;
        $this->cache = $cache;
        $this->maxAttempts = $maxAttempts;
        $this->timeWindow = $timeWindow;
    }

    /**
     * Unified signature for your runtime: no parameters, returns bool.
     * True — allow the request to proceed; false — block (e.g., set 429).
     */
    public function handle(): bool
    {
        try {
            $clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $cacheKey = "rate_limit_$clientIp";

            $attempts = $this->cacheGet($cacheKey);

            if ($attempts >= $this->maxAttempts) {
                $this->getLogger()?->warning('Rate limit exceeded', [
                    'ip' => $clientIp,
                    'attempts' => $attempts,
                    'max_attempts' => $this->maxAttempts,
                    'time_window' => $this->timeWindow,
                ]);

                // Soft response: can set 429 and show a message
                if (!headers_sent()) {
                    http_response_code(429);
                    header('Retry-After: ' . $this->timeWindow);
                }

                // If you have a centralized error /flash display — you can put a message there
                // And return false so Router/Application stops processing
                return false;
            }

            // Increment the counter
            $this->cacheSet($cacheKey, $attempts + 1, $this->timeWindow);

            return true;
        } catch (Throwable $e) {
            $this->getLogger()?->error('Rate limit middleware failed', [
                'error' => $e->getMessage(),
            ]);
            // In case of failure, do not block the request entirely
            return true;
        }
    }

    private function getLogger(): ?LoggerInterface
    {
        if ($this->logger) {
            return $this->logger;
        }

        // Lazy initialization via ServiceProvider, if available
        try {
            // Avoid hard dependency to prevent failure if the provider is unavailable
            if (class_exists(ServiceProvider::class)) {
                $sp = ServiceProvider::getInstance();
                $this->logger = $sp->getLogger();
            }
        } catch (Throwable) {
            // Ignore — just return null
        }

        return $this->logger;
    }

    private function cacheGet(string $key): int
    {
        if ($this->cache) {
            try {
                $val = $this->cache->get($key, 0);
                return is_numeric($val) ? (int)$val : 0;
            } catch (Throwable) {
                // Should not fail due to cache — use fallback
            }
        }

        // Fallback: clean up expired entries and read the local cache
        $now = time();
        if (isset($this->localCache[$key])) {
            if ($this->localCache[$key]['expires'] > $now) {
                return (int)$this->localCache[$key]['value'];
            }
            unset($this->localCache[$key]);
        }
        return 0;
    }

    private function cacheSet(string $key, int $value, int $ttl): void
    {
        if ($this->cache) {
            try {
                $this->cache->set($key, $value, $ttl);
                return;
            } catch (Throwable) {
                // Fallback below
            }
        }

        $this->localCache[$key] = [
            'value' => $value,
            'expires' => time() + $ttl,
        ];
    }
}
