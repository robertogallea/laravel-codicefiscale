<?php

namespace Robertogallea\CodiceFiscale\Parsing\Century;

use Robertogallea\CodiceFiscale\Contracts\CenturyResolver;

/**
 * Resolves a codice fiscale's century ambiguity by preferring the
 * most recent candidate year that isn't in the future and doesn't
 * imply an age over maxAge - i.e. the youngest plausible reading.
 * When neither candidate is plausible (an unusually small maxAge),
 * falls back to the more recent one anyway, as the least-bad choice.
 */
final readonly class AgeBasedCenturyResolver implements CenturyResolver
{
    public function __construct(
        private int $maxAge = 120,
        private ?\DateTimeImmutable $referenceDate = null,
    ) {
    }

    public function resolve(array $possibleYears): int
    {
        $currentYear = (int) ($this->referenceDate ?? new \DateTimeImmutable('today'))->format('Y');

        $plausible = array_values(array_filter(
            $possibleYears,
            fn (int $year): bool => $year <= $currentYear && ($currentYear - $year) <= $this->maxAge
        ));

        return $plausible !== [] ? max($plausible) : max($possibleYears);
    }
}
