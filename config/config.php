<?php
/**
 * Unified Configuration System
 *
 * This file initializes core system constants, loads environment variables,
 * and sets up application configuration settings.
 * It also provides functions to access configuration settings from the database
 * and to manage application state.
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

// Check if the file is already loaded
use App\Application\Core\ServiceProvider;
use App\Application\Core\StateManager;
use Random\RandomException;

if (defined('CONFIG_LOADED')) {
    return;
}

// =============================================================================
// CORE SYSTEM CONSTANTS (not recommended to modify)
// =============================================================================

// Path constants
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

const STORAGE_PATH = ROOT_PATH . DS . 'storage';
const LOGS_PATH = STORAGE_PATH . DS . 'logs';
const MAIL_TEMPLATE_PATH = ROOT_PATH . DS . 'resources' . DS . 'views' . DS . 'emails';

// =============================================================================
// ENVIRONMENT VARIABLES LOADING
// =============================================================================

// Load environment variables from .env file
$envFile = ROOT_PATH . DS . '.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) {
            continue; // Skip comments
        }

        if (str_contains($line, '=')) {
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

// =============================================================================
// ENVIRONMENT & DATABASE CONFIGURATION
// =============================================================================

// Database configuration (critically)
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'webengine_darkheim');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');
define('DB_CHARSET', $_ENV['DB_CHARSET'] ?? 'utf8mb4');

// Application environment
define('APP_ENV', $_ENV['APP_ENV'] ?? 'development');
define('APP_DEBUG', filter_var($_ENV['APP_DEBUG'] ?? true, FILTER_VALIDATE_BOOLEAN));

// Check if the application is running in production mode
$is_ajax_request = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                   strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' ||
                   (isset($_GET['action']) && $_GET['action'] === 'ajax');

// Turn off debug mode for AJAX requests
define('APP_DEBUG_AJAX', $is_ajax_request ? false : APP_DEBUG);

// TinyMCE API configuration
define('TINYMCE_API_KEY', $_ENV['TINYMCE_API_KEY'] ?? 'no-api-key');

define('LOG_LEVEL', $_ENV['LOG_LEVEL'] ?? 'debug');

// Security settings
try {
    define('JWT_SECRET', $_ENV['JWT_SECRET'] ?? bin2hex(random_bytes(32)));
} catch (RandomException $e) {

}
define('SESSION_LIFETIME', (int)($_ENV['SESSION_LIFETIME'] ?? 3600));
define('CSRF_TOKEN_LIFETIME', (int)($_ENV['CSRF_TOKEN_LIFETIME'] ?? 1800));

// File upload settings
define('MAX_UPLOAD_SIZE', (int)($_ENV['MAX_UPLOAD_SIZE'] ?? 10485760)); // 10MB
define('ALLOWED_UPLOAD_TYPES', $_ENV['ALLOWED_UPLOAD_TYPES'] ?? 'jpg,jpeg,png,gif,pdf,doc,docx');

// =============================================================================
// BACKUP CONFIGURATION SETTINGS
// =============================================================================

// Backup system settings
define('BACKUP_ENABLED', filter_var($_ENV['BACKUP_ENABLED'] ?? true, FILTER_VALIDATE_BOOLEAN));
define('BACKUP_PATH', $_ENV['BACKUP_PATH'] ?? STORAGE_PATH . DS . 'backups');
define('BACKUP_MAX_FILES', (int)($_ENV['BACKUP_MAX_FILES'] ?? 30));
define('BACKUP_COMPRESSION_LEVEL', (int)($_ENV['BACKUP_COMPRESSION_LEVEL'] ?? 9));
define('BACKUP_RETENTION_DAYS', (int)($_ENV['BACKUP_RETENTION_DAYS'] ?? 30));
define('BACKUP_SIZE_LIMIT_MB', (int)($_ENV['BACKUP_SIZE_LIMIT_MB'] ?? 500));
define('BACKUP_VERIFY_INTEGRITY', filter_var($_ENV['BACKUP_VERIFY_INTEGRITY'] ?? true, FILTER_VALIDATE_BOOLEAN));

// Backup notifications
define('BACKUP_NOTIFICATIONS_ENABLED', filter_var($_ENV['BACKUP_NOTIFICATIONS_ENABLED'] ?? true, FILTER_VALIDATE_BOOLEAN));
define('BACKUP_NOTIFICATION_EMAIL', $_ENV['BACKUP_NOTIFICATION_EMAIL'] ?? 'admin@darkheim.net');

// Backup schedule (cron format)
define('BACKUP_SCHEDULE', $_ENV['BACKUP_SCHEDULE'] ?? '0 2 * * *'); // Daily at 2 AM

// Backup exclusions
define('BACKUP_EXCLUDE_TABLES', $_ENV['BACKUP_EXCLUDE_TABLES'] ?? '');
define('BACKUP_INCLUDE_STRUCTURE_ONLY', filter_var($_ENV['BACKUP_INCLUDE_STRUCTURE_ONLY'] ?? false, FILTER_VALIDATE_BOOLEAN));

// =============================================================================
// SITE SETTINGS
// =============================================================================

// Default site settings
if (!defined('SITE_NAME')) {
    define('SITE_NAME', $_ENV['SITE_NAME'] ?? 'Darkheim Development Studio');
}

if (!defined('SITE_URL')) {
    define('SITE_URL', $_ENV['SITE_URL'] ?? 'https://darkheim.net');
}

// =============================================================================
// ERROR REPORTING SETUP
// =============================================================================

if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ERROR | E_WARNING | E_PARSE);
    ini_set('display_errors', '0');
}
ini_set('log_errors', '1');

// Set the error log path
ini_set('error_log', LOGS_PATH . DS . 'php_errors.log');

// =============================================================================
// CONFIGURATION LOADER FUNCTIONS
// =============================================================================

/**
 * Load configuration settings from the database
 */

