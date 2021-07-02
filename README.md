# laravel-codicefiscale

Laravel-FiscalCode is a package for the management of the Italian <code>CodiceFiscale</code> (i.e. tax number). 
The package allows easy validation and parsing of the CodiceFiscale. It is also suited for Laravel since it provides a 
convenient custom validator for request validation.

## 1. Installation

Run the following command to install the latest applicable version of the package:

```bash
composer require robertogallea/laravel-codicefiscale
```

### 1.1 Laravel

In your app config, add the Service Provider to the `$providers` array *(only for Laravel 5.4 or below)*:

 ```php
'providers' => [
    ...
    robertogallea\LaravelCodiceFiscale\CodiceFiscaleServiceProvider::class,
],
```

In your languages directory, add for each language an extra language entry for the validator:

```php
'codice_fiscale' => [
    'wrong_size' => 'The :attribute has a wrong size',
    'no_code' => 'The :attribute is empty',
    'bad_characters' => 'The :attribute contains bad characters',
    'bad_omocodia_char' => 'The :attribute contains bad omocodia characters',
    'wrong_code' => 'The :attribute is not valid',
],
```

### 1.2 Lumen

In `bootstrap/app.php`, register the Service Provider

```php
$app->register(robertogallea\LaravelCodiceFiscale\CodiceFiscaleServiceProvider::class);
```

## 2. Validation

To validate a codice fiscale, use the `codice_fiscale` keyword in your validation rules array

```php
'codice_fiscale_field'       => 'codice_fiscale',
```

## 3. Utility CodiceFiscale class

A codice fiscale can be wrapped in the `robertogallea\LaravelCodiceFiscale\CodiceFiscale` class to enhance it with 
useful utility methods. 

```php
use robertogallea\LaravelCodiceFiscale\CodiceFiscale;

...

$cf = new CodiceFiscale();
$result = $cf->parse('RSSMRA95E05F205Z');
var_dump($result);
```

produces the following result:

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


in case of error, <code>CodiceFiscale::parse()</code> returns false, and you will find information about the error using 
<code>CodiceFiscale::getError()</code>, which returns one of the defined constants among the following:<br>

- `CodiceFiscale::NO_ERROR`
- `CodiceFiscale::NO_CODE`
- `CodiceFiscale::WRONG_SIZE`
- `CodiceFiscale::BAD_CHARACTERS`
- `CodiceFiscale::BAD_OMOCODIA_CHAR`
- `CodiceFiscale::WRONG_CODE`

```php 
use robertogallea\LaravelCodiceFiscale\CodiceFiscale;

...

$cf = new CodiceFiscale();
$result = $cf->parse('RSSMRA95E0');
echo $cf->getError();
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

# City code parsing
There are three strategies for decoding the city code:

- `InternationalCitiesStaticList`: a static list of Italian cities;
- `ItalianCitiesStaticList`: a static list of International cities;
- `IstatRemoteCSVList`: a dynamic (loaded from web) list of Italian cities loaded from official ISTAT csv file. 
  Please note that the list is cached (one day by default, see config to change).

By default, the package uses the class `InternationalCitiesStaticList` to lookup the city from the code and viceversa.
However you could use your own class to change the strategy used.  

You just need to implement the `CityDecoderInterface` and its `getList()` method.
Then, to use it, just pass an istance to the `CodiceFiscale` class.  

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