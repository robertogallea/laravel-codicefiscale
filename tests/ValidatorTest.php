<?php

namespace Tests;

use Orchestra\Testbench\TestCase;
use robertogallea\LaravelCodiceFiscale\CodiceFiscaleServiceProvider;

class ValidatorTest extends TestCase
{
    /** @test */
    public function it_validates_good_codice_fiscale()
    {
        $rules = [
            'cf_field' => 'codice_fiscale',
        ];

        $data = [
            'cf_field' => 'RSSMRA95E05F205Z',
        ];

        $validator = $this->app['validator']->make($data, $rules);
        $this->assertEquals(true, $validator->passes());
    }

    /** @test */
    public function it_doesnt_validate_wrong_codice_fiscale()
    {
        $rules = [
            'cf_field' => 'codice_fiscale',
        ];

        $data = [
            'cf_field' => 'RSSMRA95E05F205*',
        ];

        $validator = $this->app['validator']->make($data, $rules);
        $this->assertEquals(false, $validator->passes());
    }

    /** @test */
    public function it_doesnt_validate_if_city_code_does_not_exist()
    {
        $rules = [
            'cf_field' => 'codice_fiscale',
        ];

        $data = [
            'cf_field' => 'LNEGLI94D20A009X',
        ];

        $validator = $this->app['validator']->make($data, $rules);
        $this->assertEquals(false, $validator->passes());
    }

    /** @test */
    public function it_can_require_cf_validation_against_form_fields_and_pass()
    {
        $rules = [
            'cf_field' => 'codice_fiscale:first_name=first_name,last_name=last_name,birthdate=birthdate,place=place,gender=gender',
        ];

        $data = [
            'cf_field' => 'RSSMRA80A01F205X',
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
            'birthdate' => '1980-01-01',
            'place' => 'Milano',
            'gender' => 'M',
        ];

        $validator = $this->app['validator']->make($data, $rules);
        $this->assertEquals(true, $validator->passes());
    }

    /** @test */
    public function it_can_require_cf_validation_against_form_fields_and_dont_pass_if_field_doesnt_match()
    {
        $rules = [
            'cf_field' => 'codice_fiscale:first_name=first_name,last_name=last_name,birthdate=birthdate,place=place,gender=gender',
        ];

        $data = [
            'cf_field' => 'RSSMRA80A01F205X',
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
            'birthdate' => '1980-01-01',
            'place' => 'PALERMO',
            'gender' => 'M',
        ];

        $validator = $this->app['validator']->make($data, $rules);
        $this->assertEquals(false, $validator->passes());
    }

    /** @test */
    public function it_can_require_cf_validation_against_form_fields_and_dont_pass_if_field_doesnt_exist()
    {
        $rules = [
            'cf_field' => 'codice_fiscale:first_name=first_name,last_name=last_name,birthdate=birthdate,place=place,gender=gender',
        ];

        $data = [
            'cf_field' => 'RSSMRA80A01F205X',
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
            'birthdate' => '1980-01-01',
            'place' => 'Milano',
        ];

        $validator = $this->app['validator']->make($data, $rules);
        $this->assertEquals(false, $validator->passes());
    }

    /** @test */
    public function it_can_require_cf_validation_against_form_fields_and_dont_pass_if_some_parameters_are_missing()
    {
        $rules = [
            'cf_field' => 'codice_fiscale:first_name=first_name,last_name=last_name,birthdate=birthdate,place=place',
        ];

        $data = [
            'cf_field' => 'RSSMRA80A01F205X',
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
            'birthdate' => '1980-01-01',
            'place' => 'Milano',
        ];

        $validator = $this->app['validator']->make($data, $rules);
        $this->assertEquals(false, $validator->passes());
    }

    protected function getPackageProviders($app)
    {
        return [
            CodiceFiscaleServiceProvider::class,
        ];
    }
}
