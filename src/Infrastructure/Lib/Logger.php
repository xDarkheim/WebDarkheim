<?php

/**
 * Logger implementation following Single Responsibility Principle
 * PSR-3 compatible logging with file rotation and error handling
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Infrastructure\Lib;

use App\Domain\Interfaces\LoggerInterface;
use Exception;
use Psr\Log\LoggerInterface as PsrLoggerInterface;
use RuntimeException;
use Stringable;
use Throwable;


class Logger implements LoggerInterface, PsrLoggerInterface
{
    private static ?self $instance = null;
    private readonly string $logPath;
    private readonly string $logLevel;
    private readonly int $maxFileSize;

    private const LOG_LEVELS = [
        'emergency' => 0,
        'alert' => 1,
        'critical' => 2,
        'error' => 3,
        'warning' => 4,
        'notice' => 5,
        'info' => 6,
        'debug' => 7,
    ];

    private function __construct()
    {
        $this->logPath = ROOT_PATH . DS . 'storage' . DS . 'logs' . DS . 'app.log';
        $this->logLevel = $_ENV['LOG_LEVEL'] ?? 'info';
        $this->maxFileSize = (int)($_ENV['LOG_MAX_FILE_SIZE'] ?? 10485760); // 10MB

        $this->ensureLogDirectory();
    }

    /**
     * Get a singleton instance
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * {@inheritdoc}
     */
    public function emergency(Stringable|string $message, array $context = []): void
    {
        $this->log('emergency', (string)$message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function alert(Stringable|string $message, array $context = []): void
    {
        $this->log('alert', (string)$message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function critical(Stringable|string $message, array $context = []): void
    {
        $this->log('critical', (string)$message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function error(Stringable|string $message, array $context = []): void
    {
        $this->log('error', (string)$message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function warning(Stringable|string $message, array $context = []): void
    {
        $this->log('warning', (string)$message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function notice(Stringable|string $message, array $context = []): void
    {
        $this->log('notice', (string)$message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function info(Stringable|string $message, array $context = []): void
    {
        $this->log('info', (string)$message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function debug(Stringable|string $message, array $context = []): void
    {
        $this->log('debug', (string)$message, $context);
    }

    /**
     * Log a message with a given level
     */
    public function log($level, Stringable|string $message, array $context = []): void
    {
        $levelStr = (string)$level;
        $messageStr = (string)$message;

        if (!$this->shouldLog($levelStr, $messageStr)) {
            return;
        }

        $logEntry = $this->formatLogEntry($levelStr, $messageStr, $context);

        $this->writeToFile($logEntry);

        // Also send critical errors to the system error log
        if (in_array($levelStr, ['emergency', 'alert', 'critical'])) {
            error_log("[$levelStr] $messageStr");
        }
    }

    /**
     * Check if we should log this message
     */
    private function shouldLog(string $level, string $message): bool
    {
        // Completely block all messages about E_STRICT
        if (str_contains($message, 'E_STRICT') ||
            str_contains($message, 'Constant E_STRICT') ||
            ($level === 'error' && str_contains($message, 'deprecated') && str_contains($message, 'Constant'))) {
            return false;
        }

        // Check the minimum log level
        $currentLevelValue = self::LOG_LEVELS[strtolower($level)] ?? 7;
        $minLevelValue = self::LOG_LEVELS[strtolower($this->logLevel)] ?? 6;

        if ($currentLevelValue > $minLevelValue) {
            return false;
        }

        // Ignore redundant messages only for non-error levels
        if (!in_array(strtolower($level), ['error', 'warning', 'critical', 'emergency', 'alert'])) {
            $ignoredMessages = [
                'Configuration loaded',
                'Bootstrap completed', 
                'Database connection established',
                'Site settings loaded',
                'System middleware initialized',
                'Loading configured page file',
                'Loading page file',
                'Authenticated user page access',
                'DEBUG Dashboard: Site management config',
                'DEBUG Dashboard: Admin navigation',
                'DEBUG Dashboard: User role'
            ];

            foreach ($ignoredMessages as $ignored) {
                if (str_contains($message, $ignored)) {
                    return false;
                }
            }
        }

        // Special filtering for cache operations that are incorrectly logged as ERROR
        if (strtolower($level) === 'error') {
            $cacheMessages = [
                'Cache clear completed',
                'OPcache cleared successfully',
                'Clearing cache directory',
                'Successfully deleted cache file',
                'Cache directory not found'
            ];

            foreach ($cacheMessages as $cacheMsg) {
                if (str_contains($message, $cacheMsg)) {
                    // These messages should be INFO, not ERROR - ignore them
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Format log entry
     */
    private function formatLogEntry(string $level, string $message, array $context): string
    {
        $timestamp = date('Y-m-d H:i:s');
        $levelUpper = strtoupper($level);
        $pid = getmypid();
        $memory = $this->getMemoryUsage();

        $logEntry = "[$timestamp] [$levelUpper] [PID:$pid] [MEM:$memory] $message";

        if (!empty($context)) {
            $contextJson = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $logEntry .= " Context: $contextJson";
        }

        return $logEntry . PHP_EOL;
    }

    /**
     * Write log entry to the file
     */
    private function writeToFile(string $logEntry): void
    {
        try {
            // Check if log rotation is needed
            if (file_exists($this->logPath) && filesize($this->logPath) > $this->maxFileSize) {
                $this->rotateLogFile();
            }

            // Check if we can write to the log file
            $logDir = dirname($this->logPath);
            if (!is_writable($logDir) || (file_exists($this->logPath) && !is_writable($this->logPath))) {
                // Try to fix permissions
                if (is_dir($logDir)) {
                    @chmod($logDir, 0755);
                }
                if (file_exists($this->logPath)) {
                    @chmod($this->logPath, 0644);
                }
            }

            $result = file_put_contents($this->logPath, $logEntry, FILE_APPEND | LOCK_EX);

            if ($result === false) {
                throw new RuntimeException("Unable to write to log file: " . $this->logPath);
            }
        } catch (Throwable $e) {
            // Fallback to the system error log if file logging fails
            error_log("Failed to write to log file (" . $this->logPath . "): " . $e->getMessage());

            // Try to log the original entry to system log as fallback
            $cleanEntry = str_replace(["\r", "\n"], ' ', trim($logEntry));
            error_log("FALLBACK LOG: " . $cleanEntry);
        }
    }

    /**
     * Rotate a log file when it gets too large
     */
    private function rotateLogFile(): void
    {
        $rotatedPath = $this->logPath . '.' . date('Y-m-d-H-i-s');

        try {
            rename($this->logPath, $rotatedPath);

            // Keep only the last 10 rotated files
            $this->cleanOldLogFiles();
        } catch (Throwable $e) {
            error_log("Failed to rotate log file: " . $e->getMessage());
        }
    }

    /**
     * Clean old log files, keeping only the most recent ones
     */
    private function cleanOldLogFiles(): void
    {
        $logDir = dirname($this->logPath);
        $logFiles = glob($logDir . DS . 'app.log.*');

        if (count($logFiles) > 10) {
            // Sort by modification time (oldest first)
            usort($logFiles, fn($a, $b) => filemtime($a) - filemtime($b));

            // Remove oldest files
            $filesToRemove = array_slice($logFiles, 0, count($logFiles) - 10);
            foreach ($filesToRemove as $file) {
                unlink($file);
            }
        }
    }

    /**
     * Ensure log directory exists
     */
    private function ensureLogDirectory(): void
    {
        $logDir = dirname($this->logPath);

        if (!is_dir($logDir)) {
            try {
                mkdir($logDir, 0755, true);

                // Ensure the directory is writable
                if (!is_writable($logDir)) {
                    @chmod($logDir, 0755);
                }
            } catch (Throwable $e) {
                error_log("Failed to create log directory (" . $logDir . "): " . $e->getMessage());
            }
        } elseif (!is_writable($logDir)) {
            // Try to fix permissions for the existing directory
            @chmod($logDir, 0755);
        }
    }

    /**
     * Get current memory usage
     */
    private function getMemoryUsage(): string
    {
        $bytes = memory_get_usage(true);
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . $units[$i];
    }

    /**
     * Prevent cloning
     */
    private function __clone() {}

    /**
     * Prevent unserialization
     * @throws Exception
     */
    public function __wakeup()
    {
        throw new Exception("Cannot unserialize singleton");
    }
}
