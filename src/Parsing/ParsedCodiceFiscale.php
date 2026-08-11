<?php

namespace Robertogallea\CodiceFiscale\Parsing;

use Robertogallea\CodiceFiscale\Contracts\BirthDateResolver;
use Robertogallea\CodiceFiscale\Contracts\BirthPlace;
use Robertogallea\CodiceFiscale\Contracts\BirthPlaceRepository;
use Robertogallea\CodiceFiscale\Data\BirthDateResolutionContext;
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
        private BirthDateResolver $birthDateResolver,
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

    /**
     * Null when the resolver finds no plausible complete date - either
     * because neither candidate is a real calendar date (e.g. a
     * checksum-invalid or otherwise nonsense code), or because neither
     * satisfies the resolver's own plausibility policy (e.g. maxAge).
     */
    public function birthYear(): ?int
    {
        $birthDate = $this->resolveBirthDate();

        return $birthDate === null ? null : (int) $birthDate->format('Y');
    }

    /**
     * Null under the same conditions as birthYear() - decoding never
     * throws for data it doesn't recognize as sensible, the same way
     * birthPlace() returns null rather than throwing for an
     * unrecognized code.
     */
    public function birthDate(): ?\DateTimeImmutable
    {
        return $this->resolveBirthDate();
    }

    public function birthPlace(): ?BirthPlace
    {
        $birthDate = $this->birthDate();

        return $birthDate === null ? null : $this->birthPlaceRepository->find($this->birthPlaceCode, $birthDate);
    }

    /** @return list<\DateTimeImmutable> calendar-valid candidates, ascending */
    private function candidateBirthDates(): array
    {
        $candidates = [];

        foreach ($this->possibleBirthYears() as $year) {
            if (checkdate($this->birthMonthNumber, $this->birthDay, $year)) {
                $candidates[] = new \DateTimeImmutable(sprintf('%04d-%02d-%02d', $year, $this->birthMonthNumber, $this->birthDay));
            }
        }

        return $candidates;
    }

    private function resolveBirthDate(): ?\DateTimeImmutable
    {
        return $this->birthDateResolver->resolve(new BirthDateResolutionContext(
            candidates: $this->candidateBirthDates(),
            referenceDate: new \DateTimeImmutable('today'),
            birthPlaceCode: $this->birthPlaceCode,
            birthPlaceRepository: $this->birthPlaceRepository,
        ));
    }
}
