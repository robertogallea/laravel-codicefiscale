<?php

use Robertogallea\CodiceFiscale\Laravel\Faker\CodiceFiscaleProvider;
use Robertogallea\CodiceFiscale\Validation\Validator;
use Tests\Support\InMemoryBirthPlaceRepository;

test('fake()->codiceFiscale() works via the service provider\'s auto-registration, in a fresh app that never ran update-places', function () {
    // The app's own codicefiscale database is empty here -
    // codice-fiscale:update-places was never run - proving generation
    // itself never touches it. Full semantic validity is then checked
    // against the same bundled list the provider draws from, not the
    // (empty) real Eloquent-backed repository.
    $code = $this->app->make(Faker\Generator::class)->codiceFiscale();

    $validator = new Validator(new InMemoryBirthPlaceRepository(...CodiceFiscaleProvider::knownBirthPlaces()));

    expect($validator->validate($code)->valid())->toBeTrue();
});
