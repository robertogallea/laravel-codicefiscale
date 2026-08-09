<?php

namespace Robertogallea\CodiceFiscale\Data;

use Robertogallea\CodiceFiscale\Enums\Gender;

final readonly class PartialPerson
{
    public function __construct(
        public ?string $firstName = null,
        public ?string $lastName = null,
        public ?\DateTimeImmutable $birthDate = null,
        public ?BirthPlaceCode $birthPlace = null,
        public ?Gender $gender = null,
    ) {
    }
}
