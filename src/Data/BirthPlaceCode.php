<?php

namespace Robertogallea\CodiceFiscale\Data;

use Robertogallea\CodiceFiscale\Exceptions\InvalidBirthPlaceCodeException;

/**
 * The 4-character cadastral code embedded in a codice fiscale's
 * positions 12-15 (e.g. "H501" for Roma, "Z404" for the USA).
 * Distinct from, and never equal to, an ISTAT code.
 */
final class BirthPlaceCode implements \Stringable
{
    private const PATTERN = '/^[A-Z][0-9]{3}$/';

    private function __construct(
        private readonly string $value,
    ) {
    }

    public static function from(string $value): self
    {
        return self::tryFrom($value) ?? throw new InvalidBirthPlaceCodeException(
            sprintf('"%s" is not a structurally valid birthplace code.', $value)
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

    public function isForeign(): bool
    {
        return $this->value[0] === 'Z';
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
