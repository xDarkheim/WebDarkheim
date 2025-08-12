<?php

/**
 * Site Settings Service
 * Handles all site configuration management
 * Uses LoggerInterface for logging
 * Uses DatabaseInterface for database interaction
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Models\SiteSettings;
use App\Domain\Interfaces\DatabaseInterface;
use Exception;
use Throwable;


class SiteSettingsService
{
    private SiteSettings $settingsModel;

    public function __construct(DatabaseInterface $database)
    {
        $this->settingsModel = new SiteSettings($database);
    }

    /**
     * Get all settings grouped by category for the admin panel
     */
    public function getAllForAdmin(): array
    {
        return $this->settingsModel->getAllSettings();
    }

    /**
     * Get settings for a specific category
     */
    public function getByCategory(string $category): array
    {
        return $this->settingsModel->getByCategory($category);
    }

    /**
     * Get a single setting value
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->settingsModel->get($key, $default);
    }

    /**
     * Update multiple settings with validation
     */
    public function updateSettings(array $settings): bool
    {
        $validatedSettings = $this->validateSettings($settings);

        if (empty($validatedSettings)) {
            return false;
        }

        return $this->settingsModel->updateMultiple($validatedSettings);
    }

    /**
     * Get public settings for frontend use
     */
    public function getPublicSettings(): array
    {
        return $this->settingsModel->getPublicSettings();
    }

    /**
     * Validate settings before saving
     */
    private function validateSettings(array $settings): array
    {
        $validated = [];

        foreach ($settings as $key => $value) {
            $trimmedValue = is_string($value) ? trim($value) : $value;

            switch ($key) {
                case 'site_name':
                    if (empty($trimmedValue)) {
                        error_log('Validation error: Site Name cannot be empty');
                        return [];
                    } elseif (strlen($trimmedValue) > 255) {
                        error_log('Validation error: Site Name is too long');
                        return [];
                    } else {
                        $validated[$key] = $trimmedValue;
                    }
                    break;

                case 'site_description':
                    if (strlen($trimmedValue) > 500) {
                        error_log('Validation error: Site Description is too long');
                        return [];
                    } else {
                        $validated[$key] = $trimmedValue;
                    }
                    break;

                case 'contact_email':
                case 'admin_email':
                    if (!empty($trimmedValue) && !filter_var($trimmedValue, FILTER_VALIDATE_EMAIL)) {
                        error_log("Validation error: Invalid email format for $key");
                        return [];
                    } else {
                        $validated[$key] = $trimmedValue;
                    }
                    break;

                case 'site_url':
                    if (!empty($trimmedValue) && !filter_var($trimmedValue, FILTER_VALIDATE_URL)) {
                        error_log('Validation error: Invalid URL format for site_url');
                        return [];
                    } else {
                        $validated[$key] = $trimmedValue;
                    }
                    break;

                case 'max_upload_size':
                case 'session_timeout':
                case 'max_login_attempts':
                    if (!is_numeric($trimmedValue) || $trimmedValue < 0) {
                        error_log("Validation error: $key must be a positive number");
                        return [];
                    } else {
                        $validated[$key] = (int) $trimmedValue;
                    }
                    break;

                // Boolean settings
                case 'maintenance_mode':
                case 'registration_enabled':
                case 'enable_comments':
                case 'require_email_verification':
                case 'enable_cache':
                case 'enable_ssl':
                case 'enable_analytics':
                case 'enable_social_sharing':
                    $validated[$key] = $trimmedValue ? '1' : '0';
                    break;

                // Text/string settings - general validation
                default:
                    if (is_string($trimmedValue) && strlen($trimmedValue) > 1000) {
                        error_log("Validation error: Setting '$key' is too long");
                        return [];
                    } else {
                        $validated[$key] = $trimmedValue;
                    }
                    break;
            }
        }

        return $validated;
    }

    /**
     * Test email configuration
     */
    public function testEmailConfiguration(): bool
    {
        try {
            $email = $this->get('contact_email');
            if (empty($email)) {
                return false;
            }

            // Here you can add an actual test email sending
            // In this case, just check email validity
            return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
        } catch (Exception $e) {
            error_log('Email test failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Clear application cache
     */
    public function clearCache(): array
    {
        $result = ['files_cleared' => 0, 'errors' => []];

        try {
            // Clear settings cache
            SiteSettings::clearCache();

            // Determine a base project path
            $basePath = defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__, 3);

            // Cache directories to clear - use only a correct path
            $cacheDirs = [
                $basePath . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'cache',
            ];

            foreach ($cacheDirs as $cacheDir) {
                if (is_dir($cacheDir)) {
                    // Changed to use INFO prefix instead of treating as error
                    error_log('INFO: Clearing cache directory: ' . $cacheDir);
                    $files = glob($cacheDir . '/*.cache');
                    if ($files === false) {
                        error_log('Failed to glob cache files in: ' . $cacheDir);
                        continue;
                    }

                    foreach ($files as $file) {
                        if (is_file($file)) {
                            if ($this->deleteFileWithPermissionCheck($file)) {
                                $result['files_cleared']++;
                                // Success operations should not be logged at the error level
                                // Removing excessive logging for individual file deletions
                            } else {
                                $error = 'Failed to delete: ' . basename($file) . ' (permission denied)';
                                $result['errors'][] = $error;
                                error_log("Cache clear error: " . $error);
                            }
                        }
                    }
                } else {
                    error_log("Cache directory not found: " . $cacheDir);
                }
            }

            // Additional clearing of other cache types
            $this->clearAdditionalCache($result);

        } catch (Exception $e) {
            $errorMsg = 'Cache clear failed: ' . $e->getMessage();
            error_log($errorMsg);
            $result['errors'][] = $errorMsg;
        }

        // Log result as info, not as error
        error_log("INFO: Cache clear completed. Files cleared: {$result['files_cleared']}, Errors: " . count($result['errors']));

        return $result;
    }

    /**
     * Clear additional cache types
     */
    private function clearAdditionalCache(array &$result): void
    {
        try {
            // Clear OPcache if available
            if (function_exists('opcache_reset')) {
                if (opcache_reset()) {
                    // Changed from error_log to info level for a success message
                    error_log("INFO: OPcache cleared successfully");
                } else {
                    $result['errors'][] = 'Failed to clear OPcache';
                }
            }

            // Clear APCu cache if available
            if (function_exists('apcu_clear_cache')) {
                if (apcu_clear_cache()) {
                    // Changed from error_log to info level for a success message
                    error_log("INFO: APCu cache cleared successfully");
                } else {
                    $result['errors'][] = 'Failed to clear APCu cache';
                }
            }

            // Clear template cache
            $basePath = defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__, 3);
            $templateCacheDir = $basePath . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'template_cache';

            if (is_dir($templateCacheDir)) {
                $files = glob($templateCacheDir . '/*');
                if ($files) {
                    foreach ($files as $file) {
                        if (is_file($file) && $this->deleteFileWithPermissionCheck($file)) {
                            $result['files_cleared']++;
                        }
                    }
                }
            }

        } catch (Exception $e) {
            error_log("Additional cache clear failed: " . $e->getMessage());
            $result['errors'][] = 'Additional cache clear failed: ' . $e->getMessage();
        }
    }

    /**
     * Delete a file with a permission check and repair attempt
     */
    private function deleteFileWithPermissionCheck(string $filePath): bool
    {
        try {
            // Check if a file exists
            if (!is_file($filePath)) {
                return false;
            }

            // First attempt to delete without changing permissions
            if (@unlink($filePath)) {
                return true;
            }

            // If deletion failed, try to fix permissions
            $parentDir = dirname($filePath);

            // Fix parent directory permissions if needed
            if (!is_writable($parentDir)) {
                @chmod($parentDir, 0755);
            }

            // Fix file permissions
            @chmod($filePath, 0666);

            // Second attempt to delete after permission fix
            return @unlink($filePath);

        } catch (Throwable $e) {
            error_log("Error deleting cache file $filePath: " . $e->getMessage());
            return false;
        }
    }
}
