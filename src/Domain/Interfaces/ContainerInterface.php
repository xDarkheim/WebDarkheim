<?php

declare(strict_types=1);

namespace App\Domain\Interfaces;

use ReflectionException;

/**
 * Container Interface
 * Defines contract for dependency injection containers
 * Follows ISP - only container related methods
 */
interface ContainerInterface
{
    /**
     * Bind a service to the container
     */
    public function bind(string $abstract, callable|string|null $concrete = null): void;

    /**
     * Register a singleton binding
     */
    public function singleton(string $abstract, callable|string|null $concrete = null): void;

    /**
     * Resolve a service from the container
     *
     * @throws ReflectionException
     */
    public function make(string $abstract): mixed;

    /**
     * Check if the container has a binding
     */
    public function has(string $abstract): bool;

    /**
     * Bind a concrete value to the container
     */
    public function value(string $abstract, mixed $value): void;

    /**
     * Get all registered bindings
     */
    public function getBindings(): array;
}
