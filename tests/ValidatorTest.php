<?php

namespace Tests;

use PHPUnit\Framework\Attributes\Test;
use robertogallea\LaravelCodiceFiscale\CodiceFiscaleServiceProvider;

class ValidatorTest extends TestCase
{
    #[Test]
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

    #[Test]
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

    #[Test]
    public function it_doesnt_validate_if_city_code_does_not_exist()
    {
        $rules = [
            'cf_field' => 'codice_fiscale',
        ];

        $data = [
            'cf_field' => 'LNEGLI94D20A000X',
        ];

        $validator = $this->app['validator']->make($data, $rules);
        $this->assertEquals(false, $validator->passes());
    }

    #[Test]
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

    #[Test]
    public function it_can_require_cf_validation_against_form_fields_and_dont_pass_if_field_is_empty()
    {
        $rules = [
            'cf_field' => 'codice_fiscale:first_name=first_name,last_name=last_name,birthdate=birthdate,place=place,gender=gender',
        ];

        $data = [
            'cf_field' => 'RSSMRA80A01F205X',
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
            'birthdate' => null,
            'place' => 'Milano',
            'gender' => 'M',
        ];

        $validator = $this->app['validator']->make($data, $rules);
        $this->assertEquals(false, $validator->passes());
        $this->assertEquals(true, $validator->errors()->has('cf_field'));
    }

