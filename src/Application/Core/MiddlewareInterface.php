<?php

/**
 * Middleware interface
 * Defines contract for middleware components
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Application\Core;

interface MiddlewareInterface
{
    public function handle(array $request, callable $next): array;
}
