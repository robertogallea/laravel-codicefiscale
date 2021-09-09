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

    /** @test */
    public function it_returns_valid_as_boolean()
    {
        $cf = new CodiceFiscale();
        $cf->parse('RSSMRA95E05F205Z');

        $this->assertIsBool($cf->isValid());
    }

    /** @test */
    public function it_returns_gender_as_char()
    {
        $cf = new CodiceFiscale();
        $cf->parse('RSSMRA95E05F205Z');

        $this->assertIsString($cf->getGender());
        $this->assertEquals(1, strlen($cf->getGender()));
    }

    /** @test */
    public function it_returns_birthplace_as_string()
    {
        $cf = new CodiceFiscale();
        $cf->parse('RSSMRA95E05F205Z');

        $this->assertIsString($cf->getBirthPlace());
    }

    /** @test */
    public function it_returns_birthdate_as_date()
    {
        $cf = new CodiceFiscale();
        $cf->parse('RSSMRA95E05F205Z');

        $this->assertInstanceOf(Carbon::class, $cf->getBirthDate());
    }

    /** @test */
    public function it_returns_year_as_string()
    {
        $cf = new CodiceFiscale();
        $cf->parse('RSSMRA95E05F205Z');

        $this->assertIsString($cf->getYear());
    }

    /** @test */
    public function it_returns_month_as_string()
    {
        $cf = new CodiceFiscale();
        $cf->parse('RSSMRA95E05F205Z');

        $this->assertIsString($cf->getMonth());
    }

    /** @test */
    public function it_returns_day_as_string()
    {
        $cf = new CodiceFiscale();
        $cf->parse('RSSMRA95E05F205Z');

        $this->assertIsString($cf->getDay());
    }

    /** @test */
    public function it_returns_codice_fiscale_as_string()
    {
        $cf = new CodiceFiscale();
        $cf->parse('RSSMRA95E05F205Z');

        $this->assertIsString($cf->getDay());
        $this->assertEquals('RSSMRA95E05F205Z', $cf->getCodiceFiscale());
    }

    public function getPackageProviders($application)
    {
        return [
            \robertogallea\LaravelCodiceFiscale\CodiceFiscaleServiceProvider::class,
        ];
    }
}
