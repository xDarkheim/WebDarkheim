<?php
/**
 * PHPStan bootstrap file
 * Loads basic setup for static analysis
 */

// Define basic constants for PHPStan analysis (only if not already defined)
if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__);
}

// Define database constants for PHPStan analysis (only if not already defined)
if (!defined('DB_HOST')) {
    define('DB_HOST', 'localhost');
}
if (!defined('DB_NAME')) {
    define('DB_NAME', 'darkheim_db');
}
if (!defined('DB_USER')) {
    define('DB_USER', 'user');
}
if (!defined('DB_PASS')) {
    define('DB_PASS', 'password');
}
if (!defined('DB_CHARSET')) {
    define('DB_CHARSET', 'utf8mb4');
}

// Load environment variables from .env file
$envFile = __DIR__ . DIRECTORY_SEPARATOR . '.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue; // Skip comments
        }
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            if (!array_key_exists($name, $_ENV)) {
                $_ENV[$name] = $value;
                putenv("$name=$value");
            }
        }
    }
}

// Load Composer autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Initialize debug helper
use App\Infrastructure\Lib\DebugHelper;
DebugHelper::initializeConstants();

// Define missing functions for PHPStan analysis
if (!function_exists('isDebugMode')) {
    function isDebugMode(): bool {
        return defined('APP_DEBUG') ? (bool) APP_DEBUG : false;
    }
}

if (!function_exists('getSiteName')) {
    function getSiteName(): string {
        return 'Darkheim Studio';
    }
}

if (!function_exists('getSiteUrl')) {
    function getSiteUrl(): string {
        return 'https://darkheim.net';
    }
}

if (!function_exists('getSetting')) {
    function getSetting(string $key, mixed $default = null, ?string $category = null): mixed {
        return $default;
    }
}

// Define functions that are used for error demonstration in ErrorController
if (!function_exists('nonExistentFunction')) {
    function nonExistentFunction(): void {
        // This is intentionally left empty for error demonstration
    }
}
