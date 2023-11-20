<?php

namespace Tests;

use robertogallea\LaravelCodiceFiscale\CityCodeDecoders\InternationalCitiesStaticList;
use robertogallea\LaravelCodiceFiscale\CodiceFiscale;
use robertogallea\LaravelCodiceFiscale\Exceptions\CodiceFiscaleValidationException;

class CodiceFiscaleValidationTest extends TestCase
{
    public function testCodiceFiscaleNull()
    {
        $codice_fiscale = null;
        $cf = new CodiceFiscale();

        $this->expectException(CodiceFiscaleValidationException::class);
        $res = $cf->parse($codice_fiscale);
    }

    public function testCodiceFiscaleTooShort()
    {
        $codice_fiscale = 'ABC';
        $cf = new CodiceFiscale();

        $this->expectException(CodiceFiscaleValidationException::class);
        $res = $cf->parse($codice_fiscale);
    }

    public function testCodiceFiscaleTooLong()
    {
        $codice_fiscale = 'ABCDEF01G23H456IX';
        $cf = new CodiceFiscale();

        $this->expectException(CodiceFiscaleValidationException::class);
        $res = $cf->parse($codice_fiscale);
    }

    public function testGoodCode()
    {
        $codice_fiscale = 'RSSMRA95E05F205Z';
        $cf = new CodiceFiscale();

        $res = $cf->parse($codice_fiscale);
        $this->assertEquals($res['birth_place_complete'], 'Milano');
    }

    public function testTryParseGoodCode()
    {
        $codice_fiscale = 'RSSMRA95E05F205Z';
        $cf = new CodiceFiscale();

        $res = $cf->tryParse($codice_fiscale);
        $this->assertEquals(true, $res);
        $this->assertEquals(true, $cf->isValid());
        $this->assertEquals(null, $cf->getError());
    }

    public function testTryParseCodiceFiscaleTooShort()
    {
        $codice_fiscale = 'ABC';
        $cf = new CodiceFiscale();

        $res = $cf->tryParse($codice_fiscale);
        $this->assertEquals(false, $res);
        $this->assertEquals(false, $cf->isValid());
        $this->assertInstanceOf(CodiceFiscaleValidationException::class, $cf->getError());
    }

    public function testWrongOmocodiaCode()
    {
        $codice_fiscale = 'RSSMRA95E05F20OU';
        $cf = new CodiceFiscale();

        $this->expectException(CodiceFiscaleValidationException::class);
        $res = $cf->parse($codice_fiscale);
    }

    /**
     * @dataProvider omocodiaProvider
     */
    public function testOmocodiaCode($codice_fiscale, $city)
    {
        $cf = new CodiceFiscale();

        $res = $cf->parse($codice_fiscale);
        $this->assertEquals($res['birth_place_complete'], $city);
    }

    public static function omocodiaProvider()
    {
        return [
            ['RSSMRA95E05F20RU', 'Milano'],
            ['MKJRLA80A01L4L7I', 'Treviso'],
        ];
    }

    public function testUnregularCode()
    {
        $codice_fiscale = '%SSMRA95E05F20RU';
        $cf = new CodiceFiscale();

        $this->expectException(CodiceFiscaleValidationException::class);
        $res = $cf->parse($codice_fiscale);
    }

    public function testFemaleCode()
    {
        $codice_fiscale = 'RSSMRA95E45F205D';
        $cf = new CodiceFiscale();
        $res = $cf->parse($codice_fiscale);
        $this->assertEquals($res['birth_place_complete'], 'Milano');
    }

    public function test_international_fiscalcode()
    {
        $codice_fiscale = 'RBRRHR93L09Z357P';
        $cf = new CodiceFiscale(new InternationalCitiesStaticList());

        $res = $cf->parse($codice_fiscale);
        $this->assertEquals($res['birth_place_complete'], 'Tanzania');
    }

    public function test_wrong_date()
    {
        $codice_fiscale = 'LOIMLC71A77F979V';
        $cf = new CodiceFiscale();

        $this->expectException(CodiceFiscaleValidationException::class);
        $cf->parse($codice_fiscale);
    }

    /** @test */
    public function test_wrong_city_code()
    {
        $codice_fiscale = 'LNEGLI94D20A000X';
        $cf = new CodiceFiscale();

        $this->expectException(CodiceFiscaleValidationException::class);
        $cf->parse($codice_fiscale);
    }
}
