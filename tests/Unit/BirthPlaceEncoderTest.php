<?php

use Robertogallea\CodiceFiscale\Data\BirthPlaceCode;
use Robertogallea\CodiceFiscale\Generation\BirthPlaceEncoder;

test('encodes a birthplace code as its 4-character value', function () {
    expect((new BirthPlaceEncoder())->encode(BirthPlaceCode::from('H501')))->toBe('H501');
});
