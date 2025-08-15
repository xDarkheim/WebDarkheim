<?php

declare(strict_types=1);

namespace App\Domain\Interfaces;

/**
 * Service Provider Interface
 * Defines contract for service provider implementations
 * Follows ISP - only service provider related methods
 */
interface ServiceProviderInterface
{
    /**
     * Register a service in the container
     */
    public function register(string $abstract, callable|string $concrete, bool $singleton = false): void;

    /**
     * Bind an interface to a concrete implementation
     */
    public function bind(string $abstract, string $concrete, bool $singleton = false): void;

    /**
     * Register a singleton service
     */
    public function singleton(string $abstract, callable|string $concrete): void;

    /**
     * Resolve a service from the container
     */
    public function resolve(string $abstract): mixed;

    /**
     * Check if service is registered
     */
    public function has(string $abstract): bool;

    /**
     * Get container instance
     */
    public function getContainer(): ContainerInterface;
}
