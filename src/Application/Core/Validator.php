<?php

/**
 * Universal validator for the application
 * Handles validation of user input
 * Provides a simple and flexible way to validate data
 * Can be used for form submissions, API requests, and more
 * Can be extended to support custom validation rules
 * Can be used with or without a database connection
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Application\Core;

use DateTime;


class Validator
{
    private array $data;
    private array $rules;
    private array $errors = [];
    private array $customMessages = [];

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * Sets validation rules
     */
    public function setRules(array $rules): self
    {
        $this->rules = $rules;
        return $this;
    }

    /**
     * Sets custom error messages
     */
    public function setMessages(array $messages): self
    {
        $this->customMessages = $messages;
        return $this;
    }

    /**
     * Performs validation
     */
    public function validate(): bool
    {
        $this->errors = [];

        foreach ($this->rules as $field => $fieldRules) {
            $value = $this->data[$field] ?? null;
            $rules = is_string($fieldRules) ? explode('|', $fieldRules) : $fieldRules;

            foreach ($rules as $rule) {
                $this->validateField($field, $value, $rule);
            }
        }

        return empty($this->errors);
    }

    /**
     * Returns validation errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Static method for quick validation
     */
    public static function make(array $data, array $rules, array $messages = []): self
    {
        return (new self($data))
            ->setRules($rules)
            ->setMessages($messages);
    }

    /**
     * Validates a single field
     */
    private function validateField(string $field, $value, string $rule): void
    {
        $ruleParts = explode(':', $rule);
        $ruleName = $ruleParts[0];
        $ruleParam = $ruleParts[1] ?? null;

        $isValid = match ($ruleName) {
            'required' => $this->validateRequired($value),
            'email' => $this->validateEmail($value),
            'min' => $this->validateMin($value, (int)$ruleParam),
            'max' => $this->validateMax($value, (int)$ruleParam),
            'numeric' => $this->validateNumeric($value),
            'alpha' => $this->validateAlpha($value),
            'alphanumeric' => $this->validateAlphanumeric($value),
            'url' => $this->validateUrl($value),
            'regex' => $this->validateRegex($value, $ruleParam),
            'confirmed' => $this->validateConfirmed($field, $value),
            'unique' => $this->validateUnique(),
            'in' => $this->validateIn($value, explode(',', $ruleParam)),
            'date' => $this->validateDate($value),
            'boolean' => $this->validateBoolean($value),
            'file' => $this->validateFile($field),
            'image' => $this->validateImage($field),
            default => true
        };

        if (!$isValid) {
            $this->addError($field, $ruleName, $ruleParam);
        }
    }

    /**
     * Adds a validation error
     */
    private function addError(string $field, string $rule, ?string $param = null): void
    {
        $key = "$field.$rule";

        $message = $this->customMessages[$key] ?? $this->getDefaultMessage($field, $rule, $param);

        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }

        $this->errors[$field][] = $message;
    }

    /**
     * Returns the default error message
     */
    private function getDefaultMessage(string $field, string $rule, ?string $param = null): string
    {
        $fieldName = ucfirst(str_replace('_', ' ', $field));

        return match ($rule) {
            'required' => "$fieldName is required",
            'email' => "$fieldName must be a valid email address",
            'min' => "$fieldName must be at least $param characters",
            'max' => "$fieldName must not exceed $param characters",
            'numeric' => "$fieldName must be numeric",
            'alpha' => "$fieldName must contain only letters",
            'alphanumeric' => "$fieldName must contain only letters and numbers",
            'url' => "$fieldName must be a valid URL",
            'regex' => "$fieldName format is invalid",
            'confirmed' => "$fieldName confirmation does not match",
            'unique' => "$fieldName already exists",
            'in' => "$fieldName is not valid",
            'date' => "$fieldName must be a valid date",
            'boolean' => "$fieldName must be true or false",
            'file' => "$fieldName must be a valid file",
            'image' => "$fieldName must be a valid image",
            default => "$fieldName is invalid"
        };
    }

    // Validation methods
    private function validateRequired($value): bool
    {
        return $value !== null && $value !== '' && (!is_array($value) || !empty($value));
    }

    private function validateEmail($value): bool
    {
        return $value === null || filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    private function validateMin($value, int $min): bool
    {
        return $value === null || strlen((string)$value) >= $min;
    }

    private function validateMax($value, int $max): bool
    {
        return $value === null || strlen((string)$value) <= $max;
    }

    private function validateNumeric($value): bool
    {
        return $value === null || is_numeric($value);
    }

    private function validateAlpha($value): bool
    {
        return $value === null || ctype_alpha($value);
    }

    private function validateAlphanumeric($value): bool
    {
        return $value === null || ctype_alnum($value);
    }

    private function validateUrl($value): bool
    {
        return $value === null || filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    private function validateRegex($value, string $pattern): bool
    {
        return $value === null || preg_match($pattern, $value);
    }

    private function validateConfirmed(string $field, $value): bool
    {
        $confirmationField = $field . '_confirmation';
        $confirmationValue = $this->data[$confirmationField] ?? null;
        return $value === $confirmationValue;
    }

    private function validateUnique(): bool
    {
        // This check requires a database connection
        // Can be implemented via callback or a separate service
        return true; // Stub
    }

    private function validateIn($value, array $options): bool
    {
        return $value === null || in_array($value, $options);
    }

    private function validateDate($value): bool
    {
        return $value === null || DateTime::createFromFormat('Y-m-d', $value) !== false;
    }

    private function validateBoolean($value): bool
    {
        return $value === null || is_bool($value) || in_array($value, ['0', '1', 'true', 'false'], true);
    }

    private function validateFile(string $field): bool
    {
        return isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK;
    }

    private function validateImage(string $field): bool
    {
        if (!$this->validateFile($field)) {
            return false;
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        return in_array($_FILES[$field]['type'], $allowedTypes);
    }
}
