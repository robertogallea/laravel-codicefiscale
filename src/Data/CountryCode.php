<?php

namespace Robertogallea\CodiceFiscale\Data;

use Robertogallea\CodiceFiscale\Exceptions\InvalidCountryCodeException;

/**
 * An ISO 3166-1 alpha-3 country code, sourced from MAECI's
 * stati-esteri table. Distinct from the Z-prefixed BirthPlaceCode
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
