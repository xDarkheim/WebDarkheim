<?php
declare(strict_types=1);

/**
 * Registration result value object
 *
 * @author Dmytro Hovenko
 */

namespace App\Application\Core;


final readonly class RegistrationResult
{
    public function __construct(
        private bool $success,
        private ?int $userId = null,
        private string $message = '',
        private array $errors = [],
        private array $data = []
    ) {
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getData(): array
    {
        return $this->data;
    }
}
