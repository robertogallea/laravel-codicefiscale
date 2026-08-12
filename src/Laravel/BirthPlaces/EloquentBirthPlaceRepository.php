<?php

namespace Robertogallea\CodiceFiscale\Laravel\BirthPlaces;

use Illuminate\Database\Eloquent\Builder;
use Robertogallea\CodiceFiscale\Contracts\BirthPlace;
use Robertogallea\CodiceFiscale\Contracts\BirthPlaceRepository;
use Robertogallea\CodiceFiscale\Data\BirthPlaceCode;
use Robertogallea\CodiceFiscale\Laravel\BirthPlaces\Models\AbstractBirthPlaceModel;
use Robertogallea\CodiceFiscale\Support\PlaceNameNormalizer;

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
        $row = $this->validOn($this->modelClass::query()->where('code', $code->value()), $on)
            ->first();

        return $this->safeToBirthPlace($row);
    }

    public function existedEver(BirthPlaceCode $code): bool
    {
        return $this->modelClass::query()->where('code', $code->value())->exists();
    }

    public function search(string $name, ?\DateTimeImmutable $on = null, ?int $limit = null): array
    {
        $needle = (new PlaceNameNormalizer())->normalize($name);

        $query = $this->modelClass::query()
            ->where('name_normalized', 'like', '%'.$needle.'%');

        if ($on !== null) {
            $this->validOn($query, $on);
        }

        $query->orderByRaw('(valid_to IS NULL) DESC')
            ->orderBy('valid_to', 'desc')
            ->orderBy('valid_from', 'desc');

        if ($limit !== null) {
            $query->limit($limit);
        }

        /** @var list<AbstractBirthPlaceModel> $rows */
        $rows = $query->get()->all();

        $places = array_map(fn (AbstractBirthPlaceModel $row): ?BirthPlace => $this->safeToBirthPlace($row), $rows);

        return array_values(array_filter($places, static fn (?BirthPlace $place): bool => $place !== null));
    }

    /**
     * @param  Builder<AbstractBirthPlaceModel>  $query
     * @return Builder<AbstractBirthPlaceModel>
     */
    private function validOn(Builder $query, \DateTimeImmutable $on): Builder
    {
        $query->whereDate('valid_from', '<=', $on)
            ->where(function (Builder $query) use ($on) {
                $query->whereNull('valid_to')->orWhereDate('valid_to', '>', $on);
            });

        return $query;
    }

    /**
     * A row whose persisted code/country_code fails validation (e.g.
     * ANPR's "ND" placeholder reaching the DB via a pre-fix import, a
     * manual edit, or a future importer bug) can't become a BirthPlace.
     * Import-time validation (MunicipalityCsvImporter /
     * ForeignCountryXlsxImporter) is the real fix; this is defense in
     * depth for data that reached the DB some other way - skip the row
     * rather than let it crash every call whose result set happens to
     * include it.
     */
    private function safeToBirthPlace(?AbstractBirthPlaceModel $row): ?BirthPlace
    {
        if ($row === null) {
            return null;
        }

        try {
            return $row->toBirthPlace();
        } catch (\InvalidArgumentException) {
            return null;
        }
    }
}
