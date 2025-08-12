<?php

/**
 * Centralized application configuration management
 * Handles loading configuration from various sources and caching
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Application\Core;

use App\Domain\Interfaces\DatabaseInterface;
use App\Domain\Interfaces\CacheInterface;
use App\Domain\Interfaces\LoggerInterface;
use Exception;
use InvalidArgumentException;


class ConfigurationManager
{
    private DatabaseInterface $database;
    private CacheInterface $cache;
    private LoggerInterface $logger;
    private array $config = [];
    private bool $loaded = false;

    public function __construct(
        DatabaseInterface $database,
        CacheInterface $cache,
        LoggerInterface $logger
    ) {
        $this->database = $database;
        $this->cache = $cache;
        $this->logger = $logger;
    }

    /**
     * Loads configuration from various sources
     */
    public function loadConfiguration(): array
    {
        if ($this->loaded) {
            return $this->config;
        }

        // Try to load from a cache
        $cachedConfig = $this->cache->get('app_configuration');
        if ($cachedConfig !== null) {
            $this->config = $cachedConfig;
            $this->loaded = true;
            $this->logger->debug('Configuration loaded from cache');
            return $this->config;
        }

        try {
            // Load from database
            $this->config = $this->loadFromDatabase();

            // Cache for 15 minutes
            $this->cache->set('app_configuration', $this->config, 900);

            $this->loaded = true;
            $this->logger->info('Configuration loaded from database and cached');

        } catch (Exception $e) {
            $this->logger->error('Failed to load configuration from database', [
                'error' => $e->getMessage()
            ]);

            // Fallback to default values
            $this->config = $this->getDefaultConfiguration();
            $this->loaded = true;
        }

        return $this->config;
    }

    /**
     * Gets a configuration value
     */
    public function get(string $key, $default = null)
    {
        if (!$this->loaded) {
            $this->loadConfiguration();
        }

        return $this->getNestedValue($this->config, $key, $default);
    }

    /**
     * Sets a configuration value and saves to DB
     */
    public function set(string $key, $value): bool
    {
        try {
            // Parse key into parts (e.g., 'email.smtp_host')
            $keyParts = explode('.', $key);
            $category = $keyParts[0];
            $setting = $keyParts[1] ?? null;

            if (!$setting) {
                throw new InvalidArgumentException('Invalid configuration key format');
            }

            // Update in database
            $query = "UPDATE site_settings SET value = :value WHERE category = :category AND setting_key = :setting";
            $stmt = $this->database->prepare($query);
            $result = $stmt->execute([
                'value' => $value,
                'category' => $category,
                'setting' => $setting
            ]);

            if ($result) {
                // Update local configuration
                $this->setNestedValue($this->config, $key, $value);

                // Clear cache
                $this->cache->delete('app_configuration');

                $this->logger->info('Configuration updated', [
                    'key' => $key,
                    'value' => $value
                ]);

                return true;
            }

        } catch (Exception $e) {
            $this->logger->error('Failed to update configuration', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
        }

        return false;
    }

    /**
     * Loads configuration from the database
     */
    private function loadFromDatabase(): array
    {
        // Load all settings, including private security settings
        // needed for internal system operation
        $query = "SELECT category, setting_key, setting_value, setting_type, is_public FROM site_settings";
        $stmt = $this->database->prepare($query);
        $stmt->execute();

        $config = [];
        while ($row = $stmt->fetch()) {
            $value = $this->castValue($row['setting_value'], $row['setting_type']);
            $config[$row['category']][$row['setting_key']] = [
                'value' => $value,
                'type' => $row['setting_type'],
                'is_public' => (bool)$row['is_public']
            ];
        }

        return $config;
    }

    /**
     * Returns default configuration
     */
    private function getDefaultConfiguration(): array
    {
        return [
            'site' => [
                'name' => ['value' => 'Darkheim Development Studio', 'type' => 'string'],
                'url' => ['value' => 'https://darkheim.net', 'type' => 'string'],
                'description' => ['value' => 'Professional web development services', 'type' => 'string'],
                'timezone' => ['value' => 'UTC', 'type' => 'string'],
            ],
            'email' => [
                'smtp_host' => ['value' => 'localhost', 'type' => 'string'],
                'smtp_port' => ['value' => 587, 'type' => 'integer'],
                'smtp_secure' => ['value' => 'tls', 'type' => 'string'],
                'smtp_auth' => ['value' => true, 'type' => 'boolean'],
                'from_email' => ['value' => 'noreply@darkheim.net', 'type' => 'string'],
                'from_name' => ['value' => 'Darkheim Studio', 'type' => 'string'],
            ],
            'security' => [
                'session_lifetime' => ['value' => 3600, 'type' => 'integer'],
                'csrf_token_lifetime' => ['value' => 1800, 'type' => 'integer'],
                'max_login_attempts' => ['value' => 5, 'type' => 'integer'],
                'lockout_duration' => ['value' => 900, 'type' => 'integer'],
            ]
        ];
    }

    /**
     * Casts value to the required type
     */
    private function castValue($value, string $type)
    {
        return match ($type) {
            'boolean' => (bool) $value,
            'integer' => (int) $value,
            'float' => (float) $value,
            'array' => is_string($value) ? json_decode($value, true) : $value,
            default => $value,
        };
    }

    /**
     * Gets nested value by key (e.g., 'email.smtp_host')
     */
    private function getNestedValue(array $array, string $key, $default = null)
    {
        $keys = explode('.', $key);
        $current = $array;

        foreach ($keys as $k) {
            if (!isset($current[$k])) {
                return $default;
            }
            $current = $current[$k];
        }

        // If this is a DB setting, return the value
        return $current['value'] ?? $current;
    }

    /**
     * Sets nested value by key
     */
    private function setNestedValue(array &$array, string $key, $value): void
    {
        $keys = explode('.', $key);
        $current = &$array;

        foreach ($keys as $k) {
            if (!isset($current[$k])) {
                $current[$k] = [];
            }
            $current = &$current[$k];
        }

        if (is_array($current) && isset($current['value'])) {
            $current['value'] = $value;
        } else {
            $current = $value;
        }
    }
}
