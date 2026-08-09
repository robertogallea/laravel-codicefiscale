<?php

use Robertogallea\CodiceFiscale\Data\BirthPlaceCode;
use Robertogallea\CodiceFiscale\Data\DomesticBirthPlace;
use Robertogallea\CodiceFiscale\Data\ForeignBirthPlace;
use Robertogallea\CodiceFiscale\Laravel\BirthPlaces\CompositeBirthPlaceRepository;
use Robertogallea\CodiceFiscale\Laravel\BirthPlaces\EloquentBirthPlaceRepository;
use Robertogallea\CodiceFiscale\Laravel\BirthPlaces\Models\ForeignCountry;
use Robertogallea\CodiceFiscale\Laravel\BirthPlaces\Models\Municipality;

function compositeBirthPlaceRepository(): CompositeBirthPlaceRepository
{
    return new CompositeBirthPlaceRepository(
        new EloquentBirthPlaceRepository(Municipality::class),
        new EloquentBirthPlaceRepository(ForeignCountry::class),
    );
}

test('routes a domestic code to Municipality', function () {
    Municipality::create([
        'code' => 'H501', 'name' => 'ROMA', 'province' => 'RM',
        'istat_code' => '058091', 'valid_from' => '1871-01-01', 'valid_to' => null,
    ]);
    // A same-shaped row in the wrong table must never be picked up.
    ForeignCountry::create([
        'code' => 'H501', 'name' => 'WRONG TABLE', 'country_code' => 'XXX',
        'valid_from' => '1900-01-01', 'valid_to' => null,
    ]);

    $place = compositeBirthPlaceRepository()->find(BirthPlaceCode::from('H501'));

    expect($place)->toBeInstanceOf(DomesticBirthPlace::class)
        ->and($place->name())->toBe('ROMA');
});

test('routes a foreign (Z-prefixed) code to ForeignCountry', function () {
    ForeignCountry::create([
        'code' => 'Z404', 'name' => "STATI UNITI D'AMERICA", 'country_code' => 'USA',
        'valid_from' => '1900-01-01', 'valid_to' => null,
    ]);

    $place = compositeBirthPlaceRepository()->find(BirthPlaceCode::from('Z404'));

    expect($place)->toBeInstanceOf(ForeignBirthPlace::class)
        ->and($place->country()->value())->toBe('USA');
});

test('existedEver() also routes by code kind', function () {
    Municipality::create([
        'code' => 'H501', 'name' => 'ROMA', 'province' => 'RM',
        'istat_code' => '058091', 'valid_from' => '1871-01-01', 'valid_to' => null,
    ]);

    $composite = compositeBirthPlaceRepository();

    expect($composite->existedEver(BirthPlaceCode::from('H501')))->toBeTrue()
        ->and($composite->existedEver(BirthPlaceCode::from('Z404')))->toBeFalse();
});
