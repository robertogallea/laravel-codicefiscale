<?php

namespace Robertogallea\CodiceFiscale\Parsing;

use Robertogallea\CodiceFiscale\Contracts\BirthPlace;
use Robertogallea\CodiceFiscale\Contracts\BirthPlaceRepository;
use Robertogallea\CodiceFiscale\Contracts\CenturyResolver;
use Robertogallea\CodiceFiscale\Data\BirthPlaceCode;
use Robertogallea\CodiceFiscale\Enums\Gender;

final readonly class ParsedCodiceFiscale
{
    public function __construct(
        private string $surnameCode,
        private string $nameCode,
        private int $birthYearCode,
        private int $birthMonthNumber,
        private int $birthDay,
        private Gender $gender,
        private BirthPlaceCode $birthPlaceCode,
        private bool $isOmocodia,
        private BirthPlaceRepository $birthPlaceRepository,
        private CenturyResolver $centuryResolver,
    ) {
    }

    public function surnameCode(): string
    {
        return $this->surnameCode;
    }

    public function nameCode(): string
    {
        return $this->nameCode;
    }

    public function gender(): Gender
    {
        return $this->gender;
    }

    public function birthMonth(): int
    {
        return $this->birthMonthNumber;
    }

    public function birthDay(): int
    {
        return $this->birthDay;
    }

    public function birthPlaceCode(): BirthPlaceCode
    {
        return $this->birthPlaceCode;
    }

    public function isOmocodia(): bool
    {
        return $this->isOmocodia;
    }

    /** @return array{int, int} */
    public function possibleBirthYears(): array
    {
        return [1900 + $this->birthYearCode, 2000 + $this->birthYearCode];
    }

    public function birthYear(): int
    {
        return $this->centuryResolver->resolve($this->possibleBirthYears());
    }

    public function birthDate(): \DateTimeImmutable
    {
        return new \DateTimeImmutable(sprintf(
            '%04d-%02d-%02d',
            $this->birthYear(),
            $this->birthMonthNumber,
            $this->birthDay,
        ));
    }

    public function birthPlace(): ?BirthPlace
    {
        return $this->birthPlaceRepository->find($this->birthPlaceCode, $this->birthDate());
    }
}
