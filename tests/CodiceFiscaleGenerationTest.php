<?php
/**
 * Created by PhpStorm.
 * User: Roberto Gallea
 * Date: 25/03/2019
 * Time: 08:13
 */

use \robertogallea\LaravelCodiceFiscale\CodiceFiscale;
use \robertogallea\LaravelCodiceFiscale\Exceptions\CodiceFiscaleException;
use \robertogallea\LaravelCodiceFiscale\Exceptions\CodiceFiscaleGenerationException;
use Carbon\Carbon;

class CodiceFiscaleGenerationTest extends PHPUnit_Framework_TestCase
{
    public function testNullFirstName()
    {
        $first_name = null;
        $last_name = 'Rossi';
        $birth_date = '1995-05-05';
        $birth_place = 'F205';
        $gender = 'M';

        $this->setExpectedException(TypeError::class);
        CodiceFiscale::generate($first_name, $last_name, $birth_date, $birth_place, $gender);
    }

    public function testEmptyFirstName()
    {
        $first_name = '';
        $last_name = 'Rossi';
        $birth_date = '1995-05-05';
        $birth_place = 'F205';
        $gender = 'M';

        $this->setExpectedException(CodiceFiscaleGenerationException::class);
        CodiceFiscale::generate($first_name, $last_name, $birth_date, $birth_place, $gender);
    }

    public function testNullLastName()
    {
        $first_name = 'Mario';
        $last_name = null;
        $birth_date = '1995-05-05';
        $birth_place = 'F205';
        $gender = 'M';

        $this->setExpectedException(TypeError::class);
        CodiceFiscale::generate($first_name, $last_name, $birth_date, $birth_place, $gender);
    }

    public function testEmptyLastName()
    {
        $first_name = 'Mario';
        $last_name = '';
        $birth_date = '1995-05-05';
        $birth_place = 'F205';
        $gender = 'M';

        $this->setExpectedException(CodiceFiscaleGenerationException::class);
        CodiceFiscale::generate($first_name, $last_name, $birth_date, $birth_place, $gender);
    }

    public function testNullBirthDate()
    {
        $first_name = 'Mario';
        $last_name = 'Rossi';
        $birth_date = null;
        $birth_place = 'F205';
        $gender = 'M';

        $this->setExpectedException(InvalidArgumentException::class);
        CodiceFiscale::generate($first_name, $last_name, $birth_date, $birth_place, $gender);
    }

    public function testInvalidCityCode()
    {
        $first_name = 'Mario';
        $last_name = 'Rossi';
        $birth_date = '1995-05-05';
        $birth_place = 'AB123';
        $gender = 'M';

        $this->setExpectedException(InvalidArgumentException::class);
        CodiceFiscale::generate($first_name, $last_name, $birth_date, $birth_place, $gender);
    }

    public function testValidCityCode()
    {
        $first_name = 'Mario';
        $last_name = 'Rossi';
        $birth_date = '1995-05-05';
        $birth_place = 'F205';
        $gender = 'M';

        $res = CodiceFiscale::generate($first_name, $last_name, $birth_date, $birth_place, $gender);
        $this->assertEquals('RSSMRA95E05F205Z', $res);
    }

    public function testValidCityName()
    {
        $first_name = 'Mario';
        $last_name = 'Rossi';
        $birth_date = '1995-05-05';
        $birth_place = 'F205';
        $gender = 'M';

        $res = CodiceFiscale::generate($first_name, $last_name, $birth_date, $birth_place, $gender);
        $this->assertEquals('RSSMRA95E05F205Z', $res);
    }

    public function testShortFirstName()
    {
        $first_name = 'Ma';
        $last_name = 'Rossi';
        $birth_date = Carbon::parse('1995-05-05');
        $birth_place = 'F205';
        $gender = 'M';

        $res = CodiceFiscale::generate($first_name, $last_name, $birth_date, $birth_place, $gender);
        $this->assertEquals('RSSMAX95E05F205P', $res);

    }



}
