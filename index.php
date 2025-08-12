<?php
/**
 * Main entry point for the application
 * This file includes the webengine.php and handles basic checks
 */

// УЛУЧШЕННЫЙ РЕДИРЕКТ НА HTTPS - исправляет ERR_TOO_MANY_REDIRECTS
function isHttps() {
    // Проверяем различные способы определения HTTPS
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }

    // Проверяем заголовки от прокси/балансировщика
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
        return true;
    }

    if (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') {
        return true;
    }

    // Проверяем стандартный порт HTTPS
    if (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) {
        return true;
    }

    return false;
}

function isLocalEnvironment() {
    $host = $_SERVER['HTTP_HOST'] ?? '';

    return in_array($host, ['localhost', '127.0.0.1', '::1'])
           || strpos($host, '.local') !== false
           || strpos($host, '.test') !== false
           || strpos($host, '.dev') !== false;
}

// Выполняем редирект только если нужно и это не приведет к петле
if (!isHttps() && !isLocalEnvironment()) {
    // Дополнительная проверка: не делаем редирект если уже есть заголовки
    if (!headers_sent()) {
        $redirectURL = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

        // Проверяем, что URL не содержит уже https (избегаем двойного редиректа)
        if (strpos($_SERVER['REQUEST_URI'] ?? '', 'https://') === false) {
            header("Location: $redirectURL", true, 301);
            exit();
        }
    }
}

// Check if the webengine.php file exists
$webengine_path = __DIR__ . '/public/webengine.php';

if (!file_exists($webengine_path)) {
    die('Error: webengine.php not found in public directory');
}

// Check if the file is readable
if (!is_readable($webengine_path)) {
    die('Error: webengine.php is not readable');
}

// Include the main application engine
try {
    require_once $webengine_path;
} catch (Exception $e) {
    error_log('Error loading webengine.php: ' . $e->getMessage());
    die('Application error occurred. Please try again later.');
} catch (Error $e) {
    error_log('Fatal error loading webengine.php: ' . $e->getMessage());
    die('Application error occurred. Please try again later.');
}
