<?php

namespace Robertogallea\CodiceFiscale\Data;

use Robertogallea\CodiceFiscale\Contracts\BirthPlaceRepository;

/**
 * Everything a BirthDateResolver needs to pick a complete birth date
 * out of a codice fiscale's two-digit-year ambiguity: the candidate
 * dates already filtered down to real calendar dates (0, 1 or 2 of
 * them - never the raw, possibly-invalid year/month/day), the
 * reference date plausibility is judged against, and the birthplace
 * code plus repository needed to use historical BirthPlace validity
 * as a tie-breaker.
 */
final readonly class BirthDateResolutionContext
{
    /**
     * @param  list<\DateTimeImmutable>  $candidates  0-2 calendar-valid
     *                                                 candidate dates, ascending
     */
    public function __construct(
        private array $candidates,
        private \DateTimeImmutable $referenceDate,
        private BirthPlaceCode $birthPlaceCode,
        private BirthPlaceRepository $birthPlaceRepository,
    ) {
    }

    /** @return list<\DateTimeImmutable> */
    public function candidates(): array
    {
        return $this->candidates;
    }

    public function referenceDate(): \DateTimeImmutable
    {
        return $this->referenceDate;
    }

    public function birthPlaceCode(): BirthPlaceCode
    {
        return $this->birthPlaceCode;
    }

    public function birthPlaceRepository(): BirthPlaceRepository
    {
        return $this->birthPlaceRepository;
    }
}
