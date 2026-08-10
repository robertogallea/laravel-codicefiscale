# laravel-codicefiscale

Parses, generates, and validates the Italian `CodiceFiscale` (tax code). A framework-agnostic domain core (`Robertogallea\CodiceFiscale`) plus a Laravel integration layer (`Robertogallea\CodiceFiscale\Laravel`) - a validation rule, an Eloquent cast, a Faker provider, and an artisan command backed by real Italian government birthplace data.

This package ships two Boost skills - activate whichever fits the task:

- `using-laravel-codicefiscale` - generating, parsing, validating, or matching a codice fiscale; working with the birthplace domain; using the Laravel-specific integrations.
- `upgrading-laravel-codicefiscale-from-v2` - migrating application code from the 2.x API to 3.x.
