<?php

namespace Robertogallea\CodiceFiscale\Laravel\BirthPlaces;

use Illuminate\Database\Eloquent\Builder;
use Robertogallea\CodiceFiscale\Contracts\BirthPlace;
use Robertogallea\CodiceFiscale\Contracts\BirthPlaceRepository;
use Robertogallea\CodiceFiscale\Data\BirthPlaceCode;
use Robertogallea\CodiceFiscale\Laravel\BirthPlaces\Models\AbstractBirthPlaceModel;

/**
 * Generic BirthPlaceRepository backed by a single Eloquent model -
 * works for either Municipality or ForeignCountry, since both share
 * the same code/valid_from/valid_to shape and each knows how to turn
 * its own row into the right BirthPlace via ConvertsToBirthPlace.
 */
final class EloquentBirthPlaceRepository implements BirthPlaceRepository
{
    /** @param class-string<AbstractBirthPlaceModel> $modelClass */
    public function __construct(
        private readonly string $modelClass,
    ) {
    }

    public function find(BirthPlaceCode $code, ?\DateTimeImmutable $on = null): ?BirthPlace
    {
        $on ??= new \DateTimeImmutable('today');

        // PHPStan can't follow the generic self-type through a dynamic
        // class-string::query() call without Larastan; $modelClass is
        // already constrained to class-string<AbstractBirthPlaceModel>
        // by the constructor's @param, so this is narrowing a type
        // PHPStan can't derive on its own, not overriding a correct one.
        /** @var AbstractBirthPlaceModel|null $row */
        $row = $this->modelClass::query()
            ->where('code', $code->value())
            ->whereDate('valid_from', '<=', $on)
            ->where(function (Builder $query) use ($on) {
                $query->whereNull('valid_to')->orWhereDate('valid_to', '>', $on);
            })
            ->first();

        return $row?->toBirthPlace();
    }

    public function existedEver(BirthPlaceCode $code): bool
    {
        return $this->modelClass::query()->where('code', $code->value())->exists();
    }
}
