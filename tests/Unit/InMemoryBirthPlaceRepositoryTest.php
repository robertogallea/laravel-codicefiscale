<?php

use Robertogallea\CodiceFiscale\Data\BirthPlaceCode;
use Robertogallea\CodiceFiscale\Data\CountryCode;
use Robertogallea\CodiceFiscale\Data\DomesticBirthPlace;
use Robertogallea\CodiceFiscale\Data\ForeignBirthPlace;
use Tests\Support\InMemoryBirthPlaceRepository;

test('find() resolves the era-record valid on the given date', function () {
    $repository = new InMemoryBirthPlaceRepository(
        abbadiaCerretoUnderMilano(),
        abbadiaCerretoUnderLodi(),
    );
    $code = BirthPlaceCode::from('A004');

    $beforeChange = $repository->find($code, new DateTimeImmutable('1950-01-01'));
    $afterChange = $repository->find($code, new DateTimeImmutable('2000-01-01'));

    expect($beforeChange->province())->toBe('MI')
        ->and($afterChange->province())->toBe('LO');
});

test('find() defaults to today when no date is given', function () {
    $repository = new InMemoryBirthPlaceRepository(abbadiaCerretoUnderLodi());

    expect($repository->find(BirthPlaceCode::from('A004'))->province())->toBe('LO');
});

test('find() returns null for a code that never existed', function () {
    $repository = new InMemoryBirthPlaceRepository(abbadiaCerretoUnderLodi());

    expect($repository->find(BirthPlaceCode::from('Z999')))->toBeNull();
});

test('find() returns null for a code that existed, but not on the given date', function () {
    $repository = new InMemoryBirthPlaceRepository(abbadiaCerretoUnderMilano());

    expect($repository->find(BirthPlaceCode::from('A004'), new DateTimeImmutable('1800-01-01')))->toBeNull();
});

test('existedEver() distinguishes a never-known code from a known one', function () {
    $repository = new InMemoryBirthPlaceRepository(abbadiaCerretoUnderMilano());

    expect($repository->existedEver(BirthPlaceCode::from('A004')))->toBeTrue()
        ->and($repository->existedEver(BirthPlaceCode::from('Z999')))->toBeFalse();
});

test('resolves a foreign birthplace the same way', function () {
    $usa = new ForeignBirthPlace(
        code: BirthPlaceCode::from('Z404'),
        name: "STATI UNITI D'AMERICA",
        country: CountryCode::from('USA'),
        validFrom: new DateTimeImmutable('1900-01-01'),
    );
    $repository = new InMemoryBirthPlaceRepository($usa);

    expect($repository->find(BirthPlaceCode::from('Z404'))->country()->value())->toBe('USA');
});

test('search() matches a substring, case-insensitively', function () {
    $repository = new InMemoryBirthPlaceRepository(abbadiaCerretoUnderLodi());

    expect($repository->search('abbadia'))->toHaveCount(1)
        ->and($repository->search('abbadia')[0]->name())->toBe('ABBADIA CERRETO');
});

test('search() is accent-insensitive', function () {
    $cittaDiCastello = new DomesticBirthPlace(
        code: BirthPlaceCode::from('C745'),
        name: 'CITTÀ DI CASTELLO',
        province: 'PG',
        istatCode: '054011',
        validFrom: new DateTimeImmutable('1861-01-01'),
    );
    $repository = new InMemoryBirthPlaceRepository($cittaDiCastello);

    expect($repository->search('citta di castello'))->toHaveCount(1);
});

test('search() returns an empty array when nothing matches', function () {
    $repository = new InMemoryBirthPlaceRepository(abbadiaCerretoUnderLodi());

    expect($repository->search('nonexistent'))->toBe([]);
});

test('search() is unfiltered by default, surfacing every matching era', function () {
    $repository = new InMemoryBirthPlaceRepository(
        abbadiaCerretoUnderMilano(),
        abbadiaCerretoUnderLodi(),
    );

    expect($repository->search('abbadia'))->toHaveCount(2);
});

test('search() with an "on" date filters to the era valid then', function () {
    $repository = new InMemoryBirthPlaceRepository(
        abbadiaCerretoUnderMilano(),
        abbadiaCerretoUnderLodi(),
    );

    $matches = $repository->search('abbadia', new DateTimeImmutable('1950-01-01'));

    expect($matches)->toHaveCount(1)
        ->and($matches[0]->province())->toBe('MI');
});

test('search() orders results most-recent-era-first and honors limit', function () {
    $repository = new InMemoryBirthPlaceRepository(
        abbadiaCerretoUnderMilano(),
        abbadiaCerretoUnderLodi(),
    );

    $matches = $repository->search('abbadia', limit: 1);

    expect($matches)->toHaveCount(1)
        ->and($matches[0]->province())->toBe('LO');
});

test('search() matches across both domestic and foreign records', function () {
    $usa = new ForeignBirthPlace(
        code: BirthPlaceCode::from('Z404'),
        name: "STATI UNITI D'AMERICA",
        country: CountryCode::from('USA'),
        validFrom: new DateTimeImmutable('1900-01-01'),
    );
    $repository = new InMemoryBirthPlaceRepository(abbadiaCerretoUnderLodi(), $usa);

    expect($repository->search('abbadia'))->toHaveCount(1)
        ->and($repository->search('stati'))->toHaveCount(1);
});
