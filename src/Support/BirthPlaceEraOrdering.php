<?php

namespace Robertogallea\CodiceFiscale\Support;

use Robertogallea\CodiceFiscale\Contracts\BirthPlace;

/**
 * Orders era-records most-recent-first: a still-currently-valid
 * record (null validTo) outranks any closed one, then a later
 * validTo wins, then a later validFrom breaks ties within the same
 * validTo. Shared by every BirthPlaceRepository::search() that needs
 * to sort in PHP rather than in SQL.
 */
final class BirthPlaceEraOrdering
{
    public static function compare(BirthPlace $a, BirthPlace $b): int
    {
        $aOpen = $a->validTo() === null;
        $bOpen = $b->validTo() === null;

        if ($aOpen !== $bOpen) {
            return $aOpen ? -1 : 1;
        }

        if (! $aOpen && $a->validTo() != $b->validTo()) {
            return $b->validTo() <=> $a->validTo();
        }

        return $b->validFrom() <=> $a->validFrom();
    }
}