function loadDatabaseSettings(): array
{
    static $loaded = false;
    static $settings = [];

    if ($loaded) {
        return $settings;
    }

    try {
        // Check if ServiceProvider is available
        if (class_exists('\App\Application\Core\ServiceProvider')) {
            $serviceProvider = ServiceProvider::getInstance();
            // Use ConfigurationManager to load settings
            $configManager = $serviceProvider->getConfigurationManager();
            $settings = $configManager->loadConfiguration();
            $loaded = true;

            // Check if StateManager is available and use it to set global variables
            if (class_exists('\App\Application\Core\StateManager')) {
                try {
                    $logger = $serviceProvider->getLogger();
                    $stateManager = StateManager::getInstance($logger);

                    if (isset($settings['general']['site_name'])) {
                        $stateManager->set('site.name', $settings['general']['site_name']['value']);
                    }
                    if (isset($settings['general']['site_url'])) {
                        $stateManager->set('site.url', $settings['general']['site_url']['value']);
                    }

                    // Real-time update of debug mode from the database
                    if (isset($settings['features']['debug_mode'])) {
                        $stateManager->set('app.debug', $settings['features']['debug_mode']['value']);
                    }
                } catch (Exception) {
                    // Fallback to the old method
                    if (isset($settings['general']['site_name'])) {
                        $GLOBALS['SITE_NAME'] = $settings['general']['site_name']['value'];
                    }
                    if (isset($settings['general']['site_url'])) {
                        $GLOBALS['SITE_URL'] = $settings['general']['site_url']['value'];
                    }

                    // Real-time update of debug mode from the database
                    if (isset($settings['features']['debug_mode'])) {
                        $GLOBALS['APP_DEBUG_FROM_DB'] = $settings['features']['debug_mode']['value'];
                    }
                }
            } else {
                // If StateManager is not available, use global variables
                if (isset($settings['general']['site_name'])) {
                    $GLOBALS['SITE_NAME'] = $settings['general']['site_name']['value'];
                }
                if (isset($settings['general']['site_url'])) {
                    $GLOBALS['SITE_URL'] = $settings['general']['site_url']['value'];
                }

                // Real-time update of debug mode from the database
                if (isset($settings['features']['debug_mode'])) {
                    $GLOBALS['APP_DEBUG_FROM_DB'] = $settings['features']['debug_mode']['value'];
                }
            }

            return $settings;
        }

        // Fallback to direct database access if ServiceProvider is not available
        $settings = loadDatabaseSettingsFallback();
        $loaded = true;

    } catch (Exception $e) {
        error_log("Failed to load database settings: " . $e->getMessage());
        $settings = [];
    }

    return $settings;
}

