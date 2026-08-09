<?php

namespace Robertogallea\CodiceFiscale\Data;

use Robertogallea\CodiceFiscale\Exceptions\InvalidCountryCodeException;

/**
 * An ISO 3166-1 alpha-3 country code, sourced from MAECI's
 * stati-esteri table. Distinct from the Z-prefixed BirthPlaceCode
 * a foreign birthplace is looked up by.
 */
final class CountryCode implements \Stringable
{
    private const PATTERN = '/^[A-Z]{3}$/';

    private function __construct(
        private readonly string $value,
    ) {
    }

    public static function from(string $value): self
    {
        return self::tryFrom($value) ?? throw new InvalidCountryCodeException(
            sprintf('"%s" is not a structurally valid ISO 3166-1 alpha-3 country code.', $value)
        );
    }

    public static function tryFrom(string $value): ?self
    {
        $normalized = strtoupper(trim($value));

        if (preg_match(self::PATTERN, $normalized) !== 1) {
            return null;
        }

        return new self($normalized);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
