---
name: upgrading-laravel-codicefiscale-from-v2
description: "Use this skill when migrating application code from laravel-codicefiscale 2.x to 3.x - e.g. the user asks to upgrade the package, mentions the old robertogallea\\LaravelCodiceFiscale namespace, or existing code calls 2.x methods like ->parse(), ->getFirstName()/->getLastName(), ->getError(), or CodiceFiscale::generate(). Not for everyday 3.x usage on an already-migrated codebase - use the using-laravel-codicefiscale skill for that."
---

# Upgrading laravel-codicefiscale from 2.x to 3.0

Full call-by-call migration tables: `UPGRADE.md` at this package's root. This skill orients you before you open it - it does not replace the tables.

## Why this is a clean break, not a shim

3.0 replaces 2.x's single mutable `CodiceFiscale` class (which mixed parsing state, validation, decoding, and person-matching together) with an immutable value object plus separate `Parser`/`Generator`/`Validator`/`Matcher` services. There is **no compatibility layer** - nothing from `robertogallea\LaravelCodiceFiscale\*` survives unchanged. Do not try to bridge the two APIs; migrate call sites to their 3.x equivalents directly, using `UPGRADE.md`'s tables.

## What changed, at a glance

- **Namespace**: `robertogallea\LaravelCodiceFiscale\*` → `Robertogallea\CodiceFiscale\*` (framework-agnostic core) / `Robertogallea\CodiceFiscale\Laravel\*` (Laravel-specific).
- **Construction**: `new CodiceFiscale()` + `->parse($cf)` → `CodiceFiscale::from($cf)` / `::tryFrom($cf)`.
- **Generation**: `CodiceFiscale::generate($firstName, $lastName, $birthDate, $place, $gender, $config?): string` (loose strings) → `(new Generator())->generate(new Person(...)): CodiceFiscale` (a `Person` DTO with a real `DateTimeImmutable`, `BirthPlaceCode`, and `Gender` enum).
- **Validation**: `->isValid()`/`->getError()` (mixed structural/checksum/semantic, exception-based) → `Validator::validate($cf)->valid()`/`->errors(): list<ValidationError>` (tiered, enum-based, not exceptions).
- **Decoded names**: `->getFirstName()`/`->getLastName()` never recovered a real name in 2.x either - they returned 3-character encoded fragments. 3.x keeps that behavior but renames them honestly to `ParsedCodiceFiscale::nameCode()`/`->surnameCode()`. Do not treat this as new data loss.
- **Birthplaces**: the 2.x bundled static city list is gone (`docs/adr/0003-no-bundled-birthplace-data.md`). 3.x sources real ANPR/MAECI data via a **new required one-time step**: `php artisan codice-fiscale:update-places`. Semantic validation and birthplace lookups return "unknown" until this has run.
- **Person-matching**: 2.x folded person-mismatch codes (`WRONG_FIRST_NAME`, `NO_MATCH`, etc.) into the same exception as validation. 3.x's `Matcher` is a fully separate service from `Validator` - a mismatch is never a validation error.

## Migration checklist for a call site

1. Identify which 2.x concern the code is exercising - construction/parsing, validation, generation, birthplace decoding, or person-matching - since each maps to a different 3.x class.
2. Look up the exact call in `UPGRADE.md`'s tables (organized by the same concerns) rather than guessing at the 3.x equivalent from the name alone - several methods changed return type or nullability, not just name.
3. If the code is on the birthplace/city-decoding path, confirm the target app has run `codice-fiscale:update-places` - this is new, easy to miss, and a common source of "everything returns unknown" after an otherwise-correct migration.
4. Flag, don't silently drop, any reliance on `CodiceFiscaleConfig`'s date-format or gender-label options - both are removed entirely in 3.x (dates are always `DateTimeImmutable`, gender is always the `Gender` enum).
