<?php
declare(strict_types=1);

namespace App\Application\Core;

/**
 * Authentication result value object
 *
 * @author Dmytro Hovenko
 */
final readonly class AuthenticationResult
{
    public function __construct(
        private bool $success,
        private ?array $user = null,
        private string $error = '',
        private array $errors = []
    ) {
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getUser(): ?array
    {
        return $this->user;
    }

    public function getError(): string
    {
        return $this->error;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
