<?php

declare(strict_types=1);

namespace App\Application\Core;

use App\Domain\Interfaces\MiddlewareManagerInterface;
use App\Application\Core\MiddlewareInterface;
use App\Domain\Interfaces\LoggerInterface;
use RuntimeException;
use ReflectionException;

/**
 * Middleware Manager
 * Manages middleware pipeline execution
 * Follows SRP - only manages middleware execution
 */
class MiddlewareManager implements MiddlewareManagerInterface
{
    /** @var MiddlewareInterface[] */
    private array $middleware = [];

    public function __construct(
        private readonly ServiceProvider $serviceProvider,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Add middleware to the pipeline
     */
    public function add(\App\Domain\Interfaces\MiddlewareInterface $middleware): self
    {
        $this->middleware[] = $middleware;
        return $this;
    }

    /**
     * Add middleware by class name
     */
    public function addByClass(string $middlewareClass): self
    {
        try {
            $middleware = $this->serviceProvider->getContainer()->make($middlewareClass);
            if (!$middleware instanceof \App\Domain\Interfaces\MiddlewareInterface) {
                throw new RuntimeException("Class {$middlewareClass} does not implement MiddlewareInterface");
            }
            return $this->add($middleware);
        } catch (ReflectionException $e) {
            $this->logger->error('Failed to instantiate middleware', [
                'class' => $middlewareClass,
                'error' => $e->getMessage()
            ]);
            throw new RuntimeException("Failed to create middleware: {$middlewareClass}");
        }
    }

    /**
     * Execute middleware pipeline
     */
    public function execute(): void
    {
        foreach ($this->middleware as $middleware) {
            try {
                // Упрощенное выполнение middleware без request/response
                if (method_exists($middleware, 'handle')) {
                    $middleware->handle();
                }
                $this->logger->debug('Middleware executed successfully', [
                    'class' => get_class($middleware)
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Middleware execution failed', [
                    'class' => get_class($middleware),
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        }
    }

    /**
     * Get registered middleware count
     */
    public function count(): int
    {
        return count($this->middleware);
    }

    /**
     * Clear all middleware
     */
    public function clear(): self
    {
        $this->middleware = [];
        return $this;
    }
}
