<?php

use Robertogallea\CodiceFiscale\Data\BirthPlaceCode;
use Robertogallea\CodiceFiscale\Data\DomesticBirthPlace;
use Tests\TestCase;

uses(TestCase::class)->in('Feature');

/**
 * Modelled on the real Abbadia Cerreto municipality (cadastral code
 * A004), which moved from the province of Milano to the newly formed
 * province of Lodi on 1992-04-16, per ANPR's comuni archive. Both
 * eras share the same BirthPlaceCode.
 */
function abbadiaCerretoUnderMilano(): DomesticBirthPlace
{
    return new DomesticBirthPlace(
        code: BirthPlaceCode::from('A004'),
        name: 'ABBADIA CERRETO',
        province: 'MI',
        istatCode: '015001',
        validFrom: new DateTimeImmutable('1861-03-17'),
        validTo: new DateTimeImmutable('1992-04-16'),
    );
}

function abbadiaCerretoUnderLodi(): DomesticBirthPlace
{
    return new DomesticBirthPlace(
        code: BirthPlaceCode::from('A004'),
        name: 'ABBADIA CERRETO',
        province: 'LO',
        istatCode: '098001',
        validFrom: new DateTimeImmutable('1992-04-16'),
        validTo: null,
    );
}
