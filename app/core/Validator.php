<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Input validation helper. Collects errors per field.
 */
final class Validator
{
    /** @var array<string,string> */
    private array $errors = [];

    public function __construct(private array $data)
    {
    }

    public function required(string $field, string $message): self
    {
        $value = trim((string) ($this->data[$field] ?? ''));
        if ($value === '') {
            $this->errors[$field] = $message;
        }
        return $this;
    }

    public function email(string $field, string $message): self
    {
        $value = trim((string) ($this->data[$field] ?? ''));
        if ($value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = $message;
        }
        return $this;
    }

    public function min(string $field, int $length, string $message): self
    {
        $value = (string) ($this->data[$field] ?? '');
        if (mb_strlen($value) < $length) {
            $this->errors[$field] = $message;
        }
        return $this;
    }

    public function numeric(string $field, string $message, bool $allowEmpty = false): self
    {
        $value = (string) ($this->data[$field] ?? '');
        if ($allowEmpty && trim($value) === '') {
            return $this;
        }
        if (!is_numeric($value)) {
            $this->errors[$field] = $message;
        }
        return $this;
    }

    public function minValue(string $field, float $min, string $message): self
    {
        $value = (string) ($this->data[$field] ?? '');
        if (is_numeric($value) && (float) $value < $min) {
            $this->errors[$field] = $message;
        }
        return $this;
    }

    public function in(string $field, array $allowed, string $message): self
    {
        $value = (string) ($this->data[$field] ?? '');
        if (!in_array($value, $allowed, true)) {
            $this->errors[$field] = $message;
        }
        return $this;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    /** @return array<string,string> */
    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): ?string
    {
        return $this->errors ? reset($this->errors) : null;
    }
}