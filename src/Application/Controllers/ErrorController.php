<?php

declare(strict_types=1);

namespace App\Application\Controllers;

use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Controller for error handling and debug information
 */
class ErrorController
{
    private LoggerInterface $logger;
    private array $errorData = [];

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Handles error and prepares data for display
     */
    public function handleError(?Throwable $exception = null): array
    {
        // If an exception is passed, process it
        if ($exception) {
            $this->processException($exception);
        } else {
            // Otherwise try to get error data from the session
            $this->loadErrorFromSession();
        }

        return [
            'view_type' => 'error',
            'page_title' => 'System Error',
            'error_data' => $this->errorData,
            'debug_mode' => isDebugMode(),
            'environment' => APP_ENV,
            'app_debug' => APP_DEBUG
        ];
    }

    /**
     * Processes exception
     */
    private function processException(Throwable $exception): void
    {
        // Ensure the session is started
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $this->errorData = [
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'code' => $exception->getCode(),
            'trace' => $exception->getTraceAsString(),
            'type' => get_class($exception),
            'timestamp' => date('Y-m-d H:i:s'),
            'context' => [
                'url' => $_SERVER['REQUEST_URI'] ?? 'Unknown',
                'method' => $_SERVER['REQUEST_METHOD'] ?? 'Unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
            ]
        ];

        // Save to a session for later use
        $_SESSION['error_message'] = $this->errorData['message'];
        $_SESSION['error_trace'] = $this->errorData['trace'];
        $_SESSION['error_context'] = $this->errorData['context'];
        $_SESSION['error_data'] = $this->errorData;

        // Log error
        $this->logger->critical('Exception occurred', [
            'exception' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);
    }

    /**
     * Loads error data from session
     */
    private function loadErrorFromSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // Check if full error data exists
        if (isset($_SESSION['error_data'])) {
            $this->errorData = $_SESSION['error_data'];
        } else {
            // Collect data from separate session variables
            $this->errorData = [
                'message' => $_SESSION['error_message'] ?? 'Unknown error occurred',
                'trace' => $_SESSION['error_trace'] ?? 'No trace available',
                'context' => $_SESSION['error_context'] ?? [],
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }
    }

}
