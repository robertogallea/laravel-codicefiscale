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

/**
 * A copy of a real xlsx fixture with one shared string edited - not a
 * fabricated file format, just a modified copy of the real one.
 */
function xlsxWithReplacedSharedString(string $originalPath, string $search, string $replace): string
{
    $tempFile = tempnam(sys_get_temp_dir(), 'xlsx');
    copy($originalPath, $tempFile);

    $zip = new ZipArchive();
    $zip->open($tempFile);
    $sharedStrings = $zip->getFromName('xl/sharedStrings.xml');
    $zip->addFromString('xl/sharedStrings.xml', str_replace($search, $replace, $sharedStrings));
    $zip->close();

    $contents = file_get_contents($tempFile);
    unlink($tempFile);

    return $contents;
}

/**
 * A codes-only v2-parity fixture (tests/Fixtures/v2-parity), one code
 * per line, sorted.
 *
 * @return list<string>
 */
function v2ParityCodes(string $file): array
{
    $codes = file(__DIR__.'/Fixtures/v2-parity/'.$file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    sort($codes);

    return $codes;
}

/**
 * 2.3.1 codes 3.x does not resolve yet, each owned by the open issue
 * that will close the gap - code => "#issue".
 *
 * @return array<string, string>
 */
function v2ParityKnownGaps(): array
{
    $gaps = [];

    foreach (v2ParityCodes('known-gaps.txt') as $line) {
        [$code, $issue] = explode(' ', $line);
        $gaps[$code] = $issue;
    }

    return $gaps;
}
