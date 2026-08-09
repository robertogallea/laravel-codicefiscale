<?php

namespace Robertogallea\CodiceFiscale\Laravel\BirthPlaces;

use Illuminate\Database\Eloquent\Model;
use Robertogallea\CodiceFiscale\Contracts\BirthPlace;
use Robertogallea\CodiceFiscale\Contracts\BirthPlaceRepository;
use Robertogallea\CodiceFiscale\Data\BirthPlaceCode;

/**
 * Generic BirthPlaceRepository backed by a single Eloquent model -
 * works for either Municipality or ForeignCountry, since both share
 * the same code/valid_from/valid_to shape and each knows how to turn
 * its own row into the right BirthPlace via ConvertsToBirthPlace.
 */
final class EloquentBirthPlaceRepository implements BirthPlaceRepository
{
    /** @param class-string<Model&ConvertsToBirthPlace> $modelClass */
    public function __construct(
        private readonly string $modelClass,
    ) {
    }

    public function find(BirthPlaceCode $code, ?\DateTimeImmutable $on = null): ?BirthPlace
    {
        $on ??= new \DateTimeImmutable('today');
        $onDate = $on->format('Y-m-d');

        /** @var (Model&ConvertsToBirthPlace)|null $row */
        $row = $this->modelClass::query()
            ->where('code', $code->value())
            ->where('valid_from', '<=', $onDate)
            ->where(function ($query) use ($onDate) {
                $query->whereNull('valid_to')->orWhere('valid_to', '>', $onDate);
            })
            ->first();

        return $row?->toBirthPlace();
    }

    public function existedEver(BirthPlaceCode $code): bool
    {
        return $this->modelClass::query()->where('code', $code->value())->exists();
    }
}
