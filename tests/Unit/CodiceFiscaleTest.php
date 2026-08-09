<?php

use Robertogallea\CodiceFiscale\CodiceFiscale;
use Robertogallea\CodiceFiscale\Exceptions\InvalidCodiceFiscaleException;
use Robertogallea\CodiceFiscale\Omocodia\Omocodia;

test('from() constructs a value object exposing the given code', function () {
    $cf = CodiceFiscale::from('RSSMRA95E05F205Z');

    expect($cf->value())->toBe('RSSMRA95E05F205Z');
});

test('from() accepts other structurally well-formed real fixtures', function (string $code) {
    expect(CodiceFiscale::from($code)->value())->toBe($code);
})->with([
    'omocodia variant' => ['RSSMRA95E05F20RU'],
    'omocodia variant, multiple positions substituted' => ['MKJRLA80A01L4L7I'],
    'female code (day + 40)' => ['RSSMRA95E45F205D'],
    'international birthplace' => ['RBRRHR93L09Z357P'],
    'wrong checksum, still structurally fine' => ['RSSMRA95E05F205A'],
    'nonsense birthplace code, still structurally fine' => ['LNEGLI94D20A000X'],
    'nonexistent date (day 77), still structurally fine' => ['LOIMLC71A77F979V'],
]);

test('from() normalizes lowercase input', function () {
    expect(CodiceFiscale::from('rssmra95e05f205z')->value())->toBe('RSSMRA95E05F205Z');
});

test('from() throws for malformed input', function (string $code) {
    expect(fn () => CodiceFiscale::from($code))->toThrow(InvalidCodiceFiscaleException::class);
})->with([
    'too short' => ['ABC'],
    'too long' => ['ABCDEF01G23H456IX'],
    'contains a non-alphanumeric character' => ['%SSMRA95E05F20RU'],
    'invalid omocodia character (O is not a valid substitution letter)' => ['RSSMRA95E05F20OU'],
    'invalid month letter' => ['RSSMRA95Z05F205Z'],
]);

test('tryFrom() returns null for the same malformed input instead of throwing', function (string $code) {
    expect(CodiceFiscale::tryFrom($code))->toBeNull();
})->with([
    'too short' => ['ABC'],
    'too long' => ['ABCDEF01G23H456IX'],
    'contains a non-alphanumeric character' => ['%SSMRA95E05F20RU'],
    'invalid omocodia character' => ['RSSMRA95E05F20OU'],
    'invalid month letter' => ['RSSMRA95Z05F205Z'],
]);

test('has no dependency on checksum validity', function () {
    // RSSMRA95E05F205A has an incorrect check character (the real one is Z).
    // Calling from() directly (rather than via toThrow()) means this test
    // fails loudly with an uncaught exception if construction ever starts
    // rejecting it — checksum correctness is exclusively the Validator's
    // concern, not the value object's.
    expect(CodiceFiscale::from('RSSMRA95E05F205A')->value())->toBe('RSSMRA95E05F205A');
});

test('casts to string as its value', function () {
    $cf = CodiceFiscale::from('RSSMRA95E05F205Z');

    expect((string) $cf)->toBe('RSSMRA95E05F205Z');
});

test('isEquivalentTo() is true for a canonical code and its own omocodia variant', function () {
    $canonical = CodiceFiscale::from('RSSMRA95E05F205Z');
    $variant = CodiceFiscale::from('RSSMRA95E05F20RU');

    expect($canonical->isEquivalentTo($variant))->toBeTrue()
        ->and($variant->isEquivalentTo($canonical))->toBeTrue();
});

test('isEquivalentTo() is true between a canonical code and every one of its 128 variants', function () {
    $canonical = CodiceFiscale::from('RSSMRA95E05F205Z');
    $omocodia = new Omocodia();

    $variants = iterator_to_array($omocodia->variants($canonical));
    expect($variants)->toHaveCount(128);

    foreach ($variants as $variant) {
        expect($canonical->isEquivalentTo($variant))->toBeTrue()
            ->and($variant->isEquivalentTo($canonical))->toBeTrue();
    }
});

test('isEquivalentTo() is true for a code compared to itself', function () {
    $cf = CodiceFiscale::from('RSSMRA95E05F205Z');

    expect($cf->isEquivalentTo($cf))->toBeTrue();
});

test('isEquivalentTo() is false for an unrelated code', function () {
    $mario = CodiceFiscale::from('RSSMRA95E05F205Z');
    $unrelated = CodiceFiscale::from('RBRRHR93L09Z357P');

    expect($mario->isEquivalentTo($unrelated))->toBeFalse();
});
