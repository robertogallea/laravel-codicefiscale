<?php

use Robertogallea\CodiceFiscale\Generation\Checksum;

test('calculates the known check character for a canonical code', function () {
    expect((new Checksum())->calculate('RSSMRA95E05F205'))->toBe('Z');
});

test('calculates the known check character for other real fixtures', function (string $first15, string $expectedCheckCharacter) {
    expect((new Checksum())->calculate($first15))->toBe($expectedCheckCharacter);
})->with([
    'omocodia variant (day substituted)' => ['RSSMRA95E05F20R', 'U'],
    'omocodia variant (multiple positions substituted)' => ['MKJRLA80A01L4L7', 'I'],
    'female code (day + 40)' => ['RSSMRA95E45F205', 'D'],
    'international birthplace' => ['RBRRHR93L09Z357', 'P'],
    'short name padded with X' => ['RSSMAX95E05F205', 'P'],
]);

test('verify confirms a correct check character and rejects an incorrect one', function () {
    $checksum = new Checksum();

    expect($checksum->verify('RSSMRA95E05F205Z'))->toBeTrue()
        ->and($checksum->verify('RSSMRA95E05F205A'))->toBeFalse();
});
