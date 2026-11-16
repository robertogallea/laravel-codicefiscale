<?php

namespace Robertogallea\CodiceFiscale\Laravel\BirthPlaces\Import;

use Robertogallea\CodiceFiscale\Laravel\BirthPlaces\Models\Municipality;

/**
 * For a domestic source that supplements a higher-priority one (ISTAT's
 * suppressed comuni behind ANPR, ADR-0015; the bundled retired list
 * behind both, ADR-0012): the higher-priority source wins a code
 * outright, never era by era. Their eras rarely share a valid_from, so
 * the key-based overwrite the foreign sources rely on can't apply.
 * Instead, a code the higher-priority source carries is skipped, and
 * any row of that code whose valid_from isn't one of that source's eras
 * - a row this supplement wrote on an earlier run - is deleted.
 */
trait DefersToHigherPrioritySources
{
    /**
     * @param  list<string>  $codes  the codes this supplement carries
     * @param  array<string, list<string>>  $sourceEras  code => valid_from (Y-m-d) of every era the higher-priority source carries
     */
    private function deleteRowsSupersededBySource(array $codes, array $sourceEras): void
    {
        $contested = array_values(array_filter($codes, static fn (string $code): bool => isset($sourceEras[$code])));

        foreach (array_chunk($contested, 500) as $chunk) {
            // where(closure) rather than whereIn(): plain PHPStan (no
            // Larastan) types the forwarded whereIn() as the base query
            // builder, losing the Municipality model type of get().
            $superseded = Municipality::query()
                ->where(static fn ($query) => $query->whereIn('code', $chunk))
                ->get(['id', 'code', 'valid_from'])
                ->reject(static fn (Municipality $row): bool => in_array($row->valid_from->toDateString(), $sourceEras[$row->code], true))
                ->pluck('id');

            if ($superseded->isNotEmpty()) {
                Municipality::query()->whereIn('id', $superseded)->delete();
            }
        }
    }
}
