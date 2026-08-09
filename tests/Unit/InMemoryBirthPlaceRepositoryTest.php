<?php

use Robertogallea\CodiceFiscale\Data\BirthPlaceCode;
use Robertogallea\CodiceFiscale\Data\CountryCode;
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
