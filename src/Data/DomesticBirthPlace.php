<?php

namespace Robertogallea\CodiceFiscale\Data;

final readonly class DomesticBirthPlace extends AbstractBirthPlace
{
    public function __construct(
        BirthPlaceCode $code,
        string $name,
        private string $province,
        private string $istatCode,
        \DateTimeImmutable $validFrom,
        ?\DateTimeImmutable $validTo = null,
    ) {
        parent::__construct($code, $name, $validFrom, $validTo);
    }

    public function province(): string
    {
        return $this->province;
    }

    public function istatCode(): string
    {
        return $this->istatCode;
    }
}
