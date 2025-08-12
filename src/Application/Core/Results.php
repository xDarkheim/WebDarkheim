<?php

/**
 * Results class for handling operation results
 * This class provides a consistent way to handle operation results, including success, failure, and validation errors.
 * It also provides methods for extracting relevant data from the result.
 * It can handle both string and array messages, storing array messages as data and extracting the first message for display.
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Application\Core;


class Results
{
    private bool $success;
    private string $message;
    private mixed $data;
    private ?array $user;

    public function __construct(
        bool $success = false,
        string|array $message = '',
        mixed $data = null,
        ?array $user = null
    ) {
        $this->success = $success;

        // Handle both string and array messages
        if (is_array($message)) {
            // If it's an array, store as data and extract the first error as a message
            $this->data = $data ?? $message;

            // Safely extract the first message from an array
            if (!empty($message)) {
                $firstValue = reset($message);
                // Check if the first value is a string, if not, try to convert it safely
                if (is_string($firstValue)) {
                    $this->message = $firstValue;
                } elseif (is_array($firstValue) && !empty($firstValue)) {
                    // If the first value is also an array, get its first string value
                    $this->message = (string)(reset($firstValue) ?: '');
                } else {
                    // Fallback to empty string or try to convert to string safely
                    $this->message = $firstValue ? (string)$firstValue : '';
                }
            } else {
                $this->message = '';
            }
        } else {
            $this->message = $message;
            $this->data = $data;
        }

        $this->user = $user;
    }

    /**
     * Check if the operation was successful
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Get result message
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Get result data
     */
    public function getData(): mixed
    {
        return $this->data;
    }

    /**
     * Get user data
     */
    public function getUser(): ?array
    {
        return $this->user;
    }

    /**
     * Set data
     */
    public function setData(mixed $data): self
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Create successful result
     */
    public static function success(string $message = '', mixed $data = null, ?array $user = null): self
    {
        return new self(true, $message, $data, $user);
    }

    /**
     * Create failure result
     */
    public static function failure(string $message = '', mixed $data = null): self
    {
        return new self(false, $message, $data);
    }

    /**
     * Check if a result is valid (alias for isSuccess)
     */
    public function isValid(): bool
    {
        return $this->success;
    }

    /**
     * Get validation errors
     */
    public function getErrors(): array
    {
        // If this is error data, return it as an array
        if (!$this->success && is_array($this->data)) {
            return $this->data;
        }

        // Return the message as an error array if not successful
        if (!$this->success && !empty($this->message)) {
            return ['error' => $this->message];
        }

        return [];
    }
}
