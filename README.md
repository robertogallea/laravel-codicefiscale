![Laravel Codice Fiscale](https://banners.beyondco.de/Laravel%20Codice%20Fiscale.png?theme=light&packageManager=composer+require&packageName=robertogallea%2Flaravel-codicefiscale&pattern=charlieBrown&style=style_1&description=Codice+fiscale+validation+and+parsing+is+a+breeze&md=1&showWatermark=0&fontSize=100px&images=identification&widths=200&heights=auto)

# laravel-codicefiscale

[![Author][ico-author]][link-author]
[![Latest Version on Packagist](https://img.shields.io/packagist/v/robertogallea/laravel-codicefiscale.svg?style=flat-square)](https://packagist.org/packages/robertogallea/laravel-codicefiscale)
[![Software License][ico-license]](LICENSE.md)
[![Sponsor me!][ico-sponsor]][link-sponsor]
[![Packagist Downloads][ico-downloads]][link-downloads]

laravel-codicefiscale is a package for parsing, generating, and validating the Italian `CodiceFiscale` (tax code). 3.0 is a ground-up rewrite: a small, framework-agnostic domain core (`Robertogallea\CodiceFiscale`) with immutable value objects and single-purpose services, plus a Laravel integration layer (`Robertogallea\CodiceFiscale\Laravel`) - a validation rule, an Eloquent cast, a Faker provider, and an artisan command backed by real Italian government data.

> **Upgrading from 2.x?** 3.0 is an intentional, clean break with no compatibility shims. See [UPGRADE.md](UPGRADE.md) for a complete call-by-call migration table.

## Requirements

- PHP ^8.2
- Laravel (`illuminate/database`, `illuminate/support`) ^12.0 or ^13.0

## Table of contents

- [Setup](#setup)
- [Core domain](#core-domain)
  - [`CodiceFiscale`](#codicefiscale)
  - [Generation](#generation)
  - [Customizing generation and parsing](#customizing-generation-and-parsing)
  - [Parsing](#parsing)
  - [Validation](#validation)
  - [Matching against a person](#matching-against-a-person)
  - [Omocodia](#omocodia)
- [Birthplace domain](#birthplace-domain)
- [Laravel integration](#laravel-integration)
  - [Validation rule](#validation-rule)
  - [Eloquent cast](#eloquent-cast)
  - [Faker provider](#faker-provider)
  - [`codice-fiscale:update-places`](#codice-fiscaleupdate-places)

## Setup

```bash
composer require robertogallea/laravel-codicefiscale:^3.0
```

The service provider is auto-discovered - no manual registration needed.

Publish the config file if you want to customize it:

```bash
php artisan vendor:publish --provider="Robertogallea\CodiceFiscale\Laravel\CodiceFiscaleServiceProvider" --tag="config"
```

```php
// config/codicefiscale.php

return [
    'database' => [
        // Path to the dedicated SQLite database backing the
        // birthplace repository. Never your application's own
        // database - installing/using this package doesn't touch it.
        'path' => storage_path('app/codicefiscale/places.sqlite'),
    ],

    // Sources for `codice-fiscale:update-places`. Only ever fetched
    // when that command runs, never during normal validation.
    'sources' => [
        'municipalities' => 'https://www.anagrafenazionale.interno.it/wp-content/uploads/ANPR_archivio_comuni.csv',
        'countries' => 'https://www.anagrafenazionale.interno.it/wp-content/uploads/tabella_2_statiesteri.xlsx',
    ],
];
```

Birthplace reference data (Italian municipalities and foreign countries) is never bundled with the package - nobody has verified the redistribution terms of the government datasets it comes from, so bundling a converted copy would mean redistributing it under this package's own unverified authority. Instead, each installation downloads its own copy:

```bash
php artisan codice-fiscale:update-places
```

This populates the dedicated SQLite database configured above via a service-provider-managed migration and connection - it never touches your application's own database or migration bookkeeping. Run it once after installing, and again whenever you want fresher data. **Semantic validation and birthplace lookups return "unknown" until this has been run at least once.**

Update just one dataset at a time if you don't need both refreshed:

```bash
php artisan codice-fiscale:update-places --municipalities-only
php artisan codice-fiscale:update-places --countries-only
```

## Core domain

Everything in this section lives under `Robertogallea\CodiceFiscale` and has no Laravel dependency - it works identically outside a Laravel application.

### `CodiceFiscale`

An immutable value object. It can only ever be constructed already structurally valid - there's no way to hold a malformed one:

```php
use Robertogallea\CodiceFiscale\CodiceFiscale;
use Robertogallea\CodiceFiscale\Exceptions\InvalidCodiceFiscaleException;

$cf = CodiceFiscale::from('RSSMRA85D15H501T'); // throws InvalidCodiceFiscaleException if malformed
$cf = CodiceFiscale::tryFrom('RSSMRA85D15H501T'); // returns null instead of throwing

$cf->value(); // 'RSSMRA85D15H501T'
(string) $cf; // same - CodiceFiscale implements Stringable
```

`from()`/`tryFrom()` only check *structure* (length, character classes, valid-shaped fields) - not checksum or semantic correctness. See [Validation](#validation) for the rest.

### Generation

`Generator` takes a `Person` and produces a `CodiceFiscale`:

```php
use Robertogallea\CodiceFiscale\Data\BirthPlaceCode;
use Robertogallea\CodiceFiscale\Data\Person;
use Robertogallea\CodiceFiscale\Enums\Gender;
use Robertogallea\CodiceFiscale\Generation\Generator;

$person = new Person(
    firstName: 'Mario',
    lastName: 'Rossi',
    birthDate: new DateTimeImmutable('1985-04-15'),
    birthPlace: BirthPlaceCode::from('H501'), // Roma
    gender: Gender::Male,
);

$cf = (new Generator())->generate($person);

$cf->value(); // 'RSSMRA85D15H501T'
```

Name normalization (accents, apostrophes, mixed case, extra whitespace) happens automatically inside `Generator` - pass names exactly as the person typed them.

### Customizing generation and parsing

`Generator` composes four internal, independently-tested services - `NameEncoder`, `DateEncoder`, `BirthPlaceEncoder`, `Checksum` - plus a swappable `Contracts\NameNormalizer` (default `ItalianNameNormalizer`, handling Latin-script accents/apostrophes/whitespace), all as constructor parameters with defaults. Supply your own `NameNormalizer` if you need different text-cleanup behavior (e.g. broader multi-script transliteration):

```php
use Robertogallea\CodiceFiscale\Contracts\NameNormalizer;

final class MyNormalizer implements NameNormalizer
{
    public function normalize(string $name): string { /* ... */ }
}

$cf = (new Generator(nameNormalizer: new MyNormalizer()))->generate($person);
```

Similarly, `Parser` resolves a codice fiscale's ambiguous two-digit birth year via a swappable `Contracts\CenturyResolver` (default `AgeBasedCenturyResolver(maxAge: 120)`, preferring the youngest plausible reading). Supply your own when you have domain-specific knowledge the default can't have:

```php
use Robertogallea\CodiceFiscale\Contracts\CenturyResolver;

final class Post1970Resolver implements CenturyResolver
{
    // e.g. "this system only ever has customers born after 1970"
    public function resolve(array $possibleYears): int { /* ... */ }
}

$parser = new Parser(app(BirthPlaceRepository::class), centuryResolver: new Post1970Resolver());
```

### Parsing

`Parser` decodes a `CodiceFiscale` back into its constituent parts. It needs a `Contracts\BirthPlaceRepository` to resolve birthplace codes to real records - in a Laravel app, resolve the one the service provider already binds:

```php
use Robertogallea\CodiceFiscale\Contracts\BirthPlaceRepository;
use Robertogallea\CodiceFiscale\Parsing\Parser;

$parser = new Parser(app(BirthPlaceRepository::class));
$parsed = $parser->parse($cf);

$parsed->surnameCode();    // 'RSS' - a 3-character encoded fragment, NOT the real surname
$parsed->nameCode();       // 'MRA' - same caveat
$parsed->gender();         // Gender::Male
$parsed->birthDate();      // DateTimeImmutable('1985-04-15'), or null if undecodable
$parsed->birthYear();      // 1985
$parsed->birthMonth();     // 4
$parsed->birthDay();       // 15
$parsed->birthPlaceCode(); // BirthPlaceCode('H501')
$parsed->birthPlace();     // ?BirthPlace - null if the code isn't recognized, or update-places hasn't run
$parsed->isOmocodia();     // false
```

There is no way to recover a person's real first/last name from a codice fiscale - `surnameCode()`/`nameCode()` are the same 3-character encoded fragments the algorithm itself works with, just honestly named (2.x's `getFirstName()`/`getLastName()` implied otherwise).

Two-digit birth years are inherently ambiguous (`85` could mean 1885 or 1985); `Parser` resolves this automatically via `AgeBasedCenturyResolver` (preferring the youngest plausible reading, under a configurable `maxAge`), while still exposing `$parsed->possibleBirthYears(): array{int, int}` for callers who need to see or control the ambiguity themselves.

### Validation

`Validator` checks a raw string in independently-callable tiers, and never accepts a `Person` - "is this a valid codice fiscale" and "does this codice fiscale belong to this person" are deliberately separate concerns (see [Matching](#matching-against-a-person)):

```php
use Robertogallea\CodiceFiscale\Validation\Validator;

$validator = new Validator(app(BirthPlaceRepository::class));

$result = $validator->validate('RSSMRA85D15H501T');
$result->valid();  // true
$result->errors(); // []

$result = $validator->validate('not-a-real-code');
$result->valid();  // false
$result->errors(); // [ValidationError::InvalidFormat]
```

`validate()` runs format as a gate (a malformed string can't safely be sliced further); once format passes, checksum and semantics run independently and both contribute to the same result, so a caller sees everything wrong with the input in one pass:

```php
$validator->validateFormat('RSSMRA85D15H501T');   // structural only
$validator->validateChecksum($cf);                // needs a real CodiceFiscale, not a raw string
$validator->validateSemantics($cf);                // valid calendar date + recognized birthplace + valid on that date
```

`ValidationError` is a backed enum: `InvalidFormat`, `InvalidChecksum`, `InvalidDate`, `UnknownBirthPlace`, `BirthPlaceNotValidOnDate` - not exceptions. Exceptions are reserved for genuine API misuse (e.g. `CodiceFiscale::from()` on malformed input), not expected validation failures.

### Matching against a person

`Matcher` cross-checks a `CodiceFiscale` against a `Person` (all fields required) or a `PartialPerson` (any subset):

```php
use Robertogallea\CodiceFiscale\Data\PartialPerson;
use Robertogallea\CodiceFiscale\Matching\Matcher;

$matcher = new Matcher(new Parser(app(BirthPlaceRepository::class)));

$result = $matcher->match($cf, $person); // the same Person the code was generated from
$result->matches();  // true
$result->skipped();  // [] - every field was checked

$result = $matcher->match($cf, new PartialPerson(firstName: 'Mario', gender: Gender::Female));
$result->matches();    // false
$result->matched();    // [PersonField::FirstName]
$result->mismatched(); // [PersonField::Gender]
$result->skipped();    // [PersonField::LastName, PersonField::BirthDate, PersonField::BirthPlace]
```

`MatchResult` distinguishes three explicit states, important when a match result feeds a compliance decision: **matched**, **mismatched**, and **skipped** (a field the `PartialPerson` simply didn't provide - never silently treated as a pass).

### Omocodia

When a computed codice fiscale collides with one already assigned, the Agenzia delle Entrate resolves it by substituting a subset of 7 fixed digit positions with letters. Any of the 2⁷ = 128 combinations may apply independently:

```php
use Robertogallea\CodiceFiscale\Omocodia\Omocodia;

$omocodia = new Omocodia();

$omocodia->canonical($cf);   // reverses all substitutions back to digits - pure, no repository needed
$omocodia->level($cf);       // 0 - a count of substituted positions (0-7), not an ordinal/unique identifier
$omocodia->variants($cf);    // iterable<CodiceFiscale> - all 128 combinations sharing $cf's underlying data

$variant = CodiceFiscale::from('RSSMRA85D15H50ML'); // one substituted position vs. the canonical form
$variant->isEquivalentTo($cf); // true - same canonical form, so the same person-derived data
```

## Birthplace domain

`Contracts\BirthPlace` is a single time-bounded record: `code()`, `name()`, `validFrom()`, `validTo()` (null if still current), `wasValidOn(DateTimeImmutable)`. `DomesticBirthPlace` (Italian municipality) adds `province()`/`istatCode()`; `ForeignBirthPlace` (country) adds `country(): CountryCode`. A municipality that renamed or changed province produces multiple `BirthPlace` records sharing the same `BirthPlaceCode`, one per era - so a birth date tied to an old municipality identity still resolves correctly.

```php
use Robertogallea\CodiceFiscale\Data\BirthPlaceCode;

$repository = app(BirthPlaceRepository::class);

$place = $repository->find(BirthPlaceCode::from('H501')); // valid today, or null
$place = $repository->find(BirthPlaceCode::from('H501'), new DateTimeImmutable('1900-01-01')); // valid on that date

$repository->existedEver(BirthPlaceCode::from('A999')); // false - distinguishes "never valid" from "valid, wrong date"

$repository->search('roma'); // list<BirthPlace> - case/accent-insensitive substring match, both domestic and foreign
$repository->search('abbadia', new DateTimeImmutable('1950-01-01')); // only era-records valid on that date
$repository->search('san', limit: 10); // most-recent-era-first, capped at 10
```

`search()` resolves a name a person actually typed to the `BirthPlaceCode`(s) it could mean - useful for building a "pick your birthplace" UI without requiring the code up front. It's unfiltered by validity unless `$on` is given, since a renamed municipality's old name should still be found; results across every matching era are returned, most-recently-valid first. It never feeds back into generation - `Person::$birthPlace` still takes a `BirthPlaceCode`, not a name, so callers resolve ambiguity themselves before generating.

`BirthPlaceCode::isForeign(): bool` tells domestic (Italian) codes apart from `Z`-prefixed foreign ones; `BirthPlaceCode::equals(BirthPlaceCode $other): bool` compares two codes by value. `CountryCode` (an ISO 3166-1 alpha-3 string, e.g. `'USA'`) works the same way - `CountryCode::from()`/`tryFrom()` construct it, `equals()` compares it; it's a value object rather than a PHP enum since ~200 countries would make an enum unmaintainable.

The default `BirthPlaceRepository` binding is `Laravel\BirthPlaces\CompositeBirthPlaceRepository`, which routes to an Eloquent-backed repository per kind - populated by [`codice-fiscale:update-places`](#codice-fiscaleupdate-places).

## Laravel integration

Everything in this section lives under `Robertogallea\CodiceFiscale\Laravel`.

### Validation rule

The `codice_fiscale` string rule checks format/checksum/semantics only:

```php
public function rules(): array
{
    return [
        'fiscal_code' => 'codice_fiscale',
    ];
}
```

For cross-checking against other request fields, use the fluent `CodiceFiscaleRule`, naming the *other fields* to check against - not the values themselves:

```php
use Robertogallea\CodiceFiscale\Laravel\Rules\CodiceFiscaleRule;

public function rules(): array
{
    return [
        'fiscal_code' => [CodiceFiscaleRule::make()->matching(
            firstName: 'first_name',
            lastName: 'last_name',
            birthDate: 'birth_date',
            birthPlace: 'birth_place_code',
            gender: 'gender',
        )],
    ];
}
```

Any argument can be omitted - an omitted field, or one absent from the request data, is skipped rather than forced into a mismatch. Validation fails with one message per mismatched field, not just the first.

Both the `codice_fiscale` string rule and `CodiceFiscaleRule` report translated, failure-specific messages - a distinct message per failure reason (bad format, bad checksum, a nonexistent date, an unrecognized birthplace, a birthplace not yet/no longer valid on the encoded date) rather than one generic "invalid" message, plus one `:field`-naming message per mismatched field for `->matching()`. `en` and `it` are bundled under the `codicefiscale` translation namespace; publish and customize them with:

```bash
php artisan vendor:publish --provider="Robertogallea\CodiceFiscale\Laravel\CodiceFiscaleServiceProvider" --tag="lang"
```

### Eloquent cast

`CodiceFiscaleCast` rounds a `fiscal_code`-style attribute to a `CodiceFiscale` value object instead of a raw string:

```php
use Robertogallea\CodiceFiscale\Laravel\Casts\CodiceFiscaleCast;

class Person extends Model
{
    protected function casts(): array
    {
        return [
            'fiscal_code' => CodiceFiscaleCast::class,
        ];
    }
}

$person->fiscal_code; // CodiceFiscale|null

$person->fiscal_code = 'RSSMRA85D15H501T'; // or a CodiceFiscale instance
$person->fiscal_code = 'not-a-real-code'; // throws InvalidCodiceFiscaleException immediately
```

Setting a structurally-invalid value throws immediately (fail-fast at the ORM boundary) - bad data can't silently enter your database through the model layer. Reading a row whose stored value is invalid (legacy data, a seeder, a direct write outside the cast) returns `null` instead of throwing, so pre-existing bad data never makes the model unusable for inspection or cleanup.

### Faker provider

Auto-registered onto Laravel's `Faker\Generator` - no setup needed beyond installing the package:

```php
class PersonFactory extends Factory
{
    public function definition(): array
    {
        return [
            'fiscal_code' => fake()->codiceFiscale(),
        ];
    }
}
```

`codiceFiscale()` takes no parameters - it always generates a fully random, valid `CodiceFiscale` via the real `Person`/`Generator` API, drawing its birthplace from a small, fixed set of ten well-known Italian municipalities bundled directly with the provider (not the full ANPR/MAECI dataset - see `docs/adr/0007-faker-provider-bundles-a-small-fixed-fact-list-not-birthplace-data.md`). It works in a fresh application that has never run `codice-fiscale:update-places`, since generation never touches a database at all.

If you need a code for a *specific* person rather than a random one, don't use the Faker provider - call `Generator` directly, as shown in [Generation](#generation).

### `codice-fiscale:update-places`

Covered in [Setup](#setup). Downloads the ANPR comuni archive and MAECI stati-esteri table via Laravel's `Http` facade and upserts them into the dedicated SQLite database - safe to re-run at any time; existing rows are updated in place rather than duplicated.

[ico-author]: https://img.shields.io/static/v1?label=author&message=robgallea&color=50ABF1&logo=twitter&style=flat-square

[ico-downloads]: https://img.shields.io/packagist/dt/robertogallea/laravel-codicefiscale

[ico-sponsor]: https://img.shields.io/static/v1?label=Sponsor&message=%E2%9D%A4&logo=GitHub&link=https://github.com/sponsors/robertogallea

[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square

[link-author]: https://twitter.com/robgallea

[link-downloads]: https://packagist.org/packages/robertogallea/laravel-codicefiscale

[link-sponsor]: https://github.com/sponsors/robertogallea
