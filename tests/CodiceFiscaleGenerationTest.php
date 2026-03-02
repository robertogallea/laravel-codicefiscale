<?php

namespace Tests;

use Carbon\Carbon;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use robertogallea\LaravelCodiceFiscale\CodiceFiscale;
use robertogallea\LaravelCodiceFiscale\Exceptions\CodiceFiscaleGenerationException;
use TypeError;
use PHPUnit\Framework\Attributes\DataProvider;

class CodiceFiscaleGenerationTest extends TestCase
{
    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
    public function testNullBirthDate()
    {
        $first_name = 'Mario';
        $last_name = 'Rossi';
        $birth_date = null;
        $birth_place = 'F205';
        $gender = 'M';

        $this->expectException(TypeError::class);
        CodiceFiscale::generate($first_name, $last_name, $birth_date, $birth_place, $gender);
    }

    #[Test]
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

    #[Test]
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

    #[Test]
    public function testValidCityName()
    {
        $first_name = 'Mario';
        $last_name = 'Rossi';
        $birth_date = '1995-05-05';
        $birth_place = 'Milano';
        $gender = 'M';

        $res = CodiceFiscale::generate($first_name, $last_name, $birth_date, $birth_place, $gender);
        $this->assertEquals('RSSMRA95E05F205Z', $res);
    }

    #[Test]
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

    #[Test]
    public function it_uses_custom_decoder_for_codice_fiscale_generation()
    {
        $this->mock(\robertogallea\LaravelCodiceFiscale\CityCodeDecoders\CityDecoderInterface::class, function ($mock) {
            $mock->shouldReceive('getList')
                ->once()
                ->andReturn(['A001' => 'Test']);
        });

        $first_name = 'Ma';
        $last_name = 'Rossi';
        $birth_date = Carbon::parse('1995-05-05');
        $birth_place = 'A001';
        $gender = 'M';

        CodiceFiscale::generate("$first_name", $last_name, $birth_date, $birth_place, $gender);
    }

    #[Test]
    public function it_returns_valid_as_boolean()
    {
        $cf = new CodiceFiscale();
        $cf->parse('RSSMRA95E05F205Z');

        $this->assertIsBool($cf->isValid());
    }

    #[Test]
    public function it_returns_gender_as_char()
    {
        $cf = new CodiceFiscale();
        $cf->parse('RSSMRA95E05F205Z');

        $this->assertIsString($cf->getGender());
        $this->assertEquals(1, strlen($cf->getGender()));
    }

    #[Test]
    public function it_returns_birthplace_as_string()
    {
        $cf = new CodiceFiscale();
        $cf->parse('RSSMRA95E05F205Z');

        $this->assertIsString($cf->getBirthPlace());
    }

    #[Test]
    public function it_returns_birthdate_as_date()
    {
        $cf = new CodiceFiscale();
        $cf->parse('RSSMRA95E05F205Z');

        $this->assertInstanceOf(Carbon::class, $cf->getBirthDate());
    }

    #[Test
    ]
    public function it_returns_year_as_string()
    {
        $cf = new CodiceFiscale();
        $cf->parse('RSSMRA95E05F205Z');

        $this->assertIsString($cf->getYear());
    }

    #[Test]
    public function it_returns_month_as_string()
    {
        $cf = new CodiceFiscale();
        $cf->parse('RSSMRA95E05F205Z');

        $this->assertIsString($cf->getMonth());
    }

    #[Test]
    public function it_returns_day_as_string()
    {
        $cf = new CodiceFiscale();
        $cf->parse('RSSMRA95E05F205Z');

        $this->assertIsString($cf->getDay());
    }

    #[Test
    ]
    public function it_returns_codice_fiscale_as_string()
    {
        $cf = new CodiceFiscale();
        $cf->parse('RSSMRA95E05F205Z');

        $this->assertIsString($cf->getDay());
        $this->assertEquals('RSSMRA95E05F205Z', $cf->getCodiceFiscale());
    }

    public function getPackageProviders($application): array
    {
        return [
            \robertogallea\LaravelCodiceFiscale\CodiceFiscaleServiceProvider::class,
        ];
    }

    #[Test]
    #[DataProvider('nameSurnameBlocksProvider')]
    public function it_generates_correct_name_and_surname_blocks(string $firstName, string $lastName, Carbon $birthDate, string $expectedCf): void
    {
        $birth_place = 'F205'; // Milano
        $gender = 'M';

        $res = CodiceFiscale::generate($firstName, $lastName, $birthDate, $birth_place, $gender);

        $this->assertEquals($expectedCf, $res);
    }

    public static function nameSurnameBlocksProvider(): array
    {
        return [
            // ASH -> SHA, OS -> SOX
            ['ASH', 'OS', Carbon::parse('1990-01-01'), 'SOXSHA90A01F205B'],
            // MARIO -> MRA, OM -> MOX
            ['MARIO', 'OM', Carbon::parse('1990-01-01'), 'MOXMRA90A01F205S'],
            // IO -> IOX, ROSSI -> RSS
            ['IO', 'ROSSI', Carbon::parse('1990-01-01'), 'RSSIOX90A01F205V'],
            // nome corto: MA -> MAX
            ['Ma', 'Rossi', Carbon::parse('1995-05-05'), 'RSSMAX95E05F205P'],
        ];
    }
}
