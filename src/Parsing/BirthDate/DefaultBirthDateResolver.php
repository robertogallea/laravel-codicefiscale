<?php

namespace Robertogallea\CodiceFiscale\Parsing\BirthDate;

use Robertogallea\CodiceFiscale\Contracts\BirthDateResolver;
use Robertogallea\CodiceFiscale\Data\BirthDateResolutionContext;

/**
 * Reference-date resolution: picks the most plausible complete birth
 * date out of the context's already calendar-valid candidates. A
 * candidate after the reference date, or older than maxAge as of the
 * reference date, is not plausible. Between two plausible candidates,
 * a BirthPlaceCode valid at exactly one candidate date wins; otherwise
 * the younger candidate is preferred. Returns null when no candidate
 * is plausible - a codice fiscale's two-digit year does not always
 * carry enough information to resolve a complete date.
 */
final readonly class DefaultBirthDateResolver implements BirthDateResolver
{
    public function __construct(
        private int $maxAge = 120,
        private ?\DateTimeImmutable $referenceDate = null,
    ) {
    }

    public function resolve(BirthDateResolutionContext $context): ?\DateTimeImmutable
    {
        $referenceDate = $this->referenceDate ?? $context->referenceDate();

        $plausible = array_values(array_filter(
            $context->candidates(),
            fn (\DateTimeImmutable $candidate): bool => $candidate <= $referenceDate
                && $candidate->diff($referenceDate)->y <= $this->maxAge
        ));

        if ($plausible === []) {
            return null;
        }

        if (count($plausible) === 1) {
            return $plausible[0];
        }

        return $this->selectByBirthPlaceHistory($plausible, $context) ?? max($plausible);
    }

    /** @param  list<\DateTimeImmutable>  $plausible  exactly two candidates */
    private function selectByBirthPlaceHistory(array $plausible, BirthDateResolutionContext $context): ?\DateTimeImmutable
    {
        $validAt = array_values(array_filter(
            $plausible,
            fn (\DateTimeImmutable $candidate): bool => $context->birthPlaceRepository()->find($context->birthPlaceCode(), $candidate) !== null
        ));

        return count($validAt) === 1 ? $validAt[0] : null;
    }
}
