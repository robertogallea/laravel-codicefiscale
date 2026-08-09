<?php

use Robertogallea\CodiceFiscale\Data\BirthPlaceCode;
use Robertogallea\CodiceFiscale\Exceptions\InvalidBirthPlaceCodeException;

test('from() accepts a real domestic cadastral code', function () {
    expect(BirthPlaceCode::from('H501')->value())->toBe('H501');
});

test('from() normalizes lowercase input', function () {
    expect(BirthPlaceCode::from('h501')->value())->toBe('H501');
});

test('isForeign() is true only for Z-prefixed codes', function () {
    expect(BirthPlaceCode::from('H501')->isForeign())->toBeFalse()
        ->and(BirthPlaceCode::from('Z404')->isForeign())->toBeTrue();
});

test('from() throws for malformed input', function (string $code) {
    expect(fn () => BirthPlaceCode::from($code))->toThrow(InvalidBirthPlaceCodeException::class);
})->with([
    'too short' => ['H50'],
    'too long' => ['H5011'],
    'digit prefix instead of letter' => ['1501'],
    'omocodia letter instead of digit' => ['H5L1'],
]);

test('tryFrom() returns null instead of throwing for the same malformed input', function () {
    expect(BirthPlaceCode::tryFrom('H50'))->toBeNull();
});

test('equals() compares by value, not identity', function () {
    $a = BirthPlaceCode::from('H501');
    $b = BirthPlaceCode::from('h501');
    $c = BirthPlaceCode::from('Z404');

    expect($a->equals($b))->toBeTrue()
        ->and($a->equals($c))->toBeFalse();
});
