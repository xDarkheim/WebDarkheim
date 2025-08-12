<?php

/**
 * Token manager interface for token operations
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Domain\Interfaces;


interface TokenManagerInterface
{
    /**
     * Generate a new token
     */
    public function generateToken(int $length = 32): string;

    /**
     * Validate a token
     */
    public function validateToken(string $token, string $type = 'default'): bool;

    /**
     * Revoke a token
     */
    public function revokeToken(string $token): bool;

    /**
     * Store token with user association
     */
    public function storeToken(string $token, int $userId, string $type = 'default', ?int $expiresAt = null): bool;

    /**
     * Get token data
     */
    public function getTokenData(string $token): ?array;

    /**
     * Clean expired tokens
     */
    public function cleanExpiredTokens(): int;

    /**
     * Create verification token for specific user and type
     */
    public function createVerificationToken(int $userId, string $type, int $expiresInMinutes = 60): string;

    /**
     * Verify token and return token data
     */
    public function verifyToken(string $token, string $type): ?array;

    /**
     * Invalidate a token (alias for revokeToken)
     */
    public function invalidateToken(string $token): bool;

    /**
     * Revoke all tokens of a specific type for a user
     */
    public function revokeUserTokensByType(int $userId, string $type): bool;
}
