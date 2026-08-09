# Upgrading from 2.x to 3.0

3.0 is an intentional, clean break from 2.x — there are no compatibility shims, and nothing from the `robertogallea\LaravelCodiceFiscale` namespace survives unchanged. This document maps every documented 2.x call to its 3.x equivalent. See [README.md](README.md) for the full 3.x API with usage examples, and `docs/adr/0001-clean-break-from-2x-api.md` for why this was done as a major version rather than incremental patches.

## Why the break

2.x's `CodiceFiscale` class mixed together parsing state, validation, birthplace/date/gender decoding, and person-matching in one mutable object — so "is this valid," "what does it decode to," and "does it match this person" couldn't be reasoned about separately. Its `getFirstName()`/`getLastName()` returned 3-character *encoded fragments*, not the person's real name, which was actively misleading. Century resolution for two-digit birth years used a heuristic 2.x itself acknowledged as broken. Birthplace data was a flat, current-day-only static list with no support for renamed/merged Italian municipalities. None of this could be fixed without breaking the documented 2.x API.

## Namespace and package layout

| 2.x | 3.x |
|---|---|
| `robertogallea\LaravelCodiceFiscale\*` (core + Laravel mixed together) | `Robertogallea\CodiceFiscale\*` for the framework-agnostic core; `Robertogallea\CodiceFiscale\Laravel\*` for everything Laravel-specific (service provider, Eloquent repository, validation rule, cast, Faker provider, artisan command). See `docs/adr/0002-framework-agnostic-core-namespace.md`. |
| `robertogallea\LaravelCodiceFiscale\CodiceFiscaleServiceProvider` | `Robertogallea\CodiceFiscale\Laravel\CodiceFiscaleServiceProvider` (auto-discovered via `composer.json`'s `extra.laravel.providers` — manual registration is no longer needed on Laravel 5.5+) |

## One-time setup change

2.x bundled a static city-code list, so it worked with zero setup. 3.0 sources real ANPR (Italian municipalities, with full rename/province-change history) and MAECI (foreign countries) government data instead of bundling a converted copy of it (`docs/adr/0003-no-bundled-birthplace-data.md`) — so **before any semantic validation or birthplace lookup will succeed**, run:

```bash
php artisan codice-fiscale:update-places
```

This downloads both datasets into your own application's dedicated SQLite database (never your primary database — see README's [Setup](README.md#setup) section). There is no 2.x equivalent to this step.

## `CodiceFiscale` class

| 2.x | 3.x | Notes |
|---|---|---|
| `new CodiceFiscale($cityDecoder?, $config?)` then `->parse($cf)` (throws `CodiceFiscaleValidationException`) | `CodiceFiscale::from(string $cf)` (throws `InvalidCodiceFiscaleException`) | Structural validation only — see `Validator` below for checksum/semantics. |
| `->tryParse($cf): bool` | `CodiceFiscale::tryFrom(string $cf): ?CodiceFiscale` | Returns null instead of a boolean; no exception ever thrown. |
| `->isValid(): bool` | `CodiceFiscale::tryFrom($cf) !== null` (structural only) or `Validator::validate($cf)->valid()` (full) | 2.x's `isValid()` mixed structural, checksum, and semantic validity into one boolean; 3.x separates them into named `Validator` tiers. |
| `->getError(): ?\Exception` | `Validator::validate($cf)->errors(): list<ValidationError>` | An enum, not exceptions — exceptions are reserved for API misuse (e.g. `CodiceFiscale::from()` on malformed input), not expected validation failures. |
| `->getCodiceFiscale(): ?string` | `CodiceFiscale::value()` (or `(string) $cf`, since it implements `Stringable`) | |
| `->getGender(): ?string` (`'M'`/`'F'`) | `ParsedCodiceFiscale::gender(): Gender` | `Gender` is a backed enum (`Gender::Male`/`Gender::Female`), not a configurable string label. |
| `->getBirthPlace(): ?string` (4-char code) | `ParsedCodiceFiscale::birthPlaceCode(): BirthPlaceCode` | A value object, not a raw string. `->value()` gets the string back. |
| `->getBirthPlaceComplete(): ?string` (threw `MISSING_CITY_CODE` if unknown) | `ParsedCodiceFiscale::birthPlace(): ?BirthPlace` | Returns null for an unrecognized code instead of throwing; `->name()` for the display name, and for a domestic record `->province()`/`->istatCode()`. Requires `codice-fiscale:update-places` to have populated the repository. |
| `->isInternational(): bool` | `BirthPlaceCode::isForeign(): bool` | |
| `->isItalian(): bool` | `! $birthPlaceCode->isForeign()` | No dedicated method — it was always the logical negation. |
| `->getBirthdate(): Carbon` (threw on invalid) | `ParsedCodiceFiscale::birthDate(): ?DateTimeImmutable` | Nullable instead of throwing for an undecodable date (e.g. a nonexistent calendar day); plain `DateTimeImmutable`, not Carbon — the core package has no Carbon dependency. |
| `->getYear(): ?string`, `->getMonth(): ?string`, `->getDay(): ?string` | `ParsedCodiceFiscale::birthYear(): int`, `->birthMonth(): int`, `->birthDay(): int` | Real integers. `birthDate()` gives you the assembled date directly in most cases. |
| `->getFirstName(): ?string`, `->getLastName(): ?string` | `ParsedCodiceFiscale::nameCode(): string`, `->surnameCode(): string` | **Same 3-character encoded fragments as 2.x — just honestly named.** There has never been a way to recover a person's real first/last name from a codice fiscale; 2.x's method names implied otherwise. |
| `->asArray(): array` | *(no equivalent)* | Read `ParsedCodiceFiscale`'s methods directly, or build your own array from them if you need one. |
| `CodiceFiscale::generate($firstName, $lastName, $birthDate, $place, $gender, $config?): string` (static, primitive strings) | `(new Generator())->generate(new Person(firstName:, lastName:, birthDate:, birthPlace:, gender:)): CodiceFiscale` | Takes a `Person` DTO — a real `DateTimeImmutable`, a `BirthPlaceCode` value object, and a `Gender` enum case, not loosely-typed strings. Returns a `CodiceFiscale`, not a raw string (call `->value()` if you need the string). |

## Exceptions and error codes

2.x's single `CodiceFiscaleValidationException` carried a numeric error code (`NO_CODE`, `WRONG_SIZE`, `BAD_CHARACTERS`, `BAD_OMOCODIA_CHAR`, `WRONG_CODE`, `MISSING_CITY_CODE`, `NO_MATCH`, `EMPTY_BIRTHDATE`, `WRONG_FIRST_NAME`, `WRONG_LAST_NAME`, `WRONG_BIRTH_DAY`, `WRONG_BIRTH_MONTH`, `WRONG_BIRTH_YEAR`, `WRONG_BIRTH_PLACE`, `WRONG_GENDER`) mixing structural, checksum, semantic, *and* person-match failures into one type.

| 2.x | 3.x | Notes |
|---|---|---|
| `WRONG_SIZE`, `BAD_CHARACTERS`, `BAD_OMOCODIA_CHAR` | `ValidationError::InvalidFormat` | From `Validator::validateFormat()`. |
| `WRONG_CODE` | `ValidationError::InvalidChecksum` | From `Validator::validateChecksum()`. |
| `MISSING_CITY_CODE` | `ValidationError::UnknownBirthPlace` or `ValidationError::BirthPlaceNotValidOnDate` | From `Validator::validateSemantics()` — 3.x distinguishes "never a valid code" from "valid code, wrong date". |
| *(no equivalent)* | `ValidationError::InvalidDate` | A structurally/checksum-valid code whose month/day don't form a real calendar date. |
| `NO_MATCH`, `WRONG_FIRST_NAME`, `WRONG_LAST_NAME`, `WRONG_BIRTH_DAY`, `WRONG_BIRTH_MONTH`, `WRONG_BIRTH_YEAR`, `WRONG_BIRTH_PLACE`, `WRONG_GENDER` | `Matcher::match()->mismatched(): list<PersonField>` | Person-match failures are no longer folded into validation at all — `Validator` never accepts a `Person`, and `Matcher` never reports validation errors. See README's [Matching](README.md#matching-against-a-person) section. |
| `NO_CODE`, `NO_ERROR`, `EMPTY_BIRTHDATE` | *(genuine API misuse, not a validation outcome)* | e.g. passing an empty string to `CodiceFiscale::from()` throws `InvalidCodiceFiscaleException` directly, the same way any other malformed input does. |

New, narrower exception types replace the single catch-all: `InvalidCodiceFiscaleException`, `InvalidBirthPlaceCodeException`, `InvalidCountryCodeException` (all `\InvalidArgumentException`), and `CodiceFiscaleGenerationException`.

## City code decoding → BirthPlace domain

| 2.x | 3.x | Notes |
|---|---|---|
| `CityDecoderInterface` (`getList(): array<code, name>`) | `Contracts\BirthPlaceRepository` (`find(BirthPlaceCode, ?DateTimeImmutable $on = null): ?BirthPlace`, `existedEver(BirthPlaceCode): bool`) | A repository with real dates, not a flat lookup table. |
| `InternationalCitiesStaticList`, `ItalianCitiesStaticList` (bundled static lists) | *(no bundled equivalent — see `docs/adr/0003-no-bundled-birthplace-data.md`)* | Real ANPR/MAECI data is downloaded into your own database via `codice-fiscale:update-places` instead. |
| `IstatRemoteCSVList` (dynamic ISTAT CSV fetch, cached) | `codice-fiscale:update-places` artisan command | One command instead of a request-time fetch-and-cache decoder; data lives in your own dedicated SQLite database, refreshed on demand rather than on a TTL. |
| `CompositeCitiesList` (merges two decoders) | `Laravel\BirthPlaces\CompositeBirthPlaceRepository` | Routes domestic (non-`Z`) codes to a Municipality-backed repository and foreign (`Z`-prefixed) codes to a ForeignCountry-backed one — this is the default binding, not an opt-in merge strategy. |
| A city name string (`getBirthPlaceComplete()`) with no history | `DomesticBirthPlace` (name, province, ISTAT code, `[validFrom, validTo)`) / `ForeignBirthPlace` (name, ISO 3166-1 alpha-3 `CountryCode`) | A municipality that renamed or changed province now produces multiple time-bounded `BirthPlace` records sharing the same `BirthPlaceCode` — a birth date tied to an old municipality identity resolves correctly instead of failing. |
| config `city-decoder` | *(removed)* | The default `BirthPlaceRepository` binding is fixed; implement the `BirthPlaceRepository` contract yourself and rebind it in your own service provider if you need a different source. |

## `CodiceFiscaleConfig`

Removed entirely (`getDateFormat()`, `getMaleLabel()`, `getFemaleLabel()`). Dates are always `DateTimeImmutable` (no format string to configure), and gender is always the `Gender` enum (no configurable string labels).

## `config/codicefiscale.php`

| 2.x key | 3.x key | Notes |
|---|---|---|
| `city-decoder` | *(removed)* | |
| `istat-csv-url` | `sources.municipalities` | Now the ANPR comuni-archive URL, not an ISTAT CSV. |
| *(no equivalent)* | `sources.countries` | The MAECI stati-esteri table URL. |
| `cache-duration` | *(removed)* | No request-time caching — data is fetched once per `update-places` run, not per-request. |
| `cities-decoder-list` | *(removed)* | |
| `date-format` | *(removed)* | |
| `labels.male` / `labels.female` | *(removed)* | |
| *(no equivalent)* | `database.path` | Path to the dedicated SQLite database backing the birthplace repository — never your application's own database. |

## Validation rule

| 2.x | 3.x | Notes |
|---|---|---|
| `'field' => 'codice_fiscale'` | `'field' => 'codice_fiscale'` | Unchanged — still format/checksum/semantics only. |
| `'field' => 'codice_fiscale:first_name=first_name_field,last_name=last_name_field,birthdate=birthdate_field,place=place_field,gender=gender_field'` | `'field' => [CodiceFiscaleRule::make()->matching(firstName: 'first_name_field', lastName: 'last_name_field', birthDate: 'birthdate_field', birthPlace: 'place_field', gender: 'gender_field')]` | Named arguments to a fluent builder instead of encoding five field names into one pipe-delimited string. |

## Faker integration

| 2.x | 3.x | Notes |
|---|---|---|
| `fake()->codiceFiscale(firstName: ?, lastName: ?, birthDate: ?, gender: ?, birthPlace: ?)` — every parameter optional, unset ones randomized | `fake()->codiceFiscale(): string` — no parameters | Always fully random, drawing from a small bundled set of well-known municipalities (see README's [Faker provider](README.md#faker-provider) section). If you need a code for a *specific* person, don't use the Faker provider — call `(new Generator())->generate(new Person(...))` directly, exactly what the provider itself does internally. |

## New in 3.x, nothing to migrate

- `CodiceFiscaleCast` — an Eloquent cast so a `fiscal_code`-style model attribute round-trips as a `CodiceFiscale` value object. 2.x had no cast; codice fiscale columns were always raw strings.
- `Omocodia` service (`canonical()`, `variants()`, `level()`) and `CodiceFiscale::isEquivalentTo()` — 2.x decoded omocodia substitutions internally as an implementation detail of `parse()`, with no public API to generate variants or compare two codes for the same underlying person.
