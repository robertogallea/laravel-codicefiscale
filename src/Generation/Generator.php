<?php

namespace Robertogallea\CodiceFiscale\Generation;

use Robertogallea\CodiceFiscale\CodiceFiscale;
use Robertogallea\CodiceFiscale\Contracts\NameNormalizer;
use Robertogallea\CodiceFiscale\Data\Person;

final class Generator
{
    public function __construct(
        private readonly NameEncoder $nameEncoder = new NameEncoder(),
        private readonly DateEncoder $dateEncoder = new DateEncoder(),
        private readonly BirthPlaceEncoder $birthPlaceEncoder = new BirthPlaceEncoder(),
        private readonly Checksum $checksum = new Checksum(),
        private readonly NameNormalizer $nameNormalizer = new ItalianNameNormalizer(),
    ) {
    }

    public function generate(Person $person): CodiceFiscale
    {
        $surnameCode = $this->nameEncoder->surnameCode(
            $this->nameNormalizer->normalize($person->lastName)
        );
        $nameCode = $this->nameEncoder->nameCode(
            $this->nameNormalizer->normalize($person->firstName)
        );
        $dateCode = $this->dateEncoder->encode($person->birthDate, $person->gender);
        $birthPlaceCode = $this->birthPlaceEncoder->encode($person->birthPlace);

        $first15 = $surnameCode.$nameCode.$dateCode.$birthPlaceCode;

        return CodiceFiscale::from($first15.$this->checksum->calculate($first15));
    }
}
