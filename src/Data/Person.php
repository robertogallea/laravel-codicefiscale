<?php

namespace Robertogallea\CodiceFiscale\Data;

use Robertogallea\CodiceFiscale\Enums\Gender;

final readonly class Person
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public \DateTimeImmutable $birthDate,
        public BirthPlaceCode $birthPlace,
        public Gender $gender,
    ) {
    }
}
