<?php

/**
 * Middleware for handling debug mode
 * Configures error display and additional debug information
 * Uses LoggerInterface for logging
 * Uses SessionManager for session management
 * Uses ConfigurationManager for dynamic settings loading
 * Uses ServiceProvider for dependency injection
 * Provides static methods for quick validation and token generation
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Application\Middleware;

use App\Application\Services\SiteSettingsService;
use App\Application\Core\SessionManager;
use App\Domain\Interfaces\LoggerInterface;
use Throwable;


class DebugMiddleware implements MiddlewareInterface
{
    private float $startTime;
    private int $startMemory;

    public function __construct(
        private readonly SiteSettingsService $siteSettings,
        private readonly LoggerInterface $logger,
        private readonly SessionManager $sessionManager
    ) {
        $this->startTime = microtime(true);
        $this->startMemory = memory_get_usage();
    }

    public function handle(): bool
    {
        // Check if debug mode is enabled
        $debugMode = $this->siteSettings->get('debug_mode', false);

        if ($debugMode) {
            $this->enableDebugMode();
            $this->logger->debug('Debug mode activated');

            // Register handler to display debug info at the end
            register_shutdown_function([$this, 'displayDebugInfo']);
        } else {
            $this->disableDebugMode();
        }

        return true;
    }

    /**
     * Enables debug mode
     */
    private function enableDebugMode(): void
    {
        // Enable display of all errors
        error_reporting(E_ALL);
        ini_set('display_errors', '1');
        ini_set('display_startup_errors', '1');

        // Enable detailed error output
        ini_set('html_errors', '1');

        // Set error handler for logging
        set_error_handler([$this, 'debugErrorHandler']);
        set_exception_handler([$this, 'debugExceptionHandler']);
    }

    /**
     * Disables debug mode
     */
    private function disableDebugMode(): void
    {
        // Disable error display in production
        // Completely exclude E_STRICT and E_DEPRECATED for all PHP versions
        $errorReporting = E_ERROR | E_WARNING | E_PARSE | E_CORE_ERROR | E_CORE_WARNING
            | E_COMPILE_ERROR | E_COMPILE_WARNING | E_USER_ERROR | E_USER_WARNING | E_RECOVERABLE_ERROR;

        error_reporting($errorReporting);
        ini_set('display_errors', '0');
        ini_set('display_startup_errors', '0');
        ini_set('log_errors', '1');
    }

    /**
     * Error handler for debug mode
     */
    public function debugErrorHandler(int $severity, string $message, string $file, int $line): bool
    {
        // Completely suppress all E_STRICT and deprecated constant messages
        if (
            str_contains($message, 'E_STRICT') ||
            str_contains($message, 'Constant E_STRICT') ||
            ($severity === 8192 && str_contains($message, 'deprecated'))
        ) {
            return true; // Suppress these warnings
        }

        $errorTypes = [
            E_ERROR => 'ERROR',
            E_WARNING => 'WARNING',
            E_PARSE => 'PARSE ERROR',
            E_NOTICE => 'NOTICE',
            E_CORE_ERROR => 'CORE ERROR',
            E_CORE_WARNING => 'CORE WARNING',
            E_COMPILE_ERROR => 'COMPILE ERROR',
            E_COMPILE_WARNING => 'COMPILE WARNING',
            E_USER_ERROR => 'USER ERROR',
            E_USER_WARNING => 'USER WARNING',
            E_USER_NOTICE => 'USER NOTICE',
            E_RECOVERABLE_ERROR => 'RECOVERABLE ERROR',
            E_DEPRECATED => 'DEPRECATED',
            E_USER_DEPRECATED => 'USER DEPRECATED'
        ];

        $errorType = $errorTypes[$severity] ?? 'UNKNOWN';

        $this->logger->debug("PHP $errorType: $message", [
            'file' => $file,
            'line' => $line,
            'severity' => $severity
        ]);

        // Return false to continue PHP error processing
        return false;
    }

    /**
     * Exception handler for debug mode
     */
    public function debugExceptionHandler(Throwable $exception): void
    {
        try {
            // Safe session handling via SessionManager
            $this->sessionManager->start();

            // Save error details in session for display
            $this->sessionManager->set('error_message', $exception->getMessage());
            $this->sessionManager->set('error_trace', $exception->getTraceAsString());
            $this->sessionManager->set('error_context', [
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'code' => $exception->getCode(),
                'type' => get_class($exception),
                'timestamp' => date('Y-m-d H:i:s'),
                'url' => $_SERVER['REQUEST_URI'] ?? 'Unknown',
                'method' => $_SERVER['REQUEST_METHOD'] ?? 'Unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
            ]);
        } catch (Throwable $sessionException) {
            // If an error occurs with a session, log it
            $this->logger->error('Session error while handling exception in debug mode', [
                'session_error' => $sessionException->getMessage(),
                'original_exception' => get_class($exception),
                'original_message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine()
            ]);

            // Create fallback data without using session
            $this->handleExceptionWithoutSession($exception);
            return;
        }

        $this->logger->error('Unhandled exception in debug mode', [
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);

        // Redirect to our nice error page
        $this->redirectToErrorPage();
    }

    /**
     * Handle exception without using session (fallback)
     */
    private function handleExceptionWithoutSession(Throwable $exception): void
    {
        // If there are problems with the session, use alternative ways to pass error data
        if (!headers_sent()) {
            // Pass main info via GET parameters (safe for debugging)
            $errorData = urlencode(base64_encode(json_encode([
                'message' => substr($exception->getMessage(), 0, 100), // Limit length
                'type' => get_class($exception),
                'file' => basename($exception->getFile()),
                'line' => $exception->getLine(),
                'timestamp' => date('Y-m-d H:i:s')
            ])));

            header("Location: /index.php?page=system/error&debug_data=$errorData");
            exit;
        }

        // Fallback if headers already sent
        $this->displayInlineError($exception);
    }

    /**
     * Display error inline if headers already sent
     */
    private function displayInlineError(Throwable $exception): void
    {
        $errorHtml = $this->generateErrorHtml($exception);
        echo $errorHtml;
        exit;
    }

    /**
     * Generate HTML for error display
     */
    private function generateErrorHtml(Throwable $exception): string
    {
        $message = htmlspecialchars($exception->getMessage());
        $file = htmlspecialchars($exception->getFile());
        $line = $exception->getLine();
        $type = htmlspecialchars(get_class($exception));

        return <<<HTML
<div style="background: #ffebee; border: 1px solid #f44336; padding: 20px; margin: 20px; 
    border-radius: 4px; font-family: Arial, sans-serif;">
    <h3 style="color: #d32f2f; margin: 0 0 10px 0;">🐛 Debug Mode—Exception Caught</h3>
    <p><strong>Type:</strong> $type</p>
    <p><strong>Message:</strong> $message</p>
    <p><strong>File:</strong> $file</p>
    <p><strong>Line:</strong> $line</p>
    <p><strong>Note:</strong> Session unavailable, displaying inline error.</p>
</div>
HTML;
    }

    /**
     * Safe redirect to error page
     */
    private function redirectToErrorPage(): void
    {
        if (!headers_sent()) {
            header('Location: /index.php?page=system/error');
            exit;
        }

        // Fallback if headers already sent
        echo "<script>window.location.href='/index.php?page=system/error';</script>";
        exit;
    }

    /**
     * Displays debug info at the end of execution
     */
    public function displayDebugInfo(): void
    {
        if (!$this->siteSettings->get('debug_mode', false)) {
            return;
        }

        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        $peakMemory = memory_get_peak_usage();

        $executionTime = round(($endTime - $this->startTime) * 1000, 2);
        $memoryUsed = round(($endMemory - $this->startMemory) / 1024 / 1024, 2);
        $peakMemoryMB = round($peakMemory / 1024 / 1024, 2);

        $debugInfo = $this->getDebugInfoHtml($executionTime, $memoryUsed, $peakMemoryMB);

        // Output debug info only if this is an HTML response
        if (!headers_sent() && str_contains(headers_list()[0] ?? '', 'Content-Type: text/html')) {
            echo $debugInfo;
        }
    }

    /**
     * Generates HTML for debug info
     */
    private function getDebugInfoHtml(
        float $executionTime,
        float $memoryUsed,
        float $peakMemory
    ): string {
        $includedFiles = get_included_files();
        $fileCount = count($includedFiles);

        return <<<HTML
<div id="debug-panel" class="debug-panel">
    <div class="debug-panel__content">
        <div class="debug-panel__metrics">
            <span class="debug-panel__metric"><strong>🕐 Time:</strong> {$executionTime}ms</span>
            <span class="debug-panel__metric"><strong>💾 Memory:</strong> {$memoryUsed}MB (peak: {$peakMemory}MB)</span>
            <span class="debug-panel__metric"><strong>📁 Files:</strong> $fileCount</span>
            <span class="debug-panel__metric"><strong>🐛 Debug mode active</strong></span>
        </div>
        <button class="debug-panel__close" onclick="closeDebugPanel()">
            ✕
        </button>
    </div>
</div>
HTML;
    }
}
