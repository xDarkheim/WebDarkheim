<?php

/**
 * Middleware Pipeline
 * Handles middleware execution
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Application\Core;


class MiddlewarePipeline
{
    private array $middleware = [];

    public function add(MiddlewareInterface $middleware): self
    {
        $this->middleware[] = $middleware;
        return $this;
    }

    public function process(array $request, callable $finalHandler): array
    {
        $pipeline = array_reduce(
            array_reverse($this->middleware),
            function ($next, $middleware) {
                return function ($request) use ($middleware, $next) {
                    return $middleware->handle($request, $next);
                };
            },
            $finalHandler
        );

        return $pipeline($request);
    }
}
