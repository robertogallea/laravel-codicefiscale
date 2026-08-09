<?php

namespace Robertogallea\CodiceFiscale\Data;

/**
 * Shared shape for a short, pattern-validated code: normalize
 * (uppercase, trim), match against a fixed regex, or fail. Used by
 * BirthPlaceCode and CountryCode, which differ only in their pattern
 * and the exception they throw.
 *
 * @phpstan-consistent-constructor
 */
abstract class AbstractValidatedCode implements \Stringable
{
    protected function __construct(
        private readonly string $value,
    ) {
    }

    abstract protected static function pattern(): string;

    /** @return class-string<\InvalidArgumentException> */
    abstract protected static function exceptionClass(): string;

    public static function from(string $value): static
    {
        return static::tryFrom($value) ?? throw new (static::exceptionClass())(
            sprintf('"%s" is not a valid %s.', $value, static::class)
        );
    }

    public static function tryFrom(string $value): ?static
    {
        $normalized = strtoupper(trim($value));

        if (preg_match(static::pattern(), $normalized) !== 1) {
            return null;
        }

        return new static($normalized);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
