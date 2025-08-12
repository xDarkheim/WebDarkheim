<?php

/**
 * Simple Dependency Injection Container
 * Implements the Dependency Inversion Principle
 * and modern PHP practices
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Application\Core;

use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use ReflectionParameter;
use RuntimeException;


class Container
{
    private array $bindings = [];
    private array $instances = [];
    private array $singletons = [];

    /**
     * Bind an interface to an implementation
     */
    public function bind(string $abstract, string|callable|null $concrete = null, bool $singleton = false): void
    {
        $this->bindings[$abstract] = [
            'concrete' => $concrete ?? $abstract,
            'singleton' => $singleton
        ];
    }

    /**
     * Bind as a singleton
     */
    public function singleton(string $abstract, string|callable|null $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }

    /**
     * Bind an existing instance
     */
    public function instance(string $abstract, object $instance): void
    {
        $this->instances[$abstract] = $instance;
    }

    /**
     * Bind any value (including arrays, scalars)
     */
    public function value(string $abstract, mixed $value): void
    {
        $this->instances[$abstract] = $value;
    }

    /**
     * Check if a binding exists
     */
    public function has(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]);
    }

    /**
     * Resolve a dependency from the container
     * @throws ReflectionException
     */
    public function make(string $abstract): mixed
    {
        // Return an existing instance if bound
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        // Check if it's a singleton and already created
        if (isset($this->singletons[$abstract])) {
            return $this->singletons[$abstract];
        }

        $concrete = $this->getConcrete($abstract);

        // If concrete is a callable, execute it
        if (is_callable($concrete)) {
            $object = $concrete($this);
        } else {
            $object = $this->build($concrete);
        }

        // Store singleton instances
        if (isset($this->bindings[$abstract]) && $this->bindings[$abstract]['singleton']) {
            $this->singletons[$abstract] = $object;
        }

        return $object;
    }

    /**
     * Get a value from the container (for non-objects like arrays, strings, etc.)
     */
    public function get(string $abstract, mixed $default = null): mixed
    {
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        return $default;
    }

    /**
     * Get the concrete implementation
     */
    private function getConcrete(string $abstract): string|callable
    {
        if (isset($this->bindings[$abstract])) {
            return $this->bindings[$abstract]['concrete'];
        }

        return $abstract;
    }

    /**
     * Build an object with dependencies
     * @throws ReflectionException
     */
    private function build(string $concrete): object
    {
        try {
            $reflectionClass = new ReflectionClass($concrete);
        } catch (ReflectionException $e) {
            throw new RuntimeException("Target class [$concrete] does not exist.", 0, $e);
        }

        if (!$reflectionClass->isInstantiable()) {
            throw new RuntimeException("Target [$concrete] is not instantiable.");
        }

        $constructor = $reflectionClass->getConstructor();

        if ($constructor === null) {
            return new $concrete();
        }

        $dependencies = $this->resolveDependencies($constructor->getParameters());

        return $reflectionClass->newInstanceArgs($dependencies);
    }

    /**
     * Resolve constructor dependencies
     */
    private function resolveDependencies(array $parameters): array
    {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            try {
                $dependency = $this->resolveDependency($parameter);
            } catch (ReflectionException) {
                throw new RuntimeException("Cannot resolve parameter [{$parameter->getName()}]");
            }
            $dependencies[] = $dependency;
        }

        return $dependencies;
    }

    /**
     * Resolve a single dependency
     * @throws ReflectionException
     */
    private function resolveDependency(ReflectionParameter $parameter): mixed
    {
        $type = $parameter->getType();

        if ($type === null) {
            if ($parameter->isDefaultValueAvailable()) {
                return $parameter->getDefaultValue();
            }

            throw new RuntimeException("Cannot resolve parameter [{$parameter->getName()}]");
        }

        if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
            return $this->make($type->getName());
        }

        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        throw new RuntimeException("Cannot resolve parameter [{$parameter->getName()}]");
    }
}
