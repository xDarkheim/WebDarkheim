<?php

/**
 * Middleware Interface
 * Defines contract for middleware components
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Application\Middleware;


interface MiddlewareInterface
{
    /**
     * Handle middleware execution
     * 
     * @return bool True to continue execution, false to stop
     */
    public function handle(): bool;
}
