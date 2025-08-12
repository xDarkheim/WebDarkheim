<?php

/**
 * Interface for password management operations
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Domain\Interfaces;

use App\Application\Core\Results;


interface PasswordManagerInterface
{
    /**
     * Validate password against security policy
     */
    public function validatePassword(string $password): Results;

    /**
     * Hash a password securely
     */
    public function hashPassword(string $password): string;

    /**
     * Verify password against hash
     */
    public function verifyPassword(string $password, string $hash): bool;

    /**
     * Check if the password needs rehashing
     */
    public function needsRehash(string $hash): bool;

    /**
     * Generate a secure random password
     */
    public function generateSecurePassword(int $length = 12): string;

    /**
     * Get a password strength score
     */
    public function getPasswordStrength(string $password): int;
}