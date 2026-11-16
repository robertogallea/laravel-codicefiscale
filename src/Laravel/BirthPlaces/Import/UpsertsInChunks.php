<?php

namespace Robertogallea\CodiceFiscale\Laravel\BirthPlaces\Import;

use Illuminate\Database\Eloquent\Model;

/**
 * Shared "map each row to a record, then upsert in chunks" shape
 * the importers otherwise duplicated verbatim - each source's own
 * row shape and field mapping stays separate (toRecord()),
 * only the chunk/upsert mechanics are shared here.
 */
trait UpsertsInChunks
{
    /**
     * @template TRow
     *
     * @param  list<TRow>  $rows
     * @param  callable(TRow): array<string, string|null>  $toRecord
     * @param  class-string<Model>  $modelClass
     * @param  non-empty-list<non-empty-string>  $uniqueBy
     * @param  list<string>  $update
     * @param  positive-int  $chunkSize
     */
    private function upsertInChunks(
        array $rows,
        callable $toRecord,
        string $modelClass,
        array $uniqueBy,
        array $update,
        int $chunkSize,
    ): int {
        $count = 0;

        foreach (array_chunk($rows, $chunkSize) as $chunk) {
            $records = array_map($toRecord, $chunk);

            // toBase() rather than the Eloquent-magic-forwarded
            // upsert(): plain PHPStan (no Larastan) can't resolve
            // __callStatic-forwarded methods, and toBase() gives the
            // real, directly-typed query builder method instead.
            $modelClass::query()->toBase()->upsert($records, $uniqueBy, $update);

            $count += count($records);
        }

        return $count;
    }
}
