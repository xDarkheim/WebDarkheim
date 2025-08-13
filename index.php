<?php

/**
 * Main entry point for the application
 * This file includes the webengine.php and handles basic checks
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

/**
 * Check if the request is made over HTTPS
 *
 * @return bool
 */

function isHttps(): bool
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
        return true;
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') {
        return true;
    }
    if (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) {
        return true;
    }
    return false;
}

/**
 * Check if the environment is local
 *
 * @return bool
 */

function isLocalEnvironment(): bool
{
    $host = $_SERVER['HTTP_HOST'] ?? '';

    return in_array($host, ['localhost', '127.0.0.1', '::1'])
           || str_contains($host, '.local')
           || str_contains($host, '.test')
           || str_contains($host, '.dev');
}

if (!isHttps() && !isLocalEnvironment()) {
    if (!headers_sent()) {
        $redirectURL = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

        if (!str_contains($_SERVER['REQUEST_URI'] ?? '', 'https://')) {
            header("Location: $redirectURL", true, 301);
            exit();
        }
    }
}

$webengine_path = __DIR__ . '/public/webengine.php';

if (!file_exists($webengine_path)) {
    die('Error: webengine.php not found in public directory');
}

if (!is_readable($webengine_path)) {
    die('Error: webengine.php is not readable');
}

try {
    require_once $webengine_path;
} catch (Exception $e) {
    error_log('Error loading webengine.php: ' . $e->getMessage());
    die('Application error occurred. Please try again later.');
} catch (Error $e) {
    error_log('Fatal error loading webengine.php: ' . $e->getMessage());
    die('Application error occurred. Please try again later.');
}
