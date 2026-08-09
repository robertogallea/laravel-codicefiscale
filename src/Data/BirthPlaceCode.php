<?php

namespace Robertogallea\CodiceFiscale\Data;

use Robertogallea\CodiceFiscale\Exceptions\InvalidBirthPlaceCodeException;

/**
 * The 4-character cadastral code embedded in a codice fiscale's
 * positions 12-15 (e.g. "H501" for Roma, "Z404" for the USA).
 * Distinct from, and never equal to, an ISTAT code.
 */
final class BirthPlaceCode extends AbstractValidatedCode
{
    protected static function pattern(): string
    {
        return '/^[A-Z][0-9]{3}$/';
    }

    protected static function exceptionClass(): string
    {
        return InvalidBirthPlaceCodeException::class;
    }

    public function isForeign(): bool
    {
        return $this->value()[0] === 'Z';
    }

    public function equals(self $other): bool
    {
        return $this->value() === $other->value();
    }
}
