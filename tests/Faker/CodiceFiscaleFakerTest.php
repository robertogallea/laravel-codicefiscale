<?php

namespace Tests\Faker;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use robertogallea\LaravelCodiceFiscale\CodiceFiscale;
use Tests\TestCase;

class CodiceFiscaleFakerTest extends TestCase
{
    use WithFaker;

    #[Test]
    public function it_can_generate_fake_fiscal_numbers()
    {
        $codiceFiscale = $this->faker->codiceFiscale;

        $cf = (new CodiceFiscale());
        $cf->parse($codiceFiscale);
        $this->assertTrue($cf->isValid());
    }

    #[Test]
    public function it_can_generate_fake_fiscal_numbers_with_provided_first_name()
    {
        $firstName = 'Mario';
        $codiceFiscale = $this->faker->codiceFiscale(firstName: $firstName);

        $this->assertEquals('MRA', substr($codiceFiscale, 3, 3));
    }

    #[Test]
    public function it_can_generate_fake_fiscal_numbers_with_provided_last_name()
    {
        $lastName = 'Rossi';
        $codiceFiscale = $this->faker->codiceFiscale(lastName: $lastName);

        $this->assertEquals('RSS', substr($codiceFiscale, 0, 3));
    }

    #[Test]
    #[DataProvider('genders')]
    public function it_can_generate_fake_fiscal_numbers_with_provided_gender(string $gender)
    {
        $codiceFiscale = $this->faker->codiceFiscale(gender: $gender);

        match ($gender) {
            'M' => $this->assertLessThanOrEqual(31, (int) substr($codiceFiscale, 9, 2)),
            'F' => $this->assertGreaterThanOrEqual(32, (int) substr($codiceFiscale, 9, 2)),
        };
    }

    #[Test]
    #[DataProvider('genders')]
    public function it_can_generate_fake_fiscal_numbers_with_provided_birth_date(string $gender)
    {
        $birthDate = Carbon::parse('1990-01-01');
        $codiceFiscale = $this->faker->codiceFiscale(gender: $gender, birthDate: $birthDate);

        $this->assertEquals('90', substr($codiceFiscale, 6, 2));
        $this->assertEquals('A', substr($codiceFiscale, 8, 1));

        $this->assertEquals(match ($gender) {
            'M' => '01',
            'F' => '41',
        }, substr($codiceFiscale, 9, 2));
    }

    #[Test]
    public function it_can_generate_fake_fiscal_numbers_with_provided_birth_place()
    {
        $birthPlace = 'Battipaglia';
        $codiceFiscale = $this->faker->codiceFiscale(birthPlace: $birthPlace);

        $this->assertEquals('A717', substr($codiceFiscale, 11, 4));
    }

    public static function genders()
    {
        return [['M'], ['F']];
    }

    #[Test]
    public function it_can_generate_fake_fiscal_numbers_with_provided_fields()
    {
        $firstName = 'Mario';
        $lastName = 'Rossi';
        $birthDate = '1995-05-05';
        $birthPlace = 'F205';
        $gender = 'M';

        $codiceFiscale = $this->faker->codiceFiscale(
            firstName: $firstName,
            lastName: $lastName,
            birthDate: $birthDate,
            birthPlace: $birthPlace,
            gender: $gender
        );

        $this->assertEquals('RSSMRA95E05F205Z', $codiceFiscale);
    }

    #[Test]
    public function it_can_use_fake_helper()
    {
        $codiceFiscale = fake()->codiceFiscale;

        $cf = (new CodiceFiscale());
        $cf->parse($codiceFiscale);
        $this->assertTrue($cf->isValid());
    }

}
