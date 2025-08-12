<?php

/**
 * Centralized application state manager
 * Replaces global variable usage with controlled state
 * Provides state management, user authentication, and site configuration
 * Uses LoggerInterface for logging
 * Handles state changes and notifications
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Application\Core;

use App\Domain\Interfaces\LoggerInterface;
use InvalidArgumentException;
use Throwable;

class StateManager
{
    private static ?StateManager $instance = null;
    private array $state = [];
    private array $listeners = [];
    private LoggerInterface $logger;

    private function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->initializeDefaultState();
    }

    public static function getInstance(?LoggerInterface $logger = null): StateManager
    {
        if (self::$instance === null) {
            if ($logger === null) {
                throw new InvalidArgumentException('Logger is required for StateManager initialization');
            }
            self::$instance = new self($logger);
        }
        return self::$instance;
    }

    /**
     * Initialize default state
     */
    private function initializeDefaultState(): void
    {
        $this->state = [
            'app' => [
                'initialized' => false,
                'debug_mode' => false,
                'maintenance_mode' => false,
                'environment' => 'production',
            ],
            'user' => [
                'authenticated' => false,
                'id' => null,
                'username' => null,
                'role' => null,
                'permissions' => [],
            ],
            'site' => [
                'name' => 'Darkheim Studio',
                'url' => 'https://darkheim.net',
                'description' => '',
                'settings' => [],
            ],
            'ui' => [
                'page_title' => '',
                'page_messages' => [],
                'navigation' => [],
                'theme' => 'default',
            ],
            'request' => [
                'current_page' => 'home',
                'is_ajax' => false,
                'csrf_token' => null,
            ],
        ];
    }

    /**
     * Get value from a state
     */
    public function get(string $key, $default = null)
    {
        $keys = explode('.', $key);
        $value = $this->state;

        foreach ($keys as $k) {
            if (!is_array($value) || !array_key_exists($k, $value)) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    /**
     * Set value in state
     */
    public function set(string $key, $value): void
    {
        $keys = explode('.', $key);
        $current = &$this->state;

        foreach ($keys as $k) {
            if (!is_array($current)) {
                $current = [];
            }
            if (!array_key_exists($k, $current)) {
                $current[$k] = [];
            }
            $current = &$current[$k];
        }

        $oldValue = $current;
        $current = $value;

        // Notify listeners about the change
        $this->notifyListeners($key, $value, $oldValue);

        $this->logger->debug("State updated: $key", [
            'old_value' => $oldValue,
            'new_value' => $value
        ]);
    }

    /**
     * Check if the key exists
     */
    public function has(string $key): bool
    {
        $keys = explode('.', $key);
        $current = $this->state;

        foreach ($keys as $k) {
            if (!is_array($current) || !array_key_exists($k, $current)) {
                return false;
            }
            $current = $current[$k];
        }

        return true;
    }

    /**
     * Remove key from state
     */
    public function unset(string $key): void
    {
        $keys = explode('.', $key);
        $current = &$this->state;
        $lastKey = array_pop($keys);

        foreach ($keys as $k) {
            if (!is_array($current) || !array_key_exists($k, $current)) {
                return;
            }
            $current = &$current[$k];
        }

        if (is_array($current) && array_key_exists($lastKey, $current)) {
            unset($current[$lastKey]);
            $this->logger->debug("State key unset: $key");
        }
    }

    /**
     * Get the entire state
     */
    public function getState(): array
    {
        return $this->state;
    }

    /**
     * Get state section
     */
    public function getSection(string $section): array
    {
        return $this->get($section, []);
    }

    /**
     * Bulk update state section
     */
    public function updateSection(string $section, array $data): void
    {
        $currentData = $this->getSection($section);
        $mergedData = array_merge($currentData, $data);
        $this->set($section, $mergedData);
    }

    /**
     * Notify listeners about changes
     */
    private function notifyListeners(string $key, $newValue, $oldValue): void
    {
        if (isset($this->listeners[$key])) {
            foreach ($this->listeners[$key] as $callback) {
                try {
                    $callback($newValue, $oldValue, $key);
                } catch (Throwable $e) {
                    $this->logger->error('State listener error', [
                        'key' => $key,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
    }

    /**
     * Clear state
     */
    public function clear(): void
    {
        $this->state = [];
        $this->initializeDefaultState();
        $this->logger->info('State cleared and reinitialized');
    }

}
