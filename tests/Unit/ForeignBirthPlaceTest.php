<?php

use Robertogallea\CodiceFiscale\Data\BirthPlaceCode;
use Robertogallea\CodiceFiscale\Data\CountryCode;
use Robertogallea\CodiceFiscale\Data\ForeignBirthPlace;

test('exposes its code, name and country', function () {
    $place = new ForeignBirthPlace(
        code: BirthPlaceCode::from('Z404'),
        name: "STATI UNITI D'AMERICA",
        country: CountryCode::from('USA'),
        validFrom: new DateTimeImmutable('1900-01-01'),
        validTo: null,
    );

    expect($place->code()->value())->toBe('Z404')
        ->and($place->name())->toBe("STATI UNITI D'AMERICA")
        ->and($place->country()->value())->toBe('USA')
        ->and($place->wasValidOn(new DateTimeImmutable('today')))->toBeTrue();
});
