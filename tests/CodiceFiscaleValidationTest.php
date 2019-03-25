<?php

use robertogallea\LaravelCodiceFiscale\CodiceFiscale;

class CodiceFiscaleValidationTest extends PHPUnit_Framework_TestCase
{
    public function testCodiceFiscaleNull()
    {
        $codice_fiscale = null;
        $cf = new CodiceFiscale();
        $res = $cf->parse($codice_fiscale);
        $this->assertFalse($res);

        $code = $cf->getError();
        $this->assertEquals($code, CodiceFiscale::NO_CODE);
    }

    public function testCodiceFiscaleTooShort()
    {
        $codice_fiscale = 'ABC';
        $cf = new CodiceFiscale();
        $res = $cf->parse($codice_fiscale);
        $this->assertFalse($res);

        $code = $cf->getError();
        $this->assertEquals($code, CodiceFiscale::WRONG_SIZE);
    }

    public function testCodiceFiscaleTooLong()
    {
        $codice_fiscale = 'ABCDEF01G23H456IX';
        $cf = new CodiceFiscale();
        $res = $cf->parse($codice_fiscale);
        $this->assertFalse($res);

        $code = $cf->getError();
        $this->assertEquals($code, CodiceFiscale::WRONG_SIZE);
    }

    public function testGoodCode()
    {
        $codice_fiscale = 'RSSMRA95E05F205Z';
        $cf = new CodiceFiscale();
        $res = $cf->parse($codice_fiscale);
        $this->assertEquals($res['birth_place_complete'], 'Milano');

        $code = $cf->getError();
        $this->assertEquals($code, CodiceFiscale::NO_ERROR);
    }


    public function testWrongOmocodiaCode()
    {
        $codice_fiscale = 'RSSMRA95E05F20OU';
        $cf = new CodiceFiscale();
        $res = $cf->parse($codice_fiscale);
        $this->assertEquals($res['birth_place_complete'], null);

        $code = $cf->getError();

        $this->assertEquals($code, CodiceFiscale::BAD_OMOCODIA_CHAR);
    }

    public function testOmocodiaCode()
    {
        $codice_fiscale = 'RSSMRA95E05F20RU';
        $cf = new CodiceFiscale();
        $res = $cf->parse($codice_fiscale);
        $this->assertEquals($res['birth_place_complete'], 'Milano');

        $code = $cf->getError();

        $this->assertEquals($code, CodiceFiscale::NO_ERROR);
    }

    public function testUnregularCode()
    {
        $codice_fiscale = '%SSMRA95E05F20RU';
        $cf = new CodiceFiscale();
        $res = $cf->parse($codice_fiscale);
        $this->assertEquals($res['birth_place_complete'], null);

        $code = $cf->getError();

        $this->assertEquals($code, CodiceFiscale::BAD_CHARACTERS);

    }

    public function testFemaleCode()
    {
        $codice_fiscale = 'RSSMRA95E45F205D';
        $cf = new CodiceFiscale();
        $res = $cf->parse($codice_fiscale);
        $this->assertEquals($res['birth_place_complete'], 'Milano');

        $code = $cf->getError();

        $this->assertEquals($code, CodiceFiscale::NO_ERROR);

    }

}