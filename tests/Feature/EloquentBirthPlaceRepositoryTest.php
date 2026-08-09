<?php

use Robertogallea\CodiceFiscale\Data\BirthPlaceCode;
use Robertogallea\CodiceFiscale\Laravel\BirthPlaces\EloquentBirthPlaceRepository;
use Robertogallea\CodiceFiscale\Laravel\BirthPlaces\Models\ForeignCountry;
use Robertogallea\CodiceFiscale\Laravel\BirthPlaces\Models\Municipality;

test('smoke: can insert a row and find it back through the repository', function () {
    Municipality::create([
        'code' => 'H501',
        'name' => 'ROMA',
        'province' => 'RM',
        'istat_code' => '058091',
        'valid_from' => '1871-01-01',
        'valid_to' => null,
    ]);

    $repository = new EloquentBirthPlaceRepository(Municipality::class);
    $place = $repository->find(BirthPlaceCode::from('H501'));

    expect($place)->not->toBeNull()
        ->and($place->name())->toBe('ROMA');
});

test('find() resolves the era-record valid on the given date - the real Abbadia Cerreto province change', function () {
    Municipality::create([
        'code' => 'A004', 'name' => 'ABBADIA CERRETO', 'province' => 'MI',
        'istat_code' => '015001', 'valid_from' => '1861-03-17', 'valid_to' => '1992-04-16',
    ]);
    Municipality::create([
        'code' => 'A004', 'name' => 'ABBADIA CERRETO', 'province' => 'LO',
        'istat_code' => '098001', 'valid_from' => '1992-04-16', 'valid_to' => null,
    ]);

    $repository = new EloquentBirthPlaceRepository(Municipality::class);
    $code = BirthPlaceCode::from('A004');

    expect($repository->find($code, new DateTimeImmutable('1950-01-01'))->province())->toBe('MI')
        ->and($repository->find($code, new DateTimeImmutable('2000-01-01'))->province())->toBe('LO');
});

test('find() defaults to today when no date is given', function () {
    Municipality::create([
        'code' => 'A004', 'name' => 'ABBADIA CERRETO', 'province' => 'LO',
        'istat_code' => '098001', 'valid_from' => '1992-04-16', 'valid_to' => null,
    ]);

    $repository = new EloquentBirthPlaceRepository(Municipality::class);

    expect($repository->find(BirthPlaceCode::from('A004'))->province())->toBe('LO');
});

test('find() returns null for a code that never existed', function () {
    $repository = new EloquentBirthPlaceRepository(Municipality::class);

    expect($repository->find(BirthPlaceCode::from('Z999')))->toBeNull();
});

test('find() returns null for a code that existed, but not on the given date', function () {
    Municipality::create([
        'code' => 'A004', 'name' => 'ABBADIA CERRETO', 'province' => 'MI',
        'istat_code' => '015001', 'valid_from' => '1861-03-17', 'valid_to' => '1992-04-16',
    ]);

    $repository = new EloquentBirthPlaceRepository(Municipality::class);

    expect($repository->find(BirthPlaceCode::from('A004'), new DateTimeImmutable('1800-01-01')))->toBeNull();
});

test('existedEver() distinguishes a never-known code from a known one', function () {
    Municipality::create([
        'code' => 'A004', 'name' => 'ABBADIA CERRETO', 'province' => 'MI',
        'istat_code' => '015001', 'valid_from' => '1861-03-17', 'valid_to' => '1992-04-16',
    ]);

    $repository = new EloquentBirthPlaceRepository(Municipality::class);

    expect($repository->existedEver(BirthPlaceCode::from('A004')))->toBeTrue()
        ->and($repository->existedEver(BirthPlaceCode::from('Z999')))->toBeFalse();
});

test('resolves a foreign birthplace via the ForeignCountry model', function () {
    ForeignCountry::create([
        'code' => 'Z404', 'name' => "STATI UNITI D'AMERICA", 'country_code' => 'USA',
        'valid_from' => '1900-01-01', 'valid_to' => null,
    ]);

    $repository = new EloquentBirthPlaceRepository(ForeignCountry::class);
    $place = $repository->find(BirthPlaceCode::from('Z404'));

    expect($place->country()->value())->toBe('USA');
});
