<?php

namespace Robertogallea\CodiceFiscale\Data;

use Robertogallea\CodiceFiscale\Exceptions\InvalidCountryCodeException;

/**
 * An ISO alpha-3 country code: a country's own current code, the
 * former code of a ceased state (`VDR`, `PCZ`), or - for a territory
 * with no code of its own - the code of the state it belongs to today
 * (ADR-0013). Distinct from the Z-prefixed BirthPlaceCode
 * a foreign birthplace is looked up by.
 */
final class CountryCode extends AbstractValidatedCode
{
    protected static function pattern(): string
    {
        return '/^[A-Z]{3}$/';
    }

    protected static function exceptionClass(): string
    {
        return InvalidCountryCodeException::class;
    }

    public function equals(self $other): bool
    {
        return $this->value() === $other->value();
    }
}
