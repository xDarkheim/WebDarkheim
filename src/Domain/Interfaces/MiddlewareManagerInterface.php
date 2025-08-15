<?php

declare(strict_types=1);

namespace App\Domain\Interfaces;

/**
 * Middleware Manager Interface
 * Defines contract for middleware management
 * Follows ISP - only middleware-related operations
 */
interface MiddlewareManagerInterface
{
    /**
     * Add middleware to the pipeline
     */
    public function add(MiddlewareInterface $middleware): self;

    /**
     * Add middleware by class name
     */
    public function addByClass(string $middlewareClass): self;

    /**
     * Execute middleware pipeline
     */
    public function execute(): void;

    /**
     * Get registered middleware count
     */
    public function count(): int;

    /**
     * Clear all middleware
     */
    public function clear(): self;
}
