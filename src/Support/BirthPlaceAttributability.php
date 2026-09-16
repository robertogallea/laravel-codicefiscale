<?php

namespace Robertogallea\CodiceFiscale\Support;

use Robertogallea\CodiceFiscale\Contracts\BirthPlace;

/**
 * Judges a BirthPlaceCode's era history against a birth date the way
 * the tax authority does: the code is assigned as of issue time, not
 * birth time, so a code instituted *after* the birth is still
 * attributable to the person (ADR-0011). The one thing that rules a
 * code out is every era having ended before the birth date - nobody
 * born then could have received it.
 */
final class BirthPlaceAttributability
{
    /**
     * The attributable era: the era valid on the birth date, else the
     * earliest one instituted after it. Null when the code is not
     * attributable for that date.
     *
     * @param  list<BirthPlace>  $eras
     */
    public static function era(array $eras, \DateTimeImmutable $birthDate): ?BirthPlace
    {
        usort($eras, static fn (BirthPlace $a, BirthPlace $b): int => $a->validFrom() <=> $b->validFrom());

        foreach ($eras as $era) {
            if ($era->validTo() === null || $era->validTo() > $birthDate) {
                return $era;
            }
        }

        return null;
    }
}
