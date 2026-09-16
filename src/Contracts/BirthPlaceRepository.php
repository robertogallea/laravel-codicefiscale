<?php

namespace Robertogallea\CodiceFiscale\Contracts;

use Robertogallea\CodiceFiscale\Data\BirthPlaceCode;

interface BirthPlaceRepository
{
    /**
     * The era-record valid on the given date (defaulting to today),
     * or null if the code isn't recognized at all, or is recognized
     * but wasn't valid on that date.
     */
    public function find(BirthPlaceCode $code, ?\DateTimeImmutable $on = null): ?BirthPlace;

    /**
     * Whether this code was ever valid, at any point in its history.
     *
     * @deprecated since 3.1.0, removed in 4.0 - the same fact is
     *             `eras($code) !== []`, and since ADR-0011 nothing in
     *             the core needs it separately.
     */
    public function existedEver(BirthPlaceCode $code): bool;

    /**
     * Every era-record of this code, oldest first (ascending
     * validFrom), or an empty list for a code that never existed.
     * The raw history a caller needs to judge a code against a birth
     * date (see BirthPlaceAttributability) - unlike find(), which
     * answers only "valid on this one date".
     *
     * @return list<BirthPlace>
     */
    public function eras(BirthPlaceCode $code): array;

    /**
     * Era-records whose name contains $name (case/accent-insensitive
     * substring match). Unfiltered by validity unless $on is given -
     * a name search surfaces historical names on purpose. Results are
     * ordered most-recent-era-first; $limit caps the count returned.
     *
     * @return list<BirthPlace>
     */
    public function search(string $name, ?\DateTimeImmutable $on = null, ?int $limit = null): array;
}
