<?php

/**
 * Router implementation following Single Responsibility Principle
 * Handles URL routing with middleware support and security features
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Infrastructure\Lib;

use App\Domain\Interfaces\FlashMessageInterface;
use App\Domain\Interfaces\LoggerInterface;
use Throwable;


class Router
{
    private array $routes = [];
    private array $middleware = [];
    private array $redirects = [];
    private string $pageDirectory;
    private LoggerInterface $logger;

    public function __construct(string $pageDirectory, array $routesConfig = [])
    {
        $this->pageDirectory = rtrim($pageDirectory, '/\\');
        $this->logger = Logger::getInstance();
        $this->loadRoutes($routesConfig);
    }

    /**
     * Dispatch route based on the page key
     */
    public function dispatch(string $pageKey): void
    {
        $pageKey = $this->sanitizePageKey($pageKey);

        // Check for redirects first
        if (isset($this->redirects[$pageKey])) {
            $this->handleRedirect($pageKey, $this->redirects[$pageKey]);
            return;
        }

        // Check if the route exists in configuration
        if (isset($this->routes[$pageKey])) {
            $routeConfig = $this->routes[$pageKey];

            // Check if the route itself has a redirect configuration
            if (isset($routeConfig['redirect'])) {
                $this->handleRedirect($pageKey, $routeConfig['redirect']);
                return;
            }

            $this->handleConfiguredRoute($pageKey, $routeConfig);
        } else {
            $this->handleFileBasedRoute($pageKey);
        }
    }

    /**
     * Add middleware to a specific route
     */
    public function addMiddleware(string $pageKey, string $middlewareClass): void
    {
        if (!isset($this->middleware[$pageKey])) {
            $this->middleware[$pageKey] = [];
        }
        $this->middleware[$pageKey][] = $middlewareClass;
    }

    /**
     * Load routes from configuration
     */
    private function loadRoutes(array $routesConfig): void
    {
        $this->routes = $routesConfig['routes'] ?? [];

        // Load middleware configuration
        if (isset($routesConfig['middleware'])) {
            foreach ($routesConfig['middleware'] as $pageKey => $middlewareList) {
                foreach ($middlewareList as $middleware) {
                    $this->addMiddleware($pageKey, $middleware);
                }
            }
        }

        // Load redirects configuration
        if (isset($routesConfig['redirects'])) {
            $this->redirects = $routesConfig['redirects'];
        }
    }

    /**
     * Handle a configured route (with controller or file)
     */
    private function handleConfiguredRoute(string $pageKey, array $routeConfig): void
    {
        try {
            $this->logger->debug('Handling configured route', [
                'page_key' => $pageKey,
                'route_config' => $routeConfig
            ]);

            // Run middleware
            if (!$this->runMiddleware($pageKey)) {
                $this->logger->debug('Middleware blocked request', ['page_key' => $pageKey]);
                return;
            }

            // Check if a controller is specified
            if (isset($routeConfig['controller'])) {
                $this->logger->debug('Processing controller route', [
                    'page_key' => $pageKey,
                    'controller' => $routeConfig['controller']
                ]);
                $this->handleControllerRoute($routeConfig);
            } elseif (isset($routeConfig['file'])) {
                $this->logger->debug('Processing file route', [
                    'page_key' => $pageKey,
                    'file' => $routeConfig['file']
                ]);
                // ИСПРАВЛЕНО: Убираем специальную обработку API - используем обычную обработку файлов
                // Это позволит восстановленному API работать с полной системной инициализацией
                $this->handleConfiguredFileRoute($routeConfig);
            } else {
                $this->logger->debug('Falling back to file-based route', ['page_key' => $pageKey]);
                // Fall back to file-based routing with original logic
                $this->handleFileBasedRoute($pageKey);
            }

        } catch (Throwable $e) {
            $this->logger->error('Route handling error', [
                'page_key' => $pageKey,
                'route_config' => $routeConfig,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
                'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            $this->handleError('Internal server error occurred.');
        }
    }

    /**
     * Handle-controller-based route
     */
    private function handleControllerRoute(array $routeConfig): void
    {
        $controllerClass = $routeConfig['controller'];
        $method = $routeConfig['method'] ?? 'index';

        $this->logger->debug('Attempting to handle controller route', [
            'controller' => $controllerClass,
            'method' => $method
        ]);

        if (!class_exists($controllerClass)) {
            $this->logger->error('Controller not found', ['controller' => $controllerClass]);
            $this->handleError('Page not found.', 404);
            return;
        }

        // Use DI container to create a controller if available
        global $container;

        if (isset($container)) {
            try {
                $this->logger->debug('Creating controller via DI container', ['controller' => $controllerClass]);
                $controller = $container->make($controllerClass);
                $this->logger->debug('Controller created successfully via DI container');
            } catch (Throwable $containerError) {
                $this->logger->warning('DI container failed to create controller, using fallback', [
                    'controller' => $controllerClass,
                    'container_error' => $containerError->getMessage(),
                    'container_trace' => $containerError->getTraceAsString()
                ]);

                // Fallback to manual creation
                try {
                    $controller = new $controllerClass();
                    $this->logger->debug('Controller created successfully via fallback instantiation');
                } catch (Throwable $fallbackError) {
                    $this->logger->error('Failed to create controller even with fallback', [
                        'controller' => $controllerClass,
                        'fallback_error' => $fallbackError->getMessage(),
                        'fallback_trace' => $fallbackError->getTraceAsString()
                    ]);
                    $this->handleError('Internal server error occurred.');
                    return;
                }
            }
        } else {
            try {
                $this->logger->debug('Creating controller without DI container', ['controller' => $controllerClass]);
                $controller = new $controllerClass();
                $this->logger->debug('Controller created successfully without DI container');
            } catch (Throwable $instantiationError) {
                $this->logger->error('Failed to create controller', [
                    'controller' => $controllerClass,
                    'instantiation_error' => $instantiationError->getMessage(),
                    'instantiation_trace' => $instantiationError->getTraceAsString()
                ]);
                $this->handleError('Internal server error occurred.');
                return;
            }
        }

        if (!method_exists($controller, $method)) {
            $this->logger->error('Controller method not found', [
                'controller' => $controllerClass,
                'method' => $method
            ]);
            $this->handleError('Page not found.', 404);
            return;
        }

        // Execute controller method
        try {
            $this->logger->debug('Executing controller method', [
                'controller' => $controllerClass,
                'method' => $method
            ]);
            $controller->$method();
            $this->logger->debug('Controller method executed successfully');
        } catch (Throwable $executionError) {
            $this->logger->error('Controller method execution failed', [
                'controller' => $controllerClass,
                'method' => $method,
                'execution_error' => $executionError->getMessage(),
                'execution_trace' => $executionError->getTraceAsString()
            ]);
            $this->handleError('Internal server error occurred.');
        }
    }

    /**
     * Handle file-based route from configuration
     */
    private function handleConfiguredFileRoute(array $routeConfig): void
    {
        $filePath = $this->pageDirectory . DIRECTORY_SEPARATOR . $routeConfig['file'];

        if (file_exists($filePath) && is_readable($filePath)) {
            // Check AJAX requests to reduce logging
            $is_ajax = (
                (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                 strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
                (isset($_GET['action']) && $_GET['action'] === 'ajax') ||
                (str_contains($_SERVER['REQUEST_URI'] ?? '', 'action=ajax')) ||
                (str_contains($_SERVER['REQUEST_URI'] ?? '', 'system_monitor') &&
                    str_contains($_SERVER['REQUEST_URI'] ?? '', 'type='))
            );

            // Log only for regular requests, not AJAX
            if (!$is_ajax) {
                $this->logger->debug('Loading configured page file', [
                    'file_path' => $filePath,
                    'config' => $routeConfig
                ]);
            }

            try {
                // Set page title if specified
                if (isset($routeConfig['title'])) {
                    global $page_title;
                    $page_title = $routeConfig['title'];
                }

                // Initialize global services for legacy pages that expect them
                $this->initializeGlobalServices();

                // Safe output buffering to prevent race conditions
                if (ob_get_level() > 0) {
                    ob_end_clean(); // Clearly previously buffer if exists
                }

                ob_start();

                // Add PHP error handler for this page in debug mode
                if (isDebugMode()) {
                    set_error_handler(function($severity, $message, $file, $line) {
                        // Define error types
                        $errorTypes = [
                            E_ERROR => 'Fatal Error',
                            E_WARNING => 'Warning',
                            E_PARSE => 'Parse Error',
                            E_NOTICE => 'Notice',
                            E_CORE_ERROR => 'Core Error',
                            E_CORE_WARNING => 'Core Warning',
                            E_COMPILE_ERROR => 'Compile Error',
                            E_COMPILE_WARNING => 'Compile Warning',
                            E_USER_ERROR => 'User Error',
                            E_USER_WARNING => 'User Warning',
                            E_USER_NOTICE => 'User Notice',
                            E_RECOVERABLE_ERROR => 'Recoverable Error',
                            E_DEPRECATED => 'Deprecated',
                            E_USER_DEPRECATED => 'User Deprecated'
                        ];

                        $errorType = $errorTypes[$severity] ?? 'Unknown Error';

                        // Clear buffer
                        if (ob_get_level() > 0) {
                            ob_end_clean();
                        }

                        // Save error data to session
                        if (session_status() !== PHP_SESSION_ACTIVE) {
                            session_start();
                        }

                        $_SESSION['error_message'] = "PHP $errorType: $message";
                        $_SESSION['error_trace'] = "File: $file\nLine: $line\nType: $errorType (Code: $severity)\nSeverity Level: " . $severity;
                        $_SESSION['error_context'] = [
                            'type' => 'PHP Error',
                            'severity' => $severity,
                            'error_type' => $errorType,
                            'file' => $file,
                            'line' => $line,
                            'timestamp' => date('Y-m-d H:i:s'),
                            'url' => $_SERVER['REQUEST_URI'] ?? 'Unknown',
                            'method' => $_SERVER['REQUEST_METHOD'] ?? 'Unknown',
                            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
                            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
                        ];

                        // Log error
                        $this->logger->error("PHP $errorType: $message in $file on line $line");

                        // Redirect to the error page for all error types in debug mode
                        if (!headers_sent()) {
                            header('Location: /index.php?page=system/error');
                            exit;
                        }

                        return true; // Error handled
                    });
                }

                include $filePath;
                $content = ob_get_clean();

                // Store content globally for a template system
                global $page_content;
                $page_content = $content;

                return;

            } catch (Throwable $e) {
                // Safe buffer cleanup on error
                while (ob_get_level() > 0) {
                    ob_end_clean();
                }

                $this->logger->error('Configured page file execution error', [
                    'file_path' => $filePath,
                    'error' => $e->getMessage()
                ]);
                $this->handleError('Page loading failed.');
                return;
            }
        }

        // File not found
        $this->logger->warning('Configured page file not found', [
            'file_path' => $filePath,
            'config' => $routeConfig
        ]);
        $this->handleError('Page not found.', 404);
    }


    /**
     * Handle file-based route (legacy support)
     */
    private function handleFileBasedRoute(string $pageKey): void
    {
        // Run middleware
        if (!$this->runMiddleware($pageKey)) {
            return;
        }

        $possiblePaths = $this->getPossibleFilePaths($pageKey);

        foreach ($possiblePaths as $filePath) {
            if (file_exists($filePath) && is_readable($filePath)) {
                $this->logger->debug('Loading page file', [
                    'page_key' => $pageKey,
                    'file_path' => $filePath
                ]);

                try {
                    // Initialize global services for legacy pages that ожидают их
                    $this->initializeGlobalServices();

                    // Safe output buffering to prevent race conditions
                    if (ob_get_level() > 0) {
                        ob_end_clean(); // Clearly previously buffer if exists
                    }

                    ob_start();
                    
                    // Add PHP error handler for this page in debug mode
                    if (isDebugMode()) {
                        set_error_handler(function($severity, $message, $file, $line) {
                            // Define error types
                            $errorTypes = [
                                E_ERROR => 'Fatal Error',
                                E_WARNING => 'Warning',
                                E_PARSE => 'Parse Error',
                                E_NOTICE => 'Notice',
                                E_CORE_ERROR => 'Core Error',
                                E_CORE_WARNING => 'Core Warning',
                                E_COMPILE_ERROR => 'Compile Error',
                                E_COMPILE_WARNING => 'Compile Warning',
                                E_USER_ERROR => 'User Error',
                                E_USER_WARNING => 'User Warning',
                                E_USER_NOTICE => 'User Notice',
                                E_STRICT => 'Strict Standards',
                                E_RECOVERABLE_ERROR => 'Recoverable Error',
                                E_DEPRECATED => 'Deprecated',
                                E_USER_DEPRECATED => 'User Deprecated'
                            ];

                            $errorType = $errorTypes[$severity] ?? 'Unknown Error';

                            // Clear buffer
                            if (ob_get_level() > 0) {
                                ob_end_clean();
                            }

                            // Save error data to session
                            if (session_status() !== PHP_SESSION_ACTIVE) {
                                session_start();
                            }

                            $_SESSION['error_message'] = "PHP $errorType: $message";
                            $_SESSION['error_trace'] = "File: $file\nLine: $line\nType: $errorType (Code: $severity)\nSeverity Level: " . $severity;
                            $_SESSION['error_context'] = [
                                'type' => 'PHP Error',
                                'severity' => $severity,
                                'error_type' => $errorType,
                                'file' => $file,
                                'line' => $line,
                                'timestamp' => date('Y-m-d H:i:s'),
                                'url' => $_SERVER['REQUEST_URI'] ?? 'Unknown',
                                'method' => $_SERVER['REQUEST_METHOD'] ?? 'Unknown',
                                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
                                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
                            ];

                            // Log error
                            $this->logger->error("PHP $errorType: $message in $file on line $line");

                            // Redirect to the error page for all error types in debug mode
                            if (!headers_sent()) {
                                header('Location: /index.php?page=system/error');
                                exit;
                            }

                            return true; // Error handled
                        });
                    }

                    include $filePath;
                    $content = ob_get_clean();

                    // Store content globally for a template system
                    global $page_content;
                    $page_content = $content;

                    return;

                } catch (Throwable $e) {
                    // Safe buffer cleanup on error
                    while (ob_get_level() > 0) {
                        ob_end_clean();
                    }

                    $this->logger->error('Page file execution error', [
                        'page_key' => $pageKey,
                        'file_path' => $filePath,
                        'error' => $e->getMessage()
                    ]);
                    $this->handleError('Page loading failed.');
                    return;
                }
            }
        }

        // No valid file found
        $this->logger->warning('Page not found', [
            'page_key' => $pageKey,
            'attempted_paths' => $possiblePaths
        ]);
        $this->handleError('Page not found.', 404);
    }

    /**
     * Run middleware for the route
     */
    private function runMiddleware(string $pageKey): bool
    {
        $middlewareList = array_merge(
            $this->middleware['*'] ?? [],
            $this->middleware[$pageKey] ?? []
        );

        foreach ($middlewareList as $middlewareClass) {
            if (!class_exists($middlewareClass)) {
                $this->logger->error('Middleware not found', ['middleware' => $middlewareClass]);
                continue;
            }

            try {
                // Use DI container if available
                global $container;
                if (isset($container)) {
                    try {
                        $middleware = $container->make($middlewareClass);
                    } catch (Throwable $containerError) {
                        $this->logger->warning('DI container failed to create middleware, using fallback', [
                            'middleware' => $middlewareClass,
                            'container_error' => $containerError->getMessage()
                        ]);
                        $middleware = new $middlewareClass();
                    }
                } else {
                    $middleware = new $middlewareClass();
                }

                // Execute middleware
                if (method_exists($middleware, 'handle')) {
                    $result = $middleware->handle();
                    if ($result === false) {
                        $this->logger->debug('Middleware blocked request', [
                            'middleware' => $middlewareClass,
                            'page_key' => $pageKey
                        ]);
                        return false;
                    }
                }

            } catch (Throwable $e) {
                $this->logger->error('Middleware execution error', [
                    'middleware' => $middlewareClass,
                    'error' => $e->getMessage()
                ]);
                $this->handleError('Access denied.');
                return false;
            }
        }

        return true;
    }

    /**
     * Get possible file paths for a page key
     */
    private function getPossibleFilePaths(string $pageKey): array
    {
        $paths = [];

        // Handle account pages - map an account_* to user/* structure (correct mapping)
        if (str_starts_with($pageKey, 'account_')) {
            $userPage = substr($pageKey, 8); // Remove the 'account_' prefix
            $paths[] = $this->pageDirectory . DIRECTORY_SEPARATOR . 'user' . DIRECTORY_SEPARATOR . $userPage . '.php';
        }

        // Direct file mapping
        $paths[] = $this->pageDirectory . DIRECTORY_SEPARATOR . $pageKey . '.php';

        // User subdirectory for user pages (correct mapping)
        if (str_starts_with($pageKey, 'user/')) {
            $paths[] = $this->pageDirectory . DIRECTORY_SEPARATOR . $pageKey . '.php';
        }

        // Account subdirectory for backward compatibility
        if (str_starts_with($pageKey, 'account/')) {
            $paths[] = $this->pageDirectory . DIRECTORY_SEPARATOR . 'user' . DIRECTORY_SEPARATOR . substr($pageKey, 8) . '.php';
        }

        // Admin subdirectory
        if (str_starts_with($pageKey, 'admin/')) {
            $paths[] = $this->pageDirectory . DIRECTORY_SEPARATOR . $pageKey . '.php';
        }

        return $paths;
    }

    /**
     * Handle routing errors
     */
    private function handleError(string $message, int $statusCode = 500): void
    {
        global $page_content, $logger;

        // Set HTTP status code
        http_response_code($statusCode);

        // Log error
        $this->logger->warning('Router error handled', [
            'message' => $message,
            'status_code' => $statusCode,
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);

        // Safe session handling to prevent race conditions
        try {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            // Check that the session is active and writable
            if (session_status() === PHP_SESSION_ACTIVE) {
                // Save error data to a session for error.php
                $_SESSION['error_message'] = $message;
                $_SESSION['error_trace'] = "Router Error:\n" . $message . "\n\nRequest: " . ($_SERVER['REQUEST_URI'] ?? 'Unknown') . "\nMethod: " . ($_SERVER['REQUEST_METHOD'] ?? 'Unknown');
                $_SESSION['error_context'] = [
                    'status_code' => $statusCode,
                    'timestamp' => date('Y-m-d H:i:s'),
                    'url' => $_SERVER['REQUEST_URI'] ?? 'Unknown',
                    'method' => $_SERVER['REQUEST_METHOD'] ?? 'Unknown',
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
                ];

                // Use flash message service to display errors
                global $container;
                if (isset($container)) {
                    $flashService = $container->make(FlashMessageInterface::class);
                    $flashService->addError($message);
                }
            }
        } catch (Throwable $sessionError) {
            // If session problem, just log it
            $this->logger->warning('Session error during error handling', [
                'session_error' => $sessionError->getMessage(),
                'original_message' => $message
            ]);
        }

        // Try to load the appropriate error page from the new structure
        $errorPagePath = match($statusCode) {
            404 => $this->pageDirectory . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . '404.php',
            default => $this->pageDirectory . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'error.php'
        };

        if (file_exists($errorPagePath)) {
            try {
                ob_start();
                include $errorPagePath;
                $page_content = ob_get_clean();
            } catch (Throwable $e) {
                ob_end_clean();
                $this->logger->error('Error page loading failed', [
                    'error_page' => $errorPagePath,
                    'error' => $e->getMessage()
                ]);
                // Fallback to basic HTML
                $page_content = '<div class="alert alert-danger">' . htmlspecialchars($message) . '</div>';
            }
        } else {
            $this->logger->warning('Error page not found', ['error_page' => $errorPagePath]);
            // Fallback error content
            $page_content = '<div class="alert alert-danger">' . htmlspecialchars($message) . '</div>';
        }
    }

    /**
     * Sanitize a page key for security
     */
    private function sanitizePageKey(string $pageKey): string
    {
        // Remove dangerous characters
        $pageKey = preg_replace('/[^a-zA-Z0-9\/_-]/', '', $pageKey);

        // Prevent directory traversal
        $pageKey = str_replace(['../', '..\\', './'], '', $pageKey);

        // Remove leading/trailing slashes
        $pageKey = trim($pageKey, '/\\');

        // Default to home if empty
        if (empty($pageKey)) {
            $pageKey = 'home';
        }

        return $pageKey;
    }

    /**
     * Handle redirect with support for different types and status codes
     */
    private function handleRedirect(string $fromPage, $redirectConfig): void
    {
        // Handle simple string redirect (backward compatibility)
        if (is_string($redirectConfig)) {
            $target = $redirectConfig;
            $statusCode = 302; // Temporary redirect by default
            $external = false;
        } else {
            // Handle array configuration
            $target = $redirectConfig['target'] ?? $redirectConfig['to'] ?? '';
            $statusCode = $redirectConfig['status'] ?? $redirectConfig['code'] ?? 302;
            $external = $redirectConfig['external'] ?? false;
        }

        if (empty($target)) {
            $this->logger->error('Invalid redirect configuration', [
                'from' => $fromPage,
                'config' => $redirectConfig
            ]);
            $this->handleError('Invalid redirect configuration.');
            return;
        }

        $this->logger->debug('Processing redirect', [
            'from' => $fromPage,
            'to' => $target,
            'status' => $statusCode,
            'external' => $external
        ]);

        // Set the appropriate status code
        http_response_code($statusCode);

        // Generate redirect URL
        if ($external || filter_var($target, FILTER_VALIDATE_URL)) {
            // External URL or full URL
            $redirectUrl = $target;
        } else {
            // Internal page redirect
            $redirectUrl = '/index.php?page=' . urlencode($target);
        }

        // Perform redirect
        header('Location: ' . $redirectUrl);
        exit;
    }

    /**
     * Initialize global services for legacy pages
     */
    private function initializeGlobalServices(): void
    {
        global $container;

        if (!isset($container)) {
            return;
        }

        try {
            // Initialize ServiceProvider globally
            global $serviceProvider;
            if (!isset($serviceProvider)) {
                $serviceProvider = \App\Application\Core\ServiceProvider::getInstance($container);
            }

            // Initialize FlashMessageService globally
            global $flashMessageService;
            if (!isset($flashMessageService)) {
                $flashMessageService = $serviceProvider->getFlashMessage();
            }

            // Initialize database handler globally
            global $database_handler;
            if (!isset($database_handler)) {
                $database_handler = $serviceProvider->getDatabase();
            }

            // Initialize logger globally
            global $logger;
            if (!isset($logger)) {
                $logger = $serviceProvider->getLogger();
            }

        } catch (Throwable $e) {
            $this->logger->error('Failed to initialize global services', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
