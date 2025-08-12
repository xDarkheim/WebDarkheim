<?php

/**
 * Password management service following Single Responsibility Principle
 * Handles password validation, hashing, and security policies
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Interfaces\PasswordManagerInterface;
use App\Application\Core\Results;
use Random\RandomException;


class PasswordManager implements PasswordManagerInterface
{
    private const MIN_LENGTH = 8;
    private const MAX_LENGTH = 128;
    private const MIN_UNIQUE_CHARS = 6;

    private bool $requireUppercase;
    private bool $requireLowercase;
    private bool $requireNumbers;
    private bool $requireSpecialChars;

    /**
     * @param bool $requireUppercase
     * @param bool $requireLowercase
     * @param bool $requireNumbers
     * @param bool $requireSpecialChars
     */
    public function __construct(
        bool $requireUppercase = true,
        bool $requireLowercase = true,
        bool $requireNumbers = true,
        bool $requireSpecialChars = true
    ) {
        $this->requireUppercase = $requireUppercase;
        $this->requireLowercase = $requireLowercase;
        $this->requireNumbers = $requireNumbers;
        $this->requireSpecialChars = $requireSpecialChars;
    }

    /**
     * {@inheritdoc}
     */
    public function validatePassword(string $password, array $userData = []): Results
    {
        $errors = [];

        // Length validation
        if (strlen($password) < self::MIN_LENGTH) {
            $errors[] = sprintf('Password must be at least %d characters long.', self::MIN_LENGTH);
        }

        if (strlen($password) > self::MAX_LENGTH) {
            $errors[] = sprintf('Password must be no more than %d characters long.', self::MAX_LENGTH);
        }

        // Character requirements
        if ($this->requireUppercase && !preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter.';
        }

        if ($this->requireLowercase && !preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter.';
        }

        if ($this->requireNumbers && !preg_match('/\d/', $password)) {
            $errors[] = 'Password must contain at least one number.';
        }

        if ($this->requireSpecialChars && !preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"|,.<>\/?]/', $password)) {
            $errors[] = 'Password must contain at least one special character.';
        }

        // Unique characters check
        $uniqueChars = count(array_unique(str_split($password)));
        if ($uniqueChars < self::MIN_UNIQUE_CHARS) {
            $errors[] = sprintf('Password must contain at least %d unique characters.', self::MIN_UNIQUE_CHARS);
        }

        // Check against user data
        foreach ($userData as $field) {
            if (!empty($field) && stripos($password, (string)$field) !== false) {
                $errors[] = 'Password must not contain your username or email.';
                break;
            }
        }

        // Common password check
        if ($this->isCommonPassword($password)) {
            $errors[] = 'Password is too common. Please choose a more secure password.';
        }

        return new Results(empty($errors), $errors);
    }

    /**
     * {@inheritdoc}
     */
    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536, // 64 MB
            'time_cost' => 4,       // 4 iterations
            'threads' => 3,         // 3 threads
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * {@inheritdoc}
     */
    public function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536, // 64 MB
            'time_cost' => 4,       // 4 iterations
            'threads' => 3,         // 3 threads
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getPasswordStrength(string $password): int
    {
        $score = 0;

        // Length scoring
        $length = strlen($password);
        if ($length >= 8) $score += 1;
        if ($length >= 12) $score += 1;
        if ($length >= 16) $score += 1;
        if ($length >= 20) $score += 1;

        // Character variety scoring
        if (preg_match('/[a-z]/', $password)) $score += 1;
        if (preg_match('/[A-Z]/', $password)) $score += 1;
        if (preg_match('/\d/', $password)) $score += 1;
        if (preg_match('/[!@#$%^&*()_+\-=\[\]{};\':",.<>\/?]/', $password)) $score += 1;

        // Unique characters bonus
        $uniqueChars = count(array_unique(str_split($password)));
        if ($uniqueChars >= 8) $score += 1;
        if ($uniqueChars >= 12) $score += 1;

        // Common password penalty
        if ($this->isCommonPassword($password)) {
            $score = max(0, $score - 3);
        }

        // Return score as percentage (0-100)
        return min(100, ($score * 10));
    }

    /**
     * Check if a password is in a common passwords list
     */
    private function isCommonPassword(string $password): bool
    {
        $commonPasswords = [
            'password', '123456', '123456789', 'qwerty', 'abc123',
            'password123', 'admin', 'letting', 'welcome', 'monkey',
            'dragon', 'password1', '123123', 'football', 'iloveyou'
        ];

        return in_array(strtolower($password), $commonPasswords, true);
    }

    /**
     * {@inheritdoc}
     * @throws RandomException
     */
    public function generateSecurePassword(int $length = 12): string
    {
        if ($length < self::MIN_LENGTH) {
            $length = self::MIN_LENGTH;
        }

        if ($length > self::MAX_LENGTH) {
            $length = self::MAX_LENGTH;
        }

        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $numbers = '0123456789';
        $special = '!@#$%^&*()_+-=[]{}|;:,.<>?';

        $all = $lowercase . $uppercase . $numbers . $special;
        $password = '';

        // Ensure at least one character from each required category
        if ($this->requireLowercase) {
            $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        }
        if ($this->requireUppercase) {
            $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        }
        if ($this->requireNumbers) {
            $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        }
        if ($this->requireSpecialChars) {
            $password .= $special[random_int(0, strlen($special) - 1)];
        }

        // Fill the rest with the password
        for ($i = strlen($password); $i < $length; $i++) {
            $password .= $all[random_int(0, strlen($all) - 1)];
        }

        // Shuffle the password to avoid predictable patterns
        return str_shuffle($password);
    }
}
