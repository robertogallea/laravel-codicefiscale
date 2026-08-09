<?php

use Robertogallea\CodiceFiscale\Data\CountryCode;
use Robertogallea\CodiceFiscale\Exceptions\InvalidCountryCodeException;

test('from() accepts a real ISO 3166-1 alpha-3 code', function () {
    expect(CountryCode::from('USA')->value())->toBe('USA');
});

test('from() normalizes lowercase input', function () {
    expect(CountryCode::from('usa')->value())->toBe('USA');
});

test('from() throws for malformed input', function (string $code) {
    expect(fn () => CountryCode::from($code))->toThrow(InvalidCountryCodeException::class);
})->with([
    'too short' => ['US'],
    'too long' => ['USAA'],
    'contains a digit' => ['US1'],
]);

test('tryFrom() returns null instead of throwing for the same malformed input', function () {
    expect(CountryCode::tryFrom('US'))->toBeNull();
});

test('equals() compares by value, not identity', function () {
    $a = CountryCode::from('USA');
    $b = CountryCode::from('usa');
    $c = CountryCode::from('FRA');

    expect($a->equals($b))->toBeTrue()
        ->and($a->equals($c))->toBeFalse();
});
