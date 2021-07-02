<?php

namespace Tests;

use Carbon\Carbon;
use InvalidArgumentException;
use Mockery;
use Orchestra\Testbench\TestCase;
use robertogallea\LaravelCodiceFiscale\CodiceFiscale;
use robertogallea\LaravelCodiceFiscale\Exceptions\CodiceFiscaleGenerationException;
use TypeError;

class CodiceFiscaleGenerationTest extends TestCase
{
    public function testNullFirstName()
    {
        $first_name = null;
        $last_name = 'Rossi';
        $birth_date = '1995-05-05';
        $birth_place = 'F205';
        $gender = 'M';

        $this->expectException(TypeError::class);
        CodiceFiscale::generate($first_name, $last_name, $birth_date, $birth_place, $gender);
    }

    public function testEmptyFirstName()
    {
        $first_name = '';
        $last_name = 'Rossi';
        $birth_date = '1995-05-05';
        $birth_place = 'F205';
        $gender = 'M';

        $this->expectException(CodiceFiscaleGenerationException::class);
        CodiceFiscale::generate($first_name, $last_name, $birth_date, $birth_place, $gender);
    }

    public function testNullLastName()
    {
        $first_name = 'Mario';
        $last_name = null;
        $birth_date = '1995-05-05';
        $birth_place = 'F205';
        $gender = 'M';

        $this->expectException(TypeError::class);
        CodiceFiscale::generate($first_name, $last_name, $birth_date, $birth_place, $gender);
    }

    public function testEmptyLastName()
    {
        $first_name = 'Mario';
        $last_name = '';
        $birth_date = '1995-05-05';
        $birth_place = 'F205';
        $gender = 'M';

        $this->expectException(CodiceFiscaleGenerationException::class);
        CodiceFiscale::generate($first_name, $last_name, $birth_date, $birth_place, $gender);
    }

    public function testNullBirthDate()
    {
        $first_name = 'Mario';
        $last_name = 'Rossi';
        $birth_date = null;
        $birth_place = 'F205';
        $gender = 'M';

        $this->expectException(InvalidArgumentException::class);
        CodiceFiscale::generate($first_name, $last_name, $birth_date, $birth_place, $gender);
    }

    public function testInvalidCityCode()
    {
        $first_name = 'Mario';
        $last_name = 'Rossi';
        $birth_date = '1995-05-05';
        $birth_place = 'AB123';
        $gender = 'M';

        $this->expectException(InvalidArgumentException::class);
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

    /** @test */
    public function it_uses_custom_decoder_for_codice_fiscale_generation()
    {
        $mock = Mockery::mock(\robertogallea\LaravelCodiceFiscale\CityCodeDecoders\CityDecoderInterface::class, function ($mock) {
            $mock->shouldReceive('getList')
                ->once()
                ->andReturn(['A001' => 'Test']);
        });

        \Illuminate\Support\Facades\Config::set('codicefiscale.city-decoder', get_class($mock));

        $first_name = 'Ma';
        $last_name = 'Rossi';
        $birth_date = Carbon::parse('1995-05-05');
        $birth_place = 'A001';
        $gender = 'M';

        $res = CodiceFiscale::generate("$first_name", $last_name, $birth_date, $birth_place, $gender);
    }

    public function getPackageProviders($application)
    {
        return [
            \robertogallea\LaravelCodiceFiscale\CodiceFiscaleServiceProvider::class,
        ];
    }
}
