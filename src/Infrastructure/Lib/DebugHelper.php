<?php

/**
 * Debug Helper for handling debug mode functionality
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Infrastructure\Lib;


class DebugHelper
{
    /**
     * Check if debug mode is enabled
     */
    public static function isDebugMode(): bool
    {
        // Check various debug indicators
        if (defined('APP_DEBUG')) {
            return (bool) APP_DEBUG;
        }
        
        if (isset($_ENV['APP_DEBUG'])) {
            return filter_var($_ENV['APP_DEBUG'], FILTER_VALIDATE_BOOLEAN);
        }
        
        if (isset($_SERVER['APP_DEBUG'])) {
            return filter_var($_SERVER['APP_DEBUG'], FILTER_VALIDATE_BOOLEAN);
        }
        
        // Check environment
        $env = self::getEnvironment();
        return in_array($env, ['development', 'dev', 'local'], true);
    }
    
    /**
     * Get current environment
     */
    public static function getEnvironment(): string
    {
        if (defined('APP_ENV')) {
            return (string) APP_ENV;
        }
        
        if (isset($_ENV['APP_ENV'])) {
            return (string) $_ENV['APP_ENV'];
        }
        
        if (isset($_SERVER['APP_ENV'])) {
            return (string) $_SERVER['APP_ENV'];
        }
        
        // Default to production for safety
        return 'production';
    }
    
    /**
     * Initialize debug constants if not defined
     */
    public static function initializeConstants(): void
    {
        if (!defined('APP_ENV')) {
            define('APP_ENV', self::getEnvironment());
        }
        
        if (!defined('APP_DEBUG')) {
            define('APP_DEBUG', self::isDebugMode());
        }
    }
}

/**
 * Global helper function for debug mode check
 */
if (!function_exists('isDebugMode')) {
    function isDebugMode(): bool
    {
        return DebugHelper::isDebugMode();
    }
}
