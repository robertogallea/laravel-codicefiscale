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

/**
 * Modelled on the real 1974-09-18 merger that created Senale-San
 * Felice (BZ, cadastral code I603) from San Felice (H837, itself
 * instituted 1948-03-14), per ANPR's comuni archive. The two eras
 * belong to *different* BirthPlaceCodes: H837 ended the day I603
 * began. ISTAT codes are those of the real comuni.
 */
function sanFeliceBeforeMerger(): DomesticBirthPlace
{
    return new DomesticBirthPlace(
        code: BirthPlaceCode::from('H837'),
        name: 'SAN FELICE',
        province: 'BZ',
        istatCode: '021081',
        validFrom: new DateTimeImmutable('1948-03-14'),
        validTo: new DateTimeImmutable('1974-09-18'),
    );
}

function senaleSanFelice(): DomesticBirthPlace
{
    return new DomesticBirthPlace(
        code: BirthPlaceCode::from('I603'),
        name: 'SENALE-SAN FELICE',
        province: 'BZ',
        istatCode: '021094',
        validFrom: new DateTimeImmutable('1974-09-18'),
        validTo: null,
    );
}
