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

test('search() matches a substring against name_normalized, case-insensitively', function () {
    Municipality::create([
        'code' => 'H501', 'name' => 'ROMA', 'name_normalized' => 'ROMA', 'province' => 'RM',
        'istat_code' => '058091', 'valid_from' => '1871-01-01', 'valid_to' => null,
    ]);

    $matches = (new EloquentBirthPlaceRepository(Municipality::class))->search('roma');

    expect($matches)->toHaveCount(1)
        ->and($matches[0]->name())->toBe('ROMA');
});

test('search() returns an empty array when nothing matches', function () {
    Municipality::create([
        'code' => 'H501', 'name' => 'ROMA', 'name_normalized' => 'ROMA', 'province' => 'RM',
        'istat_code' => '058091', 'valid_from' => '1871-01-01', 'valid_to' => null,
    ]);

    expect((new EloquentBirthPlaceRepository(Municipality::class))->search('nonexistent'))->toBe([]);
});

test('search() is unfiltered by default but honors an explicit "on" date', function () {
    Municipality::create([
        'code' => 'A004', 'name' => 'ABBADIA CERRETO', 'name_normalized' => 'ABBADIA CERRETO', 'province' => 'MI',
        'istat_code' => '015001', 'valid_from' => '1861-03-17', 'valid_to' => '1992-04-16',
    ]);
    Municipality::create([
        'code' => 'A004', 'name' => 'ABBADIA CERRETO', 'name_normalized' => 'ABBADIA CERRETO', 'province' => 'LO',
        'istat_code' => '098001', 'valid_from' => '1992-04-16', 'valid_to' => null,
    ]);

    $repository = new EloquentBirthPlaceRepository(Municipality::class);

    expect($repository->search('abbadia'))->toHaveCount(2)
        ->and($repository->search('abbadia', new DateTimeImmutable('1950-01-01')))->toHaveCount(1);
});

test('search() orders results most-recent-era-first and honors limit', function () {
    Municipality::create([
        'code' => 'A004', 'name' => 'ABBADIA CERRETO', 'name_normalized' => 'ABBADIA CERRETO', 'province' => 'MI',
        'istat_code' => '015001', 'valid_from' => '1861-03-17', 'valid_to' => '1992-04-16',
    ]);
    Municipality::create([
        'code' => 'A004', 'name' => 'ABBADIA CERRETO', 'name_normalized' => 'ABBADIA CERRETO', 'province' => 'LO',
        'istat_code' => '098001', 'valid_from' => '1992-04-16', 'valid_to' => null,
    ]);

    $matches = (new EloquentBirthPlaceRepository(Municipality::class))->search('abbadia', limit: 1);

    expect($matches)->toHaveCount(1)
        ->and($matches[0]->province())->toBe('LO');
});

test('search() skips a row with a malformed persisted code instead of throwing', function () {
    Municipality::create([
        'code' => 'ND', 'name' => 'SENALE', 'name_normalized' => 'SENALE', 'province' => 'TN',
        'istat_code' => '022999', 'valid_from' => '1861-03-17', 'valid_to' => '1928-05-06',
    ]);

    $matches = (new EloquentBirthPlaceRepository(Municipality::class))->search('senale');

    expect($matches)->toBe([]);
});

test('search() still returns valid rows alongside a skipped malformed one', function () {
    Municipality::create([
        'code' => 'H501', 'name' => 'ROMA', 'name_normalized' => 'ROMA', 'province' => 'RM',
        'istat_code' => '058091', 'valid_from' => '1871-01-01', 'valid_to' => null,
    ]);
    Municipality::create([
        'code' => 'ND', 'name' => 'ROMAGNANO', 'name_normalized' => 'ROMAGNANO', 'province' => 'TN',
        'istat_code' => '022999', 'valid_from' => '1861-03-17', 'valid_to' => '1928-05-06',
    ]);

    $matches = (new EloquentBirthPlaceRepository(Municipality::class))->search('roma');

    expect($matches)->toHaveCount(1)
        ->and($matches[0]->name())->toBe('ROMA');
});

test('find() returns null instead of throwing when the row has a malformed country_code', function () {
    ForeignCountry::create([
        'code' => 'Z404', 'name' => "STATI UNITI D'AMERICA", 'country_code' => 'ND',
        'valid_from' => '1900-01-01', 'valid_to' => null,
    ]);

    $repository = new EloquentBirthPlaceRepository(ForeignCountry::class);

    expect($repository->find(BirthPlaceCode::from('Z404')))->toBeNull();
});
