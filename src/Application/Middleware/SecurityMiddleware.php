<?php

/**
 * Security middleware
 * Handles security-related tasks
 *
 * @author Dmytro Hovenko
 */
declare(strict_types=1);

namespace App\Application\Middleware;

use App\Application\Core\ServiceProvider;
use App\Domain\Interfaces\LoggerInterface;
use ReflectionException;

/**
 * Базовый класс для security middleware
 */
abstract class SecurityMiddleware
{
    protected LoggerInterface $logger;

    /**
     * @throws ReflectionException
     */
    public function __construct()
    {
        $services = ServiceProvider::getInstance();
        $this->logger = $services->getLogger();
    }

    abstract public function handle(): bool;

    protected function logSecurityEvent(string $event, array $context = []): void
    {
        $this->logger->warning($event, array_merge([
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
        ], $context));
    }
}
