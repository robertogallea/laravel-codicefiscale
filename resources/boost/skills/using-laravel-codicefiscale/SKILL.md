---
name: using-laravel-codicefiscale
description: "Use this skill when generating, parsing, validating, or matching an Italian codice fiscale, or when working with laravel-codicefiscale's birthplace domain or its Laravel-specific integrations (validation rule, Eloquent cast, Faker provider, codice-fiscale:update-places command). Covers the 3.x API only - for migrating 2.x application code, use the upgrading-laravel-codicefiscale-from-v2 skill instead."
---

# Using laravel-codicefiscale

Full API reference with runnable examples: `README.md` at this package's root. This skill is a map to it, not a replacement for it - read the relevant README section before writing code against an API you haven't confirmed.

## Reach for these, don't hand-roll them

- **`CodiceFiscale::from()`/`::tryFrom()`** - an immutable value object; the only way to hold a codice fiscale. Checks structure only, not checksum/semantics.
- **`Generator`** - builds a `CodiceFiscale` from a `Person` DTO (`Robertogallea\CodiceFiscale\Data\Person`, with a `BirthPlaceCode`, not a name or string).
- **`Parser`** - decodes a `CodiceFiscale` into its parts. `surnameCode()`/`nameCode()` are 3-character *encoded fragments*, never the person's real name - there is no way to recover it. `birthDate()`/`birthYear()` are nullable: the two-digit year is inherently ambiguous between two candidate centuries, resolved via a swappable `Contracts\BirthDateResolver` (default `DefaultBirthDateResolver`); both are `null` when neither candidate is plausible. See README's "Reference-date resolution".
- **`Validator`** - checks format, checksum, and semantics as independently-callable tiers; never accepts a `Person`. Returns a `ValidationResult` with a `ValidationError` enum, not exceptions.
- **`Matcher`** - cross-checks a `CodiceFiscale` against a `Person` or `PartialPerson`. This is the only place a codice fiscale gets compared to a person - `Validator` never does this.
- **`Omocodia`** - `canonical()`, `level()`, `variants()` for the digit/letter substitution scheme used to resolve collisions.
- **Birthplace domain** - `Contracts\BirthPlaceRepository` (`find()`, `existedEver()`, `search()`) backed by real ANPR/MAECI government data via `codice-fiscale:update-places`, not a static list. `search()` resolves a typed name to candidate `BirthPlaceCode`s; it never feeds back into generation.

## Laravel-specific

- `codice_fiscale` validation rule (format/checksum/semantics), or the fluent `CodiceFiscaleRule::make()->matching(...)` to cross-check other request fields.
- `CodiceFiscaleCast` for an Eloquent attribute typed as `CodiceFiscale`.
- `fake()->codiceFiscale()` Faker provider - always a random, valid, fully-generated code; not for a specific person.
- `php artisan codice-fiscale:update-places` - required once before any semantic validation or birthplace lookup will succeed. Never touches the host application's own database.

## Before writing code

1. Confirm whether the target application is actually on 3.x - if you find calls like `->getFirstName()`, `->parse()`, `::generate($string, ...)`, or the `robertogallea\LaravelCodiceFiscale\*` namespace, that's 2.x; switch to the `upgrading-laravel-codicefiscale-from-v2` skill instead of applying 3.x guidance to it.
2. Read the specific README.md section for the API you're about to use - the signatures above are a map, not the full contract.
