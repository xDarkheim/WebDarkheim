<?php

/**
 * Error Handler
 * Centralized error handling
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Application\Core;

use App\Domain\Interfaces\LoggerInterface;
use JetBrains\PhpStorm\NoReturn;
use Throwable;

class ErrorHandler
{
    private LoggerInterface $logger;
    private bool $debugMode;

    public function __construct(LoggerInterface $logger, bool $debugMode = false)
    {
        $this->logger = $logger;
        $this->debugMode = $debugMode;
    }

    public function register(): void
    {
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);
    }

    public function handleError(int $severity, string $message, string $file, int $line): bool
    {
        if (!(error_reporting() & $severity)) {
            return false;
        }

        $this->logger->error("PHP Error: $message", [
            'severity' => $severity,
            'file' => $file,
            'line' => $line,
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);

        return true;
    }

    #[NoReturn]
    public function handleException(Throwable $exception): void
    {
        $this->logger->critical('Uncaught Exception', [
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'class' => get_class($exception),
            'trace' => $exception->getTraceAsString(),
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);

        if ($this->debugMode) {
            $this->renderDebugPage($exception);
        } else {
            $this->renderErrorPage();
        }
    }

    public function handleShutdown(): void
    {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
            $this->logger->critical('Fatal Error', [
                'message' => $error['message'],
                'file' => $error['file'],
                'line' => $error['line'],
                'type' => $error['type']
            ]);

            if (!$this->debugMode) {
                $this->renderErrorPage();
            }
        }
    }

    #[NoReturn]
    private function renderDebugPage(Throwable $exception): void
    {
        if (!headers_sent()) {
            header('HTTP/1.1 500 Internal Server Error');
            header('Content-Type: text/html; charset=UTF-8');
        }

        echo "<!DOCTYPE html>
<html lang=\"en\">
<head>
    <title>Application Error</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; }
        .trace { background: #f1f1f1; padding: 10px; margin-top: 10px; overflow: auto; }
        pre { white-space: pre-wrap; }
    </style>
</head>
<body>
    <div class='error'>
        <h2>Error: " . htmlspecialchars($exception->getMessage()) . "</h2>
        <p><strong>File:</strong> " . htmlspecialchars($exception->getFile()) . "</p>
        <p><strong>Line:</strong> " . $exception->getLine() . "</p>
        <p><strong>Type:</strong> " . htmlspecialchars(get_class($exception)) . "</p>
    </div>
    <div class='trace'>
        <h3>Stack Trace:</h3>
        <pre>" . htmlspecialchars($exception->getTraceAsString()) . "</pre>
    </div>
</body>
</html>";
        exit;
    }

    #[NoReturn]
    private function renderErrorPage(): void
    {
        if (!headers_sent()) {
            header('HTTP/1.1 500 Internal Server Error');
        }

        $errorPagePath = ROOT_PATH . DS . 'page' . DS . 'system' . DS . 'error.php';
        if (file_exists($errorPagePath)) {
            include $errorPagePath;
        } else {
            echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <title>Internal Server Error</title>
</head>
<body>
    <h1>Internal Server Error</h1>
    <p>Something went wrong. Please try again later.</p>
</body>
</html>";
        }
        exit;
    }
}
