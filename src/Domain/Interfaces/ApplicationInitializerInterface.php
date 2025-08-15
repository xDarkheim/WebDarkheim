<?php

declare(strict_types=1);

namespace App\Domain\Interfaces;

/**
 * Application Initializer Interface
 * Defines contract for application initialization components
 * Follows ISP - specific initialization responsibilities
 */
interface ApplicationInitializerInterface
{
    /**
     * Initialize the component
     */
    public function initialize(): void;

    /**
     * Check if component is initialized
     */
    public function isInitialized(): bool;
}
