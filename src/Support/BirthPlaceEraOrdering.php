<?php

namespace Robertogallea\CodiceFiscale\Support;

use Robertogallea\CodiceFiscale\Contracts\BirthPlace;

/**
 * Era-record orderings shared by every BirthPlaceRepository that
 * sorts in PHP rather than in SQL. compare() is search()'s
 * most-recent-first: a still-currently-valid record (null validTo)
 * outranks any closed one, then a later validTo wins, then a later
 * validFrom breaks ties within the same validTo. oldestFirst() is
 * eras()'s chronological order.
 */
final class BirthPlaceEraOrdering
{
    /**
     * @param  list<BirthPlace>  $matches
     * @return list<BirthPlace>
     */
    public static function sortAndLimit(array $matches, ?int $limit): array
    {
        usort($matches, self::compare(...));

        return $limit === null ? $matches : array_slice($matches, 0, $limit);
    }

    /**
     * Ascending validFrom - the chronological order eras() promises,
     * and the one BirthPlaceAttributability walks. Deliberately not
     * the reverse of compare(): that ranks by validTo first.
     */
    public static function oldestFirst(BirthPlace $a, BirthPlace $b): int
    {
        return $a->validFrom() <=> $b->validFrom();
    }

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
