<?php

/**
 * User registration interface
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Domain\Interfaces;

use App\Application\Core\Results;


interface UserRegistrationInterface
{
    /**
     * Register a new user
     */
    public function register(array $userData): Results;

    /**
     * Verify a user's email with token
     */
    public function verifyEmail(string $token): Results;

    /**
     * Resend verification email
     */
    public function resendVerification(string $email): Results;

    /**
     * Check if an email is already registered
     */
    public function emailExists(string $email): bool;

    /**
     * Check if the username is already taken
     */
    public function usernameExists(string $username): bool;

    /**
     * Validate registration data
     */
    public function validateRegistrationData(array $data): Results;

    /**
     * Generate verification token
     */
    public function generateVerificationToken(int $userId): string;

    /**
     * Get user by verification token
     */
    public function getUserByVerificationToken(string $token): ?array;

    /**
     * Mark email as verified
     */
    public function markEmailAsVerified(int $userId): bool;
}