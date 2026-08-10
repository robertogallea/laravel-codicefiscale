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

test('search() merges matches from both the domestic and foreign tables', function () {
    Municipality::create([
        'code' => 'H501', 'name' => 'ROMA', 'name_normalized' => 'ROMA', 'province' => 'RM',
        'istat_code' => '058091', 'valid_from' => '1871-01-01', 'valid_to' => null,
    ]);
    ForeignCountry::create([
        'code' => 'Z404', 'name' => "STATI UNITI D'AMERICA", 'name_normalized' => "STATI UNITI D'AMERICA",
        'country_code' => 'USA', 'valid_from' => '1900-01-01', 'valid_to' => null,
    ]);

    $composite = compositeBirthPlaceRepository();

    expect($composite->search('roma'))->toHaveCount(1)
        ->and($composite->search('stati'))->toHaveCount(1)
        ->and($composite->search('a'))->toHaveCount(2);
});

test('search() never picks up a same-shaped row from the wrong table', function () {
    Municipality::create([
        'code' => 'H501', 'name' => 'ROMA', 'name_normalized' => 'ROMA', 'province' => 'RM',
        'istat_code' => '058091', 'valid_from' => '1871-01-01', 'valid_to' => null,
    ]);
    ForeignCountry::create([
        'code' => 'H501', 'name' => 'WRONG TABLE', 'name_normalized' => 'WRONG TABLE',
        'country_code' => 'XXX', 'valid_from' => '1900-01-01', 'valid_to' => null,
    ]);

    expect(compositeBirthPlaceRepository()->search('wrong'))->toHaveCount(1)
        ->and(compositeBirthPlaceRepository()->search('wrong')[0])->toBeInstanceOf(ForeignBirthPlace::class);
});

test('search() interleaves domestic and foreign matches by recency, not table-by-table', function () {
    // Closed domestic era (1861-1992), then a still-open domestic era
    // from 1992, then a still-open foreign match from 2005 - the
    // foreign record is the most recent, so it must rank first even
    // though it's the second table searched.
    Municipality::create([
        'code' => 'A004', 'name' => 'ABBADIA CERRETO', 'name_normalized' => 'ABBADIA CERRETO', 'province' => 'MI',
        'istat_code' => '015001', 'valid_from' => '1861-03-17', 'valid_to' => '1992-04-16',
    ]);
    Municipality::create([
        'code' => 'A004', 'name' => 'ABBADIA CERRETO', 'name_normalized' => 'ABBADIA CERRETO', 'province' => 'LO',
        'istat_code' => '098001', 'valid_from' => '1992-04-16', 'valid_to' => null,
    ]);
    ForeignCountry::create([
        'code' => 'Z999', 'name' => 'ABBADIA LAND', 'name_normalized' => 'ABBADIA LAND',
        'country_code' => 'XYZ', 'valid_from' => '2005-01-01', 'valid_to' => null,
    ]);

    $matches = compositeBirthPlaceRepository()->search('abbadia', limit: 2);

    expect($matches)->toHaveCount(2)
        ->and($matches[0])->toBeInstanceOf(ForeignBirthPlace::class)
        ->and($matches[1]->province())->toBe('LO');
});
