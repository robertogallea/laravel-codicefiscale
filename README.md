![Laravel Codice Fiscale](https://banners.beyondco.de/Laravel%20Codice%20Fiscale.png?theme=light&packageManager=composer+require&packageName=robertogallea%2Flaravel-codicefiscale&pattern=charlieBrown&style=style_1&description=Codice+fiscale+validation+and+parsing+is+a+breeze&md=1&showWatermark=0&fontSize=100px&images=identification&widths=200&heights=auto)

# laravel-codicefiscale

[![Author][ico-author]][link-author]
[![Latest Version on Packagist](https://img.shields.io/packagist/v/robertogallea/laravel-codicefiscale.svg?style=flat-square)](https://packagist.org/packages/robertogallea/laravel-codicefiscale)
[![Laravel >=6.0][ico-laravel]][link-laravel]
[![Software License][ico-license]](LICENSE.md)
[![Sponsor me!][ico-sponsor]][link-sponsor]
[![Packagist Downloads][ico-downloads]][link-downloads]

laravel-codicefiscale is a package for the management of the Italian `CodiceFiscale` (i.e. tax code).
The package allows easy validation and parsing of the CodiceFiscale. It is also suited for Laravel since it provides a
convenient custom validator for request validation.

## Laravel Version Compatibility

| Laravel | Package |
|---------|---------|
| 12.x    | ^2.2    |
| 11.x    | 2.x     |
| 10.x    | 1.x     |
| 9.x     | 1.x     |
| 8.x     | 1.x     |
| 7.x     | 1.x     |
| 6.x     | 1.x     |

> **Important update**: now you can dynamically load city codes from ISTAT using the non-default `IstatRemoteCSVList`
> city decoder.

- [Installation](#installation)
- [Configuration](#configuration)
- [Validation](#validation)
- [Utility CodiceFiscale class](#utility-codicefiscale-class)
- [Codice fiscale Generation](#codice-fiscale-generation)
- [Faker integration](#faker-integration)
- [City code parsing](#city-code-parsing)
- [Integrate your own cities](#integrate-your-own-cities)

## Installation

Run the following command to install the latest applicable version of the package:

```bash
composer require robertogallea/laravel-codicefiscale:^2
```

### Laravel

In your app config, add the Service Provider to the `$providers` array *(only for Laravel 5.4 or below)*:

 ```php
'providers' => [
    ...
    robertogallea\LaravelCodiceFiscale\CodiceFiscaleServiceProvider::class,
],
```

The validation error messages are translated in `it` and `en` languages, if you want to add new language please send me
a PR.

### Lumen

In `bootstrap/app.php`, register the Service Provider

```php
$app->register(robertogallea\LaravelCodiceFiscale\CodiceFiscaleServiceProvider::class);
```

## Configuration

To customize the package configuration, you must export the configuration file into `config/codicefiscale.php`.

This can be achieved by launching the following command:

```
php artisan vendor:publish --provider="robertogallea\LaravelCodiceFiscale\CodiceFiscaleServiceProvider" --tag="config"
```

You can configure the following parameters:

- `city-decoder`: the class used for decoding city codes (see [City code parsing](#city-code-parsing)), default to
  `InternationalCitiesStaticList`.
- `date-format`: the date format used for parsing birthdates, default to `'Y-m-d'`.
- `labels`: the labels used for `male` and `female` persons, defaults to `'M'` and `'F'`.

## Language Files

You can customize the validation messages publishing the validation translations with this command:

```
php artisan vendor:publish --provider="robertogallea\LaravelCodiceFiscale\CodiceFiscaleServiceProvider" --tag="lang"
```

## Validation

To validate a codice fiscale, use the `codice_fiscale` keyword in your validation rules array

```php
    public function rules()
    {
        return [
            'codicefiscale' => 'codice_fiscale',
            
            //...
        ];
    }
```

From version **1.9.0** you can validate your codice fiscale against other form fields to check whether there is a match
or not.

You must specify all of the required fields:

- `first_name`
- `last_name`
- `birthdate`
- `place`
- `gender`

giving parameters to the `codice_fiscale` rule.

For example:

```php
    public function rules()
    {
        return [
            'codicefiscale' => 'codice_fiscale:first_name=first_name_field,last_name=last_name_field,birthdate=birthdate_field,place=place_field,gender=gender_field',
            'first_name_field' => 'required|string',
            'last_name_field' => 'required|string',
            'birthdate_field' => 'required|date',
            'place_field' => 'required|string',
            'gender_field' => 'required|string|max:1',
            
            //...
        ];
    }
```

Validation fails if the provided codicefiscale and the one generated from the input fields do not match.

## Utility CodiceFiscale class

A codice fiscale can be wrapped in the `robertogallea\LaravelCodiceFiscale\CodiceFiscale` class to enhance it with
useful utility methods.

```php
use robertogallea\LaravelCodiceFiscale\CodiceFiscale;

...
try {
    $cf = new CodiceFiscale();
    $result = $cf->parse('RSSMRA95E05F205Z');
    var_dump($result);
} catch (Exception $exception) {
    echo $exception;
}
```

In case of a valid codicefiscale it produces the following result:

```php
[
  "gender" => "M"
  "birth_place" => "F205"
  "birth_place_complete" => "Milano",
  "day" => "05"
  "month" => "05"
  "year" => "1995"
  "birthdate" => Carbon @799632000 {
    date: 1995-05-05 00:00:00.0 UTC (+00:00)
  }
]
```

in case of an error, `CodiceFiscale::parse()` throws an `CodiceFiscaleValidationException`, which returns one of the
defined constants with `$exception->getCode()`:

- `CodiceFiscaleException::NO_ERROR`
- `CodiceFiscaleException::NO_CODE`
- `CodiceFiscaleException::WRONG_SIZE`
- `CodiceFiscaleException::BAD_CHARACTERS`
- `CodiceFiscaleException::BAD_OMOCODIA_CHAR`
- `CodiceFiscaleException::WRONG_CODE`
- `CodiceFiscaleException::MISSING_CITY_CODE`

If you rather not want to catch exceptions, you can use `CodiceFiscale::tryParse()`:

```php
use robertogallea\LaravelCodiceFiscale\CodiceFiscale;

...
$cf = new CodiceFiscale();
$result = $cf->tryParse('RSSMRA95E05F205Z');
if ($result) {
    var_dump($cf->asArray());
} else {
    echo $cf->getError();
}
```

which returns the same values as above, you can use `$cf->isValid()` to check if the codicefiscale is valid and
`$cf->getError()` to get the error.
This is especially useful in a blade template:

```php
@php($cf = new robertogallea\LaravelCodiceFiscale\CodiceFiscale())
@if($cf->tryParse($codicefiscale))
    <p><i class="fa fa-check" style="color:green"></i>{{$cf->getCodiceFiscale()}}</p>
@else
    <p><i class="fa fa-check" style="color:red"></i>{{$cf->getError()->getMessage()}}</p>
@endif
```

## Codice fiscale Generation

Class <code>CodiceFiscale</code> could be used to generate codice fiscale strings from input values:

```php
$first_name = 'Mario';
$last_name = 'Rossi';
$birth_date = '1995-05-05'; // or Carbon::parse('1995-05-05')
$birth_place = 'F205';      // or 'Milano'
$gender = 'M';

$cf_string = CodiceFiscale::generate($first_name, $last_name, $birth_date, $birth_place, $gender);
```

## Faker integration

You can generate fake codice fiscale in your factories using the provided faker extension:

```php
class PersonFactory extends Factory
{
    
    public function definition(): array
    {
        return [
            'first_name' => $firstName = fake()->firstName(),
            'last_name' => $lastName = fake()->lastName(),
            'fiscal_number' => fake()->codiceFiscale(firstName: $firstName, lastName: $lastName),
        ];
    }
```

**Note**: you can provide some, all or none of the information required for the generation of codice fiscale
(`firstName`, `lastName`, `birthDate`, `birthPlace`, `gender`)

## City code parsing

There are three strategies for decoding the city code:

- `InternationalCitiesStaticList`: a static list of Italian cities;
- `ItalianCitiesStaticList`: a static list of International cities;
- `IstatRemoteCSVList`: a dynamic (loaded from web) list of Italian cities loaded from official ISTAT csv file.
  Please note that the list is cached (one day by default, see config to change).
- `CompositeCitiesList`: merge the results from two `CityDecoderInterface` classes (for example `IstatRemoteCSVList` and
  `InternationalCitiesStaticList`) using the base `CityDecoderInterface` in the config key
  `codicefiscale.cities-decoder-list`.

By default, the package uses the class `InternationalCitiesStaticList` to lookup the city from the code and viceversa.
However, you could use your own class to change the strategy used.

You just need to implement the `CityDecoderInterface` and its `getList()` method.
Then, to use it, just pass an instance to the `CodiceFiscale` class.

For example:

```php
class MyCityList implements CityDecoderInterface
{
  public function getList()
  {
    // Implementation
  }
}
```

```php
...
$cf = new CodiceFiscale(new MyCityList)
...
```

## Integrate your own cities

_Note_: if you find missing cities, please make a PR!

If you want to integrate the cities list, you can use the `CompositeCitiesList` by merging the results of one of the
decoders provided and a custom decoder.

For example:

```
// conf/codicefiscale.php

return [
  'city-decoder' => '\robertogallea\LaravelCodiceFiscale\CityCodeDecoders\CompositeCitiesList',

  ...
  
  'cities-decoder-list' => [
        '\robertogallea\LaravelCodiceFiscale\CityCodeDecoders\InternationalCitiesStaticList',
        'YourNamespace\MyCustomList',
    ]
```

where `MyCustomList` is defined as:

```
...

class MyCustomList implements CityDecoderInterface
{
  public function getList()
  {
    return [
      'XYZ1' => 'My city 1',
      'XYZ2' => 'My city 2',
    ]
  }
}
```

[ico-author]: https://img.shields.io/static/v1?label=author&message=robgallea&color=50ABF1&logo=twitter&style=flat-square

[ico-release]: https://img.shields.io/github/v/release/robertogallea/laravel-codicefiscale

[ico-downloads]: https://img.shields.io/packagist/dt/robertogallea/laravel-codicefiscale

[ico-laravel]: https://img.shields.io/static/v1?label=laravel&message=%E2%89%A56.0&color=ff2d20&logo=laravel&style=flat-square

[ico-sponsor]: https://img.shields.io/static/v1?label=Sponsor&message=%E2%9D%A4&logo=GitHub&link=https://github.com/sponsors/robertogallea

[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square

[ico-styleci]: https://styleci.io/repos/177130582/shield

[link-author]: https://twitter.com/robgallea

[link-release]: https://github.com/robertogallea/laravel-codicefiscale

[link-downloads]: https://packagist.org/packages/robertogallea/laravel-codicefiscale

[link-laravel]: https://laravel.com

[link-sponsor]: https://github.com/sponsors/robertogallea

[link-styleci]: https://styleci.io/repos/17713058s2/
