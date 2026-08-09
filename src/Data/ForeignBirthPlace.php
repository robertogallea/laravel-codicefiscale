<?php

namespace Robertogallea\CodiceFiscale\Data;

final readonly class ForeignBirthPlace extends AbstractBirthPlace
{
    public function __construct(
        BirthPlaceCode $code,
        string $name,
        private CountryCode $country,
        \DateTimeImmutable $validFrom,
        ?\DateTimeImmutable $validTo = null,
    ) {
        parent::__construct($code, $name, $validFrom, $validTo);
    }

    public function country(): CountryCode
    {
        return $this->country;
    }
}
