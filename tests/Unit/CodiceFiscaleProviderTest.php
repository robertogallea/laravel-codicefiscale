<?php

use Robertogallea\CodiceFiscale\Laravel\Faker\CodiceFiscaleProvider;
use Robertogallea\CodiceFiscale\Validation\Validator;
use Tests\Support\InMemoryBirthPlaceRepository;

function codiceFiscaleFaker(): Faker\Generator
{
    $faker = Faker\Factory::create('it_IT');
    $faker->seed(20260809);
    $faker->addProvider(new CodiceFiscaleProvider($faker));

    return $faker;
}

test('every generated code passes full Validator::validate() against the provider\'s own bundled birthplace list', function () {
    $validator = new Validator(new InMemoryBirthPlaceRepository(...CodiceFiscaleProvider::knownBirthPlaces()));
    $faker = codiceFiscaleFaker();

    for ($i = 0; $i < 50; $i++) {
        $result = $validator->validate($faker->codiceFiscale());

        expect($result->valid())->toBeTrue()
            ->and($result->errors())->toBe([]);
    }
});

test('knownBirthPlaces() is a small, clearly-scoped set of real Italian municipalities, not the full archive', function () {
    $places = CodiceFiscaleProvider::knownBirthPlaces();

    expect($places)->not->toBeEmpty()
        ->and(count($places))->toBeLessThan(50);

    $names = array_map(fn ($place) => $place->name(), $places);
    expect($names)->toContain('ROMA');
});

test('generated codes vary in birthplace across repeated calls, not just one hardcoded city', function () {
    $faker = codiceFiscaleFaker();

    $birthPlaceCodes = [];
    for ($i = 0; $i < 30; $i++) {
        $birthPlaceCodes[] = substr($faker->codiceFiscale(), 11, 4);
    }

    expect(count(array_unique($birthPlaceCodes)))->toBeGreaterThan(1);
});
