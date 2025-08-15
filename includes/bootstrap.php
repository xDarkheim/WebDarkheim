<?php

/**
 * Bootstrap file for the application
 * This file initializes the application environment and loads essential parts.
 * It also sets up error handling, session management, and configuration.
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

// Prevent multiple executions
if (defined('BOOTSTRAP_LOADED')) {
    return;
}
const BOOTSTRAP_LOADED = true;

// Load environment variables
$envFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) {
            continue;
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

// Load configuration
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';

// Load Composer autoloader
$autoload_path = ROOT_PATH . DS . 'vendor' . DS . 'autoload.php';
if (!file_exists($autoload_path)) {
    throw new RuntimeException("Composer autoload.php not found. Please run 'composer install'.");
}
require_once $autoload_path;

// Initialize debug helper and constants
use App\Application\Helpers\NavigationHelper;
use App\Domain\Interfaces\LoggerInterface;
use App\Domain\Models\User;
use App\Infrastructure\Lib\DebugHelper;
DebugHelper::initializeConstants();

// The session will be started centrally via SessionManager in the Application class
// Removed duplicate session_start() to avoid "headers already sent" errors
// CSRF tokens are now managed via SessionManager and CSRFMiddleware
// Removed old logic for generating $_SESSION['csrf_token']

// Initialize the application container
use App\Application\Core\Container;
use App\Application\Core\ServiceProvider;
use App\Application\Core\ErrorHandler;
use App\Infrastructure\Lib\Logger;
use App\Infrastructure\Lib\TokenManager;

$container = new Container();
$serviceProvider = ServiceProvider::getInstance($container);

// Register core services
$serviceProvider->registerCoreServices();

// Initialize error handling
try {
    $logger = $container->make(LoggerInterface::class);
} catch (ReflectionException $e) {
    throw new RuntimeException('Logger service not found in the container: ' . $e->getMessage());
}
$debugMode = ($_ENV['APP_ENV'] ?? 'production') === 'development';

// Determine log level based on a request type
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' ||
           (isset($_GET['action']) && $_GET['action'] === 'ajax');

// For AJAX requests, use only the ERROR level
$logLevel = $is_ajax ? 'error' : ($debugMode ? 'debug' : 'info');

$errorHandler = new ErrorHandler($logger, $debugMode);
$errorHandler->register();

// Load database settings
try {
    $site_settings_from_db = loadDatabaseSettings();
    $container->value('site_settings', $site_settings_from_db);

    // Log configuration loading only for regular requests, not for AJAX
    if (!$is_ajax) {
        $totalSettings = array_sum(array_map('count', $site_settings_from_db));
        $logger->info('Configuration loaded successfully', ['total_settings' => $totalSettings]);
    }

} catch (Exception $e) {
    $logger->error('Failed to load database settings: ' . $e->getMessage());
    // Fallback to default settings
    $site_settings_from_db = [];
}

// Create service instances through ServiceProvider for backward compatibility
try {
    $database_handler = $serviceProvider->getDatabase();
} catch (ReflectionException $e) {
    throw new RuntimeException('Database service not found in the container: ' . $e->getMessage());
}
try {
    $flashMessageService = $serviceProvider->getFlashMessage();
} catch (ReflectionException $e) {
    throw new RuntimeException('FlashMessage service not found in the container: ' . $e->getMessage());
}
try {
    $logger = $serviceProvider->getLogger();
} catch (ReflectionException $e) {
    throw new RuntimeException('Logger service not found in the container: ' . $e->getMessage());
}
try {
    $auth = $serviceProvider->getAuth();
} catch (ReflectionException $e) {
    throw new RuntimeException('Auth service not found in the container: ' . $e->getMessage());
}
try {
    $tokenManager = $serviceProvider->getTokenManager();
} catch (ReflectionException $e) {
    throw new RuntimeException('TokenManager service not found in the container: ' . $e->getMessage());
}
try {
    $mailerService = $serviceProvider->getMailer();
} catch (ReflectionException $e) {
    throw new RuntimeException('Mailer service not found in the container: ' . $e->getMessage());
}
try {
    $cache = $serviceProvider->getCache();
} catch (ReflectionException $e) {
    throw new RuntimeException('Cache service not found in the container: ' . $e->getMessage());
}

// Make serviceProvider available globally for page files
$GLOBALS['serviceProvider'] = $serviceProvider;

// Clean expired data periodically (5% chance)
if (rand(1, 100) <= 5) {
    $tokenManager->cleanExpiredTokens();
    $cache->cleanExpired();
    $flashMessageService->cleanOldMessages();
}

// Automatic login via "Remember me" token
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    $rememberToken = $_COOKIE['remember_token'];

    // Validate a "Remember me" token
    $tokenData = $tokenManager->validateToken(
        $rememberToken,
        TokenManager::TYPE_REMEMBER_ME
    );

    if ($tokenData !== false) {
        // Get user data
        $user = User::findById($database_handler, true['user_id']);

        if ($user && $user['is_active']) {
            // Check IP and User-Agent for additional security
            $storedData = $tokenData['data'] ?? [];
            $currentIp = $_SERVER['REMOTE_ADDR'] ?? '';
            $currentUserAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

            $ipMatches = !isset($storedData['ip']) || $storedData['ip'] === $currentIp;
            $userAgentMatches = !isset($storedData['user_agent']) || $storedData['user_agent'] === $currentUserAgent;

            if ($ipMatches && $userAgentMatches) {
                // Regenerate session ID for security
                session_regenerate_id(true);

                // Automatically log in to the system
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['auto_login'] = true; // Mark that this is an automatic login

                $logger->info('User auto-logged in via remember token', [
                    'user_id' => $user['id'],
                    'username' => $user['username']
                ]);
            } else {
                // IP or User-Agent do not match - remove the token for security
                $tokenManager->revokeToken($rememberToken);
                setcookie('remember_token', '', [
                    'expires' => time() - 3600,
                    'path' => '/',
                    'httponly' => true
                ]);

                $logger->warning('Remember token security mismatch', [
                    'user_id' => $tokenData['user_id'],
                    'ip_match' => $ipMatches,
                    'user_agent_match' => $userAgentMatches
                ]);
            }
        } else {
            // User isn't found or inactive - remove the token
            $tokenManager->revokeToken($rememberToken);
            setcookie('remember_token', '', [
                'expires' => time() - 3600,
                'path' => '/',
                'httponly' => true
            ]);
        }
    } else {
        // Token is invalid - removing cookie
        setcookie('remember_token', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'httponly' => true
        ]);
    }
}

$logger = Logger::getInstance();

// Role-based access control - check page access permissions
if (!$is_ajax && isset($_GET['page'])) {
    try {
        $currentPage = $_GET['page'];
        $userRole = $_SESSION['user_role'] ?? 'guest';

        // Skip access control for public pages
        $publicPages = ['home', 'about', 'contact', 'projects', 'services', 'team', 'login', 'register', 'news', 'verify_email', 'reset_password', 'forgot_password'];

        // Check access for non-public pages (skip API endpoints as they have their own validation)
        if (!str_starts_with($currentPage, 'api_') && !in_array($currentPage, $publicPages)) {
            // Check access for non-public pages using NavigationHelper
            if (!NavigationHelper::canAccessPage($currentPage, $userRole)) {
                $_SESSION['error_message'] = 'У вас нет прав доступа к этой странице.';

                // Redirect based on a user role
                switch ($userRole) {
                    case 'admin':
                    case 'employee':
                    case 'client':
                        header('Location: /index.php?page=dashboard');
                        break;
                    case 'guest':
                    default:
                        header('Location: /index.php?page=home');
                        break;
                }
                exit;
            }
        }
    } catch (Exception $e) {
        $logger->error('Role access control error: ' . $e->getMessage());
        // Fallback to the home page on error
        header('Location: /index.php?page=home');
        exit;
    }
}

// Log bootstrap completion only for regular requests, not for AJAX
if (!$is_ajax) {
    $logger->debug('Bootstrap completed successfully', [
        'php_version' => PHP_VERSION,
        'memory_usage' => memory_get_usage(true),
        'session_id' => session_id()
    ]);
}

// Error handling configuration
if (($_ENV['APP_ENV'] ?? 'production') === 'development') {
    // In development mode, show important errors, exclude deprecated
    error_reporting(E_ERROR | E_WARNING | E_PARSE | E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_COMPILE_WARNING | E_USER_ERROR | E_USER_WARNING | E_RECOVERABLE_ERROR | E_NOTICE);
    ini_set('display_errors', '1');
} else {
    // In production, only critical errors are shown
    error_reporting(E_ERROR | E_WARNING | E_PARSE | E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_COMPILE_WARNING | E_USER_ERROR | E_USER_WARNING | E_RECOVERABLE_ERROR);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

// Set default timezone
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'UTC');

// Security headers
if (!headers_sent()) {
    header('X-Content-Type-Options: nos niff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');

    if (($_ENV['FORCE_HTTPS'] ?? false) && !isset($_SERVER['HTTPS'])) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}
