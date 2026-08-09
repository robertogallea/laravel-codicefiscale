<?php

use Robertogallea\CodiceFiscale\Contracts\BirthPlace;
use Robertogallea\CodiceFiscale\Data\DomesticBirthPlace;
use Robertogallea\CodiceFiscale\Data\ForeignBirthPlace;

test('both flavors implement the shared BirthPlace interface', function () {
    expect(is_a(DomesticBirthPlace::class, BirthPlace::class, true))->toBeTrue()
        ->and(is_a(ForeignBirthPlace::class, BirthPlace::class, true))->toBeTrue();
});

test('DomesticBirthPlace has no country() - foreign-only concept', function () {
    expect(method_exists(DomesticBirthPlace::class, 'country'))->toBeFalse();
});

test('ForeignBirthPlace has no province() or istatCode() - domestic-only concepts', function () {
    expect(method_exists(ForeignBirthPlace::class, 'province'))->toBeFalse()
        ->and(method_exists(ForeignBirthPlace::class, 'istatCode'))->toBeFalse();
});
