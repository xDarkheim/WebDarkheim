<?php

/**
 * Enhanced Dependency Injection Container
 * Implements ContainerInterface and follows SOLID principles
 * - Single Responsibility: Only manages dependency resolution
 * - Open/Closed: Extensible through binding mechanisms
 * - Dependency Inversion: Works with abstractions
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Application\Core;

use App\Domain\Interfaces\ContainerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use ReflectionParameter;
use RuntimeException;

class Container implements ContainerInterface
{
    private array $bindings = [];
    private array $instances = [];
    private array $values = [];

    /**
     * Bind an interface to an implementation
     */
    public function bind(string $abstract, callable|string|null $concrete = null): void
    {
        $this->bindings[$abstract] = [
            'concrete' => $concrete ?? $abstract,
            'singleton' => false
        ];
    }

    /**
     * Bind as a singleton
     */
    public function singleton(string $abstract, callable|string|null $concrete = null): void
    {
        $this->bindings[$abstract] = [
            'concrete' => $concrete ?? $abstract,
            'singleton' => true
        ];
    }

    /**
     * Bind a concrete value to the container
     */
    public function value(string $abstract, mixed $value): void
    {
        $this->values[$abstract] = $value;
    }

    /**
     * Check if the container has a binding
     */
    public function has(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) || isset($this->values[$abstract]);
    }

    /**
     * Get all registered bindings
     */
    public function getBindings(): array
    {
        return $this->bindings;
    }

    /**
     * Get value by key (for backward compatibility)
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->values[$key] ?? $default;
    }

    /**
     * Resolve a service from the container
     *
     * @throws ReflectionException
     */
    public function make(string $abstract): mixed
    {
        // Return value if bound directly
        if (isset($this->values[$abstract])) {
            return $this->values[$abstract];
        }

        // Return singleton instance if already created
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        // Get binding configuration
        $binding = $this->bindings[$abstract] ?? null;
        if ($binding === null) {
            // Try to auto-resolve if no binding exists
            return $this->resolve($abstract);
        }

        $concrete = $binding['concrete'];
        $instance = $this->createInstance($concrete);

        // Store singleton instances
        if ($binding['singleton']) {
            $this->instances[$abstract] = $instance;
        }

        return $instance;
    }

    /**
     * Create an instance of the concrete class
     */
    private function createInstance(callable|string $concrete): mixed
    {
        if (is_callable($concrete)) {
            return $concrete($this);
        }

        return $this->resolve($concrete);
    }

    /**
     * Resolve a class using reflection
     *
     * @throws ReflectionException
     */
    private function resolve(string $className): mixed
    {
        try {
            $reflectionClass = new ReflectionClass($className);
        } catch (\ReflectionException $e) {
            throw new RuntimeException("Class {$className} not found: " . $e->getMessage());
        }

        if (!$reflectionClass->isInstantiable()) {
            throw new RuntimeException("Class {$className} is not instantiable");
        }

        $constructor = $reflectionClass->getConstructor();
        if ($constructor === null) {
            return $reflectionClass->newInstance();
        }

        $dependencies = $this->resolveDependencies($constructor->getParameters());
        return $reflectionClass->newInstanceArgs($dependencies);
    }

    /**
     * Resolve constructor dependencies
     *
     * @param ReflectionParameter[] $parameters
     * @return array
     * @throws ReflectionException
     */
    private function resolveDependencies(array $parameters): array
    {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $dependency = $this->resolveDependency($parameter);
            $dependencies[] = $dependency;
        }

        return $dependencies;
    }

    /**
     * Resolve a single dependency
     *
     * @throws ReflectionException
     */
    private function resolveDependency(ReflectionParameter $parameter): mixed
    {
        $type = $parameter->getType();

        if ($type === null) {
            if ($parameter->isDefaultValueAvailable()) {
                return $parameter->getDefaultValue();
            }
            throw new RuntimeException("Cannot resolve parameter '{$parameter->getName()}' - no type hint");
        }

        if (!$type instanceof ReflectionNamedType) {
            throw new RuntimeException("Cannot resolve parameter '{$parameter->getName()}' - complex type");
        }

        $className = $type->getName();

        // Handle primitive types
        if ($type->isBuiltin()) {
            if ($parameter->isDefaultValueAvailable()) {
                return $parameter->getDefaultValue();
            }
            throw new RuntimeException("Cannot resolve primitive type '{$className}' for parameter '{$parameter->getName()}'");
        }

        // Resolve class dependency
        try {
            return $this->make($className);
        } catch (RuntimeException $e) {
            if ($parameter->isOptional()) {
                return $parameter->getDefaultValue();
            }
            throw $e;
        }
    }
}
