<?php

namespace Robertogallea\CodiceFiscale\Laravel\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Robertogallea\CodiceFiscale\CodiceFiscale;
use Robertogallea\CodiceFiscale\Exceptions\InvalidCodiceFiscaleException;

/**
 * Intended usage is CodiceFiscale|string|null in, CodiceFiscale|null
 * out - but TSet is declared as the honestly-permissive `mixed`, not
 * that narrower promise: PHPStan doesn't enforce generics at runtime,
 * and trusting a narrower declaration here previously led to removing
 * a genuine defensive check (a caller can still assign an array/object
 * via mass assignment from untrusted input). set() below validates
 * the real range of what can arrive at runtime itself, rather than
 * relying on a contract nothing actually enforces.
 *
 * @implements CastsAttributes<CodiceFiscale, mixed>
 */
final class CodiceFiscaleCast implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes): ?CodiceFiscale
    {
        // Not just null: a non-string column value (wrong column type,
        // driver quirk) is exactly the same kind of "data this cast
        // doesn't recognize" that tryFrom() already handles for a
        // malformed string - so it gets the same graceful null, not a
        // TypeError from tryFrom() itself.
        if (! is_string($value)) {
            return null;
        }

        // tryFrom(), not from(): pre-existing invalid data (legacy
        // rows, seeders, direct writes) must not make the model
        // unusable to read - null surfaces it instead of throwing.
        return CodiceFiscale::tryFrom($value);
    }

    public function set($model, string $key, $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof CodiceFiscale) {
            return $value->value();
        }

        if (! is_string($value)) {
            throw new InvalidCodiceFiscaleException(
                'fiscal_code casts only accept a string or a CodiceFiscale instance.'
            );
        }

        // from(), not tryFrom(): fail fast on assignment so
        // structurally-invalid data can never enter the database
        // through this cast in the first place.
        return CodiceFiscale::from($value)->value();
    }
}
