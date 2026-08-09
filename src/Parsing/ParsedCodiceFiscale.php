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

    /**
     * Null when the decoded month/day don't form a real calendar date
     * (e.g. a checksum-invalid or otherwise nonsense code) - decoding
     * never throws for data it doesn't recognize as sensible, the
     * same way birthPlace() returns null rather than throwing for an
     * unrecognized code.
     */
    public function birthDate(): ?\DateTimeImmutable
    {
        $year = $this->birthYear();

        if (! checkdate($this->birthMonthNumber, $this->birthDay, $year)) {
            return null;
        }

        return new \DateTimeImmutable(sprintf('%04d-%02d-%02d', $year, $this->birthMonthNumber, $this->birthDay));
    }

    public function birthPlace(): ?BirthPlace
    {
        $birthDate = $this->birthDate();

        return $birthDate === null ? null : $this->birthPlaceRepository->find($this->birthPlaceCode, $birthDate);
    }
}
