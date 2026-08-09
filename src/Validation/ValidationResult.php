<?php

namespace Robertogallea\CodiceFiscale\Validation;

use Robertogallea\CodiceFiscale\Enums\ValidationError;

final readonly class ValidationResult
{
    /** @param list<ValidationError> $errors */
    public function __construct(
        private array $errors,
    ) {
    }

    public static function ok(): self
    {
        return new self([]);
    }

    public static function withError(ValidationError $error): self
    {
        return new self([$error]);
    }

    public function valid(): bool
    {
        return $this->errors === [];
    }

    /** @return list<ValidationError> */
    public function errors(): array
    {
        return $this->errors;
    }
}
