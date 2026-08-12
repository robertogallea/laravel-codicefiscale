# Changelog

All notable changes to `laravel-codicefiscale` are documented here. This file starts at 3.0.0 - see the [GitHub releases](https://github.com/robertogallea/laravel-codicefiscale/releases) for the 1.x/2.x history.

## 3.0.1

### Fixed

- `codice-fiscale:update-places` no longer imports municipalities/foreign countries whose ANPR/MAECI source row carries an unparseable code (e.g. ANPR's `"ND"` placeholder on ~12 long-superseded, pre-1928-merger comuni) - such rows are now skipped at import time, and any already-imported invalid row is pruned automatically on the next run. Previously an invalid persisted code crashed `BirthPlaceRepository::search()`/`find()` entirely with an uncaught `InvalidBirthPlaceCodeException`/`InvalidCountryCodeException` the moment it matched; `EloquentBirthPlaceRepository` now also skips such a row defensively rather than throwing, as a second line of defense. ([#115](https://github.com/robertogallea/laravel-codicefiscale/issues/115))

## 3.0.0

3.0 is a ground-up rewrite, and an intentional, clean break from 2.x with no compatibility shims. See [UPGRADE.md](UPGRADE.md) for a complete call-by-call migration table.

### Added

- A small, framework-agnostic domain core (`Robertogallea\CodiceFiscale`) with no Laravel dependency, separate from a `Robertogallea\CodiceFiscale\Laravel` integration layer.
- An immutable `CodiceFiscale` value object, constructed via `from()`/`tryFrom()`, that can only ever hold a structurally valid code.
- `Generator`, taking a `Person` DTO (real `DateTimeImmutable`, `BirthPlaceCode`, `Gender` enum) instead of loosely-typed positional arguments.
- `Parser`, decoding a `CodiceFiscale` into `surnameCode()`/`nameCode()` (honestly named as encoded fragments, not real names), birth date/gender/birthplace, with automatic reference-date resolution of the ambiguous two-digit birth year.
- `Validator`, with independently-callable format/checksum/semantics tiers and a `ValidationResult` carrying every applicable `ValidationError`, instead of one exception at a time.
- `Matcher`, comparing a `CodiceFiscale` against a `Person`/`PartialPerson` and reporting matched/mismatched/skipped fields explicitly.
- `Omocodia`, exposing `canonical()`, `variants()` (all 128 combinations), and `isEquivalentTo()`.
- A `BirthPlace` domain backed by real ANPR (Italian municipalities) and MAECI (foreign countries) government data, with genuine historical validity windows - a renamed or merged municipality now resolves correctly against a birth date from before the change.
- A `codice-fiscale:update-places` artisan command that downloads both datasets into the consuming application's own dedicated SQLite database - never bundled with the package, never touching the host application's primary database.
- `CodiceFiscaleCast` (Eloquent attribute casting), `CodiceFiscaleRule::make()->matching(...)` (fluent cross-field validation), and a Faker provider - all rebuilt on the new core API.
- Restored, localized (`en`/`it`) validation error messages.

### Removed

- The entire 2.x API (`robertogallea\LaravelCodiceFiscale\*`) - no backward-compatibility shims. See [UPGRADE.md](UPGRADE.md).
- Bundled birthplace data - nobody had verified the redistribution terms of the source datasets, so shipping a converted copy meant redistributing it under this package's own unverified authority.

### Note for early adopters

If you were requiring the pre-release `dev-dev` branch directly, switch to `^3.0` now that a tagged release exists.
