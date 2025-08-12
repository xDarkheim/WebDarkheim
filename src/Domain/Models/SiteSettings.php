<?php

/**
 * Site Settings Model
 * Manages all site configuration stored in database
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Domain\Models;

use App\Domain\Interfaces\DatabaseInterface;
use Exception;
use PDO;
use stdClass;


class SiteSettings
{
    private static ?array $cachedSettings = null;
    
    public function __construct(
        private readonly DatabaseInterface $database
    ) {}

    /**
     * Get all settings grouped by category
     */
    public function getAllSettings(): array
    {
        if (self::$cachedSettings !== null) {
            return self::$cachedSettings;
        }

        $conn = $this->database->getConnection();
        $stmt = $conn->prepare("SELECT * FROM site_settings ORDER BY category, setting_key");
        $stmt->execute();
        
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $category = $row['category'];
            if (!isset($settings[$category])) {
                $settings[$category] = [];
            }
            
            $settings[$category][$row['setting_key']] = [
                'value' => $this->castValue($row['setting_value'], $row['setting_type']),
                'type' => $row['setting_type'],
                'description' => $row['description'],
                'is_public' => (bool) $row['is_public']
            ];
        }
        
        self::$cachedSettings = $settings;
        return $settings;
    }

    /**
     * Get a specific setting value
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $conn = $this->database->getConnection();
        $stmt = $conn->prepare("SELECT setting_value, setting_type FROM site_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$result) {
            return $default;
        }
        
        return $this->castValue($result['setting_value'], $result['setting_type']);
    }

    /**
     * Set a setting value
     */
    public function set(string $key, mixed $value): bool
    {
        $conn = $this->database->getConnection();
        
        // Determine type based on value
        $type = match (true) {
            is_bool($value) => 'boolean',
            is_int($value) => 'integer',
            is_float($value) => 'float',
            is_array($value) => 'array',
            default => 'string'
        };

        // Convert value to string for storage
        $storedValue = $this->prepareValueForStorage($value, $type);
        
        $stmt = $conn->prepare("
            INSERT INTO site_settings (setting_key, setting_value, setting_type) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE 
            setting_value = VALUES(setting_value), 
            setting_type = VALUES(setting_type),
            updated_at = CURRENT_TIMESTAMP
        ");
        
        $result = $stmt->execute([$key, $storedValue, $type]);
        
        // Clear cache when settings change
        self::$cachedSettings = null;
        
        return $result;
    }

    /**
     * Update multiple settings at once
     */
    public function updateMultiple(array $settings): bool
    {
        if (empty($settings)) {
            return false;
        }

        $conn = $this->database->getConnection();

        try {
            $conn->beginTransaction();

            $stmt = $conn->prepare("
                UPDATE site_settings 
                SET setting_value = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE setting_key = ?
            ");
            
            foreach ($settings as $key => $value) {
                // Получаем тип настройки из базы данных
                $typeStmt = $conn->prepare("SELECT setting_type FROM site_settings WHERE setting_key = ?");
                $typeStmt->execute([$key]);
                $typeResult = $typeStmt->fetch(PDO::FETCH_ASSOC);

                if ($typeResult) {
                    $type = $typeResult['setting_type'];
                    $storedValue = $this->prepareValueForStorage($value, $type);
                    $stmt->execute([$storedValue, $key]);
                }
            }
            
            $conn->commit();

            // Clear cache when settings change
            self::$cachedSettings = null;

            return true;
            
        } catch (Exception $e) {
            $conn->rollBack();
            error_log("Failed to update multiple settings: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get settings by category
     */
    public function getByCategory(string $category): array
    {
        $conn = $this->database->getConnection();
        $stmt = $conn->prepare("SELECT * FROM site_settings WHERE category = ? ORDER BY setting_key");
        $stmt->execute([$category]);
        
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = [
                'value' => $this->castValue($row['setting_value'], $row['setting_type']),
                'type' => $row['setting_type'],
                'description' => $row['description'],
                'is_public' => (bool) $row['is_public']
            ];
        }
        
        return $settings;
    }

    /**
     * Get only public settings (for frontend)
     */
    public function getPublicSettings(): array
    {
        $conn = $this->database->getConnection();
        $stmt = $conn->prepare("SELECT setting_key, setting_value, setting_type FROM site_settings WHERE is_public = 1");
        $stmt->execute();
        
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = $this->castValue($row['setting_value'], $row['setting_type']);
        }
        
        return $settings;
    }

    /**
     * Get all settings as a key-value array
     */
    public static function getAll(DatabaseInterface $database): array
    {
        $conn = $database->getConnection();
        $stmt = $conn->prepare("SELECT setting_key, setting_value, setting_type FROM site_settings");
        $stmt->execute();

        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = self::castValueStatic($row['setting_value'], $row['setting_type']);
        }

        return $settings;
    }

    /**
     * Cast setting value to appropriate type (static version)
     */
    private static function castValueStatic($value, $type)
    {
        return match ($type) {
            'boolean' => (bool)$value,
            'integer' => (int)$value,
            'float' => (float)$value,
            'array' => json_decode($value, true) ?: [],
            'object' => json_decode($value) ?: new stdClass(),
            default => (string)$value,
        };
    }

    /**
     * Cast value to appropriate type (instance version)
     */
    private function castValue(string $value, string $type): mixed
    {
        return self::castValueStatic($value, $type);
    }

    /**
     * Prepare value for storage
     */
    private function prepareValueForStorage(mixed $value, string $type): string
    {
        return match ($type) {
            'boolean' => $value ? '1' : '0',
            'integer' => (string)(int)$value,
            'float' => (string)(float)$value,
            'array', 'object' => json_encode($value),
            default => (string)$value,
        };
    }

    /**
     * Clear settings cache
     */
    public static function clearCache(): void
    {
        self::$cachedSettings = null;
    }
}
