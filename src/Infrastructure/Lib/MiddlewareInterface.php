<?php

/**
 * Middleware interface for request processing
 *
 * @author Dmytro Hovenko
 */

namespace App\Infrastructure\Lib;


interface MiddlewareInterface
{
    public function handle(array $request, callable $next): array;
}