/**
 * Fallback method to load settings from the database
 */
function loadDatabaseSettingsFallback(): array
{
    $settings = [];

    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );

        $stmt = $pdo->prepare("
            SELECT category, setting_key, setting_value, setting_type, is_public
            FROM site_settings 
            ORDER BY category, setting_key
        ");
        $stmt->execute();
        $rows = $stmt->fetchAll();

        foreach ($rows as $row) {
            $value = $row['setting_value'];

            // Приводим типы
            switch ($row['setting_type']) {
                case 'boolean':
                    $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                    break;
                case 'integer':
                    $value = (int)$value;
                    break;
                case 'float':
                    $value = (float)$value;
                    break;
            }

            $settings[$row['category']][$row['setting_key']] = [
                'value' => $value,
                'type' => $row['setting_type'],
                'is_public' => (bool)$row['is_public']
            ];
        }

        // Real-time update of debug mode from the database
        if (isset($settings['features']['debug_mode'])) {
            $GLOBALS['APP_DEBUG_FROM_DB'] = $settings['features']['debug_mode']['value'];
        }

    } catch (Exception $e) {
        error_log("Fallback database settings load failed: " . $e->getMessage());
    }

    return $settings;
}

/**
 * Get a setting value from the database or a fallback value
 */
function getSetting(string $category, string $key, $default = null)
{
    static $configManager = null;

    // Try to get the ConfigurationManager instance
    if ($configManager === null && class_exists('\App\Application\Core\ServiceProvider')) {
        try {
            $serviceProvider = ServiceProvider::getInstance();
            $configManager = $serviceProvider->getConfigurationManager();
        } catch (Exception) {
            // Fallback to the old method if ServiceProvider is not available
            $configManager = false;
        }
    }

    if ($configManager) {
        return $configManager->get("$category.$key", $default);
    }

    // Fallback к старому методу
    static $settings = null;
    if ($settings === null) {
        $settings = loadDatabaseSettings();
    }

    return $settings[$category][$key]['value'] ?? $default;
}

/**
 * Get a SITE_URL setting from the database or a fallback value
 */
function getSiteUrl(): string
{
    return $GLOBALS['SITE_URL'] ?? SITE_URL;
}

/**
 * Get a SITE_NAME setting from the database or a fallback value
 */
function getSiteName(): string
{
    return $GLOBALS['SITE_NAME'] ?? SITE_NAME;
}

// =============================================================================
// COMPATIBILITY HELPERS
// =============================================================================

if (!function_exists('config')) {
    function config(string $key, $default = null) {
        $parts = explode('.', $key);
        if (count($parts) === 2) {
            return getSetting($parts[0], $parts[1], $default);
        }
        return $default;
    }
}

/**
 * Gets debug mode considering overrides from the database
 */
function isDebugMode(): bool
{
    // First, check the global variable (override from the database)
    if (isset($GLOBALS['APP_DEBUG_FROM_DB'])) {
        return (bool)$GLOBALS['APP_DEBUG_FROM_DB'];
    }

    // Then check via StateManager if available
    if (class_exists('\App\Application\Core\ServiceProvider')) {
        try {
            $serviceProvider = ServiceProvider::getInstance();
            if (class_exists('\App\Application\Core\StateManager')) {
                $logger = $serviceProvider->getLogger();
                $stateManager = StateManager::getInstance($logger);
                $debugFromState = $stateManager->get('app.debug');
                if ($debugFromState !== null) {
                    return (bool)$debugFromState;
                }
            }
        } catch (Exception $e) {
            // If unable to get via StateManager, continue
            error_log("Failed to get debug mode from StateManager: " . $e->getMessage());
        }
    }

    // Fallback to the setting from the database
    try {
        $debugFromDb = getSetting('features', 'debug_mode');
        if ($debugFromDb !== null) {
            return (bool)$debugFromDb;
        }
    } catch (Exception $e) {
        // If unable to get from the database, continue with the global variable
        error_log("Failed to get debug mode from database: " . $e->getMessage());
    }

    // Final fallback to the global variable
    return APP_DEBUG;
}

// =============================================================================
// INITIALIZATION
// =============================================================================

// Load database settings
loadDatabaseSettings();