    #[Test]
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
        $this->assertEquals(true, $validator->errors()->has('cf_field'));
    }

    #[Test]
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
        $this->assertEquals(true, $validator->errors()->has('cf_field'));
    }

    #[Test]
    public function it_can_require_cf_validation_against_form_fields_and_dont_pass_if_some_parameters_are_missing()
    {
        $rules = [
            'cf_field' => 'codice_fiscale:first_name=first_name,last_name=last_name,birthdate=birthdate,place=place',
        ];

        $data = [
            'cf_field'   => 'RSSMRA80A01F205X',
            'first_name' => 'Mario',
            'last_name'  => 'Rossi',
            'birthdate'  => '1980-01-01',
            'place'      => 'Milano',
        ];

        $validator = $this->app['validator']->make($data, $rules);
        $this->assertEquals(false, $validator->passes());
        $this->assertEquals(true, $validator->errors()->has('cf_field'));
    }

    #[Test]
    public function testValidationRequiresCorrectCfAgainstFormFieldsAndFailsOnWrongFirstName()
    {
        $rules = [
            'cf_field' => 'codice_fiscale:first_name=first_name,last_name=last_name,birthdate=birthdate,place=place,gender=gender',
        ];
        $data = [
            'cf_field' => 'RSSMRA80A01F205X',
            'first_name' => 'wrong_first_name',
            'last_name' => 'Rossi',
            'birthdate' => '1980-01-01',
            'place' => 'Milano',
            'gender' => 'M',
        ];
        $expectedErrorMessage = trans('codicefiscale::validation.wrong_first_name', ['attribute' => 'cf field']);
        $validator = $this->app['validator']->make($data, $rules);
        $this->assertFalse($validator->passes());
        $errorMessages = $validator->errors()->get('cf_field');
        $this->assertSame($expectedErrorMessage, $errorMessages[0]);
    }

    #[Test]
    public function testValidationRequiresCorrectCfAgainstFormFieldsAndFailsOnWrongLastName()
    {
        $rules = [
            'cf_field' => 'codice_fiscale:first_name=first_name,last_name=last_name,birthdate=birthdate,place=place,gender=gender',
        ];
        $data = [
            'cf_field' => 'RSSMRA80A01F205X',
            'first_name' => 'Mario',
            'last_name' => 'wrong_last_name',
            'birthdate' => '1980-01-01',
            'place' => 'Milano',
            'gender' => 'M',
        ];
        $expectedErrorMessage = trans('codicefiscale::validation.wrong_last_name', ['attribute' => 'cf field']);
        $validator = $this->app['validator']->make($data, $rules);
        $this->assertFalse($validator->passes());
        $errorMessages = $validator->errors()->get('cf_field');
        $this->assertSame($expectedErrorMessage, $errorMessages[0]);
    }

    #[Test]
    public function testValidationRequiresCorrectCfAgainstFormFieldsAndFailsOnWrongBirthDay()
    {
        $rules = [
            'cf_field' => 'codice_fiscale:first_name=first_name,last_name=last_name,birthdate=birthdate,place=place,gender=gender',
        ];
        $data = [
            'cf_field' => 'RSSMRA80A01F205X',
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
            'birthdate' => '1980-01-08', //wrong day
            'place' => 'Milano',
            'gender' => 'M',
        ];
        $expectedErrorMessage = trans('codicefiscale::validation.wrong_birth_day', ['attribute' => 'cf field']);
        $validator = $this->app['validator']->make($data, $rules);
        $this->assertFalse($validator->passes());
        $errorMessages = $validator->errors()->get('cf_field');
        $this->assertSame($expectedErrorMessage, $errorMessages[0]);
    }

    #[Test]
    public function testValidationRequiresCorrectCfAgainstFormFieldsAndFailsOnWrongBirthMonth()
    {
        $rules = [
            'cf_field' => 'codice_fiscale:first_name=first_name,last_name=last_name,birthdate=birthdate,place=place,gender=gender',
        ];
        $data = [
            'cf_field' => 'RSSMRA80A01F205X',
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
            'birthdate' => '1980-06-01', //wrong month
            'place' => 'Milano',
            'gender' => 'M',
        ];
        $expectedErrorMessage = trans('codicefiscale::validation.wrong_birth_month', ['attribute' => 'cf field']);
        $validator = $this->app['validator']->make($data, $rules);
        $this->assertFalse($validator->passes());
        $errorMessages = $validator->errors()->get('cf_field');
        $this->assertSame($expectedErrorMessage, $errorMessages[0]);
    }

    #[Test]
    public function testValidationRequiresCorrectCfAgainstFormFieldsAndFailsOnWrongBirthYear()
    {
        $rules = [
            'cf_field' => 'codice_fiscale:first_name=first_name,last_name=last_name,birthdate=birthdate,place=place,gender=gender',
        ];
        $data = [
            'cf_field' => 'RSSMRA80A01F205X',
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
            'birthdate' => '1999-01-01', //wrong year
            'place' => 'Milano',
            'gender' => 'M',
        ];
        $expectedErrorMessage = trans('codicefiscale::validation.wrong_birth_year', ['attribute' => 'cf field']);
        $validator = $this->app['validator']->make($data, $rules);
        $this->assertFalse($validator->passes());
        $errorMessages = $validator->errors()->get('cf_field');
        $this->assertSame($expectedErrorMessage, $errorMessages[0]);
    }

    #[Test]
    public function testValidationRequiresCorrectCfAgainstFormFieldsAndFailsOnWrongPlace()
    {
        $rules = [
            'cf_field' => 'codice_fiscale:first_name=first_name,last_name=last_name,birthdate=birthdate,place=place,gender=gender',
        ];
        $data = [
            'cf_field' => 'RSSMRA80A01F205X',
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
            'birthdate' => '1980-01-01',
            'place' => 'Palermo', //wrong place
            'gender' => 'M',
        ];
        $expectedErrorMessage = trans('codicefiscale::validation.wrong_birth_place', ['attribute' => 'cf field']);
        $validator = $this->app['validator']->make($data, $rules);
        $this->assertFalse($validator->passes());
        $errorMessages = $validator->errors()->get('cf_field');
        $this->assertSame($expectedErrorMessage, $errorMessages[0]);
    }

    #[Test]
    public function testValidationRequiresCorrectCfAgainstFormFieldsAndFailsOnWrongGender()
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
            'gender' => 'wrong_gender', //wrong gender
        ];
        $expectedErrorMessage = trans('codicefiscale::validation.wrong_gender', ['attribute' => 'cf field']);
        $validator = $this->app['validator']->make($data, $rules);
        $this->assertFalse($validator->passes());
        $errorMessages = $validator->errors()->get('cf_field');
        $this->assertSame($expectedErrorMessage, $errorMessages[0]);
    }

    protected function getPackageProviders($app): array
    {
        return [
            CodiceFiscaleServiceProvider::class,
        ];
    }
}
