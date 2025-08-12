<?php

/**
 * Authentication interface following the Single Responsibility Principle
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Domain\Interfaces;

use App\Application\Core\Results;


interface AuthenticationInterface
{
    /**
     * Authenticate a user with email and password
     */
    public function authenticate(string $email, string $password): Results;

    /**
     * Check if the user is currently authenticated
     */
    public function isAuthenticated(): bool;

    /**
     * Get a current authenticated user
     */
    public function getCurrentUser(): ?array;

    /**
     * Logout current user
     */
    public function logout(): void;

    /**
     * Login user by user ID
     */
    public function loginUser(int $userId): Results;

    /**
     * Check if a user has a specific role
     */
    public function hasRole(string $role): bool;

    /**
     * Get user's roles
     */
    public function getUserRoles(): array;

    /**
     * Verify user's email address
     */
    public function verifyEmail(string $token): Results;

    /**
     * Check if the current user is admin
     */
    public function isAdmin(): bool;

    /**
     * Get current user ID
     */
    public function getCurrentUserId(): ?int;

    /**
     * Get current username
     */
    public function getCurrentUsername(): ?string;

    /**
     * Get current user role
     */
    public function getCurrentUserRole(): ?string;
}
